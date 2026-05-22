<?php
$page_title = 'Booking Approvals';
require_once 'includes/auth.php';
requireAdminLogin();

$pending_bookings = [
    ['id' => 1, 'number' => '#FE-8821', 'customer' => 'Global Horizon Events', 'vehicle' => 'Mercedes S-Class', 'event' => 'Corporate Gala', 'amount' => 2450, 'date' => 'Oct 24', 'priority' => 'high'],
    ['id' => 2, 'number' => '#FE-8819', 'customer' => 'Vanguard Logistics', 'vehicle' => 'Freightliner M2', 'event' => 'Logistics', 'amount' => 2840, 'date' => 'Oct 23', 'priority' => 'normal'],
    ['id' => 3, 'number' => '#FE-8790', 'customer' => 'Artisan Catering', 'vehicle' => 'Ford Transit', 'event' => 'Catering', 'amount' => 820, 'date' => 'Oct 22', 'priority' => 'normal'],
];

$available_vehicles = [
    ['id' => 1, 'plate' => 'SUV-702', 'name' => 'Cadillac Escalade'],
    ['id' => 2, 'plate' => 'SUV-708', 'name' => 'Range Rover'],
    ['id' => 3, 'plate' => 'SED-401', 'name' => 'Mercedes S-Class'],
];

$available_drivers = [
    ['id' => 1, 'name' => 'Marcus Vance', 'rating' => '4.98', 'level' => 5],
    ['id' => 2, 'name' => 'Sarah Jennings', 'rating' => '4.95', 'level' => 4],
    ['id' => 3, 'name' => 'David Chen', 'rating' => '4.92', 'level' => 4],
];

