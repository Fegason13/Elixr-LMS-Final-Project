<?php

declare(strict_types=1);

namespace App\Service;

use App\Auth\UserContext;
use App\Repository\ClassRepository;
use App\Repository\EnrollmentRepository;

/**
 * Verifies whether a user may view or modify a class.
 *
 * @author Jericho
 * @since 2026-05-17
 * @package App\Service
 */
final class ClassAccessService
{
    /** @var ClassRepository */
    private ClassRepository $classes;

    /** @var EnrollmentRepository */
    private EnrollmentRepository $enrollments;

    /**
     * @param ClassRepository $classes Class data
     * @param EnrollmentRepository $enrollments Enrollment data
     */
    public function __construct(ClassRepository $classes, EnrollmentRepository $enrollments)
    {
        $this->classes = $classes;
        $this->enrollments = $enrollments;
    }

    /**
     * @param int $classId Class id
     * @param UserContext $user Current user
     *
     * @return bool
     */
    public function hasAccess(int $classId, UserContext $user): bool
    {
        $class = $this->classes->findById($classId);

        if ($class === null) {
            return false;
        }

        if ($user->isTeacher() && (int) $class['teacher_id'] === $user->getId()) {
            return true;
        }

        if ($user->isStudent()) {
            return $this->enrollments->isEnrolled($classId, $user->getId());
        }

        return false;
    }

    /**
     * @param array<string, mixed> $class Class row
     * @param UserContext $user Current user
     *
     * @return bool
     */
    public function isClassTeacher(array $class, UserContext $user): bool
    {
        return $user->isTeacher() && (int) $class['teacher_id'] === $user->getId();
    }
}
