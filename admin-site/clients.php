<?php
$page_title = 'Client Management';
require_once 'includes/auth.php';
requireAdminLogin();

// Use absolute path to database config
$config_path = __DIR__ . '/config/database.php';

if (!file_exists($config_path)) {
    die("Database config file not found at: " . $config_path);
}

require_once $config_path;

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

<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Client Management | FleetAdmin</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
    :root { --surface:#f9f9fa; --surface-low:#f3f4f4; --surface-card:#ffffff; --surface-high:#e7e8e9; --primary:#02414a; --primary-soft:#b8ebf7; --primary-hover:#0d5260; --outline:#c0c8ca; --text:#191c1d; --muted:#40484a; --success:#176a3a; --warning:#8a5200; --danger:#ba1a1a; }
    body { font-family: 'Inter', sans-serif; background-color: var(--surface); color: var(--text); }
    body > aside { background-color: var(--primary) !important; border-color: var(--primary) !important; color: #fff; }
    body > aside .text-red-600, body > aside .text-gray-500 { color: rgba(255,255,255,0.72) !important; }
    body > aside > div:first-child .text-red-600 { color: #fff !important; }
    body > aside a { color: rgba(255,255,255,0.72) !important; }
    body > aside a:hover { background-color: rgba(255,255,255,0.08) !important; color: #fff !important; }
    body > aside a[href="clients.php"] { background-color: rgba(255,255,255,0.10) !important; color: #fff !important; border-right: 4px solid var(--primary-soft); }
    body > aside .border-t { border-color: rgba(255,255,255,0.12) !important; }
    .bg-white { background-color: var(--surface-card) !important; }
    .bg-gray-50, .hover\:bg-gray-50:hover, .hover\:bg-gray-100:hover { background-color: var(--surface-low) !important; }
    .bg-gray-100, .bg-gray-200 { background-color: var(--surface-high) !important; }
    .border-gray-100, .border-gray-200, .border-gray-300 { border-color: var(--outline) !important; }
    .text-gray-900, .text-gray-800, .text-gray-700 { color: var(--text) !important; }
    .text-gray-600, .text-gray-500, .text-gray-400 { color: var(--muted) !important; }
    .bg-red-600 { background-color: var(--primary) !important; }
    .bg-red-50, .bg-red-100 { background-color: var(--primary-soft) !important; }
    .hover\:bg-red-700:hover { background-color: var(--primary-hover) !important; }
    .text-red-600 { color: var(--primary) !important; }
    [class*="bg-red-"] { background-color: var(--primary) !important; }
    [class*="text-red-"] { color: var(--primary) !important; }
    [class*="border-red-"] { border-color: var(--primary-soft) !important; }
    [class*="hover:bg-red-"]:hover { background-color: var(--primary-hover) !important; }
    [class*="hover:text-red-"]:hover { color: var(--primary-hover) !important; }
    .bg-red-50, .bg-red-100 { background-color: var(--primary-soft) !important; }
    .text-green-600, .text-green-700 { color: var(--success) !important; }
    .bg-green-100 { background-color: #dff5e8 !important; }
    .text-yellow-700, .text-orange-600 { color: var(--warning) !important; }
    .bg-yellow-100 { background-color: #fff3d6 !important; }
    .rounded-xl { border-radius: 0.75rem !important; }
    .rounded-lg { border-radius: 0.5rem !important; }
    .shadow-lg, .shadow-md, .shadow-sm { box-shadow: 0 10px 24px rgba(25,28,29,0.06) !important; }
</style>
</head>
<body class="text-on-background">

<!-- Sidebar Navigation -->
<aside class="fixed left-0 top-0 h-screen flex flex-col p-4 space-y-4 w-64 bg-white border-r border-gray-200 z-50">
    <div class="flex flex-col mb-8 px-2">
        <span class="text-2xl font-bold text-red-600">FleetAdmin</span>
        <span class="text-sm text-gray-500">Event Logistics Portal</span>
    </div>
    <nav class="flex-1 space-y-2">
        <a href="dashboard.php" class="flex items-center space-x-3 text-gray-500 hover:bg-gray-100 px-4 py-3 rounded-xl transition-colors duration-200">
            <span class="material-symbols-outlined">dashboard</span>
            <span class="text-sm font-medium">Dashboard</span>
        </a>
        <a href="fleet.php" class="flex items-center space-x-3 text-gray-500 hover:bg-gray-100 px-4 py-3 rounded-xl transition-colors duration-200">
            <span class="material-symbols-outlined">directions_car</span>
            <span class="text-sm font-medium">Fleet</span>
        </a>
        <a href="bookings.php" class="flex items-center space-x-3 text-gray-500 hover:bg-gray-100 px-4 py-3 rounded-xl transition-colors duration-200">
            <span class="material-symbols-outlined">calendar_month</span>
            <span class="text-sm font-medium">Bookings</span>
        </a>
        <a href="clients.php" class="flex items-center space-x-3 bg-red-50 text-red-600 font-bold rounded-xl px-4 py-3 transition-transform">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">group</span>
            <span class="text-sm font-medium">Clients</span>
        </a>
        <a href="analytics.php" class="flex items-center space-x-3 text-gray-500 hover:bg-gray-100 px-4 py-3 rounded-xl transition-colors duration-200">
            <span class="material-symbols-outlined">analytics</span>
            <span class="text-sm font-medium">Analytics</span>
        </a>
    </nav>
    <a href="new-booking.php" class="bg-red-600 hover:bg-red-700 text-white py-3 px-4 rounded-xl flex items-center justify-center space-x-2 font-bold shadow-sm transition-all active:scale-95">
        <span class="material-symbols-outlined text-[20px]">add</span>
        <span class="text-sm">Add New Vehicle</span>
    </a>
    <div class="pt-4 border-t border-gray-200 space-y-2">
        <a href="settings.php" class="flex items-center space-x-3 text-gray-500 px-4 py-2 rounded-xl hover:bg-gray-100 transition-colors">
            <span class="material-symbols-outlined">settings</span>
            <span class="text-sm">Settings</span>
        </a>
        <a href="logout.php" class="flex items-center space-x-3 text-gray-500 px-4 py-2 rounded-xl hover:bg-gray-100 transition-colors">
            <span class="material-symbols-outlined">logout</span>
            <span class="text-sm">Logout</span>
        </a>
    </div>
</aside>

<!-- Main Content Wrapper -->
<main class="ml-64 min-h-screen flex flex-col">
    <!-- Top App Bar -->
    <header class="bg-white shadow-sm sticky top-0 z-40 h-16 w-full px-8 flex justify-between items-center">
        <div class="flex items-center bg-gray-50 rounded-full px-4 py-2 w-96">
            <span class="material-symbols-outlined text-gray-400 mr-2">search</span>
            <input type="text" id="searchClients" class="bg-transparent border-none focus:ring-0 text-sm w-full placeholder:text-gray-400" placeholder="Search clients, accounts, or contacts...">
        </div>
        <div class="flex items-center space-x-6">
            <div class="flex space-x-4">
                <button class="text-gray-400 hover:bg-gray-100 p-2 rounded-full transition-all">
                    <span class="material-symbols-outlined">notifications</span>
                </button>
            </div>
            <div class="flex items-center space-x-3 border-l border-gray-200 pl-6">
                <div class="text-right">
                    <p class="text-sm font-bold text-gray-900"><?php echo $_SESSION['admin_name'] ?? 'Admin User'; ?></p>
                    <p class="text-xs text-gray-500">Super Administrator</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-bold text-lg">
                    <?php echo strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 2)); ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Dashboard Content -->
    <div class="p-10 max-w-7xl mx-auto w-full">
        <!-- Page Header -->
        <div class="flex justify-between items-end mb-6">
            <div>
                <h1 class="text-4xl font-bold text-gray-900">Client Management</h1>
                <p class="text-base text-gray-500 mt-1">Manage and monitor corporate and private fleet accounts.</p>
            </div>
            <button onclick="openAddClientModal()" class="bg-red-600 text-white px-6 py-3 rounded-lg font-bold flex items-center space-x-2 shadow-lg transition-transform active:scale-95">
                <span class="material-symbols-outlined">person_add</span>
                <span class="text-sm">Add New Client</span>
            </button>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <span class="text-sm font-bold text-gray-400 uppercase tracking-wider">Total Clients</span>
                <h2 class="text-4xl font-bold mt-2"><?php echo $total_clients; ?></h2>
                <div class="mt-4 flex items-center text-green-600 text-xs font-bold">
                    <span class="material-symbols-outlined mr-1 text-sm">trending_up</span>
                    <span>+12% from last month</span>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <span class="text-sm font-bold text-gray-400 uppercase tracking-wider">Active Corporate Accounts</span>
                <h2 class="text-4xl font-bold mt-2"><?php echo $active_corporate; ?></h2>
                <div class="mt-4 flex items-center text-red-600 text-xs font-bold">
                    <span class="material-symbols-outlined mr-1 text-sm">business</span>
                    <span>8 new this quarter</span>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <span class="text-sm font-bold text-gray-400 uppercase tracking-wider">VIP/Repeat Clients</span>
                <h2 class="text-4xl font-bold mt-2"><?php echo $vip_clients; ?></h2>
                <div class="mt-4 flex items-center text-gray-600 text-xs font-bold">
                    <span class="material-symbols-outlined mr-1 text-sm">verified</span>
                    <span>Top tier loyalty</span>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <span class="text-sm font-bold text-gray-400 uppercase tracking-wider">Revenue from Top 10%</span>
                <h2 class="text-4xl font-bold mt-2">LKR <?php echo number_format($top10_revenue, 0); ?></h2>
                <div class="mt-4 flex items-center text-green-600 text-xs font-bold">
                    <span class="material-symbols-outlined mr-1 text-sm">payments</span>
                    <span>Account concentration high</span>
                </div>
            </div>
        </div>

        <!-- Client Directory Table & Sidebar -->
        <div class="grid grid-cols-12 gap-6">
            <!-- Client Directory Table -->
            <div class="col-span-12 lg:col-span-8 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-2xl font-bold">Client Directory</h3>
                    <div class="flex space-x-2">
                        <button onclick="filterClients()" class="px-4 py-2 border border-gray-200 rounded-lg text-sm font-bold hover:bg-gray-50 transition-colors">Filter</button>
                        <button onclick="exportClients()" class="px-4 py-2 border border-gray-200 rounded-lg text-sm font-bold hover:bg-gray-50 transition-colors">Export</button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50 text-xs font-bold text-gray-400 uppercase tracking-wider">
                                <th class="px-6 py-4">Client Name</th>
                                <th class="px-6 py-4">Account Type</th>
                                <th class="px-6 py-4">Total Bookings</th>
                                <th class="px-6 py-4">Last Event</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100" id="clientsTable">
                            <?php foreach ($clients as $client): ?>
                            <tr class="hover:bg-gray-50 transition-colors cursor-pointer client-row" data-id="<?php echo $client['id']; ?>" data-name="<?php echo strtolower($client['full_name'] . ' ' . $client['company_name']); ?>" onclick="viewClient(<?php echo $client['id']; ?>)">
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
                    <span class="text-xs text-gray-500 font-medium">Showing 1-<?php echo min(10, count($clients)); ?> of <?php echo count($clients); ?> clients</span>
                    <div class="flex space-x-1">
                        <button class="w-8 h-8 flex items-center justify-center rounded border border-gray-200 hover:bg-white transition-colors">
                            <span class="material-symbols-outlined text-sm">chevron_left</span>
                        </button>
                        <button class="w-8 h-8 flex items-center justify-center rounded bg-red-600 text-white font-bold text-sm">1</button>
                        <button class="w-8 h-8 flex items-center justify-center rounded border border-gray-200 hover:bg-white transition-colors text-sm">2</button>
                        <button class="w-8 h-8 flex items-center justify-center rounded border border-gray-200 hover:bg-white transition-colors text-sm">3</button>
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
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($selected_client['phone'] ?: '+1 (555) 000-0000'); ?></p>
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

</body>
</html>
