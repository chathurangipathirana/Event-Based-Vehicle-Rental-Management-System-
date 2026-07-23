<?php
$page_title = 'Package Details';
require_once '../config/database.php';
require_once '../includes/auth.php';

$package_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$package = null;

if ($package_id) {
    $stmt = $pdo->prepare('SELECT * FROM event_packages WHERE id = ? AND status = \'active\'');
    $stmt->execute([$package_id]);
    $package = $stmt->fetch();
}

if (!$package) {
    header('Location: packages.php');
    exit();
}

$vehicleTypes = array_filter(array_map('trim', explode(',', $package['vehicle_types'] ?? '')));
$services = array_filter(array_map('trim', explode(',', $package['included_services'] ?? '')));
$packageVehicles = [];

if ($vehicleTypes) {
    $where = [];
    $params = [];
    foreach ($vehicleTypes as $vehicleType) {
        $where[] = '(name LIKE ? OR model LIKE ?)';
        $params[] = '%' . $vehicleType . '%';
        $params[] = '%' . $vehicleType . '%';
    }
    // Package details should show every vehicle included in the package, even when
    // a particular vehicle is currently booked or under maintenance.
    $stmtVehicles = $pdo->prepare('SELECT * FROM vehicles WHERE (' . implode(' OR ', $where) . ')');
    $stmtVehicles->execute($params);
    $packageVehicles = $stmtVehicles->fetchAll();
}

