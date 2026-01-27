<section class="page">
    <header class="page__header">
        <div>
            <h1>Счета</h1>
            <p class="text-muted">Управление счетами и расчетными балансами.</p>
        </div>
        <div class="page__actions">
            <button class="btn btn-primary" id="accounts-add">Добавить счёт</button>
        </div>
    </header>

    <div class="panel">
        <div class="panel__header">
            <h3>Список счетов</h3>
        </div>
        <div id="accounts-table" class="table"></div>
    </div>

    <div class="panel">
        <div class="panel__header">
            <h3>Добавить / редактировать</h3>
            <span class="text-muted" id="accounts-form-title">Новый счёт</span>
        </div>
        <form id="accounts-form" class="grid grid-4">
            <input type="hidden" name="account_id" />
            <label class="field">
                <span>Название</span>
                <input type="text" name="name" required>
            </label>
            <label class="field">
                <span>Тип</span>
                <select name="account_type">
                    <option value="cash">Наличные</option>
                    <option value="card">Карта</option>
                    <option value="bank">Банк</option>
                    <option value="other">Другое</option>
                </select>
            </label>
            <label class="field">
                <span>Валюта</span>
                <input type="text" name="currency_code" value="RUB" maxlength="3">
            </label>
            <label class="field">
                <span>Начальный баланс</span>
                <input type="number" step="0.01" name="initial_balance" value="0">
            </label>
            <label class="field">
                <span>Активен</span>
                <select name="is_active">
                    <option value="1">Да</option>
                    <option value="0">Нет</option>
                </select>
            </label>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Сохранить</button>
                <button class="btn btn-outline" type="button" id="accounts-cancel">Очистить</button>
            </div>
        </form>
    </div>
</section>
