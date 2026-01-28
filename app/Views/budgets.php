<section class="page">
    <header class="page__header">
        <div>
            <h1>Бюджеты</h1>
            <p class="text-muted">Лимиты расходов по категориям на месяц.</p>
        </div>
        <button class="btn btn-primary" type="button" id="budgets-add" data-bs-toggle="modal" data-bs-target="#budgets-modal">Добавить бюджет</button>
    </header>

    <div class="panel">
        <div class="panel__header">
            <h3>Текущий месяц</h3>
        </div>
        <div id="budgets-table" class="table"></div>
    </div>

    <div class="panel">
        <div class="panel__header">
            <h3>Другие месяцы</h3>
        </div>
        <div id="budgets-table-other" class="table"></div>
    </div>

    <div class="modal fade" id="budgets-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <p class="text-muted mb-1">Бюджеты</p>
                        <h3 class="modal-title fs-5" id="budgets-form-title">Новый бюджет</h3>
                    </div>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <form id="budgets-form">
                    <input type="hidden" name="budget_id" />
                    <div class="modal-body">
                        <div class="grid grid-4">
                            <label class="field">
                                <span>Категория расхода</span>
                                <select name="category_id" id="budgets-category" required></select>
                            </label>
                            <label class="field">
                                <span>Месяц</span>
                                <input type="month" name="period_month" required>
                            </label>
                            <label class="field field--full">
                                <span>Лимит</span>
                                <input type="number" step="0.01" name="limit_amount" required>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" type="submit">Сохранить</button>
                        <button class="btn btn-outline" type="button" data-bs-dismiss="modal" data-action="close-modal" id="budgets-cancel">Отмена</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
