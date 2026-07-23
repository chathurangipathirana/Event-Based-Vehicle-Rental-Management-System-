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
            'company_name' => $_POST['company_name'] ?? 'STS',
            'company_email' => $_POST['company_email'] ?? 'info@sts.lk',
            'company_phone' => $_POST['company_phone'] ?? '+94 11 234 5678',
            'company_address' => $_POST['company_address'] ?? '',
            'currency' => 'LKR',
            'currency_symbol' => 'LKR',
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
$company_name = $settings['company_name'] ?? 'STS';
$company_email = $settings['company_email'] ?? 'info@sts.lk';
$company_phone = $settings['company_phone'] ?? '+94 11 234 5678';
$company_address = $settings['company_address'] ?? '';
$currency = 'LKR';
$currency_symbol = 'LKR';
$timezone = $settings['timezone'] ?? 'Asia/Colombo';
$date_format = $settings['date_format'] ?? 'Y-m-d';
$tax_rate = $settings['tax_rate'] ?? 10.00;

// Operational hours
$mon_fri_start = $settings['mon_fri_start'] ?? '08:00';
$mon_fri_end = $settings['mon_fri_end'] ?? '18:00';
$sat_start = $settings['sat_start'] ?? '10:00';
$sat_end = $settings['sat_end'] ?? '15:00';
$sun_status = $settings['sun_status'] ?? 'closed';

// Recent activity logs for review
$recent_logs = [];
try {
    $stmt = $pdo->query("SELECT b.id, COALESCE(b.booking_number, CONCAT('#BK', b.id)) AS booking_number, COALESCE(u.full_name, 'Guest') AS user_name, COALESCE(v.name, 'Unknown Vehicle') AS vehicle_name, b.status, DATE_FORMAT(b.created_at, '%Y-%m-%d %H:%i') AS booked_at FROM bookings b LEFT JOIN users u ON b.user_id = u.id LEFT JOIN vehicles v ON b.vehicle_id = v.id ORDER BY b.created_at DESC LIMIT 5");
    $recent_logs = $stmt->fetchAll();
} catch (PDOException $e) {
    // no logs available or query failed
}
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<?php if ($success_message): ?>
    <div class="fixed top-20 right-4 z-50 alert-success"><?php echo $success_message; ?></div>
    <script>setTimeout(() => document.querySelector('.alert-success')?.remove(), 3000);</script>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="fixed top-20 right-4 z-50 alert-error"><?php echo $error_message; ?></div>
    <script>setTimeout(() => document.querySelector('.alert-error')?.remove(), 3000);</script>
<?php endif; ?>

