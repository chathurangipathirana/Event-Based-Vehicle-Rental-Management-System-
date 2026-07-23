<?php
$page_title = 'Booking Confirmed';
require_once '../config/database.php';

$booking_id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT b.*, v.name as vehicle_name, v.model 
                       FROM bookings b 
                       JOIN vehicles v ON b.vehicle_id = v.id 
                       WHERE b.id = ?");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

$can_view = $booking && (
    (isset($_SESSION['user_id']) && (int)$booking['user_id'] === (int)$_SESSION['user_id']) ||
    (!isset($_SESSION['user_id']) && isset($_SESSION['guest_booking_id']) && (int)$_SESSION['guest_booking_id'] === $booking_id)
);

if (!$can_view) {
    header('Location: index.php');
    exit();
}
?>

<?php require_once '../includes/header.php'; ?>

<main class="pt-16 min-h-screen bg-gray-50">
    <div class="max-w-2xl mx-auto px-4 py-12">
        <div class="bg-white rounded-xl shadow-lg p-8 text-center">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <span class="material-symbols-outlined text-green-600 text-4xl">check_circle</span>
            </div>
            
            <h1 class="text-3xl font-bold mb-2">Booking Confirmed!</h1>
            <p class="text-gray-600 mb-6">Your vehicle has been successfully booked.</p>
            
            <div class="bg-gray-50 rounded-lg p-6 mb-6 text-left">
                <p class="font-semibold mb-2">Booking Details:</p>
                <p><strong>Booking Number:</strong> <?php echo $booking['booking_number']; ?></p>
                <p><strong>Vehicle:</strong> <?php echo htmlspecialchars($booking['vehicle_name']); ?></p>
                <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($booking['event_date'])); ?></p>
                <p><strong>Total Amount:</strong> <span class="text-red-600 font-bold">LKR <?php echo number_format($booking['total_amount'], 2); ?></span></p>
                <p><strong>Status:</strong> <span class="text-yellow-600"><?php echo ucfirst($booking['status']); ?></span></p>
            </div>
            
            <div class="space-y-3">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="my-bookings.php" class="block w-full bg-red-600 text-white py-3 rounded-lg font-semibold hover:bg-red-700 transition">View My Bookings</a>
                <?php else: ?>
                    <a href="vehicles.php" class="block w-full bg-red-600 text-white py-3 rounded-lg font-semibold hover:bg-red-700 transition">Make Another Booking</a>
                <?php endif; ?>
                <a href="index.php" class="block w-full border border-red-600 text-red-600 py-3 rounded-lg font-semibold hover:bg-red-50 transition">
                    Return to Home
                </a>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
