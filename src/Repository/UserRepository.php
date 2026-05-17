<?php

declare(strict_types=1);

namespace App\Repository;

use App\Exception\DatabaseException;
use mysqli;
use mysqli_stmt;

/**
 * Persists and loads user accounts.
 *
 * @author Jericho
 * @since 2026-05-17
 * @package App\Repository
 */
final class UserRepository
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
     * @param string $email Email address
     *
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $this->execute($stmt);

        $row = $this->fetchOne($stmt);
        $stmt->close();

        return $row;
    }

    /**
     * @param string $email Email to check
     *
     * @return bool
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $this->execute($stmt);
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    /**
     * @param string $name Display name
     * @param string $email Email
     * @param string $passwordHash Hashed password
     * @param string $role Role
     *
     * @return bool
     */
    public function create(string $name, string $email, string $passwordHash, string $role): bool
    {
        $stmt = $this->prepare(
            'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('ssss', $name, $email, $passwordHash, $role);
        $success = $this->execute($stmt);
        $stmt->close();

        return $success;
    }

    /**
     * @param string $sql SQL with placeholders
     *
     * @return mysqli_stmt
     */
    private function prepare(string $sql): mysqli_stmt
    {
        $stmt = $this->connection->prepare($sql);

        if ($stmt === false) {
            throw new DatabaseException('Failed to prepare statement: ' . $this->connection->error);
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
     * @return array<string, mixed>|null
     */
    private function fetchOne(mysqli_stmt $stmt): ?array
    {
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return $row !== null ? $row : null;
    }
}
