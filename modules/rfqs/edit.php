<?php
/**
 * Edit RFQ UI
 */
require_once '../../config/db_connect.php';
require_once '../../includes/auth_check.php';

// Role Check: Only Admin & Officer can edit
if ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2) {
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
    $stmt = $pdo->prepare("SELECT * FROM rfqs WHERE rfq_id = ?");
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
    
} catch (PDOException $e) {
    die("Database Error.");
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit RFQ: #<?php echo $rfq['rfq_id']; ?></h1>
    <a href="<?php echo BASE_URL; ?>modules/rfqs/list.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to RFQs</a>
</div>

<?php if (isset($_SESSION['error_msg'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($_SESSION['error_msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error_msg']); ?>
<?php endif; ?>

<form action="<?php echo BASE_URL; ?>modules/rfqs/actions.php?action=update" method="POST">
    <input type="hidden" name="rfq_id" value="<?php echo $rfq['rfq_id']; ?>">
    
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 text-primary"><i class="bi bi-info-circle"></i> General Information</h5>
        </div>
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">RFQ Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($rfq['title']); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Deadline <span class="text-danger">*</span></label>
                    <input type="date" name="deadline" class="form-control" required value="<?php echo htmlspecialchars($rfq['deadline']); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="Draft" <?php echo ($rfq['status'] === 'Draft') ? 'selected' : ''; ?>>Draft</option>
                        <option value="Open" <?php echo ($rfq['status'] === 'Open') ? 'selected' : ''; ?>>Open</option>
                        <option value="Assigned" <?php echo ($rfq['status'] === 'Assigned') ? 'selected' : ''; ?>>Assigned</option>
                        <option value="Closed" <?php echo ($rfq['status'] === 'Closed') ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($rfq['description']); ?></textarea>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-primary"><i class="bi bi-list-check"></i> RFQ Items</h5>
            <button type="button" class="btn btn-sm btn-success" onclick="addItemRow()">
                <i class="bi bi-plus-circle"></i> Add Item
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-borderless align-middle mb-0" id="itemsTable">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Item Name <span class="text-danger">*</span></th>
                            <th style="width: 150px;">Quantity <span class="text-danger">*</span></th>
                            <th style="width: 150px;">UOM</th>
                            <th style="width: 80px;" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <?php if (count($items) > 0): ?>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="ps-4"><input type="text" name="item_name[]" class="form-control" required value="<?php echo htmlspecialchars($item['item_name']); ?>"></td>
                                <td><input type="number" name="quantity[]" class="form-control" min="1" required value="<?php echo $item['quantity']; ?>"></td>
                                <td><input type="text" name="uom[]" class="form-control" value="<?php echo htmlspecialchars($item['uom']); ?>"></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeItemRow(this)"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td class="ps-4"><input type="text" name="item_name[]" class="form-control" required></td>
                                <td><input type="number" name="quantity[]" class="form-control" min="1" required></td>
                                <td><input type="text" name="uom[]" class="form-control"></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeItemRow(this)" disabled><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="text-end mb-5">
        <a href="<?php echo BASE_URL; ?>modules/rfqs/list.php" class="btn btn-secondary me-2">Cancel</a>
        <button type="submit" class="btn btn-warning px-4"><i class="bi bi-save"></i> Update RFQ</button>
    </div>
</form>

<script>
function addItemRow() {
    const tbody = document.getElementById('itemsBody');
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td class="ps-4"><input type="text" name="item_name[]" class="form-control" required></td>
        <td><input type="number" name="quantity[]" class="form-control" min="1" required></td>
        <td><input type="text" name="uom[]" class="form-control"></td>
        <td class="text-center">
            <button type="button" class="btn btn-danger btn-sm" onclick="removeItemRow(this)"><i class="bi bi-trash"></i></button>
        </td>
    `;
    tbody.appendChild(newRow);
    updateDeleteButtons();
}

function removeItemRow(btn) {
    const tbody = document.getElementById('itemsBody');
    if (tbody.getElementsByTagName('tr').length > 1) {
        btn.closest('tr').remove();
        updateDeleteButtons();
    } else {
        alert("At least one item is required.");
    }
}

function updateDeleteButtons() {
    const tbody = document.getElementById('itemsBody');
    const rows = tbody.getElementsByTagName('tr');
    const btns = tbody.querySelectorAll('.btn-danger');
    
    if (rows.length === 1) {
        btns[0].disabled = true;
    } else {
        btns.forEach(btn => btn.disabled = false);
    }
}
// Run once on load to ensure state is correct
updateDeleteButtons();
</script>

<?php require_once '../../includes/footer.php'; ?>
