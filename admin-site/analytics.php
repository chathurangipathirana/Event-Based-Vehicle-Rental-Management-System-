<?php
$page_title = 'Analytics Dashboard';
require_once 'includes/auth.php';
requireAdminLogin();

// Mock analytics data (converted to LKR - Sri Lankan Rupees)
// Conversion rate used: 1 USD = 295 LKR (approximate)
$total_bookings = 1284;
$total_revenue = 248600 * 295;  // $248,600 × 295 = LKR 73,337,000
$active_bookings = 42;
$fleet_health = 92;

// Weekly data converted to LKR
$weekly_data = [
    ['day' => 'Monday', 'count' => 45, 'revenue' => 42500 * 295],   // LKR 12,537,500
    ['day' => 'Tuesday', 'count' => 52, 'revenue' => 48900 * 295],  // LKR 14,425,500
    ['day' => 'Wednesday', 'count' => 48, 'revenue' => 45100 * 295], // LKR 13,304,500
    ['day' => 'Thursday', 'count' => 61, 'revenue' => 57800 * 295],  // LKR 17,051,000
    ['day' => 'Friday', 'count' => 73, 'revenue' => 68900 * 295],    // LKR 20,325,500
    ['day' => 'Saturday', 'count' => 58, 'revenue' => 54200 * 295],  // LKR 15,989,000
    ['day' => 'Sunday', 'count' => 39, 'revenue' => 36800 * 295],    // LKR 10,856,000
];

// Monthly revenue converted to LKR
$monthly_revenue = [
    ['month' => 'January', 'revenue' => 185000 * 295],   // LKR 54,575,000
    ['month' => 'February', 'revenue' => 192000 * 295],  // LKR 56,640,000
    ['month' => 'March', 'revenue' => 210000 * 295],     // LKR 61,950,000
    ['month' => 'April', 'revenue' => 225000 * 295],     // LKR 66,375,000
    ['month' => 'May', 'revenue' => 248600 * 295],       // LKR 73,337,000
    ['month' => 'June', 'revenue' => 267000 * 295],      // LKR 78,765,000
];

$event_distribution = [
    ['name' => 'Wedding', 'percentage' => 45],
    ['name' => 'Business', 'percentage' => 30],
    ['name' => 'Tours', 'percentage' => 25],
];

