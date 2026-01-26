<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Auth;
use App\Services\Database;
use App\Services\Request;
use App\Services\Response;

class TransactionsController
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
        if (!empty($_GET['amountFrom'])) {
            $filters[] = 't.amount >= :amount_from';
            $params['amount_from'] = (float) $_GET['amountFrom'];
        }
        if (!empty($_GET['amountTo'])) {
            $filters[] = 't.amount <= :amount_to';
            $params['amount_to'] = (float) $_GET['amountTo'];
        }
        if (!empty($_GET['accountId'])) {
            $filters[] = 't.account_id = :account_id';
            $params['account_id'] = (int) $_GET['accountId'];
        }
        if (!empty($_GET['categoryId'])) {
            $filters[] = 't.category_id = :category_id';
            $params['category_id'] = (int) $_GET['categoryId'];
        }
        if (!empty($_GET['q'])) {
            $filters[] = '(t.note LIKE :q OR m.name LIKE :q)';
            $params['q'] = '%' . $_GET['q'] . '%';
        }
        if (!empty($_GET['type'])) {
            $filters[] = 't.tx_type = :tx_type';
            $params['tx_type'] = $_GET['type'];
        }

        $limit = !empty($_GET['limit']) ? (int) $_GET['limit'] : 100;

        $where = $filters ? (' AND ' . implode(' AND ', $filters)) : '';

        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT t.*, a.name AS account_name, c.name AS category_name, m.name AS merchant_name
             FROM transactions t
             LEFT JOIN accounts a ON a.account_id = t.account_id
             LEFT JOIN categories c ON c.category_id = t.category_id
             LEFT JOIN merchants m ON m.merchant_id = t.merchant_id
             WHERE t.user_id = :user_id' . $where . '
             ORDER BY t.tx_date DESC
             LIMIT ' . $limit
        );
        $stmt->execute($params);
        Response::json(['transactions' => $stmt->fetchAll()]);
    }

    public function store(): void
    {
        $data = Request::data();
        $payload = $this->normalize($data);
        if (isset($payload['error'])) {
            Response::json(['error' => $payload['error']], 422);
            return;
        }

        $pdo = Database::connection();
        $merchantId = $this->resolveMerchantId($pdo, $payload['merchant_name']);

        $stmt = $pdo->prepare(
            'INSERT INTO transactions (user_id, account_id, category_id, merchant_id, tx_type, amount, tx_date, note)
             VALUES (:user_id, :account_id, :category_id, :merchant_id, :tx_type, :amount, :tx_date, :note)'
        );
        $stmt->execute([
            'user_id' => Auth::userId(),
            'account_id' => $payload['account_id'],
            'category_id' => $payload['category_id'],
            'merchant_id' => $merchantId,
            'tx_type' => $payload['tx_type'],
            'amount' => $payload['amount'],
            'tx_date' => $payload['tx_date'],
            'note' => $payload['note'],
        ]);

        Response::json(['success' => true]);
    }

    public function update(string $id): void
    {
        $data = Request::data();
        $payload = $this->normalize($data);
        if (isset($payload['error'])) {
            Response::json(['error' => $payload['error']], 422);
            return;
        }

        $pdo = Database::connection();
        $merchantId = $this->resolveMerchantId($pdo, $payload['merchant_name']);

        $stmt = $pdo->prepare(
            'UPDATE transactions SET account_id = :account_id, category_id = :category_id, merchant_id = :merchant_id,
                tx_type = :tx_type, amount = :amount, tx_date = :tx_date, note = :note
             WHERE transaction_id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            'account_id' => $payload['account_id'],
            'category_id' => $payload['category_id'],
            'merchant_id' => $merchantId,
            'tx_type' => $payload['tx_type'],
            'amount' => $payload['amount'],
            'tx_date' => $payload['tx_date'],
            'note' => $payload['note'],
            'id' => $id,
            'user_id' => Auth::userId(),
        ]);

        Response::json(['success' => true]);
    }

    public function delete(string $id): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('DELETE FROM transactions WHERE transaction_id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => Auth::userId()]);
        Response::json(['success' => true]);
    }

    private function normalize(array $data): array
    {
        $accountId = (int)($data['account_id'] ?? 0);
        $categoryId = isset($data['category_id']) && $data['category_id'] !== '' ? (int) $data['category_id'] : null;
        $txType = (string)($data['tx_type'] ?? 'expense');
        $amount = (float)($data['amount'] ?? 0);
        $txDate = (string)($data['tx_date'] ?? '');
        $txDate = str_replace('T', ' ', $txDate);
        $note = trim((string)($data['note'] ?? '')) ?: null;
        $merchantName = trim((string)($data['merchant'] ?? '')) ?: null;

        if ($accountId <= 0 || $amount <= 0 || $txDate === '') {
            return ['error' => 'Заполните счёт, сумму и дату операции'];
        }

        if (!in_array($txType, ['income', 'expense'], true)) {
            return ['error' => 'Некорректный тип операции'];
        }

        if ($categoryId) {
            $pdo = Database::connection();
            $stmt = $pdo->prepare('SELECT category_type FROM categories WHERE category_id = :id AND user_id = :user_id');
            $stmt->execute(['id' => $categoryId, 'user_id' => Auth::userId()]);
            $category = $stmt->fetch();
            if ($category && $category['category_type'] !== $txType) {
                return ['error' => 'Категория не совпадает с типом операции'];
            }
        }

        return [
            'account_id' => $accountId,
            'category_id' => $categoryId,
            'tx_type' => $txType,
            'amount' => $amount,
            'tx_date' => $txDate,
            'note' => $note,
            'merchant_name' => $merchantName,
        ];
    }

    private function resolveMerchantId($pdo, ?string $name): ?int
    {
        if (!$name) {
            return null;
        }
        $stmt = $pdo->prepare('SELECT merchant_id FROM merchants WHERE user_id = :user_id AND name = :name');
        $stmt->execute(['user_id' => Auth::userId(), 'name' => $name]);
        $existing = $stmt->fetch();
        if ($existing) {
            return (int) $existing['merchant_id'];
        }
        $insert = $pdo->prepare('INSERT INTO merchants (user_id, name) VALUES (:user_id, :name)');
        $insert->execute(['user_id' => Auth::userId(), 'name' => $name]);
        return (int) $pdo->lastInsertId();
    }
}
