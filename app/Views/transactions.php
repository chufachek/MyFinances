<section class="page">
    <header class="page__header">
        <div>
            <h1>Операции</h1>
            <p class="text-muted">Доходы и расходы по вашим счетам.</p>
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
            <button class="btn btn-primary" id="transactions-add" data-action="open-quick" data-type="expense">
                Добавить операцию
            </button>
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
</section>
