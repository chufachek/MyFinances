<section class="page">
    <header class="page__header">
        <div>
            <h1>Бюджеты</h1>
            <p class="text-muted">Лимиты расходов по категориям на месяц.</p>
        </div>
    </header>

    <div class="panel">
        <div class="panel__header">
            <h3>Бюджетный месяц</h3>
            <input type="month" id="budgets-month" class="select-inline">
        </div>
        <div id="budgets-table" class="table"></div>
    </div>

    <div class="panel">
        <div class="panel__header">
            <h3>Добавить / редактировать</h3>
            <span class="text-muted" id="budgets-form-title">Новый бюджет</span>
        </div>
        <form id="budgets-form" class="grid grid-4">
            <input type="hidden" name="budget_id" />
            <label class="field">
                <span>Категория расхода</span>
                <select name="category_id" id="budgets-category"></select>
            </label>
            <label class="field">
                <span>Месяц</span>
                <input type="month" name="period_month" required>
            </label>
            <label class="field">
                <span>Лимит</span>
                <input type="number" step="0.01" name="limit_amount" required>
            </label>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Сохранить</button>
                <button class="btn btn-outline" type="button" id="budgets-cancel">Очистить</button>
            </div>
        </form>
    </div>
</section>
