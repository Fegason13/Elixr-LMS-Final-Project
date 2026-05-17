<?php

declare(strict_types=1);

namespace App\Repository;

use App\Exception\DatabaseException;
use mysqli;
use mysqli_stmt;

/**
 * Stores class announcements for the stream.
 *
 * @author Jericho
 * @since 2026-05-17
 * @package App\Repository
 */
final class AnnouncementRepository
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
     * @param int $teacherId Teacher id
     * @param string $content Announcement body
     *
     * @return void
     */
    public function create(int $classId, int $teacherId, string $content): void
    {
        $stmt = $this->prepare(
            'INSERT INTO announcements (class_id, teacher_id, content) VALUES (?, ?, ?)'
        );
        $stmt->bind_param('iis', $classId, $teacherId, $content);
        $this->execute($stmt);
        $stmt->close();
    }

    /**
     * @param int $classId Class id
     *
     * @return array<int, array<string, mixed>>
     */
    public function listByClass(int $classId): array
    {
        $sql = "SELECT a.*, u.name AS author_name, 'announcement' AS type
                FROM announcements a INNER JOIN users u ON u.id = a.teacher_id
                WHERE a.class_id = ? ORDER BY a.created_at DESC";
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
