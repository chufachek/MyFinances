<div class="modal fade" id="transfer-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <p class="text-muted mb-1">Перевод между счетами</p>
                    <h3 class="modal-title fs-5">Новый перевод</h3>
                </div>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <form id="transfer-quick-form">
                <div class="modal-body">
                    <div class="grid grid-4">
                        <label class="field">
                            <span>Со счёта</span>
                            <select name="from_account_id" id="transfer-quick-from" required></select>
                        </label>
                        <label class="field">
                            <span>На счёт</span>
                            <select name="to_account_id" id="transfer-quick-to" required></select>
                        </label>
                        <label class="field">
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
                        </label>
                        <label class="field">
                            <span>Комиссия</span>
                            <input type="number" step="0.01" name="fee" value="0">
                        </label>
                        <label class="field">
                            <span>Дата и время</span>
                            <input type="datetime-local" name="tx_date" id="transfer-quick-date" required>
                        </label>
                        <label class="field field--full">
                            <span>Комментарий</span>
                            <input type="text" name="note" placeholder="Причина перевода">
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit">Перевести</button>
                    <button class="btn btn-outline" type="button" data-bs-dismiss="modal" data-action="close-modal">
                        Отмена
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
