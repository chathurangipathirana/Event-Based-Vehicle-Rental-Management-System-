<?php
$page_title = 'Event Packages';
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
        $price = (float)($_POST['price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $services = trim($_POST['services'] ?? '');
        $vehicles = trim($_POST['vehicles'] ?? '');
        $status = trim($_POST['status'] ?? 'active');
        if (!in_array($status, ['active', 'draft', 'archived'], true)) {
            $status = 'active';
        }
        $image_url = null;

        // Handle image upload if provided
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['image']['tmp_name'];
            $origName = $_FILES['image']['name'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmp);
            finfo_close($finfo);
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
            if (isset($allowed[$mime])) {
                $ext = $allowed[$mime];
                $uploadDir = __DIR__ . '/uploads/packages/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $dest = $uploadDir . $filename;
                if (move_uploaded_file($tmp, $dest)) {
                    // store web-accessible path relative to admin-site
                    $image_url = 'uploads/packages/' . $filename;
                }
            } else {
                $_SESSION['error'] = 'Invalid image file type. Allowed: JPG, PNG, GIF, WEBP.';
            }
        }

        if (empty($name)) {
            $_SESSION['error'] = 'Package Name is required.';
        } else {
            try {
                if ($id <= 0) {
                    $check = $pdo->prepare("SELECT id FROM event_packages WHERE LOWER(name) = LOWER(?) LIMIT 1");
                    $check->execute([$name]);
                    $existingId = $check->fetchColumn();
                    if ($existingId) {
                        $id = (int)$existingId;
                    }
                }

                if ($id > 0) {
                    if ($image_url === null) {
                        $q = $pdo->prepare("SELECT image_url FROM event_packages WHERE id = ?");
                        $q->execute([$id]);
                        $image_url = $q->fetchColumn();
                    }

                    $stmt = $pdo->prepare("\
                        UPDATE event_packages
                        SET name = ?, description = ?, base_price = ?, included_services = ?, vehicle_types = ?, status = ?, image_url = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $description, $price, $services, $vehicles, $status, $image_url, $id]);
                    $_SESSION['message'] = 'Package updated successfully!';
                } else {
                    // New admin packages are published immediately for the user package page.
                    $status = 'active';
                    $stmt = $pdo->prepare("\
                        INSERT INTO event_packages (name, description, base_price, included_services, vehicle_types, status, image_url)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $description, $price, $services, $vehicles, $status, $image_url]);
                    $_SESSION['message'] = 'Package created successfully!';
                }
            } catch(PDOException $e) {
                $_SESSION['error'] = 'Database error: ' . $e->getMessage();
            }
        }
        header('Location: packages.php');
        exit();
    } elseif ($action === 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM event_packages WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['message'] = 'Package deleted successfully!';
            } catch(PDOException $e) {
                $_SESSION['error'] = 'Database error: ' . $e->getMessage();
            }
        }
        header('Location: packages.php');
        exit();
    }
}

