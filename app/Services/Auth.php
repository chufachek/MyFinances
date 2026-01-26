<?php

namespace App\Services;

use PDO;

class Auth
{
    private static $usersColumns = null;

    public static function userId()
    {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }

    public static function check()
    {
        return self::userId() !== null;
    }

    public static function requireAuth()
    {
        if (!self::check()) {
            Response::json(['error' => 'Unauthorized'], 401);
            exit;
        }
    }

    public static function login($email, $password)
    {
        $pdo = Database::connection();
        $columns = self::usersColumns($pdo);
        $passwordColumn = self::resolveColumn($columns, ['password_hash', 'password']);
        $nameColumn = self::resolveColumn($columns, ['full_name', 'name']);
        $statusColumn = in_array('status', $columns, true) ? 'status' : null;

        if (!$passwordColumn) {
            return false;
        }

        $selectFields = ['user_id', $passwordColumn, 'email'];
        if ($nameColumn) {
            $selectFields[] = $nameColumn;
        }
        $where = 'email = :email';
        if ($statusColumn) {
            $where .= ' AND status = "active"';
        }
        $sql = sprintf('SELECT %s FROM users WHERE %s', implode(', ', $selectFields), $where);
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($password, $user[$passwordColumn])) {
            return false;
        }
        $_SESSION['user_id'] = (int) $user['user_id'];
        $displayName = $user['email'];
        if ($nameColumn && !empty($user[$nameColumn])) {
            $displayName = $user[$nameColumn];
        }
        $_SESSION['user_name'] = $displayName;
        return true;
    }

    public static function register($email, $password, $fullName = null)
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email уже зарегистрирован'];
        }

        $columns = self::usersColumns($pdo);
        $passwordColumn = self::resolveColumn($columns, ['password_hash', 'password']);
        $nameColumn = self::resolveColumn($columns, ['full_name', 'name']);

        if (!$passwordColumn) {
            return ['success' => false, 'message' => 'Невозможно создать пользователя'];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $fields = ['email' => $email, $passwordColumn => $hash];
        if ($nameColumn) {
            $fields[$nameColumn] = $fullName;
        }
        $columnsSql = implode(', ', array_keys($fields));
        $placeholders = implode(', ', array_map(static function ($key) {
            return ':' . $key;
        }, array_keys($fields)));
        $insert = $pdo->prepare(sprintf('INSERT INTO users (%s) VALUES (%s)', $columnsSql, $placeholders));
        $insert->execute($fields);
        $userId = (int) $pdo->lastInsertId();
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $fullName ?: $email;
        return ['success' => true, 'user_id' => $userId];
    }

    public static function logout()
    {
        session_destroy();
        $_SESSION = [];
    }

    public static function user(PDO $pdo)
    {
        $userId = self::userId();
        if (!$userId) {
            return null;
        }
        $columns = self::usersColumns($pdo);
        $nameColumn = self::resolveColumn($columns, ['full_name', 'name']);
        $selectFields = ['user_id', 'email'];
        if ($nameColumn) {
            $selectFields[] = $nameColumn;
        }
        $stmt = $pdo->prepare(sprintf('SELECT %s FROM users WHERE user_id = :id', implode(', ', $selectFields)));
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch() ?: null;
        if ($user && $nameColumn && $nameColumn !== 'full_name') {
            $user['full_name'] = $user[$nameColumn];
        }
        return $user;
    }

    private static function usersColumns(PDO $pdo)
    {
        if (self::$usersColumns !== null) {
            return self::$usersColumns;
        }

        $stmt = $pdo->query('SHOW COLUMNS FROM users');
        $columns = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $columns[] = $row['Field'];
        }
        self::$usersColumns = $columns;
        return $columns;
    }

    private static function resolveColumn(array $columns, array $candidates)
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $columns, true)) {
                return $candidate;
            }
        }
        return null;
    }
}
