<?php
$page_title = 'Fleet Management';
require_once 'includes/auth.php';
requireAdminLogin();
require_once 'config/database.php';

$message = '';
$error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $plate = trim($_POST['plate'] ?? '');
        $vin = trim($_POST['vin'] ?? '');
        $image_url = trim($_POST['image_url'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $category = trim($_POST['category'] ?? 'Sports');
        $status = trim($_POST['status'] ?? 'available');
        
        if (empty($name) || empty($model)) {
            $_SESSION['error'] = 'Name and Model are required fields.';
        } else {
            try {
                if ($id > 0) {
                    $stmt = $pdo->prepare("
                        UPDATE vehicles 
                        SET name = ?, model = ?, license_plate = ?, vin_number = ?, image_url = ?, price_per_day = ?, category = ?, status = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $model, $plate, $vin, $image_url, $price, $category, $status, $id]);
                    $_SESSION['message'] = 'Vehicle updated successfully!';
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO vehicles (name, model, license_plate, vin_number, image_url, price_per_day, category, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $model, $plate, $vin, $image_url, $price, $category, $status]);
                    $_SESSION['message'] = 'Vehicle added successfully!';
                }
            } catch(PDOException $e) {
                $_SESSION['error'] = 'Database error: ' . $e->getMessage();
            }
        }
        header('Location: fleet.php');
        exit();
    } elseif ($action === 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['message'] = 'Vehicle deleted successfully!';
            } catch(PDOException $e) {
                $_SESSION['error'] = 'Database error: ' . $e->getMessage();
            }
        }
        header('Location: fleet.php');
        exit();
    }
}

