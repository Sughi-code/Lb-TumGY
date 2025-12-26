<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../public/src/database.php';

try {
    $databaseConnection = getDBConnection();
    
    // Удалим старые тестовые данные, если они есть
    $databaseConnection->exec("DELETE FROM film WHERE title IN ('Матрица', 'Титаник', 'Звёздные войны', 'Начало', 'Интерстеллар')");
    
    // Добавим фильмы с реальными названиями, которые есть в Кинопоиске
    $films = [
        [
            'title' => 'Матрица',
            'description' => 'Фантастический боевик о борьбе с машинами',
            'release_year' => 1999,
            'language_id' => 1,
            'rental_duration' => 5,
            'rental_rate' => 2.99,
            'length' => 136,
            'replacement_cost' => 20.99,
            'rating' => 'PG-13'
        ],
        [
            'title' => 'Титаник',
            'description' => 'Романтическая драма о любви на фоне трагедии',
            'release_year' => 1997,
            'language_id' => 1,
            'rental_duration' => 7,
            'rental_rate' => 4.99,
            'length' => 194,
            'replacement_cost' => 25.99,
            'rating' => 'PG-13'
        ],
        [
            'title' => 'Звёздные войны',
            'description' => 'Эпическая сага о борьбе добра и зла',
            'release_year' => 1977,
            'language_id' => 1,
            'rental_duration' => 6,
            'rental_rate' => 3.99,
            'length' => 121,
            'replacement_cost' => 22.99,
            'rating' => 'PG'
        ],
        [
            'title' => 'Начало',
            'description' => 'Фильм о проникновении в сны',
            'release_year' => 2010,
            'language_id' => 1,
            'rental_duration' => 5,
            'rental_rate' => 2.99,
            'length' => 148,
            'replacement_cost' => 19.99,
            'rating' => 'PG-13'
        ],
        [
            'title' => 'Интерстеллар',
            'description' => 'Фантастическая драма о путешествиях между звёздами',
            'release_year' => 2014,
            'language_id' => 1,
            'rental_duration' => 6,
            'rental_rate' => 4.99,
            'length' => 169,
            'replacement_cost' => 27.99,
            'rating' => 'PG-13'
        ]
    ];
    
    $stmt = $databaseConnection->prepare(
        "INSERT INTO film (title, description, release_year, language_id, rental_duration, rental_rate, length, replacement_cost, rating) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    
    foreach ($films as $film) {
        $stmt->execute([
            $film['title'],
            $film['description'],
            $film['release_year'],
            $film['language_id'],
            $film['rental_duration'],
            $film['rental_rate'],
            $film['length'],
            $film['replacement_cost'],
            $film['rating']
        ]);
    }
    
    echo "✅ Тестовые фильмы с реальными названиями успешно добавлены в базу данных\n";
    echo "Добавлены фильмы: Матрица, Титаник, Звёздные войны, Начало, Интерстеллар\n";
    
} catch (Exception $e) {
    echo "❌ Ошибка при добавлении тестовых фильмов: " . $e->getMessage() . "\n";
}