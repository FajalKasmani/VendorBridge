<?php
/**
 * Logout File
 * Securely destroys session and redirects to login
 */
session_start();
require_once '../../config/db_connect.php';

// Unset all session variables
$_SESSION = [];

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login
header("Location: " . BASE_URL . "modules/auth/login.php");
exit();
?>
