<?php
/**
 * Manager Approval Panel
 */
require_once '../../config/db_connect.php';
require_once '../../includes/auth_check.php';

// Role Check: Only Manager (3) can approve
if ($_SESSION['role_id'] != 3) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

try {
    // Fetch RFQs that are Open/Assigned/Under Review and have quotations
    $stmt = $pdo->query("
        SELECT r.rfq_id, r.title, r.deadline, r.status, COUNT(q.quote_id) as quote_count
        FROM rfqs r
        JOIN quotations q ON r.rfq_id = q.rfq_id
        WHERE r.status NOT IN ('Closed', 'Draft') AND q.status != 'Rejected'
        GROUP BY r.rfq_id
        ORDER BY r.deadline ASC
    ");
    $pending_rfqs = $stmt->fetchAll();

    // Fetch Recently Approved/Closed RFQs
    $hist_stmt = $pdo->query("
        SELECT r.rfq_id, r.title, a.action, a.action_date, v.company_name, q.total_price
        FROM approvals a
        JOIN rfqs r ON a.rfq_id = r.rfq_id
        JOIN quotations q ON a.quote_id = q.quote_id
        JOIN vendor_profiles v ON q.vendor_id = v.vendor_id
        ORDER BY a.action_date DESC
        LIMIT 10
    ");
    $history = $hist_stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Database Error");
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Pending Approvals</h1>
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

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary"><i class="bi bi-inbox"></i> RFQs Awaiting Decision</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">RFQ ID</th>
                                <th>Title</th>
                                <th>Received Quotes</th>
                                <th>Deadline</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($pending_rfqs)): ?>
                                <?php foreach ($pending_rfqs as $r): ?>
                                <tr>
                                    <td class="ps-4">#<?php echo $r['rfq_id']; ?></td>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($r['title']); ?></td>
                                    <td><span class="badge bg-primary rounded-pill"><?php echo $r['quote_count']; ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($r['deadline'])); ?></td>
                                    <td class="text-end pe-4">
                                        <a href="compare_quotes.php?rfq_id=<?php echo $r['rfq_id']; ?>" class="btn btn-sm btn-primary">Review & Decide</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-check-circle fs-1 d-block mb-3 text-success"></i>
                                        All caught up! No pending approvals.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-secondary"><i class="bi bi-clock-history"></i> Recent Decisions</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (!empty($history)): ?>
                        <?php foreach($history as $h): ?>
                        <li class="list-group-item py-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <a href="rfq_summary.php?rfq_id=<?php echo $h['rfq_id']; ?>" class="fw-bold text-decoration-none">RFQ #<?php echo $h['rfq_id']; ?></a>
                                <?php if ($h['action'] === 'Approved'): ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Rejected</span>
                                <?php endif; ?>
                            </div>
                            <div class="small text-muted mb-1"><?php echo htmlspecialchars($h['title']); ?></div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="fw-semibold text-primary"><?php echo htmlspecialchars($h['company_name']); ?></small>
                                <small class="text-muted"><?php echo date('M d', strtotime($h['action_date'])); ?></small>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-center py-5 text-muted">
                            No recent decisions.
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
