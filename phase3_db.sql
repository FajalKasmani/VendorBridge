-- Phase 3 Database Schema Updates

USE vendorbridge_db;

-- If tables exist from Phase 1, we drop them to recreate exactly as requested for Phase 3
-- Drop in reverse order of dependencies
DROP TABLE IF EXISTS rfq_assignments;
DROP TABLE IF EXISTS rfq_items;
DROP TABLE IF EXISTS quotations; -- quotations depend on rfqs
DROP TABLE IF EXISTS purchase_orders; -- POs depend on quotations
DROP TABLE IF EXISTS invoices; -- Invoices depend on POs
DROP TABLE IF EXISTS rfqs;

-- 1. Create RFQs Table
CREATE TABLE rfqs (
    rfq_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    deadline DATE NOT NULL,
    created_by INT,
    status ENUM('Draft', 'Open', 'Assigned', 'Closed') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- 2. Create RFQ Items Table
CREATE TABLE rfq_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    rfq_id INT,
    item_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    uom VARCHAR(50),
    FOREIGN KEY (rfq_id) REFERENCES rfqs(rfq_id) ON DELETE CASCADE
);

-- 3. Create RFQ Assignments Table
CREATE TABLE rfq_assignments (
    assignment_id INT PRIMARY KEY AUTO_INCREMENT,
    rfq_id INT,
    vendor_id INT,
    FOREIGN KEY (rfq_id) REFERENCES rfqs(rfq_id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES vendor_profiles(vendor_id) ON DELETE CASCADE
);
