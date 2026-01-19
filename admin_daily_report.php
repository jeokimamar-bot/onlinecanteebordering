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
        header("Location: admin_login.php?error=Please login as admin first");
        exit();
    }
}

// Get date filter (default to today)
$report_date = $_GET['date'] ?? date('Y-m-d');
$date_filter = $conn->real_escape_string($report_date);

// Get daily statistics
$daily_orders_query = "SELECT COUNT(*) as count FROM orders WHERE DATE(order_date) = '$date_filter'";
$daily_orders_result = $conn->query($daily_orders_query);
$daily_orders = $daily_orders_result ? $daily_orders_result->fetch_assoc()['count'] : 0;

$daily_revenue_query = "SELECT SUM(i.price * oi.quantity) as total 
                        FROM orders o 
                        JOIN order_items oi ON o.order_id = oi.order_id 
                        JOIN items i ON oi.item_id = i.item_id 
                        WHERE DATE(o.order_date) = '$date_filter' AND o.order_status != 'Cancelled'";
$daily_revenue_result = $conn->query($daily_revenue_query);
$daily_revenue = $daily_revenue_result ? ($daily_revenue_result->fetch_assoc()['total'] ?? 0) : 0;

$completed_orders_query = "SELECT COUNT(*) as count FROM orders WHERE DATE(order_date) = '$date_filter' AND order_status = 'Completed'";
$completed_orders_result = $conn->query($completed_orders_query);
$completed_orders = $completed_orders_result ? $completed_orders_result->fetch_assoc()['count'] : 0;

$pending_orders_query = "SELECT COUNT(*) as count FROM orders WHERE DATE(order_date) = '$date_filter' AND order_status = 'Pending'";
$pending_orders_result = $conn->query($pending_orders_query);
$pending_orders = $pending_orders_result ? $pending_orders_result->fetch_assoc()['count'] : 0;

// Get orders for the day
$orders_query = "SELECT o.*, u.name as user_name, u.username 
                 FROM orders o 
                 LEFT JOIN users u ON o.user_id = u.user_id 
                 WHERE DATE(o.order_date) = '$date_filter' 
                 ORDER BY o.order_date DESC";
$orders = $conn->query($orders_query);

// Get top selling items for the day
$top_items_query = "SELECT i.name, SUM(oi.quantity) as total_quantity, SUM(i.price * oi.quantity) as total_revenue
                    FROM order_items oi
                    JOIN items i ON oi.item_id = i.item_id
                    JOIN orders o ON oi.order_id = o.order_id
                    WHERE DATE(o.order_date) = '$date_filter' AND o.order_status != 'Cancelled'
                    GROUP BY i.item_id, i.name
                    ORDER BY total_quantity DESC
                    LIMIT 10";
$top_items = $conn->query($top_items_query);

