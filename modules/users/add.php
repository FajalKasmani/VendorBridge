<?php
/**
 * User Management - Add User
 */
require_once '../../config/db_connect.php';
require_once '../../includes/auth_check.php';

// Only Admin (1) can add users
if ($_SESSION['role_id'] != 1) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

try {
    $r_stmt = $pdo->query("SELECT * FROM roles ORDER BY role_id");
    $roles = $r_stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error");
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Add New User</h1>
    <a href="list.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to Users</a>
</div>

<?php if (isset($_SESSION['error_msg'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($_SESSION['error_msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error_msg']); ?>
<?php endif; ?>

<div class="card shadow-sm border-0 mb-4" style="max-width: 600px;">
    <div class="card-body">
        <form action="actions.php" method="POST">
            <input type="hidden" name="action" value="add_user">
            
            <div class="mb-3">
                <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="full_name" class="form-control" required placeholder="e.g. John Doe">
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Email Address <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" required placeholder="e.g. john@vendorbridge.com">
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Assign Role <span class="text-danger">*</span></label>
                <select name="role_id" class="form-select" required>
                    <option value="">-- Select a Role --</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?php echo $r['role_id']; ?>">
                            <?php echo htmlspecialchars($r['role_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Role determines access rights across the ERP.</div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Temporary Password <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control" required minlength="6">
                <div class="form-text">Must be at least 6 characters. User can change this later.</div>
            </div>

            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-person-plus"></i> Create User</button>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
