<?php
session_start();
include 'config.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = 'admin';

    // Validation
    if(empty($name) || empty($username) || empty($email) || empty($password)) {
        header("Location: admin_register.php?error=All fields are required");
        exit();
    }

    if($password !== $confirm_password) {
        header("Location: admin_register.php?error=Passwords do not match");
        exit();
    }

    if(strlen($password) < 6) {
        header("Location: admin_register.php?error=Password must be at least 6 characters");
        exit();
    }

    // Validate email format
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: admin_register.php?error=Invalid email format");
        exit();
    }

    // Check if username already exists using prepared statement
    $check_stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if($check_result->num_rows > 0) {
        $check_stmt->close();
        header("Location: admin_register.php?error=Username already exists. Please choose a different username.");
        exit();
    }
    $check_stmt->close();

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new admin user using prepared statement
    $insert_stmt = $conn->prepare("INSERT INTO users (name, role, username, password) VALUES (?, ?, ?, ?)");
    $insert_stmt->bind_param("ssss", $name, $role, $username, $hashed_password);
    
    if($insert_stmt->execute()) {
        $insert_stmt->close();
        header("Location: admin_login.php?success=Admin account created successfully! You can now login with your credentials.");
        exit();
    } else {
        $error_msg = $insert_stmt->error;
        $insert_stmt->close();
        header("Location: admin_register.php?error=Registration failed. Please try again. Error: " . htmlspecialchars($error_msg));
        exit();
    }
} else {
    header("Location: admin_register.php");
    exit();
}
?>
