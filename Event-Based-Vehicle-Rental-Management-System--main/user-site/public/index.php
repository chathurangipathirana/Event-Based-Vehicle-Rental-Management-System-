<?php
$page_title = 'Elite Event Vehicle Rentals';
require_once '../config/database.php';
require_once '../includes/header.php';
require_once '../includes/auth.php';

// Fetch vehicles for display
$stmt = $pdo->query("SELECT * FROM vehicles WHERE status = 'available' LIMIT 6");
$vehicles = $stmt->fetchAll();

// Fetch event types
$stmtEvent = $pdo->query("SELECT * FROM event_types WHERE is_active = 1 ORDER BY sort_order");
$eventTypes = $stmtEvent->fetchAll();
?>

<!-- Navigation -->
<header class="fixed top-0 w-full z-50 bg-white/95 dark:bg-gray-900/95 backdrop-blur-md border-b border-gray-200 dark:border-gray-800 shadow-sm">
    <nav class="max-w-[1440px] mx-auto flex justify-between items-center px-8 h-16">
        <a href="index.php" class="text-xl font-black text-red-600 dark:text-red-500 uppercase italic">FleetElite</a>
        <div class="hidden md:flex gap-8 items-center">
            <a class="text-gray-600 dark:text-gray-400 font-medium hover:text-red-500 transition-colors duration-200" href="#events">Events</a>
            <a class="text-gray-600 dark:text-gray-400 font-medium hover:text-red-500 transition-colors duration-200" href="#vehicles">Vehicles</a>
            <?php if (isLoggedIn()): ?>
                <a class="text-gray-600 dark:text-gray-500 font-medium hover:text-red-500 transition-colors duration-200" href="dashboard.php">Dashboard</a>
                <a class="text-gray-600 dark:text-gray-400 font-medium hover:text-red-500 transition-colors duration-200" href="my-bookings.php">My Bookings</a>
                <a class="bg-primary text-on-primary px-6 py-2 font-bold text-label-md rounded active:scale-95 duration-150 transition-transform" href="logout.php">Logout</a>
            <?php else: ?>
                <a class="text-gray-600 dark:text-gray-400 font-medium hover:text-red-500 transition-colors duration-200" href="login.php">Login</a>
                <a class="bg-primary text-on-primary px-6 py-2 font-bold text-label-md rounded active:scale-95 duration-150 transition-transform" href="register.php">Sign Up</a>
            <?php endif; ?>
        </div>
        <div class="flex items-center gap-6">
            <div class="flex gap-4">
                <button class="material-symbols-outlined text-gray-600 hover:text-red-600 transition-colors">notifications</button>
                <button class="material-symbols-outlined text-gray-600 hover:text-red-600 transition-colors">account_circle</button>
            </div>
            <button class="bg-primary text-on-primary px-6 py-2 font-bold text-label-md rounded active:scale-95 duration-150 transition-transform">
                Book Now
            </button>
        </div>
    </nav>
</header>

