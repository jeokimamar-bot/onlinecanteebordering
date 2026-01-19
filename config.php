
<?php
$conn = new mysqli("localhost","root","","canteendb");
if($conn->connect_error){
    die("Connection failed: ".$conn->connect_error);
}
?>
