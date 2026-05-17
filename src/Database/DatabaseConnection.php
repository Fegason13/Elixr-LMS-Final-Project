<?php

declare(strict_types=1);

namespace App\Database;

use App\Config\AppConfig;
use App\Exception\DatabaseException;
use mysqli;

/**
 * Provides a single MySQLi connection per request.
 *
 * @author Jericho
 * @since 2026-05-17
 * @package App\Database
 */
final class DatabaseConnection
{
    /** @var mysqli|null Active connection */
    private ?mysqli $connection = null;

    /**
     * @return mysqli
     *
     * @throws DatabaseException When connection fails
     */
    public function getConnection(): mysqli
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        $connection = mysqli_connect(
            AppConfig::getDbHost(),
            AppConfig::getDbUser(),
            AppConfig::getDbPass(),
            AppConfig::getDbName()
        );

        if ($connection === false) {
            throw new DatabaseException(
                'Database connection failed: ' . mysqli_connect_error()
            );
        }

        mysqli_set_charset($connection, 'utf8mb4');
        $this->connection = $connection;

        return $this->connection;
    }
}
