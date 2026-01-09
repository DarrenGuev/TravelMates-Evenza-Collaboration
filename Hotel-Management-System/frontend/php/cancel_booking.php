<?php
session_start();

// Include configuration file
require_once __DIR__ . '/../../config.php';

// Include database connection
require_once DBCONNECT_PATH . '/connect.php';

// Include class autoloader
require_once __DIR__ . '/../../classes/autoload.php';

// Check if user is logged in
Auth::requireLogin('../login.php');

// Initialize models
$bookingModel = new Booking();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingID = (int)$_POST['bookingID'];
    $userID = Auth::getUserId();
    
    // Verify the booking belongs to this user
    $booking = $bookingModel->find($bookingID);
    
    if (!$booking || $booking['userID'] !== $userID) {
        header("Location: ../bookings.php?error=Booking not found");
        exit();
    }
    
    if ($booking['bookingStatus'] !== Booking::STATUS_PENDING) {
        header("Location: ../bookings.php?error=Only pending bookings can be cancelled");
        exit();
    }
    
    // Cancel the booking using Booking model (mark as user-cancelled)
    if ($bookingModel->cancelByUser($bookingID)) {
        header("Location: ../bookings.php?success=Booking cancelled successfully");
    } else {
        header("Location: ../bookings.php?error=Failed to cancel booking. Please try again.");
    }
    exit();
} else {
    header("Location: ../bookings.php");
    exit();
}
?>
