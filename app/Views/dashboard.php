<section class="page">
    <header class="page__header">
        <div>
            <h1>Дашборд</h1>
            <p class="text-muted">Сводка по финансам и быстрые действия.</p>
        </div>
        <div class="page__actions">
            <button class="btn btn-primary" id="add-income-btn">+ Доход</button>
            <button class="btn btn-secondary" id="add-expense-btn">+ Расход</button>
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

    <section class="dashboard-grid">
        <article class="panel chart-card">
            <div class="panel__header">
                <h3>Динамика доходов и расходов</h3>
                <span class="text-muted">за текущий месяц</span>
            </div>
            <canvas id="dashboard-line" height="240"></canvas>
        </article>
        <article class="panel chart-card">
            <div class="panel__header">
                <h3>Расходы по категориям</h3>
                <span class="text-muted">структура месяца</span>
            </div>
            <div class="chart-split">
                <canvas id="dashboard-category" height="220"></canvas>
                <div id="dashboard-category-list" class="stat-list"></div>
            </div>
        </article>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h3>Последние операции</h3>
            <a class="link" href="/transactions">Открыть все</a>
        </div>
        <div id="dashboard-transactions" class="table"></div>
    </section>

    <div class="modal fade" id="income-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <p class="text-muted mb-1">Операции</p>
                        <h3 class="modal-title fs-5">Добавить доход</h3>
                    </div>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <form class="transaction-form" data-tx-type="income">
                    <input type="hidden" name="tx_type" value="income">
                    <div class="modal-body">
                        <div class="grid grid-4">
                            <label class="field">
                                <span>Тип</span>
                                <input type="text" value="Доход" readonly>
                            </label>
                            <label class="field">
                                <span>Счёт</span>
                                <select name="account_id" required></select>
                            </label>
                            <label class="field">
                                <span>Категория</span>
                                <select name="category_id"></select>
                            </label>
                            <label class="field">
                                <span>Сумма</span>
                                <div class="amount-control">
                                    <input class="amount-control__input" type="number" step="0.01" name="amount" required>
                                    <div class="amount-control__buttons">
                                        <button class="btn btn-outline btn-sm" type="button" data-amount-delta="100">+100</button>
                                        <button class="btn btn-outline btn-sm" type="button" data-amount-delta="-100">-100</button>
                                        <button class="btn btn-outline btn-sm" type="button" data-amount-delta="1000">+1000</button>
                                        <button class="btn btn-outline btn-sm" type="button" data-amount-delta="-1000">-1000</button>
                                    </div>
                                </div>
                            </label>
                            <label class="field">
                                <span>Дата</span>
                                <input type="date" name="tx_date" required>
                            </label>
                            <label class="field field--full">
                                <span>Комментарий</span>
                                <input type="text" name="note" placeholder="Например: зарплата">
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" type="submit">Сохранить</button>
                        <button class="btn btn-outline" type="button" data-bs-dismiss="modal" data-action="close-modal">
                            Отмена
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="expense-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <p class="text-muted mb-1">Операции</p>
                        <h3 class="modal-title fs-5">Добавить расход</h3>
                    </div>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <form class="transaction-form" data-tx-type="expense">
                    <input type="hidden" name="tx_type" value="expense">
                    <div class="modal-body">
                        <div class="grid grid-4">
                            <label class="field">
                                <span>Тип</span>
                                <input type="text" value="Расход" readonly>
                            </label>
                            <label class="field">
                                <span>Счёт</span>
                                <select name="account_id" required></select>
                            </label>
                            <label class="field">
                                <span>Категория</span>
                                <select name="category_id"></select>
                            </label>
                            <label class="field">
                                <span>Сумма</span>
                                <div class="amount-control">
                                    <input class="amount-control__input" type="number" step="0.01" name="amount" required>
                                    <div class="amount-control__buttons">
                                        <button class="btn btn-outline btn-sm" type="button" data-amount-delta="100">+100</button>
                                        <button class="btn btn-outline btn-sm" type="button" data-amount-delta="-100">-100</button>
                                        <button class="btn btn-outline btn-sm" type="button" data-amount-delta="1000">+1000</button>
                                        <button class="btn btn-outline btn-sm" type="button" data-amount-delta="-1000">-1000</button>
                                    </div>
                                </div>
                            </label>
                            <label class="field">
                                <span>Дата</span>
                                <input type="date" name="tx_date" required>
                            </label>
                            <label class="field field--full">
                                <span>Комментарий</span>
                                <input type="text" name="note" placeholder="Например: покупки">
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" type="submit">Сохранить</button>
                        <button class="btn btn-outline" type="button" data-bs-dismiss="modal" data-action="close-modal">
                            Отмена
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