// Popular vehicles revenue converted to LKR
$popular_vehicles = [
    ['name' => 'Toyota Axio', 'category' => 'Luxury', 'bookings' => 142, 'revenue' => 120700 * 295],   // LKR 35,606,500
    ['name' => 'Toyota Premio', 'category' => 'Luxury', 'bookings' => 98, 'revenue' => 66640 * 295],  // LKR 19,658,800
    ['name' => 'Honda Vezel', 'category' => 'Luxury SUV', 'bookings' => 87, 'revenue' => 45240 * 295],   // LKR 13,345,800
    ['name' => 'Toyota HiAce', 'category' => 'Executive', 'bookings' => 76, 'revenue' => 34200 * 295],     // LKR 10,089,000
    ['name' => 'Suzuki Wagon R', 'category' => 'Economy', 'bookings' => 54, 'revenue' => 28600 * 295],      // LKR 8,447,000
    ['name' => 'Nissan Sunny', 'category' => 'Economy', 'bookings' => 48, 'revenue' => 24000 * 295],      // LKR 7,080,000
];
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<main class="ml-64 min-h-screen bg-slate-50">
    <div class="p-8 max-w-7xl mx-auto">
        <section class="rounded-[2rem] overflow-hidden mb-10">
            <div class="relative bg-slate-900 text-white p-8 lg:p-10 overflow-hidden">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.24),_transparent_36%)] opacity-70 pointer-events-none"></div>
                <div class="relative grid grid-cols-1 xl:grid-cols-[1.5fr_1fr] gap-8 items-center">
                    <div class="max-w-2xl">
                        <p class="text-xs uppercase tracking-[0.35em] text-slate-400 mb-4">Fleet Manager</p>
                        <h1 class="text-5xl font-semibold tracking-tight">Fleet Analytics</h1>
                        <p class="mt-4 text-slate-300 text-lg leading-8">Comprehensive performance tracking for FleetElite logistics.</p>
                    </div>
                    <div class="flex flex-wrap justify-end gap-3">
                        <button onclick="exportReport()" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-cyan-500 text-white text-sm font-semibold hover:bg-cyan-400 transition-all">
                            <span class="material-symbols-outlined text-sm">download</span>
                            Export Report
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="card-3d p-6 bg-white" style="--card-accent: #0b6b6d;">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Total Bookings</p>
                    <div class="kpi-value"><?php echo number_format($total_bookings); ?></div>
                    <div class="text-xs text-green-600 mt-1">+12% from last month</div>
                </div>
                <div class="card-icon" style="background:var(--card-accent,#0b6b6d)"><span class="material-symbols-outlined">event_note</span></div>
            </div>
            <div class="card-3d p-6 bg-white" style="--card-accent: #0b6b6d;">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Total Revenue</p>
                    <div style="line-height: 1.1;">
                        <div class="text-xs font-medium text-gray-600">LKR</div>
                        <div class="kpi-value"><?php echo number_format($total_revenue); ?></div>
                    </div>
                    <div class="text-xs text-green-600 mt-1">+8.4% from last month</div>
                </div>
                <div class="card-icon" style="background:var(--card-accent,#0b6b6d)"><span class="material-symbols-outlined">payments</span></div>
            </div>
            <div class="card-3d p-6 bg-white" style="--card-accent: #0b6b6d;">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Active Bookings</p>
                    <div class="kpi-value"><?php echo $active_bookings; ?></div>
                    <div class="text-xs text-[#f59e0b] mt-1">Current operations</div>
                </div>
                <div class="card-icon" style="background:var(--card-accent,#0b6b6d)"><span class="material-symbols-outlined">pending_actions</span></div>
            </div>
            <div class="card-3d p-6 bg-white" style="--card-accent: #0b6b6d;">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Fleet Health</p>
                    <div class="kpi-value"><?php echo $fleet_health; ?>%</div>
                    <div class="w-full bg-gray-100 h-1.5 mt-3 rounded-full overflow-hidden">
                        <div class="bg-green-500 h-full" style="width: <?php echo $fleet_health; ?>%"></div>
                    </div>
                </div>
                <div class="card-icon" style="background:var(--card-accent,#0b6b6d)"><span class="material-symbols-outlined">health_and_safety</span></div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Revenue Chart -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Revenue Trend (Last 6 Months)</h2>
                <?php $max_revenue = max(array_column($monthly_revenue, 'revenue')); ?>
                <div class="h-[34rem] flex gap-6">
                    <div class="w-24 flex flex-col justify-between text-xs text-gray-500">
                        <?php for ($tick = 5; $tick >= 0; $tick--): ?>
                        <div class="h-1/6 flex items-center">
                            <span>LKR <?php echo number_format(round($max_revenue * $tick / 5 / 1000)); ?>k</span>
                        </div>
                        <?php endfor; ?>
                        <div class="font-semibold mt-4">Revenue</div>
                    </div>
                    <div class="flex-1 flex flex-col justify-end">
                        <div class="relative flex-1 flex items-end border-l border-b border-gray-200 pb-8">
                            <?php foreach($monthly_revenue as $data): 
                                $height = ($max_revenue > 0) ? round(($data['revenue'] / $max_revenue) * 100, 2) : 0;
                            ?>
                            <div class="flex flex-col items-center" style="width: 5rem;">
                                <div class="w-full h-full flex items-end">
                                    <div class="w-full bg-teal-100 rounded-t-3xl overflow-hidden" style="min-height: 4rem; height: 100%;">
                                        <div class="w-full bg-teal-600 transition-all" style="height: <?php echo $height; ?>%;"></div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-4 grid grid-cols-6 gap-4 text-xs text-gray-500">
                            <?php foreach($monthly_revenue as $data): ?>
                            <div class="text-center"><?php echo substr($data['month'], 0, 3); ?></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3 text-sm font-semibold text-gray-700">Month</div>
                    </div>
                </div>
            </div>

            <!-- Fleet Distribution -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Fleet Distribution by Event Type</h2>
                <div class="space-y-4">
                    <?php foreach($event_distribution as $event): ?>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600"><?php echo $event['name']; ?></span>
                            <span class="font-bold"><?php echo $event['percentage']; ?>%</span>
                        </div>
                        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-red-500 h-full rounded-full" style="width: <?php echo $event['percentage']; ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Popular Vehicles Table -->
        <div class="bg-white rounded-xl shadow-sm border border-[#c0c8ca] overflow-hidden mb-8">
            <div class="p-6 border-b border-[#c0c8ca] flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900">Most Popular Vehicles</h2>
                <button onclick="window.location.href='fleet.php'" class="text-red-600 text-sm hover:underline">View All →</button>
            </div>
            <div class="overflow-x-auto">
                <style>
                    .dashboard-table tbody tr {
                        transition: all 0.3s ease;
                        border-left: 3px solid transparent;
                    }
                    .dashboard-table tbody tr:nth-child(odd) {
                        background-color: #fafbfb;
                    }
                    .dashboard-table tbody tr:nth-child(even) {
                        background-color: #f3f4f4;
                    }
                    .dashboard-table tbody tr:hover {
                        background-color: #fff3e0 !important;
                        border-left-color: #02414a;
                        box-shadow: 0 4px 12px rgba(2, 65, 74, 0.15);
                        transform: translateX(2px);
                    }
                    .dashboard-table tbody tr:hover td {
                        box-shadow: inset 0 0 12px rgba(255, 193, 7, 0.2);
                    }
                    .dashboard-table td {
                        transition: all 0.2s ease;
                        border-right: 1px solid #e0e0e0;
                    }
                    .dashboard-table td:hover {
                        background-color: #ffd54f !important;
                        font-weight: 600;
                        box-shadow: inset 0 0 10px rgba(255, 152, 0, 0.3);
                    }
                    .dashboard-table thead th {
                        background-color: #1e293b;
                        color: #ffffff;
                        font-weight: 700;
                        border-right: 1px solid rgba(255,255,255,0.2);
                    }
                    .dashboard-table thead th:last-child {
                        border-right: none;
                    }
                </style>
                <table class="w-full dashboard-table">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Vehicle</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Bookings</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#c0c8ca]/30 text-[#191c1d]">
                        <?php foreach($popular_vehicles as $vehicle): ?>
                        <tr>
                            <td class="px-6 py-4 font-medium"><?php echo $vehicle['name']; ?></td>
                            <td class="px-6 py-4 text-gray-600"><?php echo $vehicle['category']; ?></td>
                            <td class="px-6 py-4 font-bold"><?php echo $vehicle['bookings']; ?></td>
                            <td class="px-6 py-4 font-bold text-red-600">LKR <?php echo number_format($vehicle['revenue']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Weekly Performance -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-bold text-gray-900">Weekly Performance</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-7 gap-4">
                    <?php foreach($weekly_data as $data): ?>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900"><?php echo $data['count']; ?></div>
                        <div class="text-xs text-gray-500 mt-1"><?php echo substr($data['day'], 0, 3); ?></div>
                        <div class="text-xs font-bold text-red-600 mt-1">LKR <?php echo round($data['revenue'] / 1000); ?>k</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function exportReport() {
    alert('Exporting report as PDF...');
}
</script>

<?php require_once 'includes/footer.php'; ?>
