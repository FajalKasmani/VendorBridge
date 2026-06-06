<?php
/**
 * Edit Vendor Page
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

$vendor_id = $_GET['id'] ?? null;
if (!$vendor_id) {
    header("Location: " . BASE_URL . "modules/vendors/list.php");
    exit();
}

try {
    // Fetch Vendor
    $stmt = $pdo->prepare("SELECT * FROM vendor_profiles WHERE vendor_id = ?");
    $stmt->execute([$vendor_id]);
    $vendor = $stmt->fetch();

    if (!$vendor) {
        $_SESSION['error_msg'] = "Vendor not found.";
        header("Location: " . BASE_URL . "modules/vendors/list.php");
        exit();
    }

    // Fetch Categories
    $cat_stmt = $pdo->query("SELECT cat_id, cat_name FROM categories ORDER BY cat_name ASC");
    $categories = $cat_stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error.");
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit Vendor</h1>
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
        <form action="<?php echo BASE_URL; ?>modules/vendors/actions.php?action=update" method="POST">
            <input type="hidden" name="vendor_id" value="<?php echo $vendor['vendor_id']; ?>">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Company Name <span class="text-danger">*</span></label>
                    <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($vendor['company_name']); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">GST Number <span class="text-danger">*</span></label>
                    <input type="text" name="gst_number" class="form-control" value="<?php echo htmlspecialchars($vendor['gst_number']); ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Category <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Select Category</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['cat_id']; ?>" <?php echo ($cat['cat_id'] == $vendor['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['cat_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Contact Email <span class="text-danger">*</span></label>
                    <input type="email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars($vendor['contact_email']); ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?php echo ($vendor['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($vendor['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        <option value="pending" <?php echo ($vendor['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Rating (0.0 to 5.0)</label>
                    <input type="number" step="0.1" min="0" max="5" name="rating" class="form-control" value="<?php echo htmlspecialchars($vendor['rating']); ?>">
                </div>
            </div>

            <hr class="my-4">
            <div class="text-end">
                <button type="submit" class="btn btn-warning px-4"><i class="bi bi-save"></i> Update Vendor</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
