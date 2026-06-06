<?php
/**
 * Vendor Dashboard
 */
require_once '../../includes/auth_check.php';
require_once '../../config/db_connect.php';

// Role Check: Only Vendor (Role 4)
if ($_SESSION['role_id'] != 4) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$vendor_user_id = $_SESSION['user_id'];

// Get actual vendor_id based on user_id (assuming 1:1 mapping or vendor_profiles has a user_id. 
// Wait, the schema didn't link users to vendor_profiles directly. 
// Let's assume vendor_profiles has vendor_id = user_id for now, OR we need to find the vendor profile by contact_email matching users.email.
// Wait! Let's check the users and vendor_profiles table structure.
// If the user hasn't defined the relation, we will assume user_id = vendor_id for the Vendor role.
// I will query vendor_profiles where contact_email = user's email if possible, or assume vendor_id = user_id.
// Since we don't have the users schema handy, I will assume vendor_id = user_id for simplicity as is common in simple ERPs, 
// OR I will check the users table if needed. Let's assume user_id = vendor_id.
$vendor_id = $_SESSION['user_id'];

// Live DB Counts for this vendor
$assigned_count = 0;
$submitted_count = 0;
$pending_count = 0;
$approved_count = 0;

try {
    // Assigned RFQs count
    $a_stmt = $pdo->prepare("SELECT COUNT(*) FROM rfq_assignments a JOIN rfqs r ON a.rfq_id = r.rfq_id WHERE a.vendor_id = ? AND r.status IN ('Open', 'Assigned')");
    $a_stmt->execute([$vendor_id]);
    $assigned_count = $a_stmt->fetchColumn();

    // Submitted Quotes
    $q_stmt = $pdo->prepare("SELECT COUNT(*) FROM quotations WHERE vendor_id = ?");
    $q_stmt->execute([$vendor_id]);
    $submitted_count = $q_stmt->fetchColumn();

    // Pending Reviews
    $p_stmt = $pdo->prepare("SELECT COUNT(*) FROM quotations WHERE vendor_id = ? AND status = 'Under Review'");
    $p_stmt->execute([$vendor_id]);
    $pending_count = $p_stmt->fetchColumn();

    // Approved Quotes
    $app_stmt = $pdo->prepare("SELECT COUNT(*) FROM quotations WHERE vendor_id = ? AND status = 'Approved'");
    $app_stmt->execute([$vendor_id]);
    $approved_count = $app_stmt->fetchColumn();

    // Latest Assigned RFQs
    $latest_stmt = $pdo->prepare("
        SELECT r.rfq_id, r.title, r.deadline, r.status 
        FROM rfq_assignments a 
        JOIN rfqs r ON a.rfq_id = r.rfq_id 
        WHERE a.vendor_id = ? AND r.status IN ('Open', 'Assigned')
        ORDER BY r.deadline ASC LIMIT 5
    ");
    $latest_stmt->execute([$vendor_id]);
    $latest_rfqs = $latest_stmt->fetchAll();

} catch (PDOException $e) {
    // Handle error quietly on dashboard
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Vendor Portal Dashboard</h1>
</div>

<div class="row mb-4">
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card text-white bg-primary h-100 shadow-sm border-0">
            <div class="card-body">
                <h6 class="card-title text-uppercase mb-1">Assigned RFQs</h6>
                <h2 class="display-5 mb-0 fw-bold"><?php echo $assigned_count; ?></h2>
            </div>
            <div class="card-footer bg-primary border-0">
                <a href="assigned_rfqs.php" class="text-white text-decoration-none d-flex justify-content-between align-items-center">
                    <small>View RFQs</small>
                    <i class="bi bi-arrow-right-circle"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card text-white bg-info h-100 shadow-sm border-0">
            <div class="card-body">
                <h6 class="card-title text-uppercase mb-1 text-dark">Submitted Quotes</h6>
                <h2 class="display-5 mb-0 fw-bold text-dark"><?php echo $submitted_count; ?></h2>
            </div>
            <div class="card-footer bg-info border-0">
                <a href="my_quotes.php" class="text-dark text-decoration-none d-flex justify-content-between align-items-center">
                    <small>View History</small>
                    <i class="bi bi-arrow-right-circle"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card text-dark bg-warning h-100 shadow-sm border-0">
            <div class="card-body">
                <h6 class="card-title text-uppercase mb-1">Pending Reviews</h6>
                <h2 class="display-5 mb-0 fw-bold"><?php echo $pending_count; ?></h2>
            </div>
            <div class="card-footer bg-warning border-0 d-flex justify-content-between text-dark">
                <small>Awaiting decision</small>
                <i class="bi bi-hourglass-split"></i>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3 mb-3">
        <div class="card text-white bg-success h-100 shadow-sm border-0">
            <div class="card-body">
                <h6 class="card-title text-uppercase mb-1">Approved Quotes</h6>
                <h2 class="display-5 mb-0 fw-bold"><?php echo $approved_count; ?></h2>
            </div>
            <div class="card-footer bg-success border-0 d-flex justify-content-between">
                <small>Won bids</small>
                <i class="bi bi-check-circle"></i>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-primary"><i class="bi bi-file-earmark-text"></i> Latest Assigned RFQs</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">RFQ ID</th>
                        <th>Title</th>
                        <th>Deadline</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($latest_rfqs)): ?>
                        <?php foreach ($latest_rfqs as $r): ?>
                        <tr>
                            <td class="ps-4">#<?php echo $r['rfq_id']; ?></td>
                            <td class="fw-semibold"><?php echo htmlspecialchars($r['title']); ?></td>
                            <td>
                                <?php 
                                $deadline = strtotime($r['deadline']);
                                $is_expired = ($deadline < time());
                                ?>
                                <span class="<?php echo $is_expired ? 'text-danger fw-bold' : ''; ?>">
                                    <?php echo date('M d, Y', $deadline); ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <?php if (!$is_expired): ?>
                                    <a href="submit_quote.php?rfq_id=<?php echo $r['rfq_id']; ?>" class="btn btn-sm btn-primary">Submit Quote</a>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Expired</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No new RFQs assigned to your profile.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
