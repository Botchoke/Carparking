<?php
session_start();
if(isset($_SESSION['admin'])){
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login - Hypercar Parking</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- This style block OVERRIDES everything in style.css -->
    <style>
        /* Force the body to be full screen */
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            height: 100% !important;
            overflow: hidden !important;
        }

        /* Force the admin login page to use Flexbox for centering */
        .admin-login-page {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            min-height: 100vh !important;
            width: 100vw !important;
            margin: 0 !important;
            padding: 0 !important;
            float: none !important;
            position: relative !important;
        }

        /* Force the login container to be perfectly centered */
        .admin-login-container {
            margin: 0 auto !important;
            position: relative !important;
            left: auto !important;
            right: auto !important;
            float: none !important;
            width: 90% !important;
            max-width: 400px !important;
        }
    </style>
</head>
<body>

<div class="login-page admin-login-page">

    <div class="login-container admin-login-container">

        <div class="login-logo">
            <div class="icon-badge admin-badge">
                <i class="fas fa-shield-alt"></i>
            </div>
        </div>

        <h1><span>ADMIN</span> PORTAL</h1>
        <p class="login-subtitle">
            <i class="fas fa-lock"></i> Restricted Access
        </p>

        <form action="login_process.php" method="POST">

            <input type="text" name="username" placeholder="Admin Username" required autocomplete="off">
            <input type="password" name="password" placeholder="Admin Password" required>

            <?php
            if(isset($_GET['error'])){
                echo "<div class='error-message'>Invalid admin credentials</div>";
            }
            ?>

            <button class="login-btn admin-login-btn" type="submit">
                <i class="fas fa-sign-in-alt"></i> Login as Admin
            </button>

        </form>

        <p class="register-text">
            Need an admin account?
            <a href="register.php">Register</a>
        </p>

    </div>

</div>

</body>
</html>