<?php
$page_title = 'Client Management';
require_once 'includes/auth.php';
requireAdminLogin();
require_once 'config/database.php';

// Get all clients from database
try {
    $clients = $pdo->query("
        SELECT 
            u.*,
            COUNT(b.id) as total_bookings,
            MAX(b.event_date) as last_event_date,
            COALESCE(SUM(b.total_amount), 0) as total_spent
        FROM users u
        LEFT JOIN bookings b ON u.id = b.user_id
        WHERE u.role = 'customer'
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ")->fetchAll();
} catch(PDOException $e) {
    $clients = [];
}

$sample_mode = false;
$sample_bookings = [];
$sample_clients = [
    ['id' => 101, 'full_name' => 'Nimal Perera', 'email' => 'nimal.perera@example.lk', 'phone' => '+94 77 111 2233', 'company_name' => 'Ceylon Luxury Events', 'address' => 'No. 45, Galle Road', 'city' => 'Colombo', 'state' => 'Western', 'zip_code' => '00300', 'total_bookings' => 4, 'last_event_date' => '2025-01-15', 'total_spent' => 1249325, 'created_at' => '2025-01-05 09:00:00'],
    ['id' => 102, 'full_name' => 'Anjali Fernando', 'email' => 'anjali.fernando@example.lk', 'phone' => '+94 71 222 3344', 'company_name' => 'Kandy Heritage Weddings', 'address' => 'No. 12, Peradeniya Road', 'city' => 'Kandy', 'state' => 'Central', 'zip_code' => '20000', 'total_bookings' => 3, 'last_event_date' => '2024-02-22', 'total_spent' => 944000, 'created_at' => '2024-02-10 11:15:00'],
    ['id' => 103, 'full_name' => 'Sameera Jayawardena', 'email' => 'sameera.jayawardena@example.lk', 'phone' => '+94 70 333 5566', 'company_name' => 'Southern Event Rentals', 'address' => 'No. 9, Galle Road', 'city' => 'Galle', 'state' => 'Southern', 'zip_code' => '80000', 'total_bookings' => 2, 'last_event_date' => '2024-03-12', 'total_spent' => 359500, 'created_at' => '2024-03-18 14:20:00'],
    ['id' => 104, 'full_name' => 'Priya Senanayake', 'email' => 'priya.senanayake@example.lk', 'phone' => '+94 78 444 7788', 'company_name' => null, 'address' => 'No. 77, Lotus Road', 'city' => 'Colombo', 'state' => 'Western', 'zip_code' => '00500', 'total_bookings' => 1, 'last_event_date' => '2025-04-22', 'total_spent' => 132750, 'created_at' => '2025-04-20 08:45:00'],
    ['id' => 105, 'full_name' => 'Sanduni Kumar', 'email' => 'sanduni.kumar@example.lk', 'phone' => '+94 76 555 8899', 'company_name' => 'Colombo Gala Planners', 'address' => 'No. 123, Park Street', 'city' => 'Colombo', 'state' => 'Western', 'zip_code' => '00700', 'total_bookings' => 6, 'last_event_date' => '2025-05-30', 'total_spent' => 1760000, 'created_at' => '2025-05-25 10:00:00'],
];

$sample_bookings = [
    101 => [
        ['event_name'=>'Kandy Royal Wedding','vehicle_name'=>'Toyota Premio','status'=>'confirmed','event_date'=>'2025-01-15','total_amount'=>826000],
        ['event_name'=>'Executive Transfer','vehicle_name'=>'Toyota Axio','status'=>'completed','event_date'=>'2025-02-05','total_amount'=>185000],
        ['event_name'=>'Corporate Roadshow','vehicle_name'=>'Honda Vezel','status'=>'completed','event_date'=>'2025-03-20','total_amount'=>183325],
        ['event_name'=>'VIP Airport Pickup','vehicle_name'=>'Toyota HiAce','status'=>'completed','event_date'=>'2025-04-10','total_amount'=>180000],
    ],
    102 => [
        ['event_name'=>'Colombo Tech Summit Logistics','vehicle_name'=>'Honda Vezel','status'=>'completed','event_date'=>'2024-10-22','total_amount'=>944000],
        ['event_name'=>'Island Tour Charter','vehicle_name'=>'Toyota HiAce','status'=>'completed','event_date'=>'2024-09-30','total_amount'=>380000],
        ['event_name'=>'City Wedding Shuttle','vehicle_name'=>'Toyota Axio','status'=>'completed','event_date'=>'2024-09-15','total_amount'=>216000],
    ],
    103 => [
        ['event_name'=>'Galle Event Transport','vehicle_name'=>'Toyota HiAce','status'=>'overdue','event_date'=>'2024-10-24','total_amount'=>226748],
        ['event_name'=>'Beach Wedding Transfer','vehicle_name'=>'Honda Vezel','status'=>'completed','event_date'=>'2024-09-25','total_amount'=>132752],
    ],
    104 => [
        ['event_name'=>'Airport Pickup','vehicle_name'=>'Toyota Axio','status'=>'pending','event_date'=>'2025-06-02','total_amount'=>132750],
    ],
    105 => [
        ['event_name'=>'Colombo Gala VIP Service','vehicle_name'=>'Toyota Premio','status'=>'confirmed','event_date'=>'2025-07-10','total_amount'=>1760000],
        ['event_name'=>'Executive Meeting Transfer','vehicle_name'=>'Honda Vezel','status'=>'completed','event_date'=>'2025-08-15','total_amount'=>215000],
    ],
];

if (empty($clients)) {
    $sample_mode = true;
    $clients = $sample_clients;
}

// Calculate statistics
if ($sample_mode) {
    $total_clients = count($clients);
    $active_corporate = count(array_filter($clients, fn($c) => !empty($c['company_name'])));
    $vip_clients = count(array_filter($clients, fn($c) => $c['total_bookings'] > 5));
    $top10_revenue = array_sum(array_map(fn($c) => $c['total_spent'], $clients));
} else {
    $total_clients = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
    $active_corporate = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer' AND company_name IS NOT NULL AND company_name != ''")->fetchColumn();
    $vip_clients = $pdo->query("
        SELECT COUNT(*) FROM (
            SELECT u.id, COUNT(b.id) as booking_count 
            FROM users u 
            LEFT JOIN bookings b ON u.id = b.user_id 
            WHERE u.role = 'customer' 
            GROUP BY u.id 
            HAVING booking_count > 5
        ) as vip
    ")->fetchColumn();

    $top10_revenue = $pdo->query("
        SELECT COALESCE(SUM(total_amount), 0) as revenue FROM (
            SELECT b.total_amount
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            WHERE u.role = 'customer'
            ORDER BY b.total_amount DESC
            LIMIT 10
        ) as top10
    ")->fetchColumn();
}

// Sidebar removed from this page; profile view handled on `client-details.php` instead
$recent_bookings = [];
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<!-- Main Content Wrapper -->
<main class="ml-64 min-h-screen bg-slate-50 flex flex-col">
    <div class="p-8 max-w-7xl mx-auto w-full">
        <section class="rounded-[2rem] overflow-hidden mb-10">
            <div class="relative bg-slate-900 text-white p-8 lg:p-10 overflow-hidden">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.24),_transparent_36%)] opacity-70 pointer-events-none"></div>
                <div class="relative grid grid-cols-1 xl:grid-cols-[1.5fr_1fr] gap-8 items-center">
                    <div class="max-w-2xl">
                        <p class="text-xs uppercase tracking-[0.35em] text-slate-400 mb-4">Fleet Manager</p>
                        <h1 class="text-5xl font-semibold tracking-tight">Client Management</h1>
                        <p class="mt-4 text-slate-300 text-lg leading-8">Manage client accounts, bookings, and operational relationships with precision.</p>
                    </div>
                </div>
            </div>
        </section>

        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5 mb-10">
            <div class="relative max-w-3xl mx-auto">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                <input type="text" id="searchClients" class="w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-cyan-300 focus:border-cyan-300 text-sm text-slate-700" placeholder="Search clients...">
            </div>
        </div>

        <div class="flex justify-end mb-8">
            <button onclick="openAddClientModal()" class="inline-flex items-center gap-2 px-5 py-3 bg-red-600 text-white rounded-2xl shadow-lg hover:bg-red-700 transition-transform active:scale-95">
                <span class="material-symbols-outlined">person_add</span>
                <span class="text-sm">Add New Client</span>
            </button>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="card-3d p-6 bg-white" style="--card-accent: #0b6b6d;">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Total Clients</p>
                    <div class="kpi-value"><?php echo $total_clients; ?></div>
                    <div class="text-xs text-green-600 mt-1">+12% from last month</div>
                </div>
                <div class="card-icon" style="background:var(--card-accent,#0b6b6d)"><span class="material-symbols-outlined">groups</span></div>
            </div>
            <div class="card-3d p-6 bg-white" style="--card-accent: #0b6b6d;">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Active Corporate</p>
                    <div class="kpi-value"><?php echo $active_corporate; ?></div>
                    <div class="text-xs text-green-600 mt-1">8 new this quarter</div>
                </div>
                <div class="card-icon" style="background:var(--card-accent,#0b6b6d)"><span class="material-symbols-outlined">business</span></div>
            </div>
            <div class="card-3d p-6 bg-white" style="--card-accent: #0b6b6d;">
                <div>
                    <p class="text-sm text-gray-500 uppercase">VIP/Repeat Clients</p>
                    <div class="kpi-value"><?php echo $vip_clients; ?></div>
                    <div class="text-xs text-gray-500 mt-1">Top tier loyalty</div>
                </div>
                <div class="card-icon" style="background:var(--card-accent,#0b6b6d)"><span class="material-symbols-outlined">verified</span></div>
            </div>
            <div class="card-3d p-6 bg-white" style="--card-accent: #0b6b6d;">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Top 10% Revenue</p>
                    <div style="line-height: 1.1;">
                        <div class="text-xs font-medium text-gray-600">LKR</div>
                        <div class="kpi-value"><?php echo number_format($top10_revenue, 0); ?></div>
                    </div>
                    <div class="text-xs text-green-600 mt-1">Account concentration high</div>
                </div>
                <div class="card-icon" style="background:var(--card-accent,#0b6b6d)"><span class="material-symbols-outlined">payments</span></div>
            </div>
        </div>

        <!-- Client Directory Table & Sidebar -->
        <div class="grid grid-cols-12 gap-6">
            <!-- Client Directory Table -->
            <div class="col-span-12 lg:col-span-12 bg-white rounded-xl shadow-sm border border-[#c0c8ca] overflow-hidden">
                <div class="p-6 border-b border-[#c0c8ca] flex justify-between items-center">
                    <h3 class="text-2xl font-bold">Client Directory</h3>
                    <div class="flex space-x-2">
                        <button onclick="filterClients()" class="px-4 py-2 border border-gray-200 rounded-lg text-sm font-bold hover:bg-gray-50 transition-colors">Filter</button>
                        <button onclick="exportClients()" class="px-4 py-2 border border-gray-200 rounded-lg text-sm font-bold hover:bg-gray-50 transition-colors">Export</button>
                    </div>
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
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Client Name</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Account Type</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Total Bookings</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Last Event</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#c0c8ca]/30 text-[#191c1d]" id="clientsTable">
                            <?php foreach ($clients as $client): ?>
                            <tr class="client-row" data-id="<?php echo $client['id']; ?>" data-name="<?php echo strtolower($client['full_name'] . ' ' . $client['company_name']); ?>">
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-bold">
                                            <?php echo strtoupper(substr($client['full_name'], 0, 2)); ?>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($client['full_name']); ?></p>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($client['email']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-[10px] font-black uppercase">
                                        <?php echo $client['company_name'] ? 'Corporate' : 'Private'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium"><?php echo $client['total_bookings']; ?></td>
                                <td class="px-6 py-4 text-sm"><?php echo $client['last_event_date'] ? date('M d, Y', strtotime($client['last_event_date'])) : 'N/A'; ?></td>
                                <td class="px-6 py-4">
                                    <span class="flex items-center space-x-1 text-green-600 font-bold text-xs">
                                        <span class="w-2 h-2 rounded-full bg-green-600"></span>
                                        <span>Active</span>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="client-details.php?id=<?php echo $client['id']; ?>" class="text-red-600 font-bold text-xs hover:underline">View Profile</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($clients)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">No clients found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-100 flex justify-between items-center bg-gray-50">
                    <span class="text-xs text-gray-500 font-medium">Showing <?php echo count($clients); ?> clients</span>
                    <div class="flex space-x-1">
                        <button class="w-8 h-8 flex items-center justify-center rounded border border-gray-200 hover:bg-white transition-colors">
                            <span class="material-symbols-outlined text-sm">chevron_left</span>
                        </button>
                        <button class="w-8 h-8 flex items-center justify-center rounded bg-red-600 text-white font-bold text-sm">1</button>
                        <button class="w-8 h-8 flex items-center justify-center rounded border border-gray-200 hover:bg-white transition-colors">
                            <span class="material-symbols-outlined text-sm">chevron_right</span>
                        </button>
                    </div>
                </div> 
            </div>

            
        </div>
    </div>
</main>

<!-- Add Client Modal -->
<div id="addClientModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
    <div class="modal-content" style="background: white; margin: 50px auto; max-width: 500px; border-radius: 12px;">
        <div class="p-6 border-b">
            <h3 class="text-xl font-bold">Add New Client</h3>
        </div>
        <form method="POST" action="add-client.php" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Full Name *</label>
                <input type="text" name="full_name" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Email *</label>
                <input type="email" name="email" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Phone</label>
                <input type="text" name="phone" class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Company Name</label>
                <input type="text" name="company_name" class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Password *</label>
                <input type="password" name="password" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-red-600 text-white py-2 rounded-lg hover:bg-red-700">Add Client</button>
                <button type="button" onclick="closeModal()" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-lg hover:bg-gray-300">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchClients')?.addEventListener('keyup', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.client-row');
    rows.forEach(row => {
        const name = row.getAttribute('data-name') || '';
        row.style.display = name.includes(searchTerm) ? '' : 'none';
    });
});

function viewClient(clientId) {
    window.location.href = 'client-details.php?id=' + clientId;
}

function openAddClientModal() {
    document.getElementById('addClientModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('addClientModal').style.display = 'none';
}

function filterClients() {
    alert('Filter clients by: All | Corporate | Private | VIP');
}

function exportClients() {
    alert('Exporting clients list as CSV...');
}

function sendStatement(clientId) {
    alert('Sending statement to client #' + clientId);
}

function bookNew(clientId) {
    window.location.href = 'new-booking.php?client=' + clientId;
}

window.onclick = function(event) {
    const modal = document.getElementById('addClientModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
