<?php
$page_title = 'Booking Approvals';
require_once 'includes/auth.php';
require_once 'config/database.php';
requireAdminLogin();

// Fetch pending bookings from the database, joined with related tables
$stmt = $pdo->prepare("
    SELECT 
        b.id,
        b.booking_number AS number,
        u.full_name AS customer,
        v.name AS vehicle,
        et.name AS event,
        b.event_date AS date,
        b.total_amount AS amount,
        b.status
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN vehicles v ON b.vehicle_id = v.id
    JOIN event_types et ON b.event_type_id = et.id
    WHERE b.status = 'pending'
    ORDER BY b.created_at DESC
");
$stmt->execute();
$pending_bookings = $stmt->fetchAll();

// Add a 'priority' flag manually since it doesn't exist in the schema yet
foreach ($pending_bookings as &$booking) {
    $booking['priority'] = 'normal'; // adjust later if you add real priority logic
}
unset($booking);

// Available vehicles — only ones marked available
$stmt = $pdo->prepare("SELECT id, name, model FROM vehicles WHERE status = 'available' LIMIT 10");
$stmt->execute();
$available_vehicles = $stmt->fetchAll();

// Drivers — no drivers table exists yet in your schema, so this stays static for now
$available_drivers = [
    ['id' => 1, 'name' => 'Marcus Vance', 'rating' => '4.98', 'level' => 5],
    ['id' => 2, 'name' => 'Sarah Jennings', 'rating' => '4.95', 'level' => 4],
    ['id' => 3, 'name' => 'David Chen', 'rating' => '4.92', 'level' => 4],
];

$pending_count = count($pending_bookings);
$available_count = count($available_vehicles);
$drivers_count = count($available_drivers);
$urgent_count = 2; // still static — can compute later based on event_date
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
    :root {
        --surface: #f9f9fa;
        --surface-low: #f3f4f4;
        --surface-card: #ffffff;
        --surface-high: #e7e8e9;
        --primary: #02414a;
        --primary-soft: #b8ebf7;
        --primary-hover: #0d5260;
        --outline: #c0c8ca;
        --text: #191c1d;
        --muted: #40484a;
        --success: #176a3a;
        --warning: #8a5200;
        --danger: #ba1a1a;
    }
    body { font-family: 'Inter', sans-serif; background-color: var(--surface); color: var(--text); }
    .bg-white { background-color: var(--surface-card) !important; }
    .bg-gray-50, .hover\:bg-gray-50:hover { background-color: var(--surface-low) !important; }
    .bg-gray-100, .bg-gray-200 { background-color: var(--surface-high) !important; }
    .border-gray-100, .border-gray-200, .border-gray-300 { border-color: var(--outline) !important; }
    .text-gray-900, .text-gray-700 { color: var(--text) !important; }
    .text-gray-600, .text-gray-500, .text-gray-400 { color: var(--muted) !important; }
    .bg-red-600 { background-color: var(--primary) !important; }
    .hover\:bg-red-700:hover, .hover\:bg-black:hover { background-color: var(--primary-hover) !important; }
    .text-red-600 { color: var(--primary) !important; }
    .border-red-600 { border-color: var(--primary-soft) !important; }
    [class*="bg-red-"] { background-color: var(--primary) !important; }
    [class*="text-red-"] { color: var(--primary) !important; }
    [class*="border-red-"] { border-color: var(--primary-soft) !important; }
    [class*="hover:bg-red-"]:hover { background-color: var(--primary-hover) !important; }
    [class*="hover:text-red-"]:hover { color: var(--primary-hover) !important; }
    .bg-red-50, .bg-red-100 { background-color: var(--primary-soft) !important; }
    .bg-gray-900 { background-color: var(--primary) !important; }
    .text-green-600 { color: var(--success) !important; }
    .bg-green-500 { background-color: var(--success) !important; }
    .rounded-xl { border-radius: 0.75rem !important; }
    .rounded-lg { border-radius: 0.5rem !important; }
    .shadow-lg, .shadow-md, .shadow-sm { box-shadow: 0 10px 24px rgba(25,28,29,0.06) !important; }
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(2,65,74,0.45); z-index: 1000; }
    .modal-content { background: var(--surface-card); margin: 50px auto; max-width: 500px; border-radius: 12px; border: 1px solid var(--outline); box-shadow: 0 24px 60px rgba(25,28,29,0.18); }
    .card-3d { position: relative; overflow: hidden; padding-right: 5.5rem; background: linear-gradient(180deg, #ffffff, #fbfbfd); border-radius: 0.75rem; border: 1px solid #d9dfe2; border-bottom: 4px solid var(--card-accent, #0b6b6d); box-shadow: 0 6px 16px rgba(2,65,74,0.06), 0 1px 4px rgba(2,65,74,0.03); transform: translateY(0); transition: transform .22s ease, box-shadow .22s ease; z-index: 1; }
    .card-3d:hover { transform: translateY(-6px); box-shadow: 0 18px 30px rgba(2,65,74,0.10); }
    .card-3d .card-icon { position: absolute; right: 1.2rem; top: 50%; transform: translateY(-50%); width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; box-shadow: 0 8px 20px rgba(2,65,74,0.12); z-index: 2; flex-shrink: 0; }
    .kpi-value { font-size: 1.6rem; font-weight: 700; color: #072029; }
</style>
</head>
<body class="bg-gray-50">

<?php require_once 'includes/sidebar.php'; ?>

<main class="ml-64 min-h-screen bg-slate-50">
    <div class="p-8 max-w-7xl mx-auto">
        <section class="rounded-[2rem] overflow-hidden mb-10">
            <div class="relative bg-slate-900 text-white p-8 lg:p-10 overflow-hidden">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.24),_transparent_36%)] opacity-70 pointer-events-none"></div>
                <div class="relative grid grid-cols-1 xl:grid-cols-[1.5fr_1fr] gap-8 items-center">
                    <div class="max-w-2xl">
                        <p class="text-xs uppercase tracking-[0.35em] text-slate-400 mb-4">Fleet Manager</p>
                        <h1 class="text-5xl font-semibold tracking-tight">Pending Approvals</h1>
                        <p class="mt-4 text-slate-300 text-lg leading-8">Review and dispatch logistics for upcoming elite event reservations.</p>
                    </div>
                    <div class="flex flex-wrap justify-end gap-3">
                        <button class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-slate-800 text-slate-300 text-sm font-semibold hover:bg-slate-700 transition-all">
                            <span class="material-symbols-outlined text-sm">filter_list</span>
                            Filter
                        </button>
                        <button class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-cyan-500 text-white text-sm font-semibold hover:bg-cyan-400 transition-all">
                            <span class="material-symbols-outlined text-sm">download</span>
                            Export
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5 mb-10">
            <div class="relative max-w-3xl mx-auto">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                <input type="text" id="searchApprovals" class="w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-cyan-300 focus:border-cyan-300 text-sm text-slate-700" placeholder="Search approvals...">
            </div>
        </div>

        <div class="flex justify-end mb-8">
            <button onclick="openNewBooking()" class="inline-flex items-center gap-2 px-5 py-3 bg-red-600 text-white rounded-2xl shadow-lg hover:bg-red-700 transition">
                <span class="material-symbols-outlined">add</span>
                New Reservation
            </button>
        </div>

        <!-- Stats Bar - UI 7 Style -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="card-3d p-6 bg-white" style="--card-accent: #b36b2a;">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Pending Requests</p>
                    <div class="kpi-value"><?php echo $pending_count; ?></div>
                    <div class="text-xs text-green-600 mt-1">3 since yesterday</div>
                </div>
                <div class="card-icon" style="background:var(--card-accent,#b36b2a)"><span class="material-symbols-outlined">pending_actions</span></div>
            </div>
            <div class="card-3d p-6 bg-white" style="--card-accent: #0b6b6d;">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Available Fleet</p>
                    <div class="kpi-value"><?php echo $available_count; ?></div>
                    <div class="w-full bg-gray-100 h-1.5 rounded-full mt-3">
                        <div class="bg-green-500 h-full rounded-full" style="width: 75%"></div>
                    </div>
                </div>
                <div class="card-icon" style="background:var(--card-accent,#0b6b6d)"><span class="material-symbols-outlined">directions_car</span></div>
            </div>
            <div class="card-3d p-6 bg-white" style="--card-accent: #0b6b6d;">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Drivers on Duty</p>
                    <div class="kpi-value"><?php echo $drivers_count; ?></div>
                    <div class="text-xs text-gray-500 mt-1">6 currently standby</div>
                </div>
                <div class="card-icon" style="background:var(--card-accent,#0b6b6d)"><span class="material-symbols-outlined">badge</span></div>
            </div>
            <div class="card-3d p-6 bg-white" style="--card-accent: #b36b2a;">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Urgent Action</p>
                    <div class="kpi-value"><?php echo $urgent_count; ?></div>
                    <div class="text-xs text-[#f59e0b] mt-1">Reservations starting &lt; 24h</div>
                </div>
                <div class="card-icon" style="background:var(--card-accent,#b36b2a)"><span class="material-symbols-outlined">warning</span></div>
            </div>
        </div>

        <!-- Main Grid: List and Sidebar - UI 7 Style -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8 items-start">
            <!-- Pending List -->
            <div class="xl:col-span-2 space-y-4">
                <?php if (empty($pending_bookings)): ?>
                <div class="bg-white border border-gray-100 rounded-xl p-10 text-center text-gray-500">
                    No pending bookings right now.
                </div>
                <?php endif; ?>
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
                                    <h3 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($booking['vehicle']); ?> - <?php echo htmlspecialchars($booking['event']); ?></h3>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['number']); ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-gray-900">LKR <?php echo number_format($booking['amount'], 2); ?></p>
                                    <p class="text-xs text-gray-500">Total Revenue</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-red-600">person</span>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Client</p>
                                        <p class="text-sm font-medium"><?php echo htmlspecialchars($booking['customer']); ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-red-600">calendar_today</span>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Dates</p>
                                        <p class="text-sm font-medium"><?php echo date('M j, Y', strtotime($booking['date'])); ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-red-600">location_on</span>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Event Type</p>
                                        <p class="text-sm font-medium"><?php echo htmlspecialchars($booking['event']); ?></p>
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
                            <option value="<?php echo $booking['id']; ?>">#<?php echo htmlspecialchars(substr($booking['number'], -6)); ?> - <?php echo htmlspecialchars($booking['customer']); ?></option>
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
                                <span class="text-sm font-medium"><?php echo htmlspecialchars($vehicle['name']); ?></span>
                                <span class="text-[10px] text-gray-500"><?php echo htmlspecialchars($vehicle['model']); ?></span>
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

