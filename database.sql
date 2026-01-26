CREATE DATABASE IF NOT EXISTS myfinances CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE myfinances;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (name, email, password)
VALUES ('Test User', 'test.user@example.com', '$2y$12$LCWl.bnVsN6t0NFeGpUd7ui/cXO6WomsixXIJ5.HjvoDLUma3EaNK');
