<?php
/**
 * User Management - List Users
 */
require_once '../../config/db_connect.php';
require_once '../../includes/auth_check.php';

// Only Admin (1) can manage users
if ($_SESSION['role_id'] != 1) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$role_filter = $_GET['role'] ?? '';

try {
    $query = "
        SELECT u.user_id, u.full_name, u.email, u.created_at, r.role_name, r.role_id 
        FROM users u
        JOIN roles r ON u.role_id = r.role_id
        WHERE 1=1
    ";
    $params = [];

    if ($role_filter) {
        $query .= " AND u.role_id = ?";
        $params[] = $role_filter;
    }

    $query .= " ORDER BY u.role_id ASC, u.full_name ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll();

    $r_stmt = $pdo->query("SELECT * FROM roles ORDER BY role_id");
    $roles = $r_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error");
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">User Management</h1>
    <a href="add.php" class="btn btn-primary"><i class="bi bi-person-plus"></i> Add New User</a>
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

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body bg-light">
        <form method="GET" class="row g-3 align-items-center">
            <div class="col-auto">
                <label class="col-form-label fw-bold">Filter by Role:</label>
            </div>
            <div class="col-md-3">
                <select name="role" class="form-select">
                    <option value="">All Roles</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?php echo $r['role_id']; ?>" <?php echo ($role_filter == $r['role_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($r['role_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-secondary">Apply Filter</button>
            </div>
            <div class="col-auto">
                <a href="list.php" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-4">Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="ps-4 fw-bold"><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <?php
                                $badge = 'bg-secondary';
                                if ($user['role_id'] == 1) $badge = 'bg-danger';
                                if ($user['role_id'] == 2) $badge = 'bg-primary';
                                if ($user['role_id'] == 3) $badge = 'bg-success';
                                if ($user['role_id'] == 4) $badge = 'bg-warning text-dark';
                                ?>
                                <span class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars($user['role_name']); ?></span>
                            </td>
                            <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                            <td class="text-end pe-4">
                                <!-- Trigger Password Reset Modal -->
                                <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#resetModal<?php echo $user['user_id']; ?>">
                                    <i class="bi bi-key"></i> Reset Password
                                </button>
                                
                                <!-- Reset Password Modal -->
                                <div class="modal fade" id="resetModal<?php echo $user['user_id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content text-start">
                                            <form action="actions.php" method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Reset Password for <?php echo htmlspecialchars($user['full_name']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" value="reset_password">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">New Password</label>
                                                        <input type="password" name="new_password" class="form-control" required minlength="6">
                                                        <div class="form-text">Password must be at least 6 characters long.</div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-warning">Reset Password</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
