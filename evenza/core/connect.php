<?php
$dbhost = "localhost";
$dbuser = "root";
$dbpass = "";
$dbname = "evenza";

$conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

if (!$conn) {
    $errorCode = mysqli_connect_errno();
    $errorMessage = mysqli_connect_error();
    
    
    if ($errorCode == 1045) {
        
        die("Database Connection Error: Invalid username or password. Please check your database credentials in connect.php");
    } elseif ($errorCode == 1049) {
        
        die("Database Connection Error: Database '$dbname' does not exist. Please check if the database name is correct in connect.php");
    } elseif ($errorCode == 2002 || strpos($errorMessage, 'Connection refused') !== false || strpos($errorMessage, 'Unknown host') !== false) {
        die("Database Connection Error: Cannot connect to database host '$dbhost'. Please check if MySQL server is running and the host is correct in connect.php");
    } else {
        die("Database Connection Error: " . $errorMessage . " (Error Code: $errorCode)");
    }
}

mysqli_set_charset($conn, "utf8mb4");

function executeQuery($query)
{
    $conn = $GLOBALS['conn'];
    return mysqli_query($conn, $query);
}