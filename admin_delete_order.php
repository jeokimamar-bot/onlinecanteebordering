<?php
session_start();
include 'config.php';

// Check if user is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: admin_login.php?error=Please login as admin first");
    exit();
}

$order_id = intval($_GET['id'] ?? 0);

if($order_id <= 0) {
    header("Location: admin_orders.php?error=Invalid order ID");
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Delete order items first (foreign key constraint)
    $delete_items_stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
    $delete_items_stmt->bind_param("i", $order_id);
    $delete_items_stmt->execute();
    $delete_items_stmt->close();
    
    // Delete the order
    $delete_order_stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
    $delete_order_stmt->bind_param("i", $order_id);
    $delete_order_stmt->execute();
    $delete_order_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    header("Location: admin_orders.php?success=Order deleted successfully");
    exit();
} catch(Exception $e) {
    // Rollback on error
    $conn->rollback();
    header("Location: admin_orders.php?error=Failed to delete order: " . htmlspecialchars($e->getMessage()));
    exit();
}
?>
