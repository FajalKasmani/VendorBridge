<?php
/**
 * Vendor Management List Page
 */
require_once 'includes/auth_check.php';
require_once 'config/db_connect.php';

// Role Check
if ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2) {
    require_once 'includes/header.php';
    require_once 'includes/sidebar.php';
    echo '<div class="alert alert-danger mt-3">Access Denied. You do not have permission to view this page.</div>';
    require_once 'includes/footer.php';
    exit();
}

// -----------------------------------------
// Search & Filter Logic
// -----------------------------------------
$search = $_GET['search'] ?? '';
$filter_category = $_GET['category'] ?? '';
$filter_status = $_GET['status'] ?? '';

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
    $query_parts[] = "(v.company_name LIKE ? OR v.gst_number LIKE ? OR v.contact_email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($filter_category)) {
    $query_parts[] = "v.category_id = ?";
    $params[] = $filter_category;
}

if (!empty($filter_status)) {
    $query_parts[] = "v.status = ?";
    $params[] = $filter_status;
}

$where_sql = "";
if (count($query_parts) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $query_parts);
}

// Count total rows for pagination
try {
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM vendor_profiles v $where_sql");
    $count_stmt->execute($params);
    $total_rows = $count_stmt->fetchColumn();
    $total_pages = ceil($total_rows / $limit);
} catch (PDOException $e) {
    $total_pages = 1;
}

// Fetch vendors
try {
    $sql = "SELECT v.*, c.cat_name 
            FROM vendor_profiles v 
            LEFT JOIN categories c ON v.category_id = c.cat_id 
            $where_sql 
            ORDER BY v.vendor_id DESC 
            LIMIT $limit OFFSET $offset";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $vendors = $stmt->fetchAll();
} catch (PDOException $e) {
    $vendors = [];
}

// Fetch categories for filter dropdown
try {
    $cat_stmt = $pdo->query("SELECT cat_id, cat_name FROM categories ORDER BY cat_name ASC");
    $categories = $cat_stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Vendor Management</h1>
    <a href="add_vendor.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Vendor</a>
</div>

<!-- Success/Error Messages -->
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
        <form method="GET" action="vendors.php" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Name, GST, Email..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['cat_id']; ?>" <?php echo ($filter_category == $cat['cat_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['cat_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active" <?php echo ($filter_status === 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($filter_status === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    <option value="pending" <?php echo ($filter_status === 'pending') ? 'selected' : ''; ?>>Pending</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-funnel"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Vendors Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Company Name</th>
                        <th>GST Number</th>
                        <th>Category</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Rating</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($vendors) > 0): ?>
                        <?php foreach($vendors as $v): ?>
                            <tr>
                                <td class="ps-3"><?php echo $v['vendor_id']; ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($v['company_name']); ?></td>
                                <td><?php echo htmlspecialchars($v['gst_number']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($v['cat_name'] ?? 'None'); ?></span></td>
                                <td><?php echo htmlspecialchars($v['contact_email']); ?></td>
                                <td>
                                    <?php 
                                    if ($v['status'] === 'active') echo '<span class="badge bg-success">Active</span>';
                                    elseif ($v['status'] === 'inactive') echo '<span class="badge bg-danger">Inactive</span>';
                                    else echo '<span class="badge bg-warning text-dark">Pending</span>';
                                    ?>
                                </td>
                                <td><i class="bi bi-star-fill text-warning"></i> <?php echo $v['rating']; ?></td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="view_vendor.php?id=<?php echo $v['vendor_id']; ?>" class="btn btn-primary text-white" title="View"><i class="bi bi-eye"></i></a>
                                        <a href="edit_vendor.php?id=<?php echo $v['vendor_id']; ?>" class="btn btn-warning text-dark" title="Edit"><i class="bi bi-pencil"></i></a>
                                        <button type="button" class="btn btn-danger" title="Delete" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $v['vendor_id']; ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>

                                    <!-- Delete Modal -->
                                    <div class="modal fade text-start" id="deleteModal<?php echo $v['vendor_id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content border-0 shadow">
                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title" id="deleteModalLabel"><i class="bi bi-exclamation-triangle"></i> Confirm Delete</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Are you sure you want to delete vendor <strong><?php echo htmlspecialchars($v['company_name']); ?></strong>? This action cannot be undone.
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <a href="vendor_actions.php?action=delete&id=<?php echo $v['vendor_id']; ?>" class="btn btn-danger">Yes, Delete</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No vendors found. Try adjusting your search or filters.
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
            
            <!-- Previous Button -->
            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($filter_category); ?>&status=<?php echo urlencode($filter_status); ?>">Previous</a>
            </li>

            <!-- Page Numbers -->
            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($filter_category); ?>&status=<?php echo urlencode($filter_status); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

            <!-- Next Button -->
            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($filter_category); ?>&status=<?php echo urlencode($filter_status); ?>">Next</a>
            </li>

        </ul>
    </nav>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
