-- Database Schema for Licensing Server (Deploy at https://justsalepos.franklin.co.tz)

CREATE TABLE IF NOT EXISTS `licenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `license_key` varchar(50) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive','Expired','Suspended') DEFAULT 'Inactive',
  `expiry_date` date DEFAULT NULL,
  `max_activations` int(11) DEFAULT 1,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `license_key` (`license_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `activations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `license_id` int(11) NOT NULL,
  `hwid` varchar(255) NOT NULL,
  `hostname` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `activated_at` timestamp DEFAULT current_timestamp(),
  `last_check_in` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `license_hwid` (`license_id`,`hwid`),
  CONSTRAINT `fk_license_id` FOREIGN KEY (`license_id`) REFERENCES `licenses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Portal Users (Customers who log in to buy keys)
CREATE TABLE IF NOT EXISTS portal_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payments Tracking
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    license_id INT DEFAULT NULL,
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'TZS',
    tx_ref VARCHAR(100) UNIQUE NOT NULL,
    transaction_id VARCHAR(100) UNIQUE,
    status ENUM('Pending', 'Successful', 'Failed') DEFAULT 'Pending',
    payment_method VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES portal_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Link licenses to owners
ALTER TABLE licenses ADD COLUMN user_id INT;
ALTER TABLE licenses ADD CONSTRAINT fk_license_user FOREIGN KEY (user_id) REFERENCES portal_users(id) ON DELETE SET NULL;

-- Central Portal Admins (You/Franklin)
CREATE TABLE IF NOT EXISTS portal_admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Central System Settings (e.g., Global bypass)
CREATE TABLE IF NOT EXISTS portal_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO portal_settings (setting_key, setting_value) VALUES ('global_license_check', 'enabled');
INSERT IGNORE INTO portal_admins (username, password_hash) VALUES ('franklin', '$2y$10$mnq4Pz1GRxLjqb7/enHQIuBqjEzqAxS5HC4A/v6vkyrwVplYko0G.'); -- admin123

-- Initial Test Data
-- INSERT INTO `licenses` (`license_key`, `customer_name`, `status`, `max_activations`) VALUES ('JUST-TEST-123-ABC', 'Test User', 'Active', 1);
