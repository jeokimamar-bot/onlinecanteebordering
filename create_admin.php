<?php
include 'config.php';

// Admin credentials
$admin_name = "Administrator";
$admin_username = "admin12345";
$admin_password = "admin123";
$admin_role = "admin";

// Check if admin already exists
$check_sql = "SELECT * FROM users WHERE username='$admin_username'";
$check_result = $conn->query($check_sql);

if($check_result->num_rows > 0) {
    echo "<h2>Admin account already exists!</h2>";
    echo "<p>The admin account with username 'admin12345' already exists in the database.</p>";
    echo "<p><a href='admin_login.php'>Go to Admin Login</a></p>";
    exit();
}

// Hash the password
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// Insert admin user
$insert_sql = "INSERT INTO users (name, role, username, password) VALUES ('$admin_name', '$admin_role', '$admin_username', '$hashed_password')";

if($conn->query($insert_sql)) {
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Admin Created - Canteen Management System</title>
        <link rel='stylesheet' href='assets/css/style.css'>
        <style>
            .success-container {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .success-box {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                padding: 40px;
                max-width: 500px;
                text-align: center;
            }
            .success-icon {
                font-size: 4rem;
                margin-bottom: 20px;
            }
            .credentials-box {
                background: #f3f4f6;
                padding: 20px;
                border-radius: 10px;
                margin: 20px 0;
                text-align: left;
            }
            .credentials-box p {
                margin: 10px 0;
                font-family: monospace;
            }
        </style>
    </head>
    <body>
        <div class='success-container'>
            <div class='success-box'>
                <div class='success-icon'>✅</div>
                <h1>Admin Account Created Successfully!</h1>
                <p style='color: #6b7280; margin: 20px 0;'>The admin account has been created. Please save these credentials:</p>
                
                <div class='credentials-box'>
                    <p><strong>Username:</strong> admin12345</p>
                    <p><strong>Password:</strong> admin123</p>
                    <p><strong>Role:</strong> Admin</p>
                </div>
                
                <div style='background: #fee2e2; padding: 15px; border-radius: 10px; margin: 20px 0;'>
                    <p style='color: #dc2626; margin: 0;'><strong>⚠️ Important:</strong> Please delete this file (create_admin.php) after use for security purposes!</p>
                </div>
                
                <a href='admin_login.php' class='btn-primary' style='text-decoration: none; display: inline-block; margin-top: 20px;'>Go to Admin Login</a>
            </div>
        </div>
    </body>
    </html>";
} else {
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Error - Canteen Management System</title>
        <link rel='stylesheet' href='assets/css/style.css'>
    </head>
    <body>
        <div class='login-container'>
            <div class='login-box'>
                <div class='alert alert-error'>
                    <h2>Error Creating Admin Account</h2>
                    <p>Error: " . $conn->error . "</p>
                    <p>Please check your database connection and try again.</p>
                </div>
                <a href='admin_login.php' class='btn-primary' style='text-decoration: none; display: block; text-align: center; margin-top: 20px;'>Go to Admin Login</a>
            </div>
        </div>
    </body>
    </html>";
}

$conn->close();
?>
