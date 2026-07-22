<footer class="bg-gray-900 dark:bg-black w-full border-t border-gray-800">
    <div class="max-w-[1440px] mx-auto py-12 px-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-8">
        <div class="flex flex-col gap-4">
            <div class="text-lg font-black text-white uppercase">STS Sri Lanka</div>
            <p class="font-inter text-xs text-gray-400 max-w-xs">Copyright © <?php echo date('Y'); ?> STS. All Rights Reserved.</p>
        </div>
        <div class="flex flex-wrap gap-8">
            <a class="text-gray-500 font-inter text-xs hover:text-red-500 underline transition-all" href="terms.php">Terms of Service</a>
            <a class="text-gray-500 font-inter text-xs hover:text-red-500 underline transition-all" href="privacy.php">Privacy Policy</a>
            <a class="text-gray-500 font-inter text-xs hover:text-red-500 underline transition-all" href="logistics.php">Fleet Logistics</a>
            <a class="text-gray-500 font-inter text-xs hover:text-red-500 underline transition-all" href="contact.php">Contact Us</a>
        </div>
        <div class="flex gap-4">
            <button onclick="sharePage()" type="button" title="Share Page" class="w-10 h-10 rounded bg-gray-800 flex items-center justify-center text-white hover:bg-primary transition-colors cursor-pointer focus:outline-none">
                <span class="material-symbols-outlined">share</span>
            </button>
            <a href="contact.php" title="Contact Us / Support Email" class="w-10 h-10 rounded bg-gray-800 flex items-center justify-center text-white hover:bg-primary transition-colors cursor-pointer">
                <span class="material-symbols-outlined">mail</span>
            </a>
        </div>
    </div>
</footer>
<div id="toast-notification" class="fixed bottom-6 right-6 z-50 hidden bg-slate-900 text-white px-5 py-3 rounded-xl shadow-2xl border border-slate-700 flex items-center gap-3 transition-all duration-300">
    <span class="material-symbols-outlined text-emerald-400">check_circle</span>
    <span id="toast-message" class="text-sm font-medium">Link copied to clipboard!</span>
</div>
<script>
    // Mobile menu toggle
    function toggleMobileMenu() {
        const menu = document.getElementById('mobile-menu');
        if (menu) menu.classList.toggle('hidden');
    }

    // Share page handler
    async function sharePage() {
        const pageTitle = document.title || 'STS Vehicle Rental';
        const pageUrl = window.location.href;

        if (navigator.share) {
            try {
                await navigator.share({
                    title: pageTitle,
                    url: pageUrl
                });
                return;
            } catch (err) {
                // Fallback to clipboard if share dialog is cancelled or unsupported
            }
        }

        if (navigator.clipboard) {
            try {
                await navigator.clipboard.writeText(pageUrl);
                showToast('Link copied to clipboard!');
            } catch (err) {
                showToast('Failed to copy link.');
            }
        } else {
            showToast('Page URL: ' + pageUrl);
        }
    }

    function showToast(message) {
        const toast = document.getElementById('toast-notification');
        const toastMsg = document.getElementById('toast-message');
        if (!toast || !toastMsg) return;
        toastMsg.textContent = message;
        toast.classList.remove('hidden');
        setTimeout(() => {
            toast.classList.add('hidden');
        }, 3000);
    }
</script>
</body>
</html>