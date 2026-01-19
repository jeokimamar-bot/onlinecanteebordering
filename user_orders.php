<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: user_login.php?error=Please login first");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user orders
$orders = $conn->query("SELECT * FROM orders WHERE user_id = $user_id ORDER BY order_date DESC");

// Check if notifications table exists and get notifications
$notifications = [];
$unread_count = 0;
$check_notifications_table = $conn->query("SHOW TABLES LIKE 'notifications'");
if($check_notifications_table->num_rows > 0) {
    $notifications_query = "SELECT * FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC";
    $notifications_result = $conn->query($notifications_query);
    if($notifications_result) {
        while($notification = $notifications_result->fetch_assoc()) {
            $notifications[] = $notification;
            if($notification['is_read'] == 0) {
                $unread_count++;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Canteen Management System</title>
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
                    <h1>My Orders</h1>
                    <p>View your order history</p>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <?php if($unread_count > 0): ?>
                        <a href="#notifications" class="btn-primary" style="text-decoration: none; display: inline-block; position: relative;">
                            🔔 Notifications
                            <span style="position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold;"><?php echo $unread_count; ?></span>
                        </a>
                    <?php endif; ?>
                    <a href="user_menu.php" class="btn-primary" style="text-decoration: none; display: inline-block;">+ New Order</a>
                </div>
            </header>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success" style="margin-bottom: 20px;">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <?php if(!empty($notifications)): ?>
            <div class="dashboard-section" id="notifications" style="background: linear-gradient(135deg, #fef3c7, #fde68a); border-left: 4px solid #f59e0b; margin-bottom: 30px;">
                <h2 style="color: #92400e; margin-bottom: 15px;">🔔 Notifications (<?php echo $unread_count; ?> unread)</h2>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <?php foreach($notifications as $notification): ?>
                        <div style="background: white; padding: 15px; border-radius: 8px; border: 1px solid <?php echo $notification['is_read'] == 0 ? '#fbbf24' : '#e5e7eb'; ?>; display: flex; justify-content: space-between; align-items: center; <?php echo $notification['is_read'] == 0 ? 'font-weight: 500;' : 'opacity: 0.8;'; ?>">
                            <div style="flex: 1;">
                                <p style="margin: 0; color: #78350f;"><?php echo htmlspecialchars($notification['message']); ?></p>
                                <p style="margin: 5px 0 0 0; color: #92400e; font-size: 0.875rem;"><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></p>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <?php if($notification['order_id']): ?>
                                    <a href="user_order_details.php?id=<?php echo $notification['order_id']; ?>" class="btn-small">View Order</a>
                                <?php endif; ?>
                                <?php if($notification['is_read'] == 0): ?>
                                    <a href="mark_notification_read.php?id=<?php echo $notification['notification_id']; ?>&redirect=user_orders.php" class="btn-small" style="background: #6b7280;">Mark Read</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="dashboard-section">
                <h2>Order History</h2>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($orders->num_rows > 0): ?>
                                <?php while($order = $orders->fetch_assoc()): 
                                    // Calculate order total
                                    $order_items = $conn->query("SELECT oi.*, i.price FROM order_items oi JOIN items i ON oi.item_id = i.item_id WHERE oi.order_id = " . $order['order_id']);
                                    $order_total = 0;
                                    while($oi = $order_items->fetch_assoc()) {
                                        $order_total += $oi['price'] * $oi['quantity'];
                                    }
                                ?>
                                    <tr>
                                        <td>#<?php echo $order['order_id']; ?></td>
                                        <td><span class="status-badge status-<?php echo strtolower($order['order_status']); ?>"><?php echo htmlspecialchars($order['order_status']); ?></span></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                        <td>₱<?php echo number_format($order_total, 2); ?></td>
                                        <td>
                                            <a href="user_order_details.php?id=<?php echo $order['order_id']; ?>" class="btn-small">View Details</a>
                                            <?php if($order['order_status'] == 'Pending'): ?>
                                                <a href="user_edit_order.php?id=<?php echo $order['order_id']; ?>" class="btn-small" style="background: #3b82f6;">Edit</a>
                                                <a href="cancel_order.php?id=<?php echo $order['order_id']; ?>" class="btn-small" style="background: #ef4444;" onclick="return confirm('Cancel this order?')">Cancel</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No orders yet. <a href="user_menu.php">Start ordering!</a></td>
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
