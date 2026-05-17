<?php

declare(strict_types=1);

namespace App\Config;

/**
 * Central application configuration for database and paths.
 *
 * @author Jericho
 * @since 2026-05-17
 * @package App\Config
 */
final class AppConfig
{
  /** @var string Application display name */
    public const SITE_NAME = 'Elixr LMS';

  /** @var string Default database host */
    public const DB_HOST = 'localhost';

  /** @var string Default database user */
    public const DB_USER = 'root';

  /** @var string Default database password */
    public const DB_PASS = '';

  /** @var string Default database name */
    public const DB_NAME = 'classroom_lms';

  /** @var string Student author name for PHPDoc blocks */
    public const AUTHOR_NAME = 'Jericho';

  /**
     * Ensures session is started once per request.
     *
     * @return void
     */
    public static function load(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * @return string
     */
    public static function getSiteName(): string
    {
        return self::SITE_NAME;
    }

    /**
     * @return string
     */
    public static function getDbHost(): string
    {
        return self::DB_HOST;
    }

    /**
     * @return string
     */
    public static function getDbUser(): string
    {
        return self::DB_USER;
    }

    /**
     * @return string
     */
    public static function getDbPass(): string
    {
        return self::DB_PASS;
    }

    /**
     * @return string
     */
    public static function getDbName(): string
    {
        return self::DB_NAME;
    }

    /**
     * Absolute path to the uploads directory.
     *
     * @return string
     */
    public static function getUploadDir(): string
    {
        return dirname(__DIR__, 2) . '/uploads/';
    }
}
