<?php

declare(strict_types=1);

use Bramus\Router\Router;

session_start();

require __DIR__ . '/../vendor/autoload.php';

$router = new Router();

$sendJson = function (array $payload, int $status = 200): void {
    header('Content-Type: application/json', true, $status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
};

$hasAuth = function (): bool {
    if (!empty($_SESSION['user_id'])) {
        return true;
    }

    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

    if (!$authHeader) {
        return false;
    }

    if (!preg_match('/Bearer\s+(?<token>.+)/i', $authHeader, $matches)) {
        return false;
    }

    return $matches['token'] === 'demo-token';
};

$requireAuthForPages = function () use ($hasAuth): void {
    if ($hasAuth()) {
        return;
    }

    header('Location: /login', true, 302);
    exit();
};

$requireAuthForApi = function () use ($hasAuth, $sendJson): void {
    if ($hasAuth()) {
        return;
    }

    $sendJson(['error' => 'Unauthorized'], 401);
    exit();
};

$router->before('GET', '/(dashboard|transactions|accounts|categories|budgets|goals|reports)(/.*)?', $requireAuthForPages);
$router->before('GET|POST|PUT|PATCH|DELETE', '/api/(accounts|categories|transactions|transfers|budgets|goals|reports)(/.*)?', $requireAuthForApi);

$router->get('/login', static function (): void {
    echo 'Login page';
});

$router->get('/register', static function (): void {
    echo 'Register page';
});

$router->get('/dashboard', static function (): void {
    echo 'Dashboard page';
});

$router->get('/transactions', static function (): void {
    echo 'Transactions page';
});

$router->get('/accounts', static function (): void {
    echo 'Accounts page';
});

$router->get('/categories', static function (): void {
    echo 'Categories page';
});

$router->get('/budgets', static function (): void {
    echo 'Budgets page';
});

$router->get('/goals', static function (): void {
    echo 'Goals page';
});

$router->get('/reports', static function (): void {
    echo 'Reports page';
});

$router->post('/api/auth/login', function () use ($sendJson): void {
    $sendJson(['token' => 'demo-token']);
});

$router->post('/api/auth/register', function () use ($sendJson): void {
    $sendJson(['status' => 'registered']);
});

$router->post('/api/auth/logout', function () use ($sendJson): void {
    $sendJson(['status' => 'logged_out']);
});

$router->get('/api/accounts', function () use ($sendJson): void {
    $sendJson(['accounts' => []]);
});

$router->post('/api/accounts', function () use ($sendJson): void {
    $sendJson(['status' => 'created']);
});

$router->get('/api/categories', function () use ($sendJson): void {
    $sendJson(['categories' => []]);
});

$router->post('/api/categories', function () use ($sendJson): void {
    $sendJson(['status' => 'created']);
});

$router->get('/api/transactions', function () use ($sendJson): void {
    $sendJson(['transactions' => []]);
});

$router->post('/api/transactions', function () use ($sendJson): void {
    $sendJson(['status' => 'created']);
});

$router->post('/api/transfers', function () use ($sendJson): void {
    $sendJson(['status' => 'created']);
});

$router->get('/api/budgets', function () use ($sendJson): void {
    $sendJson(['budgets' => []]);
});

$router->post('/api/budgets', function () use ($sendJson): void {
    $sendJson(['status' => 'created']);
});

$router->get('/api/goals', function () use ($sendJson): void {
    $sendJson(['goals' => []]);
});

$router->post('/api/goals', function () use ($sendJson): void {
    $sendJson(['status' => 'created']);
});

$router->get('/api/reports', function () use ($sendJson): void {
    $sendJson(['reports' => []]);
});

$router->set404(function () use ($sendJson): void {
    if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
        $sendJson(['error' => 'Not found'], 404);
        return;
    }

    header('HTTP/1.1 404 Not Found');
    echo 'Page not found';
});

$router->run();
