<?php
/**
 * Global Activity Logs
 */
require_once '../../config/db_connect.php';
require_once '../../includes/auth_check.php';

// Only Admin (1) and Manager (3) can view all logs. Officer (2) might be able to as well.
// Vendor (4) cannot view system logs.
if ($_SESSION['role_id'] == 4) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$user_filter = $_GET['user'] ?? '';
$module_filter = $_GET['module'] ?? '';
$date_filter = $_GET['date'] ?? '';

try {
    // Pagination variables
    $limit = 50;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    $query = "
        SELECT SQL_CALC_FOUND_ROWS a.*, u.username, u.role_id, r.role_name 
        FROM activity_logs a
        JOIN users u ON a.user_id = u.user_id
        JOIN roles r ON u.role_id = r.role_id
        WHERE 1=1
    ";
    $params = [];

    if ($user_filter) {
        $query .= " AND a.user_id = ?";
        $params[] = $user_filter;
    }
    
    if ($module_filter) {
        $query .= " AND a.module = ?";
        $params[] = $module_filter;
    }

    if ($date_filter) {
        $query .= " AND DATE(a.created_at) = ?";
        $params[] = $date_filter;
    }

    $query .= " ORDER BY a.log_id DESC LIMIT $limit OFFSET $offset";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();

    // Get total rows for pagination
    $total_stmt = $pdo->query("SELECT FOUND_ROWS()");
    $total_rows = $total_stmt->fetchColumn();
    $total_pages = ceil($total_rows / $limit);

    // Fetch filters
    $u_stmt = $pdo->query("SELECT user_id, username FROM users ORDER BY username");
    $users = $u_stmt->fetchAll();

    $m_stmt = $pdo->query("SELECT DISTINCT module FROM activity_logs ORDER BY module");
    $modules = $m_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error");
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">System Activity Logs</h1>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body bg-light">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <select name="user" class="form-select">
                    <option value="">All Users</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?php echo $u['user_id']; ?>" <?php echo ($user_filter == $u['user_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="module" class="form-select">
                    <option value="">All Modules</option>
                    <?php foreach ($modules as $m): ?>
                        <option value="<?php echo htmlspecialchars($m['module']); ?>" <?php echo ($module_filter == $m['module']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($m['module']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date_filter); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
            <div class="col-md-1">
                <a href="activity_logs.php" class="btn btn-secondary w-100"><i class="bi bi-x-circle"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 font-monospace" style="font-size: 0.9rem;">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-4">Timestamp</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Module</th>
                        <th>Action Performed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($logs) > 0): ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="ps-4 text-muted"><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($log['username']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($log['role_name']); ?></span></td>
                            <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($log['module']); ?></span></td>
                            <td><?php echo htmlspecialchars($log['action']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No activity logs found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($total_pages > 1): ?>
    <div class="card-footer bg-white py-3">
        <nav aria-label="Activity logs pagination">
            <ul class="pagination justify-content-center mb-0">
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&user=<?php echo urlencode($user_filter); ?>&module=<?php echo urlencode($module_filter); ?>&date=<?php echo urlencode($date_filter); ?>">Previous</a>
                </li>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&user=<?php echo urlencode($user_filter); ?>&module=<?php echo urlencode($module_filter); ?>&date=<?php echo urlencode($date_filter); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&user=<?php echo urlencode($user_filter); ?>&module=<?php echo urlencode($module_filter); ?>&date=<?php echo urlencode($date_filter); ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
