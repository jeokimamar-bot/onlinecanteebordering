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
    } else {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['name'] = 'Administrator';
        $_SESSION['role'] = 'admin';
    }
}

// Get filter
$status_filter = $_GET['status'] ?? 'all';

// Build query with LEFT JOIN to handle deleted users
if($status_filter == 'all') {
    $orders_query = "SELECT o.*, u.name as user_name, u.username FROM orders o LEFT JOIN users u ON o.user_id = u.user_id ORDER BY o.order_date DESC";
} else {
    $status_filter_escaped = $conn->real_escape_string($status_filter);
    $orders_query = "SELECT o.*, u.name as user_name, u.username FROM orders o LEFT JOIN users u ON o.user_id = u.user_id WHERE o.order_status = '$status_filter_escaped' ORDER BY o.order_date DESC";
}

$orders = $conn->query($orders_query);

// Get statistics
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$pending = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status='Pending'")->fetch_assoc()['count'];
$processing = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status='Processing'")->fetch_assoc()['count'];
$completed = $conn->query("SELECT COUNT(*) as count FROM orders WHERE order_status='Completed'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin Panel</title>
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
                    <h1>Manage Orders</h1>
                    <p>View and manage all orders</p>
                </div>
            </header>

            <div class="stats-grid" style="margin-bottom: 30px;">
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-info">
                        <h3><?php echo $total_orders; ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <h3><?php echo $pending; ?></h3>
                        <p>Pending</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🔄</div>
                    <div class="stat-info">
                        <h3><?php echo $processing; ?></h3>
                        <p>Processing</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <h3><?php echo $completed; ?></h3>
                        <p>Completed</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>All Orders</h2>
                    <div style="display: flex; gap: 10px;">
                        <a href="admin_orders.php?status=all" class="btn-small" style="<?php echo $status_filter == 'all' ? 'background: #6366f1;' : ''; ?>">All</a>
                        <a href="admin_orders.php?status=Pending" class="btn-small" style="<?php echo $status_filter == 'Pending' ? 'background: #6366f1;' : ''; ?>">Pending</a>
                        <a href="admin_orders.php?status=Processing" class="btn-small" style="<?php echo $status_filter == 'Processing' ? 'background: #6366f1;' : ''; ?>">Processing</a>
                        <a href="admin_orders.php?status=Completed" class="btn-small" style="<?php echo $status_filter == 'Completed' ? 'background: #6366f1;' : ''; ?>">Completed</a>
                    </div>
                </div>
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
                            <?php if($orders && $orders->num_rows > 0): ?>
                                <?php while($order = $orders->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $order['order_id']; ?></td>
                                        <td>
                                            <?php 
                                                $user_name = $order['user_name'] ?? 'Unknown User';
                                                $username = $order['username'] ?? 'N/A';
                                                echo htmlspecialchars($user_name) . ' (' . htmlspecialchars($username) . ')';
                                            ?>
                                        </td>
                                        <td><span class="status-badge status-<?php echo strtolower($order['order_status'] ?? 'Pending'); ?>"><?php echo htmlspecialchars($order['order_status'] ?? 'Pending'); ?></span></td>
                                        <td><?php echo $order['order_date'] ? date('M d, Y H:i', strtotime($order['order_date'])) : 'N/A'; ?></td>
                                        <td>
                                            <a href="admin_order_details.php?id=<?php echo $order['order_id']; ?>" class="btn-small">View</a>
                                            <a href="admin_update_order_status.php?id=<?php echo $order['order_id']; ?>" class="btn-small" style="background: #3b82f6;">Update Status</a>
                                            <a href="admin_delete_order.php?id=<?php echo $order['order_id']; ?>" class="btn-small" style="background: #ef4444;" onclick="return confirm('Are you sure you want to delete this order? This action cannot be undone.')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No orders found</td>
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
