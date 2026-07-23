<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/invoice-mailer.php';
requireAdminLogin();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$booking_id = isset($data['booking_id']) ? (int)$data['booking_id'] : 0;
$action = isset($data['action']) ? $data['action'] : ''; // 'approve' or 'reject'
$notes = isset($data['notes']) ? trim($data['notes']) : null;

if ($booking_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
    exit;
}

if ($action === 'approve') {
    $new_status = 'confirmed';
} elseif ($action === 'reject') {
    $new_status = 'cancelled';
    if (empty($notes)) {
        echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
        exit;
    }
} elseif ($action === 'dispatch') {
    $new_status = 'confirmed';
    $vehicle_id = isset($data['vehicle_id']) ? (int)$data['vehicle_id'] : null;
    $driver_id = isset($data['driver_id']) ? (int)$data['driver_id'] : null;

    if (!$vehicle_id || !$driver_id) {
        echo json_encode(['success' => false, 'message' => 'Vehicle and Driver must be selected for dispatch.']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

try {
    $pdo->beginTransaction();

    if ($action === 'dispatch') {
        $stmt = $pdo->prepare("
            UPDATE bookings
            SET status = :status, admin_notes = :notes, vehicle_id = :v_id, driver_id = :d_id
            WHERE id = :id AND status = 'pending'
        ");
        $stmt->execute([
            'status' => $new_status,
            'notes' => $notes,
            'v_id' => $vehicle_id,
            'd_id' => $driver_id,
            'id' => $booking_id
        ]);
        
        if ($stmt->rowCount() > 0) {
            // Update vehicle to booked
            $pdo->prepare("UPDATE vehicles SET status = 'booked' WHERE id = ?")->execute([$vehicle_id]);
            // Update driver to on_duty
            $pdo->prepare("UPDATE drivers SET status = 'on_duty' WHERE id = ?")->execute([$driver_id]);
            
            $invoice = getOrCreateBookingInvoice($pdo, $booking_id);
            $pdo->commit();
            $emailSent = emailBookingInvoice($pdo, $invoice);
            echo json_encode(['success' => true, 'message' => $emailSent ? 'Booking confirmed, dispatched, and invoice emailed!' : 'Booking confirmed and dispatched. The invoice was created, but email delivery needs mail server configuration.']);
        } else {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Booking not found or already processed']);
        }
    } else {
        $stmt = $pdo->prepare("
            UPDATE bookings
            SET status = :status, admin_notes = :notes
            WHERE id = :id AND status = 'pending'
        ");
        $stmt->execute([
            'status' => $new_status,
            'notes' => $notes,
            'id' => $booking_id
        ]);

        if ($stmt->rowCount() > 0) {
            $invoice = null;
            if ($action === 'approve') {
                $invoice = getOrCreateBookingInvoice($pdo, $booking_id);
            }
            $pdo->commit();
            $emailResults = $invoice ? sendBookingApprovalNotifications($pdo, $booking_id, $invoice) : ['customer' => false, 'driver' => null];
            $message = $action === 'approve'
                ? formatApprovalNotificationMessage($emailResults)
                : 'Booking ' . $new_status;
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Booking not found or already processed']);
        }
    }
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
