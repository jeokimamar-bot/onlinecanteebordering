<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: user_login.php?error=Please login first");
    exit();
}

$order_item_id = intval($_GET['order_item_id'] ?? 0);
$order_id = intval($_GET['order_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if($order_item_id > 0 && $order_id > 0) {
    // Verify order belongs to user and is pending
    $order_stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ? AND order_status = 'Pending'");
    $order_stmt->bind_param("ii", $order_id, $user_id);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result();
    $order = $order_result->fetch_assoc();
    $order_stmt->close();
    
    if($order) {
        // Get order item details
        $item_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_item_id = ? AND order_id = ?");
        $item_stmt->bind_param("ii", $order_item_id, $order_id);
        $item_stmt->execute();
        $item_result = $item_stmt->get_result();
        $order_item = $item_result->fetch_assoc();
        $item_stmt->close();
        
        if($order_item) {
            // Restore inventory
            $restore_stmt = $conn->prepare("UPDATE items SET quantity = quantity + ? WHERE item_id = ?");
            $restore_stmt->bind_param("ii", $order_item['quantity'], $order_item['item_id']);
            $restore_stmt->execute();
            $restore_stmt->close();
            
            // Update availability
            $avail_stmt = $conn->prepare("UPDATE items SET availability_status = 'Available' WHERE item_id = ? AND quantity > 0");
            $avail_stmt->bind_param("i", $order_item['item_id']);
            $avail_stmt->execute();
            $avail_stmt->close();
            
            // Delete order item
            $delete_stmt = $conn->prepare("DELETE FROM order_items WHERE order_item_id = ?");
            $delete_stmt->bind_param("i", $order_item_id);
            $delete_stmt->execute();
            $delete_stmt->close();
            
            header("Location: user_edit_order.php?id=$order_id&success=Item removed from order");
        } else {
            header("Location: user_edit_order.php?id=$order_id&error=Item not found");
        }
    } else {
        header("Location: user_orders.php?error=Order not found or cannot be edited");
    }
} else {
    header("Location: user_orders.php?error=Invalid request");
}
exit();
?>
