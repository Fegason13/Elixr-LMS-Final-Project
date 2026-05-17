<?php

declare(strict_types=1);

namespace App\Bootstrap;

use App\Auth\AuthService;
use App\Config\AppConfig;
use App\Database\DatabaseConnection;
use App\Http\FlashMessage;
use App\Http\Redirector;
use App\Repository\AnnouncementRepository;
use App\Repository\AssignmentRepository;
use App\Repository\ClassRepository;
use App\Repository\EnrollmentRepository;
use App\Repository\SubmissionRepository;
use App\Repository\UserRepository;
use App\Service\AssignmentService;
use App\Service\ClassAccessService;
use App\Service\ClassroomService;
use App\Service\DownloadService;
use App\Session\SessionManager;
use App\Util\DateFormatter;
use App\Util\JoinCodeGenerator;
use mysqli;

/**
 * Application container wiring services for the LMS.
 *
 * @author Jericho
 * @since 2026-05-17
 * @package App\Bootstrap
 */
final class Application
{
    /** @var self|null */
    private static ?self $instance = null;

    /** @var mysqli */
    private mysqli $connection;

    /** @var SessionManager */
    private SessionManager $session;

    /** @var Redirector */
    private Redirector $redirector;

    /** @var FlashMessage */
    private FlashMessage $flash;

    /** @var AuthService */
    private AuthService $auth;

    /** @var UserRepository */
    private UserRepository $users;

    /** @var ClassRepository */
    private ClassRepository $classes;

    /** @var EnrollmentRepository */
    private EnrollmentRepository $enrollments;

    /** @var AnnouncementRepository */
    private AnnouncementRepository $announcements;

    /** @var AssignmentRepository */
    private AssignmentRepository $assignments;

    /** @var SubmissionRepository */
    private SubmissionRepository $submissions;

    /** @var ClassAccessService */
    private ClassAccessService $classAccess;

    /** @var ClassroomService */
    private ClassroomService $classroom;

    /** @var AssignmentService */
    private AssignmentService $assignmentService;

    /** @var DownloadService */
    private DownloadService $downloadService;

    /** @var DateFormatter */
    private DateFormatter $dateFormatter;

    /** @var JoinCodeGenerator */
    private JoinCodeGenerator $joinCodeGenerator;

    /**
     * Boots session and builds the service container once per request.
     *
     * @return self
     */
    public static function boot(): self
    {
        if (self::$instance === null) {
            AppConfig::load();
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param DatabaseConnection $database Database factory
     */
    private function __construct()
    {
        $database = new DatabaseConnection();
        $this->connection = $database->getConnection();
        $this->session = new SessionManager();
        $this->redirector = new Redirector();
        $this->flash = new FlashMessage();
        $this->users = new UserRepository($this->connection);
        $this->classes = new ClassRepository($this->connection);
        $this->enrollments = new EnrollmentRepository($this->connection);
        $this->announcements = new AnnouncementRepository($this->connection);
        $this->assignments = new AssignmentRepository($this->connection);
        $this->submissions = new SubmissionRepository($this->connection);
        $this->auth = new AuthService($this->session, $this->users, $this->redirector);
        $this->classAccess = new ClassAccessService($this->classes, $this->enrollments);
        $this->joinCodeGenerator = new JoinCodeGenerator();
        $this->dateFormatter = new DateFormatter();
        $this->classroom = new ClassroomService(
            $this->classes,
            $this->enrollments,
            $this->announcements,
            $this->assignments,
            $this->joinCodeGenerator,
            $this->classAccess,
            $this->flash,
            $this->redirector
        );
        $this->assignmentService = new AssignmentService(
            $this->assignments,
            $this->submissions,
            $this->enrollments,
            $this->flash,
            $this->redirector
        );
        $this->downloadService = new DownloadService($this->submissions);
    }

    /**
     * @return AuthService
     */
    public function authService(): AuthService
    {
        return $this->auth;
    }

    /**
     * @return Redirector
     */
    public function redirector(): Redirector
    {
        return $this->redirector;
    }

    /**
     * @return FlashMessage
     */
    public function flashMessage(): FlashMessage
    {
        return $this->flash;
    }

    /**
     * @return ClassRepository
     */
    public function classRepository(): ClassRepository
    {
        return $this->classes;
    }

    /**
     * @return EnrollmentRepository
     */
    public function enrollmentRepository(): EnrollmentRepository
    {
        return $this->enrollments;
    }

    /**
     * @return ClassAccessService
     */
    public function classAccessService(): ClassAccessService
    {
        return $this->classAccess;
    }

    /**
     * @return ClassroomService
     */
    public function classroomService(): ClassroomService
    {
        return $this->classroom;
    }

    /**
     * @return AssignmentService
     */
    public function assignmentService(): AssignmentService
    {
        return $this->assignmentService;
    }

    /**
     * @return DownloadService
     */
    public function downloadService(): DownloadService
    {
        return $this->downloadService;
    }

    /**
     * @return DateFormatter
     */
    public function dateFormatter(): DateFormatter
    {
        return $this->dateFormatter;
    }

    /**
     * @return SessionManager
     */
    public function sessionManager(): SessionManager
    {
        return $this->session;
    }
}
