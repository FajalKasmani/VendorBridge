<?php
/**
 * List Invoices
 */
require_once '../../includes/auth_check.php';
require_once '../../config/db_connect.php';

$role_id = $_SESSION['role_id'];
$vendor_id = $_SESSION['vendor_id'] ?? null;

$status_filter = $_GET['status'] ?? '';
$vendor_filter = $_GET['vendor'] ?? '';

try {
    $query = "
        SELECT i.*, p.po_number, v.company_name, v.vendor_id
        FROM invoices i
        JOIN purchase_orders p ON i.po_id = p.po_id
        JOIN quotations q ON p.quote_id = q.quote_id
        JOIN vendor_profiles v ON q.vendor_id = v.vendor_id
        WHERE 1=1
    ";
    $params = [];

    if ($role_id == 4) {
        $query .= " AND q.vendor_id = ?";
        $params[] = $vendor_id;
    } else {
        if ($vendor_filter) {
            $query .= " AND q.vendor_id = ?";
            $params[] = $vendor_filter;
        }
    }

    if ($status_filter) {
        $query .= " AND i.payment_status = ?";
        $params[] = $status_filter;
    }

    $query .= " ORDER BY i.invoice_id DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $invoices = $stmt->fetchAll();

    $v_stmt = $pdo->query("SELECT vendor_id, company_name FROM vendor_profiles ORDER BY company_name");
    $vendors = $v_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error");
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Invoices</h1>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body bg-light">
        <form method="GET" class="row g-3">
            <?php if ($role_id != 4): ?>
            <div class="col-md-4">
                <select name="vendor" class="form-select">
                    <option value="">All Vendors</option>
                    <?php foreach ($vendors as $v): ?>
                        <option value="<?php echo $v['vendor_id']; ?>" <?php echo ($vendor_filter == $v['vendor_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($v['company_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?php echo ($status_filter == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="Paid" <?php echo ($status_filter == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                    <option value="Overdue" <?php echo ($status_filter == 'Overdue') ? 'selected' : ''; ?>>Overdue</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
            <div class="col-md-2">
                <a href="invoice_list.php" class="btn btn-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Invoice #</th>
                        <th>PO Ref</th>
                        <?php if ($role_id != 4): ?><th>Vendor</th><?php endif; ?>
                        <th>Date Issued</th>
                        <th>Total Amount</th>
                        <th>Payment Status</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($invoices) > 0): ?>
                        <?php foreach ($invoices as $inv): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-primary"><?php echo htmlspecialchars($inv['invoice_no']); ?></td>
                            <td><a href="../procurement/view_po.php?id=<?php echo $inv['po_id']; ?>"><?php echo htmlspecialchars($inv['po_number']); ?></a></td>
                            <?php if ($role_id != 4): ?>
                            <td><?php echo htmlspecialchars($inv['company_name']); ?></td>
                            <?php endif; ?>
                            <td><?php echo date('M d, Y', strtotime($inv['issued_at'])); ?></td>
                            <td class="fw-bold text-success">$<?php echo number_format($inv['final_amount'], 2); ?></td>
                            <td>
                                <?php
                                $badge = 'bg-secondary';
                                if ($inv['payment_status'] == 'Pending') $badge = 'bg-warning text-dark';
                                if ($inv['payment_status'] == 'Paid') $badge = 'bg-success';
                                if ($inv['payment_status'] == 'Overdue') $badge = 'bg-danger';
                                ?>
                                <span class="badge <?php echo $badge; ?>"><?php echo $inv['payment_status']; ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="view_invoice.php?id=<?php echo $inv['invoice_id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                <a href="print_invoice.php?id=<?php echo $inv['invoice_id']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo ($role_id != 4) ? '7' : '6'; ?>" class="text-center py-4 text-muted">No Invoices found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
