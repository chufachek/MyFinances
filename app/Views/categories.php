<section class="page">
    <header class="page__header">
        <div>
            <h1>Категории</h1>
            <p class="text-muted">Упорядочьте доходы и расходы по категориям.</p>
        </div>
        <div class="page__actions">
            <button class="btn btn-primary" id="categories-add">Добавить категорию</button>
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
</section>

<div class="modal fade" id="categories-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <p class="text-muted mb-1">Категории</p>
                    <h3 class="modal-title fs-5" id="categories-form-title">Новая категория</h3>
                </div>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <form id="categories-form">
                <input type="hidden" name="category_id" />
                <div class="modal-body">
                    <div class="grid grid-4">
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
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit">Сохранить</button>
                    <button class="btn btn-outline" type="button" id="categories-cancel" data-bs-dismiss="modal" data-action="close-modal">
                        Отмена
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
