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
            
            <!-- Dashboard Link (Common for all roles) -->
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($current_uri, 'dashboard.php') !== false) ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="<?php echo BASE_URL; ?>modules/dashboard/dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i>
                    Dashboard
                </a>
            </li>

            <!-- Role 1: Admin Links -->
            <?php if ($role_id == 1): ?>
                <li class="nav-item mt-2">
                    <a class="nav-link <?php echo ($current_page == 'users.php') ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="#">
                        <i class="bi bi-people me-2"></i> Users
                    </a>
                </li>
                <li class="nav-item">
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
                    <a class="nav-link <?php echo (strpos($current_uri, '/quotations/') !== false) ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="<?php echo BASE_URL; ?>modules/quotations/list.php">
                        <i class="bi bi-calculator me-2"></i> Quotations
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'reports.php') ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="#">
                        <i class="bi bi-bar-chart-line me-2"></i> Reports
                    </a>
                </li>
            <?php elseif ($_SESSION['role_id'] == 4): ?>
                <!-- Vendor Sidebar -->
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($current_uri, 'vendor_dashboard.php') !== false) ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="<?php echo BASE_URL; ?>modules/quotations/vendor_dashboard.php">
                        <i class="bi bi-house-door me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($current_uri, 'assigned_rfqs.php') !== false) ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="<?php echo BASE_URL; ?>modules/quotations/assigned_rfqs.php">
                        <i class="bi bi-file-earmark-text me-2"></i> Assigned RFQs
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($current_uri, 'my_quotes.php') !== false) ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="<?php echo BASE_URL; ?>modules/quotations/my_quotes.php">
                        <i class="bi bi-calculator me-2"></i> My Quotations
                    </a>
                </li>
            <?php else: ?>
                <!-- Manager/Approver Sidebar -->
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($current_uri, 'dashboard.php') !== false) ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="<?php echo BASE_URL; ?>modules/dashboard/dashboard.php">
                        <i class="bi bi-house-door me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($current_uri, '/rfqs/') !== false) ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="<?php echo BASE_URL; ?>modules/rfqs/list.php">
                        <i class="bi bi-file-earmark-text me-2"></i> RFQs
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
            <?php endif; ?>

            <!-- Role 3: Manager Links -->
            <?php if ($role_id == 3): ?>
                <li class="nav-item mt-2">
                    <a class="nav-link <?php echo ($current_page == 'approvals.php') ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="#">
                        <i class="bi bi-check-circle me-2"></i> Approvals
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'reports.php') ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="#">
                        <i class="bi bi-bar-chart-line me-2"></i> Reports
                    </a>
                </li>
            <?php endif; ?>

            <!-- Role 4: Vendor Links -->
            <?php if ($role_id == 4): ?>
                <li class="nav-item mt-2">
                    <a class="nav-link <?php echo ($current_page == 'assigned_rfqs.php') ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="#">
                        <i class="bi bi-file-earmark-check me-2"></i> Assigned RFQs
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'quotations.php') ? 'active bg-primary text-white rounded' : 'text-dark'; ?>" href="#">
                        <i class="bi bi-receipt me-2"></i> Quotations
                    </a>
                </li>
            <?php endif; ?>

        </ul>
    </div>
</nav>

<!-- Main Content wrapper open -->
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-3 pb-5">
