<section class="page">
    <header class="page__header">
        <div>
            <h1>Дашборд</h1>
            <p class="text-muted">Сводка по финансам и быстрые действия.</p>
        </div>
        <div class="page__actions">
            <button class="btn btn-primary" data-action="open-quick" data-type="income">+ Доход</button>
            <button class="btn btn-secondary" data-action="open-quick" data-type="expense">+ Расход</button>
            <button class="btn btn-outline" data-action="open-transfer">+ Перевод</button>
        </div>
    </header>

    <div class="cards">
        <article class="card">
            <p class="card__label">Общий баланс</p>
            <h2 id="summary-balance">—</h2>
            <span class="card__trend" id="summary-balance-note">по всем счетам</span>
        </article>
        <article class="card">
            <p class="card__label">Доходы за месяц</p>
            <h2 id="summary-income">—</h2>
            <span class="card__trend positive">+ поступления</span>
        </article>
        <article class="card">
            <p class="card__label">Расходы за месяц</p>
            <h2 id="summary-expense">—</h2>
            <span class="card__trend negative">- траты</span>
        </article>
        <article class="card">
            <p class="card__label">Разница</p>
            <h2 id="summary-net">—</h2>
            <span class="card__trend" id="summary-net-note">итог периода</span>
        </article>
    </div>

    <section class="panel">
        <div class="panel__header">
            <h3>Последние операции</h3>
            <a class="link" href="/transactions">Открыть все</a>
        </div>
        <div id="dashboard-transactions" class="table"></div>
    </section>
</section>
