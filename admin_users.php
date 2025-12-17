<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['city_admin', 'barangay_admin'])) {
    header("Location: index.php");
    exit;
}

$is_city_admin = ($_SESSION['role'] === 'city_admin');
$my_barangay = $_SESSION['assigned_barangay'] ?? '';

// Handle Actions
if (isset($_POST['action'])) {
    $target_user_id = $_POST['user_id'];
    $action_taken = $_POST['action'];
    
    $status = ($action_taken == 'approve') ? 'active' : 'rejected';

    $stmt = $conn->prepare("UPDATE users SET account_status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $target_user_id);
    
    if ($stmt->execute()) {
        // LOG THE ACTION
        $user_query = $conn->query("SELECT username FROM users WHERE id = $target_user_id");
        $target_username = $user_query->fetch_assoc()['username'];
        
        $log_message = ucfirst($action_taken) . " User Account: " . $target_username;
        logAction($conn, $log_message);
    }
    $stmt->close();
}

// Fetch Logic
if ($is_city_admin) {
    $sql = "SELECT * FROM users WHERE role='resident' AND account_status='pending'";
} else {
    $sql = "SELECT * FROM users WHERE role='resident' AND account_status='pending' AND barangay = '$my_barangay'";
}
$result = $conn->query($sql);

$page_title = "Verify Pending Users";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Users | Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- BUTTON FIXES --- */
        
        /* 1. Force buttons to be small and auto-width (Overrides global 100% width) */
        .btn-sm {
            width: auto !important; 
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            margin-right: 5px;
        }

        /* 2. Align buttons side-by-side */
        td form {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* 3. Add tooltips on hover */
        .btn-approve:hover, .btn-decline:hover {
            transform: scale(1.1);
        }
    </style>
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
                            <label><i class="fas fa-filter"></i> Filter Location:</label>
                            <select id="brgyFilter" class="filter-select">
                                <option value="">All Barangays</option>
                                <option value="Aniban">Aniban</option>
                                <option value="Habay">Habay</option>
                                <option value="Maliksi">Maliksi</option>
                                </select>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Barangay</th>
                                    <th>Address</th>
                                    <th style="width: 100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['first_name']." ".$row['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['barangay']); ?></td>
                                    <?php 
                                        // Logic to remove Barangay from Address string
                                        $full_address = $row['address'];
                                        $barangay_name = $row['barangay'];
                                        $short_address = str_replace($barangay_name, "", $full_address);
                                        $short_address = str_replace(", ,", ",", $short_address); 
                                        $short_address = trim($short_address, ", "); 
                                    ?>
                                    <td><?php echo htmlspecialchars($short_address); ?></td>                                    
                                    <td>
                                        <form method="POST">
                                            <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-approve" title="Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-decline" title="Reject">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </td>
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
            if ($.fn.DataTable.isDataTable('table')) {
                $('table').DataTable().destroy();
            }

            var table = $('table').DataTable({
                "language": {
                    "emptyTable": "No pending users found"
                }
            }); 
            
            $('#brgyFilter').on('change', function() {
                table.column(1).search(this.value).draw();
            });
        });
    </script>
</body>
</html>