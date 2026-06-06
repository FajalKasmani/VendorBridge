<?php
/**
 * Compare Quotes Panel
 */
require_once '../../config/db_connect.php';
require_once '../../includes/auth_check.php';

// Role Check: Admin (1), Officer (2), Manager (3)
if ($_SESSION['role_id'] == 4) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$rfq_id = $_GET['rfq_id'] ?? null;
$rfq_list = [];
$quotations = [];
$rfq_items = [];
$rfq_details = null;
$lowest_totals = [];
$item_minimums = [];

try {
    // Fetch all RFQs that have at least one quotation
    $rfq_list_stmt = $pdo->query("
        SELECT DISTINCT r.rfq_id, r.title 
        FROM rfqs r 
        JOIN quotations q ON r.rfq_id = q.rfq_id 
        ORDER BY r.rfq_id DESC
    ");
    $rfq_list = $rfq_list_stmt->fetchAll();

    if ($rfq_id) {
        // Fetch RFQ Details
        $r_stmt = $pdo->prepare("SELECT * FROM rfqs WHERE rfq_id = ?");
        $r_stmt->execute([$rfq_id]);
        $rfq_details = $r_stmt->fetch();

        // Fetch RFQ Items
        $i_stmt = $pdo->prepare("SELECT item_id, item_name, quantity, uom FROM rfq_items WHERE rfq_id = ? ORDER BY item_id ASC");
        $i_stmt->execute([$rfq_id]);
        $rfq_items = $i_stmt->fetchAll();

        // Fetch all quotations for this RFQ
        $q_stmt = $pdo->prepare("
            SELECT q.quote_id, q.vendor_id, v.company_name, q.total_price, q.delivery_days, q.status
            FROM quotations q
            JOIN vendor_profiles v ON q.vendor_id = v.vendor_id
            WHERE q.rfq_id = ?
            ORDER BY q.quote_id ASC
        ");
        $q_stmt->execute([$rfq_id]);
        $quotations = $q_stmt->fetchAll();

        if (count($quotations) > 0) {
            // Find overall lowest total
            $min_total = min(array_column($quotations, 'total_price'));
            foreach ($quotations as $q) {
                if ((float)$q['total_price'] === (float)$min_total) {
                    $lowest_totals[] = $q['quote_id'];
                }
            }

            // Fetch quotation items and calculate lowest per item
            $qi_stmt = $pdo->prepare("SELECT quote_id, item_id, unit_price FROM quotation_items WHERE quote_id IN (" . implode(',', array_column($quotations, 'quote_id')) . ")");
            $qi_stmt->execute();
            $all_qi = $qi_stmt->fetchAll();

            // Reorganize $all_qi by item_id -> quote_id => unit_price
            $matrix = [];
            foreach ($all_qi as $qi) {
                $matrix[$qi['item_id']][$qi['quote_id']] = $qi['unit_price'];
            }

            // Find minimum price for each item
            foreach ($rfq_items as $item) {
                $i_id = $item['item_id'];
                if (isset($matrix[$i_id])) {
                    $min_price = min($matrix[$i_id]);
                    $item_minimums[$i_id] = $min_price;
                }
            }
        }
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Compare Quotations</h1>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body bg-light">
        <form method="GET" action="compare_quotes.php" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label text-muted small text-uppercase">Select RFQ to Compare</label>
                <select name="rfq_id" class="form-select" required onchange="this.form.submit()">
                    <option value="">-- Choose an RFQ --</option>
                    <?php foreach($rfq_list as $rl): ?>
                        <option value="<?php echo $rl['rfq_id']; ?>" <?php echo ($rfq_id == $rl['rfq_id']) ? 'selected' : ''; ?>>
                            #<?php echo $rl['rfq_id']; ?> - <?php echo htmlspecialchars($rl['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Load Comparison</button>
            </div>
        </form>
    </div>
</div>

<?php if ($rfq_id && $rfq_details): ?>
    <?php if (count($quotations) === 0): ?>
        <div class="alert alert-warning">No quotations have been received for this RFQ yet.</div>
    <?php else: ?>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-primary">RFQ #<?php echo $rfq_details['rfq_id']; ?>: <?php echo htmlspecialchars($rfq_details['title']); ?></h5>
                <span class="badge bg-secondary"><?php echo $rfq_details['status']; ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0 text-center">
                        <thead class="bg-light">
                            <tr>
                                <th class="text-start ps-4" style="width: 30%;">Item Required</th>
                                <th>Quantity</th>
                                <?php foreach ($quotations as $q): ?>
                                    <th style="min-width: 150px;">
                                        <div class="fw-bold text-primary mb-1"><?php echo htmlspecialchars($q['company_name']); ?></div>
                                        <small class="badge bg-light text-dark border">Quote #<?php echo $q['quote_id']; ?></small>
                                        <?php if (in_array($q['quote_id'], $lowest_totals)): ?>
                                            <div class="mt-1"><span class="badge bg-success"><i class="bi bi-star-fill text-warning"></i> Best Value</span></div>
                                        <?php endif; ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rfq_items as $item): ?>
                            <tr>
                                <td class="text-start ps-4">
                                    <div class="fw-semibold"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                    <small class="text-muted">UOM: <?php echo htmlspecialchars($item['uom']); ?></small>
                                </td>
                                <td class="fw-bold"><?php echo $item['quantity']; ?></td>
                                
                                <?php foreach ($quotations as $q): ?>
                                    <?php 
                                        $u_price = $matrix[$item['item_id']][$q['quote_id']] ?? 0; 
                                        $is_lowest = ($u_price == $item_minimums[$item['item_id']]);
                                        $bg_class = $is_lowest ? 'bg-success bg-opacity-10 text-success fw-bold' : '';
                                    ?>
                                    <td class="<?php echo $bg_class; ?>">
                                        $<?php echo number_format($u_price, 2); ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="2" class="text-end fw-bold py-3 text-uppercase">Delivery Timeline:</td>
                                <?php foreach ($quotations as $q): ?>
                                    <td class="py-3"><?php echo $q['delivery_days']; ?> Days</td>
                                <?php endforeach; ?>
                            </tr>
                            <tr>
                                <td colspan="2" class="text-end fw-bold py-4 text-uppercase fs-5">Grand Total:</td>
                                <?php foreach ($quotations as $q): ?>
                                    <?php $is_best = in_array($q['quote_id'], $lowest_totals); ?>
                                    <td class="py-4 <?php echo $is_best ? 'bg-success text-white fw-bold fs-5' : 'fw-bold fs-5'; ?>">
                                        $<?php echo number_format($q['total_price'], 2); ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php if ($_SESSION['role_id'] == 3 && $rfq_details['status'] !== 'Closed'): ?>
                            <tr>
                                <td colspan="2" class="text-end fw-bold py-3">Manager Action:</td>
                                <?php foreach ($quotations as $q): ?>
                                    <td class="py-3">
                                        <?php if ($q['status'] === 'Approved'): ?>
                                            <span class="badge bg-success fs-6"><i class="bi bi-check-circle"></i> Approved</span>
                                        <?php elseif ($q['status'] === 'Rejected'): ?>
                                            <span class="badge bg-danger">Rejected</span>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $q['quote_id']; ?>">Select Vendor</button>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endif; ?>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Render Modals Outside Table-Responsive -->
<?php if ($rfq_id && $rfq_details && count($quotations) > 0 && $_SESSION['role_id'] == 3 && $rfq_details['status'] !== 'Closed'): ?>
    <?php foreach ($quotations as $q): ?>
        <?php if ($q['status'] !== 'Approved' && $q['status'] !== 'Rejected'): ?>
            <div class="modal fade" id="approveModal<?php echo $q['quote_id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content text-start">
                        <form action="process_approval.php" method="POST">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title">Approve Quotation #<?php echo $q['quote_id']; ?></h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="rfq_id" value="<?php echo $rfq_id; ?>">
                                <input type="hidden" name="quote_id" value="<?php echo $q['quote_id']; ?>">
                                <input type="hidden" name="action" value="Approved">
                                
                                <p>You are about to approve the quotation from <strong><?php echo htmlspecialchars($q['company_name']); ?></strong> for <strong>$<?php echo number_format($q['total_price'], 2); ?></strong>.</p>
                                <p class="text-danger"><i class="bi bi-exclamation-triangle"></i> This action will lock the RFQ and automatically reject all other vendors.</p>
                                
                                <div class="mb-3">
                                    <label class="form-label">Approval Remarks (Optional)</label>
                                    <textarea name="remarks" class="form-control" rows="3" placeholder="Add any final notes..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Confirm Approval</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?>