// Fetch event packages from database
$packages = [];
try {
    $packages = $pdo->query("
        SELECT *,
               base_price as price,
               included_services as services,
               vehicle_types as vehicles
        FROM event_packages
        ORDER BY id DESC
    ")->fetchAll();
} catch(PDOException $e) {
    // Keep list empty if tables don't exist
}

// Remove duplicate package names if the database has duplicate rows
$seenPackageNames = [];
$packages = array_values(array_filter($packages, function($pkg) use (&$seenPackageNames) {
    $key = strtolower(trim($pkg['name']));
    if ($key === '') {
        return true;
    }
    if (isset($seenPackageNames[$key])) {
        return false;
    }
    $seenPackageNames[$key] = true;
    return true;
}));

$active_packages = count(array_filter($packages, fn($p) => $p['status'] == 'active'));
$default_package_image = '../user-site/public/assets/vehicles/toyota-premio.png';

function getPackageImageUrl(array $package): string {
    $image_url = trim($package['image_url'] ?? '');

    if (!empty($image_url) && !str_starts_with($image_url, 'http')) {
        if (file_exists(__DIR__ . '/' . $image_url)) {
            return $image_url;
        }
        if (file_exists(__DIR__ . '/../user-site/public/' . ltrim($image_url, '/'))) {
            return '../user-site/public/' . ltrim($image_url, '/');
        }
    }

    $name = strtolower(trim($package['name'] ?? ''));
    $desc = strtolower(trim($package['description'] ?? ''));

    if (str_contains($name, 'wedding') || str_contains($desc, 'wedding') || str_contains($name, 'kandyan')) {
        return '../user-site/public/assets/vehicles/toyota-premio.png';
    }
    if (str_contains($name, 'business') || str_contains($name, 'corporate') || str_contains($desc, 'business') || str_contains($name, 'colombo')) {
        return '../user-site/public/assets/vehicles/honda-vezel.png';
    }
    if (str_contains($name, 'gala') || str_contains($name, 'tour') || str_contains($desc, 'tour') || str_contains($name, 'galle')) {
        return '../user-site/public/assets/vehicles/toyota-hiace.png';
    }

    return '../user-site/public/assets/vehicles/toyota-axio.png';
}

// If there are no packages in the database, provide a small set of sample packages
if (empty($packages)) {
    $packages = [
        [
            'id' => 101,
            'name' => 'Kandyan Wedding Premium',
            'description' => 'Elite wedding transportation for traditional ceremonies, featuring decorated sedans and VIP guest shuttles.',
            'price' => 737500,
            'services' => 'Decorated wedding cars,VIP chauffeur,Guest shuttle',
            'vehicles' => 'Toyota Premio,Toyota Axio',
            'status' => 'active',
            'image_url' => 'https://images.unsplash.com/photo-1511919884226-fd3cad34687c?auto=format&fit=crop&w=1400&q=80'
        ],
        [
            'id' => 102,
            'name' => 'Colombo Business Pro',
            'description' => 'Corporate transfer services for Colombo meetings and airport pickups with premium executive cars.',
            'price' => 531000,
            'services' => 'Airport transfer,Executive sedan,Meeting transport',
            'vehicles' => 'Honda Vezel,Toyota Premio',
            'status' => 'active',
            'image_url' => 'https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?auto=format&fit=crop&w=1400&q=80'
        ],
        [
            'id' => 103,
            'name' => 'Galle Gala Elite',
            'description' => 'Premium gala transport for coastal events, including luxury SUVs and VIP coordination.',
            'price' => 944000,
            'services' => 'VIP coordination,Red carpet service,Luxury SUVs',
            'vehicles' => 'Toyota HiAce,Honda Vezel',
            'status' => 'active',
            'image_url' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=1400&q=80'
        ],
    ];
}
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Royal Lanka Rides Admin | Event Packages</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<style>
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    :root { --surface:#f9f9fa; --surface-low:#f3f4f4; --surface-card:#ffffff; --surface-high:#e7e8e9; --primary:#02414a; --primary-soft:#b8ebf7; --primary-hover:#0d5260; --outline:#c0c8ca; --text:#191c1d; --muted:#40484a; --success:#176a3a; --warning:#8a5200; --danger:#ba1a1a; }
    body { font-family: 'Inter', sans-serif; background-color: var(--surface); color: var(--text); }
    .bg-white { background-color: var(--surface-card) !important; }
    .bg-gray-50, .hover\:bg-gray-50:hover { background-color: var(--surface-low) !important; }
    .bg-gray-100, .bg-gray-200 { background-color: var(--surface-high) !important; }
    .border-gray-100, .border-gray-200, .border-gray-300 { border-color: var(--outline) !important; }
    .text-gray-900, .text-gray-700 { color: var(--text) !important; }
    .text-gray-600, .text-gray-500, .text-gray-400 { color: var(--muted) !important; }
    .bg-red-600, .bg-gray-900 { background-color: var(--primary) !important; }
    .bg-red-50, .bg-red-100 { background-color: var(--primary-soft) !important; }
    .hover\:bg-red-700:hover, .hover\:bg-black:hover { background-color: var(--primary-hover) !important; }
    .text-red-600 { color: var(--primary) !important; }
    [class*="bg-red-"] { background-color: var(--primary) !important; }
    [class*="text-red-"] { color: var(--primary) !important; }
    [class*="border-red-"] { border-color: var(--primary-soft) !important; }
    [class*="hover:bg-red-"]:hover { background-color: var(--primary-hover) !important; }
    [class*="hover:text-red-"]:hover { color: var(--primary-hover) !important; }
    .bg-red-50, .bg-red-100 { background-color: var(--primary-soft) !important; }
    .focus\:ring-red-500:focus { --tw-ring-color: rgba(2,65,74,0.18) !important; border-color: var(--primary) !important; }
    .text-green-600, .text-green-700 { color: var(--success) !important; }
    .bg-green-100 { background-color: #dff5e8 !important; }
    .text-yellow-700, .text-orange-600 { color: var(--warning) !important; }
    .bg-yellow-100 { background-color: #fff3d6 !important; }
    .rounded-xl { border-radius: 0.75rem !important; }
    .rounded-lg { border-radius: 0.5rem !important; }
    .shadow-lg, .shadow-md, .shadow-sm { box-shadow: 0 10px 24px rgba(25,28,29,0.06) !important; }
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(2,65,74,0.45); z-index: 1000; overflow-y: auto; }
    .modal-content { background: var(--surface-card); margin: 50px auto; max-width: 600px; border-radius: 12px; border: 1px solid var(--outline); box-shadow: 0 24px 60px rgba(25,28,29,0.18); }
</style>
</head>
<body class="bg-gray-50">

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
                        <p class="text-xs uppercase tracking-[0.35em] text-slate-400 mb-4">Vehicle Manager</p>
                        <h1 class="text-5xl font-semibold tracking-tight">Event Packages</h1>
                        <p class="mt-4 text-slate-300 text-lg leading-8">Configure premium fleet service bundles for corporate and private events.</p>
                    </div>
                    <div class="flex flex-wrap justify-end gap-3">
                        <button onclick="openAddModal()" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-cyan-500 text-white text-sm font-semibold hover:bg-cyan-400 transition-all">
                            <span class="material-symbols-outlined text-sm">add</span>
                            Create Package
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Dashboard Grid - UI 6 Style -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
            <?php foreach ($packages as $package): ?>
            <!-- Package Card -->
            <div class="md:col-span-4 bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition overflow-hidden flex flex-col group">
                <?php $pkgImage = getPackageImageUrl($package); ?>
                <div class="h-48 relative overflow-hidden bg-gray-100 flex items-center justify-center">
                    <img src="<?php echo htmlspecialchars($pkgImage); ?>" alt="<?php echo htmlspecialchars($package['name']); ?>" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='<?php echo htmlspecialchars($default_package_image, ENT_QUOTES); ?>'">
                    <div class="absolute top-4 right-4 bg-white/90 backdrop-blur px-3 py-1 rounded text-sm text-red-600 shadow-sm">
                        LKR <?php echo number_format($package['price'], 2); ?>/BASE
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
                        <button onclick="viewDetails(<?php echo htmlspecialchars(json_encode($package)); ?>)" class="flex-1 border border-gray-300 text-gray-700 hover:bg-gray-50 py-2 rounded-lg text-sm transition">View Details</button>
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
                <form method="POST" action="packages.php" class="bg-gray-100 p-6 rounded-xl border border-gray-200">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="quickId" value="0">
                    <h4 class="text-2xl font-bold text-gray-900 mb-6">Quick Edit Context</h4>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm text-gray-700 mb-2">Package Name</label>
                            <input type="text" name="name" id="quickName" class="w-full bg-white border border-gray-200 rounded px-4 py-2 focus:ring-1 focus:ring-red-500" required>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-2">Base Price (LKR)</label>
                            <input type="number" name="price" id="quickPrice" class="w-full bg-white border border-gray-200 rounded px-4 py-2 focus:ring-1 focus:ring-red-500" required>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-2">Description</label>
                            <textarea name="description" id="quickDesc" rows="3" class="w-full bg-white border border-gray-200 rounded px-4 py-2 focus:ring-1 focus:ring-red-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-2">Status</label>
                            <select name="status" id="quickStatus" class="w-full bg-white border border-gray-200 rounded px-4 py-2">
                                <option value="active">Active</option>
                                <option value="draft">Draft</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                        <button type="submit" class="w-full bg-red-600 text-white py-3 rounded-lg font-medium mt-2 hover:bg-red-700 transition">Save Changes</button>
                    </div>
                </form>
            </div>

            <!-- Data Table - UI 6 Style -->
            <div class="md:col-span-12 bg-white rounded-xl border border-[#c0c8ca] shadow-sm mt-6 overflow-hidden">
                <div class="px-6 py-4 border-b border-[#c0c8ca] flex justify-between items-center bg-gray-50">
                    <h3 class="text-xl font-bold text-gray-900">All Service Packages</h3>
                    <div class="flex gap-2">
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg">search</span>
                            <input type="text" id="tableSearch" class="pl-10 pr-4 py-2 bg-white border border-gray-200 rounded text-sm" placeholder="Search packages...">
                        </div>
                    </div>
                </div>
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
                            background-color: #1e293b;
                            color: #ffffff;
                            font-weight: 700;
                            border-right: 1px solid rgba(255,255,255,0.2);
                        }
                        .dashboard-table thead th:last-child {
                            border-right: none;
                        }
                    </style>
                    <table class="w-full dashboard-table" id="packagesTable">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Package</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Base Price</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Vehicles</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Services</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#c0c8ca]/30 text-[#191c1d]">
                            <?php foreach ($packages as $package): ?>
                            <tr data-name="<?php echo strtolower($package['name']); ?>">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-12 h-12 rounded-lg bg-slate-900 overflow-hidden flex items-center justify-center border border-gray-200 mr-3 flex-shrink-0">
                                            <?php $tablePkgImg = getPackageImageUrl($package); ?>
                                            <img src="<?php echo htmlspecialchars($tablePkgImg); ?>" alt="<?php echo htmlspecialchars($package['name']); ?>" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='../user-site/public/assets/vehicle-default.svg'">
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($package['name']); ?></div>
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

<!-- View Details Modal -->
<div id="detailsModal" class="modal">
    <div class="modal-content mx-4 p-6 space-y-4">
        <div class="flex justify-between items-start border-b pb-4">
            <div>
                <h3 id="detailsName" class="text-2xl font-bold text-gray-900">Package Name</h3>
                <span id="detailsStatus" class="inline-block mt-2 px-2 py-1 text-xs rounded-full bg-green-100 text-green-700 uppercase font-semibold">Active</span>
            </div>
            <button onclick="closeDetailsModal()" class="text-gray-400 hover:text-gray-600">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="space-y-4">
            <div>
                <h4 class="text-xs uppercase tracking-widest text-gray-500 font-semibold mb-1">Base Price</h4>
                <p id="detailsPrice" class="text-lg font-bold text-gray-900">LKR 0.00</p>
            </div>
            <div>
                <h4 class="text-xs uppercase tracking-widest text-gray-500 font-semibold mb-1">Description</h4>
                <p id="detailsDesc" class="text-gray-600 text-sm leading-relaxed"></p>
            </div>
            <div>
                <h4 class="text-xs uppercase tracking-widest text-gray-500 font-semibold mb-1">Included Services</h4>
                <ul id="detailsServices" class="list-disc list-inside text-gray-600 text-sm space-y-1">
                </ul>
            </div>
            <div>
                <h4 class="text-xs uppercase tracking-widest text-gray-500 font-semibold mb-1">Vehicle Types</h4>
                <p id="detailsVehicles" class="text-gray-600 text-sm"></p>
            </div>
        </div>
        <div class="pt-4 border-t flex gap-3">
            <button id="detailsEditBtn" class="flex-1 bg-gray-900 text-white hover:bg-black py-2 rounded-lg text-sm transition">Edit Package</button>
            <button onclick="closeDetailsModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 rounded-lg text-sm transition">Close</button>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="packageModal" class="modal">
    <div class="modal-content mx-4">
        <div class="p-6 border-b">
            <h3 id="modalTitle" class="text-xl font-bold">Add Package</h3>
        </div>
        <form id="packageForm" method="POST" action="packages.php" class="p-6 space-y-4" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="pkgId" value="0">
            <input type="text" name="name" id="pkgName" placeholder="Package Name" required class="w-full px-3 py-2 border rounded-lg">
            <input type="number" name="price" id="pkgPrice" placeholder="Base Price (LKR)" required class="w-full px-3 py-2 border rounded-lg">
            <textarea name="description" id="pkgDesc" rows="3" placeholder="Description" class="w-full px-3 py-2 border rounded-lg"></textarea>
            <textarea name="services" id="pkgServices" rows="2" placeholder="Included Services (comma separated)" class="w-full px-3 py-2 border rounded-lg"></textarea>
            <input type="text" name="vehicles" id="pkgVehicles" placeholder="Vehicle Types (comma separated)" class="w-full px-3 py-2 border rounded-lg">
            <select name="status" id="pkgStatus" class="w-full px-3 py-2 border rounded-lg">
                <option value="active">Active</option>
                <option value="draft">Draft</option>
                <option value="archived">Archived</option>
            </select>
            <div>
                <label class="block text-sm text-gray-700 mb-2">Package Image</label>
                <input type="file" name="image" id="pkgImage" accept="image/*" class="w-full">
                <p class="text-xs text-gray-400 mt-1">Upload an image (JPG, PNG, GIF, WEBP). Leave blank to keep existing.</p>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-red-600 text-white py-2 rounded-lg">Save</button>
                <button type="button" id="modalDeleteBtn" onclick="deletePackage(document.getElementById('pkgId').value)" class="flex-1 bg-red-100 text-red-600 hover:bg-red-200 py-2 rounded-lg" style="display: none;">Delete</button>
                <button type="button" onclick="closeModal()" class="flex-1 bg-gray-200 py-2 rounded-lg">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').innerText = 'Add Package';
    document.getElementById('pkgId').value = 0;
    document.getElementById('modalDeleteBtn').style.display = 'none';
    document.getElementById('packageForm').reset();
    document.getElementById('packageModal').style.display = 'block';
}

function editPackage(pkg) {
    document.getElementById('modalTitle').innerText = 'Edit Package';
    document.getElementById('pkgId').value = pkg.id;
    document.getElementById('pkgName').value = pkg.name;
    document.getElementById('pkgPrice').value = pkg.price;
    document.getElementById('pkgDesc').value = pkg.description;
    document.getElementById('pkgServices').value = pkg.services;
    document.getElementById('pkgVehicles').value = pkg.vehicles;
    document.getElementById('pkgStatus').value = pkg.status;

    document.getElementById('quickId').value = pkg.id;
    document.getElementById('quickName').value = pkg.name;
    document.getElementById('quickPrice').value = pkg.price;
    document.getElementById('quickDesc').value = pkg.description;
    document.getElementById('quickStatus').value = pkg.status;

    document.getElementById('modalDeleteBtn').style.display = 'block';
    document.getElementById('packageModal').style.display = 'block';
}

function deletePackage(id) {
    if (confirm('Are you sure you want to delete this package?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'packages.php';

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

function viewDetails(pkg) {
    document.getElementById('detailsName').innerText = pkg.name;
    document.getElementById('detailsStatus').innerText = pkg.status.toUpperCase();

    const statusEl = document.getElementById('detailsStatus');
    statusEl.className = 'inline-block mt-2 px-2 py-1 text-xs rounded-full uppercase font-semibold ' +
        (pkg.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700');

    document.getElementById('detailsPrice').innerText = 'LKR ' + parseFloat(pkg.price).toLocaleString(undefined, {minimumFractionDigits: 2});
    document.getElementById('detailsDesc').innerText = pkg.description || 'No description provided.';
    document.getElementById('detailsVehicles').innerText = pkg.vehicles || 'None specified.';

    const servicesList = document.getElementById('detailsServices');
    servicesList.innerHTML = '';
    if (pkg.services) {
        pkg.services.split(',').forEach(service => {
            const li = document.createElement('li');
            li.innerText = service.trim();
            servicesList.appendChild(li);
        });
    } else {
        servicesList.innerHTML = '<li class="italic text-gray-400">No services specified.</li>';
    }

    document.getElementById('detailsEditBtn').onclick = function() {
        closeDetailsModal();
        editPackage(pkg);
    };

    document.getElementById('detailsModal').style.display = 'block';
}

function closeDetailsModal() {
    document.getElementById('detailsModal').style.display = 'none';
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
