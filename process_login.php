<?php
session_start();
include 'config.php';

// Check if form was submitted
if($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: admin_login.php?error=Invalid request");
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$login_type = $_POST['login_type'] ?? 'user';

// Validate input
if(empty($username) || empty($password)) {
    if($login_type == 'admin') {
        header("Location: admin_login.php?error=Username and password are required");
    } else {
        header("Location: user_login.php?error=Username and password are required");
    }
    exit();
}

// Check if admin login
if($login_type == 'admin') {
    // First, try to find user by username (case-insensitive)
    $stmt = $conn->prepare("SELECT * FROM users WHERE LOWER(username) = LOWER(?)");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if($res->num_rows == 1) {
        $row = $res->fetch_assoc();
        
        // Check if user is admin (case-insensitive check)
        if(strtolower($row['role']) == 'admin') {
            // Check if password hash exists and verify
            if(!empty($row['password']) && password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['name'] = $row['name'] ?? 'Admin';
                $_SESSION['role'] = 'admin';
                $stmt->close();
                header("Location: admin_dashboard.php");
                exit();
            } else {
                $stmt->close();
                header("Location: admin_login.php?error=Invalid password. Please check your password and try again.");
                exit();
            }
        } else {
            $stmt->close();
            header("Location: admin_login.php?error=This account is not an admin account. Please use user login instead.");
            exit();
        }
    } else {
        $stmt->close();
        // Check if username exists but with different case or as regular user
        $check_stmt = $conn->prepare("SELECT username, role FROM users WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_res = $check_stmt->get_result();
        
        if($check_res->num_rows > 0) {
            $check_row = $check_res->fetch_assoc();
            $check_stmt->close();
            header("Location: admin_login.php?error=Account found but it's not an admin account. Role: " . htmlspecialchars($check_row['role']) . ". Please use user login instead.");
        } else {
            $check_stmt->close();
            header("Location: admin_login.php?error=Admin account not found. Username '" . htmlspecialchars($username) . "' does not exist. Please check your username or contact system administrator.");
        }
        exit();
    }
} else {
    // Regular user login
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND role != 'admin'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if($res->num_rows == 1) {
        $row = $res->fetch_assoc();
        
        // Check if password hash exists and verify
        if(!empty($row['password']) && password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['name'] = $row['name'] ?? 'User';
            $_SESSION['role'] = $row['role'];
            $stmt->close();
            header("Location: user_dashboard.php");
            exit();
        } else {
            $stmt->close();
            header("Location: user_login.php?error=Invalid password");
            exit();
        }
    } else {
        $stmt->close();
        header("Location: user_login.php?error=User account not found. Please check your username or register a new account.");
        exit();
    }
}
?>
