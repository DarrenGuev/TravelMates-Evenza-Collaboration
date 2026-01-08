<?php
session_start();

// Include configuration file
require_once __DIR__ . '/../../config.php';

// Include database connection
require_once DBCONNECT_PATH . '/connect.php';

// Include class autoloader
require_once CLASSES_PATH . '/autoload.php';

// Check if user is admin
Auth::requireAdmin('../../frontend/login.php');

// Include SMS Service
require_once SMS_PATH . '/SmsService.php';

// Include Email Service
require_once GMAIL_PATH . '/EmailService.php';

// Initialize models
$bookingModel = new Booking();
$userModel = new User();

// Handle booking status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bookingAction'])) {
    $bookingID = (int)$_POST['bookingID'];
    $action = $_POST['bookingAction'];
    
    // Get booking details for SMS
    $bookingDetails = $bookingModel->getById($bookingID);
    if ($bookingDetails) {
        // Get user details
        $bookingUser = $userModel->find($bookingDetails['userID']);
        $phoneNumber = $bookingDetails['phoneNumber'] ?? '';
        $checkInDate = $bookingDetails['checkInDate'] ?? '';
        $customerName = trim(($bookingUser['firstName'] ?? '') . ' ' . ($bookingUser['lastName'] ?? ''));
        
        if ($action === 'confirm') {
            if ($bookingModel->confirm($bookingID)) {
                // Send SMS notification
                if (!empty($phoneNumber)) {
                    try {
                        $smsService = new SmsService();
                        $smsService->sendBookingApprovalSms($bookingID, $phoneNumber, $customerName, $checkInDate);
                    } catch (Exception $e) {
                        error_log('SMS Error: ' . $e->getMessage());
                    }
                }
                
                // Send email receipt automatically
                try {
                    $emailService = new EmailService();
                    $bookingData = $bookingModel->getByIdWithDetails($bookingID);
                    
                    if ($bookingData && !empty($bookingData['email'])) {
                        $bookingData['customerName'] = trim($bookingData['firstName'] . ' ' . $bookingData['lastName']);
                        $emailResult = $emailService->sendBookingReceipt($bookingData);
                        if (!$emailResult['success']) {
                            error_log('Email Receipt Error for Booking #' . $bookingID . ': ' . ($emailResult['error'] ?? 'Unknown error'));
                        }
                    }
                } catch (Exception $e) {
                    error_log('Email Service Error: ' . $e->getMessage());
                }
                
                header("Location: ../admin.php?success=Booking confirmed successfully!");
                exit();
            }
        } elseif ($action === 'cancel') {
            if ($bookingModel->cancel($bookingID)) {
                // Send SMS notification
                if (!empty($phoneNumber)) {
                    try {
                        $smsService = new SmsService();
                        $smsService->sendBookingCancelledSms($bookingID, $phoneNumber, $customerName);
                    } catch (Exception $e) {
                        error_log('SMS Error: ' . $e->getMessage());
                    }
                }
                header("Location: ../admin.php?success=Booking cancelled successfully!");
                exit();
            }
        }
    }
}

// If accessed directly or no action taken
header("Location: ../admin.php");
exit();
