CREATE TABLE IF NOT EXISTS `store` (
  `store_id` tinyint UNSIGNED NOT NULL AUTO_INCREMENT,
  `manager_staff_id` tinyint UNSIGNED NOT NULL,
  `address_id` smallint UNSIGNED NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`store_id`),
  UNIQUE KEY `idx_unique_manager` (`manager_staff_id`),
  KEY `idx_fk_address_id` (`address_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;