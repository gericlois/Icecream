-- JMC FOODIES ICE CREAM DISTRIBUTION SYSTEM
-- Earnings separation migration
-- Run ONCE against the existing database (import via phpMyAdmin).
-- Adds a separate Earnings balance + ledger, distinct from the e-funds wallet.
-- Earnings = incentives the company pays out (subsidy, freezer allowance,
-- town/agent over-ride, registration commission). E-funds stays the
-- purchase/reload wallet used to buy ice cream.

-- 1) Separate earnings balance on each user.
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `earnings_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `efunds_balance`;

-- 2) Ledger of earnings movements (mirrors efunds_transactions).
CREATE TABLE IF NOT EXISTS `earnings_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `type` VARCHAR(30) NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `balance_after` DECIMAL(12,2) NOT NULL,
    `reference_type` VARCHAR(30) DEFAULT NULL,
    `reference_id` INT DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `processed_by` INT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_user` (`user_id`),
    KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
