<?php
$page_title = 'Login';
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = isset($_GET['reset']) && $_GET['reset'] === 'success' ? 'Your password has been reset. You can now sign in.' : '';
$redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? '';
if (!preg_match('/^booking\.php\?(?:package_id=\d+(?:&vehicle=\d+)?|vehicle=\d+)$/', $redirect)) {
    $redirect = '';
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['login_identifier'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $staySignedIn = isset($_POST['stay_signed_in']);

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                if ($staySignedIn) {
                    $cookie = session_get_cookie_params();
                    setcookie(session_name(), session_id(), [
                        'expires' => time() + (86400 * 30),
                        'path' => $cookie['path'] ?: '/',
                        'domain' => $cookie['domain'],
                        'secure' => $cookie['secure'],
                        'httponly' => $cookie['httponly'],
                        'samesite' => 'Lax'
                    ]);
                }

                header('Location: ' . ($redirect ?: 'dashboard.php'));
                exit();
            } else {
                $error = 'Invalid email or password';
            }
        } catch (PDOException $e) {
            $error = 'Database error occurred. Please try again.';
        }
    }
}
require_once '../includes/header.php';
?>

<main class="min-h-screen flex flex-col md:flex-row bg-[#f3fffe]">
    <section class="hidden md:flex md:w-1/2 lg:w-3/5 bg-slate-900 relative overflow-hidden">
        <div class="absolute inset-0 z-0">
            <img class="w-full h-full object-cover opacity-60" alt="Royal Lanka Rides Performance Vehicle" src="assets/vehicles/toyota-premio.png"/>
            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-black/20"></div>
        </div>
        <div class="relative z-10 p-12 flex flex-col justify-between h-full">
            <div>
                <a href="index.php" class="text-2xl font-black text-white italic uppercase tracking-tight flex items-center gap-2">
                    <span class="material-symbols-outlined text-cyan-400 text-3xl">directions_car</span>
                    Royal Lanka Rides
                </a>
            </div>
            <div class="max-w-lg">
                <h1 class="text-4xl font-extrabold text-white mb-4 leading-tight">Reliable Event Transportation</h1>
                <p class="text-lg text-white font-semibold">Manage bookings, drivers, vehicles, and billing from one powerful platform.</p>
            </div>
            <div class="grid grid-cols-2 gap-x-8 gap-y-3 text-white">
                <div class="flex items-center space-x-2">
                    <span class="material-symbols-outlined text-cyan-400">check_circle</span>
                    <span class="text-xs uppercase font-bold tracking-wider">Secure Booking</span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="material-symbols-outlined text-cyan-400">check_circle</span>
                    <span class="text-xs uppercase font-bold tracking-wider">Instant Confirmation</span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="material-symbols-outlined text-cyan-400">check_circle</span>
                    <span class="text-xs uppercase font-bold tracking-wider">24/7 Customer Support</span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="material-symbols-outlined text-cyan-400">check_circle</span>
                    <span class="text-xs uppercase font-bold tracking-wider">Verified Drivers</span>
                </div>
            </div>
        </div>
    </section>

    <section class="w-full md:w-1/2 lg:w-2/5 flex items-center justify-center bg-[#f3fffe] py-16 px-6 md:px-12 shadow-2xl z-10">
        <div class="w-full max-w-lg space-y-6 bg-white rounded-3xl border border-teal-100 p-8 md:p-10 shadow-xl">
            <div class="md:hidden text-center mb-6">
                <a href="index.php" class="text-3xl font-black text-primary italic uppercase tracking-tight inline-flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-3xl">directions_car</span>
                    Royal Lanka Rides
                </a>
            </div>

            <div class="space-y-1">
                <h2 class="text-3xl font-black text-gray-900 tracking-tight">Welcome Back</h2>
                <p class="text-sm font-medium text-gray-700">Sign in to manage your Royal Lanka Rides bookings.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl font-medium text-sm"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 p-4 rounded-xl font-medium text-sm"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="flex w-full border-b border-gray-200">
                <a href="login.php" aria-current="page" class="flex-1 py-3 text-sm font-bold text-primary text-center border-b-2 border-primary -mb-px">Login</a>
                <a href="register.php" class="flex-1 py-3 text-sm font-bold text-gray-600 hover:text-primary text-center transition-colors">Create Account</a>
            </div>

            <form method="POST" action="login.php" class="space-y-4" autocomplete="on">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                <div>
                    <label class="block text-xs uppercase font-bold text-gray-900 tracking-wider mb-1" for="email">Email Address</label>
                    <input id="email" name="login_identifier" type="email" inputmode="email" required value="" autocomplete="username" autocapitalize="none" spellcheck="false" class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-gray-900 font-medium placeholder-gray-400 focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm" placeholder="john@example.com"/>
                </div>
                <div>
                    <div class="flex justify-between items-center">
                        <label class="block text-xs uppercase font-bold text-gray-900 tracking-wider mb-1" for="password">Password</label>
                        <a href="forgot-password.php" class="text-primary hover:underline text-xs font-bold">Forgot password?</a>
                    </div>
                    <div class="relative">
                        <input id="password" name="password" type="password" required autocomplete="current-password" class="w-full px-5 py-4 border border-gray-300 rounded-xl bg-white text-gray-900 font-medium focus:border-primary focus:ring-2 focus:ring-primary/20 text-base pr-14 shadow-sm" placeholder="••••••••"/>
                        <button type="button" onclick="togglePassword()" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-900">
                            <span id="pwd-icon" class="material-symbols-outlined text-xl">visibility</span>
                        </button>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <input id="stay_signed_in" name="stay_signed_in" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary cursor-pointer" <?php echo isset($_POST['stay_signed_in']) ? 'checked' : ''; ?> />
                    <label for="stay_signed_in" class="text-xs font-bold text-gray-800 cursor-pointer">Stay signed in for 30 days</label>
                </div>
                <button type="submit" class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-4 rounded-xl text-sm transition-all duration-200 shadow-lg hover:shadow-xl active:scale-[0.99] mt-2">Sign In</button>
            </form>

            <p class="text-center text-xs font-medium text-gray-700">By continuing, you agree to Royal Lanka Rides's <a class="text-gray-900 font-bold underline hover:text-primary" href="terms.php">Terms of Service</a> and <a class="text-gray-900 font-bold underline hover:text-primary" href="privacy.php">Privacy Policy</a>.</p>
        </div>
    </section>
</main>

<script>
    function togglePassword() {
        const pwd = document.getElementById('password');
        const icon = document.getElementById('pwd-icon');
        if (!pwd || !icon) return;
        if (pwd.type === 'password') {
            pwd.type = 'text';
            icon.textContent = 'visibility_off';
        } else {
            pwd.type = 'password';
            icon.textContent = 'visibility';
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>
