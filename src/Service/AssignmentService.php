<?php

declare(strict_types=1);

namespace App\Service;

use App\Auth\UserContext;
use App\Config\AppConfig;
use App\Http\FlashMessage;
use App\Http\Redirector;
use App\Repository\AssignmentRepository;
use App\Repository\EnrollmentRepository;
use App\Repository\SubmissionRepository;

/**
 * Handles assignment access, submissions, and grading.
 *
 * @author Jericho
 * @since 2026-05-17
 * @package App\Service
 */
final class AssignmentService
{
    /** @var string[] Allowed upload extensions */
    private const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'zip'];

    /** @var int Max upload size in bytes (5MB) */
    private const MAX_FILE_SIZE = 5000000;

    /** @var AssignmentRepository */
    private AssignmentRepository $assignments;

    /** @var SubmissionRepository */
    private SubmissionRepository $submissions;

    /** @var EnrollmentRepository */
    private EnrollmentRepository $enrollments;

    /** @var FlashMessage */
    private FlashMessage $flash;

    /** @var Redirector */
    private Redirector $redirector;

    /**
     * @param AssignmentRepository $assignments Assignments
     * @param SubmissionRepository $submissions Submissions
     * @param EnrollmentRepository $enrollments Enrollments
     * @param FlashMessage $flash Flash
     * @param Redirector $redirector Redirects
     */
    public function __construct(
        AssignmentRepository $assignments,
        SubmissionRepository $submissions,
        EnrollmentRepository $enrollments,
        FlashMessage $flash,
        Redirector $redirector
    ) {
        $this->assignments = $assignments;
        $this->submissions = $submissions;
        $this->enrollments = $enrollments;
        $this->flash = $flash;
        $this->redirector = $redirector;
    }

    /**
     * @param array<string, mixed> $assignment Assignment row
     * @param UserContext $user Current user
     *
     * @return bool
     */
    public function hasAccess(array $assignment, UserContext $user): bool
    {
        $classId = (int) $assignment['class_id'];

        if ($user->isTeacher() && (int) $assignment['teacher_id'] === $user->getId()) {
            return true;
        }

        return $user->isStudent() && $this->enrollments->isEnrolled($classId, $user->getId());
    }

    /**
     * @param int $assignmentId Assignment id
     * @param UserContext $user Student
     * @param string $content Written work
     * @param array<string, mixed>|null $file Uploaded file from $_FILES
     *
     * @return string|null Error or null on success
     */
    public function submitWork(
        int $assignmentId,
        UserContext $user,
        string $content,
        ?array $file
    ): ?string {
        $uploadResult = $this->processUpload($assignmentId, $user->getId(), $file);

        if ($uploadResult === false) {
            return 'Invalid file. Use PDF, DOC, TXT, images or ZIP under 5MB.';
        }

        if ($uploadResult === null) {
            return 'File upload failed.';
        }

        $fileName = $uploadResult;

        if ($content === '' && $fileName === '') {
            return 'Add text or upload a file to submit.';
        }

        $this->submissions->save($assignmentId, $user->getId(), $content, $fileName);

        return null;
    }

    /**
     * @param int $assignmentId Assignment id
     * @param int $submissionId Submission id
     * @param string $grade Grade input
     * @param string $feedback Feedback text
     *
     * @return bool Whether grade was saved
     */
    public function saveGrade(
        int $assignmentId,
        int $submissionId,
        string $grade,
        string $feedback
    ): bool {
        if ($grade === '' || !is_numeric($grade)) {
            return false;
        }

        $this->submissions->saveGrade(
            $submissionId,
            $assignmentId,
            (float) $grade,
            $feedback
        );

        return true;
    }

    /**
     * @param int $assignmentId Assignment id
     *
     * @return array<string, mixed>|null
     */
    public function findAssignment(int $assignmentId): ?array
    {
        return $this->assignments->findById($assignmentId);
    }

    /**
     * @param int $assignmentId Assignment id
     * @param UserContext $user Student
     *
     * @return array<string, mixed>|null
     */
    public function getStudentSubmission(int $assignmentId, UserContext $user): ?array
    {
        return $this->submissions->findForStudent($assignmentId, $user->getId());
    }

    /**
     * @param int $assignmentId Assignment id
     * @param int $classId Class id
     *
     * @return array<int, array<string, mixed>>
     */
    public function listSubmissionsForTeacher(int $assignmentId, int $classId): array
    {
        return $this->submissions->listForTeacher($assignmentId, $classId);
    }

    /**
     * @param int $assignmentId Assignment id
     * @param int $studentId Student id
     * @param array<string, mixed>|null $file File array
     *
     * @return string|false|null Filename, empty string, false if invalid, null if upload failed
     */
    private function processUpload(int $assignmentId, int $studentId, ?array $file)
    {
        if ($file === null || ($file['name'] ?? '') === '') {
            return '';
        }

        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return false;
        }

        if ((int) $file['size'] >= self::MAX_FILE_SIZE) {
            return false;
        }

        $uploadDir = AppConfig::getUploadDir();

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = $studentId . '_' . $assignmentId . '_' . time() . '.' . $ext;
        $target = $uploadDir . $fileName;

        if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
            return null;
        }

        return $fileName;
    }
}
