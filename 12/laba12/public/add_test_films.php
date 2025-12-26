<?php
require __DIR__ . '/src/database.php';
require __DIR__ . '/src/api.php';

// Тестовые фильмы, которые точно есть в Кинопоиске
$testFilms = [
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
    ],
    [
        'title' => 'Матрица',
        'description' => 'Когда компьютерный хакер Нео узнает всю правду о своей реальности, он должен выбрать: принять мир таким, какой он есть, или бороться с системой.',
        'release_year' => '1999',
        'language_id' => 1,
        'rental_duration' => 3,
        'rental_rate' => 4.99,
        'length' => 136,
        'replacement_cost' => 19.99,
        'rating' => 'R'
    ]
];

echo "Добавление тестовых фильмов...\n";

foreach ($testFilms as $film) {
    try {
        // Проверяем, не существует ли уже такой фильм
        $stmt = $databaseConnection->prepare("SELECT film_id FROM film WHERE title = :title");
        $stmt->bindValue(':title', $film['title']);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            echo "Фильм '{$film['title']}' уже существует в базе\n";
            continue;
        }
        
        $stmt = $databaseConnection->prepare("
            INSERT INTO film (title, description, release_year, language_id, rental_duration, rental_rate, length, replacement_cost, rating, last_update)
            VALUES (:title, :description, :release_year, :language_id, :rental_duration, :rental_rate, :length, :replacement_cost, :rating, NOW())
        ");
        $stmt->bindValue(':title', $film['title']);
        $stmt->bindValue(':description', $film['description']);
        $stmt->bindValue(':release_year', $film['release_year']);
        $stmt->bindValue(':language_id', $film['language_id'], PDO::PARAM_INT);
        $stmt->bindValue(':rental_duration', $film['rental_duration'], PDO::PARAM_INT);
        $stmt->bindValue(':rental_rate', $film['rental_rate']);
        $stmt->bindValue(':length', $film['length'], PDO::PARAM_INT);
        $stmt->bindValue(':replacement_cost', $film['replacement_cost']);
        $stmt->bindValue(':rating', $film['rating']);
        
        $stmt->execute();
        $filmId = $databaseConnection->lastInsertId();
        echo "Тестовый фильм '{$film['title']}' успешно добавлен. ID: $filmId\n";
    } catch (PDOException $e) {
        echo "Ошибка при добавлении фильма '{$film['title']}': " . $e->getMessage() . "\n";
    }
}

echo "Все тестовые фильмы добавлены.\n";
$databaseConnection = null;
?>