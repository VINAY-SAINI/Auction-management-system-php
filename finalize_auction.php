<?php
require '_database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $itemId = intval($_POST['item_id']);
    $bidAmount = floatval($_POST['bid_amount']);
    
    // Retrieve the username from the session
    $username = $_SESSION['jwt_decoded_username'];

    // Fetch the user ID from the database using the username
    $sql = "SELECT ID FROM ams_users WHERE username = :username LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $buyerId = $row['ID'];

        // Insert the highest bid into the ams_bid table
        $sql = "INSERT INTO ams_bid (amount, buyer_id, auction_item_id) VALUES (:amount, :buyer_id, :auction_item_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':amount', $bidAmount);
        $stmt->bindParam(':buyer_id', $buyerId);
        $stmt->bindParam(':auction_item_id', $itemId);

        if ($stmt->execute()) {
            // Update the item status to 3 (Closed)
            $sql_update = "UPDATE ams_auctionitem SET status = 3 WHERE ID = :item_id";
            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->bindParam(':item_id', $itemId);
            if ($stmt_update->execute()) {
                echo "Auction finalized successfully.";
            } else {
                echo "Error: Could not update item status.";
            }
        } else {
            echo "Error: Could not insert bid.";
        }
    } else {
        echo "Error: Could not retrieve user ID.";
    }
}