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

// Calculate statistics
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

// Get selected client for sidebar
$selected_client_id = isset($_GET['view']) ? intval($_GET['view']) : ($clients[0]['id'] ?? 0);
$selected_client = null;

if ($selected_client_id) {
    $stmt = $pdo->prepare("
        SELECT 
            u.*,
            COUNT(b.id) as total_bookings,
            MAX(b.event_date) as last_event_date,
            COALESCE(SUM(b.total_amount), 0) as total_spent,
            SUM(CASE WHEN b.status = 'pending' THEN b.total_amount ELSE 0 END) as outstanding_balance
        FROM users u
        LEFT JOIN bookings b ON u.id = b.user_id
        WHERE u.id = ? AND u.role = 'customer'
        GROUP BY u.id
    ");
    $stmt->execute([$selected_client_id]);
    $selected_client = $stmt->fetch();
    
    // Get recent bookings for this client
    $stmt2 = $pdo->prepare("
        SELECT b.*, v.name as vehicle_name
        FROM bookings b
        JOIN vehicles v ON b.vehicle_id = v.id
        WHERE b.user_id = ?
        ORDER BY b.created_at DESC
        LIMIT 5
    ");
    $stmt2->execute([$selected_client_id]);
    $recent_bookings = $stmt2->fetchAll();
}
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
                        <p class="text-xs uppercase tracking-[0.35em] text-slate-400 mb-4">Vehicle Manager</p>
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
            <div class="col-span-12 lg:col-span-8 bg-white rounded-xl shadow-sm border border-[#c0c8ca] overflow-hidden">
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
                            <tr class="cursor-pointer client-row" data-id="<?php echo $client['id']; ?>" data-name="<?php echo strtolower($client['full_name'] . ' ' . $client['company_name']); ?>" onclick="viewClient(<?php echo $client['id']; ?>)">
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
                                    <a href="?view=<?php echo $client['id']; ?>" class="text-red-600 font-bold text-xs hover:underline">View Profile</a>
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

            <!-- Sidebar Detail (Selected Client) -->
            <aside class="col-span-12 lg:col-span-4 space-y-6">
                <?php if ($selected_client): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden sticky top-24">
                    <div class="h-24 bg-red-600 relative">
                        <div class="absolute -bottom-6 left-6">
                            <div class="w-16 h-16 rounded-xl bg-white shadow-md flex items-center justify-center text-red-600 text-2xl font-black border-2 border-white">
                                <?php echo strtoupper(substr($selected_client['full_name'], 0, 2)); ?>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 pt-10 pb-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($selected_client['full_name']); ?></h3>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($selected_client['company_name'] ?: 'Private Client'); ?></p>
                            </div>
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-[10px] font-bold uppercase">VIP Client</span>
                        </div>
                        <div class="mt-8 space-y-6">
                            <div class="flex items-start space-x-4">
                                <span class="material-symbols-outlined text-gray-400">badge</span>
                                <div>
                                    <p class="text-xs text-gray-400 font-bold uppercase">Account Manager</p>
                                    <p class="text-base font-medium"><?php echo $_SESSION['admin_name'] ?? 'Admin'; ?></p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <span class="material-symbols-outlined text-gray-400">contact_mail</span>
                                <div>
                                    <p class="text-xs text-gray-400 font-bold uppercase">Primary Contact</p>
                                    <p class="text-base font-medium"><?php echo htmlspecialchars($selected_client['full_name']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($selected_client['phone'] ?: '+94 77 123 4567'); ?></p>
                                </div>
                            </div>
                            <div class="flex items-start space-x-4">
                                <span class="material-symbols-outlined text-gray-400">directions_car</span>
                                <div>
                                    <p class="text-xs text-gray-400 font-bold uppercase">Preferred Category</p>
                                    <p class="text-base font-medium">Luxury Sedans &amp; SUVs</p>
                                </div>
                            </div>
                            <div class="p-4 bg-red-50 rounded-lg border border-red-200">
                                <div class="flex justify-between items-center">
                                    <p class="text-xs text-red-800 font-bold uppercase">Outstanding Balance</p>
                                    <span class="material-symbols-outlined text-red-600">warning</span>
                                </div>
                                <p class="text-3xl font-bold text-red-800 mt-1">LKR <?php echo number_format($selected_client['outstanding_balance'] ?? 0, 2); ?></p>
                            </div>
                        </div>
                        <div class="mt-8 grid grid-cols-2 gap-3">
                            <button onclick="sendStatement(<?php echo $selected_client['id']; ?>)" class="bg-gray-100 hover:bg-gray-200 text-gray-900 py-3 px-4 rounded-lg font-bold text-sm flex items-center justify-center space-x-2 transition-colors">
                                <span class="material-symbols-outlined">account_balance_wallet</span>
                                <span>Send Statement</span>
                            </button>
                            <button onclick="bookNew(<?php echo $selected_client['id']; ?>)" class="bg-red-600 text-white py-3 px-4 rounded-lg font-bold text-sm flex items-center justify-center space-x-2 transition-transform active:scale-95">
                                <span class="material-symbols-outlined">calendar_add_on</span>
                                <span>Book New</span>
                            </button>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 flex justify-center border-t border-gray-100">
                        <a href="client-details.php?id=<?php echo $selected_client['id']; ?>" class="text-red-600 font-bold text-sm flex items-center">
                            <span>View Full Profile &amp; History</span>
                            <span class="material-symbols-outlined ml-1 text-sm">arrow_forward</span>
                        </a>
                    </div>
                </div>

                <!-- Recent Activity Feed -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h4 class="text-sm font-bold text-gray-900 uppercase mb-4">Recent Bookings</h4>
                    <div class="space-y-4">
                        <?php if (!empty($recent_bookings)): ?>
                            <?php foreach ($recent_bookings as $booking): ?>
                            <div class="flex items-center space-x-3 text-sm">
                                <div class="w-2 h-2 rounded-full bg-red-600"></div>
                                <div class="flex-1">
                                    <p class="font-bold"><?php echo htmlspecialchars($booking['event_name'] ?: 'Vehicle Rental'); ?></p>
                                    <p class="text-gray-500 text-xs"><?php echo htmlspecialchars($booking['vehicle_name']); ?> • <?php echo ucfirst($booking['status']); ?></p>
                                </div>
                                <span class="text-gray-400 text-xs"><?php echo date('M d', strtotime($booking['event_date'])); ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-gray-500 text-sm text-center">No recent bookings</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </aside>
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
    window.location.href = 'clients.php?view=' + clientId;
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
    exportElementAsPDF('main', 'clients-report.pdf');
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
