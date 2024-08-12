<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include("_database.php");
require 'vendor/autoload.php'; 
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

// Check if the session JWT is set
if (!isset($_SESSION['jwt'])) {
    echo "Session expired or not logged in. Please log in again.";
    header("Location: _login.php");
    exit;
}

$secretKey = 'abcd'; 
$jwt = $_SESSION['jwt'];

try {
    $decoded = JWT::decode($jwt, new Key($secretKey, 'HS256'));  
    $loggedInUsername = $decoded->username;
    $loggedInRoleID = $decoded->roleID;
    $_SESSION['jwt_decoded_username'] = $loggedInUsername;
} catch (\Firebase\JWT\ExpiredException $e) {
    header("Location: _login.php?message=Session expired. Please log in again.");
    exit;
} catch (Exception $e) {
    echo "Access denied. Error: " . $e->getMessage();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Create User
    if (isset($_POST['createUser']) && ($loggedInRoleID == 1 || $loggedInRoleID == 2)) {
        $newUsername = $_POST['userUsername'];
        $newPassword = password_hash($_POST['userPassword'], PASSWORD_BCRYPT); // Hashing the password for security
        $newEmail = $_POST['userEmail'];
        $newRoleID = $_POST['userRoleID'];

        createUser($newUsername, $newPassword, $newEmail, $newRoleID);
    } elseif (isset($_POST['createUser'])) {
        echo "Access denied: You do not have permission to create users.";
    }

    // Create Manager
    if (isset($_POST['createManager']) && $loggedInRoleID == 1) {
        $newManagerUsername = $_POST['managerUsername'];
        $newManagerPassword = password_hash($_POST['managerPassword'], PASSWORD_BCRYPT); // Hashing the password for security
        $newManagerEmail = $_POST['managerEmail'];
        $newManagerRoleID = $_POST['roleID'];

        createUser($newManagerUsername, $newManagerPassword, $newManagerEmail, $newManagerRoleID);
    } elseif (isset($_POST['createManager'])) {
        echo "Access denied: You do not have permission to create managers.";
    }

    // Create Item
    if (isset($_POST['itemName'], $_POST['itemDescription'], $_POST['minPrice'], $_POST['maxPrice']) && $loggedInRoleID == 1) {
        $newItemName = $_POST['itemName'];
        $newItemDescription = $_POST['itemDescription'];
        $newItemMinPrice = $_POST['minPrice'];
        $newItemMaxPrice = $_POST['maxPrice'];

        createAuctionItem($newItemName, $newItemDescription, $newItemMinPrice, $newItemMaxPrice);
    } elseif (isset($_POST['itemName'])) {
        echo "Access denied: You do not have permission to create items.";
    }

    // Delete Item
    if (isset($_POST['delete']) && ($loggedInRoleID == 1 || $loggedInRoleID == 2)) {
        $itemIdToDelete = $_POST['delete_id'];
        deleteAuctionItem($itemIdToDelete);
    } elseif (isset($_POST['delete'])) {
        echo "Access denied: You do not have permission to delete items.";
    }
    
    if (isset($_POST['start_auction'])) {
        $auctionId = intval($_POST['auction_id']);
        
        // Call the startAuction function
        if (startAuction($auctionId)) {
            echo "Auction started successfully.";
            header("Location: _dashboard.php?message=Auction started successfully");
            exit;
        } else {
            echo "Error: Could not start auction.";
        }
    }
}

function startAuction($auctionId) {
    global $pdo;

    $sql_update = "UPDATE ams_auctionitem SET status = 2 WHERE ID = :auction_id AND is_deleted = 0";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->bindParam(':auction_id', $auctionId, PDO::PARAM_INT);

    if ($stmt_update->execute()) {
        return true;  // Auction started successfully
    } else {
        return false; // Failed to start the auction
    }
}
function createUser($newUsername, $newPassword, $newEmail, $newRoleID) {
    global $pdo;
    global $loggedInUsername;

    $createdBy = getUserIDByUsername($loggedInUsername);
    
    if (getUserIDByUsername($newUsername) !== null) {
        echo "Error: Username already exists.";
        return;
    }

    $sql = "INSERT INTO ams_users (username, password, email, role_id, created_by, is_deleted) 
            VALUES (:username, :password, :email, :role_id, :created_by, 0)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $newUsername);
    $stmt->bindParam(':password', $newPassword);
    $stmt->bindParam(':email', $newEmail);
    $stmt->bindParam(':role_id', $newRoleID);
    $stmt->bindParam(':created_by', $createdBy);

    if ($stmt->execute()) {
        echo "User created successfully.";
    } else {
        echo "Error: " . $stmt->errorInfo()[2];
    }
}

