<section class="page">
    <header class="page__header">
        <div>
            <h1>Цели</h1>
            <p class="text-muted">Накопления и прогресс по целям.</p>
        </div>
    </header>

    <div class="panel">
        <div class="panel__header">
            <h3>Ваши цели</h3>
        </div>
        <div id="goals-list" class="cards"></div>
    </div>

    <div class="panel">
        <div class="panel__header">
            <h3>Добавить / редактировать</h3>
            <span class="text-muted" id="goals-form-title">Новая цель</span>
        </div>
        <form id="goals-form" class="grid grid-4">
            <input type="hidden" name="goal_id" />
            <label class="field">
                <span>Название</span>
                <input type="text" name="name" required>
            </label>
            <label class="field">
                <span>Целевая сумма</span>
                <input type="number" step="0.01" name="target_amount" required>
            </label>
            <label class="field">
                <span>Текущая сумма</span>
                <input type="number" step="0.01" name="current_amount" value="0">
            </label>
            <label class="field">
                <span>Дедлайн</span>
                <input type="date" name="due_date">
            </label>
            <label class="field">
                <span>Статус</span>
                <select name="status">
                    <option value="active">Активна</option>
                    <option value="done">Выполнена</option>
                    <option value="canceled">Отменена</option>
                </select>
            </label>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Сохранить</button>
                <button class="btn btn-outline" type="button" id="goals-cancel">Очистить</button>
            </div>
        </form>
    </div>
</section>
