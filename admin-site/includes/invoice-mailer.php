<?php

/** Create one invoice for an approved booking, or return the existing invoice. */
function getOrCreateBookingInvoice(PDO $pdo, int $bookingId): array
{
    $existing = $pdo->prepare('SELECT * FROM invoices WHERE booking_id = ? LIMIT 1');
    $existing->execute([$bookingId]);
    $invoice = $existing->fetch();
    if ($invoice) {
        return $invoice;
    }

    $bookingStmt = $pdo->prepare(
        'SELECT b.id, b.booking_number, b.subtotal, b.tax, b.total_amount,
                u.full_name AS client_name, u.email AS client_email, v.name AS vehicle_name
         FROM bookings b
         LEFT JOIN users u ON u.id = b.user_id
         LEFT JOIN vehicles v ON v.id = b.vehicle_id
         WHERE b.id = ? LIMIT 1'
    );
    $bookingStmt->execute([$bookingId]);
    $booking = $bookingStmt->fetch();

    if (!$booking || empty($booking['client_email']) || !filter_var($booking['client_email'], FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('A valid customer email address is required before sending an invoice.');
    }

    $subtotal = (float) $booking['subtotal'];
    $total = (float) $booking['total_amount'];
    if ($subtotal <= 0) {
        $subtotal = $total;
    }
    $tax = (float) $booking['tax'];
    $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad((string) $bookingId, 5, '0', STR_PAD_LEFT);

    $insert = $pdo->prepare(
        "INSERT INTO invoices
            (invoice_number, booking_id, client_name, client_email, amount, tax, total_amount, status, issue_date, due_date, description)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), ?)"
    );
    $insert->execute([
        $invoiceNumber,
        $bookingId,
        $booking['client_name'] ?: 'Customer',
        $booking['client_email'],
        $subtotal,
        $tax,
        $total,
        'Invoice for booking ' . $booking['booking_number']
    ]);

    $invoiceId = (int) $pdo->lastInsertId();
    $item = $pdo->prepare('INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total) VALUES (?, ?, 1, ?, ?)');
    $item->execute([$invoiceId, 'Event Vehicle Rental: ' . ($booking['vehicle_name'] ?: 'Vehicle'), $total, $total]);
    $pdo->prepare('UPDATE bookings SET invoice_generated = 1 WHERE id = ?')->execute([$bookingId]);

    $created = $pdo->prepare('SELECT * FROM invoices WHERE id = ?');
    $created->execute([$invoiceId]);
    return $created->fetch();
}

/** Send an invoice email and return true only when PHP accepts it for delivery. */
function emailBookingInvoice(PDO $pdo, array $invoice): bool
{
    $email = $invoice['client_email'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $adminPosition = strpos($scriptName, '/admin-site');
    $projectPath = $adminPosition === false ? '' : substr($scriptName, 0, $adminPosition);
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $invoiceUrl = $scheme . '://' . $host . $projectPath . '/user-site/public/invoice-print.php?number=' . rawurlencode($invoice['invoice_number']);

    $customer = htmlspecialchars($invoice['client_name'] ?? 'Customer', ENT_QUOTES, 'UTF-8');
    $number = htmlspecialchars($invoice['invoice_number'], ENT_QUOTES, 'UTF-8');
    $amount = number_format((float) $invoice['total_amount'], 2);
    $subject = 'Your FleetElite invoice ' . $invoice['invoice_number'];
    $message = "<html><body>"
        . "<p>Dear {$customer},</p>"
        . "<p>Your booking has been approved. Your invoice <strong>{$number}</strong> for <strong>LKR {$amount}</strong> is ready.</p>"
        . "<p><a href=\"" . htmlspecialchars($invoiceUrl, ENT_QUOTES, 'UTF-8') . "\">View or download your invoice</a></p>"
        . "<p>Thank you for choosing FleetElite.</p>"
        . "</body></html>";
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: FleetElite <noreply@fleetelite.local>'
    ];

    $sent = @mail($email, $subject, $message, implode("\r\n", $headers));
    if ($sent) {
        $pdo->prepare("UPDATE invoices SET status = 'sent' WHERE id = ? AND status = 'pending'")->execute([$invoice['id']]);
    }
    return $sent;
}
