-- Schema
CREATE TABLE users (
  id SERIAL PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE accounts (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  name VARCHAR(100) NOT NULL,
  account_type VARCHAR(50) NOT NULL,
  currency CHAR(3) NOT NULL,
  balance NUMERIC(12, 2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (user_id, name)
);

CREATE TABLE categories (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  name VARCHAR(100) NOT NULL,
  category_type VARCHAR(20) NOT NULL CHECK (category_type IN ('income', 'expense')),
  UNIQUE (user_id, name)
);

CREATE TABLE merchants (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  name VARCHAR(150) NOT NULL,
  UNIQUE (user_id, name)
);

CREATE TABLE transactions (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  account_id INTEGER NOT NULL REFERENCES accounts(id) ON DELETE CASCADE,
  category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
  merchant_id INTEGER REFERENCES merchants(id) ON DELETE SET NULL,
  tx_date DATE NOT NULL,
  amount NUMERIC(12, 2) NOT NULL CHECK (amount > 0),
  direction VARCHAR(10) NOT NULL CHECK (direction IN ('debit', 'credit')),
  note VARCHAR(255)
);

CREATE TABLE transfers (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  from_account_id INTEGER NOT NULL REFERENCES accounts(id) ON DELETE CASCADE,
  to_account_id INTEGER NOT NULL REFERENCES accounts(id) ON DELETE CASCADE,
  tx_date DATE NOT NULL,
  amount NUMERIC(12, 2) NOT NULL CHECK (amount > 0),
  note VARCHAR(255),
  CHECK (from_account_id <> to_account_id)
);

CREATE TABLE budgets (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
  amount NUMERIC(12, 2) NOT NULL CHECK (amount > 0),
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  CHECK (end_date >= start_date),
  UNIQUE (user_id, category_id, start_date, end_date)
);

CREATE TABLE goals (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  name VARCHAR(120) NOT NULL,
  target_amount NUMERIC(12, 2) NOT NULL CHECK (target_amount > 0),
  current_amount NUMERIC(12, 2) NOT NULL DEFAULT 0 CHECK (current_amount >= 0),
  target_date DATE NOT NULL,
  UNIQUE (user_id, name)
);

CREATE INDEX idx_accounts_user_id ON accounts(user_id);
CREATE INDEX idx_categories_user_id ON categories(user_id);
CREATE INDEX idx_merchants_user_id ON merchants(user_id);
CREATE INDEX idx_transactions_user_id ON transactions(user_id);
CREATE INDEX idx_transactions_account_id ON transactions(account_id);
CREATE INDEX idx_transactions_category_id ON transactions(category_id);
CREATE INDEX idx_transactions_tx_date ON transactions(tx_date);
CREATE INDEX idx_transfers_user_id ON transfers(user_id);
CREATE INDEX idx_transfers_tx_date ON transfers(tx_date);
CREATE INDEX idx_budgets_user_id ON budgets(user_id);
CREATE INDEX idx_goals_user_id ON goals(user_id);

-- Seed data
INSERT INTO users (id, full_name, email) VALUES
  (1, 'Анна Петрова', 'anna.pet@example.com');

INSERT INTO accounts (id, user_id, name, account_type, currency, balance) VALUES
  (1, 1, 'Основная карта', 'debit', 'RUB', 85000.00),
  (2, 1, 'Кэш', 'cash', 'RUB', 12000.00),
  (3, 1, 'Накопительный счет', 'savings', 'RUB', 210000.00),
  (4, 1, 'Кредитная карта', 'credit', 'RUB', 15000.00);

INSERT INTO categories (id, user_id, name, category_type) VALUES
  (1, 1, 'Зарплата', 'income'),
  (2, 1, 'Фриланс', 'income'),
  (3, 1, 'Продукты', 'expense'),
  (4, 1, 'Транспорт', 'expense'),
  (5, 1, 'Кафе', 'expense'),
  (6, 1, 'Жилье', 'expense'),
  (7, 1, 'Развлечения', 'expense'),
  (8, 1, 'Здоровье', 'expense');

INSERT INTO merchants (id, user_id, name) VALUES
  (1, 1, 'Пятерочка'),
  (2, 1, 'Яндекс Такси'),
  (3, 1, 'Starbucks'),
  (4, 1, 'Кинотеатр'),
  (5, 1, 'Аптека 36.6'),
  (6, 1, 'ДомОфис');

INSERT INTO transactions (id, user_id, account_id, category_id, merchant_id, tx_date, amount, direction, note) VALUES
  (1, 1, 1, 1, NULL, '2024-01-05', 120000.00, 'credit', 'Зарплата'),
  (2, 1, 1, 3, 1, '2024-01-06', 2450.35, 'debit', 'Покупка продуктов'),
  (3, 1, 1, 4, 2, '2024-01-07', 620.00, 'debit', 'Такси до работы'),
  (4, 1, 2, 5, 3, '2024-01-08', 480.00, 'debit', 'Кофе'),
  (5, 1, 1, 6, 6, '2024-01-10', 35000.00, 'debit', 'Аренда'),
  (6, 1, 1, 7, 4, '2024-01-12', 950.00, 'debit', 'Кино'),
  (7, 1, 1, 8, 5, '2024-01-13', 1200.00, 'debit', 'Аптека'),
  (8, 1, 1, 3, 1, '2024-01-15', 3890.20, 'debit', 'Продукты на неделю'),
  (9, 1, 2, 4, 2, '2024-01-16', 300.00, 'debit', 'Такси домой'),
  (10, 1, 1, 2, NULL, '2024-01-18', 15000.00, 'credit', 'Фриланс проект'),
  (11, 1, 1, 3, 1, '2024-01-20', 2750.00, 'debit', 'Продукты'),
  (12, 1, 4, 5, 3, '2024-01-21', 680.00, 'debit', 'Кофе и десерт'),
  (13, 1, 4, 7, 4, '2024-01-22', 1200.00, 'debit', 'Фильм'),
  (14, 1, 1, 4, 2, '2024-01-24', 540.00, 'debit', 'Такси аэропорт'),
  (15, 1, 1, 3, 1, '2024-01-25', 4100.00, 'debit', 'Закупка продуктов'),
  (16, 1, 2, 5, 3, '2024-01-26', 520.00, 'debit', 'Кофе'),
  (17, 1, 1, 8, 5, '2024-01-27', 890.00, 'debit', 'Витамины'),
  (18, 1, 3, 1, NULL, '2024-02-05', 120000.00, 'credit', 'Зарплата на накопительный'),
  (19, 1, 1, 6, 6, '2024-02-10', 35000.00, 'debit', 'Аренда февраль'),
  (20, 1, 1, 3, 1, '2024-02-12', 2300.00, 'debit', 'Продукты'),
  (21, 1, 4, 7, 4, '2024-02-14', 1500.00, 'debit', 'Концерт'),
  (22, 1, 1, 4, 2, '2024-02-15', 710.00, 'debit', 'Такси'),
  (23, 1, 2, 5, 3, '2024-02-18', 540.00, 'debit', 'Капучино'),
  (24, 1, 1, 2, NULL, '2024-02-20', 18000.00, 'credit', 'Фриланс'),
  (25, 1, 1, 3, 1, '2024-02-22', 3200.00, 'debit', 'Продукты'),
  (26, 1, 1, 8, 5, '2024-02-25', 1450.00, 'debit', 'Аптека');

INSERT INTO transfers (id, user_id, from_account_id, to_account_id, tx_date, amount, note) VALUES
  (1, 1, 1, 3, '2024-01-11', 20000.00, 'Перевод в накопления'),
  (2, 1, 1, 2, '2024-01-19', 5000.00, 'Наличные'),
  (3, 1, 3, 1, '2024-02-08', 15000.00, 'Возврат на карту');

INSERT INTO budgets (id, user_id, category_id, amount, start_date, end_date) VALUES
  (1, 1, 3, 30000.00, '2024-01-01', '2024-01-31'),
  (2, 1, 4, 7000.00, '2024-01-01', '2024-01-31');

INSERT INTO goals (id, user_id, name, target_amount, current_amount, target_date) VALUES
  (1, 1, 'Отпуск летом', 150000.00, 40000.00, '2024-07-01'),
  (2, 1, 'Подушка безопасности', 300000.00, 120000.00, '2024-12-31');
