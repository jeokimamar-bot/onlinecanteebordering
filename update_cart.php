<?php
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: user_login.php?error=Please login first");
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_id = intval($_POST['item_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    
    if($item_id > 0 && $quantity > 0 && isset($_SESSION['cart'][$item_id])) {
        include 'config.php';
        
        // Check stock availability
        $item_stmt = $conn->prepare("SELECT quantity FROM items WHERE item_id = ?");
        $item_stmt->bind_param("i", $item_id);
        $item_stmt->execute();
        $item_result = $item_stmt->get_result();
        $item = $item_result->fetch_assoc();
        $item_stmt->close();
        
        if($item && $quantity <= $item['quantity']) {
            $_SESSION['cart'][$item_id]['quantity'] = $quantity;
            header("Location: user_cart.php?success=Cart updated");
        } else {
            header("Location: user_cart.php?error=Insufficient stock. Available: " . ($item['quantity'] ?? 0));
        }
        exit();
    }
}

header("Location: user_cart.php");
exit();
?>