<main class="ml-64 min-h-screen bg-slate-50">
    <div class="p-8 max-w-7xl mx-auto">
        <section class="mb-8">
            <div class="rounded-[2rem] overflow-hidden bg-slate-900 text-white shadow-lg">
                <div class="relative overflow-hidden p-8 lg:p-10">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.22),_transparent_36%)] opacity-70 pointer-events-none"></div>
                    <div class="relative">
                        <p class="text-sm uppercase tracking-[0.35em] text-cyan-200 opacity-80 mb-4">Settings Overview</p>
                        <h1 class="text-4xl lg:text-5xl font-semibold tracking-tight">General Settings</h1>
                        <p class="mt-4 text-slate-300 max-w-2xl">Update company identity, regional preferences, and operational hours from one page.</p>
                    </div>
                </div>
            </div>
        </section>

        <form id="generalSettingsForm" method="POST" class="space-y-8">
            <section class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                <div class="flex items-center gap-2 mb-6 text-cyan-600">
                    <span class="material-symbols-outlined">palette</span>
                    <h3 class="text-2xl font-bold">Brand Identity</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div>
                        <label class="block text-sm font-medium text-slate-500 mb-3">Company Name</label>
                        <input type="text" name="company_name" value="<?php echo htmlspecialchars($company_name); ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-500 mb-3">Company Email</label>
                        <input type="email" name="company_email" value="<?php echo htmlspecialchars($company_email); ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-500 mb-3">Phone Number</label>
                        <input type="text" name="company_phone" value="<?php echo htmlspecialchars($company_phone); ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-500 mb-3">Address</label>
                        <textarea name="company_address" rows="2" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none"><?php echo htmlspecialchars($company_address); ?></textarea>
                    </div>
                </div>
            </section>

            <section class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                <div class="flex items-center gap-2 mb-6 text-cyan-600">
                    <span class="material-symbols-outlined">language</span>
                    <h3 class="text-2xl font-bold">Regional Settings</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-500">Default Currency</label>
                        <input type="text" value="LKR - Sri Lankan Rupee" class="w-full px-4 py-3 bg-slate-100 border border-slate-200 rounded-xl text-slate-600" readonly>
                        <input type="hidden" name="currency" value="LKR">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-500">Currency Symbol</label>
                        <input type="text" name="currency_symbol" value="LKR" class="w-full px-4 py-3 bg-slate-100 border border-slate-200 rounded-xl text-slate-600" readonly>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-500">System Timezone</label>
                        <select name="timezone" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-cyan-500 outline-none">
                            <option value="Asia/Colombo" <?php echo $timezone == 'Asia/Colombo' ? 'selected' : ''; ?>>(GMT+05:30) Sri Lanka Time</option>
                            <option value="Asia/Kolkata" <?php echo $timezone == 'Asia/Kolkata' ? 'selected' : ''; ?>>(GMT+05:30) India Time</option>
                            <option value="Asia/Dubai" <?php echo $timezone == 'Asia/Dubai' ? 'selected' : ''; ?>>(GMT+04:00) Dubai</option>
                            <option value="Asia/Singapore" <?php echo $timezone == 'Asia/Singapore' ? 'selected' : ''; ?>>(GMT+08:00) Singapore</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-500">Tax Rate (%)</label>
                        <input type="number" step="0.01" name="tax_rate" value="<?php echo $tax_rate; ?>" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-cyan-500 outline-none">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-slate-500">Date Format</label>
                        <div class="flex gap-4">
                            <label class="flex-1 flex items-center justify-center p-3 border rounded-xl cursor-pointer <?php echo $date_format == 'Y-m-d' ? 'border-cyan-600 bg-cyan-50 text-cyan-600' : 'border-slate-200 hover:border-slate-300'; ?>">
                                <input type="radio" name="date_format" value="Y-m-d" <?php echo $date_format == 'Y-m-d' ? 'checked' : ''; ?> class="hidden">
                                <span class="text-sm">YYYY-MM-DD</span>
                            </label>
                            <label class="flex-1 flex items-center justify-center p-3 border rounded-xl cursor-pointer <?php echo $date_format == 'd/m/Y' ? 'border-cyan-600 bg-cyan-50 text-cyan-600' : 'border-slate-200 hover:border-slate-300'; ?>">
                                <input type="radio" name="date_format" value="d/m/Y" <?php echo $date_format == 'd/m/Y' ? 'checked' : ''; ?> class="hidden">
                                <span class="text-sm">DD/MM/YYYY</span>
                            </label>
                            <label class="flex-1 flex items-center justify-center p-3 border rounded-xl cursor-pointer <?php echo $date_format == 'm/d/Y' ? 'border-cyan-600 bg-cyan-50 text-cyan-600' : 'border-slate-200 hover:border-slate-300'; ?>">
                                <input type="radio" name="date_format" value="m/d/Y" <?php echo $date_format == 'm/d/Y' ? 'checked' : ''; ?> class="hidden">
                                <span class="text-sm">MM/DD/YYYY</span>
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            <section class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <div class="flex items-center gap-2 text-cyan-600">
                        <span class="material-symbols-outlined">schedule</span>
                        <h3 class="text-2xl font-bold">Operational Hours</h3>
                    </div>
                </div>
                <div class="divide-y divide-slate-200">
                    <div class="py-4 flex items-center justify-between flex-wrap gap-4">
                        <div class="w-32">
                            <span class="text-sm font-bold text-slate-900">Monday - Friday</span>
                        </div>
                        <div class="flex items-center gap-4 flex-1 justify-center">
                            <input type="time" name="mon_fri_start" value="<?php echo $mon_fri_start; ?>" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5">
                            <span class="text-slate-400">—</span>
                            <input type="time" name="mon_fri_end" value="<?php echo $mon_fri_end; ?>" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5">
                        </div>
                        <div class="w-32 text-right">
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Active</span>
                        </div>
                    </div>
                    <div class="py-4 flex items-center justify-between flex-wrap gap-4">
                        <div class="w-32">
                            <span class="text-sm font-bold text-slate-900">Saturday</span>
                        </div>
                        <div class="flex items-center gap-4 flex-1 justify-center">
                            <input type="time" name="sat_start" value="<?php echo $sat_start; ?>" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5">
                            <span class="text-slate-400">—</span>
                            <input type="time" name="sat_end" value="<?php echo $sat_end; ?>" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5">
                        </div>
                        <div class="w-32 text-right">
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Reduced</span>
                        </div>
                    </div>
                    <div class="py-4 flex items-center justify-between flex-wrap gap-4">
                        <div class="w-32">
                            <span class="text-sm font-bold text-slate-900">Sunday</span>
                        </div>
                        <div class="flex items-center gap-4 flex-1 justify-center">
                            <select name="sun_status" class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5">
                                <option value="closed" <?php echo $sun_status == 'closed' ? 'selected' : ''; ?>>Closed</option>
                                <option value="limited" <?php echo $sun_status == 'limited' ? 'selected' : ''; ?>>Limited Hours</option>
                                <option value="full" <?php echo $sun_status == 'full' ? 'selected' : ''; ?>>Full Day</option>
                            </select>
                        </div>
                        <div class="w-32 text-right">
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">Inactive</span>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="save_general_settings" value="1">
            </section>
        </form>

        <div class="grid grid-cols-3 gap-6 mt-8">
            <div class="col-span-2 bg-slate-900 text-white p-8 rounded-2xl flex flex-col justify-between">
                <div>
                    <h4 class="text-2xl font-bold mb-2">Automated Data Retention</h4>
                    <p class="text-sm opacity-80 mb-6">Specify how long the system should keep booking records and customer logs before archiving them to deep storage.</p>
                </div>
                <div class="flex gap-4">
                    <button type="button" id="reviewLogsBtn" class="px-6 py-2 bg-white text-slate-900 font-medium rounded-lg hover:bg-slate-100 transition">Review Logs</button>
                    <button type="button" id="configureArchivingBtn" class="px-6 py-2 border border-white/30 font-medium rounded-lg hover:bg-white/10 transition">Configure Archiving</button>
                </div>
                <div id="reviewLogPanel" class="mt-6 hidden rounded-2xl border border-white/20 bg-white/10 p-6 text-left text-sm leading-6">
                    <h5 class="text-base font-semibold text-white mb-3">Recent Booking Logs</h5>
                    <div class="space-y-4 text-slate-100">
                        <?php if (empty($recent_logs)): ?>
                            <p class="text-slate-100">No recent booking logs are available.</p>
                        <?php else: ?>
                            <?php foreach ($recent_logs as $log): ?>
                                <div class="rounded-2xl border border-slate-200/10 bg-slate-950/60 p-4">
                                    <div class="flex items-center justify-between gap-4">
                                        <div>
                                            <p class="text-sm text-slate-400">Booking #<?php echo htmlspecialchars($log['booking_number']); ?> · <?php echo htmlspecialchars($log['booked_at']); ?></p>
                                            <p class="mt-1 font-semibold text-white"><?php echo htmlspecialchars($log['user_name']); ?> — <?php echo htmlspecialchars($log['vehicle_name']); ?></p>
                                        </div>
                                        <span class="rounded-full bg-slate-800 px-3 py-1 text-xs font-semibold uppercase text-slate-200"><?php echo htmlspecialchars($log['status']); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div id="actionDetails" class="mt-6 hidden rounded-2xl border border-white/20 bg-white/10 p-6 text-left text-sm leading-6">
                    <h5 class="text-base font-semibold text-white mb-3">Details</h5>
                    <p id="actionDetailsText" class="text-slate-100"></p>
                </div>
            </div>
            <div class="col-span-1 bg-cyan-500 p-8 rounded-2xl text-white flex flex-col items-center justify-center text-center relative overflow-hidden group">
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

