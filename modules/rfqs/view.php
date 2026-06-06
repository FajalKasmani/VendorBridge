<?php
/**
 * View RFQ Details
 */
require_once '../../includes/auth_check.php';
require_once '../../config/db_connect.php';

if ($_SESSION['role_id'] == 4) {
    require_once '../../includes/header.php';
    require_once '../../includes/sidebar.php';
    echo '<div class="alert alert-danger mt-3">Access Denied.</div>';
    require_once '../../includes/footer.php';
    exit();
}

$rfq_id = $_GET['id'] ?? null;
if (!$rfq_id) {
    header("Location: " . BASE_URL . "modules/rfqs/list.php");
    exit();
}

try {
    // Fetch RFQ
    $stmt = $pdo->prepare("SELECT r.*, u.full_name as creator_name FROM rfqs r LEFT JOIN users u ON r.created_by = u.user_id WHERE r.rfq_id = ?");
    $stmt->execute([$rfq_id]);
    $rfq = $stmt->fetch();

    if (!$rfq) {
        $_SESSION['error_msg'] = "RFQ not found.";
        header("Location: " . BASE_URL . "modules/rfqs/list.php");
        exit();
    }

    // Fetch Items
    $item_stmt = $pdo->prepare("SELECT * FROM rfq_items WHERE rfq_id = ?");
    $item_stmt->execute([$rfq_id]);
    $items = $item_stmt->fetchAll();

    // Fetch Assigned Vendors
    $vendor_stmt = $pdo->prepare("SELECT v.vendor_id, v.company_name, v.contact_email 
                                  FROM rfq_assignments a 
                                  JOIN vendor_profiles v ON a.vendor_id = v.vendor_id 
                                  WHERE a.rfq_id = ?");
    $vendor_stmt->execute([$rfq_id]);
    $assigned_vendors = $vendor_stmt->fetchAll();

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">RFQ Details: #<?php echo $rfq['rfq_id']; ?></h1>
    <a href="<?php echo BASE_URL; ?>modules/rfqs/list.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to RFQs</a>
</div>

<div class="row">
    <div class="col-lg-8 mb-4">
        <!-- RFQ Info -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary"><i class="bi bi-info-circle"></i> General Information</h5>
            </div>
            <div class="card-body">
                <h4 class="card-title fw-bold"><?php echo htmlspecialchars($rfq['title']); ?></h4>
                <p class="text-muted"><?php echo nl2br(htmlspecialchars($rfq['description'])); ?></p>
                <hr>
                <div class="row">
                    <div class="col-sm-4 mb-2">
                        <small class="text-muted d-block">Deadline</small>
                        <strong><?php echo date('d M Y', strtotime($rfq['deadline'])); ?></strong>
                    </div>
                    <div class="col-sm-4 mb-2">
                        <small class="text-muted d-block">Created By</small>
                        <strong><?php echo htmlspecialchars($rfq['creator_name']); ?></strong>
                    </div>
                    <div class="col-sm-4 mb-2">
                        <small class="text-muted d-block">Status</small>
                        <?php 
                        if ($rfq['status'] === 'Draft') echo '<span class="badge bg-secondary">Draft</span>';
                        elseif ($rfq['status'] === 'Open') echo '<span class="badge bg-primary">Open</span>';
                        elseif ($rfq['status'] === 'Assigned') echo '<span class="badge bg-warning text-dark">Assigned</span>';
                        elseif ($rfq['status'] === 'Closed') echo '<span class="badge bg-success">Closed</span>';
                        ?>
                    </div>
                </div>
            </div>
            <?php if ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2): ?>
            <div class="card-footer bg-light">
                <a href="<?php echo BASE_URL; ?>modules/rfqs/edit.php?id=<?php echo $rfq['rfq_id']; ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i> Edit RFQ</a>
                <a href="<?php echo BASE_URL; ?>modules/rfqs/assign.php?id=<?php echo $rfq['rfq_id']; ?>" class="btn btn-info btn-sm"><i class="bi bi-people"></i> Manage Vendors</a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Items -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary"><i class="bi bi-list-check"></i> Requested Items</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Item Name</th>
                                <th>Quantity</th>
                                <th>UOM</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($items as $item): ?>
                                <tr>
                                    <td class="ps-4 fw-bold"><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo htmlspecialchars($item['uom']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Assigned Vendors -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary"><i class="bi bi-buildings"></i> Assigned Vendors</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (count($assigned_vendors) > 0): ?>
                        <?php foreach($assigned_vendors as $av): ?>
                            <li class="list-group-item py-3">
                                <div class="fw-bold"><?php echo htmlspecialchars($av['company_name']); ?></div>
                                <div class="small text-muted"><a href="mailto:<?php echo htmlspecialchars($av['contact_email']); ?>"><?php echo htmlspecialchars($av['contact_email']); ?></a></div>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item py-4 text-center text-muted">
                            No vendors assigned yet.
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
