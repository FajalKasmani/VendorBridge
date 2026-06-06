<?php
/**
 * Generate Purchase Order
 */
require_once '../../config/db_connect.php';
require_once '../../includes/auth_check.php';

// Only Admin (1) or Procurement Officer (2) can generate POs
if ($_SESSION['role_id'] == 3 || $_SESSION['role_id'] == 4) {
    die("Access Denied.");
}

$quote_id = $_GET['quote_id'] ?? null;
if (!$quote_id) {
    header("Location: " . BASE_URL . "modules/quotations/list.php");
    exit();
}

try {
    // 1. Fetch approved quotation
    $q_stmt = $pdo->prepare("SELECT * FROM quotations WHERE quote_id = ? AND status = 'Approved'");
    $q_stmt->execute([$quote_id]);
    $quote = $q_stmt->fetch();

    if (!$quote) {
        die("Invalid or Unapproved Quotation.");
    }

    // Check if PO already exists
    $po_check = $pdo->prepare("SELECT po_id FROM purchase_orders WHERE quote_id = ?");
    $po_check->execute([$quote_id]);
    if ($po_check->fetch()) {
        die("Purchase Order already exists for this quotation.");
    }

    $rfq_id = $quote['rfq_id'];

    // 4. Generate PO Number
    // Format: PO-YYYYMM-XXXX
    $month_prefix = "PO-" . date('Ym') . "-";
    
    // Get latest PO number to increment
    $num_stmt = $pdo->query("SELECT po_number FROM purchase_orders WHERE po_number LIKE '$month_prefix%' ORDER BY po_number DESC LIMIT 1");
    $last_po = $num_stmt->fetch();
    
    if ($last_po) {
        $last_num = (int)str_replace($month_prefix, "", $last_po['po_number']);
        $new_num = str_pad($last_num + 1, 4, "0", STR_PAD_LEFT);
    } else {
        $new_num = "0001";
    }
    
    $po_number = $month_prefix . $new_num;

    // Transaction
    $pdo->beginTransaction();

    // 5. Insert into purchase_orders
    $insert_po = $pdo->prepare("INSERT INTO purchase_orders (po_number, quote_id, po_date, status) VALUES (?, ?, CURDATE(), 'Generated')");
    $insert_po->execute([$po_number, $quote_id]);
    $new_po_id = $pdo->lastInsertId();

    // 6. Update RFQ Status
    $update_rfq = $pdo->prepare("UPDATE rfqs SET status = 'PO Generated' WHERE rfq_id = ?");
    $update_rfq->execute([$rfq_id]);

    // 7. Insert Activity Log
    $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, module, reference_id) VALUES (?, ?, 'Procurement', ?)");
    $log_stmt->execute([$_SESSION['user_id'], "Generated Purchase Order $po_number", $new_po_id]);

    $pdo->commit();

    header("Location: view_po.php?id=" . $new_po_id);
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Database Error: " . $e->getMessage());
}
