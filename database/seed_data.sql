USE `jmc_icecream`;

-- Default admin user (password: admin123)
INSERT INTO `users` (`username`, `password`, `full_name`, `role`, `phone`, `status`) VALUES
('admin', '$2y$10$XfwJaSB2XX8CS7EyjGvQ..DGcw1sTUlCcB9JS/W.ekP15tAGZkSfa', 'System Administrator', 'admin', '+63 991 802 1964', 'active');

-- Sample subdealer
INSERT INTO `users` (`username`, `password`, `full_name`, `role`, `phone`, `address`, `registered_by`, `status`) VALUES
('agent1', '$2y$10$XfwJaSB2XX8CS7EyjGvQ..DGcw1sTUlCcB9JS/W.ekP15tAGZkSfa', 'Juan Dela Cruz', 'subdealer', '+63 912 345 6789', 'Urdaneta City, Pangasinan', 1, 'active');

-- Sample retailer
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
('Half Gallon 1.6L', 1, 170.00, 15),
('One Gallon 3.0L', 1, 350.00, 16);

-- Product Flavors
-- Ice Lolly (id=1)
INSERT INTO `product_flavors` (`product_id`, `flavor_name`, `sort_order`) VALUES
(1, 'Chocolate Cheers', 1),
(1, 'Orange Refresher', 2),
(1, 'Bubble Berry', 3);

-- Dessert Bar (id=2)
INSERT INTO `product_flavors` (`product_id`, `flavor_name`, `sort_order`) VALUES
(2, 'Chocolate', 1),
(2, 'Cookies & Cream', 2),
(2, 'Mango Graham', 3);

-- Fiesta ICS (id=3)
INSERT INTO `product_flavors` (`product_id`, `flavor_name`, `sort_order`) VALUES
(3, 'Buko Pandan', 1),
(3, 'Coffee Jelly', 2),
(3, 'Mongo Sarap', 3);

-- Sundae Cups (id=4)
INSERT INTO `product_flavors` (`product_id`, `flavor_name`, `sort_order`) VALUES
(4, 'Rocky Road', 1),
(4, 'Cookies ''N Cream', 2),
(4, 'Ube', 3),
(4, 'Avocado', 4),
(4, 'Bubble Berry', 5);

-- Classic Crunchy Bar (id=5)
INSERT INTO `product_flavors` (`product_id`, `flavor_name`, `sort_order`) VALUES
(5, 'Double Chocolate', 1),
(5, 'Cheesy Corn Crunch', 2);

-- Premium Crunchy Bar (id=6)
INSERT INTO `product_flavors` (`product_id`, `flavor_name`, `sort_order`) VALUES
(6, 'Peanut Dripple', 1);

-- Fiesta Halo-Halo (id=7)
INSERT INTO `product_flavors` (`product_id`, `flavor_name`, `sort_order`) VALUES
(7, 'Buko Salad', 1);

-- Gusto ko 2 (id=8)
INSERT INTO `product_flavors` (`product_id`, `flavor_name`, `sort_order`) VALUES
(8, 'Assorted', 1);

-- Creamsticks (id=9)
INSERT INTO `product_flavors` (`product_id`, `flavor_name`, `sort_order`) VALUES
(9, 'Super Choco', 1),
(9, 'Cookies ''N Cream', 2);

-- Butter Cup (id=10)
INSERT INTO `product_flavors` (`product_id`, `flavor_name`, `sort_order`) VALUES
(10, 'Choco Rhapsody', 1),
(10, 'Triple Dutch', 2);

-- Megasticks (id=11)
INSERT INTO `product_flavors` (`product_id`, `flavor_name`, `sort_order`) VALUES
(11, 'Triple Dutch', 1);

-- Pint (id=12)
INSERT INTO `product_flavors` (`product_id`, `flavor_name`, `sort_order`) VALUES
(12, 'Chocolate', 1),
(12, 'Cookies ''N Cream', 2),
(12, 'Ube', 3),
(12, 'Keso', 4),
(12, 'Mango Royale', 5);

-- Pint Mango (id=13)
INSERT INTO `product_flavors` (`product_id`, `flavor_name`, `sort_order`) VALUES
(13, 'Mango', 1);

-- 850ml (id=14)
INSERT INTO `product_flavors` (`product_id`, `flavor_name`, `sort_order`) VALUES
(14, 'Rocky Road', 1),
(14, 'Cookies & Cream', 2),
(14, 'Double Dutch', 3),
(14, 'Fruit Salad', 4),
(14, 'Keso', 5);

-- Half Gallon 1.6L (id=15)
INSERT INTO `product_flavors` (`product_id`, `flavor_name`, `sort_order`) VALUES
(15, 'Rocky Road', 1),
(15, 'Cookies ''N Cream', 2),
(15, 'Double Dutch', 3),
(15, 'Fruit Salad', 4),
(15, 'Keso', 5),
(15, 'Choco Double Dutch', 6),
(15, 'Mocha Coffee', 7),
(15, 'Ube Keso', 8);

-- One Gallon 3.0L (id=16)
INSERT INTO `product_flavors` (`product_id`, `flavor_name`, `sort_order`) VALUES
(16, 'Butterscotch', 1),
(16, 'Chocolate', 2),
(16, 'Mango Plain', 3),
(16, 'Mango Royale', 4),
(16, 'Rocky Road', 5),
(16, 'Cookies ''N Cream', 6),
(16, 'Double Dutch', 7),
(16, 'Bubble Berry', 8),
(16, 'Fruit Salad', 9);

-- Settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('efunds_discount_percent', '0'),
('subsidy_min_orders', '6000'),
('subsidy_factor', '0.88'),
('subsidy_rate', '0.05'),
('company_name', 'JMC FOODIES ICE CREAM DISTRIBUTIONS'),
('company_address', 'Blk 2 Lot 34 City Homes Subdivision Nancatyasan Urdaneta City, Pangasinan'),
('company_tin', '000-420-482-187'),
('company_hotline', '+63 991 802 1964');