<script>
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            const group = e.target.closest('form');
            if (!group) return;
            group.querySelectorAll('label').forEach(label => {
                label.classList.remove('border-cyan-600', 'bg-cyan-50', 'text-cyan-600');
                label.classList.add('border-slate-200');
            });
            const checkedLabel = e.target.closest('label');
            if (checkedLabel) {
                checkedLabel.classList.remove('border-slate-200');
                checkedLabel.classList.add('border-cyan-600', 'bg-cyan-50', 'text-cyan-600');
            }
        });
    });

    const actionDetails = document.getElementById('actionDetails');
    const actionDetailsText = document.getElementById('actionDetailsText');

    const reviewLogPanel = document.getElementById('reviewLogPanel');

    document.getElementById('reviewLogsBtn')?.addEventListener('click', () => {
        reviewLogPanel?.classList.remove('hidden');
        actionDetails?.classList.add('hidden');
    });

    document.getElementById('configureArchivingBtn')?.addEventListener('click', () => {
        if (actionDetails && actionDetailsText) {
            actionDetailsText.textContent = 'Configure Archiving lets you set retention periods and archive rules. This area will be updated when the archiving module is connected.';
            actionDetails.classList.remove('hidden');
        }
        reviewLogPanel?.classList.add('hidden');
    });
</script>

<?php require_once 'includes/footer.php'; ?>
