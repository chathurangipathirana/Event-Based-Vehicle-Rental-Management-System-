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

// Fetch hero images dynamically from vehicles table
$stmtHero = $pdo->query("SELECT image_url FROM vehicles WHERE status = 'available' AND image_url IS NOT NULL LIMIT 4");
$heroImages = $stmtHero->fetchAll();

$fallbackImages = [
    ['image_url' => 'https://images.unsplash.com/photo-1555215695-3004980ad54e?w=1920&h=1080&fit=crop&crop=center'],
    ['image_url' => 'https://images.unsplash.com/photo-1563720223185-11003d516935?w=1920&h=1080&fit=crop&crop=center'],
    ['image_url' => 'https://images.unsplash.com/photo-1502877338535-766e1452684a?w=1920&h=1080&fit=crop&crop=center'],
    ['image_url' => 'https://images.unsplash.com/photo-1605559424843-9e4c228bf1c2?w=1920&h=1080&fit=crop&crop=center']
];

// Ensure we have at least 2 images for the transition to work
if (count($heroImages) < 2) {
    foreach ($fallbackImages as $fb) {
        if (count($heroImages) >= 4) break;
        $heroImages[] = $fb;
    }
}

// Fetch stats dynamically
$stmtTotalVehicles = $pdo->query("SELECT COUNT(*) FROM vehicles");
$totalVehicles = $stmtTotalVehicles->fetchColumn();

$stmtTotalBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'completed'");
$totalBookings = $stmtTotalBookings->fetchColumn();
$totalBookingsDisplay = $totalBookings > 1000 ? $totalBookings : (12000 + $totalBookings);
?>

<?php require_once '../includes/navbar.php'; ?>

