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

// Get all items
$items = $conn->query("SELECT * FROM items ORDER BY item_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Items - Admin Panel</title>
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
                    <h1>Manage Items</h1>
                    <p>Add, edit, or remove menu items</p>
                </div>
                <button onclick="document.getElementById('addItemModal').style.display='block'" class="btn-primary">+ Add New Item</button>
            </header>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success" style="margin-bottom: 20px;">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-error" style="margin-bottom: 20px;">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-section">
                <h2>All Items (<?php echo $items->num_rows; ?>)</h2>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($items->num_rows > 0): ?>
                                <?php while($item = $items->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $item['item_id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($item['description'] ?? 'No description'); ?></td>
                                        <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><span class="status-badge status-<?php echo strtolower($item['availability_status']); ?>"><?php echo htmlspecialchars($item['availability_status']); ?></span></td>
                                        <td>
                                            <a href="admin_edit_item.php?id=<?php echo $item['item_id']; ?>" class="btn-small" style="background: #3b82f6;">Edit</a>
                                            <button onclick="openDecreaseModal(<?php echo $item['item_id']; ?>, '<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>', <?php echo $item['quantity']; ?>)" class="btn-small" style="background: #f59e0b;">Decrease Qty</button>
                                            <a href="admin_delete_item.php?id=<?php echo $item['item_id']; ?>" class="btn-small" style="background: #ef4444;" onclick="return confirm('Are you sure you want to delete this item?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">No items found. Add your first item!</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Item Modal -->
    <div id="addItemModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
        <div style="background: white; margin: 5% auto; padding: 30px; border-radius: 15px; width: 90%; max-width: 500px;">
            <h2>Add New Item</h2>
            <form method="post" action="process_add_item.php">
                <div class="form-group">
                    <label>Item Name</label>
                    <input type="text" name="name" required placeholder="e.g., Burger Meal">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Item description"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Price</label>
                        <input type="number" name="price" step="0.01" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" name="quantity" required placeholder="0" min="0">
                    </div>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="availability_status" required>
                        <option value="Available">Available</option>
                        <option value="Unavailable">Unavailable</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn-primary">Add Item</button>
                    <button type="button" class="btn-primary" style="background: #6b7280;" onclick="document.getElementById('addItemModal').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Decrease Quantity Modal -->
    <div id="decreaseQuantityModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
        <div style="background: white; margin: 5% auto; padding: 30px; border-radius: 15px; width: 90%; max-width: 500px;">
            <h2>DEDUCTED</h2>
            <form method="post" action="process_decrease_quantity.php">
                <input type="hidden" id="decrease_item_id" name="item_id" value="">
                <div class="form-group">
                    <label>Item Name</label>
                    <input type="text" id="decrease_item_name" readonly style="background: #f3f4f6;">
                </div>
                <div class="form-group">
                    <label>Current Quantity</label>
                    <input type="number" id="decrease_current_qty" readonly style="background: #f3f4f6;">
                </div>
                <div class="form-group">
                    <label>Quantity to Decrease</label>
                    <input type="number" id="decrease_quantity" name="decrease_quantity" required min="1" placeholder="Enter amount to decrease">
                </div>
                <div class="form-group">
                    <label>Reason (Optional)</label>
                    <textarea name="reason" rows="3" placeholder="Reason for decreasing quantity (e.g., damaged, expired, sold)"></textarea>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn-primary" style="background: #f59e0b;">DEDUCTED</button>
                    <button type="button" class="btn-primary" style="background: #6b7280;" onclick="document.getElementById('decreaseQuantityModal').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openDecreaseModal(itemId, itemName, currentQty) {
            document.getElementById('decrease_item_id').value = itemId;
            document.getElementById('decrease_item_name').value = itemName;
            document.getElementById('decrease_current_qty').value = currentQty;
            document.getElementById('decrease_quantity').max = currentQty;
            document.getElementById('decrease_quantity').value = '';
            document.getElementById('decreaseQuantityModal').style.display = 'block';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('decreaseQuantityModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Validate decrease quantity
        document.querySelector('#decreaseQuantityModal form').addEventListener('submit', function(e) {
            const decreaseQty = parseInt(document.getElementById('decrease_quantity').value);
            const currentQty = parseInt(document.getElementById('decrease_current_qty').value);
            
            if (decreaseQty > currentQty) {
                e.preventDefault();
                alert('Cannot decrease more than current quantity!');
                return false;
            }
            
            if (decreaseQty <= 0) {
                e.preventDefault();
                alert('Quantity to decrease must be greater than 0!');
                return false;
            }
        });
    </script>
</body>
</html>
