<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$app = app();
$auth = $app->authService();
$flash = $app->flashMessage();
$classroom = $app->classroomService();
$classRepository = $app->classRepository();
$classAccess = $app->classAccessService();
$enrollments = $app->enrollmentRepository();
$formatDate = static function (?string $datetime) use ($app): string {
    return $app->dateFormatter()->format($datetime);
};

$auth->requireLogin();

$classId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$user = $auth->currentUser();

if ($classId === 0) {
    $app->redirector()->redirect('dashboard.php');
}

$class = $classRepository->findById($classId);

if ($class === null) {
    $flash->set('Class not found.', 'error');
    $app->redirector()->redirect('dashboard.php');
}

if (!$classAccess->hasAccess($classId, $user)) {
    $flash->set('You do not have access to this class.', 'error');
    $app->redirector()->redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $classroom->handlePost($classId, $user, $action, $_POST);
}

$pageTitle = $class['name'];
$streamItems = $classroom->buildStream($classId);
$students = $auth->isTeacher() ? $enrollments->listStudents($classId) : [];

require_once __DIR__ . '/includes/header.php';
?>

<div class="class-header">
    <h1><?php echo htmlspecialchars($class['name']); ?></h1>
    <p class="meta">
        <?php echo htmlspecialchars($class['teacher_name']); ?>
        <?php if ($class['section']): ?> · <?php echo htmlspecialchars($class['section']); ?><?php endif; ?>
        <?php if ($class['subject']): ?> · <?php echo htmlspecialchars($class['subject']); ?><?php endif; ?>
    </p>
    <?php if ($auth->isTeacher()): ?>
    <p style="margin-top: 12px;">Join code: <span class="join-code"><?php echo htmlspecialchars($class['join_code']); ?></span></p>
    <?php endif; ?>
    <?php if ($class['description']): ?>
    <p style="margin-top: 12px;"><?php echo nl2br(htmlspecialchars($class['description'])); ?></p>
    <?php endif; ?>
</div>

<?php if ($auth->isTeacher()): ?>
<div class="post-box">
    <h3>Share an announcement</h3>
    <form method="post">
        <input type="hidden" name="action" value="announcement">
        <div class="form-group">
            <textarea name="content" placeholder="Announce something to your class..." required></textarea>
        </div>
        <button type="submit" class="btn">Post</button>
    </form>
</div>

<div class="post-box">
    <h3>Create assignment</h3>
    <form method="post">
        <input type="hidden" name="action" value="assignment">
        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" id="title" name="title" required placeholder="Assignment title">
        </div>
        <div class="form-group">
            <label for="description">Instructions</label>
            <textarea id="description" name="description" placeholder="What should students do?"></textarea>
        </div>
        <div class="form-group">
            <label for="due_date">Due date</label>
            <input type="datetime-local" id="due_date" name="due_date">
        </div>
        <div class="form-group">
            <label for="points">Points</label>
            <input type="number" id="points" name="points" value="100" min="1" max="1000">
        </div>
        <button type="submit" class="btn">Assign</button>
    </form>
</div>
<?php endif; ?>

<h2 style="margin-bottom: 16px;">Class Stream</h2>

<?php if (count($streamItems) === 0): ?>
<div class="empty-state">
    <h3>No posts yet</h3>
    <p><?php echo $auth->isTeacher() ? 'Post an announcement or create an assignment.' : 'Check back later for class work.'; ?></p>
</div>
<?php else: ?>
<div class="stream">
    <?php foreach ($streamItems as $item): ?>
    <div class="stream-item">
        <div class="stream-item-header">
            <div>
                <?php if ($item['type'] === 'announcement'): ?>
                <span class="badge badge-announcement">Announcement</span>
                <span class="author"><?php echo htmlspecialchars($item['author_name']); ?></span>
                <?php else: ?>
                <span class="badge badge-assignment">Assignment</span>
                <?php endif; ?>
            </div>
            <span class="date"><?php echo $formatDate($item['created_at']); ?></span>
        </div>

        <?php if ($item['type'] === 'announcement'): ?>
        <p><?php echo nl2br(htmlspecialchars($item['content'])); ?></p>
        <?php else: ?>
        <h3 style="margin-bottom: 8px;">
            <a href="assignment.php?id=<?php echo (int) $item['id']; ?>"><?php echo htmlspecialchars($item['title']); ?></a>
        </h3>
        <?php if ($item['description']): ?>
        <p><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
        <?php endif; ?>
        <p class="due-date <?php echo (strtotime((string) $item['due_date']) > time()) ? 'ok' : ''; ?>">
            Due: <?php echo $formatDate($item['due_date']); ?> · <?php echo (int) $item['points']; ?> points
        </p>
        <a href="assignment.php?id=<?php echo (int) $item['id']; ?>" class="btn btn-small" style="margin-top: 12px;">
            <?php echo $auth->isTeacher() ? 'View submissions' : 'View / Submit'; ?>
        </a>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($auth->isTeacher()): ?>
<h2 style="margin: 32px 0 16px;">Students (<?php echo count($students); ?>)</h2>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Joined</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($students) === 0): ?>
            <tr><td colspan="3">No students enrolled yet. Share your join code.</td></tr>
            <?php else: ?>
            <?php foreach ($students as $student): ?>
            <tr>
                <td><?php echo htmlspecialchars($student['name']); ?></td>
                <td><?php echo htmlspecialchars($student['email']); ?></td>
                <td><?php echo $formatDate($student['joined_at']); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<p class="actions" style="margin-top: 24px;">
    <a href="dashboard.php" class="btn btn-secondary">Back to classes</a>
    <?php if ($auth->isTeacher()): ?>
    <form method="post" style="display: inline;" onsubmit="return confirm('Delete this class permanently? All announcements, assignments, enrollments, and submissions will be removed.');">
        <input type="hidden" name="action" value="delete_class">
        <button type="submit" class="btn btn-danger">Delete Class</button>
    </form>
    <?php endif; ?>
    <?php if ($auth->isStudent()): ?>
    <form method="post" style="display: inline;" onsubmit="return confirm('Leave this class? You will need the join code to enroll again.');">
        <input type="hidden" name="action" value="leave_class">
        <button type="submit" class="btn btn-danger">Leave Class</button>
    </form>
    <?php endif; ?>
</p>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
