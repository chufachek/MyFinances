<?php

namespace App\Controllers;

use App\Services\Auth;
use App\Services\Database;
use App\Services\Request;
use App\Services\Response;
use PDO;
use PDOException;

class BudgetsController
{
    private function ensureBudgetsTable(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS budgets (
                budget_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                category_id INT NOT NULL,
                period_month VARCHAR(7) NOT NULL,
                limit_amount DECIMAL(12,2) NOT NULL,
                CONSTRAINT fk_budgets_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                CONSTRAINT fk_budgets_category FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE,
                UNIQUE KEY uniq_budgets_user_period (user_id, category_id, period_month),
                KEY idx_budgets_user_period (user_id, period_month)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private function isMissingTableError(PDOException $e): bool
    {
        // MySQL: SQLSTATE[42S02] = Base table or view not found
        return $e->getCode() === '42S02';
    }

    private function withBudgetsTable(PDO $pdo, callable $fn)
    {
        try {
            return $fn();
        } catch (PDOException $e) {
            if ($this->isMissingTableError($e)) {
                $this->ensureBudgetsTable($pdo);
                return $fn();
            }
            throw $e;
        }
    }

    public function index()
    {
        $month = isset($_GET['month']) ? (string)$_GET['month'] : date('Y-m');
        $pdo = Database::connection();
        $userId = Auth::userId();

        $query = "
            SELECT
                b.budget_id,
                b.user_id,
                b.category_id,
                b.period_month,
                b.limit_amount,
                c.name AS category_name,
                COALESCE(SUM(t.amount), 0) AS spent
            FROM budgets b
            JOIN categories c ON c.category_id = b.category_id
            LEFT JOIN transactions t
                ON t.user_id = b.user_id
                AND t.category_id = b.category_id
                AND t.tx_type = 'expense'
                AND DATE_FORMAT(t.tx_date, '%Y-%m') = :month
            WHERE b.user_id = :user_id
              AND b.period_month = :month
            GROUP BY
                b.budget_id, b.user_id, b.category_id, b.period_month, b.limit_amount, c.name
            ORDER BY c.name
        ";

        try {
            $budgets = $this->withBudgetsTable($pdo, function () use ($pdo, $query, $userId, $month) {
                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    'user_id' => $userId,
                    'month'   => $month,
                ]);
                return $stmt->fetchAll();
            });

            Response::json(['budgets' => $budgets]);
        } catch (PDOException $e) {
            Response::json(['error' => 'Ошибка загрузки бюджетов'], 500);
        }
    }

    public function store()
    {
        $data = Request::data();

        $categoryId = (int)($data['category_id'] ?? 0);
        $month      = trim((string)($data['period_month'] ?? ''));
        $limit      = (float)($data['limit_amount'] ?? 0);

        if ($categoryId <= 0 || $month === '' || $limit <= 0) {
            Response::json(['error' => 'Заполните данные бюджета'], 422);
            return;
        }

        $pdo = Database::connection();
        $userId = Auth::userId();

        $query = "
            INSERT INTO budgets (user_id, category_id, period_month, limit_amount)
            VALUES (:user_id, :category_id, :month, :amount)
            ON DUPLICATE KEY UPDATE limit_amount = :amount
        ";

        try {
            $this->withBudgetsTable($pdo, function () use ($pdo, $query, $userId, $categoryId, $month, $limit) {
                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    'user_id'     => $userId,
                    'category_id' => $categoryId,
                    'month'       => $month,
                    'amount'      => $limit,
                ]);
            });

            Response::json(['success' => true]);
        } catch (PDOException $e) {
            Response::json(['error' => 'Ошибка создания бюджета'], 500);
        }
    }

    public function update($id)
    {
        $data = Request::data();

        $budgetId   = (int)$id;
        $categoryId = (int)($data['category_id'] ?? 0);
        $month      = trim((string)($data['period_month'] ?? ''));
        $limit      = (float)($data['limit_amount'] ?? 0);

        if ($budgetId <= 0 || $categoryId <= 0 || $month === '' || $limit <= 0) {
            Response::json(['error' => 'Заполните данные бюджета'], 422);
            return;
        }

        $pdo = Database::connection();
        $userId = Auth::userId();

        try {
            $this->withBudgetsTable($pdo, function () use ($pdo, $userId, $budgetId, $categoryId, $month, $limit) {
                // Чтобы не ловить 23000, проверим дубликат заранее
                $check = $pdo->prepare("
                    SELECT budget_id
                    FROM budgets
                    WHERE user_id = :user_id
                      AND category_id = :category_id
                      AND period_month = :month
                      AND budget_id <> :id
                    LIMIT 1
                ");
                $check->execute([
                    'user_id'     => $userId,
                    'category_id' => $categoryId,
                    'month'       => $month,
                    'id'          => $budgetId,
                ]);

                if ($check->fetch()) {
                    Response::json(['error' => 'Бюджет на эту категорию и месяц уже существует'], 409);
                    return;
                }

                $stmt = $pdo->prepare("
                    UPDATE budgets
                    SET category_id = :category_id,
                        period_month = :month,
                        limit_amount = :amount
                    WHERE budget_id = :id
                      AND user_id = :user_id
                ");
                $stmt->execute([
                    'category_id' => $categoryId,
                    'month'       => $month,
                    'amount'      => $limit,
                    'id'          => $budgetId,
                    'user_id'     => $userId,
                ]);

                Response::json(['success' => true]);
            });
        } catch (PDOException $e) {
            Response::json(['error' => 'Ошибка обновления бюджета'], 500);
        }
    }

    public function delete($id)
    {
        $budgetId = (int)$id;
        $pdo = Database::connection();
        $userId = Auth::userId();

        try {
            $this->withBudgetsTable($pdo, function () use ($pdo, $budgetId, $userId) {
                $stmt = $pdo->prepare('DELETE FROM budgets WHERE budget_id = :id AND user_id = :user_id');
                $stmt->execute([
                    'id'      => $budgetId,
                    'user_id' => $userId,
                ]);
            });

            Response::json(['success' => true]);
        } catch (PDOException $e) {
            Response::json(['error' => 'Ошибка удаления бюджета'], 500);
        }
    }
}
