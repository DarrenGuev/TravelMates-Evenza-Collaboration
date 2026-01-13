<?php
session_start();
require_once '../../core/connect.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Please login to make a reservation.';
    header('Location: ../pages/login.php');
    exit;
}

$success = false;
$error_message = '';
$reservationId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
    $eventId = isset($_POST['eventId']) ? intval($_POST['eventId']) : 0;
    $packageId = isset($_POST['packageId']) ? intval($_POST['packageId']) : 0;
    $packageTier = isset($_POST['packageTier']) ? trim($_POST['packageTier']) : '';
    $reservationDate = isset($_POST['reservationDate']) ? $_POST['reservationDate'] : date('Y-m-d');
    $startTime = isset($_POST['eventStartTime']) ? $_POST['eventStartTime'] : null;
    $endTime = isset($_POST['eventEndTime']) ? $_POST['eventEndTime'] : null;
    
    if ($userId <= 0) {
        $error_message = 'Invalid user session. Please login again.';
    } elseif ($eventId <= 0) {
        $error_message = 'Invalid event selected.';
    } elseif ($packageId <= 0) {
        $error_message = 'Please select a valid package.';
    } else {
        $packageQuery = "SELECT packageId, packageName, price FROM packages WHERE packageId = ?";
        $packageStmt = mysqli_prepare($conn, $packageQuery);
        
        if ($packageStmt) {
            mysqli_stmt_bind_param($packageStmt, "i", $packageId);
            mysqli_stmt_execute($packageStmt);
            $packageResult = mysqli_stmt_get_result($packageStmt);
            $package = mysqli_fetch_assoc($packageResult);
            mysqli_stmt_close($packageStmt);
            
            if ($package) {
                $totalAmount = floatval($package['price']);
                $packageName = $package['packageName'];
                
                $startTimeFormatted = null;
                $endTimeFormatted = null;
                if ($startTime) {
                    $startTimeFormatted = date('H:i:s', strtotime($startTime));
                }
                if ($endTime) {
                    $endTimeFormatted = date('H:i:s', strtotime($endTime));
                }
                
                $insertQuery = "INSERT INTO reservations (userId, eventId, packageId, reservationDate, startTime, endTime, totalAmount, status) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
                
                $insertStmt = mysqli_prepare($conn, $insertQuery);
                
                if ($insertStmt) {
                    mysqli_stmt_bind_param($insertStmt, "iissssd", $userId, $eventId, $packageId, $reservationDate, $startTimeFormatted, $endTimeFormatted, $totalAmount);
                    
                    if (mysqli_stmt_execute($insertStmt)) {
                        $reservationId = mysqli_insert_id($conn);
                        $success = true;
                        
                        mysqli_stmt_close($insertStmt);
                        
                        header('Location: ../pages/reservation.php?eventId=' . $eventId . '&success=1');
                        exit;
                    } else {
                        $error_message = 'Failed to save reservation: ' . mysqli_error($conn);
                        mysqli_stmt_close($insertStmt);
                    }
                } else {
                    $error_message = 'Database error: ' . mysqli_error($conn);
                }
            } else {
                $error_message = 'Package not found in database.';
            }
        } else {
            $error_message = 'Database error: ' . mysqli_error($conn);
        }
    }
    
    if (!empty($error_message)) {
        $_SESSION['error_message'] = $error_message;
        header('Location: ../pages/reservation.php?eventId=' . $eventId);
        exit;
    }
} else {
    $eventId = isset($_GET['eventId']) ? intval($_GET['eventId']) : 0;
    header('Location: ../pages/reservation.php?eventId=' . $eventId);
    exit;
}
?>