require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<main class="min-h-screen bg-gray-50 pt-28 pb-16">
    <style>
        .package-vehicle-card { overflow: hidden; border: 1px solid #cbd5e1; border-radius: 1.25rem; background: #fff; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04); }
        .package-vehicle-image { display: block; width: 100%; height: 16rem; object-fit: contain; padding: 0.75rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        .package-vehicle-points { margin: 0.5rem 0 0; padding: 0; list-style: none; color: #334155; font-size: 0.875rem; }
        .package-vehicle-points li { display: flex; align-items: center; gap: 0.45rem; margin-top: 0.3rem; font-weight: 500; }
        .package-vehicle-points li::before { content: '•'; color: #0284c7; font-size: 1.1rem; font-weight: 800; }
        .vehicle-select-box { display: flex; align-items: center; gap: 0.55rem; cursor: pointer; color: #0f172a; font-size: 0.8rem; font-weight: 800; }
        .vehicle-select-box input { width: 1.1rem; height: 1.1rem; accent-color: #b1000d; cursor: pointer; }
        .package-book-button { display: block; width: 100%; margin-top: 1rem; padding: 0.85rem 1rem; border-radius: 0.75rem; background: #0284c7; color: #fff; font-weight: 800; text-align: center; text-decoration: none; transition: background-color 0.2s; letter-spacing: 0.05em; text-transform: uppercase; font-size: 0.85rem; }
        .package-book-button:hover { background: #0369a1; color: #fff; }
        @media (min-width: 1024px) {
            .package-details-layout { grid-template-columns: minmax(0, 3fr) minmax(24rem, 2fr); }
            .package-details-main, .package-price-sidebar { grid-column: auto; }
        }
    </style>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-4">
            <a href="index.php" class="hover:text-cyan-600">Home</a>
            <span>/</span>
            <a href="packages.php" class="hover:text-cyan-600">Packages</a>
            <span>/</span>
            <span class="text-gray-900 font-medium"><?php echo htmlspecialchars($package['name']); ?></span>
        </nav>

        <div class="package-details-layout grid grid-cols-1 lg:grid-cols-3 gap-6">
            <section class="package-details-main lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6">
                    <!-- Package Title & Price directly under title -->
                    <div class="pb-4 mb-4 border-b">
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-tight"><?php echo htmlspecialchars($package['name']); ?></h1>
                        <p class="text-3xl font-black text-cyan-600 mt-1">LKR <?php echo number_format($package['base_price'], 2); ?></p>
                    </div>

                    <!-- INCLUDED VEHICLES with clear space between items -->
                    <?php if ($vehicleTypes): ?>
                        <div class="bg-slate-50/80 rounded-2xl p-5 border-2 border-slate-200/90 shadow-sm mb-4">
                            <div class="flex items-center gap-2 text-slate-900 font-extrabold text-xs uppercase tracking-wider border-b border-slate-200 pb-2.5 mb-4">
                                <span class="p-1 bg-cyan-600 text-white rounded-md material-symbols-outlined text-sm">directions_car</span>
                                <span>Included Vehicles</span>
                            </div>
                            <?php if ($packageVehicles): ?>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                    <?php foreach ($packageVehicles as $vehicle): ?>
                                        <article class="package-vehicle-card flex flex-col justify-between">
                                            <div>
                                                <!-- Full Clear Vehicle Image -->
                                                <img src="<?php echo htmlspecialchars(getVehicleImageUrl($vehicle['image_url'], $vehicle['name'])); ?>" alt="<?php echo htmlspecialchars($vehicle['name']); ?>" class="package-vehicle-image">
                                                <div class="p-4">
                                                    <h3 class="font-bold text-gray-900 text-base"><?php echo htmlspecialchars($vehicle['name']); ?></h3>
                                                    <!-- Specs List -->
                                                    <ul class="package-vehicle-points space-y-1">
                                                        <li><?php echo htmlspecialchars($vehicle['model']); ?> (<?php echo $vehicle['year']; ?>)</li>
                                                        <li><?php echo $vehicle['capacity']; ?> Passenger Seats</li>
                                                        <li><?php echo htmlspecialchars($vehicle['transmission']); ?> · <?php echo htmlspecialchars($vehicle['fuel_type']); ?></li>
                                                        <li>Status: <span class="font-semibold text-green-700"><?php echo htmlspecialchars(ucfirst($vehicle['status'])); ?></span></li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="px-4 pb-4">
                                                <label class="vehicle-select-box">
                                                    <input type="checkbox" class="vehicle-selection-input" value="<?php echo $vehicle['id']; ?>" data-vehicle-name="<?php echo htmlspecialchars($vehicle['name'], ENT_QUOTES); ?>" onchange="selectPackageVehicle(this)">
                                                    <span>Select this vehicle</span>
                                                </label>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="flex flex-col gap-2.5">
                                    <?php foreach ($vehicleTypes as $vehicleType): ?>
                                        <div class="flex items-center gap-2.5 px-3.5 py-2 bg-white text-slate-900 font-bold rounded-xl border border-slate-300 shadow-2xs text-xs">
                                            <span class="w-2 h-2 rounded-full bg-cyan-600 shrink-0"></span>
                                            <span><?php echo htmlspecialchars($vehicleType); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- INCLUDED SERVICES -->
                    <?php if ($services): ?>
                        <div class="bg-emerald-50/70 rounded-2xl p-5 border-2 border-emerald-200/80 shadow-sm">
                            <div class="flex items-center gap-2 text-emerald-950 font-extrabold text-xs uppercase tracking-wider border-b border-emerald-200/80 pb-2.5 mb-3">
                                <span class="p-1 bg-emerald-600 text-white rounded-md material-symbols-outlined text-sm">task_alt</span>
                                <span>Included Services</span>
                            </div>
                            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs text-emerald-950 font-semibold">
                                <?php foreach ($services as $service): ?>
                                    <li class="flex items-center gap-2.5 bg-white p-3 rounded-xl border border-emerald-200 shadow-2xs">
                                        <span class="material-symbols-outlined text-emerald-600 text-lg shrink-0">check_circle</span>
                                        <span><?php echo htmlspecialchars($service); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Sidebar -->
            <aside class="package-price-sidebar lg:col-span-1">
                <div class="sticky top-24 rounded-2xl bg-white p-5 text-center shadow-lg border border-gray-100">
                    <p class="text-xs uppercase tracking-widest font-bold text-gray-400">Total Package Price</p>
                    <p class="mt-1 text-3xl font-black text-cyan-600">LKR <?php echo number_format($package['base_price'], 2); ?></p>
                    <div id="selected-vehicle-summary" class="mt-4 rounded-xl border border-cyan-100 bg-cyan-50 p-3">
                        <p class="text-xs font-bold uppercase tracking-wider text-cyan-700">Selected Vehicle</p>
                        <p id="selected-vehicle-name" class="mt-1 text-sm font-extrabold text-gray-900">No vehicle selected</p>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Choose your included vehicle and package details in the next step.</p>
                    <?php $bookingUrl = 'booking.php?package_id=' . $package['id']; ?>
                    <?php if (isLoggedIn()): ?>
                        <a href="<?php echo $bookingUrl; ?>" class="package-book-button package-booking-link" data-booking-url="<?php echo htmlspecialchars($bookingUrl); ?>">Book This Package</a>
                    <?php else: ?>
                        <div class="mt-5 rounded-xl border border-cyan-100 bg-cyan-50 p-4 text-center">
                            <p class="text-sm font-bold text-gray-900">Book this package</p>
                            <p class="mt-1 text-xs text-gray-600">Create an account or log in to continue.</p>
                            <a href="login.php?redirect=<?php echo urlencode($bookingUrl); ?>" class="package-book-button package-booking-link" data-booking-url="<?php echo htmlspecialchars($bookingUrl); ?>" data-auth-prefix="login.php?redirect=">Log In</a>
                            <a href="register.php?redirect=<?php echo urlencode($bookingUrl); ?>" class="mt-3 block w-full rounded-xl border py-3 text-sm font-bold package-booking-link" style="border-color: #b1000d; color: #b1000d;" data-booking-url="<?php echo htmlspecialchars($bookingUrl); ?>" data-auth-prefix="register.php?redirect=">Create Account</a>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </div>
</main>

<script>
    function selectPackageVehicle(checkbox) {
        if (checkbox.checked) {
            document.querySelectorAll('.vehicle-selection-input').forEach((other) => {
                if (other !== checkbox) other.checked = false;
            });
        }

        const selectedVehicle = document.querySelector('.vehicle-selection-input:checked');
        const selectedVehicleName = document.getElementById('selected-vehicle-name');
        selectedVehicleName.textContent = selectedVehicle ? selectedVehicle.dataset.vehicleName : 'No vehicle selected';

        document.querySelectorAll('.package-booking-link').forEach((link) => {
            let bookingUrl = link.dataset.bookingUrl;
            if (selectedVehicle) bookingUrl += '&vehicle=' + encodeURIComponent(selectedVehicle.value);
            link.href = link.dataset.authPrefix ? link.dataset.authPrefix + encodeURIComponent(bookingUrl) : bookingUrl;
        });
    }
</script>

<?php require_once '../includes/footer.php'; ?>
