<?php

namespace App\Controllers;

use App\Services\Auth;
use App\Services\Database;
use App\Services\Request;
use App\Services\Response;
use PDOException;

class GoalsController
{
    private function isMissingTableError(PDOException $exception): bool
    {
        return $exception->getCode() === '42S02';
    }

    private function isDuplicateKeyError(PDOException $exception): bool
    {
        return $exception->getCode() === '23000';
    }

    private function ensureGoalsTable($pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS goals (
                goal_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                name VARCHAR(120) NOT NULL,
                target_amount DECIMAL(12,2) NOT NULL,
                current_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
                due_date DATE DEFAULT NULL,
                status ENUM(\'active\', \'done\', \'canceled\') NOT NULL DEFAULT \'active\',
                CONSTRAINT fk_goals_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                UNIQUE KEY uniq_goals_user_name (user_id, name),
                KEY idx_goals_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    public function index()
    {
        $pdo = Database::connection();
        try {
            $stmt = $pdo->prepare('SELECT * FROM goals WHERE user_id = :user_id ORDER BY due_date ASC');
            $stmt->execute(['user_id' => Auth::userId()]);
            Response::json(['goals' => $stmt->fetchAll()]);
        } catch (PDOException $exception) {
            if ($this->isMissingTableError($exception)) {
                $this->ensureGoalsTable($pdo);
                $stmt = $pdo->prepare('SELECT * FROM goals WHERE user_id = :user_id ORDER BY due_date ASC');
                $stmt->execute(['user_id' => Auth::userId()]);
                Response::json(['goals' => $stmt->fetchAll()]);
                return;
            }
            Response::json(['error' => 'Ошибка загрузки целей'], 500);
        }
    }

    public function store()
    {
        $data = Request::data();
        $name = trim((string)(isset($data['name']) ? $data['name'] : ''));
        $target = (float)(isset($data['target_amount']) ? $data['target_amount'] : 0);
        $current = (float)(isset($data['current_amount']) ? $data['current_amount'] : 0);
        $due = isset($data['due_date']) ? $data['due_date'] : null;
        $status = isset($data['status']) ? $data['status'] : 'active';

        if ($name === '' || $target <= 0) {
            Response::json(['error' => 'Заполните название и сумму цели'], 422);
            return;
        }

        $pdo = Database::connection();
        try {
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
        } catch (PDOException $exception) {
            if ($this->isMissingTableError($exception)) {
                $this->ensureGoalsTable($pdo);
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
                return;
            }
            if ($this->isDuplicateKeyError($exception)) {
                Response::json(['error' => 'Цель с таким названием уже существует'], 409);
                return;
            }
            Response::json(['error' => 'Ошибка создания цели'], 500);
        }
    }

    public function update($id)
    {
        $data = Request::data();
        $name = trim((string)(isset($data['name']) ? $data['name'] : ''));
        $target = (float)(isset($data['target_amount']) ? $data['target_amount'] : 0);
        $current = (float)(isset($data['current_amount']) ? $data['current_amount'] : 0);
        $due = isset($data['due_date']) ? $data['due_date'] : null;
        $status = isset($data['status']) ? $data['status'] : 'active';

        $pdo = Database::connection();
        try {
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
        } catch (PDOException $exception) {
            if ($this->isMissingTableError($exception)) {
                $this->ensureGoalsTable($pdo);
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
                return;
            }
            if ($this->isDuplicateKeyError($exception)) {
                Response::json(['error' => 'Цель с таким названием уже существует'], 409);
                return;
            }
            Response::json(['error' => 'Ошибка обновления цели'], 500);
        }
    }

    public function delete($id)
    {
        $pdo = Database::connection();
        try {
            $stmt = $pdo->prepare('DELETE FROM goals WHERE goal_id = :id AND user_id = :user_id');
            $stmt->execute(['id' => $id, 'user_id' => Auth::userId()]);
            Response::json(['success' => true]);
        } catch (PDOException $exception) {
            if ($this->isMissingTableError($exception)) {
                $this->ensureGoalsTable($pdo);
                $stmt = $pdo->prepare('DELETE FROM goals WHERE goal_id = :id AND user_id = :user_id');
                $stmt->execute(['id' => $id, 'user_id' => Auth::userId()]);
                Response::json(['success' => true]);
                return;
            }
            Response::json(['error' => 'Ошибка удаления цели'], 500);
        }
    }
}
