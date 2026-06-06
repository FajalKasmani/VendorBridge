<?php
/**
 * Sidebar File
 * Role-based sidebar navigation
 */
$current_uri = $_SERVER['REQUEST_URI'];
$role_id = $_SESSION['role_id'] ?? null;
?>

<!-- Sidebar column -->
<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse shadow-sm" style="min-height: calc(100vh - 56px);">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            
            <?php if ($_SESSION['role_id'] == 4): ?>
                <!-- Role 4: Vendor Links -->
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($current_uri, 'vendor_dashboard.php') !== false) ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="<?php echo BASE_URL; ?>modules/quotations/vendor_dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($current_uri, 'assigned_rfqs.php') !== false) ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="<?php echo BASE_URL; ?>modules/quotations/assigned_rfqs.php">
                        <i class="bi bi-file-earmark-check me-2"></i> Assigned RFQs
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($current_uri, 'my_quotes.php') !== false || strpos($current_uri, 'view_quote.php') !== false || strpos($current_uri, 'submit_quote.php') !== false) ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="<?php echo BASE_URL; ?>modules/quotations/my_quotes.php">
                        <i class="bi bi-receipt me-2"></i> My Quotations
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($current_uri, '/procurement/') !== false) ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="<?php echo BASE_URL; ?>modules/procurement/po_list.php">
                        <i class="bi bi-box-seam me-2"></i> Purchase Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($current_uri, '/invoices/') !== false) ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="<?php echo BASE_URL; ?>modules/invoices/invoice_list.php">
                        <i class="bi bi-cash-coin me-2"></i> Invoices
                    </a>
                </li>
            <?php else: ?>
                <!-- Internal Staff Dashboard Link (Roles 1, 2, 3) -->
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($current_uri, 'dashboard.php') !== false && strpos($current_uri, 'vendor_dashboard.php') === false) ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="<?php echo BASE_URL; ?>modules/dashboard/dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                </li>

                <!-- Role 1: Admin Links -->
                <?php if ($_SESSION['role_id'] == 1): // Admin Menu ?>
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
                    <span>Administration</span>
                </h6>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>modules/users/list.php">
                        <i class="bi bi-people me-2"></i> User Management
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>modules/vendors/list.php">
                        <i class="bi bi-building me-2"></i> Vendor Directory
                    </a>
                </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($current_uri, '/rfqs/') !== false) ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="<?php echo BASE_URL; ?>modules/rfqs/list.php">
                            <i class="bi bi-file-earmark-text me-2"></i> RFQs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($current_uri, '/quotations/') !== false) ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="<?php echo BASE_URL; ?>modules/quotations/list.php">
                            <i class="bi bi-calculator me-2"></i> Quotations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($current_uri, 'compare_quotes.php') !== false) ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="<?php echo BASE_URL; ?>modules/approvals/compare_quotes.php">
                            <i class="bi bi-layout-split me-2"></i> Compare Quotes
                        </a>
                    </li>
                    <li class="nav-item mt-2">
                        <a class="nav-link <?php echo (strpos($current_uri, '/procurement/') !== false) ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="<?php echo BASE_URL; ?>modules/procurement/po_list.php">
                            <i class="bi bi-box-seam me-2"></i> Purchase Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($current_uri, '/invoices/') !== false) ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="<?php echo BASE_URL; ?>modules/invoices/invoice_list.php">
                            <i class="bi bi-cash-coin me-2"></i> Invoices
                        </a>
                    </li>
                    <li class="nav-item mt-2">
                        <a class="nav-link <?php echo (strpos($current_uri, '/logs/') !== false) ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="<?php echo BASE_URL; ?>modules/logs/activity_logs.php">
                            <i class="bi bi-journal-text me-2"></i> Activity Logs
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Role 2: Procurement Officer Links -->
                <?php if ($role_id == 2): ?>
                    <li class="nav-item mt-2">
                        <a class="nav-link <?php echo (strpos($current_uri, '/vendors/') !== false) ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="<?php echo BASE_URL; ?>modules/vendors/list.php">
                            <i class="bi bi-shop me-2"></i> Vendors
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($current_uri, '/rfqs/') !== false) ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="<?php echo BASE_URL; ?>modules/rfqs/list.php">
                            <i class="bi bi-file-earmark-text me-2"></i> RFQs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($current_uri, 'compare_quotes.php') !== false) ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="<?php echo BASE_URL; ?>modules/approvals/compare_quotes.php">
                            <i class="bi bi-layout-split me-2"></i> Compare Quotes
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Role 3: Manager Links -->
                <?php if ($role_id == 3): ?>
                    <li class="nav-item mt-2">
                        <a class="nav-link <?php echo (strpos($current_uri, '/rfqs/') !== false) ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="<?php echo BASE_URL; ?>modules/rfqs/list.php">
                            <i class="bi bi-file-earmark-text me-2"></i> RFQs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($current_uri, '/approvals/approval_panel.php') !== false) ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="<?php echo BASE_URL; ?>modules/approvals/approval_panel.php">
                            <i class="bi bi-check2-circle me-2"></i> Pending Approvals
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($current_uri, 'compare_quotes.php') !== false) ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="<?php echo BASE_URL; ?>modules/approvals/compare_quotes.php">
                            <i class="bi bi-layout-split me-2"></i> Compare Quotes
                        </a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>

        </ul>
    </div>
</nav>

<!-- Main Content wrapper open -->
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3 pb-5">
