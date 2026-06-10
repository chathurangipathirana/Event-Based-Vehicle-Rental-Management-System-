<?php
$page_title = 'Event Packages';
require_once 'includes/auth.php';
requireAdminLogin();

// Package prices converted to Sri Lankan Rupees (LKR)
// Conversion rate: 1 USD = 295 LKR
$packages = [
    ['id' => 1, 'name' => 'Wedding Premium', 'price' => 2500 * 295, 'description' => 'The ultimate luxury experience for high-profile weddings.', 'services' => '3 Luxury Sedans + 1 Stretch Limousine,8-Hour Chauffeur Service,Champagne & Concierge', 'vehicles' => 'Sedan,Limousine', 'status' => 'active'],   // LKR 737,500
    ['id' => 2, 'name' => 'Business Pro', 'price' => 1800 * 295, 'description' => 'Optimized logistics for corporate summits.', 'services' => '5 Executive SUVs,Airport Transfer Coordination,Real-time Fleet Tracking', 'vehicles' => 'SUV', 'status' => 'active'],   // LKR 531,000
    ['id' => 3, 'name' => 'Gala Elite', 'price' => 3200 * 295, 'description' => 'Premium gala night package with multiple arrival points.', 'services' => 'Red Carpet Service,Multiple Arrival Points,VIP Coordination', 'vehicles' => 'Sedan,Limo,Bus', 'status' => 'draft'],   // LKR 944,000
];

$active_packages = count(array_filter($packages, fn($p) => $p['status'] == 'active'));
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>FleetElite Admin | Event Packages</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<style>
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    body { font-family: 'Inter', sans-serif; background-color: #f7f9fb; }
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto; }
    .modal-content { background: white; margin: 50px auto; max-width: 600px; border-radius: 12px; }
</style>
</head>
<body class="bg-gray-50">

<?php require_once 'includes/sidebar.php'; ?>

