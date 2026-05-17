<?php

declare(strict_types=1);

namespace App\Service;

use App\Auth\UserContext;
use App\Config\AppConfig;
use App\Exception\AccessDeniedException;
use App\Exception\NotFoundException;
use App\Repository\SubmissionRepository;

/**
 * Validates access and streams submission file downloads.
 *
 * @author Jericho
 * @since 2026-05-17
 * @package App\Service
 */
final class DownloadService
{
    /** @var SubmissionRepository */
    private SubmissionRepository $submissions;

    /**
     * @param SubmissionRepository $submissions Submission data
     */
    public function __construct(SubmissionRepository $submissions)
    {
        $this->submissions = $submissions;
    }

    /**
     * @param string $fileName Requested file name
     * @param UserContext $user Current user
     *
     * @return array{path: string, file: string}
     *
     * @throws NotFoundException When file is missing
     * @throws AccessDeniedException When user cannot download
     */
    public function resolveDownload(string $fileName, UserContext $user): array
    {
        $safeName = basename($fileName);
        $path = AppConfig::getUploadDir() . $safeName;

        if ($safeName === '' || !file_exists($path)) {
            throw new NotFoundException('File not found.');
        }

        $submission = $this->submissions->findByFileName($safeName);

        if ($submission === null) {
            throw new AccessDeniedException('Access denied.');
        }

        if (!$this->canDownload($submission, $user)) {
            throw new AccessDeniedException('Access denied.');
        }

        return ['path' => $path, 'file' => $safeName];
    }

    /**
     * @param array<string, mixed> $submission Submission row
     * @param UserContext $user Current user
     *
     * @return bool
     */
    private function canDownload(array $submission, UserContext $user): bool
    {
        if ($user->isTeacher() && (int) $submission['teacher_id'] === $user->getId()) {
            return true;
        }

        return $user->isStudent() && (int) $submission['student_id'] === $user->getId();
    }
}
