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
    $bookingID = isset($_POST['bookingID']) ? (int)$_POST['bookingID'] : 0;
    $refundReason = isset($_POST['refundReason']) ? trim($_POST['refundReason']) : '';
    $userID = Auth::getUserId();
    
    // Validate booking ID
    if ($bookingID <= 0) {
        header("Location: ../bookings.php?error=Invalid booking ID");
        exit();
    }
    
    // Verify the booking belongs to this user
    $booking = $bookingModel->find($bookingID);
    
    if (!$booking || $booking['userID'] !== $userID) {
        header("Location: ../bookings.php?error=Booking not found");
        exit();
    }
    
    // Check if booking is already cancelled
    if ($booking['bookingStatus'] === Booking::STATUS_CANCELLED) {
        header("Location: ../bookings.php?error=This booking is already cancelled");
        exit();
    }
    
    // Check if booking is completed
    if ($booking['bookingStatus'] === Booking::STATUS_COMPLETED) {
        header("Location: ../bookings.php?error=Completed bookings cannot be cancelled");
        exit();
    }
    
    $isConfirmedBooking = $booking['bookingStatus'] === Booking::STATUS_CONFIRMED;
    $isPaidBooking = $booking['paymentStatus'] === Booking::PAYMENT_PAID;
    
    // For confirmed/paid bookings, validate refund reason
    if ($isConfirmedBooking) {
        if (empty($refundReason)) {
            header("Location: ../bookings.php?error=Please provide a reason for your refund request");
            exit();
        }
        
        if (strlen($refundReason) < 10) {
            header("Location: ../bookings.php?error=Refund reason must be at least 10 characters");
            exit();
        }
        
        if (strlen($refundReason) > 500) {
            header("Location: ../bookings.php?error=Refund reason is too long (maximum 500 characters)");
            exit();
        }
        
        // Sanitize the refund reason
        $refundReason = htmlspecialchars($refundReason, ENT_QUOTES, 'UTF-8');
        
        // Add refund request marker to notes
        $refundNote = "[REFUND_REQUEST] " . date('Y-m-d H:i:s') . " - " . $refundReason;
    }
    
    // Cancel the booking using appropriate method
    if ($bookingModel->cancelByUser($bookingID, $isConfirmedBooking, $refundReason)) {
        // Send appropriate success message
        if ($isConfirmedBooking) {
            // Try to send SMS notification to admin (optional - won't fail if SMS service unavailable)
            try {
                require_once SMS_PATH . '/SmsService.php';
                $smsService = new SmsService();
                
                // Get user details
                $userModel = new User();
                $user = $userModel->find($userID);
                $userName = trim(($user['firstName'] ?? '') . ' ' . ($user['lastName'] ?? ''));
                
                // Log refund request for admin review
                error_log("REFUND REQUEST: Booking #{$bookingID} by {$userName}. Reason: {$refundReason}");
            } catch (Exception $e) {
                // Log error but don't stop the process
                error_log('Notification Error: ' . $e->getMessage());
            }
            
            header("Location: ../bookings.php?success=Refund request submitted successfully. Your booking is now pending admin approval for refund processing");
        } else {
            header("Location: ../bookings.php?success=Booking cancelled successfully");
        }
    } else {
        header("Location: ../bookings.php?error=Failed to cancel booking. Please try again or contact support");
    }
    exit();
} else {
    header("Location: ../bookings.php");
    exit();
}
?>
