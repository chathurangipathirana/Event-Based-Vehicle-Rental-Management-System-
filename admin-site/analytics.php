<?php
$page_title = 'Fleet Analytics Report';
require_once 'includes/auth.php';
requireAdminLogin();
require_once 'config/database.php';

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

$reportPeriods = [
    'today' => 'Today',
    'week' => 'This Week',
    'month' => 'This Month',
    'year' => 'This Year',
    'custom' => 'Custom Date Range',
];
$reportPeriod = $_GET['period'] ?? 'month';
$reportPeriod = array_key_exists($reportPeriod, $reportPeriods) ? $reportPeriod : 'month';
$today = new DateTimeImmutable('today');
$reportStartDate = null;
$reportEndDate = null;

switch ($reportPeriod) {
    case 'today':
        $reportStartDate = $today;
        $reportEndDate = $today;
        break;
    case 'week':
        $reportStartDate = $today->modify('monday this week');
        $reportEndDate = $reportStartDate->modify('+6 days');
        break;
    case 'year':
        $reportStartDate = $today->setDate((int) $today->format('Y'), 1, 1);
        $reportEndDate = $today->setDate((int) $today->format('Y'), 12, 31);
        break;
    case 'custom':
        $customStart = DateTimeImmutable::createFromFormat('Y-m-d', $_GET['start_date'] ?? '');
        $customEnd = DateTimeImmutable::createFromFormat('Y-m-d', $_GET['end_date'] ?? '');
        if ($customStart && $customEnd && $customStart <= $customEnd) {
            $reportStartDate = $customStart;
            $reportEndDate = $customEnd;
        }
        break;
    default:
        $reportStartDate = $today->modify('first day of this month');
        $reportEndDate = $today->modify('last day of this month');
}

$reportDateSql = $reportStartDate && $reportEndDate ? ' WHERE event_date BETWEEN ? AND ?' : '';
$reportDateParams = $reportStartDate && $reportEndDate
    ? [$reportStartDate->format('Y-m-d'), $reportEndDate->format('Y-m-d')]
    : [];
$nonCancelledBookingFilterSql = $reportStartDate && $reportEndDate
    ? 'event_date BETWEEN ? AND ? AND status != \'cancelled\''
    : "status != 'cancelled'";
$nonCancelledAliasBookingFilterSql = $reportStartDate && $reportEndDate
    ? "b.event_date BETWEEN ? AND ? AND b.status != 'cancelled'"
    : "b.status != 'cancelled'";

// Fetch stats and all graph datasets from the real database
$total_bookings = 0;
$total_revenue = 0;
$active_bookings = 0;
$fleet_health = 0;
$weekly_data = [
    ['day' => 'Monday', 'count' => 0, 'revenue' => 0],
    ['day' => 'Tuesday', 'count' => 0, 'revenue' => 0],
    ['day' => 'Wednesday', 'count' => 0, 'revenue' => 0],
    ['day' => 'Thursday', 'count' => 0, 'revenue' => 0],
    ['day' => 'Friday', 'count' => 0, 'revenue' => 0],
    ['day' => 'Saturday', 'count' => 0, 'revenue' => 0],
    ['day' => 'Sunday', 'count' => 0, 'revenue' => 0],
];
$monthly_revenue = [];
$event_distribution = [];
$vehicle_performance = [];

