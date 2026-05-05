<?php
$page_title = 'Register';
require_once '../config/database.php';
require_once '../includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $company_name = $_POST['company_name'] ?? '';

    if (empty($full_name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, phone, company_name) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$full_name, $email, $hashed_password, $phone, $company_name])) {
                $success = 'Registration successful! You can now login.';
                header('refresh:2;url=login.php');
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<main class="min-h-screen flex items-center justify-center py-20">
    <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8">
        <div class="text-center mb-8">
            <h2 class="font-h2 text-h2 text-on-surface">Create Account</h2>
            <p class="text-gray-600">Join FleetElite today</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-4">
            <div>
                <label class="block font-label-md mb-2" for="full_name">Full Name *</label>
                <input class="w-full px-4 py-3 border border-gray-300 rounded-lg" 
                       type="text" id="full_name" name="full_name" required>
            </div>
            <div>
                <label class="block font-label-md mb-2" for="email">Email Address *</label>
                <input class="w-full px-4 py-3 border border-gray-300 rounded-lg" 
                       type="email" id="email" name="email" required>
            </div>
            <div>
                <label class="block font-label-md mb-2" for="phone">Phone Number</label>
                <input class="w-full px-4 py-3 border border-gray-300 rounded-lg" 
                       type="tel" id="phone" name="phone">
            </div>
            <div>
                <label class="block font-label-md mb-2" for="company_name">Company Name</label>
                <input class="w-full px-4 py-3 border border-gray-300 rounded-lg" 
                       type="text" id="company_name" name="company_name">
            </div>
            <div>
                <label class="block font-label-md mb-2" for="password">Password *</label>
                <input class="w-full px-4 py-3 border border-gray-300 rounded-lg" 
                       type="password" id="password" name="password" required>
            </div>
            <div>
                <label class="block font-label-md mb-2" for="confirm_password">Confirm Password *</label>
                <input class="w-full px-4 py-3 border border-gray-300 rounded-lg" 
                       type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="w-full bg-primary text-white py-3 rounded-lg font-bold hover:bg-red-700 transition">
                Create Account
            </button>
        </form>
        <p class="text-center mt-6">
            Already have an account? <a href="login.php" class="text-primary hover:underline">Sign in</a>
        </p>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>