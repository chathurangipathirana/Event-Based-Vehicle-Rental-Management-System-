<?php
require_once __DIR__ . '/database.php';

try {
    // 1. Ensure table structure is present
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS vehicles (
          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          name VARCHAR(150) NOT NULL,
          model VARCHAR(150) NOT NULL,
          year YEAR DEFAULT NULL,
          capacity TINYINT UNSIGNED DEFAULT 4,
          transmission VARCHAR(50) DEFAULT 'Automatic',
          fuel_type VARCHAR(50) DEFAULT 'Gasoline',
          status VARCHAR(50) NOT NULL DEFAULT 'available',
          image_url VARCHAR(255) DEFAULT NULL,
          description TEXT DEFAULT NULL,
          price_per_hour DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          price_per_day DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Comprehensive Sri Lankan fleet entries with Available, Booked, and 1 Maintenance status
    $sriLankanVehicles = [
        [
            'name' => 'Colombo Toyota Premio F-Ex',
            'model' => 'Toyota Premio',
            'year' => 2021,
            'capacity' => 5,
            'transmission' => 'Automatic',
            'fuel_type' => 'Petrol',
            'status' => 'available',
            'image_url' => 'assets/vehicles/toyota-premio.png',
            'description' => 'Luxury Sri Lankan executive sedan for weddings, VIP arrivals, and corporate transport in Colombo.',
            'price_per_hour' => 2800.00,
            'price_per_day' => 22000.00
        ],
        [
            'name' => 'Kandy Toyota Axio Hybrid',
            'model' => 'Toyota Corolla Axio',
            'year' => 2020,
            'capacity' => 5,
            'transmission' => 'Automatic',
            'fuel_type' => 'Hybrid',
            'status' => 'booked',
            'image_url' => 'assets/vehicles/toyota-axio.png',
            'description' => 'Popular, comfortable Sri Lankan sedan currently reserved for a wedding escort hire in Kandy.',
            'price_per_hour' => 2400.00,
            'price_per_day' => 18500.00
        ],
        [
            'name' => 'Galle Honda Vezel RS',
            'model' => 'Honda Vezel RS',
            'year' => 2021,
            'capacity' => 5,
            'transmission' => 'Automatic',
            'fuel_type' => 'Hybrid',
            'status' => 'available',
            'image_url' => 'assets/vehicles/honda-vezel.png',
            'description' => 'Stylish hybrid SUV ideal for southern beach weddings, luxury island tours, and corporate delegations.',
            'price_per_hour' => 3200.00,
            'price_per_day' => 24500.00
        ],
        [
            'name' => 'Negombo Toyota HiAce KDH Super GL',
            'model' => 'Toyota HiAce KDH 200',
            'year' => 2019,
            'capacity' => 10,
            'transmission' => 'Automatic',
            'fuel_type' => 'Diesel',
            'status' => 'booked',
            'image_url' => 'assets/vehicles/toyota-hiace.png',
            'description' => 'Premium high-roof passenger van currently booked for an airport delegation arrival.',
            'price_per_hour' => 3800.00,
            'price_per_day' => 30000.00
        ],
        [
            'name' => 'Colombo Nissan Sunny Super Saloon',
            'model' => 'Nissan Sunny N17',
            'year' => 2020,
            'capacity' => 5,
            'transmission' => 'Automatic',
            'fuel_type' => 'Petrol',
            'status' => 'available',
            'image_url' => 'assets/vehicles/nissan-sunny.png',
            'description' => 'Reliable Sri Lankan sedan for Colombo city business meetings, airport runs, and daily hires.',
            'price_per_hour' => 2000.00,
            'price_per_day' => 16000.00
        ],
        [
            'name' => 'Matara Suzuki Wagon R Stingray',
            'model' => 'Suzuki Wagon R Stingray',
            'year' => 2022,
            'capacity' => 4,
            'transmission' => 'Automatic',
            'fuel_type' => 'Hybrid',
            'status' => 'available',
            'image_url' => 'assets/vehicles/suzuki-wagonr.png',
            'description' => 'Highly fuel-efficient Sri Lankan city hatchback for short event hires and coastal errands.',
            'price_per_hour' => 1800.00,
            'price_per_day' => 14000.00
        ],
        [
            'name' => 'Bentota Toyota Premio Luxury Bridal Edition',
            'model' => 'Toyota Premio G Superior',
            'year' => 2022,
            'capacity' => 5,
            'transmission' => 'Automatic',
            'fuel_type' => 'Petrol',
            'status' => 'booked',
            'image_url' => 'assets/vehicles/toyota-premio.png',
            'description' => 'Decorated luxury Sri Lankan wedding car currently reserved for a bridal procession hire in Bentota.',
            'price_per_hour' => 3500.00,
            'price_per_day' => 26000.00
        ],
        [
            'name' => 'Nuwara Eliya Honda Vezel Z Sensing',
            'model' => 'Honda Vezel Hybrid',
            'year' => 2021,
            'capacity' => 5,
            'transmission' => 'Automatic',
            'fuel_type' => 'Hybrid',
            'status' => 'available',
            'image_url' => 'assets/vehicles/honda-vezel.png',
            'description' => 'Smooth, powerful hybrid SUV for Sri Lankan hill-country expeditions and Nuwara Eliya tours.',
            'price_per_hour' => 3300.00,
            'price_per_day' => 25000.00
        ],
        [
            'name' => 'Jaffna Toyota Axio EX',
            'model' => 'Toyota Corolla Axio',
            'year' => 2021,
            'capacity' => 5,
            'transmission' => 'Automatic',
            'fuel_type' => 'Petrol',
            'status' => 'available',
            'image_url' => 'assets/vehicles/toyota-axio.png',
            'description' => 'Spacious, reliable Sri Lankan sedan for northern peninsula travel and event guest transport.',
            'price_per_hour' => 2350.00,
            'price_per_day' => 18000.00
        ],
        [
            'name' => 'Trincomalee Suzuki Swift RS',
            'model' => 'Suzuki Swift Turbo',
            'year' => 2021,
            'capacity' => 5,
            'transmission' => 'Automatic',
            'fuel_type' => 'Petrol',
            'status' => 'maintenance',
            'image_url' => 'assets/vehicles/suzuki-wagonr.png',
            'description' => 'Agile hatchback currently undergoing scheduled routine maintenance and safety inspection.',
            'price_per_hour' => 1900.00,
            'price_per_day' => 15000.00
        ],
        [
            'name' => 'Kurunegala Toyota HiAce VIP Commuter',
            'model' => 'Toyota HiAce KDH 222',
            'year' => 2020,
            'capacity' => 12,
            'transmission' => 'Automatic',
            'fuel_type' => 'Diesel',
            'status' => 'available',
            'image_url' => 'assets/vehicles/toyota-hiace.png',
            'description' => 'Large luxury van for Sri Lankan wedding entourages, corporate delegates, and family trips.',
            'price_per_hour' => 4200.00,
            'price_per_day' => 32000.00
        ],
        [
            'name' => 'Colombo Toyota Corolla Grace Hybrid',
            'model' => 'Honda Grace / Toyota Corolla',
            'year' => 2021,
            'capacity' => 5,
            'transmission' => 'Automatic',
            'fuel_type' => 'Hybrid',
            'status' => 'available',
            'image_url' => 'assets/vehicles/toyota-axio.png',
            'description' => 'Premium hybrid sedan with leather interior for diplomatic and corporate transfers.',
            'price_per_hour' => 2600.00,
            'price_per_day' => 20000.00
        ]
    ];

    // 3. Clear existing or upsert vehicles to ensure all statuses are set
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE vehicles; SET FOREIGN_KEY_CHECKS = 1;");

    $stmtInsert = $pdo->prepare("
        INSERT INTO vehicles (name, model, year, capacity, transmission, fuel_type, status, image_url, description, price_per_hour, price_per_day)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($sriLankanVehicles as $v) {
        $stmtInsert->execute([
            $v['name'],
            $v['model'],
            $v['year'],
            $v['capacity'],
            $v['transmission'],
            $v['fuel_type'],
            $v['status'],
            $v['image_url'],
            $v['description'],
            $v['price_per_hour'],
            $v['price_per_day']
        ]);
    }

    echo "SUCCESS: 12 Sri Lankan vehicles seeded (8 Available, 3 Booked, 1 Maintenance).\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
