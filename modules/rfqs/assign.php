<?php
/**
 * Assign Vendors to RFQ UI
 */
require_once '../../config/db_connect.php';
require_once '../../includes/auth_check.php';

// Role Check: Only Admin & Officer
if ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2) {
    require_once '../../includes/header.php';
    require_once '../../includes/sidebar.php';
    echo '<div class="alert alert-danger mt-3">Access Denied.</div>';
    require_once '../../includes/footer.php';
    exit();
}

$rfq_id = $_GET['id'] ?? null;
if (!$rfq_id) {
    header("Location: " . BASE_URL . "modules/rfqs/list.php");
    exit();
}

try {
    // Fetch RFQ
    $stmt = $pdo->prepare("SELECT title FROM rfqs WHERE rfq_id = ?");
    $stmt->execute([$rfq_id]);
    $rfq = $stmt->fetch();

    if (!$rfq) {
        $_SESSION['error_msg'] = "RFQ not found.";
        header("Location: " . BASE_URL . "modules/rfqs/list.php");
        exit();
    }

    // Fetch Active Vendors
    $v_stmt = $pdo->query("SELECT vendor_id, company_name, contact_email, rating FROM vendor_profiles WHERE status = 'active' ORDER BY company_name ASC");
    $active_vendors = $v_stmt->fetchAll();

    // Fetch currently assigned vendors
    $a_stmt = $pdo->prepare("SELECT vendor_id FROM rfq_assignments WHERE rfq_id = ?");
    $a_stmt->execute([$rfq_id]);
    $assigned_ids = $a_stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    die("Database Error.");
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Assign Vendors to RFQ</h1>
    <a href="<?php echo BASE_URL; ?>modules/rfqs/list.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to RFQs</a>
</div>

<?php if (isset($_SESSION['error_msg'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($_SESSION['error_msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error_msg']); ?>
<?php endif; ?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-primary">RFQ: <?php echo htmlspecialchars($rfq['title']); ?> (ID: <?php echo $rfq_id; ?>)</h5>
    </div>
    <div class="card-body p-4">
        
        <form action="<?php echo BASE_URL; ?>modules/rfqs/actions.php?action=assign" method="POST">
            <input type="hidden" name="rfq_id" value="<?php echo $rfq_id; ?>">
            
            <p class="text-muted mb-4">Select the active vendors you want to invite to this RFQ.</p>

            <div class="row">
                <?php if (count($active_vendors) > 0): ?>
                    <?php foreach ($active_vendors as $vendor): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="form-check p-3 border rounded <?php echo in_array($vendor['vendor_id'], $assigned_ids) ? 'bg-light border-primary' : ''; ?>">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="vendors[]" value="<?php echo $vendor['vendor_id']; ?>" id="vendor_<?php echo $vendor['vendor_id']; ?>" <?php echo in_array($vendor['vendor_id'], $assigned_ids) ? 'checked' : ''; ?>>
                                <label class="form-check-label w-100" style="cursor: pointer;" for="vendor_<?php echo $vendor['vendor_id']; ?>">
                                    <div class="fw-bold"><?php echo htmlspecialchars($vendor['company_name']); ?></div>
                                    <small class="text-muted d-block"><?php echo htmlspecialchars($vendor['contact_email']); ?></small>
                                    <small class="text-warning"><i class="bi bi-star-fill"></i> <?php echo $vendor['rating']; ?></small>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center text-muted py-4">
                        <i class="bi bi-shop fs-1 d-block mb-2"></i>
                        No active vendors available in the system. <br>
                        Please activate vendors in the Vendor Management module first.
                    </div>
                <?php endif; ?>
            </div>

            <hr class="my-4">
            <div class="text-end">
                <a href="<?php echo BASE_URL; ?>modules/rfqs/list.php" class="btn btn-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-info text-dark px-4" <?php echo count($active_vendors) === 0 ? 'disabled' : ''; ?>>
                    <i class="bi bi-check2-square"></i> Save Assignments
                </button>
            </div>
        </form>

    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
