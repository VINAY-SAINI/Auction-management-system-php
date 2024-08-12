<?php
$servername = "localHost";
$username = "root";
$password = "";
$dbName = "ams";

// connection
$conn = new mysqli($servername, $username, $password, $dbName);
// check connection
if ($conn->connect_error) {
    $conn_status = "Connection Failed: " . $conn->connect_error;
} else {
    $conn_status = "Connection successful";
}

?>