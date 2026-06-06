<?php
/**
 * Vendor Assigned RFQs List
 */
require_once '../../includes/auth_check.php';
require_once '../../config/db_connect.php';

// Role Check: Only Vendor (Role 4)
if ($_SESSION['role_id'] != 4) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$vendor_id = $_SESSION['vendor_id'] ?? null;
if (!$vendor_id) die("Vendor Profile Not Linked.");

try {
    // Fetch all assigned RFQs for this vendor that are Open/Assigned
    $stmt = $pdo->prepare("
        SELECT r.rfq_id, r.title, r.deadline, r.status, r.created_at,
               (SELECT COUNT(*) FROM quotations q WHERE q.rfq_id = r.rfq_id AND q.vendor_id = a.vendor_id) as has_quoted
        FROM rfq_assignments a 
        JOIN rfqs r ON a.rfq_id = r.rfq_id 
        WHERE a.vendor_id = ? AND r.status IN ('Open', 'Assigned')
        ORDER BY r.deadline ASC
    ");
    $stmt->execute([$vendor_id]);
    $rfqs = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error");
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Assigned RFQs</h1>
</div>

<?php if (isset($_SESSION['success_msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($_SESSION['success_msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success_msg']); ?>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">RFQ ID</th>
                        <th>Title</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rfqs)): ?>
                        <?php foreach ($rfqs as $r): ?>
                        <?php 
                            $deadline = strtotime($r['deadline']);
                            $is_expired = ($deadline < strtotime('today'));
                        ?>
                        <tr>
                            <td class="ps-4">#<?php echo $r['rfq_id']; ?></td>
                            <td class="fw-semibold"><?php echo htmlspecialchars($r['title']); ?></td>
                            <td>
                                <span class="<?php echo $is_expired ? 'text-danger fw-bold' : ''; ?>">
                                    <?php echo date('M d, Y', $deadline); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($r['has_quoted'] > 0): ?>
                                    <span class="badge bg-success">Quoted</span>
                                <?php elseif ($is_expired): ?>
                                    <span class="badge bg-danger">Expired</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <?php if ($r['has_quoted'] > 0): ?>
                                    <a href="my_quotes.php" class="btn btn-sm btn-outline-secondary">View Quote</a>
                                <?php elseif (!$is_expired): ?>
                                    <a href="submit_quote.php?rfq_id=<?php echo $r['rfq_id']; ?>" class="btn btn-sm btn-primary"><i class="bi bi-send"></i> Submit Quote</a>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-secondary" disabled>Expired</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                No active RFQs currently assigned to you.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
