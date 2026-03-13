-- JUSTSALE POS - Ready-to-Deploy Database Schema
-- Version 2.0.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- 1. Users & Authentication
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `fullname` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('Admin','Accounts','Cashier') NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Inventory Structure
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `units` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `short_name` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku` varchar(50) DEFAULT NULL UNIQUE,
  `barcode` varchar(100) DEFAULT NULL UNIQUE,
  `name` varchar(255) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `cost_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `min_stock_level` int(11) DEFAULT 5,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `unit_id` (`unit_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_ibfk_2` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. People & Entities
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `tin` varchar(20) DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `tin` varchar(20) DEFAULT NULL,
  `vrn` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Shift & Sales
CREATE TABLE IF NOT EXISTS `shifts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `terminal_name` varchar(50) DEFAULT 'Main Terminal',
  `opening_time` timestamp DEFAULT current_timestamp(),
  `closing_time` timestamp NULL DEFAULT NULL,
  `opening_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `closing_balance` decimal(12,2) DEFAULT NULL,
  `status` enum('Open','Closed') DEFAULT 'Open',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `shifts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shift_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `payment_method` enum('Cash','Card','Mobile') DEFAULT 'Cash',
  `amount_paid` decimal(12,2) NOT NULL,
  `change_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `sale_date` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `shift_id` (`shift_id`),
  KEY `user_id` (`user_id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`),
  CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `sales_ibfk_3` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sale_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Procurement & Receptions
CREATE TABLE IF NOT EXISTS `purchases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_id` int(11) DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `status` enum('Pending','Partial','Received','Cancelled') DEFAULT 'Pending',
  `purchase_date` timestamp DEFAULT current_timestamp(),
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `purchases_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `purchase_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `cost_price` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_id` (`purchase_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `purchase_items_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `receptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_id` int(11) NOT NULL,
  `received_by` int(11) NOT NULL,
  `received_at` timestamp DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_id` (`purchase_id`),
  KEY `received_by` (`received_by`),
  CONSTRAINT `receptions_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `receptions_ibfk_2` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `reception_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reception_id` int(11) NOT NULL,
  `purchase_item_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_received` int(11) NOT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `reception_id` (`reception_id`),
  KEY `purchase_item_id` (`purchase_item_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `reception_items_ibfk_1` FOREIGN KEY (`reception_id`) REFERENCES `receptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reception_items_ibfk_2` FOREIGN KEY (`purchase_item_id`) REFERENCES `purchase_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reception_items_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. System Configurations
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Mandatory Default Data
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('company_name', 'JUSTSALE'),
('company_currency_code', 'USD'),
('company_currency_name', 'US Dollar'),
('sku_template', 'PROD-{MMYYYY}-00000'),
('sku_next_number', '1'),
('tax_percent', '0'),
('receipt_show_logo', 'yes');

INSERT IGNORE INTO `users` (`username`, `fullname`, `password_hash`, `role`) VALUES
('admin', 'Administrator', '$2y$10$mnq4Pz1GRxLjqb7/enHQIuBqjEzqAxS5HC4A/v6vkyrwVplYko0G.', 'Admin');

COMMIT;
