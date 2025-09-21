CREATE DATABASE IF NOT EXISTS `flights_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `flights_db`;

CREATE TABLE IF NOT EXISTS `flights` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `airline` VARCHAR(150)            NOT NULL,
  `airline_code` VARCHAR(20)        NOT NULL,
  `flight_number` VARCHAR(50)       NOT NULL,
  `origin` CHAR(3)                  NOT NULL,
  `destination` CHAR(3)             NOT NULL,
  `departure` DATETIME              NOT NULL,
  `arrival` DATETIME                NULL,
  `duration` VARCHAR(40)            NULL,
  `price` DECIMAL(10,2)             NOT NULL DEFAULT 0.00,
  `available_seats` INT             NOT NULL DEFAULT 0,
  `operational_days` JSON           NOT NULL,    -- normalized to array of ints 0..6 (0=Sunday)
  `raw_meta` JSON                   DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `ux_flight_unique` (`airline_code`, `flight_number`, `departure`),
  INDEX `idx_origin_destination` (`origin`, `destination`),
  INDEX `idx_departure` (`departure`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Users table
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
