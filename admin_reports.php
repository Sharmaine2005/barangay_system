<?php
session_start();
require_once 'includes/db_connect.php';

// Security Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['city_admin', 'barangay_admin'])) {
    header("Location: index.php");
    exit;
}

// Set Page Title
$page_title = "Generate Reports";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports | Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .report-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .report-card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-top: 4px solid var(--primary-color); }
        .report-card h3 { margin-top: 0; color: var(--primary-color); }
        .report-card p { color: #666; font-size: 0.9rem; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 0.9rem; }
        .form-group select, .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include 'includes/header.php'; ?>

            <div class="content-padding">
                <div class="report-grid">
                    
                    <div class="report-card">
                        <h3><i class="fas fa-chart-bar"></i> Monthly Request Summary</h3>
                        <p>Generate a breakdown of all documents requested, approved, and pending for a specific month.</p>
                        
                        <form action="generate_report.php" method="GET" target="_blank">
                            <input type="hidden" name="type" value="monthly_summary">
                            
                            <div class="form-group">
                                <label>Select Month</label>
                                <input type="month" name="month" required value="<?php echo date('Y-m'); ?>">
                            </div>
                            
                            <button type="submit" class="btn btn-primary" style="width:100%;">
                                <i class="fas fa-print"></i> Generate Report
                            </button>
                        </form>
                    </div>

                    <div class="report-card" style="border-top-color: var(--accent-color);">
                        <h3><i class="fas fa-hand-holding-heart"></i> List of Indigents</h3>
                        <p>List of all residents who have successfully requested a "Certificate of Indigency".</p>
                        
                        <form action="generate_report.php" method="GET" target="_blank">
                            <input type="hidden" name="type" value="indigency_list">
                            
                            <div class="form-group">
                                <label>Year</label>
                                <select name="year">
                                    <?php for($i=date('Y'); $i>=2020; $i--) { echo "<option value='$i'>$i</option>"; } ?>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary" style="width:100%; background-color: var(--accent-color);">
                                <i class="fas fa-print"></i> Generate Report
                            </button>
                        </form>
                    </div>

                    <div class="report-card" style="border-top-color: green;">
                        <h3><i class="fas fa-users"></i> Resident Demographics</h3>
                        <p>Summary of resident population by Age, Sex, and Account Status.</p>
                        
                        <form action="generate_report.php" method="GET" target="_blank">
                            <input type="hidden" name="type" value="demographics">
                            <button type="submit" class="btn btn-primary" style="width:100%; background-color: green; margin-top: 25px;">
                                <i class="fas fa-print"></i> Generate Report
                            </button>
                        </form>
                    </div>

                    <div class="report-card" style="border-top-color: #8e44ad;">
                        <h3><i class="fas fa-file-signature"></i> Issued Clearances</h3>
                        <p>List of all approved Barangay Clearances (useful for business permit cross-checking).</p>
                        
                        <form action="generate_report.php" method="GET" target="_blank">
                            <input type="hidden" name="type" value="clearance_list">
                            
                            <div class="form-group">
                                <label>Select Year</label>
                                <select name="year">
                                    <?php for($i=date('Y'); $i>=2023; $i--) { echo "<option value='$i'>$i</option>"; } ?>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary" style="width:100%; background-color: #8e44ad;">
                                <i class="fas fa-print"></i> Generate Report
                            </button>
                        </form>
                    </div>

                    <div class="report-card" style="border-top-color: #2c3e50;">
                        <h3><i class="fas fa-address-book"></i> Master List of Residents</h3>
                        <p>A complete directory of all active residents in the barangay sorted by name.</p>
                        
                        <form action="generate_report.php" method="GET" target="_blank">
                            <input type="hidden" name="type" value="resident_masterlist">
                            
                            <div class="form-group">
                                <label>Status</label>
                                <input type="text" value="Active Accounts Only" readonly disabled>
                            </div>

                            <button type="submit" class="btn btn-primary" style="width:100%; background-color: #2c3e50;">
                                <i class="fas fa-print"></i> Generate Master List
                            </button>
                        </form>
                    </div>

                    <div class="report-card" style="border-top-color: #c0392b;">
                        <h3><i class="fas fa-history"></i> System Audit Trail</h3>
                        <p>Printable log of admin actions (Approvals, Rejections, Logins) for security.</p>
                        
                        <form action="generate_report.php" method="GET" target="_blank">
                            <input type="hidden" name="type" value="audit_trail">
                            <button type="submit" class="btn btn-primary" style="width:100%; background-color: #c0392b; margin-top: 25px;">
                                <i class="fas fa-print"></i> Generate Log Report
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</body>
</html>