<main class="pt-16">
    <!-- Hero Section -->
    <section class="relative h-[870px] w-full flex items-center overflow-hidden">
        <div class="absolute inset-0 z-0">
            <img class="w-full h-full object-cover grayscale-[0.2] contrast-[1.1]" alt="A high-end luxury black sedan parked in front of a sleek, modern architectural building during twilight. The scene is illuminated by cool blue hour light with warm interior glows from the windows. The overall aesthetic is professional, premium, and sophisticated, utilizing a corporate color palette with deep blacks and sharp reflections." src="https://images.unsplash.com/photo-1555215695-3004980ad54e?w=1920&h=1080&fit=crop&crop=center"/>
            <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/40 to-transparent"></div>
        </div>
        <div class="relative z-10 max-w-[1440px] mx-auto w-full px-8">
            <div class="max-w-2xl">
                <span class="text-primary font-bold tracking-widest uppercase text-label-sm mb-4 block">Precision in Motion</span>
                <h1 class="font-h1 text-h1 text-white mb-6 leading-tight">Elite Logistics for Extraordinary Events.</h1>
                <p class="font-body-lg text-body-lg text-gray-200 mb-10 leading-relaxed">
                    FleetElite delivers a premium fleet of high-performance vehicles tailored for the most demanding event schedules. From luxury weddings to corporate summits, we guarantee operational excellence and punctuality.
                </p>
                <div class="flex gap-4">
                    <a href="#vehicles" class="bg-primary hover:bg-primary-container text-white px-8 py-4 font-bold rounded flex items-center gap-2 transition-all active:scale-95">
                        Browse the Fleet
                        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 0;">arrow_forward</span>
                    </a>
                    <a href="booking.php" class="border border-white/30 bg-white/10 backdrop-blur-md hover:bg-white/20 text-white px-8 py-4 font-bold rounded transition-all">
                        View Pricing
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Event Selection Bento Grid -->
    <section id="events" class="py-24 max-w-[1440px] mx-auto px-8">
        <div class="flex flex-col mb-16">
            <h2 class="font-h2 text-h2 text-on-surface mb-4">Tailored for Your Occasion</h2>
            <div class="h-1 w-24 bg-primary"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 h-auto md:h-[600px]">
            <!-- Wedding Card -->
            <div class="md:col-span-4 group relative overflow-hidden rounded-xl shadow-lg transition-all hover:shadow-2xl">
                <img class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" alt="A pristine white vintage luxury car adorned with elegant floral arrangements" src="https://images.unsplash.com/photo-1584464491033-06628f3a6b7b?w=800&h=600&fit=crop"/>
                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent"></div>
                <div class="absolute bottom-0 p-8 w-full">
                    <h3 class="text-white font-h3 text-h3 mb-2">Wedding</h3>
                    <p class="text-gray-300 font-body-md text-body-md mb-6 opacity-0 group-hover:opacity-100 transition-opacity duration-300">Timeless elegance for your most significant journey. Professional chauffeurs and pristine vintage models.</p>
                    <a href="booking.php?event=wedding" class="text-white border border-white/50 px-4 py-2 rounded text-label-md hover:bg-white hover:text-black transition-all">Explore Wedding Packages</a>
                </div>
            </div>
            <!-- Business Card -->
            <div class="md:col-span-8 group relative overflow-hidden rounded-xl shadow-lg transition-all hover:shadow-2xl">
                <img class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" alt="A fleet of identical, high-end dark grey corporate SUVs" src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1200&h=600&fit=crop"/>
                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent"></div>
                <div class="absolute bottom-0 p-8 w-full flex justify-between items-end">
                    <div class="max-w-md">
                        <h3 class="text-white font-h3 text-h3 mb-2">Business</h3>
                        <p class="text-gray-300 font-body-md text-body-md mb-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">Seamless logistics for executive summits and corporate roadshows. Reliability that matches your professional standards.</p>
                    </div>
                    <a href="booking.php?event=corporate" class="bg-primary text-white p-4 rounded-full flex items-center justify-center transition-transform hover:rotate-45 active:scale-90">
                        <span class="material-symbols-outlined">north_east</span>
                    </a>
                </div>
            </div>
            <!-- Tours Card -->
            <div class="md:col-span-12 group relative overflow-hidden rounded-xl shadow-lg transition-all h-[300px] hover:shadow-2xl">
                <img class="absolute inset-0 w-full h-full object-cover transition-transform duration-1000 group-hover:scale-105" alt="A modern luxury passenger van driving along a scenic coastal road" src="https://images.unsplash.com/photo-1549399735-cef2e2c3f638?w=1920&h=300&fit=crop"/>
                <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/10 to-transparent"></div>
                <div class="absolute inset-y-0 left-0 p-8 flex flex-col justify-center max-w-lg">
                    <h3 class="text-white font-h3 text-h3 mb-2">Tours</h3>
                    <p class="text-gray-300 font-body-md text-body-md mb-6">Explore regional destinations in absolute comfort. Specially configured vehicles for group logistics and sightseeing.</p>
                    <div class="flex gap-4">
                        <span class="flex items-center gap-2 text-white/80 text-label-sm"><span class="material-symbols-outlined text-primary text-sm">check_circle</span> 12+ Passenger</span>
                        <span class="flex items-center gap-2 text-white/80 text-label-sm"><span class="material-symbols-outlined text-primary text-sm">check_circle</span> On-board Wi-Fi</span>
                        <span class="flex items-center gap-2 text-white/80 text-label-sm"><span class="material-symbols-outlined text-primary text-sm">check_circle</span> Multi-day Support</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats / Trust Section -->
    <section class="bg-surface-container py-20 border-y border-outline-variant/30">
        <div class="max-w-[1440px] mx-auto px-8 grid grid-cols-2 md:grid-cols-4 gap-12 text-center">
            <div>
                <p class="text-h1 font-h1 text-primary mb-2">500+</p>
                <p class="text-label-md font-label-md text-on-surface-variant uppercase tracking-widest">Premium Vehicles</p>
            </div>
            <div>
                <p class="text-h1 font-h1 text-primary mb-2">12k</p>
                <p class="text-label-md font-label-md text-on-surface-variant uppercase tracking-widest">Events Managed</p>
            </div>
            <div>
                <p class="text-h1 font-h1 text-primary mb-2">99.9%</p>
                <p class="text-label-md font-label-md text-on-surface-variant uppercase tracking-widest">On-Time Rate</p>
            </div>
            <div>
                <p class="text-h1 font-h1 text-primary mb-2">24/7</p>
                <p class="text-label-md font-label-md text-on-surface-variant uppercase tracking-widest">Logistics Support</p>
            </div>
        </div>
    </section>

    <!-- Vehicles Section -->
    <section id="vehicles" class="py-24 bg-gray-50">
        <div class="max-w-[1440px] mx-auto px-8">
            <div class="flex flex-col mb-16">
                <h2 class="font-h2 text-h2 text-on-surface mb-4">Our Premium Fleet</h2>
                <div class="h-1 w-24 bg-primary"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php foreach ($vehicles as $vehicle): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-2xl transition">
                    <div class="h-48 bg-gray-300 flex items-center justify-center">
                        <?php if ($vehicle['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($vehicle['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($vehicle['name']); ?>"
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=400&h=300&fit=crop" 
                                 alt="<?php echo htmlspecialchars($vehicle['name']); ?>"
                                 class="w-full h-full object-cover">
                        <?php endif; ?>
                    </div>
                    <div class="p-6">
                        <h3 class="font-h3 text-h3 mb-2"><?php echo htmlspecialchars($vehicle['name']); ?></h3>
                        <p class="text-gray-600 mb-2"><?php echo htmlspecialchars($vehicle['model']); ?> (<?php echo $vehicle['year']; ?>)</p>
                        <p class="text-gray-600 mb-2">Capacity: <?php echo $vehicle['capacity']; ?> persons</p>
                        <p class="text-2xl font-bold text-primary mb-4">$<?php echo number_format($vehicle['price_per_day'], 2); ?>/day</p>
                        <a href="booking.php?vehicle=<?php echo $vehicle['id']; ?>" class="block w-full bg-primary text-white text-center py-3 rounded hover:bg-red-700 transition">
                            Book This Vehicle
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-24 max-w-[1440px] mx-auto px-8">
        <div class="bg-gray-900 rounded-3xl p-12 md:p-20 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-1/2 h-full opacity-20 pointer-events-none">
                <img class="w-full h-full object-cover" alt="An abstract close-up of a high-performance engine part" src="https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?w=800&h=600&fit=crop"/>
            </div>
            <div class="relative z-10 max-w-xl">
                <h2 class="text-white font-h2 text-h2 mb-6">Ready to elevate your event logistics?</h2>
                <p class="text-gray-400 font-body-lg text-body-lg mb-10">Connect with our dedicated logistics planners to architect a transport solution that meets your exact requirements.</p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="booking.php" class="bg-primary text-white px-8 py-4 font-bold rounded hover:bg-primary-container transition-all">Start Planning</a>
                    <a href="#" class="bg-transparent border border-gray-700 text-white px-8 py-4 font-bold rounded hover:bg-gray-800 transition-all">Download Brochure</a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once '../includes/footer.php'; ?>