<?php
// check_username.php
require_once 'includes/db_connect.php';

if (isset($_POST['username'])) {
    $username = trim($_POST['username']);
    
    // Prepare SQL to check if username exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    
    // Return "taken" or "available"
    if ($stmt->num_rows > 0) {
        echo "taken";
    } else {
        echo "available";
    }
    $stmt->close();
}
?>