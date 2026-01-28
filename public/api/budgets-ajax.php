<?php

require __DIR__ . '/../../app/bootstrap.php';

use App\Services\Auth;

header('Content-Type: application/json; charset=utf-8');

$respond = static function (array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

if (!Auth::check()) {
    $respond(['error' => 'Unauthorized'], 401);
}

$action = isset($_POST['action']) ? (string) $_POST['action'] : '';
$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    $respond(['error' => 'Unauthorized'], 401);
}

$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$mysqli) {
    $respond(['error' => 'Ошибка подключения к базе'], 500);
}
mysqli_set_charset($mysqli, DB_CHARSET);

$hasTable = static function ($mysqli, string $table): bool {
    $tableSafe = mysqli_real_escape_string($mysqli, $table);
    $result = mysqli_query($mysqli, "SHOW TABLES LIKE '{$tableSafe}'");
    if (!$result) {
        return false;
    }
    $exists = mysqli_num_rows($result) > 0;
    mysqli_free_result($result);
    return $exists;
};

$fetchBudgets = static function ($mysqli, int $userId, ?string $month = null): array {
    $sql = "SELECT b.budget_id,
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
            WHERE b.user_id = ?";
    $types = 'i';
    $params = [$userId];
    if ($month !== null) {
        $sql .= ' AND b.period_month = ?';
        $types .= 's';
        $params[] = $month;
    }
    $sql .= ' ORDER BY b.period_month DESC, c.name';

    $stmt = mysqli_prepare($mysqli, $sql);
    if (!$stmt) {
        return [];
    }
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        mysqli_free_result($result);
    }
    mysqli_stmt_close($stmt);
    return $rows;
};

if (!$hasTable($mysqli, 'budgets') || !$hasTable($mysqli, 'categories')) {
    $respond(['budgets' => []]);
}

switch ($action) {
    case 'getNow':
        $month = isset($_POST['month']) && $_POST['month'] !== '' ? (string) $_POST['month'] : date('Y-m');
        $budgets = $fetchBudgets($mysqli, $userId, $month);
        $respond(['budgets' => $budgets]);
        break;
    case 'getAll':
        $budgets = $fetchBudgets($mysqli, $userId);
        $respond(['budgets' => $budgets]);
        break;
    case 'create':
        $categoryId = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
        $month = isset($_POST['period_month']) ? (string) $_POST['period_month'] : '';
        $limit = isset($_POST['limit_amount']) ? (float) $_POST['limit_amount'] : 0;

        if ($categoryId <= 0 || $month === '' || $limit <= 0) {
            $respond(['error' => 'Заполните данные бюджета'], 422);
        }

        $stmt = mysqli_prepare(
            $mysqli,
            'INSERT INTO budgets (user_id, category_id, period_month, limit_amount) VALUES (?, ?, ?, ?)'
        );
        if (!$stmt) {
            $respond(['error' => 'Ошибка создания бюджета'], 500);
        }
        mysqli_stmt_bind_param($stmt, 'iisd', $userId, $categoryId, $month, $limit);
        $ok = mysqli_stmt_execute($stmt);
        $errorCode = mysqli_errno($mysqli);
        mysqli_stmt_close($stmt);
        if (!$ok) {
            if ($errorCode === 1062) {
                $respond(['error' => 'Бюджет на эту категорию и месяц уже существует'], 409);
            }
            $respond(['error' => 'Ошибка создания бюджета'], 500);
        }
        $respond(['success' => true]);
        break;
    case 'update':
        $budgetId = isset($_POST['budget_id']) ? (int) $_POST['budget_id'] : 0;
        $categoryId = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
        $month = isset($_POST['period_month']) ? (string) $_POST['period_month'] : '';
        $limit = isset($_POST['limit_amount']) ? (float) $_POST['limit_amount'] : 0;

        if ($budgetId <= 0 || $categoryId <= 0 || $month === '' || $limit <= 0) {
            $respond(['error' => 'Заполните данные бюджета'], 422);
        }

        $stmt = mysqli_prepare(
            $mysqli,
            'UPDATE budgets SET category_id = ?, period_month = ?, limit_amount = ? WHERE budget_id = ? AND user_id = ?'
        );
        if (!$stmt) {
            $respond(['error' => 'Ошибка обновления бюджета'], 500);
        }
        mysqli_stmt_bind_param($stmt, 'isdii', $categoryId, $month, $limit, $budgetId, $userId);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        if (!$ok) {
            $respond(['error' => 'Ошибка обновления бюджета'], 500);
        }
        $respond(['success' => true]);
        break;
    case 'delete':
        $budgetId = isset($_POST['budget_id']) ? (int) $_POST['budget_id'] : 0;
        if ($budgetId <= 0) {
            $respond(['error' => 'Некорректный бюджет'], 422);
        }
        $stmt = mysqli_prepare($mysqli, 'DELETE FROM budgets WHERE budget_id = ? AND user_id = ?');
        if (!$stmt) {
            $respond(['error' => 'Ошибка удаления бюджета'], 500);
        }
        mysqli_stmt_bind_param($stmt, 'ii', $budgetId, $userId);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        if (!$ok) {
            $respond(['error' => 'Ошибка удаления бюджета'], 500);
        }
        $respond(['success' => true]);
        break;
    default:
        $respond(['error' => 'Method not allowed'], 405);
}
