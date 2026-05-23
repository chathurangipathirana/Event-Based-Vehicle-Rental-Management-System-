<?php
$page_title = 'Login';
require_once '../config/database.php';
require_once '../includes/header.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
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
    }
}
?>

<main class="min-h-screen flex flex-col md:flex-row">
    <section class="hidden md:flex md:w-1/2 lg:w-3/5 bg-inverse-surface relative overflow-hidden">
        <div class="absolute inset-0 z-0">
            <img class="w-full h-full object-cover" alt="FleetElite Performance Vehicle" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBmKQ_OdZrS02QVd1Umy0JvqwHkxtyvZrcw6tpl3gn93VHOi-PEDACeC2pTm6WfMIX5lt5AaWR7xS9utvtCRzuGz8KNMGvtR6bUfHgQZu58bRlvwDQzzSNJIZ45glD5OcaJF6c8WAlG8aWk4RGDqKjTXCgQVa1eNhxvmzD4cuBaov_jAabt-udP3CpCwdj6OF9HCKylLAG43hN_dYhDo0i-8Y_RPmOfaUkBlaa8EEgRiRvAizdU0lammY4oeA9vreIC0BLpSAA1_P76"/>
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-black/20"></div>
        </div>
        <div class="relative z-10 p-12 flex flex-col justify-between h-full">
            <div>
                <span class="text-xl font-black text-white italic uppercase tracking-tight">FleetElite</span>
            </div>
            <div class="max-w-lg">
                <h1 class="font-h1 text-h1 text-white mb-4 leading-tight">Operational Excellence, Unlocked.</h1>
                <p class="font-body-lg text-body-lg text-white/80">Manage your high-performance fleet with precision. Access logistics, billing, and scheduling in one centralized hub.</p>
            </div>
            <div class="flex items-center space-x-6 text-white/60">
                <div class="flex items-center space-x-2">
                    <span class="material-symbols-outlined text-white">verified_user</span>
                    <span class="font-label-sm uppercase">Secure Login</span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="material-symbols-outlined text-white">speed</span>
                    <span class="font-label-sm uppercase">Real-time Data</span>
                </div>
            </div>
        </div>
    </section>

    <section class="w-full md:w-1/2 lg:w-2/5 flex items-center justify-center bg-surface-container-lowest py-16 px-6 md:px-12">
        <div class="w-full max-w-md space-y-8">
            <div class="md:hidden text-center">
                <span class="text-2xl font-black text-primary italic uppercase tracking-tight">FleetElite</span>
            </div>

            <div class="space-y-2">
                <h2 class="font-h2 text-h2 text-on-surface">Welcome Back</h2>
                <p class="font-body-md text-body-md text-on-surface-variant">Sign in to your professional dashboard.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="flex p-1 bg-surface-container rounded-lg mb-8">
                <button class="flex-1 py-2 rounded-md font-label-md bg-white shadow-sm text-primary">Login</button>
                <button class="flex-1 py-2 rounded-md font-label-md text-on-surface-variant">Create Account</button>
            </div>

            <form method="POST" action="" class="space-y-6">
                <div class="space-y-2">
                    <label class="block font-label-md text-on-surface" for="email">Work Email</label>
                    <input id="email" name="email" type="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? $_COOKIE['user_email'] ?? ''); ?>" class="w-full px-4 py-3 border border-outline-variant rounded-lg bg-surface-container-low text-on-surface focus:ring-primary focus:border-primary form-input-focus" placeholder="name@company.com"/>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <label class="block font-label-md text-on-surface" for="password">Password</label>
                        <a href="#" class="text-primary hover:underline text-sm">Forgot password?</a>
                    </div>
                    <div class="relative">
                        <input id="password" name="password" type="password" required class="w-full px-4 py-3 border border-outline-variant rounded-lg bg-surface-container-low text-on-surface focus:ring-primary focus:border-primary form-input-focus" placeholder="••••••••"/>
                        <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant">
                            <span class="material-symbols-outlined">visibility</span>
                        </button>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <input id="remember" name="remember" type="checkbox" class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary" <?php echo isset($_POST['remember']) ? 'checked' : ''; ?> />
                    <label for="remember" class="font-label-sm text-label-sm text-on-surface-variant">Stay signed in for 30 days</label>
                </div>
                <button type="submit" class="w-full bg-primary hover:bg-primary-container text-on-primary py-4 rounded-lg font-h3 text-h3 transition-all duration-200 active:scale-[0.98] shadow-lg shadow-primary/20">Sign In</button>
            </form>

            <p class="text-center font-label-sm text-label-sm text-on-surface-variant">By continuing, you agree to FleetElite's <a class="text-on-surface underline" href="#">Terms of Service</a> and <a class="text-on-surface underline" href="#">Privacy Policy</a>.</p>
        </div>
    </section>
</main>

<?php require_once '../includes/footer.php'; ?>