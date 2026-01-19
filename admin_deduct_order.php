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

// Get order details
$order_stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();
$order = $order_result->fetch_assoc();
$order_stmt->close();

if(!$order) {
    header("Location: admin_orders.php?error=Order not found");
    exit();
}

// Check if order is in valid status for deduction
if($order['order_status'] != 'Completed' && $order['order_status'] != 'Processing') {
    header("Location: admin_order_details.php?id=$order_id&error=Order must be Completed or Processing to deduct inventory");
    exit();
}

// Get order items
$items_stmt = $conn->prepare("SELECT oi.*, i.name as item_name, i.quantity as current_stock FROM order_items oi JOIN items i ON oi.item_id = i.item_id WHERE oi.order_id = ?");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$order_items = $items_result->fetch_all(MYSQLI_ASSOC);
$items_stmt->close();

if(empty($order_items)) {
    header("Location: admin_order_details.php?id=$order_id&error=No items found in this order");
    exit();
}

// Check if deduction_logs table exists, create if not
$check_table = $conn->query("SHOW TABLES LIKE 'deduction_logs'");
if($check_table->num_rows == 0) {
    $create_table = "CREATE TABLE deduction_logs (
        deduction_id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        item_id INT NOT NULL,
        item_name VARCHAR(100),
        quantity_deducted INT NOT NULL,
        deducted_by INT,
        deduction_date DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($create_table);
}

// Check if notifications table exists, create if not
$check_notifications_table = $conn->query("SHOW TABLES LIKE 'notifications'");
if($check_notifications_table->num_rows == 0) {
    $create_notifications_table = "CREATE TABLE notifications (
        notification_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        order_id INT,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($create_notifications_table);
}

// Check if this order has already been deducted
$check_deduction = $conn->prepare("SELECT COUNT(*) as count FROM deduction_logs WHERE order_id = ?");
$check_deduction->bind_param("i", $order_id);
$check_deduction->execute();
$deduction_result = $check_deduction->get_result();
$deduction_count = $deduction_result->fetch_assoc()['count'];
$check_deduction->close();

if($deduction_count > 0) {
    header("Location: admin_order_details.php?id=$order_id&error=This order has already been deducted from inventory");
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    $errors = [];
    $admin_id = $_SESSION['user_id'] ?? null;
    
    // Deduct quantities for each item and log the deduction
    foreach($order_items as $item) {
        $item_id = $item['item_id'];
        $quantity_to_deduct = $item['quantity'];
        $current_stock = $item['current_stock'];
        $item_name = $item['item_name'];
        
        // Check if enough stock available
        if($current_stock < $quantity_to_deduct) {
            $errors[] = "Insufficient stock for {$item_name}. Available: $current_stock, Required: $quantity_to_deduct";
            continue;
        }
        
        // Deduct quantity
        $deduct_stmt = $conn->prepare("UPDATE items SET quantity = quantity - ? WHERE item_id = ?");
        $deduct_stmt->bind_param("ii", $quantity_to_deduct, $item_id);
        
        if(!$deduct_stmt->execute()) {
            $errors[] = "Failed to deduct stock for {$item_name}";
            $deduct_stmt->close();
            continue;
        }
        $deduct_stmt->close();
        
        // Log the deduction
        $log_stmt = $conn->prepare("INSERT INTO deduction_logs (order_id, item_id, item_name, quantity_deducted, deducted_by) VALUES (?, ?, ?, ?, ?)");
        $log_stmt->bind_param("iisii", $order_id, $item_id, $item_name, $quantity_to_deduct, $admin_id);
        
        if(!$log_stmt->execute()) {
            $errors[] = "Failed to log deduction for {$item_name}";
        }
        $log_stmt->close();
    }
    
    if(!empty($errors)) {
        $conn->rollback();
        $error_message = implode("; ", $errors);
        header("Location: admin_order_details.php?id=$order_id&error=" . urlencode($error_message));
        exit();
    }
    
    // Create notification for the user that their order is ready to claim
    $user_id = $order['user_id'];
    $notification_message = "Your order #{$order_id} is ready to claim! The items have been prepared and are available for pickup.";
    $notification_stmt = $conn->prepare("INSERT INTO notifications (user_id, order_id, message) VALUES (?, ?, ?)");
    $notification_stmt->bind_param("iis", $user_id, $order_id, $notification_message);
    $notification_stmt->execute();
    $notification_stmt->close();
    
    // Commit transaction
    $conn->commit();
    
    header("Location: admin_order_details.php?id=$order_id&success=Order quantities deducted from inventory successfully, recorded in daily report, and user notified that order is ready to claim!");
    exit();
} catch(Exception $e) {
    // Rollback on error
    $conn->rollback();
    header("Location: admin_order_details.php?id=$order_id&error=Failed to deduct order: " . htmlspecialchars($e->getMessage()));
    exit();
}
?>
