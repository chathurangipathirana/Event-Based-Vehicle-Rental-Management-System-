<?php
$page_title = 'Register';
require_once '../config/database.php';

$error = '';
$success = '';
$redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? '';
if (!preg_match('/^booking\.php\?(?:package_id=\d+(?:&vehicle=\d+)?|vehicle=\d+)$/', $redirect)) {
    $redirect = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = filter_var($_POST['registration_email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');

    if (empty($full_name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already registered';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, phone, company_name) VALUES (?, ?, ?, ?, ?)");
                
                if ($stmt->execute([$full_name, $email, $hashed_password, $phone, $company_name])) {
                    $success = 'Registration successful! Redirecting to login...';
                    header('refresh:2;url=login.php' . ($redirect ? '?redirect=' . rawurlencode($redirect) : ''));
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error occurred. Please try again.';
        }
    }
}

require_once '../includes/header.php';
?>

<main class="min-h-screen flex flex-col md:flex-row bg-slate-100">
    <!-- Left Hero Image Banner -->
    <section class="hidden md:flex md:w-1/2 lg:w-3/5 bg-slate-900 relative overflow-hidden">
        <div class="absolute inset-0 z-0">
            <img class="w-full h-full object-cover opacity-60" alt="STS Fleet Vehicle" src="assets/vehicles/honda-vezel.png"/>
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/60 to-slate-900/30"></div>
        </div>
        <div class="relative z-10 p-12 lg:p-16 flex flex-col justify-between h-full w-full">
            <div>
                <a href="index.php" class="text-2xl font-black text-white italic uppercase tracking-tight flex items-center gap-2">
                    <span class="material-symbols-outlined text-cyan-400 text-3xl">directions_car</span>
                    STS
                </a>
            </div>
            <div class="max-w-xl">
                <h1 class="text-4xl lg:text-5xl font-extrabold text-white mb-4 leading-tight drop-shadow-md">
                    Join STS Vehicle Rentals
                </h1>
                <p class="text-base lg:text-lg text-white font-semibold leading-relaxed drop-shadow">
                    Create your free account today to manage vehicle rentals, book luxury event fleets, and access exclusive corporate rates.
                </p>
            </div>
            <div class="flex items-center space-x-8 text-white">
                <div class="flex items-center space-x-2">
                    <span class="material-symbols-outlined text-cyan-400 text-xl">event_available</span>
                    <span class="text-xs uppercase font-bold tracking-wider text-white">Instant Reservations</span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="material-symbols-outlined text-cyan-400 text-xl">support_agent</span>
                    <span class="text-xs uppercase font-bold tracking-wider text-white">24/7 Support</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Right Registration Form Section -->
    <section class="w-full md:w-1/2 lg:w-2/5 flex items-center justify-center bg-white py-16 px-6 md:px-12 shadow-2xl z-10">
        <div class="w-full max-w-md space-y-6">
            
            <div class="md:hidden text-center mb-6">
                <a href="index.php" class="text-3xl font-black text-primary italic uppercase tracking-tight inline-flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-3xl">directions_car</span>
                    STS
                </a>
            </div>

            <div class="space-y-1">
                <h2 class="text-3xl font-black text-gray-900 tracking-tight">Create Account</h2>
                <p class="text-sm font-medium text-gray-700">Join STS today for fast event bookings.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="p-4 bg-rose-50 border border-rose-300 rounded-xl text-rose-900 text-sm font-semibold flex items-center gap-2">
                    <span class="material-symbols-outlined text-rose-600">error</span>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="p-4 bg-emerald-50 border border-emerald-300 rounded-xl text-emerald-900 text-sm font-semibold flex items-center gap-2">
                    <span class="material-symbols-outlined text-emerald-600">check_circle</span>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <!-- Tabs -->
            <div class="flex p-1 bg-slate-100 rounded-xl border border-slate-200">
                <a href="login.php" class="flex-1 py-2.5 rounded-lg text-xs font-bold text-gray-700 hover:text-gray-900 text-center">
                    Login
                </a>
                <a href="register.php" class="flex-1 py-2.5 rounded-lg text-xs font-bold bg-white text-primary shadow-sm text-center">
                    Create Account
                </a>
            </div>

            <form method="POST" action="register.php" class="space-y-4" autocomplete="off">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                <div>
                    <label class="block text-xs uppercase font-bold text-gray-900 tracking-wider mb-1" for="full_name">
                        Full Name <span class="text-rose-600">*</span>
                    </label>
                    <input id="full_name" name="full_name" type="text" required 
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-900 font-medium placeholder-gray-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm" 
                           placeholder="John Doe"/>
                </div>

                <div>
                    <label class="block text-xs uppercase font-bold text-gray-900 tracking-wider mb-1" for="email">
                        Email Address <span class="text-rose-600">*</span>
                    </label>
                    <input id="email" name="registration_email" type="text" inputmode="email" required autocomplete="one-time-code" autocapitalize="none" spellcheck="false"
                           value="" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-900 font-medium placeholder-gray-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm" 
                           placeholder="john@example.com"/>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs uppercase font-bold text-gray-900 tracking-wider mb-1" for="phone">
                            Phone Number
                        </label>
                        <input id="phone" name="phone" type="tel" 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-900 font-medium placeholder-gray-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm" 
                               placeholder="+94 77 123 4567"/>
                    </div>
                    <div>
                        <label class="block text-xs uppercase font-bold text-gray-900 tracking-wider mb-1" for="company_name">
                            Company Name
                        </label>
                        <input id="company_name" name="company_name" type="text" 
                               value="<?php echo htmlspecialchars($_POST['company_name'] ?? ''); ?>" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-900 font-medium placeholder-gray-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm" 
                               placeholder="Optional"/>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs uppercase font-bold text-gray-900 tracking-wider mb-1" for="password">
                            Password <span class="text-rose-600">*</span>
                        </label>
                        <input id="password" name="password" type="password" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-900 font-medium placeholder-gray-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm" 
                               placeholder="Min. 6 chars"/>
                    </div>
                    <div>
                        <label class="block text-xs uppercase font-bold text-gray-900 tracking-wider mb-1" for="confirm_password">
                            Confirm Password <span class="text-rose-600">*</span>
                        </label>
                        <input id="confirm_password" name="confirm_password" type="password" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-900 font-medium placeholder-gray-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm" 
                               placeholder="Repeat password"/>
                    </div>
                </div>

                <button type="submit" class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-4 rounded-xl text-sm transition-all duration-200 shadow-lg hover:shadow-xl active:scale-[0.99] mt-2">
                    Create Account
                </button>
            </form>

            <p class="text-center text-xs font-semibold text-gray-700">
                Already have an account? <a href="login.php" class="text-primary font-bold underline hover:text-primary/80">Sign in</a>
            </p>

        </div>
    </section>
</main>

<script>
    // Prevent browser-saved login details from appearing in the registration form.
    window.addEventListener('DOMContentLoaded', () => {
        const email = document.getElementById('email');
        email.value = '';
        email.readOnly = true;
        email.addEventListener('focus', () => {
            email.readOnly = false;
            email.value = '';
        }, { once: true });
        window.setTimeout(() => { email.value = ''; }, 500);
    });
</script>

<?php require_once '../includes/footer.php'; ?>
