<?php
/**
 * RFQ Summary / Final Report
 */
require_once '../../includes/auth_check.php';
require_once '../../config/db_connect.php';

// Role Check: All internal staff can view (Vendor cannot)
if ($_SESSION['role_id'] == 4) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$rfq_id = $_GET['rfq_id'] ?? null;
if (!$rfq_id) {
    header("Location: approval_panel.php");
    exit();
}

try {
    // Fetch Approval Record and Winning Quote
    $stmt = $pdo->prepare("
        SELECT a.action, a.remarks, a.action_date, u.full_name as manager_name,
               r.title, r.description, r.deadline, r.created_at, r.status as rfq_status,
               q.quote_id, q.total_price, q.delivery_days,
               v.company_name, v.contact_email
        FROM approvals a
        JOIN rfqs r ON a.rfq_id = r.rfq_id
        JOIN users u ON a.manager_id = u.user_id
        JOIN quotations q ON a.quote_id = q.quote_id
        JOIN vendor_profiles v ON q.vendor_id = v.vendor_id
        WHERE a.rfq_id = ?
        ORDER BY a.action_date DESC LIMIT 1
    ");
    $stmt->execute([$rfq_id]);
    $summary = $stmt->fetch();

    if (!$summary) {
        $_SESSION['error_msg'] = "No approval record found for this RFQ.";
        header("Location: approval_panel.php");
        exit();
    }

    // Count competing quotes
    $c_stmt = $pdo->prepare("SELECT COUNT(*) FROM quotations WHERE rfq_id = ?");
    $c_stmt->execute([$rfq_id]);
    $competing_quotes = $c_stmt->fetchColumn() - 1;

} catch (PDOException $e) {
    die("Database Error");
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">RFQ Award Summary</h1>
    <a href="approval_panel.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to Approvals</a>
</div>

<?php if (isset($_SESSION['success_msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($_SESSION['success_msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success_msg']); ?>
<?php endif; ?>

<div class="row">
    <div class="col-md-8 mb-4">
        <!-- Certificate of Approval -->
        <div class="card shadow-sm border-success border-2">
            <div class="card-header bg-success text-white py-3 d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="bi bi-award"></i> Winning Vendor Awarded</h4>
                <span class="badge bg-light text-success fs-6">RFQ #<?php echo $rfq_id; ?> CLOSED</span>
            </div>
            <div class="card-body p-5">
                <div class="row text-center mb-5">
                    <div class="col-12">
                        <h2 class="text-primary mb-3"><?php echo htmlspecialchars($summary['company_name']); ?></h2>
                        <h1 class="display-4 fw-bold text-success">$<?php echo number_format($summary['total_price'], 2); ?></h1>
                        <p class="text-muted fs-5">Selected from <?php echo $competing_quotes + 1; ?> total bids</p>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-sm-6 mb-3">
                        <label class="text-muted small text-uppercase">RFQ Title</label>
                        <div class="fs-5 fw-semibold"><?php echo htmlspecialchars($summary['title']); ?></div>
                    </div>
                    <div class="col-sm-6 mb-3">
                        <label class="text-muted small text-uppercase">Promised Delivery</label>
                        <div class="fs-5 fw-semibold"><?php echo $summary['delivery_days']; ?> Days</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="bg-light p-4 rounded border">
                            <label class="text-muted small text-uppercase mb-2"><i class="bi bi-chat-left-quote"></i> Manager Remarks</label>
                            <p class="fst-italic mb-0">"<?php echo nl2br(htmlspecialchars($summary['remarks'] ?? 'No remarks provided.')); ?>"</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white text-muted d-flex justify-content-between py-3">
                <small>Approved by: <strong><?php echo htmlspecialchars($summary['manager_name']); ?></strong></small>
                <small>Approved on: <?php echo date('F d, Y h:i A', strtotime($summary['action_date'])); ?></small>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-4">
        <!-- Timeline -->
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-secondary"><i class="bi bi-hourglass"></i> Process Timeline</h5>
            </div>
            <div class="card-body">
                <div class="position-relative border-start border-2 border-primary ms-3 ps-4 pb-4">
                    <i class="bi bi-circle-fill text-primary position-absolute top-0 start-0 translate-middle ms-1"></i>
                    <h6 class="mb-1">RFQ Created</h6>
                    <small class="text-muted"><?php echo date('M d, Y', strtotime($summary['created_at'])); ?></small>
                </div>
                <div class="position-relative border-start border-2 border-primary ms-3 ps-4 pb-4">
                    <i class="bi bi-circle-fill text-primary position-absolute top-0 start-0 translate-middle ms-1"></i>
                    <h6 class="mb-1">Bidding Phase</h6>
                    <small class="text-muted"><?php echo $competing_quotes; ?> competing bids received</small>
                </div>
                <div class="position-relative ms-3 ps-4">
                    <i class="bi bi-check-circle-fill fs-4 text-success position-absolute top-0 start-0 translate-middle ms-1 mt-n1"></i>
                    <h6 class="mb-1 text-success fw-bold">Approved & Closed</h6>
                    <small class="text-muted"><?php echo date('M d, Y', strtotime($summary['action_date'])); ?></small>
                </div>
            </div>
            <div class="card-footer bg-white border-0 text-center pb-4">
                <a href="compare_quotes.php?rfq_id=<?php echo $rfq_id; ?>" class="btn btn-outline-primary"><i class="bi bi-table"></i> View Full Comparison</a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
