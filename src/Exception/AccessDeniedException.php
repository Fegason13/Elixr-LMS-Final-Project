<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

/**
 * Thrown when the current user lacks permission for an action.
 *
 * @author Jericho
 * @since 2026-05-17
 * @package App\Exception
 */
class AccessDeniedException extends RuntimeException
{
}
