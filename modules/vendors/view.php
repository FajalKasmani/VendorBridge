<?php
/**
 * View Vendor Profile
 */
require_once '../../config/db_connect.php';
require_once '../../includes/auth_check.php';

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

// Fetch Vendor Data
try {
    $stmt = $pdo->prepare("
        SELECT v.*, c.cat_name, u.full_name as contact_person, u.email as login_email, u.role_id 
        FROM vendor_profiles v 
        LEFT JOIN categories c ON v.category_id = c.cat_id 
        LEFT JOIN users u ON v.user_id = u.user_id
        WHERE v.vendor_id = ?
    ");
    $stmt->execute([$vendor_id]);
    $vendor = $stmt->fetch();
    
    if (!$vendor) {
        $_SESSION['error_msg'] = "Vendor not found.";
        header("Location: " . BASE_URL . "modules/vendors/list.php");
        exit();
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Vendor Profile</h1>
    <a href="<?php echo BASE_URL; ?>modules/vendors/list.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to List</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-primary"><i class="bi bi-building"></i> <?php echo htmlspecialchars($vendor['company_name']); ?></h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="text-muted small">GST Number</label>
                <div class="fs-5"><?php echo htmlspecialchars($vendor['gst_number']); ?></div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="text-muted small">Contact Email</label>
                <div class="fs-5"><a href="mailto:<?php echo htmlspecialchars($vendor['contact_email']); ?>"><?php echo htmlspecialchars($vendor['contact_email']); ?></a></div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="text-muted small">Category</label>
                <div class="fs-5"><?php echo htmlspecialchars($vendor['cat_name'] ?? 'Uncategorized'); ?></div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="text-muted small">Rating</label>
                <div class="fs-5 text-warning">
                    <?php 
                    $rating = floatval($vendor['rating']);
                    echo $rating . " <i class='bi bi-star-fill'></i>"; 
                    ?>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="text-muted small">Status</label>
                <div>
                    <?php 
                    $status = $vendor['status'];
                    if ($status === 'active') echo '<span class="badge bg-success px-3 py-2">Active</span>';
                    elseif ($status === 'inactive') echo '<span class="badge bg-danger px-3 py-2">Inactive</span>';
                    else echo '<span class="badge bg-warning text-dark px-3 py-2">Pending</span>';
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mt-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-primary"><i class="bi bi-person-badge"></i> User Account Information</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="text-muted small">Contact Person Name</label>
                <div class="fs-5"><?php echo htmlspecialchars($vendor['contact_person'] ?? 'N/A'); ?></div>
            </div>
            <div class="col-md-4 mb-3">
                <label class="text-muted small">Login Email</label>
                <div class="fs-5"><a href="mailto:<?php echo htmlspecialchars($vendor['login_email'] ?? $vendor['contact_email']); ?>"><?php echo htmlspecialchars($vendor['login_email'] ?? $vendor['contact_email']); ?></a></div>
            </div>
            <div class="col-md-4 mb-3">
                <label class="text-muted small">Role</label>
                <div class="fs-5"><span class="badge bg-info text-dark">Vendor (Role 4)</span></div>
            </div>
        </div>
    </div>
    <div class="card-footer bg-light py-3 d-flex justify-content-between align-items-center">
        <a href="<?php echo BASE_URL; ?>modules/vendors/edit.php?id=<?php echo $vendor['vendor_id']; ?>" class="btn btn-warning"><i class="bi bi-pencil-square"></i> Edit Profile</a>
        <a href="<?php echo BASE_URL; ?>modules/vendors/reset_password.php?id=<?php echo $vendor['vendor_id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to reset the password to Vendor@123?');"><i class="bi bi-key"></i> Reset Password</a>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
