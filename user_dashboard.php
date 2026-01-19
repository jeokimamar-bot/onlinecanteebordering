<?php
session_start();
include 'config.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: user_login.php?error=Please login first");
    exit();
}

// Get user orders
$user_id = $_SESSION['user_id'];
$user_orders = $conn->query("SELECT * FROM orders WHERE user_id=$user_id ORDER BY order_date DESC LIMIT 5");

// Get available items with stock
$items = $conn->query("SELECT * FROM items WHERE availability_status='Available' AND quantity > 0 ORDER BY name");

// Check if notifications table exists and get unread notifications
$unread_notifications = [];
$unread_count = 0;
$check_notifications_table = $conn->query("SHOW TABLES LIKE 'notifications'");
if($check_notifications_table->num_rows > 0) {
    $notifications_query = "SELECT * FROM notifications WHERE user_id = $user_id AND is_read = 0 ORDER BY created_at DESC LIMIT 5";
    $notifications_result = $conn->query($notifications_query);
    if($notifications_result) {
        while($notification = $notifications_result->fetch_assoc()) {
            $unread_notifications[] = $notification;
        }
        $unread_count = count($unread_notifications);
        // Get total unread count
        $count_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = 0";
        $count_result = $conn->query($count_query);
        if($count_result) {
            $unread_count = $count_result->fetch_assoc()['count'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Canteen Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>🍽️ Canteen</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="user_dashboard.php" class="nav-item active">
                    <span>🏠 Dashboard</span>
                </a>
                <a href="user_menu.php" class="nav-item">
                    <span>🍕 Menu</span>
                </a>
                <a href="user_cart.php" class="nav-item">
                    <span>🛒 Cart</span>
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
                    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h1>
                    <p>Order your favorite meals from our canteen</p>
                </div>
                <div style="display: flex; gap: 15px; align-items: center;">
                    <?php if($unread_count > 0): ?>
                        <a href="user_orders.php#notifications" class="btn-primary" style="text-decoration: none; display: inline-block; position: relative;">
                            🔔 Notifications
                            <span style="position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold;"><?php echo $unread_count; ?></span>
                        </a>
                    <?php endif; ?>
                    <div class="user-info">
                        <span><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                    </div>
                </div>
            </header>

            <?php if($unread_count > 0): ?>
            <div class="dashboard-section" style="background: linear-gradient(135deg, #fef3c7, #fde68a); border-left: 4px solid #f59e0b;">
                <h2 style="color: #92400e; margin-bottom: 15px;">🔔 New Notifications (<?php echo $unread_count; ?>)</h2>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php foreach($unread_notifications as $notification): ?>
                        <div style="background: white; padding: 15px; border-radius: 8px; border: 1px solid #fbbf24; display: flex; justify-content: space-between; align-items: center;">
                            <div style="flex: 1;">
                                <p style="margin: 0; color: #78350f; font-weight: 500;"><?php echo htmlspecialchars($notification['message']); ?></p>
                                <p style="margin: 5px 0 0 0; color: #92400e; font-size: 0.875rem;"><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></p>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <?php if($notification['order_id']): ?>
                                    <a href="user_order_details.php?id=<?php echo $notification['order_id']; ?>" class="btn-small">View Order</a>
                                <?php endif; ?>
                                <a href="mark_notification_read.php?id=<?php echo $notification['notification_id']; ?>&redirect=user_dashboard.php" class="btn-small" style="background: #6b7280;">Mark Read</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if($unread_count > 5): ?>
                        <div style="text-align: center; margin-top: 10px;">
                            <a href="user_orders.php#notifications" class="btn-primary" style="text-decoration: none; display: inline-block;">View All Notifications</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="dashboard-section">
                <h2>Available Items</h2>
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
            </div>

            <div class="dashboard-section">
                <h2>My Recent Orders</h2>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($user_orders->num_rows > 0): ?>
                                <?php while($order = $user_orders->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $order['order_id']; ?></td>
                                        <td><span class="status-badge status-<?php echo strtolower($order['order_status']); ?>"><?php echo htmlspecialchars($order['order_status']); ?></span></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                        <td>
                                            <a href="user_order_details.php?id=<?php echo $order['order_id']; ?>" class="btn-small">View</a>
                                            <?php if($order['order_status'] == 'Pending'): ?>
                                                <a href="cancel_order.php?id=<?php echo $order['order_id']; ?>" class="btn-small" style="background: #ef4444;" onclick="return confirm('Cancel this order?')">Cancel</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No orders yet. Start ordering!</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

</body>
</html>
