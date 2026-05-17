<?php

declare(strict_types=1);

namespace App\Repository;

use App\Exception\DatabaseException;
use mysqli;
use mysqli_stmt;

/**
 * Loads and mutates class records.
 *
 * @author Jericho
 * @since 2026-05-17
 * @package App\Repository
 */
final class ClassRepository
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
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $classId): ?array
    {
        $sql = 'SELECT c.*, u.name AS teacher_name FROM classes c
                INNER JOIN users u ON u.id = c.teacher_id WHERE c.id = ?';
        $stmt = $this->prepare($sql);
        $stmt->bind_param('i', $classId);
        $this->execute($stmt);
        $row = $this->fetchOne($stmt);
        $stmt->close();

        return $row;
    }

    /**
     * @param string $joinCode Join code
     *
     * @return array<string, mixed>|null
     */
    public function findByJoinCode(string $joinCode): ?array
    {
        $stmt = $this->prepare('SELECT * FROM classes WHERE join_code = ? LIMIT 1');
        $stmt->bind_param('s', $joinCode);
        $this->execute($stmt);
        $row = $this->fetchOne($stmt);
        $stmt->close();

        return $row;
    }

    /**
     * @param string $joinCode Code to check
     *
     * @return bool
     */
    public function joinCodeExists(string $joinCode): bool
    {
        $stmt = $this->prepare('SELECT id FROM classes WHERE join_code = ? LIMIT 1');
        $stmt->bind_param('s', $joinCode);
        $this->execute($stmt);
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    /**
     * @param int $teacherId Teacher user id
     *
     * @return array<int, array<string, mixed>>
     */
    public function listForTeacher(int $teacherId): array
    {
        $sql = 'SELECT c.*, (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = c.id)
                AS student_count FROM classes c WHERE c.teacher_id = ? ORDER BY c.created_at DESC';
        $stmt = $this->prepare($sql);
        $stmt->bind_param('i', $teacherId);
        $this->execute($stmt);
        $rows = $this->fetchAll($stmt);
        $stmt->close();

        return $rows;
    }

    /**
     * @param int $studentId Student user id
     *
     * @return array<int, array<string, mixed>>
     */
    public function listForStudent(int $studentId): array
    {
        $sql = 'SELECT c.*, u.name AS teacher_name FROM classes c
                INNER JOIN enrollments e ON e.class_id = c.id
                INNER JOIN users u ON u.id = c.teacher_id
                WHERE e.student_id = ? ORDER BY c.name ASC';
        $stmt = $this->prepare($sql);
        $stmt->bind_param('i', $studentId);
        $this->execute($stmt);
        $rows = $this->fetchAll($stmt);
        $stmt->close();

        return $rows;
    }

    /**
     * @param string $name Class name
     * @param string $section Section
     * @param string $subject Subject
     * @param string $description Description
     * @param string $joinCode Unique code
     * @param int $teacherId Teacher id
     *
     * @return int New class id
     */
    public function create(
        string $name,
        string $section,
        string $subject,
        string $description,
        string $joinCode,
        int $teacherId
    ): int {
        $sql = 'INSERT INTO classes (name, section, subject, description, join_code, teacher_id)
                VALUES (?, ?, ?, ?, ?, ?)';
        $stmt = $this->prepare($sql);
        $stmt->bind_param('sssssi', $name, $section, $subject, $description, $joinCode, $teacherId);
        $this->execute($stmt);
        $id = (int) $this->connection->insert_id;
        $stmt->close();

        return $id;
    }

    /**
     * @param int $classId Class id
     * @param int $teacherId Owner teacher id
     *
     * @return void
     */
    public function deleteForTeacher(int $classId, int $teacherId): void
    {
        $stmt = $this->prepare('DELETE FROM classes WHERE id = ? AND teacher_id = ?');
        $stmt->bind_param('ii', $classId, $teacherId);
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
