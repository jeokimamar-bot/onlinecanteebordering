<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - Canteen Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-container {
            background-image: url('1.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 0;
        }
        .login-box {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>🍽️ Canteen System</h1>
                <p>Welcome! Please choose your login option</p>
            </div>

            <div style="display: flex; flex-direction: column; gap: 15px; margin-top: 30px;">
                <a href="user_login.php" class="btn-primary" style="text-decoration: none; text-align: center; display: block;">
                    👤 User Login
                </a>
                <a href="admin_login.php" class="btn-primary btn-admin" style="text-decoration: none; text-align: center; display: block;">
                    🔐 Admin Login
                </a>
            </div>

            <div class="link-text">
                <p>New user? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
</body>
</html>
