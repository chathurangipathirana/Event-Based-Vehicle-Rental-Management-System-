<?php
$page_title = 'Login';
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
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

<main class="min-h-screen flex flex-col md:flex-row bg-gray-50">
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

    <section class="w-full md:w-1/2 lg:w-2/5 flex items-center justify-center bg-white py-16 px-6 md:px-12 border-l border-gray-200">
        <div class="w-full max-w-md space-y-8">
            <div class="md:hidden text-center">
                <a href="index.php" class="text-3xl font-black text-primary italic uppercase tracking-tight inline-flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-3xl">directions_car</span>
                    Royal Lanka Rides
                </a>
            </div>

            <div class="space-y-2">
                <h2 class="text-3xl font-extrabold text-gray-900">Welcome Back</h2>
                <p class="text-base text-gray-700 font-medium">Sign in to your professional dashboard.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl font-medium text-sm"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="flex gap-1.5 p-1.5 bg-gray-100 rounded-xl mb-8 border border-gray-200 shadow-inner">
                <a href="login.php" aria-current="page" class="flex-1 py-3 px-4 rounded-lg text-sm font-bold bg-primary text-white shadow-md text-center transition-all duration-300 ease-out hover:bg-primary/90 hover:-translate-y-0.5">Login</a>
                <a href="register.php" class="flex-1 py-3 px-4 rounded-lg text-sm font-bold text-gray-700 text-center transition-all duration-300 ease-out hover:bg-white hover:text-primary hover:shadow-sm hover:-translate-y-0.5">Create Account</a>
            </div>

            <form method="POST" action="login.php" class="space-y-8" autocomplete="on">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                <div class="space-y-3">
                    <label class="block text-sm uppercase font-bold text-gray-900 tracking-wider" for="email">Email Address</label>
                    <input id="email" name="login_identifier" type="email" inputmode="email" required value="" autocomplete="username" autocapitalize="none" spellcheck="false" class="w-full px-5 py-4 border border-gray-300 rounded-xl bg-white text-gray-900 font-medium focus:border-primary focus:ring-2 focus:ring-primary/20 text-base shadow-sm" placeholder="Enter your email"/>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <label class="block text-sm uppercase font-bold text-gray-900 tracking-wider" for="password">Password</label>
                        <a href="contact.php" class="text-primary hover:underline text-xs font-bold">Forgot password?</a>
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
                <button type="submit" class="w-full py-3 px-4 bg-primary text-white rounded-lg text-sm font-bold shadow-md transition-all duration-300 ease-out hover:bg-primary/90 hover:shadow-lg hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.99]">Sign In</button>
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
