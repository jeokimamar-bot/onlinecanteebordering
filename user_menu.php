<?php
session_start();
include 'config.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: user_login.php?error=Please login first");
    exit();
}

// Get available items
$items = $conn->query("SELECT * FROM items WHERE availability_status='Available' AND quantity > 0 ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Canteen Management System</title>
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
                <a href="user_menu.php" class="nav-item active">
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
                    <h1>Menu</h1>
                    <p>Browse and order from our menu</p>
                </div>
                <a href="user_cart.php" class="btn-primary" style="text-decoration: none; display: inline-block;">🛒 View Cart</a>
            </header>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success" style="margin-bottom: 20px;">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <div class="items-grid">
                <?php if($items->num_rows > 0): ?>
                    <?php while($item = $items->fetch_assoc()): ?>
                        <div class="item-card">
                            <div class="item-content">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="item-description"><?php echo htmlspecialchars($item['description'] ?? 'Delicious meal'); ?></p>
                                <div class="item-footer">
                                    <span class="item-price">₱<?php echo number_format($item['price'], 2); ?></span>
                                    <span class="item-stock">Stock: <?php echo $item['quantity']; ?></span>
                                </div>
                                <form method="post" action="add_to_cart.php" style="margin-top: 10px;">
                                    <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                    <div style="display: flex; gap: 10px; align-items: center;">
                                        <input type="number" name="quantity" value="1" min="1" max="<?php echo $item['quantity']; ?>" style="width: 80px; padding: 8px; border: 2px solid var(--border-color); border-radius: 6px;">
                                        <button type="submit" class="btn-primary btn-sm" style="flex: 1;">Add to Cart</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No items available at the moment</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
