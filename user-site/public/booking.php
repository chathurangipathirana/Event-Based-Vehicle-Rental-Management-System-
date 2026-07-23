<?php
session_start();

if (isset($_SESSION['user_id'])) {
    require_once 'booking-process.php';
    exit();
}

$package_id = isset($_GET['package_id']) ? (int)$_GET['package_id'] : 0;
$vehicle_id = isset($_GET['vehicle']) ? (int)$_GET['vehicle'] : 0;
$booking_url = $package_id
    ? 'booking.php?package_id=' . $package_id . ($vehicle_id ? '&vehicle=' . $vehicle_id : '')
    : 'booking.php?vehicle=' . $vehicle_id;
$page_title = 'Sign In to Book';
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<main class="min-h-screen bg-gray-50 pt-28 pb-16">
    <div class="max-w-lg mx-auto px-4">
        <section class="rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-lg">
            <span class="material-symbols-outlined text-5xl text-primary">account_circle</span>
            <h1 class="mt-4 text-2xl font-bold text-gray-900">Sign in to complete your booking</h1>
            <p class="mt-3 text-gray-600">Log in to continue with your booking, or create an account if you are new to Royal Lanka Rides.</p>
            <div class="mt-8 space-y-3">
                <a href="login.php?redirect=<?php echo urlencode($booking_url); ?>" class="block w-full rounded-xl bg-primary py-3 font-bold text-white hover:bg-primary-container transition">Log In</a>
                <a href="register.php?redirect=<?php echo urlencode($booking_url); ?>" class="block w-full rounded-xl border border-primary py-3 font-bold text-primary hover:bg-gray-50 transition">Create Account</a>
            </div>
            <a href="<?php echo $package_id ? 'package-details.php?id=' . $package_id : 'vehicle-details.php?id=' . $vehicle_id; ?>" class="mt-6 inline-block text-sm font-semibold text-gray-600 hover:underline">Back to details</a>
        </section>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
