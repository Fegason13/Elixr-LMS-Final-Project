<?php

declare(strict_types=1);

namespace App\Service;

use App\Auth\UserContext;
use App\Http\FlashMessage;
use App\Http\Redirector;
use App\Repository\AnnouncementRepository;
use App\Repository\AssignmentRepository;
use App\Repository\ClassRepository;
use App\Repository\EnrollmentRepository;
use App\Util\JoinCodeGenerator;

/**
 * Coordinates class creation, stream content, and membership actions.
 *
 * @author Jericho
 * @since 2026-05-17
 * @package App\Service
 */
final class ClassroomService
{
    /** @var ClassRepository */
    private ClassRepository $classes;

    /** @var EnrollmentRepository */
    private EnrollmentRepository $enrollments;

    /** @var AnnouncementRepository */
    private AnnouncementRepository $announcements;

    /** @var AssignmentRepository */
    private AssignmentRepository $assignments;

    /** @var JoinCodeGenerator */
    private JoinCodeGenerator $joinCodes;

    /** @var ClassAccessService */
    private ClassAccessService $access;

    /** @var FlashMessage */
    private FlashMessage $flash;

    /** @var Redirector */
    private Redirector $redirector;

    /**
     * @param ClassRepository $classes Classes
     * @param EnrollmentRepository $enrollments Enrollments
     * @param AnnouncementRepository $announcements Announcements
     * @param AssignmentRepository $assignments Assignments
     * @param JoinCodeGenerator $joinCodes Code generator
     * @param ClassAccessService $access Access checks
     * @param FlashMessage $flash Flash messages
     * @param Redirector $redirector Redirects
     */
    public function __construct(
        ClassRepository $classes,
        EnrollmentRepository $enrollments,
        AnnouncementRepository $announcements,
        AssignmentRepository $assignments,
        JoinCodeGenerator $joinCodes,
        ClassAccessService $access,
        FlashMessage $flash,
        Redirector $redirector
    ) {
        $this->classes = $classes;
        $this->enrollments = $enrollments;
        $this->announcements = $announcements;
        $this->assignments = $assignments;
        $this->joinCodes = $joinCodes;
        $this->access = $access;
        $this->flash = $flash;
        $this->redirector = $redirector;
    }

    /**
     * @param UserContext $user Teacher
     * @param string $name Class name
     * @param string $section Section
     * @param string $subject Subject
     * @param string $description Description
     *
     * @return array{id: int, join_code: string}|string Error message
     */
    public function createClass(
        UserContext $user,
        string $name,
        string $section,
        string $subject,
        string $description
    ) {
        if ($name === '') {
            return 'Class name is required.';
        }

        $joinCode = $this->generateUniqueJoinCode();
        $classId = $this->classes->create(
            $name,
            $section,
            $subject,
            $description,
            $joinCode,
            $user->getId()
        );

        return ['id' => $classId, 'join_code' => $joinCode];
    }

    /**
     * @param UserContext $user Student
     * @param string $code Join code
     *
     * @return array{id: int, name: string}|string Error message
     */
    public function joinClass(UserContext $user, string $code)
    {
        if ($code === '') {
            return 'Please enter a join code.';
        }

        $class = $this->classes->findByJoinCode($code);

        if ($class === null) {
            return 'Invalid join code. Check with your teacher.';
        }

        $classId = (int) $class['id'];

        if ($this->enrollments->isEnrolled($classId, $user->getId())) {
            return 'You are already in this class.';
        }

        if (!$this->enrollments->enroll($classId, $user->getId())) {
            return 'Could not join class.';
        }

        return ['id' => $classId, 'name' => $class['name']];
    }

    /**
     * @param int $classId Class id
     * @param UserContext $user Current user
     * @param string $action Posted action name
     * @param array<string, mixed> $post POST data
     *
     * @return never|null Redirects on success
     */
    public function handlePost(int $classId, UserContext $user, string $action, array $post): ?bool
    {
        $class = $this->classes->findById($classId);

        if ($class === null) {
            return null;
        }

        if ($action === 'delete_class' && $this->access->isClassTeacher($class, $user)) {
            $this->classes->deleteForTeacher($classId, $user->getId());
            $this->flash->set('Class deleted.', 'success');
            $this->redirector->redirect('dashboard.php');
        }

        if ($action === 'leave_class' && $user->isStudent()) {
            $this->enrollments->leave($classId, $user->getId());
            $this->flash->set('You have left the class.', 'success');
            $this->redirector->redirect('dashboard.php');
        }

        if (!$this->access->isClassTeacher($class, $user)) {
            return null;
        }

        $this->handleTeacherPost($classId, $user, $action, $post);
        $this->redirector->redirect('class.php?id=' . $classId);
    }

    /**
     * @param int $classId Class id
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildStream(int $classId): array
    {
        $items = array_merge(
            $this->announcements->listByClass($classId),
            $this->assignments->listByClass($classId)
        );

        usort($items, static function (array $a, array $b): int {
            return strtotime((string) $b['created_at']) <=> strtotime((string) $a['created_at']);
        });

        return $items;
    }

    /**
     * @return string Unique join code
     */
    private function generateUniqueJoinCode(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = $this->joinCodes->generate();

            if (!$this->classes->joinCodeExists($code)) {
                return $code;
            }
        }

        return $this->joinCodes->generate();
    }

    /**
     * @param int $classId Class id
     * @param UserContext $user Teacher
     * @param string $action Action
     * @param array<string, mixed> $post POST
     *
     * @return void
     */
    private function handleTeacherPost(
        int $classId,
        UserContext $user,
        string $action,
        array $post
    ): void {
        if ($action === 'announcement') {
            $this->postAnnouncement($classId, $user, $post);
        }

        if ($action === 'assignment') {
            $this->createAssignment($classId, $post);
        }
    }

    /**
     * @param int $classId Class id
     * @param UserContext $user Teacher
     * @param array<string, mixed> $post POST
     *
     * @return void
     */
    private function postAnnouncement(int $classId, UserContext $user, array $post): void
    {
        $content = trim((string) ($post['content'] ?? ''));

        if ($content === '') {
            return;
        }

        $this->announcements->create($classId, $user->getId(), $content);
        $this->flash->set('Announcement posted.', 'success');
    }

    /**
     * @param int $classId Class id
     * @param array<string, mixed> $post POST
     *
     * @return void
     */
    private function createAssignment(int $classId, array $post): void
    {
        $title = trim((string) ($post['title'] ?? ''));

        if ($title === '') {
            return;
        }

        $description = trim((string) ($post['description'] ?? ''));
        $dueDate = trim((string) ($post['due_date'] ?? ''));
        $points = (int) ($post['points'] ?? 100);

        if ($points < 1) {
            $points = 100;
        }

        $this->assignments->create(
            $classId,
            $title,
            $description,
            $dueDate !== '' ? $dueDate : null,
            $points
        );
        $this->flash->set('Assignment created.', 'success');
    }
}
