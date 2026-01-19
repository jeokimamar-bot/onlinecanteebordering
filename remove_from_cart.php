<?php
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: user_login.php?error=Please login first");
    exit();
}

$item_id = intval($_GET['item_id'] ?? 0);

if($item_id > 0 && isset($_SESSION['cart'][$item_id])) {
    unset($_SESSION['cart'][$item_id]);
    header("Location: user_cart.php?success=Item removed from cart");
} else {
    header("Location: user_cart.php?error=Item not found in cart");
}
exit();
?>
