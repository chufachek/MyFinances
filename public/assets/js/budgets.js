(() => {
    const page = document.body?.dataset?.page ?? '';
    const basePath = document.body?.dataset?.basePath ?? '';
    const withBasePath = (path) => {
        if (!path.startsWith('/')) {
            return path;
        }
        if (!basePath) {
            return path;
        }
        if (path.startsWith(`${basePath}/`)) {
            return path;
        }
        return `${basePath}${path}`;
    };

    const budgetEndpoint = withBasePath('/api/budgets-ajax.php');
    const byId = (id) => document.getElementById(id);

    const formatCurrency = (value) => {
        const amount = Number(value ?? 0);
        return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(amount);
    };

    const getBudgetStatus = (limitValue, spentValue) => {
        const limit = Number(limitValue) || 0;
        const spent = Number(spentValue) || 0;
        const percent = limit > 0 ? (spent / limit) * 100 : 0;
        if (percent >= 100) {
            return { label: 'Превышено', variant: 'danger', percent, limit, spent };
        }
        if (percent >= 85) {
            return { label: 'Почти лимит', variant: 'warning', percent, limit, spent };
        }
        return { label: 'В пределах', variant: 'success', percent, limit, spent };
    };

    const emptyMessages = [
        'Данных нет.',
        'Пока пусто — добавьте расходы и бюджеты.',
        'Данные появятся, когда вы добавите бюджеты.',
    ];

    const getRandomEmptyMessage = () => emptyMessages[Math.floor(Math.random() * emptyMessages.length)];

    const createIconButton = ({ icon, label, variant = 'outline' }) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = `btn btn-${variant} btn-sm icon-btn`;
        btn.setAttribute('aria-label', label);
        btn.innerHTML = `<span aria-hidden="true">${icon}</span>`;
        return btn;
    };

    const renderTable = (container, headers, rows) => {
        const table = document.createElement('div');
        table.className = 'table';

        const headerRow = document.createElement('div');
        headerRow.className = 'table__row table__row--header';
        headers.forEach((header) => {
            const cell = document.createElement('div');
            cell.textContent = header;
            headerRow.appendChild(cell);
        });
        table.appendChild(headerRow);

        if (rows.length === 0) {
            const rowEl = document.createElement('div');
            rowEl.className = 'table__row table__row--empty';
            const cellEl = document.createElement('div');
            cellEl.textContent = getRandomEmptyMessage();
            rowEl.appendChild(cellEl);
            table.appendChild(rowEl);
        } else {
            rows.forEach((row) => {
                const rowEl = document.createElement('div');
                rowEl.className = 'table__row';
                row.forEach((cell) => {
                    const cellEl = document.createElement('div');
                    if (cell instanceof HTMLElement) {
                        cellEl.appendChild(cell);
                    } else {
                        cellEl.innerHTML = cell;
                    }
                    rowEl.appendChild(cellEl);
                });
                table.appendChild(rowEl);
            });
        }

        container.innerHTML = '';
        container.appendChild(table);
    };

    const showToast = (message, variant = 'success') => {
        const container = byId('toast-container');
        if (!container) {
            alert(message);
            return;
        }
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${variant === 'error' ? 'danger' : 'success'} border-0 fade`;
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        toast.setAttribute('aria-atomic', 'true');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Закрыть"></button>
            </div>
        `;
        container.appendChild(toast);
        if (window.bootstrap?.Toast) {
            const instance = window.bootstrap.Toast.getOrCreateInstance(toast, { delay: 4500 });
            toast.addEventListener('hidden.bs.toast', () => toast.remove());
            instance.show();
        } else {
            toast.classList.add('show');
            setTimeout(() => toast.remove(), 4500);
        }
    };

    const ajaxPost = (url, data) =>
        new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
            xhr.onreadystatechange = () => {
                if (xhr.readyState !== 4) {
                    return;
                }
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        resolve(JSON.parse(xhr.responseText || '{}'));
                    } catch (error) {
                        reject(new Error('Invalid JSON response'));
                    }
                    return;
                }
                let errorMessage = xhr.responseText || 'Network response was not ok';
                try {
                    const parsed = JSON.parse(xhr.responseText);
                    errorMessage = parsed.error || errorMessage;
                } catch (error) {
                    // ignore
                }
                reject(new Error(errorMessage));
            };
            xhr.onerror = () => reject(new Error('Network response was not ok'));
            const body = new URLSearchParams(data).toString();
            xhr.send(body);
        });

    const postBudgetAction = (action, payload = {}) => ajaxPost(budgetEndpoint, { action, ...payload });

    const fetchCategories = async () => {
        const response = await fetch(withBasePath('/api/categories?type=expense'), { credentials: 'same-origin' });
        if (!response.ok) {
            throw new Error('Не удалось загрузить категории');
        }
        return response.json();
    };

    const initDashboardBudgets = async () => {
        const budgetList = byId('dashboard-budget-list');
        if (!budgetList) {
            return;
        }
        try {
            const month = new Date().toISOString().slice(0, 7);
            const data = await postBudgetAction('getNow', { month });
            const availableBudgets = data.budgets || [];
            budgetList.innerHTML = '';
            if (availableBudgets.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'text-muted';
                empty.textContent = getRandomEmptyMessage();
                budgetList.appendChild(empty);
                return;
            }
            availableBudgets
                .map((item) => {
                    const status = getBudgetStatus(item.limit_amount, item.spent);
                    const percent = Math.round(status.percent);
                    return {
                        ...item,
                        limit: status.limit,
                        spent: status.spent,
                        percent,
                        status_label: status.label,
                        status_variant: status.variant,
                    };
                })
                .sort((a, b) => b.percent - a.percent)
                .slice(0, 3)
                .forEach((item) => {
                    const row = document.createElement('div');
                    row.className = 'budget-status';

                    const header = document.createElement('div');
                    header.className = 'budget-status__header';

                    const title = document.createElement('span');
                    title.textContent = item.category_name;

                    const badge = document.createElement('span');
                    const variant = item.status_variant || 'success';
                    badge.className = 'badge';
                    if (variant === 'danger') {
                        badge.classList.add('budget-status__label--danger');
                    } else if (variant === 'warning') {
                        badge.classList.add('budget-status__label--warning');
                    }
                    badge.textContent = item.status_label || 'В пределах';

                    header.append(title, badge);

                    const value = document.createElement('span');
                    value.className = 'budget-status__value';
                    if (variant === 'danger') {
                        value.classList.add('budget-status__value--danger');
                    } else if (variant === 'warning') {
                        value.classList.add('budget-status__value--warning');
                    }
                    value.textContent = `${formatCurrency(item.spent)} из ${formatCurrency(item.limit)}`;

                    const progress = document.createElement('div');
                    progress.className = 'progress progress--budget';
                    const bar = document.createElement('div');
                    bar.className = 'progress__bar';
                    if (variant === 'danger') {
                        bar.classList.add('progress__bar--danger');
                    } else if (variant === 'warning') {
                        bar.classList.add('progress__bar--warning');
                    }
                    bar.style.width = `${Math.min(item.percent, 100)}%`;
                    progress.appendChild(bar);

                    const meta = document.createElement('span');
                    meta.className = 'budget-status__meta';
                    meta.textContent = `Использовано: ${item.percent}%`;

                    row.append(header, value, progress, meta);
                    budgetList.appendChild(row);
                });
        } catch (error) {
            showToast('Не удалось загрузить бюджеты дашборда', 'error');
        }
    };

    const initBudgetsPage = async () => {
        const currentTable = byId('budgets-current-table');
        const historyTable = byId('budgets-history-table');
        const modal = byId('budgets-modal');
        const form = byId('budgets-form');
        const title = byId('budgets-form-title');
        const cancel = byId('budgets-cancel');
        const addButton = byId('budgets-add');
        const categorySelect = byId('budgets-category');

        if (!currentTable || !historyTable || !modal || !form || !title || !cancel || !addButton || !categorySelect) {
            return;
        }

        const modalInstance = window.bootstrap?.Modal ? window.bootstrap.Modal.getOrCreateInstance(modal) : null;

        const fillSelect = (select, items, placeholder) => {
            select.innerHTML = '';
            const placeholderOption = document.createElement('option');
            placeholderOption.value = '';
            placeholderOption.textContent = placeholder;
            select.appendChild(placeholderOption);
            items.forEach((item) => {
                const option = document.createElement('option');
                option.value = item.value;
                option.textContent = item.label;
                select.appendChild(option);
            });
        };

        try {
            const { categories } = await fetchCategories();
            fillSelect(categorySelect, categories.map((cat) => ({ value: cat.category_id, label: cat.name })), 'Выберите');
        } catch (error) {
            showToast('Не удалось загрузить категории', 'error');
            fillSelect(categorySelect, [], 'Выберите');
        }

        const resetForm = () => {
            form.reset();
            const month = new Date().toISOString().slice(0, 7);
            form.querySelector('[name="period_month"]').value = month;
            form.querySelector('[name="budget_id"]').value = '';
            title.textContent = 'Новый бюджет';
            if (categorySelect.options.length > 0) {
                categorySelect.selectedIndex = 0;
            }
        };

        const openFormModal = (budget = null) => {
            if (budget) {
                form.querySelector('[name="budget_id"]').value = budget.budget_id;
                form.querySelector('[name="category_id"]').value = budget.category_id;
                form.querySelector('[name="period_month"]').value = budget.period_month;
                form.querySelector('[name="limit_amount"]').value = budget.limit_amount;
                title.textContent = `Редактирование: ${budget.category_name}`;
            } else {
                resetForm();
            }
            if (modalInstance) {
                modalInstance.show();
            } else {
                modal.style.display = 'block';
                modal.classList.add('show');
            }
        };

        const closeFormModal = () => {
            if (modalInstance) {
                modalInstance.hide();
            } else {
                modal.classList.remove('show');
                modal.style.display = 'none';
            }
        };

        const buildProgress = (budget) => {
            const percent = budget.limit_amount > 0 ? Math.min(100, (budget.spent / budget.limit_amount) * 100) : 0;
            const size = 32;
            const stroke = 4;
            const radius = (size - stroke) / 2;
            const circumference = 2 * Math.PI * radius;
            const offset = circumference - (percent / 100) * circumference;

            const wrapper = document.createElement('div');
            wrapper.className = 'budget-progress';
            if (budget.status_variant === 'danger') {
                wrapper.classList.add('budget-progress--danger');
            } else if (budget.status_variant === 'warning') {
                wrapper.classList.add('budget-progress--warning');
            }

            const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.classList.add('budget-progress__ring');
            svg.setAttribute('viewBox', `0 0 ${size} ${size}`);
            svg.setAttribute('width', size);
            svg.setAttribute('height', size);

            const track = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            track.classList.add('budget-progress__track');
            track.setAttribute('cx', size / 2);
            track.setAttribute('cy', size / 2);
            track.setAttribute('r', radius);

            const value = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            value.classList.add('budget-progress__value');
            value.setAttribute('cx', size / 2);
            value.setAttribute('cy', size / 2);
            value.setAttribute('r', radius);
            value.setAttribute('stroke-dasharray', `${circumference} ${circumference}`);
            value.setAttribute('stroke-dashoffset', offset);

            svg.append(track, value);

            const label = document.createElement('span');
            label.className = 'budget-progress__text';
            label.textContent = `${percent.toFixed(0)}%`;

            wrapper.append(svg, label);
            return wrapper;
        };

        const buildStatusBadge = (budget) => {
            const badge = document.createElement('span');
            badge.className = 'badge';
            if (budget.status_variant === 'danger') {
                badge.classList.add('budget-status__label--danger');
            } else if (budget.status_variant === 'warning') {
                badge.classList.add('budget-status__label--warning');
            }
            badge.textContent = budget.status_label || 'В пределах';
            return badge;
        };

        const buildActions = (budget) => {
            const editBtn = createIconButton({ icon: '✏️', label: 'Редактировать' });
            editBtn.addEventListener('click', () => openFormModal(budget));

            const deleteBtn = createIconButton({ icon: '🗑️', label: 'Удалить бюджет', variant: 'outline' });
            deleteBtn.addEventListener('click', async () => {
                if (!window.confirm('Удалить бюджет?')) {
                    return;
                }
                try {
                    await postBudgetAction('delete', { budget_id: budget.budget_id });
                    showToast('Бюджет удалён');
                    await load();
                } catch (error) {
                    showToast(error.message || 'Ошибка удаления бюджета', 'error');
                }
            });

            const actions = document.createElement('div');
            actions.className = 'table__actions';
            actions.append(editBtn, deleteBtn);
            return actions;
        };

        const load = async () => {
            try {
                const data = await postBudgetAction('getAll');
                const budgets = data.budgets || [];
                const sortedBudgets = [...budgets].sort((a, b) => b.period_month.localeCompare(a.period_month));
                const currentMonth = new Date().toISOString().slice(0, 7);
                const normalize = (budget) => {
                    const status = getBudgetStatus(budget.limit_amount, budget.spent);
                    return {
                        ...budget,
                        status_label: status.label,
                        status_variant: status.variant,
                    };
                };
                const currentBudgets = sortedBudgets.filter((budget) => budget.period_month === currentMonth).map(normalize);
                const historyBudgets = sortedBudgets.filter((budget) => budget.period_month !== currentMonth).map(normalize);

                renderTable(
                    currentTable,
                    ['Категория', 'Лимит', 'Факт', 'Прогресс', 'Статус', 'Действия'],
                    currentBudgets.map((budget) => [
                        budget.category_name,
                        formatCurrency(budget.limit_amount),
                        formatCurrency(budget.spent),
                        buildProgress(budget),
                        buildStatusBadge(budget),
                        buildActions(budget),
                    ])
                );
                renderTable(
                    historyTable,
                    ['Месяц', 'Категория', 'Лимит', 'Факт', 'Статус', 'Действия'],
                    historyBudgets.map((budget) => [
                        budget.period_month,
                        budget.category_name,
                        formatCurrency(budget.limit_amount),
                        formatCurrency(budget.spent),
                        buildStatusBadge(budget),
                        buildActions(budget),
                    ])
                );
            } catch (error) {
                showToast('Не удалось загрузить бюджеты', 'error');
                renderTable(currentTable, ['Категория', 'Лимит', 'Факт', 'Прогресс', 'Статус', 'Действия'], []);
                renderTable(historyTable, ['Месяц', 'Категория', 'Лимит', 'Факт', 'Статус', 'Действия'], []);
            }
        };

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            const action = data.budget_id ? 'update' : 'create';
            try {
                await postBudgetAction(action, data);
                showToast(action === 'create' ? 'Бюджет создан' : 'Бюджет обновлён');
                resetForm();
                closeFormModal();
                await load();
            } catch (error) {
                showToast(error.message || 'Ошибка сохранения бюджета', 'error');
            }
        });

        cancel.addEventListener('click', () => {
            resetForm();
            closeFormModal();
        });

        addButton.addEventListener('click', () => openFormModal());
        modal.addEventListener('hidden.bs.modal', resetForm);

        await load();
    };

    if (page === 'dashboard') {
        initDashboardBudgets();
    }
    if (page === 'budgets') {
        initBudgetsPage();
    }
})();
