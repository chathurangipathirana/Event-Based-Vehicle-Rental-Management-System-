<?php
$page_title = 'Complete Your Booking';
require_once '../config/database.php';

$step = $_GET['step'] ?? 1;
$booking_data = $_SESSION['booking_data'] ?? [];

// Step 1: Vehicle and Schedule Selection
if ($step == 1) {
    $package_id = isset($_GET['package_id']) ? (int)$_GET['package_id'] : 0;
    $selected_package = null;
    $package_vehicles = [];
    if ($package_id > 0) {
        $stmtPkg = $pdo->prepare("SELECT * FROM event_packages WHERE id = ?");
        $stmtPkg->execute([$package_id]);
        $selected_package = $stmtPkg->fetch();

        // Match the vehicle names stored on the package to real, available fleet records.
        if ($selected_package && !empty($selected_package['vehicle_types'])) {
            $vehicle_types = array_filter(array_map('trim', explode(',', $selected_package['vehicle_types'])));
            $where = [];
            $params = [];
            foreach ($vehicle_types as $vehicle_type) {
                $where[] = '(name LIKE ? OR model LIKE ?)';
                $params[] = '%' . $vehicle_type . '%';
                $params[] = '%' . $vehicle_type . '%';
            }
            if ($where) {
                $stmtVehicles = $pdo->prepare('SELECT * FROM vehicles WHERE status = \'available\' AND (' . implode(' OR ', $where) . ')');
                $stmtVehicles->execute($params);
                $package_vehicles = $stmtVehicles->fetchAll();
            }
        }
    }

    $requested_vehicle_id = isset($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : (int)($_GET['vehicle'] ?? 0);
    $hasExplicitVehicleSelection = $requested_vehicle_id > 0;
    $vehicle_id = $requested_vehicle_id;
    if ($selected_package) {
        // A booking stores one vehicle ID as its fleet reference. Use an included
        // vehicle, or the first available vehicle only if the package has no match.
        $package_vehicle_ids = array_map('intval', array_column($package_vehicles, 'id'));
        if (!$vehicle_id || !in_array($vehicle_id, $package_vehicle_ids, true)) {
            $vehicle_id = $package_vehicle_ids[0] ?? $pdo->query("SELECT id FROM vehicles WHERE status = 'available' LIMIT 1")->fetchColumn();
        }
    }
    
    $selected_vehicle = getVehicleById($vehicle_id);
    
    if (!$selected_vehicle) {
        header('Location: vehicles.php');
        exit();
    }

    $eventTypes = $pdo->query("SELECT * FROM event_types WHERE is_active = 1")->fetchAll();
    $default_event_type_id = $eventTypes[0]['id'] ?? 0;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $guest_name = trim($_POST['guest_name'] ?? '');
        $guest_email = trim($_POST['guest_email'] ?? '');
        $guest_phone = trim($_POST['guest_phone'] ?? '');
        $is_guest = !isset($_SESSION['user_id']);

        if ($is_guest && (!$guest_name || !filter_var($guest_email, FILTER_VALIDATE_EMAIL) || !$guest_phone)) {
            $booking_error = 'Please enter your name, a valid email address, and phone number to continue as a guest.';
        } else {
            $_SESSION['booking_data'] = [
                'vehicle_id' => $vehicle_id,
                'package_id' => $package_id,
                'event_type_id' => $selected_package ? $default_event_type_id : (int)$_POST['event_type_id'],
                'event_name' => $selected_package ? $selected_package['name'] : ($_POST['event_name'] ?? ''),
                'event_date' => $_POST['event_date'],
                'start_time' => $_POST['start_time'],
                'end_time' => $_POST['end_time'],
                'pickup_location' => $_POST['pickup_location'],
                'dropoff_location' => $_POST['dropoff_location'],
                'special_requests' => trim($_POST['special_requests'] ?? ''),
                'guest_contact' => [
                    'name' => $guest_name,
                    'email' => $guest_email,
                    'phone' => $guest_phone
                ]
            ];
            header('Location: booking-process.php?step=2');
            exit();
        }
    }
    
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
                    <?php if (!empty($booking_error)): ?>
                        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"><?php echo htmlspecialchars($booking_error); ?></div>
                    <?php endif; ?>
                    <?php if ($selected_package): ?>
                        <section class="rounded-xl border border-cyan-100 bg-cyan-50/60 p-5 text-center">
                            <div class="mb-5">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wider text-cyan-700">Selected package</p>
                                    <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($selected_package['name']); ?></h2>
                                    <p class="mt-1 text-sm text-gray-600">LKR <?php echo number_format($selected_package['base_price'], 2); ?> package price</p>
                                </div>
                            </div>

                            <h3 class="mb-3 text-sm font-bold text-gray-800">Selected Vehicle: <span id="selected-booking-vehicle-name" class="text-cyan-700"><?php echo $hasExplicitVehicleSelection ? htmlspecialchars($selected_vehicle['name']) : 'No vehicle selected'; ?></span></h3>
                            <?php if ($package_vehicles): ?>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <?php foreach ($package_vehicles as $package_vehicle): ?>
                                        <label class="cursor-pointer rounded-lg border bg-white p-3 transition hover:border-cyan-500 <?php echo $hasExplicitVehicleSelection && $package_vehicle['id'] == $vehicle_id ? 'border-cyan-600 ring-1 ring-cyan-600' : 'border-gray-200'; ?>">
                                            <input type="radio" name="vehicle_id" value="<?php echo $package_vehicle['id']; ?>" data-vehicle-name="<?php echo htmlspecialchars($package_vehicle['name'], ENT_QUOTES); ?>" <?php echo $hasExplicitVehicleSelection && $package_vehicle['id'] == $vehicle_id ? 'checked' : ''; ?> required class="sr-only">
                                            <div class="flex gap-3">
                                                <img src="<?php echo htmlspecialchars(getVehicleImageUrl($package_vehicle['image_url'], $package_vehicle['name'])); ?>" alt="<?php echo htmlspecialchars($package_vehicle['name']); ?>" class="h-20 w-28 rounded-md object-cover">
                                                <div class="min-w-0">
                                                    <p class="font-bold text-gray-900"><?php echo htmlspecialchars($package_vehicle['name']); ?></p>
                                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($package_vehicle['model']); ?> · <?php echo $package_vehicle['year']; ?></p>
                                                    <p class="mt-2 text-xs text-gray-600"><?php echo $package_vehicle['capacity']; ?> seats · <?php echo htmlspecialchars($package_vehicle['transmission']); ?></p>
                                                    <a href="vehicle-details.php?id=<?php echo $package_vehicle['id']; ?>" class="mt-1 inline-block text-xs font-semibold text-cyan-700 hover:underline">Vehicle details</a>
                                                </div>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-sm text-gray-600">The fleet for this package will be confirmed by our team. A vehicle has been reserved as the booking reference.</p>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Event fields are set automatically for customized bookings. -->
                        <?php if (!$selected_package): ?>
                        <input type="hidden" name="event_type_id" value="<?php echo $default_event_type_id; ?>">
                        <input type="hidden" name="event_name" value="Customized Booking">
                        <?php endif; ?>
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

                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h3 class="font-bold text-gray-900">Guest contact details</h3>
                                <a href="register.php" class="text-sm font-bold text-primary hover:underline">Create an account</a>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">No account is required. You can also create an account to manage your bookings later.</p>
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2">Full Name *</label>
                                    <input type="text" name="guest_name" required value="<?php echo htmlspecialchars($_POST['guest_name'] ?? ''); ?>" class="w-full px-4 py-2 border rounded-lg">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-2">Email *</label>
                                    <input type="email" name="guest_email" required value="<?php echo htmlspecialchars($_POST['guest_email'] ?? ''); ?>" class="w-full px-4 py-2 border rounded-lg">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-2">Phone *</label>
                                    <input type="tel" name="guest_phone" required value="<?php echo htmlspecialchars($_POST['guest_phone'] ?? ''); ?>" class="w-full px-4 py-2 border rounded-lg">
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2">Pickup Location *</label>
                        <input type="text" name="pickup_location" required placeholder="Full address" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2">Dropoff Location *</label>
                        <input type="text" name="dropoff_location" required placeholder="Full address" class="w-full px-4 py-2 border rounded-lg">
                    </div>

                    <?php if ($selected_package): ?>
                    <div>
                        <label class="block text-sm font-medium mb-2">Package Details</label>
                        <textarea name="special_requests" rows="4" placeholder="Add package preferences, decoration needs, passenger requirements, or other details." class="w-full px-4 py-2 border rounded-lg"></textarea>
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="w-full bg-red-600 text-white py-3 rounded-lg font-semibold hover:bg-red-700 transition">
                        Continue to Extras
                    </button>
                </form>
            </div>
        </div>
    </main>
    <script>
        document.querySelectorAll('input[name="vehicle_id"]').forEach((input) => {
            input.addEventListener('change', () => {
                if (input.checked) {
                    document.getElementById('selected-booking-vehicle-name').textContent = input.dataset.vehicleName;
                }
            });
        });
    </script>
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
            $_SESSION['user_id'] ?? null,
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
            json_encode([
                'extras' => $booking_data['extras'],
                'guest_contact' => $booking_data['guest_contact'] ?? null,
                'special_requests' => $booking_data['special_requests'] ?? ''
            ])
        ]);

        $new_booking_id = $pdo->lastInsertId();
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['guest_booking_id'] = $new_booking_id;
        }
        
        // Clear session data
        unset($_SESSION['booking_data']);
        
        header('Location: booking-success.php?id=' . $new_booking_id);
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
                                <p><strong>Booking Reference Vehicle:</strong> <?php echo htmlspecialchars($vehicle['name']); ?> — <?php echo htmlspecialchars($vehicle['model']); ?> (<?php echo $vehicle['capacity']; ?> seats, <?php echo htmlspecialchars($vehicle['transmission']); ?>)</p>
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
                                        <span>LKR <?php echo number_format($subtotal, 2); ?></span>
                                    </div>
                                    <?php if ($extras_total > 0): ?>
                                        <div class="flex justify-between">
                                            <span>Extras</span>
                                            <span>LKR <?php echo number_format($extras_total, 2); ?></span>
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
