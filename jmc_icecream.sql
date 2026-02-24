-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 24, 2026 at 09:55 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `jmc_icecream`
--

-- --------------------------------------------------------

--
-- Table structure for table `efunds_transactions`
--

CREATE TABLE `efunds_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('reload','payment','subsidy','adjustment') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `balance_after` decimal(12,2) NOT NULL,
  `reference_type` varchar(30) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `electric_subsidy`
--

CREATE TABLE `electric_subsidy` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `total_orders_amount` decimal(12,2) NOT NULL,
  `subsidy_amount` decimal(12,2) NOT NULL,
  `converted` tinyint(1) NOT NULL DEFAULT 0,
  `converted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(30) NOT NULL,
  `user_id` int(11) NOT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','for_delivery','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `payment_method` enum('cod','efunds') NOT NULL DEFAULT 'cod',
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `delivery_start_date` date DEFAULT NULL,
  `delivery_end_date` date DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_flavor_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `flavor_name` varchar(100) NOT NULL,
  `qty_per_pack` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `quantity_packs` int(11) NOT NULL,
  `quantity_units` int(11) NOT NULL,
  `line_total` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `subsidy_rate` decimal(5,4) DEFAULT 0.0000,
  `subsidy_min_orders` decimal(12,2) DEFAULT 0.00,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `name`, `slug`, `description`, `status`, `subsidy_rate`, `subsidy_min_orders`, `sort_order`, `created_at`) VALUES
(1, 'Starter Pack', 'starter_pack', NULL, 'active', 0.0200, 8000.00, 1, '2026-02-23 20:36:24'),
(2, 'Premium Pack', 'premium_pack', NULL, 'active', 0.0300, 15000.00, 2, '2026-02-23 20:36:24'),
(3, 'Ice Cream House', 'ice_cream_house', NULL, 'active', 0.0500, 100000.00, 3, '2026-02-23 21:19:45');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `qty_per_pack` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `qty_per_pack`, `unit_price`, `image`, `sort_order`, `status`, `created_at`) VALUES
(1, 'Ice Lolly', 25, 8.00, NULL, 1, 'active', '2026-02-18 02:05:46'),
(2, 'Dessert Bar', 20, 12.00, NULL, 2, 'active', '2026-02-18 02:05:46'),
(3, 'Fiesta ICS', 20, 12.00, NULL, 3, 'active', '2026-02-18 02:05:46'),
(4, 'Sundae Cups', 18, 12.00, NULL, 4, 'active', '2026-02-18 02:05:46'),
(5, 'Classic Crunchy Bar', 20, 16.50, NULL, 5, 'active', '2026-02-18 02:05:46'),
(6, 'Premium Crunchy Bar', 20, 25.00, NULL, 6, 'active', '2026-02-18 02:05:46'),
(7, 'Fiesta Halo-Halo', 20, 16.50, NULL, 7, 'active', '2026-02-18 02:05:46'),
(8, 'Gusto ko 2', 25, 8.00, NULL, 8, 'active', '2026-02-18 02:05:46'),
(9, 'Creamsticks', 25, 19.00, NULL, 9, 'active', '2026-02-18 02:05:46'),
(10, 'Butter Cup', 12, 25.00, NULL, 10, 'active', '2026-02-18 02:05:46'),
(11, 'Megasticks', 25, 27.50, NULL, 11, 'active', '2026-02-18 02:05:46'),
(12, 'Pint', 1, 63.00, NULL, 12, 'active', '2026-02-18 02:05:46'),
(13, 'Pint Mango', 1, 63.00, NULL, 13, 'active', '2026-02-18 02:05:46'),
(14, '850ml', 1, 100.00, NULL, 14, 'active', '2026-02-18 02:05:46'),
(15, 'Half Gallon 1.6L', 1, 170.00, NULL, 15, 'active', '2026-02-18 02:05:46'),
(16, 'One Gallon 3.0L', 1, 350.00, NULL, 16, 'active', '2026-02-18 02:05:46');

-- --------------------------------------------------------

--
-- Table structure for table `product_flavors`
--

