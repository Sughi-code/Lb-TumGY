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
        echo "🔧 Попытка подключения к базе данных...\n";
        $connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        echo "✅ Подключение к базе данных успешно установлено\n";
        
        echo "🚀 Запуск системы миграций...\n";
        require __DIR__ . '/migrations.php';
        $migrationPath = __DIR__ . '/../resources/sql';
        runMigrations($connection, $migrationPath);
        
        return $connection;
    } catch (PDOException $exception) {
        echo "❌ Ошибка подключения к базе данных: " . $exception->getMessage() . "\n";
        exit(1);
    }
}

$databaseConnection = getDBConnection();
?>