function createAuctionItem($itemName, $itemDescription, $minPrice, $maxPrice) {
    global $pdo;
    global $loggedInUsername;

    $createdBy = getUserIDByUsername($loggedInUsername);

    $sql_user_check = "SELECT ID FROM ams_users WHERE ID = :created_by";
    $stmt_user_check = $pdo->prepare($sql_user_check);
    $stmt_user_check->bindParam(':created_by', $createdBy, PDO::PARAM_INT);
    $stmt_user_check->execute();

    if ($stmt_user_check->rowCount() == 0) {
        echo "Error: The user with ID $createdBy does not exist.";
        return;
    }

    $addedTime = date('Y-m-d H:i:s');
    $updatedTime = date('Y-m-d H:i:s');
    $status = 1;

    $sql_check = "SELECT id FROM ams_auctionitem WHERE name = :name LIMIT 1";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->bindParam(':name', $itemName);
    $stmt_check->execute();

    if ($stmt_check->rowCount() > 0) {
        $row = $stmt_check->fetch(PDO::FETCH_ASSOC);
        $itemId = $row['id'];
        $sql_update = "UPDATE ams_auctionitem SET is_deleted = 0, description = :description, min_price = :min_price, max_price = :max_price, updated_time = :updated_time, status = :status WHERE id = :item_id";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->bindParam(':description', $itemDescription);
        $stmt_update->bindParam(':min_price', $minPrice);
        $stmt_update->bindParam(':max_price', $maxPrice);
        $stmt_update->bindParam(':updated_time', $updatedTime);
        $stmt_update->bindParam(':status', $status);
        $stmt_update->bindParam(':item_id', $itemId);

        if ($stmt_update->execute()) {
            echo "Item updated successfully.";
            header("Location: _dashboard.php?message=Item updated successfully");
            exit;
        } else {
            echo "Error updating item: " . $stmt_update->errorInfo()[2];
        }
    } else {
        $sql_insert = "INSERT INTO ams_auctionitem (name, description, min_price, max_price, created_by, is_deleted, added_time, updated_time, status)
                        VALUES (:name, :description, :min_price, :max_price, :created_by, 0, :added_time, :updated_time, :status)";
        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->bindParam(':name', $itemName);
        $stmt_insert->bindParam(':description', $itemDescription);
        $stmt_insert->bindParam(':min_price', $minPrice);
        $stmt_insert->bindParam(':max_price', $maxPrice);
        $stmt_insert->bindParam(':created_by', $createdBy);
        $stmt_insert->bindParam(':added_time', $addedTime);
        $stmt_insert->bindParam(':updated_time', $updatedTime);
        $stmt_insert->bindParam(':status', $status);

        if ($stmt_insert->execute()) {
            echo "Item inserted successfully.";
            header("Location: _dashboard.php?message=Item created successfully");
            exit;
        } else {
            echo "Error inserting item: " . $stmt_insert->errorInfo()[2];
        }
    }
}

function getUserIDByUsername($username) {
    global $pdo;

    $sql = "SELECT ID FROM ams_users WHERE username = :username LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['ID'];
    } else {
        return null;
    }
}
function AddItem() {
    global $pdo;   
    $sql = "SELECT * FROM ams_auctionitem WHERE status=1 AND is_deleted=0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['description']) . "</td>";
            echo "<td>$" . htmlspecialchars($row['min_price']) . "</td>";
            echo "<td>$" . htmlspecialchars($row['max_price']) . "</td>";
            echo "<td class='actions-column'>";
            if ($GLOBALS['loggedInRoleID'] == 1 || $GLOBALS['loggedInRoleID'] == 2) {
                echo "<form method='POST' action='#' class='d-inline'>";
                echo "<input type='hidden' name='delete_id' value='" . $row['ID'] . "'>";
                echo "<button type='submit' name='delete' class='btn btn-danger btn-sm'>Delete</button>";
                echo "</form>";
            }
            echo "<form method='POST' action='#' class='d-inline'>";
            echo "<input type='hidden' name='auction_id' value='" . $row['ID'] . "'>";
            echo "<input type='hidden' name='auction_id' value='".$row['ID']. "'>";
            echo "<button type='submit'  name='start_auction' class='btn btn-success btn-sm'>Auction</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='5'>No items found.</td></tr>";
    }
}

