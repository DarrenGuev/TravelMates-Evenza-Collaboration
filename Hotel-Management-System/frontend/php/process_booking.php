<?php
session_start();

// Include configuration file
require_once __DIR__ . '/../../config.php';

// Include database connection
require_once DBCONNECT_PATH . '/connect.php';

// Include class autoloader
require_once __DIR__ . '/../../classes/autoload.php';

// Include shared HTTP helper functions (ajax/redirect responses)
require_once __DIR__ . '/../includes/http_helpers.php';

// Detect AJAX flag (sent by frontend as form field 'ajax')
$isAjax = false;
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] == '1') {
    $isAjax = true;
}

if (!Auth::isLoggedIn()) {
    if ($isAjax) {
        ajaxResponse(['success' => false, 'message' => 'Please login to book a room'], 401);
    }
    header("Location: ../login.php?error=Please login to book a room");
    exit();
}

// Initialize models
$userModel = new User();
$roomModel = new Room();
$bookingModel = new Booking();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userID = Auth::getUserId();
    $roomID = (int)$_POST['roomID'];

    // Prefer posted first/last name when available; otherwise fetch from users table
    $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
    $lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phoneNumber = isset($_POST['phoneNumber']) ? trim($_POST['phoneNumber']) : '';

    if ($firstName === '' || $lastName === '' || $email === '' || $phoneNumber === '') {
        $userInfo = $userModel->find($userID);
        if ($userInfo) {
            if ($firstName === '') $firstName = $userInfo['firstName'];
            if ($lastName === '') $lastName = $userInfo['lastName'];
            if ($email === '') $email = $userInfo['email'];
            if ($phoneNumber === '') $phoneNumber = $userInfo['phoneNumber'];
        }
    }

    $fullName = trim($firstName . ' ' . $lastName);
    $checkInDate = $_POST['checkInDate'];
    $checkOutDate = $_POST['checkOutDate'];
    $numberOfGuests = (int)$_POST['numberOfGuests'];
    $totalPrice = (float)$_POST['totalPrice'];
    $paymentMethod = $_POST['paymentMethod'];
    $checkIn = new DateTime($checkInDate);
    $checkOut = new DateTime($checkOutDate);
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    if ($checkIn < $today) {
        handleRedirectOrJson('Check-in date cannot be in the past', 400);
    }
    
    if ($checkOut <= $checkIn) {
        handleRedirectOrJson('Check-out date must be after check-in date', 400);
    }
    
    // Only PayPal payments are accepted from the frontend
    $validPaymentMethods = ['paypal'];
    if (!in_array($paymentMethod, $validPaymentMethods)) {
        handleRedirectOrJson('Invalid payment method', 400);
    }

    // For PayPal flow we should mark payment as pending until capture completes.
    $paymentStatus = Booking::PAYMENT_PENDING;
    
    // Check room availability
    $room = $roomModel->find($roomID);
    if (!$room || $room['quantity'] < 1) {
        handleRedirectOrJson('Room is not available', 400);
    }
    
    // Check for overlapping bookings using Room model
    if (!$roomModel->isAvailable($roomID, $checkInDate, $checkOutDate)) {
        handleRedirectOrJson('Room is not available for the selected dates', 400);
    }
    
    // Create booking using Booking model
    $bookingData = [
        'userID' => $userID,
        'roomID' => $roomID,
        'fullName' => $fullName,
        'email' => $email,
        'phoneNumber' => $phoneNumber,
        'checkInDate' => $checkInDate,
        'checkOutDate' => $checkOutDate,
        'numberOfGuests' => $numberOfGuests,
        'totalPrice' => $totalPrice,
        'paymentMethod' => $paymentMethod,
        'paymentStatus' => $paymentStatus,
        'bookingStatus' => Booking::STATUS_PENDING
    ];
    
    $newBookingId = $bookingModel->createBooking($bookingData);

    if ($newBookingId) {
        // If AJAX request, return JSON containing bookingID so frontend can continue to PayPal
        if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'bookingID' => $newBookingId]);
            exit();
        }

        header("Location: ../bookings.php?success=Booking submitted successfully! Waiting for confirmation.");
    } else {
        if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
            header('Content-Type: application/json', true, 500);
            echo json_encode(['success' => false, 'message' => 'Failed to create booking']);
            exit();
        }

        handleRedirectOrJson('Failed to submit booking. Please try again.', 500);
    }
    exit();
} else {
    header("Location: ../rooms.php");
    exit();
}
?>
