<?php
session_start();
include 'config.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $role = $_POST['role'];
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if(empty($name) || empty($role) || empty($username) || empty($password)) {
        header("Location: register.php?error=All fields are required");
        exit();
    }

    if($password !== $confirm_password) {
        header("Location: register.php?error=Passwords do not match");
        exit();
    }

    if(strlen($password) < 6) {
        header("Location: register.php?error=Password must be at least 6 characters");
        exit();
    }

    // Check if username already exists
    $check_sql = "SELECT * FROM users WHERE username='$username'";
    $check_result = $conn->query($check_sql);
    
    if($check_result->num_rows > 0) {
        header("Location: register.php?error=Username already exists");
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $insert_sql = "INSERT INTO users (name, role, username, password) VALUES ('$name', '$role', '$username', '$hashed_password')";
    
    if($conn->query($insert_sql)) {
        header("Location: user_login.php?success=Registration successful! Please login.");
        exit();
    } else {
        header("Location: register.php?error=Registration failed. Please try again.");
        exit();
    }
} else {
    header("Location: register.php");
    exit();
}
?>
