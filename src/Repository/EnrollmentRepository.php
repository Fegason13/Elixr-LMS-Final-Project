<?php

declare(strict_types=1);

namespace App\Repository;

use App\Exception\DatabaseException;
use mysqli;
use mysqli_stmt;

/**
 * Manages student enrollments in classes.
 *
 * @author Jericho
 * @since 2026-05-17
 * @package App\Repository
 */
final class EnrollmentRepository
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
     * @param int $classId Class id
     * @param int $studentId Student id
     *
     * @return bool
     */
    public function isEnrolled(int $classId, int $studentId): bool
    {
        $stmt = $this->prepare(
            'SELECT id FROM enrollments WHERE class_id = ? AND student_id = ? LIMIT 1'
        );
        $stmt->bind_param('ii', $classId, $studentId);
        $this->execute($stmt);
        $enrolled = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        return $enrolled;
    }

    /**
     * @param int $classId Class id
     * @param int $studentId Student id
     *
     * @return bool
     */
    public function enroll(int $classId, int $studentId): bool
    {
        $stmt = $this->prepare(
            'INSERT INTO enrollments (class_id, student_id) VALUES (?, ?)'
        );
        $stmt->bind_param('ii', $classId, $studentId);
        $success = $this->execute($stmt);
        $stmt->close();

        return $success;
    }

    /**
     * @param int $classId Class id
     * @param int $studentId Student id
     *
     * @return void
     */
    public function leave(int $classId, int $studentId): void
    {
        $stmt = $this->prepare(
            'DELETE FROM enrollments WHERE class_id = ? AND student_id = ?'
        );
        $stmt->bind_param('ii', $classId, $studentId);
        $this->execute($stmt);
        $stmt->close();
    }

    /**
     * @param int $classId Class id
     *
     * @return array<int, array<string, mixed>>
     */
    public function listStudents(int $classId): array
    {
        $sql = 'SELECT u.id, u.name, u.email, e.joined_at FROM enrollments e
                INNER JOIN users u ON u.id = e.student_id
                WHERE e.class_id = ? ORDER BY u.name';
        $stmt = $this->prepare($sql);
        $stmt->bind_param('i', $classId);
        $this->execute($stmt);
        $rows = $this->fetchAll($stmt);
        $stmt->close();

        return $rows;
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
     * @return bool
     */
    private function execute(mysqli_stmt $stmt): bool
    {
        if (!$stmt->execute()) {
            throw new DatabaseException('Query failed: ' . $stmt->error);
        }

        return true;
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
