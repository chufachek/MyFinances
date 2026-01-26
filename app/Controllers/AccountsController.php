<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Auth;
use App\Services\Database;
use App\Services\Request;
use App\Services\Response;

class AccountsController
{
    public function index(): void
    {
        $pdo = Database::connection();
        $userId = Auth::userId();
        $stmt = $pdo->prepare(
            'SELECT a.*, 
                a.initial_balance
                + IFNULL((SELECT SUM(t.amount) FROM transactions t WHERE t.account_id = a.account_id AND t.user_id = a.user_id AND t.tx_type = "income"), 0)
                - IFNULL((SELECT SUM(t.amount) FROM transactions t WHERE t.account_id = a.account_id AND t.user_id = a.user_id AND t.tx_type = "expense"), 0)
                + IFNULL((SELECT SUM(tr.amount) FROM transfers tr WHERE tr.to_account_id = a.account_id AND tr.user_id = a.user_id), 0)
                - IFNULL((SELECT SUM(tr.amount + tr.fee) FROM transfers tr WHERE tr.from_account_id = a.account_id AND tr.user_id = a.user_id), 0)
                AS balance
             FROM accounts a
             WHERE a.user_id = :user_id
             ORDER BY a.created_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);
        Response::json(['accounts' => $stmt->fetchAll()]);
    }

    public function store(): void
    {
        $data = Request::data();
        $name = trim((string)($data['name'] ?? ''));
        $type = (string)($data['account_type'] ?? 'card');
        $currency = strtoupper((string)($data['currency_code'] ?? 'RUB'));
        $balance = (float)($data['initial_balance'] ?? 0);
        $isActive = isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1;

        if ($name === '') {
            Response::json(['error' => 'Название счёта обязательно'], 422);
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO accounts (user_id, name, account_type, currency_code, initial_balance, is_active) VALUES (:user_id, :name, :type, :currency, :balance, :active)');
        $stmt->execute([
            'user_id' => Auth::userId(),
            'name' => $name,
            'type' => $type,
            'currency' => $currency,
            'balance' => $balance,
            'active' => $isActive,
        ]);

        Response::json(['success' => true]);
    }

    public function update(string $id): void
    {
        $data = Request::data();
        $name = trim((string)($data['name'] ?? ''));
        $type = (string)($data['account_type'] ?? 'card');
        $currency = strtoupper((string)($data['currency_code'] ?? 'RUB'));
        $balance = (float)($data['initial_balance'] ?? 0);
        $isActive = isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1;

        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE accounts SET name = :name, account_type = :type, currency_code = :currency, initial_balance = :balance, is_active = :active WHERE account_id = :id AND user_id = :user_id');
        $stmt->execute([
            'name' => $name,
            'type' => $type,
            'currency' => $currency,
            'balance' => $balance,
            'active' => $isActive,
            'id' => $id,
            'user_id' => Auth::userId(),
        ]);

        Response::json(['success' => true]);
    }

    public function delete(string $id): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE accounts SET is_active = 0 WHERE account_id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => Auth::userId()]);
        Response::json(['success' => true]);
    }
}
