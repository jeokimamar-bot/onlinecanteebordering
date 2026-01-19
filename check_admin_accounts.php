<?php
// Diagnostic tool to check admin accounts
include 'config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Admin Accounts Check</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔍 Admin Accounts Diagnostic Tool</h1>";

// Check all users
$all_users = $conn->query("SELECT user_id, name, username, role, password FROM users ORDER BY user_id");

if($all_users->num_rows > 0) {
    echo "<div class='info'><strong>Total Users in Database:</strong> " . $all_users->num_rows . "</div>";
    
    echo "<h2>All Users:</h2>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Username</th><th>Role</th><th>Password Hash (first 30 chars)</th></tr>";
    
    $admin_count = 0;
    while($user = $all_users->fetch_assoc()) {
        $is_admin = (strtolower($user['role']) == 'admin');
        if($is_admin) $admin_count++;
        
        $row_class = $is_admin ? "style='background-color: #d4edda;'" : "";
        echo "<tr $row_class>";
        echo "<td>" . $user['user_id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['name'] ?? 'N/A') . "</td>";
        echo "<td>" . htmlspecialchars($user['username']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($user['role']) . "</strong></td>";
        echo "<td>" . substr($user['password'], 0, 30) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div class='info'>";
    echo "<strong>Admin Accounts Found:</strong> <span class='" . ($admin_count > 0 ? "success" : "error") . "'>$admin_count</span><br>";
    
    if($admin_count == 0) {
        echo "<p class='error'>⚠️ No admin accounts found! Please create one:</p>";
        echo "<ul>";
        echo "<li><a href='create_admin.php'>Create default admin (admin12345/admin123)</a></li>";
        echo "</ul>";
    } else {
        echo "<p class='success'>✅ Admin accounts exist in the database.</p>";
    }
    echo "</div>";
    
    // Test login for each admin
    if($admin_count > 0) {
        echo "<h2>Admin Account Details:</h2>";
        $admin_query = $conn->query("SELECT user_id, name, username, role, password FROM users WHERE LOWER(role) = 'admin'");
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Username</th><th>Role</th><th>Can Login?</th></tr>";
        
        while($admin = $admin_query->fetch_assoc()) {
            $has_password = !empty($admin['password']);
            $can_login = $has_password && strlen($admin['password']) > 20; // Basic check for hashed password
            
            echo "<tr>";
            echo "<td>" . $admin['user_id'] . "</td>";
            echo "<td>" . htmlspecialchars($admin['name'] ?? 'N/A') . "</td>";
            echo "<td><strong>" . htmlspecialchars($admin['username']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($admin['role']) . "</td>";
            echo "<td class='" . ($can_login ? "success" : "error") . "'>" . ($can_login ? "✅ Yes" : "❌ No (password issue)") . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} else {
    echo "<div class='error'>";
    echo "<h2>❌ No users found in database!</h2>";
    echo "<p>Please create an admin account:</p>";
    echo "<ul>";
    echo "<li><a href='create_admin.php'>Create default admin (admin12345/admin123)</a></li>";
    echo "<li><a href='admin_register.php'>Register new admin account</a></li>";
    echo "</ul>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='admin_login.php'>← Back to Admin Login</a> | <a href='create_admin.php'>Create Default Admin</a></p>";

$conn->close();
echo "</body></html>";
?>
