<?php
$page_title = 'Settings';
require_once 'includes/auth.php';
requireAdminLogin();
require_once 'config/database.php';

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_general_settings'])) {
        // Update company settings
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        
        $settings = [
            'company_name' => $_POST['company_name'] ?? 'FleetElite',
            'company_email' => $_POST['company_email'] ?? 'info@fleetelite.com',
            'company_phone' => $_POST['company_phone'] ?? '+94 11 234 5678',
            'company_address' => $_POST['company_address'] ?? '',
            'currency' => $_POST['currency'] ?? 'LKR',
            'currency_symbol' => $_POST['currency_symbol'] ?? 'LKR',
            'timezone' => $_POST['timezone'] ?? 'Asia/Colombo',
            'date_format' => $_POST['date_format'] ?? 'Y-m-d',
            'tax_rate' => $_POST['tax_rate'] ?? 10.00,
        ];
        
        foreach ($settings as $key => $value) {
            $stmt->execute([$value, $key]);
        }
        
        $success_message = 'Settings saved successfully!';
    }
    
    if (isset($_POST['update_operational_hours'])) {
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$_POST['mon_fri_start'], 'mon_fri_start']);
        $stmt->execute([$_POST['mon_fri_end'], 'mon_fri_end']);
        $stmt->execute([$_POST['sat_start'], 'sat_start']);
        $stmt->execute([$_POST['sat_end'], 'sat_end']);
        $stmt->execute([$_POST['sun_status'], 'sun_status']);
        
        $success_message = 'Operational hours updated!';
    }
}

// Get current settings
$settings = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Default values if not set
$company_name = $settings['company_name'] ?? 'FleetElite';
$company_email = $settings['company_email'] ?? 'info@fleetelite.com';
$company_phone = $settings['company_phone'] ?? '+94 11 234 5678';
$company_address = $settings['company_address'] ?? '';
$currency = $settings['currency'] ?? 'LKR';
$currency_symbol = $settings['currency_symbol'] ?? 'LKR';
$timezone = $settings['timezone'] ?? 'Asia/Colombo';
$date_format = $settings['date_format'] ?? 'Y-m-d';
$tax_rate = $settings['tax_rate'] ?? 10.00;

