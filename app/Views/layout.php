<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(isset($title) ? $title : 'МоиФинансы', ENT_QUOTES) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(($basePath ?? '') . '/assets/styles/app.css', ENT_QUOTES) ?>">
</head>
<body data-page="<?= htmlspecialchars(isset($page) ? $page : '', ENT_QUOTES) ?>" data-base-path="<?= htmlspecialchars(isset($basePath) ? $basePath : '', ENT_QUOTES) ?>">
    <div class="app-shell">
        <?php if (!isset($showSidebar) || $showSidebar !== false) : ?>
            <?php require __DIR__ . '/partials/menu.php'; ?>
            <div class="sidebar__backdrop" data-action="close-sidebar"></div>
        <?php endif; ?>
        <main class="content">
            <?php if (!isset($showSidebar) || $showSidebar !== false) : ?>
                <div class="mobile-header">
                    <button class="sidebar__toggle" type="button" data-action="toggle-sidebar" aria-label="Открыть меню">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                    <a class="mobile-header__brand" href="<?= htmlspecialchars(($basePath ?? '') . '/dashboard', ENT_QUOTES) ?>">
                        <span class="brand__dot"></span>
                        <span>МоиФинансы</span>
                    </a>
                </div>
            <?php endif; ?>
            <?= $content ?>
        </main>
    </div>
    <?php if (!isset($showSidebar) || $showSidebar !== false) : ?>
        <?php require __DIR__ . '/partials/transaction-modal.php'; ?>
        <?php require __DIR__ . '/partials/transfer-modal.php'; ?>
    <?php endif; ?>
    <div id="toast-container" class="toast-container"></div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" data-chartjs></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script type="module" src="<?= htmlspecialchars(($basePath ?? '') . '/assets/js/app.js', ENT_QUOTES) ?>"></script>
</body>
</html>
