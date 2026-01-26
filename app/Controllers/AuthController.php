<?php

namespace App\Controllers;

use App\Services\Auth;
use App\Services\Request;
use App\Services\Response;

class AuthController
{
    public function register()
    {
        $data = Request::data();
        $email = trim((string)(isset($data['email']) ? $data['email'] : ''));
        $password = (string)(isset($data['password']) ? $data['password'] : '');
        $name = trim((string)(isset($data['full_name']) ? $data['full_name'] : '')) ?: null;

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

    public function login()
    {
        $data = Request::data();
        $email = trim((string)(isset($data['email']) ? $data['email'] : ''));
        $password = (string)(isset($data['password']) ? $data['password'] : '');

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

    public function logout()
    {
        Auth::logout();
        Response::json(['success' => true]);
    }

    public function me()
    {
        $user = Auth::user();
        if (!$user) {
            Response::json(['user' => null], 401);
            return;
        }
        Response::json(['user' => $user]);
    }
}
