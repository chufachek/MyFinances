<?php

namespace App\Controllers;

use App\Services\Auth;
use App\Services\Database;
use App\Services\Request;
use App\Services\Response;
use PDOException;

class BudgetsController
{
    /**
     * Нормализует значение месяца из тела запроса.
     * Разрешаем "YYYY-MM" и "YYYY-MM-DD" (обрежем до "YYYY-MM").
     * Возвращает '' если значение невалидное/пустое.
     */
    private function normalizeMonthValue($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/^\d{4}-\d{2}$/', $value)) {
            return $value;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return substr($value, 0, 7);
        }

        // на случай ISO-строк и т.п. главное чтобы начиналось с YYYY-MM
        if (preg_match('/^\d{4}-\d{2}/', $value)) {
            return substr($value, 0, 7);
        }

        return '';
    }

    /**
     * Месяц для index(): если не передали или передали мусор — текущий.
     */
    private function monthFromQuery()
    {
        $raw = isset($_GET['month']) ? $_GET['month'] : '';
        $normalized = $this->normalizeMonthValue($raw);
        return $normalized !== '' ? $normalized : date('Y-m');
    }

    private function isDuplicateKeyError(PDOException $e)
    {
        // SQLSTATE[23000]: Integrity constraint violation (duplicate key)
        return $e->getCode() === '23000';
    }

    public function index()
    {
        $month = $this->monthFromQuery();
        $pdo = Database::connection();

        $query = "
            SELECT
                b.budget_id,
                b.user_id,
                b.category_id,
                b.period_month,
                b.limit_amount,
                c.name AS category_name,
                IFNULL((
                    SELECT SUM(t.amount)
                    FROM transactions t
                    WHERE t.user_id = b.user_id
                      AND t.category_id = b.category_id
                      AND t.tx_type = 'expense'
                      AND DATE_FORMAT(t.tx_date, '%Y-%m') = b.period_month
                ), 0) AS spent
            FROM budgets b
            JOIN categories c ON c.category_id = b.category_id
            WHERE b.user_id = :user_id
              AND b.period_month = :month
            ORDER BY c.name
        ";

        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'user_id' => Auth::userId(),
                'month'   => $month,
            ]);
            Response::json(['budgets' => $stmt->fetchAll()]);
        } catch (PDOException $e) {
            Response::json(['error' => 'Ошибка загрузки бюджетов'], 500);
        }
    }

    public function store()
    {
        $data = Request::data();

        $categoryId = (int)(isset($data['category_id']) ? $data['category_id'] : 0);
        $monthRaw   = isset($data['period_month']) ? $data['period_month'] : '';
        $month      = $this->normalizeMonthValue($monthRaw);
        $limit      = (float)(isset($data['limit_amount']) ? $data['limit_amount'] : 0);

        if ($categoryId <= 0 || $month === '' || $limit <= 0) {
            Response::json(['error' => 'Заполните данные бюджета'], 422);
            return;
        }

        $pdo = Database::connection();

        $query = "
            INSERT INTO budgets (user_id, category_id, period_month, limit_amount)
            VALUES (:user_id, :category_id, :month, :amount)
            ON DUPLICATE KEY UPDATE limit_amount = VALUES(limit_amount)
        ";

        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'user_id'     => Auth::userId(),
                'category_id' => $categoryId,
                'month'       => $month,
                'amount'      => $limit,
            ]);
            Response::json(['success' => true]);
        } catch (PDOException $e) {
            Response::json(['error' => 'Ошибка создания бюджета'], 500);
        }
    }

    public function update($id)
    {
        $data = Request::data();

        $budgetId   = (int)$id;
        $categoryId = (int)(isset($data['category_id']) ? $data['category_id'] : 0);
        $monthRaw   = isset($data['period_month']) ? $data['period_month'] : '';
        $month      = $this->normalizeMonthValue($monthRaw);
        $limit      = (float)(isset($data['limit_amount']) ? $data['limit_amount'] : 0);

        if ($budgetId <= 0 || $categoryId <= 0 || $month === '' || $limit <= 0) {
            Response::json(['error' => 'Заполните данные бюджета'], 422);
            return;
        }

        $pdo = Database::connection();

        $query = "
            UPDATE budgets
            SET category_id = :category_id,
                period_month = :month,
                limit_amount = :amount
            WHERE budget_id = :id
              AND user_id = :user_id
        ";

        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'category_id' => $categoryId,
                'month'       => $month,
                'amount'      => $limit,
                'id'          => $budgetId,
                'user_id'     => Auth::userId(),
            ]);
            Response::json(['success' => true]);
        } catch (PDOException $e) {
            if ($this->isDuplicateKeyError($e)) {
                Response::json(['error' => 'Бюджет на эту категорию и месяц уже существует'], 409);
                return;
            }
            Response::json(['error' => 'Ошибка обновления бюджета'], 500);
        }
    }

    public function delete($id)
    {
        $budgetId = (int)$id;
        $pdo = Database::connection();

        try {
            $stmt = $pdo->prepare('DELETE FROM budgets WHERE budget_id = :id AND user_id = :user_id');
            $stmt->execute([
                'id'      => $budgetId,
                'user_id' => Auth::userId(),
            ]);
            Response::json(['success' => true]);
        } catch (PDOException $e) {
            Response::json(['error' => 'Ошибка удаления бюджета'], 500);
        }
    }
}
