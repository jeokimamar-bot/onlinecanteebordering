
<?php include 'config.php'; ?>
<h2>Online Canteen Menu</h2>
<?php
$res=$conn->query("SELECT * FROM items WHERE availability_status='Available'");
while($row=$res->fetch_assoc()){
echo "<p>".$row['name']." - ₱".$row['price']."</p>";
}
?>
