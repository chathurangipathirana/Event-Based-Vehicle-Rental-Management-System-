<?php
$page_title = 'Driver Management';
require_once 'includes/auth.php';
requireAdminLogin();
require_once 'config/database.php';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $rating = (float)($_POST['rating'] ?? 5.0);
        $status = trim($_POST['status'] ?? 'available');

        if (empty($name)) {
            $_SESSION['error'] = 'Driver Name is required.';
        } else {
            try {
                if ($id > 0) {
                    $stmt = $pdo->prepare("
                        UPDATE drivers
                        SET name = ?, email = ?, phone = ?, rating = ?, rating_level = ?, status = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $email, $phone, $rating, round($rating), $status, $id]);
                    $_SESSION['message'] = 'Driver updated successfully!';
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO drivers (name, email, phone, rating, rating_level, status)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $email, $phone, $rating, round($rating), $status]);
                    $_SESSION['message'] = 'Driver added successfully!';
                }
            } catch(PDOException $e) {
                $_SESSION['error'] = 'Database error: ' . $e->getMessage();
            }
        }
        header('Location: drivers.php');
        exit();
    } elseif ($action === 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM drivers WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['message'] = 'Driver deleted successfully!';
            } catch(PDOException $e) {
                $_SESSION['error'] = 'Database error: ' . $e->getMessage();
            }
        }
        header('Location: drivers.php');
        exit();
    }
}

// Fetch all drivers
$drivers = [];
try {
    $drivers = $pdo->query("SELECT * FROM drivers ORDER BY id DESC")->fetchAll();
} catch(PDOException $e) {
    // Suppress error
}

