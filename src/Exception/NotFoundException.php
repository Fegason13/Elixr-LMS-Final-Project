<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

/**
 * Thrown when a requested resource does not exist.
 *
 * @author Jericho
 * @since 2026-05-17
 * @package App\Exception
 */
class NotFoundException extends RuntimeException
{
}
