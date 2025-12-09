<?php
// includes/header.php

// Ensure session is active
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// FETCH FULL USER DETAILS
// We use the ID from the session to get the latest data from the DB
if (isset($_SESSION['user_id'])) {
    $header_user_id = $_SESSION['user_id'];
    $header_sql = "SELECT * FROM users WHERE id = $header_user_id";
    $header_result = $conn->query($header_sql);
    $current_user = $header_result->fetch_assoc();
    
    // Formatting variables for display
    $display_name = $current_user['first_name'] . " " . $current_user['last_name'];
    $full_name = $current_user['first_name'] . " " . ($current_user['middle_name'] ? $current_user['middle_name'] . " " : "") . $current_user['last_name'];
    $role_label = ($current_user['role'] == 'city_admin') ? 'City Hall Admin' : 'Barangay Admin';
    
    // New Fields Formatting
    $voter_display = ($current_user['is_voter'] == 1) ? 'Yes' : 'No';
    $civil_display = $current_user['civil_status'] ?? 'N/A';
    $years_display = $current_user['years_resident'] ?? 0;
}
?>

<header class="top-header">
    <h1 class="page-title"><?php echo htmlspecialchars($page_title ?? 'Dashboard'); ?></h1>
    
    <div class="user-profile" onclick="openProfileModal()" title="Click to view details">
        <span class="user-name"><?php echo htmlspecialchars($display_name); ?></span>
        <div class="profile-icon">
            <i class="fas fa-user"></i>
        </div>
    </div>
</header>

<div id="profileModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-id-badge"></i> Admin Profile</h2>
            <span class="close-modal" onclick="closeProfileModal()">&times;</span>
        </div>
        
        <div class="modal-body">
            <div class="profile-details-grid">
                
                <div class="detail-item full-width">
                    <label>Full Name</label>
                    <p><?php echo htmlspecialchars($full_name); ?></p>
                </div>

                <div class="detail-item">
                    <label>Role</label>
                    <p><?php echo htmlspecialchars($role_label); ?></p>
                </div>

                <div class="detail-item">
                    <label>Assignment</label>
                    <p><?php echo htmlspecialchars($current_user['barangay'] ?? 'City Hall'); ?></p>
                </div>

                <div class="detail-item">
                    <label>Contact Number</label>
                    <p>+<?php echo htmlspecialchars($current_user['contact_number']); ?></p>
                </div>

                <div class="detail-item">
                    <label>Sex</label>
                    <p><?php echo htmlspecialchars($current_user['sex']); ?></p>
                </div>

                <div class="detail-item">
                    <label>Age</label>
                    <p><?php echo htmlspecialchars($current_user['age']); ?> years old</p>
                </div>

                <div class="detail-item">
                    <label>Civil Status</label>
                    <p><?php echo htmlspecialchars($civil_display); ?></p>
                </div>

                <div class="detail-item">
                    <label>Registered Voter?</label>
                    <p><?php echo $voter_display; ?></p>
                </div>

                <div class="detail-item">
                    <label>Years of Residency</label>
                    <p><?php echo $years_display; ?> years</p>
                </div>

                <div class="detail-item">
                    <label>Birthday</label>
                    <p><?php echo date("F j, Y", strtotime($current_user['birthday'])); ?></p>
                </div>

                <div class="detail-item full-width">
                    <label>Address</label>
                    <p><?php echo htmlspecialchars($current_user['address']); ?></p>
                </div>

            </div>
            
            <div style="text-align:center; margin-top:20px;">
                <button onclick="closeProfileModal()" class="btn btn-sm btn-primary" style="background:#666;">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Open Modal
    function openProfileModal() {
        document.getElementById("profileModal").style.display = "block";
    }

    // Close Modal
    function closeProfileModal() {
        document.getElementById("profileModal").style.display = "none";
    }

    // Close if clicked outside the box
    window.onclick = function(event) {
        var modal = document.getElementById("profileModal");
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>