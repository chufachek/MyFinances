import { getJson, postJson, putJson, deleteJson } from './api.js';

const formatCurrency = (value) => {
    const amount = Number(value ?? 0);
    return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(amount);
};

const byId = (id) => document.getElementById(id);

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

    container.innerHTML = '';
    container.appendChild(table);
};

const serializeForm = (form) => Object.fromEntries(new FormData(form).entries());

const fillSelect = (select, options, placeholder = 'Все') => {
    select.innerHTML = '';
    if (placeholder) {
        const empty = document.createElement('option');
        empty.value = '';
        empty.textContent = placeholder;
        select.appendChild(empty);
    }
    options.forEach((opt) => {
        const option = document.createElement('option');
        option.value = opt.value;
        option.textContent = opt.label;
        select.appendChild(option);
    });
};

const setFormValues = (form, values) => {
    Object.entries(values).forEach(([key, value]) => {
        const field = form.querySelector(`[name="${key}"]`);
        if (field) {
            field.value = value ?? '';
        }
    });
};

const showError = (message) => {
    alert(message);
};

const setupLogout = () => {
    document.querySelectorAll('[data-action="logout"]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            await postJson('/api/auth/logout', {});
            window.location.href = '/login';
        });
    });
};

const initAuthForms = () => {
    const loginForm = byId('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const data = serializeForm(loginForm);
            try {
                await postJson('/api/auth/login', data);
                window.location.href = '/dashboard';
            } catch (error) {
                showError('Не удалось войти. Проверьте данные.');
            }
        });
    }

    const registerForm = byId('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const data = serializeForm(registerForm);
            if (data.password !== data.password_confirm) {
                showError('Пароли не совпадают');
                return;
            }
            try {
                await postJson('/api/auth/register', data);
                window.location.href = '/dashboard';
            } catch (error) {
                showError('Не удалось зарегистрироваться');
            }
        });
    }
};

const initDashboard = async () => {
    const summary = await getJson('/api/reports/summary');
    byId('summary-balance').textContent = formatCurrency(summary.balance);
    byId('summary-income').textContent = formatCurrency(summary.income);
    byId('summary-expense').textContent = formatCurrency(summary.expense);
    byId('summary-net').textContent = formatCurrency(summary.net);
    byId('summary-net-note').textContent = summary.net >= 0 ? 'профицит' : 'дефицит';

    const tx = await getJson('/api/transactions?limit=5');
    const rows = tx.transactions.map((item) => [
        new Date(item.tx_date).toLocaleDateString('ru-RU'),
        item.tx_type === 'income' ? 'Доход' : 'Расход',
        item.category_name ?? 'Без категории',
        item.account_name ?? '—',
        formatCurrency(item.amount),
    ]);

    renderTable(byId('dashboard-transactions'), ['Дата', 'Тип', 'Категория', 'Счёт', 'Сумма'], rows);
};

const initAccounts = async () => {
    const table = byId('accounts-table');
    const form = byId('accounts-form');
    const title = byId('accounts-form-title');
    const cancel = byId('accounts-cancel');

    const load = async () => {
        const { accounts } = await getJson('/api/accounts');
        renderTable(
            table,
            ['Название', 'Тип', 'Валюта', 'Баланс', 'Статус', 'Действия'],
            accounts.map((acc) => {
                const editBtn = document.createElement('button');
                editBtn.className = 'btn btn-outline btn-sm';
                editBtn.textContent = 'Редактировать';
                editBtn.addEventListener('click', () => {
                    setFormValues(form, {
                        account_id: acc.account_id,
                        name: acc.name,
                        account_type: acc.account_type,
                        currency_code: acc.currency_code,
                        initial_balance: acc.initial_balance,
                        is_active: acc.is_active,
                    });
                    title.textContent = `Редактирование: ${acc.name}`;
                });

                const deleteBtn = document.createElement('button');
                deleteBtn.className = 'btn btn-outline btn-sm';
                deleteBtn.textContent = 'Скрыть';
                deleteBtn.addEventListener('click', async () => {
                    await deleteJson(`/api/accounts/${acc.account_id}`);
                    await load();
                });

                const actions = document.createElement('div');
                actions.style.display = 'flex';
                actions.style.gap = '8px';
                actions.append(editBtn, deleteBtn);

                return [
                    acc.name,
                    acc.account_type,
                    acc.currency_code,
                    formatCurrency(acc.balance),
                    acc.is_active ? 'Активен' : 'Скрыт',
                    actions,
                ];
            })
        );
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const data = serializeForm(form);
        const id = data.account_id;
        delete data.account_id;
        if (id) {
            await putJson(`/api/accounts/${id}`, data);
        } else {
            await postJson('/api/accounts', data);
        }
        form.reset();
        title.textContent = 'Новый счёт';
        await load();
    });

    cancel.addEventListener('click', () => {
        form.reset();
        title.textContent = 'Новый счёт';
    });

    await load();
};

