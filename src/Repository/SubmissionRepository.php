<?php

declare(strict_types=1);

namespace App\Repository;

use App\Exception\DatabaseException;
use mysqli;
use mysqli_stmt;

/**
 * Handles student assignment submissions and grading.
 *
 * @author Jericho
 * @since 2026-05-17
 * @package App\Repository
 */
final class SubmissionRepository
{
    /** @var mysqli */
    private mysqli $connection;

    /**
     * @param mysqli $connection Database connection
     */
    public function __construct(mysqli $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string $fileName Stored file name
     *
     * @return array<string, mixed>|null
     */
    public function findByFileName(string $fileName): ?array
    {
        $sql = 'SELECT s.*, a.class_id, c.teacher_id FROM submissions s
                INNER JOIN assignments a ON a.id = s.assignment_id
                INNER JOIN classes c ON c.id = a.class_id
                WHERE s.file_name = ? LIMIT 1';
        $stmt = $this->prepare($sql);
        $stmt->bind_param('s', $fileName);
        $this->execute($stmt);
        $row = $this->fetchOne($stmt);
        $stmt->close();

        return $row;
    }

    /**
     * @param int $assignmentId Assignment id
     * @param int $studentId Student id
     *
     * @return array<string, mixed>|null
     */
    public function findForStudent(int $assignmentId, int $studentId): ?array
    {
        $stmt = $this->prepare(
            'SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ? LIMIT 1'
        );
        $stmt->bind_param('ii', $assignmentId, $studentId);
        $this->execute($stmt);
        $row = $this->fetchOne($stmt);
        $stmt->close();

        return $row;
    }

    /**
     * @param int $assignmentId Assignment id
     * @param int $classId Class id
     *
     * @return array<int, array<string, mixed>>
     */
    public function listForTeacher(int $assignmentId, int $classId): array
    {
        $sql = 'SELECT s.*, u.name AS student_name, u.email FROM enrollments e
                INNER JOIN users u ON u.id = e.student_id
                LEFT JOIN submissions s ON s.student_id = u.id AND s.assignment_id = ?
                WHERE e.class_id = ? ORDER BY u.name';
        $stmt = $this->prepare($sql);
        $stmt->bind_param('ii', $assignmentId, $classId);
        $this->execute($stmt);
        $rows = $this->fetchAll($stmt);
        $stmt->close();

        return $rows;
    }

    /**
     * @param int $assignmentId Assignment id
     * @param int $studentId Student id
     * @param string $content Written response
     * @param string $fileName Attachment filename
     *
     * @return void
     */
    public function save(
        int $assignmentId,
        int $studentId,
        string $content,
        string $fileName
    ): void {
        $existing = $this->findForStudent($assignmentId, $studentId);

        if ($existing !== null) {
            $this->update($assignmentId, $studentId, $content, $fileName, $existing);
            return;
        }

        $this->insert($assignmentId, $studentId, $content, $fileName);
    }

    /**
     * @param int $submissionId Submission id
     * @param int $assignmentId Assignment id
     * @param float $grade Numeric grade
     * @param string $feedback Teacher feedback
     *
     * @return void
     */
    public function saveGrade(
        int $submissionId,
        int $assignmentId,
        float $grade,
        string $feedback
    ): void {
        $stmt = $this->prepare(
            'UPDATE submissions SET grade = ?, feedback = ? WHERE id = ? AND assignment_id = ?'
        );
        $stmt->bind_param('dsii', $grade, $feedback, $submissionId, $assignmentId);
        $this->execute($stmt);
        $stmt->close();
    }

    /**
     * @param int $assignmentId Assignment id
     * @param int $studentId Student id
     * @param string $content Content
     * @param string $fileName File
     * @param array<string, mixed> $existing Existing row
     *
     * @return void
     */
    private function update(
        int $assignmentId,
        int $studentId,
        string $content,
        string $fileName,
        array $existing
    ): void {
        if ($fileName === '' && $existing['file_name'] !== '') {
            $fileName = (string) $existing['file_name'];
        }

        $stmt = $this->prepare(
            'UPDATE submissions SET content = ?, file_name = ?, submitted_at = NOW()
             WHERE assignment_id = ? AND student_id = ?'
        );
        $stmt->bind_param('ssii', $content, $fileName, $assignmentId, $studentId);
        $this->execute($stmt);
        $stmt->close();
    }

    /**
     * @param int $assignmentId Assignment id
     * @param int $studentId Student id
     * @param string $content Content
     * @param string $fileName File
     *
     * @return void
     */
    private function insert(
        int $assignmentId,
        int $studentId,
        string $content,
        string $fileName
    ): void {
        $stmt = $this->prepare(
            'INSERT INTO submissions (assignment_id, student_id, content, file_name) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('iiss', $assignmentId, $studentId, $content, $fileName);
        $this->execute($stmt);
        $stmt->close();
    }

    /**
     * @param string $sql SQL
     *
     * @return mysqli_stmt
     */
    private function prepare(string $sql): mysqli_stmt
    {
        $stmt = $this->connection->prepare($sql);

        if ($stmt === false) {
            throw new DatabaseException('Failed to prepare: ' . $this->connection->error);
        }

        return $stmt;
    }

    /**
     * @param mysqli_stmt $stmt Statement
     *
     * @return void
     */
    private function execute(mysqli_stmt $stmt): void
    {
        if (!$stmt->execute()) {
            throw new DatabaseException('Query failed: ' . $stmt->error);
        }
    }

    /**
     * @param mysqli_stmt $stmt Statement
     *
     * @return array<string, mixed>|null
     */
    private function fetchOne(mysqli_stmt $stmt): ?array
    {
        $row = $stmt->get_result()->fetch_assoc();

        return $row !== null ? $row : null;
    }

    /**
     * @param mysqli_stmt $stmt Statement
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchAll(mysqli_stmt $stmt): array
    {
        $rows = [];
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }
}
