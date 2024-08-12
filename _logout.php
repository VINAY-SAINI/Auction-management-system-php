<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Optionally, clear the JWT cookie if you're using one
if (isset($_SESSION['jwt'])) {
    unset($_SESSION['jwt']); 
    setcookie('jwt', '', time() - 3600, '/'); // empty value and old timestamp
}

// Redirect to the login page
header("Location: _Login.php");
exit();
?>