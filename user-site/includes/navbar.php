<?php
// user-site/includes/navbar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    .nav-link-active {
        color: #0d6a75;
        border-bottom: 3px solid #0d6a75;
        border-radius: 0;
        box-shadow: none;
    }
    .nav-link-active:hover {
        color: #02414a;
        border-bottom-color: #02414a;
    }
    .nav-link {
        color: #3f4b53;
        font-weight: 600;
    }
    .nav-link:hover {
        color: #0d6a75;
        background-color: #eef9fa;
    }
</style>
<header class="fixed top-0 w-full z-50 bg-white/95 dark:bg-gray-900/95 backdrop-blur-md border-b border-gray-200 dark:border-gray-800 shadow-sm">
    <nav class="max-w-[1440px] mx-auto flex justify-between items-center px-8 h-16">
        <a href="index.php" class="text-xl font-black text-primary dark:text-red-500 uppercase italic flex items-center gap-2">
            <span class="material-symbols-outlined text-2xl text-primary">directions_car</span>
            FleetElite
        </a>
        <div class="hidden md:flex gap-8 items-center">
            <a class="<?php echo $current_page == 'index.php' ? 'nav-link-active font-bold' : 'nav-link'; ?> px-4 py-2 rounded-full transition-all duration-200" href="index.php">Home</a>
            <a class="<?php echo $current_page == 'vehicles.php' ? 'nav-link-active font-bold' : 'nav-link'; ?> px-4 py-2 rounded-full transition-all duration-200" href="vehicles.php">Vehicles</a>
            <a class="<?php echo $current_page == 'packages.php' ? 'nav-link-active font-bold' : 'nav-link'; ?> px-4 py-2 rounded-full transition-all duration-200" href="packages.php">Packages</a>
            
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <a class="<?php echo $current_page == 'dashboard.php' ? 'nav-link-active font-bold' : 'nav-link'; ?> px-4 py-2 rounded-full transition-all duration-200" href="dashboard.php">Dashboard</a>
                <a class="<?php echo $current_page == 'my-bookings.php' ? 'nav-link-active font-bold' : 'nav-link'; ?> px-4 py-2 rounded-full transition-all duration-200" href="my-bookings.php">My Bookings</a>
                <a class="nav-link px-4 py-2 rounded-full transition-all duration-200" href="logout.php">Logout</a>
            <?php else: ?>
                <a class="<?php echo $current_page == 'login.php' ? 'nav-link-active font-bold' : 'nav-link'; ?> px-4 py-2 rounded-full transition-all duration-200" href="login.php">Login</a>
                <a class="nav-link px-4 py-2 rounded-full transition-all duration-200" href="register.php">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