$pending_count = count($pending_bookings);
$available_count = count($available_vehicles);
$drivers_count = count($available_drivers);
$urgent_count = 2;
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>FleetElite Admin | Booking Approvals</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<style>
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    body { font-family: 'Inter', sans-serif; background-color: #f7f9fb; }
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
    .modal-content { background: white; margin: 50px auto; max-width: 500px; border-radius: 12px; }
</style>
</head>
<body class="bg-gray-50">

<?php require_once 'includes/sidebar.php'; ?>

<main class="ml-64 min-h-screen p-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header - UI 7 Style -->
        <div class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-4xl font-bold text-gray-900">Pending Approvals</h1>
                <p class="text-gray-600 mt-1">Review and dispatch logistics for upcoming elite event reservations.</p>
            </div>
            <div class="flex gap-3">
                <button class="flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-50">
                    <span class="material-symbols-outlined">filter_list</span>
                    Filter
                </button>
                <button onclick="openNewBooking()" class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    <span class="material-symbols-outlined">add</span>
                    New Reservation
                </button>
            </div>
        </div>

        <!-- Stats Bar - UI 7 Style -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                <p class="text-sm text-gray-500 uppercase tracking-wider">Pending Requests</p>
                <p class="text-4xl font-bold text-red-600 mt-2"><?php echo $pending_count; ?></p>
                <div class="flex items-center gap-1 text-xs text-green-600 mt-2">
                    <span class="material-symbols-outlined text-sm">trending_up</span>
                    <span>3 since yesterday</span>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                <p class="text-sm text-gray-500 uppercase tracking-wider">Available Fleet</p>
                <p class="text-4xl font-bold text-gray-900 mt-2"><?php echo $available_count; ?></p>
                <div class="w-full bg-gray-100 h-1.5 rounded-full mt-3">
                    <div class="bg-green-500 h-full rounded-full" style="width: 75%"></div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                <p class="text-sm text-gray-500 uppercase tracking-wider">Drivers on Duty</p>
                <p class="text-4xl font-bold text-gray-900 mt-2"><?php echo $drivers_count; ?></p>
                <p class="text-xs text-gray-500 mt-2">6 currently standby</p>
            </div>
            <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                <p class="text-sm text-gray-500 uppercase tracking-wider">Urgent Action</p>
                <p class="text-4xl font-bold text-red-600 mt-2"><?php echo $urgent_count; ?></p>
                <p class="text-xs text-red-600 font-medium mt-2">Reservations starting &lt; 24h</p>
            </div>
        </div>

        <!-- Main Grid: List and Sidebar - UI 7 Style -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8 items-start">
            <!-- Pending List -->
            <div class="xl:col-span-2 space-y-4">
                <?php foreach ($pending_bookings as $booking): ?>
                <div class="bg-white border border-gray-100 rounded-xl overflow-hidden hover:shadow-md transition">
                    <div class="flex flex-col md:flex-row">
                        <div class="md:w-64 h-48 md:h-auto overflow-hidden relative bg-gray-100 flex items-center justify-center">
                            <span class="material-symbols-outlined text-5xl text-gray-400">directions_car</span>
                            <?php if ($booking['priority'] == 'high'): ?>
                            <div class="absolute top-3 left-3 bg-red-600 text-white text-[10px] font-bold px-2 py-1 uppercase rounded">High Priority</div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1 p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-2xl font-bold text-gray-900"><?php echo $booking['vehicle']; ?> - <?php echo $booking['event']; ?></h3>
                                    <p class="text-sm text-gray-500"><?php echo $booking['number']; ?> • Submitted 2h ago</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-gray-900">$<?php echo number_format($booking['amount'], 2); ?></p>
                                    <p class="text-xs text-gray-500">Total Revenue</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-red-600">person</span>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Client</p>
                                        <p class="text-sm font-medium"><?php echo $booking['customer']; ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-red-600">calendar_today</span>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Dates</p>
                                        <p class="text-sm font-medium"><?php echo $booking['date']; ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-red-600">location_on</span>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Event Type</p>
                                        <p class="text-sm font-medium"><?php echo $booking['event']; ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-3 pt-4 border-t border-gray-100">
                                <button onclick="openApproveModal(<?php echo $booking['id']; ?>)" class="flex-1 bg-red-600 text-white font-medium py-2.5 rounded-lg hover:bg-red-700 flex items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-sm">check_circle</span>
                                    Approve & Dispatch
                                </button>
                                <button onclick="openRejectModal(<?php echo $booking['id']; ?>)" class="px-6 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50">
                                    Reject
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Dispatch Sidebar - UI 7 Style -->
            <aside class="sticky top-24 bg-white border border-gray-100 rounded-xl shadow-lg p-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Logistics Dispatch</h2>
                    <p class="text-sm text-gray-500 mt-1">Assign resources for Approved bookings.</p>
                </div>
                
                <div class="space-y-6 mt-6">
                    <div>
                        <label class="text-sm font-medium text-gray-700 block mb-2">Target Booking</label>
                        <select id="targetBooking" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5">
                            <option value="">Select a booking</option>
                            <?php foreach ($pending_bookings as $booking): ?>
                            <option value="<?php echo $booking['id']; ?>">#<?php echo substr($booking['number'], -6); ?> - <?php echo $booking['customer']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Vehicle Assignment -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-sm font-medium text-gray-700">Select Vehicle</label>
                            <span class="text-xs text-green-600 font-medium"><?php echo $available_count; ?> Available</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <?php foreach ($available_vehicles as $vehicle): ?>
                            <button type="button" onclick="selectVehicle(this, <?php echo $vehicle['id']; ?>)" class="flex flex-col items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition vehicle-option">
                                <span class="material-symbols-outlined text-gray-500 mb-1">directions_car</span>
                                <span class="text-sm font-medium"><?php echo $vehicle['plate']; ?></span>
                                <span class="text-[10px] text-gray-500"><?php echo $vehicle['name']; ?></span>
                            </button>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" id="selectedVehicleId">
                    </div>

                    <!-- Driver Assignment -->
                    <div>
                        <label class="text-sm font-medium text-gray-700 block mb-2">Assign Driver</label>
                        <div class="space-y-3">
                            <?php foreach ($available_drivers as $driver): ?>
                            <div class="flex items-center gap-3 p-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition cursor-pointer driver-option" onclick="selectDriver(this, <?php echo $driver['id']; ?>)">
                                <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-bold">
                                    <?php echo strtoupper(substr($driver['name'], 0, 1)); ?>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-gray-900"><?php echo $driver['name']; ?></p>
                                    <p class="text-[10px] text-gray-500 uppercase">Level <?php echo $driver['level']; ?> • <?php echo $driver['rating']; ?> Rating</p>
                                </div>
                                <input type="radio" name="driver" value="<?php echo $driver['id']; ?>" class="driver-radio">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Progress Indicator -->
                    <div class="pt-4 border-t border-gray-100">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-medium uppercase tracking-wider text-gray-500">Preparation Status</p>
                            <span class="text-xs font-bold text-red-600">60%</span>
                        </div>
                        <div class="flex gap-1 h-1.5 w-full">
                            <div class="flex-1 bg-red-600 rounded-full"></div>
                            <div class="flex-1 bg-red-600 rounded-full"></div>
                            <div class="flex-1 bg-red-600 rounded-full"></div>
                            <div class="flex-1 bg-gray-200 rounded-full"></div>
                            <div class="flex-1 bg-gray-200 rounded-full"></div>
                        </div>
                        <p class="text-[10px] text-gray-500 mt-2 italic">Vehicle cleaning and fueling in progress...</p>
                    </div>

                    <button onclick="finalizeDispatch()" class="w-full bg-gray-900 text-white font-medium py-4 rounded-xl hover:bg-black transition shadow-md flex items-center justify-center gap-2 mt-4">
                        <span class="material-symbols-outlined">send</span>
                        Finalize Dispatch
                    </button>
                </div>
            </aside>
        </div>
    </div>
</main>

<!-- Approve Modal -->
<div id="approveModal" class="modal">
    <div class="modal-content">
        <div class="p-6 border-b">
            <h3 class="text-xl font-bold">Approve Booking</h3>
        </div>
        <form class="p-6 space-y-4">
            <textarea id="approveNotes" rows="3" class="w-full px-3 py-2 border rounded-lg" placeholder="Admin Notes (Optional)"></textarea>
            <div class="flex gap-3">
                <button type="button" onclick="confirmApprove()" class="flex-1 bg-red-600 text-white py-2 rounded-lg">Approve Booking</button>
                <button type="button" onclick="closeModals()" class="flex-1 bg-gray-200 py-2 rounded-lg">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="modal">
    <div class="modal-content">
        <div class="p-6 border-b">
            <h3 class="text-xl font-bold">Reject Booking</h3>
        </div>
        <form class="p-6 space-y-4">
            <textarea id="rejectReason" rows="3" class="w-full px-3 py-2 border rounded-lg" placeholder="Reason for rejection *" required></textarea>
            <div class="flex gap-3">
                <button type="button" onclick="confirmReject()" class="flex-1 bg-red-600 text-white py-2 rounded-lg">Reject Booking</button>
                <button type="button" onclick="closeModals()" class="flex-1 bg-gray-200 py-2 rounded-lg">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentBookingId = null;

function openApproveModal(id) {
    currentBookingId = id;
    document.getElementById('approveModal').style.display = 'block';
}

function openRejectModal(id) {
    currentBookingId = id;
    document.getElementById('rejectModal').style.display = 'block';
}

function confirmApprove() {
    alert('Booking #' + currentBookingId + ' approved!');
    closeModals();
}

function confirmReject() {
    alert('Booking #' + currentBookingId + ' rejected!');
    closeModals();
}

function closeModals() {
    document.getElementById('approveModal').style.display = 'none';
    document.getElementById('rejectModal').style.display = 'none';
}

function openNewBooking() {
    alert('New booking form');
}

function selectVehicle(btn, vehicleId) {
    document.querySelectorAll('.vehicle-option').forEach(opt => {
        opt.classList.remove('border-2', 'border-red-600', 'bg-red-50');
        opt.classList.add('border', 'border-gray-200');
    });
    btn.classList.remove('border', 'border-gray-200');
    btn.classList.add('border-2', 'border-red-600', 'bg-red-50');
    document.getElementById('selectedVehicleId').value = vehicleId;
}

function selectDriver(div, driverId) {
    document.querySelectorAll('.driver-option').forEach(d => {
        d.classList.remove('bg-red-50', 'border-red-600');
        d.classList.add('border', 'border-gray-200');
    });
    div.classList.remove('border', 'border-gray-200');
    div.classList.add('bg-red-50', 'border-red-600');
    div.querySelector('.driver-radio').checked = true;
}

function finalizeDispatch() {
    alert('Booking dispatched successfully!');
}

window.onclick = function(event) {
    if (event.target.classList && event.target.classList.contains('modal')) {
        closeModals();
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>