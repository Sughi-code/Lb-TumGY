CREATE TABLE IF NOT EXISTS film (
    film_id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    release_year YEAR DEFAULT NULL,
    language_id TINYINT UNSIGNED NOT NULL,
    original_language_id TINYINT UNSIGNED DEFAULT NULL,
    rental_duration TINYINT UNSIGNED NOT NULL DEFAULT 3,
    rental_rate DECIMAL(4,2) NOT NULL DEFAULT 4.99,
    length SMALLINT UNSIGNED DEFAULT NULL,
    replacement_cost DECIMAL(5,2) NOT NULL DEFAULT 19.99,
    rating ENUM('G','PG','PG-13','R','NC-17') DEFAULT 'G',
    special_features SET('Trailers','Commentaries','Deleted Scenes','Behind the Scenes') DEFAULT NULL,
    last_update TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (film_id),
    KEY idx_title (title),
    KEY idx_fk_language_id (language_id),
    KEY idx_fk_original_language_id (original_language_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO film (film_id, title, description, release_year, language_id, rental_duration, rental_rate, length, replacement_cost, rating) VALUES
(1, 'Интерстеллар', 'Фантастический эпос о путешествии сквозь червоточину.', 2014, 1, 7, 3.99, 169, 24.99, 'PG-13'),
(2, 'Начало', 'Профессиональный вор идей проникает в сны других людей.', 2010, 1, 5, 2.99, 148, 19.99, 'PG-13'),
(3, 'Побег из Шоушенка', 'Банкир Энди Дюфрейн оказывается в тюрьме по ложному обвинению.', 1994, 1, 10, 1.99, 142, 14.99, 'R'),
(4, 'Зеленая миля', 'Надзиратель тюрьмы открывает в заключенном необычный дар.', 1999, 1, 8, 2.49, 189, 17.99, 'R'),
(5, 'Форрест Гамп', 'История простого человека с добрым сердцем и низким IQ.', 1994, 1, 6, 2.99, 142, 16.99, 'PG-13');