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

/** Send the assigned driver the booking details by email. */
function emailDriverAssignment(PDO $pdo, int $bookingId): bool
{
    $assignment = $pdo->prepare(
        'SELECT b.booking_number, b.event_name, b.event_date, b.start_time, b.end_time,
                b.pickup_location, b.dropoff_location,
                d.name AS driver_name, d.email AS driver_email,
                v.name AS vehicle_name, v.model AS vehicle_model, v.license_plate
         FROM bookings b
         INNER JOIN drivers d ON d.id = b.driver_id
         LEFT JOIN vehicles v ON v.id = b.vehicle_id
         WHERE b.id = ? LIMIT 1'
    );
    $assignment->execute([$bookingId]);
    $booking = $assignment->fetch();

    if (!$booking || !filter_var($booking['driver_email'], FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $driver = htmlspecialchars($booking['driver_name'], ENT_QUOTES, 'UTF-8');
    $reference = htmlspecialchars($booking['booking_number'], ENT_QUOTES, 'UTF-8');
    $event = htmlspecialchars($booking['event_name'] ?: 'Event rental', ENT_QUOTES, 'UTF-8');
    $date = $booking['event_date'] ? date('F j, Y', strtotime($booking['event_date'])) : 'To be confirmed';
    $startTime = $booking['start_time'] ? date('g:i A', strtotime($booking['start_time'])) : 'To be confirmed';
    $endTime = $booking['end_time'] ? date('g:i A', strtotime($booking['end_time'])) : 'To be confirmed';
    $pickup = htmlspecialchars($booking['pickup_location'] ?: 'To be confirmed', ENT_QUOTES, 'UTF-8');
    $dropoff = htmlspecialchars($booking['dropoff_location'] ?: 'To be confirmed', ENT_QUOTES, 'UTF-8');
    $vehicleParts = array_filter([$booking['vehicle_name'], $booking['vehicle_model'], $booking['license_plate']]);
    $vehicle = htmlspecialchars($vehicleParts ? implode(' — ', $vehicleParts) : 'To be confirmed', ENT_QUOTES, 'UTF-8');

    $subject = 'New driver assignment: ' . $booking['booking_number'];
    $message = "<html><body>"
        . "<p>Dear {$driver},</p>"
        . "<p>You have been assigned to booking <strong>{$reference}</strong>.</p>"
        . "<p><strong>Event:</strong> {$event}<br>"
        . "<strong>Date:</strong> {$date}<br>"
        . "<strong>Time:</strong> {$startTime} - {$endTime}<br>"
        . "<strong>Vehicle:</strong> {$vehicle}<br>"
        . "<strong>Pickup:</strong> {$pickup}<br>"
        . "<strong>Drop-off:</strong> {$dropoff}</p>"
        . "<p>Please review the assignment and contact the operations team if you need assistance.</p>"
        . "</body></html>";
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: FleetElite Operations <noreply@fleetelite.local>'
    ];

    return @mail($booking['driver_email'], $subject, $message, implode("\r\n", $headers));
}

/** Send the assigned driver a text message through Twilio. */
function smsDriverAssignment(PDO $pdo, int $bookingId): bool
{
    $config = require __DIR__ . '/../config/notifications.php';
    $accountSid = $config['twilio_account_sid'] ?? '';
    $authToken = $config['twilio_auth_token'] ?? '';
    $fromNumber = $config['twilio_from_number'] ?? '';

    if (!$accountSid || !$authToken || !$fromNumber || !function_exists('curl_init')) {
        return false;
    }

    $assignment = $pdo->prepare(
        'SELECT b.booking_number, b.event_name, b.event_date, b.start_time, b.pickup_location,
                d.name AS driver_name, d.phone AS driver_phone, v.name AS vehicle_name, v.license_plate
         FROM bookings b
         INNER JOIN drivers d ON d.id = b.driver_id
         LEFT JOIN vehicles v ON v.id = b.vehicle_id
         WHERE b.id = ? LIMIT 1'
    );
    $assignment->execute([$bookingId]);
    $booking = $assignment->fetch();

    if (!$booking || empty($booking['driver_phone'])) {
        return false;
    }

    $phone = preg_replace('/[^\d+]/', '', $booking['driver_phone']);
    if (str_starts_with($phone, '00')) {
        $phone = '+' . substr($phone, 2);
    } elseif (str_starts_with($phone, '0')) {
        $phone = '+94' . substr($phone, 1);
    } elseif (!str_starts_with($phone, '+')) {
        $phone = '+' . $phone;
    }

    if (!preg_match('/^\+\d{8,15}$/', $phone)) {
        return false;
    }

    $date = $booking['event_date'] ? date('M j, Y', strtotime($booking['event_date'])) : 'TBC';
    $time = $booking['start_time'] ? date('g:i A', strtotime($booking['start_time'])) : 'TBC';
    $vehicle = trim(($booking['vehicle_name'] ?? 'Vehicle') . (!empty($booking['license_plate']) ? ' (' . $booking['license_plate'] . ')' : ''));
    $body = "STS: You are assigned to booking {$booking['booking_number']}. Event: "
        . ($booking['event_name'] ?: 'Event rental') . ". Date: {$date}, {$time}. Vehicle: {$vehicle}. Pickup: "
        . ($booking['pickup_location'] ?: 'TBC') . '.';

    $curl = curl_init('https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode($accountSid) . '/Messages.json');
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['To' => $phone, 'From' => $fromNumber, 'Body' => $body]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => $accountSid . ':' . $authToken,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_TIMEOUT => 15,
    ]);
    curl_exec($curl);
    $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    return $statusCode >= 200 && $statusCode < 300;
}
