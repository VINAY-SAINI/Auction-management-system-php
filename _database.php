
<?php
// This is done so that we can prevent it from Attack : SQL Injection - dangerous attack
$servername = "localhost";
$username = "root";
$password = "";
$dbName = "auction_management_system";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbName;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn_status = "Connection successful";

   
} catch (PDOException $e) {
    $conn_status = "Connection failed: " . $e->getMessage();
}

?>
