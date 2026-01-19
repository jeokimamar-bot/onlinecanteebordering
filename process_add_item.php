<?php
session_start();
include 'config.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $availability_status = $_POST['availability_status'] ?? 'Available';

    if(empty($name) || $price <= 0) {
        header("Location: admin_items.php?error=Name and price are required");
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO items (name, description, price, quantity, availability_status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdis", $name, $description, $price, $quantity, $availability_status);

    if($stmt->execute()) {
        $stmt->close();
        header("Location: admin_items.php?success=Item added successfully!");
        exit();
    } else {
        $error = $stmt->error;
        $stmt->close();
        header("Location: admin_items.php?error=Failed to add item: " . htmlspecialchars($error));
        exit();
    }
} else {
    header("Location: admin_items.php");
    exit();
}
?>
