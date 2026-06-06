<?php
/**
 * Main Entry Point
 */
require_once 'config/db_connect.php';

session_start();
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role_id'] == 4) {
        header("Location: " . BASE_URL . "modules/quotations/vendor_dashboard.php");
    } else {
        header("Location: " . BASE_URL . "modules/dashboard/dashboard.php");
    }
} else {
    header("Location: " . BASE_URL . "modules/auth/login.php");
}
exit();
?>
