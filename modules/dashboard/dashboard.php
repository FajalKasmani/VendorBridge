<?php
/**
 * Dashboard File
 * Main landing page post-login showing role-specific statistics
 */
require_once '../../config/db_connect.php';
require_once '../../includes/auth_check.php';
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
$rfqs_under_review = 0;
$pending_approvals = 0;
$total_pos = 0;
$total_invoices = 0;
$pending_payments = 0;
$completed_transactions = 0;

try {
    $ur_stmt = $pdo->query("SELECT COUNT(DISTINCT rfq_id) FROM quotations WHERE status = 'Under Review'");
    $rfqs_under_review = $ur_stmt->fetchColumn();

    $pa_stmt = $pdo->query("SELECT COUNT(*) FROM quotations WHERE status = 'Under Review'");
    $pending_approvals = $pa_stmt->fetchColumn();

    $po_stmt = $pdo->query("SELECT COUNT(*) FROM purchase_orders");
    $total_pos = $po_stmt->fetchColumn();
    
    $inv_stmt = $pdo->query("SELECT COUNT(*) FROM invoices");
    $total_invoices = $inv_stmt->fetchColumn();

    $pend_stmt = $pdo->query("SELECT COUNT(*) FROM invoices WHERE payment_status = 'Pending'");
    $pending_payments = $pend_stmt->fetchColumn();

    $comp_stmt = $pdo->query("SELECT COUNT(*) FROM invoices WHERE payment_status = 'Paid'");
    $completed_transactions = $comp_stmt->fetchColumn();

} catch (PDOException $e) {
    // Suppress error
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
                        <h6 class="card-title text-uppercase mb-1">Total Purchase Orders</h6>
                        <h2 class="display-5 mb-0 fw-bold"><?php echo $total_pos; ?></h2>
                    </div>
                    <i class="bi bi-box-seam fs-1 opacity-50"></i>
                </div>
            </div>
            <div class="card-footer bg-primary border-0 d-flex justify-content-between">
                <small class="text-white-50">Active Procurement Orders</small>
                <i class="bi bi-arrow-right-circle"></i>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card text-white bg-info h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase mb-1 text-dark">Total Invoices</h6>
                        <h2 class="display-5 mb-0 fw-bold text-dark"><?php echo $total_invoices; ?></h2>
                    </div>
                    <i class="bi bi-cash-coin fs-1 text-dark opacity-50"></i>
                </div>
            </div>
            <div class="card-footer bg-info border-0 d-flex justify-content-between">
                <small class="text-dark opacity-75">All Issued Invoices</small>
                <i class="bi bi-arrow-right-circle text-dark"></i>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card text-white bg-warning h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase mb-1 text-dark">Pending Payments</h6>
                        <h2 class="display-5 mb-0 fw-bold text-dark"><?php echo $pending_payments; ?></h2>
                    </div>
                    <i class="bi bi-hourglass-split fs-1 text-dark opacity-50"></i>
                </div>
            </div>
            <div class="card-footer bg-warning border-0 d-flex justify-content-between">
                <small class="text-dark opacity-75">Awaiting Clearing</small>
                <i class="bi bi-arrow-right-circle text-dark"></i>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card text-white bg-success h-100 shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title text-uppercase mb-1">Completed Transactions</h6>
                        <h2 class="display-5 mb-0 fw-bold"><?php echo $completed_transactions; ?></h2>
                    </div>
                    <i class="bi bi-check-circle fs-1 opacity-50"></i>
                </div>
            </div>
            <div class="card-footer bg-success border-0 d-flex justify-content-between">
                <small class="text-white-50">Successfully Paid</small>
                <i class="bi bi-arrow-right-circle"></i>
            </div>
        </div>
    </div>

</div>

<?php
// Fetch Recent Activity Logs
$recent_logs = [];
try {
    $l_stmt = $pdo->query("
        SELECT a.action, a.created_at, u.full_name as username 
        FROM activity_logs a 
        JOIN users u ON a.user_id = u.user_id 
        ORDER BY a.log_id DESC LIMIT 5
    ");
    $recent_logs = $l_stmt->fetchAll();
} catch (PDOException $e) {}
?>

<!-- Role-specific content placeholder -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3">
        <h5 class="mb-0 text-primary"><i class="bi bi-activity me-2"></i> Recent Activity</h5>
    </div>
    <div class="card-body p-0">
        <?php if(count($recent_logs) > 0): ?>
            <ul class="list-group list-group-flush">
                <?php foreach($recent_logs as $log): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                    <div>
                        <i class="bi bi-journal-text me-2 text-primary"></i>
                        <strong><?php echo htmlspecialchars($log['username']); ?></strong>: <?php echo htmlspecialchars($log['action']); ?>
                    </div>
                    <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?></small>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 mb-3 d-block opacity-25"></i>
                <p>No recent activity to display.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
