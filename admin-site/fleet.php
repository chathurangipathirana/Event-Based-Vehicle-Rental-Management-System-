<?php
$page_title = 'Fleet Management';
require_once 'includes/auth.php';
requireAdminLogin();

// Vehicle prices converted to Sri Lankan Rupees (LKR)
// Conversion rate: 1 USD = 295 LKR
$vehicles = [
    ['id' => 1, 'name' => 'Porsche 911 GT3', 'model' => '992', 'plate' => 'FLT-001', 'price' => 850 * 295, 'status' => 'available', 'category' => 'Sports', 'vin' => 'FLT-8829-PX'],      // Rs. 250,750
    ['id' => 2, 'name' => 'Range Rover SV', 'model' => 'L405', 'plate' => 'FLT-002', 'price' => 680 * 295, 'status' => 'booked', 'category' => 'Luxury SUV', 'vin' => 'FLT-1142-RR'],        // Rs. 200,600
    ['id' => 3, 'name' => 'BMW 7 Series', 'model' => 'G70', 'plate' => 'FLT-003', 'price' => 450 * 295, 'status' => 'maintenance', 'category' => 'Executive Sedan', 'vin' => 'FLT-5510-BM'],   // Rs. 132,750
    ['id' => 4, 'name' => 'Mercedes S-Class', 'model' => 'W223', 'plate' => 'FLT-004', 'price' => 520 * 295, 'status' => 'available', 'category' => 'Luxury', 'vin' => 'FLT-4432-MB'],        // Rs. 153,400
    ['id' => 5, 'name' => 'Tesla Model S', 'model' => 'Plaid', 'plate' => 'FLT-005', 'price' => 380 * 295, 'status' => 'available', 'category' => 'Electric', 'vin' => 'FLT-9912-TS'],         // Rs. 112,100
];

