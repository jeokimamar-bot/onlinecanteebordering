<?php
// Test script to check admin account in database
include 'config.php';

echo "<h2>Admin Account Check</h2>";

// Check all admin accounts
$sql = "SELECT user_id, name, username, role, password FROM users WHERE role = 'admin'";
$result = $conn->query($sql);

if($result->num_rows > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Name</th><th>Username</th><th>Role</th><th>Password Hash</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "<td>" . substr($row['password'], 0, 30) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test password verification
    echo "<h3>Password Verification Test</h3>";
    $test_username = "admin12345";
    $test_password = "admin123";
    
    $check_sql = "SELECT * FROM users WHERE username = '$test_username' AND role = 'admin'";
    $check_result = $conn->query($check_sql);
    
    if($check_result->num_rows > 0) {
        $user = $check_result->fetch_assoc();
        echo "<p>Testing username: <strong>$test_username</strong></p>";
        echo "<p>Testing password: <strong>$test_password</strong></p>";
        
        if(password_verify($test_password, $user['password'])) {
            echo "<p style='color: green;'>✅ Password verification SUCCESSFUL!</p>";
        } else {
            echo "<p style='color: red;'>❌ Password verification FAILED!</p>";
            echo "<p>Password hash in DB: " . substr($user['password'], 0, 50) . "...</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Admin account '$test_username' not found!</p>";
    }
} else {
    echo "<p style='color: red;'>❌ No admin accounts found in database!</p>";
    echo "<p>Please run <a href='create_admin.php'>create_admin.php</a> to create an admin account.</p>";
}

$conn->close();
?>
