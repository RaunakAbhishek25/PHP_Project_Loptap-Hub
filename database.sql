-- Create Database
CREATE DATABASE IF NOT EXISTS laptophub;
USE laptophub;

-- Admins Table
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users Table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    zipcode VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    email_verified BOOLEAN DEFAULT FALSE
);

-- Brands Table
CREATE TABLE brands (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    logo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories Table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    icon VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Laptops Table
CREATE TABLE laptops (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    brand_id INT,
    category_id INT,
    price DECIMAL(10,2) NOT NULL,
    old_price DECIMAL(10,2),
    description TEXT,
    specifications TEXT,
    processor VARCHAR(100),
    ram VARCHAR(50),
    storage VARCHAR(100),
    graphics VARCHAR(100),
    os VARCHAR(50),
    screen_size VARCHAR(20),
    stock INT DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0,
    reviews_count INT DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    is_bestseller BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Laptop Images Table
CREATE TABLE laptop_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    laptop_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (laptop_id) REFERENCES laptops(id) ON DELETE CASCADE
);

-- Cart Table
CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    laptop_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (laptop_id) REFERENCES laptops(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart (user_id, laptop_id)
);

-- Wishlist Table
CREATE TABLE wishlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    laptop_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (laptop_id) REFERENCES laptops(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, laptop_id)
);

-- Orders Table
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    shipping_charge DECIMAL(10,2) DEFAULT 0,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    grand_total DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_method ENUM('cod', 'upi', 'card') NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    billing_address TEXT NOT NULL,
    shipping_address TEXT NOT NULL,
    coupon_code VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order Items Table
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    laptop_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (laptop_id) REFERENCES laptops(id) ON DELETE CASCADE
);

-- Reviews Table
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    laptop_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (laptop_id) REFERENCES laptops(id) ON DELETE CASCADE
);

-- Coupons Table
CREATE TABLE coupons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    discount_type ENUM('percentage', 'fixed') DEFAULT 'percentage',
    discount_value DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(10,2) DEFAULT 0,
    max_discount DECIMAL(10,2),
    valid_from DATE NOT NULL,
    valid_to DATE NOT NULL,
    usage_limit INT DEFAULT 1,
    used_count INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Contact Messages Table
CREATE TABLE contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200),
    message TEXT NOT NULL,
    status ENUM('unread', 'read', 'replied') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Admin
