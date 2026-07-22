<?php
$page_title = 'Dashboard';
require_once 'includes/auth.php';
requireAdminLogin();
require_once 'config/database.php';

// Stats converted to Sri Lankan Rupees (LKR)
$stats = [
    'total_bookings' => 0,
    'revenue' => 0,
    'active_vehicles' => 0,
    'pending_approvals' => 0,
    'total_vehicles' => 0,
    'maintenance' => 0
];

try {
    $stats['total_bookings'] = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    // Sum of bookings that are confirmed, in progress or completed
    $stats['revenue'] = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE status IN ('confirmed', 'in_progress', 'completed')")->fetchColumn();
    $stats['active_vehicles'] = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status != 'maintenance'")->fetchColumn();
    $stats['pending_approvals'] = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
    $stats['total_vehicles'] = $pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
    $stats['maintenance'] = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'maintenance'")->fetchColumn();
} catch(PDOException $e) {
    // If tables don't exist yet, keep them at 0
}

// Recent bookings with amounts
$recent_bookings = [];
try {
    $stmt = $pdo->query("
        SELECT 
            b.id as real_id,
            COALESCE(b.booking_number, CONCAT('#FE-', b.id)) as display_id,
            COALESCE(u.full_name, 'Guest') as customer,
            COALESCE(v.name, 'Unknown Vehicle') as vehicle,
            b.total_amount as amount,
            DATE_FORMAT(b.event_date, '%b %d') as date,
            b.status
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN vehicles v ON b.vehicle_id = v.id
        ORDER BY b.created_at DESC
        LIMIT 6
    ");
    $recent_bookings = $stmt->fetchAll();
} catch(PDOException $e) {
    // Fallback if query fails
}
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
                        <p class="text-xs uppercase tracking-[0.35em] text-slate-400 mb-4">Welcome back</p>
                        <h1 class="text-5xl font-semibold tracking-tight">Dashboard Overview</h1>
                        <p class="mt-4 text-slate-300 text-lg leading-8"><?php echo $_SESSION['admin_name']; ?>, here's your fleet management summary.</p>
                    </div>
                    <div class="flex flex-wrap justify-end gap-3">
                        <button onclick="exportDashboard()" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-cyan-500 text-white text-sm font-semibold hover:bg-cyan-400 transition-all">
                            <span class="material-symbols-outlined text-sm">download</span>
                            Export Report
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <style>
            .card-3d {
                background: linear-gradient(180deg, #ffffff, #fbfbfd);
                border-radius: 0.75rem;
                box-shadow: 0 6px 16px rgba(2,65,74,0.06), 0 1px 4px rgba(2,65,74,0.03);
                transform: translateY(0);
                transition: transform .22s ease, box-shadow .22s ease;
                position: relative;
                z-index: 1;
            }
            .card-3d:hover { transform: translateY(-6px); box-shadow: 0 18px 30px rgba(2,65,74,0.10); }
        </style>

        <!-- Stats Grid - UI 2 Style -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="card-3d p-6 bg-white" style="--card-accent: #0b6b6d;">
                    <div>
                        <p class="text-sm text-gray-500 uppercase">Total Bookings</p>
                        <div class="kpi-value"><?php echo $stats['total_bookings']; ?></div>
                        <div class="text-xs text-green-600 mt-1">+12% from last month</div>
                    </div>
                    <div class="card-icon" style="background:var(--card-accent,#0b6b6d)"><span class="material-symbols-outlined">event_note</span></div>
                </div>

                <div class="card-3d p-6 bg-white" style="--card-accent: #0b6b6d;">
                    <div>
                        <p class="text-sm text-gray-500 uppercase">Revenue</p>
                        <div style="line-height: 1.1;">
                            <div class="text-xs font-medium text-gray-600">LKR</div>
                            <div class="kpi-value"><?php echo number_format($stats['revenue']); ?></div>
                        </div>
                        <div class="text-xs text-green-600 mt-1">+8.4% from last month</div>
                    </div>
                    <div class="card-icon" style="background:var(--card-accent,#0b6b6d)"><span class="material-symbols-outlined">payments</span></div>
                </div>

                <div class="card-3d p-6 bg-white" style="--card-accent: #0b6b6d;">
                    <div>
                        <p class="text-sm text-gray-500 uppercase">Active Vehicles</p>
                        <div class="kpi-value"><?php echo $stats['active_vehicles']; ?>/<?php echo $stats['total_vehicles']; ?></div>
                        <div class="text-xs text-red-600 mt-1"><?php echo $stats['maintenance']; ?> in maintenance</div>
                    </div>
                    <div class="card-icon" style="background:var(--card-accent,#0b6b6d)"><span class="material-symbols-outlined">directions_car</span></div>
                </div>

                <div class="card-3d p-6 bg-white" style="--card-accent: #b36b2a;">
                    <div>
                        <p class="text-sm text-gray-500 uppercase">Pending Approvals</p>
                        <div class="kpi-value"><?php echo $stats['pending_approvals']; ?></div>
                        <div class="text-xs text-[#f59e0b] mt-1">Requires attention</div>
                    </div>
                    <div class="card-icon" style="background:var(--card-accent,#b36b2a)"><span class="material-symbols-outlined">pending_actions</span></div>
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
                        background-color: #1e293b;
                        color: #ffffff;
                        font-weight: 700;
                        border-right: 1px solid rgba(255,255,255,0.2);
                    }
                    .booking-table thead th:last-child {
                        border-right: none;
                    }
                </style>
                <table id="recentBookingsTable" class="w-full booking-table">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Booking ID</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Vehicle</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#c0c8ca]/30 text-[#191c1d]">
                        <?php foreach ($recent_bookings as $booking): ?>
                        <tr class="cursor-pointer hover:bg-slate-100 transition-all" onclick="window.location.href='booking-details.php?id=<?php echo $booking['real_id']; ?>'">
                            <td class="px-6 py-4 font-semibold text-cyan-700">
                                <a href="booking-details.php?id=<?php echo $booking['real_id']; ?>" class="hover:underline">
                                    <?php echo htmlspecialchars($booking['display_id']); ?>
                                </a>
                            </td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($booking['customer']); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($booking['vehicle']); ?></td>
                            <td class="px-6 py-4 font-medium">LKR <?php echo number_format($booking['amount'], 2); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($booking['date']); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 text-xs rounded-full font-semibold <?php 
                                    echo $booking['status'] == 'pending' ? 'bg-amber-100 text-amber-700' : 
                                        ($booking['status'] == 'confirmed' ? 'bg-emerald-100 text-emerald-700' : 'bg-sky-100 text-sky-700'); ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <a href="booking-details.php?id=<?php echo $booking['real_id']; ?>" class="px-3 py-1.5 bg-slate-900 text-white rounded-lg text-xs font-bold hover:bg-slate-800 transition-all inline-flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs">visibility</span>
                                    Details
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
function exportDashboard() {
    exportElementAsPDF('main', 'dashboard-report.pdf');
}
</script>

<?php require_once 'includes/footer.php'; ?>
