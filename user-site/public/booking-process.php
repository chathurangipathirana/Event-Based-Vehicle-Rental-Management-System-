<?php
$page_title = 'Complete Your Booking';
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();

$step = $_GET['step'] ?? 1;
$booking_data = $_SESSION['booking_data'] ?? [];

// Step 1: Vehicle and Schedule Selection
if ($step == 1) {
    $package_id = isset($_GET['package_id']) ? (int)$_GET['package_id'] : 0;
    $selected_package = null;
    if ($package_id > 0) {
        $stmtPkg = $pdo->prepare("SELECT * FROM event_packages WHERE id = ?");
        $stmtPkg->execute([$package_id]);
        $selected_package = $stmtPkg->fetch();
    }

    $vehicle_id = $_GET['vehicle'] ?? 0;
    if ($selected_package) {
        // Find a fallback vehicle to fulfill foreign key constraint
        $vehicle_id = $pdo->query("SELECT id FROM vehicles LIMIT 1")->fetchColumn();
    }
    
    $selected_vehicle = getVehicleById($vehicle_id);
    
    if (!$selected_vehicle) {
        header('Location: vehicles.php');
        exit();
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $_SESSION['booking_data'] = [
            'vehicle_id' => $vehicle_id,
            'package_id' => $package_id,
            'event_type_id' => $_POST['event_type_id'],
            'event_name' => $_POST['event_name'] ?: ($selected_package ? $selected_package['name'] : ''),
            'event_date' => $_POST['event_date'],
            'start_time' => $_POST['start_time'],
            'end_time' => $_POST['end_time'],
            'pickup_location' => $_POST['pickup_location'],
            'dropoff_location' => $_POST['dropoff_location']
        ];
        header('Location: booking-process.php?step=2');
        exit();
    }
    
    $eventTypes = $pdo->query("SELECT * FROM event_types WHERE is_active = 1")->fetchAll();
    ?>
    
    <?php require_once '../includes/header.php'; ?>
    <main class="pt-16 min-h-screen bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 py-8">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-red-600 text-white p-6">
                    <h1 class="text-2xl font-bold">Complete Your Booking</h1>
                    <p class="text-red-100">Step 1 of 3: Select Schedule</p>
                </div>
                
                <form method="POST" class="p-8 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium mb-2">Event Type *</label>
                            <select name="event_type_id" required class="w-full px-4 py-2 border rounded-lg focus:ring-red-500">
                                <option value="">Select Event Type</option>
                                <?php foreach ($eventTypes as $event): ?>
                                    <option value="<?php echo $event['id']; ?>"><?php echo htmlspecialchars($event['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Event Name</label>
                            <input type="text" name="event_name" placeholder="e.g., Smith Wedding" class="w-full px-4 py-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Event Date *</label>
                            <input type="date" name="event_date" required min="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Start Time *</label>
                            <input type="time" name="start_time" required class="w-full px-4 py-2 border rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">End Time *</label>
                            <input type="time" name="end_time" required class="w-full px-4 py-2 border rounded-lg">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2">Pickup Location *</label>
                        <input type="text" name="pickup_location" required placeholder="Full address" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2">Dropoff Location *</label>
                        <input type="text" name="dropoff_location" required placeholder="Full address" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    
                    <button type="submit" class="w-full bg-red-600 text-white py-3 rounded-lg font-semibold hover:bg-red-700 transition">
                        Continue to Extras
                    </button>
                </form>
            </div>
        </div>
    </main>
    <?php require_once '../includes/footer.php'; ?>
    
<?php } elseif ($step == 2) {
    // Step 2: Extras Selection
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $_SESSION['booking_data']['extras'] = [
            'professional_driver' => isset($_POST['professional_driver']),
            'decorations' => isset($_POST['decorations']),
            'extra_hours' => intval($_POST['extra_hours'] ?? 0)
        ];
        header('Location: booking-process.php?step=3');
        exit();
    }
    
    $vehicle = getVehicleById($booking_data['vehicle_id']);
    ?>
    
    <?php require_once '../includes/header.php'; ?>
    <main class="pt-16 min-h-screen bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 py-8">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-red-600 text-white p-6">
                    <h1 class="text-2xl font-bold">Customize Your Experience</h1>
                    <p class="text-red-100">Step 2 of 3: Add Extras</p>
                </div>
                
                <form method="POST" class="p-8 space-y-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 border rounded-lg">
                            <div>
                                <h3 class="font-semibold">Professional Driver</h3>
                                <p class="text-sm text-gray-500">Elite chauffeur service</p>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="text-red-600 font-bold">+LKR 120/day</span>
                                <input type="checkbox" name="professional_driver" class="w-5 h-5 text-red-600">
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between p-4 border rounded-lg">
                            <div>
                                <h3 class="font-semibold">Event Decorations</h3>
                                <p class="text-sm text-gray-500">Custom ribbons and floral arrangements</p>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="text-red-600 font-bold">+LKR 75</span>
                                <input type="checkbox" name="decorations" class="w-5 h-5 text-red-600">
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between p-4 border rounded-lg">
                            <div>
                                <h3 class="font-semibold">Extra Hours</h3>
                                <p class="text-sm text-gray-500">Additional time beyond standard</p>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="flex items-center border rounded">
                                    <button type="button" onclick="decrementHours()" class="px-3 py-1 hover:bg-gray-100">-</button>
                                    <input type="number" name="extra_hours" id="extra_hours" value="0" min="0" max="10" class="w-16 text-center border-x">
                                    <button type="button" onclick="incrementHours()" class="px-3 py-1 hover:bg-gray-100">+</button>
                                </div>
                                <span class="text-red-600 font-bold">+LKR <span id="extra_cost">0</span></span>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full bg-red-600 text-white py-3 rounded-lg font-semibold hover:bg-red-700 transition">
                        Continue to Review
                    </button>
                </form>
            </div>
        </div>
    </main>
    
    <script>
        const hourlyRate = <?php echo $vehicle['price_per_hour']; ?>;
        function updateExtraCost() {
            let hours = document.getElementById('extra_hours').value;
            let cost = hours * hourlyRate;
            document.getElementById('extra_cost').innerText = cost;
        }
        function incrementHours() {
            let input = document.getElementById('extra_hours');
            input.value = parseInt(input.value) + 1;
            updateExtraCost();
        }
        function decrementHours() {
            let input = document.getElementById('extra_hours');
            if (input.value > 0) {
                input.value = parseInt(input.value) - 1;
                updateExtraCost();
            }
        }
        document.getElementById('extra_hours').addEventListener('change', updateExtraCost);
    </script>
    <?php require_once '../includes/footer.php'; ?>
    
<?php } elseif ($step == 3) {
    // Step 3: Review and Confirm
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Calculate total
        $vehicle = getVehicleById($booking_data['vehicle_id']);
        
        $package_id = isset($booking_data['package_id']) ? (int)$booking_data['package_id'] : 0;
        $selected_package = null;
        if ($package_id > 0) {
            $stmtPkg = $pdo->prepare("SELECT * FROM event_packages WHERE id = ?");
            $stmtPkg->execute([$package_id]);
            $selected_package = $stmtPkg->fetch();
        }

        // Calculate base cost
        $start = new DateTime($booking_data['event_date'] . ' ' . $booking_data['start_time']);
        $end = new DateTime($booking_data['event_date'] . ' ' . $booking_data['end_time']);
        $interval = $start->diff($end);
        $total_hours = $interval->h + ($interval->days * 24);
        
        if ($selected_package) {
            $subtotal = $selected_package['base_price'];
            $extras_total = 0;
            $tax = $subtotal * 0.10;
            $total_amount = $subtotal + $tax;
        } else {
            $subtotal = $vehicle['price_per_hour'] * $total_hours;
            
            // Add extras
            $extras_total = 0;
            if ($booking_data['extras']['professional_driver']) $extras_total += 120;
            if ($booking_data['extras']['decorations']) $extras_total += 75;
            if ($booking_data['extras']['extra_hours'] > 0) $extras_total += $booking_data['extras']['extra_hours'] * $vehicle['price_per_hour'];
            
            $tax = ($subtotal + $extras_total) * 0.10;
            $total_amount = $subtotal + $extras_total + $tax;
        }
        
        // Create booking
        $booking_number = 'FLT' . date('Ymd') . rand(1000, 9999);
        
        $stmt = $pdo->prepare("INSERT INTO bookings (booking_number, user_id, vehicle_id, event_type_id, event_name, event_date, start_time, end_time, pickup_location, dropoff_location, total_hours, subtotal, tax, total_amount, special_requests, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        
        $stmt->execute([
            $booking_number,
            $_SESSION['user_id'],
            $booking_data['vehicle_id'],
            $booking_data['event_type_id'],
            $booking_data['event_name'],
            $booking_data['event_date'],
            $booking_data['start_time'],
            $booking_data['end_time'],
            $booking_data['pickup_location'],
            $booking_data['dropoff_location'],
            $total_hours + $booking_data['extras']['extra_hours'],
            $subtotal + $extras_total,
            $tax,
            $total_amount,
            json_encode($booking_data['extras'])
        ]);
        
        // Clear session data
        unset($_SESSION['booking_data']);
        
        header('Location: booking-success.php?id=' . $pdo->lastInsertId());
        exit();
    }
    
    $vehicle = getVehicleById($booking_data['vehicle_id']);
    $event_type = getEventTypeById($booking_data['event_type_id']);
    
    $package_id = isset($booking_data['package_id']) ? (int)$booking_data['package_id'] : 0;
    $selected_package = null;
    if ($package_id > 0) {
        $stmtPkg = $pdo->prepare("SELECT * FROM event_packages WHERE id = ?");
        $stmtPkg->execute([$package_id]);
        $selected_package = $stmtPkg->fetch();
    }

    // Calculate costs
    $start = new DateTime($booking_data['event_date'] . ' ' . $booking_data['start_time']);
    $end = new DateTime($booking_data['event_date'] . ' ' . $booking_data['end_time']);
    $interval = $start->diff($end);
    $total_hours = $interval->h + ($interval->days * 24);
    
    if ($selected_package) {
        $subtotal = $selected_package['base_price'];
        $extras_total = 0;
        $tax = $subtotal * 0.10;
        $grand_total = $subtotal + $tax;
    } else {
        $subtotal = $vehicle['price_per_hour'] * $total_hours;
        $extras_total = 0;
        if ($booking_data['extras']['professional_driver']) $extras_total += 120;
        if ($booking_data['extras']['decorations']) $extras_total += 75;
        if ($booking_data['extras']['extra_hours'] > 0) $extras_total += $booking_data['extras']['extra_hours'] * $vehicle['price_per_hour'];
        $tax = ($subtotal + $extras_total) * 0.10;
        $grand_total = $subtotal + $extras_total + $tax;
    }
    ?>
    
    <?php require_once '../includes/header.php'; ?>
    <main class="pt-16 min-h-screen bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 py-8">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-red-600 text-white p-6">
                    <h1 class="text-2xl font-bold">Review Your Booking</h1>
                    <p class="text-red-100">Step 3 of 3: Confirm Details</p>
                </div>
                
                <div class="p-8">
                    <div class="space-y-6">
                        <div class="border-b pb-4">
                            <?php if ($selected_package): ?>
                                <h3 class="font-bold text-lg mb-3">Package Details</h3>
                                <p><strong>Package:</strong> <?php echo htmlspecialchars($selected_package['name']); ?></p>
                                <p><strong>Vehicles Included:</strong> <?php echo htmlspecialchars($selected_package['vehicle_types']); ?></p>
                            <?php else: ?>
                                <h3 class="font-bold text-lg mb-3">Vehicle Details</h3>
                                <p><strong>Vehicle:</strong> <?php echo htmlspecialchars($vehicle['name']); ?></p>
                            <?php endif; ?>
                            <p><strong>Event Type:</strong> <?php echo htmlspecialchars($event_type['name']); ?></p>
                            <p><strong>Event Name:</strong> <?php echo htmlspecialchars($booking_data['event_name'] ?: 'Not specified'); ?></p>
                        </div>
                        
                        <div class="border-b pb-4">
                            <h3 class="font-bold text-lg mb-3">Schedule</h3>
                            <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($booking_data['event_date'])); ?></p>
                            <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($booking_data['start_time'])); ?> - <?php echo date('g:i A', strtotime($booking_data['end_time'])); ?></p>
                            <p><strong>Duration:</strong> <?php echo $total_hours; ?> hours</p>
                        </div>
                        
                        <div class="border-b pb-4">
                            <h3 class="font-bold text-lg mb-3">Locations</h3>
                            <p><strong>Pickup:</strong> <?php echo htmlspecialchars($booking_data['pickup_location']); ?></p>
                            <p><strong>Dropoff:</strong> <?php echo htmlspecialchars($booking_data['dropoff_location']); ?></p>
                        </div>
                        
                        <?php if (!$selected_package): ?>
                        <div class="border-b pb-4">
                            <h3 class="font-bold text-lg mb-3">Extras</h3>
                            <?php if ($booking_data['extras']['professional_driver']): ?>
                                <p>✓ Professional Driver (+LKR 120)</p>
                            <?php endif; ?>
                            <?php if ($booking_data['extras']['decorations']): ?>
                                <p>✓ Event Decorations (+LKR 75)</p>
                            <?php endif; ?>
                            <?php if ($booking_data['extras']['extra_hours'] > 0): ?>
                                <p>✓ Extra Hours (<?php echo $booking_data['extras']['extra_hours']; ?> hrs) (+LKR <?php echo number_format($booking_data['extras']['extra_hours'] * $vehicle['price_per_hour'], 2); ?>)</p>
                            <?php endif; ?>
                            <?php if (!$booking_data['extras']['professional_driver'] && !$booking_data['extras']['decorations'] && $booking_data['extras']['extra_hours'] == 0): ?>
                                <p class="text-gray-500">No extras selected</p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="bg-gray-50 p-6 rounded-lg">
                            <h3 class="font-bold text-lg mb-3">Cost Summary</h3>
                            <div class="space-y-2">
                                <?php if ($selected_package): ?>
                                    <div class="flex justify-between">
                                        <span><?php echo htmlspecialchars($selected_package['name']); ?> Base Price</span>
                                        <span>LKR <?php echo number_format($subtotal, 2); ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="flex justify-between">
                                        <span>Base Rental (<?php echo $total_hours; ?> hours)</span>
                                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                                    </div>
                                    <?php if ($extras_total > 0): ?>
                                        <div class="flex justify-between">
                                            <span>Extras</span>
                                            <span>$<?php echo number_format($extras_total, 2); ?></span>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <div class="flex justify-between">
                                    <span>Tax (10%)</span>
                                    <span>LKR <?php echo number_format($tax, 2); ?></span>
                                </div>
                                <div class="flex justify-between pt-2 border-t font-bold text-lg">
                                    <span>Grand Total</span>
                                    <span class="text-red-600 font-bold">LKR <?php echo number_format($grand_total, 2); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <form method="POST">
                            <button type="submit" class="w-full bg-red-600 text-white py-3 rounded-lg font-semibold hover:bg-red-700 transition">
                                Confirm & Pay
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php require_once '../includes/footer.php'; ?>
<?php } ?>