<?php
/**
 * List Purchase Orders
 */
require_once '../../config/db_connect.php';
require_once '../../includes/auth_check.php';

$role_id = $_SESSION['role_id'];
$vendor_id = $_SESSION['vendor_id'] ?? null;

// Filters
$status_filter = $_GET['status'] ?? '';
$vendor_filter = $_GET['vendor'] ?? '';

try {
    $query = "
        SELECT p.*, r.title, v.company_name, v.vendor_id, q.total_price 
        FROM purchase_orders p
        JOIN quotations q ON p.quote_id = q.quote_id
        JOIN rfqs r ON q.rfq_id = r.rfq_id
        JOIN vendor_profiles v ON q.vendor_id = v.vendor_id
        WHERE 1=1
    ";
    $params = [];

    if ($role_id == 4) { // Vendor
        $query .= " AND q.vendor_id = ?";
        $params[] = $vendor_id;
    } else {
        if ($vendor_filter) {
            $query .= " AND q.vendor_id = ?";
            $params[] = $vendor_filter;
        }
    }

    if ($status_filter) {
        $query .= " AND p.status = ?";
        $params[] = $status_filter;
    }

    $query .= " ORDER BY p.po_id DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $pos = $stmt->fetchAll();

    // Fetch vendors for filter dropdown
    $v_stmt = $pdo->query("SELECT vendor_id, company_name FROM vendor_profiles ORDER BY company_name");
    $vendors = $v_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error");
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Purchase Orders</h1>
</div>

<!-- Filters -->
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
                    <option value="Generated" <?php echo ($status_filter == 'Generated') ? 'selected' : ''; ?>>Generated</option>
                    <option value="Sent" <?php echo ($status_filter == 'Sent') ? 'selected' : ''; ?>>Sent</option>
                    <option value="Completed" <?php echo ($status_filter == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
            <div class="col-md-2">
                <a href="po_list.php" class="btn btn-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- PO List -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">PO Number</th>
                        <?php if ($role_id != 4): ?><th>Vendor</th><?php endif; ?>
                        <th>RFQ Reference</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pos) > 0): ?>
                        <?php foreach ($pos as $po): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-primary">
                                <?php echo htmlspecialchars($po['po_number']); ?>
                            </td>
                            <?php if ($role_id != 4): ?>
                            <td><?php echo htmlspecialchars($po['company_name']); ?></td>
                            <?php endif; ?>
                            <td><?php echo htmlspecialchars($po['title']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($po['po_date'])); ?></td>
                            <td class="fw-bold">$<?php echo number_format($po['total_price'], 2); ?></td>
                            <td>
                                <?php
                                $badge = 'bg-secondary';
                                if ($po['status'] == 'Generated') $badge = 'bg-primary';
                                if ($po['status'] == 'Sent') $badge = 'bg-info text-dark';
                                if ($po['status'] == 'Completed') $badge = 'bg-success';
                                ?>
                                <span class="badge <?php echo $badge; ?>"><?php echo $po['status']; ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="view_po.php?id=<?php echo $po['po_id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                <a href="print_po.php?id=<?php echo $po['po_id']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo ($role_id != 4) ? '7' : '6'; ?>" class="text-center py-4 text-muted">No Purchase Orders found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
