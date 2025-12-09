<?php
// reset_passwords.php
require_once 'includes/db_connect.php';

// The universal password we want for everyone
$new_password = "admin123";

// 1. Fetch all users
$sql = "SELECT id, username FROM users";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<h2>Resetting Passwords...</h2>";
    echo "<ul>";

    // 2. Loop through each user
    while($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $username = $row['username'];

        // 3. Generate a fresh hash SPECIFIC to your computer
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

        // 4. Update the database
        $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->bind_param("si", $new_hash, $id);
        
        if ($update->execute()) {
            echo "<li>User <strong>$username</strong>: Password reset to 'admin123' <span style='color:green'>[OK]</span></li>";
        } else {
            echo "<li>User <strong>$username</strong>: Update Failed <span style='color:red'>[ERROR]</span></li>";
        }
    }
    echo "</ul>";
    echo "<h3>Done! <a href='index.php'>Go to Login</a></h3>";
} else {
    echo "No users found in the database.";
}
?>