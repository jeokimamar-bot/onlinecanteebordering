
CREATE DATABASE canteendb;
USE canteendb;

CREATE TABLE users (
 user_id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100),
 role ENUM('student','guest','staff','admin'),
 username VARCHAR(50),
 password VARCHAR(255)
);

CREATE TABLE items (
 item_id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100),
 description TEXT,
 quantity INT,
 price DECIMAL(10,2),
 availability_status ENUM('Available','Unavailable')
);

CREATE TABLE orders (
 order_id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT,
 order_status VARCHAR(20),
 order_date DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE order_items (
 order_item_id INT AUTO_INCREMENT PRIMARY KEY,
 order_id INT,
 item_id INT,
 quantity INT
);

CREATE TABLE deduction_logs (
 deduction_id INT AUTO_INCREMENT PRIMARY KEY,
 order_id INT DEFAULT NULL,
 item_id INT NOT NULL,
 item_name VARCHAR(100),
 quantity_deducted INT NOT NULL,
 deducted_by INT,
 reason TEXT,
 deduction_date DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE notifications (
 notification_id INT AUTO_INCREMENT PRIMARY KEY,
 user_id INT NOT NULL,
 order_id INT,
 message TEXT NOT NULL,
 is_read TINYINT(1) DEFAULT 0,
 created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- To create the default admin account, run create_admin.php in your browser
-- Admin credentials:
-- Username: admin12345
-- Password: admin123
