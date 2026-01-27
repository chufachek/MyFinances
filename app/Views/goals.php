<section class="page">
    <header class="page__header">
        <div>
            <h1>Цели</h1>
            <p class="text-muted">Накопления и прогресс по целям.</p>
        </div>
        <button class="btn btn-primary" type="button" id="goals-add">Добавить цель</button>
    </header>

    <div class="panel">
        <div class="panel__header">
            <h3>Ваши цели</h3>
        </div>
        <div id="goals-list" class="cards"></div>
    </div>

    <div class="modal fade" id="goals-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <p class="text-muted mb-1">Цели</p>
                        <h3 class="modal-title fs-5" id="goals-form-title">Новая цель</h3>
                    </div>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <form id="goals-form">
                    <input type="hidden" name="goal_id" />
                    <div class="modal-body">
                        <div class="grid grid-4">
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
                            <label class="field field--full">
                                <span>Статус</span>
                                <select name="status">
                                    <option value="active">Активна</option>
                                    <option value="done">Выполнена</option>
                                    <option value="canceled">Отменена</option>
                                </select>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" type="submit">Сохранить</button>
                        <button class="btn btn-outline" type="button" data-bs-dismiss="modal" data-action="close-modal" id="goals-cancel">Отмена</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
