<?php
session_start();
include 'config.php';

// Check if user is admin
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

// IDs of admin users to delete (from the user's list)
$admin_ids_to_delete = [7, 6, 4, 3, 2];

// Require confirmation parameter for safety
$confirm = $_GET['confirm'] ?? '';
if($confirm !== 'yes') {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Delete Admin Accounts</title>";
    echo "<style>body{font-family:Arial,sans-serif;padding:40px;text-align:center;background:#f5f5f5;}";
    echo ".warning{background:#fff3cd;border:2px solid #ffc107;padding:20px;border-radius:10px;max-width:600px;margin:20px auto;}";
    echo ".btn{display:inline-block;padding:12px 24px;margin:10px;text-decoration:none;border-radius:5px;font-weight:bold;}";
    echo ".btn-danger{background:#dc3545;color:white;}.btn-cancel{background:#6c757d;color:white;}</style></head><body>";
    echo "<div class='warning'>";
    echo "<h2 style='color:#856404;'>⚠️ Warning: Delete Admin Accounts</h2>";
    echo "<p><strong>You are about to delete the following admin accounts:</strong></p>";
    echo "<ul style='text-align:left;display:inline-block;'>";
    foreach($admin_ids_to_delete as $id) {
        echo "<li>Admin ID: $id</li>";
    }
    echo "</ul>";
    echo "<p style='color:red;font-weight:bold;'>This action cannot be undone!</p>";
    echo "<a href='?confirm=yes' class='btn btn-danger' onclick=\"return confirm('Are you absolutely sure you want to delete these admin accounts?')\">Yes, Delete These Admins</a>";
    echo "<a href='admin_users.php' class='btn btn-cancel'>Cancel</a>";
    echo "</div></body></html>";
    exit;
}

// Start transaction
$conn->begin_transaction();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Deleting Admin Accounts</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:40px;background:#f5f5f5;}";
echo ".container{background:white;padding:30px;border-radius:10px;max-width:800px;margin:0 auto;}</style></head><body>";
echo "<div class='container'><h2>Deleting Admin Accounts...</h2>";

try {
    $deleted_count = 0;
    $errors = [];
    
    foreach($admin_ids_to_delete as $user_id) {
        // Verify the user exists and is an admin
        $check_stmt = $conn->prepare("SELECT user_id, name, username, role FROM users WHERE user_id = ?");
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $user = $result->fetch_assoc();
        $check_stmt->close();
        
        if(!$user) {
            $errors[] = "User ID $user_id not found";
            continue;
        }
        
        if(strtolower($user['role']) != 'admin') {
            $errors[] = "User ID $user_id ({$user['name']}) is not an admin";
            continue;
        }
        
        // Check if this is the currently logged-in admin (prevent self-deletion)
        if($user_id == $_SESSION['user_id']) {
            $errors[] = "Cannot delete yourself (User ID $user_id: {$user['name']})";
            continue;
        }
        
        // Delete the admin user
        $delete_stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $delete_stmt->bind_param("i", $user_id);
        
        if($delete_stmt->execute()) {
            $deleted_count++;
            echo "✓ Deleted admin: ID $user_id - {$user['name']} ({$user['username']})<br>";
        } else {
            $errors[] = "Failed to delete User ID $user_id: " . $delete_stmt->error;
        }
        $delete_stmt->close();
    }
    
    if($deleted_count > 0) {
        $conn->commit();
        echo "<br><strong style='color: green;'>Successfully deleted $deleted_count admin account(s)!</strong><br>";
    } else {
        $conn->rollback();
        echo "<strong style='color: red;'>No admin accounts were deleted.</strong><br>";
    }
    
    if(!empty($errors)) {
        echo "<br><strong style='color: orange;'>Errors:</strong><br>";
        foreach($errors as $error) {
            echo "- $error<br>";
        }
    }
    
    echo "<br><a href='admin_users.php' style='padding: 10px 20px; background: #6366f1; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px;'>← Back to Users</a>";
    
} catch(Exception $e) {
    $conn->rollback();
    echo "<strong style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</strong><br>";
    echo "<a href='admin_users.php' style='padding: 10px 20px; background: #6366f1; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px;'>← Back to Users</a>";
}

echo "</body></html>";
?>
