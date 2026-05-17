<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$app = app();
$auth = $app->authService();
$auth->requireLogin();

$user = $auth->currentUser();
$classRepository = $app->classRepository();

if ($auth->isTeacher()) {
    $classes = $classRepository->listForTeacher($user->getId());
} else {
    $classes = $classRepository->listForStudent($user->getId());
}

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1>My Classes</h1>
    <p><?php echo $auth->isTeacher() ? 'Manage your classes and students' : 'Your enrolled classes'; ?></p>
</div>

<?php if (count($classes) === 0): ?>
<div class="empty-state">
    <h3>No classes yet</h3>
    <p><?php echo $auth->isTeacher() ? 'Create your first class to get started.' : 'Join a class using a code from your teacher.'; ?></p>
    <div class="actions" style="justify-content: center;">
        <?php if ($auth->isTeacher()): ?>
        <a href="create_class.php" class="btn">Create Class</a>
        <?php else: ?>
        <a href="join_class.php" class="btn">Join Class</a>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="class-grid">
    <?php foreach ($classes as $class): ?>
    <a href="class.php?id=<?php echo (int) $class['id']; ?>" class="class-card">
        <div class="class-card-banner"></div>
        <div class="class-card-body">
            <h3><?php echo htmlspecialchars($class['name']); ?></h3>
            <p class="meta">
                <?php if (!empty($class['section'])): ?>
                    <?php echo htmlspecialchars($class['section']); ?> ·
                <?php endif; ?>
                <?php if (!empty($class['subject'])): ?>
                    <?php echo htmlspecialchars($class['subject']); ?>
                <?php endif; ?>
            </p>
            <?php if ($auth->isTeacher()): ?>
            <p class="meta"><?php echo (int) $class['student_count']; ?> students · Code:
                <span class="join-code"><?php echo htmlspecialchars($class['join_code']); ?></span>
            </p>
            <?php else: ?>
            <p class="meta">Teacher: <?php echo htmlspecialchars($class['teacher_name']); ?></p>
            <?php endif; ?>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
