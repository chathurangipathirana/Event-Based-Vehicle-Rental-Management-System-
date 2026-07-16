<?php
$page_title = 'Booking Details';
require_once 'includes/auth.php';
requireAdminLogin();
require_once 'config/database.php';

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($booking_id <= 0) {
    header('Location: bookings.php');
    exit;
}

// Fetch booking details
try {
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            v.name as vehicle_name,
            v.model as vehicle_model,
            v.license_plate as vehicle_plate,
            v.category as vehicle_category,
            u.full_name as client_name,
            u.email as client_email,
            u.phone as client_phone,
            u.company_name,
            u.address as client_address,
            u.city as client_city,
            et.name as event_type_name
        FROM bookings b
        LEFT JOIN vehicles v ON b.vehicle_id = v.id
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN event_types et ON b.event_type_id = et.id
        WHERE b.id = ?
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();
} catch(PDOException $e) {
    $booking = null;
}

if (!$booking) {
    header('Location: bookings.php');
    exit;
}

// Fetch invoice if exists
$invoice = null;
try {
    $stmtInv = $pdo->prepare("SELECT * FROM invoices WHERE booking_id = ?");
    $stmtInv->execute([$booking_id]);
    $invoice = $stmtInv->fetch();
} catch(PDOException $e) {
    // Suppress error
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $new_status = $_POST['status'] ?? '';
        $admin_notes = trim($_POST['admin_notes'] ?? '');
        
        $valid_statuses = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'];
        if (in_array($new_status, $valid_statuses)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE bookings 
                    SET status = ?, admin_notes = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$new_status, $admin_notes, $booking_id]);
                
                // Update vehicle availability status
                if ($booking['vehicle_id']) {
                    if ($new_status === 'completed' || $new_status === 'cancelled') {
                        $stmtVeh = $pdo->prepare("UPDATE vehicles SET status = 'available' WHERE id = ?");
                        $stmtVeh->execute([$booking['vehicle_id']]);
                    } elseif ($new_status === 'confirmed' || $new_status === 'in_progress') {
                        $stmtVeh = $pdo->prepare("UPDATE vehicles SET status = 'booked' WHERE id = ?");
                        $stmtVeh->execute([$booking['vehicle_id']]);
                    }
                }
                
                $_SESSION['message'] = 'Booking status updated successfully!';
            } catch(PDOException $e) {
                $_SESSION['error'] = 'Database error: ' . $e->getMessage();
            }
        }
        header("Location: booking-details.php?id=$booking_id");
        exit();
    } elseif ($action === 'generate_invoice') {
        try {
            $pdo->beginTransaction();
            
            // Check again to prevent double generation
            $stmtCheck = $pdo->prepare("SELECT id FROM invoices WHERE booking_id = ?");
            $stmtCheck->execute([$booking_id]);
            if ($stmtCheck->fetch()) {
                throw new Exception('An invoice has already been generated for this booking.');
            }
            
            $inv_number = 'INV-' . mt_rand(1000, 9999);
            
            // Insert into invoices
            $stmtInv = $pdo->prepare("
                INSERT INTO invoices (invoice_number, booking_id, client_name, client_email, amount, tax, total_amount, status, issue_date, due_date, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), ?)
            ");
            
            $subtotal = $booking['subtotal'] > 0 ? $booking['subtotal'] : $booking['total_amount'];
            $tax = $booking['tax'] > 0 ? $booking['tax'] : 0;
            
            $stmtInv->execute([
                $inv_number,
                $booking_id,
                $booking['client_name'] ?? 'Guest Customer',
                $booking['client_email'] ?? 'guest@customer.com',
                $subtotal,
                $tax,
                $booking['total_amount'],
                'Invoice for booking ' . $booking['booking_number']
            ]);
            
            $invoice_id = $pdo->lastInsertId();
            
            // Insert invoice items
            $stmtItem = $pdo->prepare("
                INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total)
                VALUES (?, ?, 1, ?, ?)
            ");
            $stmtItem->execute([
                $invoice_id,
                'Event Vehicle Rental: ' . ($booking['vehicle_name'] ?? 'Premium Vehicle'),
                $booking['total_amount'],
                $booking['total_amount']
            ]);
            
            // Update booking
            $stmtUp = $pdo->prepare("UPDATE bookings SET invoice_generated = 1 WHERE id = ?");
            $stmtUp->execute([$booking_id]);
            
            $pdo->commit();
            $_SESSION['message'] = "Invoice $inv_number generated successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Failed to generate invoice: ' . $e->getMessage();
        }
        header("Location: booking-details.php?id=$booking_id");
        exit();
    }
}
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<main class="ml-64 min-h-screen bg-slate-50">
    <div class="p-8 max-w-7xl mx-auto">
        <!-- Back Button -->
        <a href="bookings.php" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900 mb-6 font-medium">
            <span class="material-symbols-outlined text-lg">arrow_back</span>
            Back to Bookings
        </a>

        <!-- Alert Banners -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert-success mb-6 p-4 rounded-xl"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-error mb-6 p-4 rounded-xl"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Details Header Card -->
        <div class="bg-white border border-[#c0c8ca]/50 rounded-3xl p-6 shadow-sm mb-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <p class="text-xs uppercase tracking-widest text-gray-500 font-bold mb-1">Booking Reference</p>
                <div class="flex items-center gap-3">
                    <h1 class="text-3xl font-extrabold text-gray-900"><?php echo htmlspecialchars($booking['booking_number']); ?></h1>
                    <?php
                    $status_colors = [
                        'pending' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                        'confirmed' => 'bg-green-100 text-green-700 border-green-200',
                        'in_progress' => 'bg-blue-100 text-blue-700 border-blue-200',
                        'completed' => 'bg-gray-100 text-gray-700 border-gray-200',
                        'cancelled' => 'bg-red-100 text-red-700 border-red-200'
                    ];
                    $color_class = $status_colors[$booking['status']] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                    ?>
                    <span class="px-3 py-1 text-xs font-semibold uppercase tracking-wider rounded-full border <?php echo $color_class; ?>">
                        <?php echo htmlspecialchars($booking['status']); ?>
                    </span>
                </div>
                <p class="text-xs text-gray-400 mt-1">Submitted on <?php echo date('F d, Y \a\t g:i a', strtotime($booking['created_at'])); ?></p>
            </div>
            
            <div class="flex flex-wrap gap-3">
                <?php if (!$booking['invoice_generated'] && !$invoice): ?>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="generate_invoice">
                        <button type="submit" class="bg-cyan-500 hover:bg-cyan-600 text-white font-semibold px-5 py-3 rounded-2xl transition flex items-center gap-2 shadow-sm text-sm">
                            <span class="material-symbols-outlined text-lg">receipt_long</span>
                            Generate Invoice
                        </button>
                    </form>
                <?php elseif ($invoice): ?>
                    <a href="billing.php?search=<?php echo urlencode($invoice['invoice_number']); ?>" class="bg-gray-900 hover:bg-black text-white font-semibold px-5 py-3 rounded-2xl transition flex items-center gap-2 shadow-sm text-sm">
                        <span class="material-symbols-outlined text-lg">payments</span>
                        View Invoice (<?php echo htmlspecialchars($invoice['invoice_number']); ?>)
                    </a>
                    <a href="../user-site/public/invoice-print.php?number=<?php echo urlencode($invoice['invoice_number']); ?>" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-3 rounded-2xl transition flex items-center gap-2 shadow-sm text-sm">
                        <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                        Print / PDF
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left 2 Columns: Information Details -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Customer Details Card -->
                <div class="bg-white border border-gray-200 rounded-3xl p-6 shadow-sm">
                    <h3 class="text-xl font-bold text-gray-900 border-b pb-4 mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-gray-400">person</span>
                        Customer Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                        <div>
                            <p class="text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Full Name</p>
                            <p class="text-gray-900 font-medium text-base"><?php echo htmlspecialchars($booking['client_name'] ?? 'N/A'); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Company Name</p>
                            <p class="text-gray-900 font-medium text-base"><?php echo htmlspecialchars($booking['company_name'] ?? 'N/A'); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Email Address</p>
                            <p class="text-gray-900 font-medium text-base"><?php echo htmlspecialchars($booking['client_email'] ?? 'N/A'); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Phone Number</p>
                            <p class="text-gray-900 font-medium text-base"><?php echo htmlspecialchars($booking['client_phone'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Billing Address</p>
                            <p class="text-gray-900 font-medium text-base">
                                <?php 
                                $address_parts = array_filter([$booking['client_address'], $booking['client_city']]);
                                echo htmlspecialchars(implode(', ', $address_parts) ?: 'N/A');
                                ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Event Details Card -->
                <div class="bg-white border border-gray-200 rounded-3xl p-6 shadow-sm">
                    <h3 class="text-xl font-bold text-gray-900 border-b pb-4 mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-gray-400">celebration</span>
                        Event Logistics
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                        <div>
                            <p class="text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Event Name</p>
                            <p class="text-gray-900 font-medium text-base"><?php echo htmlspecialchars($booking['event_name'] ?? 'N/A'); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Event Category</p>
                            <p class="text-gray-900 font-medium text-base"><?php echo htmlspecialchars($booking['event_type_name'] ?? 'N/A'); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Date of Event</p>
                            <p class="text-gray-900 font-medium text-base"><?php echo date('l, F d, Y', strtotime($booking['event_date'])); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Service Timing</p>
                            <p class="text-gray-900 font-medium text-base">
                                <?php 
                                $start = date('g:i a', strtotime($booking['start_time']));
                                $end = date('g:i a', strtotime($booking['end_time']));
                                echo "$start to $end";
                                ?>
                            </p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Pickup Location</p>
                            <p class="text-gray-900 font-medium text-base"><?php echo htmlspecialchars($booking['pickup_location'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-xs text-gray-400 font-semibold mb-1 uppercase tracking-wider">Drop-off Location</p>
                            <p class="text-gray-900 font-medium text-base"><?php echo htmlspecialchars($booking['dropoff_location'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Special Requests -->
                <div class="bg-white border border-gray-200 rounded-3xl p-6 shadow-sm">
                    <h3 class="text-xl font-bold text-gray-900 border-b pb-4 mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-gray-400">notes</span>
                        Customer Special Requests
                    </h3>
                    <p class="text-gray-700 text-sm leading-relaxed whitespace-pre-line"><?php echo htmlspecialchars($booking['special_requests'] ?: 'No special instructions or requests provided by the customer.'); ?></p>
                </div>
            </div>

            <!-- Right 1 Column: Vehicle, Invoice, and Status Control -->
            <div class="space-y-8">
                <!-- Vehicle Details -->
                <div class="bg-white border border-gray-200 rounded-3xl p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-900 border-b pb-4 mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-gray-400">directions_car</span>
                        Assigned Vehicle
                    </h3>
                    <?php if ($booking['vehicle_id']): ?>
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Vehicle Name</p>
                                <p class="text-gray-900 font-bold text-base"><?php echo htmlspecialchars($booking['vehicle_name']); ?></p>
                            </div>
                            <div class="grid grid-cols-2 gap-4 text-sm pt-2">
                                <div>
                                    <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">License Plate</p>
                                    <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($booking['vehicle_plate'] ?: 'N/A'); ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Category</p>
                                    <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($booking['vehicle_category'] ?: 'N/A'); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 text-sm italic">No vehicle assigned to this booking.</p>
                    <?php endif; ?>
                </div>

                <!-- Price and Cost Summary -->
                <div class="bg-white border border-gray-200 rounded-3xl p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-900 border-b pb-4 mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-gray-400">payments</span>
                        Pricing Details
                    </h3>
                    <div class="space-y-4 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Rental Duration</span>
                            <span class="font-medium text-gray-900">
                                <?php 
                                if ($booking['total_days'] > 0) {
                                    echo $booking['total_days'] . ' Day(s)';
                                } else {
                                    echo ($booking['total_hours'] ?: 0) . ' Hour(s)';
                                }
                                ?>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Subtotal</span>
                            <span class="font-medium text-gray-900">LKR <?php echo number_format($booking['subtotal'] ?: $booking['total_amount'], 2); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Tax</span>
                            <span class="font-medium text-gray-900">LKR <?php echo number_format($booking['tax'] ?: 0, 2); ?></span>
                        </div>
                        <div class="border-t pt-3 flex justify-between items-center">
                            <span class="text-base font-bold text-gray-900">Total Amount</span>
                            <span class="text-xl font-extrabold text-cyan-600">LKR <?php echo number_format($booking['total_amount'], 2); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Update Status Form -->
                <div class="bg-white border border-gray-200 rounded-3xl p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-900 border-b pb-4 mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-gray-400">edit_note</span>
                        Manage & Status Notes
                    </h3>
                    <form method="POST" action="" class="space-y-4">
                        <input type="hidden" name="action" value="update_status">
                        <div>
                            <label class="block text-xs uppercase tracking-wider font-semibold text-gray-400 mb-2">Booking Status</label>
                            <select name="status" class="w-full px-3 py-2 border rounded-lg focus:ring-cyan-500 focus:border-cyan-500 bg-white">
                                <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>Pending Approval</option>
                                <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="in_progress" <?php echo $booking['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress (Dispatched)</option>
                                <option value="completed" <?php echo $booking['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs uppercase tracking-wider font-semibold text-gray-400 mb-2">Admin Notes</label>
                            <textarea name="admin_notes" rows="4" placeholder="Enter notes visible to administrators only..." class="w-full px-3 py-2 border rounded-lg focus:ring-cyan-500 focus:border-cyan-500 text-sm bg-white"><?php echo htmlspecialchars($booking['admin_notes'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="w-full bg-cyan-500 hover:bg-cyan-600 text-white font-semibold py-3 rounded-xl transition shadow-sm text-sm">
                            Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
