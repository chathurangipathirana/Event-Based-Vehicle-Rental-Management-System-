<?php
$page_title = 'Booking Details';
require_once 'includes/auth.php';
requireAdminLogin();
require_once 'config/database.php';
require_once 'includes/invoice-mailer.php';

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
            d.name as driver_name,
            d.phone as driver_phone,
            u.full_name as client_name,
            u.email as client_email,
            u.phone as client_phone,
            u.company_name,
            u.address as client_address,
            u.city as client_city,
            et.name as event_type_name
        FROM bookings b
        LEFT JOIN vehicles v ON b.vehicle_id = v.id
        LEFT JOIN drivers d ON b.driver_id = d.id
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

// Decode extras and check driver requirement
$extras = null;
$driver_requested = false;
$special_requests_text = '';

if (!empty($booking['special_requests'])) {
    $decoded = json_decode($booking['special_requests'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $extras = $decoded;
        $driver_requested = isset($extras['professional_driver']) && $extras['professional_driver'] === true;
        
        $special_requests_text = "Extras Selected:\n";
        $special_requests_text .= "• Professional Driver: " . ($driver_requested ? "Yes" : "No") . "\n";
        $special_requests_text .= "• Decorations: " . ((isset($extras['decorations']) && $extras['decorations']) ? "Yes" : "No") . "\n";
        $special_requests_text .= "• Extra Hours: " . (isset($extras['extra_hours']) ? $extras['extra_hours'] : 0) . " hr(s)";
    } else {
        $special_requests_text = $booking['special_requests'];
    }
} else {
    $special_requests_text = 'No special instructions or requests provided by the customer.';
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

// Fetch drivers for assignment (available drivers OR the one currently assigned to this booking)
$drivers = [];
try {
    $stmtDrv = $pdo->prepare("
        SELECT id, name, status 
        FROM drivers 
        WHERE status = 'available' OR id = ? 
        ORDER BY name
    ");
    $stmtDrv->execute([$booking['driver_id']]);
    $drivers = $stmtDrv->fetchAll();
} catch(PDOException $e) {
    // Suppress error
}

// Fetch available vehicles (available OR currently assigned)
$available_vehicles = [];
try {
    $stmtVeh = $pdo->prepare("
        SELECT id, name, model 
        FROM vehicles 
        WHERE status = 'available' OR id = ?
        ORDER BY name ASC
    ");
    $stmtVeh->execute([$booking['vehicle_id']]);
    $available_vehicles = $stmtVeh->fetchAll();
} catch(PDOException $e) {
    // Suppress error
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $new_status = $_POST['status'] ?? '';
        if ($new_status === 'rejected') {
            $new_status = 'cancelled';
        }
        $admin_notes = trim($_POST['admin_notes'] ?? '');
        $driver_id = !empty($_POST['driver_id']) ? (int)$_POST['driver_id'] : null;
        $vehicle_id = !empty($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : null;
        $notify_driver_email = isset($_POST['notify_driver_email']);
        $notify_driver_sms = isset($_POST['notify_driver_sms']);
        
        $valid_statuses = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'];
        if (in_array($new_status, $valid_statuses)) {
            try {
                // Fetch previous assignments before update
                $old_vehicle_id = $booking['vehicle_id'];
                $old_driver_id = $booking['driver_id'];

                $stmt = $pdo->prepare("
                    UPDATE bookings 
                    SET status = ?, admin_notes = ?, driver_id = ?, vehicle_id = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$new_status, $admin_notes, $driver_id, $vehicle_id, $booking_id]);

                $driverAssignmentChanged = $driver_id && (int) $old_driver_id !== $driver_id;
                
                // Update vehicle availability status
                if ($new_status === 'completed' || $new_status === 'cancelled') {
                    if ($old_vehicle_id) {
                        $pdo->prepare("UPDATE vehicles SET status = 'available' WHERE id = ?")->execute([$old_vehicle_id]);
                    }
                } elseif ($new_status === 'confirmed' || $new_status === 'in_progress') {
                    if ($old_vehicle_id && $old_vehicle_id != $vehicle_id) {
                        $pdo->prepare("UPDATE vehicles SET status = 'available' WHERE id = ?")->execute([$old_vehicle_id]);
                    }
                    if ($vehicle_id) {
                        $pdo->prepare("UPDATE vehicles SET status = 'booked' WHERE id = ?")->execute([$vehicle_id]);
                    }
                }

                // Update driver status
                if ($new_status === 'completed' || $new_status === 'cancelled') {
                    if ($old_driver_id) {
                        $pdo->prepare("UPDATE drivers SET status = 'available' WHERE id = ?")->execute([$old_driver_id]);
                    }
                } elseif ($new_status === 'confirmed' || $new_status === 'in_progress') {
                    if ($old_driver_id && $old_driver_id != $driver_id) {
                        $pdo->prepare("UPDATE drivers SET status = 'available' WHERE id = ?")->execute([$old_driver_id]);
                    }
                    if ($driver_id) {
                        $pdo->prepare("UPDATE drivers SET status = 'on_duty' WHERE id = ?")->execute([$driver_id]);
                    }
                }

                if ($new_status === 'confirmed' && $booking['status'] !== 'confirmed') {
                    $invoice = getOrCreateBookingInvoice($pdo, $booking_id);
                    $emailResults = sendBookingApprovalNotifications($pdo, $booking_id, $invoice);
                }
                
                $_SESSION['message'] = isset($emailSent)
                    ? ($emailSent ? 'Booking approved and invoice emailed successfully!' : 'Booking approved and invoice created. Email delivery needs mail server configuration.')
                    : 'Booking status and assignments updated successfully!';
                if (isset($driverEmailSent) || isset($driverSmsSent)) {
                    if (isset($driverEmailSent)) {
                        $message .= $driverEmailSent
                            ? ' The assigned driver was notified by email.'
                            : ' The driver was assigned, but email delivery needs mail server configuration or a valid driver email.';
                    }
                    if (isset($driverSmsSent)) {
                        $message .= $driverSmsSent
                            ? ' An SMS assignment alert was also sent.'
                            : ' SMS was not sent; add a valid driver phone number and Twilio configuration to enable it.';
                    }
                }
                $_SESSION['message'] = $message;
            } catch(Throwable $e) {
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
        <div class="max-w-4xl mx-auto space-y-8">
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
                    Customer Special Requests & Extras
                </h3>
                <p class="text-gray-700 text-sm leading-relaxed whitespace-pre-line"><?php echo htmlspecialchars($special_requests_text); ?></p>
            </div>

            <!-- Price and Cost Summary -->
            <div class="bg-white border border-gray-200 rounded-3xl p-6 shadow-sm">
                <h3 class="text-xl font-bold text-gray-900 border-b pb-4 mb-4 flex items-center gap-2">
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

            <!-- Logistics Assignment & Status Decision Form -->
            <div class="bg-white border border-gray-200 rounded-3xl p-6 shadow-sm">
                <h3 class="text-xl font-bold text-gray-900 border-b pb-4 mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-gray-400">assignment_turned_in</span>
                    Logistics & Booking Decision
                </h3>
                <form method="POST" action="" class="space-y-6">
                    <input type="hidden" name="action" value="update_status">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs uppercase tracking-wider font-semibold text-gray-400 mb-2">Assign Vehicle *</label>
                            <select name="vehicle_id" id="assignVehicleSelect" onchange="validateForm()" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 bg-white text-sm text-gray-900" required>
                                <option value="">-- Choose Available Vehicle --</option>
                                <?php foreach ($available_vehicles as $veh): ?>
                                    <option value="<?php echo $veh['id']; ?>" <?php echo $booking['vehicle_id'] == $veh['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($veh['name']); ?> (<?php echo htmlspecialchars($veh['model']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-[10px] text-gray-400 mt-1 italic">Filtered to show only available vehicles matching category: <strong><?php echo htmlspecialchars($booking['vehicle_category']); ?></strong></p>
                        </div>

                        <div>
                            <label class="block text-xs uppercase tracking-wider font-semibold text-gray-400 mb-2">
                                Assign Driver <?php echo $driver_requested ? '*' : '(Optional)'; ?>
                            </label>
                            <select name="driver_id" id="assignDriverSelect" onchange="validateForm()" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 bg-white text-sm text-gray-900" <?php echo $driver_requested ? 'required' : ''; ?>>
                                <option value="">-- Choose Available Driver --</option>
                                <?php foreach ($drivers as $driver): ?>
                                    <option value="<?php echo $driver['id']; ?>" <?php echo $booking['driver_id'] == $driver['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($driver['name']); ?> <?php echo $driver['status'] !== 'available' ? '('.ucfirst($driver['status']).')' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-[10px] text-gray-400 mt-1 italic">
                                <?php echo $driver_requested ? '<span class="text-red-500 font-bold">* Required:</span> Driver was requested in booking.' : 'Optional: Driver was not requested (Self-Drive).'; ?>
                            </p>
                        </div>
                    </div>

                    <fieldset class="rounded-2xl border border-cyan-100 bg-cyan-50/60 p-4">
                        <legend class="px-1 text-xs uppercase tracking-wider font-semibold text-cyan-800">Driver Assignment Notification</legend>
                        <p class="mt-1 text-xs text-gray-600">Choose how to notify the selected driver when this assignment is approved or changed.</p>
                        <div class="mt-3 flex flex-wrap gap-5">
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-800 cursor-pointer">
                                <input type="checkbox" name="notify_driver_email" value="1" checked class="rounded border-gray-300 text-cyan-600 focus:ring-cyan-500">
                                <span class="material-symbols-outlined text-base text-cyan-700">mail</span>
                                Send Email
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-800 cursor-pointer">
                                <input type="checkbox" name="notify_driver_sms" value="1" checked class="rounded border-gray-300 text-cyan-600 focus:ring-cyan-500">
                                <span class="material-symbols-outlined text-base text-cyan-700">sms</span>
                                Send SMS
                            </label>
                        </div>
                    </fieldset>

                    <div>
                        <label class="block text-xs uppercase tracking-wider font-semibold text-gray-400 mb-2">Internal Admin Notes / Remarks</label>
                        <textarea name="admin_notes" rows="3" placeholder="Enter notes visible to administrators only..." class="w-full px-3 py-2.5 border border-gray-200 rounded-xl focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 text-sm bg-white"><?php echo htmlspecialchars($booking['admin_notes'] ?? ''); ?></textarea>
                    </div>

                    <div class="pt-4 border-t border-gray-100 flex flex-col md:flex-row gap-4">
                        <?php if ($booking['status'] === 'pending'): ?>
                            <button type="submit" name="status" value="confirmed" id="approveBtn" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-3.5 rounded-xl transition flex items-center justify-center gap-2 shadow disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-red-600" disabled>
                                <span class="material-symbols-outlined">check_circle</span>
                                Approve & Dispatch
                            </button>
                            <button type="submit" name="status" value="cancelled" formnovalidate class="flex-1 bg-white border border-gray-300 hover:bg-gray-50 text-red-600 font-bold py-3.5 rounded-xl transition flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined">cancel</span>
                                Reject Booking
                            </button>
                        <?php else: ?>
                            <div class="w-full">
                                <label class="block text-xs uppercase tracking-wider font-semibold text-gray-400 mb-2">Update Booking Status</label>
                                <div class="flex gap-4">
                                    <select name="status" class="flex-1 px-3 py-2.5 border border-gray-200 rounded-xl focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 bg-white text-sm text-gray-900">
                                        <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="in_progress" <?php echo $booking['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="completed" <?php echo $booking['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" class="bg-cyan-500 hover:bg-cyan-600 text-white font-semibold px-6 py-3.5 rounded-xl transition flex items-center justify-center gap-2">
                                        <span class="material-symbols-outlined">save</span>
                                        Save Status
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <script>
            function validateForm() {
                const approveBtn = document.getElementById('approveBtn');
                if (!approveBtn) return;
                
                const vehicleSelect = document.getElementById('assignVehicleSelect');
                const driverSelect = document.getElementById('assignDriverSelect');
                const driverRequired = <?php echo $driver_requested ? 'true' : 'false'; ?>;
                
                const vehicleAssigned = vehicleSelect && vehicleSelect.value;
                const driverAssigned = driverSelect && driverSelect.value;
                
                if (vehicleAssigned && (!driverRequired || driverAssigned)) {
                    approveBtn.removeAttribute('disabled');
                } else {
                    approveBtn.setAttribute('disabled', 'true');
                }
            }
            document.addEventListener('DOMContentLoaded', validateForm);
            </script>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
