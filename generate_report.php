<?php
session_start();
require_once 'includes/db_connect.php';

// Security Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['city_admin', 'barangay_admin'])) {
    die("Access Denied");
}

// 1. DETERMINE SCOPE (City vs Barangay)
$is_city_admin = ($_SESSION['role'] === 'city_admin');
$my_barangay = $_SESSION['assigned_barangay'] ?? '';
$location_label = $is_city_admin ? "Entire Bacoor City" : "Barangay " . $my_barangay;

// Filter Logic
$user_filter_sql = $is_city_admin ? "" : "AND barangay = '$my_barangay'";
$req_filter_sql = $is_city_admin ? "" : "AND user_id IN (SELECT id FROM users WHERE barangay = '$my_barangay')";

// 2. GET INPUTS
$report_type = $_GET['type'] ?? '';
$report_title = "";
$data = [];

// 3. GENERATE DATA BASED ON TYPE
if ($report_type == 'monthly_summary') {
    $month = $_GET['month']; // Format: YYYY-MM
    $report_title = "Monthly Request Summary (" . date("F Y", strtotime($month)) . ")";
    
    // Count requests by Doc Type & Status
    $sql = "SELECT doc_type, 
            SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) as rejected_count,
            COUNT(*) as total
            FROM requests 
            WHERE request_date LIKE '$month%' $req_filter_sql
            GROUP BY doc_type";
    $result = $conn->query($sql);
    while($row = $result->fetch_assoc()) { $data[] = $row; }

} elseif ($report_type == 'indigency_list') {
    $year = $_GET['year'];
    $report_title = "List of Indigents ($year)";
    
    // Fetch users who got an APPROVED Indigency Cert
    $sql = "SELECT r.request_date, u.first_name, u.last_name, u.address, u.barangay 
            FROM requests r
            JOIN users u ON r.user_id = u.id
            WHERE r.doc_type = 'Certificate of Indigency' 
            AND r.status = 'approved' 
            AND r.request_date LIKE '$year%'
            " . ($is_city_admin ? "" : "AND u.barangay = '$my_barangay'");
    
    $result = $conn->query($sql);
    while($row = $result->fetch_assoc()) { $data[] = $row; }

} elseif ($report_type == 'demographics') {
    $report_title = "Resident Demographics Report";
    
    // Simple Counts
    $male = $conn->query("SELECT COUNT(*) FROM users WHERE sex='Male' AND role='resident' $user_filter_sql")->fetch_row()[0];
    $female = $conn->query("SELECT COUNT(*) FROM users WHERE sex='Female' AND role='resident' $user_filter_sql")->fetch_row()[0];
    $seniors = $conn->query("SELECT COUNT(*) FROM users WHERE age >= 60 AND role='resident' $user_filter_sql")->fetch_row()[0];
    $minors = $conn->query("SELECT COUNT(*) FROM users WHERE age < 18 AND role='resident' $user_filter_sql")->fetch_row()[0];
    $total = $conn->query("SELECT COUNT(*) FROM users WHERE role='resident' $user_filter_sql")->fetch_row()[0];
    
    $data = [
        ['category' => 'Total Residents', 'count' => $total],
        ['category' => 'Male', 'count' => $male],
        ['category' => 'Female', 'count' => $female],
        ['category' => 'Senior Citizens (60+)', 'count' => $seniors],
        ['category' => 'Minors (<18)', 'count' => $minors],
    ];

} elseif ($report_type == 'clearance_list') {
    $year = $_GET['year'];
    $report_title = "Issued Barangay Clearances ($year)";
    
    // Fetch approved clearances
    $sql = "SELECT r.request_date, r.full_name, r.purpose, u.address, u.barangay 
            FROM requests r
            JOIN users u ON r.user_id = u.id
            WHERE r.doc_type = 'Barangay Clearance' 
            AND r.status = 'approved' 
            AND r.request_date LIKE '$year%'
            " . ($is_city_admin ? "" : "AND u.barangay = '$my_barangay'");
    
    $result = $conn->query($sql);
    while($row = $result->fetch_assoc()) { $data[] = $row; }

} elseif ($report_type == 'resident_masterlist') {
    $report_title = "Official Master List of Residents";
    
    // Fetch all active residents sorted by Name
    $sql = "SELECT first_name, last_name, age, sex, contact_number, address, barangay 
            FROM users 
            WHERE role = 'resident' 
            AND account_status = 'active'
            " . ($is_city_admin ? "" : "AND barangay = '$my_barangay'") . "
            ORDER BY last_name ASC";
            
    $result = $conn->query($sql);
    while($row = $result->fetch_assoc()) { $data[] = $row; }

} elseif ($report_type == 'audit_trail') {
    $report_title = "System Audit Trail Report";
    
    // Fetch logs (Limit to last 200)
    if ($is_city_admin) {
        $sql = "SELECT l.*, u.username, u.role as user_role FROM audit_logs l JOIN users u ON l.user_id = u.id ORDER BY l.id DESC LIMIT 200";
    } else {
        $sql = "SELECT l.*, u.username, u.role as user_role FROM audit_logs l JOIN users u ON l.user_id = u.id 
                WHERE u.barangay = '$my_barangay' ORDER BY l.id DESC LIMIT 200";
    }
    
    $result = $conn->query($sql);
    while($row = $result->fetch_assoc()) { $data[] = $row; }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report: <?php echo htmlspecialchars($report_title); ?></title>
    <style>
        body { font-family: "Arial", sans-serif; padding: 40px; color: #333; }
        .header { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #333; padding-bottom: 20px; }
        .header h2 { margin: 0; text-transform: uppercase; font-size: 16px; }
        .header h1 { margin: 10px 0; font-size: 24px; }
        .meta { display: flex; justify-content: space-between; margin-bottom: 20px; font-weight: bold; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 10px; text-align: left; }
        th { background-color: #eee; }
        
        .footer { margin-top: 50px; text-align: right; }
        .print-btn { 
            position: fixed; top: 20px; right: 20px; 
            padding: 10px 20px; background: #007bff; color: white; 
            border: none; cursor: pointer; border-radius: 5px; font-size: 14px;
        }
        @media print { .print-btn { display: none; } }
    </style>
</head>
<body>

    <button onclick="window.print()" class="print-btn">🖨 Print Report</button>

    <div class="header">
        <h2>Republic of the Philippines</h2>
        <h2>City of Bacoor</h2>
        <h1><?php echo htmlspecialchars($report_title); ?></h1>
        <p>Scope: <?php echo htmlspecialchars($location_label); ?></p>
    </div>

    <div class="meta">
        <span>Generated By: <?php echo $_SESSION['username']; ?></span>
        <span>Date: <?php echo date("F j, Y"); ?></span>
    </div>

    <table>
        <thead>
            <tr>
                <?php if($report_type == 'monthly_summary'): ?>
                    <th>Document Type</th>
                    <th>Approved</th>
                    <th>Pending</th>
                    <th>Rejected</th>
                    <th>Total</th>

                <?php elseif($report_type == 'indigency_list'): ?>
                    <th>Date Issued</th>
                    <th>Resident Name</th>
                    <th>Address</th>
                    <th>Barangay</th>

                <?php elseif($report_type == 'demographics'): ?>
                    <th>Category</th>
                    <th>Count</th>

                <?php elseif($report_type == 'clearance_list'): ?>
                    <th>Date Issued</th>
                    <th>Resident Name</th>
                    <th>Purpose</th>
                    <th>Address</th>

                <?php elseif($report_type == 'resident_masterlist'): ?>
                    <th>Last Name</th>
                    <th>First Name</th>
                    <th>Age/Sex</th>
                    <th>Contact</th>
                    <th>Address</th>

                <?php elseif($report_type == 'audit_trail'): ?>
                    <th>Date/Time</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Action Performed</th>
                <?php endif; ?>
            </tr>
        </thead>

        <tbody>
            <?php if (!empty($data)): ?>
                <?php foreach($data as $row): ?>
                    <tr>
                        <?php if($report_type == 'monthly_summary'): ?>
                            <td><?php echo $row['doc_type']; ?></td>
                            <td><?php echo $row['approved_count']; ?></td>
                            <td><?php echo $row['pending_count']; ?></td>
                            <td><?php echo $row['rejected_count']; ?></td>
                            <td><strong><?php echo $row['total']; ?></strong></td>
                        
                        <?php elseif($report_type == 'indigency_list'): ?>
                            <td><?php echo date("M d, Y", strtotime($row['request_date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['address']); ?></td>
                            <td><?php echo htmlspecialchars($row['barangay']); ?></td>

                        <?php elseif($report_type == 'demographics'): ?>
                            <td><?php echo $row['category']; ?></td>
                            <td><strong><?php echo $row['count']; ?></strong></td>

                        <?php elseif($report_type == 'clearance_list'): ?>
                            <td><?php echo date("M d, Y", strtotime($row['request_date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                            <td><?php echo htmlspecialchars($row['address']); ?></td>

                        <?php elseif($report_type == 'resident_masterlist'): ?>
                            <td><strong><?php echo htmlspecialchars($row['last_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                            <td><?php echo $row['age'] . " / " . $row['sex']; ?></td>
                            <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['address']); ?></td>

                        <?php elseif($report_type == 'audit_trail'): ?>
                            <td><?php echo date("M d, Y h:i A", strtotime($row['timestamp'])); ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo ucfirst($row['user_role']); ?></td>
                            <td><?php echo htmlspecialchars($row['action']); ?></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align:center;">No records found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>Certified Correct:</p>
        <br><br>
        <p>__________________________<br>Barangay Secretary / Admin</p>
    </div>

</body>
</html>