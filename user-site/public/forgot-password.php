<?php
$page_title = 'Reset Password';
require_once '../config/database.php';
require_once '../../admin-site/includes/mail-sender.php';

$notice = '';
$error = '';
$step = $_SESSION['password_reset_email'] ?? '' ? 'verify' : 'request';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_codes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(150) NOT NULL,
        code_hash VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_password_reset_email (email),
        INDEX idx_password_reset_expiry (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {
    $error = 'Password reset is temporarily unavailable. Please try again later.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $action = $_POST['action'] ?? '';

    if ($action === 'send_code') {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        if (!$email) {
            $error = 'Enter a valid email address.';
        } else {
            $userStmt = $pdo->prepare('SELECT full_name, email FROM users WHERE email = ? LIMIT 1');
            $userStmt->execute([$email]);
            $user = $userStmt->fetch();

            // Always use the same message so an email address cannot be checked through this form.
            $notice = 'If an account exists for that email, a six-digit reset code has been sent. The code expires in 10 minutes.';
            if ($user) {
                $code = (string) random_int(100000, 999999);
                $pdo->prepare('DELETE FROM password_reset_codes WHERE email = ?')->execute([$user['email']]);
                $insert = $pdo->prepare('INSERT INTO password_reset_codes (email, code_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))');
                $insert->execute([$user['email'], password_hash($code, PASSWORD_DEFAULT)]);

                $safeName = htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8');
                $message = "<div style=\"font-family:Arial,sans-serif;color:#172033;max-width:560px;margin:auto;padding:28px\">
                    <h2 style=\"margin:0 0 16px;color:#0f766e\">Reset your password</h2>
                    <p>Hello {$safeName},</p>
                    <p>Use the following one-time code to reset your Royal Lanka Rides password:</p>
                    <p style=\"font-size:30px;font-weight:700;letter-spacing:7px;background:#f0fdfa;padding:16px 20px;border-radius:10px;text-align:center\">{$code}</p>
                    <p>This code expires in 10 minutes and can be used only once. If you did not request this, you can safely ignore this email.</p>
                </div>";
                if (sendHtmlEmail($user['email'], 'Your Royal Lanka Rides password reset code', $message, $user['full_name'])) {
                    $_SESSION['password_reset_email'] = $user['email'];
                    $step = 'verify';
                } else {
                    $pdo->prepare('DELETE FROM password_reset_codes WHERE email = ?')->execute([$user['email']]);
                    $notice = '';
                    $error = 'We could not send the reset email. Please try again later.';
                }
            }
        }
    }

    if ($action === 'reset_password') {
        $email = $_SESSION['password_reset_email'] ?? '';
        $code = preg_replace('/\D/', '', $_POST['code'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!$email) {
            $error = 'Request a new reset code first.';
            $step = 'request';
        } elseif (strlen($code) !== 6) {
            $error = 'Enter the six-digit code from your email.';
        } elseif (strlen($password) < 6) {
            $error = 'Your new password must be at least 6 characters.';
        } elseif ($password !== $confirmPassword) {
            $error = 'The passwords do not match.';
        } else {
            $codeStmt = $pdo->prepare('SELECT * FROM password_reset_codes WHERE email = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1');
            $codeStmt->execute([$email]);
            $resetCode = $codeStmt->fetch();

            if (!$resetCode || (int) $resetCode['attempts'] >= 5 || !password_verify($code, $resetCode['code_hash'])) {
                if ($resetCode) {
                    $pdo->prepare('UPDATE password_reset_codes SET attempts = attempts + 1 WHERE id = ?')->execute([$resetCode['id']]);
                }
                $error = 'That code is invalid or has expired. Request a new code and try again.';
            } else {
                $pdo->beginTransaction();
                try {
                    $pdo->prepare('UPDATE users SET password = ? WHERE email = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $email]);
                    $pdo->prepare('DELETE FROM password_reset_codes WHERE email = ?')->execute([$email]);
                    $pdo->commit();
                    unset($_SESSION['password_reset_email']);
                    header('Location: login.php?reset=success');
                    exit();
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $error = 'Your password could not be reset. Please try again.';
                }
            }
        }
    }
}

require_once '../includes/header.php';
?>

<main class="min-h-screen bg-gray-50 py-24 px-4">
    <section class="max-w-md mx-auto bg-white rounded-2xl border border-gray-200 shadow-xl p-8">
        <a href="login.php" class="inline-flex items-center gap-2 text-sm font-bold text-primary hover:underline mb-6"><span class="material-symbols-outlined text-base">arrow_back</span>Back to login</a>
        <h1 class="text-3xl font-extrabold text-gray-900">Reset password</h1>
        <p class="mt-3 text-sm text-gray-600">We’ll send a one-time code to your registered email address.</p>

        <?php if ($notice): ?><div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800"><?php echo htmlspecialchars($notice); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <?php if ($step === 'request'): ?>
            <form method="POST" class="mt-8 max-w-sm mx-auto space-y-7">
                <input type="hidden" name="action" value="send_code">
                <div>
                    <label for="email" class="block text-sm font-bold text-gray-800 mb-2">Email address</label>
                    <input id="email" name="email" type="email" required autocomplete="email" class="w-full rounded-xl border border-gray-300 px-4 py-4 focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="you@example.com">
                </div>
                <button type="submit" class="w-full rounded-xl bg-primary px-4 py-4 font-bold text-white hover:bg-primary/90" style="margin-top: 2rem;">Send one-time code</button>
            </form>
        <?php else: ?>
            <form method="POST" class="mt-7 space-y-5">
                <input type="hidden" name="action" value="reset_password">
                <div>
                    <label for="code" class="block text-sm font-bold text-gray-800 mb-2">Six-digit code</label>
                    <input id="code" name="code" type="text" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" class="w-full rounded-xl border border-gray-300 px-4 py-3.5 text-center text-xl font-bold tracking-[0.35em] focus:border-primary focus:ring-2 focus:ring-primary/20" placeholder="000000">
                </div>
                <div>
                    <label for="password" class="block text-sm font-bold text-gray-800 mb-2">New password</label>
                    <input id="password" name="password" type="password" required minlength="6" autocomplete="new-password" class="w-full rounded-xl border border-gray-300 px-4 py-3.5 focus:border-primary focus:ring-2 focus:ring-primary/20">
                </div>
                <div>
                    <label for="confirm_password" class="block text-sm font-bold text-gray-800 mb-2">Confirm new password</label>
                    <input id="confirm_password" name="confirm_password" type="password" required minlength="6" autocomplete="new-password" class="w-full rounded-xl border border-gray-300 px-4 py-3.5 focus:border-primary focus:ring-2 focus:ring-primary/20">
                </div>
                <button type="submit" class="w-full rounded-xl bg-primary px-4 py-3.5 font-bold text-white hover:bg-primary/90">Reset password</button>
            </form>
            <form method="POST" class="mt-4 text-center">
                <input type="hidden" name="action" value="send_code">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['password_reset_email'], ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="text-sm font-bold text-primary hover:underline">Send a new code</button>
            </form>
        <?php endif; ?>
    </section>
</main>

<?php require_once '../includes/footer.php'; ?>
