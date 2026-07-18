<?php
$page_title = 'Our Premium Event Packages';
require_once '../config/database.php';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Fetch active packages
try {
    $stmt = $pdo->query("SELECT * FROM event_packages WHERE status = 'active' ORDER BY base_price ASC");
    $all_packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $all_packages = [];
}

// Group packages by type based on name/description matches
$grouped_packages = [
    'wedding' => [],
    'business' => [],
    'tours' => [],
    'other' => []
];

foreach ($all_packages as $pkg) {
    $name = strtolower($pkg['name']);
    $desc = strtolower($pkg['description']);
    
    if (strpos($name, 'wedding') !== false || strpos($desc, 'wedding') !== false) {
        $grouped_packages['wedding'][] = $pkg;
    } elseif (strpos($name, 'business') !== false || strpos($name, 'corporate') !== false || strpos($name, 'gala') !== false || strpos($desc, 'business') !== false || strpos($desc, 'corporate') !== false || strpos($desc, 'gala') !== false) {
        $grouped_packages['business'][] = $pkg;
    } elseif (strpos($name, 'tour') !== false || strpos($name, 'travel') !== false || strpos($desc, 'tour') !== false || strpos($desc, 'travel') !== false) {
        $grouped_packages['tours'][] = $pkg;
    } else {
        $grouped_packages['other'][] = $pkg;
    }
}

$categories = [
    'wedding' => ['title' => 'Wedding Packages', 'icon' => 'favorite', 'color' => 'bg-pink-50 text-pink-700 border-pink-100'],
    'business' => ['title' => 'Business & Corporate Packages', 'icon' => 'business_center', 'color' => 'bg-slate-50 text-slate-700 border-slate-100'],
    'tours' => ['title' => 'Tour & Travel Packages', 'icon' => 'explore', 'color' => 'bg-teal-50 text-teal-700 border-teal-100'],
    'other' => ['title' => 'Special Event Packages', 'icon' => 'celebration', 'color' => 'bg-amber-50 text-amber-700 border-amber-100']
];
?>

<?php require_once '../includes/navbar.php'; ?>

<main class="pt-24 min-h-screen bg-gray-50 pb-20">
    <div class="max-w-7xl mx-auto px-8">
        <div class="mb-12 text-center max-w-3xl mx-auto">
            <h1 class="font-h1 text-4xl font-extrabold tracking-tight text-gray-900 mb-4">Premium Service Packages</h1>
            <p class="text-lg text-gray-500">Carefully curated service bundles combining luxury fleet arrangements and executive coordination for your special occasions.</p>
        </div>

        <?php if (empty($all_packages)): ?>
            <div class="bg-white rounded-3xl p-12 text-center border shadow-sm max-w-2xl mx-auto">
                <span class="material-symbols-outlined text-6xl text-gray-300 mb-4">package_2</span>
                <p class="text-gray-500 text-lg">No service packages are currently available. Check back soon!</p>
            </div>
        <?php else: ?>
            <div class="space-y-16">
                <?php foreach ($grouped_packages as $key => $packages): ?>
                    <?php if (empty($packages)) continue; ?>
                    
                    <div class="space-y-6">
                        <!-- Category Header -->
                        <div class="flex items-center gap-3 border-b pb-4">
                            <span class="material-symbols-outlined text-2xl text-cyan-600"><?php echo $categories[$key]['icon']; ?></span>
                            <h2 class="text-2xl font-bold text-gray-900"><?php echo $categories[$key]['title']; ?></h2>
                            <span class="ml-2 text-xs font-semibold px-2.5 py-0.5 rounded-full <?php echo $categories[$key]['color']; ?> border">
                                <?php echo count($packages); ?> Available
                            </span>
                        </div>

                        <!-- Packages Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            <?php foreach ($packages as $pkg): ?>
                                <div class="bg-white rounded-3xl border border-gray-200 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col justify-between group">
                                    <div>
                                        <!-- Package Header Image / Icon -->
                                        <div class="h-36 relative bg-slate-900 flex items-center justify-center overflow-hidden">
                                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent z-10"></div>
                                            <span class="material-symbols-outlined text-white/20 text-[100px] absolute -right-6 -bottom-6 rotate-12"><?php echo $categories[$key]['icon']; ?></span>
                                            <div class="relative z-20 text-center px-6">
                                                <h3 class="text-xl font-bold text-white mb-1"><?php echo htmlspecialchars($pkg['name']); ?></h3>
                                                <span class="inline-block px-2.5 py-0.5 bg-white/20 text-white rounded-full text-[10px] font-semibold uppercase tracking-wider">Bundle Package</span>
                                            </div>
                                        </div>

                                        <div class="p-6 space-y-6">
                                            <!-- Price -->
                                            <div>
                                                <span class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Base Price</span>
                                                <p class="text-2xl font-extrabold text-cyan-600 mt-0.5">LKR <?php echo number_format($pkg['base_price'], 2); ?></p>
                                            </div>

                                            <!-- Description -->
                                            <p class="text-gray-600 text-sm leading-relaxed h-12 overflow-hidden line-clamp-2"><?php echo htmlspecialchars($pkg['description']); ?></p>

                                            <!-- Included Vehicles -->
                                            <?php if ($pkg['vehicle_types']): ?>
                                                <div class="border-t pt-4">
                                                    <h4 class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-2">Vehicles Included</h4>
                                                    <div class="flex flex-wrap gap-1.5">
                                                        <?php foreach (explode(',', $pkg['vehicle_types']) as $vType): ?>
                                                            <span class="bg-cyan-50 text-cyan-700 text-[10px] px-2.5 py-0.5 rounded-full border border-cyan-100 font-medium"><?php echo htmlspecialchars(trim($vType)); ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Included Services -->
                                            <?php if ($pkg['included_services']): ?>
                                                <div class="border-t pt-4">
                                                    <h4 class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-2">Included Services</h4>
                                                    <ul class="space-y-1.5 text-xs text-gray-700">
                                                        <?php foreach (explode(',', $pkg['included_services']) as $service): ?>
                                                            <li class="flex items-center gap-2">
                                                                <span class="material-symbols-outlined text-green-500 text-base">check_circle</span>
                                                                <?php echo htmlspecialchars(trim($service)); ?>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Booking Button -->
                                    <div class="p-6 border-t bg-gray-50/50">
                                        <a href="booking.php?package_id=<?php echo $pkg['id']; ?>" class="w-full inline-flex items-center justify-center gap-2 py-3 bg-primary text-white rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-primary-hover transition-colors shadow-sm">
                                            Book Package
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
