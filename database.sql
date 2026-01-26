-- MyFinances database schema + seed data
CREATE TABLE users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(120) DEFAULT NULL,
  status ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE accounts (
  account_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  account_type ENUM('cash', 'card', 'bank', 'other') NOT NULL DEFAULT 'card',
  currency_code CHAR(3) NOT NULL DEFAULT 'RUB',
  initial_balance DECIMAL(12,2) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_accounts_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  UNIQUE KEY uniq_accounts_user_name (user_id, name),
  KEY idx_accounts_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
  category_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  parent_category_id INT DEFAULT NULL,
  name VARCHAR(120) NOT NULL,
  category_type ENUM('income', 'expense') NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_categories_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_categories_parent FOREIGN KEY (parent_category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
  UNIQUE KEY uniq_categories_user_name (user_id, name),
  KEY idx_categories_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE merchants (
  merchant_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  CONSTRAINT fk_merchants_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  UNIQUE KEY uniq_merchants_user_name (user_id, name),
  KEY idx_merchants_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE transactions (
  transaction_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  account_id INT NOT NULL,
  category_id INT DEFAULT NULL,
  merchant_id INT DEFAULT NULL,
  tx_type ENUM('income', 'expense') NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  tx_date DATETIME NOT NULL,
  note VARCHAR(255) DEFAULT NULL,
  receipt_path VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_transactions_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_transactions_account FOREIGN KEY (account_id) REFERENCES accounts(account_id) ON DELETE CASCADE,
  CONSTRAINT fk_transactions_category FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
  CONSTRAINT fk_transactions_merchant FOREIGN KEY (merchant_id) REFERENCES merchants(merchant_id) ON DELETE SET NULL,
  KEY idx_transactions_user_date (user_id, tx_date),
  KEY idx_transactions_user_category (user_id, category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE transfers (
  transfer_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  from_account_id INT NOT NULL,
  to_account_id INT NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  fee DECIMAL(12,2) NOT NULL DEFAULT 0,
  tx_date DATETIME NOT NULL,
  note VARCHAR(255) DEFAULT NULL,
  CONSTRAINT fk_transfers_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_transfers_from FOREIGN KEY (from_account_id) REFERENCES accounts(account_id) ON DELETE CASCADE,
  CONSTRAINT fk_transfers_to FOREIGN KEY (to_account_id) REFERENCES accounts(account_id) ON DELETE CASCADE,
  CONSTRAINT chk_transfers_accounts CHECK (from_account_id <> to_account_id),
  KEY idx_transfers_user_date (user_id, tx_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE budgets (
  budget_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  category_id INT NOT NULL,
  period_month VARCHAR(7) NOT NULL,
  limit_amount DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_budgets_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_budgets_category FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE,
  UNIQUE KEY uniq_budgets_user_period (user_id, category_id, period_month),
  KEY idx_budgets_user_period (user_id, period_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE goals (
  goal_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  target_amount DECIMAL(12,2) NOT NULL,
  current_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  due_date DATE DEFAULT NULL,
  status ENUM('active', 'done', 'canceled') NOT NULL DEFAULT 'active',
  CONSTRAINT fk_goals_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  UNIQUE KEY uniq_goals_user_name (user_id, name),
  KEY idx_goals_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (user_id, email, password_hash, full_name) VALUES
  (1, 'demo@myfinances.local', '$2y$12$XHXuFpCOZ5HpRpt1RHsqjepFHXjha7iYOaCraVPJ8/F38trEYexGq', 'Анна Петрова');

INSERT INTO accounts (account_id, user_id, name, account_type, currency_code, initial_balance, is_active) VALUES
  (1, 1, 'Наличные', 'cash', 'RUB', 12000.00, 1),
  (2, 1, 'Карта Сбер', 'card', 'RUB', 85000.00, 1),
  (3, 1, 'Карта Тинькофф', 'card', 'RUB', 25000.00, 1),
  (4, 1, 'Накопительный', 'bank', 'RUB', 210000.00, 1);

INSERT INTO categories (category_id, user_id, name, category_type, is_active) VALUES
  (1, 1, 'Зарплата', 'income', 1),
  (2, 1, 'Фриланс', 'income', 1),
  (3, 1, 'Подарки', 'income', 1),
  (4, 1, 'Продукты', 'expense', 1),
  (5, 1, 'Транспорт', 'expense', 1),
  (6, 1, 'Кафе', 'expense', 1),
  (7, 1, 'Жильё', 'expense', 1),
  (8, 1, 'Развлечения', 'expense', 1),
  (9, 1, 'Здоровье', 'expense', 1),
  (10, 1, 'Образование', 'expense', 1);

INSERT INTO merchants (merchant_id, user_id, name) VALUES
  (1, 1, 'Пятёрочка'),
  (2, 1, 'Яндекс Такси'),
  (3, 1, 'Coffee Point'),
  (4, 1, 'Кинотеатр'),
  (5, 1, 'Аптека 36.6');

INSERT INTO transactions (transaction_id, user_id, account_id, category_id, merchant_id, tx_type, amount, tx_date, note) VALUES
  (1, 1, 2, 1, NULL, 'income', 120000.00, '2024-02-01 10:00:00', 'Зарплата'),
  (2, 1, 2, 4, 1, 'expense', 2450.35, '2024-02-02 18:00:00', 'Продукты на неделю'),
  (3, 1, 2, 5, 2, 'expense', 620.00, '2024-02-03 09:00:00', 'Такси до работы'),
  (4, 1, 1, 6, 3, 'expense', 480.00, '2024-02-04 11:00:00', 'Кофе'),
  (5, 1, 2, 7, NULL, 'expense', 35000.00, '2024-02-05 12:00:00', 'Аренда'),
  (6, 1, 2, 8, 4, 'expense', 950.00, '2024-02-06 20:00:00', 'Кино'),
  (7, 1, 2, 9, 5, 'expense', 1200.00, '2024-02-07 15:00:00', 'Аптека'),
  (8, 1, 2, 4, 1, 'expense', 3890.20, '2024-02-08 19:00:00', 'Продукты'),
  (9, 1, 1, 5, 2, 'expense', 300.00, '2024-02-09 22:00:00', 'Такси'),
  (10, 1, 2, 2, NULL, 'income', 15000.00, '2024-02-10 08:30:00', 'Фриланс проект'),
  (11, 1, 2, 4, 1, 'expense', 2750.00, '2024-02-12 18:20:00', 'Продукты'),
  (12, 1, 3, 6, 3, 'expense', 680.00, '2024-02-13 10:10:00', 'Кофе и десерт'),
  (13, 1, 3, 8, 4, 'expense', 1200.00, '2024-02-14 21:00:00', 'Фильм'),
  (14, 1, 2, 5, 2, 'expense', 540.00, '2024-02-15 07:00:00', 'Такси аэропорт'),
  (15, 1, 2, 4, 1, 'expense', 4100.00, '2024-02-16 19:10:00', 'Закупка продуктов'),
  (16, 1, 1, 6, 3, 'expense', 520.00, '2024-02-18 12:15:00', 'Кофе'),
  (17, 1, 2, 9, 5, 'expense', 890.00, '2024-02-19 11:00:00', 'Витамины'),
  (18, 1, 4, 1, NULL, 'income', 120000.00, '2024-02-20 10:00:00', 'Зарплата на накопительный'),
  (19, 1, 2, 7, NULL, 'expense', 35000.00, '2024-02-21 12:00:00', 'Аренда февраль'),
  (20, 1, 2, 4, 1, 'expense', 2300.00, '2024-02-22 19:00:00', 'Продукты'),
  (21, 1, 3, 8, 4, 'expense', 1500.00, '2024-02-23 20:30:00', 'Концерт'),
  (22, 1, 2, 5, 2, 'expense', 710.00, '2024-02-24 09:15:00', 'Такси'),
  (23, 1, 1, 6, 3, 'expense', 540.00, '2024-02-25 14:10:00', 'Капучино'),
  (24, 1, 2, 2, NULL, 'income', 18000.00, '2024-02-26 08:30:00', 'Фриланс'),
  (25, 1, 2, 4, 1, 'expense', 3200.00, '2024-02-27 19:00:00', 'Продукты'),
  (26, 1, 2, 9, 5, 'expense', 1450.00, '2024-02-28 17:00:00', 'Аптека');

INSERT INTO transfers (transfer_id, user_id, from_account_id, to_account_id, amount, fee, tx_date, note) VALUES
  (1, 1, 2, 4, 20000.00, 0.00, '2024-02-05 13:00:00', 'Перевод в накопления'),
  (2, 1, 2, 1, 5000.00, 0.00, '2024-02-11 18:00:00', 'Наличные'),
  (3, 1, 4, 2, 15000.00, 0.00, '2024-02-17 09:00:00', 'Возврат на карту');

INSERT INTO budgets (budget_id, user_id, category_id, period_month, limit_amount) VALUES
  (1, 1, 4, '2024-02', 30000.00),
  (2, 1, 5, '2024-02', 7000.00),
  (3, 1, 6, '2024-02', 6000.00);

INSERT INTO goals (goal_id, user_id, name, target_amount, current_amount, due_date, status) VALUES
  (1, 1, 'Отпуск летом', 150000.00, 40000.00, '2024-07-01', 'active'),
  (2, 1, 'Подушка безопасности', 300000.00, 120000.00, '2024-12-31', 'active');
