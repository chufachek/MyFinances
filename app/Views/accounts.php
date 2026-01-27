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
</section>

<div class="modal fade" id="accounts-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <p class="text-muted mb-1">Счета</p>
                    <h3 class="modal-title fs-5" id="accounts-form-title">Новый счёт</h3>
                </div>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <form id="accounts-form">
                <input type="hidden" name="account_id" />
                <div class="modal-body">
                    <div class="grid grid-4">
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
                            <select name="currency_code">
                                <option value="RUB">Российский рубль (RUB)</option>
                                <option value="USD">Доллар США (USD)</option>
                                <option value="EUR">Евро (EUR)</option>
                                <option value="GBP">Фунт стерлингов (GBP)</option>
                            </select>
                        </label>
                        <label class="field">
                            <span>Начальный баланс</span>
                            <input type="number" step="1" name="initial_balance" value="0">
                        </label>
                        <label class="field">
                            <span>Активен</span>
                            <select name="is_active">
                                <option value="1">Да</option>
                                <option value="0">Нет</option>
                            </select>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit">Сохранить</button>
                    <button class="btn btn-outline" type="button" data-bs-dismiss="modal" data-action="close-modal" id="accounts-cancel">
                        Отмена
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="accounts-delete-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <p class="text-muted mb-1">Счета</p>
                    <h3 class="modal-title fs-5">Удаление счёта</h3>
                </div>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <form id="accounts-delete-form">
                <input type="hidden" name="account_id" />
                <div class="modal-body">
                    <p>Счёт <strong id="accounts-delete-name"></strong> будет скрыт.</p>
                    <p class="text-muted">Баланс: <strong id="accounts-delete-balance"></strong></p>
                    <div class="field" id="accounts-delete-transfer">
                        <span>Перевести остаток на счёт</span>
                        <select name="target_account_id" id="accounts-delete-target"></select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit">Удалить</button>
                    <button class="btn btn-outline" type="button" data-bs-dismiss="modal" data-action="close-modal">Отмена</button>
                </div>
            </form>
        </div>
    </div>
</div>
