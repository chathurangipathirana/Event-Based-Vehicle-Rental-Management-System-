<?php
$page_title = 'Analytics Dashboard';
require_once 'includes/auth.php';
requireAdminLogin();

// Mock analytics data (converted to LKR - Sri Lankan Rupees)
// Conversion rate used: 1 USD = 295 LKR (approximate)
$total_bookings = 1284;
$total_revenue = 248600 * 295;  // $248,600 × 295 = Rs. 73,337,000
$active_bookings = 42;
$fleet_health = 92;

// Weekly data converted to LKR
$weekly_data = [
    ['day' => 'Monday', 'count' => 45, 'revenue' => 42500 * 295],   // Rs. 12,537,500
    ['day' => 'Tuesday', 'count' => 52, 'revenue' => 48900 * 295],  // Rs. 14,425,500
    ['day' => 'Wednesday', 'count' => 48, 'revenue' => 45100 * 295], // Rs. 13,304,500
    ['day' => 'Thursday', 'count' => 61, 'revenue' => 57800 * 295],  // Rs. 17,051,000
    ['day' => 'Friday', 'count' => 73, 'revenue' => 68900 * 295],    // Rs. 20,325,500
    ['day' => 'Saturday', 'count' => 58, 'revenue' => 54200 * 295],  // Rs. 15,989,000
    ['day' => 'Sunday', 'count' => 39, 'revenue' => 36800 * 295],    // Rs. 10,856,000
];

// Monthly revenue converted to LKR
$monthly_revenue = [
    ['month' => 'January', 'revenue' => 185000 * 295],   // Rs. 54,575,000
    ['month' => 'February', 'revenue' => 192000 * 295],  // Rs. 56,640,000
    ['month' => 'March', 'revenue' => 210000 * 295],     // Rs. 61,950,000
    ['month' => 'April', 'revenue' => 225000 * 295],     // Rs. 66,375,000
    ['month' => 'May', 'revenue' => 248600 * 295],       // Rs. 73,337,000
    ['month' => 'June', 'revenue' => 267000 * 295],      // Rs. 78,765,000
];

$event_distribution = [
    ['name' => 'Wedding', 'percentage' => 45],
    ['name' => 'Business', 'percentage' => 30],
    ['name' => 'Tours', 'percentage' => 25],
];

// Popular vehicles revenue converted to LKR
$popular_vehicles = [
    ['name' => 'Porsche 911 GT3', 'category' => 'Sports', 'bookings' => 142, 'revenue' => 120700 * 295],   // Rs. 35,606,500
    ['name' => 'Range Rover SV', 'category' => 'Luxury SUV', 'bookings' => 98, 'revenue' => 66640 * 295],  // Rs. 19,658,800
    ['name' => 'Mercedes S-Class', 'category' => 'Luxury', 'bookings' => 87, 'revenue' => 45240 * 295],   // Rs. 13,345,800
    ['name' => 'BMW 7 Series', 'category' => 'Executive', 'bookings' => 76, 'revenue' => 34200 * 295],     // Rs. 10,089,000
];
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<main class="ml-64 min-h-screen p-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-4xl font-bold text-gray-900">Fleet Analytics</h1>
                <p class="text-gray-500 mt-2">Comprehensive performance tracking for FleetElite logistics.</p>
            </div>
            <button onclick="exportReport()" class="bg-red-600 text-white px-4 py-2 flex items-center gap-2 font-medium hover:bg-red-700 transition rounded-lg">
                <span class="material-symbols-outlined text-lg">download</span> Export Report
            </button>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <span class="text-xs font-bold text-gray-400 uppercase">Total Bookings</span>
                <div class="flex justify-between items-center mt-2">
                    <span class="text-3xl font-black text-gray-900"><?php echo number_format($total_bookings); ?></span>
                    <span class="text-green-600 bg-green-50 px-2 py-0.5 rounded text-xs font-bold">+12%</span>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <span class="text-xs font-bold text-gray-400 uppercase">Total Revenue</span>
                <div class="flex justify-between items-center mt-2">
                    <span class="text-3xl font-black text-green-600">Rs. <?php echo number_format($total_revenue); ?></span>
                    <span class="text-green-600 bg-green-50 px-2 py-0.5 rounded text-xs font-bold">+8.4%</span>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <span class="text-xs font-bold text-gray-400 uppercase">Active Bookings</span>
                <div class="flex justify-between items-center mt-2">
                    <span class="text-3xl font-black text-gray-900"><?php echo $active_bookings; ?></span>
                    <span class="text-orange-600 bg-orange-50 px-2 py-0.5 rounded text-xs font-bold">Current</span>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <span class="text-xs font-bold text-gray-400 uppercase">Fleet Health</span>
                <div class="flex justify-between items-center mt-2">
                    <span class="text-3xl font-black text-gray-900"><?php echo $fleet_health; ?>%</span>
                    <span class="text-green-600 bg-green-50 px-2 py-0.5 rounded text-xs font-bold">Good</span>
                </div>
                <div class="w-full bg-gray-100 h-1.5 mt-4 rounded-full overflow-hidden">
                    <div class="bg-green-500 h-full" style="width: <?php echo $fleet_health; ?>%"></div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Revenue Chart -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Revenue Trend (Last 6 Months)</h2>
                <div class="h-64 flex items-end gap-4">
                    <?php 
                    $max_revenue = max(array_column($monthly_revenue, 'revenue'));
                    foreach($monthly_revenue as $data): 
                        $height = ($data['revenue'] / $max_revenue) * 100;
                    ?>
                    <div class="flex-1 flex flex-col items-center">
                        <div class="w-full bg-red-100 rounded-t-lg transition-all hover:bg-red-200" style="height: <?php echo $height; ?>%">
                            <div class="w-full bg-red-500 rounded-t-lg transition-all" style="height: <?php echo $height; ?>%"></div>
                        </div>
                        <span class="text-xs text-gray-500 mt-2"><?php echo substr($data['month'], 0, 3); ?></span>
                        <span class="text-xs font-bold text-gray-700">Rs. <?php echo round($data['revenue'] / 1000); ?>k</span>
                    </div>
                    <?php endforeach; ?>
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
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900">Most Popular Vehicles</h2>
                <button onclick="window.location.href='fleet.php'" class="text-red-600 text-sm hover:underline">View All →</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-xs font-bold text-gray-400 uppercase">Vehicle</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-400 uppercase">Category</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-400 uppercase">Bookings</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-400 uppercase">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach($popular_vehicles as $vehicle): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium"><?php echo $vehicle['name']; ?></td>
                            <td class="px-6 py-4 text-gray-600"><?php echo $vehicle['category']; ?></td>
                            <td class="px-6 py-4 font-bold"><?php echo $vehicle['bookings']; ?></td>
                            <td class="px-6 py-4 font-bold text-red-600">Rs. <?php echo number_format($vehicle['revenue']); ?></td>
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
                        <div class="text-xs font-bold text-red-600 mt-1">Rs. <?php echo round($data['revenue'] / 1000); ?>k</div>
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