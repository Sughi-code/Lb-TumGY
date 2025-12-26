<?php
// Подключение к базе данных
$db_host = '127.127.126.31';
$db_name = 'sakila';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Фильмы, которые ТОЧНО есть в Кинопоиске
    $films = [
        [
            'title' => 'Reservoir Dogs',
            'description' => 'Это должно было стать идеальным преступлением. Задумав ограбить ювелирный магазин, криминальный босс Джо Кэбот собрал вместе шестерых опытных и совершенно незнакомых друг с другом преступников.',
            'release_year' => '1992',
            'language_id' => 1,
            'rental_duration' => 3,
            'rental_rate' => 4.99,
            'length' => 100,
            'replacement_cost' => 19.99,
            'rating' => 'R'
        ],
        [
            'title' => 'Inception',
            'description' => 'Вор, способный проникать в сны людей, получает задание, которое представляет собой нечто большее, чем просто кража.',
            'release_year' => '2010',
            'language_id' => 1,
            'rental_duration' => 3,
            'rental_rate' => 4.99,
            'length' => 148,
            'replacement_cost' => 19.99,
            'rating' => 'PG-13'
        ]
    ];

    foreach ($films as $film) {
        // Проверяем, не существует ли уже такой фильм
        $checkStmt = $pdo->prepare("SELECT film_id FROM film WHERE title = :title");
        $checkStmt->execute([':title' => $film['title']]);
        
        if ($checkStmt->fetch()) {
            echo "Фильм '{$film['title']}' уже существует в базе\n";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO film (title, description, release_year, language_id, rental_duration, rental_rate, length, replacement_cost, rating, last_update)
                VALUES (:title, :description, :release_year, :language_id, :rental_duration, :rental_rate, :length, :replacement_cost, :rating, NOW())
            ");
            
            $stmt->execute([
                ':title' => $film['title'],
                ':description' => $film['description'],
                ':release_year' => $film['release_year'],
                ':language_id' => $film['language_id'],
                ':rental_duration' => $film['rental_duration'],
                ':rental_rate' => $film['rental_rate'],
                ':length' => $film['length'],
                ':replacement_cost' => $film['replacement_cost'],
                ':rating' => $film['rating']
            ]);
            
            $filmId = $pdo->lastInsertId();
            echo "Фильм '{$film['title']}' добавлен с ID = $filmId\n";
        }
    }
    
    echo "Готово! Теперь проверьте запросы к /films/{id}/details\n";
    
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage() . "\n");
}
?>