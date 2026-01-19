<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: user_login.php?error=Please login first");
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_id = intval($_POST['item_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    
    if($item_id <= 0 || $quantity <= 0) {
        header("Location: user_menu.php?error=Invalid item or quantity");
        exit();
    }
    
    // Check item availability
    $item_stmt = $conn->prepare("SELECT * FROM items WHERE item_id = ? AND availability_status = 'Available' AND quantity >= ?");
    $item_stmt->bind_param("ii", $item_id, $quantity);
    $item_stmt->execute();
    $item_result = $item_stmt->get_result();
    $item = $item_result->fetch_assoc();
    $item_stmt->close();
    
    if(!$item) {
        header("Location: user_menu.php?error=Item not available or insufficient stock");
        exit();
    }
    
    // Initialize cart if not exists
    if(!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Add to cart or update quantity
    if(isset($_SESSION['cart'][$item_id])) {
        $new_quantity = $_SESSION['cart'][$item_id]['quantity'] + $quantity;
        if($new_quantity > $item['quantity']) {
            header("Location: user_menu.php?error=Insufficient stock. Available: " . $item['quantity']);
            exit();
        }
        $_SESSION['cart'][$item_id]['quantity'] = $new_quantity;
    } else {
        $_SESSION['cart'][$item_id] = [
            'name' => $item['name'],
            'price' => $item['price'],
            'quantity' => $quantity
        ];
    }
    
    header("Location: user_cart.php?success=Item added to cart");
    exit();
} else {
    header("Location: user_menu.php");
    exit();
}
?>