function deleteAuctionItem($itemId) {
    global $pdo;
    $sql = "UPDATE ams_auctionitem SET is_deleted = 1 WHERE id = :item_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':item_id', $itemId);

    if ($stmt->execute()) {
        echo "Item deleted successfully.";
    } else {
        echo "Error: " . $stmt->errorInfo()[2];
    }
}

function TotalUser() {
    global $pdo;
    $sql = "SELECT COUNT(*) as user_count FROM ams_users WHERE role_id = 3";
    $stmt = $pdo->prepare($sql);

    if ($stmt && $stmt->execute()) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_count = $row['user_count'];
        echo "$user_count";
    } else {
        echo "Error: " . ($stmt ? implode(", ", $stmt->errorInfo()) : $pdo->errorInfo()[2]);
    }
}

function TotalManager() {
    global $pdo;
    $sql = "SELECT COUNT(*) as user_count FROM ams_users WHERE role_id = 2";
    $stmt = $pdo->prepare($sql);

    if ($stmt && $stmt->execute()) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_count = $row['user_count'];
        echo "$user_count";
    } else {
        echo "Error: " . ($stmt ? implode(", ", $stmt->errorInfo()) : $pdo->errorInfo()[2]);
    }
}

function TotalItem() {
    global $pdo;
    $sql = "SELECT COUNT(*) as item_count FROM ams_auctionitem WHERE status=2 AND is_deleted=0";
    $stmt = $pdo->prepare($sql);

    if ($stmt && $stmt->execute()) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $item_count = $row['item_count'];
        echo "$item_count";
    } else {
        echo "Error: " . ($stmt ? implode(", ", $stmt->errorInfo()) : $pdo->errorInfo()[2]);
    }
}

function TotalBid() {
    global $pdo;
    $sql = "SELECT COUNT(*) as item_count FROM ams_bid";
    $stmt = $pdo->prepare($sql);

    if ($stmt && $stmt->execute()) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $item_count = $row['item_count'];
        echo "$item_count";
    } else {
        echo "Error: " . ($stmt ? implode(", ", $stmt->errorInfo()) : $pdo->errorInfo()[2]);
    }
}