// Get deduction logs for the day (check if table exists first)
$deduction_logs = null;
$check_deduction_table = $conn->query("SHOW TABLES LIKE 'deduction_logs'");
if($check_deduction_table->num_rows > 0) {
    // Check and add missing columns if needed
    $columns_check = $conn->query("SHOW COLUMNS FROM deduction_logs");
    $existing_columns = [];
    while($col = $columns_check->fetch_assoc()) {
        $existing_columns[] = $col['Field'];
    }
    
    // Add order_id column if it doesn't exist (must be added first)
    if(!in_array('order_id', $existing_columns)) {
        $conn->query("ALTER TABLE deduction_logs ADD COLUMN order_id INT DEFAULT NULL AFTER deduction_id");
        $existing_columns[] = 'order_id';
    }
    
    // Add deducted_by column if it doesn't exist
    if(!in_array('deducted_by', $existing_columns)) {
        $conn->query("ALTER TABLE deduction_logs ADD COLUMN deducted_by INT AFTER quantity_deducted");
        $existing_columns[] = 'deducted_by';
    }
    
    // Add reason column if it doesn't exist
    if(!in_array('reason', $existing_columns)) {
        $conn->query("ALTER TABLE deduction_logs ADD COLUMN reason TEXT AFTER deducted_by");
        $existing_columns[] = 'reason';
    }
    
    // Add deduction_date column if it doesn't exist
    if(!in_array('deduction_date', $existing_columns)) {
        $conn->query("ALTER TABLE deduction_logs ADD COLUMN deduction_date DATETIME DEFAULT CURRENT_TIMESTAMP");
        // Update existing rows to have current timestamp
        $conn->query("UPDATE deduction_logs SET deduction_date = NOW() WHERE deduction_date IS NULL");
        $existing_columns[] = 'deduction_date';
    }
    
    // Only query if deduction_date column exists
    if(in_array('deduction_date', $existing_columns)) {
        $deductions_query = "SELECT dl.*, u.name as admin_name, u.username as admin_username
                            FROM deduction_logs dl
                            LEFT JOIN users u ON dl.deducted_by = u.user_id
                            WHERE DATE(dl.deduction_date) = '$date_filter'
                            ORDER BY dl.deduction_date DESC";
        $deduction_logs = $conn->query($deductions_query);
    } else {
        // Fallback: query without date filter if deduction_date doesn't exist
        $deductions_query = "SELECT dl.*, u.name as admin_name, u.username as admin_username
                            FROM deduction_logs dl
                            LEFT JOIN users u ON dl.deducted_by = u.user_id
                            ORDER BY dl.deduction_id DESC";
        $deduction_logs = $conn->query($deductions_query);
    }
}

// Get total quantities deducted for the day
$total_deducted = 0;
$deducted_orders_count = 0;
if($deduction_logs && $deduction_logs->num_rows > 0) {
    $deduction_logs->data_seek(0); // Reset pointer
    $deducted_order_ids = [];
    while($log = $deduction_logs->fetch_assoc()) {
        $total_deducted += $log['quantity_deducted'] ?? 0;
        $order_id = $log['order_id'] ?? null;
        if($order_id !== null && !in_array($order_id, $deducted_order_ids)) {
            $deducted_order_ids[] = $order_id;
        }
    }
    $deducted_orders_count = count($deducted_order_ids);
    $deduction_logs->data_seek(0); // Reset again for display
}

