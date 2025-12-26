CREATE TABLE IF NOT EXISTS store (
    store_id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    manager_staff_id TINYINT UNSIGNED NOT NULL,
    address_id SMALLINT UNSIGNED NOT NULL,
    last_update TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (store_id),
    UNIQUE KEY idx_unique_manager (manager_staff_id),
    KEY idx_fk_address_id (address_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO store (store_id, manager_staff_id, address_id) VALUES
(1, 1, 1),
(2, 2, 2);