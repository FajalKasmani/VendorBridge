<?php
/**
 * Vendor's Quote History
 */
require_once '../../includes/auth_check.php';
require_once '../../config/db_connect.php';

// Role Check: Only Vendor
if ($_SESSION['role_id'] != 4) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$vendor_id = $_SESSION['vendor_id'] ?? null;
if (!$vendor_id) die("Vendor Profile Not Linked.");

try {
    $stmt = $pdo->prepare("
        SELECT q.quote_id, q.total_price, q.delivery_days, q.status, q.submitted_at, r.title, r.rfq_id
        FROM quotations q
        JOIN rfqs r ON q.rfq_id = r.rfq_id
        WHERE q.vendor_id = ?
        ORDER BY q.submitted_at DESC
    ");
    $stmt->execute([$vendor_id]);
    $quotes = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error");
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">My Quotations</h1>
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
                        <th class="ps-4">Quote ID</th>
                        <th>RFQ Title</th>
                        <th>Total Price</th>
                        <th>Delivery (Days)</th>
                        <th>Status</th>
                        <th>Submitted Date</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($quotes)): ?>
                        <?php foreach ($quotes as $q): ?>
                        <tr>
                            <td class="ps-4">#<?php echo $q['quote_id']; ?></td>
                            <td><a href="<?php echo BASE_URL; ?>modules/rfqs/view.php?id=<?php echo $q['rfq_id']; ?>" class="text-decoration-none"><?php echo htmlspecialchars($q['title']); ?></a></td>
                            <td class="fw-bold">$<?php echo number_format($q['total_price'], 2); ?></td>
                            <td><?php echo $q['delivery_days']; ?></td>
                            <td>
                                <?php
                                $badge = 'bg-secondary';
                                if ($q['status'] === 'Submitted') $badge = 'bg-primary';
                                if ($q['status'] === 'Under Review') $badge = 'bg-warning text-dark';
                                if ($q['status'] === 'Approved') $badge = 'bg-success';
                                if ($q['status'] === 'Rejected') $badge = 'bg-danger';
                                ?>
                                <span class="badge <?php echo $badge; ?>"><?php echo $q['status']; ?></span>
                            </td>
                            <td><?php echo date('M d, Y h:i A', strtotime($q['submitted_at'])); ?></td>
                            <td class="text-end pe-4">
                                <a href="view_quote.php?id=<?php echo $q['quote_id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-file-earmark-x fs-1 d-block mb-3"></i>
                                You haven't submitted any quotations yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
