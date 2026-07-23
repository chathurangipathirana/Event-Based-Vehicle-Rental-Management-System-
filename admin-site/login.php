<?php
session_start();

if (isset($_SESSION['admin_id']) && isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin') {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    // Demo credentials - In production, check database
    if (($email === 'admin@sts.com' || $email === 'admin@fleetelite.com' || $email === 'admin@royallankarides.com') && $password === 'Admin123!') {
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_name'] = 'Administrator';
        $_SESSION['admin_email'] = $email;
        $_SESSION['admin_role'] = 'admin';
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Invalid email or password';
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Admin Login | Royal Lanka Rides</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet"/>
<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 48;
        font-kerning: normal;
        text-rendering: optimizeLegibility;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }
    :root {
        --surface: rgba(255, 255, 255, 0.16);
        --surface-card: rgba(0, 0, 0, 0.24);
        --primary: #02414a;
        --primary-soft: #b8ebf7;
        --primary-hover: #0d5260;
        --danger: #ba1a1a;
        --on-primary: #ffffff;
    }
    body {
        font-family: 'Inter', sans-serif;
        min-height: 100vh;
        margin: 0;
        background-color: #07121a;
        background-image:
            radial-gradient(circle at top left, rgba(91, 198, 255, 0.18), transparent 22%),
            radial-gradient(circle at bottom right, rgba(9, 147, 255, 0.16), transparent 20%),
            linear-gradient(135deg, rgba(10, 34, 58, 0.94), rgba(4, 13, 23, 0.96)),
            url('../user-site/public/assets/2.jpg');
        background-size: cover;
        background-position: center;
        background-blend-mode: screen, screen, overlay, normal;
        color: #f8fafc;
    }
    body::before {
        content: '';
        position: fixed;
        inset: 0;
        background-image: url('../user-site/public/assets/2.jpg');
        background-size: cover;
        background-position: center;
        pointer-events: none;
        z-index: -1;
    }
    body::after {
        content: '';
        position: fixed;
        inset: 0;
        background: linear-gradient(180deg, rgba(255,255,255,0.06) 0%, rgba(0,0,0,0.25) 100%);
        pointer-events: none;
        z-index: 0;
    }
    .alert-error { background: #ffdad6; color: #93000a; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ffb4ab; }
    .brand-icon { background-color: var(--primary); color: var(--on-primary); }
    .login-input { background: rgba(255, 255, 255, 0.92); color: #111827; }
    .login-input::placeholder { color: #6b7280; opacity: 1; }
    .login-input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(36,84,98,0.08); outline: none; }
    .btn-primary { background-color: var(--primary); color: var(--on-primary); }
    .btn-primary:hover { background-color: var(--primary-hover); }
    .login-card {
        background: #ffffff;
        box-shadow: 0 28px 70px rgba(15, 23, 42, 0.12);
    }
</style>
</head>
<body class="min-h-screen flex items-center justify-end py-12 px-6 relative">
    <div class="max-w-md w-full login-card rounded-[32px] p-8 mr-8">
        <div class="text-center mb-8">
            <div class="w-20 h-20 brand-icon rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-white text-4xl">admin_panel_settings</span>
            </div>
            <h2 class="text-3xl font-bold text-gray-900">Admin Portal</h2>
            <p class="text-gray-600 mt-2">Royal Lanka Rides Operations Dashboard</p>
        </div>

        <?php if ($error): ?>
            <div class="alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" autocomplete="username" class="w-full px-4 py-3 border border-gray-300 rounded-lg login-input" placeholder="admin@fleetelite.com">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                <input type="password" name="password" required class="w-full px-4 py-3 border border-gray-300 rounded-lg login-input" placeholder="••••••••">
            </div>
            <button type="submit" class="w-full btn-primary py-3 rounded-lg font-semibold hover:opacity-90 transition">
                Sign In to Dashboard
            </button>
        </form>
        
    </div>
</body>
</html>
