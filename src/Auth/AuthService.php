<?php

declare(strict_types=1);

namespace App\Auth;

use App\Http\Redirector;
use App\Repository\UserRepository;
use App\Session\SessionManager;

/**
 * Handles login state, registration, and access guards for pages.
 *
 * @author Jericho
 * @since 2026-05-17
 * @package App\Auth
 */
final class AuthService
{
    /** @var SessionManager */
    private SessionManager $session;

    /** @var UserRepository */
    private UserRepository $users;

    /** @var Redirector */
    private Redirector $redirector;

    /**
     * @param SessionManager $session Session wrapper
     * @param UserRepository $users User persistence
     * @param Redirector $redirector HTTP redirects
     */
    public function __construct(
        SessionManager $session,
        UserRepository $users,
        Redirector $redirector
    ) {
        $this->session = $session;
        $this->users = $users;
        $this->redirector = $redirector;
    }

    /**
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return $this->session->isLoggedIn();
    }

    /**
     * Redirects guests to the login page.
     *
     * @return void
     */
    public function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            $this->redirector->redirect('../index.php');
        }
    }

    /**
     * @return UserContext
     */
    public function currentUser(): UserContext
    {
        return new UserContext(
            $this->session->getUserId(),
            $this->session->getUserName(),
            $this->session->getUserEmail(),
            $this->session->getUserRole()
        );
    }

    /**
     * @return bool
     */
    public function isTeacher(): bool
    {
        return $this->isLoggedIn() && $this->session->getUserRole() === 'teacher';
    }

    /**
     * @return bool
     */
    public function isStudent(): bool
    {
        return $this->isLoggedIn() && $this->session->getUserRole() === 'student';
    }

    /**
     * @param string $email Login email
     * @param string $password Plain password
     *
     * @return string|null Error message or null on success
     */
    public function attemptLogin(string $email, string $password): ?string
    {
        if ($email === '' || $password === '') {
            return 'Please enter email and password.';
        }

        $user = $this->users->findByEmail($email);

        if ($user === null || !password_verify($password, $user['password'])) {
            return 'Invalid email or password.';
        }

        $this->session->setUser(
            (int) $user['id'],
            $user['name'],
            $user['email'],
            $user['role']
        );

        return null;
    }

    /**
     * @param string $name Full name
     * @param string $email Email
     * @param string $password Plain password
     * @param string $confirm Confirmation password
     * @param string $role teacher or student
     *
     * @return string|null Error message or null on success
     */
    public function register(
        string $name,
        string $email,
        string $password,
        string $confirm,
        string $role
    ): ?string {
        $validationError = $this->validateRegistration($name, $email, $password, $confirm, $role);

        if ($validationError !== null) {
            return $validationError;
        }

        if ($this->users->emailExists($email)) {
            return 'Email is already registered.';
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        if (!$this->users->create($name, $email, $hash, $role)) {
            return 'Registration failed. Please try again.';
        }

        return null;
    }

    /**
     * @param string $name Name
     * @param string $email Email
     * @param string $password Password
     * @param string $confirm Confirm
     * @param string $role Role
     *
     * @return string|null
     */
    private function validateRegistration(
        string $name,
        string $email,
        string $password,
        string $confirm,
        string $role
    ): ?string {
        if ($name === '' || $email === '' || $password === '') {
            return 'All fields are required.';
        }

        if ($password !== $confirm) {
            return 'Passwords do not match.';
        }

        if (strlen($password) < 6) {
            return 'Password must be at least 6 characters.';
        }

        if ($role !== 'teacher' && $role !== 'student') {
            return 'Please select a valid role.';
        }

        return null;
    }
}
