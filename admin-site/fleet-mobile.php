<?php
$page_title = 'Vehicle Management';
require_once 'includes/auth.php';
requireAdminLogin();
require_once 'config/database.php';

function getAdminVehicleImageUrl(?string $image_url, string $vehicleName = ''): string {
    $name = strtolower(trim($vehicleName));

    $map = [
        'axio' => '../user-site/public/assets/vehicles/toyota-axio.png',
        'premio' => '../user-site/public/assets/vehicles/toyota-premio.png',
        'vezel' => '../user-site/public/assets/vehicles/honda-vezel.png',
        'hiace' => '../user-site/public/assets/vehicles/toyota-hiace.png',
        'sunny' => '../user-site/public/assets/vehicles/nissan-sunny.png',
        'wagon' => '../user-site/public/assets/vehicles/suzuki-wagonr.png',
    ];

    foreach ($map as $key => $path) {
        if (str_contains($name, $key)) {
            return $path;
        }
    }

    if (!empty($image_url) && !str_starts_with($image_url, 'http')) {
        return '../user-site/public/' . ltrim($image_url, '/');
    }

    return '../user-site/public/assets/vehicles/toyota-axio.png';
}

try {
    $vehicles = $pdo->query("
        SELECT id, name, model, license_plate as plate, price_per_day as price, status, category, image_url
        FROM vehicles
        ORDER BY id ASC
    ")->fetchAll();
} catch (PDOException $e) {
    $vehicles = [];
}

if (empty($vehicles)) {
    $vehicles = [
        ['id' => 1, 'name' => 'Colombo Toyota Axio', 'model' => 'Toyota Corolla Axio', 'plate' => 'WP CAA-2345', 'price' => 18500, 'status' => 'available', 'category' => 'Luxury'],
        ['id' => 2, 'name' => 'Kandy Toyota Premio', 'model' => 'Toyota Premio FL', 'plate' => 'CP CAD-9876', 'price' => 22000, 'status' => 'booked', 'category' => 'Luxury'],
        ['id' => 3, 'name' => 'Galle Honda Vezel', 'model' => 'Honda Vezel Z Hybrid', 'plate' => 'SP CBA-4456', 'price' => 24500, 'status' => 'available', 'category' => 'Luxury SUV'],
        ['id' => 4, 'name' => 'Negombo Toyota HiAce KDH', 'model' => 'Toyota HiAce High Roof KDH', 'plate' => 'WP NB-1123', 'price' => 30000, 'status' => 'maintenance', 'category' => 'Executive'],
        ['id' => 5, 'name' => 'Matara Suzuki Wagon R', 'model' => 'Suzuki Wagon R Stingray FX', 'plate' => 'SP CBA-5567', 'price' => 14000, 'status' => 'available', 'category' => 'Economy'],
    ];
}

$total_fleet = count($vehicles);
$total_booked = count(array_filter($vehicles, fn($v) => $v['status'] == 'booked'));
$maintenance_count = count(array_filter($vehicles, fn($v) => $v['status'] == 'maintenance'));
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0, viewport-fit=cover" name="viewport"/>
<title>FleetElite Admin Portal</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<style>
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    :root { --surface:#f9f9fa; --surface-low:#f3f4f4; --surface-card:#ffffff; --surface-high:#e7e8e9; --primary:#02414a; --primary-soft:#b8ebf7; --primary-hover:#0d5260; --outline:#c0c8ca; --text:#191c1d; --muted:#40484a; --success:#176a3a; --warning:#8a5200; --danger:#ba1a1a; }
    body { font-family: 'Inter', sans-serif; background-color: var(--surface); color: var(--text); -webkit-tap-highlight-color: transparent; }
    header { border-color: var(--outline) !important; }
    .bg-white { background-color: var(--surface-card) !important; }
    .bg-gray-50, .hover\:bg-gray-50:hover { background-color: var(--surface-low) !important; }
    .bg-gray-100, .bg-gray-200 { background-color: var(--surface-high) !important; }
    .border-gray-100, .border-gray-200, .border-gray-300 { border-color: var(--outline) !important; }
    .text-gray-900, .text-gray-700 { color: var(--text) !important; }
    .text-gray-600, .text-gray-500, .text-gray-400 { color: var(--muted) !important; }
    .bg-red-600 { background-color: var(--primary) !important; }
    .bg-red-50, .bg-red-100 { background-color: var(--primary-soft) !important; }
    .hover\:bg-red-700:hover { background-color: var(--primary-hover) !important; }
    .text-red-600, .text-blue-700 { color: var(--primary) !important; }
    [class*="bg-red-"] { background-color: var(--primary) !important; }
    [class*="text-red-"] { color: var(--primary) !important; }
    [class*="border-red-"] { border-color: var(--primary-soft) !important; }
    [class*="hover:bg-red-"]:hover { background-color: var(--primary-hover) !important; }
    [class*="hover:text-red-"]:hover { color: var(--primary-hover) !important; }
    .bg-red-50, .bg-red-100 { background-color: var(--primary-soft) !important; }
    .focus\:ring-red-500:focus, .focus\:border-red-500:focus { --tw-ring-color: rgba(2,65,74,0.18) !important; border-color: var(--primary) !important; }
    .text-green-600, .text-green-700 { color: var(--success) !important; }
    .bg-green-50, .bg-green-100 { background-color: #dff5e8 !important; }
    .text-orange-600, .text-orange-700 { color: var(--warning) !important; }
    .bg-orange-50, .bg-yellow-100 { background-color: #fff3d6 !important; }
    .bg-blue-50, .bg-blue-100 { background-color: var(--primary-soft) !important; }
    .rounded-xl { border-radius: 0.75rem !important; }
    .rounded-lg { border-radius: 0.5rem !important; }
    .rounded-full { border-radius: 9999px !important; }
    .shadow-lg, .shadow-md, .shadow-sm { box-shadow: 0 10px 24px rgba(25,28,29,0.06) !important; }
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(2,65,74,0.45); z-index: 1000; overflow-y: auto; }
    .modal-content { background: var(--surface-card); margin: 50px auto; max-width: 600px; border-radius: 12px; border: 1px solid var(--outline); box-shadow: 0 24px 60px rgba(25,28,29,0.18); }
</style>
</head>
<body class="bg-gray-50 pb-20">

<!-- TopAppBar - UI 5 Style -->
<header class="fixed top-0 w-full z-50 bg-white border-b border-gray-100 shadow-sm flex justify-between items-center h-16 px-4">
    <div class="flex items-center gap-3">
        <span class="material-symbols-outlined text-red-600" onclick="history.back()">arrow_back</span>
        <span class="text-xl font-black tracking-tight text-red-600">FleetElite</span>
    </div>
    <div class="flex items-center gap-4">
        <span class="text-sm text-gray-500 uppercase hidden sm:block">Admin Portal</span>
        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-bold">
            AD
        </div>
    </div>
</header>

<main class="pt-20 px-4 max-w-7xl mx-auto">
    <!-- Metrics Bento Grid - UI 5 Style -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-red-50 flex items-center justify-center text-red-600">
                <span class="material-symbols-outlined text-3xl">directions_car</span>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase">Total Vehicles</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $total_fleet; ?></p>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-green-50 flex items-center justify-center text-green-600">
                <span class="material-symbols-outlined text-3xl">event_available</span>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase">Booked</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $total_booked; ?></p>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-orange-50 flex items-center justify-center text-orange-600">
                <span class="material-symbols-outlined text-3xl">health_and_safety</span>
            </div>
            <div>
                <p class="text-xs text-gray-500 uppercase">Health</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo $total_fleet > 0 ? round(($maintenance_count / $total_fleet) * 100) : 0; ?>%</p>
            </div>
        </div>
    </div>

    <!-- Action Bar - UI 5 Style -->
    <div class="flex flex-col sm:flex-row gap-4 mb-6 items-center justify-between">
        <div class="relative w-full sm:max-w-md">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
            <input type="text" id="mobileSearch" class="w-full pl-10 pr-4 py-3 bg-white border border-gray-200 rounded-full text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none" placeholder="Search vehicle...">
        </div>
        <button onclick="openAddModal()" class="w-full sm:w-auto bg-red-600 text-white px-6 py-3 rounded-full font-medium flex items-center justify-center gap-2 shadow-md">
            <span class="material-symbols-outlined">add</span>
            Add Vehicle
        </button>
    </div>

    <!-- Vehicle Cards - UI 5 Style -->
    <div class="space-y-4" id="vehicleList">
        <?php foreach ($vehicles as $vehicle): ?>
        <div class="bg-white rounded-xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-md transition" data-name="<?php echo strtolower($vehicle['name'] . ' ' . $vehicle['model'] . ' ' . $vehicle['plate']); ?>">
            <div class="flex p-3 sm:p-4 gap-4">
                <?php $mImg = getAdminVehicleImageUrl($vehicle['image_url'] ?? '', $vehicle['name']); ?>
                <div class="w-24 h-24 sm:w-32 sm:h-32 rounded-lg bg-slate-900 flex-shrink-0 flex items-center justify-center overflow-hidden border border-gray-200">
                    <img src="<?php echo htmlspecialchars($mImg); ?>" alt="<?php echo htmlspecialchars($vehicle['name']); ?>" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='../user-site/public/assets/vehicle-default.svg'">
                </div>
                <div class="flex-grow flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-start">
                            <h3 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($vehicle['name']); ?></h3>
                            <span class="text-red-600 font-bold text-lg">LKR <?php echo number_format($vehicle['price'], 2); ?><span class="text-xs text-gray-500">/day</span></span>
                        </div>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($vehicle['model']); ?></p>
                    </div>
                    <div class="flex items-center justify-between mt-2">
                        <span class="px-2.5 py-1 rounded-md text-xs font-bold
                            <?php echo $vehicle['status'] == 'available' ? 'bg-green-50 text-green-700' :
                                ($vehicle['status'] == 'booked' ? 'bg-blue-50 text-blue-700' : 'bg-orange-50 text-orange-700'); ?> border uppercase">
                            <?php echo ucfirst($vehicle['status']); ?>
                        </span>
                        <div class="flex gap-2">
                            <button onclick="editVehicle(<?php echo htmlspecialchars(json_encode($vehicle)); ?>)" class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50">
                                <span class="material-symbols-outlined text-xl">edit</span>
                            </button>
                            <button onclick="deleteVehicle(<?php echo $vehicle['id']; ?>)" class="w-9 h-9 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-red-50 hover:text-red-600">
                                <span class="material-symbols-outlined text-xl">delete</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</main>

<!-- Bottom Navigation - UI 5 Style -->
<nav class="fixed bottom-0 w-full z-50 rounded-t-xl bg-white border-t border-gray-200 shadow-lg flex justify-around items-center h-20 px-2">
    <a href="fleet-mobile.php" class="flex flex-col items-center justify-center bg-red-50 text-red-600 rounded-xl px-3 py-1.5 transition">
        <span class="material-symbols-outlined">directions_car</span>
        <span class="text-[11px] font-semibold uppercase">Vehicle</span>
    </a>
    <a href="dashboard.php" class="flex flex-col items-center justify-center text-gray-400 px-3 py-1.5">
        <span class="material-symbols-outlined">analytics</span>
        <span class="text-[11px] font-semibold uppercase">Status</span>
    </a>
    <a href="bookings.php" class="flex flex-col items-center justify-center text-gray-400 px-3 py-1.5">
        <span class="material-symbols-outlined">monitor_heart</span>
        <span class="text-[11px] font-semibold uppercase">Health</span>
    </a>
    <a href="#" onclick="openAddModal()" class="flex flex-col items-center justify-center text-gray-400 px-3 py-1.5">
        <span class="material-symbols-outlined">add_circle</span>
        <span class="text-[11px] font-semibold uppercase">Add</span>
    </a>
</nav>

<!-- Add Vehicle Modal -->
<div id="vehicleModal" class="modal">
    <div class="modal-content mx-4">
        <div class="p-6 border-b">
            <h3 id="modalTitle" class="text-xl font-bold">Add Vehicle</h3>
        </div>
        <form class="p-6 space-y-4">
            <input type="text" id="vehicleName" placeholder="Vehicle Name" class="w-full px-3 py-2 border rounded-lg">
            <input type="text" id="vehicleModel" placeholder="Model" class="w-full px-3 py-2 border rounded-lg">
            <input type="text" id="vehiclePlate" placeholder="License Plate" class="w-full px-3 py-2 border rounded-lg">
            <input type="number" id="vehiclePrice" placeholder="Daily Rate" class="w-full px-3 py-2 border rounded-lg">
            <select id="vehicleStatus" class="w-full px-3 py-2 border rounded-lg">
                <option value="available">Available</option>
                <option value="booked">Booked</option>
                <option value="maintenance">Maintenance</option>
            </select>
            <div class="flex gap-3">
                <button type="button" onclick="saveVehicle()" class="flex-1 bg-red-600 text-white py-2 rounded-lg">Save</button>
                <button type="button" onclick="closeModal()" class="flex-1 bg-gray-200 py-2 rounded-lg">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Add Vehicle';
    document.getElementById('vehicleModal').style.display = 'block';
}

function editVehicle(vehicle) {
    document.getElementById('modalTitle').innerText = 'Edit Vehicle';
    document.getElementById('vehicleName').value = vehicle.name;
    document.getElementById('vehicleModel').value = vehicle.model;
    document.getElementById('vehiclePlate').value = vehicle.plate;
    document.getElementById('vehiclePrice').value = vehicle.price;
    document.getElementById('vehicleStatus').value = vehicle.status;
    document.getElementById('vehicleModal').style.display = 'block';
}

function deleteVehicle(id) {
    if (confirm('Delete this vehicle?')) {
        alert('Vehicle deleted');
    }
}

function saveVehicle() {
    alert('Vehicle saved!');
    closeModal();
}

function closeModal() {
    document.getElementById('vehicleModal').style.display = 'none';
}

document.getElementById('mobileSearch')?.addEventListener('keyup', function(e) {
    const term = e.target.value.toLowerCase();
    const cards = document.querySelectorAll('#vehicleList > div');
    cards.forEach(card => {
        const name = card.getAttribute('data-name') || '';
        card.style.display = name.includes(term) ? '' : 'none';
    });
});
</script>

</body>
</html>
