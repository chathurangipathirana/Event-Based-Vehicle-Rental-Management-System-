<?php
// user-site/includes/navbar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<header class="fixed top-0 w-full z-50 bg-white/95 dark:bg-gray-900/95 backdrop-blur-md border-b border-gray-200 dark:border-gray-800 shadow-sm">
    <nav class="max-w-[1440px] mx-auto flex justify-between items-center px-8 h-16">
        <a href="index.php" class="text-xl font-black text-cyan-600 dark:text-cyan-500 uppercase italic flex items-center gap-2">
            <span class="material-symbols-outlined text-2xl text-cyan-600">directions_car</span>
            FleetElite
        </a>
        <div class="hidden md:flex gap-8 items-center">
            <a class="font-medium <?php echo $current_page == 'index.php' ? 'text-cyan-600 font-semibold border-b-2 border-cyan-600 pb-1' : 'text-gray-600 hover:text-cyan-600'; ?> transition-colors duration-200" href="index.php">Home</a>
            <a class="font-medium <?php echo $current_page == 'vehicles.php' ? 'text-cyan-600 font-semibold border-b-2 border-cyan-600 pb-1' : 'text-gray-600 hover:text-cyan-600'; ?> transition-colors duration-200" href="vehicles.php">Vehicles</a>
            <a class="font-medium <?php echo $current_page == 'packages.php' ? 'text-cyan-600 font-semibold border-b-2 border-cyan-600 pb-1' : 'text-gray-600 hover:text-cyan-600'; ?> transition-colors duration-200" href="packages.php">Packages</a>
            
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <a class="font-medium <?php echo $current_page == 'dashboard.php' ? 'text-cyan-600 font-semibold border-b-2 border-cyan-600 pb-1' : 'text-gray-600 hover:text-cyan-600'; ?> transition-colors duration-200" href="dashboard.php">Dashboard</a>
                <a class="font-medium <?php echo $current_page == 'my-bookings.php' ? 'text-cyan-600 font-semibold border-b-2 border-cyan-600 pb-1' : 'text-gray-600 hover:text-cyan-600'; ?> transition-colors duration-200" href="my-bookings.php">My Bookings</a>
                <a class="bg-cyan-600 hover:bg-cyan-700 text-white px-5 py-2 font-bold rounded-xl active:scale-95 duration-150 transition-transform shadow-sm" href="logout.php">Logout</a>
            <?php else: ?>
                <a class="font-medium <?php echo $current_page == 'login.php' ? 'text-cyan-600 font-semibold border-b-2 border-cyan-600 pb-1' : 'text-gray-600 hover:text-cyan-600'; ?> transition-colors duration-200" href="login.php">Login</a>
                <a class="bg-cyan-600 hover:bg-cyan-700 text-white px-5 py-2 font-bold rounded-xl active:scale-95 duration-150 transition-transform shadow-sm" href="register.php">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
