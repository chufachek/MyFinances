<?php

namespace App\Controllers;

use App\Services\Auth;
use App\Services\Database;
use App\Services\Request;
use App\Services\Response;

class CategoriesController
{
    public function index()
    {
        $type = isset($_GET['type']) ? $_GET['type'] : null;
        $pdo = Database::connection();
        $query = 'SELECT * FROM categories WHERE user_id = :user_id';
        $params = ['user_id' => Auth::userId()];
        if ($type) {
            $query .= ' AND category_type = :type';
            $params['type'] = $type;
        }
        $query .= ' ORDER BY is_active DESC, name';
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        Response::json(['categories' => $stmt->fetchAll()]);
    }

    public function store()
    {
        $data = Request::data();
        $name = trim((string)(isset($data['name']) ? $data['name'] : ''));
        $type = (string)(isset($data['category_type']) ? $data['category_type'] : 'expense');
        $isActive = isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1;

        if ($name === '') {
            Response::json(['error' => 'Название категории обязательно'], 422);
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO categories (user_id, name, category_type, is_active) VALUES (:user_id, :name, :type, :active)');
        $stmt->execute([
            'user_id' => Auth::userId(),
            'name' => $name,
            'type' => $type,
            'active' => $isActive,
        ]);
        Response::json(['success' => true]);
    }

    public function update($id)
    {
        $data = Request::data();
        $name = trim((string)(isset($data['name']) ? $data['name'] : ''));
        $type = (string)(isset($data['category_type']) ? $data['category_type'] : 'expense');
        $isActive = isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1;

        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE categories SET name = :name, category_type = :type, is_active = :active WHERE category_id = :id AND user_id = :user_id');
        $stmt->execute([
            'name' => $name,
            'type' => $type,
            'active' => $isActive,
            'id' => $id,
            'user_id' => Auth::userId(),
        ]);
        Response::json(['success' => true]);
    }

    public function delete($id)
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE categories SET is_active = 0 WHERE category_id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => Auth::userId()]);
        Response::json(['success' => true]);
    }
}
