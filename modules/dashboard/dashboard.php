<?php
/**
 * Dashboard File
 * Main landing page post-login showing role-specific statistics
 */
require_once '../../includes/auth_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

$role_id = $_SESSION['role_id'];

// Determine Role-specific Welcome Message
$welcome_msg = "Welcome";
switch ($role_id) {
    case 1:
        $welcome_msg = "System Administration Panel";
        break;
    case 2:
        $welcome_msg = "Procurement Operations Panel";
        break;
    case 3:
        $welcome_msg = "Approval Management Panel";
        break;
    case 4:
        $welcome_msg = "Vendor Self-Service Portal";
        break;
}

// Live DB Counts
$total_vendors = 0;
$active_rfqs = 0;
$assigned_rfqs = 0;
$total_quotations = 0;

try {
    // Total Active Vendors
    $v_stmt = $pdo->query("SELECT COUNT(*) FROM vendor_profiles WHERE status = 'active'");
    $total_vendors = $v_stmt->fetchColumn();

    // Open RFQs
    $r_stmt = $pdo->query("SELECT COUNT(*) FROM rfqs WHERE status = 'Open'");
    $active_rfqs = $r_stmt->fetchColumn();

    // Assigned RFQs
    $ar_stmt = $pdo->query("SELECT COUNT(*) FROM rfqs WHERE status = 'Assigned'");
    $assigned_rfqs = $ar_stmt->fetchColumn();
    
    // Quotations (Assuming we have quotations table from Phase 1 structure or placeholder)
    $q_stmt = $pdo->query("SELECT COUNT(*) FROM quotations WHERE status = 'pending'");
    $total_quotations = $q_stmt->fetchColumn();
} catch (PDOException $e) {
    // Suppress error if tables don't exist yet for some metrics
}

?>

<!-- Dashboard Content -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo $welcome_msg; ?></h1>
</div>

<!-- Dashboard Cards -->
<div class="row g-4 mb-4">
    
    <div class="col-md-6 col-lg-3">
        <div class="card text-white bg-primary h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase mb-1">Total Vendors</h6>
                        <h2 class="display-5 mb-0 fw-bold"><?php echo $total_vendors; ?></h2>
                    </div>
                    <i class="bi bi-shop fs-1 opacity-50"></i>
                </div>
            </div>
            <div class="card-footer bg-primary border-0 d-flex justify-content-between">
                <small class="text-white-50">Active partners</small>
                <i class="bi bi-arrow-right-circle"></i>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card text-white bg-success h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase mb-1">Active RFQs</h6>
                        <h2 class="display-5 mb-0 fw-bold"><?php echo $active_rfqs; ?></h2>
                    </div>
                    <i class="bi bi-file-earmark-text fs-1 opacity-50"></i>
                </div>
            </div>
            <div class="card-footer bg-success border-0 d-flex justify-content-between">
                <small class="text-white-50">Open requests</small>
                <i class="bi bi-arrow-right-circle"></i>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card text-white bg-warning h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase mb-1 text-dark">Quotations</h6>
                        <h2 class="display-5 mb-0 fw-bold text-dark"><?php echo $total_quotations; ?></h2>
                    </div>
                    <i class="bi bi-receipt fs-1 text-dark opacity-50"></i>
                </div>
            </div>
            <div class="card-footer bg-warning border-0 d-flex justify-content-between">
                <small class="text-dark opacity-75">Pending review</small>
                <i class="bi bi-arrow-right-circle text-dark"></i>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card text-white bg-danger h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase mb-1">Assigned RFQs</h6>
                        <h2 class="display-5 mb-0 fw-bold"><?php echo $assigned_rfqs; ?></h2>
                    </div>
                    <i class="bi bi-cart-check fs-1 opacity-50"></i>
                </div>
            </div>
            <div class="card-footer bg-danger border-0 d-flex justify-content-between">
                <small class="text-white-50">Sent to vendors</small>
                <i class="bi bi-arrow-right-circle"></i>
            </div>
        </div>
    </div>

</div>

<!-- Role-specific content placeholder -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <h5 class="mb-0 text-primary"><i class="bi bi-activity me-2"></i> Recent Activity</h5>
    </div>
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-inbox fs-1 mb-3 d-block opacity-25"></i>
        <p>No recent activity to display.</p>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
