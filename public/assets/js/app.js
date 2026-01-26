import { getJson, postJson, putJson, deleteJson } from './api.js';

const formatCurrency = (value) => {
    const amount = Number(value ?? 0);
    return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(amount);
};

const byId = (id) => document.getElementById(id);
const setText = (id, value) => {
    const el = byId(id);
    if (el) {
        el.textContent = value;
    }
};

const ensureToastContainer = () => {
    let container = byId('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    return container;
};

const showToast = (message, variant = 'success') => {
    const container = ensureToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast toast--${variant}`;
    toast.innerHTML = `<strong>${variant === 'error' ? 'Ошибка' : 'Готово'}</strong><span>${message}</span>`;
    container.appendChild(toast);
    requestAnimationFrame(() => {
        toast.classList.add('is-visible');
    });
    setTimeout(() => {
        toast.classList.remove('is-visible');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
};

const requestWithToast = async (callback, successMessage) => {
    try {
        const result = await callback();
        if (successMessage) {
            showToast(successMessage, 'success');
        } else if (result && result.message) {
            showToast(result.message, 'success');
        } else {
            showToast('Операция выполнена', 'success');
        }
        return result;
    } catch (error) {
        showToast(error.message || 'Что-то пошло не так', 'error');
        throw error;
    }
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

let chartLibraryPromise;
const ensureChart = () => {
    if (typeof Chart !== 'undefined') {
        return Promise.resolve(Chart);
    }
    if (!chartLibraryPromise) {
        chartLibraryPromise = import('https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js')
            .then((mod) => mod.Chart ?? mod.default ?? mod)
            .catch(() => null);
    }
    return chartLibraryPromise;
};

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

const formatDateTimeLocal = (date) => {
    const pad = (value) => String(value).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(
        date.getMinutes()
    )}`;
};

const normalizeDateTime = (value) => (value ? value.replace(' ', 'T') : '');

const showError = (message) => {
    showToast(message, 'error');
};

const getBootstrapModal = (modal) => {
    if (!modal) {
        return null;
    }
    if (window.bootstrap?.Modal) {
        return window.bootstrap.Modal.getOrCreateInstance(modal);
    }
    return null;
};

const openModal = (modal) => {
    if (!modal) {
        return;
    }
    const instance = getBootstrapModal(modal);
    if (instance) {
        instance.show();
        return;
    }
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
};

const closeModal = (modal) => {
    if (!modal) {
        return;
    }
    const instance = getBootstrapModal(modal);
    if (instance) {
        instance.hide();
        return;
    }
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
};

const closeAllModals = () => {
    if (window.bootstrap?.Modal) {
        document.querySelectorAll('.modal.show').forEach((modal) => {
            window.bootstrap.Modal.getOrCreateInstance(modal).hide();
        });
        return;
    }
    document.querySelectorAll('.modal.is-open').forEach((modal) => closeModal(modal));
};

const formatDateInput = (date) => date.toISOString().slice(0, 10);
const selectFirstOption = (select) => {
    if (!select) {
        return;
    }
    const option = Array.from(select.options).find((opt) => opt.value);
    if (option) {
        select.value = option.value;
    }
};

document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) {
        return;
    }
    if (target.dataset.action === 'close-modal') {
        closeAllModals();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeAllModals();
    }
});

const setupLogout = () => {
    document.querySelectorAll('[data-action="logout"]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            await postJson('/api/auth/logout', {});
            window.location.href = '/login';
        });
    });
};

