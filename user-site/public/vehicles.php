<?php
$page_title = 'Our Premium Fleet';
require_once '../config/database.php';
require_once '../includes/header.php';

// Get filter parameters
$event_filter = $_GET['event'] ?? '';
$type_filter = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT * FROM vehicles WHERE status = 'available'";
$params = [];

if ($search) {
    $sql .= " AND (name LIKE ? OR model LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($type_filter) {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $typeSearch = "%$type_filter%";
    $params[] = $typeSearch;
    $params[] = $typeSearch;
}

if ($event_filter) {
    $eventSearch = "%$event_filter%";
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = $eventSearch;
    $params[] = $eventSearch;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

// Get event types for filter
$stmtEvent = $pdo->query("SELECT * FROM event_types WHERE is_active = 1");
$eventTypes = $stmtEvent->fetchAll();

require_once '../includes/navbar.php';
?>

<main class="flex-1 bg-surface-bright min-h-screen pt-16">
    <div class="max-w-[1440px] mx-auto p-gutter lg:p-margin">
        <div class="mb-10">
            <nav class="flex items-center gap-2 text-label-sm text-gray-400 mb-4">
                <a href="index.php" class="hover:text-red-600">Home</a>
                <span class="material-symbols-outlined text-sm">chevron_right</span>
                <span class="text-gray-900 font-bold">Vehicle Gallery</span>
            </nav>
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <h1 class="font-h1 text-h1 text-on-surface mb-2">Vehicle Gallery</h1>
                    <p class="text-body-lg text-gray-500 max-w-2xl">Premium selections curated for high-profile events, combining luxury with operational reliability.</p>
                </div>
                <div class="flex items-center gap-4 bg-white p-1 rounded-lg border border-gray-200 shadow-sm">
                    <button id="grid-view-btn" class="px-4 py-2 bg-gray-100 text-gray-900 font-bold rounded">Grid View</button>
                    <button id="list-view-btn" class="px-4 py-2 text-gray-500 hover:text-red-600 font-medium rounded">List View</button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-[20rem_minmax(0,1fr)] gap-8">
            <aside class="hidden lg:block bg-white rounded-3xl border border-gray-200 p-6 shadow-sm sticky top-24 h-fit">
                <div class="mb-8">
                    <h4 class="font-h3 text-label-md text-on-surface uppercase tracking-widest mb-4">Filter by Event</h4>
                    <div class="flex flex-col gap-2">
                        <?php foreach ($eventTypes as $event): ?>
                            <a href="vehicles.php?event=<?php echo $event['slug']; ?>" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm <?php echo $event_filter == $event['slug'] ? 'bg-red-50 text-red-600 font-semibold border-r-4 border-red-600' : 'text-gray-600 hover:bg-gray-100'; ?> transition-all">
                                <span class="material-symbols-outlined text-[18px]">celebration</span>
                                <?php echo htmlspecialchars($event['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="mb-8">
                    <h4 class="font-h3 text-label-md text-on-surface uppercase tracking-widest mb-4">Vehicle Type</h4>
                    <div class="flex flex-col gap-3">
                        <?php $types = ['Sedan', 'SUV', 'Luxury', 'Van']; ?>
                        <?php foreach ($types as $type): ?>
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" disabled class="w-4 h-4 text-red-600 rounded border-gray-300" <?php echo $type_filter === $type ? 'checked' : ''; ?> />
                                <span class="text-body-md text-gray-600 group-hover:text-red-600 transition-colors"><?php echo $type; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <h4 class="font-h3 text-label-md text-on-surface uppercase tracking-widest mb-4">Price Range</h4>
                    <div class="px-2">
                        <input type="range" min="50" max="1000" step="50" class="w-full h-2 bg-gray-200 rounded-lg" style="accent-color: var(--primary);" />
                        <div class="flex justify-between mt-2 text-label-sm text-gray-400">
                            <span>$50/hr</span>
                            <span>$1000/hr</span>
                        </div>
                    </div>
                </div>
            </aside>

            <section class="space-y-8">
                <div class="bg-white rounded-3xl border border-gray-200 p-6 shadow-sm">
                    <form method="GET" action="vehicles.php" class="grid grid-cols-1 lg:grid-cols-[1fr_220px_220px_160px] gap-4 items-end">
                        <div>
                            <label class="block text-label-sm font-label-sm text-gray-500 mb-2">Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search vehicles or models..." class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-primary focus:border-primary" />
                        </div>
                        <div>
                            <label class="block text-label-sm font-label-sm text-gray-500 mb-2">Vehicle Type</label>
                            <select name="type" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-primary focus:border-primary">
                                <option value="">All Types</option>
                                <?php foreach ($types as $type): ?>
                                    <option value="<?php echo $type; ?>" <?php echo $type_filter == $type ? 'selected' : ''; ?>><?php echo $type; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-label-sm font-label-sm text-gray-500 mb-2">Event</label>
                            <select name="event" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-primary focus:border-primary">
                                <option value="">All Events</option>
                                <?php foreach ($eventTypes as $event): ?>
                                    <option value="<?php echo $event['slug']; ?>" <?php echo $event_filter == $event['slug'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($event['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="w-full lg:w-auto bg-red-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-red-700 transition">Search</button>
                    </form>
                </div>

                <div id="vehicles-container" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
                    <?php foreach ($vehicles as $vehicle): ?>
                        <div class="bg-white border border-gray-200 rounded-3xl overflow-hidden hover:shadow-xl transition-all duration-300 group flex flex-col vehicle-card">
                            <div class="relative h-72 w-full overflow-hidden card-img-container flex-shrink-0">
                                <?php if ($vehicle['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($vehicle['image_url']); ?>" alt="<?php echo htmlspecialchars($vehicle['name']); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" />
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center bg-gray-100">
                                        <span class="material-symbols-outlined text-6xl text-gray-400">directions_car</span>
                                    </div>
                                <?php endif; ?>
                                <div class="absolute top-4 left-4 bg-red-600 text-white px-3 py-1 rounded text-xs uppercase tracking-wider shadow-lg">Premium</div>
                                <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-md px-3 py-1 rounded text-red-600 font-bold text-sm shadow-lg">
                                    $<?php echo number_format($vehicle['price_per_hour'], 2); ?>/hr
                                </div>
                            </div>
                            <div class="p-6 flex-1 flex flex-col justify-between">
                                <div>
                                    <h3 class="font-h2 text-h3 text-on-surface mb-4"><?php echo htmlspecialchars($vehicle['name']); ?></h3>
                                    <div class="flex items-center gap-6 mb-6 text-gray-500">
                                        <div class="flex items-center gap-2"><span class="material-symbols-outlined">person</span><span class="text-sm"><?php echo $vehicle['capacity']; ?> Seats</span></div>
                                        <div class="flex items-center gap-2"><span class="material-symbols-outlined">luggage</span><span class="text-sm"><?php echo min($vehicle['capacity'], 5); ?> Bags</span></div>
                                        <div class="flex items-center gap-2"><span class="material-symbols-outlined">settings</span><span class="text-sm"><?php echo $vehicle['transmission']; ?></span></div>
                                    </div>
                                </div>
                                <a href="booking.php?vehicle=<?php echo $vehicle['id']; ?>" class="w-full inline-flex items-center justify-center gap-2 py-3 bg-red-600 text-white rounded-2xl font-bold uppercase tracking-widest hover:bg-red-700 transition">View Details <span class="material-symbols-outlined text-sm">arrow_forward</span></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($vehicles)): ?>
                    <div class="bg-white rounded-3xl border border-gray-200 p-12 text-center text-gray-500">
                        <span class="material-symbols-outlined text-6xl mb-4">search_off</span>
                        <p class="text-lg">No vehicles found matching your criteria.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const gridBtn = document.getElementById('grid-view-btn');
        const listBtn = document.getElementById('list-view-btn');
        const container = document.getElementById('vehicles-container');
        
        if (gridBtn && listBtn && container) {
            const cards = container.querySelectorAll('.vehicle-card');
            const images = container.querySelectorAll('.card-img-container');
            
            listBtn.addEventListener('click', () => {
                // Update active/inactive button styling
                listBtn.classList.add('bg-gray-100', 'text-gray-900', 'font-bold');
                listBtn.classList.remove('text-gray-500', 'hover:text-red-600', 'font-medium');
                gridBtn.classList.remove('bg-gray-100', 'text-gray-900', 'font-bold');
                gridBtn.classList.add('text-gray-500', 'hover:text-red-600', 'font-medium');
                
                // Adjust container layout
                container.classList.remove('grid-cols-1', 'md:grid-cols-2', 'xl:grid-cols-3', 'gap-8');
                container.classList.add('grid-cols-1', 'gap-6');
                
                // Adjust cards layout
                cards.forEach(card => {
                    card.classList.remove('flex-col');
                    card.classList.add('md:flex-row');
                });
                
                // Adjust images sizing
                images.forEach(img => {
                    img.classList.remove('h-72', 'w-full');
                    img.classList.add('h-72', 'md:h-auto', 'md:w-80', 'w-full');
                });
            });
            
            gridBtn.addEventListener('click', () => {
                // Update active/inactive button styling
                gridBtn.classList.add('bg-gray-100', 'text-gray-900', 'font-bold');
                gridBtn.classList.remove('text-gray-500', 'hover:text-red-600', 'font-medium');
                listBtn.classList.remove('bg-gray-100', 'text-gray-900', 'font-bold');
                listBtn.classList.add('text-gray-500', 'hover:text-red-600', 'font-medium');
                
                // Adjust container layout
                container.classList.remove('grid-cols-1', 'gap-6');
                container.classList.add('grid-cols-1', 'md:grid-cols-2', 'xl:grid-cols-3', 'gap-8');
                
                // Adjust cards layout
                cards.forEach(card => {
                    card.classList.remove('md:flex-row');
                    card.classList.add('flex-col');
                });
                
                // Adjust images sizing
                images.forEach(img => {
                    img.classList.remove('h-72', 'md:h-auto', 'md:w-80', 'w-full');
                    img.classList.add('h-72', 'w-full');
                });
            });
        }
    });
    </script>
</main>

<?php require_once '../includes/footer.php'; ?>