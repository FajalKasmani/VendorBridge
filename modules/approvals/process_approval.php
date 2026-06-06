<?php
/**
 * Process Approval Action
 */
session_start();
require_once '../../config/db_connect.php';

// Role Check: Only Manager (3) can process approvals
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rfq_id = $_POST['rfq_id'];
    $quote_id = $_POST['quote_id'];
    $action = $_POST['action']; // Expected 'Approved'
    $remarks = trim($_POST['remarks']);
    $manager_id = $_SESSION['user_id'];

    if (empty($rfq_id) || empty($quote_id) || empty($action)) {
        $_SESSION['error_msg'] = "Invalid approval request.";
        header("Location: compare_quotes.php?rfq_id=" . $rfq_id);
        exit();
    }

    try {
        $pdo->beginTransaction();

        // 1. Verify RFQ is not already closed
        $r_stmt = $pdo->prepare("SELECT status FROM rfqs WHERE rfq_id = ? FOR UPDATE");
        $r_stmt->execute([$rfq_id]);
        $rfq = $r_stmt->fetch();
        
        if (!$rfq || $rfq['status'] === 'Closed') {
            $pdo->rollBack();
            $_SESSION['error_msg'] = "This RFQ is already closed and cannot be approved again.";
            header("Location: approval_panel.php");
            exit();
        }

        // 2. Insert into approvals table
        $ins_stmt = $pdo->prepare("INSERT INTO approvals (rfq_id, quote_id, manager_id, action, remarks) VALUES (?, ?, ?, ?, ?)");
        $ins_stmt->execute([$rfq_id, $quote_id, $manager_id, $action, $remarks]);

        // 3. Set the winning quote to Approved, all others to Rejected
        $q_upd_win = $pdo->prepare("UPDATE quotations SET status = 'Approved' WHERE quote_id = ?");
        $q_upd_win->execute([$quote_id]);

        $q_upd_lose = $pdo->prepare("UPDATE quotations SET status = 'Rejected' WHERE rfq_id = ? AND quote_id != ?");
        $q_upd_lose->execute([$rfq_id, $quote_id]);

        // 4. Update RFQ status to Closed
        $r_upd = $pdo->prepare("UPDATE rfqs SET status = 'Closed' WHERE rfq_id = ?");
        $r_upd->execute([$rfq_id]);

        $pdo->commit();

        $_SESSION['success_msg'] = "Approval successfully processed. RFQ is now Closed.";
        header("Location: rfq_summary.php?rfq_id=" . $rfq_id);
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Database Error: " . $e->getMessage();
        header("Location: compare_quotes.php?rfq_id=" . $rfq_id);
        exit();
    }
}

header("Location: approval_panel.php");
exit();
?>
