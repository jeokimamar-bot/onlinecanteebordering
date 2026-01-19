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

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_status = $_POST['status'] ?? '';
    
    if($order_id > 0 && !empty($new_status)) {
        $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        
        if($stmt->execute()) {
            $stmt->close();
            header("Location: admin_orders.php?success=Order status updated successfully!");
            exit();
        } else {
            $stmt->close();
            header("Location: admin_orders.php?error=Failed to update order status");
            exit();
        }
    }
}

// Get order
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();
$stmt->close();

if(!$order) {
    header("Location: admin_orders.php?error=Order not found");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Order Status - Admin Panel</title>
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
                    <h1>Update Order Status</h1>
                    <p>Order #<?php echo $order['order_id']; ?></p>
                </div>
                <a href="admin_orders.php" class="btn-primary" style="text-decoration: none; display: inline-block;">← Back to Orders</a>
            </header>

            <div class="dashboard-section">
                <form method="post" class="register-form" style="max-width: 400px;">
                    <div class="form-group">
                        <label>Current Status</label>
                        <input type="text" value="<?php echo htmlspecialchars($order['order_status']); ?>" disabled style="background: #f3f4f6;">
                    </div>
                    <div class="form-group">
                        <label>New Status</label>
                        <select name="status" required>
                            <option value="Pending" <?php echo $order['order_status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Processing" <?php echo $order['order_status'] == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="Completed" <?php echo $order['order_status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="Cancelled" <?php echo $order['order_status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" class="btn-primary">Update Status</button>
                        <a href="admin_orders.php" class="btn-primary" style="text-decoration: none; display: inline-block; background: #6b7280; text-align: center;">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
