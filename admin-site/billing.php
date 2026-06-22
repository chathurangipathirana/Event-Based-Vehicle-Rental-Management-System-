<?php
$page_title = 'Billing & Invoices';
require_once 'includes/auth.php';
requireAdminLogin();
require_once 'config/database.php';

// Get invoices from database
$invoices = $pdo->query("SELECT * FROM invoices ORDER BY created_at DESC")->fetchAll();

// Calculate totals
$total_revenue = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE status = 'paid'")->fetchColumn();
$outstanding = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE status IN ('pending', 'overdue')")->fetchColumn();
$recent_paid = $pdo->query("SELECT COUNT(*) FROM invoices WHERE status = 'paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_invoice'])) {
        $invoice_number = 'INV-' . date('Y') . '-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        $amount = floatval($_POST['amount']);
        $tax = $amount * 0.10;
        $total_amount = $amount + $tax;
        
        $stmt = $pdo->prepare("INSERT INTO invoices (invoice_number, client_name, client_email, amount, tax, total_amount, status, issue_date, due_date, description) VALUES (?, ?, ?, ?, ?, ?, 'pending', CURDATE(), ?, ?)");
        $stmt->execute([
            $invoice_number,
            $_POST['client_name'],
            $_POST['client_email'],
            $amount,
            $tax,
            $total_amount,
            $_POST['due_date'],
            $_POST['description']
        ]);
        
        $_SESSION['message'] = 'Invoice created successfully!';
        header('Location: billing.php');
        exit();
    }
    
    if (isset($_POST['send_invoice'])) {
        $stmt = $pdo->prepare("UPDATE invoices SET status = 'sent' WHERE id = ?");
        $stmt->execute([$_POST['invoice_id']]);
        $_SESSION['message'] = 'Invoice sent to customer!';
        header('Location: billing.php');
        exit();
    }
    
    if (isset($_POST['mark_paid'])) {
        $stmt = $pdo->prepare("UPDATE invoices SET status = 'paid' WHERE id = ?");
        $stmt->execute([$_POST['invoice_id']]);
        $_SESSION['message'] = 'Invoice marked as paid!';
        header('Location: billing.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>FleetElite | Billing &amp; Invoices</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<style>
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    :root { --surface:#f9f9fa; --surface-low:#f3f4f4; --surface-card:#ffffff; --surface-high:#e7e8e9; --primary:#02414a; --primary-soft:#b8ebf7; --primary-hover:#0d5260; --outline:#c0c8ca; --text:#191c1d; --muted:#40484a; --success:#176a3a; --warning:#8a5200; --danger:#ba1a1a; }
    body { font-family: 'Inter', sans-serif; background-color: var(--surface); color: var(--text); }
    header { border-color: var(--outline) !important; }
    .bg-white { background-color: var(--surface-card) !important; }
    .bg-gray-50, .hover\:bg-gray-50:hover { background-color: var(--surface-low) !important; }
    .bg-gray-100, .bg-gray-200 { background-color: var(--surface-high) !important; }
    .border-gray-100, .border-gray-200, .border-gray-300 { border-color: var(--outline) !important; }
    .text-gray-900, .text-gray-700 { color: var(--text) !important; }
    .text-gray-600, .text-gray-500, .text-gray-400 { color: var(--muted) !important; }
    .bg-red-600 { background-color: var(--primary) !important; }
    .bg-red-100, .bg-red-50 { background-color: var(--primary-soft) !important; }
    .hover\:bg-red-700:hover { background-color: var(--primary-hover) !important; }
    .text-red-600 { color: var(--primary) !important; }
    [class*="bg-red-"] { background-color: var(--primary) !important; }
    [class*="text-red-"] { color: var(--primary) !important; }
    [class*="border-red-"] { border-color: var(--primary-soft) !important; }
    [class*="hover:bg-red-"]:hover { background-color: var(--primary-hover) !important; }
    [class*="hover:text-red-"]:hover { color: var(--primary-hover) !important; }
    .bg-red-50, .bg-red-100 { background-color: var(--primary-soft) !important; }
    .focus\:ring-red-500:focus { --tw-ring-color: rgba(2,65,74,0.18) !important; border-color: var(--primary) !important; }
    .text-green-600, .text-green-700 { color: var(--success) !important; }
    .bg-green-100 { background-color: #dff5e8 !important; }
    .text-yellow-700, .text-orange-600 { color: var(--warning) !important; }
    .bg-yellow-100 { background-color: #fff3d6 !important; }
    .rounded-xl { border-radius: 0.75rem !important; }
    .rounded-lg { border-radius: 0.5rem !important; }
    .rounded-full { border-radius: 9999px !important; }
    .shadow-lg, .shadow-md, .shadow-sm { box-shadow: 0 10px 24px rgba(25,28,29,0.06) !important; }
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(2,65,74,0.45); z-index: 1000; overflow-y: auto; }
    .modal-content { background: var(--surface-card); margin: 50px auto; max-width: 500px; border-radius: 12px; border: 1px solid var(--outline); box-shadow: 0 24px 60px rgba(25,28,29,0.18); }
</style>
</head>
<body class="bg-gray-50">

<?php if (isset($_SESSION['message'])): ?>
    <div class="fixed top-20 left-4 right-4 z-50 p-4 bg-green-100 text-green-700 rounded-lg shadow-lg text-center" id="message">
        <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
    </div>
    <script>setTimeout(() => document.getElementById('message')?.remove(), 3000);</script>
<?php endif; ?>

<!-- TopAppBar -->
<header class="fixed top-0 w-full z-50 bg-white border-b border-gray-100 shadow-sm flex justify-between items-center h-16 px-4">
<div class="flex items-center gap-3">
<span class="material-symbols-outlined text-gray-900 cursor-pointer" onclick="history.back()">arrow_back</span>
<h1 class="text-xl font-black tracking-tight text-red-600">FleetElite</h1>
</div>
<div class="flex items-center gap-3">
<a href="logout.php" class="material-symbols-outlined text-gray-500 hover:text-red-600">logout</a>
<div class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-bold">
    <?php echo strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 2)); ?>
</div>
</div>
</header>

<main class="pt-20 pb-24 px-4 max-w-7xl mx-auto min-h-screen">
<section class="mb-6">
<h2 class="text-3xl font-bold text-gray-900 mb-4">Billing &amp; Invoices</h2>
<div class="flex gap-2">
<div class="relative flex-1">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
<input type="text" id="searchInvoices" class="w-full pl-10 pr-4 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500" placeholder="Search invoices..."/>
</div>
</div>
</section>

<!-- Summary Cards -->
<section class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
<div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
<span class="text-xs font-bold text-gray-400 uppercase">Total Revenue</span>
<div class="mt-2">
<span class="text-2xl font-bold text-gray-900">LKR <?php echo number_format($total_revenue, 2); ?></span>
</div>
</div>
<div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
<span class="text-xs font-bold text-gray-400 uppercase">Outstanding</span>
<div class="mt-2">
<span class="text-2xl font-bold text-gray-900">LKR <?php echo number_format($outstanding, 2); ?></span>
</div>
</div>
<div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm">
<span class="text-xs font-bold text-gray-400 uppercase">Recently Paid</span>
<div class="mt-2">
<span class="text-2xl font-bold text-gray-900"><?php echo $recent_paid; ?> Invoices</span>
</div>
</div>
</section>

<!-- Invoice List -->
<section class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
<div class="p-4 border-b border-gray-100">
<h3 class="font-bold text-gray-900">Recent Invoices</h3>
</div>
<div class="divide-y divide-gray-100" id="invoiceList">
<?php if (empty($invoices)): ?>
<div class="p-8 text-center text-gray-500">No invoices found. Create your first invoice!</div>
<?php else: ?>
<?php foreach ($invoices as $invoice): ?>
<div class="p-4 flex flex-col gap-3 hover:bg-gray-50 transition invoice-item" data-name="<?php echo strtolower($invoice['client_name']); ?>">
<div class="flex justify-between items-start">
<div>
<p class="font-bold text-gray-900">#<?php echo $invoice['invoice_number']; ?> - <?php echo $invoice['client_name']; ?></p>
<p class="text-xs text-gray-500"><?php echo date('M d, Y', strtotime($invoice['issue_date'])); ?></p>
</div>
<span class="text-[10px] px-2 py-0.5 rounded font-bold uppercase <?php 
    echo $invoice['status'] == 'paid' ? 'bg-green-100 text-green-700' : 
        ($invoice['status'] == 'pending' ? 'bg-orange-100 text-orange-700' : 'bg-red-100 text-red-700'); ?>">
    <?php echo ucfirst($invoice['status']); ?>
</span>
</div>
<div class="flex justify-between items-center">
<span class="text-xl font-bold text-gray-900">LKR <?php echo number_format($invoice['total_amount'], 2); ?></span>
<div class="flex gap-2">
<?php if ($invoice['status'] != 'paid'): ?>
<button onclick="markAsPaid(<?php echo $invoice['id']; ?>)" class="w-9 h-9 bg-green-50 text-green-600 rounded-lg hover:bg-green-100">✓</button>
<?php endif; ?>
<button onclick="sendInvoice(<?php echo $invoice['id']; ?>)" class="w-9 h-9 bg-gray-100 rounded-lg hover:bg-gray-200">📧</button>
</div>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</section>
</main>

<!-- FAB Button -->
<button onclick="openCreateInvoiceModal()" class="fixed bottom-6 right-6 w-14 h-14 bg-red-600 text-white rounded-full shadow-2xl flex items-center justify-center hover:scale-105 z-40">
<span class="material-symbols-outlined text-3xl">add</span>
</button>

<!-- Create Invoice Modal -->
<div id="invoiceModal" class="modal">
    <div class="modal-content mx-4">
        <div class="p-6 border-b"><h3 class="text-xl font-bold">Create New Invoice</h3></div>
        <form method="POST" class="p-6 space-y-4">
            <input type="text" name="client_name" placeholder="Client Name" required class="w-full px-3 py-2 border rounded-lg">
            <input type="email" name="client_email" placeholder="Client Email" required class="w-full px-3 py-2 border rounded-lg">
            <input type="number" step="0.01" name="amount" placeholder="Amount (LKR)" required class="w-full px-3 py-2 border rounded-lg">
            <input type="date" name="due_date" class="w-full px-3 py-2 border rounded-lg">
            <textarea name="description" rows="3" placeholder="Description" class="w-full px-3 py-2 border rounded-lg"></textarea>
            <div class="flex gap-3">
                <button type="submit" name="create_invoice" class="flex-1 bg-red-600 text-white py-2 rounded-lg">Create</button>
                <button type="button" onclick="closeModal()" class="flex-1 bg-gray-200 py-2 rounded-lg">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Send Modal -->
<div id="sendModal" class="modal">
    <div class="modal-content mx-4">
        <div class="p-6 border-b"><h3 class="text-xl font-bold">Send Invoice</h3></div>
        <form method="POST" class="p-6">
            <input type="hidden" name="invoice_id" id="sendInvoiceId">
            <p class="mb-4">Send this invoice to the customer?</p>
            <div class="flex gap-3">
                <button type="submit" name="send_invoice" class="flex-1 bg-red-600 text-white py-2 rounded-lg">Send Now</button>
                <button type="button" onclick="closeModals()" class="flex-1 bg-gray-200 py-2 rounded-lg">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Paid Modal -->
<div id="paidModal" class="modal">
    <div class="modal-content mx-4">
        <div class="p-6 border-b"><h3 class="text-xl font-bold">Mark as Paid</h3></div>
        <form method="POST" class="p-6">
            <input type="hidden" name="invoice_id" id="paidInvoiceId">
            <p class="mb-4">Mark this invoice as paid?</p>
            <div class="flex gap-3">
                <button type="submit" name="mark_paid" class="flex-1 bg-green-600 text-white py-2 rounded-lg">Mark as Paid</button>
                <button type="button" onclick="closeModals()" class="flex-1 bg-gray-200 py-2 rounded-lg">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('searchInvoices')?.addEventListener('keyup', function(e) {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('.invoice-item').forEach(item => {
        item.style.display = item.getAttribute('data-name').includes(term) ? '' : 'none';
    });
});

function sendInvoice(id) { document.getElementById('sendInvoiceId').value = id; document.getElementById('sendModal').style.display = 'block'; }
function markAsPaid(id) { document.getElementById('paidInvoiceId').value = id; document.getElementById('paidModal').style.display = 'block'; }
function openCreateInvoiceModal() { document.getElementById('invoiceModal').style.display = 'block'; }
function closeModal() { document.getElementById('invoiceModal').style.display = 'none'; }
function closeModals() { document.getElementById('sendModal').style.display = 'none'; document.getElementById('paidModal').style.display = 'none'; document.getElementById('invoiceModal').style.display = 'none'; }
window.onclick = function(event) { if (event.target.classList?.contains('modal')) closeModals(); }
</script>
</body>
</html>
