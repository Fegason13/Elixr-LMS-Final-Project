<?php

declare(strict_types=1);

namespace App\Repository;

use App\Exception\DatabaseException;
use mysqli;
use mysqli_stmt;

/**
 * Persists assignments and loads them for classes.
 *
 * @author Jericho
 * @since 2026-05-17
 * @package App\Repository
 */
final class AssignmentRepository
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
     * @param int $assignmentId Assignment id
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $assignmentId): ?array
    {
        $sql = 'SELECT a.*, c.name AS class_name, c.id AS class_id, c.teacher_id
                FROM assignments a INNER JOIN classes c ON c.id = a.class_id
                WHERE a.id = ?';
        $stmt = $this->prepare($sql);
        $stmt->bind_param('i', $assignmentId);
        $this->execute($stmt);
        $row = $this->fetchOne($stmt);
        $stmt->close();

        return $row;
    }

    /**
     * @param int $classId Class id
     *
     * @return array<int, array<string, mixed>>
     */
    public function listByClass(int $classId): array
    {
        $sql = "SELECT a.*, 'assignment' AS type, '' AS author_name
                FROM assignments a WHERE a.class_id = ? ORDER BY a.created_at DESC";
        $stmt = $this->prepare($sql);
        $stmt->bind_param('i', $classId);
        $this->execute($stmt);
        $rows = $this->fetchAll($stmt);
        $stmt->close();

        return $rows;
    }

    /**
     * @param int $classId Class id
     * @param string $title Title
     * @param string $description Instructions
     * @param string|null $dueDate Due datetime or null
     * @param int $points Point value
     *
     * @return void
     */
    public function create(
        int $classId,
        string $title,
        string $description,
        ?string $dueDate,
        int $points
    ): void {
        if ($dueDate === null) {
            $sql = 'INSERT INTO assignments (class_id, title, description, due_date, points)
                    VALUES (?, ?, ?, NULL, ?)';
            $stmt = $this->prepare($sql);
            $stmt->bind_param('issi', $classId, $title, $description, $points);
        } else {
            $sql = 'INSERT INTO assignments (class_id, title, description, due_date, points)
                    VALUES (?, ?, ?, ?, ?)';
            $stmt = $this->prepare($sql);
            $stmt->bind_param('isssi', $classId, $title, $description, $dueDate, $points);
        }

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
