<?php
$host = "localhost";
$dbname = "barangay_system";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $dbname, 3307);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: Set charset to handle special characters
$conn->set_charset("utf8");
?>