$total_drivers = count($drivers);
$available_count = count(array_filter($drivers, fn($d) => $d['status'] == 'available'));
$on_duty_count = count(array_filter($drivers, fn($d) => $d['status'] == 'on_duty'));
$off_duty_count = count(array_filter($drivers, fn($d) => $d['status'] == 'off_duty'));
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

        <!-- Header Hero Section -->
        <section class="rounded-[2rem] overflow-hidden mb-10">
            <div class="relative bg-slate-900 text-white p-8 lg:p-10 overflow-hidden">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.24),_transparent_36%)] opacity-70 pointer-events-none"></div>
                <div class="relative grid grid-cols-1 xl:grid-cols-[1.5fr_1fr] gap-8 items-center">
                    <div class="max-w-2xl">
                        <p class="text-xs uppercase tracking-[0.35em] text-slate-400 mb-4">Operations</p>
                        <h1 class="text-5xl font-semibold tracking-tight">Driver Management</h1>
                        <p class="mt-4 text-slate-300 text-lg leading-8">Manage company drivers, contact information, performance ratings, and current duty status.</p>
                    </div>
                    <div class="flex flex-wrap justify-end gap-3">
                        <button type="button" onclick="openDriverAddModal()" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-cyan-500 text-white text-sm font-semibold hover:bg-cyan-400 transition-all">
                            <span class="material-symbols-outlined text-sm">add</span>
                            Add Driver
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 justify-center gap-6 mb-8">
            <div class="card-3d p-6 pr-24 min-h-[140px] bg-white" style="--card-accent: #0b6b6d;">
                <div>
                    <p class="text-sm text-gray-500 uppercase mb-3">Total Drivers</p>
                    <div class="text-3xl font-extrabold text-gray-900"><?php echo $total_drivers; ?></div>
                </div>
                <div class="card-icon !right-7 bg-cyan-600 text-white p-3 rounded-xl absolute top-6 right-6 flex items-center justify-center"><span class="material-symbols-outlined">group</span></div>
            </div>
            <div class="card-3d p-6 pr-24 min-h-[140px] bg-white" style="--card-accent: #176a3a;">
                <div>
                    <p class="text-sm text-gray-500 uppercase mb-3">Available Drivers</p>
                    <div class="text-3xl font-extrabold text-green-600"><?php echo $available_count; ?></div>
                </div>
                <div class="card-icon !right-7 bg-green-600 text-white p-3 rounded-xl absolute top-6 right-6 flex items-center justify-center"><span class="material-symbols-outlined">person_pin</span></div>
            </div>
            <div class="card-3d p-6 pr-24 min-h-[140px] bg-white" style="--card-accent: #ba1a1a;">
                <div>
                    <p class="text-sm text-gray-500 uppercase mb-3">On Duty</p>
                    <div class="text-3xl font-extrabold text-red-600"><?php echo $on_duty_count; ?></div>
                </div>
                <div class="card-icon !right-7 bg-red-600 text-white p-3 rounded-xl absolute top-6 right-6 flex items-center justify-center"><span class="material-symbols-outlined">work_history</span></div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-6 p-4">
            <div class="flex flex-col lg:flex-row gap-4">
                <div class="flex-1 relative">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">search</span>
                    <input type="text" id="searchInput" class="w-full pl-12 pr-4 py-3 rounded-xl border-gray-200 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500" placeholder="Search by name, email, or phone...">
                </div>
                <div class="flex gap-4">
                    <select id="statusFilter" class="rounded-xl border-gray-200 px-6 py-3 min-w-[160px]">
                        <option value="">All Statuses</option>
                        <option value="available">Available</option>
                        <option value="on_duty">On Duty</option>
                        <option value="off_duty">Off Duty</option>
                    </select>
                    <button onclick="applyFilters()" class="bg-cyan-500 text-white hover:bg-cyan-600 px-5 py-3 rounded-xl flex items-center transition font-semibold">
                        <span class="material-symbols-outlined mr-2">filter_alt</span> Apply Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Driver List Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="driversTable">
                    <thead>
                        <tr class="bg-slate-900 text-white">
                            <th class="px-6 py-4 text-sm font-semibold uppercase">Driver Name</th>
                            <th class="px-6 py-4 text-sm font-semibold uppercase">Contact Information</th>
                            <th class="px-6 py-4 text-sm font-semibold uppercase text-center">Rating</th>
                            <th class="px-6 py-4 text-sm font-semibold uppercase">Status</th>
                            <th class="px-6 py-4 text-sm font-semibold uppercase text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($drivers)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500 italic">No drivers found in the system. Click "Add Driver" to create one.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($drivers as $driver): ?>
                        <tr class="hover:bg-slate-50 transition" data-name="<?php echo strtolower($driver['name']); ?>" data-status="<?php echo $driver['status']; ?>">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full bg-cyan-100 text-cyan-800 font-extrabold mr-3 flex items-center justify-center text-sm uppercase">
                                        <?php echo strtoupper(substr($driver['name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900 text-base"><?php echo htmlspecialchars($driver['name']); ?></div>
                                        <div class="text-xs text-gray-400">Added: <?php echo date('M d, Y', strtotime($driver['created_at'])); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-700"><?php echo htmlspecialchars($driver['email'] ?: 'No email'); ?></div>
                                <div class="text-xs text-gray-500 font-medium"><?php echo htmlspecialchars($driver['phone'] ?: 'No phone'); ?></div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="inline-flex items-center gap-1 bg-yellow-50 text-yellow-700 font-bold px-2.5 py-1 rounded-lg text-sm border border-yellow-200">
                                    <span class="material-symbols-outlined text-sm font-fill">star</span>
                                    <?php echo number_format($driver['rating'], 2); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $status_colors = [
                                    'available' => 'bg-green-100 text-green-700 border-green-200',
                                    'on_duty' => 'bg-blue-100 text-blue-700 border-blue-200',
                                    'off_duty' => 'bg-red-100 text-red-700 border-red-200'
                                ];
                                $col = $status_colors[$driver['status']] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                                ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border <?php echo $col; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $driver['status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button type="button" onclick='openDriverEditModal(<?php echo htmlspecialchars(json_encode($driver), ENT_QUOTES); ?>)' class="w-10 h-10 inline-flex items-center justify-center rounded-xl border border-gray-200 text-cyan-600 hover:bg-cyan-50 transition" title="Edit" aria-label="Edit driver">
                                        <span class="material-symbols-outlined text-xl leading-none">edit</span>
                                    </button>
                                    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this driver?');" class="inline-block">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $driver['id']; ?>">
                                        <button
                                            type="submit"
                                            class="w-10 h-10 inline-flex items-center justify-center rounded-xl border transition"
                                            style="color: #dc2626; border-color: #fecaca; background-color: #fef2f2;"
                                            onmouseover="this.style.backgroundColor='#fee2e2'; this.style.borderColor='#fca5a5';"
                                            onmouseout="this.style.backgroundColor='#fef2f2'; this.style.borderColor='#fecaca';"
                                            title="Delete"
                                            aria-label="Delete driver"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1L5 6" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 11v6" />
                                            </svg>
                                        </button>
                                    </form>
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

<!-- Add / Edit Driver Modal -->
<div id="driverModal" class="fixed inset-0 z-50 overflow-y-auto hidden bg-black/50 backdrop-blur-sm items-center justify-center p-4">
    <div class="bg-white rounded-3xl w-full max-w-lg shadow-xl overflow-hidden border border-gray-100">
        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center">
            <h3 id="modalTitle" class="text-xl font-bold text-gray-900">Add Driver</h3>
            <button type="button" onclick="closeDriverModal()" class="text-gray-400 hover:text-gray-600" aria-label="Close driver modal">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" action="" class="p-6 space-y-4">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="driverId" value="0">

            <div>
                <label class="block text-xs uppercase tracking-wider font-semibold text-gray-400 mb-1.5">Driver Full Name *</label>
                <input type="text" name="name" id="driverName" required class="w-full px-3 py-2.5 border border-gray-200 rounded-xl focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 bg-white" placeholder="e.g. John Doe">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs uppercase tracking-wider font-semibold text-gray-400 mb-1.5">Email Address</label>
                    <input type="email" name="email" id="driverEmail" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 bg-white" placeholder="e.g. john@email.com">
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wider font-semibold text-gray-400 mb-1.5">Phone Number</label>
                    <input type="text" name="phone" id="driverPhone" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 bg-white" placeholder="e.g. +94 77 123 4567">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs uppercase tracking-wider font-semibold text-gray-400 mb-1.5">Driver Rating (1-5)</label>
                    <input type="number" step="0.1" min="1" max="5" name="rating" id="driverRating" value="5.0" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 bg-white">
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wider font-semibold text-gray-400 mb-1.5">Duty Status</label>
                    <select name="status" id="driverStatus" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500 bg-white">
                        <option value="available">Available</option>
                        <option value="on_duty">On Duty</option>
                        <option value="off_duty">Off Duty</option>
                    </select>
                </div>
            </div>

            <div class="pt-4 border-t border-gray-100 flex gap-3">
                <button type="submit" class="flex-1 bg-cyan-500 hover:bg-cyan-600 text-white font-semibold py-3 rounded-xl transition shadow-sm">Save Driver</button>
                <button type="button" onclick="closeDriverModal()" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 rounded-xl transition">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openDriverAddModal() {
    document.getElementById('modalTitle').innerText = 'Add Driver';
    document.getElementById('driverId').value = 0;
    document.getElementById('driverName').value = '';
    document.getElementById('driverEmail').value = '';
    document.getElementById('driverPhone').value = '';
    document.getElementById('driverRating').value = '5.0';
    document.getElementById('driverStatus').value = 'available';
    document.getElementById('driverModal').classList.remove('hidden');
    document.getElementById('driverModal').classList.add('flex');
}

function openDriverEditModal(driver) {
    document.getElementById('modalTitle').innerText = 'Edit Driver Details';
    document.getElementById('driverId').value = driver.id;
    document.getElementById('driverName').value = driver.name;
    document.getElementById('driverEmail').value = driver.email || '';
    document.getElementById('driverPhone').value = driver.phone || '';
    document.getElementById('driverRating').value = driver.rating;
    document.getElementById('driverStatus').value = driver.status;
    document.getElementById('driverModal').classList.remove('hidden');
    document.getElementById('driverModal').classList.add('flex');
}

function closeDriverModal() {
    document.getElementById('driverModal').classList.add('hidden');
    document.getElementById('driverModal').classList.remove('flex');
}

document.getElementById('driverModal').addEventListener('click', function (event) {
    if (event.target === this) {
        closeDriverModal();
    }
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closeDriverModal();
    }
});

function applyFilters() {
    const searchVal = document.getElementById('searchInput').value.toLowerCase().trim();
    const statusVal = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('#driversTable tbody tr');

    rows.forEach(row => {
        if (!row.dataset.name) return; // skip no driver placeholder
        const name = row.dataset.name;
        const status = row.dataset.status;

        let matchesSearch = !searchVal || name.includes(searchVal);
        let matchesStatus = !statusVal || status === statusVal;

        if (matchesSearch && matchesStatus) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
