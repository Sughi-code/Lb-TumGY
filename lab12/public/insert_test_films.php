<?php
require_once 'config.php';

try {
    $connection = getDBConnection();
    echo "Adding test films...\n";
    
    $testFilms = [
        [
            'title' => 'Интерстеллар',
            'description' => 'Фантастический эпос о путешествии сквозь червоточину.',
            'release_year' => 2014,
            'language_id' => 1,
            'rental_duration' => 7,
            'rental_rate' => 3.99,
            'length' => 169,
            'replacement_cost' => 24.99,
            'rating' => 'PG-13'
        ],
        [
            'title' => 'Начало',
            'description' => 'Профессиональный вор идей проникает в сны других людей.',
            'release_year' => 2010,
            'language_id' => 1,
            'rental_duration' => 5,
            'rental_rate' => 2.99,
            'length' => 148,
            'replacement_cost' => 19.99,
            'rating' => 'PG-13'
        ],
        [
            'title' => 'Побег из Шоушенка',
            'description' => 'Банкир Энди Дюфрейн оказывается в тюрьме по ложному обвинению.',
            'release_year' => 1994,
            'language_id' => 1,
            'rental_duration' => 10,
            'rental_rate' => 1.99,
            'length' => 142,
            'replacement_cost' => 14.99,
            'rating' => 'R'
        ],
        [
            'title' => 'Зеленая миля',
            'description' => 'Надзиратель тюрьмы открывает в заключенном необычный дар.',
            'release_year' => 1999,
            'language_id' => 1,
            'rental_duration' => 8,
            'rental_rate' => 2.49,
            'length' => 189,
            'replacement_cost' => 17.99,
            'rating' => 'R'
        ],
        [
            'title' => 'Форрест Гамп',
            'description' => 'История простого человека с добрым сердцем и низким IQ.',
            'release_year' => 1994,
            'language_id' => 1,
            'rental_duration' => 6,
            'rental_rate' => 2.99,
            'length' => 142,
            'replacement_cost' => 16.99,
            'rating' => 'PG-13'
        ]
    ];
    
    $added = 0;
    $errors = 0;
    
    foreach ($testFilms as $filmData) {
        try {
            $stmt = $connection->prepare("SELECT film_id FROM film WHERE title = ?");
            $stmt->execute([$filmData['title']]);
            
            if ($stmt->rowCount() > 0) {
                echo "Film '{$filmData['title']}' already exists\n";
                continue;
            }
            
            $sql = "INSERT INTO film (title, description, release_year, language_id, 
                    rental_duration, rental_rate, length, replacement_cost, rating, last_update) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $connection->prepare($sql);
            $stmt->execute([
                $filmData['title'],
                $filmData['description'],
                $filmData['release_year'],
                $filmData['language_id'],
                $filmData['rental_duration'],
                $filmData['rental_rate'],
                $filmData['length'],
                $filmData['replacement_cost'],
                $filmData['rating']
            ]);
            
            $filmId = $connection->lastInsertId();
            echo "Added film: '{$filmData['title']}' (ID: {$filmId})\n";
            $added++;
            
        } catch (PDOException $e) {
            echo "Error adding film '{$filmData['title']}': " . $e->getMessage() . "\n";
            $errors++;
        }
    }
    
    echo "\nTotal: {$added} films added, {$errors} errors\n";
    
    $stmt = $connection->query("SELECT COUNT(*) as count FROM film");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total films in database now: " . $result['count'] . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>