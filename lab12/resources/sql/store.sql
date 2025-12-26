CREATE TABLE IF NOT EXISTS store (
    store_id INTEGER PRIMARY KEY AUTOINCREMENT,
    manager_staff_id INTEGER NOT NULL,
    address_id INTEGER NOT NULL,
    last_update DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_unique_manager ON store(manager_staff_id);
CREATE INDEX IF NOT EXISTS idx_fk_address_id ON store(address_id);

INSERT OR IGNORE INTO store (store_id, manager_staff_id, address_id) VALUES
(1, 1, 1),
(2, 2, 2);