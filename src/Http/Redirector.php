<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Sends HTTP redirect responses and stops execution.
 *
 * @author Jericho
 * @since 2026-05-17
 * @package App\Http
 */
final class Redirector
{
    /**
     * @param string $url Relative or absolute URL
     *
     * @return never
     */
    public function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