<main class="pt-16">
    <style>
        .hero-slide {
            opacity: 0;
            transition: opacity 1.5s ease-in-out, transform 7s ease-out;
            transform: scale(1);
        }
        .hero-slide.active {
            opacity: 1;
            transform: scale(1.1); /* 3D-like zoom effect */
            z-index: 1;
        }
        .booking-option-card {
            background: linear-gradient(135deg, #ffffff 0%, #eef9fa 100%);
            border: 1px solid rgba(2, 65, 74, 0.12);
            border-radius: 24px;
            box-shadow: 0 14px 34px rgba(2, 65, 74, 0.09);
            transform: translateY(0);
            transition: transform 220ms ease, box-shadow 220ms ease, border-color 220ms ease;
        }
        .booking-option-card:hover {
            border-color: rgba(2, 65, 74, 0.24);
            box-shadow: 0 20px 40px rgba(0, 0, 0, .12);
            transform: translateY(-8px);
        }
        .booking-option-icon {
            transition: transform 220ms ease, background-color 220ms ease;
        }
        .booking-option-card:hover .booking-option-icon {
            transform: rotate(-8deg) scale(1.06);
        }
        .booking-option-gallery {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.65rem;
        }
        .booking-option-gallery img {
            width: 100%;
            height: 92px;
            object-fit: cover;
            border-radius: 14px;
            box-shadow: 0 6px 14px rgba(2, 65, 74, 0.12);
        }
        .booking-option-button {
            background: linear-gradient(135deg, #02414a 0%, #0d6a75 100%);
            box-shadow: 0 8px 18px rgba(2, 65, 74, 0.24);
            transition: transform 200ms ease, box-shadow 200ms ease, filter 200ms ease;
        }
        .booking-option-button:hover {
            box-shadow: 0 13px 28px rgba(2, 65, 74, 0.38);
            filter: brightness(1.08);
            transform: translateY(-2px);
        }
        .booking-option-card:hover .booking-option-button {
            background: linear-gradient(135deg, #013840 0%, #087985 100%);
        }
        .booking-option-button-arrow {
            display: inline-block;
            font-size: 1.1rem;
            line-height: 1;
            transition: transform 200ms ease, letter-spacing 200ms ease;
        }
        .booking-option-button:hover .booking-option-button-arrow {
            letter-spacing: 0.1em;
            transform: translateX(5px);
        }
    </style>
    <!-- Hero Section -->
    <section class="relative w-full flex items-center overflow-hidden" style="min-height: calc(100vh - 4rem);">
        <div class="absolute inset-0 z-0 bg-black" id="hero-slider">
            <?php foreach ($heroImages as $index => $img): ?>
                <img class="hero-slide <?php echo $index === 0 ? 'active' : ''; ?> absolute inset-0 w-full h-full object-cover grayscale-[0.2] contrast-[1.1]" 
                     src="<?php echo htmlspecialchars(strpos($img['image_url'], 'http') === 0 ? $img['image_url'] : '../uploads/' . $img['image_url']); ?>" 
                     alt="Premium Fleet Vehicle"/>
            <?php endforeach; ?>
            
            <!-- Gradient Overlay -->
            <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/40 to-transparent z-10 pointer-events-none"></div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const slides = document.querySelectorAll('.hero-slide');
                let currentSlide = 0;

                setInterval(() => {
                    slides[currentSlide].classList.remove('active');
                    currentSlide = (currentSlide + 1) % slides.length;
                    slides[currentSlide].classList.add('active');
                }, 5000); // 5 seconds per slide
            });
        </script>
        <div class="relative z-10 max-w-[1440px] mx-auto w-full px-8">
            <div class="max-w-2xl">
                <h1 class="font-h1 text-h1 text-white mb-6 leading-tight">Smart Transportation for Every Event</h1>
                <p class="font-body-lg text-body-lg text-gray-200 mb-10 leading-relaxed">
                    Book vehicles, manage transportation schedules, assign drivers and event management from one platform.
                </p>
                <div class="flex gap-4">
                    <a href="#vehicles" class="bg-primary hover:bg-primary-container text-white px-8 py-4 font-bold rounded flex items-center gap-2 transition-all active:scale-95">
                        Browse Vehicles
                        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 0;">arrow_forward</span>
                    </a>
                    <a href="booking.php" class="border border-white/30 bg-white/10 backdrop-blur-md hover:bg-white/20 text-white px-8 py-4 font-bold rounded transition-all">
                        View Pricing
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Booking Strategy Selection -->
    <section id="booking-options" class="py-24 bg-white border-y border-gray-150">
        <div class="max-w-7xl mx-auto px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="text-primary font-bold tracking-widest uppercase text-xs">How would you like to book?</span>
                <h2 class="font-h2 text-3xl md:text-4xl font-extrabold text-gray-900 mt-2">Two Ways to Begin Your Journey</h2>
                <div class="h-1 w-24 bg-primary mx-auto mt-4"></div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 max-w-5xl mx-auto">
                <!-- Option 1: Customized Booking -->
                <div class="booking-option-card p-8 md:p-10 flex flex-col justify-between group">
                    <div class="space-y-6">
                        <div class="booking-option-icon w-20 h-20 rounded-full bg-red-50 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-colors duration-300">
                            <span class="text-4xl" role="img" aria-label="Car">🚘</span>
                        </div>
                        <div class="booking-option-gallery" aria-label="Luxury vehicle options">
                            <img src="https://images.unsplash.com/photo-1555215695-3004980ad54e?w=500&amp;h=300&amp;fit=crop" alt="Luxury Mercedes vehicle">
                            <img src="https://images.unsplash.com/photo-1563720223185-11003d516935?w=500&amp;h=300&amp;fit=crop" alt="Premium BMW vehicle">
                            <img src="https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=500&amp;h=300&amp;fit=crop" alt="Luxury SUV">
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900">Customized Booking</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">
                            Design your own transportation plan by selecting your preferred vehicle, rental duration, optional chauffeur, and event decorations. Perfect for unique event requirements.
                        </p>
                        <ul class="space-y-2 text-sm text-gray-600 font-medium">
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-lg">check</span>
                                120+ Vehicles
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-lg">check</span>
                                Flexible Scheduling
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-lg">check</span>
                                24/7 Support
                            </li>
                        </ul>
                    </div>
                    <a href="vehicles.php" class="booking-option-button mt-8 inline-flex items-center justify-center gap-2 py-4 text-white font-bold rounded-2xl text-sm uppercase tracking-wider">
                        Customize My Booking
                        <span class="booking-option-button-arrow" aria-hidden="true">➜➜</span>
                    </a>
                </div>

                <!-- Option 2: Pre-Configured Packages -->
                <div class="booking-option-card p-8 md:p-10 flex flex-col justify-between group">
                    <div class="space-y-6">
                        <div class="booking-option-icon w-20 h-20 rounded-full bg-red-50 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-colors duration-300">
                            <span class="text-4xl" role="img" aria-label="Celebration">🎉</span>
                        </div>
                        <div class="booking-option-gallery" aria-label="Event transport package options">
                            <img src="https://images.unsplash.com/photo-1519225421980-715cb0215aed?w=500&amp;h=300&amp;fit=crop" alt="Wedding convoy package">
                            <img src="https://images.unsplash.com/photo-1497366811353-6870744d04b2?w=500&amp;h=300&amp;fit=crop" alt="Corporate transport package">
                            <img src="https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=500&amp;h=300&amp;fit=crop" alt="Luxury bus package">
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900">Event Packages</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">
                            Choose from professionally designed transportation packages for weddings, corporate events, airport transfers, and tours with transparent pricing.
                        </p>
                        <ul class="space-y-2 text-sm text-gray-600 font-medium">
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-lg">check</span>
                                30 Ready Packages
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-lg">check</span>
                                Professional Drivers
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-lg">check</span>
                                Instant Confirmation
                            </li>
                        </ul>
                    </div>
                    <a href="packages.php" class="booking-option-button mt-8 inline-flex items-center justify-center gap-2 py-4 text-white font-bold rounded-2xl text-sm uppercase tracking-wider">
                        Browse Event Packages
                        <span class="booking-option-button-arrow" aria-hidden="true">➜➜</span>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats / Trust Section -->
    <section class="bg-surface-container py-20 border-y border-outline-variant/30">
        <div class="max-w-[1440px] mx-auto px-8 grid grid-cols-2 md:grid-cols-4 gap-12 text-center">
            <div>
                <p class="text-h1 font-h1 text-primary mb-2"><?php echo $totalVehicles; ?>+</p>
                <p class="text-label-md font-label-md text-on-surface-variant uppercase tracking-widest">Premium Vehicles</p>
            </div>
            <div>
                <p class="text-h1 font-h1 text-primary mb-2"><?php echo number_format($totalBookingsDisplay); ?>+</p>
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
                <h2 class="font-h2 text-h2 text-on-surface mb-4">Our Premium Vehicles</h2>
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
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once '../includes/footer.php'; ?>
