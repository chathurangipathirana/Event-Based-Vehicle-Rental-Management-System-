<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar Navigation -->
<aside class="flex flex-col fixed left-0 top-0 h-full py-6 bg-gray-50 h-screen w-64 border-r border-gray-200 z-50">
    <div class="px-6 mb-8">
        <div class="text-lg font-bold text-gray-900">Admin Portal</div>
        <div class="text-xs text-gray-500">Operations Lead</div>
    </div>
    
    <nav class="flex-1 space-y-1">
        <a href="dashboard.php" class="flex items-center px-6 py-3 <?php echo $current_page == 'dashboard.php' ? 'bg-red-50 text-red-600 font-semibold border-r-4 border-red-600' : 'text-gray-500 hover:bg-gray-100'; ?> transition-all">
            <span class="material-symbols-outlined mr-3">dashboard</span>
            Dashboard
        </a>
        <a href="fleet.php" class="flex items-center px-6 py-3 <?php echo $current_page == 'fleet.php' ? 'bg-red-50 text-red-600 font-semibold border-r-4 border-red-600' : 'text-gray-500 hover:bg-gray-100'; ?> transition-all">
            <span class="material-symbols-outlined mr-3">directions_car</span>
            Fleet
        </a>
        <a href="fleet-mobile.php" class="flex items-center px-6 py-3 <?php echo $current_page == 'fleet-mobile.php' ? 'bg-red-50 text-red-600 font-semibold border-r-4 border-red-600' : 'text-gray-500 hover:bg-gray-100'; ?> transition-all lg:hidden">
            <span class="material-symbols-outlined mr-3">smartphone</span>
            Mobile Fleet
        </a>
        <a href="bookings.php" class="flex items-center px-6 py-3 <?php echo $current_page == 'bookings.php' ? 'bg-red-50 text-red-600 font-semibold border-r-4 border-red-600' : 'text-gray-500 hover:bg-gray-100'; ?> transition-all">
            <span class="material-symbols-outlined mr-3">event_available</span>
            Bookings
        </a>
        <a href="booking-approvals.php" class="flex items-center px-6 py-3 <?php echo $current_page == 'booking-approvals.php' ? 'bg-red-50 text-red-600 font-semibold border-r-4 border-red-600' : 'text-gray-500 hover:bg-gray-100'; ?> transition-all">
            <span class="material-symbols-outlined mr-3">pending_actions</span>
            Approvals
        </a>
        <a href="packages.php" class="flex items-center px-6 py-3 <?php echo $current_page == 'packages.php' ? 'bg-red-50 text-red-600 font-semibold border-r-4 border-red-600' : 'text-gray-500 hover:bg-gray-100'; ?> transition-all">
            <span class="material-symbols-outlined mr-3">inventory_2</span>
            Packages
        </a>
        <a href="clients.php" class="flex items-center px-6 py-3 <?php echo $current_page == 'clients.php' ? 'bg-red-50 text-red-600 font-semibold border-r-4 border-red-600' : 'text-gray-500 hover:bg-gray-100'; ?> transition-all">
            <span class="material-symbols-outlined mr-3">group</span>
            Clients
        </a>
        <a href="billing.php" class="flex items-center px-6 py-3 <?php echo $current_page == 'billing.php' ? 'bg-red-50 text-red-600 font-semibold border-r-4 border-red-600' : 'text-gray-500 hover:bg-gray-100'; ?> transition-all">
            <span class="material-symbols-outlined mr-3">receipt_long</span>
            Billing
        </a>
        <a href="analytics.php" class="flex items-center px-6 py-3 <?php echo $current_page == 'analytics.php' ? 'bg-red-50 text-red-600 font-semibold border-r-4 border-red-600' : 'text-gray-500 hover:bg-gray-100'; ?> transition-all">
            <span class="material-symbols-outlined mr-3">insert_chart</span>
            Analytics
        </a>
    </nav>
    
    <div class="px-4 py-6 border-t border-gray-200">
        <a href="settings.php" class="flex items-center px-4 py-2 text-gray-500 hover:bg-gray-100 rounded-lg transition-all">
            <span class="material-symbols-outlined mr-3">settings</span>
            Settings
        </a>
        <a href="logout.php" class="flex items-center px-4 py-2 text-gray-500 hover:bg-gray-100 rounded-lg transition-all mt-2">
            <span class="material-symbols-outlined mr-3">logout</span>
            Logout
        </a>
    </div>
</aside>