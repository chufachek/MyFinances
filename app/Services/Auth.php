<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

class Auth
{
    public static function userId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function check(): bool
    {
        return self::userId() !== null;
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            Response::json(['error' => 'Unauthorized'], 401);
            exit;
        }
    }

    public static function login(string $email, string $password): bool
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT user_id, password_hash, full_name, email FROM users WHERE email = :email AND status = "active"');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        $_SESSION['user_id'] = (int) $user['user_id'];
        $_SESSION['user_name'] = $user['full_name'] ?: $user['email'];
        return true;
    }

    public static function register(string $email, string $password, ?string $fullName = null): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email уже зарегистрирован'];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $insert = $pdo->prepare('INSERT INTO users (email, password_hash, full_name) VALUES (:email, :hash, :name)');
        $insert->execute([
            'email' => $email,
            'hash' => $hash,
            'name' => $fullName,
        ]);
        $userId = (int) $pdo->lastInsertId();
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $fullName ?: $email;
        return ['success' => true, 'user_id' => $userId];
    }

    public static function logout(): void
    {
        session_destroy();
        $_SESSION = [];
    }

    public static function user(PDO $pdo): ?array
    {
        $userId = self::userId();
        if (!$userId) {
            return null;
        }
        $stmt = $pdo->prepare('SELECT user_id, email, full_name FROM users WHERE user_id = :id');
        $stmt->execute(['id' => $userId]);
        return $stmt->fetch() ?: null;
    }
}
