-- JMC FOODIES ICE CREAM DISTRIBUTION SYSTEM
-- Inventory feature migration
-- Run ONCE against the existing database (import via phpMyAdmin).
-- Adds per-flavor pack stock tracking + an inventory transaction log.

-- 1) Per-flavor stock (counted in packs) and a low-stock alert threshold.
ALTER TABLE `product_flavors`
    ADD COLUMN IF NOT EXISTS `stock_packs` INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `low_stock_threshold` INT NOT NULL DEFAULT 0;

-- 2) Audit log of every stock movement.
CREATE TABLE IF NOT EXISTS `inventory_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_flavor_id` INT NOT NULL,
    `change_packs` INT NOT NULL,
    `balance_after` INT NOT NULL,
    `type` ENUM('restock','adjustment','order','cancel_return') NOT NULL,
    `reference_type` VARCHAR(30) DEFAULT NULL,
    `reference_id` INT DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_flavor` (`product_flavor_id`),
    KEY `idx_reference` (`reference_type`, `reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