const initCategories = async () => {
    const table = byId('categories-table');
    const form = byId('categories-form');
    const title = byId('categories-form-title');
    const filter = byId('categories-filter');
    const cancel = byId('categories-cancel');

    const load = async () => {
        const type = filter.value;
        const url = type ? `/api/categories?type=${type}` : '/api/categories';
        const { categories } = await getJson(url);
        renderTable(
            table,
            ['Название', 'Тип', 'Статус', 'Действия'],
            categories.map((cat) => {
                const editBtn = document.createElement('button');
                editBtn.className = 'btn btn-outline btn-sm';
                editBtn.textContent = 'Редактировать';
                editBtn.addEventListener('click', () => {
                    setFormValues(form, {
                        category_id: cat.category_id,
                        name: cat.name,
                        category_type: cat.category_type,
                        is_active: cat.is_active,
                    });
                    title.textContent = `Редактирование: ${cat.name}`;
                });

                const deleteBtn = document.createElement('button');
                deleteBtn.className = 'btn btn-outline btn-sm';
                deleteBtn.textContent = 'Скрыть';
                deleteBtn.addEventListener('click', async () => {
                    await deleteJson(`/api/categories/${cat.category_id}`);
                    await load();
                });

                const actions = document.createElement('div');
                actions.style.display = 'flex';
                actions.style.gap = '8px';
                actions.append(editBtn, deleteBtn);

                return [cat.name, cat.category_type === 'income' ? 'Доход' : 'Расход', cat.is_active ? 'Активна' : 'Скрыта', actions];
            })
        );
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const data = serializeForm(form);
        const id = data.category_id;
        delete data.category_id;
        if (id) {
            await putJson(`/api/categories/${id}`, data);
        } else {
            await postJson('/api/categories', data);
        }
        form.reset();
        title.textContent = 'Новая категория';
        await load();
    });

    cancel.addEventListener('click', () => {
        form.reset();
        title.textContent = 'Новая категория';
    });

    filter.addEventListener('change', load);

    await load();
};

