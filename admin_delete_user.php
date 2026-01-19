<?php
session_start();
include 'config.php';

// Auto-set admin session if not set
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    $admin_query = $conn->query("SELECT * FROM users WHERE LOWER(role) = 'admin' LIMIT 1");
    if($admin_query->num_rows > 0) {
        $admin = $admin_query->fetch_assoc();
        $_SESSION['user_id'] = $admin['user_id'];
        $_SESSION['username'] = $admin['username'];
        $_SESSION['name'] = $admin['name'] ?? 'Administrator';
        $_SESSION['role'] = 'admin';
    }
}

$user_id = intval($_GET['id'] ?? 0);

if($user_id > 0) {
    // Get user details
    $check_stmt = $conn->prepare("SELECT user_id, role, name FROM users WHERE user_id = ?");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $user = $check_result->fetch_assoc();
    $check_stmt->close();
    
    if(!$user) {
        header("Location: admin_users.php?error=User not found");
        exit();
    }
    
    // Prevent self-deletion
    if($user_id == $_SESSION['user_id']) {
        header("Location: admin_users.php?error=Cannot delete your own account");
        exit();
    }
    
    // Allow deletion of admin accounts (with warning in UI)
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    
    if($stmt->execute()) {
        $stmt->close();
        $user_type = strtolower($user['role']) == 'admin' ? 'Admin' : 'User';
        header("Location: admin_users.php?success={$user_type} deleted successfully!");
        exit();
    } else {
        $stmt->close();
        header("Location: admin_users.php?error=Failed to delete user");
        exit();
    }
} else {
    header("Location: admin_users.php?error=Invalid user ID");
    exit();
}
?>
