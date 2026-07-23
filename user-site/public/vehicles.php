<?php
$page_title = 'Our Premium Vehicles';
require_once '../config/database.php';
require_once '../includes/header.php';

// Get search parameter
$search = trim($_GET['search'] ?? '');

// Build query
$sql = "SELECT * FROM vehicles WHERE status = 'available'";
$params = [];

if ($search !== '') {
    $sql .= " AND (name LIKE ? OR model LIKE ? OR description LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

require_once '../includes/navbar.php';
?>

<main class="flex-1 bg-surface-bright min-h-screen pt-36 pb-16">
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
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <form method="GET" action="vehicles.php">
                        <label for="vehicle-search" class="sr-only">Search vehicles</label>
                        <input id="vehicle-search" type="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search" class="w-full sm:w-52 px-4 py-2 border border-gray-200 rounded-lg focus:ring-primary focus:border-primary" />
                    </form>
                    <div class="flex items-center gap-1 bg-white p-1 rounded-lg border border-gray-200 shadow-sm">
                        <button id="grid-view-btn" class="px-4 py-2 bg-gray-100 text-gray-900 font-bold rounded">Grid View</button>
                        <button id="list-view-btn" class="px-4 py-2 text-gray-500 hover:text-red-600 font-medium rounded">List View</button>
                    </div>
                </div>
            </div>
        </div>

        <section class="space-y-8">
                <div id="vehicles-container" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
                    <?php foreach ($vehicles as $vehicle): ?>
                        <div class="bg-white border border-gray-200 rounded-3xl overflow-hidden hover:shadow-xl transition-all duration-300 group flex flex-col vehicle-card">
                            <div class="relative h-72 w-full overflow-hidden card-img-container flex-shrink-0">
                                <?php $vehicleImage = getVehicleImageUrl($vehicle['image_url'], $vehicle['name']); ?>
                                <img src="<?php echo htmlspecialchars($vehicleImage); ?>" alt="<?php echo htmlspecialchars($vehicle['name']); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" onerror="this.onerror=null;this.src='assets/vehicle-default.svg'" />
                                <div class="absolute top-4 left-4 bg-red-600 text-white px-3 py-1 rounded text-xs uppercase tracking-wider shadow-lg">Premium</div>
                                <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-md px-3 py-1 rounded text-red-600 font-bold text-sm shadow-lg">
                                    LKR <?php echo number_format($vehicle['price_per_hour'], 2); ?>/hr
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
                                <a href="vehicle-details.php?id=<?php echo $vehicle['id']; ?>" class="w-full inline-flex items-center justify-center gap-2 py-3 bg-red-600 text-white rounded-2xl font-bold uppercase tracking-widest hover:bg-red-700 transition">View Details <span class="material-symbols-outlined text-sm">arrow_forward</span></a>
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
