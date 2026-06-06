<?php
/**
 * Reset Vendor Password to Vendor@123
 */
session_start();
require_once '../../config/db_connect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2)) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$vendor_id = $_GET['id'] ?? null;

if ($vendor_id) {
    try {
        $pdo->beginTransaction();
        
        $v_stmt = $pdo->prepare("SELECT user_id FROM vendor_profiles WHERE vendor_id = ?");
        $v_stmt->execute([$vendor_id]);
        $vendor = $v_stmt->fetch();

        if ($vendor && $vendor['user_id']) {
            $new_hash = password_hash('Vendor@123', PASSWORD_DEFAULT);
            $u_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $u_stmt->execute([$new_hash, $vendor['user_id']]);
            
            $pdo->commit();
            $_SESSION['success_msg'] = "Password successfully reset to 'Vendor@123'.";
        } else {
            $pdo->rollBack();
            $_SESSION['error_msg'] = "Vendor does not have a linked user account.";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Database Error: " . $e->getMessage();
    }
}

header("Location: view.php?id=" . $vendor_id);
exit();
?>
