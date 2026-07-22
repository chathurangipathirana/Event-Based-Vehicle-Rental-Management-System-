<?php
$page_title = 'Contact Us';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        $error_msg = 'Please fill in all required fields (Name, Email, and Message).';
    } else {
        $success_msg = 'Thank you for reaching out! Your message has been received. Our team will contact you shortly.';
    }
}
?>

<main class="flex-1 bg-surface-bright min-h-screen pt-36 pb-16">
    <div class="max-w-[1440px] mx-auto p-gutter lg:p-margin">
        <div class="mb-10">
            <nav class="flex items-center gap-2 text-label-sm text-gray-400 mb-4">
                <a href="index.php" class="hover:text-red-600">Home</a>
                <span class="material-symbols-outlined text-sm">chevron_right</span>
                <span class="text-gray-900 font-bold">Contact Us</span>
            </nav>
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <h1 class="font-h1 text-h1 text-on-surface mb-2">Contact Us</h1>
                    <p class="text-body-lg text-gray-500 max-w-2xl">Contact Our Logistics & Support Team • We are available 24/7 to assist you.</p>
                </div>
            </div>
        </div>

        <?php if (!empty($success_msg)): ?>
            <div class="max-w-3xl mx-auto mb-8 p-4 bg-emerald-50 border border-emerald-300 text-emerald-800 rounded-xl flex items-center gap-3">
                <span class="material-symbols-outlined text-emerald-600">check_circle</span>
                <span><?php echo htmlspecialchars($success_msg); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="max-w-3xl mx-auto mb-8 p-4 bg-rose-50 border border-rose-300 text-rose-800 rounded-xl flex items-center gap-3">
                <span class="material-symbols-outlined text-rose-600">error</span>
                <span><?php echo htmlspecialchars($error_msg); ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Contact Cards (Left 5 Cols) -->
            <div class="lg:col-span-5 flex flex-col gap-6">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0">
                        <span class="material-symbols-outlined text-2xl">location_on</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white text-lg">Main Logistics Hub</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm font-inter mt-1">STS Headquarters & Fleet Operations Center<br/>No. 45, Galle Road, Colombo 03, Sri Lanka</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0">
                        <span class="material-symbols-outlined text-2xl">call</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white text-lg">Hotline & Support</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm font-inter mt-1">+94 11 234 5678 (24/7 Dispatch)</p>
                        <p class="text-gray-600 dark:text-gray-400 text-sm font-inter">+94 77 123 4567 (WhatsApp Logistics)</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0">
                        <span class="material-symbols-outlined text-2xl">mail</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white text-lg">Email Inquiry</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm font-inter mt-1">support@stsrental.com</p>
                        <p class="text-gray-600 dark:text-gray-400 text-sm font-inter">info@sts.com</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0">
                        <span class="material-symbols-outlined text-2xl">schedule</span>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white text-lg">Operating Hours</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm font-inter mt-1">Fleet Operations: 24 Hours / 7 Days</p>
                        <p class="text-gray-600 dark:text-gray-400 text-sm font-inter">Office Admin: Mon - Sat (8:00 AM - 6:00 PM)</p>
                    </div>
                </div>
            </div>

            <!-- Contact Form (Right 7 Cols) -->
            <div class="lg:col-span-7 bg-white dark:bg-gray-800 p-8 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Send Us a Message</h2>
                <form method="POST" action="contact.php" class="flex flex-col gap-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase mb-2">Your Name *</label>
                            <input type="text" name="name" required placeholder="John Doe" class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase mb-2">Email Address *</label>
                            <input type="email" name="email" required placeholder="john@example.com" class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:border-primary">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase mb-2">Inquiry Category</label>
                        <select name="subject" class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:border-primary">
                            <option value="General Inquiry">General Inquiry</option>
                            <option value="Event Vehicle Booking">Event Vehicle Booking</option>
                            <option value="Fleet Logistics Support">Fleet Logistics Support</option>
                            <option value="Custom Package Request">Custom Package Request</option>
                            <option value="Feedback / Complaints">Feedback / Complaints</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase mb-2">Your Message *</label>
                        <textarea name="message" rows="5" required placeholder="Write your message or event details here..." class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:border-primary"></textarea>
                    </div>

                    <button type="submit" class="w-full py-4 rounded-xl bg-primary text-white font-bold text-base hover:bg-primary/90 transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">send</span>
                        Send Message
                    </button>
                </form>
            </div>

        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
