<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Config\AppConfig;

$app = app();
$auth = $app->authService();

if ($auth->isLoggedIn()) {
    $app->redirector()->redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $loginError = $auth->attemptLogin($email, $password);

    if ($loginError === null) {
        $app->redirector()->redirect('dashboard.php');
    }

    $error = $loginError ?? '';
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
?>

<div class="login-page">

    <div class="overlay"></div>

    <div class="auth-box">

        <div class="logo-container">
            <img src="assets/img/Logo.jfif" alt="Logo" class="login-logo">
        </div>

        <h1><?php echo htmlspecialchars(AppConfig::getSiteName()); ?></h1>
        <p class="subtitle">Sign in to continue</p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">

            <div class="form-group">
                <label for="email">Email</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars((string) $_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    required>
            </div>
            <button type="submit" class="btn btn-block">
                Sign In
            </button>

        </form>
        <p class="auth-link">
            No account?
            <a href="register.php">Register here</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
