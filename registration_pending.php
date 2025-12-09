<?php
// registration_pending.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="login-wrapper">
        <div class="card login-card" style="text-align: center;">
            <div style="font-size: 50px; color: var(--accent-color); margin-bottom: 15px;">
                &#9203; </div>
            
            <h2 style="color: var(--primary-color);">Registration Successful!</h2>
            
            <p style="font-size: 1.1rem; color: #555;">
                Thank you for signing up. Your account is currently <strong>PENDING APPROVAL</strong>.
            </p>

            <div class="alert success" style="margin: 20px 0; text-align: left; font-size: 0.9rem;">
                <strong>What happens next?</strong><br>
                1. The Barangay Admin will review your details.<br>
                2. Once verified, your account will be activated.<br>
                3. You can check your status by attempting to log in below.
            </div>

            <a href="index.php" class="btn btn-primary" style="text-decoration: none; display: inline-block; width: 100%;">
                Go to Login Page
            </a>
        </div>
    </div>

</body>
</html>