<?php
/**
 * Add Vendor Page
 */
require_once '../../includes/auth_check.php';
require_once '../../config/db_connect.php';

// Role Check
if ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2) {
    require_once '../../includes/header.php';
    require_once '../../includes/sidebar.php';
    echo '<div class="alert alert-danger mt-3">Access Denied.</div>';
    require_once '../../includes/footer.php';
    exit();
}

// Fetch categories for dropdown
try {
    $stmt = $pdo->query("SELECT cat_id, cat_name FROM categories ORDER BY cat_name ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Add New Vendor</h1>
    <a href="<?php echo BASE_URL; ?>modules/vendors/list.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to Vendors</a>
</div>

<?php if (isset($_SESSION['error_msg'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($_SESSION['error_msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error_msg']); ?>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form action="<?php echo BASE_URL; ?>modules/vendors/actions.php?action=add" method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Company Name <span class="text-danger">*</span></label>
                    <input type="text" name="company_name" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">GST Number <span class="text-danger">*</span></label>
                    <input type="text" name="gst_number" class="form-control" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Select Category</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['cat_id']; ?>"><?php echo htmlspecialchars($cat['cat_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Contact Person Name <span class="text-danger">*</span></label>
                    <input type="text" name="contact_name" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Login Email <span class="text-danger">*</span></label>
                    <input type="email" name="contact_email" class="form-control" required>
                    <small class="text-muted">A vendor account will be created with default password: Vendor@123</small>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="pending" selected>Pending</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Initial Rating (0.0 to 5.0)</label>
                    <input type="number" step="0.1" min="0" max="5" name="rating" class="form-control" value="0.0">
                </div>
            </div>

            <hr class="my-4">
            <div class="text-end">
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save"></i> Save Vendor</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
