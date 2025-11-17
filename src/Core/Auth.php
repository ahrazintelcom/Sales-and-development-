<?php

namespace App\Core;

use App\Models\User;

class Auth
{
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function attempt(string $email, string $password): bool
    {
        $userModel = new User();
        $user = $userModel->findByEmail($email);
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user'] = $user;
            return true;
        }
        return false;
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
    }

    public static function requireRole(array $roles): void
    {
        $user = self::user();
        if (!$user || !in_array($user['role'], $roles, true)) {
            header('Location: /?route=login');
            exit;
        }
    }
}
