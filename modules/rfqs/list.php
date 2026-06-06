<?php
/**
 * RFQs List Page
 */
require_once '../../config/db_connect.php';
require_once '../../includes/auth_check.php';

// Role Check: All except Vendor (4) can access. Manager (3) can view only.
if ($_SESSION['role_id'] == 4) {
    require_once '../../includes/header.php';
    require_once '../../includes/sidebar.php';
    echo '<div class="alert alert-danger mt-3">Access Denied. Vendors cannot access this page.</div>';
    require_once '../../includes/footer.php';
    exit();
}

// -----------------------------------------
// Search & Filter Logic
// -----------------------------------------
$search = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_date = $_GET['date'] ?? '';

$modals_html = ''; // Store modals to output outside table

// -----------------------------------------
// Pagination Logic
// -----------------------------------------
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build query dynamically
$query_parts = [];
$params = [];

if (!empty($search)) {
    $query_parts[] = "(r.title LIKE ?)";
    $params[] = "%$search%";
}

if (!empty($filter_status)) {
    $query_parts[] = "r.status = ?";
    $params[] = $filter_status;
}

if (!empty($filter_date)) {
    $query_parts[] = "r.deadline >= ?";
    $params[] = $filter_date;
}

$where_sql = "";
if (count($query_parts) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $query_parts);
}

// Count total rows
try {
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM rfqs r $where_sql");
    $count_stmt->execute($params);
    $total_rows = $count_stmt->fetchColumn();
    $total_pages = ceil($total_rows / $limit);
} catch (PDOException $e) {
    $total_pages = 1;
}

// Fetch RFQs
try {
    $sql = "SELECT r.*, u.full_name as creator_name, 
            (SELECT COUNT(*) FROM rfq_assignments a WHERE a.rfq_id = r.rfq_id) as assigned_vendors
            FROM rfqs r 
            LEFT JOIN users u ON r.created_by = u.user_id 
            $where_sql 
            ORDER BY r.rfq_id DESC 
            LIMIT $limit OFFSET $offset";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rfqs = $stmt->fetchAll();
} catch (PDOException $e) {
    $rfqs = [];
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">RFQ Management</h1>
    <?php if ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2): ?>
        <a href="<?php echo BASE_URL; ?>modules/rfqs/create.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Create RFQ</a>
    <?php endif; ?>
</div>

<?php if (isset($_SESSION['success_msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($_SESSION['success_msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success_msg']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_msg'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($_SESSION['error_msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error_msg']); ?>
<?php endif; ?>

<!-- Filters and Search -->
<div class="card shadow-sm border-0 mb-4 bg-light">
    <div class="card-body">
        <form method="GET" action="rfqs.php" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Search Title</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="Draft" <?php echo ($filter_status === 'Draft') ? 'selected' : ''; ?>>Draft</option>
                    <option value="Open" <?php echo ($filter_status === 'Open') ? 'selected' : ''; ?>>Open</option>
                    <option value="Assigned" <?php echo ($filter_status === 'Assigned') ? 'selected' : ''; ?>>Assigned</option>
                    <option value="Closed" <?php echo ($filter_status === 'Closed') ? 'selected' : ''; ?>>Closed</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Deadline From</label>
                <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($filter_date); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-funnel"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- RFQs Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Title</th>
                        <th>Deadline</th>
                        <th>Created By</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th class="text-center">Assigned Vendors</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($rfqs) > 0): ?>
                        <?php foreach($rfqs as $r): ?>
                            <tr>
                                <td class="ps-3"><?php echo $r['rfq_id']; ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($r['title']); ?></td>
                                <td><?php echo date('d M Y', strtotime($r['deadline'])); ?></td>
                                <td><?php echo htmlspecialchars($r['creator_name']); ?></td>
                                <td>
                                    <?php 
                                    if ($r['status'] === 'Draft') echo '<span class="badge bg-secondary">Draft</span>';
                                    elseif ($r['status'] === 'Open') echo '<span class="badge bg-primary">Open</span>';
                                    elseif ($r['status'] === 'Assigned') echo '<span class="badge bg-warning text-dark">Assigned</span>';
                                    elseif ($r['status'] === 'Closed') echo '<span class="badge bg-success">Closed</span>';
                                    ?>
                                </td>
                                <td><?php echo date('d M Y H:i', strtotime($r['created_at'])); ?></td>
                                <td class="text-center">
                                    <span class="badge rounded-pill bg-info text-dark"><?php echo $r['assigned_vendors']; ?></span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo BASE_URL; ?>modules/rfqs/view.php?id=<?php echo $r['rfq_id']; ?>" class="btn btn-primary" title="View"><i class="bi bi-eye"></i></a>
                                        
                                        <?php if ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2): ?>
                                            <a href="<?php echo BASE_URL; ?>modules/rfqs/edit.php?id=<?php echo $r['rfq_id']; ?>" class="btn btn-warning text-dark" title="Edit"><i class="bi bi-pencil"></i></a>
                                            <a href="<?php echo BASE_URL; ?>modules/rfqs/assign.php?id=<?php echo $r['rfq_id']; ?>" class="btn btn-info text-dark" title="Assign Vendors"><i class="bi bi-people"></i></a>
                                            <button type="button" class="btn btn-danger" title="Delete" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $r['rfq_id']; ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2): ?>
                                    <?php ob_start(); ?>
                                    <!-- Delete Modal -->
                                    <div class="modal fade text-start" id="deleteModal<?php echo $r['rfq_id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content border-0 shadow">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title" id="deleteModalLabel"><i class="bi bi-exclamation-triangle"></i> Confirm Delete</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Are you sure you want to delete RFQ <strong>#<?php echo $r['rfq_id']; ?></strong>? All associated items and vendor assignments will be permanently deleted.
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <a href="<?php echo BASE_URL; ?>modules/rfqs/actions.php?action=delete&id=<?php echo $r['rfq_id']; ?>" class="btn btn-danger">Yes, Delete</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php $modals_html .= ob_get_clean(); ?>
                                    <?php endif; ?>

                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No RFQs found.
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
            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($filter_status); ?>&date=<?php echo urlencode($filter_date); ?>">Previous</a>
            </li>
            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($filter_status); ?>&date=<?php echo urlencode($filter_date); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($filter_status); ?>&date=<?php echo urlencode($filter_date); ?>">Next</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<!-- Render Modals Here -->
<?php echo $modals_html; ?>

<?php require_once '../../includes/footer.php'; ?>
