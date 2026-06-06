<?php
/**
 * Save RFQ Logic (Create new RFQ)
 */
session_start();
require_once '../../config/db_connect.php';

// Role Check: Only Admin (1) & Officer (2)
if ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2) {
    $_SESSION['error_msg'] = "Access Denied.";
    header("Location: " . BASE_URL . "modules/dashboard/dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $deadline = $_POST['deadline'];
    $created_by = $_SESSION['user_id'];
    
    // Arrays from dynamic inputs
    $item_names = $_POST['item_name'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $uoms = $_POST['uom'] ?? [];

    // Validations
    if (empty($title) || empty($deadline)) {
        $_SESSION['error_msg'] = "Title and Deadline are required.";
        header("Location: " . BASE_URL . "modules/rfqs/create.php");
        exit();
    }

    if (strtotime($deadline) < strtotime(date('Y-m-d'))) {
        $_SESSION['error_msg'] = "Deadline must be a future date.";
        header("Location: " . BASE_URL . "modules/rfqs/create.php");
        exit();
    }

    if (count($item_names) === 0 || empty($item_names[0])) {
        $_SESSION['error_msg'] = "Minimum one item is required.";
        header("Location: " . BASE_URL . "modules/rfqs/create.php");
        exit();
    }

    try {
        // BEGIN TRANSACTION
        $pdo->beginTransaction();

        // 1. Insert into rfqs
        $stmt = $pdo->prepare("INSERT INTO rfqs (title, description, deadline, created_by, status) VALUES (?, ?, ?, ?, 'Open')");
        $stmt->execute([$title, $description, $deadline, $created_by]);
        
        $rfq_id = $pdo->lastInsertId();

        // 2. Loop through item rows and insert into rfq_items
        $item_stmt = $pdo->prepare("INSERT INTO rfq_items (rfq_id, item_name, quantity, uom) VALUES (?, ?, ?, ?)");
        
        for ($i = 0; $i < count($item_names); $i++) {
            $i_name = trim($item_names[$i]);
            $i_qty = intval($quantities[$i]);
            $i_uom = trim($uoms[$i]);

            if (!empty($i_name) && $i_qty > 0) {
                $item_stmt->execute([$rfq_id, $i_name, $i_qty, $i_uom]);
            }
        }

        // COMMIT TRANSACTION
        $pdo->commit();

        $_SESSION['success_msg'] = "RFQ created successfully.";
        header("Location: " . BASE_URL . "modules/rfqs/list.php");
        exit();

    } catch (PDOException $e) {
        // ROLLBACK on error
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Failed to create RFQ: " . $e->getMessage();
        header("Location: " . BASE_URL . "modules/rfqs/create.php");
        exit();
    }
} else {
    header("Location: " . BASE_URL . "modules/rfqs/create.php");
    exit();
}
?>
