<?php
$page_title = 'Event Service Packages';
require_once '../config/database.php';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Fetch active packages
try {
    $stmt = $pdo->query("SELECT * FROM event_packages WHERE status = 'active' ORDER BY base_price ASC");
    $raw_packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Deduplicate by package name to ensure no duplicate cards are shown
    $all_packages = [];
    $seen_names = [];
    foreach ($raw_packages as $pkg) {
        $normalized_name = strtolower(trim($pkg['name']));
        if (!in_array($normalized_name, $seen_names)) {
            $seen_names[] = $normalized_name;
            $all_packages[] = $pkg;
        }
    }
} catch (PDOException $e) {
    $all_packages = [];
}

// Group packages by the category selected in the admin package form.
$grouped_packages = [
    'wedding' => [],
    'business' => [],
    'tours' => [],
    'other' => []
];

foreach ($all_packages as $pkg) {
    $category = $pkg['category'] ?? 'other';
    $grouped_packages[array_key_exists($category, $grouped_packages) ? $category : 'other'][] = $pkg;
}

$categories = [
    'wedding' => ['title' => 'Wedding Packages', 'icon' => 'favorite', 'color' => 'bg-pink-50 text-pink-700 border-pink-100'],
    'business' => ['title' => 'Business & Corporate Packages', 'icon' => 'business_center', 'color' => 'bg-slate-50 text-slate-700 border-slate-100'],
    'tours' => ['title' => 'Tour & Travel Packages', 'icon' => 'explore', 'color' => 'bg-teal-50 text-teal-700 border-teal-100'],
    'other' => ['title' => 'Special Event Packages', 'icon' => 'celebration', 'color' => 'bg-amber-50 text-amber-700 border-amber-100']
];
?>

<?php require_once '../includes/navbar.php'; ?>

<style>
    /* The compiled stylesheet does not include every Tailwind spacing utility. */
    .package-category-list > .package-category + .package-category {
        margin-top: 5rem;
    }
</style>