const initTransactions = async () => {
    const table = byId('transactions-table');
    const filterForm = byId('transactions-filter');
    const form = byId('transactions-form');
    const title = byId('transactions-form-title');
    const cancel = byId('transactions-cancel');
    const addBtn = byId('transactions-add');
    const resetBtn = byId('transactions-reset');
    const transfersTable = byId('transfers-table');

    const accounts = await getJson('/api/accounts');
    const accountOptions = accounts.accounts.map((acc) => ({ value: acc.account_id, label: acc.name }));

    fillSelect(byId('filter-account'), accountOptions, 'Все');
    fillSelect(byId('tx-account'), accountOptions, 'Выберите');
    fillSelect(byId('transfer-from'), accountOptions, 'Выберите');
    fillSelect(byId('transfer-to'), accountOptions, 'Выберите');

    const loadCategories = async (type) => {
        const url = type ? `/api/categories?type=${type}` : '/api/categories';
        const { categories } = await getJson(url);
        return categories.map((cat) => ({ value: cat.category_id, label: cat.name }));
    };

    const refreshFormCategories = async () => {
        const type = byId('tx-type').value;
        const options = await loadCategories(type);
        fillSelect(byId('tx-category'), options, 'Без категории');
    };

    const refreshFilterCategories = async () => {
        const type = filterForm.querySelector('[name=\"type\"]').value;
        const options = await loadCategories(type);
        fillSelect(byId('filter-category'), options, 'Все');
    };

    const loadTransactions = async () => {
        const params = new URLSearchParams(new FormData(filterForm));
        const { transactions } = await getJson(`/api/transactions?${params.toString()}`);
        renderTable(
            table,
            ['Дата', 'Тип', 'Категория', 'Счёт', 'Сумма', 'Комментарий', 'Действия'],
            transactions.map((tx) => {
                const editBtn = document.createElement('button');
                editBtn.className = 'btn btn-outline btn-sm';
                editBtn.textContent = 'Редактировать';
                editBtn.addEventListener('click', () => {
                    setFormValues(form, {
                        transaction_id: tx.transaction_id,
                        tx_type: tx.tx_type,
                        account_id: tx.account_id,
                        category_id: tx.category_id,
                        amount: tx.amount,
                        tx_date: tx.tx_date.replace(' ', 'T'),
                        note: tx.note,
                        merchant: tx.merchant_name,
                    });
                    title.textContent = `Редактирование: ${tx.category_name ?? 'Без категории'}`;
                });

                const deleteBtn = document.createElement('button');
                deleteBtn.className = 'btn btn-outline btn-sm';
                deleteBtn.textContent = 'Удалить';
                deleteBtn.addEventListener('click', async () => {
                    await deleteJson(`/api/transactions/${tx.transaction_id}`);
                    await loadTransactions();
                });

                const actions = document.createElement('div');
                actions.style.display = 'flex';
                actions.style.gap = '8px';
                actions.append(editBtn, deleteBtn);

                return [
                    new Date(tx.tx_date).toLocaleDateString('ru-RU'),
                    tx.tx_type === 'income' ? 'Доход' : 'Расход',
                    tx.category_name ?? '—',
                    tx.account_name ?? '—',
                    formatCurrency(tx.amount),
                    tx.note ?? '',
                    actions,
                ];
            })
        );
    };

    const loadTransfers = async () => {
        const { transfers } = await getJson('/api/transfers');
        renderTable(
            transfersTable,
            ['Дата', 'Откуда', 'Куда', 'Сумма', 'Комиссия', 'Комментарий', 'Действия'],
            transfers.map((tr) => {
                const deleteBtn = document.createElement('button');
                deleteBtn.className = 'btn btn-outline btn-sm';
                deleteBtn.textContent = 'Удалить';
                deleteBtn.addEventListener('click', async () => {
                    await deleteJson(`/api/transfers/${tr.transfer_id}`);
                    await loadTransfers();
                });
                return [
                    new Date(tr.tx_date).toLocaleDateString('ru-RU'),
                    tr.from_account,
                    tr.to_account,
                    formatCurrency(tr.amount),
                    formatCurrency(tr.fee),
                    tr.note ?? '',
                    deleteBtn,
                ];
            })
        );
    };

    await refreshFormCategories();
    await refreshFilterCategories();

    byId('tx-type').addEventListener('change', refreshFormCategories);
    filterForm.querySelector('[name=\"type\"]').addEventListener('change', refreshFilterCategories);

    filterForm.addEventListener('input', loadTransactions);

    resetBtn.addEventListener('click', () => {
        filterForm.reset();
        refreshFilterCategories();
        loadTransactions();
    });

    addBtn.addEventListener('click', () => {
        form.reset();
        title.textContent = 'Новая операция';
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const data = serializeForm(form);
        const id = data.transaction_id;
        delete data.transaction_id;
        if (id) {
            await putJson(`/api/transactions/${id}`, data);
        } else {
            await postJson('/api/transactions', data);
        }
        form.reset();
        title.textContent = 'Новая операция';
        await loadTransactions();
    });

    cancel.addEventListener('click', () => {
        form.reset();
        title.textContent = 'Новая операция';
    });

    byId('transfer-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const data = serializeForm(event.target);
        await postJson('/api/transfers', data);
        event.target.reset();
        await loadTransfers();
    });

    await loadTransactions();
    await loadTransfers();
};

