<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$app = app();
$auth = $app->authService();
$flash = $app->flashMessage();
$classroom = $app->classroomService();

$auth->requireLogin();

if (!$auth->isStudent()) {
    $flash->set('Only students can join classes.', 'error');
    $app->redirector()->redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim((string) ($_POST['join_code'] ?? '')));
    $result = $classroom->joinClass($auth->currentUser(), $code);

    if (is_array($result)) {
        $flash->set('Joined class: ' . $result['name'], 'success');
        $app->redirector()->redirect('class.php?id=' . $result['id']);
    }

    $error = is_string($result) ? $result : 'Could not join class.';
}

$pageTitle = 'Join Class';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>Join a Class</h1>
    <p>Enter the 6-character code from your teacher</p>
</div>

<div class="post-box" style="max-width: 400px;">
    <?php if ($error !== ''): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label for="join_code">Class Code</label>
            <input type="text" id="join_code" name="join_code" required maxlength="10"
                   placeholder="e.g. ABC123" style="text-transform: uppercase; letter-spacing: 2px; font-size: 1.2rem;">
        </div>
        <button type="submit" class="btn btn-block">Join</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
