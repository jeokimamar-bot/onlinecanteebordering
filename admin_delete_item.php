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

$item_id = intval($_GET['id'] ?? 0);

if($item_id > 0) {
    $stmt = $conn->prepare("DELETE FROM items WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    
    if($stmt->execute()) {
        $stmt->close();
        header("Location: admin_items.php?success=Item deleted successfully!");
        exit();
    } else {
        $stmt->close();
        header("Location: admin_items.php?error=Failed to delete item");
        exit();
    }
} else {
    header("Location: admin_items.php?error=Invalid item ID");
    exit();
}
?>
