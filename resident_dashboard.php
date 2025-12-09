<?php
session_start();
require_once 'includes/db_connect.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'resident') {
    header("Location: index.php");
    exit;
}

// 1. FETCH USER DETAILS (Updated to include new columns: civil_status, is_voter, years_resident)
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT first_name, last_name, birthday, age, address, contact_number, sex, civil_status, is_voter, years_resident FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

// Construct Full Name
$full_name = $user_data['first_name'] . " " . $user_data['last_name'];

// 2. HANDLE FORM SUBMISSION
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $doc_type = $_POST['doc_type'];
    $purpose = htmlspecialchars($_POST['purpose']);
    
    // Check if there is already a PENDING request for this doc type to prevent spam
    $check = $conn->prepare("SELECT id FROM requests WHERE user_id = ? AND doc_type = ? AND status = 'pending'");
    $check->bind_param("is", $user_id, $doc_type);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $message = "<div class='alert error'>You already have a pending request for this document.</div>";
    } else {
        $sql = "INSERT INTO requests (user_id, full_name, doc_type, purpose, status) VALUES (?, ?, ?, ?, 'pending')";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("isss", $user_id, $full_name, $doc_type, $purpose);
            if ($stmt->execute()) {
                $message = "<div class='alert success'>Request submitted successfully!</div>";
            } else {
                $message = "<div class='alert error'>Error: " . $conn->error . "</div>";
            }
            $stmt->close();
        }
    }
}

// 3. FETCH REQUEST HISTORY
$history_sql = "SELECT * FROM requests WHERE user_id = ? ORDER BY request_date DESC";
$hist_stmt = $conn->prepare($history_sql);
$hist_stmt->bind_param("i", $user_id);
$hist_stmt->execute();
$history_result = $hist_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resident Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Specific Styles for Resident Dashboard */
        .res-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 20px; }
        
        .profile-img-box { text-align: center; margin-bottom: 20px; }
        .profile-img-box i { font-size: 80px; color: var(--primary-color); background: #e9ecef; padding: 20px; border-radius: 50%; }
        
        .history-card { margin-top: 20px; }
        
        /* Status Badges */
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 0.8rem; font-weight: bold; display: inline-block; }
        .badge-pending { background-color: #ffeeba; color: #856404; }
        .badge-approved { background-color: #d4edda; color: #155724; }
        .badge-rejected { background-color: #f8d7da; color: #721c24; }

        @media (max-width: 768px) {
            .res-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <nav>
        <div class="brand"><i class="fas fa-landmark"></i> Barangay E-Service</div>
        <div class="nav-links">
            <span style="color: rgba(255,255,255,0.9); margin-right: 20px; font-weight: bold;">
                Welcome, <?php echo htmlspecialchars($user_data['first_name']); ?>
            </span>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <div class="container">
        
        <?php echo $message; ?>

        <div class="res-grid">
            
            <div class="card">
                <div class="profile-img-box">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h3 style="text-align:center; margin-bottom:5px;"><?php echo htmlspecialchars($full_name); ?></h3>
                <p style="text-align:center; color:#777; margin-bottom:20px;">Resident</p>
                
                <hr style="border:0; border-top:1px solid #eee; margin: 15px 0;">

                <p><strong><i class="fas fa-venus-mars"></i> Sex:</strong> <?php echo $user_data['sex']; ?></p>
                <p><strong><i class="fas fa-heart"></i> Civil Status:</strong> <?php echo $user_data['civil_status']; ?></p>
                <p><strong><i class="fas fa-birthday-cake"></i> Age:</strong> <?php echo $user_data['age']; ?></p>
                <p><strong><i class="fas fa-vote-yea"></i> Voter:</strong> <?php echo ($user_data['is_voter'] == 1 ? 'Yes' : 'No'); ?></p>
                <p><strong><i class="fas fa-phone"></i> Contact:</strong> +<?php echo $user_data['contact_number']; ?></p>
                <p><strong><i class="fas fa-home"></i> Residency:</strong> <?php echo $user_data['years_resident']; ?> years</p>
                
                <p style="margin-top:10px;"><strong><i class="fas fa-map-marker-alt"></i> Address:</strong><br> <?php echo htmlspecialchars($user_data['address']); ?></p>
            </div>

            <div class="card">
                <h2><i class="fas fa-paper-plane"></i> Request a Document</h2>
                <p style="font-size:0.9rem; color:#666;">Select the document you need and state your purpose. You will see the status in the history table below.</p>
                
                <form action="resident_dashboard.php" method="POST" style="margin-top:20px;">
                    
                    <label>Document Type</label>
                    <select name="doc_type" required>
                        <option value="" disabled selected>Select Document...</option>
                        <optgroup label="Common Certificates">
                            <option value="Barangay Clearance">Barangay Clearance</option>
                            <option value="Certificate of Indigency">Certificate of Indigency</option>
                            <option value="Certificate of Residency">Certificate of Residency</option>
                            <option value="Certificate of Good Moral Character">Certificate of Good Moral Character</option>
                        </optgroup>
                        <optgroup label="Special Permits">
                            <option value="Barangay Business Clearance">Barangay Business Clearance</option>
                            <option value="Certificate of Solo Parent">Certificate of Solo Parent</option>
                            <option value="First Time Job Seeker Oath">First Time Job Seeker Oath</option>
                            <option value="Permit to Construct">Permit to Construct (Building)</option>
                        </optgroup>
                    </select>

                    <label>Purpose</label>
                    <textarea name="purpose" rows="3" required placeholder="e.g. Employment requirement, School enrollment, etc."></textarea>

                    <button type="submit" class="btn-primary"><i class="fas fa-check-circle"></i> Submit Request</button>
                </form>
            </div>

        </div>

        <div class="card history-card">
            <h2><i class="fas fa-history"></i> My Request History</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date Requested</th>
                            <th>Document Type</th>
                            <th>Purpose</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($history_result->num_rows > 0): ?>
                            <?php while($row = $history_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date("M d, Y", strtotime($row['request_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['doc_type']); ?></td>
                                    <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $row['status']; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align:center; color:#777;">You haven't made any requests yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>
</html>