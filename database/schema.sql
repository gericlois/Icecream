-- JMC FOODIES ICE CREAM DISTRIBUTION SYSTEM
-- Database Schema

CREATE DATABASE IF NOT EXISTS `jmc_icecream` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `jmc_icecream`;

-- Users table (admin, subdealer, retailer)
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(50) DEFAULT NULL,
    `first_name` VARCHAR(50) DEFAULT NULL,
    `middle_name` VARCHAR(50) DEFAULT NULL,
    `birthday` DATE DEFAULT NULL,
    `gender` ENUM('M','F') DEFAULT NULL,
    `sss_gsis` VARCHAR(30) DEFAULT NULL,
    `tin` VARCHAR(30) DEFAULT NULL,
    `tel_no` VARCHAR(20) DEFAULT NULL,
    `role` ENUM('admin','subdealer','retailer') NOT NULL DEFAULT 'retailer',
    `phone` VARCHAR(20) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `efunds_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `application_type` ENUM('cod','7days_term') DEFAULT NULL,
    `package_info` ENUM('starter_pack','premium_pack') DEFAULT NULL,
    `payment_type` ENUM('cash','check','online_transfer') DEFAULT NULL,
    `payment_details` TEXT DEFAULT NULL,
    `auth_rep_name` VARCHAR(100) DEFAULT NULL,
    `auth_rep_relationship` VARCHAR(50) DEFAULT NULL,
    `auth_rep_gender` ENUM('M','F') DEFAULT NULL,
    `freezer_brand` VARCHAR(100) DEFAULT NULL,
    `freezer_size` VARCHAR(50) DEFAULT NULL,
    `freezer_serial` VARCHAR(100) DEFAULT NULL,
    `freezer_status` VARCHAR(50) DEFAULT NULL,
    `registered_by` INT DEFAULT NULL,
    `agent_id` INT DEFAULT NULL,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`registered_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`agent_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Products table
CREATE TABLE `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `qty_per_pack` INT NOT NULL,
    `unit_price` DECIMAL(10,2) NOT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Product flavors
CREATE TABLE `product_flavors` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `flavor_name` VARCHAR(100) NOT NULL,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `sort_order` INT NOT NULL DEFAULT 0,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Orders
CREATE TABLE `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(30) NOT NULL UNIQUE,
    `user_id` INT NOT NULL,
    `agent_id` INT DEFAULT NULL,
    `status` ENUM('pending','approved','for_delivery','delivered','cancelled') NOT NULL DEFAULT 'pending',
    `payment_method` ENUM('cod','efunds') NOT NULL DEFAULT 'cod',
    `discount_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `notes` TEXT DEFAULT NULL,
    `delivery_start_date` DATE DEFAULT NULL,
    `delivery_end_date` DATE DEFAULT NULL,
    `approved_by` INT DEFAULT NULL,
    `approved_at` DATETIME DEFAULT NULL,
    `delivered_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`agent_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Order items (snapshot data)
CREATE TABLE `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_flavor_id` INT NOT NULL,
    `product_name` VARCHAR(100) NOT NULL,
    `flavor_name` VARCHAR(100) NOT NULL,
    `qty_per_pack` INT NOT NULL,
    `unit_price` DECIMAL(10,2) NOT NULL,
    `quantity_packs` INT NOT NULL,
    `quantity_units` INT NOT NULL,
    `line_total` DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_flavor_id`) REFERENCES `product_flavors`(`id`)
) ENGINE=InnoDB;

-- E-funds transaction ledger
CREATE TABLE `efunds_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `type` ENUM('reload','payment','subsidy','adjustment') NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `balance_after` DECIMAL(12,2) NOT NULL,
    `reference_type` VARCHAR(30) DEFAULT NULL,
    `reference_id` INT DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `processed_by` INT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Reload requests
CREATE TABLE `reload_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `method` ENUM('gcash','bank_transfer','manual') NOT NULL,
    `reference_number` VARCHAR(100) DEFAULT NULL,
    `proof_image` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `processed_by` INT DEFAULT NULL,
    `processed_at` DATETIME DEFAULT NULL,
    `admin_notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Electric subsidy records
CREATE TABLE `electric_subsidy` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `month` INT NOT NULL,
    `year` INT NOT NULL,
    `total_orders_amount` DECIMAL(12,2) NOT NULL,
    `subsidy_amount` DECIMAL(12,2) NOT NULL,
    `converted` TINYINT(1) NOT NULL DEFAULT 0,
    `converted_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `user_month_year` (`user_id`, `month`, `year`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB;

-- System settings
CREATE TABLE `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(50) NOT NULL UNIQUE,
    `setting_value` TEXT NOT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
