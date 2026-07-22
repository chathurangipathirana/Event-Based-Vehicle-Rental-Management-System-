<?php
$page_title = 'Login';
require_once '../config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

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

                if ($remember) {
                    setcookie('user_email', $email, time() + (86400 * 30), "/");
                }

                header('Location: dashboard.php');
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
            <img class="w-full h-full object-cover opacity-60" alt="STS Performance Vehicle" src="assets/vehicles/toyota-premio.png"/>
            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-black/20"></div>
        </div>
        <div class="relative z-10 p-12 flex flex-col justify-between h-full">
            <div>
                <a href="index.php" class="text-2xl font-black text-white italic uppercase tracking-tight flex items-center gap-2">
                    <span class="material-symbols-outlined text-cyan-400 text-3xl">directions_car</span>
                    STS
                </a>
            </div>
            <div class="max-w-lg">
                <h1 class="text-4xl font-extrabold text-white mb-4 leading-tight">Operational Excellence, Unlocked.</h1>
                <p class="text-lg text-white font-semibold">Manage your high-performance fleet with precision. Access logistics, billing, and scheduling in one centralized hub.</p>
            </div>
            <div class="flex items-center space-x-6 text-white">
                <div class="flex items-center space-x-2">
                    <span class="material-symbols-outlined text-cyan-400">verified_user</span>
                    <span class="text-xs uppercase font-bold tracking-wider">Secure Login</span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="material-symbols-outlined text-cyan-400">speed</span>
                    <span class="text-xs uppercase font-bold tracking-wider">Real-time Data</span>
                </div>
            </div>
        </div>
    </section>

    <section class="w-full md:w-1/2 lg:w-2/5 flex items-center justify-center bg-white py-16 px-6 md:px-12 border-l border-gray-200">
        <div class="w-full max-w-md space-y-8">
            <div class="md:hidden text-center">
                <a href="index.php" class="text-3xl font-black text-primary italic uppercase tracking-tight inline-flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-3xl">directions_car</span>
                    STS
                </a>
            </div>

            <div class="space-y-2">
                <h2 class="text-3xl font-extrabold text-gray-900">Welcome Back</h2>
                <p class="text-base text-gray-700 font-medium">Sign in to your professional dashboard.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl font-medium text-sm"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="flex p-1 bg-gray-100 rounded-lg mb-8 border border-gray-200">
                <a href="login.php" class="flex-1 py-2.5 rounded-md text-xs font-bold bg-white shadow-sm text-primary text-center">Login</a>
                <a href="register.php" class="flex-1 py-2.5 rounded-md text-xs font-bold text-gray-700 hover:text-gray-900 text-center">Create Account</a>
            </div>

            <form method="POST" action="login.php" class="space-y-6">
                <div class="space-y-2">
                    <label class="block text-xs uppercase font-bold text-gray-900 tracking-wider" for="email">Work Email</label>
                    <input id="email" name="email" type="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? $_COOKIE['user_email'] ?? ''); ?>" class="w-full px-4 py-3.5 border border-gray-300 rounded-xl bg-white text-gray-900 font-medium focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm" placeholder="name@company.com"/>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <label class="block text-xs uppercase font-bold text-gray-900 tracking-wider" for="password">Password</label>
                        <a href="contact.php" class="text-primary hover:underline text-xs font-bold">Forgot password?</a>
                    </div>
                    <div class="relative">
                        <input id="password" name="password" type="password" required class="w-full px-4 py-3.5 border border-gray-300 rounded-xl bg-white text-gray-900 font-medium focus:border-primary focus:ring-2 focus:ring-primary/20 text-sm pr-12" placeholder="••••••••"/>
                        <button type="button" onclick="togglePassword()" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-900">
                            <span id="pwd-icon" class="material-symbols-outlined text-xl">visibility</span>
                        </button>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <input id="remember" name="remember" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-primary focus:ring-primary cursor-pointer" <?php echo isset($_POST['remember']) ? 'checked' : ''; ?> />
                    <label for="remember" class="text-xs font-bold text-gray-800 cursor-pointer">Stay signed in for 30 days</label>
                </div>
                <button type="submit" class="w-full bg-primary hover:bg-primary/90 text-white py-4 rounded-xl text-sm font-bold transition-all shadow-md active:scale-[0.99]">Sign In</button>
            </form>

            <p class="text-center text-xs font-medium text-gray-700">By continuing, you agree to STS's <a class="text-gray-900 font-bold underline hover:text-primary" href="terms.php">Terms of Service</a> and <a class="text-gray-900 font-bold underline hover:text-primary" href="privacy.php">Privacy Policy</a>.</p>
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