// Alternative query to get deducted orders count (more efficient)
$check_deduction_table2 = $conn->query("SHOW TABLES LIKE 'deduction_logs'");
if($check_deduction_table2->num_rows > 0) {
    // Check if order_id column exists before querying
    $check_order_id_col = $conn->query("SHOW COLUMNS FROM deduction_logs LIKE 'order_id'");
    if($check_order_id_col->num_rows > 0) {
        $deducted_orders_query = "SELECT COUNT(DISTINCT order_id) as count 
                                  FROM deduction_logs 
                                  WHERE DATE(deduction_date) = '$date_filter' AND order_id IS NOT NULL";
        $deducted_orders_result = $conn->query($deducted_orders_query);
        if($deducted_orders_result) {
            $deducted_orders_count = $deducted_orders_result->fetch_assoc()['count'] ?? 0;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Report - Admin Panel</title>
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
                <a href="admin_orders.php" class="nav-item">
                    <span>Orders</span>
                </a>
                <a href="admin_users.php" class="nav-item">
                    <span>👥 Users</span>
                </a>
                <a href="admin_daily_report.php" class="nav-item active">
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
                    <h1>Daily Report</h1>
                    <p>View daily sales and order statistics</p>
                </div>
                <form method="get" action="admin_daily_report.php" style="display: flex; gap: 10px; align-items: center;">
                    <input type="date" name="date" value="<?php echo htmlspecialchars($report_date); ?>" style="padding: 10px; border: 2px solid var(--border-color); border-radius: 6px;">
                    <button type="submit" class="btn-primary">View Report</button>
                </form>
            </header>

            <div class="stats-grid" style="margin-bottom: 30px;">
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-info">
                        <h3><?php echo $daily_orders; ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <h3>₱<?php echo number_format($daily_revenue, 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <h3><?php echo $completed_orders; ?></h3>
                        <p>Completed Orders</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <h3><?php echo $pending_orders; ?></h3>
                        <p>Pending Orders</p>
                    </div>
                </div>
                <?php if($total_deducted > 0): ?>
                <div class="stat-card">
                    <div class="stat-icon">📉</div>
                    <div class="stat-info">
                        <h3><?php echo $total_deducted; ?></h3>
                        <p>Items Deducted</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="dashboard-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>Orders for <?php echo date('F d, Y', strtotime($report_date)); ?></h2>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <span style="color: #6b7280; font-size: 0.9rem;">Total Orders: <strong><?php echo $daily_orders; ?></strong></span>
                        <span style="color: #6b7280; font-size: 0.9rem;">|</span>
                        <span style="color: #6b7280; font-size: 0.9rem;">Deducted Orders: <strong style="color: #10b981;"><?php echo $deducted_orders_count; ?></strong></span>
                    </div>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>User</th>
                                <th>Status</th>
                                <th>Time</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($orders && $orders->num_rows > 0): ?>
                                <?php while($order = $orders->fetch_assoc()): 
                                    // Calculate order total
                                    $order_items_query = "SELECT SUM(i.price * oi.quantity) as total 
                                                          FROM order_items oi 
                                                          JOIN items i ON oi.item_id = i.item_id 
                                                          WHERE oi.order_id = " . $order['order_id'];
                                    $order_total_result = $conn->query($order_items_query);
                                    $order_total = $order_total_result ? ($order_total_result->fetch_assoc()['total'] ?? 0) : 0;
                                ?>
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
                                        <td><?php echo $order['order_date'] ? date('H:i', strtotime($order['order_date'])) : 'N/A'; ?></td>
                                        <td>₱<?php echo number_format($order_total, 2); ?></td>
                                        <td>
                                            <a href="admin_order_details.php?id=<?php echo $order['order_id']; ?>" class="btn-small">View</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No orders found for this date</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="dashboard-section">
                <h2>Top Selling Items for <?php echo date('F d, Y', strtotime($report_date)); ?></h2>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Quantity Sold</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($top_items && $top_items->num_rows > 0): ?>
                                <?php while($item = $top_items->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                                        <td><?php echo $item['total_quantity']; ?></td>
                                        <td>₱<?php echo number_format($item['total_revenue'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center">No items sold on this date</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if($deduction_logs && $deduction_logs->num_rows > 0): ?>
            <div class="dashboard-section">
                <h2>Inventory Deductions for <?php echo date('F d, Y', strtotime($report_date)); ?></h2>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Item Name</th>
                                <th>Quantity Deducted</th>
                                <th>Reason</th>
                                <th>Deducted By</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($log = $deduction_logs->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo (isset($log['order_id']) && $log['order_id'] !== null) ? '#' . $log['order_id'] : '<span style="color: #6b7280; font-style: italic;">Manual</span>'; ?></td>
                                    <td><strong><?php echo htmlspecialchars($log['item_name'] ?? 'N/A'); ?></strong></td>
                                    <td><?php echo $log['quantity_deducted'] ?? 0; ?></td>
                                    <td><?php echo (isset($log['reason']) && $log['reason']) ? htmlspecialchars($log['reason']) : '<span style="color: #6b7280;">-</span>'; ?></td>
                                    <td><?php echo htmlspecialchars($log['admin_name'] ?? 'System'); ?> (<?php echo htmlspecialchars($log['admin_username'] ?? 'N/A'); ?>)</td>
                                    <td><?php echo (isset($log['deduction_date']) && $log['deduction_date']) ? date('H:i', strtotime($log['deduction_date'])) : 'N/A'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
