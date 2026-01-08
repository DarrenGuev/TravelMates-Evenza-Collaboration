<?php
session_start();

// Include configuration file
require_once __DIR__ . '/../../config.php';

// Include database connection
require_once DBCONNECT_PATH . '/connect.php';

// Include class autoloader
require_once CLASSES_PATH . '/autoload.php';

Auth::requireAdmin('../../frontend/login.php');

$bookingModel = new Booking();
$userModel = new User();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingID = (int)$_POST['bookingID'];
    $bookingStatus = $_POST['bookingStatus'];
    $paymentStatus = $_POST['paymentStatus'];
    $notes = $_POST['notes'] ?? '';

    $validBookingStatuses = [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED, Booking::STATUS_CANCELLED, Booking::STATUS_COMPLETED];
    $validPaymentStatuses = [Booking::PAYMENT_PENDING, Booking::PAYMENT_PAID, Booking::PAYMENT_REFUNDED];

    if (!in_array($bookingStatus, $validBookingStatuses) || !in_array($paymentStatus, $validPaymentStatuses)) {
        header("Location: ../admin.php?error=Invalid status");
        exit();
    }

    // Get the current booking before update
    $currentBooking = $bookingModel->find($bookingID);
    $oldStatus = $currentBooking['bookingStatus'] ?? '';
    $phoneNumber = $currentBooking['phoneNumber'] ?? '';
    $checkInDate = $currentBooking['checkInDate'] ?? '';

    // Get customer name
    $customer = $currentBooking ? $userModel->find($currentBooking['userID']) : null;
    $customerName = $customer ? ($customer['firstName'] ?? '') . ' ' . ($customer['lastName'] ?? '') : '';

    // Update booking status and payment status
    $updateData = [
        'bookingStatus' => $bookingStatus,
        'paymentStatus' => $paymentStatus,
        'updatedAt' => date('Y-m-d H:i:s')
    ];

    if ($bookingModel->update($bookingID, $updateData)) {
        // Send SMS notification if status changed
        if ($oldStatus !== $bookingStatus && !empty($phoneNumber)) {
            try {
                require_once SMS_PATH . '/SmsService.php';
                require_once GMAIL_PATH . '/EmailService.php';
                $smsService = new SmsService();
                
                if ($bookingStatus === Booking::STATUS_CONFIRMED) {
                    $smsService->sendBookingApprovalSms($bookingID, $phoneNumber, trim($customerName), $checkInDate);
                    
                    // Send email receipt automatically
                    try {
                        $emailService = new EmailService();
                        $bookingData = $bookingModel->getByIdWithDetails($bookingID);
                        
                        if ($bookingData && !empty($bookingData['email'])) {
                            $customerName = trim($bookingData['firstName'] . ' ' . $bookingData['lastName']);
                            $subject = "Booking Confirmation - TravelMates Hotel";
                            
                            $body = "
                            <html>
                            <body style='font-family: Arial, sans-serif;'>
                                <h2>Booking Confirmation</h2>
                                <p>Dear {$customerName},</p>
                                <p>Your booking has been confirmed. Here are your booking details:</p>
                                <table style='border-collapse: collapse; width: 100%;'>
                                    <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Booking ID:</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>#{$bookingData['bookingID']}</td></tr>
                                    <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Room:</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{$bookingData['roomName']}</td></tr>
                                    <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Check-in:</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{$bookingData['checkInDate']}</td></tr>
                                    <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Check-out:</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>{$bookingData['checkOutDate']}</td></tr>
                                    <tr><td style='padding: 8px; border: 1px solid #ddd;'><strong>Total Amount:</strong></td><td style='padding: 8px; border: 1px solid #ddd;'>₱" . number_format($bookingData['totalAmount'], 2) . "</td></tr>
                                </table>
                                <p>Thank you for choosing TravelMates Hotel!</p>
                                <p>Best regards,<br>TravelMates Hotel Team</p>
                            </body>
                            </html>
                            ";
                            
                            $emailService->sendEmail($bookingData['email'], $subject, $body);
                        }
                    } catch (Exception $emailEx) {
                        error_log('Email Service Error: ' . $emailEx->getMessage());
                    }
                } elseif ($bookingStatus === Booking::STATUS_CANCELLED) {
                    $smsService->sendBookingCancelledSms($bookingID, $phoneNumber, trim($customerName));
                } elseif ($bookingStatus === Booking::STATUS_COMPLETED) {
                    $smsService->sendBookingCompletedSms($bookingID, $phoneNumber, trim($customerName));
                }
            } catch (Exception $e) {
                // Log error but don't fail the booking update
                error_log('SMS Error: ' . $e->getMessage());
            }
        }
        
        header("Location: ../admin.php?success=Booking updated successfully");
    } else {
        header("Location: ../admin.php?error=Failed to update booking");
    }
    exit();
}

header("Location: ../admin.php");
exit();
?>
