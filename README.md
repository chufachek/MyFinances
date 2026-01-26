# МоиФинансы

Полностью рабочий учебный проект для учета личных финансов: операции, счета, категории, переводы, бюджеты, цели и отчеты.

## Стек

- PHP 8.x + MySQL 8.x
- Bramus Router для маршрутизации
- Чистый JS (fetch/AJAX)
- Chart.js для графиков

## Быстрый запуск

1. Установите PHP 8.x и MySQL 8.x.
2. Создайте базу данных `my_finances` и импортируйте `database.sql`.
3. Обновите доступы к БД в `config/db.php`.
4. Запустите сервер:

```bash
php -S localhost:8000 -t public
```

5. Откройте в браузере: <http://localhost:8000>

## Демо-доступ

После импорта `database.sql` доступен тестовый пользователь:

- **Email:** `demo@myfinances.local`
- **Пароль:** `demo1234`

## Структура проекта

```
public/          # index.php, .htaccess, публичные ассеты
app/             # контроллеры, сервисы
app/Views/       # шаблоны страниц
assets/          # исходники фронта
assets/styles/   # стили
assets/js/       # JS-модули
config/          # конфиг БД
database.sql     # схема + сиды
```

## Маршруты

### Страницы

- `/login`
- `/register`
- `/dashboard`
- `/transactions`
- `/accounts`
- `/categories`
- `/budgets`
- `/goals`
- `/reports`

### API

- `/api/auth/register`
- `/api/auth/login`
- `/api/auth/logout`
- `/api/accounts`
- `/api/categories`
- `/api/transactions`
- `/api/transfers`
- `/api/budgets`
- `/api/goals`
- `/api/reports/summary`
- `/api/reports/expense-by-category`
- `/api/reports/dynamics`
