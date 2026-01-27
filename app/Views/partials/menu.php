<nav class="sidebar" data-sidebar>
    <div class="brand">
        <a class="brand__link" href="<?= htmlspecialchars(($basePath ?? '') . '/dashboard', ENT_QUOTES) ?>">
            <span class="brand__dot"></span>
            <span class="brand__title">МоиФинансы</span>
        </a>
    </div>
    <div class="sidebar__section">
        <p class="sidebar__label">Основное</p>
        <a class="sidebar__link" href="<?= htmlspecialchars(($basePath ?? '') . '/dashboard', ENT_QUOTES) ?>" data-page="dashboard">Сводка</a>
        <a class="sidebar__link" href="<?= htmlspecialchars(($basePath ?? '') . '/transactions', ENT_QUOTES) ?>" data-page="transactions">Операции</a>
        <a class="sidebar__link" href="<?= htmlspecialchars(($basePath ?? '') . '/accounts', ENT_QUOTES) ?>" data-page="accounts">Счета</a>
        <a class="sidebar__link" href="<?= htmlspecialchars(($basePath ?? '') . '/categories', ENT_QUOTES) ?>" data-page="categories">Категории</a>
    </div>
    <div class="sidebar__section">
        <p class="sidebar__label">Планирование</p>
        <a class="sidebar__link" href="<?= htmlspecialchars(($basePath ?? '') . '/budgets', ENT_QUOTES) ?>" data-page="budgets">Бюджеты</a>
        <a class="sidebar__link" href="<?= htmlspecialchars(($basePath ?? '') . '/goals', ENT_QUOTES) ?>" data-page="goals">Цели</a>
        <a class="sidebar__link" href="<?= htmlspecialchars(($basePath ?? '') . '/reports', ENT_QUOTES) ?>" data-page="reports">Отчёты</a>
    </div>
    <div class="sidebar__section sidebar__section--footer">
        <button class="sidebar__link sidebar__link--logout" data-action="logout">Выйти</button>
    </div>
</nav>
