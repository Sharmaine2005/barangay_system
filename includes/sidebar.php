<?php
// Get the current file name (e.g., 'admin_dashboard.php')
$current_page = basename($_SERVER['PHP_SELF']);
$is_city_admin = ($_SESSION['role'] === 'city_admin');
?>

<div class="sidebar">
    <div class="brand">
        <i class="fas fa-shield-alt"></i> <?php echo $is_city_admin ? "City Admin" : "Brgy Admin"; ?>
    </div>
    
    <a href="admin_dashboard.php" class="<?php echo ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    
    <a href="admin_users.php" class="<?php echo ($current_page == 'admin_users.php') ? 'active' : ''; ?>">
        <i class="fas fa-user-check"></i> Verify Users
    </a>
    
    <a href="admin_requests.php" class="<?php echo ($current_page == 'admin_requests.php') ? 'active' : ''; ?>">
        <i class="fas fa-file-alt"></i> Requests
    </a>

    <a href="admin_reports.php" class="<?php echo ($current_page == 'admin_reports.php') ? 'active' : ''; ?>">
        <i class="fas fa-chart-line"></i> Reports
    </a>

    <a href="admin_logs.php" class="<?php echo ($current_page == 'admin_logs.php') ? 'active' : ''; ?>">
        <i class="fas fa-history"></i> Audit Logs
    </a>
    
    <div class="sidebar-footer">
        <a href="logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>