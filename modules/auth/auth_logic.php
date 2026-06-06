<?php
/**
 * Authentication Logic File
 * Process login credentials and start session securely
 */
session_start();
require_once '../../config/db_connect.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Please enter both email and password.";
        header("Location: " . BASE_URL . "modules/auth/login.php");
        exit();
    }

    try {
        // Prepare statement to prevent SQL Injection
        $stmt = $pdo->prepare("SELECT user_id, role_id, full_name, password FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        // Fetch user record
        $user = $stmt->fetch();

        // Verify password hash
        if ($user && password_verify($password, $user['password'])) {
            // Password is correct, set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['username'] = $user['username'];
            
            // Redirect Vendor to Vendor Dashboard, others to generic dashboard
            if ($user['role_id'] == 4) {
                header("Location: " . BASE_URL . "modules/quotations/vendor_dashboard.php");
            } else {
                header("Location: " . BASE_URL . "modules/dashboard/dashboard.php");
            }
            exit();

            // Prevent session fixation
            session_regenerate_id(true);

            // Redirect to dashboard
            header("Location: " . BASE_URL . "modules/dashboard/dashboard.php");
            exit();
        } else {
            // Invalid credentials
            $_SESSION['login_error'] = "Invalid email or password.";
            header("Location: " . BASE_URL . "modules/auth/login.php");
            exit();
        }

    } catch (PDOException $e) {
        // Log the error securely and show generic error
        error_log("Login Error: " . $e->getMessage());
        $_SESSION['login_error'] = "A system error occurred. Please try again later.";
        header("Location: " . BASE_URL . "modules/auth/login.php");
        exit();
    }

} else {
    // Direct access to this file is not allowed
    header("Location: " . BASE_URL . "modules/auth/login.php");
    exit();
}
?>
