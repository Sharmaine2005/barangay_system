<?php
session_start();
require_once 'includes/db_connect.php';

// Security Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['city_admin', 'barangay_admin'])) {
    header("Location: index.php");
    exit;
}

$is_city_admin = ($_SESSION['role'] === 'city_admin');
$my_barangay = $_SESSION['assigned_barangay'] ?? '';

// FETCH LOGS
// City Admin sees ALL logs. Brgy Admin sees logs ONLY from users in their barangay.
if ($is_city_admin) {
    $sql = "SELECT l.*, u.username, u.barangay FROM audit_logs l 
            JOIN users u ON l.user_id = u.id 
            ORDER BY l.id DESC";
} else {
    $sql = "SELECT l.*, u.username, u.barangay FROM audit_logs l 
            JOIN users u ON l.user_id = u.id 
            WHERE u.barangay = '$my_barangay' 
            ORDER BY l.id DESC";
}
$result = $conn->query($sql);

$page_title = "Audit Logs";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audit Logs | Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include 'includes/header.php'; ?>

            <div class="content-padding">
                <div class="card">
                    <div class="table-container">
                        <div class="table-controls">
                            <label><i class="fas fa-filter"></i> Filter Category:</label>
                            <select id="actionFilter" class="filter-select">
                                <option value="">All Actions</option>
                                <option value="Login">Login History</option>
                                <option value="Approve">Approved Requests</option>
                                <option value="Reject">Rejected Requests</option>
                                <option value="Update">Updates</option>
                            </select>
                        </div>
                        <table id="logsTable">
                            <thead>
                                <tr>
                                    <th>Date/Time</th>
                                    <th>Admin User</th>
                                    <th>Action</th>
                                    <?php if($is_city_admin): ?><th>Barangay</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date("M d, Y h:i A", strtotime($row['timestamp'])); ?></td>
                                    <td>
                                        <span style="font-weight:bold; color:var(--primary-color);">
                                            <?php echo htmlspecialchars($row['username']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['action']); ?></td>
                                    <?php if($is_city_admin): ?>
                                        <td><?php echo htmlspecialchars($row['barangay']); ?></td>
                                    <?php endif; ?>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() { 
            if ($.fn.DataTable.isDataTable('#logsTable')) {
                $('#logsTable').DataTable().destroy();
            }

            var table = $('#logsTable').DataTable({ 
                "order": [[ 0, "desc" ]] 
            });

            // Filter Logic: Column 2 is "Action"
            $('#actionFilter').on('change', function() {
                table.column(2).search(this.value).draw();
            });
        });
    </script>
</body>
</html>