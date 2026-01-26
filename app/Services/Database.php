<?php

namespace App\Services;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;

    public static function connection()
    {
        if (self::$instance) {
            return self::$instance;
        }

        $config = require BASE_PATH . '/config/db.php';
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['dbname'],
            $config['charset']
        );

        try {
            $pdo = new PDO($dsn, $config['user'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            http_response_code(500);
            echo 'Database connection failed.';
            exit;
        }

        self::$instance = $pdo;
        return self::$instance;
    }
}
