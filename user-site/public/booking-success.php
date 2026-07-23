<?php
$page_title = 'Booking Status & Confirmation';
require_once '../config/database.php';

$booking_id = (int)($_GET['id'] ?? 0);
$confirm_success_msg = '';

// Handle manual confirm action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_booking') {
    $stmtConfirm = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
    $stmtConfirm->execute([$booking_id]);
    $confirm_success_msg = 'Your booking has been officially confirmed!';
}

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

<main class="pt-16 min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-2xl mx-auto px-4 py-12">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 text-center border border-gray-100 dark:border-gray-700">
            
            <?php if (!empty($confirm_success_msg)): ?>
                <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 font-semibold text-sm flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-emerald-600">check_circle</span>
                    <?php echo htmlspecialchars($confirm_success_msg); ?>
                </div>
            <?php endif; ?>

            <div class="w-20 h-20 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-4xl">check_circle</span>
            </div>
            
            <h1 class="text-3xl font-extrabold mb-2 text-gray-900 dark:text-white">
                <?php echo $booking['status'] === 'confirmed' ? 'Booking Confirmed!' : 'Booking Submitted!'; ?>
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mb-8 font-medium">Thank you for choosing Royal Lanka Rides.</p>
            
            <div class="bg-gray-50 dark:bg-gray-900/60 rounded-xl p-6 mb-8 text-left border border-gray-200 dark:border-gray-700 space-y-3">
                <p class="font-bold text-gray-900 dark:text-white pb-3 border-b border-gray-200 dark:border-gray-700 text-base flex items-center justify-between">
                    <span>Booking Details</span>
                    <span class="text-xs font-mono px-2.5 py-1 rounded bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300">#<?php echo htmlspecialchars($booking['booking_number']); ?></span>
                </p>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <p class="text-gray-500 dark:text-gray-400">Vehicle:</p>
                    <p class="font-semibold text-gray-900 dark:text-white text-right"><?php echo htmlspecialchars($booking['vehicle_name']); ?></p>
                    
                    <p class="text-gray-500 dark:text-gray-400">Event Date:</p>
                    <p class="font-medium text-gray-900 dark:text-white text-right"><?php echo date('F d, Y', strtotime($booking['event_date'])); ?></p>
                    
                    <p class="text-gray-500 dark:text-gray-400">Total Amount:</p>
                    <p class="font-bold text-primary text-right">LKR <?php echo number_format($booking['total_amount'], 2); ?></p>
                    
                    <p class="text-gray-500 dark:text-gray-400">Status:</p>
                    <p class="text-right">
                        <span class="inline-block px-3 py-0.5 text-xs font-bold rounded-full uppercase <?php echo $booking['status'] === 'confirmed' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-100 text-amber-800 border border-amber-300'; ?>">
                            <?php echo ucfirst($booking['status']); ?>
                        </span>
                    </p>
                </div>
            </div>
            
            <div class="flex flex-col gap-3.5">
                <!-- 1. Confirm Button -->
                <?php if ($booking['status'] !== 'confirmed'): ?>
                    <form method="POST" action="booking-success.php?id=<?php echo $booking_id; ?>">
                        <input type="hidden" name="action" value="confirm_booking">
                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 px-6 rounded-xl font-bold transition flex items-center justify-center gap-2 shadow-md active:scale-[0.99]">
                            <span class="material-symbols-outlined">check_circle</span>
                            Confirm Booking
                        </button>
                    </form>
                <?php else: ?>
                    <div class="w-full bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-700 text-emerald-800 dark:text-emerald-300 py-3.5 px-6 rounded-xl font-bold flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400">verified</span>
                        Booking Confirmed
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-2 gap-6" style="column-gap: 1.5rem;">
                    <!-- 2. View Booking Button -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="my-bookings.php" class="w-full bg-primary hover:bg-primary/90 text-white py-3.5 px-4 rounded-xl font-bold transition flex items-center justify-center gap-2 shadow-md active:scale-[0.99]">
                            <span class="material-symbols-outlined">visibility</span>
                            View Booking
                        </a>
                    <?php else: ?>
                        <a href="invoice-print.php?booking_id=<?php echo $booking_id; ?>" target="_blank" class="w-full bg-primary hover:bg-primary/90 text-white py-3.5 px-4 rounded-xl font-bold transition flex items-center justify-center gap-2 shadow-md active:scale-[0.99]">
                            <span class="material-symbols-outlined">visibility</span>
                            View Receipt
                        </a>
                    <?php endif; ?>

                    <!-- 3. Back Button -->
                    <a href="vehicles.php" class="w-full bg-primary hover:bg-primary/90 text-white py-3.5 px-4 rounded-xl font-bold transition flex items-center justify-center gap-2 shadow-md active:scale-[0.99]">
                        <span class="material-symbols-outlined">arrow_back</span>
                        Back to Vehicles
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
