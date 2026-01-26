<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Auth;
use App\Services\Request;
use App\Services\Response;
use App\Services\Database;

class AuthController
{
    public function register(): void
    {
        $data = Request::data();
        $email = trim((string)($data['email'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $name = trim((string)($data['full_name'] ?? '')) ?: null;

        if ($email === '' || $password === '') {
            Response::json(['error' => 'Email и пароль обязательны'], 422);
            return;
        }

        $result = Auth::register($email, $password, $name);
        if (!$result['success']) {
            Response::json(['error' => $result['message']], 409);
            return;
        }

        Response::json(['success' => true]);
    }

    public function login(): void
    {
        $data = Request::data();
        $email = trim((string)($data['email'] ?? ''));
        $password = (string)($data['password'] ?? '');

        if ($email === '' || $password === '') {
            Response::json(['error' => 'Email и пароль обязательны'], 422);
            return;
        }

        if (!Auth::login($email, $password)) {
            Response::json(['error' => 'Неверный логин или пароль'], 401);
            return;
        }

        Response::json(['success' => true]);
    }

    public function logout(): void
    {
        Auth::logout();
        Response::json(['success' => true]);
    }

    public function me(): void
    {
        $pdo = Database::connection();
        $user = Auth::user($pdo);
        if (!$user) {
            Response::json(['user' => null], 401);
            return;
        }
        Response::json(['user' => $user]);
    }
}
