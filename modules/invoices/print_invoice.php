<?php
/**
 * Print Invoice
 */
require_once '../../includes/auth_check.php';
require_once '../../config/db_connect.php';

$invoice_id = $_GET['id'] ?? null;
if (!$invoice_id) {
    die("Invalid Request.");
}

$role_id = $_SESSION['role_id'];
$vendor_id = $_SESSION['vendor_id'] ?? null;

try {
    $stmt = $pdo->prepare("
        SELECT i.*, p.po_number, p.po_date, q.total_price AS subtotal, q.quote_id, v.company_name, v.address, v.contact_email, v.phone, v.vendor_id
        FROM invoices i
        JOIN purchase_orders p ON i.po_id = p.po_id
        JOIN quotations q ON p.quote_id = q.quote_id
        JOIN vendor_profiles v ON q.vendor_id = v.vendor_id
        WHERE i.invoice_id = ?
    ");
    $stmt->execute([$invoice_id]);
    $inv = $stmt->fetch();

    if (!$inv) {
        die("Invoice not found.");
    }

    if ($role_id == 4 && $inv['vendor_id'] != $vendor_id) {
        die("Access Denied.");
    }

    $i_stmt = $pdo->prepare("
        SELECT qi.unit_price, ri.item_name, ri.quantity, ri.uom
        FROM quotation_items qi
        JOIN rfq_items ri ON qi.item_id = ri.item_id
        WHERE qi.quote_id = ?
    ");
    $i_stmt->execute([$inv['quote_id']]);
    $items = $i_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice: <?php echo htmlspecialchars($inv['invoice_no']); ?></title>
    <!-- Use Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .document-wrapper {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            padding: 50px;
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
        .invoice-title { letter-spacing: 2px; }
    </style>
</head>
<body>

<div class="text-center mt-3 no-print">
    <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print Invoice</button>
    <button class="btn btn-secondary" onclick="window.close()">Close</button>
</div>

<div class="document-wrapper">
    <div class="row pb-4 mb-4 border-bottom border-2 border-dark">
        <div class="col-6">
            <h1 class="fw-bold text-dark invoice-title">INVOICE</h1>
            <h5 class="text-secondary"><?php echo $inv['invoice_no']; ?></h5>
        </div>
        <div class="col-6 text-end">
            <h2 class="fw-bold text-primary mb-1">VendorBridge ERP</h2>
            <p class="text-muted mb-0 small">123 Corporate Blvd, Tech City, TX 75001<br>billing@vendorbridge.com<br>+1 (555) 999-8888</p>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-7">
            <h6 class="text-muted text-uppercase fw-bold mb-2">Billed To:</h6>
            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($inv['company_name']); ?></h5>
            <p class="mb-0 text-muted"><?php echo nl2br(htmlspecialchars($inv['address'] ?? '')); ?></p>
            <p class="mb-0 text-muted"><?php echo htmlspecialchars($inv['contact_email']); ?></p>
        </div>
        <div class="col-5">
            <table class="table table-sm table-borderless mb-0">
                <tr>
                    <td class="text-muted fw-bold">Date Issued:</td>
                    <td class="text-end"><?php echo date('M d, Y', strtotime($inv['issued_at'])); ?></td>
                </tr>
                <tr>
                    <td class="text-muted fw-bold">PO Number:</td>
                    <td class="text-end"><?php echo $inv['po_number']; ?></td>
                </tr>
                <tr>
                    <td class="text-muted fw-bold">Status:</td>
                    <td class="text-end fw-bold <?php echo ($inv['payment_status'] == 'Paid') ? 'text-success' : 'text-danger'; ?>"><?php echo strtoupper($inv['payment_status']); ?></td>
                </tr>
            </table>
        </div>
    </div>

    <table class="table table-bordered align-middle print-exact">
        <thead class="table-dark">
            <tr>
                <th style="width: 50%;">Description</th>
                <th class="text-center">Qty</th>
                <th class="text-end">Rate</th>
                <th class="text-end">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <?php $line_total = $item['quantity'] * $item['unit_price']; ?>
            <tr>
                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                <td class="text-center"><?php echo $item['quantity']; ?></td>
                <td class="text-end">$<?php echo number_format($item['unit_price'], 2); ?></td>
                <td class="text-end fw-bold">$<?php echo number_format($line_total, 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot class="border-top border-2">
            <tr>
                <td colspan="3" class="text-end py-2">Subtotal:</td>
                <td class="text-end py-2">$<?php echo number_format($inv['subtotal'], 2); ?></td>
            </tr>
            <tr>
                <td colspan="3" class="text-end py-2">Tax (18%):</td>
                <td class="text-end py-2 text-danger">+$<?php echo number_format($inv['tax_amount'], 2); ?></td>
            </tr>
            <tr class="table-light">
                <td colspan="3" class="text-end fw-bold py-3 fs-5">TOTAL DUE:</td>
                <td class="text-end fs-4 fw-bold py-3">$<?php echo number_format($inv['final_amount'], 2); ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="mt-5 pt-4 text-center text-muted small border-top">
        <p class="mb-1">Please make all checks payable to VendorBridge ERP.</p>
        <p class="mb-0">Thank you for your business!</p>
    </div>
</div>

<script>
    window.onload = function() {
        // window.print();
    };
</script>
</body>
</html>
