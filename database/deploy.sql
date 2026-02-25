-- JMC FOODIES ICE CREAM DISTRIBUTION SYSTEM
-- Combined Schema + Seed Data for Deployment
-- Import this file into your InfinityFree phpMyAdmin

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
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
    `package_info` VARCHAR(100) DEFAULT NULL,
    `payment_type` ENUM('cash','check','online_transfer') DEFAULT NULL,
    `payment_details` TEXT DEFAULT NULL,
    `auth_rep_name` VARCHAR(100) DEFAULT NULL,
    `auth_rep_relationship` VARCHAR(50) DEFAULT NULL,
    `auth_rep_gender` ENUM('M','F') DEFAULT NULL,
    `freezer_brand` VARCHAR(100) DEFAULT NULL,
    `freezer_size` VARCHAR(50) DEFAULT NULL,
    `freezer_serial` VARCHAR(100) DEFAULT NULL,
    `freezer_status` VARCHAR(50) DEFAULT NULL,
    `nao_name` VARCHAR(100) DEFAULT NULL,
    `salesman_name` VARCHAR(100) DEFAULT NULL,
    `registered_by` INT DEFAULT NULL,
    `agent_id` INT DEFAULT NULL,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `qty_per_pack` INT NOT NULL,
    `unit_price` DECIMAL(10,2) NOT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `product_flavors` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `flavor_name` VARCHAR(100) NOT NULL,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `sort_order` INT NOT NULL DEFAULT 0,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `orders` (
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
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `order_items` (
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
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `efunds_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `type` ENUM('reload','payment','subsidy','adjustment','fda') NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `balance_after` DECIMAL(12,2) NOT NULL,
    `reference_type` VARCHAR(30) DEFAULT NULL,
    `reference_id` INT DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `processed_by` INT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `reload_requests` (
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
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `electric_subsidy` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `month` INT NOT NULL,
    `year` INT NOT NULL,
    `total_orders_amount` DECIMAL(12,2) NOT NULL,
    `subsidy_amount` DECIMAL(12,2) NOT NULL,
    `converted` TINYINT(1) NOT NULL DEFAULT 0,
    `converted_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `user_month_year` (`user_id`, `month`, `year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `packages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `subsidy_rate` DECIMAL(5,4) DEFAULT 0.0000,
    `subsidy_min_orders` DECIMAL(12,2) DEFAULT 0.00,
    `freezer_display_allowance` DECIMAL(12,2) DEFAULT 0.00,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `freezer_allowance` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `month` INT NOT NULL,
    `year` INT NOT NULL,
    `total_orders_amount` DECIMAL(12,2) NOT NULL,
    `allowance_amount` DECIMAL(12,2) NOT NULL,
    `converted` TINYINT(1) NOT NULL DEFAULT 0,
    `converted_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `user_month_year` (`user_id`, `month`, `year`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `withdrawal_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `method` ENUM('gcash','check') NOT NULL,
    `gcash_name` VARCHAR(100) DEFAULT NULL,
    `gcash_number` VARCHAR(20) DEFAULT NULL,
    `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `processed_by` INT DEFAULT NULL,
    `processed_at` DATETIME DEFAULT NULL,
    `admin_notes` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(50) NOT NULL UNIQUE,
    `setting_value` TEXT NOT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- SEED DATA
-- =============================================

-- Default admin (password: admin123)
INSERT INTO `users` (`username`, `password`, `full_name`, `role`, `phone`, `status`) VALUES
('admin', '$2y$10$XfwJaSB2XX8CS7EyjGvQ..DGcw1sTUlCcB9JS/W.ekP15tAGZkSfa', 'System Administrator', 'admin', '+63 991 802 1964', 'active');

-- Sample subdealer (password: admin123)
INSERT INTO `users` (`username`, `password`, `full_name`, `role`, `phone`, `address`, `registered_by`, `status`) VALUES
('agent1', '$2y$10$XfwJaSB2XX8CS7EyjGvQ..DGcw1sTUlCcB9JS/W.ekP15tAGZkSfa', 'Juan Dela Cruz', 'subdealer', '+63 912 345 6789', 'Urdaneta City, Pangasinan', 1, 'active');

-- Sample retailer (password: admin123)
INSERT INTO `users` (`username`, `password`, `full_name`, `role`, `phone`, `address`, `registered_by`, `agent_id`, `efunds_balance`, `status`) VALUES
('retailer1', '$2y$10$XfwJaSB2XX8CS7EyjGvQ..DGcw1sTUlCcB9JS/W.ekP15tAGZkSfa', 'Maria Santos Store', 'retailer', '+63 917 123 4567', 'Binalonan, Pangasinan', 2, 2, 500.00, 'active');

-- Products
INSERT INTO `products` (`name`, `qty_per_pack`, `unit_price`, `sort_order`) VALUES
('Ice Lolly', 25, 8.00, 1),
('Dessert Bar', 20, 12.00, 2),
('Fiesta ICS', 20, 12.00, 3),
('Sundae Cups', 18, 12.00, 4),
('Classic Crunchy Bar', 20, 16.50, 5),
('Premium Crunchy Bar', 20, 25.00, 6),
('Fiesta Halo-Halo', 20, 16.50, 7),
('Gusto ko 2', 25, 8.00, 8),
('Creamsticks', 25, 19.00, 9),
('Butter Cup', 12, 25.00, 10),
('Megasticks', 25, 27.50, 11),
('Pint', 1, 63.00, 12),
('Pint Mango', 1, 63.00, 13),
('850ml', 1, 100.00, 14),
('Half Gallon 1.5L', 1, 170.00, 15),
('One Gallon 3.0L', 1, 350.00, 16);

-- Product Flavors
INSERT INTO `product_flavors` (`product_id`, `flavor_name`, `sort_order`) VALUES
(1, 'Chocolate Cheers', 1), (1, 'Orange Refresher', 2), (1, 'Bubble Berry', 3),
(2, 'Chocolate', 1), (2, 'Cookies & Cream', 2), (2, 'Mango Graham', 3),
(3, 'Buko Pandan', 1), (3, 'Coffee Jelly', 2), (3, 'Mongo Sarap', 3),
(4, 'Rocky Road', 1), (4, 'Cookies ''N Cream', 2), (4, 'Ube', 3), (4, 'Avocado', 4), (4, 'Bubble Berry', 5),
(5, 'Double Chocolate', 1), (5, 'Cheesy Corn Crunch', 2),
(6, 'Peanut Dripple', 1),
(7, 'Buko Salad', 1),
(8, 'Assorted', 1),
(9, 'Super Choco', 1), (9, 'Cookies ''N Cream', 2),
(10, 'Choco Rhapsody', 1), (10, 'Triple Dutch', 2),
(11, 'Triple Dutch', 1),
(12, 'Chocolate', 1), (12, 'Cookies ''N Cream', 2), (12, 'Ube', 3), (12, 'Keso', 4), (12, 'Mango Royale', 5),
(13, 'Mango', 1),
(14, 'Rocky Road', 1), (14, 'Cookies & Cream', 2), (14, 'Double Dutch', 3), (14, 'Fruit Salad', 4), (14, 'Keso', 5),
(15, 'Rocky Road', 1), (15, 'Cookies ''N Cream', 2), (15, 'Double Dutch', 3), (15, 'Fruit Salad', 4), (15, 'Keso', 5), (15, 'Choco Double Dutch', 6), (15, 'Mocha Coffee', 7), (15, 'Ube Keso', 8),
(16, 'Butterscotch', 1), (16, 'Chocolate', 2), (16, 'Mango Plain', 3), (16, 'Mango Royale', 4), (16, 'Rocky Road', 5), (16, 'Cookies ''N Cream', 6), (16, 'Double Dutch', 7), (16, 'Bubble Berry', 8), (16, 'Fruit Salad', 9);

-- Packages
INSERT INTO `packages` (`name`, `slug`, `subsidy_rate`, `subsidy_min_orders`, `freezer_display_allowance`, `sort_order`) VALUES
('Starter Pack', 'starter_pack', 0.0500, 8000.00, 200.00, 1),
('Premium Pack', 'premium_pack', 0.0500, 8000.00, 1000.00, 2),
('Ice Cream House', 'ice_cream_house', 0.0500, 8000.00, 0.00, 3);

-- Settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('efunds_discount_percent', '0'),
('subsidy_min_orders', '6000'),
('subsidy_factor', '0.88'),
('subsidy_rate', '0.05'),
('company_name', 'JMC FOODIES ICE CREAM DISTRIBUTIONS'),
('company_address', 'Blk 2 Lot 34 City Homes Subdivision Nancatyasan Urdaneta City, Pangasinan'),
('company_tin', '000-420-482-187'),
('company_hotline', '+63 991 802 1964'),
('agent_subsidy_min_orders', '8000'),
('fda_min_orders', '8000');
