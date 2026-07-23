<?php
require_once '../config/database.php';

$invoice_number = $_GET['number'] ?? '';
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if (empty($invoice_number) && $booking_id <= 0) {
    die("Invoice identifier is required.");
}

// Fetch invoice and associated booking info
try {
    $sql = "
        SELECT
            i.*,
            b.booking_number,
            b.event_name,
            b.event_date,
            b.start_time,
            b.end_time,
            b.pickup_location,
            b.dropoff_location,
            b.user_id as booking_user_id,
            v.name as vehicle_name,
            v.model as vehicle_model,
            v.license_plate as vehicle_plate
        FROM invoices i
        LEFT JOIN bookings b ON i.booking_id = b.id
        LEFT JOIN vehicles v ON b.vehicle_id = v.id
        WHERE ";

    if (!empty($invoice_number)) {
        $sql .= "i.invoice_number = ?";
        $param = $invoice_number;
    } else {
        $sql .= "i.booking_id = ?";
        $param = $booking_id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$param]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

if (!$invoice) {
    die("Invoice not found.");
}

// Security Check: Ensure either admin is logged in, OR the client is the owner of this booking
$is_authorized = false;

// Check Admin site session
if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin') {
    $is_authorized = true;
}

// Check User site admin role
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    $is_authorized = true;
}

// Check Customer site owner match
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $invoice['booking_user_id']) {
    $is_authorized = true;
}

if (!$is_authorized) {
    die("Access Denied. You are not authorized to view this invoice.");
}

// Fetch invoice items
$items = [];
try {
    $stmtItems = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
    $stmtItems->execute([$invoice['id']]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // ignore
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?php echo htmlspecialchars($invoice['invoice_number']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white; color: black; }
            .print-border { border: 1px solid #e2e8f0 !important; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen p-4 md:p-8">
    <!-- Action Header -->
    <div class="max-w-4xl mx-auto mb-6 flex justify-between items-center no-print bg-white p-4 rounded-xl shadow-sm border border-gray-200">
        <span class="text-sm font-medium text-gray-500">Invoice PDF Download</span>
        <div class="flex gap-3">
            <button onclick="downloadInvoicePDF()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg text-sm transition flex items-center gap-2">
                Download PDF
            </button>
            <button onclick="window.print()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold px-4 py-2 rounded-lg text-sm transition">
                Print
            </button>
        </div>
    </div>

    <!-- Invoice Wrapper -->
    <div id="invoice-card" class="max-w-4xl mx-auto bg-white p-8 md:p-12 rounded-3xl shadow-sm border border-gray-200 print-border">
        <!-- Logo and Invoice Meta -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b pb-8 mb-8 gap-6">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-cyan-600">Royal Lanka Rides</h1>
                <p class="text-sm text-gray-500 mt-1">Premium Vehicle Logistics (Pvt) Ltd</p>
                <p class="text-xs text-gray-400">No. 123, Galle Road, Colombo 03, Sri Lanka</p>
            </div>
            <div class="text-left md:text-right">
                <h2 class="text-xl font-bold text-gray-900">INVOICE</h2>
                <p class="text-sm font-semibold text-cyan-600 mt-1"><?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
                <p class="text-xs text-gray-400 mt-1">Issue Date: <?php echo date('Y-m-d', strtotime($invoice['issue_date'])); ?></p>
                <p class="text-xs text-gray-400">Due Date: <?php echo date('Y-m-d', strtotime($invoice['due_date'])); ?></p>
            </div>
        </div>

        <!-- Billing Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 border-b pb-8 mb-8">
            <div>
                <h3 class="text-xs uppercase tracking-wider text-gray-400 font-bold mb-3">BILLED TO:</h3>
                <p class="font-bold text-gray-900 text-base"><?php echo htmlspecialchars($invoice['client_name']); ?></p>
                <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($invoice['client_email']); ?></p>
            </div>
            <div>
                <h3 class="text-xs uppercase tracking-wider text-gray-400 font-bold mb-3">BOOKING & LOGISTICS:</h3>
                <p class="text-sm text-gray-700"><span class="font-semibold">Booking Ref:</span> <?php echo htmlspecialchars($invoice['booking_number']); ?></p>
                <p class="text-sm text-gray-700 mt-1"><span class="font-semibold">Event Name:</span> <?php echo htmlspecialchars($invoice['event_name'] ?: 'Private Rental'); ?></p>
                <p class="text-sm text-gray-700"><span class="font-semibold">Event Date:</span> <?php echo date('F d, Y', strtotime($invoice['event_date'])); ?></p>
            </div>
        </div>

        <!-- Items Table -->
        <table class="w-full mb-8">
            <thead>
                <tr class="border-b text-left text-xs uppercase tracking-wider text-gray-400 font-bold">
                    <th class="pb-3">Description</th>
                    <th class="pb-3 text-center">Qty</th>
                    <th class="pb-3 text-right">Unit Price (LKR)</th>
                    <th class="pb-3 text-right">Total (LKR)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="py-4">
                            <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($item['description']); ?></span>
                        </td>
                        <td class="py-4 text-center"><?php echo $item['quantity']; ?></td>
                        <td class="py-4 text-right">LKR <?php echo number_format($item['unit_price'], 2); ?></td>
                        <td class="py-4 text-right font-semibold text-gray-900">LKR <?php echo number_format($item['total'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                    <tr>
                        <td class="py-4">
                            <span class="font-semibold text-gray-900">Event Vehicle Rental Services</span>
                        </td>
                        <td class="py-4 text-center">1</td>
                        <td class="py-4 text-right">LKR <?php echo number_format($invoice['amount'], 2); ?></td>
                        <td class="py-4 text-right font-semibold text-gray-900">LKR <?php echo number_format($invoice['amount'], 2); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Totals Block -->
        <div class="flex justify-end pt-4">
            <div class="w-full md:w-80 text-sm space-y-3">
                <div class="flex justify-between text-gray-500">
                    <span>Subtotal</span>
                    <span class="font-medium text-gray-900">LKR <?php echo number_format($invoice['amount'], 2); ?></span>
                </div>
                <div class="flex justify-between text-gray-500">
                    <span>Tax (10.00%)</span>
                    <span class="font-medium text-gray-900">LKR <?php echo number_format($invoice['tax'], 2); ?></span>
                </div>
                <div class="flex justify-between text-base font-bold text-gray-900 border-t pt-3">
                    <span>Total Amount</span>
                    <span class="text-lg font-extrabold text-cyan-600">LKR <?php echo number_format($invoice['total_amount'], 2); ?></span>
                </div>
            </div>
        </div>

        <!-- Note / Payment status -->
        <div class="mt-12 border-t pt-8 text-center text-xs text-gray-400">
            <p>Thank you for choosing Royal Lanka Rides. For inquiries, email info@royallankarides.com</p>
            <p class="mt-1 font-semibold text-cyan-600 uppercase tracking-widest">Status: <?php echo htmlspecialchars($invoice['status']); ?></p>
        </div>
    </div>

    <!-- Invoice Download Script -->
    <script>
        function downloadInvoicePDF() {
            const element = document.getElementById('invoice-card');
            const opt = {
                margin:       [0.4, 0.4, 0.4, 0.4],
                filename:     '<?php echo htmlspecialchars($invoice['invoice_number']); ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, logging: false, useCORS: true },
                jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().from(element).set(opt).save();
        }
    </script>
</body>
</html>
