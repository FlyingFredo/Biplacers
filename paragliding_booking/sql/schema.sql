-- MySQL Database Schema for Paragliding Booking Application
-- version 1.0
--
-- Target Server Type    : MySQL
-- Target Server Version : 8.0+ (adjust if using older versions, especially for JSON types or other features)
-- File Encoding         : utf8mb4

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0; -- Disable checks temporarily

-- -----------------------------------------------------
-- Table `passengers`
-- Stores information about the passengers making flight requests.
-- -----------------------------------------------------
DROP TABLE IF EXISTS `passengers`;
CREATE TABLE `passengers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `age` TINYINT UNSIGNED NOT NULL,
  `weight` DECIMAL(5,2) UNSIGNED NOT NULL COMMENT 'Weight in KG, e.g., 75.50',
  `phone` VARCHAR(30) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `email_UNIQUE` (`email` ASC) VISIBLE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table `pilots`
-- Stores information about the paragliding pilots.
-- -----------------------------------------------------
DROP TABLE IF EXISTS `pilots`;
CREATE TABLE `pilots` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'Stores hashed passwords',
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Admin can deactivate pilots',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `email_UNIQUE` (`email` ASC) VISIBLE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table `flight_requests`
-- Stores details of flight requests made by passengers.
-- -----------------------------------------------------
DROP TABLE IF EXISTS `flight_requests`;
CREATE TABLE `flight_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `passenger_id` INT UNSIGNED NOT NULL,
  `desired_date` DATE NOT NULL,
  `other_date_available` BOOLEAN NOT NULL DEFAULT FALSE,
  `status` ENUM('pending_confirmation', 'confirmed', 'assigned', 'completed', 'cancelled_by_passenger', 'cancelled_by_pilot', 'cancelled_by_admin') NOT NULL DEFAULT 'pending_confirmation',
  `confirmation_token` VARCHAR(64) NULL DEFAULT NULL COMMENT 'Token sent to passenger for email confirmation',
  `token_expires_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Expiry for confirmation token',
  `assigned_pilot_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Which pilot is assigned to this flight',
  `notes_passenger` TEXT NULL DEFAULT NULL COMMENT 'Optional notes from passenger',
  `notes_admin_pilot` TEXT NULL DEFAULT NULL COMMENT 'Internal notes for admin/pilots',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `confirmation_token_UNIQUE` (`confirmation_token` ASC) VISIBLE,
  INDEX `fk_flight_requests_passengers_idx` (`passenger_id` ASC) VISIBLE,
  INDEX `fk_flight_requests_pilots_idx` (`assigned_pilot_id` ASC) VISIBLE,
  INDEX `idx_status_desired_date` (`status` ASC, `desired_date` ASC) VISIBLE,
  CONSTRAINT `fk_flight_requests_passengers`
    FOREIGN KEY (`passenger_id`)
    REFERENCES `passengers` (`id`)
    ON DELETE CASCADE -- If a passenger is deleted, their requests are also deleted. Consider RESTRICT or SET NULL based on requirements.
    ON UPDATE CASCADE,
  CONSTRAINT `fk_flight_requests_pilots`
    FOREIGN KEY (`assigned_pilot_id`)
    REFERENCES `pilots` (`id`)
    ON DELETE SET NULL -- If a pilot is deleted, the flight is unassigned.
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1; -- Re-enable checks

-- -----------------------------------------------------
-- Example Data (Optional - for development)
-- -----------------------------------------------------
-- INSERT INTO `pilots` (`first_name`, `last_name`, `email`, `phone`, `password_hash`) VALUES
-- ('Admin', 'User', 'admin@example.com', '0000000000', '$2y$10$yourbcryptpwhashhere'), -- Replace with a real bcrypt hash
-- ('John', 'Doe', 'john.doe@example.com', '1111111111', '$2y$10$anotherbcryptpwhash');
