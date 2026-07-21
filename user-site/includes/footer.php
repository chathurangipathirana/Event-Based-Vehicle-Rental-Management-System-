<footer class="bg-gray-900 dark:bg-black w-full border-t border-gray-800">
    <div class="max-w-[1440px] mx-auto py-12 px-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-8">
        <div class="flex flex-col gap-4">
            <div class="text-lg font-black text-white uppercase">FleetElite Sri Lanka</div>
            <p class="font-inter text-xs text-gray-400 max-w-xs">© <?php echo date('Y'); ?> FleetElite Vehicle Rental Management System. All rights reserved.</p>
        </div>
        <div class="flex flex-wrap gap-8">
            <a class="text-gray-500 font-inter text-xs hover:text-red-500 underline transition-all" href="#">Terms of Service</a>
            <a class="text-gray-500 font-inter text-xs hover:text-red-500 underline transition-all" href="#">Privacy Policy</a>
            <a class="text-gray-500 font-inter text-xs hover:text-red-500 underline transition-all" href="#">Fleet Logistics</a>
            <a class="text-gray-500 font-inter text-xs hover:text-red-500 underline transition-all" href="#">Contact Us</a>
        </div>
        <div class="flex gap-4">
            <div class="w-10 h-10 rounded bg-gray-800 flex items-center justify-center text-white hover:bg-primary transition-colors cursor-pointer">
                <span class="material-symbols-outlined">share</span>
            </div>
            <div class="w-10 h-10 rounded bg-gray-800 flex items-center justify-center text-white hover:bg-primary transition-colors cursor-pointer">
                <span class="material-symbols-outlined">mail</span>
            </div>
        </div>
    </div>
</footer>
<script>
    // Mobile menu toggle
    function toggleMobileMenu() {
        const menu = document.getElementById('mobile-menu');
        if (menu) menu.classList.toggle('hidden');
    }
</script>
</body>
</html>