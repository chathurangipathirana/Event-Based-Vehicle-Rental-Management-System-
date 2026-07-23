<?php

require_once __DIR__ . '/mail-sender.php';

function fetchBookingNotificationContext(PDO $pdo, int $bookingId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT
            b.id,
            b.booking_number,
            b.event_name,
            b.event_date,
            b.start_time,
            b.end_time,
            b.pickup_location,
            b.dropoff_location,
            b.special_requests,
            b.admin_notes,
            b.driver_id,
            b.subtotal,
            b.tax,
            b.total_amount,
            u.full_name AS client_name,
            u.email AS client_email,
            u.phone AS client_phone,
            v.name AS vehicle_name,
            v.model AS vehicle_model,
            v.license_plate AS vehicle_plate,
            d.name AS driver_name,
            d.email AS driver_email,
            d.phone AS driver_phone,
            et.name AS event_type_name
         FROM bookings b
         LEFT JOIN users u ON u.id = b.user_id
         LEFT JOIN vehicles v ON v.id = b.vehicle_id
         LEFT JOIN drivers d ON d.id = b.driver_id
         LEFT JOIN event_types et ON b.event_type_id = et.id
         WHERE b.id = ?
         LIMIT 1'
    );
    $stmt->execute([$bookingId]);

    return $stmt->fetch() ?: null;
}

function formatSpecialRequestsForEmail(?string $specialRequests): string
{
    if ($specialRequests === null || trim($specialRequests) === '') {
        return 'No special instructions provided.';
    }

    $decoded = json_decode($specialRequests, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        return trim($specialRequests);
    }

    $lines = [];
    if (array_key_exists('professional_driver', $decoded)) {
        $lines[] = 'Professional driver: ' . ($decoded['professional_driver'] ? 'Yes' : 'No');
    }
    if (array_key_exists('decorations', $decoded)) {
        $lines[] = 'Decorations: ' . ($decoded['decorations'] ? 'Yes' : 'No');
    }
    if (array_key_exists('extra_hours', $decoded)) {
        $lines[] = 'Extra hours: ' . (int) $decoded['extra_hours'];
    }

    return $lines ? implode("\n", $lines) : 'No special instructions provided.';
}

