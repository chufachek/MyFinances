<?php

session_start();

define('BASE_PATH', dirname(__DIR__));

define('APP_PATH', BASE_PATH . '/app');

spl_autoload_register(static function ($class) {
    $prefix = 'App\\';
    if (strpos($class, $prefix) === 0) {
        $relative = substr($class, strlen($prefix));
        $path = APP_PATH . '/' . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($path)) {
            require $path;
        }
    }
});

require APP_PATH . '/Services/Bramus/Router/Router.php';
