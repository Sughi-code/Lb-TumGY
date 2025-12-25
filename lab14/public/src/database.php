<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'rent_db');
define('DB_CHARSET', 'utf8mb4');

/**
 * Получение соединения с базой данных
 * @return PDO Объект соединения с базой данных
 */
function getDBConnection(): PDO
{
    try {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $exception) {
        die('Ошибка подключения к базе данных: ' . $exception->getMessage());
    }
}

// Удаляем глобальную переменную $databaseConnection, чтобы подключение было ленивым
// $databaseConnection = getDBConnection();
?>