const initTransactionModal = async () => {
    const modal = byId('quick-modal');
    const form = byId('quick-form');
    if (!modal || !form) {
        return null;
    }

    const title = byId('quick-modal-title');
    const typeSelect = byId('quick-type');
    const accountSelect = byId('quick-account');
    const categorySelect = byId('quick-category');
    const dateInput = byId('quick-date');
    const transactionId = byId('quick-transaction-id');

    let onSaved = null;
    let accountsReady = false;

    const setOnSaved = (handler) => {
        onSaved = handler;
    };

    const loadAccounts = async () => {
        if (accountsReady) {
            return;
        }
        try {
            const accounts = await getJson('/api/accounts');
            const accountOptions = accounts.accounts.map((acc) => ({ value: acc.account_id, label: acc.name }));
            fillSelect(accountSelect, accountOptions, 'Выберите');
            selectFirstOption(accountSelect);
            accountsReady = true;
        } catch (error) {
            showError('Не удалось загрузить список счетов.');
            fillSelect(accountSelect, [], 'Выберите');
        }
    };

    const loadCategories = async (type) => {
        try {
            const url = type ? `/api/categories?type=${type}` : '/api/categories';
            const { categories } = await getJson(url);
            return categories.map((cat) => ({ value: cat.category_id, label: cat.name }));
        } catch (error) {
            showError('Не удалось загрузить категории.');
            return [];
        }
    };

    const getTitle = (type, isEdit) => {
        if (isEdit) {
            return 'Редактирование операции';
        }
        if (type === 'income') {
            return 'Новый доход';
        }
        if (type === 'expense') {
            return 'Новый расход';
        }
        return 'Новая операция';
    };

    const updateCategories = async (type, selectedValue = '') => {
        const options = await loadCategories(type);
        fillSelect(categorySelect, options, 'Без категории');
        if (selectedValue) {
            categorySelect.value = selectedValue;
        } else {
            selectFirstOption(categorySelect);
        }
    };

    const open = async ({ type, transaction } = {}) => {
        await loadAccounts();
        form.reset();
        const isEdit = Boolean(transaction);
        const resolvedType = type || transaction?.tx_type || typeSelect.value || 'expense';
        typeSelect.value = resolvedType;
        title.textContent = getTitle(resolvedType, isEdit);
        transactionId.value = transaction?.transaction_id ?? '';
        dateInput.value = transaction?.tx_date ? normalizeDateTime(transaction.tx_date) : formatDateTimeLocal(new Date());
        await updateCategories(resolvedType, transaction?.category_id ?? '');

        if (transaction) {
            setFormValues(form, {
                tx_type: transaction.tx_type,
                account_id: transaction.account_id,
                amount: transaction.amount,
                note: transaction.note,
                merchant: transaction.merchant_name,
            });
            if (transaction.category_id) {
                categorySelect.value = transaction.category_id;
            }
        }

        openModal(modal);
    };

    typeSelect.addEventListener('change', async () => {
        title.textContent = getTitle(typeSelect.value, Boolean(transactionId.value));
        await updateCategories(typeSelect.value, categorySelect.value);
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const data = serializeForm(form);
        const id = data.transaction_id;
        delete data.transaction_id;
        if (id) {
            await requestWithToast(() => putJson(`/api/transactions/${id}`, data), 'Операция обновлена');
        } else {
            await requestWithToast(
                () => postJson('/api/transactions', data),
                data.tx_type === 'income' ? 'Доход добавлен' : 'Расход добавлен'
            );
        }
        closeModal(modal);
        if (onSaved) {
            await onSaved();
        }
    });

    document.querySelectorAll('[data-action="open-quick"]').forEach((btn) => {
        btn.addEventListener('click', () => open({ type: btn.dataset.type }));
    });

    return { open, setOnSaved };
};

const initTransferModal = ({ onSaved } = {}) => {
    const modal = byId('transfer-modal');
    const form = byId('transfer-quick-form');
    if (!modal || !form) {
        return null;
    }

    const fromSelect = byId('transfer-quick-from');
    const toSelect = byId('transfer-quick-to');
    const dateInput = byId('transfer-quick-date');

    let accountsCache = null;

    const loadAccounts = async () => {
        if (accountsCache) {
            return accountsCache;
        }
        try {
            const { accounts } = await getJson('/api/accounts');
            accountsCache = accounts.map((acc) => ({ value: acc.account_id, label: acc.name }));
        } catch (error) {
            showError('Не удалось загрузить список счетов.');
            accountsCache = [];
        }
        return accountsCache;
    };

    const setDefaults = async () => {
        form.reset();
        dateInput.value = formatDateTimeLocal(new Date());
        const accounts = await loadAccounts();
        fillSelect(fromSelect, accounts, 'Выберите');
        fillSelect(toSelect, accounts, 'Выберите');

        if (accounts.length > 0) {
            fromSelect.value = accounts[0].value;
        }
        if (accounts.length > 1) {
            toSelect.value = accounts[1].value;
        } else if (accounts.length === 1) {
            toSelect.value = accounts[0].value;
        }
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const data = serializeForm(form);
        if (data.from_account_id === data.to_account_id) {
            showError('Выберите разные счета для перевода.');
            return;
        }
        await requestWithToast(() => postJson('/api/transfers', data), 'Перевод выполнен');
        closeModal(modal);
        if (onSaved) {
            await onSaved();
        }
    });

    document.querySelectorAll('[data-action="open-transfer"]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            await setDefaults();
            openModal(modal);
        });
    });

    return { open: async () => {
        await setDefaults();
        openModal(modal);
    } };
};

