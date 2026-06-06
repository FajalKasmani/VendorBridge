<?php
/**
 * Vendor Actions logic (CRUD operations)
 */
session_start();
require_once 'config/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check Role (Only Admin & Procurement Officer)
if ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2) {
    $_SESSION['error_msg'] = "Access Denied.";
    header("Location: dashboard.php");
    exit();
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ==========================================
    // CREATE VENDOR
    // ==========================================
    if ($action === 'create') {
        $company_name = trim($_POST['company_name']);
        $gst_number = trim($_POST['gst_number']);
        $category_id = $_POST['category_id'];
        $contact_email = trim($_POST['contact_email']);
        $status = $_POST['status'];
        $rating = $_POST['rating'] ?? 0;

        // Basic validation
        if (empty($company_name) || empty($gst_number) || empty($contact_email) || empty($category_id)) {
            $_SESSION['error_msg'] = "Please fill all required fields.";
            header("Location: add_vendor.php");
            exit();
        }

        try {
            // Check for duplicate GST or Email
            $stmt = $pdo->prepare("SELECT vendor_id FROM vendor_profiles WHERE gst_number = ? OR contact_email = ?");
            $stmt->execute([$gst_number, $contact_email]);
            if ($stmt->rowCount() > 0) {
                $_SESSION['error_msg'] = "GST Number or Email already exists.";
                header("Location: add_vendor.php");
                exit();
            }

            // Insert new vendor
            $insert = $pdo->prepare("INSERT INTO vendor_profiles (company_name, gst_number, category_id, contact_email, status, rating) VALUES (?, ?, ?, ?, ?, ?)");
            $insert->execute([$company_name, $gst_number, $category_id, $contact_email, $status, $rating]);

            $_SESSION['success_msg'] = "Vendor added successfully.";
            header("Location: vendors.php");
            exit();

        } catch (PDOException $e) {
            $_SESSION['error_msg'] = "Database Error: " . $e->getMessage();
            header("Location: add_vendor.php");
            exit();
        }
    }

    // ==========================================
    // UPDATE VENDOR
    // ==========================================
    if ($action === 'update') {
        $vendor_id = $_POST['vendor_id'];
        $company_name = trim($_POST['company_name']);
        $gst_number = trim($_POST['gst_number']);
        $category_id = $_POST['category_id'];
        $contact_email = trim($_POST['contact_email']);
        $status = $_POST['status'];
        $rating = $_POST['rating'] ?? 0;

        if (empty($vendor_id) || empty($company_name) || empty($gst_number) || empty($contact_email)) {
            $_SESSION['error_msg'] = "Please fill all required fields.";
            header("Location: edit_vendor.php?id=" . $vendor_id);
            exit();
        }

        try {
            // Check for duplicate GST or Email (excluding self)
            $stmt = $pdo->prepare("SELECT vendor_id FROM vendor_profiles WHERE (gst_number = ? OR contact_email = ?) AND vendor_id != ?");
            $stmt->execute([$gst_number, $contact_email, $vendor_id]);
            if ($stmt->rowCount() > 0) {
                $_SESSION['error_msg'] = "GST Number or Email already belongs to another vendor.";
                header("Location: edit_vendor.php?id=" . $vendor_id);
                exit();
            }

            // Update vendor
            $update = $pdo->prepare("UPDATE vendor_profiles SET company_name = ?, gst_number = ?, category_id = ?, contact_email = ?, status = ?, rating = ? WHERE vendor_id = ?");
            $update->execute([$company_name, $gst_number, $category_id, $contact_email, $status, $rating, $vendor_id]);

            $_SESSION['success_msg'] = "Vendor updated successfully.";
            header("Location: vendors.php");
            exit();

        } catch (PDOException $e) {
            $_SESSION['error_msg'] = "Database Error: " . $e->getMessage();
            header("Location: edit_vendor.php?id=" . $vendor_id);
            exit();
        }
    }
}

// ==========================================
// DELETE VENDOR (GET request usually, or POST via form)
// ==========================================
if ($action === 'delete') {
    $vendor_id = $_GET['id'] ?? null;
    
    if ($vendor_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM vendor_profiles WHERE vendor_id = ?");
            $stmt->execute([$vendor_id]);
            $_SESSION['success_msg'] = "Vendor deleted successfully.";
        } catch (PDOException $e) {
            $_SESSION['error_msg'] = "Cannot delete vendor. They might have dependent records.";
        }
    }
    header("Location: vendors.php");
    exit();
}

// Fallback
header("Location: vendors.php");
exit();
?>