const initBudgets = async () => {
    const table = byId('budgets-table');
    const monthPicker = byId('budgets-month');
    const form = byId('budgets-form');
    const title = byId('budgets-form-title');
    const cancel = byId('budgets-cancel');

    const { categories } = await getJson('/api/categories?type=expense');
    fillSelect(byId('budgets-category'), categories.map((cat) => ({ value: cat.category_id, label: cat.name })), 'Выберите');

    const load = async () => {
        const month = monthPicker.value || new Date().toISOString().slice(0, 7);
        monthPicker.value = month;
        const { budgets } = await getJson(`/api/budgets?month=${month}`);
        renderTable(
            table,
            ['Категория', 'Лимит', 'Факт', 'Статус', 'Действия'],
            budgets.map((b) => {
                const percent = b.limit_amount > 0 ? Math.min(100, (b.spent / b.limit_amount) * 100) : 0;
                const status = percent >= 100 ? 'Превышено' : `${percent.toFixed(0)}%`;

                const progress = document.createElement('div');
                progress.className = 'progress';
                const bar = document.createElement('div');
                bar.className = 'progress__bar';
                bar.style.width = `${percent}%`;
                progress.appendChild(bar);

                const editBtn = document.createElement('button');
                editBtn.className = 'btn btn-outline btn-sm';
                editBtn.textContent = 'Редактировать';
                editBtn.addEventListener('click', () => {
                    setFormValues(form, {
                        budget_id: b.budget_id,
                        category_id: b.category_id,
                        period_month: b.period_month,
                        limit_amount: b.limit_amount,
                    });
                    title.textContent = `Редактирование: ${b.category_name}`;
                });

                const deleteBtn = document.createElement('button');
                deleteBtn.className = 'btn btn-outline btn-sm';
                deleteBtn.textContent = 'Удалить';
                deleteBtn.addEventListener('click', async () => {
                    await deleteJson(`/api/budgets/${b.budget_id}`);
                    await load();
                });

                const actions = document.createElement('div');
                actions.style.display = 'flex';
                actions.style.gap = '8px';
                actions.append(editBtn, deleteBtn);

                return [b.category_name, formatCurrency(b.limit_amount), formatCurrency(b.spent), progress, actions];
            })
        );
    };

    monthPicker.addEventListener('change', load);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const data = serializeForm(form);
        const id = data.budget_id;
        delete data.budget_id;
        if (id) {
            await putJson(`/api/budgets/${id}`, data);
        } else {
            await postJson('/api/budgets', data);
        }
        form.reset();
        title.textContent = 'Новый бюджет';
        await load();
    });

    cancel.addEventListener('click', () => {
        form.reset();
        title.textContent = 'Новый бюджет';
    });

    await load();
};

const initGoals = async () => {
    const list = byId('goals-list');
    const form = byId('goals-form');
    const title = byId('goals-form-title');
    const cancel = byId('goals-cancel');

    const renderGoals = async () => {
        const { goals } = await getJson('/api/goals');
        list.innerHTML = '';
        goals.forEach((goal) => {
            const card = document.createElement('article');
            card.className = 'card';
            const percent = goal.target_amount > 0 ? Math.min(100, (goal.current_amount / goal.target_amount) * 100) : 0;
            card.innerHTML = `
                <div class="card__header">
                    <span class="badge">${goal.status}</span>
                    <h3>${goal.name}</h3>
                </div>
                <p class="text-muted">${formatCurrency(goal.current_amount)} из ${formatCurrency(goal.target_amount)}</p>
            `;
            const progress = document.createElement('div');
            progress.className = 'progress';
            const bar = document.createElement('div');
            bar.className = 'progress__bar';
            bar.style.width = `${percent}%`;
            progress.appendChild(bar);
            card.appendChild(progress);

            const actions = document.createElement('div');
            actions.className = 'form-actions';

            const editBtn = document.createElement('button');
            editBtn.className = 'btn btn-outline btn-sm';
            editBtn.textContent = 'Редактировать';
            editBtn.addEventListener('click', () => {
                setFormValues(form, {
                    goal_id: goal.goal_id,
                    name: goal.name,
                    target_amount: goal.target_amount,
                    current_amount: goal.current_amount,
                    due_date: goal.due_date ?? '',
                    status: goal.status,
                });
                title.textContent = `Редактирование: ${goal.name}`;
            });

            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'btn btn-outline btn-sm';
            deleteBtn.textContent = 'Удалить';
            deleteBtn.addEventListener('click', async () => {
                await deleteJson(`/api/goals/${goal.goal_id}`);
                await renderGoals();
            });

            actions.append(editBtn, deleteBtn);
            card.appendChild(actions);
            list.appendChild(card);
        });
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const data = serializeForm(form);
        const id = data.goal_id;
        delete data.goal_id;
        if (id) {
            await putJson(`/api/goals/${id}`, data);
        } else {
            await postJson('/api/goals', data);
        }
        form.reset();
        title.textContent = 'Новая цель';
        await renderGoals();
    });

    cancel.addEventListener('click', () => {
        form.reset();
        title.textContent = 'Новая цель';
    });

    await renderGoals();
};

