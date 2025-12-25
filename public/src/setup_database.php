<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/database.php';

try {
    // Подключение к серверу без указания базы данных
    $dsn = sprintf('mysql:host=%s;charset=%s', DB_HOST, DB_CHARSET);
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Создание базы данных, если она не существует
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET " . DB_CHARSET);
    echo "База данных " . DB_NAME . " создана успешно\n";
    
    // Подключение к созданной базе данных
    $pdo = getDBConnection();
    
    // Создание таблицы арендаторов
    $sql = "CREATE TABLE IF NOT EXISTS tenants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE,
        last_name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Таблица tenants создана успешно\n";
    
    // Создание таблицы недвижимости
    $sql = "CREATE TABLE IF NOT EXISTS rental_properties (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE,
        type VARCHAR(100) NOT NULL,
        monthly_rent DECIMAL(10, 2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Таблица rental_properties создана успешно\n";
    
    // Создание таблицы информации об аренде
    $sql = "CREATE TABLE IF NOT EXISTS rental_info (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL,
        property_id INT NOT NULL,
        start_date DATE NOT NULL,
        lease_term INT NOT NULL COMMENT 'Срок аренды в месяцах',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
        FOREIGN KEY (property_id) REFERENCES rental_properties(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "Таблица rental_info создана успешно\n";
    
    // Заполнение таблицы арендаторов
    $stmt = $pdo->prepare("INSERT IGNORE INTO tenants (code, last_name) VALUES (?, ?)");
    $tenants = [
        ['T001', 'Иванов'],
        ['T002', 'Петров'],
        ['T003', 'Сидоров'],
        ['T004', 'Козлов'],
        ['T005', 'Волков'],
        ['T006', 'Смирнов'],
        ['T007', 'Попов'],
        ['T008', 'Лебедев'],
        ['T009', 'Новиков'],
        ['T010', 'Морозов']
    ];
    
    foreach ($tenants as $tenant) {
        $stmt->execute($tenant);
    }
    echo "Данные арендаторов добавлены\n";
    
    // Заполнение таблицы недвижимости
    $stmt = $pdo->prepare("INSERT IGNORE INTO rental_properties (code, type, monthly_rent) VALUES (?, ?, ?)");
    $properties = [
        ['P001', 'Квартира-студия', 25000.00],
        ['P002', 'Однокомнатная квартира', 30000.00],
        ['P003', 'Двухкомнатная квартира', 45000.00],
        ['P004', 'Трехкомнатная квартира', 60000.00],
        ['P005', 'Офис', 75000.00],
        ['P006', 'Магазин', 55000.00],
        ['P007', 'Гараж', 15000.00],
        ['P008', 'Склад', 35000.00],
        ['P009', 'Квартира-студия', 27000.00],
        ['P010', 'Однокомнатная квартира', 32000.00]
    ];
    
    foreach ($properties as $property) {
        $stmt->execute($property);
    }
    echo "Данные недвижимости добавлены\n";
    
    // Заполнение таблицы информации об аренде
    $stmt = $pdo->prepare("INSERT IGNORE INTO rental_info (tenant_id, property_id, start_date, lease_term) VALUES (?, ?, ?, ?)");
    $rental_info = [
        [1, 1, '2023-01-15', 12],
        [2, 2, '2023-02-20', 6],
        [3, 3, '2023-03-10', 24],
        [4, 4, '2023-04-05', 18],
        [5, 5, '2023-05-12', 12],
        [6, 6, '2023-06-18', 9],
        [7, 7, '2023-07-22', 6],
        [8, 8, '2023-08-30', 15],
        [9, 9, '2023-09-14', 12],
        [10, 10, '2023-10-01', 10],
        [1, 3, '2024-01-15', 18],
        [2, 5, '2024-02-20', 12],
        [3, 1, '2024-03-10', 6],
        [4, 6, '2024-04-05', 24],
        [5, 2, '2024-05-12', 9],
        [6, 8, '2024-06-18', 12],
        [7, 4, '2024-07-22', 15],
        [8, 10, '2024-08-30', 18],
        [9, 5, '2024-09-14', 6],
        [10, 7, '2024-10-01', 24]
    ];
    
    foreach ($rental_info as $rental) {
        $stmt->execute($rental);
    }
    echo "Данные арендной информации добавлены\n";
    
    echo "База данных успешно создана и заполнена!\n";
    
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}