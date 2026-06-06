<?php
/**
 * Vendor Actions (Add, Update, Delete)
 */
session_start();
require_once '../../config/db_connect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2)) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ==========================================
    // ADD VENDOR
    // ==========================================
    if ($action === 'add') {
        $company_name = trim($_POST['company_name']);
        $gst_number = trim($_POST['gst_number'] ?? '');
        $category_id = $_POST['category_id'];
        $contact_name = trim($_POST['contact_name']);
        $contact_email = trim($_POST['contact_email']);
        $rating = $_POST['rating'] ?? 0;
        
        if (empty($company_name) || empty($contact_email) || empty($contact_name)) {
            $_SESSION['error_msg'] = "Company Name, Contact Person, and Email are required.";
            header("Location: add.php");
            exit();
        }

        try {
            $pdo->beginTransaction();

            // 1. Check if email already exists in users table
            $chk_stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $chk_stmt->execute([$contact_email]);
            if ($chk_stmt->fetch()) {
                $pdo->rollBack();
                $_SESSION['error_msg'] = "Email is already in use by another user account.";
                header("Location: add.php");
                exit();
            }

            // 2. Insert into users table (role 4 = Vendor)
            $default_password = password_hash('Vendor@123', PASSWORD_DEFAULT);
            $u_stmt = $pdo->prepare("INSERT INTO users (role_id, full_name, email, password) VALUES (4, ?, ?, ?)");
            $u_stmt->execute([$contact_name, $contact_email, $default_password]);
            
            $user_id = $pdo->lastInsertId();

            // 3. Insert into vendor_profiles table
            $v_stmt = $pdo->prepare("INSERT INTO vendor_profiles (user_id, company_name, gst_number, category_id, contact_email, status, rating) VALUES (?, ?, ?, ?, ?, 'active', ?)");
            $v_stmt->execute([$user_id, $company_name, $gst_number, $category_id, $contact_email, $rating]);

            $pdo->commit();
            
            $_SESSION['success_msg'] = "Vendor added successfully and user account created (Default Password: Vendor@123).";
            header("Location: list.php");
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error_msg'] = "Database Error: " . $e->getMessage();
            header("Location: add.php");
            exit();
        }
    }

    // ==========================================
    // UPDATE VENDOR
    // ==========================================
    if ($action === 'update') {
        $vendor_id = $_POST['vendor_id'];
        $company_name = trim($_POST['company_name']);
        $gst_number = trim($_POST['gst_number'] ?? '');
        $category_id = $_POST['category_id'];
        $contact_name = trim($_POST['contact_name']);
        $contact_email = trim($_POST['contact_email']);
        $status = $_POST['status'];
        $rating = $_POST['rating'];

        if (empty($company_name) || empty($contact_email) || empty($contact_name)) {
            $_SESSION['error_msg'] = "Company Name, Contact Person, and Email are required.";
            header("Location: edit.php?id=" . $vendor_id);
            exit();
        }

        try {
            $pdo->beginTransaction();

            // Get current user_id for this vendor
            $v_stmt = $pdo->prepare("SELECT user_id FROM vendor_profiles WHERE vendor_id = ?");
            $v_stmt->execute([$vendor_id]);
            $vendor = $v_stmt->fetch();

            if (!$vendor || !$vendor['user_id']) {
                $pdo->rollBack();
                $_SESSION['error_msg'] = "Invalid vendor profile or missing user account.";
                header("Location: edit.php?id=" . $vendor_id);
                exit();
            }
            $user_id = $vendor['user_id'];

            // Check if email belongs to someone else
            $chk_stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $chk_stmt->execute([$contact_email, $user_id]);
            if ($chk_stmt->fetch()) {
                $pdo->rollBack();
                $_SESSION['error_msg'] = "Email is already in use by another user account.";
                header("Location: edit.php?id=" . $vendor_id);
                exit();
            }

            // Update user table
            $u_stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE user_id = ?");
            $u_stmt->execute([$contact_name, $contact_email, $user_id]);

            // Update vendor profile
            $stmt = $pdo->prepare("UPDATE vendor_profiles SET company_name = ?, gst_number = ?, category_id = ?, contact_email = ?, status = ?, rating = ? WHERE vendor_id = ?");
            $stmt->execute([$company_name, $gst_number, $category_id, $contact_email, $status, $rating, $vendor_id]);

            $pdo->commit();
            $_SESSION['success_msg'] = "Vendor and User Profile updated successfully.";
            header("Location: list.php");
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error_msg'] = "Database Error: " . $e->getMessage();
            header("Location: edit.php?id=" . $vendor_id);
            exit();
        }
    }
}

// ==========================================
// DELETE VENDOR
// ==========================================
if ($action === 'delete') {
    $vendor_id = $_GET['id'] ?? null;
    if ($vendor_id) {
        try {
            $pdo->beginTransaction();
            
            // Fetch user_id before deleting
            $v_stmt = $pdo->prepare("SELECT user_id FROM vendor_profiles WHERE vendor_id = ?");
            $v_stmt->execute([$vendor_id]);
            $vendor = $v_stmt->fetch();

            // Delete Vendor Profile
            $pdo->prepare("DELETE FROM vendor_profiles WHERE vendor_id = ?")->execute([$vendor_id]);

            // Cascade delete user
            if ($vendor && $vendor['user_id']) {
                $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$vendor['user_id']]);
            }

            $pdo->commit();
            $_SESSION['success_msg'] = "Vendor and associated User Account deleted successfully.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error_msg'] = "Cannot delete vendor. They may be tied to existing RFQs or Quotations.";
        }
    }
    header("Location: list.php");
    exit();
}

header("Location: list.php");
exit();
?>
