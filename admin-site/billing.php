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

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<!-- Main Content Area -->
<main class="ml-64 min-h-screen bg-slate-50">
    <div class="p-8 max-w-7xl mx-auto">
        <section class="rounded-[2rem] overflow-hidden mb-10">
            <div class="relative bg-slate-900 text-white p-8 lg:p-10 overflow-hidden">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.24),_transparent_36%)] opacity-70 pointer-events-none"></div>
                <div class="relative grid grid-cols-1 xl:grid-cols-[1.5fr_1fr] gap-8 items-center">
                    <div class="max-w-2xl">
                        <p class="text-xs uppercase tracking-[0.35em] text-slate-400 mb-4">Fleet Manager</p>
                        <h1 class="text-5xl font-semibold tracking-tight">Billing &amp; Invoices</h1>
                        <p class="mt-4 text-slate-300 text-lg leading-8">Manage client invoices and payments in Sri Lankan Rupees (LKR)</p>
                    </div>
                    <div class="flex flex-wrap justify-end gap-3">
                        <button onclick="openCreateInvoiceModal()" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-cyan-500 text-white text-sm font-semibold hover:bg-cyan-400 transition-all">
                            <span class="material-symbols-outlined text-sm">add</span>
                            Create Invoice
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5 mb-10">
            <div class="relative max-w-3xl mx-auto">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                <input type="text" id="searchInvoices" class="w-full pl-12 pr-4 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-cyan-300 focus:border-cyan-300 text-sm text-slate-700" placeholder="Search invoices...">
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="card-3d p-6 bg-white" style="--card-accent: #0b6b6d;">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Total Revenue</p>
                    <div style="line-height: 1.1;">
                        <div class="text-xs font-medium text-gray-600">LKR</div>
                        <div class="kpi-value"><?php echo number_format($total_revenue, 2); ?></div>
                    </div>
                    <div class="text-xs text-green-600 mt-1">Paid invoices</div>
                </div>
                <div class="card-icon" style="background:var(--card-accent,#0b6b6d)"><span class="material-symbols-outlined">payments</span></div>
            </div>
            <div class="card-3d p-6 bg-white" style="--card-accent: #b36b2a;">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Outstanding</p>
                    <div style="line-height: 1.1;">
                        <div class="text-xs font-medium text-gray-600">LKR</div>
                        <div class="kpi-value"><?php echo number_format($outstanding, 2); ?></div>
                    </div>
                    <div class="text-xs text-[#f59e0b] mt-1">Pending or overdue</div>
                </div>
                <div class="card-icon" style="background:var(--card-accent,#b36b2a)"><span class="material-symbols-outlined">receipt_long</span></div>
            </div>
            <div class="card-3d p-6 bg-white" style="--card-accent: #0b6b6d;">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Recently Paid</p>
                    <div class="kpi-value"><?php echo $recent_paid; ?></div>
                    <div class="text-xs text-green-600 mt-1">Invoices this week</div>
                </div>
                <div class="card-icon" style="background:var(--card-accent,#0b6b6d)"><span class="material-symbols-outlined">check_circle</span></div>
            </div>
        </div>

        <!-- Invoice List -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold">Recent Invoices</h3>
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
                        <span class="px-2 py-1 text-xs rounded-full <?php 
                            echo $invoice['status'] == 'paid' ? 'bg-green-100 text-green-700' : 
                                ($invoice['status'] == 'pending' ? 'bg-yellow-100 text-yellow-700' : 
                                ($invoice['status'] == 'overdue' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700')); ?>">
                            <?php echo ucfirst($invoice['status']); ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xl font-bold text-gray-900">LKR <?php echo number_format($invoice['total_amount'], 2); ?></span>
                        <div class="flex gap-2">
                            <?php if ($invoice['status'] != 'paid'): ?>
                            <button onclick="markAsPaid(<?php echo $invoice['id']; ?>)" class="w-9 h-9 flex items-center justify-center rounded-lg bg-green-50 text-green-600 hover:bg-green-100" title="Mark as Paid">
                                <span class="material-symbols-outlined text-lg">check_circle</span>
                            </button>
                            <?php endif; ?>
                            <button onclick="sendInvoice(<?php echo $invoice['id']; ?>)" class="w-9 h-9 flex items-center justify-center rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200" title="Send Invoice">
                                <span class="material-symbols-outlined text-lg">send</span>
                            </button>
                            <button onclick="downloadPDF('<?php echo $invoice['invoice_number']; ?>')" class="w-9 h-9 flex items-center justify-center rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200" title="Download PDF">
                                <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                            </button>
                            <button onclick="viewInvoiceDetails(<?php echo $invoice['id']; ?>)" class="w-9 h-9 flex items-center justify-center rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200" title="View Details">
                                <span class="material-symbols-outlined text-lg">visibility</span>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Create Invoice Modal -->
<div id="invoiceModal" class="modal">
    <div class="modal-content mx-4">
        <div class="p-6 border-b">
            <h3 class="text-xl font-bold">Create New Invoice</h3>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1">Client Name *</label>
                <input type="text" name="client_name" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Client Email *</label>
                <input type="email" name="client_email" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Amount (LKR) *</label>
                <input type="number" step="0.01" name="amount" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Due Date</label>
                <input type="date" name="due_date" class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 border rounded-lg" placeholder="Service description..."></textarea>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="submit" name="create_invoice" class="flex-1 bg-red-600 text-white py-2 rounded-lg hover:bg-red-700">Create Invoice</button>
                <button type="button" onclick="closeModal()" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-lg hover:bg-gray-300">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Send Modal -->
<div id="sendModal" class="modal">
    <div class="modal-content mx-4">
        <div class="p-6 border-b">
            <h3 class="text-xl font-bold">Send Invoice</h3>
        </div>
        <form method="POST" class="p-6">
            <input type="hidden" name="invoice_id" id="sendInvoiceId">
            <p class="text-gray-600">Send this invoice to the customer?</p>
            <div class="flex gap-3">
                <button type="submit" name="send_invoice" class="flex-1 bg-red-600 text-white py-2 rounded-lg hover:bg-red-700">Send Now</button>
                <button type="button" onclick="closeModals()" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-lg hover:bg-gray-300">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Paid Modal -->
<div id="paidModal" class="modal">
    <div class="modal-content mx-4">
        <div class="p-6 border-b">
            <h3 class="text-xl font-bold">Mark as Paid</h3>
        </div>
        <form method="POST" class="p-6">
            <input type="hidden" name="invoice_id" id="paidInvoiceId">
            <p class="text-gray-600">Mark this invoice as paid?</p>
            <div class="flex gap-3">
                <button type="submit" name="mark_paid" class="flex-1 bg-green-600 text-white py-2 rounded-lg hover:bg-green-700">Mark as Paid</button>
                <button type="button" onclick="closeModals()" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-lg hover:bg-gray-300">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('searchInvoices')?.addEventListener('keyup', function(e) {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('.invoice-item').forEach(item => {
        const name = item.getAttribute('data-name') || '';
        item.style.display = name.includes(term) ? '' : 'none';
    });
});

function sendInvoice(id) {
    document.getElementById('sendInvoiceId').value = id;
    document.getElementById('sendModal').style.display = 'block';
}

function markAsPaid(id) {
    document.getElementById('paidInvoiceId').value = id;
    document.getElementById('paidModal').style.display = 'block';
}

function downloadPDF(invoiceNumber) {
    window.open('../user-site/public/invoice-print.php?number=' + encodeURIComponent(invoiceNumber), '_blank');
}

function viewInvoiceDetails(invoiceId) {
    alert('Viewing details for invoice #' + invoiceId);
}

function openCreateInvoiceModal() {
    document.getElementById('invoiceModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('invoiceModal').style.display = 'none';
}

function closeModals() {
    document.getElementById('invoiceModal').style.display = 'none';
    document.getElementById('sendModal').style.display = 'none';
    document.getElementById('paidModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target.classList && event.target.classList.contains('modal')) {
        closeModals();
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
