<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: user_login.php?error=Please login first");
    exit();
}

if($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: user_orders.php");
    exit();
}

$order_id = intval($_POST['order_id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Verify order belongs to user and is pending
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ? AND order_status = 'Pending'");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if(!$order) {
    header("Location: user_orders.php?error=Order not found or cannot be edited");
    exit();
}

$conn->begin_transaction();

try {
    $order_items = $_POST['order_items'] ?? [];
    $new_items = $_POST['new_items'] ?? [];
    
    // Process existing order items
    foreach($order_items as $item_data) {
        $order_item_id = intval($item_data['order_item_id'] ?? 0);
        $item_id = intval($item_data['item_id'] ?? 0);
        $new_quantity = intval($item_data['quantity'] ?? 0);
        
        if($order_item_id > 0 && $item_id > 0) {
            // Get current order item
            $current_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_item_id = ?");
            $current_stmt->bind_param("i", $order_item_id);
            $current_stmt->execute();
            $current_result = $current_stmt->get_result();
            $current_item = $current_result->fetch_assoc();
            $current_stmt->close();
            
            if($current_item) {
                $old_quantity = $current_item['quantity'];
                $quantity_diff = $new_quantity - $old_quantity;
                
                if($new_quantity == 0) {
                    // Remove item from order
                    $delete_stmt = $conn->prepare("DELETE FROM order_items WHERE order_item_id = ?");
                    $delete_stmt->bind_param("i", $order_item_id);
                    $delete_stmt->execute();
                    $delete_stmt->close();
                    
                    // Restore inventory
                    $restore_stmt = $conn->prepare("UPDATE items SET quantity = quantity + ? WHERE item_id = ?");
                    $restore_stmt->bind_param("ii", $old_quantity, $item_id);
                    $restore_stmt->execute();
                    $restore_stmt->close();
                } else if($quantity_diff != 0) {
                    // Update quantity
                    $update_stmt = $conn->prepare("UPDATE order_items SET quantity = ? WHERE order_item_id = ?");
                    $update_stmt->bind_param("ii", $new_quantity, $order_item_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                    
                    // Adjust inventory
                    if($quantity_diff > 0) {
                        // Need more - deduct from inventory
                        $deduct_stmt = $conn->prepare("UPDATE items SET quantity = quantity - ? WHERE item_id = ?");
                        $deduct_stmt->bind_param("ii", $quantity_diff, $item_id);
                        $deduct_stmt->execute();
                        $deduct_stmt->close();
                    } else {
                        // Need less - restore to inventory
                        $restore_stmt = $conn->prepare("UPDATE items SET quantity = quantity + ? WHERE item_id = ?");
                        $restore_stmt->bind_param("ii", abs($quantity_diff), $item_id);
                        $restore_stmt->execute();
                        $restore_stmt->close();
                    }
                }
                
                // Update availability
                $avail_stmt = $conn->prepare("UPDATE items SET availability_status = CASE WHEN quantity = 0 THEN 'Unavailable' ELSE 'Available' END WHERE item_id = ?");
                $avail_stmt->bind_param("i", $item_id);
                $avail_stmt->execute();
                $avail_stmt->close();
            }
        }
    }
    
    // Process new items
    foreach($new_items as $item_id => $quantity) {
        $item_id = intval($item_id);
        $quantity = intval($quantity);
        
        if($item_id > 0 && $quantity > 0) {
            // Check if item already in order
            $check_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ? AND item_id = ?");
            $check_stmt->bind_param("ii", $order_id, $item_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $existing = $check_result->fetch_assoc();
            $check_stmt->close();
            
            if($existing) {
                // Update existing
                $new_qty = $existing['quantity'] + $quantity;
                $update_stmt = $conn->prepare("UPDATE order_items SET quantity = ? WHERE order_item_id = ?");
                $update_stmt->bind_param("ii", $new_qty, $existing['order_item_id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Deduct inventory
                $deduct_stmt = $conn->prepare("UPDATE items SET quantity = quantity - ? WHERE item_id = ?");
                $deduct_stmt->bind_param("ii", $quantity, $item_id);
                $deduct_stmt->execute();
                $deduct_stmt->close();
            } else {
                // Add new item
                $insert_stmt = $conn->prepare("INSERT INTO order_items (order_id, item_id, quantity) VALUES (?, ?, ?)");
                $insert_stmt->bind_param("iii", $order_id, $item_id, $quantity);
                $insert_stmt->execute();
                $insert_stmt->close();
                
                // Deduct inventory
                $deduct_stmt = $conn->prepare("UPDATE items SET quantity = quantity - ? WHERE item_id = ?");
                $deduct_stmt->bind_param("ii", $quantity, $item_id);
                $deduct_stmt->execute();
                $deduct_stmt->close();
            }
            
            // Update availability
            $avail_stmt = $conn->prepare("UPDATE items SET availability_status = CASE WHEN quantity = 0 THEN 'Unavailable' ELSE 'Available' END WHERE item_id = ?");
            $avail_stmt->bind_param("i", $item_id);
            $avail_stmt->execute();
            $avail_stmt->close();
        }
    }
    
    $conn->commit();
    header("Location: user_orders.php?success=Order updated successfully!");
    exit();
    
} catch (Exception $e) {
    $conn->rollback();
    header("Location: user_edit_order.php?id=$order_id&error=" . urlencode($e->getMessage()));
    exit();
}
?>