// Operational hours
$mon_fri_start = $settings['mon_fri_start'] ?? '08:00';
$mon_fri_end = $settings['mon_fri_end'] ?? '18:00';
$sat_start = $settings['sat_start'] ?? '10:00';
$sat_end = $settings['sat_end'] ?? '15:00';
$sun_status = $settings['sun_status'] ?? 'closed';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>FleetAdmin - Settings</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<style>
    /* Admin palette variables */
    :root {
        --surface: #f9f9fa;
        --surface-low: #f3f4f4;
        --surface-card: #ffffff;
        --primary: #02414a;
        --primary-soft: #b8ebf7;
        --primary-hover: #0d5260;
        --outline: #c0c8ca;
        --text: #191c1d;
        --muted: #40484a;
        --success: #176a3a;
        --warning: #8a5200;
        --danger: #ba1a1a;
    }
    body { font-family: 'Inter', sans-serif; background-color: var(--surface); color: var(--text); }
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    .alert-success {
        background-color: #dff5e8;
        color: #0f4d2b;
        padding: 12px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid #a8dcc0;
    }
    .alert-error {
        background-color: #ffdad6;
        color: #93000a;
        padding: 12px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid #ffb4ab;
    }
    /* Override legacy red utility classes to use admin primary palette */
    .text-red-600 { color: var(--primary) !important; }
    .bg-red-50 { background-color: var(--primary-soft) !important; }
    .border-red-600 { border-color: var(--primary) !important; }
    .bg-red-600 { background-color: var(--primary) !important; }
    .text-red-600 { color: var(--primary) !important; }
    [class*="bg-red-"] { background-color: var(--primary) !important; }
    [class*="text-red-"] { color: var(--primary) !important; }
    [class*="border-red-"] { border-color: var(--primary-soft) !important; }
    [class*="hover:bg-red-"]:hover { background-color: var(--primary-hover) !important; }
    [class*="hover:text-red-"]:hover { color: var(--primary-hover) !important; }
    .bg-red-50, .bg-red-100 { background-color: var(--primary-soft) !important; }
    .bg-white { background-color: var(--surface-card) !important; }
    .bg-gray-50, .hover\:bg-gray-50:hover, .hover\:bg-gray-100:hover { background-color: var(--surface-low) !important; }
    .bg-gray-100, .bg-gray-200 { background-color: var(--surface-high, #e7e8e9) !important; }
    .border-gray-100, .border-gray-200, .border-gray-300 { border-color: var(--outline) !important; }
    .text-gray-900, .text-gray-800, .text-gray-700 { color: var(--text) !important; }
    .text-gray-600, .text-gray-500, .text-gray-400 { color: var(--muted) !important; }
    .hover\:bg-red-700:hover, .hover\:bg-black:hover { background-color: var(--primary-hover) !important; }
    .focus\:ring-red-500:focus, .focus\:border-red-500:focus { --tw-ring-color: rgba(2,65,74,0.18) !important; border-color: var(--primary) !important; }
    .text-green-600, .text-green-700 { color: var(--success) !important; }
    .bg-green-100 { background-color: #dff5e8 !important; }
    .text-yellow-700, .text-orange-600 { color: var(--warning) !important; }
    .bg-yellow-100 { background-color: #fff3d6 !important; }
    input, select, textarea { border-color: var(--outline) !important; }
    .rounded-xl { border-radius: 0.75rem !important; }
    .rounded-lg { border-radius: 0.5rem !important; }
    .rounded-full { border-radius: 9999px !important; }
    .shadow-lg, .shadow-md, .shadow-sm { box-shadow: 0 10px 24px rgba(25,28,29,0.06) !important; }
</style>
</head>
<body class="bg-background text-on-surface">

<?php if ($success_message): ?>
    <div class="fixed top-20 right-4 z-50 alert-success"><?php echo $success_message; ?></div>
    <script>setTimeout(() => document.querySelector('.alert-success')?.remove(), 3000);</script>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="fixed top-20 right-4 z-50 alert-error"><?php echo $error_message; ?></div>
    <script>setTimeout(() => document.querySelector('.alert-error')?.remove(), 3000);</script>
<?php endif; ?>

<!-- Top Navigation Shell -->
<header class="fixed top-0 z-40 w-full bg-white shadow-sm h-16 border-b border-gray-100">
    <div class="flex justify-between items-center px-8 h-full max-w-7xl mx-auto">
        <div class="flex items-center gap-8">
            <span class="text-2xl font-black text-red-600 tracking-tighter">FleetAdmin</span>
            <nav class="hidden md:flex gap-6 h-full items-center">
                <a href="dashboard.php" class="text-gray-500 hover:bg-gray-100 transition-all px-3 py-1 rounded-lg text-sm">Dashboard</a>
                <a href="fleet.php" class="text-gray-500 hover:bg-gray-100 transition-all px-3 py-1 rounded-lg text-sm">Fleet</a>
                <a href="settings.php" class="text-red-600 border-b-2 border-red-600 px-3 py-1 text-sm font-medium">Settings</a>
            </nav>
        </div>
        <div class="flex items-center gap-4">
            <button class="p-2 text-gray-500 hover:bg-gray-100 rounded-full transition-all">
                <span class="material-symbols-outlined">notifications</span>
            </button>
            <div class="flex items-center gap-3 pl-4 border-l border-gray-200">
                <div class="text-right">
                    <p class="text-sm font-medium leading-none"><?php echo $_SESSION['admin_name'] ?? 'Admin User'; ?></p>
                    <p class="text-xs text-gray-500">Super Admin</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-bold text-lg">
                    <?php echo strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 2)); ?>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="flex pt-16 min-h-screen">
    <!-- Sidebar Navigation -->
    <aside class="fixed left-0 top-16 h-[calc(100vh-64px)] w-72 bg-gray-50 border-r border-gray-200 flex flex-col p-6 space-y-2 overflow-y-auto">
        <div class="pb-4 mb-4 border-b border-gray-200">
            <h2 class="text-xs font-medium uppercase tracking-widest text-gray-400 opacity-70 px-4">Management</h2>
        </div>
        <a href="settings.php" class="flex items-center gap-3 px-4 py-3 bg-red-50 text-red-600 font-bold rounded-xl">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">settings</span>
            <span class="text-sm font-medium">General</span>
        </a>
        <a href="users.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-100 transition-colors rounded-xl">
            <span class="material-symbols-outlined">group</span>
            <span class="text-sm font-medium">Users &amp; Permissions</span>
        </a>
        <a href="vehicle-categories.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-100 transition-colors rounded-xl">
            <span class="material-symbols-outlined">category</span>
            <span class="text-sm font-medium">Vehicle Categories</span>
        </a>
        <a href="pricing.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-100 transition-colors rounded-xl">
            <span class="material-symbols-outlined">payments</span>
            <span class="text-sm font-medium">Pricing Rules</span>
        </a>
        <a href="notifications.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-100 transition-colors rounded-xl">
            <span class="material-symbols-outlined">notifications_active</span>
            <span class="text-sm font-medium">Notifications</span>
        </a>
        <div class="mt-auto pt-6 border-t border-gray-200">
            <a href="support.php" class="flex items-center gap-3 px-4 py-3 text-gray-500 hover:bg-gray-100 transition-colors rounded-xl">
                <span class="material-symbols-outlined">help</span>
                <span class="text-sm font-medium">Support Center</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="ml-72 flex-1 p-10 bg-gray-50">
        <div class="max-w-4xl mx-auto">
            <!-- Page Header -->
            <div class="flex justify-between items-end mb-10">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">General Settings</h1>
                    <p class="text-base text-gray-500">Configure the core identity and operational parameters of your FleetElite instance.</p>
                </div>
                <button type="submit" form="generalSettingsForm" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-xl font-medium shadow-lg transition-all active:scale-95">
                    Save Changes
                </button>
            </div>

            <form id="generalSettingsForm" method="POST" class="space-y-8">
                <!-- Brand Identity Section -->
                <section class="bg-white p-8 rounded-2xl border border-gray-100 shadow-sm">
                    <div class="flex items-center gap-2 mb-6 text-red-600">
                        <span class="material-symbols-outlined">palette</span>
                        <h3 class="text-2xl font-bold">Brand Identity</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-3">Company Name</label>
                            <input type="text" name="company_name" value="<?php echo htmlspecialchars($company_name); ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-3">Company Email</label>
                            <input type="email" name="company_email" value="<?php echo htmlspecialchars($company_email); ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-3">Phone Number</label>
                            <input type="text" name="company_phone" value="<?php echo htmlspecialchars($company_phone); ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-3">Address</label>
                            <textarea name="company_address" rows="2" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"><?php echo htmlspecialchars($company_address); ?></textarea>
                        </div>
                    </div>
                </section>

                <!-- Regional Settings Section -->
                <section class="bg-white p-8 rounded-2xl border border-gray-100 shadow-sm">
                    <div class="flex items-center gap-2 mb-6 text-red-600">
                        <span class="material-symbols-outlined">language</span>
                        <h3 class="text-2xl font-bold">Regional Settings</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-500">Default Currency</label>
                            <select name="currency" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 outline-none">
                                <option value="LKR" <?php echo $currency == 'LKR' ? 'selected' : ''; ?>>LKR - Sri Lankan Rupee (LKR)</option>
                                <option value="USD" <?php echo $currency == 'USD' ? 'selected' : ''; ?>>USD - US Dollar ($)</option>
                                <option value="EUR" <?php echo $currency == 'EUR' ? 'selected' : ''; ?>>EUR - Euro (€)</option>
                                <option value="GBP" <?php echo $currency == 'GBP' ? 'selected' : ''; ?>>GBP - British Pound (£)</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-500">Currency Symbol</label>
                            <input type="text" name="currency_symbol" value="<?php echo htmlspecialchars($currency_symbol); ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 outline-none">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-500">System Timezone</label>
                            <select name="timezone" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 outline-none">
                                <option value="Asia/Colombo" <?php echo $timezone == 'Asia/Colombo' ? 'selected' : ''; ?>>(GMT+05:30) Sri Lanka Time</option>
                                <option value="Asia/Kolkata" <?php echo $timezone == 'Asia/Kolkata' ? 'selected' : ''; ?>>(GMT+05:30) India Time</option>
                                <option value="Asia/Dubai" <?php echo $timezone == 'Asia/Dubai' ? 'selected' : ''; ?>>(GMT+04:00) Dubai</option>
                                <option value="Asia/Singapore" <?php echo $timezone == 'Asia/Singapore' ? 'selected' : ''; ?>>(GMT+08:00) Singapore</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-500">Tax Rate (%)</label>
                            <input type="number" step="0.01" name="tax_rate" value="<?php echo $tax_rate; ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-red-500 outline-none">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-500">Date Format</label>
                            <div class="flex gap-4">
                                <label class="flex-1 flex items-center justify-center p-3 border rounded-xl cursor-pointer <?php echo $date_format == 'Y-m-d' ? 'border-red-600 bg-red-50 text-red-600' : 'border-gray-200 hover:border-red-300'; ?>">
                                    <input type="radio" name="date_format" value="Y-m-d" <?php echo $date_format == 'Y-m-d' ? 'checked' : ''; ?> class="hidden">
                                    <span class="text-sm">YYYY-MM-DD</span>
                                </label>
                                <label class="flex-1 flex items-center justify-center p-3 border rounded-xl cursor-pointer <?php echo $date_format == 'd/m/Y' ? 'border-red-600 bg-red-50 text-red-600' : 'border-gray-200 hover:border-red-300'; ?>">
                                    <input type="radio" name="date_format" value="d/m/Y" <?php echo $date_format == 'd/m/Y' ? 'checked' : ''; ?> class="hidden">
                                    <span class="text-sm">DD/MM/YYYY</span>
                                </label>
                                <label class="flex-1 flex items-center justify-center p-3 border rounded-xl cursor-pointer <?php echo $date_format == 'm/d/Y' ? 'border-red-600 bg-red-50 text-red-600' : 'border-gray-200 hover:border-red-300'; ?>">
                                    <input type="radio" name="date_format" value="m/d/Y" <?php echo $date_format == 'm/d/Y' ? 'checked' : ''; ?> class="hidden">
                                    <span class="text-sm">MM/DD/YYYY</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Operational Hours Section -->
                <section class="bg-white p-8 rounded-2xl border border-gray-100 shadow-sm">
                    <div class="flex justify-between items-center mb-6">
                        <div class="flex items-center gap-2 text-red-600">
                            <span class="material-symbols-outlined">schedule</span>
                            <h3 class="text-2xl font-bold">Operational Hours</h3>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div class="py-4 flex items-center justify-between flex-wrap gap-4">
                            <div class="w-32">
                                <span class="text-sm font-bold text-gray-900">Monday - Friday</span>
                            </div>
                            <div class="flex items-center gap-4 flex-1 justify-center">
                                <input type="time" name="mon_fri_start" value="<?php echo $mon_fri_start; ?>" class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5">
                                <span class="text-gray-400">—</span>
                                <input type="time" name="mon_fri_end" value="<?php echo $mon_fri_end; ?>" class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5">
                            </div>
                            <div class="w-32 text-right">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                            </div>
                        </div>
                        <div class="py-4 flex items-center justify-between flex-wrap gap-4">
                            <div class="w-32">
                                <span class="text-sm font-bold text-gray-900">Saturday</span>
                            </div>
                            <div class="flex items-center gap-4 flex-1 justify-center">
                                <input type="time" name="sat_start" value="<?php echo $sat_start; ?>" class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5">
                                <span class="text-gray-400">—</span>
                                <input type="time" name="sat_end" value="<?php echo $sat_end; ?>" class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5">
                            </div>
                            <div class="w-32 text-right">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Reduced</span>
                            </div>
                        </div>
                        <div class="py-4 flex items-center justify-between flex-wrap gap-4">
                            <div class="w-32">
                                <span class="text-sm font-bold text-gray-900">Sunday</span>
                            </div>
                            <div class="flex items-center gap-4 flex-1 justify-center">
                                <select name="sun_status" class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5">
                                    <option value="closed" <?php echo $sun_status == 'closed' ? 'selected' : ''; ?>>Closed</option>
                                    <option value="limited" <?php echo $sun_status == 'limited' ? 'selected' : ''; ?>>Limited Hours</option>
                                    <option value="full" <?php echo $sun_status == 'full' ? 'selected' : ''; ?>>Full Day</option>
                                </select>
                            </div>
                            <div class="w-32 text-right">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="save_general_settings" value="1">
                </section>
            </form>

            <!-- Advanced Maintenance Card -->
            <div class="grid grid-cols-3 gap-6 mt-8">
                <div class="col-span-2 bg-gray-800 text-white p-8 rounded-2xl flex flex-col justify-between">
                    <div>
                        <h4 class="text-2xl font-bold mb-2">Automated Data Retention</h4>
                        <p class="text-sm opacity-80 mb-6">Specify how long the system should keep booking records and customer logs before archiving them to deep storage.</p>
                    </div>
                    <div class="flex gap-4">
                        <button class="px-6 py-2 bg-white text-gray-900 font-medium rounded-lg hover:bg-gray-100 transition">Review Logs</button>
                        <button class="px-6 py-2 border border-white/30 font-medium rounded-lg hover:bg-white/10 transition">Configure Archiving</button>
                    </div>
                </div>
                <div class="col-span-1 bg-red-600 p-8 rounded-2xl text-white flex flex-col items-center justify-center text-center relative overflow-hidden group">
                    <div class="absolute -top-10 -right-10 w-32 h-32 bg-white/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                    <span class="material-symbols-outlined text-5xl mb-4">rocket_launch</span>
                    <p class="text-sm font-bold mb-1">System Status</p>
                    <p class="text-3xl font-black">99.9%</p>
                    <p class="text-xs opacity-80">Up-time across all clusters</p>
                </div>
            </div>
            
            <div class="h-12"></div>
        </div>
    </main>
</div>

<script>
    // Radio button styling
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            const parent = e.target.closest('.flex');
            if (parent) {
                parent.querySelectorAll('.border-red-600, .bg-red-50').forEach(el => {
                    el.classList.remove('border-red-600', 'bg-red-50', 'text-red-600');
                    el.classList.add('border-gray-200');
                });
                e.target.closest('label').classList.remove('border-gray-200');
                e.target.closest('label').classList.add('border-red-600', 'bg-red-50', 'text-red-600');
            }
        });
    });
</script>

</body>
</html>
