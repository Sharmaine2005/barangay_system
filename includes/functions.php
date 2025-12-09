<?php
// includes/functions.php

function logAction($conn, $action) {
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $role = $_SESSION['role'];
        
        $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, user_role, action) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $role, $action);
        $stmt->execute();
        $stmt->close();
    }
}
?>