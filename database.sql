CREATE DATABASE IF NOT EXISTS justsale;
USE justsale;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Accounts', 'Cashier') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barcode VARCHAR(100) UNIQUE,
    name VARCHAR(255) NOT NULL,
    category_id INT,
    cost_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    selling_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    stock_quantity INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    terminal_name VARCHAR(50) DEFAULT 'Main Terminal',
    opening_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closing_time TIMESTAMP NULL,
    opening_balance DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    closing_balance DECIMAL(10, 2) NULL,
    status ENUM('Open', 'Closed') DEFAULT 'Open',
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shift_id INT NOT NULL,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('Cash', 'Card', 'Mobile') DEFAULT 'Cash',
    amount_paid DECIMAL(10, 2) NOT NULL,
    change_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shift_id) REFERENCES shifts(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE IF NOT EXISTS purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(255),
    total_amount DECIMAL(10, 2) NOT NULL,
    purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS purchase_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    cost_price DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT
);

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES 
('company_name', 'JUSTSALE'),
('company_tin', ''),
('company_vrn', ''),
('company_logo', ''),
('company_address', ''),
('company_city', ''),
('company_country', ''),
('company_email', ''),
('company_website', ''),
('company_phone', ''),
('company_currency_code', 'USD'),
('company_currency_name', 'US Dollar');

-- Insert default admin: password is 'admin123'
INSERT IGNORE INTO users (username, password_hash, role) VALUES ('admin', '$2y$10$mnq4Pz1GRxLjqb7/enHQIuBqjEzqAxS5HC4A/v6vkyrwVplYko0G.', 'Admin');