try {
    $statsStmt = $pdo->prepare("SELECT COUNT(*) AS total_bookings, COALESCE(SUM(total_amount), 0) AS total_revenue FROM bookings WHERE {$nonCancelledBookingFilterSql}");
    $statsStmt->execute($reportDateParams);
    $reportStats = $statsStmt->fetch() ?: [];
    $total_bookings = (int) ($reportStats['total_bookings'] ?? 0);
    $total_revenue = (float) ($reportStats['total_revenue'] ?? 0);

    $activeDateSql = $reportDateSql ? "{$reportDateSql} AND" : ' WHERE';
    $activeStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings{$activeDateSql} status IN ('pending', 'confirmed', 'in_progress')");
    $activeStmt->execute($reportDateParams);
    $active_bookings = (int) $activeStmt->fetchColumn();

    $fleetStats = $pdo->query("SELECT COUNT(*) AS total, SUM(CASE WHEN status != 'maintenance' THEN 1 ELSE 0 END) AS operational FROM vehicles")->fetch();
    $totalVehicles = (int) ($fleetStats['total'] ?? 0);
    $operationalVehicles = (int) ($fleetStats['operational'] ?? 0);
    $fleet_health = $totalVehicles > 0 ? round(($operationalVehicles / $totalVehicles) * 100) : 0;

    $weeklyStart = $reportStartDate ? $reportStartDate->modify('monday this week') : $today->modify('monday this week');
    $weeklyEnd = $weeklyStart->modify('+6 days');
    $weeklyStmt = $pdo->prepare("
        SELECT DAYOFWEEK(event_date) AS weekday_index,
               COUNT(*) AS booking_count,
               COALESCE(SUM(total_amount), 0) AS total_revenue
        FROM bookings
        WHERE event_date BETWEEN ? AND ? AND status != 'cancelled'
        GROUP BY DAYOFWEEK(event_date)
    ");
    $weeklyStmt->execute([$weeklyStart->format('Y-m-d'), $weeklyEnd->format('Y-m-d')]);
    $weeklyIndexMap = [2 => 0, 3 => 1, 4 => 2, 5 => 3, 6 => 4, 7 => 5, 1 => 6];
    foreach ($weeklyStmt->fetchAll() as $row) {
        $sourceIndex = (int) ($row['weekday_index'] ?? 0);
        if (isset($weeklyIndexMap[$sourceIndex])) {
            $targetIndex = $weeklyIndexMap[$sourceIndex];
            $weekly_data[$targetIndex]['count'] = (int) ($row['booking_count'] ?? 0);
            $weekly_data[$targetIndex]['revenue'] = (float) ($row['total_revenue'] ?? 0);
        }
    }

    $monthlyStart = $today->modify('first day of -5 months')->setTime(0, 0);
    $monthlyStmt = $pdo->prepare("
        SELECT DATE_FORMAT(event_date, '%Y-%m') AS month_key,
               DATE_FORMAT(event_date, '%M') AS month_name,
               COALESCE(SUM(total_amount), 0) AS total_revenue
        FROM bookings
        WHERE event_date >= ? AND event_date <= ? AND status != 'cancelled'
        GROUP BY DATE_FORMAT(event_date, '%Y-%m'), DATE_FORMAT(event_date, '%M')
        ORDER BY month_key ASC
    ");
    $monthlyStmt->execute([$monthlyStart->format('Y-m-d'), $today->format('Y-m-d')]);
    $monthlyMap = [];
    foreach ($monthlyStmt->fetchAll() as $row) {
        $monthlyMap[$row['month_key']] = [
            'month' => $row['month_name'],
            'revenue' => (float) ($row['total_revenue'] ?? 0),
        ];
    }
    for ($i = 5; $i >= 0; $i--) {
        $monthDate = $today->modify('first day of -' . $i . ' months');
        $monthKey = $monthDate->format('Y-m');
        $monthly_revenue[] = $monthlyMap[$monthKey] ?? [
            'month' => $monthDate->format('F'),
            'revenue' => 0,
        ];
    }

    $eventStmt = $pdo->prepare("
        SELECT COALESCE(et.name, 'Uncategorized') AS event_name,
               COUNT(*) AS booking_count
        FROM bookings b
        LEFT JOIN event_types et ON et.id = b.event_type_id
        WHERE {$nonCancelledAliasBookingFilterSql}
        GROUP BY COALESCE(et.name, 'Uncategorized')
        ORDER BY booking_count DESC, event_name ASC
        LIMIT 5
    ");
    $eventStmt->execute($reportDateParams);
    $eventRows = $eventStmt->fetchAll();
    $eventTotalBookings = array_sum(array_map(static fn($row) => (int) ($row['booking_count'] ?? 0), $eventRows));
    foreach ($eventRows as $row) {
        $bookingCount = (int) ($row['booking_count'] ?? 0);
        $event_distribution[] = [
            'name' => $row['event_name'] ?: 'Uncategorized',
            'percentage' => $eventTotalBookings > 0 ? round(($bookingCount / $eventTotalBookings) * 100) : 0,
        ];
    }

    $bookingJoinFilter = ' AND ' . $nonCancelledAliasBookingFilterSql;
    $vehicleStmt = $pdo->prepare("
        SELECT
            v.id,
            v.name,
            v.model,
            COALESCE(v.category, 'Uncategorized') AS category,
            v.status,
            v.price_per_day,
            v.price_per_hour,
            v.image_url,
            COALESCE(COUNT(b.id), 0) AS bookings,
            COALESCE(SUM(b.total_hours), 0) AS total_hours,
            COALESCE(SUM(b.total_amount), 0) AS revenue
        FROM vehicles v
        LEFT JOIN bookings b ON v.id = b.vehicle_id{$bookingJoinFilter}
        GROUP BY v.id, v.name, v.model, v.category, v.status, v.price_per_day, v.price_per_hour, v.image_url
        ORDER BY revenue DESC, bookings DESC, v.name ASC
    ");
    $vehicleStmt->execute($reportDateParams);
    $vehicle_performance = $vehicleStmt->fetchAll();
} catch (PDOException $e) {
    $total_bookings = 0;
    $total_revenue = 0;
    $active_bookings = 0;
    $fleet_health = 0;
    $vehicle_performance = [];
}

if (empty($monthly_revenue)) {
    for ($i = 5; $i >= 0; $i--) {
        $monthDate = $today->modify('first day of -' . $i . ' months');
        $monthly_revenue[] = [
            'month' => $monthDate->format('F'),
            'revenue' => 0,
        ];
    }
}

if (empty($event_distribution)) {
    $event_distribution = [
        ['name' => 'No booking data', 'percentage' => 100],
    ];
}

// Calculate summary totals across each vehicle
$total_fleet_revenue = array_sum(array_map(static fn($vehicle) => (float) ($vehicle['revenue'] ?? 0), $vehicle_performance));
$total_fleet_bookings = array_sum(array_map(static fn($vehicle) => (int) ($vehicle['bookings'] ?? 0), $vehicle_performance));
$total_fleet_hours = array_sum(array_map(static fn($vehicle) => (float) ($vehicle['total_hours'] ?? 0), $vehicle_performance));
$total_vehicle_count = count($vehicle_performance);
$max_vehicle_hours = $total_vehicle_count > 0
    ? max(array_map(static fn($vehicle) => (float) ($vehicle['total_hours'] ?? 0), $vehicle_performance))
    : 1;
$max_vehicle_hours = $max_vehicle_hours > 0 ? $max_vehicle_hours : 1;
$top_vehicle = $vehicle_performance[0] ?? null;
$maintenance_count = count(array_filter($vehicle_performance, static fn($vehicle) => ($vehicle['status'] ?? '') === 'maintenance'));
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<!-- Include Chart.js library for interactive Bar Chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<main class="ml-64 min-h-screen bg-slate-50">
    <div class="p-8 max-w-7xl mx-auto">
        <!-- Hero Header -->
        <section class="rounded-[2rem] overflow-hidden mb-10">
            <div class="relative bg-slate-900 text-white p-8 lg:p-10 overflow-hidden">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.24),_transparent_36%)] opacity-70 pointer-events-none"></div>
                <div class="relative grid grid-cols-1 xl:grid-cols-[1.5fr_1fr] gap-8 items-center">
                    <div class="max-w-2xl">
                        <p class="text-xs uppercase tracking-[0.35em] text-slate-400 mb-4">Fleet Analytics Manager</p>
                        <h1 class="text-5xl font-semibold tracking-tight">Fleet Analytics Report</h1>
                        <p class="mt-4 text-slate-300 text-lg leading-8">A complete overview of fleet revenue, rentals, utilization, vehicle performance, and operational recommendations.</p>
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
                    <p class="text-sm text-gray-500 uppercase">Total Fleet Revenue</p>
                    <div class="kpi-value">LKR <?php echo number_format($total_fleet_revenue, 2); ?></div>
                    <div class="text-xs text-green-600 mt-1">Across all <?php echo number_format($total_vehicle_count); ?> vehicles in your system</div>
                </div>
                <div class="card-icon" style="background:var(--card-accent,#0b6b6d)"><span class="material-symbols-outlined">payments</span></div>
            </div>
            <div class="card-3d p-6 bg-white" style="--card-accent: #0b6b6d;">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Total Completed Rentals</p>
                    <div class="kpi-value"><?php echo number_format($total_fleet_bookings); ?></div>
                    <div class="text-xs text-green-600 mt-1">Combined vehicle bookings</div>
                </div>
                <div class="card-icon" style="background:var(--card-accent,#0b6b6d)"><span class="material-symbols-outlined">directions_car</span></div>
            </div>
            <div class="card-3d p-6 bg-white" style="--card-accent: #0b6b6d;">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Total Rented Hours</p>
                    <div class="kpi-value"><?php echo number_format($total_fleet_hours); ?> hrs</div>
                    <div class="text-xs text-[#f59e0b] mt-1">Fleet operational time</div>
                </div>
                <div class="card-icon" style="background:var(--card-accent,#0b6b6d)"><span class="material-symbols-outlined">schedule</span></div>
            </div>
            <div class="card-3d p-6 bg-white" style="--card-accent: #0b6b6d;">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Avg Revenue / Vehicle</p>
                    <div class="kpi-value">LKR <?php echo number_format(count($vehicle_performance) > 0 ? ($total_fleet_revenue / count($vehicle_performance)) : 0, 2); ?></div>
                    <div class="text-xs text-cyan-600 mt-1">Per vehicle metric average</div>
                </div>
                <div class="card-icon" style="background:var(--card-accent,#0b6b6d)"><span class="material-symbols-outlined">insights</span></div>
            </div>
        </div>

        <!-- Analytics Graphs Section -->
        <section class="mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-[0.25em] text-cyan-600 font-bold mb-2">Analytics Graphs</p>
                        <h2 class="text-2xl font-bold text-gray-900">Visual Fleet Performance Overview</h2>
                        <p class="text-sm text-gray-500 mt-1">Track revenue by vehicle, monthly growth trends, and booking mix in one place.</p>
                    </div>
                    <span class="material-symbols-outlined text-cyan-600 text-3xl">monitoring</span>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-8">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Vehicle Revenue Comparison (LKR)</h2>
                            <p class="text-xs text-gray-500">Top-performing physical vehicles by total generated revenue</p>
                        </div>
                        <span class="material-symbols-outlined text-cyan-600">bar_chart</span>
                    </div>
                    <div class="h-72 relative mb-4">
                        <canvas id="vehicleBarChart"></canvas>
                    </div>
                    <div id="vehicleBarFallback" class="space-y-3">
                        <?php $vehicleChartFallback = array_slice($vehicle_performance, 0, 8); ?>
                        <?php $vehicleChartFallbackMax = max(array_map(static fn($vehicle) => (float) ($vehicle['revenue'] ?? 0), $vehicleChartFallback ?: [['revenue' => 1]])) ?: 1; ?>
                        <?php foreach ($vehicleChartFallback as $vehicle): ?>
                            <div>
                                <div class="flex items-center justify-between gap-3 text-xs mb-1">
                                    <span class="font-semibold text-gray-700 truncate pr-3"><?php echo htmlspecialchars($vehicle['name']); ?></span>
                                    <span class="font-bold text-cyan-700 whitespace-nowrap">LKR <?php echo number_format((float) ($vehicle['revenue'] ?? 0), 0); ?></span>
                                </div>
                                <div class="h-3 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full bg-blue-500" style="width: <?php echo max(8, round((((float) ($vehicle['revenue'] ?? 0)) / $vehicleChartFallbackMax) * 100)); ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Monthly Revenue Trend (LKR)</h2>
                            <p class="text-xs text-gray-500">Month-over-month revenue movement across all event rentals</p>
                        </div>
                        <span class="material-symbols-outlined text-cyan-600">show_chart</span>
                    </div>
                    <div class="h-72 relative mb-4">
                        <canvas id="monthlyBarChart"></canvas>
                    </div>
                    <div id="monthlyBarFallback" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <?php foreach ($monthly_revenue as $monthData): ?>
                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500"><?php echo htmlspecialchars($monthData['month']); ?></p>
                                <p class="mt-2 text-sm font-semibold text-cyan-700">LKR <?php echo number_format((float) ($monthData['revenue'] ?? 0), 0); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 lg:col-span-1">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Event Booking Mix</h2>
                            <p class="text-xs text-gray-500">Distribution of major booking categories</p>
                        </div>
                        <span class="material-symbols-outlined text-cyan-600">pie_chart</span>
                    </div>
                    <div class="h-72 relative mb-4">
                        <canvas id="eventMixChart"></canvas>
                    </div>
                    <div id="eventMixFallback" class="space-y-3">
                        <?php foreach ($event_distribution as $distribution): ?>
                            <div>
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($distribution['name']); ?></span>
                                    <span class="font-bold text-cyan-700"><?php echo (int) ($distribution['percentage'] ?? 0); ?>%</span>
                                </div>
                                <div class="h-3 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full bg-blue-500" style="width: <?php echo max(5, min(100, (int) ($distribution['percentage'] ?? 0))); ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="bg-slate-900 text-white p-6 rounded-2xl shadow-sm border border-slate-800 lg:col-span-2 flex flex-col justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-[0.25em] text-cyan-400 font-bold mb-2">Graph Insight</p>
                        <h3 class="text-2xl font-bold mb-2">Performance Snapshot</h3>
                        <p class="text-sm text-slate-300 leading-7">The charts above give you the visual analytics view that was previously shown in Analytics. Use them to compare physical vehicle earnings, understand revenue trends, and quickly spot where demand is strongest.</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6">
                        <div class="rounded-xl bg-slate-800/80 border border-slate-700 p-4">
                            <p class="text-xs uppercase text-slate-400 font-bold mb-1">Top Vehicle</p>
                            <p class="font-semibold text-white"><?php echo htmlspecialchars($top_vehicle['name'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="rounded-xl bg-slate-800/80 border border-slate-700 p-4">
                            <p class="text-xs uppercase text-slate-400 font-bold mb-1">Fleet Revenue</p>
                            <p class="font-semibold text-white">LKR <?php echo number_format($total_fleet_revenue, 2); ?></p>
                        </div>
                        <div class="rounded-xl bg-slate-800/80 border border-slate-700 p-4">
                            <p class="text-xs uppercase text-slate-400 font-bold mb-1">Total Rentals</p>
                            <p class="font-semibold text-white"><?php echo number_format($total_fleet_bookings); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- EACH VEHICLE PERFORMANCE REPORT TABLE -->
        <section id="vehicle-performance-report-container" class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-8">
            <!-- Report Header for PDF / Print Output -->
            <div class="p-6 border-b border-gray-200 bg-slate-900 text-white flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <span class="text-xs uppercase font-extrabold tracking-widest text-cyan-400 block">System Performance Analysis</span>
                    <h2 class="text-2xl font-bold">Fleet Performance Report</h2>
                    <p class="text-xs text-slate-300 mt-1">Download a clear PDF with the fleet summary, revenue charts, vehicle performance, utilization, and recommendations.</p>
                </div>
            </div>

            <!-- PDF Report Header Banner (Only visible during PDF generation) -->
            <div id="pdf-report-header" class="hidden p-6 bg-slate-900 text-white border-b border-slate-800">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-white">ROYAL LANKA RIDES FLEET MANAGEMENT</h1>
                        <p class="text-xs text-cyan-300">Each Vehicle Performance & Revenue Report</p>
                    </div>
                    <div class="text-right text-xs text-slate-300">
                        <p>Generated: <strong class="pdf-generated-at"><?php echo date('F d, Y h:i A'); ?></strong></p>
                        <p>Scope: <strong>All Active & Managed Vehicles</strong></p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <style>
                    .performance-table tbody tr {
                        transition: all 0.2s ease;
                    }
                    .performance-table tbody tr:nth-child(odd) {
                        background-color: #ffffff;
                    }
                    .performance-table tbody tr:nth-child(even) {
                        background-color: #f8fafc;
                    }
                    .performance-table tbody tr:hover {
                        background-color: #f0fdfa !important;
                    }
                    .performance-table td {
                        border-right: 1px solid #e2e8f0;
                    }
                    .performance-table thead th {
                        background-color: #0f172a;
                        color: #ffffff;
                        font-weight: 700;
                        border-right: 1px solid rgba(255,255,255,0.15);
                    }
                    .performance-table thead th:last-child {
                        border-right: none;
                    }
                </style>
                <table class="w-full performance-table" id="vehiclePerformanceTable">
                    <thead>
                        <tr>
                            <th class="px-5 py-4 text-center text-xs font-bold uppercase w-12">#</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase">Vehicle Details</th>
                            <th class="px-5 py-4 text-center text-xs font-bold uppercase">Status</th>
                            <th class="px-5 py-4 text-right text-xs font-bold uppercase">Daily Rate</th>
                            <th class="px-5 py-4 text-center text-xs font-bold uppercase">Bookings</th>
                            <th class="px-6 py-4 text-right text-xs font-bold uppercase">Total Revenue (LKR)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm">
                        <?php $rank = 1; foreach($vehicle_performance as $v): ?>
                        <?php
                            $status_class = ($v['status'] == 'available') ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : (($v['status'] == 'booked') ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-rose-100 text-rose-800 border-rose-200');
                            $status_dot = ($v['status'] == 'available') ? 'bg-emerald-500' : (($v['status'] == 'booked') ? 'bg-amber-500' : 'bg-rose-500');
                            $status_label = ($v['status'] == 'available') ? 'Available' : (($v['status'] == 'booked') ? 'Booked' : 'In Maintenance');
                        ?>
                        <tr>
                            <td class="px-5 py-4 text-center font-bold text-gray-500 text-xs"><?php echo $rank++; ?></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-12 rounded-lg bg-slate-900 overflow-hidden flex items-center justify-center border border-gray-200 flex-shrink-0">
                                        <?php $vImg = getAdminVehicleImageUrl($v['image_url'] ?? '', $v['name']); ?>
                                        <img src="<?php echo htmlspecialchars($vImg); ?>" alt="<?php echo htmlspecialchars($v['name']); ?>" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='../user-site/public/assets/vehicle-default.svg'">
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900 text-sm leading-snug"><?php echo htmlspecialchars($v['name']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($v['model']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border <?php echo $status_class; ?>">
                                    <span class="w-1.5 h-1.5 rounded-full mr-1.5 <?php echo $status_dot; ?>"></span>
                                    <?php echo $status_label; ?>
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right text-gray-700 text-xs font-semibold">LKR <?php echo number_format($v['price_per_day'], 2); ?></td>
                            <td class="px-5 py-4 text-center font-extrabold text-slate-900 text-sm"><?php echo number_format($v['bookings']); ?></td>
                            <td class="px-6 py-4 text-right font-black text-cyan-600 text-base">LKR <?php echo number_format($v['revenue'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-900 text-white font-bold">
                            <td colspan="4" class="px-6 py-4 text-right uppercase tracking-wider text-xs">Total Fleet Summary:</td>
                            <td class="px-5 py-4 text-center text-sm font-black"><?php echo number_format($total_fleet_bookings); ?></td>
                            <td class="px-6 py-4 text-right text-cyan-300 text-base font-black">LKR <?php echo number_format($total_fleet_revenue, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <!-- Weekly Performance Bar Breakdown -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Weekly Rentals Breakdown</h2>
                    <p class="text-xs text-gray-500">Daily reservation counts and revenue highlights across the week</p>
                </div>
                <span class="material-symbols-outlined text-gray-400">calendar_view_week</span>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-4">
                    <?php foreach($weekly_data as $data): ?>
                    <div class="text-center p-4 rounded-xl bg-slate-50 border border-slate-100 hover:border-cyan-200 transition-all">
                        <div class="text-xs text-gray-500 uppercase font-bold"><?php echo substr($data['day'], 0, 3); ?></div>
                        <div class="text-2xl font-extrabold text-gray-900 my-1"><?php echo $data['count']; ?></div>
                        <div class="text-xs font-bold text-cyan-600">LKR <?php echo round($data['revenue'] / 1000); ?>k</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Dedicated print layout for the Fleet Analytics PDF export. -->
<style>
    #fleet-analytics-pdf-report { position: fixed; left: -12000px; top: 0; width: 1040px; box-sizing: border-box; padding: 28px; background: #fff; color: #172033; font-family: Arial, sans-serif; }
    #fleet-analytics-pdf-report, #fleet-analytics-pdf-report * { box-sizing: border-box; }
    #fleet-analytics-pdf-report .pdf-rule { border: 0; border-top: 2px dashed #64748b; margin: 20px 0; }
    #fleet-analytics-pdf-report h1 { margin: 0; font-size: 28px; text-align: center; letter-spacing: .04em; }
    #fleet-analytics-pdf-report h2 { margin: 0 0 12px; padding-bottom: 8px; font-size: 18px; border-bottom: 1px solid #cbd5e1; }
    #fleet-analytics-pdf-report .pdf-date { margin-top: 12px; text-align: center; color: #475569; font-size: 13px; }
    #fleet-analytics-pdf-report .pdf-section-note { margin: -4px 0 14px; color: #64748b; font-size: 12px; }
    #fleet-analytics-pdf-report .pdf-summary { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    #fleet-analytics-pdf-report .pdf-card { padding: 14px; border: 1px solid #cbd5e1; border-radius: 8px; text-align: center; background: #f8fafc; }
    #fleet-analytics-pdf-report .pdf-card span { display: block; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    #fleet-analytics-pdf-report .pdf-card strong { display: block; margin-top: 7px; font-size: 18px; color: #0f766e; }
    #fleet-analytics-pdf-report table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 12px; }
    #fleet-analytics-pdf-report th, #fleet-analytics-pdf-report td { padding: 9px; border: 1px solid #cbd5e1; text-align: left; }
    #fleet-analytics-pdf-report th, #fleet-analytics-pdf-report td { overflow-wrap: anywhere; vertical-align: top; }
    #fleet-analytics-pdf-report th { color: #fff; background: #0f172a; }
    #fleet-analytics-pdf-report .numeric { text-align: right; }
    #fleet-analytics-pdf-report .pdf-charts { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    #fleet-analytics-pdf-report .pdf-chart { border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; }
    #fleet-analytics-pdf-report .pdf-chart img { display: block; width: 100%; height: 250px; object-fit: contain; }
    #fleet-analytics-pdf-report .recommendations { margin: 0; padding-left: 20px; line-height: 1.7; font-size: 12px; }
    #fleet-analytics-pdf-report .pdf-footer { margin-top: 22px; text-align: center; color: #64748b; font-size: 11px; }
    #fleet-analytics-pdf-report tr, #fleet-analytics-pdf-report .pdf-card, #fleet-analytics-pdf-report .pdf-chart { break-inside: avoid; page-break-inside: avoid; }
</style>
<section id="fleet-analytics-pdf-report" aria-hidden="true">
    <header>
        <hr class="pdf-rule">
        <h1>Fleet Analytics Report</h1>
        <p class="pdf-date">Generated on <span class="pdf-generated-at"><?php echo date('F d, Y, g:i A'); ?></span> &middot; Scope: all recorded fleet bookings</p>
        <hr class="pdf-rule">
    </header>

    <section>
        <h2>1. Fleet Summary</h2>
        <p class="pdf-section-note">Key results across every vehicle in the fleet.</p>
        <div class="pdf-summary">
            <div class="pdf-card"><span>Total Revenue</span><strong>LKR <?php echo number_format($total_fleet_revenue, 2); ?></strong></div>
            <div class="pdf-card"><span>Total Bookings</span><strong><?php echo number_format($total_fleet_bookings); ?></strong></div>
            <div class="pdf-card"><span>Total Rented Hours</span><strong><?php echo number_format($total_fleet_hours); ?></strong></div>
            <div class="pdf-card"><span>Average Revenue per Vehicle</span><strong>LKR <?php echo number_format(count($vehicle_performance) ? ($total_fleet_revenue / count($vehicle_performance)) : 0, 2); ?></strong></div>
        </div>
    </section>

    <hr class="pdf-rule">
    <section>
        <h2>2. Vehicle Performance</h2>
        <p class="pdf-section-note">Revenue, bookings, and rented hours for each vehicle.</p>
        <table>
            <thead><tr><th>Vehicle</th><th>Category</th><th>Rentals</th><th>Hours</th><th class="numeric">Revenue (LKR)</th></tr></thead>
            <tbody>
                <?php foreach ($vehicle_performance as $vehicle): ?>
                <tr>
                    <td><?php echo htmlspecialchars($vehicle['name']); ?></td>
                    <td><?php echo htmlspecialchars($vehicle['category']); ?></td>
                    <td><?php echo number_format($vehicle['bookings']); ?></td>
                    <td><?php echo number_format($vehicle['total_hours']); ?></td>
                    <td class="numeric">LKR <?php echo number_format($vehicle['revenue'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="pdf-charts">
        <div class="pdf-chart"><h2>Revenue Comparison Chart</h2><img id="pdf-vehicle-chart" alt="Vehicle revenue comparison chart"></div>
        <div class="pdf-chart"><h2>Monthly Revenue Chart</h2><img id="pdf-monthly-chart" alt="Monthly revenue chart"></div>
    </section>

    <hr class="pdf-rule">
    <section>
        <h2>5. Vehicle Utilization</h2>
        <p class="pdf-section-note">Utilization compares each vehicle's rented hours with the fleet's highest rented-hours value.</p>
        <table>
            <thead><tr><th>Vehicle</th><th>Rentals</th><th>Rented Hours</th><th>Utilization</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($vehicle_performance as $vehicle): ?>
                <tr>
                    <td><?php echo htmlspecialchars($vehicle['name']); ?></td>
                    <td><?php echo number_format($vehicle['bookings']); ?></td>
                    <td><?php echo number_format($vehicle['total_hours']); ?></td>
                    <td><?php echo number_format(($vehicle['total_hours'] / $max_vehicle_hours) * 100, 1); ?>%</td>
                    <td><?php echo htmlspecialchars(ucfirst($vehicle['status'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <hr class="pdf-rule">
    <section>
        <h2>6. Operational Recommendations</h2>
        <ul class="recommendations">
            <?php if ($top_vehicle): ?><li>Prioritize availability for <?php echo htmlspecialchars($top_vehicle['name']); ?>, the fleet's highest-revenue vehicle.</li><?php endif; ?>
            <li>Review lower-utilization vehicles and target them with event-package promotions.</li>
            <?php if ($maintenance_count): ?><li>Schedule maintenance follow-up for <?php echo $maintenance_count; ?> vehicle(s) currently marked for maintenance.</li><?php else: ?><li>Maintain preventive service schedules to protect current fleet availability.</li><?php endif; ?>
        </ul>
    </section>
    <hr class="pdf-rule">
    <footer class="pdf-footer">Generated by Smart Transportation System<br>Page 1 of X</footer>
</section>

<script>
const vehiclePerformanceData = <?php echo json_encode($vehicle_performance, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const eventDistributionData = <?php echo json_encode($event_distribution, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const monthlyRevenueData = <?php echo json_encode($monthly_revenue, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

document.addEventListener('DOMContentLoaded', function () {
    const customDateRange = document.getElementById('custom-date-range');
    const hideFallback = function (id) {
        const element = document.getElementById(id);
        if (element) {
            element.style.display = 'none';
        }
    };
    document.querySelectorAll('input[name="period"]').forEach((input) => {
        input.addEventListener('change', () => {
            if (customDateRange) {
                customDateRange.classList.toggle('is-visible', input.value === 'custom' && input.checked);
            }
        });
    });

    if (typeof Chart === 'undefined') {
        console.error('Chart.js failed to load on analytics.php');
        return;
    }

    const topVehicleData = vehiclePerformanceData.slice(0, 8);
    const vehicleNames = topVehicleData.map((vehicle) => vehicle.name || 'Vehicle');
    const vehicleShortLabels = vehicleNames.map((name) => name.length > 18 ? name.slice(0, 18) + '…' : name);
    const vehicleRevenues = topVehicleData.map((vehicle) => Number(vehicle.revenue || 0));

    const vehicleCanvas = document.getElementById('vehicleBarChart');
    if (vehicleCanvas && vehicleRevenues.length > 0) {
        new Chart(vehicleCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: vehicleShortLabels,
                datasets: [{
                    label: 'Revenue (LKR)',
                    data: vehicleRevenues,
                    backgroundColor: ['#0ea5e9', '#0ea5e9', '#0ea5e9', '#0ea5e9', '#0ea5e9', '#0ea5e9', '#0ea5e9', '#0ea5e9'],
                    borderRadius: 10,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: function (items) {
                                const index = items[0] && typeof items[0].dataIndex !== 'undefined' ? items[0].dataIndex : 0;
                                return vehicleNames[index] || 'Vehicle';
                            },
                            label: function (context) {
                                return 'Revenue: LKR ' + Number(context.raw || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return 'LKR ' + (value / 1000000).toFixed(1) + 'M';
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: { size: 10 },
                            maxRotation: 35,
                            minRotation: 35
                        }
                    }
                }
            }
        });
        hideFallback('vehicleBarFallback');
    }

    const monthlyCanvas = document.getElementById('monthlyBarChart');
    const monthlyLabels = monthlyRevenueData.map((item) => item.month || '');
    const monthlyRevenues = monthlyRevenueData.map((item) => Number(item.revenue || 0));
    if (monthlyCanvas && monthlyRevenues.length > 0) {
        new Chart(monthlyCanvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Monthly Revenue (LKR)',
                    data: monthlyRevenues,
                    fill: true,
                    backgroundColor: 'rgba(14, 116, 144, 0.14)',
                    borderColor: '#0e7490',
                    borderWidth: 3,
                    tension: 0.35,
                    pointRadius: 4,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#0891b2'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return 'Revenue: LKR ' + Number(context.raw || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return 'LKR ' + (value / 1000000).toFixed(1) + 'M';
                            }
                        }
                    }
                }
            }
        });
        hideFallback('monthlyBarFallback');
    }

    const eventMixCanvas = document.getElementById('eventMixChart');
    if (eventMixCanvas && eventDistributionData.length > 0) {
        new Chart(eventMixCanvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: eventDistributionData.map((item) => item.name || 'Category'),
                datasets: [{
                    data: eventDistributionData.map((item) => Number(item.percentage || 0)),
                    backgroundColor: ['#0ea5e9', '#38bdf8', '#7dd3fc'],
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return (context.label || '') + ': ' + Number(context.raw || 0) + '%';
                            }
                        }
                    }
                },
                cutout: '62%'
            }
        });
        hideFallback('eventMixFallback');
    }
});

// Function to export EACH Vehicle Performance Report as PDF
async function exportVehiclePerformancePDF(button) {
    const JsPdf = window.jspdf?.jsPDF || window.jsPDF;
    if (!JsPdf) {
        window.alert('The report exporter is still loading. Please try again in a moment.');
        return;
    }

    const originalLabel = button ? button.innerHTML : '';
    if (button) {
        button.disabled = true;
        button.innerHTML = '<span class="material-symbols-outlined text-sm">hourglass_top</span> Preparing report...';
    }

    try {
        const pdf = new JsPdf({ orientation: 'landscape', unit: 'mm', format: 'a4' });
        const pageWidth = 297;
        const pageHeight = 210;
        const margin = 12;
        const timestamp = new Intl.DateTimeFormat(undefined, { dateStyle: 'long', timeStyle: 'short' }).format(new Date());
        const formatCurrency = (value) => 'LKR ' + Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const drawHeader = (title, subtitle) => {
            pdf.setFillColor(15, 23, 42);
            pdf.rect(0, 0, pageWidth, 28, 'F');
            pdf.setTextColor(255, 255, 255);
            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(18);
            pdf.text(title, margin, 14);
            pdf.setFont('helvetica', 'normal');
            pdf.setFontSize(9);
            pdf.setTextColor(186, 230, 253);
            pdf.text(subtitle, margin, 21);
            pdf.setTextColor(71, 85, 105);
        };
        const drawFooter = (pageNumber) => {
            pdf.setDrawColor(203, 213, 225);
            pdf.line(margin, 198, pageWidth - margin, 198);
            pdf.setFontSize(8);
            pdf.setTextColor(100, 116, 139);
            pdf.text('Generated ' + timestamp, margin, 203);
            pdf.text('Page ' + pageNumber, pageWidth - margin, 203, { align: 'right' });
        };

        drawHeader('Fleet Analytics Report', 'Current fleet performance and revenue overview');
        pdf.setFont('helvetica', 'normal');
        pdf.setFontSize(10);
        pdf.text('Exported ' + timestamp, margin, 38);

        const totalRevenue = vehiclePerformanceData.reduce((sum, vehicle) => sum + Number(vehicle.revenue || 0), 0);
        const totalBookings = vehiclePerformanceData.reduce((sum, vehicle) => sum + Number(vehicle.bookings || 0), 0);
        const cards = [
            ['Total Revenue', formatCurrency(totalRevenue)],
            ['Total Bookings', totalBookings.toLocaleString()],
            ['Vehicles', vehiclePerformanceData.length.toLocaleString()],
            ['Average / Vehicle', formatCurrency(vehiclePerformanceData.length ? totalRevenue / vehiclePerformanceData.length : 0)]
        ];
        const cardWidth = 64;
        cards.forEach((card, index) => {
            const x = margin + (index * 68);
            pdf.setFillColor(248, 250, 252);
            pdf.setDrawColor(203, 213, 225);
            pdf.roundedRect(x, 45, cardWidth, 25, 2, 2, 'FD');
            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(8);
            pdf.setTextColor(100, 116, 139);
            pdf.text(card[0].toUpperCase(), x + 4, 53);
            pdf.setFontSize(11);
            pdf.setTextColor(15, 118, 110);
            pdf.text(card[1], x + 4, 63);
        });

        const vehicleChart = document.getElementById('vehicleBarChart');
        const monthlyChart = document.getElementById('monthlyBarChart');
        if (vehicleChart) pdf.addImage(vehicleChart.toDataURL('image/png', 1), 'PNG', margin, 80, 132, 88);
        if (monthlyChart) pdf.addImage(monthlyChart.toDataURL('image/png', 1), 'PNG', 153, 80, 132, 88);
        pdf.setFont('helvetica', 'bold');
        pdf.setFontSize(11);
        pdf.setTextColor(30, 41, 59);
        pdf.text('Vehicle Revenue Comparison', margin, 76);
        pdf.text('Monthly Revenue Trend', 153, 76);
        drawFooter(1);

        pdf.addPage();
        drawHeader('Vehicle Performance', 'Current details by vehicle');
        const columns = [
            { label: '#', width: 10, align: 'center' },
            { label: 'Vehicle', width: 105, align: 'left' },
            { label: 'Status', width: 30, align: 'center' },
            { label: 'Daily Rate', width: 38, align: 'right' },
            { label: 'Bookings', width: 26, align: 'center' },
            { label: 'Total Revenue', width: 64, align: 'right' }
        ];
        let x = margin;
        let y = 39;
        pdf.setFillColor(15, 23, 42);
        pdf.rect(margin, y, 273, 9, 'F');
        pdf.setFont('helvetica', 'bold');
        pdf.setFontSize(8);
        pdf.setTextColor(255, 255, 255);
        columns.forEach((column) => {
            const textX = column.align === 'right' ? x + column.width - 3 : column.align === 'center' ? x + (column.width / 2) : x + 3;
            pdf.text(column.label, textX, y + 5.8, { align: column.align });
            x += column.width;
        });
        y += 9;
        pdf.setFont('helvetica', 'normal');
        pdf.setFontSize(8);
        vehiclePerformanceData.forEach((vehicle, index) => {
            const rowHeight = 10;
            x = margin;
            pdf.setFillColor(index % 2 === 0 ? 255 : 248, index % 2 === 0 ? 255 : 250, index % 2 === 0 ? 255 : 252);
            pdf.rect(margin, y, 273, rowHeight, 'F');
            pdf.setDrawColor(226, 232, 240);
            pdf.rect(margin, y, 273, rowHeight, 'S');
            const values = [
                String(index + 1),
                String(vehicle.name || 'Vehicle'),
                String(vehicle.status || 'Unknown').replace(/^./, (letter) => letter.toUpperCase()),
                formatCurrency(vehicle.price_per_day),
                Number(vehicle.bookings || 0).toLocaleString(),
                formatCurrency(vehicle.revenue)
            ];
            pdf.setTextColor(30, 41, 59);
            columns.forEach((column, columnIndex) => {
                const textX = column.align === 'right' ? x + column.width - 3 : column.align === 'center' ? x + (column.width / 2) : x + 3;
                const maxWidth = column.width - 6;
                const line = pdf.splitTextToSize(values[columnIndex], maxWidth)[0];
                pdf.text(line, textX, y + 6.2, { align: column.align });
                x += column.width;
            });
            y += rowHeight;
        });
        pdf.setFillColor(15, 23, 42);
        pdf.rect(margin, y, 273, 10, 'F');
        pdf.setFont('helvetica', 'bold');
        pdf.setTextColor(255, 255, 255);
        pdf.text('Fleet Total', margin + 3, y + 6.3);
        pdf.text(totalBookings.toLocaleString(), 12 + 10 + 105 + 30 + 38 + 13, y + 6.3, { align: 'center' });
        pdf.setTextColor(165, 243, 252);
        pdf.text(formatCurrency(totalRevenue), pageWidth - margin - 3, y + 6.3, { align: 'right' });
        drawFooter(2);
        pdf.save('Fleet-Analytics-Report-' + new Date().toISOString().slice(0, 10) + '.pdf');
    } catch (error) {
        console.error('Unable to generate fleet analytics report:', error);
        window.alert('The report could not be generated. Please refresh the page and try again.');
    } finally {
        if (button) {
            button.disabled = false;
            button.innerHTML = originalLabel;
        }
    }
}

// Default export action
function exportReport() {
    exportVehiclePerformancePDF();
}
</script>

<?php require_once 'includes/footer.php'; ?>