function getTotalBidAmount() {
    global $pdo;
    $sql = "SELECT IFNULL(SUM(amount), 0) as total_amount FROM ams_bid";
    $stmt = $pdo->prepare($sql);

    if ($stmt && $stmt->execute()) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_amount = $row['total_amount'];
        echo $total_amount;
    } else {
        echo "Error: " . ($stmt ? implode(", ", $stmt->errorInfo()) : $pdo->errorInfo()[2]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auction Management Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="Dashboard.css">
</head>

<body>
    <!-- Main Content -->
    <div id="main-content" class="container">
        <!-- Header -->
        <div id="header" class="d-flex justify-content-between align-items-center">
            <a href="#" class="navbar-brand">Auction Management System</a>
            <div>
                <button class="btn btn-light" id="userProfileButton">User Profile</button>
                <button class="btn btn-light" onclick="window.location.href='logout.php';">Logout</button>
            </div>
        </div>

        <!-- Profile Modal -->
        <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="profileModalLabel">User Profile</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($loggedInUsername); ?></p>
                        <p><strong>Role ID:</strong> <?php echo htmlspecialchars($loggedInRoleID); ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Overview -->
        <section id="overview" class="mt-4">
            <h2>Dashboard Overview</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="card text-white bg-primary mb-3 animate-card">
                        <div class="card-body">
                            <h5 class="card-title">Active Manager</h5>
                            <p class="card-text"><?php TotalManager(); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success mb-3 animate-card">
                        <div class="card-body">
                            <h5 class="card-title">Revenue</h5>
                            <p class="card-text"><?php getTotalBidAmount(); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-info mb-3 animate-card">
                        <div class="card-body">
                            <h5 class="card-title">Total Bids</h5>
                            <p class="card-text"><?php TotalBid(); ?> </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-warning mb-3 animate-card">
                        <div class="card-body">
                            <h5 class="card-title">Total Items</h5>
                            <p class="card-text"><?php TotalItem(); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-danger mb-3 animate-card">
                        <div class="card-body">
                            <h5 class="card-title">Total Users</h5>
                            <p class="card-text"><?php TotalUser(); ?> </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Create Item -->
        <?php if ($loggedInRoleID == 1) { ?>
        <section id="create-item" class="mt-4">
            <h2>Create Item</h2>
            <div class="card animate-form">
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="itemName">Item Name</label>
                            <input type="text" class="form-control" id="itemName" name="itemName" placeholder="Enter item name" required>
                        </div>
                        <div class="form-group">
                            <label for="itemDescription">Description</label>
                            <textarea class="form-control" id="itemDescription" name="itemDescription" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="minPrice">Min Price</label>
                            <input type="number" class="form-control" id="minPrice" name="minPrice" placeholder="Enter minimum price" required>
                        </div>
                        <div class="form-group">
                            <label for="maxPrice">Max Price</label>
                            <input type="number" class="form-control" id="maxPrice" name="maxPrice" placeholder="Enter maximum price" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Create</button>
                        </form>
                    </div>
                </div>
            </section>
            <?php } ?>
    
            <!-- Add Item -->
            <?php if ($loggedInRoleID == 1 || $loggedInRoleID == 2) { ?>
            <section id="add-item" class="mt-4">
                <h2>Added Item</h2>
                <div class="card animate-form">
                    <div class="card-body">
                        <h5 class="card-title">Item List</h5>
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Min Price</th>
                                    <th>Max Price</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Items will be listed here dynamically -->
                                <?php AddItem(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            <?php } ?>
    
            
         <!-- Auction -->
<section id="auction" class="mt-4">
    <h2>Auction</h2>
    <div class="card animate-form">
        <div class="card-body">
            <h5 class="card-title">Active Auctions</h5>
            <div id="auction-items">
                <?php
                global $pdo;
                $sql = "SELECT * FROM ams_auctionitem WHERE status = 2 AND is_deleted = 0";
                $stmt = $pdo->prepare($sql);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<div class='auction-item'>";
                        echo "<h5>" . htmlspecialchars($row['name']) . "</h5>";
                        echo "<p>" . htmlspecialchars($row['description']) . "</p>";
                        echo "<p>Starting Price: $" . htmlspecialchars($row['min_price']) . "</p>";
                        echo "<p>Maximum Price: $" . htmlspecialchars($row['max_price']) . "</p>";
                        echo "<div id='bid-area-" . $row['ID'] . "'>";

                        if ($loggedInRoleID == 3) {  // Only allow buyers (role_id = 3) to place bids
                            $bidIncrement = ($row['max_price'] - $row['min_price']) / 5;
                            for ($i = 1; $i <= 5; $i++) {
                                $bidAmount = $row['min_price'] + ($bidIncrement * $i);
                                echo "<button class='btn btn-primary bid-btn' data-item-id='" . $row['ID'] . "' data-bid-amount='" . round($bidAmount, 2) . "'>Bid $" . round($bidAmount, 2) . "</button> ";
                            }
                        }
                        echo "</div>";
                        echo "<div id='timer-" . $row['ID'] . "'></div>";  // Timer display
                        echo "</div><hr>";
                    }
                } else {
                    echo "<p>No items available for auction.</p>";
                }
                ?>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let auctionItems = document.querySelectorAll('.auction-item');
    
    auctionItems.forEach(function(item) {
        let timerElement = item.querySelector('div[id^="timer-"]');
        let bidButtons = item.querySelectorAll('.bid-btn');
        let itemId = bidButtons[0].getAttribute('data-item-id');
        let highestBid = parseFloat(bidButtons[0].getAttribute('data-bid-amount'));
        let interval;

        function startAuctionTimer() {
            let timeLeft = 30;
            interval = setInterval(function() {
                if (timeLeft <= 0) {
                    clearInterval(interval);
                    finalizeAuction(itemId, highestBid);
                } else {
                    timerElement.textContent = timeLeft;
                    timeLeft--;
                }
            }, 1000);
        }

        function finalizeAuction(itemId, highestBid) {
            alert("Auction ended for item " + itemId + ". Winning bid: $" + highestBid);
            // Update the bid table and set item status to 3 via AJAX
            let xhr = new XMLHttpRequest();
            xhr.open("POST", "finalize_auction.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
                    alert("Auction finalized successfully.");
                    window.location.reload();
                }
            };
            xhr.send("item_id=" + itemId + "&bid_amount=" + highestBid);
        }

        bidButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                let bidAmount = parseFloat(button.getAttribute('data-bid-amount'));
                if (bidAmount > highestBid) {
                    highestBid = bidAmount;
                    alert("Your bid of $" + highestBid + " is now the highest bid!");
                } else {
                    alert("Your bid must be higher than the current highest bid.");
                }
            });
        });

        startAuctionTimer();
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let auctionItems = document.querySelectorAll('.auction-item');
    
    auctionItems.forEach(function(item) {
        let timerElement = item.querySelector('div[id^="timer-"]');
        let bidButtons = item.querySelectorAll('.bid-btn');

        if (bidButtons.length > 0) {
            let itemId = bidButtons[0].getAttribute('data-item-id');
            let highestBid = parseFloat(bidButtons[0].getAttribute('data-bid-amount'));
            let interval;

            function startAuctionTimer() {
                let timeLeft = 30;
                interval = setInterval(function() {
                    if (timeLeft <= 0) {
                        clearInterval(interval);
                        finalizeAuction(itemId, highestBid);
                    } else {
                        timerElement.textContent = timeLeft;
                        timeLeft--;
                    }
                }, 1000);
            }

            function finalizeAuction(itemId, highestBid) {
                alert("Auction ended for item " + itemId + ". Winning bid: $" + highestBid);
                // Update the bid table and set item status to 3 via AJAX
                let xhr = new XMLHttpRequest();
                xhr.open("POST", "finalize_auction.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
                        alert(xhr.responseText); // Display the response from the server
                        window.location.reload();
                    }
                };
                xhr.send("item_id=" + itemId + "&bid_amount=" + highestBid);
            }

            bidButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    let bidAmount = parseFloat(button.getAttribute('data-bid-amount'));
                    if (bidAmount > highestBid) {
                        highestBid = bidAmount;
                        alert("Your bid of $" + highestBid + " is now the highest bid!");
                    } else {
                        alert("Your bid must be higher than the current highest bid.");
                    }
                });
            });

            startAuctionTimer();
        } else {
            console.warn("No bid buttons found for item: ", item);
        }
    });
});
</script>
    
            <!-- User Management -->
            <?php if ($loggedInRoleID == 1 || $loggedInRoleID == 2) { ?>
            <section id="user-management" class="mt-4">
                <h2>User Management</h2>
                <div class="card animate-form">
                    <div class="card-body">
                        <h5 class="card-title">Create User</h5>
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="userUsername">Username</label>
                                <input type="text" class="form-control" id="userUsername" name="userUsername" placeholder="Enter username" required>
                            </div>
                            <div class="form-group">
                                <label for="userPassword">Password</label>
                                <input type="password" class="form-control" id="userPassword" name="userPassword" placeholder="Enter password" required>
                            </div>
                            <div class="form-group">
                                <label for="userEmail">Email</label>
                                <input type="email" class="form-control" id="userEmail" name="userEmail" placeholder="Enter email" required>
                            </div>
                            <div class="form-group">
                                <input type="hidden" class="form-control" id="userRoleID" name="userRoleID" value="3" readonly>
                            </div>
                            <button type="submit" name="createUser" class="btn btn-primary">Create User</button>
                        </form>
                    </div>
                </div>
            </section>
            <?php } ?>
    
            <!-- Manager Control -->
            <?php if ($loggedInRoleID == 1) { ?>
            <section id="manager-control" class="mt-4">
                <h2>Manager Control</h2>
                <div class="card animate-form">
                    <div class="card-body">
                        <h5 class="card-title">Create Manager</h5>
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="managerUsername">Username</label>
                                <input type="text" class="form-control" id="managerUsername" name="managerUsername" placeholder="Enter username" required>
                            </div>
                            <div class="form-group">
                                <label for="managerPassword">Password</label>
                                <input type="password" class="form-control" id="managerPassword" name="managerPassword" placeholder="Enter password" required>
                            </div>
                            <div class="form-group">
                                <label for="managerEmail">Email</label>
                                <input type="email" class="form-control" id="managerEmail" name="managerEmail" placeholder="Enter email" required>
                            </div>
                            <div class="form-group">
                                <input type="hidden" class="form-control" id="roleID" name="roleID" value="2" readonly>
                            </div>
                            <button type="submit" name="createManager" class="btn btn-primary">Create Manager</button>
                        </form>
                    </div>
                </div>
            </section>
            <?php } ?>
    
            <div class="footer">
                <p>&copy; 2024 Auction Management System. All rights reserved.</p>
                <a href="#" class="text-white">Terms & Conditions</a> |
                <a href="#" class="text-white">Privacy Policy</a> |
                <a href="#" class="text-white">Contact Us</a> |
                <a href="#" class="text-white">Help & Support</a>
            </div>
    
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <script>
                document.getElementById('userProfileButton').addEventListener('click', function () {
                    var myModal = new bootstrap.Modal(document.getElementById('profileModal'), {
                        keyboard: true
                    });
                    myModal.show();
                });
            </script>
        </div>
    </body>
    </html>