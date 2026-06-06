-- Phase 2 Database Schema Updates

USE vendorbridge_db;

-- 1. Create Categories Table
CREATE TABLE IF NOT EXISTS categories (
    cat_id INT PRIMARY KEY AUTO_INCREMENT,
    cat_name VARCHAR(100) UNIQUE NOT NULL
);

-- Insert Sample Categories
INSERT IGNORE INTO categories (cat_id, cat_name) VALUES 
(1, 'Raw Materials'),
(2, 'Packaging'),
(3, 'Logistics'),
(4, 'IT Services'),
(5, 'Office Supplies');

-- 2. Create Vendor Profiles Table
-- This replaces or runs alongside the old 'vendors' table. We use the requested name.
CREATE TABLE IF NOT EXISTS vendor_profiles (
    vendor_id INT PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(150) NOT NULL,
    gst_number VARCHAR(50) UNIQUE NOT NULL,
    category_id INT,
    contact_email VARCHAR(100) UNIQUE NOT NULL,
    status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
    rating DECIMAL(3,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(cat_id) ON DELETE SET NULL
);
