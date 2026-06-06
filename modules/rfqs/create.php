<?php
/**
 * Create RFQ UI
 */
require_once '../../includes/auth_check.php';
require_once '../../config/db_connect.php';

// Role Check: Only Admin & Officer can create
if ($_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2) {
    require_once '../../includes/header.php';
    require_once '../../includes/sidebar.php';
    echo '<div class="alert alert-danger mt-3">Access Denied.</div>';
    require_once '../../includes/footer.php';
    exit();
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Create New RFQ</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>modules/rfqs/list.php">RFQs</a></li>
            <li class="breadcrumb-item active" aria-current="page">Create</li>
        </ol>
    </nav>
</div>

<?php if (isset($_SESSION['error_msg'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($_SESSION['error_msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error_msg']); ?>
<?php endif; ?>

<form action="<?php echo BASE_URL; ?>modules/rfqs/save.php" method="POST">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 text-primary"><i class="bi bi-info-circle"></i> General Information</h5>
        </div>
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label">RFQ Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g. Procurement of Office Laptops">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Deadline <span class="text-danger">*</span></label>
                    <input type="date" name="deadline" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Additional details or instructions..."></textarea>
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
                        <tr>
                            <td class="ps-4"><input type="text" name="item_name[]" class="form-control" required></td>
                            <td><input type="number" name="quantity[]" class="form-control" min="1" required></td>
                            <td><input type="text" name="uom[]" class="form-control" placeholder="e.g. Nos"></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeItemRow(this)" disabled><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="text-end mb-5">
        <a href="<?php echo BASE_URL; ?>modules/rfqs/list.php" class="btn btn-secondary me-2">Cancel</a>
        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save"></i> Save RFQ</button>
    </div>
</form>

<script>
function addItemRow() {
    const tbody = document.getElementById('itemsBody');
    const rowCount = tbody.getElementsByTagName('tr').length;
    
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td class="ps-4"><input type="text" name="item_name[]" class="form-control" required></td>
        <td><input type="number" name="quantity[]" class="form-control" min="1" required></td>
        <td><input type="text" name="uom[]" class="form-control" placeholder="e.g. Nos"></td>
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
</script>

<?php require_once '../../includes/footer.php'; ?>
