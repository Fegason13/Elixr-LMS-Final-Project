<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$app = app();
$auth = $app->authService();
$flash = $app->flashMessage();
$classroom = $app->classroomService();

$auth->requireLogin();

if (!$auth->isTeacher()) {
    $flash->set('Only teachers can create classes.', 'error');
    $app->redirector()->redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $auth->currentUser();
    $result = $classroom->createClass(
        $user,
        trim((string) ($_POST['name'] ?? '')),
        trim((string) ($_POST['section'] ?? '')),
        trim((string) ($_POST['subject'] ?? '')),
        trim((string) ($_POST['description'] ?? ''))
    );

    if (is_array($result)) {
        $flash->set('Class created! Share join code: ' . $result['join_code'], 'success');
        $app->redirector()->redirect('class.php?id=' . $result['id']);
    }

    $error = is_string($result) ? $result : 'Could not create class.';
}

$pageTitle = 'Create Class';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>Create Class</h1>
    <p>Set up a new class for your students</p>
</div>

<div class="post-box">
    <?php if ($error !== ''): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label for="name">Class Name *</label>
            <input type="text" id="name" name="name" required placeholder="e.g. Mathematics 101"
                   value="<?php echo isset($_POST['name']) ? htmlspecialchars((string) $_POST['name']) : ''; ?>">
        </div>
        <div class="form-group">
            <label for="section">Section</label>
            <input type="text" id="section" name="section" placeholder="e.g. Section A"
                   value="<?php echo isset($_POST['section']) ? htmlspecialchars((string) $_POST['section']) : ''; ?>">
        </div>
        <div class="form-group">
            <label for="subject">Subject</label>
            <input type="text" id="subject" name="subject" placeholder="e.g. Algebra"
                   value="<?php echo isset($_POST['subject']) ? htmlspecialchars((string) $_POST['subject']) : ''; ?>">
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" placeholder="What is this class about?"><?php echo isset($_POST['description']) ? htmlspecialchars((string) $_POST['description']) : ''; ?></textarea>
        </div>
        <button type="submit" class="btn">Create Class</button>
        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
