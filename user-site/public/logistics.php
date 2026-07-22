<?php
$page_title = 'Fleet Logistics';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="min-h-screen pt-48 pb-16 bg-slate-50 dark:bg-gray-900">
    <div class="max-w-[1200px] mx-auto px-6">
        
        <!-- Hero Header -->
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="inline-block px-4 py-1.5 rounded-full bg-cyan-100 text-primary text-xs font-bold uppercase tracking-wider mb-4">Event Fleet & Transport Management</span>
            <h1 class="text-3xl md:text-5xl font-extrabold text-gray-900 dark:text-white tracking-tight mb-4">Seamless Event Logistics & Fleet Solutions</h1>
            <p class="text-gray-600 dark:text-gray-400 font-inter text-base md:text-lg leading-relaxed">
                STS provides complete end-to-end event fleet logistics across Sri Lanka — supporting weddings, corporate summits, VIP delegations, and large-scale entertainment events with precision timing and luxury vehicles.
            </p>
        </div>

        <!-- Key Logistics Capabilities Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
            
            <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-all">
                <div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center text-primary mb-6">
                    <span class="material-symbols-outlined text-3xl">celebration</span>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Wedding & VIP Convoys</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm font-inter leading-relaxed mb-4">
                    Coordinated luxury vehicle processions with uniformed chauffeurs, flower decoration setups, and on-ground wedding coordinators.
                </p>
                <a href="packages.php" class="text-primary font-bold text-sm inline-flex items-center gap-1 hover:underline">
                    View Wedding Packages <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>

            <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-all">
                <div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center text-primary mb-6">
                    <span class="material-symbols-outlined text-3xl">domain</span>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Corporate Event Fleets</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm font-inter leading-relaxed mb-4">
                    High-capacity transport for international delegates, conference shuttle services, and executive Sedans or SUVs on standby.
                </p>
                <a href="packages.php" class="text-primary font-bold text-sm inline-flex items-center gap-1 hover:underline">
                    View Corporate Fleets <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>

            <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-all">
                <div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center text-primary mb-6">
                    <span class="material-symbols-outlined text-3xl">local_dispatch</span>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">On-Site Fleet Control</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm font-inter leading-relaxed mb-4">
                    Dedicated live dispatch team monitoring route optimization, GPS tracking, and real-time vehicle allocation for smooth flow.
                </p>
                <a href="contact.php" class="text-primary font-bold text-sm inline-flex items-center gap-1 hover:underline">
                    Speak to Logistics Manager <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>

            <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-all">
                <div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center text-primary mb-6">
                    <span class="material-symbols-outlined text-3xl">airport_shuttle</span>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Airport & Hotel Transfers</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm font-inter leading-relaxed mb-4">
                    Punctual airport pick-ups and luxury hotel transfers for guests with flight schedule tracking and meet-and-greet services.
                </p>
                <a href="vehicles.php" class="text-primary font-bold text-sm inline-flex items-center gap-1 hover:underline">
                    Explore Transfer Vehicles <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>

            <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-all">
                <div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center text-primary mb-6">
                    <span class="material-symbols-outlined text-3xl">badge</span>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Professional Chauffeurs</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm font-inter leading-relaxed mb-4">
                    Licensed, vetted, and multi-lingual professional drivers trained in defensive driving, event etiquette, and executive protocol.
                </p>
                <a href="contact.php" class="text-primary font-bold text-sm inline-flex items-center gap-1 hover:underline">
                    Inquire Chauffeur Services <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>

            <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-all">
                <div class="w-14 h-14 rounded-2xl bg-primary/10 flex items-center justify-center text-primary mb-6">
                    <span class="material-symbols-outlined text-3xl">shield_check</span>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Backup Fleet Security</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm font-inter leading-relaxed mb-4">
                    Zero-downtime guarantee with standby replacement vehicles on call across major island hubs for zero event disruption.
                </p>
                <a href="contact.php" class="text-primary font-bold text-sm inline-flex items-center gap-1 hover:underline">
                    Get Customized Quote <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>

        </div>

        <!-- Call to Action Banner -->
        <div class="bg-primary text-white p-10 md:p-14 rounded-3xl text-center shadow-lg relative overflow-hidden">
            <h2 class="text-2xl md:text-3xl font-extrabold mb-4 relative z-10">Planning a Special Event or Corporate Gathering?</h2>
            <p class="text-cyan-100 max-w-2xl mx-auto font-inter text-base mb-8 relative z-10">
                Let our logistics team handle all vehicle schedules, route planning, and driver assignments tailored to your event budget.
            </p>
            <div class="flex flex-wrap justify-center gap-4 relative z-10">
                <a href="packages.php" class="px-8 py-4 bg-white text-primary font-bold rounded-xl hover:bg-slate-100 transition-all">Browse Packages</a>
                <a href="contact.php" class="px-8 py-4 bg-primary/30 border border-white/30 text-white font-bold rounded-xl hover:bg-white/10 transition-all">Request Custom Logistics</a>
            </div>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
