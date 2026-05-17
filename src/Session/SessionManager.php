<?php

declare(strict_types=1);

namespace App\Session;

/**
 * Wraps PHP session access for authentication state.
 *
 * @author Jericho
 * @since 2026-05-17
 * @package App\Session
 */
final class SessionManager
{
    /**
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * @param int $userId User primary key
     * @param string $name Display name
     * @param string $email Email address
     * @param string $role teacher or student
     *
     * @return void
     */
    public function setUser(int $userId, string $name, string $email, string $role): void
    {
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $role;
    }

    /**
     * @return void
     */
    public function destroy(): void
    {
        session_destroy();
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return (int) $_SESSION['user_id'];
    }

    /**
     * @return string
     */
    public function getUserName(): string
    {
        return (string) $_SESSION['user_name'];
    }

    /**
     * @return string
     */
    public function getUserEmail(): string
    {
        return (string) $_SESSION['user_email'];
    }

    /**
     * @return string
     */
    public function getUserRole(): string
    {
        return (string) $_SESSION['user_role'];
    }
}
