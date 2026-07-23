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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

function getVehiclePlaceholderImageUrl(string $vehicleName = ''): string {
    $name = strtolower(trim($vehicleName));
    
    if (str_contains($name, 'premio') || str_contains($name, 'porsche') || str_contains($name, 'bmw') || str_contains($name, 'mercedes') || str_contains($name, 'benz') || str_contains($name, 'city')) {
        return 'assets/vehicles/toyota-premio.png';
    }
    if (str_contains($name, 'vezel') || str_contains($name, 'rover') || str_contains($name, 'suv') || str_contains($name, 'range') || str_contains($name, 'honda')) {
        return 'assets/vehicles/honda-vezel.png';
    }
    if (str_contains($name, 'hiace') || str_contains($name, 'van') || str_contains($name, 'bus') || str_contains($name, 'kdh')) {
        return 'assets/vehicles/toyota-hiace.png';
    }
    if (str_contains($name, 'sunny') || str_contains($name, 'nissan')) {
        return 'assets/vehicles/nissan-sunny.png';
    }
    if (str_contains($name, 'wagon') || str_contains($name, 'swift') || str_contains($name, 'suzuki') || str_contains($name, 'tesla') || str_contains($name, 'hatchback')) {
        return 'assets/vehicles/suzuki-wagonr.png';
    }

    return 'assets/vehicles/toyota-axio.png';
}

function getVehicleImageUrl(?string $image_url, string $vehicleName = ''): string {
    if (!empty($image_url) && !str_starts_with($image_url, 'http') && file_exists(__DIR__ . '/../public/' . $image_url)) {
        return $image_url;
    }

    return getVehiclePlaceholderImageUrl($vehicleName);
}

function getVehicleGalleryImages(array $vehicle): array {
    $name = strtolower(trim($vehicle['name'] ?? ''));
    $mainImage = getVehicleImageUrl($vehicle['image_url'] ?? '', $vehicle['name'] ?? '');
    
    $allSriLankanImages = [
        'assets/vehicles/toyota-axio.png',
        'assets/vehicles/toyota-premio.png',
        'assets/vehicles/honda-vezel.png',
        'assets/vehicles/toyota-hiace.png',
        'assets/vehicles/suzuki-wagonr.png',
        'assets/vehicles/nissan-sunny.png',
    ];

    $gallery = [];
    if (!empty($mainImage) && in_array($mainImage, $allSriLankanImages)) {
        $gallery[] = $mainImage;
    }

    foreach ($allSriLankanImages as $img) {
        if (!in_array($img, $gallery)) {
            $gallery[] = $img;
        }
    }

    return $gallery;
}
?>
