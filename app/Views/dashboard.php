<section class="page">
    <header class="page__header">
        <div>
            <h1>Финансовая панель</h1>
            <p>Сводка ключевых метрик и свежих операций.</p>
        </div>
        <button class="button button--primary">Добавить операцию</button>
    </header>

    <div class="grid grid--stats">
        <article class="card">
            <h3>Баланс</h3>
            <p class="metric">₽ 268 400</p>
            <span class="badge badge--success">+12% к прошлому месяцу</span>
        </article>
        <article class="card">
            <h3>Расходы</h3>
            <p class="metric">₽ 84 120</p>
            <span class="badge badge--warning">-4% к прошлому месяцу</span>
        </article>
        <article class="card">
            <h3>Сбережения</h3>
            <p class="metric">₽ 42 500</p>
            <span class="badge">Цель: 60 000</span>
        </article>
    </div>

    <div class="grid grid--split">
        <article class="card">
            <h3>Свежие транзакции</h3>
            <div class="table" data-widget="transactions"></div>
        </article>
        <article class="card">
            <h3>Структура расходов</h3>
            <div class="chart" data-widget="categories">
                <div class="chart__legend">
                    <span>Дом и ЖКХ</span><span>35%</span>
                    <span>Еда</span><span>25%</span>
                    <span>Транспорт</span><span>18%</span>
                    <span>Отдых</span><span>12%</span>
                </div>
            </div>
        </article>
    </div>
</section>
