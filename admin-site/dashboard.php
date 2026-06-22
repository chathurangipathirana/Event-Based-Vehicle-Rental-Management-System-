<?php
$page_title = 'Dashboard';
require_once 'includes/auth.php';
requireAdminLogin();

// Stats converted to Sri Lankan Rupees (LKR)
// Conversion rate: 1 USD = 295 LKR
$stats = [
    'total_bookings' => 1284,
    'revenue' => 248600 * 295,  // $248,600 × 295 = LKR 73,337,000
    'active_vehicles' => 42,
    'pending_approvals' => 14,
    'total_vehicles' => 50,
    'maintenance' => 8
];

// Recent bookings with amounts converted to LKR
$recent_bookings = [
    ['id' => '#FE-8821', 'customer' => 'Global Horizon Events', 'event' => 'Corporate Gala', 'vehicle' => 'Mercedes S-Class', 'amount' => 1450 * 295, 'status' => 'pending', 'date' => 'Oct 24'],   // LKR 427,750
    ['id' => '#FE-8819', 'customer' => 'Vanguard Logistics', 'event' => 'Logistics Contract', 'vehicle' => 'Freightliner M2', 'amount' => 2840 * 295, 'status' => 'pending', 'date' => 'Oct 23'],   // LKR 837,800
    ['id' => '#FE-8790', 'customer' => 'Artisan Catering Co.', 'event' => 'Catering Delivery', 'vehicle' => 'Ford Transit Van', 'amount' => 820 * 295, 'status' => 'completed', 'date' => 'Oct 22'],   // LKR 241,900
    ['id' => '#FE-8785', 'customer' => 'Summit Tech', 'event' => 'Tech Conference', 'vehicle' => 'Audi Q8', 'amount' => 1100 * 295, 'status' => 'confirmed', 'date' => 'Oct 21'],   // LKR 324,500
];
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<main class="ml-64 min-h-screen p-8 bg-[#f9f9fa]">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-[#191c1d]">Dashboard Overview</h1>
            <p class="text-[#5e5e5e] mt-1">Welcome back, <?php echo $_SESSION['admin_name']; ?></p>
        </div>

        <!-- Stats Grid - UI 2 Style -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-[#f3f4f4] rounded-xl shadow-sm p-6 border border-[#c0c8ca] border-b-4 border-b-[#02414a]">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-[#40484a] uppercase">Total Bookings</p>
                        <p class="text-3xl font-bold text-[#191c1d]"><?php echo $stats['total_bookings']; ?></p>
                        <p class="text-xs text-[#176a3a] mt-1">+12% from last month</p>
                    </div>
                    <span class="material-symbols-outlined text-2xl text-white bg-[#02414a] w-12 h-12 rounded-full flex items-center justify-center">event_note</span>
                </div>
            </div>
            <div class="bg-[#f3f4f4] rounded-xl shadow-sm p-6 border border-[#c0c8ca] border-b-4 border-b-[#5e5e5e]">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-[#40484a] uppercase">Revenue</p>
                        <p class="text-3xl font-bold text-[#191c1d]">LKR <?php echo number_format($stats['revenue']); ?></p>
                        <p class="text-xs text-[#176a3a] mt-1">+8.4% from last month</p>
                    </div>
                    <span class="material-symbols-outlined text-2xl text-white bg-[#02414a] w-12 h-12 rounded-full flex items-center justify-center">payments</span>
                </div>
            </div>
            <div class="bg-[#f3f4f4] rounded-xl shadow-sm p-6 border border-[#c0c8ca] border-b-4 border-b-[#6f4924]">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-[#40484a] uppercase">Active Vehicles</p>
                        <p class="text-3xl font-bold text-[#191c1d]"><?php echo $stats['active_vehicles']; ?>/<?php echo $stats['total_vehicles']; ?></p>
                        <p class="text-xs text-[#ba1a1a] mt-1"><?php echo $stats['maintenance']; ?> in maintenance</p>
                    </div>
                    <span class="material-symbols-outlined text-2xl text-white bg-[#02414a] w-12 h-12 rounded-full flex items-center justify-center">directions_car</span>
                </div>
            </div>
            <div class="bg-[#f3f4f4] rounded-xl shadow-sm p-6 border border-[#c0c8ca] border-b-4 border-b-[#f1bc8e]">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-[#40484a] uppercase">Pending Approvals</p>
                        <p class="text-3xl font-bold text-[#191c1d]"><?php echo $stats['pending_approvals']; ?></p>
                        <p class="text-xs text-[#8a5200] mt-1">Requires attention</p>
                    </div>
                    <span class="material-symbols-outlined text-2xl text-white bg-[#02414a] w-12 h-12 rounded-full flex items-center justify-center">pending_actions</span>
                </div>
            </div>
        </div>

        <!-- Recent Bookings Table - UI 2 Style -->
        <div class="bg-white rounded-xl shadow-sm border border-[#c0c8ca] overflow-hidden">
            <div class="px-6 py-4 border-b border-[#c0c8ca] flex justify-between items-center">
                <h3 class="text-lg font-semibold text-[#191c1d]">Recent Bookings</h3>
                <a href="bookings.php" class="text-red-600 text-sm hover:underline">View All →</a>
            </div>
            <div class="overflow-x-auto">
                <style>
                    .booking-table tbody tr {
                        transition: all 0.3s ease;
                        border-left: 3px solid transparent;
                    }
                    .booking-table tbody tr:nth-child(odd) {
                        background-color: #fafbfb;
                    }
                    .booking-table tbody tr:nth-child(even) {
                        background-color: #f3f4f4;
                    }
                    .booking-table tbody tr:hover {
                        background-color: #fff3e0 !important;
                        border-left-color: #02414a;
                        box-shadow: 0 4px 12px rgba(2, 65, 74, 0.15);
                        transform: translateX(2px);
                    }
                    .booking-table tbody tr:hover td {
                        box-shadow: inset 0 0 12px rgba(255, 193, 7, 0.2);
                    }
                    .booking-table td {
                        transition: all 0.2s ease;
                        border-right: 1px solid #e0e0e0;
                    }
                    .booking-table td:hover {
                        background-color: #ffd54f !important;
                        font-weight: 600;
                        box-shadow: inset 0 0 10px rgba(255, 152, 0, 0.3);
                    }
                    .booking-table thead th {
                        background: linear-gradient(180deg, #02414a 0%, #0d5260 100%);
                        color: #ffffff;
                        font-weight: 700;
                        border-right: 1px solid rgba(255,255,255,0.2);
                    }
                    .booking-table thead th:last-child {
                        border-right: none;
                    }
                </style>
                <table class="w-full booking-table">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Booking ID</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Vehicle</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#c0c8ca]/30 text-[#191c1d]">
                        <?php foreach ($recent_bookings as $booking): ?>
                        <tr>
                            <td class="px-6 py-4 font-semibold"><?php echo $booking['id']; ?></td>
                            <td class="px-6 py-4"><?php echo $booking['customer']; ?></td>
                            <td class="px-6 py-4"><?php echo $booking['vehicle']; ?></td>
                            <td class="px-6 py-4 font-medium">LKR <?php echo number_format($booking['amount'], 2); ?></td>
                            <td class="px-6 py-4"><?php echo $booking['date']; ?></td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 text-xs rounded-full font-semibold <?php 
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