INSERT INTO admins (username, email, password) VALUES 
('admin', 'admin@laptophub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert Brands
INSERT INTO brands (name) VALUES 
('Apple'), ('Dell'), ('ASUS'), ('Lenovo'), ('HP');

-- Insert Categories
INSERT INTO categories (name, slug) VALUES 
('Gaming', 'gaming'), 
('Ultrabook', 'ultrabook'), 
('Business', 'business'),
('Student', 'student');

-- Insert Sample Laptops
INSERT INTO laptops (name, slug, brand_id, category_id, price, old_price, description, processor, ram, storage, graphics, os, screen_size, stock, is_featured, is_bestseller) VALUES
('MacBook Pro 14', 'macbook-pro-14', 1, 2, 1999.00, 2299.00, 'Apple M3 Pro chip with 12-core CPU and 18-core GPU', 'Apple M3 Pro', '16GB', '512GB SSD', 'Integrated 18-core GPU', 'macOS Sonoma', '14.2"', 15, 1, 1),
('MacBook Air M3', 'macbook-air-m3', 1, 2, 1199.00, 1399.00, 'Apple M3 chip, 8GB RAM, 256GB SSD', 'Apple M3', '8GB', '256GB SSD', 'Integrated', 'macOS Sonoma', '13.6"', 25, 1, 0),
('Dell XPS 13', 'dell-xps-13', 2, 2, 1499.00, 1699.00, 'Intel Core i7-1360P, 16GB RAM, 512GB SSD', 'Intel Core i7-1360P', '16GB', '512GB SSD', 'Intel Iris Xe', 'Windows 11 Pro', '13.4"', 20, 1, 1),
('Dell G16 Gaming', 'dell-g16-gaming', 2, 1, 1299.00, 1499.00, 'Intel Core i7-13700H, RTX 4060, 16GB RAM, 1TB SSD', 'Intel Core i7-13700H', '16GB', '1TB SSD', 'NVIDIA RTX 4060', 'Windows 11', '16"', 10, 0, 0),
('ASUS ROG Zephyrus G14', 'asus-rog-zephyrus-g14', 3, 1, 1799.00, 1999.00, 'AMD Ryzen 9 7940HS, RTX 4070, 32GB RAM', 'AMD Ryzen 9 7940HS', '32GB', '1TB SSD', 'NVIDIA RTX 4070', 'Windows 11', '14"', 8, 1, 1),
('ASUS ZenBook 14', 'asus-zenbook-14', 3, 2, 999.00, 1199.00, 'Intel Core i7-1355U, 16GB RAM, 512GB SSD, OLED', 'Intel Core i7-1355U', '16GB', '512GB SSD', 'Intel Iris Xe', 'Windows 11', '14"', 30, 0, 0),
('Lenovo ThinkPad X1 Carbon', 'lenovo-thinkpad-x1-carbon', 4, 3, 1399.00, 1599.00, 'Intel Core i7-1365U, 16GB RAM, 512GB SSD', 'Intel Core i7-1365U', '16GB', '512GB SSD', 'Integrated', 'Windows 11 Pro', '14"', 18, 0, 0),
('Lenovo Legion Pro 5', 'lenovo-legion-pro-5', 4, 1, 1499.00, 1699.00, 'AMD Ryzen 7 7745HX, RTX 4060, 16GB RAM', 'AMD Ryzen 7 7745HX', '16GB', '1TB SSD', 'NVIDIA RTX 4060', 'Windows 11', '16"', 12, 0, 1),
('HP Spectre x360', 'hp-spectre-x360', 5, 2, 1299.00, 1499.00, 'Intel Core i7-1355U, 16GB RAM, 1TB SSD, 2-in-1', 'Intel Core i7-1355U', '16GB', '1TB SSD', 'Intel Iris Xe', 'Windows 11', '13.5"', 15, 1, 0),
('HP Victus 16', 'hp-victus-16', 5, 1, 899.00, 1099.00, 'AMD Ryzen 5 7640HS, RTX 3050, 8GB RAM', 'AMD Ryzen 5 7640HS', '8GB', '512GB SSD', 'NVIDIA RTX 3050', 'Windows 11', '16.1"', 22, 0, 0),
('Apple iMac 24', 'apple-imac-24', 1, 3, 1299.00, 1499.00, 'Apple M3 chip, 8GB RAM, 256GB SSD, 24" 4.5K display', 'Apple M3', '8GB', '256GB SSD', 'Integrated', 'macOS Sonoma', '24"', 10, 0, 0),
('Dell Latitude 7440', 'dell-latitude-7440', 2, 3, 1699.00, 1899.00, 'Intel Core i7-1365U, 16GB RAM, 512GB SSD, vPro', 'Intel Core i7-1365U', '16GB', '512GB SSD', 'Integrated', 'Windows 11 Pro', '14"', 8, 0, 0),
('ASUS TUF Gaming A16', 'asus-tuf-gaming-a16', 3, 1, 949.00, 1099.00, 'AMD Ryzen 7 7735HS, RTX 4050, 16GB RAM', 'AMD Ryzen 7 7735HS', '16GB', '512GB SSD', 'NVIDIA RTX 4050', 'Windows 11', '16"', 20, 0, 0),
('Lenovo Yoga 9i', 'lenovo-yoga-9i', 4, 2, 1599.00, 1799.00, 'Intel Core i7-1360P, 16GB RAM, 1TB SSD, OLED 4K', 'Intel Core i7-1360P', '16GB', '1TB SSD', 'Intel Iris Xe', 'Windows 11', '14"', 12, 0, 0),
('HP Envy 16', 'hp-envy-16', 5, 2, 1099.00, 1299.00, 'Intel Core i7-13700H, RTX 4060, 16GB RAM', 'Intel Core i7-13700H', '16GB', '1TB SSD', 'NVIDIA RTX 4060', 'Windows 11', '16"', 14, 0, 0);

-- Insert Sample Coupons
INSERT INTO coupons (code, discount_type, discount_value, min_order_amount, max_discount, valid_from, valid_to, usage_limit) VALUES
('SAVE10', 'percentage', 10, 50, 100, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 365 DAY), 100),
('WELCOME20', 'percentage', 20, 100, 200, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 365 DAY), 50),
('FREESHIP', 'fixed', 10, 50, 10, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 365 DAY), 200);