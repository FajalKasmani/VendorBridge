<?php
/**
 * Authentication Check File
 * Ensures the user is logged in before accessing protected pages
 */

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in by verifying session variables
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // User is not logged in, redirect to login page
    header("Location: " . BASE_URL . "modules/auth/login.php");
    exit(); // Stop further script execution
}

// Optionally, you can add more checks here (e.g., role-based access control)
?>
