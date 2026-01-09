<?php
session_start();

// Include configuration file
require_once __DIR__ . '/../../config.php';

// Include database connection
require_once DBCONNECT_PATH . '/connect.php';

// Include class autoloader
require_once CLASSES_PATH . '/autoload.php';

// Helper function to send JSON response
function sendJsonResponse($success, $message) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

// Check if user is admin - handle AJAX requests differently
if (!Auth::isAdmin()) {
    sendJsonResponse(false, 'Access denied. Please login as admin.');
}

// Include SMS Service
require_once SMS_PATH . '/SmsService.php';

// Include Email Service
require_once GMAIL_PATH . '/EmailService.php';

// Initialize models
$bookingModel = new Booking();
$userModel = new User();

// Handle booking status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingID = isset($_POST['bookingID']) ? (int)$_POST['bookingID'] : 0;
    $action = isset($_POST['bookingAction']) ? $_POST['bookingAction'] : '';

    if ($bookingID <= 0) {
        sendJsonResponse(false, 'Invalid booking ID');
    }

    if ($action !== 'confirm' && $action !== 'cancel' && $action !== 'complete' && $action !== 'edit') {
        sendJsonResponse(false, 'Invalid action');
    }

    // Get booking details for SMS
    $bookingDetails = $bookingModel->find($bookingID);
    if (!$bookingDetails) {
        sendJsonResponse(false, 'Booking not found');
    }

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

            sendJsonResponse(true, 'Booking confirmed successfully!');
        }

        sendJsonResponse(false, 'Failed to confirm booking');
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
            sendJsonResponse(true, 'Booking cancelled successfully!');
        }

        sendJsonResponse(false, 'Failed to cancel booking');
    } elseif ($action === 'complete') {
        if ($bookingModel->complete($bookingID)) {
            // Send SMS notification
            if (!empty($phoneNumber)) {
                try {
                    $smsService = new SmsService();
                    $smsService->sendBookingCompletedSms($bookingID, $phoneNumber, $customerName);
                } catch (Exception $e) {
                    error_log('SMS Error: ' . $e->getMessage());
                }
            }
            sendJsonResponse(true, 'Booking marked as completed!');
        }
        sendJsonResponse(false, 'Failed to complete booking');
    } elseif ($action === 'edit') {
        $newStatus = isset($_POST['newStatus']) ? $_POST['newStatus'] : '';
        $newPaymentStatus = isset($_POST['newPaymentStatus']) ? $_POST['newPaymentStatus'] : '';

        // Validate status values
        $validStatuses = [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED, Booking::STATUS_CANCELLED, Booking::STATUS_COMPLETED];
        $validPaymentStatuses = [Booking::PAYMENT_PENDING, Booking::PAYMENT_PAID, Booking::PAYMENT_REFUNDED];

        if (in_array($newStatus, $validStatuses) && in_array($newPaymentStatus, $validPaymentStatuses)) {
            $bookingModel->update($bookingID, [
                'bookingStatus' => $newStatus,
                'paymentStatus' => $newPaymentStatus,
                'updatedAt' => date('Y-m-d H:i:s')
            ]);
            sendJsonResponse(true, 'Booking updated successfully!');
        } else {
            sendJsonResponse(false, 'Invalid status value!');
        }
    }
}

// If not POST request
sendJsonResponse(false, 'Invalid request method');
