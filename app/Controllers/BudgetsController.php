<?php

namespace App\Controllers;

use App\Services\Auth;
use App\Services\Database;
use App\Services\Request;
use App\Services\Response;
use PDOException;

class BudgetsController
{
    private function isMissingTableError(PDOException $exception): bool
    {
        return $exception->getCode() === '42S02';
    }

    private function isDuplicateKeyError(PDOException $exception): bool
    {
        return $exception->getCode() === '23000';
    }

    private function ensureBudgetsTable($pdo): void
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

    public function index()
    {
        $month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
        $pdo = Database::connection();
        try {
            $stmt = $pdo->prepare(
                'SELECT b.*, c.name AS category_name,
                    IFNULL((SELECT SUM(t.amount) FROM transactions t WHERE t.user_id = b.user_id AND t.category_id = b.category_id AND t.tx_type = 'expense' AND DATE_FORMAT(t.tx_date, '%Y-%m') = :month), 0) AS spent
                 FROM budgets b
                 JOIN categories c ON c.category_id = b.category_id
                 WHERE b.user_id = :user_id AND b.period_month = :month
                 ORDER BY c.name'
            );
            $stmt->execute([
                'user_id' => Auth::userId(),
                'month' => $month,
            ]);
            Response::json(['budgets' => $stmt->fetchAll()]);
        } catch (PDOException $exception) {
            if ($this->isMissingTableError($exception)) {
                $this->ensureBudgetsTable($pdo);
                $stmt = $pdo->prepare(
                    'SELECT b.*, c.name AS category_name,
                        IFNULL((SELECT SUM(t.amount) FROM transactions t WHERE t.user_id = b.user_id AND t.category_id = b.category_id AND t.tx_type = 'expense' AND DATE_FORMAT(t.tx_date, '%Y-%m') = :month), 0) AS spent
                     FROM budgets b
                     JOIN categories c ON c.category_id = b.category_id
                     WHERE b.user_id = :user_id AND b.period_month = :month
                     ORDER BY c.name'
                );
                $stmt->execute([
                    'user_id' => Auth::userId(),
                    'month' => $month,
                ]);
                Response::json(['budgets' => $stmt->fetchAll()]);
                return;
            }
            Response::json(['error' => 'Ошибка загрузки бюджетов'], 500);
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
            $stmt = $pdo->prepare('INSERT INTO budgets (user_id, category_id, period_month, limit_amount) VALUES (:user_id, :category_id, :month, :amount)');
            $stmt->execute([
                'user_id' => Auth::userId(),
                'category_id' => $categoryId,
                'month' => $month,
                'amount' => $limit,
            ]);
            Response::json(['success' => true]);
        } catch (PDOException $exception) {
            if ($this->isMissingTableError($exception)) {
                $this->ensureBudgetsTable($pdo);
                $stmt = $pdo->prepare('INSERT INTO budgets (user_id, category_id, period_month, limit_amount) VALUES (:user_id, :category_id, :month, :amount)');
                $stmt->execute([
                    'user_id' => Auth::userId(),
                    'category_id' => $categoryId,
                    'month' => $month,
                    'amount' => $limit,
                ]);
                Response::json(['success' => true]);
                return;
            }
            if ($this->isDuplicateKeyError($exception)) {
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

        $pdo = Database::connection();
        try {
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
            if ($this->isMissingTableError($exception)) {
                $this->ensureBudgetsTable($pdo);
                $stmt = $pdo->prepare('UPDATE budgets SET category_id = :category_id, period_month = :month, limit_amount = :amount WHERE budget_id = :id AND user_id = :user_id');
                $stmt->execute([
                    'category_id' => $categoryId,
                    'month' => $month,
                    'amount' => $limit,
                    'id' => $id,
                    'user_id' => Auth::userId(),
                ]);
                Response::json(['success' => true]);
                return;
            }
            Response::json(['error' => 'Ошибка обновления бюджета'], 500);
        }
    }

    public function delete($id)
    {
        $pdo = Database::connection();
        try {
            $stmt = $pdo->prepare('DELETE FROM budgets WHERE budget_id = :id AND user_id = :user_id');
            $stmt->execute(['id' => $id, 'user_id' => Auth::userId()]);
            Response::json(['success' => true]);
        } catch (PDOException $exception) {
            if ($this->isMissingTableError($exception)) {
                $this->ensureBudgetsTable($pdo);
                $stmt = $pdo->prepare('DELETE FROM budgets WHERE budget_id = :id AND user_id = :user_id');
                $stmt->execute(['id' => $id, 'user_id' => Auth::userId()]);
                Response::json(['success' => true]);
                return;
            }
            Response::json(['error' => 'Ошибка удаления бюджета'], 500);
        }
    }
}
