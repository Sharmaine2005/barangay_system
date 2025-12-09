<?php
session_start();
require_once 'includes/db_connect.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Fetch Role and Barangay
    $stmt = $conn->prepare("SELECT id, password, role, account_status, barangay FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password, $role, $status, $assigned_barangay);
        $stmt->fetch();

        // Check Password (Using Hash)
        if (password_verify($password, $hashed_password)) {
            if ($status === 'pending') {
                $error = "Account pending approval.";
            } elseif ($status === 'rejected') {
                $error = "Account rejected.";
            } else {
                // Success
                $_SESSION['user_id'] = $id;
                $_SESSION['role'] = $role;
                $_SESSION['username'] = $username;
                
                // CRITICAL: Store their assigned location
                $_SESSION['assigned_barangay'] = $assigned_barangay; 

                // Redirect based on Role
                if ($role == 'resident') {
                    header("Location: resident_dashboard.php");
                } else {
                    // Both City Admin and Brgy Admin go here
                    header("Location: admin_dashboard.php");
                }
                exit;
            }
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Username not found.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay E-Service | Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Custom Background for Login Page Only */
        .login-wrapper {
            /* Linear gradient overlay + Background Image */
            background: linear-gradient(135deg, rgba(12, 36, 97, 0.85), rgba(30, 55, 153, 0.85)), url('assets/images/bacoor_city_hall.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        /* Logo & Header Alignment */
        .login-header {
            display: flex;
            align-items: center; /* Vertically center items */
            justify-content: center; /* Horizontally center the group */
            gap: 15px; /* Space between Logo and Text */
            margin-bottom: 25px;
        }

        .login-header img {
            width: 80px; /* Adjust size as needed */
            height: auto;
        }

        .login-header-text {
            text-align: left;
        }

        .login-header-text h2 {
            margin: 0;
            line-height: 1.2;
            color: var(--primary-color);
        }

        .login-header-text p {
            margin: 0;
            font-size: 0.9rem;
            color: #666;
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="card login-card">
            
            <div class="login-header">
                <img src="assets/images/bacoor_city_logo.png" alt="Bacoor Logo">
                <div class="login-header-text">
                    <h2>Barangay System</h2>
                    <p>City of Bacoor</p>
                </div>
            </div>
            
            <?php if($error): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="index.php" method="POST">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" required placeholder="Enter your username">

                <label for="password">Password</label>
                <input type="password" name="password" id="password" required placeholder="Enter your password">

                <button type="submit" class="btn-primary">Login</button>
            </form>
            
            <p style="text-align:center; margin-top:20px; font-size:0.9rem;">
                No account? <a href="register.php" style="color:var(--primary-color); font-weight:bold;">Register here</a>
            </p>
        </div>
    </div>

</body>
</html>