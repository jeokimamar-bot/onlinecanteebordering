<?php
session_start();
include 'config.php';

// Check if user is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: admin_login.php?error=Please login as admin first");
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_id = intval($_POST['item_id'] ?? 0);
    $decrease_quantity = intval($_POST['decrease_quantity'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    // Validation
    if($item_id <= 0) {
        header("Location: admin_items.php?error=Invalid item ID");
        exit();
    }

    if($decrease_quantity <= 0) {
        header("Location: admin_items.php?error=Quantity to decrease must be greater than 0");
        exit();
    }

    // Get current item details
    $item_stmt = $conn->prepare("SELECT * FROM items WHERE item_id = ?");
    $item_stmt->bind_param("i", $item_id);
    $item_stmt->execute();
    $item_result = $item_stmt->get_result();
    $item = $item_result->fetch_assoc();
    $item_stmt->close();

    if(!$item) {
        header("Location: admin_items.php?error=Item not found");
        exit();
    }

    $current_quantity = $item['quantity'];

    // Check if decrease amount is valid
    if($decrease_quantity > $current_quantity) {
        header("Location: admin_items.php?error=Cannot decrease more than current quantity. Current: $current_quantity");
        exit();
    }

    // Calculate new quantity
    $new_quantity = $current_quantity - $decrease_quantity;

    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check if deduction_logs table exists, create if not
        $check_table = $conn->query("SHOW TABLES LIKE 'deduction_logs'");
        if($check_table->num_rows == 0) {
            $create_table = "CREATE TABLE deduction_logs (
                deduction_id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT DEFAULT NULL,
                item_id INT NOT NULL,
                item_name VARCHAR(100),
                quantity_deducted INT NOT NULL,
                deducted_by INT,
                reason TEXT,
                deduction_date DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
            $conn->query($create_table);
        }
        
        // Update item quantity
        $update_stmt = $conn->prepare("UPDATE items SET quantity = ? WHERE item_id = ?");
        $update_stmt->bind_param("ii", $new_quantity, $item_id);
        
        if(!$update_stmt->execute()) {
            throw new Exception("Failed to update item quantity");
        }
        $update_stmt->close();
        
        // Log the decrease to deduction_logs table
        $admin_id = $_SESSION['user_id'] ?? null;
        $item_name = $item['name'];
        
        // Check if deduction_logs table has reason column, add if not
        $check_reason_column = $conn->query("SHOW COLUMNS FROM deduction_logs LIKE 'reason'");
        if($check_reason_column->num_rows == 0) {
            $conn->query("ALTER TABLE deduction_logs ADD COLUMN reason TEXT AFTER deducted_by");
        }
        
        // Insert into deduction_logs (order_id will be NULL for manual decreases)
        // Use NULL for order_id since this is a manual decrease, not from an order
        $log_stmt = $conn->prepare("INSERT INTO deduction_logs (order_id, item_id, item_name, quantity_deducted, deducted_by, reason) VALUES (NULL, ?, ?, ?, ?, ?)");
        $log_stmt->bind_param("isiss", $item_id, $item_name, $decrease_quantity, $admin_id, $reason);
        
        if(!$log_stmt->execute()) {
            // If logging fails, continue anyway but log the error
            error_log("Failed to log quantity decrease: " . $log_stmt->error);
        }
        $log_stmt->close();
        
        // Update availability status if quantity becomes 0
        if($new_quantity == 0) {
            $status_stmt = $conn->prepare("UPDATE items SET availability_status = 'Unavailable' WHERE item_id = ?");
            $status_stmt->bind_param("i", $item_id);
            $status_stmt->execute();
            $status_stmt->close();
        } else if($item['availability_status'] == 'Unavailable' && $new_quantity > 0) {
            // If it was unavailable and now has quantity, make it available
            $status_stmt = $conn->prepare("UPDATE items SET availability_status = 'Available' WHERE item_id = ?");
            $status_stmt->bind_param("i", $item_id);
            $status_stmt->execute();
            $status_stmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        
        $success_msg = "Quantity decreased successfully! " . htmlspecialchars($item['name']) . ": $current_quantity → $new_quantity (Recorded in inventory logs)";
        if($reason) {
            $success_msg .= " (Reason: " . htmlspecialchars($reason) . ")";
        }
        
        header("Location: admin_items.php?success=" . urlencode($success_msg));
        exit();
    } catch(Exception $e) {
        // Rollback on error
        $conn->rollback();
        header("Location: admin_items.php?error=Failed to decrease quantity: " . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: admin_items.php");
    exit();
}
?>