<main class="ml-64 min-h-screen p-8">
    <div class="max-w-7xl mx-auto">
        <!-- Page Header - UI 6 Style -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8">
            <div>
                <h1 class="text-4xl font-bold text-gray-900 mb-2">Event Packages</h1>
                <p class="text-gray-600">Configure premium fleet service bundles for corporate and private events.</p>
            </div>
            <button onclick="openAddModal()" class="mt-4 md:mt-0 flex items-center bg-red-600 text-white px-6 py-3 rounded-lg font-medium shadow-lg hover:bg-red-700 transition">
                <span class="material-symbols-outlined mr-2">add</span>
                Create New Package
            </button>
        </div>

        <!-- Dashboard Grid - UI 6 Style -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
            <?php foreach ($packages as $package): ?>
            <!-- Package Card -->
            <div class="md:col-span-4 bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition overflow-hidden flex flex-col group">
                <div class="h-48 relative overflow-hidden bg-gray-100 flex items-center justify-center">
                    <span class="material-symbols-outlined text-6xl text-gray-400">celebration</span>
                    <div class="absolute top-4 right-4 bg-white/90 backdrop-blur px-3 py-1 rounded text-sm text-red-600 shadow-sm">
                        LKR <?php echo number_format($package['price'], 0); ?>/BASE
                    </div>
                </div>
                <div class="p-6 flex-1 flex flex-col">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-2xl font-bold text-gray-900"><?php echo $package['name']; ?></h3>
                        <button onclick="editPackage(<?php echo htmlspecialchars(json_encode($package)); ?>)" class="text-gray-400 hover:text-gray-600">
                            <span class="material-symbols-outlined">more_vert</span>
                        </button>
                    </div>
                    <p class="text-gray-600 mb-6 line-clamp-2"><?php echo $package['description']; ?></p>
                    <div class="space-y-3 mb-6">
                        <?php $services = explode(',', $package['services']); ?>
                        <?php foreach (array_slice($services, 0, 3) as $service): ?>
                        <div class="flex items-center text-sm text-gray-700">
                            <span class="material-symbols-outlined text-red-600 mr-2 text-xl" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                            <?php echo trim($service); ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-auto flex gap-3">
                        <button onclick="viewDetails(<?php echo $package['id']; ?>)" class="flex-1 border border-gray-300 text-gray-700 hover:bg-gray-50 py-2 rounded-lg text-sm transition">View Details</button>
                        <button onclick="editPackage(<?php echo htmlspecialchars(json_encode($package)); ?>)" class="flex-1 bg-gray-900 text-white hover:bg-black py-2 rounded-lg text-sm transition">Edit Package</button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Quick Stats Card - UI 6 Style -->
            <div class="md:col-span-4 space-y-6">
                <div class="bg-red-600 text-white p-6 rounded-xl shadow-lg relative overflow-hidden">
                    <div class="relative z-10">
                        <h4 class="text-sm uppercase tracking-widest opacity-80 mb-2">Active Packages</h4>
                        <div class="text-4xl font-bold mb-4"><?php echo $active_packages; ?></div>
                        <div class="flex items-center text-sm bg-white/20 w-fit px-2 py-1 rounded">
                            <span class="material-symbols-outlined text-sm mr-1">trending_up</span>
                            +12% from last month
                        </div>
                    </div>
                    <span class="material-symbols-outlined absolute -bottom-4 -right-4 text-white/10 text-[120px] rotate-12">package_2</span>
                </div>

                <!-- Quick Edit Panel - UI 6 Style -->
                <div class="bg-gray-100 p-6 rounded-xl border border-gray-200">
                    <h4 class="text-2xl font-bold text-gray-900 mb-6">Quick Edit Context</h4>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm text-gray-700 mb-2">Package Name</label>
                            <input type="text" id="quickName" class="w-full bg-white border border-gray-200 rounded px-4 py-2 focus:ring-1 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-2">Base Price (LKR)</label>
                            <input type="number" id="quickPrice" class="w-full bg-white border border-gray-200 rounded px-4 py-2 focus:ring-1 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-2">Description</label>
                            <textarea id="quickDesc" rows="3" class="w-full bg-white border border-gray-200 rounded px-4 py-2 focus:ring-1 focus:ring-red-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-2">Status</label>
                            <select id="quickStatus" class="w-full bg-white border border-gray-200 rounded px-4 py-2">
                                <option value="active">Active</option>
                                <option value="draft">Draft</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                        <button onclick="quickSave()" class="w-full bg-red-600 text-white py-3 rounded-lg font-medium mt-2 hover:bg-red-700 transition">Save Changes</button>
                    </div>
                </div>
            </div>

            <!-- Data Table - UI 6 Style -->
            <div class="md:col-span-12 bg-white rounded-xl border border-gray-100 shadow-sm mt-6 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="text-xl font-bold text-gray-900">All Service Packages</h3>
                    <div class="flex gap-2">
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg">search</span>
                            <input type="text" id="tableSearch" class="pl-10 pr-4 py-2 bg-white border border-gray-200 rounded text-sm" placeholder="Search packages...">
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left" id="packagesTable">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 border-b border-gray-100">
                                <th class="px-6 py-4 text-sm uppercase tracking-wider">Package</th>
                                <th class="px-6 py-4 text-sm uppercase tracking-wider">Base Price</th>
                                <th class="px-6 py-4 text-sm uppercase tracking-wider">Vehicles</th>
                                <th class="px-6 py-4 text-sm uppercase tracking-wider">Services</th>
                                <th class="px-6 py-4 text-sm uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-sm uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($packages as $package): ?>
                            <tr class="hover:bg-gray-50" data-name="<?php echo strtolower($package['name']); ?>">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 rounded bg-red-50 flex items-center justify-center text-red-600 font-bold mr-3">
                                            <?php echo strtoupper(substr($package['name'], 0, 2)); ?>
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900"><?php echo $package['name']; ?></div>
                                            <div class="text-xs text-gray-500">Updated <?php echo date('M d'); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">LKR <?php echo number_format($package['price'], 2); ?></td>
                                <td class="px-6 py-4"><?php echo $package['vehicles']; ?></td>
                                <td class="px-6 py-4 text-sm"><?php echo substr($package['services'], 0, 40); ?>...</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full <?php echo $package['status'] == 'active' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'; ?>">
                                        <?php echo strtoupper($package['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button onclick="editPackage(<?php echo htmlspecialchars(json_encode($package)); ?>)" class="text-gray-500 hover:text-blue-600 mr-2">
                                        <span class="material-symbols-outlined text-xl">edit</span>
                                    </button>
                                    <button onclick="deletePackage(<?php echo $package['id']; ?>)" class="text-gray-500 hover:text-red-600">
                                        <span class="material-symbols-outlined text-xl">delete</span>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Add/Edit Modal -->
<div id="packageModal" class="modal">
    <div class="modal-content mx-4">
        <div class="p-6 border-b">
            <h3 id="modalTitle" class="text-xl font-bold">Add Package</h3>
        </div>
        <form class="p-6 space-y-4">
            <input type="text" id="pkgName" placeholder="Package Name" class="w-full px-3 py-2 border rounded-lg">
            <input type="number" id="pkgPrice" placeholder="Base Price (LKR)" class="w-full px-3 py-2 border rounded-lg">
            <textarea id="pkgDesc" rows="3" placeholder="Description" class="w-full px-3 py-2 border rounded-lg"></textarea>
            <textarea id="pkgServices" rows="2" placeholder="Included Services (comma separated)" class="w-full px-3 py-2 border rounded-lg"></textarea>
            <input type="text" id="pkgVehicles" placeholder="Vehicle Types (comma separated)" class="w-full px-3 py-2 border rounded-lg">
            <select id="pkgStatus" class="w-full px-3 py-2 border rounded-lg">
                <option value="active">Active</option>
                <option value="draft">Draft</option>
                <option value="archived">Archived</option>
            </select>
            <div class="flex gap-3">
                <button type="button" onclick="savePackage()" class="flex-1 bg-red-600 text-white py-2 rounded-lg">Save</button>
                <button type="button" onclick="closeModal()" class="flex-1 bg-gray-200 py-2 rounded-lg">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Add Package';
    document.getElementById('packageModal').style.display = 'block';
}

function editPackage(pkg) {
    document.getElementById('modalTitle').innerText = 'Edit Package';
    document.getElementById('pkgName').value = pkg.name;
    document.getElementById('pkgPrice').value = pkg.price;
    document.getElementById('pkgDesc').value = pkg.description;
    document.getElementById('pkgServices').value = pkg.services;
    document.getElementById('pkgVehicles').value = pkg.vehicles;
    document.getElementById('pkgStatus').value = pkg.status;
    
    document.getElementById('quickName').value = pkg.name;
    document.getElementById('quickPrice').value = pkg.price;
    document.getElementById('quickDesc').value = pkg.description;
    document.getElementById('quickStatus').value = pkg.status;
    
    document.getElementById('packageModal').style.display = 'block';
}

function deletePackage(id) {
    if (confirm('Delete this package?')) alert('Package deleted');
}

function savePackage() {
    alert('Package saved!');
    closeModal();
}

function quickSave() {
    alert('Changes saved!');
}

function viewDetails(id) {
    alert('Package details view');
}

function closeModal() {
    document.getElementById('packageModal').style.display = 'none';
}

document.getElementById('tableSearch')?.addEventListener('keyup', function(e) {
    const term = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#packagesTable tbody tr');
    rows.forEach(row => {
        const name = row.getAttribute('data-name') || '';
        row.style.display = name.includes(term) ? '' : 'none';
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>