-- Create Database if not exists
CREATE DATABASE IF NOT EXISTS `payment_sandbox` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `payment_sandbox`;

-- 1. Users Table (Role-based access)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Transactions Table
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` VARCHAR(100) NOT NULL UNIQUE,
    `user_id` INT DEFAULT NULL,
    `customer_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `product_name` VARCHAR(100) NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `payment_type` VARCHAR(50) DEFAULT NULL,
    `transaction_status` VARCHAR(30) NOT NULL DEFAULT 'pending',
    `transaction_time` DATETIME DEFAULT NULL,
    `snap_token` VARCHAR(255) DEFAULT NULL,
    `pdf_invoice_path` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. API Integration Logs
CREATE TABLE IF NOT EXISTS `api_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `endpoint` VARCHAR(255) NOT NULL,
    `request_body` LONGTEXT DEFAULT NULL,
    `response_body` LONGTEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Payment Simulation Events / Timeline History
CREATE TABLE IF NOT EXISTS `payment_events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` VARCHAR(100) NOT NULL,
    `event_name` VARCHAR(100) NOT NULL,
    `event_data` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. System/User Audit Logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default user accounts (Passwords are hashed using bcrypt)
-- Admin Password: admin123
-- User Password: user123
INSERT INTO `users` (`username`, `password`, `role`) VALUES
('admin', '$2y$10$9sSxBmI7tPUbVoQ7zgZurefntIrOzCStF9QlV6AtCzkcAok2wyl/K', 'admin'),
('user', '$2y$10$XdGTCUtbu2Utp7wrCzmbdezdSJuc3lAa1szgCpxABBokhdS65zTKS', 'user')
ON DUPLICATE KEY UPDATE `username`=`username`;
