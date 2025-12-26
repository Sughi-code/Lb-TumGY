<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sakila');
define('DB_CHARSET', 'utf8mb4');

function getDBConnection(): PDO
{
    try {
        $host = getenv('DB_HOST') ?: DB_HOST;
        $user = getenv('DB_USER') ?: DB_USER;
        $pass = getenv('DB_PASS') ?: DB_PASS;
        $name = getenv('DB_NAME') ?: DB_NAME;
        $charset = getenv('DB_CHARSET') ?: DB_CHARSET;

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $host,
            $name,
            $charset
        );
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $connection = new PDO($dsn, $user, $pass, $options);
        
        return $connection;
    } catch (PDOException $exception) {
        // Since we can't use sendJsonError here due to dependency issues, we'll throw the exception
        throw $exception;
    }
}

$databaseConnection = getDBConnection();
?>