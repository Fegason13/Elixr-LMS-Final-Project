<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

/**
 * Thrown when the database connection or query fails unexpectedly.
 *
 * @author Jericho
 * @since 2026-05-17
 * @package App\Exception
 */
class DatabaseException extends RuntimeException
{
}
