<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'МоиФинансы', ENT_QUOTES) ?></title>
    <link rel="stylesheet" href="/assets/styles/app.css">
</head>
<body data-page="<?= htmlspecialchars($page ?? '', ENT_QUOTES) ?>">
    <div class="app-shell">
        <?php if (!isset($showSidebar) || $showSidebar !== false) : ?>
            <?php require __DIR__ . '/partials/menu.php'; ?>
        <?php endif; ?>
        <main class="content">
            <?= $content ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script type="module" src="/assets/js/app.js"></script>
</body>
</html>
