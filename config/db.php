<?php

$envHost = getenv('DB_HOST');
$envName = getenv('DB_NAME');
$envUser = getenv('DB_USER');
$envPass = getenv('DB_PASS');
$envCharset = getenv('DB_CHARSET');

defined('DB_HOST') || define('DB_HOST', $envHost !== false && $envHost !== '' ? $envHost : 'localhost');
defined('DB_NAME') || define('DB_NAME', $envName !== false && $envName !== '' ? $envName : 'my_finances');
defined('DB_USER') || define('DB_USER', $envUser !== false && $envUser !== '' ? $envUser : 'root');
defined('DB_PASS') || define('DB_PASS', $envPass !== false ? $envPass : '');
defined('DB_CHARSET') || define('DB_CHARSET', $envCharset !== false && $envCharset !== '' ? $envCharset : 'utf8mb4');

return [
    'host' => DB_HOST,
    'dbname' => DB_NAME,
    'user' => DB_USER,
    'password' => DB_PASS,
    'charset' => DB_CHARSET,
];
