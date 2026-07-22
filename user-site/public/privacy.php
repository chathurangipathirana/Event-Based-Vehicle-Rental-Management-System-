<?php
$page_title = 'Privacy Policy';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="flex-1 bg-surface-bright min-h-screen pt-36 pb-16">
    <div class="max-w-[1440px] mx-auto p-gutter lg:p-margin">
        <div class="mb-10">
            <nav class="flex items-center gap-2 text-label-sm text-gray-400 mb-4">
                <a href="index.php" class="hover:text-red-600">Home</a>
                <span class="material-symbols-outlined text-sm">chevron_right</span>
                <span class="text-gray-900 font-bold">Privacy Policy</span>
            </nav>
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <h1 class="font-h1 text-h1 text-on-surface mb-2">Privacy Policy</h1>
                    <p class="text-body-lg text-gray-500 max-w-2xl">Data Protection & Privacy Policy • STS Vehicle Rental Management System</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-8 md:p-12 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm space-y-8 font-inter text-gray-700 dark:text-gray-300">
            
            <section>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">lock</span>
                    1. Information We Collect
                </h2>
                <p class="leading-relaxed text-sm mb-3">
                    At STS, we value your privacy and are committed to protecting your personal information. When you register or book a vehicle on our platform, we collect:
                </p>
                <ul class="list-disc pl-6 space-y-2 text-sm leading-relaxed">
                    <li>Personal Details: Full Name, Email Address, Phone Number, and Mailing Address.</li>
                    <li>Verification Data: National Identity Card (NIC) or Passport numbers and Driver's License details.</li>
                    <li>Booking & Payment Info: Reservation history, event schedules, and payment processing tokens.</li>
                    <li>Telemetry Data: Vehicle GPS telemetry data during active rental periods for safety and fleet monitoring.</li>
                </ul>
            </section>

            <hr class="border-gray-200 dark:border-gray-700" />

            <section>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">tune</span>
                    2. How We Use Your Information
                </h2>
                <ul class="list-disc pl-6 space-y-2 text-sm leading-relaxed">
                    <li>To process reservations, manage event logistics, and issue invoices.</li>
                    <li>To communicate booking updates, driver details, and emergency notifications.</li>
                    <li>To ensure vehicle safety, prevent fraud, and comply with Sri Lanka transportation regulations.</li>
                </ul>
            </section>

            <hr class="border-gray-200 dark:border-gray-700" />

            <section>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">share</span>
                    3. Information Sharing & Third Parties
                </h2>
                <p class="leading-relaxed text-sm">
                    We do not sell or rent your personal information to third parties. We share data strictly with authorized payment gateway providers, assigned chauffeurs/drivers, and law enforcement agencies when legally required.
                </p>
            </section>

            <hr class="border-gray-200 dark:border-gray-700" />

            <section>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">security</span>
                    4. Data Security & Storage Standards
                </h2>
                <p class="leading-relaxed text-sm">
                    All user sensitive data is stored using industry-standard encryption, SSL protocols, and restricted administrative access controls to prevent unauthorized access, loss, or disclosure.
                </p>
            </section>

            <hr class="border-gray-200 dark:border-gray-700" />

            <section>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">manage_accounts</span>
                    5. Your Data Rights & Contact
                </h2>
                <p class="leading-relaxed text-sm">
                    You have the right to inspect, update, or request deletion of your account data at any time. For privacy requests, please contact our privacy compliance team via our <a href="contact.php" class="text-primary font-bold underline">Contact Us</a> page or email <a href="mailto:privacy@stsrental.com" class="text-primary font-bold underline">privacy@stsrental.com</a>.
                </p>
            </section>

        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
