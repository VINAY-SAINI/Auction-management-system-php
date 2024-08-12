<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'vendor/autoload.php'; 
use \Firebase\JWT\JWT;

require '_database.php'; 

$secretKey = "abcd"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    if (empty($username) || empty($password)) {
        echo "<p style='color:red;'>Username and Password are required.</p>";
    } else {
        // Use the correct table name
        $stmt = $pdo->prepare("SELECT ID, password, role_id FROM ams_users WHERE username = :username AND is_deleted = 0");
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify the password
            if (password_verify($password, $user['password'])) {
                $issuedAt = time();
                $expirationTime = $issuedAt + 3600; 
                $payload = array(
                    'iat' => $issuedAt,
                    'exp' => $expirationTime,
                    'userID' => $user['ID'],
                    'username' => $username,
                    'roleID' => $user['role_id']
                );

                // Encode the payload to create the JWT
                $jwt = JWT::encode($payload, $secretKey, 'HS256');

                // Store the JWT in session
                $_SESSION['jwt'] = $jwt;

                // Redirect to the dashboard
                header("Location: _dashboard.php");
                exit();
            } else {
                echo "<p style='color:red;'>Incorrect password. Please try again.</p>";
            }
        } else {
            echo "<p style='color:red;'>Username does not exist or has been deleted.</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    <form method="POST" action="">
        <label for="username">Username:</label><br>
        <input type="text" id="username" name="username"><br><br>

        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password"><br><br>

        <button type="submit">Login</button>
    </form>
</body>
</html>