<?php
/**
 * View Purchase Order
 */
require_once '../../config/db_connect.php';
require_once '../../includes/auth_check.php';

$po_id = $_GET['id'] ?? null;
if (!$po_id) {
    header("Location: po_list.php");
    exit();
}

$role_id = $_SESSION['role_id'];
$vendor_id = $_SESSION['vendor_id'] ?? null;

try {
    $stmt = $pdo->prepare("
        SELECT p.*, q.total_price, q.delivery_days, r.title, r.rfq_id, v.company_name, v.contact_email, v.phone, v.vendor_id
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

    // Fetch items
    $i_stmt = $pdo->prepare("
        SELECT qi.unit_price, ri.item_name, ri.quantity, ri.uom
        FROM quotation_items qi
        JOIN rfq_items ri ON qi.item_id = ri.item_id
        WHERE qi.quote_id = ?
    ");
    $i_stmt->execute([$po['quote_id']]);
    $items = $i_stmt->fetchAll();

    // Check if invoice exists
    $inv_stmt = $pdo->prepare("SELECT invoice_id, invoice_no FROM invoices WHERE po_id = ?");
    $inv_stmt->execute([$po_id]);
    $invoice = $inv_stmt->fetch();

} catch (PDOException $e) {
    die("Database Error");
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Purchase Order: <?php echo htmlspecialchars($po['po_number']); ?></h1>
    <div>
        <a href="print_po.php?id=<?php echo $po_id; ?>" target="_blank" class="btn btn-outline-secondary me-2"><i class="bi bi-printer"></i> Print</a>
        <?php if ($role_id == 1 || $role_id == 2): ?>
            <?php if (!$invoice): ?>
                <a href="../invoices/generate_invoice.php?po_id=<?php echo $po_id; ?>" class="btn btn-success"><i class="bi bi-file-earmark-plus"></i> Generate Invoice</a>
            <?php else: ?>
                <a href="../invoices/view_invoice.php?id=<?php echo $invoice['invoice_id']; ?>" class="btn btn-info text-white"><i class="bi bi-eye"></i> View Invoice (<?php echo $invoice['invoice_no']; ?>)</a>
            <?php endif; ?>
        <?php endif; ?>
        <a href="po_list.php" class="btn btn-secondary">Back to List</a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Vendor Details</h5>
            </div>
            <div class="card-body">
                <h6><strong><?php echo htmlspecialchars($po['company_name']); ?></strong></h6>
                <p class="mb-1 text-muted"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($po['contact_email']); ?></p>
                <p class="mb-0 text-muted"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($po['phone']); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Order Details</h5>
            </div>
            <div class="card-body">
                <p class="mb-1"><strong>PO Date:</strong> <?php echo date('F d, Y', strtotime($po['po_date'])); ?></p>
                <p class="mb-1"><strong>RFQ Ref:</strong> <a href="../rfqs/view.php?id=<?php echo $po['rfq_id']; ?>">#<?php echo $po['rfq_id']; ?> - <?php echo htmlspecialchars($po['title']); ?></a></p>
                <p class="mb-0"><strong>Status:</strong> <span class="badge bg-primary"><?php echo $po['status']; ?></span></p>
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
                        <th>Quantity</th>
                        <th class="text-end">Unit Price ($)</th>
                        <th class="text-end pe-4">Line Total ($)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <?php $line_total = $item['quantity'] * $item['unit_price']; ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-semibold"><?php echo htmlspecialchars($item['item_name']); ?></div>
                            <small class="text-muted">UOM: <?php echo htmlspecialchars($item['uom']); ?></small>
                        </td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td class="text-end"><?php echo number_format($item['unit_price'], 2); ?></td>
                        <td class="text-end pe-4 fw-bold"><?php echo number_format($line_total, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-light">
                    <tr>
                        <td colspan="3" class="text-end fw-bold py-3 text-uppercase">Grand Total:</td>
                        <td class="text-end pe-4 text-primary fs-4 fw-bold py-3">$<?php echo number_format($po['total_price'], 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
