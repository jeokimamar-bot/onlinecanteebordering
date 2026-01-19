<?php
session_start();
include 'config.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: user_login.php?error=Please login first");
    exit();
}

$notification_id = intval($_GET['id'] ?? 0);
$redirect = $_GET['redirect'] ?? 'user_orders.php';

if($notification_id <= 0) {
    header("Location: $redirect?error=Invalid notification ID");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if notifications table exists
$check_notifications_table = $conn->query("SHOW TABLES LIKE 'notifications'");
if($check_notifications_table->num_rows > 0) {
    // Verify the notification belongs to the user
    $check_stmt = $conn->prepare("SELECT notification_id FROM notifications WHERE notification_id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $notification_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $check_stmt->close();
    
    if($result->num_rows > 0) {
        // Mark notification as read
        $update_stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
        $update_stmt->bind_param("ii", $notification_id, $user_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        header("Location: $redirect?success=Notification marked as read");
        exit();
    }
}

header("Location: $redirect?error=Notification not found");
exit();
?>
