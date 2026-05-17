<?php

declare(strict_types=1);

namespace App\Auth;

/**
 * Immutable snapshot of the logged-in user from session.
 *
 * @author Jericho
 * @since 2026-05-17
 * @package App\Auth
 */
final class UserContext
{
    /** @var int */
    private int $id;

    /** @var string */
    private string $name;

    /** @var string */
    private string $email;

    /** @var string */
    private string $role;

    /**
     * @param int $id User id
     * @param string $name Display name
     * @param string $email Email
     * @param string $role teacher or student
     */
    public function __construct(int $id, string $name, string $email, string $role)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->role = $role;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * @return bool
     */
    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    /**
     * @return bool
     */
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    /**
     * @return array<string, int|string>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
        ];
    }
}
