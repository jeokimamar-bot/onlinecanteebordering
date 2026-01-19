<?php
include 'config.php';

// Create deduction_logs table
$sql = "CREATE TABLE IF NOT EXISTS deduction_logs (
    deduction_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_id INT NOT NULL,
    item_name VARCHAR(100),
    quantity_deducted INT NOT NULL,
    deducted_by INT,
    deduction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE,
    FOREIGN KEY (deducted_by) REFERENCES users(user_id) ON DELETE SET NULL
)";

if($conn->query($sql)) {
    echo "Deduction logs table created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}
?>
