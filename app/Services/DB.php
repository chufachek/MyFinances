<?php

class DB
{
    public static $dsn = 'mysql:dbname=' . DB_NAME . ';host=' . DB_HOST;
    public static $user = DB_USER;
    public static $pass = DB_PASS;

    public static $dbh = null;
    public static $sth = null;

    public static $query = '';

    public static function getDbh()
    {
        if (!self::$dbh) {
            try {
                self::$dbh = new PDO(
                    self::$dsn,
                    self::$user,
                    self::$pass,
                    [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"]
                );
                self::$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                exit('Error connecting to database: ' . $e->getMessage());
            }
        }

        return self::$dbh;
    }

    public static function destroy()
    {
        self::$dbh = null;
        return self::$dbh;
    }

    public static function getError()
    {
        $info = self::$sth->errorInfo();
        return (isset($info[2])) ? 'SQL: ' . $info[2] : null;
    }

    private static function isMissingTableError(PDOException $exception): bool
    {
        return $exception->getCode() === '42S02';
    }

    private static function executeQuery($query, $param = [])
    {
        self::$sth = self::getDbh()->prepare($query);
        try {
            return self::$sth->execute((array) $param);
        } catch (PDOException $exception) {
            if (self::isMissingTableError($exception)) {
                return false;
            }
            throw $exception;
        }
    }

    public static function getStructure($table)
    {
        $res = [];
        foreach (self::getAll("SHOW COLUMNS FROM {$table}") as $row) {
            $res[$row['Field']] = (is_null($row['Default'])) ? '' : $row['Default'];
        }

        return $res;
    }

    /**
     * Добавление в таблицу, в случаи успеха вернет вставленный ID, иначе 0.
     */
    public static function add($query, $param = [])
    {
        return (self::executeQuery($query, $param)) ? self::getDbh()->lastInsertId() : 0;
    }

    /**
     * Выполнение запроса.
     */
    public static function set($query, $param = [])
    {
        return self::executeQuery($query, $param);
    }

    /**
     * Получение строки из таблицы.
     */
    public static function getRow($query, $param = [])
    {
        if (!self::executeQuery($query, $param)) {
            return [];
        }
        return self::$sth->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Получение всех строк из таблицы.
     */
    public static function getAll($query, $param = [])
    {
        if (!self::executeQuery($query, $param)) {
            return [];
        }
        return self::$sth->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Получение значения.
     */
    public static function getValue($query, $param = [], $default = null)
    {
        $result = self::getRow($query, $param);
        if (!empty($result)) {
            $result = array_shift($result);
        }

        return (empty($result)) ? $default : $result;
    }

    /**
     * Получение столбца таблицы.
     */
    public static function getColumn($query, $param = [])
    {
        if (!self::executeQuery($query, $param)) {
            return [];
        }
        return self::$sth->fetchAll(PDO::FETCH_COLUMN);
    }
}
