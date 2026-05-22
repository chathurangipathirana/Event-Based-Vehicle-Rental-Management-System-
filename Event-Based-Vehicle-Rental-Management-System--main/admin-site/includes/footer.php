<!-- Footer -->
<footer class="ml-64 border-t border-gray-200 bg-white mt-8">
    <div class="max-w-7xl mx-auto py-6 px-8">
        <p class="text-center text-sm text-gray-500">© 2024 FleetElite Logistics. Operational Excellence Guaranteed.</p>
    </div>
</footer>

<script>
    function closeModal() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => modal.style.display = 'none');
    }
    
    window.onclick = function(event) {
        if (event.target.classList && event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
    
    function showMessage(message, type) {
        const msgDiv = document.createElement('div');
        msgDiv.className = type === 'success' ? 'alert-success' : 'alert-error';
        msgDiv.innerHTML = message;
        msgDiv.style.position = 'fixed';
        msgDiv.style.top = '20px';
        msgDiv.style.right = '20px';
        msgDiv.style.zIndex = '9999';
        document.body.appendChild(msgDiv);
        setTimeout(() => msgDiv.remove(), 3000);
    }
</script>
</body>
</html>