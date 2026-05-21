-- Database Schema for Association Management System

-- Create Users Table (for Authentication)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Document Types (Dastavej Type Master) Table
CREATE TABLE IF NOT EXISTS `document_types` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Plot Statuses Master Table
CREATE TABLE IF NOT EXISTS `plot_statuses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Plots Table (Screenshot 2 Main Form)
CREATE TABLE IF NOT EXISTS `plots` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `plot_no` VARCHAR(50) NOT NULL,
    `plot_size_sq_mt` DECIMAL(10,2) NOT NULL,
    `plot_size_sq_vaar` DECIMAL(10,2) NOT NULL,
    `plot_status_id` INT DEFAULT NULL,
    `purchaser_name` VARCHAR(255) NOT NULL,
    `purchaser_address` TEXT NOT NULL,
    `purchaser_city` VARCHAR(100) DEFAULT NULL,
    `purchaser_mobile` VARCHAR(20) NOT NULL,
    `purchaser_alt_mobile` VARCHAR(20) DEFAULT NULL,
    `purchaser_co` VARCHAR(255) DEFAULT NULL,
    `document_no` VARCHAR(100) DEFAULT NULL,
    `document_date` DATE DEFAULT NULL,
    `document_type_id` INT DEFAULT NULL,
    `seller_name` VARCHAR(255) DEFAULT NULL,
    `seller_address` TEXT DEFAULT NULL,
    `seller_city` VARCHAR(100) DEFAULT NULL,
    `seller_mobile` VARCHAR(20) DEFAULT NULL,
    `seller_alt_mobile` VARCHAR(20) DEFAULT NULL,
    `seller_co` VARCHAR(255) DEFAULT NULL,
    `note` TEXT DEFAULT NULL,
    `plot_transfer` ENUM('YES', 'NO', 'Not Applicable') DEFAULT 'NO',
    `status` ENUM('Active', 'Deactive') DEFAULT 'Active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`document_type_id`) REFERENCES `document_types`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`plot_status_id`) REFERENCES `plot_statuses`(`id`) ON DELETE SET NULL,
    INDEX (`plot_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Receipts Table (Screenshot 1 Receipt Form)
CREATE TABLE IF NOT EXISTS `receipts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `receipt_no` VARCHAR(50) NOT NULL UNIQUE,
    `receipt_date` DATE NOT NULL,
    `plot_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `mobile_no` VARCHAR(20) NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    -- Maintenance Charges
    `maintenance_charge_1` DECIMAL(10,2) DEFAULT 0.00, -- Upto Dt.31.03.2022
    `maintenance_charge_2` DECIMAL(10,2) DEFAULT 0.00, -- Date: 01.04.2022 To 31.03.2024
    `maintenance_charge_3` DECIMAL(10,2) DEFAULT 0.00, -- Date: 01.04.2024 To 31.03.2026
    `share_fee` DECIMAL(10,2) DEFAULT 0.00,
    -- Other particulars
    `entry_member_fee` DECIMAL(10,2) DEFAULT 0.00,
    `transfer_fee` DECIMAL(10,2) DEFAULT 0.00,
    `temple_development_fund` DECIMAL(10,2) DEFAULT 0.00,
    `other_fee` DECIMAL(10,2) DEFAULT 0.00,
    `other_income` DECIMAL(10,2) DEFAULT 0.00,
    -- Summary
    `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `payment_mode` VARCHAR(50) NOT NULL DEFAULT 'Cash',
    `remark` TEXT DEFAULT NULL,
    `received_by` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`plot_id`) REFERENCES `plots`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Default Admin User (username: admin, password: admin123)
-- Hash generated using password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO `users` (`id`, `username`, `password`, `full_name`)
SELECT 1, 'admin', '$2y$10$GkDFup7Lvr6QXsWzE4TQau9e8qQafJ.q72IRZ61KeAiLa0tPlKYIe', 'Association Administrator'
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

-- Seed Default Document Types
INSERT INTO `document_types` (`id`, `name`) VALUES
(1, 'Company'),
(2, 'First Owner'),
(3, 'Resell'),
(4, 'Gift Deed'),
(5, 'Heirs')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Seed Default Plot Statuses
INSERT INTO `plot_statuses` (`id`, `name`) VALUES
(1, 'Open Land'),
(2, 'Residents'),
(3, 'Under Construction')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