$total_fleet = count($vehicles);
$total_booked = count(array_filter($vehicles, fn($v) => $v['status'] == 'booked'));
$maintenance_count = count(array_filter($vehicles, fn($v) => $v['status'] == 'maintenance'));
$fleet_health = $total_fleet > 0 ? round(($maintenance_count / $total_fleet) * 100) : 0;
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<main class="ml-64 min-h-screen p-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header Actions - UI 4 Style -->
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
            <div>
                <h1 class="text-4xl font-bold text-gray-900 mb-2">Fleet Inventory</h1>
                <p class="text-gray-600">Manage your luxury vehicle assets and availability status.</p>
            </div>
            <button onclick="openAddModal()" class="bg-red-600 text-white px-6 py-3 rounded-xl font-medium flex items-center gap-2 shadow-lg hover:bg-red-700 transition">
                <span class="material-symbols-outlined">add</span>
                Add New Vehicle
            </button>
        </div>

        <!-- Stats Overview - UI 4 Style -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <span class="material-symbols-outlined text-red-600 bg-red-50 p-2 rounded-lg">directions_car</span>
                    <span class="text-sm text-green-600 bg-green-50 px-2 py-1 rounded">+4%</span>
                </div>
                <div class="text-3xl font-bold"><?php echo $total_fleet; ?></div>
                <div class="text-sm text-gray-500">Total Fleet</div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <span class="material-symbols-outlined text-blue-600 bg-blue-50 p-2 rounded-lg">event_available</span>
                    <span class="text-sm text-blue-600 bg-blue-50 px-2 py-1 rounded">Active</span>
                </div>
                <div class="text-3xl font-bold"><?php echo $total_booked; ?></div>
                <div class="text-sm text-gray-500">Currently Booked</div>
            </div>
            <div class="md:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-sm text-gray-500 uppercase tracking-wider">Fleet Health</div>
                    <span class="material-symbols-outlined text-gray-400">info</span>
                </div>
                <div class="flex items-end justify-between">
                    <div class="flex-1 mr-8">
                        <div class="flex justify-between mb-2">
                            <span class="text-sm">Maintenance Progress</span>
                            <span class="text-sm"><?php echo $maintenance_count; ?> Vehicles</span>
                        </div>
                        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                            <div class="bg-red-600 h-full" style="width: <?php echo $fleet_health; ?>%"></div>
                        </div>
                    </div>
                    <div class="text-4xl font-bold text-red-600"><?php echo $fleet_health; ?>%</div>
                </div>
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
                <table class="w-full text-left border-collapse" id="vehiclesTable">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-6 py-4 text-sm text-gray-500 uppercase tracking-wider">Vehicle Name</th>
                            <th class="px-6 py-4 text-sm text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-4 text-sm text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-4 text-sm text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-sm text-gray-500 uppercase tracking-wider text-right">Daily Rate</th>
                            <th class="px-6 py-4 text-sm text-gray-500 uppercase tracking-wider text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($vehicles as $vehicle): ?>
                        <tr class="hover:bg-gray-50 transition-colors group" data-name="<?php echo strtolower($vehicle['name']); ?>" data-category="<?php echo $vehicle['category']; ?>" data-status="<?php echo $vehicle['status']; ?>">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 rounded-lg bg-gray-100 mr-4 flex items-center justify-center">
                                        <span class="material-symbols-outlined text-3xl text-gray-400">directions_car</span>
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
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold 
                                    <?php echo $vehicle['status'] == 'available' ? 'bg-green-100 text-green-700' : 
                                        ($vehicle['status'] == 'booked' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'); ?>">
                                    <span class="w-1.5 h-1.5 rounded-full mr-2 
                                        <?php echo $vehicle['status'] == 'available' ? 'bg-green-500' : 
                                            ($vehicle['status'] == 'booked' ? 'bg-blue-500' : 'bg-amber-500'); ?>"></span>
                                    <?php echo ucfirst($vehicle['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-red-600">Rs. <?php echo number_format($vehicle['price']); ?></td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center items-center space-x-2">
                                    <button onclick="editVehicle(<?php echo htmlspecialchars(json_encode($vehicle)); ?>)" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500 hover:text-blue-600 transition">
                                        <span class="material-symbols-outlined text-xl">edit</span>
                                    </button>
                                    <button onclick="deleteVehicle(<?php echo $vehicle['id']; ?>)" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500 hover:text-red-600 transition">
                                        <span class="material-symbols-outlined text-xl">delete</span>
                                    </button>
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
        <form id="vehicleForm" class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div><input type="text" id="vehicleName" placeholder="Vehicle Name" class="w-full px-3 py-2 border rounded-lg"></div>
                <div><input type="text" id="vehicleModel" placeholder="Model" class="w-full px-3 py-2 border rounded-lg"></div>
                <div><input type="text" id="vehiclePlate" placeholder="License Plate" class="w-full px-3 py-2 border rounded-lg"></div>
                <div><input type="text" id="vehicleVin" placeholder="VIN Number" class="w-full px-3 py-2 border rounded-lg"></div>
                <div><input type="number" id="vehiclePrice" placeholder="Daily Rate (Rs.)" class="w-full px-3 py-2 border rounded-lg"></div>
                <div>
                    <select id="vehicleCategory" class="w-full px-3 py-2 border rounded-lg">
                        <option value="Sports">Sports Performance</option>
                        <option value="Luxury">Executive Sedan</option>
                        <option value="Luxury SUV">Luxury SUV</option>
                        <option value="Electric">Electric Fleet</option>
                    </select>
                </div>
                <div>
                    <select id="vehicleStatus" class="w-full px-3 py-2 border rounded-lg">
                        <option value="available">Available</option>
                        <option value="booked">Booked</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="saveVehicle()" class="flex-1 bg-red-600 text-white py-2 rounded-lg hover:bg-red-700">Save</button>
                <button type="button" onclick="closeModal()" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-lg hover:bg-gray-300">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Add New Vehicle';
    document.getElementById('vehicleForm').reset();
    document.getElementById('vehicleModal').style.display = 'block';
}

function editVehicle(vehicle) {
    document.getElementById('modalTitle').innerText = 'Edit Vehicle';
    document.getElementById('vehicleName').value = vehicle.name;
    document.getElementById('vehicleModel').value = vehicle.model;
    document.getElementById('vehiclePlate').value = vehicle.plate;
    document.getElementById('vehicleVin').value = vehicle.vin;
    document.getElementById('vehiclePrice').value = vehicle.price;
    document.getElementById('vehicleCategory').value = vehicle.category;
    document.getElementById('vehicleStatus').value = vehicle.status;
    document.getElementById('vehicleModal').style.display = 'block';
}

function deleteVehicle(id) {
    if (confirm('Are you sure you want to delete this vehicle?')) {
        showMessage('Vehicle deleted successfully!', 'success');
    }
}

function saveVehicle() {
    showMessage('Vehicle saved successfully!', 'success');
    closeModal();
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