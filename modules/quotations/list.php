<?php
/**
 * Admin/Officer Quotations List
 */
require_once '../../config/db_connect.php';
require_once '../../includes/auth_check.php';

// Role Check: Only Admin (1) or Officer (2)
if ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

// Search and Filter
$search = trim($_GET['search'] ?? '');
$filter_status = $_GET['status'] ?? '';
$filter_rfq = $_GET['rfq_id'] ?? '';

$where_clauses = [];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(v.company_name LIKE ? OR r.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_status !== '') {
    $where_clauses[] = "q.status = ?";
    $params[] = $filter_status;
}

if ($filter_rfq !== '') {
    $where_clauses[] = "q.rfq_id = ?";
    $params[] = $filter_rfq;
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

try {
    // Pagination
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Count Total
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) FROM quotations q 
        JOIN vendor_profiles v ON q.vendor_id = v.vendor_id 
        JOIN rfqs r ON q.rfq_id = r.rfq_id 
        $where_sql
    ");
    $count_stmt->execute($params);
    $total_rows = $count_stmt->fetchColumn();
    $total_pages = ceil($total_rows / $limit);

    // Fetch Records
    $sql = "
        SELECT q.quote_id, q.total_price, q.delivery_days, q.status, q.submitted_at, 
               r.rfq_id, r.title, v.company_name
        FROM quotations q
        JOIN vendor_profiles v ON q.vendor_id = v.vendor_id
        JOIN rfqs r ON q.rfq_id = r.rfq_id
        $where_sql
        ORDER BY q.submitted_at DESC
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $quotations = $stmt->fetchAll();

    // Fetch all RFQs for filter dropdown
    $rfq_list = $pdo->query("SELECT rfq_id, title FROM rfqs ORDER BY rfq_id DESC")->fetchAll();

} catch (PDOException $e) {
    die("Database Error");
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">All Quotations</h1>
</div>

<?php if (isset($_SESSION['success_msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($_SESSION['success_msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success_msg']); ?>
<?php endif; ?>

<!-- Filters -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body bg-light">
        <form method="GET" action="list.php" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label text-muted small uppercase">Search Vendor/RFQ</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted small uppercase">Filter by RFQ</label>
                <select name="rfq_id" class="form-select">
                    <option value="">All RFQs</option>
                    <?php foreach($rfq_list as $rl): ?>
                        <option value="<?php echo $rl['rfq_id']; ?>" <?php echo ($filter_rfq == $rl['rfq_id']) ? 'selected' : ''; ?>>
                            #<?php echo $rl['rfq_id']; ?> - <?php echo htmlspecialchars($rl['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label text-muted small uppercase">Filter Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="Submitted" <?php echo ($filter_status === 'Submitted') ? 'selected' : ''; ?>>Submitted</option>
                    <option value="Under Review" <?php echo ($filter_status === 'Under Review') ? 'selected' : ''; ?>>Under Review</option>
                    <option value="Approved" <?php echo ($filter_status === 'Approved') ? 'selected' : ''; ?>>Approved</option>
                    <option value="Rejected" <?php echo ($filter_status === 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Vendor</th>
                        <th>RFQ Title</th>
                        <th class="text-end">Total Price</th>
                        <th class="text-center">Delivery</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($quotations)): ?>
                        <?php foreach ($quotations as $q): ?>
                        <tr>
                            <td class="ps-4">#<?php echo $q['quote_id']; ?></td>
                            <td class="fw-semibold text-primary"><?php echo htmlspecialchars($q['company_name']); ?></td>
                            <td><a href="<?php echo BASE_URL; ?>modules/rfqs/view.php?id=<?php echo $q['rfq_id']; ?>" class="text-decoration-none">#<?php echo $q['rfq_id']; ?> - <?php echo htmlspecialchars($q['title']); ?></a></td>
                            <td class="fw-bold text-end">$<?php echo number_format($q['total_price'], 2); ?></td>
                            <td class="text-center"><?php echo $q['delivery_days']; ?> days</td>
                            <td>
                                <?php
                                $badge = 'bg-secondary';
                                if ($q['status'] === 'Submitted') $badge = 'bg-primary';
                                if ($q['status'] === 'Under Review') $badge = 'bg-warning text-dark';
                                if ($q['status'] === 'Approved') $badge = 'bg-success';
                                if ($q['status'] === 'Rejected') $badge = 'bg-danger';
                                ?>
                                <span class="badge <?php echo $badge; ?>"><?php echo $q['status']; ?></span>
                            </td>
                            <td class="text-muted small"><?php echo date('M d, Y', strtotime($q['submitted_at'])); ?></td>
                            <td class="text-end pe-4">
                                <a href="view_quote.php?id=<?php echo $q['quote_id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-file-earmark-x fs-1 d-block mb-3"></i>
                                No quotations found matching your criteria.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($filter_status); ?>&rfq_id=<?php echo urlencode($filter_rfq); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
