<?php
$page_title = 'Dashboard';
require_once 'includes/auth.php';
requireAdminLogin();

$stats = [
    'total_bookings' => 1284,
    'revenue' => 248600,
    'active_vehicles' => 42,
    'pending_approvals' => 14,
    'total_vehicles' => 50,
    'maintenance' => 8
];

$recent_bookings = [
    ['id' => '#FE-8821', 'customer' => 'Global Horizon Events', 'event' => 'Corporate Gala', 'vehicle' => 'Mercedes S-Class', 'amount' => 1450, 'status' => 'pending', 'date' => 'Oct 24'],
    ['id' => '#FE-8819', 'customer' => 'Vanguard Logistics', 'event' => 'Logistics Contract', 'vehicle' => 'Freightliner M2', 'amount' => 2840, 'status' => 'pending', 'date' => 'Oct 23'],
    ['id' => '#FE-8790', 'customer' => 'Artisan Catering Co.', 'event' => 'Catering Delivery', 'vehicle' => 'Ford Transit Van', 'amount' => 820, 'status' => 'completed', 'date' => 'Oct 22'],
    ['id' => '#FE-8785', 'customer' => 'Summit Tech', 'event' => 'Tech Conference', 'vehicle' => 'Audi Q8', 'amount' => 1100, 'status' => 'confirmed', 'date' => 'Oct 21'],
];
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<main class="ml-64 min-h-screen p-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Dashboard Overview</h1>
            <p class="text-gray-600 mt-1">Welcome back, <?php echo $_SESSION['admin_name']; ?></p>
        </div>

        <!-- Stats Grid - UI 2 Style -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 uppercase">Total Bookings</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $stats['total_bookings']; ?></p>
                        <p class="text-xs text-green-600 mt-1">+12% from last month</p>
                    </div>
                    <span class="material-symbols-outlined text-3xl text-gray-400">event_note</span>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 uppercase">Revenue</p>
                        <p class="text-3xl font-bold text-green-600">$<?php echo number_format($stats['revenue']); ?></p>
                        <p class="text-xs text-green-600 mt-1">+8.4% from last month</p>
                    </div>
                    <span class="material-symbols-outlined text-3xl text-gray-400">payments</span>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 uppercase">Active Vehicles</p>
                        <p class="text-3xl font-bold text-gray-900"><?php echo $stats['active_vehicles']; ?>/<?php echo $stats['total_vehicles']; ?></p>
                        <p class="text-xs text-red-600 mt-1"><?php echo $stats['maintenance']; ?> in maintenance</p>
                    </div>
                    <span class="material-symbols-outlined text-3xl text-gray-400">directions_car</span>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 uppercase">Pending Approvals</p>
                        <p class="text-3xl font-bold text-orange-600"><?php echo $stats['pending_approvals']; ?></p>
                        <p class="text-xs text-orange-600 mt-1">Requires attention</p>
                    </div>
                    <span class="material-symbols-outlined text-3xl text-gray-400">pending_actions</span>
                </div>
            </div>
        </div>

        <!-- Recent Bookings Table - UI 2 Style -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-lg font-semibold">Recent Bookings</h3>
                <a href="bookings.php" class="text-red-600 text-sm hover:underline">View All →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Booking ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vehicle</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($recent_bookings as $booking): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium"><?php echo $booking['id']; ?></td>
                            <td class="px-6 py-4"><?php echo $booking['customer']; ?></td>
                            <td class="px-6 py-4"><?php echo $booking['vehicle']; ?></td>
                            <td class="px-6 py-4">$<?php echo number_format($booking['amount'], 2); ?></td>
                            <td class="px-6 py-4"><?php echo $booking['date']; ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full <?php 
                                    echo $booking['status'] == 'pending' ? 'bg-yellow-100 text-yellow-700' : 
                                        ($booking['status'] == 'confirmed' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'); ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>





<?php require_once 'includes/footer.php'; ?>