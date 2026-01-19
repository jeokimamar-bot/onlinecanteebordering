<?php
session_start();
include 'config.php';

// Auto-set admin session if not set
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    // Get first admin account from database
    $admin_query = $conn->query("SELECT * FROM users WHERE LOWER(role) = 'admin' LIMIT 1");
    
    if($admin_query->num_rows > 0) {
        $admin = $admin_query->fetch_assoc();
        $_SESSION['user_id'] = $admin['user_id'];
        $_SESSION['username'] = $admin['username'];
        $_SESSION['name'] = $admin['name'] ?? 'Administrator';
        $_SESSION['role'] = 'admin';
    } else {
        // Set default admin session if no admin in database
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['name'] = 'Administrator';
        $_SESSION['role'] = 'admin';
    }
}

// Get statistics
$total_items_result = $conn->query("SELECT COUNT(*) as count FROM items");
$total_items = $total_items_result ? $total_items_result->fetch_assoc()['count'] : 0;

$total_orders_result = $conn->query("SELECT COUNT(*) as count FROM orders");
$total_orders = $total_orders_result ? $total_orders_result->fetch_assoc()['count'] : 0;

$total_users_result = $conn->query("SELECT COUNT(*) as count FROM users WHERE LOWER(role) != 'admin'");
$total_users = $total_users_result ? $total_users_result->fetch_assoc()['count'] : 0;

$pending_orders_result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status='Pending'");
$pending_orders = $pending_orders_result ? $pending_orders_result->fetch_assoc()['count'] : 0;

// Get recent orders
$recent_orders = $conn->query("SELECT o.*, u.name as user_name FROM orders o JOIN users u ON o.user_id = u.user_id ORDER BY o.order_date DESC LIMIT 5");
if(!$recent_orders) {
    $recent_orders = $conn->query("SELECT * FROM orders ORDER BY order_date DESC LIMIT 5");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Canteen Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>🍽️ Admin Panel</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="nav-item active">
                    <span>📊 Dashboard</span>
                </a>
                <a href="admin_items.php" class="nav-item">
                    <span>🍕 Manage Items</span>
                </a>
                <a href="admin_orders.php" class="nav-item">
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
                    <h1>Admin Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Administrator'); ?>!</p>
                </div>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($_SESSION['username'] ?? 'admin'); ?></span>
                </div>
            </header>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🍕</div>
                    <div class="stat-info">
                        <h3><?php echo $total_items; ?></h3>
                        <p>Total Items</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-info">
                        <h3><?php echo $total_orders; ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3><?php echo $total_users; ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <h3><?php echo $pending_orders; ?></h3>
                        <p>Pending Orders</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-section">
                <h2>Recent Orders</h2>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>User</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($recent_orders && $recent_orders->num_rows > 0): ?>
                                <?php while($order = $recent_orders->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $order['order_id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['user_name'] ?? 'Unknown User'); ?></td>
                                        <td><span class="status-badge status-<?php echo strtolower($order['order_status'] ?? 'Pending'); ?>"><?php echo htmlspecialchars($order['order_status'] ?? 'Pending'); ?></span></td>
                                        <td><?php echo $order['order_date'] ? date('M d, Y H:i', strtotime($order['order_date'])) : 'N/A'; ?></td>
                                        <td>
                                            <a href="admin_order_details.php?id=<?php echo $order['order_id']; ?>" class="btn-small">View</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No orders yet. Orders will appear here when users place them.</td>
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