CREATE TABLE `product_flavors` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `flavor_name` varchar(100) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_flavors`
--

INSERT INTO `product_flavors` (`id`, `product_id`, `flavor_name`, `status`, `sort_order`) VALUES
(1, 1, 'Chocolate Cheers', 'active', 1),
(2, 1, 'Orange Refresher', 'active', 2),
(3, 1, 'Bubble Berry', 'active', 3),
(4, 2, 'Chocolate', 'active', 1),
(5, 2, 'Cookies & Cream', 'active', 2),
(6, 2, 'Mango Graham', 'active', 3),
(7, 3, 'Buko Pandan', 'active', 1),
(8, 3, 'Coffee Jelly', 'active', 2),
(9, 3, 'Mongo Sarap', 'active', 3),
(10, 4, 'Rocky Road', 'active', 1),
(11, 4, 'Cookies \'N Cream', 'active', 2),
(12, 4, 'Ube', 'active', 3),
(13, 4, 'Avocado', 'active', 4),
(14, 4, 'Bubble Berry', 'active', 5),
(15, 5, 'Double Chocolate', 'active', 1),
(16, 5, 'Cheesy Corn Crunch', 'active', 2),
(17, 6, 'Peanut Dripple', 'active', 1),
(18, 7, 'Buko Salad', 'active', 1),
(19, 8, 'Assorted', 'active', 1),
(20, 9, 'Super Choco', 'active', 1),
(21, 9, 'Cookies \'N Cream', 'active', 2),
(22, 10, 'Choco Rhapsody', 'active', 1),
(23, 10, 'Triple Dutch', 'active', 2),
(24, 11, 'Triple Dutch', 'active', 1),
(25, 12, 'Chocolate', 'active', 1),
(26, 12, 'Cookies \'N Cream', 'active', 2),
(27, 12, 'Ube', 'active', 3),
(28, 12, 'Keso', 'active', 4),
(29, 12, 'Mango Royale', 'active', 5),
(30, 13, 'Mango', 'active', 1),
(31, 14, 'Rocky Road', 'active', 1),
(32, 14, 'Cookies & Cream', 'active', 2),
(33, 14, 'Double Dutch', 'active', 3),
(34, 14, 'Fruit Salad', 'active', 4),
(35, 14, 'Keso', 'active', 5),
(36, 15, 'Rocky Road', 'active', 1),
(37, 15, 'Cookies \'N Cream', 'active', 2),
(38, 15, 'Double Dutch', 'active', 3),
(39, 15, 'Fruit Salad', 'active', 4),
(40, 15, 'Keso', 'active', 5),
(41, 15, 'Choco Double Dutch', 'active', 6),
(42, 15, 'Mocha Coffee', 'active', 7),
(43, 15, 'Ube Keso', 'active', 8),
(44, 16, 'Butterscotch', 'active', 1),
(45, 16, 'Chocolate', 'active', 2),
(46, 16, 'Mango Plain', 'active', 3),
(47, 16, 'Mango Royale', 'active', 4),
(48, 16, 'Rocky Road', 'active', 5),
(49, 16, 'Cookies \'N Cream', 'active', 6),
(50, 16, 'Double Dutch', 'active', 7),
(51, 16, 'Bubble Berry', 'active', 8),
(52, 16, 'Fruit Salad', 'active', 9);

-- --------------------------------------------------------

--
-- Table structure for table `reload_requests`
--

CREATE TABLE `reload_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `method` enum('gcash','bank_transfer','manual') NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `proof_image` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'efunds_discount_percent', '0', '2026-02-18 02:05:46'),
(2, 'subsidy_min_orders', '6000', '2026-02-18 02:05:46'),
(3, 'subsidy_factor', '0.88', '2026-02-18 02:05:46'),
(4, 'subsidy_rate', '0.05', '2026-02-18 02:05:46'),
(5, 'company_name', 'JMC FOODIES ICE CREAM DISTRIBUTIONS', '2026-02-18 02:05:46'),
(6, 'company_address', 'Blk 2 Lot 34 City Homes Subdivision Nancatyasan Urdaneta City, Pangasinan', '2026-02-18 02:05:46'),
(7, 'company_tin', '000-420-482-187', '2026-02-18 02:05:46'),
(8, 'company_hotline', '+63 991 802 1964', '2026-02-18 02:05:46'),
(9, 'agent_subsidy_min_orders', '8000', '2026-02-23 21:05:07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `gender` enum('M','F') DEFAULT NULL,
  `sss_gsis` varchar(30) DEFAULT NULL,
  `tin` varchar(30) DEFAULT NULL,
  `tel_no` varchar(20) DEFAULT NULL,
  `role` enum('admin','subdealer','retailer') NOT NULL DEFAULT 'retailer',
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `efunds_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `application_type` enum('cod','7days_term') DEFAULT NULL,
  `package_info` varchar(100) DEFAULT NULL,
  `payment_type` enum('cash','check','online_transfer') DEFAULT NULL,
  `payment_details` text DEFAULT NULL,
  `auth_rep_name` varchar(100) DEFAULT NULL,
  `auth_rep_relationship` varchar(50) DEFAULT NULL,
  `auth_rep_gender` enum('M','F') DEFAULT NULL,
  `freezer_brand` varchar(100) DEFAULT NULL,
  `freezer_size` varchar(50) DEFAULT NULL,
  `freezer_serial` varchar(100) DEFAULT NULL,
  `freezer_status` varchar(50) DEFAULT NULL,
  `nao_name` varchar(100) DEFAULT NULL,
  `salesman_name` varchar(100) DEFAULT NULL,
  `registered_by` int(11) DEFAULT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `last_name`, `first_name`, `middle_name`, `birthday`, `gender`, `sss_gsis`, `tin`, `tel_no`, `role`, `phone`, `address`, `email`, `efunds_balance`, `application_type`, `package_info`, `payment_type`, `payment_details`, `auth_rep_name`, `auth_rep_relationship`, `auth_rep_gender`, `freezer_brand`, `freezer_size`, `freezer_serial`, `freezer_status`, `nao_name`, `salesman_name`, `registered_by`, `agent_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$XfwJaSB2XX8CS7EyjGvQ..DGcw1sTUlCcB9JS/W.ekP15tAGZkSfa', 'System Administrator', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'admin', '+63 991 802 1964', NULL, NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', '2026-02-18 02:05:46', '2026-02-18 02:05:46'),
