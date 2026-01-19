<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: user_login.php?error=Please login first");
    exit();
}

$order_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Get order
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if(!$order) {
    header("Location: user_orders.php?error=Order not found");
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
    <title>Order Details - Canteen Management System</title>
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
                    <h1>Order #<?php echo $order['order_id']; ?></h1>
                    <p>Order details</p>
                </div>
                <a href="user_orders.php" class="btn-primary" style="text-decoration: none; display: inline-block;">← Back to Orders</a>
            </header>

            <div class="dashboard-section">
                <h2>Order Information</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <div>
                        <strong>Order ID:</strong> #<?php echo $order['order_id']; ?>
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