// Fetch all vehicles from database
$vehicles = [];
$is_sample_fleet = false;
try {
    $vehicles = $pdo->query("
        SELECT *, 
               price_per_day as price, 
               license_plate as plate, 
               vin_number as vin 
        FROM vehicles 
        ORDER BY id DESC
    ")->fetchAll();
} catch(PDOException $e) {
    // Keep list empty if tables don't exist
}

function localizeVehicleName($name) {
    $aliases = [
        'Tesla Model S' => 'Toyota Axio',
        'Mercedes S-Class' => 'Toyota Premio',
        'BMW 7 Series' => 'Honda Vezel',
        'Range Rover SV' => 'Toyota HiAce',
        'Porsche 911 GT3' => 'Nissan Sunny',
        'Luxury Sedan' => 'Toyota Premio',
    ];
    return $aliases[$name] ?? $name;
}

foreach ($vehicles as &$vehicle) {
    if (!empty($vehicle['name'])) {
        $vehicle['name'] = localizeVehicleName($vehicle['name']);
    }
}
unset($vehicle);

if (empty($vehicles)) {
    $is_sample_fleet = true;
    $vehicles = [
        [
            'id' => 101,
            'name' => 'Toyota Axio',
            'model' => 'Toyota Corolla Axio',
            'plate' => 'WP CAA-2345',
            'vin' => 'LK-1001-AXIO',
            'image_url' => 'https://images.unsplash.com/photo-1555215695-3004980ad54e?auto=format&fit=crop&w=900&q=80',
            'price' => 18500,
            'category' => 'Luxury',
            'status' => 'available'
        ],
        [
            'id' => 102,
            'name' => 'Toyota Premio',
            'model' => 'Toyota Premio',
            'plate' => 'CP CAD-9876',
            'vin' => 'LK-1002-PREMIO',
            'image_url' => 'https://images.unsplash.com/photo-1502877338535-766e1452684a?auto=format&fit=crop&w=900&q=80',
            'price' => 22000,
            'category' => 'Luxury',
            'status' => 'available'
        ],
        [
            'id' => 103,
            'name' => 'Honda Vezel',
            'model' => 'Honda Vezel',
            'plate' => 'SP CBA-4456',
            'vin' => 'LK-1003-VEZEL',
            'image_url' => 'https://images.unsplash.com/photo-1549399735-cef2e2c3f638?auto=format&fit=crop&w=900&q=80',
            'price' => 24500,
            'category' => 'Luxury SUV',
            'status' => 'available'
        ],
        [
            'id' => 104,
            'name' => 'Toyota HiAce',
            'model' => 'Toyota HiAce',
            'plate' => 'WP NB-1123',
            'vin' => 'LK-1004-HIACE',
            'image_url' => 'https://images.unsplash.com/photo-1610647752706-3bb12232b3b1?auto=format&fit=crop&w=900&q=80',
            'price' => 30000,
            'category' => 'Executive',
            'status' => 'maintenance'
        ],
        [
            'id' => 105,
            'name' => 'Nissan Sunny',
            'model' => 'Nissan Sunny',
            'plate' => 'NP JAF-5566',
            'vin' => 'LK-1005-SUNNY',
            'image_url' => 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=900&q=80',
            'price' => 16000,
            'category' => 'Economy',
            'status' => 'available'
        ],
    ];
}

$total_fleet = count($vehicles);
$total_booked = count(array_filter($vehicles, fn($v) => $v['status'] == 'booked'));
$maintenance_count = count(array_filter($vehicles, fn($v) => $v['status'] == 'maintenance'));
$fleet_health = $total_fleet > 0 ? round((($total_fleet - $maintenance_count) / $total_fleet) * 100) : 100;
$default_vehicle_image = 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?auto=format&fit=crop&w=400&q=80';
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<main class="ml-64 min-h-screen bg-slate-50">
    <div class="p-8 max-w-7xl mx-auto">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert-success mb-6 p-4 rounded-xl"><?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-error mb-6 p-4 rounded-xl"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <section class="rounded-[2rem] overflow-hidden mb-10">
            <div class="relative bg-slate-900 text-white p-8 lg:p-10 overflow-hidden">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.24),_transparent_36%)] opacity-70 pointer-events-none"></div>
                <div class="relative grid grid-cols-1 xl:grid-cols-[1.5fr_1fr] gap-8 items-center">
                    <div class="max-w-2xl">
                        <p class="text-xs uppercase tracking-[0.35em] text-slate-400 mb-4">Fleet Manager</p>
                        <h1 class="text-5xl font-semibold tracking-tight">Fleet Inventory</h1>
                        <p class="mt-4 text-slate-300 text-lg leading-8">Manage your luxury vehicle assets and availability status with operational precision.</p>
                    </div>
                    <div class="flex flex-wrap justify-end gap-3">
                        <button onclick="openAddModal()" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-cyan-500 text-white text-sm font-semibold hover:bg-cyan-400 transition-all">
                            <span class="material-symbols-outlined text-sm">add</span>
                            Add Vehicle
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-[260px_260px_520px] justify-center gap-6 mb-8">
            <div class="card-3d p-6 pr-24 min-h-[165px] bg-white" style="--card-accent: #0b6b6d;">
                <div>
                    <p class="text-sm text-gray-500 uppercase mb-3">Total Fleet</p>
                    <div class="kpi-value"><?php echo $total_fleet; ?></div>
                    <div class="text-xs text-green-600 mt-6 leading-5">+4% from last month</div>
                </div>
                <div class="card-icon !right-7" style="background:var(--card-accent,#0b6b6d)"><span class="material-symbols-outlined">directions_car</span></div>
            </div>
            <div class="card-3d p-6 pr-24 min-h-[165px] bg-white" style="--card-accent: #0b6b6d;">
                <div>
                    <p class="text-sm text-gray-500 uppercase mb-3">Currently Booked</p>
                    <div class="kpi-value"><?php echo $total_booked; ?></div>
                    <div class="text-xs text-gray-500 mt-6 leading-5">Active rentals</div>
                </div>
                <div class="card-icon !right-7" style="background:var(--card-accent,#0b6b6d)"><span class="material-symbols-outlined">event_available</span></div>
            </div>
            <div class="card-3d p-6 pr-24 min-h-[165px] bg-white sm:col-span-2 xl:col-span-1" style="--card-accent: #b36b2a;">
                <div>
                    <p class="text-sm text-gray-500 uppercase mb-3">Fleet Health</p>
                    <div class="kpi-value"><?php echo $fleet_health; ?>%</div>
                    <div class="mt-6">
                        <div class="flex justify-between mb-2">
                            <span class="text-xs text-gray-500">Maintenance Progress</span>
                            <span class="text-xs text-red-600"><?php echo $maintenance_count; ?> Vehicles</span>
                        </div>
                        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-red-600 h-full" style="width: <?php echo $fleet_health; ?>%"></div>
                        </div>
                    </div>
                </div>
                <div class="card-icon !right-7" style="background:var(--card-accent,#b36b2a)"><span class="material-symbols-outlined">health_and_safety</span></div>
            </div>
        </div>

        <!-- Search and Filter Bar - UI 4 Style -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-6 p-4">
            <div class="flex flex-col lg:flex-row gap-4">
                <div class="flex-1 relative">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">search</span>
                    <input type="text" id="searchInput" class="w-full pl-12 pr-4 py-3 rounded-xl border-gray-200 focus:border-red-500 focus:ring-1 focus:ring-red-500" placeholder="Search by name, model, or VIN...">
                </div>
                <div class="flex gap-4">
                    <select id="categoryFilter" class="rounded-xl border-gray-200 px-6 py-3 min-w-[160px]">
                        <option value="">All Categories</option>
                        <option value="Sports">Sports Performance</option>
                        <option value="Luxury">Executive Sedan</option>
                        <option value="Luxury SUV">Luxury SUV</option>
                        <option value="Electric">Electric Fleet</option>
                    </select>
                    <select id="statusFilter" class="rounded-xl border-gray-200 px-6 py-3 min-w-[160px]">
                        <option value="">All Status</option>
                        <option value="available">Available</option>
                        <option value="booked">Booked</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                    <button onclick="applyFilters()" class="bg-gray-100 hover:bg-gray-200 px-4 py-3 rounded-xl flex items-center transition">
                        <span class="material-symbols-outlined">filter_list</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Data Table - UI 4 Style -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <style>
                    .dashboard-table tbody tr {
                        transition: all 0.3s ease;
                        border-left: 3px solid transparent;
                    }
                    .dashboard-table tbody tr:nth-child(odd) {
                        background-color: #fafbfb;
                    }
                    .dashboard-table tbody tr:nth-child(even) {
                        background-color: #f3f4f4;
                    }
                    .dashboard-table tbody tr:hover {
                        background-color: #fff3e0 !important;
                        border-left-color: #02414a;
                        box-shadow: 0 4px 12px rgba(2, 65, 74, 0.15);
                        transform: translateX(2px);
                    }
                    .dashboard-table tbody tr:hover td {
                        box-shadow: inset 0 0 12px rgba(255, 193, 7, 0.2);
                    }
                    .dashboard-table td {
                        transition: all 0.2s ease;
                        border-right: 1px solid #e0e0e0;
                    }
                    .dashboard-table td:hover {
                        background-color: #ffd54f !important;
                        font-weight: 600;
                        box-shadow: inset 0 0 10px rgba(255, 152, 0, 0.3);
                    }
                    .dashboard-table thead th {
                        background-color: #0f172a;
                        color: #ffffff;
                        font-weight: 700;
                        border-right: 1px solid rgba(255,255,255,0.2);
                    }
                    .dashboard-table thead th:last-child {
                        border-right: none;
                    }
                </style>
                <table class="w-full text-left border-collapse dashboard-table" id="vehiclesTable">
                    <thead>
                        <tr>
                            <th class="px-6 py-4 text-sm uppercase tracking-wider">Vehicle Name</th>
                            <th class="px-6 py-4 text-sm uppercase tracking-wider">Type</th>
                            <th class="px-6 py-4 text-sm uppercase tracking-wider">Category</th>
                            <th class="px-6 py-4 text-sm uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-sm uppercase tracking-wider text-right">Daily Rate</th>
                            <th class="px-6 py-4 text-sm uppercase tracking-wider text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vehicles as $vehicle): ?>
                        <tr data-name="<?php echo strtolower($vehicle['name']); ?>" data-category="<?php echo $vehicle['category']; ?>" data-status="<?php echo $vehicle['status']; ?>">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 rounded-lg bg-gray-100 mr-4 overflow-hidden flex items-center justify-center">
                                        <?php
                                            $imageSource = $vehicle['image_url'] ?? '';
                                            if (!empty($imageSource)) {
                                                // allow commas, semicolons, pipes, or newlines as separators
                                                $imageParts = preg_split('/\s*[,;|\n\r]\s*/', $imageSource);
                                                $imageSource = trim($imageParts[0]);
                                            }
                                            if (empty($imageSource) || !filter_var($imageSource, FILTER_VALIDATE_URL)) {
                                                $imageSource = $default_vehicle_image;
                                            }
                                        ?>
                                        <img src="<?php echo htmlspecialchars($imageSource); ?>" alt="<?php echo htmlspecialchars($vehicle['name']); ?>" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='<?php echo $default_vehicle_image; ?>'">
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900"><?php echo $vehicle['name']; ?></div>
                                        <div class="text-xs text-gray-500">VIN: <?php echo $vehicle['vin']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4"><?php echo $vehicle['model']; ?></td>
                            <td class="px-6 py-4"><?php echo $vehicle['category']; ?></td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold <?php echo $vehicle['status'] == 'available' ? 'bg-green-100 text-green-700' : ($vehicle['status'] == 'booked' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'); ?>">
                                    <span class="w-1.5 h-1.5 rounded-full mr-2 <?php echo $vehicle['status'] == 'available' ? 'bg-green-500' : ($vehicle['status'] == 'booked' ? 'bg-blue-500' : 'bg-amber-500'); ?>"></span>
                                    <?php echo ucfirst($vehicle['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-red-600">LKR <?php echo number_format($vehicle['price']); ?></td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center items-center space-x-2">
                                    <?php if (!$is_sample_fleet): ?>
                                        <button onclick="editVehicle(<?php echo htmlspecialchars(json_encode($vehicle)); ?>)" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500 hover:text-blue-600 transition">
                                            <span class="material-symbols-outlined text-xl">edit</span>
                                        </button>
                                        <button onclick="deleteVehicle(<?php echo $vehicle['id']; ?>)" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500 hover:text-red-600 transition">
                                            <span class="material-symbols-outlined text-xl">delete</span>
                                        </button>
                                    <?php else: ?>
                                        <button class="p-2 rounded-lg text-gray-400 cursor-not-allowed" disabled>
                                            <span class="material-symbols-outlined text-xl">edit</span>
                                        </button>
                                        <button class="p-2 rounded-lg text-gray-400 cursor-not-allowed" disabled>
                                            <span class="material-symbols-outlined text-xl">delete</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Add/Edit Vehicle Modal -->
<div id="vehicleModal" class="modal">
    <div class="modal-content">
        <div class="p-6 border-b">
            <h3 id="modalTitle" class="text-xl font-bold">Add New Vehicle</h3>
        </div>
        <form id="vehicleForm" method="POST" action="fleet.php" class="p-6 space-y-4">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="vehicleId" value="0">
            <div class="grid grid-cols-2 gap-4">
                <div><input type="text" name="name" id="vehicleName" placeholder="Vehicle Name" required class="w-full px-3 py-2 border rounded-lg"></div>
                <div><input type="text" name="model" id="vehicleModel" placeholder="Model" required class="w-full px-3 py-2 border rounded-lg"></div>
                <div><input type="text" name="plate" id="vehiclePlate" placeholder="License Plate" class="w-full px-3 py-2 border rounded-lg"></div>
                <div><input type="text" name="vin" id="vehicleVin" placeholder="VIN Number" class="w-full px-3 py-2 border rounded-lg"></div>
                <div class="col-span-2"><input type="url" name="image_url" id="vehicleImageUrl" placeholder="Vehicle Photo URL" class="w-full px-3 py-2 border rounded-lg"></div>
                <div><input type="number" name="price" id="vehiclePrice" placeholder="Daily Rate (LKR)" required class="w-full px-3 py-2 border rounded-lg"></div>
                <div>
                    <select name="category" id="vehicleCategory" class="w-full px-3 py-2 border rounded-lg">
                        <option value="Sports">Sports Performance</option>
                        <option value="Luxury">Executive Sedan</option>
                        <option value="Luxury SUV">Luxury SUV</option>
                        <option value="Electric">Electric Fleet</option>
                    </select>
                </div>
                <div>
                    <select name="status" id="vehicleStatus" class="w-full px-3 py-2 border rounded-lg">
                        <option value="available">Available</option>
                        <option value="booked">Booked</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-red-600 text-white py-2 rounded-lg hover:bg-red-700">Save</button>
                <button type="button" onclick="closeModal()" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-lg hover:bg-gray-300">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Add New Vehicle';
    document.getElementById('vehicleId').value = 0;
    document.getElementById('vehicleForm').reset();
    document.getElementById('vehicleModal').style.display = 'block';
}

function editVehicle(vehicle) {
    document.getElementById('modalTitle').innerText = 'Edit Vehicle';
    document.getElementById('vehicleId').value = vehicle.id;
    document.getElementById('vehicleName').value = vehicle.name;
    document.getElementById('vehicleModel').value = vehicle.model;
    document.getElementById('vehiclePlate').value = vehicle.plate || '';
    document.getElementById('vehicleVin').value = vehicle.vin || '';
    document.getElementById('vehicleImageUrl').value = vehicle.image_url || '';
    document.getElementById('vehiclePrice').value = vehicle.price;
    document.getElementById('vehicleCategory').value = vehicle.category;
    document.getElementById('vehicleStatus').value = vehicle.status;
    document.getElementById('vehicleModal').style.display = 'block';
}

function deleteVehicle(id) {
    if (confirm('Are you sure you want to delete this vehicle?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'fleet.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        form.appendChild(actionInput);
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;
        form.appendChild(idInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function closeModal() {
    document.getElementById('vehicleModal').style.display = 'none';
}

function applyFilters() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const categoryFilter = document.getElementById('categoryFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('#vehiclesTable tbody tr');
    
    rows.forEach(row => {
        const name = row.getAttribute('data-name') || '';
        const category = row.getAttribute('data-category') || '';
        const status = row.getAttribute('data-status') || '';
        
        let show = true;
        if (searchTerm && !name.includes(searchTerm)) show = false;
        if (categoryFilter && category !== categoryFilter) show = false;
        if (statusFilter && status !== statusFilter) show = false;
        
        row.style.display = show ? '' : 'none';
    });
}

document.getElementById('searchInput')?.addEventListener('keyup', applyFilters);
document.getElementById('categoryFilter')?.addEventListener('change', applyFilters);
document.getElementById('statusFilter')?.addEventListener('change', applyFilters);
</script>

<?php require_once 'includes/footer.php'; ?>
