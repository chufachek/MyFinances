const sampleTables = {
    transactions: [
        ['Дата', 'Категория', 'Счёт', 'Сумма'],
        ['12.07', 'Продукты', 'Дебетовая карта', '- ₽ 2 450'],
        ['11.07', 'Кафе', 'Дебетовая карта', '- ₽ 860'],
        ['10.07', 'Зарплата', 'Основной счёт', '+ ₽ 120 000'],
    ],
    categories: [
        ['Категория', 'Лимит', 'Использовано', 'Статус'],
        ['Еда', '20 000', '12 500', '62%'],
        ['Транспорт', '10 000', '6 800', '68%'],
        ['Развлечения', '8 000', '4 200', '53%'],
    ],
    'top-categories': [
        ['Категория', 'Сумма', 'Доля', 'Тренд'],
        ['Дом', '18 200', '35%', '+2%'],
        ['Еда', '12 500', '25%', '-1%'],
        ['Транспорт', '9 300', '18%', '+3%'],
    ],
};

export function renderTable(container, rows) {
    const table = document.createElement('div');
    table.className = 'table';

    rows.forEach((row, index) => {
        const rowEl = document.createElement('div');
        rowEl.className = `table__row${index === 0 ? ' table__row--header' : ''}`;
        row.forEach((cell) => {
            const cellEl = document.createElement('div');
            cellEl.textContent = cell;
            rowEl.appendChild(cellEl);
        });
        table.appendChild(rowEl);
    });

    container.innerHTML = '';
    container.appendChild(table);
}

export function initWidgets() {
    document.querySelectorAll('[data-widget]').forEach((node) => {
        const widget = node.dataset.widget;
        if (!widget || !(widget in sampleTables)) {
            return;
        }
        renderTable(node, sampleTables[widget]);
    });
}
