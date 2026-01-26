<?php

require __DIR__ . '/../app/bootstrap.php';

use App\Controllers\AccountsController;
use App\Controllers\AuthController;
use App\Controllers\BudgetsController;
use App\Controllers\CategoriesController;
use App\Controllers\GoalsController;
use App\Controllers\ReportsController;
use App\Controllers\TransactionsController;
use App\Controllers\TransfersController;
use App\Services\Auth;
use App\Services\Response;

$router = new \Bramus\Router\Router();

$scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/';
$basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
if ($basePath !== '') {
    $router->setBasePath($basePath);
}

$render = static function ($view, array $data = array()) {
    $viewFile = __DIR__ . '/../app/Views/' . $view . '.php';
    if (!file_exists($viewFile)) {
        http_response_code(404);
        echo 'View not found';
        return;
    }

    extract($data, EXTR_SKIP);

    ob_start();
    require $viewFile;
    $content = ob_get_clean();

    require __DIR__ . '/../app/Views/layout.php';
};

$router->before('GET|POST|PUT|DELETE', '/api/.*', static function () {
    $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
    $path = parse_url($requestUri, PHP_URL_PATH);
    if ($path === null || $path === false) {
        $path = '';
    }
    if (in_array($path, ['/api/auth/login', '/api/auth/register'], true)) {
        return;
    }
    if (!Auth::check()) {
        Response::json(['error' => 'Unauthorized'], 401);
        exit;
    }
});

$authRequired = static function () {
    if (!Auth::check()) {
        header('Location: /login');
        exit;
    }
};

$router->get('/', static function () use ($render) {
    header('Location: /dashboard');
});

$router->get('/login', static function () use ($render) {
    $render('login', ['title' => 'Вход', 'showSidebar' => false]);
});
$router->get('/register', static function () use ($render) {
    $render('register', ['title' => 'Регистрация', 'showSidebar' => false]);
});

$router->get('/dashboard', static function () use ($render, $authRequired) {
    $authRequired();
    $render('dashboard', ['title' => 'Дашборд', 'page' => 'dashboard']);
});
$router->get('/transactions', static function () use ($render, $authRequired) {
    $authRequired();
    $render('transactions', ['title' => 'Операции', 'page' => 'transactions']);
});
$router->get('/accounts', static function () use ($render, $authRequired) {
    $authRequired();
    $render('accounts', ['title' => 'Счета', 'page' => 'accounts']);
});
$router->get('/categories', static function () use ($render, $authRequired) {
    $authRequired();
    $render('categories', ['title' => 'Категории', 'page' => 'categories']);
});
$router->get('/budgets', static function () use ($render, $authRequired) {
    $authRequired();
    $render('budgets', ['title' => 'Бюджеты', 'page' => 'budgets']);
});
$router->get('/goals', static function () use ($render, $authRequired) {
    $authRequired();
    $render('goals', ['title' => 'Цели', 'page' => 'goals']);
});
$router->get('/reports', static function () use ($render, $authRequired) {
    $authRequired();
    $render('reports', ['title' => 'Отчёты', 'page' => 'reports']);
});

$router->post('/api/auth/register', [new AuthController(), 'register']);
$router->post('/api/auth/login', [new AuthController(), 'login']);
$router->post('/api/auth/logout', [new AuthController(), 'logout']);
$router->get('/api/auth/me', [new AuthController(), 'me']);

$router->get('/api/accounts', [new AccountsController(), 'index']);
$router->post('/api/accounts', [new AccountsController(), 'store']);
$router->put('/api/accounts/{id}', [new AccountsController(), 'update']);
$router->delete('/api/accounts/{id}', [new AccountsController(), 'delete']);

$router->get('/api/categories', [new CategoriesController(), 'index']);
$router->post('/api/categories', [new CategoriesController(), 'store']);
$router->put('/api/categories/{id}', [new CategoriesController(), 'update']);
$router->delete('/api/categories/{id}', [new CategoriesController(), 'delete']);

$router->get('/api/transactions', [new TransactionsController(), 'index']);
$router->post('/api/transactions', [new TransactionsController(), 'store']);
$router->put('/api/transactions/{id}', [new TransactionsController(), 'update']);
$router->delete('/api/transactions/{id}', [new TransactionsController(), 'delete']);

$router->get('/api/transfers', [new TransfersController(), 'index']);
$router->post('/api/transfers', [new TransfersController(), 'store']);
$router->delete('/api/transfers/{id}', [new TransfersController(), 'delete']);

$router->get('/api/budgets', [new BudgetsController(), 'index']);
$router->post('/api/budgets', [new BudgetsController(), 'store']);
$router->put('/api/budgets/{id}', [new BudgetsController(), 'update']);
$router->delete('/api/budgets/{id}', [new BudgetsController(), 'delete']);

$router->get('/api/goals', [new GoalsController(), 'index']);
$router->post('/api/goals', [new GoalsController(), 'store']);
$router->put('/api/goals/{id}', [new GoalsController(), 'update']);
$router->delete('/api/goals/{id}', [new GoalsController(), 'delete']);

$router->get('/api/reports/summary', [new ReportsController(), 'summary']);
$router->get('/api/reports/expense-by-category', [new ReportsController(), 'expenseByCategory']);
$router->get('/api/reports/dynamics', [new ReportsController(), 'dynamics']);

$router->set404(static function () use ($render) {
    http_response_code(404);
    $render('dashboard', ['title' => 'Страница не найдена', 'page' => 'dashboard']);
});

$router->run();
