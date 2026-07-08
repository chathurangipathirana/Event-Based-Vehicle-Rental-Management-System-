<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
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
    $new_status = 'rejected';
    if (empty($notes)) {
        echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

try {
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
        echo json_encode(['success' => true, 'message' => 'Booking ' . $new_status]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Booking not found or already processed']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}