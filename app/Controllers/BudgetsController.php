<?php

namespace App\Controllers;

use App\Services\Auth;
use App\Services\Database;
use App\Services\Request;
use App\Services\Response;

class BudgetsController
{
    public function index()
    {
        $month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT b.*, c.name AS category_name,
                IFNULL((SELECT SUM(t.amount) FROM transactions t WHERE t.user_id = b.user_id AND t.category_id = b.category_id AND t.tx_type = "expense" AND DATE_FORMAT(t.tx_date, "%Y-%m") = :month), 0) AS spent
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
        $stmt = $pdo->prepare('INSERT INTO budgets (user_id, category_id, period_month, limit_amount) VALUES (:user_id, :category_id, :month, :amount)');
        $stmt->execute([
            'user_id' => Auth::userId(),
            'category_id' => $categoryId,
            'month' => $month,
            'amount' => $limit,
        ]);
        Response::json(['success' => true]);
    }

    public function update($id)
    {
        $data = Request::data();
        $categoryId = (int)(isset($data['category_id']) ? $data['category_id'] : 0);
        $month = (string)(isset($data['period_month']) ? $data['period_month'] : '');
        $limit = (float)(isset($data['limit_amount']) ? $data['limit_amount'] : 0);

        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE budgets SET category_id = :category_id, period_month = :month, limit_amount = :amount WHERE budget_id = :id AND user_id = :user_id');
        $stmt->execute([
            'category_id' => $categoryId,
            'month' => $month,
            'amount' => $limit,
            'id' => $id,
            'user_id' => Auth::userId(),
        ]);
        Response::json(['success' => true]);
    }

    public function delete($id)
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('DELETE FROM budgets WHERE budget_id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => Auth::userId()]);
        Response::json(['success' => true]);
    }
}
