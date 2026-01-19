<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: user_login.php?error=Please login first");
    exit();
}

$order_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

if($order_id > 0) {
    // Verify order belongs to user and is pending
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ? AND order_status = 'Pending'");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
    
    if($order) {
        // Get order items to restore inventory
        $items_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $items_stmt->bind_param("i", $order_id);
        $items_stmt->execute();
        $items_result = $items_stmt->get_result();
        $order_items = $items_result->fetch_all(MYSQLI_ASSOC);
        $items_stmt->close();
        
        // Restore inventory
        foreach($order_items as $oi) {
            $restore_stmt = $conn->prepare("UPDATE items SET quantity = quantity + ? WHERE item_id = ?");
            $restore_stmt->bind_param("ii", $oi['quantity'], $oi['item_id']);
            $restore_stmt->execute();
            $restore_stmt->close();
            
            // Update availability if quantity becomes > 0
            $avail_stmt = $conn->prepare("UPDATE items SET availability_status = 'Available' WHERE item_id = ? AND quantity > 0");
            $avail_stmt->bind_param("i", $oi['item_id']);
            $avail_stmt->execute();
            $avail_stmt->close();
        }
        
        // Update order status
        $update_stmt = $conn->prepare("UPDATE orders SET order_status = 'Cancelled' WHERE order_id = ?");
        $update_stmt->bind_param("i", $order_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        header("Location: user_orders.php?success=Order cancelled successfully. Inventory restored.");
    } else {
        header("Location: user_orders.php?error=Order not found or cannot be cancelled");
    }
} else {
    header("Location: user_orders.php?error=Invalid order ID");
}
exit();
?>
