<section class="page">
    <header class="page__header">
        <div>
            <h1>Операции</h1>
            <p class="text-muted">Доходы и расходы по вашим счетам.</p>
        </div>
        <div class="page__actions">
            <button class="btn btn-primary" id="add-income-btn">+ Доход</button>
            <button class="btn btn-secondary" id="add-expense-btn">+ Расход</button>
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
        <form id="transfer-form" class="grid grid-4">
            <label class="field">
                <span>Счёт списания</span>
                <select name="from_account_id" id="transfer-from"></select>
            </label>
            <label class="field">
                <span>Счёт получения</span>
                <select name="to_account_id" id="transfer-to"></select>
            </label>
            <label class="field">
                <span>Сумма</span>
                <input type="number" step="0.01" name="amount" required>
            </label>
            <label class="field">
                <span>Комиссия</span>
                <input type="number" step="0.01" name="fee" value="0">
            </label>
            <label class="field">
                <span>Дата</span>
                <input type="datetime-local" name="tx_date" required>
            </label>
            <label class="field field--full">
                <span>Комментарий</span>
                <input type="text" name="note">
            </label>
            <div class="form-actions">
                <button class="btn btn-secondary" type="submit">Создать перевод</button>
            </div>
        </form>
        <div id="transfers-table" class="table"></div>
    </div>

    <div class="modal" id="income-modal" aria-hidden="true">
        <div class="modal__backdrop" data-action="close-modal"></div>
        <div class="modal__dialog">
            <div class="modal__header">
                <div>
                    <p class="text-muted">Операции</p>
                    <h3>Добавить доход</h3>
                </div>
                <button class="btn btn-outline btn-sm" type="button" data-action="close-modal">Закрыть</button>
            </div>
            <form class="grid grid-4 transaction-form" data-tx-type="income">
                <input type="hidden" name="tx_type" value="income">
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
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Сохранить</button>
                    <button class="btn btn-outline" type="button" data-action="close-modal">Отмена</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="expense-modal" aria-hidden="true">
        <div class="modal__backdrop" data-action="close-modal"></div>
        <div class="modal__dialog">
            <div class="modal__header">
                <div>
                    <p class="text-muted">Операции</p>
                    <h3>Добавить расход</h3>
                </div>
                <button class="btn btn-outline btn-sm" type="button" data-action="close-modal">Закрыть</button>
            </div>
            <form class="grid grid-4 transaction-form" data-tx-type="expense">
                <input type="hidden" name="tx_type" value="expense">
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
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Сохранить</button>
                    <button class="btn btn-outline" type="button" data-action="close-modal">Отмена</button>
                </div>
            </form>
        </div>
    </div>
</section>
