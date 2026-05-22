<?php
$host = 'localhost';
$dbname = 'fleetelite_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

session_start();

// Helper functions
function getVehicleById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getEventTypeById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM event_types WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getUserBookings($user_id, $limit = null) {
    global $pdo;
    $sql = "SELECT b.*, v.name as vehicle_name, v.image_url, et.name as event_type_name 
            FROM bookings b 
            JOIN vehicles v ON b.vehicle_id = v.id 
            JOIN event_types et ON b.event_type_id = et.id 
            WHERE b.user_id = ? 
            ORDER BY b.created_at DESC";
    if ($limit) {
        $sql .= " LIMIT $limit";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}
?>