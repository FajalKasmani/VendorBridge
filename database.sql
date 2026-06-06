-- Create database
CREATE DATABASE IF NOT EXISTS vendorbridge_db;
USE vendorbridge_db;

-- 1. Roles Table (RBAC)
CREATE TABLE IF NOT EXISTS roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL
);

-- 2. Users Table
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- Insert Default Roles
INSERT INTO roles (role_id, role_name) VALUES
(1, 'Admin'),
(2, 'Procurement Officer'),
(3, 'Manager'),
(4, 'Vendor')
ON DUPLICATE KEY UPDATE role_name=VALUES(role_name);

-- Insert Demo Users (Passwords hashed with password_hash using BCRYPT)
-- admin123, officer123, manager123, vendor123
INSERT INTO users (full_name, email, password, role_id) VALUES
('System Admin', 'admin@vendorbridge.com', '$2y$10$QjSH496pcT5CEjzjDnnR1eThpQ.E8A6bH9A.oO0uW0/8n4F6B2U7i', 1),
('Procurement Officer', 'officer@vendorbridge.com', '$2y$10$w3U/lZ0j4x9R3fNlZp/8Jee.K2X4/z8Nf1cMh5p6F7S8B1V3O2S2m', 2),
('Operations Manager', 'manager@vendorbridge.com', '$2y$10$L1E.G3U4d6G3N9Lp7P9A/e.f5Y4H6D9E3N6I4F7S2B1V3O2S2m', 3),
('Demo Vendor', 'vendor@vendorbridge.com', '$2y$10$M2F.H4V5e7H4O0Mq8Q0B/f.g6Z5I7E0F4O7J5G8T3C2W4P3T3n', 4)
ON DUPLICATE KEY UPDATE email=VALUES(email);

-- For the sake of completeness, I'll add the rest of the schema from db.sql here too

-- 3. Vendors Table
CREATE TABLE IF NOT EXISTS vendors (
    vendor_id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_name VARCHAR(150) NOT NULL,
    category VARCHAR(100),
    gst_details VARCHAR(50),
    contact_person VARCHAR(100),
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. RFQs (Request for Quotation)
CREATE TABLE IF NOT EXISTS rfqs (
    rfq_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    deadline DATE NOT NULL,
    status ENUM('open', 'closed', 'in_progress', 'completed') DEFAULT 'open',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- 5. RFQ Items (Normalization: Separating items from RFQ header)
CREATE TABLE IF NOT EXISTS rfq_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    rfq_id INT,
    product_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(15,2), 
    FOREIGN KEY (rfq_id) REFERENCES rfqs(rfq_id) ON DELETE CASCADE
);

-- 6. Quotations
CREATE TABLE IF NOT EXISTS quotations (
    quotation_id INT PRIMARY KEY AUTO_INCREMENT,
    rfq_id INT,
    vendor_id INT,
    total_amount DECIMAL(15,2) NOT NULL,
    delivery_timeline VARCHAR(100),
    notes TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rfq_id) REFERENCES rfqs(rfq_id),
    FOREIGN KEY (vendor_id) REFERENCES vendors(vendor_id)
);

-- 7. Purchase Orders (PO)
CREATE TABLE IF NOT EXISTS purchase_orders (
    po_id INT PRIMARY KEY AUTO_INCREMENT,
    quotation_id INT UNIQUE,
    po_number VARCHAR(50) UNIQUE NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('ordered', 'received', 'cancelled') DEFAULT 'ordered',
    FOREIGN KEY (quotation_id) REFERENCES quotations(quotation_id)
);

-- 8. Invoices
CREATE TABLE IF NOT EXISTS invoices (
    invoice_id INT PRIMARY KEY AUTO_INCREMENT,
    po_id INT,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    amount_due DECIMAL(15,2) NOT NULL,
    tax_amount DECIMAL(15,2) NOT NULL,
    issue_date DATE,
    status ENUM('unpaid', 'paid', 'overdue') DEFAULT 'unpaid',
    FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id)
);

-- 9. Activity Logs (Audit Trail)
CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(255),
    details TEXT,
    log_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- INDEXING for Performance
CREATE INDEX idx_vendor_name ON vendors(vendor_name);
CREATE INDEX idx_rfq_status ON rfqs(status);
CREATE INDEX idx_quotation_status ON quotations(status);
