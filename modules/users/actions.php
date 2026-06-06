<?php
/**
 * User Management Actions
 */
require_once '../../config/db_connect.php';
session_start();

// Ensure user is logged in and is an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_user') {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $role_id = $_POST['role_id'];
        $password = $_POST['password'];

        if (empty($full_name) || empty($email) || empty($role_id) || empty($password)) {
            $_SESSION['error_msg'] = "All fields are required.";
            header("Location: add.php");
            exit();
        }

        try {
            // Check if email already exists
            $chk = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $_SESSION['error_msg'] = "Email already exists.";
                header("Location: add.php");
                exit();
            }

            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO users (role_id, full_name, email, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$role_id, $full_name, $email, $hashed_password]);
            
            // Log Activity
            $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, module) VALUES (?, ?, 'Users')");
            $log_stmt->execute([$_SESSION['user_id'], "Added new user: $full_name"]);

            $pdo->commit();

            $_SESSION['success_msg'] = "User account for $full_name created successfully.";
            header("Location: list.php");
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error_msg'] = "Database Error: " . $e->getMessage();
            header("Location: add.php");
            exit();
        }
    }

    if ($action === 'reset_password') {
        $user_id = $_POST['user_id'];
        $new_password = $_POST['new_password'];

        if (empty($user_id) || empty($new_password)) {
            $_SESSION['error_msg'] = "Invalid request.";
            header("Location: list.php");
            exit();
        }

        try {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            
            // Log Activity
            $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, module, reference_id) VALUES (?, ?, 'Users', ?)");
            $log_stmt->execute([$_SESSION['user_id'], "Reset password for User ID $user_id", $user_id]);

            $pdo->commit();

            $_SESSION['success_msg'] = "Password reset successfully.";
            header("Location: list.php");
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error_msg'] = "Database Error: " . $e->getMessage();
            header("Location: list.php");
            exit();
        }
    }
}

// Fallback
header("Location: list.php");
exit();
