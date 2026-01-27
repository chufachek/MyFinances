<section class="page">
    <header class="page__header">
        <div>
            <h1>Операции</h1>
            <p class="text-muted">Доходы и расходы по вашим счетам.</p>
        </div>
        <div class="page__actions">
            <button class="btn btn-primary" id="add-income-btn">+ Доход</button>
            <button class="btn btn-secondary" id="add-expense-btn">+ Расход</button>
            <button class="btn btn-outline" data-action="open-transfer">+ Перевод</button>
        </div>
    </header>

    <div class="panel">
        <div class="panel__header">
            <h3>Фильтры</h3>
            <button class="btn btn-outline btn-sm" id="transactions-reset">Сбросить</button>
        </div>
        <form id="transactions-filter" class="grid grid-6">
            <label class="field">
                <span>Тип</span>
                <select name="type">
                    <option value="">Все</option>
                    <option value="income">Доход</option>
                    <option value="expense">Расход</option>
                </select>
            </label>
            <label class="field">
                <span>Счет</span>
                <select name="accountId" id="filter-account"></select>
            </label>
            <label class="field">
                <span>Категория</span>
                <select name="categoryId" id="filter-category"></select>
            </label>
            <label class="field">
                <span>Дата с</span>
                <input type="date" name="dateFrom">
            </label>
            <label class="field">
                <span>Дата по</span>
                <input type="date" name="dateTo">
            </label>
            <label class="field">
                <span>Поиск</span>
                <input type="search" name="q" placeholder="Магазин или заметка">
            </label>
        </form>
    </div>

    <div class="panel">
        <div class="panel__header">
            <h3>Список операций</h3>
        </div>
        <div id="transactions-table" class="table"></div>
    </div>

    <div class="panel">
        <div class="panel__header">
            <h3>Переводы между счетами</h3>
        </div>
        <div id="transfers-table" class="table"></div>
    </div>

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
                            <div class="field">
                                <span>Сумма</span>
                                <div class="amount-control">
                                    <button class="btn btn-outline btn-sm" type="button" data-amount-delta="-1000">-1000</button>
                                    <button class="btn btn-outline btn-sm" type="button" data-amount-delta="-500">-500</button>
                                    <button class="btn btn-outline btn-sm" type="button" data-amount-delta="-100">-100</button>
                                    <input class="amount-control__input" type="number" step="0.01" name="amount" required>
                                    <button class="btn btn-outline btn-sm" type="button" data-amount-delta="100">+100</button>
                                    <button class="btn btn-outline btn-sm" type="button" data-amount-delta="500">+500</button>
                                    <button class="btn btn-outline btn-sm" type="button" data-amount-delta="1000">+1000</button>
                                </div>
                            </div>
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
                            <div class="field">
                                <span>Сумма</span>
                                <div class="amount-control">
                                    <button class="btn btn-outline btn-sm" type="button" data-amount-delta="-1000">-1000</button>
                                    <button class="btn btn-outline btn-sm" type="button" data-amount-delta="-500">-500</button>
                                    <button class="btn btn-outline btn-sm" type="button" data-amount-delta="-100">-100</button>
                                    <input class="amount-control__input" type="number" step="0.01" name="amount" required>
                                    <button class="btn btn-outline btn-sm" type="button" data-amount-delta="100">+100</button>
                                    <button class="btn btn-outline btn-sm" type="button" data-amount-delta="500">+500</button>
                                    <button class="btn btn-outline btn-sm" type="button" data-amount-delta="1000">+1000</button>
                                </div>
                            </div>
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