// Инициализация модалок добавления доходов и расходов.
const initIncomeExpenseModals = async ({ onSaved } = {}) => {
    const configs = [
        { id: 'income-modal', type: 'income' },
        { id: 'expense-modal', type: 'expense' },
    ];
    const state = {
        accountOptions: null,
    };

    // Единоразово загружаем счета для обоих модалок.
    const loadAccounts = async () => {
        if (state.accountOptions) {
            return state.accountOptions;
        }
        try {
            const accounts = await getJson('/api/accounts');
            state.accountOptions = accounts.accounts.map((acc) => ({ value: acc.account_id, label: acc.name }));
        } catch (error) {
            showError('Не удалось загрузить список счетов.');
            state.accountOptions = [];
        }
        return state.accountOptions;
    };

    // Категории подгружаются отдельно для доходов и расходов.
    const loadCategories = async (type) => {
        try {
            const { categories } = await getJson(`/api/categories?type=${type}`);
            return categories.map((cat) => ({ value: cat.category_id, label: cat.name }));
        } catch (error) {
            showError('Не удалось загрузить категории.');
            return [];
        }
    };

    const setupModal = (config) => {
        const modal = byId(config.id);
        if (!modal) {
            return null;
        }

        const form = modal.querySelector('form');
        const accountSelect = form.querySelector('select[name="account_id"]');
        const categorySelect = form.querySelector('select[name="category_id"]');
        const dateInput = form.querySelector('input[name="tx_date"]');
        const amountInput = form.querySelector('input[name="amount"]');

        // Подготовка формы перед каждым открытием.
        const setDefaults = async () => {
            form.reset();
            dateInput.value = formatDateInput(new Date());
            const accounts = await loadAccounts();
            fillSelect(accountSelect, accounts, 'Выберите');
            selectFirstOption(accountSelect);
            const categories = await loadCategories(config.type);
            fillSelect(categorySelect, categories, 'Без категории');
            selectFirstOption(categorySelect);
        };

        // Мини-калькулятор для быстрого изменения суммы.
        modal.querySelectorAll('[data-amount-delta]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const delta = Number(btn.dataset.amountDelta || 0);
                const current = Number(amountInput.value || 0);
                const next = Math.max(0, current + delta);
                amountInput.value = next ? next.toFixed(2) : '';
            });
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const data = serializeForm(form);
            const amountValue = Number(data.amount || 0);
            if (!data.account_id) {
                showError('Выберите счёт.');
                return;
            }
            if (amountValue <= 0) {
                showError('Сумма должна быть больше нуля.');
                amountInput.focus();
                return;
            }
            await requestWithToast(
                () => postJson('/api/transactions', data),
                config.type === 'income' ? 'Доход добавлен' : 'Расход добавлен'
            );
            closeModal(modal);
            if (onSaved) {
                await onSaved();
            }
        });

        return {
            open: async () => {
                await setDefaults();
                openModal(modal);
            },
        };
    };

    const instances = configs.map((config) => setupModal(config));

    const incomeBtn = byId('add-income-btn');
    const expenseBtn = byId('add-expense-btn');

    if (incomeBtn && instances[0]) {
        incomeBtn.addEventListener('click', () => instances[0].open());
    }
    if (expenseBtn && instances[1]) {
        expenseBtn.addEventListener('click', () => instances[1].open());
    }
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
    const lineCtx = byId('dashboard-line');
    const categoryCtx = byId('dashboard-category');
    const monthlyCtx = byId('dashboard-monthly');
    const categoryList = byId('dashboard-category-list');
    let lineChart;
    let categoryChart;
    let monthlyChart;

    const formatDate = (date) => date.toISOString().slice(0, 10);

    const loadDashboard = async () => {
        const chartLib = await ensureChart();
        const now = new Date();
        const month = now.toISOString().slice(0, 7);
        const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
        const dateFrom = `${month}-01`;
        const dateTo = `${month}-${String(lastDay).padStart(2, '0')}`;

        const startMonth = new Date(now.getFullYear(), now.getMonth() - 5, 1);
        const endMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0);

        const [summaryResult, txResult, categoryResult, dailyResult, monthlyResult] =
            await Promise.allSettled([
                getJson(`/api/reports/summary?dateFrom=${dateFrom}&dateTo=${dateTo}`),
                getJson('/api/transactions?limit=5'),
                getJson(`/api/reports/expense-by-category?month=${month}`),
                getJson(`/api/reports/dynamics?dateFrom=${dateFrom}&dateTo=${dateTo}&groupBy=day`),
                monthlyCtx
                    ? getJson(
                          `/api/reports/dynamics?dateFrom=${formatDate(startMonth)}&dateTo=${formatDate(
                              endMonth
                          )}&groupBy=month&type=expense`
                      )
                    : Promise.resolve({ labels: [], expense: [] }),
            ]);

        if (
            summaryResult.status === 'rejected' ||
            txResult.status === 'rejected' ||
            categoryResult.status === 'rejected' ||
            dailyResult.status === 'rejected' ||
            monthlyResult.status === 'rejected'
        ) {
            showError('Не удалось загрузить все данные дашборда.');
        }

        const summary =
            summaryResult.status === 'fulfilled'
                ? summaryResult.value
                : { balance: 0, income: 0, expense: 0, net: 0 };
        const tx =
            txResult.status === 'fulfilled'
                ? txResult.value
                : { transactions: [] };
        const categoryData =
            categoryResult.status === 'fulfilled'
                ? categoryResult.value
                : { items: [] };
        const daily =
            dailyResult.status === 'fulfilled'
                ? dailyResult.value
                : { labels: [], income: [], expense: [] };
        const monthly =
            monthlyResult.status === 'fulfilled'
                ? monthlyResult.value
                : { labels: [], expense: [] };

        setText('summary-balance', formatCurrency(summary.balance));
        setText('summary-income', formatCurrency(summary.income));
        setText('summary-expense', formatCurrency(summary.expense));
        setText('summary-net', formatCurrency(summary.net));
        setText('summary-net-note', summary.net >= 0 ? 'профицит' : 'дефицит');
        setText('summary-balance-note', summary.balance >= 0 ? 'по всем счетам' : 'минус');

        setText('summary-average-expense', formatCurrency(summary.expense / lastDay));
        setText('summary-average-expense-note', `в ${lastDay} днях месяца`);

        const topCategory = categoryData.items[0];
        setText('summary-top-category', topCategory ? topCategory.name : '—');
        setText('summary-top-category-amount', topCategory ? formatCurrency(topCategory.total) : 'нет данных');

        const savingsRate = summary.income > 0 ? (summary.expense / summary.income) * 100 : 0;
        setText('summary-savings-rate', `${savingsRate.toFixed(1)}%`);
        setText('summary-savings-rate-note', summary.expense <= summary.income ? 'в пределах бюджета' : 'перерасход');

        setText('summary-month-expense', formatCurrency(summary.expense));

        const rows = (tx.transactions || []).map((item) => [
            new Date(item.tx_date).toLocaleDateString('ru-RU'),
            item.tx_type === 'income' ? 'Доход' : 'Расход',
            item.category_name ?? 'Без категории',
            item.account_name ?? '—',
            formatCurrency(item.amount),
        ]);

        renderTable(byId('dashboard-transactions'), ['Дата', 'Тип', 'Категория', 'Счёт', 'Сумма'], rows);

        if (categoryList) {
            categoryList.innerHTML = '';
            if (categoryData.items.length === 0) {
                const empty = document.createElement('p');
                empty.className = 'text-muted';
                empty.textContent = 'Нет расходов за период';
                categoryList.appendChild(empty);
            } else {
                categoryData.items.slice(0, 5).forEach((item) => {
                    const row = document.createElement('div');
                    row.className = 'stat-item';
                    row.innerHTML = `<span>${item.name}</span><span class="stat-item__value">${formatCurrency(
                        item.total
                    )}</span>`;
                    categoryList.appendChild(row);
                });
            }
        }

        if (lineCtx && chartLib) {
            if (lineChart) {
                lineChart.destroy();
            }
            lineChart = new chartLib(lineCtx, {
                type: 'line',
                data: {
                    labels: daily.labels,
                    datasets: [
                        {
                            label: 'Доходы',
                            data: daily.income,
                            borderColor: '#2f7a4d',
                            backgroundColor: 'rgba(47, 122, 77, 0.15)',
                            tension: 0.3,
                            fill: true,
                        },
                        {
                            label: 'Расходы',
                            data: daily.expense,
                            borderColor: '#b42318',
                            backgroundColor: 'rgba(180, 35, 24, 0.1)',
                            tension: 0.3,
                            fill: true,
                        },
                    ],
                },
                options: {
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                    },
                },
            });
        }

        if (categoryCtx && chartLib) {
            if (categoryChart) {
                categoryChart.destroy();
            }
            categoryChart = new chartLib(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: categoryData.items.map((item) => item.name),
                    datasets: [
                        {
                            data: categoryData.items.map((item) => item.total),
                            backgroundColor: ['#2f7a4d', '#4ecf7d', '#6fdd9d', '#b6f0c9', '#d9f7e3', '#2f9e6c'],
                        },
                    ],
                },
                options: {
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                    },
                    cutout: '70%',
                },
            });
        }

        if (monthlyCtx && chartLib) {
            if (monthlyChart) {
                monthlyChart.destroy();
            }
            const monthLabels = monthly.labels.map((label) => {
                const [year, monthValue] = label.split('-');
                const date = new Date(Number(year), Number(monthValue) - 1, 1);
                return date.toLocaleDateString('ru-RU', { month: 'short', year: 'numeric' });
            });
            monthlyChart = new chartLib(monthlyCtx, {
                type: 'bar',
                data: {
                    labels: monthLabels,
                    datasets: [
                        {
                            label: 'Расходы',
                            data: monthly.expense,
                            backgroundColor: 'rgba(47, 122, 77, 0.5)',
                            borderColor: '#2f7a4d',
                            borderWidth: 1,
                            borderRadius: 8,
                        },
                    ],
                },
                options: {
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                    },
                },
            });
        }
    };

    const transactionModal = await initTransactionModal();
    if (transactionModal) {
        transactionModal.setOnSaved(loadDashboard);
    }
    await initIncomeExpenseModals({ onSaved: loadDashboard });
    initTransferModal({ onSaved: loadDashboard });
    await loadDashboard();
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
                    await requestWithToast(
                        () => deleteJson(`/api/accounts/${acc.account_id}`),
                        'Счёт скрыт'
                    );
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
            await requestWithToast(
                () => putJson(`/api/accounts/${id}`, data),
                'Счёт обновлён'
            );
        } else {
            await requestWithToast(() => postJson('/api/accounts', data), 'Счёт создан');
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
                    await requestWithToast(
                        () => deleteJson(`/api/categories/${cat.category_id}`),
                        'Категория скрыта'
                    );
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
            await requestWithToast(
                () => putJson(`/api/categories/${id}`, data),
                'Категория обновлена'
            );
        } else {
            await requestWithToast(() => postJson('/api/categories', data), 'Категория создана');
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
    const resetBtn = byId('transactions-reset');
    const transfersTable = byId('transfers-table');

    const accounts = await getJson('/api/accounts');
    const accountOptions = accounts.accounts.map((acc) => ({ value: acc.account_id, label: acc.name }));

    fillSelect(byId('filter-account'), accountOptions, 'Все');

    const loadCategories = async (type) => {
        const url = type ? `/api/categories?type=${type}` : '/api/categories';
        const { categories } = await getJson(url);
        return categories.map((cat) => ({ value: cat.category_id, label: cat.name }));
    };

    const refreshFilterCategories = async () => {
        const type = filterForm.querySelector('[name="type"]').value;
        const options = await loadCategories(type);
        fillSelect(byId('filter-category'), options, 'Все');
    };

    let transactionModal = null;

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
                    transactionModal?.open({ transaction: tx });
                });

                const deleteBtn = document.createElement('button');
                deleteBtn.className = 'btn btn-outline btn-sm';
                deleteBtn.textContent = 'Удалить';
                deleteBtn.addEventListener('click', async () => {
                    await requestWithToast(
                        () => deleteJson(`/api/transactions/${tx.transaction_id}`),
                        'Операция удалена'
                    );
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
                    await requestWithToast(
                        () => deleteJson(`/api/transfers/${tr.transfer_id}`),
                        'Перевод удалён'
                    );
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

    await refreshFilterCategories();

    filterForm.querySelector('[name="type"]').addEventListener('change', refreshFilterCategories);

    filterForm.addEventListener('input', loadTransactions);

    resetBtn.addEventListener('click', () => {
        filterForm.reset();
        refreshFilterCategories();
        loadTransactions();
    });

    transactionModal = await initTransactionModal();
    if (transactionModal) {
        transactionModal.setOnSaved(loadTransactions);
    }
    await initIncomeExpenseModals({ onSaved: loadTransactions });
    initTransferModal({ onSaved: loadTransfers });

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
                    await requestWithToast(
                        () => deleteJson(`/api/budgets/${b.budget_id}`),
                        'Бюджет удалён'
                    );
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
            await requestWithToast(
                () => putJson(`/api/budgets/${id}`, data),
                'Бюджет обновлён'
            );
        } else {
            await requestWithToast(() => postJson('/api/budgets', data), 'Бюджет создан');
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
                await requestWithToast(
                    () => deleteJson(`/api/goals/${goal.goal_id}`),
                    'Цель удалена'
                );
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
            await requestWithToast(() => putJson(`/api/goals/${id}`, data), 'Цель обновлена');
        } else {
            await requestWithToast(() => postJson('/api/goals', data), 'Цель добавлена');
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
