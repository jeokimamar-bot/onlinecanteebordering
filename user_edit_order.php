<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: user_login.php?error=Please login first");
    exit();
}

$order_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Get order and verify it belongs to user and is pending
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ? AND order_status = 'Pending'");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if(!$order) {
    header("Location: user_orders.php?error=Order not found or cannot be edited");
    exit();
}

// Get current order items
$items_stmt = $conn->prepare("SELECT oi.*, i.name as item_name, i.price, i.quantity as stock_quantity, i.availability_status FROM order_items oi JOIN items i ON oi.item_id = i.item_id WHERE oi.order_id = ?");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$order_items = $items_result->fetch_all(MYSQLI_ASSOC);
$items_stmt->close();

// Get all available items for adding new items
$available_items = $conn->query("SELECT * FROM items WHERE availability_status = 'Available' AND quantity > 0 ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Order - Canteen Management System</title>
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
                <a href="user_orders.php" class="nav-item active">
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
                    <h1>Edit Order #<?php echo $order['order_id']; ?></h1>
                    <p>Modify your pending order</p>
                </div>
                <a href="user_orders.php" class="btn-primary" style="text-decoration: none; display: inline-block;">← Back to Orders</a>
            </header>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-error" style="margin-bottom: 20px;">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-section">
                <form method="post" action="process_edit_order.php">
                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                    
                    <h2>Current Order Items</h2>
                    <div class="table-container" style="margin-bottom: 30px;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Price</th>
                                    <th>Current Qty</th>
                                    <th>New Quantity</th>
                                    <th>Stock Available</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($order_items) > 0): ?>
                                    <?php foreach($order_items as $index => $item): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                                            <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>
                                                <input type="hidden" name="order_items[<?php echo $index; ?>][order_item_id]" value="<?php echo $item['order_item_id']; ?>">
                                                <input type="hidden" name="order_items[<?php echo $index; ?>][item_id]" value="<?php echo $item['item_id']; ?>">
                                                <input type="number" name="order_items[<?php echo $index; ?>][quantity]" value="<?php echo $item['quantity']; ?>" min="0" max="<?php echo $item['stock_quantity'] + $item['quantity']; ?>" style="width: 80px; padding: 8px; border: 2px solid var(--border-color); border-radius: 6px;">
                                            </td>
                                            <td><?php echo $item['stock_quantity'] + $item['quantity']; ?> (includes current order)</td>
                                            <td>
                                                <a href="remove_order_item.php?order_item_id=<?php echo $item['order_item_id']; ?>&order_id=<?php echo $order_id; ?>" class="btn-small" style="background: #ef4444;" onclick="return confirm('Remove this item?')">Remove</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No items in this order</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <h2>Add More Items</h2>
                    <div class="items-grid" style="margin-bottom: 30px;">
                        <?php if($available_items->num_rows > 0): ?>
                            <?php while($item = $available_items->fetch_assoc()): ?>
                                <div class="item-card">
                                    <div class="item-content">
                                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                        <p class="item-description"><?php echo htmlspecialchars($item['description'] ?? 'Delicious meal'); ?></p>
                                        <div class="item-footer">
                                            <span class="item-price">₱<?php echo number_format($item['price'], 2); ?></span>
                                            <span class="item-stock">Stock: <?php echo $item['quantity']; ?></span>
                                        </div>
                                        <div style="display: flex; gap: 10px; align-items: center; margin-top: 10px;">
                                            <input type="number" name="new_items[<?php echo $item['item_id']; ?>]" value="0" min="0" max="<?php echo $item['quantity']; ?>" style="width: 80px; padding: 8px; border: 2px solid var(--border-color); border-radius: 6px;">
                                            <span style="font-size: 0.9rem; color: var(--text-light);">Qty</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>No additional items available</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div style="text-align: right; margin-top: 30px;">
                        <button type="submit" class="btn-primary" style="padding: 15px 40px; font-size: 1.1rem;">Update Order</button>
                        <a href="user_orders.php" class="btn-primary" style="text-decoration: none; display: inline-block; padding: 15px 40px; font-size: 1.1rem; background: #6b7280; margin-left: 10px;">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
