<?php
/**
 * Print Purchase Order
 */
require_once '../../config/db_connect.php';
require_once '../../includes/auth_check.php';

$po_id = $_GET['id'] ?? null;
if (!$po_id) {
    die("Invalid Request.");
}

$role_id = $_SESSION['role_id'];
$vendor_id = $_SESSION['vendor_id'] ?? null;

try {
    $stmt = $pdo->prepare("
        SELECT p.*, q.total_price, q.delivery_days, r.title, r.rfq_id, v.company_name, v.contact_email, v.vendor_id
        FROM purchase_orders p
        JOIN quotations q ON p.quote_id = q.quote_id
        JOIN rfqs r ON q.rfq_id = r.rfq_id
        JOIN vendor_profiles v ON q.vendor_id = v.vendor_id
        WHERE p.po_id = ?
    ");
    $stmt->execute([$po_id]);
    $po = $stmt->fetch();

    if (!$po) {
        die("PO not found.");
    }

    if ($role_id == 4 && $po['vendor_id'] != $vendor_id) {
        die("Access Denied.");
    }

    $i_stmt = $pdo->prepare("
        SELECT qi.unit_price, ri.item_name, ri.quantity, ri.uom
        FROM quotation_items qi
        JOIN rfq_items ri ON qi.item_id = ri.item_id
        WHERE qi.quote_id = ?
    ");
    $i_stmt->execute([$po['quote_id']]);
    $items = $i_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order: <?php echo htmlspecialchars($po['po_number']); ?></title>
    <!-- Use Bootstrap 5 for fast structural layout -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .document-wrapper {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        @media print {
            body { background: white; }
            .document-wrapper {
                box-shadow: none;
                margin: 0;
                padding: 0;
                max-width: 100%;
            }
            .no-print { display: none !important; }
            .print-exact {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

<div class="text-center mt-3 no-print">
    <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print Document</button>
    <button class="btn btn-secondary" onclick="window.close()">Close</button>
</div>

<div class="document-wrapper">
    <div class="row border-bottom pb-4 mb-4">
        <div class="col-6">
            <h2 class="fw-bold text-primary">VendorBridge ERP</h2>
            <p class="text-muted mb-0">123 Corporate Blvd, Tech City, TX 75001<br>procurement@vendorbridge.com<br>+1 (555) 123-4567</p>
        </div>
        <div class="col-6 text-end">
            <h1 class="text-uppercase text-secondary">Purchase Order</h1>
            <p class="mb-0 fs-5"><strong><?php echo $po['po_number']; ?></strong></p>
            <p class="text-muted">Date: <?php echo date('F d, Y', strtotime($po['po_date'])); ?></p>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-6">
            <h6 class="text-muted text-uppercase fw-bold">Vendor To:</h6>
            <h5 class="fw-bold"><?php echo htmlspecialchars($po['company_name']); ?></h5>
            <p class="mb-0"><?php echo htmlspecialchars($po['contact_email']); ?></p>
        </div>
        <div class="col-6 text-end">
            <h6 class="text-muted text-uppercase fw-bold">Order Details:</h6>
            <p class="mb-0"><strong>RFQ Reference:</strong> #<?php echo $po['rfq_id']; ?></p>
            <p class="mb-0"><strong>Delivery Timeline:</strong> <?php echo $po['delivery_days']; ?> Days</p>
        </div>
    </div>

    <table class="table table-bordered align-middle print-exact">
        <thead class="table-light">
            <tr>
                <th style="width: 50%;">Description</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Unit Price</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <?php $line_total = $item['quantity'] * $item['unit_price']; ?>
            <tr>
                <td>
                    <div class="fw-semibold"><?php echo htmlspecialchars($item['item_name']); ?></div>
                    <small class="text-muted">UOM: <?php echo htmlspecialchars($item['uom']); ?></small>
                </td>
                <td class="text-center"><?php echo $item['quantity']; ?></td>
                <td class="text-end">$<?php echo number_format($item['unit_price'], 2); ?></td>
                <td class="text-end fw-bold">$<?php echo number_format($line_total, 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-end fw-bold py-3 text-uppercase">Grand Total:</td>
                <td class="text-end text-primary fs-5 fw-bold py-3">$<?php echo number_format($po['total_price'], 2); ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="row mt-5 pt-5">
        <div class="col-6 text-center">
            <hr class="w-75 mx-auto border-dark">
            <p class="text-muted">Procurement Officer Signature</p>
        </div>
        <div class="col-6 text-center">
            <hr class="w-75 mx-auto border-dark">
            <p class="text-muted">Vendor Acceptance Signature</p>
        </div>
    </div>
</div>

<script>
    // Auto print prompt
    window.onload = function() {
        // window.print();
    };
</script>
</body>
</html>