function removeBookingCard(id) {
    const card = document.querySelector('button[onclick="openApproveModal(' + id + ')"]')?.closest('.bg-white.border.border-gray-100.rounded-xl.overflow-hidden');
    if (card) {
        card.remove();
    }
    const option = document.querySelector('#targetBooking option[value="' + id + '"]');
    if (option) option.remove();
}

async function confirmApprove() {
    const notes = document.getElementById('approveNotes').value;
    try {
        const res = await fetch('ajax/update-status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ booking_id: currentBookingId, action: 'approve', notes: notes })
        });
        const data = await res.json();
        if (data.success) {
            removeBookingCard(currentBookingId);
            alert('Booking approved!');
        } else {
            alert('Error: ' + data.message);
        }
    } catch (err) {
        alert('Request failed: ' + err.message);
    }
    closeModals();
}

async function confirmReject() {
    const reason = document.getElementById('rejectReason').value.trim();
    if (!reason) {
        alert('Please provide a rejection reason.');
        return;
    }
    try {
        const res = await fetch('ajax/update-status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ booking_id: currentBookingId, action: 'reject', notes: reason })
        });
        const data = await res.json();
        if (data.success) {
            removeBookingCard(currentBookingId);
            alert('Booking rejected.');
        } else {
            alert('Error: ' + data.message);
        }
    } catch (err) {
        alert('Request failed: ' + err.message);
    }
    closeModals();
}

function closeModals() {
    document.getElementById('approveModal').style.display = 'none';
    document.getElementById('rejectModal').style.display = 'none';
    document.getElementById('approveNotes').value = '';
    document.getElementById('rejectReason').value = '';
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