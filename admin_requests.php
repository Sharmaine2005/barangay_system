<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/functions.php'; // <--- Add this

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['city_admin', 'barangay_admin'])) {
    header("Location: index.php");
    exit;
}

$is_city_admin = ($_SESSION['role'] === 'city_admin');
$my_barangay = $_SESSION['assigned_barangay'] ?? '';

if (isset($_POST['request_id'])) {
    $req_id = $_POST['request_id'];
    $action_taken = $_POST['action']; // 'approve' or 'decline'
    
    $status = ($action_taken == 'approve') ? 'approved' : 'rejected';
    
    // 1. Update Request
    $stmt = $conn->prepare("UPDATE requests SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $req_id);
    
    if ($stmt->execute()) {
        // 2. LOG THE ACTION <--- New Code
        $log_message = ucfirst($action_taken) . " Document Request ID: " . $req_id;
        logAction($conn, $log_message);
    }
    $stmt->close();
}

// Fetch Logic
if ($is_city_admin) {
    $sql = "SELECT r.*, u.barangay FROM requests r JOIN users u ON r.user_id = u.id ORDER BY r.id DESC";
} else {
    $sql = "SELECT r.*, u.barangay FROM requests r JOIN users u ON r.user_id = u.id 
            WHERE u.barangay = '$my_barangay' ORDER BY r.id DESC";
}
$result = $conn->query($sql);

// SET TITLE FOR HEADER
$page_title = "Document Requests";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Requests | Admin</title>
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
                            <label><i class="fas fa-filter"></i> Filter by Doc:</label>
                            <select id="docFilter" class="filter-select">
                                <option value="">All Documents</option>
                                <option value="Barangay Clearance">Barangay Clearance</option>
                                <option value="Certificate of Indigency">Certificate of Indigency</option>
                                <option value="Certificate of Residency">Certificate of Residency</option>
                            </select>
                        </div>
                        <table>
                            <thead>
                                <tr><th>Name</th><th>Barangay</th><th>Doc</th><th>Date</th><th>Status</th><th>Action</th></tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['barangay']); ?></td>
                                    <td><?php echo htmlspecialchars($row['doc_type']); ?></td>
                                    <td><?php echo date("M d", strtotime($row['request_date'])); ?></td>
                                    <td><span class="status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                    <td>
                                        <?php if ($row['status'] == 'pending'): ?>
                                            <form method="POST">
                                                <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" name="action" value="approve" class="btn btn-sm btn-approve">✔</button>
                                                <button type="submit" name="action" value="decline" class="btn btn-sm btn-decline">✖</button>
                                            </form>
                                        <?php elseif ($row['status'] == 'approved'): ?>
                                            <a href="generate_certificate.php?id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-sm btn-print">Print</a>
                                        <?php else: ?>
                                            <span style="color:#aaa;">Rejected</span>
                                        <?php endif; ?>
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
                "order": [[ 3, "desc" ]] // Sort by Date (Column 3)
            }); 

            // Filter Logic: Column 2 is "Doc"
            $('#docFilter').on('change', function() {
                table.column(2).search(this.value).draw();
            });
        });
    </script>
</body>
</html>