<?php

require __DIR__ . '/../../app/bootstrap.php';

use App\Services\Auth;
use App\Services\Database;
use PDOException;

header('Content-Type: application/json; charset=utf-8');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$pdo = Database::connection();

$respond = static function (array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload);
    exit;
};

$hasTable = static function ($pdo, string $table): bool {
    $stmt = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table));
    return (bool) $stmt->fetchColumn();
};

$ensureBudgetsTable = static function ($pdo) use ($hasTable): void {
    $hasUsers = $hasTable($pdo, 'users');
    $hasCategories = $hasTable($pdo, 'categories');
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
};

$buildStatus = static function (float $limit, float $spent): array {
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
};

$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
$input = file_get_contents('php://input') ?: '';
$data = json_decode($input, true);
if (!is_array($data)) {
    $data = $_POST;
}
$budgetId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

try {
    $ensureBudgetsTable($pdo);
    if (!$hasTable($pdo, 'categories')) {
        if ($method === 'GET') {
            $respond(['budgets' => []]);
        }
        $respond(['error' => 'Сначала создайте категории'], 422);
    }

    if ($method === 'GET') {
        $hasTransactions = $hasTable($pdo, 'transactions');
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
        $params = ['user_id' => Auth::userId()];
        $stmt->execute($params);
        $budgets = $stmt->fetchAll();
        foreach ($budgets as &$budget) {
            $limit = (float) $budget['limit_amount'];
            $spent = (float) $budget['spent'];
            $status = $buildStatus($limit, $spent);
            $budget['status_label'] = $status['label'];
            $budget['status_variant'] = $status['variant'];
        }
        unset($budget);
        $respond(['budgets' => $budgets]);
    }

    if ($method === 'POST') {
        $categoryId = (int) (isset($data['category_id']) ? $data['category_id'] : 0);
        $month = (string) (isset($data['period_month']) ? $data['period_month'] : '');
        $limit = (float) (isset($data['limit_amount']) ? $data['limit_amount'] : 0);

        if ($categoryId <= 0 || $month === '' || $limit <= 0) {
            $respond(['error' => 'Заполните данные бюджета'], 422);
        }

        $stmt = $pdo->prepare('INSERT INTO budgets (user_id, category_id, period_month, limit_amount) VALUES (:user_id, :category_id, :month, :amount)');
        $stmt->execute([
            'user_id' => Auth::userId(),
            'category_id' => $categoryId,
            'month' => $month,
            'amount' => $limit,
        ]);
        $respond(['success' => true]);
    }

    if ($method === 'PUT') {
        if ($budgetId <= 0) {
            $respond(['error' => 'Некорректный бюджет'], 422);
        }
        $categoryId = (int) (isset($data['category_id']) ? $data['category_id'] : 0);
        $month = (string) (isset($data['period_month']) ? $data['period_month'] : '');
        $limit = (float) (isset($data['limit_amount']) ? $data['limit_amount'] : 0);

        if ($categoryId <= 0 || $month === '' || $limit <= 0) {
            $respond(['error' => 'Заполните данные бюджета'], 422);
        }

        $stmt = $pdo->prepare('UPDATE budgets SET category_id = :category_id, period_month = :month, limit_amount = :amount WHERE budget_id = :id AND user_id = :user_id');
        $stmt->execute([
            'category_id' => $categoryId,
            'month' => $month,
            'amount' => $limit,
            'id' => $budgetId,
            'user_id' => Auth::userId(),
        ]);
        $respond(['success' => true]);
    }

    if ($method === 'DELETE') {
        if ($budgetId <= 0) {
            $respond(['error' => 'Некорректный бюджет'], 422);
        }
        $stmt = $pdo->prepare('DELETE FROM budgets WHERE budget_id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $budgetId, 'user_id' => Auth::userId()]);
        $respond(['success' => true]);
    }

    $respond(['error' => 'Method not allowed'], 405);
} catch (PDOException $exception) {
    $code = $exception->getCode();
    if ($method === 'POST' && $code === '23000') {
        $respond(['error' => 'Бюджет на эту категорию и месяц уже существует'], 409);
    }
    $message = 'Ошибка загрузки бюджетов';
    if ($method === 'POST') {
        $message = 'Ошибка создания бюджета';
    } elseif ($method === 'PUT') {
        $message = 'Ошибка обновления бюджета';
    } elseif ($method === 'DELETE') {
        $message = 'Ошибка удаления бюджета';
    }
    $respond(['error' => $message], 500);
}
