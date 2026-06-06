<?php
/**
 * Generate Invoice
 */
require_once '../../includes/auth_check.php';
require_once '../../config/db_connect.php';

if ($_SESSION['role_id'] == 3 || $_SESSION['role_id'] == 4) {
    die("Access Denied.");
}

$po_id = $_GET['po_id'] ?? null;
if (!$po_id) {
    die("Invalid request.");
}

try {
    // 1. Fetch PO
    $po_stmt = $pdo->prepare("
        SELECT p.po_id, p.quote_id, p.po_number, q.total_price 
        FROM purchase_orders p
        JOIN quotations q ON p.quote_id = q.quote_id
        WHERE p.po_id = ?
    ");
    $po_stmt->execute([$po_id]);
    $po = $po_stmt->fetch();

    if (!$po) {
        die("Purchase Order not found.");
    }

    // Check if invoice exists
    $chk = $pdo->prepare("SELECT invoice_id FROM invoices WHERE po_id = ?");
    $chk->execute([$po_id]);
    if ($chk->fetch()) {
        die("Invoice already generated for this PO.");
    }

    // 2. Calculate tax (18% default)
    $subtotal = (float)$po['total_price'];
    $tax_rate = 0.18;
    $tax_amount = $subtotal * $tax_rate;
    $final_amount = $subtotal + $tax_amount;

    // 4. Generate Invoice Number (INV-YYYYMM-XXXX)
    $prefix = "INV-" . date('Ym') . "-";
    $num_stmt = $pdo->query("SELECT invoice_no FROM invoices WHERE invoice_no LIKE '$prefix%' ORDER BY invoice_no DESC LIMIT 1");
    $last_inv = $num_stmt->fetch();

    if ($last_inv) {
        $last_num = (int)str_replace($prefix, "", $last_inv['invoice_no']);
        $new_num = str_pad($last_num + 1, 4, "0", STR_PAD_LEFT);
    } else {
        $new_num = "0001";
    }
    
    $invoice_no = $prefix . $new_num;

    $pdo->beginTransaction();

    // 5. Insert Invoice
    $ins = $pdo->prepare("INSERT INTO invoices (invoice_no, po_id, tax_amount, final_amount, payment_status) VALUES (?, ?, ?, ?, 'Pending')");
    $ins->execute([$invoice_no, $po_id, $tax_amount, $final_amount]);
    $new_inv_id = $pdo->lastInsertId();

    // Log Activity
    $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, module, reference_id) VALUES (?, ?, 'Invoices', ?)");
    $log_stmt->execute([$_SESSION['user_id'], "Generated Invoice $invoice_no for PO " . $po['po_number'], $new_inv_id]);

    $pdo->commit();

    header("Location: view_invoice.php?id=" . $new_inv_id);
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Database Error: " . $e->getMessage());
}
