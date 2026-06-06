# VendorBridge ERP

VendorBridge is a lightweight, responsive Procurement and Vendor Management Enterprise Resource Planning (ERP) system built with Vanilla PHP and MySQL. It is designed to be straightforward, secure, and easy to deploy on any standard LAMP/WAMP stack like XAMPP without relying on heavy frameworks or external dependencies.

## 🚀 Features

- **Role-Based Access Control (RBAC)**: Secure access tailored for Admins, Procurement Officers, Managers, and Vendors.
- **Vendor Management**: Complete CRUD operations for vendor profiles.
- **Secure Authentication**: Utilizes `password_hash()` and `password_verify()` with secure session management.
- **Database Security**: All SQL queries strictly use **PDO Prepared Statements** to prevent SQL injection.
- **Responsive UI**: Clean, professional interface styled with **Bootstrap 5**.

## 🛠️ Tech Stack

- **Backend**: Plain PHP 8+ (No Frameworks, No Composer)
- **Database**: MySQL (PDO Extension)
- **Frontend**: HTML5, Vanilla CSS, Vanilla JS, Bootstrap 5 CDN, Bootstrap Icons
- **Environment**: XAMPP (or any standard web server)

## ⚙️ Installation & Setup

1. **Clone the Repository**
   ```bash
   git clone https://github.com/FajalKasmani/VendorBridge.git
   ```
   *Place the `VendorBridge` folder inside your `htdocs` (XAMPP) or `www` (WAMP) directory.*

2. **Database Setup**
   - Open **phpMyAdmin** (`http://localhost/phpmyadmin/`).
   - You don't need to manually create the database; the SQL scripts handle it.
   - Import `database.sql` (Phase 1 core schema).
   - Import `phase2_db.sql` (Phase 2 Vendor Management schema).

3. **Configure Database Connection**
   - The connection is pre-configured for a default XAMPP setup (user: `root`, password: `[empty]`).
   - If your setup is different, update the credentials in `config/db_connect.php`.

4. **Seed Demo Users**
   - To ensure your password hashes match your local PHP environment natively, open your browser and run:
     `http://localhost/VendorBridge/seed_users.php`
   - This will populate the `roles` and `users` tables securely.

## 👥 User Roles & Demo Credentials

Use these credentials to log in and explore the role-based features.

| Role | Email | Password | Access Level |
|------|-------|----------|--------------|
| **Admin** | `admin@vendorbridge.com` | `admin123` | Full System Access |
| **Procurement Officer** | `officer@vendorbridge.com` | `officer123` | Vendors, RFQs |
| **Manager** | `manager@vendorbridge.com` | `manager123` | Approvals, Reports |
| **Vendor** | `vendor@vendorbridge.com` | `vendor123` | Self-Service, Quotations |

## 📁 Project Structure

```text
vendorbridge/
│
├── assets/
│   ├── css/style.css       # Custom ERP styling
│   └── js/script.js        # Custom interactivity
│
├── config/
│   └── db_connect.php      # PDO database connection
│
├── includes/
│   ├── auth_check.php      # Session and security guard
│   ├── header.php          # Top Navbar & Bootstrap CDN
│   ├── sidebar.php         # Role-based sidebar navigation
│   └── footer.php          # Footer & JS Scripts
│
├── add_vendor.php          # UI: Create new vendor
├── auth_logic.php          # Logic: Processes login
├── dashboard.php           # UI: Main ERP dashboard
├── database.sql            # Core database schema
├── edit_vendor.php         # UI: Edit vendor profile
├── login.php               # UI: Authentication portal
├── logout.php              # Logic: Destroys session
├── phase2_db.sql           # Schema updates for Phase 2
├── seed_users.php          # Script: Generates valid users/hashes
├── vendor_actions.php      # Logic: Vendor CRUD operations
├── vendors.php             # UI: Vendor listing/management
└── view_vendor.php         # UI: Read-only vendor profile
```

## 🔒 Security Measures

- Direct access to internal pages redirects to `login.php`.
- Session fixation protection on login.
- `password_hash()` (BCRYPT) used for all user passwords.
- Form inputs validated and sanitized before database insertion.
- Prepared statements (`?`) used consistently to prevent SQL Injection.
- Unique constraints on database columns (e.g., Emails, GST numbers) to maintain data integrity.

---
*Developed for the VendorBridge ERP Project.*
