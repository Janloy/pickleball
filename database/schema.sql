CREATE DATABASE IF NOT EXISTS `pickleball_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `pickleball_db`;

-- Roles Table
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `roles` (`id`, `name`) VALUES 
(1, 'Administrator'), 
(2, 'Staff'), 
(3, 'Customer');

-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `role_id` INT NOT NULL DEFAULT 3,
  `first_name` VARCHAR(60) NOT NULL,
  `last_name` VARCHAR(60) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT 'default-avatar.png',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Courts Table
CREATE TABLE IF NOT EXISTS `courts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `court_name` VARCHAR(100) NOT NULL,
  `court_number` VARCHAR(20) NOT NULL UNIQUE,
  `type` ENUM('Indoor', 'Outdoor') DEFAULT 'Indoor',
  `description` TEXT,
  `price_per_hour` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Available', 'Maintenance', 'Disabled') DEFAULT 'Available',
  `image` VARCHAR(255) DEFAULT 'court-default.jpg',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bookings Table
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_number` VARCHAR(50) NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `court_id` INT NOT NULL,
  `booking_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `booking_status` ENUM('Pending', 'Confirmed', 'Completed', 'Cancelled') DEFAULT 'Pending',
  `payment_method` ENUM('GCash', 'Cash', 'Maya') DEFAULT 'Cash',
  `payment_proof` VARCHAR(255) DEFAULT NULL,
  `payment_status` ENUM('Pending', 'Paid', 'Failed', 'Refunded') DEFAULT 'Pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`court_id`) REFERENCES `courts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payment QR Codes Table
CREATE TABLE IF NOT EXISTS `payment_qr_codes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `provider` ENUM('GCash', 'Maya') NOT NULL UNIQUE,
  `image` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reviews Table
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `court_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `rating` INT CHECK (`rating` >= 1 AND `rating` <= 5),
  `comment` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`court_id`) REFERENCES `courts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Initial Seed Data
-- Default Admin: admin@pickleball.com / Password: AdminPassword123!
INSERT INTO `users` (`role_id`, `first_name`, `last_name`, `email`, `password`, `phone`) VALUES
(1, 'Super', 'Admin', 'admin@pickleball.com', '$2y$10$wN9a5sLg1d4P3yS9G6tX/Oq0G8m6W8.8u9P/G6d9.7K2e/8V2gG0S', '09170000000'),
(2, 'Staff', 'One', 'staff@pickleball.com', '$2y$10$wN9a5sLg1d4P3yS9G6tX/Oq0G8m6W8.8u9P/G6d9.7K2e/8V2gG0S', '09170000001'),
(3, 'John', 'Doe', 'customer@pickleball.com', '$2y$10$wN9a5sLg1d4P3yS9G6tX/Oq0G8m6W8.8u9P/G6d9.7K2e/8V2gG0S', '09170000002');

INSERT INTO `courts` (`court_name`, `court_number`, `type`, `description`, `price_per_hour`, `status`) VALUES
('Smash Central', 'COURT-01', 'Indoor', 'Pro-grade cushion acrylic flooring with LED lighting.', 25.00, 'Available'),
('Apex Outdoor', 'COURT-02', 'Outdoor', 'Weather-resistant outdoor surface with perimeter seating.', 20.00, 'Available'),
('Dink Domain', 'COURT-03', 'Indoor', 'Air-conditioned tournament court with standard dimensions.', 30.00, 'Available');