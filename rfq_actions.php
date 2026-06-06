<?php
/**
 * RFQ Actions (Update, Delete, Assign Vendors)
 */
session_start();
require_once 'config/db_connect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2)) {
    header("Location: dashboard.php");
    exit();
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ==========================================
    // UPDATE RFQ
    // ==========================================
    if ($action === 'update') {
        $rfq_id = $_POST['rfq_id'];
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $deadline = $_POST['deadline'];
        $status = $_POST['status'];
        
        $item_names = $_POST['item_name'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $uoms = $_POST['uom'] ?? [];

        if (empty($title) || empty($deadline)) {
            $_SESSION['error_msg'] = "Title and Deadline are required.";
            header("Location: edit_rfq.php?id=" . $rfq_id);
            exit();
        }

        if (count($item_names) === 0 || empty($item_names[0])) {
            $_SESSION['error_msg'] = "Minimum one item is required.";
            header("Location: edit_rfq.php?id=" . $rfq_id);
            exit();
        }

        try {
            $pdo->beginTransaction();

            // Update RFQ master
            $stmt = $pdo->prepare("UPDATE rfqs SET title = ?, description = ?, deadline = ?, status = ? WHERE rfq_id = ?");
            $stmt->execute([$title, $description, $deadline, $status, $rfq_id]);

            // Replace items (Delete all existing and insert new ones to handle removals/additions easily)
            $pdo->prepare("DELETE FROM rfq_items WHERE rfq_id = ?")->execute([$rfq_id]);
            
            $item_stmt = $pdo->prepare("INSERT INTO rfq_items (rfq_id, item_name, quantity, uom) VALUES (?, ?, ?, ?)");
            for ($i = 0; $i < count($item_names); $i++) {
                $i_name = trim($item_names[$i]);
                $i_qty = intval($quantities[$i]);
                $i_uom = trim($uoms[$i]);
                if (!empty($i_name) && $i_qty > 0) {
                    $item_stmt->execute([$rfq_id, $i_name, $i_qty, $i_uom]);
                }
            }

            $pdo->commit();
            $_SESSION['success_msg'] = "RFQ updated successfully.";
            header("Location: rfqs.php");
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error_msg'] = "Database Error: " . $e->getMessage();
            header("Location: edit_rfq.php?id=" . $rfq_id);
            exit();
        }
    }

    // ==========================================
    // ASSIGN VENDORS
    // ==========================================
    if ($action === 'assign') {
        $rfq_id = $_POST['rfq_id'];
        $vendors = $_POST['vendors'] ?? []; // Array of vendor_ids

        try {
            $pdo->beginTransaction();

            // Clear previous assignments
            $pdo->prepare("DELETE FROM rfq_assignments WHERE rfq_id = ?")->execute([$rfq_id]);

            // Insert new assignments if any
            if (count($vendors) > 0) {
                $assign_stmt = $pdo->prepare("INSERT INTO rfq_assignments (rfq_id, vendor_id) VALUES (?, ?)");
                foreach ($vendors as $v_id) {
                    $assign_stmt->execute([$rfq_id, $v_id]);
                }
                
                // Update status to 'Assigned' if currently 'Draft' or 'Open'
                $pdo->prepare("UPDATE rfqs SET status = 'Assigned' WHERE rfq_id = ? AND status IN ('Draft', 'Open')")->execute([$rfq_id]);
                
                $_SESSION['success_msg'] = "Vendors assigned successfully.";
            } else {
                $_SESSION['success_msg'] = "Assignments cleared successfully.";
            }

            $pdo->commit();
            header("Location: rfqs.php");
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error_msg'] = "Assignment Error: " . $e->getMessage();
            header("Location: assign_vendors.php?id=" . $rfq_id);
            exit();
        }
    }
}

// ==========================================
// DELETE RFQ
// ==========================================
if ($action === 'delete') {
    $rfq_id = $_GET['id'] ?? null;
    if ($rfq_id) {
        try {
            $pdo->beginTransaction();
            // Delete dependent records first due to strict constraints (though ON DELETE CASCADE handles this if supported)
            $pdo->prepare("DELETE FROM rfq_items WHERE rfq_id = ?")->execute([$rfq_id]);
            $pdo->prepare("DELETE FROM rfq_assignments WHERE rfq_id = ?")->execute([$rfq_id]);
            // Delete RFQ
            $pdo->prepare("DELETE FROM rfqs WHERE rfq_id = ?")->execute([$rfq_id]);
            $pdo->commit();
            
            $_SESSION['success_msg'] = "RFQ deleted successfully.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error_msg'] = "Cannot delete RFQ: " . $e->getMessage();
        }
    }
    header("Location: rfqs.php");
    exit();
}

header("Location: rfqs.php");
exit();
?>
