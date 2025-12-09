<?php
session_start();
require_once 'includes/db_connect.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['city_admin', 'barangay_admin'])) {
    header("Location: index.php");
    exit;
}

$is_city_admin = ($_SESSION['role'] === 'city_admin');
$my_barangay = $_SESSION['assigned_barangay'] ?? '';

// Build Filters
$user_filter = $is_city_admin ? "" : "AND barangay = '$my_barangay'";
$req_filter  = $is_city_admin ? "" : "AND user_id IN (SELECT id FROM users WHERE barangay = '$my_barangay')";

// --- 1. KEY METRICS ---
$total_residents = $conn->query("SELECT COUNT(*) FROM users WHERE role='resident' $user_filter")->fetch_row()[0];
$pending_users   = $conn->query("SELECT COUNT(*) FROM users WHERE role='resident' AND account_status='pending' $user_filter")->fetch_row()[0];
$pending_docs    = $conn->query("SELECT COUNT(*) FROM requests WHERE status='pending' $req_filter")->fetch_row()[0];
$approved_docs   = $conn->query("SELECT COUNT(*) FROM requests WHERE status='approved' $req_filter")->fetch_row()[0];

// --- 2. CHART DATA (Demographics) ---
$male_count   = $conn->query("SELECT COUNT(*) FROM users WHERE sex='Male' AND role='resident' $user_filter")->fetch_row()[0];
$female_count = $conn->query("SELECT COUNT(*) FROM users WHERE sex='Female' AND role='resident' $user_filter")->fetch_row()[0];

// --- 3. CHART DATA (Age Groups) ---
$seniors = $conn->query("SELECT COUNT(*) FROM users WHERE age >= 60 AND role='resident' $user_filter")->fetch_row()[0];
$minors  = $conn->query("SELECT COUNT(*) FROM users WHERE age < 18 AND role='resident' $user_filter")->fetch_row()[0];
$adults  = $total_residents - ($seniors + $minors);

// --- 4. RECENT ACTIVITY LOGS ---
if ($is_city_admin) {
    $log_sql = "SELECT l.action, l.timestamp, u.username FROM audit_logs l JOIN users u ON l.user_id = u.id ORDER BY l.id DESC LIMIT 5";
} else {
    $log_sql = "SELECT l.action, l.timestamp, u.username FROM audit_logs l JOIN users u ON l.user_id = u.id WHERE u.barangay = '$my_barangay' ORDER BY l.id DESC LIMIT 5";
}
$logs_result = $conn->query($log_sql);

