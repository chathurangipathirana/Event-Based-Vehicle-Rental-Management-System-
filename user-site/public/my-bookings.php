<?php
$page_title = 'My Bookings';
require_once '../config/database.php';
require_once '../includes/auth.php';
requireLogin();

// Get all user bookings
$stmt = $pdo->prepare("SELECT b.*, v.name as vehicle_name, v.image_url, et.name as event_type_name 
                       FROM bookings b 
                       JOIN vehicles v ON b.vehicle_id = v.id 
                       JOIN event_types et ON b.event_type_id = et.id 
                       WHERE b.user_id = ? 
                       ORDER BY b.event_date DESC");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();

// Handle cancellation
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $booking_id = (int)$_GET['cancel'];
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute([$booking_id, $_SESSION['user_id']]);

    if ($stmt->rowCount() > 0) {
        header('Location: my-bookings.php?msg=cancelled');
        exit();
    }

    header('Location: my-bookings.php?msg=cancel-error');
    exit();
}
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/navbar.php'; ?>

<main class="min-h-screen py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="font-h2 text-h2">My Bookings</h1>
                <p class="text-gray-600">View and manage all your vehicle bookings</p>
            </div>
            <a href="vehicles.php" class="inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold px-6 py-3 rounded-xl shadow-md transition-all active:scale-[0.99]">
                <span class="material-symbols-outlined text-xl">add_circle</span>
                <span>New Booking</span>
            </a>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'cancelled'): ?>
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-700">
                Booking cancelled successfully. Your booking is now marked as cancelled.
            </div>
        <?php elseif (isset($_GET['msg']) && $_GET['msg'] == 'cancel-error'): ?>
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700">
                Only pending bookings can be cancelled.
            </div>
        <?php endif; ?>

        <?php if (empty($bookings)): ?>
            <div class="bg-white rounded-xl shadow-lg p-12 text-center">
                <span class="material-symbols-outlined text-6xl text-gray-400 mb-4">event_busy</span>
                <h3 class="font-h3 text-h3 mb-2">No Bookings Yet</h3>
                <p class="text-gray-600 mb-6">You haven't made any vehicle bookings yet.</p>
                <a href="booking.php" class="bg-primary text-white px-6 py-3 rounded-lg inline-block hover:bg-red-700 transition">
                    Book Your First Vehicle
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($bookings as $booking): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="md:flex">
                        <div class="md:w-52 h-48 bg-slate-900 flex items-center justify-center overflow-hidden relative flex-shrink-0">
                            <?php $bImg = getVehicleImageUrl($booking['image_url'], $booking['vehicle_name']); ?>
                            <img src="<?php echo htmlspecialchars($bImg); ?>" alt="<?php echo htmlspecialchars($booking['vehicle_name']); ?>" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='assets/vehicle-default.svg'">
                        </div>
                        <div class="flex-1 p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="font-h3 text-h3 mb-1"><?php echo htmlspecialchars($booking['vehicle_name']); ?></h3>
                                    <p class="text-gray-600">Booking #: <?php echo $booking['booking_number']; ?></p>
                                </div>
                                <span class="px-3 py-1 rounded text-sm 
                                    <?php echo $booking['status'] == 'confirmed' ? 'bg-green-100 text-green-800' : 
                                        ($booking['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                        ($booking['status'] == 'completed' ? 'bg-blue-100 text-blue-800' : 
                                        ($booking['status'] == 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'))); ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <p class="text-sm text-gray-600">Event Type</p>
                                    <p class="font-semibold"><?php echo htmlspecialchars($booking['event_type_name']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Event Date</p>
                                    <p class="font-semibold"><?php echo date('F d, Y', strtotime($booking['event_date'])); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Time</p>
                                    <p class="font-semibold"><?php echo date('g:i A', strtotime($booking['start_time'])); ?> - <?php echo date('g:i A', strtotime($booking['end_time'])); ?></p>
                                </div>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm text-gray-600">Total Amount</p>
                                    <p class="text-2xl font-bold text-primary">LKR <?php echo number_format($booking['total_amount'], 2); ?></p>
                                </div>
                                <div class="space-x-3">
                                    <?php if ($booking['invoice_generated']): ?>
                                        <a href="invoice-print.php?booking_id=<?php echo $booking['id']; ?>" target="_blank" class="inline-block px-4 py-2 bg-primary text-white rounded hover:bg-red-700 transition">
                                            Download Invoice
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($booking['status'] == 'pending'): ?>
                                        <a href="?cancel=<?php echo $booking['id']; ?>" onclick="return confirm('Are you sure you want to cancel this booking?')" class="inline-block px-4 py-2 border border-red-500 bg-red-500 text-white rounded hover:bg-red-700 hover:border-red-700 transition">
                                            Cancel Booking
                                        </a>
                                    <?php elseif ($booking['status'] == 'cancelled'): ?>
                                        <span class="inline-block px-4 py-2 border border-red-300 bg-red-50 text-red-600 rounded cursor-not-allowed">
                                            Cancelled
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
