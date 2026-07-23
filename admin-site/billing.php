<?php
$page_title = 'Billing & Invoices';
require_once 'includes/auth.php';
requireAdminLogin();
require_once 'config/database.php';
require_once 'includes/invoice-mailer.php';

// Get invoices from database
$stmtInvoices = $pdo->query("
    SELECT
        i.*,
        b.booking_number,
        b.event_name,
        b.event_date,
        b.start_time,
        b.end_time,
        b.pickup_location,
        b.dropoff_location,
        v.name AS vehicle_name,
        v.model AS vehicle_model,
        v.license_plate AS vehicle_plate
    FROM invoices i
    LEFT JOIN bookings b ON i.booking_id = b.id
    LEFT JOIN vehicles v ON b.vehicle_id = v.id
    ORDER BY i.created_at DESC
");
$invoices = $stmtInvoices->fetchAll();

$invoiceItemsMap = [];
if (!empty($invoices)) {
    $invoiceIds = array_column($invoices, 'id');
    $placeholders = implode(',', array_fill(0, count($invoiceIds), '?'));
    $stmtItems = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id IN ($placeholders) ORDER BY id ASC");
    $stmtItems->execute($invoiceIds);

    foreach ($stmtItems->fetchAll() as $item) {
        $invoiceItemsMap[$item['invoice_id']][] = $item;
    }

    foreach ($invoices as &$invoice) {
        $invoice['items'] = $invoiceItemsMap[$invoice['id']] ?? [];
    }
    unset($invoice);
}

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
                        <p class="text-xs uppercase tracking-[0.35em] text-slate-400 mb-4">Vehicle Manager</p>
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
                    <div class="kpi-value">LKR <?php echo number_format($total_revenue, 2); ?></div>
                    <div class="text-xs text-green-600 mt-1">Paid invoices</div>
                </div>
                <div class="card-icon" style="background:var(--card-accent,#0b6b6d)"><span class="material-symbols-outlined">payments</span></div>
            </div>
            <div class="card-3d p-6 bg-white" style="--card-accent: #b36b2a;">
                <div>
                    <p class="text-sm text-gray-500 uppercase">Outstanding</p>
                    <div class="kpi-value">LKR <?php echo number_format($outstanding, 2); ?></div>
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
                        <div class="flex flex-wrap justify-end gap-2">
                            <?php if ($invoice['status'] != 'paid'): ?>
                            <button type="button" onclick="markAsPaid(<?php echo $invoice['id']; ?>)" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-green-50 text-green-700 border border-green-200 hover:bg-green-100 transition font-semibold text-sm shadow-sm" title="Mark as Paid" aria-label="Mark invoice <?php echo htmlspecialchars($invoice['invoice_number']); ?> as paid">
                                <span class="material-symbols-outlined text-xl leading-none">check_circle</span>
                                <span>Mark Paid</span>
                            </button>
                            <?php endif; ?>

                            <button type="button" onclick="downloadPDF('<?php echo $invoice['invoice_number']; ?>')" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 transition font-semibold text-sm shadow-sm" title="Download PDF" aria-label="Download PDF invoice <?php echo htmlspecialchars($invoice['invoice_number']); ?>">
                                <span class="material-symbols-outlined text-xl leading-none">picture_as_pdf</span>
                                <span>Download PDF</span>
                            </button>
                            <button type="button" onclick="viewInvoiceDetails(<?php echo $invoice['id']; ?>)" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 text-slate-700 border border-slate-200 hover:bg-slate-200 transition font-semibold text-sm shadow-sm" title="View Details" aria-label="View invoice <?php echo htmlspecialchars($invoice['invoice_number']); ?> details">
                                <span class="material-symbols-outlined text-xl leading-none">visibility</span>
                                <span>View Details</span>
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

<!-- Invoice Details Modal -->
<div id="invoiceDetailsModal" class="modal">
    <div class="modal-content mx-4 max-w-4xl">
        <div class="p-6 border-b flex items-center justify-between gap-4">
            <div>
                <h3 class="text-xl font-bold">Invoice Details</h3>
                <p id="invoiceDetailsNumber" class="text-sm text-gray-500 mt-1"></p>
            </div>
            <button type="button" onclick="closeInvoiceDetailsModal()" class="text-gray-500 hover:text-gray-700" aria-label="Close invoice details">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div id="invoiceDetailsBody" class="p-6 space-y-6 max-h-[75vh] overflow-y-auto"></div>
        <div class="p-6 border-t border-gray-100 flex flex-col sm:flex-row gap-3">
            <button type="button" id="invoiceDetailsPdfBtn" class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 transition font-semibold text-sm shadow-sm">
                <span class="material-symbols-outlined text-xl leading-none">picture_as_pdf</span>
                <span>Download PDF</span>
            </button>
            <button type="button" onclick="closeInvoiceDetailsModal()" class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-gray-100 text-gray-700 border border-gray-200 hover:bg-gray-200 transition font-semibold text-sm shadow-sm">
                <span class="material-symbols-outlined text-xl leading-none">close</span>
                <span>Close</span>
            </button>
        </div>
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
const invoiceDetailsMap = <?php echo json_encode(array_column($invoices, null, 'id'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;

document.getElementById('searchInvoices')?.addEventListener('keyup', function(e) {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('.invoice-item').forEach(item => {
        const name = item.getAttribute('data-name') || '';
        item.style.display = name.includes(term) ? '' : 'none';
    });
});


function markAsPaid(id) {
    document.getElementById('paidInvoiceId').value = id;
    document.getElementById('paidModal').style.display = 'block';
}

function downloadPDF(invoiceNumber) {
    window.open('../user-site/public/invoice-print.php?number=' + encodeURIComponent(invoiceNumber), '_blank');
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatCurrency(amount) {
    const numericValue = Number(amount || 0);
    return 'LKR ' + numericValue.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDate(dateString) {
    if (!dateString) {
        return '—';
    }

    const date = new Date(dateString + 'T00:00:00');
    if (Number.isNaN(date.getTime())) {
        return escapeHtml(dateString);
    }

    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

function formatTimeRange(startTime, endTime) {
    if (!startTime && !endTime) {
        return '—';
    }

    const cleanStart = startTime ? String(startTime).slice(0, 5) : '—';
    const cleanEnd = endTime ? String(endTime).slice(0, 5) : '—';
    return cleanStart + ' - ' + cleanEnd;
}

function viewInvoiceDetails(invoiceId) {
    const invoice = invoiceDetailsMap[invoiceId];
    if (!invoice) {
        window.alert('Invoice details could not be loaded.');
        return;
    }

    const items = Array.isArray(invoice.items) ? invoice.items : [];
    const itemsHtml = items.length
        ? items.map(item => `
            <tr class="border-t border-gray-100">
                <td class="px-4 py-3 text-sm text-gray-800">${escapeHtml(item.description || '')}</td>
                <td class="px-4 py-3 text-sm text-gray-600 text-center">${escapeHtml(item.quantity || 1)}</td>
                <td class="px-4 py-3 text-sm text-gray-600 text-right">${formatCurrency(item.unit_price)}</td>
                <td class="px-4 py-3 text-sm font-semibold text-gray-900 text-right">${formatCurrency(item.total)}</td>
            </tr>
        `).join('')
        : `
            <tr class="border-t border-gray-100">
                <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">No invoice items were added for this invoice.</td>
            </tr>
        `;

    document.getElementById('invoiceDetailsNumber').textContent = invoice.invoice_number ? '#' + invoice.invoice_number : 'Invoice Details';
    document.getElementById('invoiceDetailsBody').innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-slate-50 rounded-2xl border border-slate-200 p-5">
                <h4 class="text-xs uppercase tracking-wider font-semibold text-gray-500 mb-3">Billing Information</h4>
                <div class="space-y-2 text-sm text-gray-700">
                    <p><span class="font-semibold text-gray-900">Client:</span> ${escapeHtml(invoice.client_name || '—')}</p>
                    <p><span class="font-semibold text-gray-900">Email:</span> ${escapeHtml(invoice.client_email || '—')}</p>
                    <p><span class="font-semibold text-gray-900">Status:</span> ${escapeHtml(invoice.status || '—')}</p>
                    <p><span class="font-semibold text-gray-900">Issue Date:</span> ${formatDate(invoice.issue_date)}</p>
                    <p><span class="font-semibold text-gray-900">Due Date:</span> ${formatDate(invoice.due_date)}</p>
                </div>
            </div>
            <div class="bg-slate-50 rounded-2xl border border-slate-200 p-5">
                <h4 class="text-xs uppercase tracking-wider font-semibold text-gray-500 mb-3">Booking Information</h4>
                <div class="space-y-2 text-sm text-gray-700">
                    <p><span class="font-semibold text-gray-900">Booking Ref:</span> ${escapeHtml(invoice.booking_number || '—')}</p>
                    <p><span class="font-semibold text-gray-900">Event:</span> ${escapeHtml(invoice.event_name || 'Private Rental')}</p>
                    <p><span class="font-semibold text-gray-900">Event Date:</span> ${formatDate(invoice.event_date)}</p>
                    <p><span class="font-semibold text-gray-900">Time:</span> ${escapeHtml(formatTimeRange(invoice.start_time, invoice.end_time))}</p>
                    <p><span class="font-semibold text-gray-900">Vehicle:</span> ${escapeHtml(invoice.vehicle_name || '—')}${invoice.vehicle_model ? ' (' + escapeHtml(invoice.vehicle_model) + ')' : ''}</p>
                    <p><span class="font-semibold text-gray-900">Pickup:</span> ${escapeHtml(invoice.pickup_location || '—')}</p>
                    <p><span class="font-semibold text-gray-900">Drop-off:</span> ${escapeHtml(invoice.dropoff_location || '—')}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h4 class="text-base font-semibold text-gray-900">Invoice Breakdown</h4>
                    <p class="text-sm text-gray-500">Detailed line items and totals for this invoice.</p>
                </div>
                <div class="text-left sm:text-right">
                    <p class="text-xs uppercase tracking-wider text-gray-400 font-semibold">Total Amount</p>
                    <p class="text-lg font-bold text-gray-900">${formatCurrency(invoice.total_amount)}</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-slate-900 text-white text-xs uppercase tracking-wider">
                            <th class="px-4 py-3 text-left">Description</th>
                            <th class="px-4 py-3 text-center">Qty</th>
                            <th class="px-4 py-3 text-right">Unit Price</th>
                            <th class="px-4 py-3 text-right">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>${itemsHtml}</tbody>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-2xl border border-gray-200 bg-slate-50 p-4">
                <p class="text-xs uppercase tracking-wider text-gray-400 font-semibold">Subtotal</p>
                <p class="mt-2 text-lg font-bold text-gray-900">${formatCurrency(invoice.amount)}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-slate-50 p-4">
                <p class="text-xs uppercase tracking-wider text-gray-400 font-semibold">Tax</p>
                <p class="mt-2 text-lg font-bold text-gray-900">${formatCurrency(invoice.tax)}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-slate-50 p-4">
                <p class="text-xs uppercase tracking-wider text-gray-400 font-semibold">Grand Total</p>
                <p class="mt-2 text-lg font-bold text-cyan-700">${formatCurrency(invoice.total_amount)}</p>
            </div>
        </div>

        <div class="bg-slate-50 rounded-2xl border border-slate-200 p-5">
            <h4 class="text-xs uppercase tracking-wider font-semibold text-gray-500 mb-3">Description</h4>
            <p class="text-sm text-gray-700 whitespace-pre-line">${escapeHtml(invoice.description || 'No description available for this invoice.')}</p>
        </div>
    `;

    const pdfButton = document.getElementById('invoiceDetailsPdfBtn');
    pdfButton.onclick = function () {
        downloadPDF(invoice.invoice_number);
    };

    document.getElementById('invoiceDetailsModal').style.display = 'block';
}

function closeInvoiceDetailsModal() {
    document.getElementById('invoiceDetailsModal').style.display = 'none';
}

function openCreateInvoiceModal() {
    document.getElementById('invoiceModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('invoiceModal').style.display = 'none';
}

function closeModals() {
    document.getElementById('invoiceModal').style.display = 'none';
    document.getElementById('paidModal').style.display = 'none';
    document.getElementById('invoiceDetailsModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target.classList && event.target.classList.contains('modal')) {
        closeModals();
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
