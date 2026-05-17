<?php

declare(strict_types=1);

namespace App\Util;

/**
 * Formats database datetimes for display in the UI.
 *
 * @author Jericho
 * @since 2026-05-17
 * @package App\Util
 */
final class DateFormatter
{
    /**
     * @param string|null $datetime Raw datetime from database
     *
     * @return string Human-readable date or placeholder
     */
    public function format(?string $datetime): string
    {
        if ($datetime === null || $datetime === '') {
            return 'No due date';
        }

        $timestamp = strtotime($datetime);

        if ($timestamp === false) {
            return 'No due date';
        }

        return date('M j, Y g:i A', $timestamp);
    }
}
