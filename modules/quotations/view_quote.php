<?php
/**
 * View Quotation Read-only
 */
require_once '../../config/db_connect.php';
require_once '../../includes/auth_check.php';

$quote_id = $_GET['id'] ?? null;
if (!$quote_id) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$user_role = $_SESSION['role_id'];
$user_id = $_SESSION['user_id'];

try {
    // Fetch quote and associated RFQ & Vendor info
    $stmt = $pdo->prepare("
        SELECT q.*, r.title, r.description, r.deadline, v.company_name, v.contact_email
        FROM quotations q
        JOIN rfqs r ON q.rfq_id = r.rfq_id
        JOIN vendor_profiles v ON q.vendor_id = v.vendor_id
        WHERE q.quote_id = ?
    ");
    $stmt->execute([$quote_id]);
    $quote = $stmt->fetch();

    if (!$quote) {
        die("Quotation not found.");
    }

    // Role-based isolation check
    if ($user_role == 4) {
        $session_vendor_id = $_SESSION['vendor_id'] ?? null;
        if ($quote['vendor_id'] != $session_vendor_id) {
            die("Access Denied: You can only view your own quotations.");
        }
    }

    // Fetch Line Items
    $i_stmt = $pdo->prepare("
        SELECT qi.*, ri.item_name, ri.quantity, ri.uom
        FROM quotation_items qi
        JOIN rfq_items ri ON qi.item_id = ri.item_id
        WHERE qi.quote_id = ?
    ");
    $i_stmt->execute([$quote_id]);
    $items = $i_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error");
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Quotation #<?php echo $quote['quote_id']; ?></h1>
    <?php if ($user_role == 4): ?>
        <a href="my_quotes.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to History</a>
    <?php else: ?>
        <a href="list.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to Quotations</a>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary">Vendor Details</h5>
            </div>
            <div class="card-body">
                <h4 class="card-title"><?php echo htmlspecialchars($quote['company_name']); ?></h4>
                <p class="text-muted"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($quote['contact_email']); ?></p>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary">RFQ Reference</h5>
            </div>
            <div class="card-body">
                <h5 class="card-title"><a href="<?php echo BASE_URL; ?>modules/rfqs/view.php?id=<?php echo $quote['rfq_id']; ?>" class="text-decoration-none">#<?php echo $quote['rfq_id']; ?> - <?php echo htmlspecialchars($quote['title']); ?></a></h5>
                <p class="text-muted">Deadline: <?php echo date('M d, Y', strtotime($quote['deadline'])); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-primary">Itemized Pricing</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Item Name</th>
                        <th>Quantity</th>
                        <th class="text-end">Unit Price ($)</th>
                        <th class="text-end pe-4">Line Total ($)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <?php $line_total = $item['quantity'] * $item['unit_price']; ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-semibold"><?php echo htmlspecialchars($item['item_name']); ?></div>
                            <small class="text-muted">UOM: <?php echo htmlspecialchars($item['uom']); ?></small>
                        </td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td class="text-end"><?php echo number_format($item['unit_price'], 2); ?></td>
                        <td class="text-end pe-4 fw-bold">
                            <?php echo number_format($line_total, 2); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-light">
                    <tr>
                        <td colspan="3" class="text-end fw-bold py-3">Grand Total:</td>
                        <td class="text-end pe-4 text-primary fs-4 fw-bold py-3">$<?php echo number_format($quote['total_price'], 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 bg-light">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-1">Estimated Delivery:</h5>
            <span class="fs-4 fw-bold"><?php echo $quote['delivery_days']; ?> Days</span>
        </div>
        <div class="text-end">
            <h5 class="mb-1">Quotation Status:</h5>
            <?php
            $badge = 'bg-secondary';
            if ($quote['status'] === 'Submitted') $badge = 'bg-primary';
            if ($quote['status'] === 'Under Review') $badge = 'bg-warning text-dark';
            if ($quote['status'] === 'Approved') $badge = 'bg-success';
            if ($quote['status'] === 'Rejected') $badge = 'bg-danger';
            ?>
            <span class="badge <?php echo $badge; ?> fs-5"><?php echo $quote['status']; ?></span>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
