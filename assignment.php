<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$app = app();
$auth = $app->authService();
$flash = $app->flashMessage();
$assignmentService = $app->assignmentService();
$formatDate = static function (?string $datetime) use ($app): string {
    return $app->dateFormatter()->format($datetime);
};

$auth->requireLogin();

$assignmentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$user = $auth->currentUser();

if ($assignmentId === 0) {
    $app->redirector()->redirect('dashboard.php');
}

$assignment = $assignmentService->findAssignment($assignmentId);

if ($assignment === null) {
    $flash->set('Assignment not found.', 'error');
    $app->redirector()->redirect('dashboard.php');
}

$classId = (int) $assignment['class_id'];

if (!$assignmentService->hasAccess($assignment, $user)) {
    $flash->set('Access denied.', 'error');
    $app->redirector()->redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $auth->isStudent()) {
    $content = trim((string) ($_POST['content'] ?? ''));
    $file = isset($_FILES['file']) ? $_FILES['file'] : null;
    $submitError = $assignmentService->submitWork($assignmentId, $user, $content, $file);

    if ($submitError === null) {
        $flash->set('Work submitted successfully!', 'success');
        $app->redirector()->redirect('assignment.php?id=' . $assignmentId);
    }

    $error = $submitError ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $auth->isTeacher() && isset($_POST['grade_submission'])) {
    $submissionId = (int) ($_POST['submission_id'] ?? 0);
    $grade = (string) ($_POST['grade'] ?? '');
    $feedback = trim((string) ($_POST['feedback'] ?? ''));

    if ($assignmentService->saveGrade($assignmentId, $submissionId, $grade, $feedback)) {
        $flash->set('Grade saved.', 'success');
        $app->redirector()->redirect('assignment.php?id=' . $assignmentId);
    }
}

$mySubmission = $auth->isStudent()
    ? $assignmentService->getStudentSubmission($assignmentId, $user)
    : null;

$submissions = $auth->isTeacher()
    ? $assignmentService->listSubmissionsForTeacher($assignmentId, $classId)
    : [];

$pageTitle = $assignment['title'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="class-header">
    <p class="meta"><a href="class.php?id=<?php echo $classId; ?>"><?php echo htmlspecialchars($assignment['class_name']); ?></a></p>
    <h1><?php echo htmlspecialchars($assignment['title']); ?></h1>
    <p class="due-date">Due: <?php echo $formatDate($assignment['due_date']); ?> · <?php echo (int) $assignment['points']; ?> points</p>
    <?php if ($assignment['description']): ?>
    <div style="margin-top: 16px;"><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></div>
    <?php endif; ?>
</div>

<?php if ($auth->isStudent()): ?>
<div class="post-box">
    <h3>Your work</h3>

    <?php if ($error !== ''): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($mySubmission !== null): ?>
    <p class="submitted-badge">Submitted on <?php echo $formatDate($mySubmission['submitted_at']); ?></p>
    <?php if ($mySubmission['grade'] !== null): ?>
    <p><strong>Grade:</strong> <?php echo htmlspecialchars((string) $mySubmission['grade']); ?> / <?php echo (int) $assignment['points']; ?></p>
    <?php if ($mySubmission['feedback']): ?>
    <p><strong>Feedback:</strong> <?php echo nl2br(htmlspecialchars($mySubmission['feedback'])); ?></p>
    <?php endif; ?>
    <?php endif; ?>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="content">Written response</label>
            <textarea id="content" name="content" placeholder="Type your answer here..."><?php echo $mySubmission ? htmlspecialchars($mySubmission['content']) : ''; ?></textarea>
        </div>
        <div class="form-group">
            <label for="file">Attach file (optional)</label>
            <input type="file" id="file" name="file">
            <?php if ($mySubmission && $mySubmission['file_name']): ?>
            <p style="margin-top: 8px; font-size: 0.9rem;">Current file:
                <a href="download.php?file=<?php echo urlencode($mySubmission['file_name']); ?>"><?php echo htmlspecialchars($mySubmission['file_name']); ?></a>
            </p>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn"><?php echo $mySubmission ? 'Update submission' : 'Turn in'; ?></button>
    </form>
</div>
<?php endif; ?>

<?php if ($auth->isTeacher()): ?>
<h2 style="margin-bottom: 16px;">Student work</h2>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Student</th>
                <th>Status</th>
                <th>Submitted</th>
                <th>Grade</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($submissions as $sub): ?>
            <tr>
                <td>
                    <?php echo htmlspecialchars($sub['student_name']); ?><br>
                    <small><?php echo htmlspecialchars($sub['email']); ?></small>
                </td>
                <td>
                    <?php if ($sub['id']): ?>
                    <span class="submitted-badge">Turned in</span>
                    <?php else: ?>
                    <span class="missing-badge">Missing</span>
                    <?php endif; ?>
                </td>
                <td><?php echo $sub['submitted_at'] ? $formatDate($sub['submitted_at']) : '—'; ?></td>
                <td>
                    <?php if ($sub['grade'] !== null): ?>
                    <?php echo htmlspecialchars((string) $sub['grade']); ?> / <?php echo (int) $assignment['points']; ?>
                    <?php else: ?>
                    —
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($sub['id']): ?>
                    <details>
                        <summary>View & grade</summary>
                        <div style="padding: 12px 0;">
                            <?php if ($sub['content']): ?>
                            <p><?php echo nl2br(htmlspecialchars($sub['content'])); ?></p>
                            <?php endif; ?>
                            <?php if ($sub['file_name']): ?>
                            <p><a href="download.php?file=<?php echo urlencode($sub['file_name']); ?>">Download attachment</a></p>
                            <?php endif; ?>
                            <form method="post" class="grade-form" style="margin-top: 12px;">
                                <input type="hidden" name="grade_submission" value="1">
                                <input type="hidden" name="submission_id" value="<?php echo (int) $sub['id']; ?>">
                                <input type="number" name="grade" step="0.5" min="0" max="<?php echo (int) $assignment['points']; ?>"
                                       placeholder="Grade" value="<?php echo $sub['grade'] !== null ? htmlspecialchars((string) $sub['grade']) : ''; ?>" required>
                                <input type="text" name="feedback" placeholder="Feedback" value="<?php echo htmlspecialchars((string) ($sub['feedback'] ?? '')); ?>">
                                <button type="submit" class="btn btn-small">Save</button>
                            </form>
                        </div>
                    </details>
                    <?php else: ?>
                    —
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<p class="actions">
    <a href="class.php?id=<?php echo $classId; ?>" class="btn btn-secondary">Back to class</a>
</p>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
