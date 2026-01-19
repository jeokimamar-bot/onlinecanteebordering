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

$item_id = intval($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM items WHERE item_id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();
$stmt->close();

if(!$item) {
    header("Location: admin_items.php?error=Item not found");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Item - Admin Panel</title>
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
                <a href="admin_items.php" class="nav-item active">
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
                    <h1>Edit Item</h1>
                    <p>Update item information</p>
                </div>
                <a href="admin_items.php" class="btn-primary" style="text-decoration: none; display: inline-block;">← Back to Items</a>
            </header>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-error" style="margin-bottom: 20px;">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-section">
                <form method="post" action="process_edit_item.php" class="register-form" style="max-width: 600px;">
                    <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                    
                    <div class="form-group">
                        <label>Item Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($item['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Price</label>
                            <input type="number" name="price" step="0.01" value="<?php echo $item['price']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Quantity</label>
                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" required min="0">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select name="availability_status" required>
                            <option value="Available" <?php echo $item['availability_status'] == 'Available' ? 'selected' : ''; ?>>Available</option>
                            <option value="Unavailable" <?php echo $item['availability_status'] == 'Unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                        </select>
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" class="btn-primary">Update Item</button>
                        <a href="admin_items.php" class="btn-primary" style="text-decoration: none; display: inline-block; background: #6b7280; text-align: center;">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
