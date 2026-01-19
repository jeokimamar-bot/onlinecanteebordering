<?php
session_start();
include 'config.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_id = intval($_POST['item_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $availability_status = $_POST['availability_status'] ?? 'Available';

    if(empty($name) || $price <= 0 || $item_id <= 0) {
        header("Location: admin_edit_item.php?id=$item_id&error=Invalid data provided");
        exit();
    }

    $stmt = $conn->prepare("UPDATE items SET name = ?, description = ?, price = ?, quantity = ?, availability_status = ? WHERE item_id = ?");
    $stmt->bind_param("ssdisi", $name, $description, $price, $quantity, $availability_status, $item_id);

    if($stmt->execute()) {
        $stmt->close();
        header("Location: admin_items.php?success=Item updated successfully!");
        exit();
    } else {
        $error = $stmt->error;
        $stmt->close();
        header("Location: admin_edit_item.php?id=$item_id&error=Failed to update item: " . htmlspecialchars($error));
        exit();
    }
} else {
    header("Location: admin_items.php");
    exit();
}
?>
