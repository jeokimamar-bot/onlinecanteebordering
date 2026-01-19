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

// Get all users (excluding current admin)
$users = $conn->query("SELECT * FROM users ORDER BY user_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
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
                <a href="admin_users.php" class="nav-item active">
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
                    <h1>Manage Users</h1>
                    <p>View and manage all users</p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button onclick="document.getElementById('createUserModal').style.display='block'" class="btn-primary" style="background: #3b82f6;">+ Create User</button>
                    <button onclick="document.getElementById('createAdminModal').style.display='block'" class="btn-primary">+ Create Admin</button>
                </div>
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
                <h2>All Users (<?php echo $users->num_rows; ?>)</h2>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($users->num_rows > 0): ?>
                                <?php while($user = $users->fetch_assoc()): ?>
                                    <tr style="<?php echo strtolower($user['role']) == 'admin' ? 'background: #d4edda;' : ''; ?>">
                                        <td><?php echo $user['user_id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($user['name'] ?? 'N/A'); ?></strong></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td>
                                            <span class="status-badge" style="background: <?php 
                                                echo strtolower($user['role']) == 'admin' ? '#10b981' : 
                                                    (strtolower($user['role']) == 'student' ? '#3b82f6' : 
                                                    (strtolower($user['role']) == 'guest' ? '#8b5cf6' : '#f59e0b')); 
                                            ?>; color: white;">
                                                <?php echo htmlspecialchars($user['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                                $is_current_user = ($user['user_id'] == $_SESSION['user_id']);
                                                if($is_current_user): 
                                            ?>
                                                <span style="color: #6b7280; font-size: 0.85rem;">(Current User)</span>
                                            <?php else: ?>
                                                <a href="admin_delete_user.php?id=<?php echo $user['user_id']; ?>" 
                                                   class="btn-small" 
                                                   style="background: #ef4444;" 
                                                   onclick="return confirm('<?php echo strtolower($user['role']) == 'admin' ? 'WARNING: You are about to delete an ADMIN account. Are you sure?' : 'Are you sure you want to delete this user?'; ?>')">
                                                    Delete
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No users found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Create User Modal -->
    <div id="createUserModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
        <div style="background: white; margin: 5% auto; padding: 30px; border-radius: 15px; width: 90%; max-width: 500px;">
            <h2>Create User Account</h2>
            <form method="post" action="process_create_user.php">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required placeholder="Enter user's full name">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" required style="padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 1rem; width: 100%;">
                        <option value="">Select Role</option>
                        <option value="student">Student</option>
                        <option value="guest">Guest</option>
                        <option value="staff">Staff</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required placeholder="Choose a username">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Create a password" minlength="6">
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required placeholder="Confirm password" minlength="6">
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn-primary" style="background: #3b82f6;">Create User</button>
                    <button type="button" class="btn-primary" style="background: #6b7280;" onclick="document.getElementById('createUserModal').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Admin Modal -->
    <div id="createAdminModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
        <div style="background: white; margin: 5% auto; padding: 30px; border-radius: 15px; width: 90%; max-width: 500px;">
            <h2>Create Admin Account</h2>
            <form method="post" action="process_create_admin.php">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required placeholder="Enter administrator full name">
                </div>
                <div class="form-group">
                    <label>Admin Username</label>
                    <input type="text" name="username" required placeholder="Choose an admin username">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Create a secure password" minlength="6">
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required placeholder="Confirm your password" minlength="6">
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn-primary">Create Admin</button>
                    <button type="button" class="btn-primary" style="background: #6b7280;" onclick="document.getElementById('createAdminModal').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Password confirmation validation for Create User Modal
        document.querySelector('#createUserModal form').addEventListener('submit', function(e) {
            const password = document.querySelector('#createUserModal input[name="password"]').value;
            const confirmPassword = document.querySelector('#createUserModal input[name="confirm_password"]').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
        });

        // Password confirmation validation for Create Admin Modal
        document.querySelector('#createAdminModal form').addEventListener('submit', function(e) {
            const password = document.querySelector('#createAdminModal input[name="password"]').value;
            const confirmPassword = document.querySelector('#createAdminModal input[name="confirm_password"]').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
        });
    </script>
</body>
</html>
