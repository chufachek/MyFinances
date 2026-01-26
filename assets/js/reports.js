const reportsRoot = document.getElementById('reports');
const summaryUrl = reportsRoot?.dataset.summaryUrl;
const expenseUrl = reportsRoot?.dataset.expenseUrl;
const dynamicsUrl = reportsRoot?.dataset.dynamicsUrl;

const startInput = document.getElementById('startDate');
const endInput = document.getElementById('endDate');
const applyButton = document.getElementById('applyFilters');

const totalIncome = document.getElementById('totalIncome');
const totalExpense = document.getElementById('totalExpense');
const balance = document.getElementById('balance');

let expenseChart;
let dynamicsChart;

const defaultStart = new Date();
const defaultEnd = new Date();

defaultStart.setDate(1);

defaultEnd.setMonth(defaultEnd.getMonth() + 1, 0);

const formatDateInput = (date) => date.toISOString().slice(0, 10);

if (startInput && endInput) {
    startInput.value = formatDateInput(defaultStart);
    endInput.value = formatDateInput(defaultEnd);
}

const formatCurrency = (value) => `${value.toLocaleString('ru-RU')} ₽`;

const buildQuery = () => {
    const params = new URLSearchParams();
    if (startInput?.value) {
        params.set('start', startInput.value);
    }
    if (endInput?.value) {
        params.set('end', endInput.value);
    }
    return params.toString();
};

const fetchJson = async (url) => {
    const response = await fetch(url);
    if (!response.ok) {
        throw new Error(`Request failed: ${response.status}`);
    }
    return response.json();
};

const updateSummary = (data) => {
    if (!totalIncome || !totalExpense || !balance) {
        return;
    }

    totalIncome.textContent = formatCurrency(data.income ?? 0);
    totalExpense.textContent = formatCurrency(data.expense ?? 0);
    balance.textContent = formatCurrency(data.balance ?? 0);
};

const renderExpenseChart = (data) => {
    const ctx = document.getElementById('expenseChart');
    if (!ctx) {
        return;
    }

    const labels = data.items?.map((item) => item.category) ?? [];
    const values = data.items?.map((item) => item.total) ?? [];

    if (expenseChart) {
        expenseChart.data.labels = labels;
        expenseChart.data.datasets[0].data = values;
        expenseChart.update();
        return;
    }

    expenseChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [
                {
                    data: values,
                    backgroundColor: [
                        '#2f6fed',
                        '#38bdf8',
                        '#f97316',
                        '#facc15',
                        '#10b981',
                        '#f43f5e',
                        '#a855f7',
                    ],
                    borderWidth: 0,
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
};

const renderDynamicsChart = (data) => {
    const ctx = document.getElementById('dynamicsChart');
    if (!ctx) {
        return;
    }

    const labels = data.labels ?? [];
    const income = data.income ?? [];
    const expense = data.expense ?? [];

    if (dynamicsChart) {
        dynamicsChart.data.labels = labels;
        dynamicsChart.data.datasets[0].data = income;
        dynamicsChart.data.datasets[1].data = expense;
        dynamicsChart.update();
        return;
    }

    dynamicsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Доходы',
                    data: income,
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34, 197, 94, 0.2)',
                    tension: 0.3,
                    fill: true,
                },
                {
                    label: 'Расходы',
                    data: expense,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.2)',
                    tension: 0.3,
                    fill: true,
                },
            ],
        },
        options: {
            interaction: {
                intersect: false,
                mode: 'index',
            },
            scales: {
                y: {
                    beginAtZero: true,
                },
            },
        },
    });
};

const loadReports = async () => {
    if (!summaryUrl || !expenseUrl || !dynamicsUrl) {
        return;
    }

    const query = buildQuery();
    const querySuffix = query ? `?${query}` : '';

    const [summaryData, expenseData, dynamicsData] = await Promise.all([
        fetchJson(`${summaryUrl}${querySuffix}`),
        fetchJson(`${expenseUrl}${querySuffix}`),
        fetchJson(`${dynamicsUrl}${querySuffix}`),
    ]);

    updateSummary(summaryData);
    renderExpenseChart(expenseData);
    renderDynamicsChart(dynamicsData);
};

if (applyButton) {
    applyButton.addEventListener('click', () => {
        loadReports().catch((error) => {
            console.error(error);
        });
    });
}

loadReports().catch((error) => {
    console.error(error);
});
