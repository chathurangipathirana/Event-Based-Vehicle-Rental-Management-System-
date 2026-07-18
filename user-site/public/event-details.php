<?php
$type = $_GET['type'] ?? 'wedding';
// Normalize type
$allowed_types = ['wedding', 'business', 'tours'];
if (!in_array($type, $allowed_types)) {
    $type = 'wedding';
}

$page_title = ucfirst($type) . ' Packages & Fleet';
require_once '../config/database.php';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Configure event details page banners and metadata
$meta = [
    'wedding' => [
        'title' => 'Wedding Transportation',
        'subtitle' => 'Timeless elegance, luxurious comfort, and impeccable chauffeur coordination for your most significant milestone.',
        'banner_img' => 'https://images.unsplash.com/photo-1584464491033-06628f3a6b7b?w=1920&h=600&fit=crop',
        'vehicle_query' => "category = 'Luxury' OR category = 'Executive'",
        'keyword' => 'wedding'
    ],
    'business' => [
        'title' => 'Corporate & Business Logistics',
        'subtitle' => 'Professional, on-time executive transport and group logistics architecture for conferences, summits, and VIP delegates.',
        'banner_img' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1920&h=600&fit=crop',
        'vehicle_query' => "category = 'Executive' OR category = 'Luxury SUV'",
        'keyword' => 'business'
    ],
    'tours' => [
        'title' => 'Scenic Tours & Travel Group Transport',
        'subtitle' => 'Comfortable, spacious group logistics with luxury SUVs, electric vehicles, and custom transport options for regional touring.',
        'banner_img' => 'https://images.unsplash.com/photo-1549399735-cef2e2c3f638?w=1920&h=600&fit=crop',
        'vehicle_query' => "category = 'Luxury SUV' OR category = 'Electric' OR category = 'Sports'",
        'keyword' => 'gala' // fallback search or matches
    ]
];

$current_meta = $meta[$type];

// Fetch packages matching this event type
try {
    $pkg_keyword = $current_meta['keyword'];
    $stmtPkg = $pdo->prepare("SELECT * FROM event_packages WHERE status = 'active' AND (name LIKE ? OR description LIKE ? OR name LIKE ?)");
    $stmtPkg->execute(["%$type%", "%$type%", "%$pkg_keyword%"]);
    $packages = $stmtPkg->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $packages = [];
}

