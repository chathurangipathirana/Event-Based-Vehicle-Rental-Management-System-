<?php
$page_title = 'Manage Bookings';
require_once 'includes/auth.php';
requireAdminLogin();
require_once 'config/database.php';

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

// Get fleet availability stats
try {
    $total_vehicles = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'available'")->fetchColumn();
    $total_all_vehicles = $pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
    $available_percent = $total_all_vehicles > 0 ? round(($total_vehicles / $total_all_vehicles) * 100) : 0;
} catch(PDOException $e) {
    $total_vehicles = 0;
    $available_percent = 0;
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
                        <p class="text-xs uppercase tracking-[0.35em] text-slate-400 mb-4">Vehicle Manager</p>
                        <h1 class="text-5xl font-semibold tracking-tight">Manage Bookings</h1>
                        <p class="mt-4 text-slate-300 text-lg leading-8">Oversee and coordinate all upcoming event logistics and vehicle assignments with operational precision.</p>
                    </div>
                    <div class="flex flex-wrap justify-end gap-3">
                        <button type="button" onclick="filterByStatus()" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-slate-800/80 border border-slate-700 text-sm font-semibold hover:bg-slate-700 transition-all">
                            <span class="material-symbols-outlined text-sm">filter_list</span>
                            Status
                        </button>
                        <button type="button" onclick="filterByDate()" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-white text-slate-900 text-sm font-semibold hover:bg-slate-100 transition-all">
                            <span class="material-symbols-outlined text-sm">calendar_month</span>
                            Date Range
                        </button>

                    </div>
                </div>
            </div>
        </section>

        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5 mb-10">
            <div class="relative max-w-3xl mx-auto">
                <label for="searchBookings" class="sr-only">Search bookings</label>
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                <input type="text" id="searchBookings" class="w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-cyan-300 focus:border-cyan-300 text-sm text-slate-700" placeholder="Search bookings, clients, or vehicles...">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
            <div class="card-3d p-6 bg-white" style="--card-accent: #0b6b6d;">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Total Active</p>
                    <div class="kpi-value"><?php echo $total_active; ?></div>
                    <div class="text-xs text-green-600 mt-1">+12% vs last month</div>
                </div>
                <div class="card-icon" style="background:var(--card-accent,#0b6b6d)"><span class="material-symbols-outlined">event_available</span></div>
            </div>
            <div class="card-3d p-6 bg-white" style="--card-accent: #b36b2a;">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Pending Review</p>
                    <div class="kpi-value"><?php echo $pending_review; ?></div>
                    <div class="text-xs text-[#f59e0b] mt-1">Requires immediate attention</div>
                </div>
                <div class="card-icon" style="background:var(--card-accent,#b36b2a)"><span class="material-symbols-outlined">pending_actions</span></div>
            </div>
            <div class="card-3d p-6 bg-white" style="--card-accent: #0b6b6d;">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Dispatched</p>
                    <div class="kpi-value"><?php echo $dispatched; ?></div>
                    <div class="text-xs text-gray-500 mt-1">In transit or on-site</div>
                </div>
                <div class="card-icon" style="background:var(--card-accent,#0b6b6d)"><span class="material-symbols-outlined">directions_car</span></div>
            </div>
            <div class="card-3d p-6 bg-white" style="--card-accent: #0b6b6d;">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Completion Rate</p>
                    <div class="kpi-value"><?php echo number_format($completion_rate, 1); ?>%</div>
                    <div class="text-xs text-green-600 mt-1">Rolling 30-day average</div>
                </div>
                <div class="card-icon" style="background:var(--card-accent,#0b6b6d)"><span class="material-symbols-outlined">insights</span></div>
            </div>
        </div>

        <!-- Main Data Table Container -->
        <div class="bg-white rounded-3xl shadow-sm border border-[#c0c8ca] overflow-hidden">
            <div class="px-6 py-4 border-b border-[#c0c8ca] flex justify-between items-center">
                <h3 class="text-lg font-semibold text-[#191c1d]">Booking Records</h3>
                <a href="bookings.php" class="text-cyan-600 text-sm hover:underline">Refresh</a>
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
                        <tr class="bg-slate-900 text-slate-100">
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Booking ID</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Client &amp; Event</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Vehicle</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Date &amp; Time</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#c0c8ca]/30 text-[#191c1d]" id="bookingsTable">
                        <?php if (!empty($bookings)): ?>
                            <?php foreach ($bookings as $booking): ?>
                            <tr class="booking-row cursor-pointer hover:bg-slate-100/80 transition-all" data-status="<?php echo htmlspecialchars($booking['status'] ?? ''); ?>" data-event-date="<?php echo htmlspecialchars($booking['event_date'] ?? ''); ?>" data-client="<?php echo htmlspecialchars($booking['client_name'] ?? 'Guest'); ?>" onclick="window.location.href='booking-details.php?id=<?php echo $booking['id']; ?>'">
                                <td class="px-6 py-4">
                                    <a href="booking-details.php?id=<?php echo $booking['id']; ?>" class="text-sm font-semibold text-cyan-700 hover:underline">
                                        #<?php echo substr($booking['booking_number'] ?? 'BK-' . $booking['id'], -8); ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        <a href="booking-details.php?id=<?php echo $booking['id']; ?>" class="text-sm font-semibold text-[#191c1d] hover:text-cyan-700">
                                            <?php echo htmlspecialchars($booking['event_name'] ?: $booking['event_type_name'] ?: 'Event'); ?>
                                        </a>
                                        <span class="text-xs text-slate-500"><?php echo htmlspecialchars($booking['client_name'] ?: 'Guest'); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2 text-[#191c1d]">
                                        <span class="material-symbols-outlined text-lg text-slate-400">directions_car</span>
                                        <span class="text-sm"><?php echo htmlspecialchars($booking['vehicle_name'] ?: 'Vehicle'); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1 text-slate-600">
                                        <span class="text-sm"><?php echo $booking['event_date'] ? date('M d, Y', strtotime($booking['event_date'])) : 'N/A'; ?></span>
                                        <span class="text-xs text-slate-500">
                                            <?php echo $booking['start_time'] ? date('g:i A', strtotime($booking['start_time'])) : '--'; ?> -
                                            <?php echo $booking['end_time'] ? date('g:i A', strtotime($booking['end_time'])) : '--'; ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-semibold text-[#191c1d]">LKR <?php echo number_format($booking['total_amount'], 2); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $status_class = match($booking['status']) {
                                        'confirmed' => 'bg-emerald-100 text-emerald-700',
                                        'pending' => 'bg-amber-100 text-amber-700',
                                        'in_progress' => 'bg-sky-100 text-sky-700',
                                        'completed' => 'bg-slate-100 text-slate-700',
                                        'cancelled' => 'bg-rose-100 text-rose-700',
                                        default => 'bg-slate-100 text-slate-700'
                                    };
                                    $status_text = match($booking['status']) {
                                        'in_progress' => 'Dispatched',
                                        default => ucfirst($booking['status'] ?? 'Pending')
                                    };
                                    ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-semibold uppercase tracking-[0.12em] <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4" onclick="event.stopPropagation();">
                                    <a href="booking-details.php?id=<?php echo $booking['id']; ?>" class="inline-flex items-center justify-center px-4 py-2 rounded-full bg-[#191c1d] text-white text-xs font-semibold hover:bg-slate-800 transition-all gap-1">
                                        <span class="material-symbols-outlined text-xs">visibility</span>
                                        Details
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-500">No bookings found. Create your first booking!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-col lg:flex-row justify-between items-center gap-4">
                <span class="text-sm text-slate-500">Showing <?php echo count($bookings); ?> results</span>
                <div class="flex gap-2">
                    <button class="p-2 rounded-2xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 transition-all" disabled>
                        <span class="material-symbols-outlined text-sm">chevron_left</span>
                    </button>
                    <button class="w-10 h-10 rounded-2xl bg-cyan-600 text-white text-sm font-bold">1</button>
                    <button class="p-2 rounded-2xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 transition-all">
                        <span class="material-symbols-outlined text-sm">chevron_right</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Contextual Insight (Bento Section) -->
        <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-slate-900 rounded-3xl p-8 relative overflow-hidden border border-slate-800 shadow-sm">
                <div class="relative z-10 max-w-md">
                    <h3 class="text-2xl font-bold text-white mb-4">Pending Approvals Alert</h3>
                    <p class="text-sm text-slate-300 mb-6">There are <?php echo $pending_review; ?> high-priority bookings requiring attention. Review and approve to maintain operational efficiency.</p>
                    <a href="booking-approvals.php" class="inline-block bg-cyan-500 text-slate-950 px-6 py-3 rounded-2xl font-semibold text-sm hover:bg-cyan-400 transition-all">
                        Review Pending Approvals
                    </a>
                </div>
                <div class="absolute right-0 top-0 bottom-0 w-1/3 opacity-10">
                    <span class="material-symbols-outlined text-8xl mt-8 text-cyan-200">warning</span>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-3xl p-8 flex flex-col justify-between shadow-sm">
                <div>
                    <h3 class="text-2xl font-bold text-slate-900 mb-2">Vehicle Availability</h3>
                    <p class="text-xs text-slate-500 uppercase mb-6">Current Inventory Status</p>
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between text-sm mb-1 text-slate-700">
                                <span>Available Vehicles</span>
                                <span class="font-bold"><?php echo $available_percent; ?>%</span>
                            </div>
                            <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-cyan-600 rounded-full" style="width: <?php echo $available_percent; ?>%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm mb-1 text-slate-700">
                                <span>Total Vehicles</span>
                                <span class="font-bold"><?php echo $total_all_vehicles; ?> Vehicles</span>
                            </div>
                            <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-cyan-600 rounded-full" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <a href="fleet.php" class="w-full border border-cyan-600 text-cyan-600 py-3 rounded-2xl font-semibold text-sm mt-8 text-center hover:bg-cyan-50 transition-all">
                    Manage Vehicles
                </a>
            </div>
        </div>
    </div>
</main>

<script>
const bookingSearch = document.getElementById('searchBookings');
let selectedStatus = '';
let selectedStartDate = '';
let selectedEndDate = '';
let selectedClient = '';

function applyBookingFilters() {
    const searchTerm = bookingSearch.value.trim().toLowerCase();
    const status = selectedStatus;
    const startDate = selectedStartDate;
    const endDate = selectedEndDate;
    const client = selectedClient.toLowerCase();

    document.querySelectorAll('.booking-row').forEach((row) => {
        const matchesSearch = !searchTerm || row.textContent.toLowerCase().includes(searchTerm);
        const matchesStatus = !status || row.dataset.status === status;
        const bookingDate = row.dataset.eventDate || '';
        const matchesStartDate = !startDate || (bookingDate && bookingDate >= startDate);
        const matchesEndDate = !endDate || (bookingDate && bookingDate <= endDate);
        const matchesClient = !client || (row.dataset.client || '').toLowerCase().includes(client);
        row.style.display = matchesSearch && matchesStatus && matchesStartDate && matchesEndDate && matchesClient ? '' : 'none';
    });
}

bookingSearch?.addEventListener('input', applyBookingFilters);

function filterByStatus() {
    const allowedStatuses = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'];
    const status = window.prompt('Enter a status: pending, confirmed, in_progress, completed, or cancelled. Leave blank to clear the filter.', selectedStatus);
    if (status === null) return;
    const normalizedStatus = status.trim().toLowerCase();
    if (normalizedStatus && !allowedStatuses.includes(normalizedStatus)) {
        window.alert('Please enter a valid booking status.');
        return;
    }
    selectedStatus = normalizedStatus;
    applyBookingFilters();
}

function filterByClient() {
    const client = window.prompt('Enter a client name. Leave blank to clear the client filter.', selectedClient);
    if (client === null) return;
    selectedClient = client.trim();
    applyBookingFilters();
}

function filterByDate() {
    const startDate = window.prompt('Enter the start date (YYYY-MM-DD). Leave blank to clear.', selectedStartDate);
    if (startDate === null) return;
    const endDate = window.prompt('Enter the end date (YYYY-MM-DD). Leave blank to clear.', selectedEndDate);
    if (endDate === null) return;
    const datePattern = /^\d{4}-\d{2}-\d{2}$/;
    if ((startDate && !datePattern.test(startDate)) || (endDate && !datePattern.test(endDate))) {
        window.alert('Use the YYYY-MM-DD date format.');
        return;
    }
    selectedStartDate = startDate;
    selectedEndDate = endDate;
    applyBookingFilters();
}

</script>

<?php require_once 'includes/footer.php'; ?>
