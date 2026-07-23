<?php
$page_title = 'Terms of Service';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="flex-1 bg-surface-bright min-h-screen pt-36 pb-16">
    <div class="max-w-[1440px] mx-auto p-gutter lg:p-margin">
        <div class="mb-10">
            <nav class="flex items-center gap-2 text-label-sm text-gray-400 mb-4">
                <a href="index.php" class="hover:text-red-600">Home</a>
                <span class="material-symbols-outlined text-sm">chevron_right</span>
                <span class="text-gray-900 font-bold">Terms of Service</span>
            </nav>
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <h1 class="font-h1 text-h1 text-on-surface mb-2">Terms of Service</h1>
                    <p class="text-body-lg text-gray-500 max-w-2xl">Rental Terms & Conditions • Royal Lanka Rides Vehicle Rental Management System</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-8 md:p-12 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm space-y-8 font-inter text-gray-700 dark:text-gray-300">
            
            <section>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">gavel</span>
                    1. Agreement Overview & Scope
                </h2>
                <p class="leading-relaxed text-sm">
                    Welcome to Royal Lanka Rides ("Company", "we", "our", or "us"). By creating an account, reserving a vehicle, or utilizing our event vehicle rental management services, you agree to be bound by these Terms of Service. Please read them carefully before confirming any booking.
                </p>
            </section>

            <hr class="border-gray-200 dark:border-gray-700" />

            <section>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">badge</span>
                    2. Driver Eligibility & License Requirements
                </h2>
                <ul class="list-disc pl-6 space-y-2 text-sm leading-relaxed">
                    <li>The hirer/driver must be at least 21 years of age and hold a valid, unexpired national driving license or International Driving Permit (IDP).</li>
                    <li>For VIP or Event Chauffeur packages, Royal Lanka Rides provides certified professional drivers; self-drive requirements do not apply.</li>
                    <li>Verification of identity documents (NIC or Passport copy) is mandatory prior to vehicle key handover.</li>
                </ul>
            </section>

            <hr class="border-gray-200 dark:border-gray-700" />

            <section>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">payments</span>
                    3. Vehicle Rental, Deposits & Payment Terms
                </h2>
                <p class="leading-relaxed text-sm mb-3">
                    All rental rates, event packages, and security deposit amounts are clearly itemized prior to checkout.
                </p>
                <ul class="list-disc pl-6 space-y-2 text-sm leading-relaxed">
                    <li>A security deposit must be paid upon confirmation and will be refunded upon inspection after vehicle return.</li>
                    <li>Payment methods include credit/debit card, bank transfer, and online gateway integration.</li>
                    <li>Late returns exceeding a 1-hour grace period are subject to additional hourly or full-day charge surcharges.</li>
                </ul>
            </section>

            <hr class="border-gray-200 dark:border-gray-700" />

            <section>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">event_busy</span>
                    4. Cancellation & Refund Policy
                </h2>
                <ul class="list-disc pl-6 space-y-2 text-sm leading-relaxed">
                    <li>Cancellations made 48 hours prior to rental start time receive a full refund of deposit.</li>
                    <li>Cancellations within 24 to 48 hours of schedule incur a 20% administrative deduction.</li>
                    <li>No-shows or cancellations under 24 hours prior to booking start time are non-refundable.</li>
                </ul>
            </section>

            <hr class="border-gray-200 dark:border-gray-700" />

            <section>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">shield</span>
                    5. Insurance & Damage Liabilities
                </h2>
                <p class="leading-relaxed text-sm">
                    All vehicles in the Royal Lanka Rides fleet are covered under comprehensive commercial vehicle insurance. However, the hirer remains liable for any intentional damage, traffic violations, or unauthorized subletting during the rental period.
                </p>
            </section>

            <hr class="border-gray-200 dark:border-gray-700" />

            <section>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">contact_support</span>
                    6. Support & Inquiries
                </h2>
                <p class="leading-relaxed text-sm">
                    For questions regarding our Terms of Service, please visit our <a href="contact.php" class="text-primary font-bold underline">Contact Us</a> page or email <a href="mailto:support@royallankarides.com" class="text-primary font-bold underline">support@royallankarides.com</a>.
                </p>
            </section>

        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
