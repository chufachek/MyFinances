<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Auth;
use App\Services\Database;
use App\Services\Request;
use App\Services\Response;

class TransfersController
{
    public function index(): void
    {
        $userId = Auth::userId();
        $params = ['user_id' => $userId];
        $filters = [];

        if (!empty($_GET['dateFrom'])) {
            $filters[] = 't.tx_date >= :date_from';
            $params['date_from'] = $_GET['dateFrom'] . ' 00:00:00';
        }
        if (!empty($_GET['dateTo'])) {
            $filters[] = 't.tx_date <= :date_to';
            $params['date_to'] = $_GET['dateTo'] . ' 23:59:59';
        }

        $where = $filters ? (' AND ' . implode(' AND ', $filters)) : '';

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT t.*, fa.name AS from_account, ta.name AS to_account
             FROM transfers t
             LEFT JOIN accounts fa ON fa.account_id = t.from_account_id
             LEFT JOIN accounts ta ON ta.account_id = t.to_account_id
             WHERE t.user_id = :user_id' . $where . '
             ORDER BY t.tx_date DESC'
        );
        $stmt->execute($params);
        Response::json(['transfers' => $stmt->fetchAll()]);
    }

    public function store(): void
    {
        $data = Request::data();
        $from = (int)($data['from_account_id'] ?? 0);
        $to = (int)($data['to_account_id'] ?? 0);
        $amount = (float)($data['amount'] ?? 0);
        $fee = (float)($data['fee'] ?? 0);
        $txDate = (string)($data['tx_date'] ?? '');
        $txDate = str_replace('T', ' ', $txDate);
        $note = trim((string)($data['note'] ?? '')) ?: null;

        if ($from <= 0 || $to <= 0 || $amount <= 0 || $txDate === '') {
            Response::json(['error' => 'Заполните все поля перевода'], 422);
            return;
        }
        if ($from === $to) {
            Response::json(['error' => 'Счета не должны совпадать'], 422);
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO transfers (user_id, from_account_id, to_account_id, amount, fee, tx_date, note)
             VALUES (:user_id, :from_id, :to_id, :amount, :fee, :tx_date, :note)'
        );
        $stmt->execute([
            'user_id' => Auth::userId(),
            'from_id' => $from,
            'to_id' => $to,
            'amount' => $amount,
            'fee' => $fee,
            'tx_date' => $txDate,
            'note' => $note,
        ]);

        Response::json(['success' => true]);
    }

    public function delete(string $id): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('DELETE FROM transfers WHERE transfer_id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => Auth::userId()]);
        Response::json(['success' => true]);
    }
}
