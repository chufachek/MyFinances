# MyFinances

## Требования

- PHP 8
- MySQL 8

## Установка

1. Импортируйте схему и сиды:
   ```bash
   mysql -u root -p < database.sql
   ```
2. Настройте подключение к базе данных в `config/db.php` (host, user, password, dbname).
3. Запустите встроенный сервер PHP из корня проекта:
   ```bash
   php -S localhost:8000 -t public
   ```

## Тестовые доступы

- Email: `test.user@example.com`
- Пароль: `password`

> Учетная запись создается сидом в `database.sql`.
