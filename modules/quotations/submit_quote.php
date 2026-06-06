<?php
/**
 * Submit Quote Form
 */
require_once '../../includes/auth_check.php';
require_once '../../config/db_connect.php';

// Role Check: Only Vendor (Role 4)
if ($_SESSION['role_id'] != 4) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$rfq_id = $_GET['rfq_id'] ?? null;
$vendor_id = $_SESSION['user_id'];

if (!$rfq_id) {
    header("Location: assigned_rfqs.php");
    exit();
}

try {
    // Verify assignment and fetch RFQ
    $stmt = $pdo->prepare("
        SELECT r.* 
        FROM rfqs r 
        JOIN rfq_assignments a ON r.rfq_id = a.rfq_id 
        WHERE r.rfq_id = ? AND a.vendor_id = ? AND r.status IN ('Open', 'Assigned')
    ");
    $stmt->execute([$rfq_id, $vendor_id]);
    $rfq = $stmt->fetch();

    if (!$rfq) {
        $_SESSION['error_msg'] = "Invalid RFQ or you don't have access.";
        header("Location: assigned_rfqs.php");
        exit();
    }

    // Check if already quoted
    $q_check = $pdo->prepare("SELECT quote_id FROM quotations WHERE rfq_id = ? AND vendor_id = ?");
    $q_check->execute([$rfq_id, $vendor_id]);
    if ($q_check->fetch()) {
        $_SESSION['error_msg'] = "You have already submitted a quotation for this RFQ.";
        header("Location: assigned_rfqs.php");
        exit();
    }

    // Fetch RFQ items
    $i_stmt = $pdo->prepare("SELECT * FROM rfq_items WHERE rfq_id = ?");
    $i_stmt->execute([$rfq_id]);
    $items = $i_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error");
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Submit Quotation: #<?php echo $rfq['rfq_id']; ?></h1>
    <a href="assigned_rfqs.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<?php if (isset($_SESSION['error_msg'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($_SESSION['error_msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error_msg']); ?>
<?php endif; ?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary">RFQ Details</h5>
            </div>
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($rfq['title']); ?></h5>
                <p class="text-muted mb-4"><?php echo nl2br(htmlspecialchars($rfq['description'])); ?></p>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted">Deadline:</span>
                        <strong class="text-danger"><?php echo date('d M Y', strtotime($rfq['deadline'])); ?></strong>
                    </li>
                    <li class="list-group-item px-0 d-flex justify-content-between">
                        <span class="text-muted">Status:</span>
                        <span class="badge bg-warning text-dark"><?php echo $rfq['status']; ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-8 mb-4">
        <form action="save_quote.php" method="POST" id="quoteForm">
            <input type="hidden" name="rfq_id" value="<?php echo $rfq_id; ?>">
            
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
                                    <th style="width: 100px;">Quantity</th>
                                    <th style="width: 180px;">Unit Price ($)</th>
                                    <th style="width: 150px;" class="text-end pe-4">Line Total ($)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-semibold"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                        <small class="text-muted">UOM: <?php echo htmlspecialchars($item['uom']); ?></small>
                                        <input type="hidden" name="item_ids[]" value="<?php echo $item['item_id']; ?>">
                                    </td>
                                    <td>
                                        <input type="hidden" class="item-qty" value="<?php echo $item['quantity']; ?>">
                                        <?php echo $item['quantity']; ?>
                                    </td>
                                    <td>
                                        <input type="number" name="unit_prices[]" class="form-control unit-price" step="0.01" min="0" required placeholder="0.00">
                                    </td>
                                    <td class="text-end pe-4 fw-bold line-total">
                                        0.00
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Grand Total:</td>
                                    <td class="text-end pe-4 text-primary fs-5 fw-bold" id="grandTotal">0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Estimated Delivery Days <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="delivery_days" class="form-control" min="1" required placeholder="e.g., 14">
                                <span class="input-group-text">days</span>
                            </div>
                        </div>
                        <div class="col-md-6 text-end mt-4 mt-md-0">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="bi bi-send-fill"></i> Submit Quotation
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const prices = document.querySelectorAll('.unit-price');
    
    function calculateTotals() {
        let grandTotal = 0;
        
        document.querySelectorAll('tbody tr').forEach(row => {
            const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            const price = parseFloat(row.querySelector('.unit-price').value) || 0;
            const lineTotal = qty * price;
            
            row.querySelector('.line-total').innerText = lineTotal.toFixed(2);
            grandTotal += lineTotal;
        });
        
        document.getElementById('grandTotal').innerText = grandTotal.toFixed(2);
    }
    
    prices.forEach(input => {
        input.addEventListener('input', calculateTotals);
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>
