<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Auth;
use App\Services\Database;
use App\Services\Response;

class ReportsController
{
    public function summary(): void
    {
        $userId = Auth::userId();
        $dateFrom = $_GET['dateFrom'] ?? date('Y-m-01');
        $dateTo = $_GET['dateTo'] ?? date('Y-m-t');

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT
                SUM(CASE WHEN tx_type = "income" THEN amount ELSE 0 END) AS total_income,
                SUM(CASE WHEN tx_type = "expense" THEN amount ELSE 0 END) AS total_expense
             FROM transactions
             WHERE user_id = :user_id AND tx_date BETWEEN :date_from AND :date_to'
        );
        $stmt->execute([
            'user_id' => $userId,
            'date_from' => $dateFrom . ' 00:00:00',
            'date_to' => $dateTo . ' 23:59:59',
        ]);
        $totals = $stmt->fetch() ?: ['total_income' => 0, 'total_expense' => 0];

        $balanceStmt = $pdo->prepare(
            'SELECT SUM(
                a.initial_balance
                + IFNULL((SELECT SUM(t.amount) FROM transactions t WHERE t.account_id = a.account_id AND t.user_id = a.user_id AND t.tx_type = "income"), 0)
                - IFNULL((SELECT SUM(t.amount) FROM transactions t WHERE t.account_id = a.account_id AND t.user_id = a.user_id AND t.tx_type = "expense"), 0)
                + IFNULL((SELECT SUM(tr.amount) FROM transfers tr WHERE tr.to_account_id = a.account_id AND tr.user_id = a.user_id), 0)
                - IFNULL((SELECT SUM(tr.amount + tr.fee) FROM transfers tr WHERE tr.from_account_id = a.account_id AND tr.user_id = a.user_id), 0)
            ) AS total_balance
             FROM accounts a
             WHERE a.user_id = :user_id'
        );
        $balanceStmt->execute(['user_id' => $userId]);
        $balance = $balanceStmt->fetch();

        Response::json([
            'income' => (float) ($totals['total_income'] ?? 0),
            'expense' => (float) ($totals['total_expense'] ?? 0),
            'net' => (float) ($totals['total_income'] ?? 0) - (float) ($totals['total_expense'] ?? 0),
            'balance' => (float) ($balance['total_balance'] ?? 0),
        ]);
    }

    public function expenseByCategory(): void
    {
        $month = $_GET['month'] ?? date('Y-m');
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT c.name, SUM(t.amount) AS total
             FROM transactions t
             JOIN categories c ON c.category_id = t.category_id
             WHERE t.user_id = :user_id AND t.tx_type = "expense" AND DATE_FORMAT(t.tx_date, "%Y-%m") = :month
             GROUP BY c.name
             ORDER BY total DESC'
        );
        $stmt->execute([
            'user_id' => Auth::userId(),
            'month' => $month,
        ]);
        Response::json(['items' => $stmt->fetchAll()]);
    }

    public function dynamics(): void
    {
        $groupBy = $_GET['groupBy'] ?? 'day';
        $type = $_GET['type'] ?? 'both';
        $dateFrom = $_GET['dateFrom'] ?? date('Y-m-01');
        $dateTo = $_GET['dateTo'] ?? date('Y-m-t');

        $format = $groupBy === 'month' ? '%Y-%m' : '%Y-%m-%d';

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT DATE_FORMAT(tx_date, :format) AS period,
                SUM(CASE WHEN tx_type = "income" THEN amount ELSE 0 END) AS income,
                SUM(CASE WHEN tx_type = "expense" THEN amount ELSE 0 END) AS expense
             FROM transactions
             WHERE user_id = :user_id AND tx_date BETWEEN :date_from AND :date_to
             GROUP BY period
             ORDER BY period'
        );
        $stmt->execute([
            'format' => $format,
            'user_id' => Auth::userId(),
            'date_from' => $dateFrom . ' 00:00:00',
            'date_to' => $dateTo . ' 23:59:59',
        ]);
        $rows = $stmt->fetchAll();

        $labels = array_column($rows, 'period');
        $income = array_map(static fn ($row) => (float) $row['income'], $rows);
        $expense = array_map(static fn ($row) => (float) $row['expense'], $rows);

        $data = [
            'labels' => $labels,
            'income' => $income,
            'expense' => $expense,
        ];

        if ($type === 'income') {
            $data['expense'] = [];
        }
        if ($type === 'expense') {
            $data['income'] = [];
        }

        Response::json($data);
    }
}
