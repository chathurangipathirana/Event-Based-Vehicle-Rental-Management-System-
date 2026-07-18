<?php
$page_title = 'Client Profile';
require_once 'includes/auth.php';
requireAdminLogin();
require_once 'config/database.php';

$client_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$client_id) {
    header('Location: clients.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT u.*, COUNT(b.id) as total_bookings, MAX(b.event_date) as last_event_date, COALESCE(SUM(b.total_amount),0) as total_spent
        FROM users u
        LEFT JOIN bookings b ON u.id = b.user_id
        WHERE u.id = ? AND u.role = 'customer'
        GROUP BY u.id");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch();

    $recent_bookings = [];
    if ($client) {
        $s2 = $pdo->prepare("SELECT b.*, v.name as vehicle_name FROM bookings b LEFT JOIN vehicles v ON b.vehicle_id = v.id WHERE b.user_id = ? ORDER BY b.created_at DESC LIMIT 10");
        $s2->execute([$client_id]);
        $recent_bookings = $s2->fetchAll();
    }
} catch (PDOException $e) {
    $client = null;
    $recent_bookings = [];
}

// If client not found in DB, fall back to built-in sample clients (ids 101-105)
if (!$client) {
    $sample_clients = [
        101 => ['id' => 101, 'full_name' => 'Nimal Perera', 'email' => 'nimal.perera@example.lk', 'phone' => '+94 77 111 2233', 'company_name' => 'Ceylon Luxury Events', 'address' => 'No. 45, Galle Road', 'city' => 'Colombo', 'state' => 'Western', 'zip_code' => '00300', 'total_bookings' => 4, 'last_event_date' => '2025-01-15', 'total_spent' => 1249325, 'created_at' => '2025-01-05 09:00:00'],
        102 => ['id' => 102, 'full_name' => 'Anjali Fernando', 'email' => 'anjali.fernando@example.lk', 'phone' => '+94 71 222 3344', 'company_name' => 'Kandy Heritage Weddings', 'address' => 'No. 12, Peradeniya Road', 'city' => 'Kandy', 'state' => 'Central', 'zip_code' => '20000', 'total_bookings' => 3, 'last_event_date' => '2024-02-22', 'total_spent' => 944000, 'created_at' => '2024-02-10 11:15:00'],
        103 => ['id' => 103, 'full_name' => 'Sameera Jayawardena', 'email' => 'sameera.jayawardena@example.lk', 'phone' => '+94 70 333 5566', 'company_name' => 'Southern Event Rentals', 'address' => 'No. 9, Galle Road', 'city' => 'Galle', 'state' => 'Southern', 'zip_code' => '80000', 'total_bookings' => 2, 'last_event_date' => '2024-03-12', 'total_spent' => 359500, 'created_at' => '2024-03-18 14:20:00'],
        104 => ['id' => 104, 'full_name' => 'Priya Senanayake', 'email' => 'priya.senanayake@example.lk', 'phone' => '+94 78 444 7788', 'company_name' => null, 'address' => 'No. 77, Lotus Road', 'city' => 'Colombo', 'state' => 'Western', 'zip_code' => '00500', 'total_bookings' => 1, 'last_event_date' => '2025-04-22', 'total_spent' => 132750, 'created_at' => '2025-04-20 08:45:00'],
        105 => ['id' => 105, 'full_name' => 'Sanduni Kumar', 'email' => 'sanduni.kumar@example.lk', 'phone' => '+94 76 555 8899', 'company_name' => 'Colombo Gala Planners', 'address' => 'No. 123, Park Street', 'city' => 'Colombo', 'state' => 'Western', 'zip_code' => '00700', 'total_bookings' => 6, 'last_event_date' => '2025-05-30', 'total_spent' => 1760000, 'created_at' => '2025-05-25 10:00:00'],
    ];

    $sample_bookings = [
        101 => [
            ['event_name'=>'Kandy Royal Wedding','vehicle_name'=>'Toyota Premio','status'=>'confirmed','event_date'=>'2025-01-15','total_amount'=>826000],
            ['event_name'=>'Executive Transfer','vehicle_name'=>'Toyota Axio','status'=>'completed','event_date'=>'2025-02-05','total_amount'=>185000],
        ],
        102 => [
            ['event_name'=>'Colombo Tech Summit Logistics','vehicle_name'=>'Honda Vezel','status'=>'completed','event_date'=>'2024-02-22','total_amount'=>944000],
        ],
        103 => [
            ['event_name'=>'Galle Event Transport','vehicle_name'=>'Toyota HiAce','status'=>'overdue','event_date'=>'2024-03-24','total_amount'=>226748],
        ],
        104 => [
            ['event_name'=>'Airport Pickup','vehicle_name'=>'Toyota Axio','status'=>'pending','event_date'=>'2025-06-02','total_amount'=>132750],
        ],
        105 => [
            ['event_name'=>'Colombo Gala VIP Service','vehicle_name'=>'Toyota Premio','status'=>'confirmed','event_date'=>'2025-07-10','total_amount'=>1760000],
        ],
    ];

    if (isset($sample_clients[$client_id])) {
        $client = $sample_clients[$client_id];
        $recent_bookings = $sample_bookings[$client_id] ?? [];
    }
}
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<main class="ml-64 min-h-screen bg-slate-50 flex flex-col">
    <div class="p-8 max-w-4xl mx-auto w-full">
        <?php if (!$client): ?>
            <div class="bg-white p-8 rounded-xl shadow">
                <h2 class="text-xl font-bold">Client not found</h2>
                <p class="text-sm text-gray-500">The requested client was not found in the database.</p>
                <div class="mt-4">
                    <a href="clients.php" class="px-4 py-2 bg-red-600 text-white rounded">Back to clients</a>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-xl border p-6 shadow mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 rounded-xl bg-red-100 flex items-center justify-center text-red-600 text-2xl font-black"><?php echo strtoupper(substr($client['full_name'],0,2)); ?></div>
                        <div>
                            <h1 class="text-2xl font-bold"><?php echo htmlspecialchars($client['full_name']); ?></h1>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($client['company_name'] ?: 'Private Client'); ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-500 uppercase">Total Bookings</div>
                        <div class="text-2xl font-bold"><?php echo $client['total_bookings']; ?></div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                    <div>
                        <p class="text-xs text-gray-400 uppercase">Primary Contact</p>
                        <p class="font-medium"><?php echo htmlspecialchars($client['full_name']); ?></p>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($client['phone'] ?? 'N/A'); ?></p>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($client['email']); ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase">Address</p>
                        <p class="font-medium"><?php echo nl2br(htmlspecialchars($client['address'] ?? '')); ?></p>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($client['city'] . ($client['state'] ? ', '.$client['state'] : '')); ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase">Financials</p>
                        <p class="font-medium">LKR <?php echo number_format($client['total_spent'] ?? 0, 2); ?></p>
                        <p class="text-sm text-gray-500">Last event: <?php echo $client['last_event_date'] ? date('M d, Y', strtotime($client['last_event_date'])) : 'N/A'; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border p-6 shadow">
                <h3 class="text-lg font-bold mb-4">Recent Bookings</h3>
                <?php if (!empty($recent_bookings)): ?>
                    <div class="space-y-3">
                        <?php foreach ($recent_bookings as $b): ?>
                            <div class="p-3 border rounded flex justify-between items-center">
                                <div>
                                    <div class="font-bold"><?php echo htmlspecialchars($b['event_name'] ?: 'Vehicle Rental'); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($b['vehicle_name'] ?? ''); ?> • <?php echo ucfirst($b['status']); ?></div>
                                </div>
                                <div class="text-sm text-gray-400"><?php echo date('M d, Y', strtotime($b['event_date'])); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-gray-500">No bookings found for this client.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