// Fetch vehicles matching this event type category
try {
    $v_query = $current_meta['vehicle_query'];
    $stmtVehicles = $pdo->query("SELECT * FROM vehicles WHERE status = 'available' AND ($v_query)");
    $vehicles = $stmtVehicles->fetchAll(PDO::FETCH_ASSOC);
    
    // If no vehicles found in specific category, fetch general available vehicles
    if (empty($vehicles)) {
        $stmtVehicles = $pdo->query("SELECT * FROM vehicles WHERE status = 'available' LIMIT 3");
        $vehicles = $stmtVehicles->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $vehicles = [];
}
?>

<?php require_once '../includes/navbar.php'; ?>

<main class="min-h-screen bg-gray-50 pb-24">
    <!-- Hero Banner Section -->
    <div class="relative h-[480px] w-full flex items-center overflow-hidden">
        <div class="absolute inset-0 z-0">
            <img class="w-full h-full object-cover grayscale-[0.1] contrast-[1.05]" src="<?php echo $current_meta['banner_img']; ?>" alt="<?php echo htmlspecialchars($current_meta['title']); ?> Banner">
            <div class="absolute inset-0 bg-gradient-to-r from-black/85 via-black/55 to-transparent"></div>
        </div>
        <div class="relative z-10 max-w-7xl mx-auto w-full px-8 text-white">
            <div class="max-w-2xl space-y-4">
                <span class="text-cyan-400 font-bold tracking-widest uppercase text-xs">Event Solutions</span>
                <h1 class="text-4xl md:text-5xl font-black tracking-tight leading-tight uppercase italic"><?php echo htmlspecialchars($current_meta['title']); ?></h1>
                <p class="text-gray-300 text-lg leading-relaxed"><?php echo htmlspecialchars($current_meta['subtitle']); ?></p>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-8 mt-16 space-y-20">
        <!-- Part 1: Service Packages Section -->
        <section id="event-packages" class="space-y-8">
            <div class="border-b pb-4 flex justify-between items-end">
                <div>
                    <h2 class="text-3xl font-extrabold text-gray-900 uppercase tracking-tight">1. Event Packages</h2>
                    <p class="text-gray-500 mt-1">Select from our pre-configured premium bundles for <?php echo htmlspecialchars($type); ?> occasions.</p>
                </div>
                <div class="h-1 w-24 bg-cyan-600"></div>
            </div>

            <?php if (empty($packages)): ?>
                <div class="bg-white rounded-3xl p-12 text-center border border-gray-200 shadow-sm max-w-2xl mx-auto">
                    <span class="material-symbols-outlined text-5xl text-gray-300 mb-3">package_2</span>
                    <p class="text-gray-600 font-medium">Custom <?php echo ucfirst($type); ?> packages can be arranged directly. Contact our logistics support.</p>
                    <a href="booking.php" class="mt-4 inline-block bg-cyan-600 text-white px-6 py-2 rounded-xl font-bold text-sm">Request Custom Booking</a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($packages as $pkg): ?>
                        <div class="bg-white rounded-3xl border border-gray-200 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col justify-between group">
                            <div>
                                <div class="p-6 bg-slate-900 text-white relative overflow-hidden h-32 flex flex-col justify-center">
                                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 to-transparent opacity-80 z-10"></div>
                                    <span class="material-symbols-outlined text-white/10 text-8xl absolute -right-4 -bottom-4 rotate-12">celebration</span>
                                    <h3 class="text-xl font-bold relative z-20"><?php echo htmlspecialchars($pkg['name']); ?></h3>
                                    <span class="inline-block self-start mt-1 px-2.5 py-0.5 bg-cyan-600/30 text-cyan-400 rounded-full text-[10px] font-bold uppercase tracking-wider relative z-20">Specialized Bundle</span>
                                </div>

                                <div class="p-6 space-y-6">
                                    <div>
                                        <span class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Package Price</span>
                                        <p class="text-2xl font-extrabold text-cyan-600 mt-0.5">LKR <?php echo number_format($pkg['base_price'], 2); ?></p>
                                    </div>

                                    <p class="text-gray-600 text-sm leading-relaxed h-12 overflow-hidden line-clamp-2"><?php echo htmlspecialchars($pkg['description']); ?></p>

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

                                    <?php if ($pkg['included_services']): ?>
                                        <div class="border-t pt-4">
                                            <h4 class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-2">Included Extras</h4>
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

                            <div class="p-6 border-t bg-gray-50/50">
                                <a href="booking.php?package_id=<?php echo $pkg['id']; ?>" class="w-full inline-flex items-center justify-center gap-2 py-3 bg-cyan-600 hover:bg-cyan-700 text-white rounded-xl font-bold text-xs uppercase tracking-wider transition-colors shadow-sm">
                                    Book This Package
                                    <span class="material-symbols-outlined text-xs">arrow_forward</span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Part 2: Suitable Fleet Section -->
        <section id="event-fleet" class="space-y-8">
            <div class="border-b pb-4 flex justify-between items-end">
                <div>
                    <h2 class="text-3xl font-extrabold text-gray-900 uppercase tracking-tight">2. Suitable Fleet Selection</h2>
                    <p class="text-gray-500 mt-1">Prefer to select a specific vehicle for custom scheduling? Browse available fleets suitable for <?php echo htmlspecialchars($type); ?> bookings.</p>
                </div>
                <div class="h-1 w-24 bg-cyan-600"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($vehicles as $vehicle): ?>
                    <div class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden hover:shadow-xl transition-all duration-300 flex flex-col justify-between group">
                        <div>
                            <div class="h-52 bg-gray-200 relative overflow-hidden">
                                <?php if ($vehicle['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($vehicle['image_url']); ?>" alt="<?php echo htmlspecialchars($vehicle['name']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                <?php else: ?>
                                    <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600&h=400&fit=crop" alt="<?php echo htmlspecialchars($vehicle['name']); ?>" class="w-full h-full object-cover">
                                <?php endif; ?>
                                <span class="absolute top-4 right-4 bg-emerald-500 text-white text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full shadow-sm">Available</span>
                            </div>

                            <div class="p-6 space-y-4">
                                <div>
                                    <span class="text-[10px] uppercase tracking-wider text-cyan-600 font-bold"><?php echo htmlspecialchars($vehicle['category']); ?> Class</span>
                                    <h3 class="text-xl font-bold text-gray-900 mt-0.5"><?php echo htmlspecialchars($vehicle['name']); ?></h3>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($vehicle['model']); ?> • <?php echo $vehicle['year']; ?></p>
                                </div>

                                <div class="grid grid-cols-2 gap-4 text-xs text-gray-600 bg-gray-50 p-3.5 rounded-xl border border-gray-100">
                                    <div class="flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-gray-400 text-base">groups</span>
                                        <span><?php echo $vehicle['capacity']; ?> Seats</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-gray-400 text-base">settings</span>
                                        <span><?php echo htmlspecialchars($vehicle['transmission']); ?></span>
                                    </div>
                                </div>

                                <div class="flex justify-between items-baseline pt-2">
                                    <div>
                                        <span class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">Daily Rate</span>
                                        <p class="text-xl font-extrabold text-cyan-600">LKR <?php echo number_format($vehicle['price_per_day'], 2); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">Hourly Rate</span>
                                        <p class="text-base font-bold text-gray-700">LKR <?php echo number_format($vehicle['price_per_hour'], 2); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 border-t bg-gray-50/50">
                            <a href="booking.php?vehicle=<?php echo $vehicle['id']; ?>" class="w-full inline-flex items-center justify-center gap-2 py-3 bg-cyan-600 hover:bg-cyan-700 text-white rounded-xl font-bold text-xs uppercase tracking-wider transition-colors shadow-sm">
                                Book This Vehicle
                                <span class="material-symbols-outlined text-xs">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>
