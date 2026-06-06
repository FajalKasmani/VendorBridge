-- Phase 5 - Database Script for Approvals Module
USE vendorbridge_db;

CREATE TABLE IF NOT EXISTS approvals (
    approval_id INT AUTO_INCREMENT PRIMARY KEY,
    rfq_id INT NOT NULL,
    quote_id INT NOT NULL,
    manager_id INT NOT NULL,
    action ENUM('Approved', 'Rejected') NOT NULL,
    remarks TEXT,
    action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rfq_id) REFERENCES rfqs(rfq_id) ON DELETE CASCADE,
    FOREIGN KEY (quote_id) REFERENCES quotations(quote_id) ON DELETE CASCADE,
    FOREIGN KEY (manager_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
