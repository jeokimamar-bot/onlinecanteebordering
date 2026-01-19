<?php
session_start();
include 'config.php';

// Auto-set admin session if not set
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    $admin_query = $conn->query("SELECT * FROM users WHERE LOWER(role) = 'admin' LIMIT 1");
    if($admin_query->num_rows > 0) {
        $admin = $admin_query->fetch_assoc();
        $_SESSION['user_id'] = $admin['user_id'];
        $_SESSION['username'] = $admin['username'];
        $_SESSION['name'] = $admin['name'] ?? 'Administrator';
        $_SESSION['role'] = 'admin';
    }
}

$order_id = intval($_GET['id'] ?? 0);

// Get order details
$order_stmt = $conn->prepare("SELECT o.*, u.name as user_name, u.username FROM orders o JOIN users u ON o.user_id = u.user_id WHERE o.order_id = ?");
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();
$order = $order_result->fetch_assoc();
$order_stmt->close();

if(!$order) {
    header("Location: admin_orders.php?error=Order not found");
    exit();
}

// Get order items
$items_stmt = $conn->prepare("SELECT oi.*, i.name as item_name, i.price FROM order_items oi JOIN items i ON oi.item_id = i.item_id WHERE oi.order_id = ?");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$order_items = $items_result->fetch_all(MYSQLI_ASSOC);
$items_stmt->close();

$total = 0;
foreach($order_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Admin Panel</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>🍽️ Admin Panel</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="nav-item">
                    <span>📊 Dashboard</span>
                </a>
                <a href="admin_items.php" class="nav-item">
                    <span>🍕 Manage Items</span>
                </a>
                <a href="admin_orders.php" class="nav-item active">
                    <span>Orders</span>
                </a>
                <a href="admin_users.php" class="nav-item">
                    <span>👥 Users</span>
                </a>
                <a href="admin_daily_report.php" class="nav-item">
                    <span>📈 Daily Report</span>
                </a>
                <a href="logout.php" class="nav-item">
                    <span>🚪 Logout</span>
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="dashboard-header">
                <div>
                    <h1>Order #<?php echo $order['order_id']; ?></h1>
                    <p>Order details and items</p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="admin_orders.php" class="btn-primary" style="text-decoration: none; display: inline-block;">← Back to Orders</a>
                    <?php 
                    // Check if order has been deducted
                    $check_deduction = $conn->query("SHOW TABLES LIKE 'deduction_logs'");
                    $is_deducted = false;
                    if($check_deduction->num_rows > 0) {
                        $deduction_check = $conn->prepare("SELECT COUNT(*) as count FROM deduction_logs WHERE order_id = ?");
                        $deduction_check->bind_param("i", $order_id);
                        $deduction_check->execute();
                        $deduction_result = $deduction_check->get_result();
                        $is_deducted = $deduction_result->fetch_assoc()['count'] > 0;
                        $deduction_check->close();
                    }
                    
                    if($order['order_status'] == 'Completed' || $order['order_status'] == 'Processing'): 
                        if(!$is_deducted):
                    ?>
                        <a href="admin_deduct_order.php?id=<?php echo $order['order_id']; ?>" class="btn-primary" style="text-decoration: none; display: inline-block; background: #10b981;" onclick="return confirm('Deduct quantities from inventory for this order? This will reduce item stock and record to daily report.')">Deduct Order</a>
                    <?php 
                        else: 
                    ?>
                        <span class="btn-primary" style="text-decoration: none; display: inline-block; background: #6b7280; cursor: default;">Already Deducted</span>
                    <?php 
                        endif;
                    endif; 
                    ?>
                </div>
            </header>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-error" style="margin-bottom: 20px;">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success" style="margin-bottom: 20px;">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-section">
                <h2>Order Information</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <div>
                        <strong>Order ID:</strong> #<?php echo $order['order_id']; ?>
                    </div>
                    <div>
                        <strong>Customer:</strong> <?php echo htmlspecialchars($order['user_name']); ?> (<?php echo htmlspecialchars($order['username']); ?>)
                    </div>
                    <div>
                        <strong>Status:</strong> <span class="status-badge status-<?php echo strtolower($order['order_status']); ?>"><?php echo htmlspecialchars($order['order_status']); ?></span>
                    </div>
                    <div>
                        <strong>Order Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?>
                    </div>
                </div>

                <h2>Order Items</h2>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($order_items) > 0): ?>
                                <?php foreach($order_items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                        <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr style="font-weight: bold; background: #f3f4f6;">
                                    <td colspan="3" style="text-align: right;">Total:</td>
                                    <td>₱<?php echo number_format($total, 2); ?></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No items in this order</td>
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