(2, 'agent1', '$2y$10$XfwJaSB2XX8CS7EyjGvQ..DGcw1sTUlCcB9JS/W.ekP15tAGZkSfa', 'Juan Dela Cruz', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'subdealer', '+63 912 345 6789', 'Urdaneta City, Pangasinan', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, 'active', '2026-02-18 02:05:46', '2026-02-18 02:05:46'),
(3, 'retailer1', '$2y$10$XfwJaSB2XX8CS7EyjGvQ..DGcw1sTUlCcB9JS/W.ekP15tAGZkSfa', 'Store, Maria Santos', 'Store', 'Maria', 'Santos', '2026-02-27', 'F', '', '', '', 'retailer', '+63 917 123 4567', 'Binalonan, Pangasinan', '', 500.00, NULL, 'premium_pack', NULL, NULL, '', '', NULL, '', '', '', '', '', '', 2, 2, 'active', '2026-02-18 02:05:46', '2026-02-23 15:31:46'),
(4, 'raaaaaaaaaaaaaaaaaaaaaaaaaa', '$2y$10$P1S/A6D.S3EJO/MYOqcnje1.TTargeeeUzMci6z4SVbiucR2E2MtW', 'aaaaaaaaaaaaa, aaaaaaaaaaaaaaaa aaaaaaaaaaa', 'aaaaaaaaaaaaa', 'aaaaaaaaaaaaaaaa', 'aaaaaaaaaaa', NULL, NULL, '', '', '', 'retailer', '09088555555', 'aaaaaaaaaaaaaaaa', '', 0.00, NULL, 'ice_cream_house', NULL, NULL, '', '', NULL, '', '', '', '', '', '', 2, 2, 'active', '2026-02-24 10:00:11', '2026-02-24 10:00:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `efunds_transactions`
--
ALTER TABLE `efunds_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `processed_by` (`processed_by`);

--
-- Indexes for table `electric_subsidy`
--
ALTER TABLE `electric_subsidy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_month_year` (`user_id`,`month`,`year`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `agent_id` (`agent_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_flavor_id` (`product_flavor_id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product_flavors`
--
ALTER TABLE `product_flavors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `reload_requests`
--
ALTER TABLE `reload_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `processed_by` (`processed_by`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `registered_by` (`registered_by`),
  ADD KEY `agent_id` (`agent_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `efunds_transactions`
--
ALTER TABLE `efunds_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `electric_subsidy`
--
ALTER TABLE `electric_subsidy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `product_flavors`
--
ALTER TABLE `product_flavors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `reload_requests`
--
ALTER TABLE `reload_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `efunds_transactions`
--
ALTER TABLE `efunds_transactions`
  ADD CONSTRAINT `efunds_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `efunds_transactions_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `electric_subsidy`
--
ALTER TABLE `electric_subsidy`
  ADD CONSTRAINT `electric_subsidy_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_flavor_id`) REFERENCES `product_flavors` (`id`);

--
-- Constraints for table `product_flavors`
--
ALTER TABLE `product_flavors`
  ADD CONSTRAINT `product_flavors_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reload_requests`
--
ALTER TABLE `reload_requests`
  ADD CONSTRAINT `reload_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reload_requests_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`registered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
