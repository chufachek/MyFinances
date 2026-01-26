<?php

declare(strict_types=1);

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require $autoload;
}

$router = new \Bramus\Router\Router();

$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if ($basePath !== '') {
    $router->setBasePath($basePath);
}

$render = static function (string $view, array $data = []): void {
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

$router->get('/', static fn () => $render('dashboard', ['title' => 'Dashboard']));
$router->get('/login', static fn () => $render('login', ['title' => 'Login']));
$router->get('/register', static fn () => $render('register', ['title' => 'Register']));
$router->get('/dashboard', static fn () => $render('dashboard', ['title' => 'Dashboard']));
$router->get('/transactions', static fn () => $render('transactions', ['title' => 'Transactions']));
$router->get('/accounts', static fn () => $render('accounts', ['title' => 'Accounts']));
$router->get('/categories', static fn () => $render('categories', ['title' => 'Categories']));
$router->get('/budgets', static fn () => $render('budgets', ['title' => 'Budgets']));
$router->get('/goals', static fn () => $render('goals', ['title' => 'Goals']));
$router->get('/reports', static fn () => $render('reports', ['title' => 'Reports']));

$router->set404(static function () use ($render): void {
    http_response_code(404);
    $render('dashboard', ['title' => 'Page Not Found']);
});

$router->run();