$page_title = $is_city_admin ? "City Hall Overview" : "$my_barangay Dashboard";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* New Dashboard Layout */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-left: 5px solid var(--primary-color); display: flex; justify-content: space-between; align-items: center; text-decoration: none; color: inherit; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-info h3 { font-size: 2.5rem; margin: 0; color: var(--primary-color); }
        .stat-icon { font-size: 3rem; color: #eee; }

        /* Dashboard Lower Section */
        .dashboard-row { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        
        .chart-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .chart-header h3 { margin: 0; font-size: 1.1rem; color: #555; }

        /* Recent Activity Feed */
        .activity-feed { list-style: none; padding: 0; margin: 0; }
        .activity-item { display: flex; gap: 15px; padding: 12px 0; border-bottom: 1px solid #f0f0f0; align-items: flex-start; }
        .activity-item:last-child { border-bottom: none; }
        .activity-icon { background: #e8f4fd; color: var(--primary-color); width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0; }
        .activity-details p { margin: 0; font-size: 0.9rem; font-weight: 600; color: #333; }
        .activity-details span { font-size: 0.75rem; color: #888; }

        /* Quick Actions */
        .quick-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 15px; }
        .qa-btn { padding: 10px; text-align: center; border: 1px solid #ddd; border-radius: 4px; color: #555; font-size: 0.9rem; transition: 0.2s; }
        .qa-btn:hover { background: var(--primary-color); color: white; border-color: var(--primary-color); }

        @media (max-width: 900px) { .dashboard-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include 'includes/header.php'; ?>

            <div class="content-padding">
                
                <div class="stats-grid">
                    <a href="admin_users.php" class="stat-card">
                        <div class="stat-info"><h3><?php echo $total_residents; ?></h3><p>Total Residents</p></div>
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                    </a>
                    <a href="admin_users.php" class="stat-card" style="border-left-color: var(--accent-color);">
                        <div class="stat-info"><h3><?php echo $pending_users; ?></h3><p>Pending Accounts</p></div>
                        <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
                    </a>
                    <a href="admin_requests.php" class="stat-card" style="border-left-color: orange;">
                        <div class="stat-info"><h3><?php echo $pending_docs; ?></h3><p>Pending Requests</p></div>
                        <div class="stat-icon"><i class="fas fa-file-contract"></i></div>
                    </a>
                    <a href="admin_requests.php" class="stat-card" style="border-left-color: green;">
                        <div class="stat-info"><h3><?php echo $approved_docs; ?></h3><p>Issued Docs</p></div>
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    </a>
                </div>

                <div class="dashboard-row">
                    
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3><i class="fas fa-chart-pie"></i> Population Analytics</h3>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div style="height: 250px;">
                                <canvas id="sexChart"></canvas>
                            </div>
                            <div style="height: 250px;">
                                <canvas id="ageChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="chart-container" style="margin-bottom: 20px;">
                            <div class="chart-header">
                                <h3><i class="fas fa-history"></i> Recent Activity</h3>
                                <a href="admin_logs.php" style="font-size:0.8rem; color:var(--primary-color);">View All</a>
                            </div>
                            <ul class="activity-feed">
                                <?php if($logs_result->num_rows > 0): ?>
                                    <?php while($log = $logs_result->fetch_assoc()): ?>
                                        <li class="activity-item">
                                            <div class="activity-icon"><i class="fas fa-info"></i></div>
                                            <div class="activity-details">
                                                <p><?php echo htmlspecialchars($log['action']); ?></p>
                                                <span>
                                                    by <?php echo htmlspecialchars($log['username']); ?> 
                                                    &bull; <?php echo date("M d, h:i A", strtotime($log['timestamp'])); ?>
                                                </span>
                                            </div>
                                        </li>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <li class="activity-item"><p style="color:#999;">No recent activities found.</p></li>
                                <?php endif; ?>
                            </ul>
                        </div>

                        <div class="chart-container">
                            <div class="chart-header">
                                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                            </div>
                            <div class="quick-actions">
                                <a href="admin_reports.php" class="qa-btn"><i class="fas fa-print"></i> Print Reports</a>
                                <a href="admin_users.php" class="qa-btn"><i class="fas fa-user-check"></i> Verify Users</a>
                                <a href="admin_requests.php" class="qa-btn"><i class="fas fa-file-signature"></i> Process Docs</a>
                                <a href="logout.php" class="qa-btn" style="color:red; border-color:red;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                            </div>
                        </div>
                    </div>
                
                </div>
            </div>
        </div>
    </div>

    <script>
        // 1. SEX CHART (Pie)
        const ctx1 = document.getElementById('sexChart');
        new Chart(ctx1, {
            type: 'pie',
            data: {
                labels: ['Male', 'Female'],
                datasets: [{
                    data: [<?php echo $male_count; ?>, <?php echo $female_count; ?>],
                    backgroundColor: ['#3498db', '#e74c3c'],
                    borderWidth: 1
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' }, title: { display: true, text: 'Resident Sex Distribution' } } }
        });

        // 2. AGE CHART (Bar)
        const ctx2 = document.getElementById('ageChart');
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: ['Minors (<18)', 'Adults', 'Seniors (60+)'],
                datasets: [{
                    label: 'Residents',
                    data: [<?php echo $minors; ?>, <?php echo $adults; ?>, <?php echo $seniors; ?>],
                    backgroundColor: ['#f1c40f', '#2ecc71', '#9b59b6']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, title: { display: true, text: 'Age Demographics' } } }
        });
    </script>
</body>
</html>