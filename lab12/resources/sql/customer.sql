CREATE TABLE IF NOT EXISTS customer (
    customer_id INTEGER PRIMARY KEY AUTOINCREMENT,
    store_id INTEGER NOT NULL,
    first_name VARCHAR(45) NOT NULL,
    last_name VARCHAR(45) NOT NULL,
    email VARCHAR(50) DEFAULT NULL,
    address_id INTEGER NOT NULL,
    active INTEGER NOT NULL DEFAULT 1,
    create_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_update DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_fk_store_id ON customer(store_id);
CREATE INDEX IF NOT EXISTS idx_fk_address_id ON customer(address_id);
CREATE INDEX IF NOT EXISTS idx_last_name ON customer(last_name);

INSERT OR IGNORE INTO customer (customer_id, store_id, first_name, last_name, email, address_id, active, create_date) VALUES
(1, 1, 'Иван', 'Иванов', 'ivan@example.com', 1, 1, datetime('now')),
(2, 1, 'Петр', 'Петров', 'petr@example.com', 2, 1, datetime('now')),
(3, 2, 'Анна', 'Сидорова', 'anna@example.com', 3, 1, datetime('now'));