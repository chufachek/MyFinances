<section class="page">
    <header class="page__header">
        <div>
            <h1>Категории</h1>
            <p class="text-muted">Упорядочьте доходы и расходы по категориям.</p>
        </div>
    </header>

    <div class="panel">
        <div class="panel__header">
            <h3>Список категорий</h3>
            <select id="categories-filter" class="select-inline">
                <option value="">Все</option>
                <option value="income">Доход</option>
                <option value="expense">Расход</option>
            </select>
        </div>
        <div id="categories-table" class="table"></div>
    </div>

    <div class="panel">
        <div class="panel__header">
            <h3>Добавить / редактировать</h3>
            <span class="text-muted" id="categories-form-title">Новая категория</span>
        </div>
        <form id="categories-form" class="grid grid-4">
            <input type="hidden" name="category_id" />
            <label class="field">
                <span>Название</span>
                <input type="text" name="name" required>
            </label>
            <label class="field">
                <span>Тип</span>
                <select name="category_type">
                    <option value="income">Доход</option>
                    <option value="expense">Расход</option>
                </select>
            </label>
            <label class="field">
                <span>Активна</span>
                <select name="is_active">
                    <option value="1">Да</option>
                    <option value="0">Нет</option>
                </select>
            </label>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Сохранить</button>
                <button class="btn btn-outline" type="button" id="categories-cancel">Очистить</button>
            </div>
        </form>
    </div>
</section>
