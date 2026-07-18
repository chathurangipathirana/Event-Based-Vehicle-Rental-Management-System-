<?php
$page_title = 'Dashboard';
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();

// Get user's bookings
$stmt = $pdo->prepare("SELECT b.*, v.name as vehicle_name, et.name as event_type_name 
                       FROM bookings b 
                       JOIN vehicles v ON b.vehicle_id = v.id 
                       JOIN event_types et ON b.event_type_id = et.id 
                       WHERE b.user_id = ? 
                       ORDER BY b.created_at DESC 
                       LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$recent_bookings = $stmt->fetchAll();

// Get booking statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(total_amount) as total_spent 
                       FROM bookings WHERE user_id = ? AND status != 'cancelled'");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

// Get upcoming bookings
$stmt = $pdo->prepare("SELECT COUNT(*) as upcoming FROM bookings 
                       WHERE user_id = ? AND event_date >= CURDATE() AND status IN ('pending', 'confirmed')");
$stmt->execute([$_SESSION['user_id']]);
$upcoming = $stmt->fetch();
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/navbar.php'; ?>

<main class="min-h-screen py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-8">
        <div class="mb-8">
            <h1 class="font-h2 text-h2">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
            <p class="text-gray-600">Manage your bookings and profile</p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Total Bookings</p>
                        <p class="text-3xl font-bold text-primary"><?php echo $stats['total'] ?? 0; ?></p>
                    </div>
                    <span class="material-symbols-outlined text-4xl text-gray-400">receipt</span>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Total Spent</p>
                        <p class="text-3xl font-bold text-primary">$<?php echo number_format($stats['total_spent'] ?? 0, 2); ?></p>
                    </div>
                    <span class="material-symbols-outlined text-4xl text-gray-400">payments</span>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Upcoming Bookings</p>
                        <p class="text-3xl font-bold text-primary"><?php echo $upcoming['upcoming'] ?? 0; ?></p>
                    </div>
                    <span class="material-symbols-outlined text-4xl text-gray-400">event</span>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm">Member Since</p>
                        <p class="text-xl font-bold text-primary">2024</p>
                    </div>
                    <span class="material-symbols-outlined text-4xl text-gray-400">verified_user</span>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="bg-white rounded-xl shadow-lg p-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="font-h3 text-h3">Recent Bookings</h2>
                <a href="my-bookings.php" class="text-primary hover:underline">View All</a>
            </div>
            
            <?php if (empty($recent_bookings)): ?>
                <p class="text-gray-600 text-center py-8">No bookings yet. <a href="booking.php" class="text-primary">Book your first vehicle</a></p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b">
                            <tr>
                                <th class="text-left py-3">Booking #</th>
                                <th class="text-left py-3">Vehicle</th>
                                <th class="text-left py-3">Event Type</th>
                                <th class="text-left py-3">Date</th>
                                <th class="text-left py-3">Amount</th>
                                <th class="text-left py-3">Status</th>
                                <th class="text-left py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_bookings as $booking): ?>
                            <tr class="border-b">
                                <td class="py-3"><?php echo $booking['booking_number']; ?></td>
                                <td class="py-3"><?php echo htmlspecialchars($booking['vehicle_name']); ?></td>
                                <td class="py-3"><?php echo htmlspecialchars($booking['event_type_name']); ?></td>
                                <td class="py-3"><?php echo date('M d, Y', strtotime($booking['event_date'])); ?></td>
                                <td class="py-3">$<?php echo number_format($booking['total_amount'], 2); ?></td>
                                <td class="py-3">
                                    <span class="px-2 py-1 rounded text-xs 
                                        <?php echo $booking['status'] == 'confirmed' ? 'bg-green-100 text-green-800' : 
                                            ($booking['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                            ($booking['status'] == 'completed' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800')); ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                                 <td class="py-3">
                                     <?php if ($booking['invoice_generated']): ?>
                                         <a href="invoice-print.php?booking_id=<?php echo $booking['id']; ?>" target="_blank" class="text-primary hover:underline font-semibold">Invoice</a>
                                     <?php else: ?>
                                         <span class="text-gray-400 italic font-normal">No Invoice</span>
                                     <?php endif; ?>
                                 </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            <a href="booking.php" class="bg-primary text-white rounded-xl shadow-lg p-6 text-center hover:bg-red-700 transition">
                <span class="material-symbols-outlined text-3xl mb-2">add_circle</span>
                <h3 class="font-h3 text-h3 mb-2">New Booking</h3>
                <p class="text-white/80">Book a vehicle for your next event</p>
            </a>
            <a href="my-bookings.php" class="bg-gray-800 text-white rounded-xl shadow-lg p-6 text-center hover:bg-gray-900 transition">
                <span class="material-symbols-outlined text-3xl mb-2">list_alt</span>
                <h3 class="font-h3 text-h3 mb-2">My Bookings</h3>
                <p class="text-white/80">View and manage all your bookings</p>
            </a>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>