function buildCustomerBookingDetailsHtml(array $booking): string
{
    $bookingNumber = htmlspecialchars($booking['booking_number'] ?? '', ENT_QUOTES, 'UTF-8');
    $eventName = htmlspecialchars($booking['event_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $eventType = htmlspecialchars($booking['event_type_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $vehicleName = htmlspecialchars($booking['vehicle_name'] ?? 'Vehicle', ENT_QUOTES, 'UTF-8');
    $eventDate = htmlspecialchars($booking['event_date'] ?? '', ENT_QUOTES, 'UTF-8');
    $startTime = htmlspecialchars(substr((string) ($booking['start_time'] ?? ''), 0, 5), ENT_QUOTES, 'UTF-8');
    $endTime = htmlspecialchars(substr((string) ($booking['end_time'] ?? ''), 0, 5), ENT_QUOTES, 'UTF-8');
    $pickup = htmlspecialchars($booking['pickup_location'] ?? '', ENT_QUOTES, 'UTF-8');
    $dropoff = htmlspecialchars($booking['dropoff_location'] ?? '', ENT_QUOTES, 'UTF-8');

    $html = "<p><strong>Booking details:</strong></p><ul>"
        . "<li>Booking number: {$bookingNumber}</li>"
        . "<li>Event: {$eventName}</li>";

    if ($eventType !== '') {
        $html .= "<li>Event type: {$eventType}</li>";
    }

    return $html
        . "<li>Date: {$eventDate}</li>"
        . "<li>Time: {$startTime} - {$endTime}</li>"
        . "<li>Vehicle: {$vehicleName}</li>"
        . "<li>Pickup: {$pickup}</li>"
        . "<li>Drop-off: {$dropoff}</li>"
        . "</ul>";
}

function buildDriverAssignmentDetailsHtml(array $booking): string
{
    $bookingNumber = htmlspecialchars($booking['booking_number'] ?? '', ENT_QUOTES, 'UTF-8');
    $eventName = htmlspecialchars($booking['event_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $eventType = htmlspecialchars($booking['event_type_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $eventDate = htmlspecialchars($booking['event_date'] ?? '', ENT_QUOTES, 'UTF-8');
    $startTime = htmlspecialchars(substr((string) ($booking['start_time'] ?? ''), 0, 5), ENT_QUOTES, 'UTF-8');
    $endTime = htmlspecialchars(substr((string) ($booking['end_time'] ?? ''), 0, 5), ENT_QUOTES, 'UTF-8');
    $pickup = htmlspecialchars($booking['pickup_location'] ?? '', ENT_QUOTES, 'UTF-8');
    $dropoff = htmlspecialchars($booking['dropoff_location'] ?? '', ENT_QUOTES, 'UTF-8');
    $vehicleName = htmlspecialchars($booking['vehicle_name'] ?? 'Vehicle', ENT_QUOTES, 'UTF-8');
    $vehicleModel = htmlspecialchars($booking['vehicle_model'] ?? '', ENT_QUOTES, 'UTF-8');
    $vehiclePlate = htmlspecialchars($booking['vehicle_plate'] ?? '', ENT_QUOTES, 'UTF-8');
    $clientName = htmlspecialchars($booking['client_name'] ?? 'Customer', ENT_QUOTES, 'UTF-8');
    $clientPhone = htmlspecialchars($booking['client_phone'] ?? 'Not provided', ENT_QUOTES, 'UTF-8');
    $clientEmail = htmlspecialchars($booking['client_email'] ?? 'Not provided', ENT_QUOTES, 'UTF-8');
    $specialRequests = nl2br(htmlspecialchars(formatSpecialRequestsForEmail($booking['special_requests'] ?? null), ENT_QUOTES, 'UTF-8'));
    $adminNotes = trim((string) ($booking['admin_notes'] ?? ''));

    $html = "<p><strong>Assignment details:</strong></p><ul>"
        . "<li>Booking number: {$bookingNumber}</li>"
        . "<li>Event: {$eventName}</li>";

    if ($eventType !== '') {
        $html .= "<li>Event type: {$eventType}</li>";
    }

    $html .= "<li>Date: {$eventDate}</li>"
        . "<li>Time: {$startTime} - {$endTime}</li>"
        . "<li>Pickup location: {$pickup}</li>"
        . "<li>Drop-off location: {$dropoff}</li>"
        . "<li>Vehicle: {$vehicleName}";

    if ($vehicleModel !== '') {
        $html .= " ({$vehicleModel})";
    }
    if ($vehiclePlate !== '') {
        $html .= " - Plate: {$vehiclePlate}";
    }

    $html .= "</li>"
        . "</ul>"
        . "<p><strong>Customer contact:</strong></p>"
        . "<ul>"
        . "<li>Name: {$clientName}</li>"
        . "<li>Phone: {$clientPhone}</li>"
        . "<li>Email: {$clientEmail}</li>"
        . "</ul>"
        . "<p><strong>Special requests:</strong><br>{$specialRequests}</p>";

    if ($adminNotes !== '') {
        $html .= "<p><strong>Admin notes:</strong><br>"
            . nl2br(htmlspecialchars($adminNotes, ENT_QUOTES, 'UTF-8'))
            . "</p>";
    }

    return $html;
}

/** Create one invoice for an approved booking, or return the existing invoice. */
function getOrCreateBookingInvoice(PDO $pdo, int $bookingId): array
{
    $existing = $pdo->prepare('SELECT * FROM invoices WHERE booking_id = ? LIMIT 1');
    $existing->execute([$bookingId]);
    $invoice = $existing->fetch();
    if ($invoice) {
        return $invoice;
    }

    $booking = fetchBookingNotificationContext($pdo, $bookingId);

    if (!$booking || empty($booking['client_email']) || !filter_var($booking['client_email'], FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('A valid customer email address is required before sending an invoice.');
    }

    $subtotal = (float) ($booking['subtotal'] ?? 0);
    $total = (float) ($booking['total_amount'] ?? 0);
    if ($subtotal <= 0) {
        $subtotal = $total;
    }
    $tax = (float) ($booking['tax'] ?? 0);
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

/** Send an invoice email and return true only when delivery succeeds. */
function emailBookingInvoice(PDO $pdo, array $invoice): bool
{
    $email = $invoice['client_email'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $bookingDetails = null;
    if (!empty($invoice['booking_id'])) {
        $bookingDetails = fetchBookingNotificationContext($pdo, (int) $invoice['booking_id']);
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
    $subject = 'Your Royal Lanka Rides booking has been approved - Invoice ' . $invoice['invoice_number'];
    $bookingInfo = $bookingDetails ? buildCustomerBookingDetailsHtml($bookingDetails) : '';

    $message = "<html><body style=\"font-family: Arial, sans-serif; color: #1e293b; line-height: 1.6;\">"
        . "<p>Dear {$customer},</p>"
        . "<p>Great news! Your vehicle rental booking has been <strong>approved</strong>.</p>"
        . $bookingInfo
        . "<p>Your invoice <strong>{$number}</strong> for <strong>LKR {$amount}</strong> is ready.</p>"
        . "<p><a href=\"" . htmlspecialchars($invoiceUrl, ENT_QUOTES, 'UTF-8') . "\" style=\"display:inline-block;padding:12px 20px;background:#0891b2;color:#ffffff;text-decoration:none;border-radius:8px;\">View or download your invoice</a></p>"
        . "<p>If you have any questions, reply to this email and our team will assist you.</p>"
        . "<p>Thank you for choosing Royal Lanka Rides.</p>"
        . "</body></html>";

    $sent = sendHtmlEmail($email, $subject, $message, $invoice['client_name'] ?? null);
    if ($sent) {
        $pdo->prepare("UPDATE invoices SET status = 'sent' WHERE id = ? AND status = 'pending'")->execute([$invoice['id']]);
    }

    return $sent;
}

function emailAssignedDriver(PDO $pdo, int $bookingId): ?bool
{
    $booking = fetchBookingNotificationContext($pdo, $bookingId);
    if (!$booking || empty($booking['driver_id'])) {
        return null;
    }

    $driverEmail = $booking['driver_email'] ?? '';
    if (!filter_var($driverEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $driverName = htmlspecialchars($booking['driver_name'] ?? 'Driver', ENT_QUOTES, 'UTF-8');
    $subject = 'New Royal Lanka Rides driver assignment - Booking ' . ($booking['booking_number'] ?? $bookingId);
    $details = buildDriverAssignmentDetailsHtml($booking);

    $message = "<html><body style=\"font-family: Arial, sans-serif; color: #1e293b; line-height: 1.6;\">"
        . "<p>Dear {$driverName},</p>"
        . "<p>You have been assigned to an upcoming Royal Lanka Rides booking.</p>"
        . $details
        . "<p>Please review the assignment details and contact the operations team if you need clarification.</p>"
        . "<p>Thank you.</p>"
        . "</body></html>";

    return sendHtmlEmail($driverEmail, $subject, $message, $booking['driver_name'] ?? null);
}

function sendBookingApprovalNotifications(PDO $pdo, int $bookingId, array $invoice): array
{
    return [
        'customer' => emailBookingInvoice($pdo, $invoice),
        'driver' => emailAssignedDriver($pdo, $bookingId),
    ];
}

function formatApprovalNotificationMessage(array $results): string
{
    $customerSent = !empty($results['customer']);
    $driverResult = $results['driver'] ?? null;

    if ($customerSent) {
        if ($driverResult === true) {
            return 'Booking approved, invoice emailed, and driver notified successfully!';
        }
        if ($driverResult === false) {
            return 'Booking approved and invoice emailed. Driver email delivery needs a valid address or mail server configuration.';
        }

        return 'Booking approved and invoice emailed successfully!';
    }

    if ($driverResult === true) {
        return 'Booking approved. Invoice was created, but customer email delivery failed. The assigned driver was notified by email.';
    }
    if ($driverResult === false) {
        return 'Booking approved. Invoice was created, but customer and driver email delivery failed.';
    }

    return 'Booking approved and invoice created. Email delivery needs mail server configuration.';
}
