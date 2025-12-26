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
(1, 'Interstellar', 'A team of explorers travel through a wormhole in space in an attempt to ensure humanity\'s survival.', 2014, 1, 7, 3.99, 169, 24.99, 'PG-13'),
    film_id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    release_year INTEGER DEFAULT NULL,
    language_id INTEGER NOT NULL,
    original_language_id INTEGER DEFAULT NULL,
    rental_duration INTEGER NOT NULL DEFAULT 3,
    rental_rate REAL NOT NULL DEFAULT 4.99,
    length INTEGER DEFAULT NULL,
    replacement_cost REAL NOT NULL DEFAULT 19.99,
    rating TEXT DEFAULT 'G',
    special_features TEXT DEFAULT NULL,
    last_update DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_title ON film(title);
CREATE INDEX IF NOT EXISTS idx_fk_language_id ON film(language_id);
CREATE INDEX IF NOT EXISTS idx_fk_original_language_id ON film(original_language_id);

INSERT OR IGNORE INTO film (film_id, title, description, release_year, language_id, rental_duration, rental_rate, length, replacement_cost, rating) VALUES
(1, 'Interstellar', 'A team of explorers travel through a wormhole in space in an attempt to ensure humanity''s survival.', 2014, 1, 7, 3.99, 169, 24.99, 'PG-13'),
(2, 'Inception', 'A thief who steals corporate secrets through dream-sharing technology is given the task of planting an idea into the mind of a C.E.O.', 2010, 1, 5, 2.99, 148, 19.99, 'PG-13'),
(3, 'The Shawshank Redemption', 'Two imprisoned men bond over a number of years, finding solace and eventual redemption through acts of common decency.', 1994, 1, 10, 1.99, 142, 14.99, 'R'),
(4, 'The Green Mile', 'The lives of guards on Death Row are affected by one of their charges: a black man accused of child murder and rape, yet who has a mysterious gift.', 1999, 1, 8, 2.49, 189, 17.99, 'R'),
(5, 'Forrest Gump', 'The presidencies of Kennedy and Johnson, the events of Vietnam, Watergate, and other historical events unfold through the perspective of an Alabama man.', 1994, 1, 6, 2.99, 142, 16.99, 'PG-13');