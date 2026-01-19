<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: user_login.php?error=Please login first");
    exit();
}

$cart = $_SESSION['cart'] ?? [];

if(empty($cart)) {
    header("Location: user_cart.php?error=Your cart is empty");
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Verify all items are still available and get current stock
    $items_data = [];
    foreach($cart as $item_id => $cart_item) {
        $stmt = $conn->prepare("SELECT * FROM items WHERE item_id = ? AND availability_status = 'Available'");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        $stmt->close();
        
        if(!$item) {
            throw new Exception("Item '{$cart_item['name']}' is no longer available");
        }
        
        if($item['quantity'] < $cart_item['quantity']) {
            throw new Exception("Insufficient stock for '{$cart_item['name']}'. Available: {$item['quantity']}");
        }
        
        $items_data[$item_id] = $item;
    }
    
    // Create order
    $user_id = $_SESSION['user_id'];
    $order_status = 'Pending';
    $stmt = $conn->prepare("INSERT INTO orders (user_id, order_status) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $order_status);
    
    if(!$stmt->execute()) {
        throw new Exception("Failed to create order");
    }
    
    $order_id = $conn->insert_id;
    $stmt->close();
    
    // Add order items and deduct from inventory
    foreach($cart as $item_id => $cart_item) {
        // Add to order_items
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, item_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $order_id, $item_id, $cart_item['quantity']);
        $stmt->execute();
        $stmt->close();
        
        // Deduct from inventory
        $new_quantity = $items_data[$item_id]['quantity'] - $cart_item['quantity'];
        $update_stmt = $conn->prepare("UPDATE items SET quantity = ? WHERE item_id = ?");
        $update_stmt->bind_param("ii", $new_quantity, $item_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        // Update availability if quantity reaches 0
        if($new_quantity == 0) {
            $avail_stmt = $conn->prepare("UPDATE items SET availability_status = 'Unavailable' WHERE item_id = ?");
            $avail_stmt->bind_param("i", $item_id);
            $avail_stmt->execute();
            $avail_stmt->close();
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Clear cart
    unset($_SESSION['cart']);
    
    header("Location: user_orders.php?success=Order placed successfully! Order ID: #$order_id");
    exit();
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    header("Location: user_cart.php?error=" . urlencode($e->getMessage()));
    exit();
}
?>
