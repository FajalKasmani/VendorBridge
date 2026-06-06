-- Phase 4 Database Schema Updates

USE vendorbridge_db;

-- 1. Create Quotations Table
CREATE TABLE IF NOT EXISTS quotations (
    quote_id INT PRIMARY KEY AUTO_INCREMENT,
    rfq_id INT NOT NULL,
    vendor_id INT NOT NULL,
    total_price DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    delivery_days INT NOT NULL,
    status ENUM('Submitted', 'Under Review', 'Approved', 'Rejected') DEFAULT 'Submitted',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rfq_id) REFERENCES rfqs(rfq_id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES vendor_profiles(vendor_id) ON DELETE CASCADE,
    UNIQUE KEY unique_quote (rfq_id, vendor_id) -- Ensures one quote per vendor per RFQ
);

-- 2. Create Quotation Items Table
CREATE TABLE IF NOT EXISTS quotation_items (
    q_item_id INT PRIMARY KEY AUTO_INCREMENT,
    quote_id INT NOT NULL,
    item_id INT NOT NULL,
    unit_price DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (quote_id) REFERENCES quotations(quote_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES rfq_items(item_id) ON DELETE CASCADE
);
