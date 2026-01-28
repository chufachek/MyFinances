<?php

namespace App\Controllers;

use App\Services\Auth;
use App\Services\Database;
use App\Services\Request;
use App\Services\Response;
use PDOException;

class BudgetsController
{
    private function ensureBudgetsTable($pdo): void
    {
        $hasUsers = $this->hasTable($pdo, 'users');
        $hasCategories = $this->hasTable($pdo, 'categories');
        $constraints = '';

        if ($hasUsers) {
            $constraints .= ', CONSTRAINT fk_budgets_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE';
        }

        if ($hasCategories) {
            $constraints .= ', CONSTRAINT fk_budgets_category FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE';
        }

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS budgets (
                budget_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                category_id INT NOT NULL,
                period_month VARCHAR(7) NOT NULL,
                limit_amount DECIMAL(12,2) NOT NULL,
                UNIQUE KEY uniq_budgets_user_period (user_id, category_id, period_month),
                KEY idx_budgets_user_period (user_id, period_month)' .
                $constraints .
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private function hasTable($pdo, string $table): bool
    {
        $stmt = $pdo->prepare('SHOW TABLES LIKE :table');
        $stmt->execute(['table' => $table]);
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

    public function index()
    {
        $pdo = Database::connection();
        try {
            $this->ensureBudgetsTable($pdo);
            $hasCategories = $this->hasTable($pdo, 'categories');
            if (!$hasCategories) {
                Response::json(['budgets' => []]);
                return;
            }
            $hasTransactions = $this->hasTable($pdo, 'transactions');
            $month = isset($_GET['month']) ? trim($_GET['month']) : '';
            $monthFilter = $month !== '' ? ' AND b.period_month = :month' : '';
            if ($hasTransactions) {
                $stmt = $pdo->prepare(
                    "SELECT b.*, c.name AS category_name, IFNULL(SUM(t.amount), 0) AS spent
                     FROM budgets b
                     JOIN categories c ON c.category_id = b.category_id
                     LEFT JOIN transactions t
                        ON t.user_id = b.user_id
                        AND t.category_id = b.category_id
                        AND t.tx_type = 'expense'
                        AND DATE_FORMAT(t.tx_date, '%Y-%m') = b.period_month
                     WHERE b.user_id = :user_id{$monthFilter}
                     GROUP BY b.budget_id
                     ORDER BY b.period_month DESC, c.name"
                );
            } else {
                $stmt = $pdo->prepare(
                    "SELECT b.*, c.name AS category_name, 0 AS spent
                     FROM budgets b
                     JOIN categories c ON c.category_id = b.category_id
                     WHERE b.user_id = :user_id{$monthFilter}
                     ORDER BY b.period_month DESC, c.name"
                );
            }
            $params = [
                'user_id' => Auth::userId(),
            ];
            if ($month !== '') {
                $params['month'] = $month;
            }
            $stmt->execute($params);
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
            Response::json(['budgets' => []]);
        }
    }

    public function store()
    {
        $data = Request::data();
        $categoryId = (int)(isset($data['category_id']) ? $data['category_id'] : 0);
        $month = (string)(isset($data['period_month']) ? $data['period_month'] : '');
        $limit = (float)(isset($data['limit_amount']) ? $data['limit_amount'] : 0);

        if ($categoryId <= 0 || $month === '' || $limit <= 0) {
            Response::json(['error' => 'Заполните данные бюджета'], 422);
            return;
        }

        $pdo = Database::connection();
        try {
            $this->ensureBudgetsTable($pdo);
            if (!$this->hasTable($pdo, 'categories')) {
                Response::json(['error' => 'Сначала создайте категории'], 422);
                return;
            }
            $stmt = $pdo->prepare('INSERT INTO budgets (user_id, category_id, period_month, limit_amount) VALUES (:user_id, :category_id, :month, :amount)');
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
        $data = Request::data();
        $categoryId = (int)(isset($data['category_id']) ? $data['category_id'] : 0);
        $month = (string)(isset($data['period_month']) ? $data['period_month'] : '');
        $limit = (float)(isset($data['limit_amount']) ? $data['limit_amount'] : 0);

        if ($categoryId <= 0 || $month === '' || $limit <= 0) {
            Response::json(['error' => 'Заполните данные бюджета'], 422);
            return;
        }

        $pdo = Database::connection();
        try {
            $this->ensureBudgetsTable($pdo);
            if (!$this->hasTable($pdo, 'categories')) {
                Response::json(['error' => 'Сначала создайте категории'], 422);
                return;
            }
            $stmt = $pdo->prepare('UPDATE budgets SET category_id = :category_id, period_month = :month, limit_amount = :amount WHERE budget_id = :id AND user_id = :user_id');
            $stmt->execute([
                'category_id' => $categoryId,
                'month' => $month,
                'amount' => $limit,
                'id' => $id,
                'user_id' => Auth::userId(),
            ]);
            Response::json(['success' => true]);
        } catch (PDOException $exception) {
            Response::json(['error' => 'Ошибка обновления бюджета'], 500);
        }
    }

    public function delete($id)
    {
        $pdo = Database::connection();
        try {
            $this->ensureBudgetsTable($pdo);
            $stmt = $pdo->prepare('DELETE FROM budgets WHERE budget_id = :id AND user_id = :user_id');
            $stmt->execute(['id' => $id, 'user_id' => Auth::userId()]);
            Response::json(['success' => true]);
        } catch (PDOException $exception) {
            Response::json(['error' => 'Ошибка удаления бюджета'], 500);
        }
    }
}
