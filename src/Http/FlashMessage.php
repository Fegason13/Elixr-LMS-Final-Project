<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Stores one-time flash messages across redirects.
 *
 * @author Jericho
 * @since 2026-05-17
 * @package App\Http
 */
final class FlashMessage
{
    /**
     * @param string $message Text to display
     * @param string $type success or error
     *
     * @return void
     */
    public function set(string $message, string $type): void
    {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }

    /**
     * @return array{message: string, type: string}|null
     */
    public function get(): ?array
    {
        if (!isset($_SESSION['flash_message'])) {
            return null;
        }

        $flash = [
            'message' => (string) $_SESSION['flash_message'],
            'type' => (string) $_SESSION['flash_type'],
        ];

        unset($_SESSION['flash_message'], $_SESSION['flash_type']);

        return $flash;
    }
}
