<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: user_login.php?error=Please login first");
    exit();
}

$cart = $_SESSION['cart'] ?? [];
$total = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - Canteen Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>🍽️ Canteen</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="user_dashboard.php" class="nav-item">
                    <span>🏠 Dashboard</span>
                </a>
                <a href="user_menu.php" class="nav-item">
                    <span>🍕 Menu</span>
                </a>
                <a href="user_orders.php" class="nav-item">
                    <span>My Orders</span>
                </a>
                <a href="logout.php" class="nav-item">
                    <span>🚪 Logout</span>
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="dashboard-header">
                <div>
                    <h1>My Cart</h1>
                    <p>Review your order before checkout</p>
                </div>
                <a href="user_menu.php" class="btn-primary" style="text-decoration: none; display: inline-block;">← Continue Shopping</a>
            </header>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success" style="margin-bottom: 20px;">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-error" style="margin-bottom: 20px;">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-section">
                <?php if(!empty($cart)): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($cart as $item_id => $item): ?>
                                <?php 
                                    $subtotal = $item['price'] * $item['quantity'];
                                    $total += $subtotal;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                                    <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                    <td>
                                        <form method="post" action="update_cart.php" style="display: inline-flex; gap: 5px; align-items: center;">
                                            <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" style="width: 60px; padding: 6px; border: 2px solid var(--border-color); border-radius: 6px;">
                                            <button type="submit" class="btn-small">Update</button>
                                        </form>
                                    </td>
                                    <td>₱<?php echo number_format($subtotal, 2); ?></td>
                                    <td>
                                        <a href="remove_from_cart.php?item_id=<?php echo $item_id; ?>" class="btn-small" style="background: #ef4444;" onclick="return confirm('Remove this item?')">Remove</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr style="font-weight: bold; background: #f3f4f6;">
                                <td colspan="3" style="text-align: right;">Total:</td>
                                <td>₱<?php echo number_format($total, 2); ?></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                    <div style="margin-top: 30px; text-align: right;">
                        <form method="post" action="process_order.php">
                            <button type="submit" class="btn-primary" style="padding: 15px 40px; font-size: 1.1rem;">Place Order</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>Your cart is empty</p>
                        <a href="user_menu.php" class="btn-primary" style="text-decoration: none; display: inline-block; margin-top: 20px;">Browse Menu</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
