<div class="modal fade" id="quick-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <p class="text-muted mb-1">Операции</p>
                    <h3 class="modal-title fs-5" id="quick-modal-title">Новая операция</h3>
                </div>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <form id="quick-form">
                <input type="hidden" name="transaction_id" id="quick-transaction-id" />
                <div class="modal-body">
                    <div class="grid grid-4">
                        <label class="field">
                            <span>Тип</span>
                            <select name="tx_type" id="quick-type" required>
                                <option value="income">Доход</option>
                                <option value="expense">Расход</option>
                            </select>
                        </label>
                        <label class="field">
                            <span>Счёт</span>
                            <select name="account_id" id="quick-account" required></select>
                        </label>
                        <label class="field">
                            <span>Категория</span>
                            <select name="category_id" id="quick-category"></select>
                        </label>
                        <label class="field">
                            <span>Сумма</span>
                            <input type="number" step="0.01" name="amount" required>
                        </label>
                        <label class="field">
                            <span>Дата и время</span>
                            <input type="datetime-local" name="tx_date" id="quick-date" required>
                        </label>
                        <label class="field field--full">
                            <span>Комментарий</span>
                            <input type="text" name="note" placeholder="Например: зарплата или покупки">
                        </label>
                        <label class="field field--full">
                            <span>Место / продавец</span>
                            <input type="text" name="merchant" placeholder="Кафе, магазин, сервис">
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