<main class="flex-1 bg-surface-bright min-h-screen pt-36 pb-20">
    <div class="max-w-[1440px] mx-auto p-gutter lg:p-margin">
        <div class="mb-8">
            <nav class="flex items-center gap-2 text-label-sm text-gray-400 mb-3">
                <a href="index.php" class="hover:text-red-600">Home</a>
                <span class="material-symbols-outlined text-sm">chevron_right</span>
                <span class="text-gray-900 font-bold">Event Packages</span>
            </nav>
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h1 class="font-h1 text-h1 text-on-surface mb-1">Service Packages</h1>
                    <p class="text-body-lg text-gray-500 max-w-2xl">Selections curated for high-profile events, combining luxury fleet arrangements with operational reliability.</p>
                </div>
            </div>
        </div>

        <?php if (empty($all_packages)): ?>
            <div class="bg-white rounded-3xl p-12 text-center border shadow-sm max-w-2xl mx-auto">
                <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">package_2</span>
                <p class="text-gray-500 text-lg">No service packages are currently available. Check back soon!</p>
            </div>
        <?php else: ?>
            <!-- Clearly separate one package category from the next. -->
            <div class="package-category-list">
                <?php foreach ($grouped_packages as $key => $packages): ?>
                    <?php if (empty($packages)) continue; ?>
                    
                    <div class="package-category space-y-8">
                        <!-- Category Header -->
                        <div class="flex items-center gap-3 border-b pb-3">
                            <span class="material-symbols-outlined text-2xl text-cyan-600"><?php echo $categories[$key]['icon']; ?></span>
                            <h2 class="text-2xl font-bold text-gray-900"><?php echo $categories[$key]['title']; ?></h2>
                            <span class="ml-2 text-xs font-semibold px-2.5 py-0.5 rounded-full <?php echo $categories[$key]['color']; ?> border">
                                <?php echo count($packages); ?> Available
                            </span>
                        </div>

                        <!-- Packages Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($packages as $pkg): ?>
                                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden flex flex-col justify-between group">
                                    <div>
                                        <!-- Package name is displayed prominently before its vehicle image. -->
                                        <?php
                                        $packageDiscounts = [
                                            'Coastal Beach Wedding Deluxe' => 20,
                                            'Kandyan Wedding Premium' => 15,
                                            'Royal Presidential Wedding' => 10,
                                            'Colombo Business Pro' => 20,
                                            'Executive VIP Fleet Shuttle' => 15,
                                            'Diplomatic Summit Transport' => 12,
                                            'Hill Country Scenic Expedition' => 18,
                                            'Cultural Triangle Grand Tour' => 15,
                                            'Galle Island Tour Elite' => 20,
                                            'VIP Party Shuttle Express' => 25,
                                            'Gala & Red Carpet Night' => 15,
                                            'Milestone Celebration Package' => 10,
                                        ];
                                        $discountPercent = $packageDiscounts[trim($pkg['name'])] ?? null;
                                        $isDiscountPackage = $discountPercent !== null;
                                        ?>
                                        <div class="px-5 pt-5 text-center">
                                            <h3 class="inline-block rounded-xl px-4 py-2 text-xl font-extrabold leading-snug transition-colors <?php echo $isDiscountPackage ? 'text-primary underline' : 'bg-cyan-50 text-cyan-800 ring-1 ring-cyan-200 group-hover:bg-cyan-100'; ?>">
                                                <?php echo htmlspecialchars($pkg['name']); ?>
                                            </h3>
                                        </div>
                                        <!-- Sharp, Clear Vehicle Image Showcase -->
                                        <?php 
                                        $firstVType = !empty($pkg['vehicle_types']) ? explode(',', $pkg['vehicle_types'])[0] : '';
                                        $vehicleLookup = !empty($pkg['image_url']) ? $pkg['name'] : (!empty($firstVType) ? trim($firstVType) : $pkg['name']);
                                        $pkgImg = getVehicleImageUrl($pkg['image_url'] ?? '', $vehicleLookup);
                                        ?>
                                        <div class="relative w-full h-56 bg-slate-100 flex items-center justify-center p-3 overflow-hidden border-b border-gray-200">
                                            <img src="<?php echo htmlspecialchars($pkgImg); ?>" alt="<?php echo htmlspecialchars($pkg['name']); ?>" class="w-full h-full object-contain filter drop-shadow-md transition-transform duration-500 group-hover:scale-105">
                                            <div class="absolute top-3 left-3">
                                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-white/95 text-cyan-900 rounded-full text-xs font-extrabold shadow-sm border border-cyan-100">
                                                    <span class="material-symbols-outlined text-xs text-cyan-600">verified</span>
                                                    Bundle Package
                                                </span>
                                            </div>
                                        </div>

                                        <div class="p-5">
                                            <!-- Centered package price -->
                                            <div class="mb-4 text-center">
                                                <p class="inline-block rounded-full bg-cyan-50 px-5 py-2 text-2xl font-black text-cyan-600 ring-1 ring-cyan-100">LKR <?php echo number_format($pkg['base_price'], 2); ?></p>
                                                <?php if ($discountPercent !== null): ?>
                                                    <p class="mt-2 text-sm font-extrabold" style="color: #b1000d;"><?php echo $discountPercent; ?>% OFF</p>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Included Vehicles with clear spacing between Toyota Premio & Suzuki WagonR -->
                                            <?php if ($pkg['vehicle_types']): ?>
                                                <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 mb-3">
                                                    <div class="flex items-center gap-2 text-slate-800 font-bold text-sm uppercase tracking-wider mb-3">
                                                        <span class="material-symbols-outlined text-cyan-600 text-xl">directions_car</span>
                                                        <span>Included Vehicles</span>
                                                    </div>
                                                    <div class="flex flex-wrap gap-3">
                                                        <?php foreach (explode(',', $pkg['vehicle_types']) as $vType): ?>
                                                            <div class="inline-flex items-center gap-2 px-4 py-2 text-slate-900 text-sm font-bold rounded-full border" style="background-color: #eef9fa; border-color: #b8e1e5;">
                                                                <span class="w-2 h-2 rounded-full shrink-0" style="background-color: #0d6a75;"></span>
                                                                <span><?php echo htmlspecialchars(trim($vType)); ?></span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Included Services immediately under Included Vehicles -->
                                            <?php if ($pkg['included_services']): ?>
                                                <div class="bg-emerald-50/70 rounded-xl p-4 border border-emerald-100">
                                                    <div class="flex items-center gap-2 text-emerald-900 font-bold text-sm uppercase tracking-wider mb-3">
                                                        <span class="material-symbols-outlined text-emerald-600 text-xl">task_alt</span>
                                                        <span>Included Services</span>
                                                    </div>
                                                    <ul class="space-y-2 text-sm text-emerald-950 font-semibold leading-relaxed">
                                                        <?php foreach (explode(',', $pkg['included_services']) as $service): ?>
                                                            <li class="flex items-center gap-2.5">
                                                                <span class="material-symbols-outlined text-emerald-600 text-lg shrink-0">check_circle</span>
                                                                <span><?php echo htmlspecialchars(trim($service)); ?></span>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- View Details Action Button -->
                                    <div class="mt-4 px-2 py-4 border-t bg-gray-50/50">
                                        <a href="package-details.php?id=<?php echo $pkg['id']; ?>" class="inline-flex items-center justify-center gap-2 py-2 bg-primary text-white rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-primary-hover transition-colors shadow-sm" style="width: calc(100% + 16px); margin-left: -8px;">
                                            View Vehicle Details
                                            <span class="material-symbols-outlined text-sm">arrow_forward</span>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
