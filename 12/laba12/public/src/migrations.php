<?php
function runMigrations(PDO $databaseConnection, string $migrationPath): void
{
    echo "\n===== ЗАПУСК СИСТЕМЫ МИГРАЦИЙ =====\n";
    try {
        $databaseConnection->query("SELECT 1");
        echo "✅ Успешное подключение к базе данных\n";
        echo "🔍 Проверка наличия таблиц...\n";
        
        $tables = [
            'film' => 'films.sql',
            'customer' => 'customers.sql', 
            'rental' => 'rentals.sql',
            'store' => 'stores.sql'
        ];
        
        $createdTables = [];
        foreach ($tables as $tableName => $sqlFile) {
            $stmt = $databaseConnection->prepare("
                SELECT COUNT(*) as exists_count 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = ?
            ");
            $stmt->execute([$tableName]);
            $result = $stmt->fetch();
            
            if ($result['exists_count'] == 0) {
                echo "❌ Таблица `$tableName` не найдена\n";
                $fullSqlPath = $migrationPath . '/' . $sqlFile;
                if (file_exists($fullSqlPath)) {
                    echo "🔧 Выполнение миграции для таблицы `$tableName` из файла `$sqlFile`\n";
                    $sql = file_get_contents($fullSqlPath);
                    try {
                        $databaseConnection->exec($sql);
                        echo "✅ Таблица `$tableName` успешно создана\n";
                        $createdTables[] = $tableName;
                    } catch (PDOException $e) {
                        echo "❌ Ошибка при создании таблицы `$tableName`: " . $e->getMessage() . "\n";
                    }
                } else {
                    echo "❌ Файл миграции не найден: `$fullSqlPath`\n";
                }
            } else {
                echo "✅ Таблица `$tableName` уже существует\n";
            }
        }
        
        if (empty($createdTables)) {
            echo "✨ Все таблицы уже существуют. Миграции не требуются\n";
        } else {
            echo "✨ Миграции успешно завершены. Созданы таблицы: " . implode(', ', $createdTables) . "\n";
        }
    } catch (PDOException $e) {
        echo "❌ Критическая ошибка при выполнении миграций: " . $e->getMessage() . "\n";
    }
    echo "===== ЗАВЕРШЕНИЕ СИСТЕМЫ МИГРАЦИЙ =====\n";
}
?>