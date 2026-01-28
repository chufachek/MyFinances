<?php

namespace App\Controllers;

use App\Services\Auth;
use App\Services\Database;
use App\Services\Request;
use App\Services\Response;
use PDOException;

class BudgetsController
{
    public function index()
    {
        $pdo = Database::connection();
        $userId = Auth::userId();

        try {
            if (!$this->hasTable($pdo, 'budgets')) {
                Response::json(['budgets' => []]);
                return;
            }
            if (!$this->hasTable($pdo, 'categories')) {
                Response::json(['budgets' => []]);
                return;
            }

            $hasTransactions = $this->hasTable($pdo, 'transactions');
            if ($hasTransactions) {
                $stmt = $pdo->prepare(
                    "SELECT b.budget_id,
                            b.user_id,
                            b.category_id,
                            b.period_month,
                            b.limit_amount,
                            c.name AS category_name,
                            IFNULL(
                                (
                                    SELECT SUM(t.amount)
                                    FROM transactions t
                                    WHERE t.user_id = b.user_id
                                      AND t.category_id = b.category_id
                                      AND t.tx_type = 'expense'
                                      AND DATE_FORMAT(t.tx_date, '%Y-%m') = b.period_month
                                ),
                                0
                            ) AS spent
                     FROM budgets b
                     JOIN categories c ON c.category_id = b.category_id
                     WHERE b.user_id = :user_id
                     ORDER BY b.period_month DESC, c.name"
                );
            } else {
                $stmt = $pdo->prepare(
                    "SELECT b.budget_id,
                            b.user_id,
                            b.category_id,
                            b.period_month,
                            b.limit_amount,
                            c.name AS category_name,
                            0 AS spent
                     FROM budgets b
                     JOIN categories c ON c.category_id = b.category_id
                     WHERE b.user_id = :user_id
                     ORDER BY b.period_month DESC, c.name"
                );
            }
            $stmt->execute(['user_id' => $userId]);
            $budgets = $stmt->fetchAll();
            foreach ($budgets as &$budget) {
                $limit = (float) $budget['limit_amount'];
                $spent = (float) $budget['spent'];
                $status = $this->buildStatus($limit, $spent);
                $budget['status_label'] = $status['label'];
                $budget['status_variant'] = $status['variant'];
            }
            unset($budget);
            Response::json(['budgets' => $budgets]);
        } catch (PDOException $exception) {
            Response::json(['error' => 'Ошибка загрузки бюджетов'], 500);
        }
    }

    public function store()
    {
        $data = Request::data();
        $categoryId = (int) ($data['category_id'] ?? 0);
        $month = (string) ($data['period_month'] ?? '');
        $limit = (float) ($data['limit_amount'] ?? 0);

        if ($categoryId <= 0 || $month === '' || $limit <= 0) {
            Response::json(['error' => 'Заполните данные бюджета'], 422);
            return;
        }

        $pdo = Database::connection();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO budgets (user_id, category_id, period_month, limit_amount)
                 VALUES (:user_id, :category_id, :month, :amount)'
            );
            $stmt->execute([
                'user_id' => Auth::userId(),
                'category_id' => $categoryId,
                'month' => $month,
                'amount' => $limit,
            ]);
            Response::json(['success' => true]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                Response::json(['error' => 'Бюджет на эту категорию и месяц уже существует'], 409);
                return;
            }
            Response::json(['error' => 'Ошибка создания бюджета'], 500);
        }
    }

    public function update($id)
    {
        $budgetId = (int) $id;
        if ($budgetId <= 0) {
            Response::json(['error' => 'Некорректный бюджет'], 422);
            return;
        }

        $data = Request::data();
        $categoryId = (int) ($data['category_id'] ?? 0);
        $month = (string) ($data['period_month'] ?? '');
        $limit = (float) ($data['limit_amount'] ?? 0);

        if ($categoryId <= 0 || $month === '' || $limit <= 0) {
            Response::json(['error' => 'Заполните данные бюджета'], 422);
            return;
        }

        $pdo = Database::connection();
        try {
            $stmt = $pdo->prepare(
                'UPDATE budgets
                 SET category_id = :category_id, period_month = :month, limit_amount = :amount
                 WHERE budget_id = :id AND user_id = :user_id'
            );
            $stmt->execute([
                'category_id' => $categoryId,
                'month' => $month,
                'amount' => $limit,
                'id' => $budgetId,
                'user_id' => Auth::userId(),
            ]);
            Response::json(['success' => true]);
        } catch (PDOException $exception) {
            Response::json(['error' => 'Ошибка обновления бюджета'], 500);
        }
    }

    public function delete($id)
    {
        $budgetId = (int) $id;
        if ($budgetId <= 0) {
            Response::json(['error' => 'Некорректный бюджет'], 422);
            return;
        }

        $pdo = Database::connection();
        try {
            $stmt = $pdo->prepare('DELETE FROM budgets WHERE budget_id = :id AND user_id = :user_id');
            $stmt->execute(['id' => $budgetId, 'user_id' => Auth::userId()]);
            Response::json(['success' => true]);
        } catch (PDOException $exception) {
            Response::json(['error' => 'Ошибка удаления бюджета'], 500);
        }
    }

    private function hasTable($pdo, string $table): bool
    {
        $stmt = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table));
        return (bool) $stmt->fetchColumn();
    }

    private function buildStatus(float $limit, float $spent): array
    {
        if ($limit <= 0) {
            return ['label' => 'В пределах', 'variant' => 'success'];
        }

        $percent = ($spent / $limit) * 100;
        if ($percent >= 100) {
            return ['label' => 'Превышено', 'variant' => 'danger'];
        }
        if ($percent >= 85) {
            return ['label' => 'Почти лимит', 'variant' => 'warning'];
        }
        return ['label' => 'В пределах', 'variant' => 'success'];
    }
}
