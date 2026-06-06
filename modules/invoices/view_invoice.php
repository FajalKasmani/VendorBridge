<?php
/**
 * View Invoice
 */
require_once '../../config/db_connect.php';
require_once '../../includes/auth_check.php';

$invoice_id = $_GET['id'] ?? null;
if (!$invoice_id) {
    header("Location: invoice_list.php");
    exit();
}

$role_id = $_SESSION['role_id'];
$vendor_id = $_SESSION['vendor_id'] ?? null;

try {
    $stmt = $pdo->prepare("
        SELECT i.*, p.po_number, p.po_date, q.total_price AS subtotal, q.quote_id, v.company_name, v.address, v.vendor_id
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

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Invoice: <?php echo htmlspecialchars($inv['invoice_no']); ?></h1>
    <div>
        <a href="print_invoice.php?id=<?php echo $invoice_id; ?>" target="_blank" class="btn btn-outline-secondary me-2"><i class="bi bi-printer"></i> Print Invoice</a>
        <a href="invoice_list.php" class="btn btn-secondary">Back to List</a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100 bg-primary text-white">
            <div class="card-body">
                <h5 class="mb-3 text-white-50">Invoice Summary</h5>
                <h2 class="mb-0 fw-bold">$<?php echo number_format($inv['final_amount'], 2); ?></h2>
                <p class="mb-0 text-white-50 mt-2">Status: <span class="badge bg-light text-dark"><?php echo $inv['payment_status']; ?></span></p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <h6 class="text-muted text-uppercase mb-1">Billed To</h6>
                        <p class="fw-bold mb-0"><?php echo htmlspecialchars($inv['company_name']); ?></p>
                        <small class="text-muted"><?php echo nl2br(htmlspecialchars($inv['address'] ?? '')); ?></small>
                    </div>
                    <div class="col-6 text-end">
                        <h6 class="text-muted text-uppercase mb-1">Reference</h6>
                        <p class="mb-0"><strong>PO Number:</strong> <a href="../procurement/view_po.php?id=<?php echo $inv['po_id']; ?>"><?php echo $inv['po_number']; ?></a></p>
                        <p class="mb-0"><strong>Issue Date:</strong> <?php echo date('M d, Y', strtotime($inv['issued_at'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Item Description</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Rate ($)</th>
                        <th class="text-end pe-4">Amount ($)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <?php $line_total = $item['quantity'] * $item['unit_price']; ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-semibold"><?php echo htmlspecialchars($item['item_name']); ?></div>
                        </td>
                        <td class="text-center"><?php echo $item['quantity']; ?> <?php echo htmlspecialchars($item['uom']); ?></td>
                        <td class="text-end"><?php echo number_format($item['unit_price'], 2); ?></td>
                        <td class="text-end pe-4"><?php echo number_format($line_total, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-light border-top border-2">
                    <tr>
                        <td colspan="3" class="text-end py-2">Subtotal:</td>
                        <td class="text-end pe-4 py-2">$<?php echo number_format($inv['subtotal'], 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-end py-2">Tax (18%):</td>
                        <td class="text-end pe-4 py-2 text-danger">+$<?php echo number_format($inv['tax_amount'], 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-end fw-bold py-3 fs-5">Final Amount Due:</td>
                        <td class="text-end pe-4 text-success fs-4 fw-bold py-3">$<?php echo number_format($inv['final_amount'], 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
