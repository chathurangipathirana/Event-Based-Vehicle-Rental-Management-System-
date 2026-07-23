<?php
require_once 'config/database.php';

try {
    $stmt = $pdo->query("SELECT id, booking_number, user_id, vehicle_id, event_type_id, status FROM bookings");
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Bookings in Database:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Booking Number</th><th>User ID</th><th>Vehicle ID</th><th>Event Type ID</th><th>Status</th></tr>";
    foreach ($bookings as $b) {
        echo "<tr>";
        echo "<td>{$b['id']}</td>";
        echo "<td>{$b['booking_number']}</td>";
        echo "<td>{$b['user_id']}</td>";
        echo "<td>" . ($b['vehicle_id'] === null ? 'NULL' : $b['vehicle_id']) . "</td>";
        echo "<td>" . ($b['event_type_id'] === null ? 'NULL' : $b['event_type_id']) . "</td>";
        echo "<td>{$b['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    $stmtVeh = $pdo->query("SELECT id, name, category, status FROM vehicles");
    $vehicles = $stmtVeh->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Vehicles in Database:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Category</th><th>Status</th></tr>";
    foreach ($vehicles as $v) {
        echo "<tr>";
        echo "<td>{$v['id']}</td>";
        echo "<td>{$v['name']}</td>";
        echo "<td>{$v['category']}</td>";
        echo "<td>{$v['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    $stmtEt = $pdo->query("SELECT id, name, is_active FROM event_types");
    $event_types = $stmtEt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Event Types in Database:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Is Active</th></tr>";
    foreach ($event_types as $et) {
        echo "<tr>";
        echo "<td>{$et['id']}</td>";
        echo "<td>{$et['name']}</td>";
        echo "<td>{$et['is_active']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
