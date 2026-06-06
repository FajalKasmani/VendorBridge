<?php
/**
 * Save Quotation endpoint
 */
session_start();
require_once '../../config/db_connect.php';

// Role Check
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 4) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rfq_id = $_POST['rfq_id'];
    $vendor_id = $_SESSION['user_id'];
    $delivery_days = intval($_POST['delivery_days']);
    $item_ids = $_POST['item_ids'] ?? [];
    $unit_prices = $_POST['unit_prices'] ?? [];

    if (empty($rfq_id) || empty($item_ids) || $delivery_days <= 0) {
        $_SESSION['error_msg'] = "Invalid form submission. Please fill all required fields.";
        header("Location: submit_quote.php?rfq_id=" . $rfq_id);
        exit();
    }

    try {
        $pdo->beginTransaction();

        // 1. Check if quotation already exists for this RFQ + Vendor
        $chk_stmt = $pdo->prepare("SELECT quote_id FROM quotations WHERE rfq_id = ? AND vendor_id = ? FOR UPDATE");
        $chk_stmt->execute([$rfq_id, $vendor_id]);
        if ($chk_stmt->fetch()) {
            $pdo->rollBack();
            $_SESSION['error_msg'] = "You have already submitted a quotation for this RFQ.";
            header("Location: assigned_rfqs.php");
            exit();
        }

        // 2. Insert Quotation Header (Initial total_price = 0)
        $q_stmt = $pdo->prepare("INSERT INTO quotations (rfq_id, vendor_id, delivery_days, total_price, status) VALUES (?, ?, ?, 0, 'Submitted')");
        $q_stmt->execute([$rfq_id, $vendor_id, $delivery_days]);
        $quote_id = $pdo->lastInsertId();

        // 3. Loop items, calculate total, and insert into quotation_items
        $total_price = 0.00;
        
        $item_stmt = $pdo->prepare("INSERT INTO quotation_items (quote_id, item_id, unit_price) VALUES (?, ?, ?)");
        $qty_stmt = $pdo->prepare("SELECT quantity FROM rfq_items WHERE item_id = ? AND rfq_id = ?");

        for ($i = 0; $i < count($item_ids); $i++) {
            $i_id = $item_ids[$i];
            $u_price = floatval($unit_prices[$i]);
            
            if ($u_price < 0) $u_price = 0;

            // Get Quantity to calculate line total accurately
            $qty_stmt->execute([$i_id, $rfq_id]);
            $item_data = $qty_stmt->fetch();
            
            if ($item_data) {
                $qty = $item_data['quantity'];
                $line_total = $qty * $u_price;
                $total_price += $line_total;

                // Insert line item
                $item_stmt->execute([$quote_id, $i_id, $u_price]);
            }
        }

        // 4. Update the Quotation Header with the calculated total_price
        $upd_stmt = $pdo->prepare("UPDATE quotations SET total_price = ? WHERE quote_id = ?");
        $upd_stmt->execute([$total_price, $quote_id]);

        $pdo->commit();

        $_SESSION['success_msg'] = "Quotation submitted successfully!";
        header("Location: my_quotes.php");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = "Database Error: " . $e->getMessage();
        header("Location: submit_quote.php?rfq_id=" . $rfq_id);
        exit();
    }
}

header("Location: assigned_rfqs.php");
exit();
?>
