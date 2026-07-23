<?php
$page_title = 'Booking Approvals';
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/invoice-mailer.php';
requireAdminLogin();

// Fetch pending bookings from the database, joined with related tables
$stmt = $pdo->prepare("
    SELECT 
        b.id,
        b.booking_number AS number,
        u.full_name AS customer,
        u.email AS email,
        u.phone AS phone,
        v.name AS vehicle,
        v.category AS category,
        v.image_url AS vehicle_image,
        et.name AS event,
        b.event_date AS date,
        b.total_amount AS amount,
        b.pickup_location,
        b.dropoff_location,
        b.start_time,
        b.end_time,
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

function getAdminVehicleImageUrl(?string $image_url, string $vehicleName = ''): string {
    $name = strtolower(trim($vehicleName));
    
    $map = [
        'axio' => '../user-site/public/assets/vehicles/toyota-axio.png',
        'premio' => '../user-site/public/assets/vehicles/toyota-premio.png',
        'vezel' => '../user-site/public/assets/vehicles/honda-vezel.png',
        'hiace' => '../user-site/public/assets/vehicles/toyota-hiace.png',
        'sunny' => '../user-site/public/assets/vehicles/nissan-sunny.png',
        'wagon' => '../user-site/public/assets/vehicles/suzuki-wagonr.png',
    ];
    
    foreach ($map as $key => $path) {
        if (str_contains($name, $key)) {
            return $path;
        }
    }
    
    if (!empty($image_url) && !str_starts_with($image_url, 'http')) {
        return '../user-site/public/' . ltrim($image_url, '/');
    }

    return '../user-site/public/assets/vehicles/toyota-axio.png';
}

// Add a 'priority' flag manually since it doesn't exist in the schema yet
foreach ($pending_bookings as &$booking) {
    $booking['priority'] = 'normal'; // adjust later if you add real priority logic
}
unset($booking);

// Available vehicles — only ones marked available
$stmt = $pdo->prepare("SELECT id, name, model, category FROM vehicles WHERE status = 'available'");
$stmt->execute();
$available_vehicles = $stmt->fetchAll();

// Drivers — fetch from drivers table
$stmt = $pdo->prepare("SELECT id, name, rating, rating_level as level FROM drivers WHERE status = 'available'");
$stmt->execute();
$available_drivers = $stmt->fetchAll();

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
                        <p class="text-xs uppercase tracking-[0.35em] text-slate-400 mb-4">Vehicle Manager</p>
                        <h1 class="text-5xl font-semibold tracking-tight">Pending Approvals</h1>
                        <p class="mt-4 text-slate-300 text-lg leading-8">Review and dispatch logistics for upcoming elite event reservations.</p>
                    </div>
                    <div class="flex flex-wrap justify-end gap-3">
                        <button class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-slate-800 text-slate-300 text-sm font-semibold hover:bg-slate-700 transition-all">
                            <span class="material-symbols-outlined text-sm">filter_list</span>
                            Filter
                        </button>
                        <button onclick="exportApprovals()" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-cyan-500 text-white text-sm font-semibold hover:bg-cyan-400 transition-all">
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
                    <p class="text-sm text-gray-500 uppercase">Available Vehicles</p>
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

        <!-- Main List (Centered and Wide) -->
        <div class="max-w-5xl mx-auto space-y-4">
            <?php if (empty($pending_bookings)): ?>
            <div class="bg-white border border-gray-100 rounded-xl p-10 text-center text-gray-500">
                No pending bookings right now.
            </div>
            <?php endif; ?>
            <?php foreach ($pending_bookings as $booking): ?>
            <div id="booking-card-<?php echo $booking['id']; ?>" class="bg-white border border-gray-100 rounded-xl overflow-hidden hover:shadow-md transition">
                <div class="flex flex-col md:flex-row flex-row">
                    <div class="md:w-64 h-48 md:h-auto overflow-hidden relative bg-slate-900 flex items-center justify-center flex-shrink-0">
                        <?php $approvalImg = getAdminVehicleImageUrl($booking['vehicle_image'] ?? '', $booking['vehicle']); ?>
                        <img src="<?php echo htmlspecialchars($approvalImg); ?>" 
                             alt="<?php echo htmlspecialchars($booking['vehicle']); ?>" 
                             class="w-full h-full object-cover transition-transform duration-300 hover:scale-105" 
                             onerror="this.onerror=null;this.src='../user-site/public/assets/vehicle-default.svg'">
                        <?php if ($booking['priority'] == 'high'): ?>
                        <div class="absolute top-3 left-3 bg-red-600 text-white text-[10px] font-bold px-2 py-1 uppercase rounded shadow">High Priority</div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900 hover:text-cyan-700 transition-colors">
                                    <a href="booking-details.php?id=<?php echo $booking['id']; ?>">
                                        <?php echo htmlspecialchars($booking['vehicle']); ?> - <?php echo htmlspecialchars($booking['event']); ?>
                                    </a>
                                </h3>
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
                        <div class="flex flex-wrap md:flex-nowrap gap-3 pt-4 border-t border-gray-100">
                            <a href="booking-details.php?id=<?php echo $booking['id']; ?>" class="flex-1 bg-cyan-700 hover:bg-cyan-800 text-white font-medium py-2.5 rounded-lg flex items-center justify-center gap-2 transition text-center text-sm">
                                <span class="material-symbols-outlined text-sm">visibility</span>
                                View Details & Process
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<script>
function exportApprovals() {
    const cards = document.querySelectorAll('[id^="booking-card-"]');
    if (cards.length === 0) {
        alert("No pending approvals to export.");
        return;
    }
    exportElementAsPDF('main', 'pending-booking-approvals.pdf');
}
</script>

<?php require_once 'includes/footer.php'; ?>
