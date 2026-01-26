<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Auth;
use App\Services\Database;
use App\Services\Request;
use App\Services\Response;

class GoalsController
{
    public function index(): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM goals WHERE user_id = :user_id ORDER BY due_date ASC');
        $stmt->execute(['user_id' => Auth::userId()]);
        Response::json(['goals' => $stmt->fetchAll()]);
    }

    public function store(): void
    {
        $data = Request::data();
        $name = trim((string)($data['name'] ?? ''));
        $target = (float)($data['target_amount'] ?? 0);
        $current = (float)($data['current_amount'] ?? 0);
        $due = $data['due_date'] ?? null;
        $status = $data['status'] ?? 'active';

        if ($name === '' || $target <= 0) {
            Response::json(['error' => 'Заполните название и сумму цели'], 422);
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO goals (user_id, name, target_amount, current_amount, due_date, status) VALUES (:user_id, :name, :target, :current, :due, :status)');
        $stmt->execute([
            'user_id' => Auth::userId(),
            'name' => $name,
            'target' => $target,
            'current' => $current,
            'due' => $due,
            'status' => $status,
        ]);
        Response::json(['success' => true]);
    }

    public function update(string $id): void
    {
        $data = Request::data();
        $name = trim((string)($data['name'] ?? ''));
        $target = (float)($data['target_amount'] ?? 0);
        $current = (float)($data['current_amount'] ?? 0);
        $due = $data['due_date'] ?? null;
        $status = $data['status'] ?? 'active';

        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE goals SET name = :name, target_amount = :target, current_amount = :current, due_date = :due, status = :status WHERE goal_id = :id AND user_id = :user_id');
        $stmt->execute([
            'name' => $name,
            'target' => $target,
            'current' => $current,
            'due' => $due,
            'status' => $status,
            'id' => $id,
            'user_id' => Auth::userId(),
        ]);
        Response::json(['success' => true]);
    }

    public function delete(string $id): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('DELETE FROM goals WHERE goal_id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => Auth::userId()]);
        Response::json(['success' => true]);
    }
}
