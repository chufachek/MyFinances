<section class="page">
    <header class="page__header">
        <div>
            <h1>Отчёты</h1>
            <p class="text-muted">Аналитика по категориям и динамика по дням.</p>
        </div>
    </header>

    <div class="panel">
        <div class="panel__header">
            <h3>Период</h3>
        </div>
        <form id="reports-filter" class="grid grid-4">
            <label class="field">
                <span>Месяц</span>
                <input type="month" name="month" id="reports-month">
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
                <span>Группировка</span>
                <select name="groupBy">
                    <option value="day">По дням</option>
                    <option value="month">По месяцам</option>
                </select>
            </label>
        </form>
    </div>

    <div class="reports-grid">
        <div class="panel">
            <div class="panel__header">
                <h3>Расходы по категориям</h3>
            </div>
            <canvas id="report-pie" height="220"></canvas>
            <div id="report-category-table" class="table"></div>
        </div>
        <div class="panel">
            <div class="panel__header">
                <h3>Динамика доходов и расходов</h3>
            </div>
            <canvas id="report-line" height="220"></canvas>
        </div>
    </div>
</section>
