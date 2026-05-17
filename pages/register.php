<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$app = app();
$auth = $app->authService();
$flash = $app->flashMessage();

if ($auth->isLoggedIn()) {
    $app->redirector()->redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');
    $role = (string) ($_POST['role'] ?? '');

    $registerError = $auth->register($name, $email, $password, $confirm, $role);

    if ($registerError === null) {
        $flash->set('Account created! Please sign in.', 'success');
        $app->redirector()->redirect('../index.php');
    }

    $error = $registerError ?? '';
}

$pageTitle = 'Register';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-box">
    <h1>Create Account</h1>
    <p class="subtitle">Join as a teacher or student</p>

    <?php if ($error !== ''): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" required
                   value="<?php echo isset($_POST['name']) ? htmlspecialchars((string) $_POST['name']) : ''; ?>">
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars((string) $_POST['email']) : ''; ?>">
        </div>
        <div class="form-group">
            <label for="role">I am a</label>
            <select id="role" name="role" required>
                <option value="student" <?php echo (isset($_POST['role']) && $_POST['role'] === 'student') ? 'selected' : ''; ?>>Student</option>
                <option value="teacher" <?php echo (isset($_POST['role']) && $_POST['role'] === 'teacher') ? 'selected' : ''; ?>>Teacher</option>
            </select>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required minlength="6">
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        <button type="submit" class="btn btn-block">Register</button>
    </form>

    <p class="auth-link">Already have an account? <a href="../index.php">Sign in</a></p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
