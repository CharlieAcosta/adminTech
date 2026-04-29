<?php
// filename: configIni.php
// path: 00-config/configIni.php

$appEnv = strtolower(trim((string)(getenv('APP_ENV') ?: 'production')));
$isDocker = file_exists('/.dockerenv');

if (!defined('APP_ENV')) {
    define('APP_ENV', $appEnv);
}

if (!defined('APP_IS_DOCKER')) {
    define('APP_IS_DOCKER', $isDocker);
}

if (!function_exists('admintechAppEnv')) {
    function admintechAppEnv(): string
    {
        return defined('APP_ENV') ? (string)APP_ENV : 'production';
    }
}

if (!function_exists('admintechEsEntornoProduccion')) {
    function admintechEsEntornoProduccion(): bool
    {
        return !in_array(
            admintechAppEnv(),
            ['development', 'dev', 'local', 'test', 'testing', 'qa', 'staging', 'preproduction', 'preproduccion'],
            true
        );
    }
}

if (!function_exists('admintechEsEntornoNoProductivo')) {
    function admintechEsEntornoNoProductivo(): bool
    {
        return !admintechEsEntornoProduccion();
    }
}

// BASE DE DATOS
if ($appEnv === 'development') {

    // Desarrollo en Docker => host "db"
    // Desarrollo en WAMP   => host local
    define('DB_SERVER', $isDocker ? 'db' : '127.0.0.1');

    define('DB_USER', 'root');
    define('DB_PASS', 'root');
    define('DB_NAME', 'techos');
    define('COLUMN_TYPE', 'COLUMN_TYPE');

} else {

    define('DB_SERVER', 'localhost');
    define('DB_USER', 'c1401312_techos');
    define('DB_PASS', 'Eco853Techos');
    define('DB_NAME', 'c1401312_techos');
    define('COLUMN_TYPE', 'column_type');
}

// SESION
define('URL_LOGIN', '../01-views/login.php');
define('SESION_TIME', 10800);
define('REMEMBER_LOGIN_TIME', 2592000);
define('BASE_PATH', __DIR__ . '/../');
?>
