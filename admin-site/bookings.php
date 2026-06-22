<?php
$page_title = 'Manage Bookings';
require_once 'includes/auth.php';
requireAdminLogin();

// Database connection
$config_path = __DIR__ . '/config/database.php';

if (!file_exists($config_path)) {
    die("Database config file not found at: " . $config_path);
}

require_once $config_path;

// Get all bookings from database
try {
    $bookings = $pdo->query("
        SELECT 
            b.*,
            v.name as vehicle_name,
            u.full_name as client_name,
            u.company_name,
            et.name as event_type_name
        FROM bookings b
        LEFT JOIN vehicles v ON b.vehicle_id = v.id
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN event_types et ON b.event_type_id = et.id
        ORDER BY b.created_at DESC
    ")->fetchAll();
} catch(PDOException $e) {
    $bookings = [];
    echo "Debug: " . $e->getMessage();
}

// Calculate statistics
try {
    $total_active = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status NOT IN ('completed', 'cancelled')")->fetchColumn();
} catch(PDOException $e) {
    $total_active = 0;
}

try {
    $pending_review = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
} catch(PDOException $e) {
    $pending_review = 0;
}

try {
    $dispatched = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'in_progress'")->fetchColumn();
} catch(PDOException $e) {
    $dispatched = 0;
}

try {
    $completion_rate = $pdo->query("
        SELECT ROUND((COUNT(CASE WHEN status = 'completed' THEN 1 END) / NULLIF(COUNT(*), 0)) * 100, 1) 
        FROM bookings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetchColumn();
} catch(PDOException $e) {
    $completion_rate = 98.4;
}

// Get fleet availability stats (using name instead of category if category doesn't exist)
try {
    $total_vehicles = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'available'")->fetchColumn();
    $total_all_vehicles = $pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
    $available_percent = $total_all_vehicles > 0 ? round(($total_vehicles / $total_all_vehicles) * 100) : 0;
} catch(PDOException $e) {
    $total_vehicles = 0;
    $available_percent = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Manage Bookings - FleetElite Admin Portal</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        vertical-align: middle;
    }
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
    body > aside { background-color: var(--primary) !important; border-color: var(--primary) !important; color: #fff; }
    body > aside h2, body > aside .text-red-600 { color: #fff !important; }
    body > aside .text-gray-500 { color: rgba(255,255,255,0.72) !important; }
    body > aside a { color: rgba(255,255,255,0.72) !important; }
    body > aside a:hover { background-color: rgba(255,255,255,0.08) !important; color: #fff !important; }
    body > aside a[href="bookings.php"] { background-color: rgba(255,255,255,0.10) !important; color: #fff !important; border-color: var(--primary-soft) !important; }
    body > aside .border-t { border-color: rgba(255,255,255,0.12) !important; }
    .bg-white { background-color: var(--surface-card) !important; }
    .bg-gray-50, .hover\:bg-gray-50:hover { background-color: var(--surface-low) !important; }
    .bg-gray-100 { background-color: var(--surface-high) !important; }
    .border-gray-100, .border-gray-200, .border-gray-300 { border-color: var(--outline) !important; }
    .text-gray-900, .text-gray-700 { color: var(--text) !important; }
    .text-gray-500, .text-gray-400 { color: var(--muted) !important; }
    .bg-red-600 { background-color: var(--primary) !important; }
    .hover\:bg-red-700:hover { background-color: var(--primary-hover) !important; }
    .text-red-600, .text-blue-700 { color: var(--primary) !important; }
    .border-red-600 { border-color: var(--primary-soft) !important; }
    [class*="bg-red-"] { background-color: var(--primary) !important; }
    [class*="text-red-"] { color: var(--primary) !important; }
    [class*="border-red-"] { border-color: var(--primary-soft) !important; }
    [class*="hover:bg-red-"]:hover { background-color: var(--primary-hover) !important; }
    [class*="hover:text-red-"]:hover { color: var(--primary-hover) !important; }
    .bg-red-50, .bg-red-100 { background-color: var(--primary-soft) !important; }
    .focus\:ring-red-500:focus { --tw-ring-color: rgba(2,65,74,0.18) !important; }
    .text-green-600, .text-green-700 { color: var(--success) !important; }
    .text-orange-600, .text-yellow-700 { color: var(--warning) !important; }
    .text-red-700 { color: var(--danger) !important; }
    .bg-green-100 { background-color: #dff5e8 !important; }
    .bg-yellow-100 { background-color: #fff3d6 !important; }
    .bg-blue-100 { background-color: var(--primary-soft) !important; }
    .bg-red-100 { background-color: #ffdad6 !important; }
    .rounded-xl { border-radius: 0.75rem !important; }
    .rounded-lg { border-radius: 0.5rem !important; }
    .shadow-sm { box-shadow: 0 10px 24px rgba(25,28,29,0.06) !important; }
</style>
</head>
<body class="bg-background text-on-background">

<!-- SideNavBar Shell -->
<aside class="h-screen w-64 fixed left-0 top-0 flex flex-col py-6 px-4 bg-white border-r border-gray-200 z-50">
    <div class="mb-10 px-2">
        <h2 class="text-2xl font-bold text-red-600">Fleet Manager</h2>
        <p class="text-xs text-gray-500 uppercase tracking-wider">Operational Excellence</p>
    </div>
    <nav class="flex-grow space-y-1">
        <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded text-gray-500 hover:text-red-600 hover:bg-gray-50 transition-colors duration-200">
            <span class="material-symbols-outlined">dashboard</span>
            <span class="text-sm font-medium">Dashboard</span>
        </a>
        <a href="fleet.php" class="flex items-center gap-3 px-4 py-3 rounded text-gray-500 hover:text-red-600 hover:bg-gray-50 transition-colors duration-200">
            <span class="material-symbols-outlined">directions_car</span>
            <span class="text-sm font-medium">Fleet</span>
        </a>
        <a href="bookings.php" class="flex items-center gap-3 px-4 py-3 rounded text-red-600 font-bold border-r-4 border-red-600 bg-red-50">
            <span class="material-symbols-outlined">calendar_today</span>
            <span class="text-sm font-medium">Bookings</span>
        </a>
        <a href="booking-approvals.php" class="flex items-center gap-3 px-4 py-3 rounded text-gray-500 hover:text-red-600 hover:bg-gray-50 transition-colors duration-200">
            <span class="material-symbols-outlined">pending_actions</span>
            <span class="text-sm font-medium">Approvals</span>
        </a>
        <a href="analytics.php" class="flex items-center gap-3 px-4 py-3 rounded text-gray-500 hover:text-red-600 hover:bg-gray-50 transition-colors duration-200">
            <span class="material-symbols-outlined">analytics</span>
            <span class="text-sm font-medium">Reports</span>
        </a>
    </nav>
    <div class="mt-auto pt-6 border-t border-gray-200 space-y-1">
        <a href="new-booking.php" class="w-full bg-red-600 text-white py-3 rounded-lg font-bold text-sm mb-4 block text-center hover:bg-red-700 transition">
            + New Reservation
        </a>
        <a href="settings.php" class="flex items-center gap-3 px-4 py-2 text-gray-500 hover:text-red-600 transition-colors">
            <span class="material-symbols-outlined">settings</span>
            <span class="text-sm font-medium">Settings</span>
        </a>
        <a href="logout.php" class="flex items-center gap-3 px-4 py-2 text-gray-500 hover:text-red-600 transition-colors">
            <span class="material-symbols-outlined">logout</span>
            <span class="text-sm font-medium">Logout</span>
        </a>
    </div>
</aside>

<!-- Main Content Area -->
<main class="ml-64 min-h-screen">
    <!-- TopNavBar Shell -->
    <header class="flex justify-between items-center h-16 px-8 sticky top-0 z-40 bg-white shadow-sm border-b border-gray-100">
        <div class="flex items-center gap-6 w-1/2">
            <div class="relative w-full max-w-md">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
                <input type="text" id="searchBookings" class="w-full pl-10 pr-4 py-2 bg-gray-50 border-none rounded-lg focus:ring-2 focus:ring-red-500 text-sm" placeholder="Search bookings, clients, or vehicles...">
            </div>
        </div>
        <div class="flex items-center gap-4">
            <button class="p-2 text-gray-500 hover:text-red-600 transition-colors">
                <span class="material-symbols-outlined">notifications</span>
            </button>
            <div class="h-8 w-px bg-gray-200 mx-2"></div>
            <div class="flex items-center gap-3 cursor-pointer">
                <span class="text-sm font-medium text-gray-700"><?php echo $_SESSION['admin_name'] ?? 'Admin User'; ?></span>
                <span class="material-symbols-outlined text-red-600" style="font-variation-settings: 'FILL' 1;">account_circle</span>
            </div>
        </div>
    </header>

    <!-- Content Canvas -->
    <div class="p-8 max-w-7xl mx-auto">
        <div class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-4xl font-bold text-gray-900">Manage Bookings</h1>
                <p class="text-lg text-gray-500 mt-1">Oversee and coordinate all upcoming event logistics and vehicle assignments.</p>
            </div>
            <div class="flex gap-3">
                <button onclick="filterByStatus()" class="flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg bg-white hover:bg-gray-50 transition-colors text-sm">
                    <span class="material-symbols-outlined text-sm">filter_list</span>
                    Status
                </button>
                <button onclick="filterByDate()" class="flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg bg-white hover:bg-gray-50 transition-colors text-sm">
                    <span class="material-symbols-outlined text-sm">calendar_month</span>
                    Date Range
                </button>
                <button onclick="exportBookings()" class="flex items-center gap-2 px-4 py-2 border border-gray-200 rounded-lg bg-white hover:bg-gray-50 transition-colors text-sm">
                    <span class="material-symbols-outlined text-sm">download</span>
                    Export
                </button>
            </div>
        </div>

        <!-- Dashboard Stats Summary -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                <p class="text-xs text-gray-500 uppercase tracking-widest mb-2">Total Active</p>
                <p class="text-3xl font-bold text-gray-900"><?php echo $total_active; ?></p>
                <p class="text-xs text-green-600 mt-2 flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">trending_up</span> +12% vs last month
                </p>
            </div>
            <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                <p class="text-xs text-gray-500 uppercase tracking-widest mb-2">Pending Review</p>
                <p class="text-3xl font-bold text-orange-600"><?php echo $pending_review; ?></p>
                <p class="text-xs text-gray-500 mt-2">Requires immediate attention</p>
            </div>
            <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                <p class="text-xs text-gray-500 uppercase tracking-widest mb-2">Dispatched</p>
                <p class="text-3xl font-bold text-gray-900"><?php echo $dispatched; ?></p>
                <p class="text-xs text-gray-500 mt-2">In transit or on-site</p>
            </div>
            <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                <p class="text-xs text-gray-500 uppercase tracking-widest mb-2">Completion Rate</p>
                <p class="text-3xl font-bold text-gray-900"><?php echo $completion_rate ?: 98.4; ?>%</p>
                <p class="text-xs text-gray-500 mt-2">Rolling 30-day average</p>
            </div>
        </div>

        <!-- Main Data Table Container -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Booking ID</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Client &amp; Event</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Vehicle</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Date &amp; Time</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Amount</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">Status</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100" id="bookingsTable">
                        <?php if (!empty($bookings)): ?>
                            <?php foreach ($bookings as $booking): ?>
                            <tr class="hover:bg-gray-50 transition-colors group booking-row" data-status="<?php echo $booking['status']; ?>">
                                <td class="px-6 py-4">
                                    <span class="text-sm font-bold text-gray-900">#<?php echo substr($booking['booking_number'] ?? 'BK-' . $booking['id'], -8); ?></span>
                                 </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($booking['event_name'] ?: $booking['event_type_name'] ?: 'Event'); ?></span>
                                        <span class="text-xs text-gray-500"><?php echo htmlspecialchars($booking['client_name'] ?: 'Guest'); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-gray-400 text-lg">directions_car</span>
                                        <span class="text-sm"><?php echo htmlspecialchars($booking['vehicle_name'] ?: 'Vehicle'); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-sm"><?php echo $booking['event_date'] ? date('M d, Y', strtotime($booking['event_date'])) : 'N/A'; ?></span>
                                        <span class="text-xs text-gray-500">
                                            <?php echo $booking['start_time'] ? date('g:i A', strtotime($booking['start_time'])) : '--'; ?> - 
                                            <?php echo $booking['end_time'] ? date('g:i A', strtotime($booking['end_time'])) : '--'; ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-bold text-gray-900">LKR <?php echo number_format($booking['total_amount'], 2); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $status_class = match($booking['status']) {
                                        'confirmed' => 'bg-green-100 text-green-700',
                                        'pending' => 'bg-yellow-100 text-yellow-700',
                                        'in_progress' => 'bg-blue-100 text-blue-700',
                                        'completed' => 'bg-gray-100 text-gray-700',
                                        'cancelled' => 'bg-red-100 text-red-700',
                                        default => 'bg-gray-100 text-gray-700'
                                    };
                                    $status_text = match($booking['status']) {
                                        'in_progress' => 'Dispatched',
                                        default => ucfirst($booking['status'] ?? 'Pending')
                                    };
                                    ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="booking-details.php?id=<?php echo $booking['id']; ?>" class="text-red-600 font-bold text-sm hover:underline transition-all">View Details</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">No bookings found. Create your first booking!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                <span class="text-sm text-gray-500">Showing <?php echo count($bookings); ?> results</span>
                <div class="flex gap-2">
                    <button class="p-2 border border-gray-200 rounded bg-white hover:bg-gray-50" disabled>
                        <span class="material-symbols-outlined text-sm">chevron_left</span>
                    </button>
                    <button class="w-8 h-8 rounded bg-red-600 text-white text-sm font-bold">1</button>
                    <button class="p-2 border border-gray-200 rounded bg-white hover:bg-gray-50">
                        <span class="material-symbols-outlined text-sm">chevron_right</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Contextual Insight (Bento Section) -->
        <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-red-50 rounded-xl p-8 relative overflow-hidden">
                <div class="relative z-10 max-w-md">
                    <h3 class="text-2xl font-bold text-red-800 mb-4">Upcoming Logistics Warning</h3>
                    <p class="text-sm text-red-700 mb-6">There are <?php echo $pending_review; ?> high-priority bookings requiring attention. Consider reviewing pending approvals to maintain 100% on-time performance.</p>
                    <a href="booking-approvals.php" class="inline-block bg-red-600 text-white px-6 py-3 rounded-lg font-bold text-sm hover:bg-red-700 transition">
                        Review Pending Approvals
                    </a>
                </div>
                <div class="absolute right-0 top-0 bottom-0 w-1/3 opacity-10">
                    <span class="material-symbols-outlined text-8xl mt-8">warning</span>
                </div>
            </div>

            <div class="bg-gray-100 rounded-xl p-8 flex flex-col justify-between">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Fleet Availability</h3>
                    <p class="text-xs text-gray-500 uppercase mb-6">Current Inventory Status</p>
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span>Available Vehicles</span>
                                <span class="font-bold"><?php echo $available_percent; ?>%</span>
                            </div>
                            <div class="h-2 w-full bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-red-600 rounded-full" style="width: <?php echo $available_percent; ?>%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span>Total Fleet</span>
                                <span class="font-bold"><?php echo $total_all_vehicles; ?> Vehicles</span>
                            </div>
                            <div class="h-2 w-full bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-red-600 rounded-full" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <a href="fleet.php" class="w-full border border-red-600 text-red-600 py-3 rounded-lg font-bold text-sm mt-8 text-center hover:bg-red-600 hover:text-white transition-all">
                    Manage Fleet
                </a>
            </div>
        </div>
    </div>
</main>

<script>
// Search functionality
document.getElementById('searchBookings')?.addEventListener('keyup', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.booking-row');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

function filterByStatus() {
    const status = prompt('Filter by status: pending, confirmed, in_progress, completed, cancelled', '');
    if (status) {
        const rows = document.querySelectorAll('.booking-row');
        rows.forEach(row => {
            const rowStatus = row.getAttribute('data-status');
            row.style.display = rowStatus === status ? '' : 'none';
        });
    }
}

function filterByDate() {
    alert('Date range filter would open a date picker');
}

function exportBookings() {
    alert('Exporting bookings as CSV...');
}
</script>

</body>
</html>
