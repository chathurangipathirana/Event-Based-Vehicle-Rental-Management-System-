<?php
$page_title = 'Vehicle Details';
require_once '../config/database.php';
require_once '../includes/auth.php';

$vehicle_id = $_GET['id'] ?? 0;
$vehicle = getVehicleById($vehicle_id);

if (!$vehicle) {
    header('Location: vehicles.php');
    exit();
}

// Get similar vehicles
$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE status = 'available' AND id != ? LIMIT 3");
$stmt->execute([$vehicle_id]);
$similar_vehicles = $stmt->fetchAll();
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/navbar.php'; ?>

<main class="pt-16 min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="index.php" class="hover:text-red-600">Home</a>
            <span>/</span>
            <a href="vehicles.php" class="hover:text-red-600">Vehicles</a>
            <span>/</span>
            <span class="text-gray-900"><?php echo htmlspecialchars($vehicle['name']); ?></span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Vehicle Images and Main Info -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="h-96 bg-gray-200 flex items-center justify-center">
                        <?php if ($vehicle['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($vehicle['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($vehicle['name']); ?>"
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <span class="material-symbols-outlined text-8xl text-gray-400">directions_car</span>
                        <?php endif; ?>
                    </div>
                    <div class="p-8">
                        <h1 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars($vehicle['name']); ?></h1>
                        <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($vehicle['model']); ?> (<?php echo $vehicle['year']; ?>)</p>
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
                            <div>
                                <span class="material-symbols-outlined text-gray-400">airline_seat_recline_normal</span>
                                <p class="font-semibold mt-1"><?php echo $vehicle['capacity']; ?> Seats</p>
                                <p class="text-sm text-gray-500">Capacity</p>
                            </div>
                            <div>
                                <span class="material-symbols-outlined text-gray-400">settings</span>
                                <p class="font-semibold mt-1"><?php echo $vehicle['transmission']; ?></p>
                                <p class="text-sm text-gray-500">Transmission</p>
                            </div>
                            <div>
                                <span class="material-symbols-outlined text-gray-400">local_gas_station</span>
                                <p class="font-semibold mt-1"><?php echo $vehicle['fuel_type']; ?></p>
                                <p class="text-sm text-gray-500">Fuel Type</p>
                            </div>
                            <div>
                                <span class="material-symbols-outlined text-gray-400">luggage</span>
                                <p class="font-semibold mt-1">2-3 Bags</p>
                                <p class="text-sm text-gray-500">Luggage</p>
                            </div>
                        </div>
                        
                        <div class="mb-8">
                            <h3 class="text-xl font-bold mb-4">Description</h3>
                            <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($vehicle['description'] ?? 'Experience luxury and performance with this premium vehicle. Perfect for corporate events, weddings, and special occasions.')); ?></p>
                        </div>
                        
                        <div>
                            <h3 class="text-xl font-bold mb-4">Features</h3>
                            <ul class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <li class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-green-600">check_circle</span>
                                    <span>GPS Navigation</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-green-600">check_circle</span>
                                    <span>Leather Seats</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-green-600">check_circle</span>
                                    <span>Climate Control</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-green-600">check_circle</span>
                                    <span>Premium Sound System</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Booking Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-lg p-6 sticky top-24 border border-gray-100">
                    <div class="text-center mb-6">
                        <p class="text-3xl font-bold text-red-600">LKR <?php echo number_format($vehicle['price_per_hour'], 2); ?></p>
                        <p class="text-gray-500">per hour</p>
                        <p class="text-sm text-gray-400 mt-2">or LKR <?php echo number_format($vehicle['price_per_day'], 2); ?>/day</p>
                    </div>
                    
                    <a href="booking.php?vehicle=<?php echo $vehicle['id']; ?>" 
                       class="block w-full bg-red-600 text-white text-center py-3 rounded-lg font-semibold hover:bg-red-700 transition mb-4 shadow-md">
                        Book This Vehicle
                    </a>
                    
                    <div class="border-t pt-4 mt-4">
                        <h4 class="font-semibold mb-3">Included Services:</h4>
                        <ul class="space-y-2">
                            <li class="flex items-center gap-2 text-sm">
                                <span class="material-symbols-outlined text-green-600 text-sm">verified</span>
                                Full Sri Lankan Insurance Coverage
                            </li>
                            <li class="flex items-center gap-2 text-sm">
                                <span class="material-symbols-outlined text-green-600 text-sm">support_agent</span>
                                24/7 Islandwide Customer Support
                            </li>
                            <li class="flex items-center gap-2 text-sm">
                                <span class="material-symbols-outlined text-green-600 text-sm">local_car_wash</span>
                                Complimentary Event Washing & Polishing
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Similar Vehicles -->
        <?php if (!empty($similar_vehicles)): ?>
        <div class="mt-12">
            <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-red-600">directions_car</span>
                Similar Sri Lankan Vehicles You Might Like
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($similar_vehicles as $similar): ?>
                <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition border border-gray-100 group">
                    <div class="h-48 bg-slate-900 relative overflow-hidden">
                        <?php $similarImg = getVehicleImageUrl($similar['image_url'], $similar['name']); ?>
                        <img src="<?php echo htmlspecialchars($similarImg); ?>" 
                             alt="<?php echo htmlspecialchars($similar['name']); ?>" 
                             class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                             onerror="this.onerror=null;this.src='assets/vehicle-default.svg'">
                        <span class="absolute top-3 left-3 bg-red-600 text-white text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider shadow">
                            Sri Lanka Fleet
                        </span>
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold text-lg mb-1"><?php echo htmlspecialchars($similar['name']); ?></h3>
                        <p class="text-gray-600 text-sm mb-3"><?php echo htmlspecialchars($similar['model']); ?></p>
                        <div class="flex justify-between items-center">
                            <span class="text-red-600 font-bold">LKR <?php echo number_format($similar['price_per_hour'], 2); ?>/hr</span>
                            <a href="vehicle-details.php?id=<?php echo $similar['id']; ?>" class="text-red-600 hover:underline text-sm">View Details</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
function changeMainImage(src, element) {
    const mainImg = document.getElementById('mainVehicleImage');
    if (mainImg) {
        mainImg.style.opacity = '0.4';
        setTimeout(() => {
            mainImg.src = src;
            mainImg.style.opacity = '1';
        }, 150);
    }
    document.querySelectorAll('.thumb-btn').forEach(btn => {
        btn.classList.remove('border-red-600', 'shadow-md', 'scale-105', 'ring-2', 'ring-red-400/50', 'opacity-100');
        btn.classList.add('border-transparent', 'opacity-70');
    });
    if (element) {
        element.classList.remove('border-transparent', 'opacity-70');
        element.classList.add('border-red-600', 'shadow-md', 'scale-105', 'ring-2', 'ring-red-400/50', 'opacity-100');
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>