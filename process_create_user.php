<?php
session_start();
include 'config.php';

// Check if user is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: admin_login.php?error=Please login as admin first");
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';

    // Validation
    if(empty($name) || empty($username) || empty($password) || empty($role)) {
        header("Location: admin_users.php?error=All fields are required");
        exit();
    }

    // Validate role
    $valid_roles = ['student', 'guest', 'staff'];
    if(!in_array(strtolower($role), $valid_roles)) {
        header("Location: admin_users.php?error=Invalid role selected");
        exit();
    }

    if($password !== $confirm_password) {
        header("Location: admin_users.php?error=Passwords do not match");
        exit();
    }

    if(strlen($password) < 6) {
        header("Location: admin_users.php?error=Password must be at least 6 characters");
        exit();
    }

    // Check if username already exists
    $check_stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if($check_result->num_rows > 0) {
        $check_stmt->close();
        header("Location: admin_users.php?error=Username already exists. Please choose a different username.");
        exit();
    }
    $check_stmt->close();

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $insert_stmt = $conn->prepare("INSERT INTO users (name, role, username, password) VALUES (?, ?, ?, ?)");
    $insert_stmt->bind_param("ssss", $name, $role, $username, $hashed_password);
    
    if($insert_stmt->execute()) {
        $insert_stmt->close();
        header("Location: admin_users.php?success=User account created successfully!");
        exit();
    } else {
        $error_msg = $insert_stmt->error;
        $insert_stmt->close();
        header("Location: admin_users.php?error=Failed to create user account. Error: " . htmlspecialchars($error_msg));
        exit();
    }
} else {
    header("Location: admin_users.php");
    exit();
}
?>
