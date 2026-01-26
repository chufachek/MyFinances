<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'MyFinances', ENT_QUOTES) ?></title>
    <link rel="stylesheet" href="/assets/styles/app.css">
</head>
<body>
    <div class="app-shell">
        <?php require __DIR__ . '/partials/menu.php'; ?>
        <main class="content">
            <?= $content ?>
        </main>
    </div>

    <script type="module" src="/assets/js/app.js"></script>
</body>
</html>