const initReports = async () => {
    const pieCtx = byId('report-pie');
    const lineCtx = byId('report-line');
    const table = byId('report-category-table');
    const filterForm = byId('reports-filter');
    const monthPicker = byId('reports-month');

    let pieChart;
    let lineChart;

    const load = async () => {
        const data = serializeForm(filterForm);
        const month = data.month || new Date().toISOString().slice(0, 7);
        monthPicker.value = month;
        const [year, monthValue] = month.split('-').map(Number);
        const lastDay = new Date(year, monthValue, 0).getDate();
        const dateFrom = data.dateFrom || `${month}-01`;
        const dateTo = data.dateTo || `${month}-${String(lastDay).padStart(2, '0')}`;
        const [pie, line] = await Promise.all([
            getJson(`/api/reports/expense-by-category?month=${month}`),
            getJson(`/api/reports/dynamics?dateFrom=${dateFrom}&dateTo=${dateTo}&groupBy=${data.groupBy || 'day'}`),
        ]);

        const labels = pie.items.map((item) => item.name);
        const values = pie.items.map((item) => item.total);

        if (pieChart) {
            pieChart.destroy();
        }
        pieChart = new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [
                    {
                        data: values,
                        backgroundColor: ['#2f7a4d', '#4ecf7d', '#6fdd9d', '#b6f0c9', '#d9f7e3', '#2f9e6c'],
                    },
                ],
            },
        });

        renderTable(
            table,
            ['Категория', 'Сумма', 'Доля'],
            pie.items.map((item) => {
                const total = values.reduce((sum, v) => sum + Number(v), 0) || 1;
                const share = ((item.total / total) * 100).toFixed(1);
                return [item.name, formatCurrency(item.total), `${share}%`];
            })
        );

        if (lineChart) {
            lineChart.destroy();
        }
        lineChart = new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: line.labels,
                datasets: [
                    {
                        label: 'Доходы',
                        data: line.income,
                        borderColor: '#2f7a4d',
                        backgroundColor: 'rgba(47, 122, 77, 0.15)',
                    },
                    {
                        label: 'Расходы',
                        data: line.expense,
                        borderColor: '#b42318',
                        backgroundColor: 'rgba(180, 35, 24, 0.1)',
                    },
                ],
            },
        });
    };

    filterForm.addEventListener('change', load);

    await load();
};

const page = document.body.dataset.page;

setupLogout();
initAuthForms();

if (page === 'dashboard') {
    initDashboard().catch(console.error);
}
if (page === 'accounts') {
    initAccounts().catch(console.error);
}
if (page === 'categories') {
    initCategories().catch(console.error);
}
if (page === 'transactions') {
    initTransactions().catch(console.error);
}
if (page === 'budgets') {
    initBudgets().catch(console.error);
}
if (page === 'goals') {
    initGoals().catch(console.error);
}
if (page === 'reports') {
    initReports().catch(console.error);
}
