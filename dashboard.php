<?php
// dashboard.php
session_start();

// 1. Security Check: Is the user logged in?
// If not, kick them back to the login page.
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// 2. Role Check: "Traffic Cop" Logic
// Check the 'role' stored in the session during login
if ($_SESSION['role'] === 'admin') {
    // If Admin, send to the Admin Portal
    header("Location: admin_dashboard.php");
    exit;
} elseif ($_SESSION['role'] === 'resident') {
    // If Resident, send to the Resident Portal
    header("Location: resident_dashboard.php");
    exit;
} else {
    // Safety Fallback: If role is unknown/invalid, log them out
    header("Location: logout.php");
    exit;
}
?>