CREATE TABLE IF NOT EXISTS rental (
    rental_id INTEGER PRIMARY KEY AUTOINCREMENT,
    rental_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    inventory_id INTEGER NOT NULL,
    customer_id INTEGER NOT NULL,
    return_date DATETIME DEFAULT NULL,
    staff_id INTEGER NOT NULL,
    last_update DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_rental_date ON rental(rental_date, inventory_id, customer_id);
CREATE INDEX IF NOT EXISTS idx_fk_inventory_id ON rental(inventory_id);
CREATE INDEX IF NOT EXISTS idx_fk_customer_id ON rental(customer_id);
CREATE INDEX IF NOT EXISTS idx_fk_staff_id ON rental(staff_id);

INSERT OR IGNORE INTO rental (rental_id, rental_date, inventory_id, customer_id, return_date, staff_id) VALUES
(1, datetime('now'), 1, 1, NULL, 1),
(2, datetime('now', '-3 days'), 2, 2, datetime('now'), 1),
(3, datetime('now', '-1 day'), 3, 3, NULL, 2);