<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчеты</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 24px;
            color: #1f2a44;
        }
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: flex-end;
            margin-bottom: 24px;
        }
        .filters label {
            display: flex;
            flex-direction: column;
            font-size: 14px;
            gap: 6px;
        }
        .filters input {
            padding: 6px 10px;
            font-size: 14px;
        }
        .filters button {
            padding: 8px 16px;
            background: #2f6fed;
            border: none;
            border-radius: 6px;
            color: #fff;
            cursor: pointer;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        .summary-card {
            background: #f6f8fb;
            padding: 16px;
            border-radius: 10px;
        }
        .charts {
            display: grid;
            gap: 32px;
        }
        canvas {
            max-width: 100%;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
            padding: 16px;
        }
    </style>
</head>
<body>
    <h1>Отчеты</h1>
    <div
        id="reports"
        data-summary-url="/reports/summary"
        data-expense-url="/reports/expense-by-category"
        data-dynamics-url="/reports/dynamics"
    ></div>

    <section class="filters">
        <label>
            Дата начала
            <input type="date" id="startDate">
        </label>
        <label>
            Дата окончания
            <input type="date" id="endDate">
        </label>
        <button type="button" id="applyFilters">Применить</button>
    </section>

    <section class="summary">
        <div class="summary-card">
            <div>Доходы</div>
            <strong id="totalIncome">0 ₽</strong>
        </div>
        <div class="summary-card">
            <div>Расходы</div>
            <strong id="totalExpense">0 ₽</strong>
        </div>
        <div class="summary-card">
            <div>Баланс</div>
            <strong id="balance">0 ₽</strong>
        </div>
    </section>

    <section class="charts">
        <div>
            <h2>Расходы по категориям</h2>
            <canvas id="expenseChart" height="220"></canvas>
        </div>
        <div>
            <h2>Динамика</h2>
            <canvas id="dynamicsChart" height="220"></canvas>
        </div>
    </section>

    <script src="/assets/js/reports.js"></script>
</body>
</html>
