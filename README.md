# VendorBridge ERP – Procurement & Vendor Management System

VendorBridge is a lightweight, cloud-ready Enterprise Resource Planning (ERP) platform designed specifically for digitizing and automating procurement and vendor workflows. 

## Problem It Solves
Traditional procurement relies on scattered emails, manual Excel tracking, and undocumented verbal approvals, leading to inefficiencies, lost records, and lack of accountability. VendorBridge centralizes the entire procurement lifecycle—from vendor onboarding to quote comparison, automated purchase orders, and invoicing—creating a secure, auditable, and structured workflow.

## ERP Workflow Overview
1. **Onboarding**: Vendors register and are approved by Admins.
2. **Sourcing**: Procurement Officers create Requests for Quotation (RFQs) and assign them to approved vendors.
3. **Bidding**: Vendors log in to their self-service portal to submit structured quotations.
4. **Evaluation**: Managers use a side-by-side comparison matrix to evaluate bids and approve the best offer.
5. **Fulfillment**: Approved quotes are automatically converted into Purchase Orders (POs) and Invoices.
6. **Audit**: Every action is permanently recorded in an immutable activity log.

---

## Features

### Authentication System
* Role-based login (Admin, Officer, Manager, Vendor)
* Secure session management and password hashing
* Public vendor registration with approval gates

### Vendor Management
* Vendor CRUD operations
* Vendor login mapping and secure profiles

### RFQ System
* RFQ creation with secure file attachments
* Dynamic item management
* Vendor assignment functionality

### Quotation System
* Vendor bidding system via dedicated self-service portal
* Itemized quotations
* One quote per vendor per RFQ constraint

### Approval Engine
* Quote comparison matrix
* Lowest price highlighting
* Manager approval workflow with status tracking

### Procurement Module
* Auto-generated Purchase Orders from approved quotes
* Invoice generation with tax calculations
* 1-click email dispatch for invoices

### Reporting
* Activity logs for system auditing
* Real-time notification system
* Analytics dashboard with CSV exports

---

## Tech Stack
* **Backend**: Vanilla PHP 8+ (No Frameworks, No Composer required)
* **Database**: MySQL (PDO Extension with Prepared Statements)
* **Frontend**: HTML5, Vanilla CSS, Vanilla JavaScript
* **UI Framework**: Bootstrap 5 (loaded via CDN)
* **Deployment**: Shared Hosting, XAMPP, or any standard LAMP stack compatible

---

## Installation Guide

Follow these steps to deploy VendorBridge on any standard hosting environment or localhost:

1. **Clone repository**
   Download or clone the repository to your local machine or server.

2. **Upload to hosting / htdocs**
   Upload the contents of the `vendorbridge-erp` folder directly to your `public_html`, `www`, or `htdocs` directory via FTP/File Manager.

3. **Import database SQL file**
   Access your hosting's phpMyAdmin (or local equivalent) and import the `database/vendorbridge.sql` file.

4. **Configure db_connect.php**
   Open `config/config.php` and update the environment variables or default values to match your database credentials:
   - `DB_HOST`
   - `DB_NAME`
   - `DB_USER`
   - `DB_PASS`

5. **Login credentials**
   Navigate to your domain to access the login portal.

---

## Default Login Accounts

Use the following credentials to explore the different roles:

* **Admin**: `admin@vendorbridge.com` | `admin123`
* **Officer**: `officer@vendorbridge.com` | `officer123`
* **Manager**: `manager@vendorbridge.com` | `manager123`
* **Vendor**: `vendor@vendorbridge.com` | `vendor123`

---

## Folder Structure

```text
vendorbridge-erp/
│
├── config/              # Dynamic BASE_URL and DB config
├── modules/
│   ├── auth/            # Registration & password recovery
│   ├── dashboard/       # Dashboards & Analytics
│   ├── vendors/         # Vendor profiles & management
│   ├── rfqs/            # RFQ creation & details
│   ├── quotations/      # Vendor bidding & portal
│   ├── approvals/       # Manager quote comparison
│   ├── procurement/     # Purchase Orders
│   ├── invoices/        # Billing & emails
│   └── logs/            # Global activity logs
│
├── assets/
│   ├── css/
│   └── js/
│
├── database/
│   └── vendorbridge.sql # Consolidated DB Schema + Demo Data
│
├── uploads/             # Secure file storage
│
├── index.php            # Root router
├── login.php            # Primary Auth Entry
├── logout.php           # Session destroyer
└── README.md            # You are here
```

---

## Security Notes

* **SQL Injection Prevention**: 100% of database queries use PDO prepared statements.
* **Authentication**: Password hashing (`bcrypt`) implemented natively via `password_hash()`.
* **Access Control**: Strict Role-Based Access Control (RBAC) enforced on every module.
* **Session Protection**: Cross-Site Request Forgery (CSRF) tokens and secure session regeneration enabled.
* **No Hardcoding**: `BASE_URL` dynamically detects protocols and host headers, making it completely portable across environments without manually editing URLs.

---

## Live Demo
[Live Demo URL (optional)](#)

---

## Future Enhancements
* AI vendor scoring and automated selection criteria
* Advanced visual analytics dashboard
* Multi-company / Multi-tenant ERP support
* API integration support (REST/GraphQL)
* Native mobile app extension for field officers
