<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config.php';
require_once CLASSES_PATH . '/autoload.php';
require_once SMS_PATH . '/SmsService.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['username']) || empty(trim($input['username']))) {
    echo json_encode(['success' => false, 'message' => 'Username is required.']);
    exit;
}

if (!isset($input['phoneNumber']) || empty(trim($input['phoneNumber']))) {
    echo json_encode(['success' => false, 'message' => 'Phone number is required.']);
    exit;
}

$username = trim($input['username']);
$phoneNumber = trim($input['phoneNumber']);

if (!preg_match('/^\+?[0-9]{7,15}$/', $phoneNumber)) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number format.']);
    exit;
}

try {
    $user = new User();
    $userData = $user->findByUsernameAndPhone($username, $phoneNumber);

    if (!$userData) {
        echo json_encode(['success' => false, 'message' => 'No account found with this username and phone number combination.']);
        exit;
    }

    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT); //generate 6digit OTP
    
    //store OTP in session with expiry (5 minutes)
    $_SESSION['forgot_password_otp'] = $otp;
    $_SESSION['forgot_password_username'] = $username;
    $_SESSION['forgot_password_phone'] = $phoneNumber;
    $_SESSION['forgot_password_user_id'] = $userData['userID'];
    $_SESSION['forgot_password_otp_expiry'] = time() + 300; // 5 minutes
    $_SESSION['forgot_password_verified'] = false;

    //this will send OTP via sms
    $smsService = new SmsService();
    $message = "Your TravelMates verification code is: {$otp}. This code expires in 5 minutes. Do not share this code with anyone.";
    
    $result = $smsService->sendCustomSms($phoneNumber, $message);

    if ($result['status'] === 'success' || $result['status'] === 'sent') {
        echo json_encode([
            'success' => true, 
            'message' => 'Verification code sent to your phone number.'
        ]);
    } else {
        unset($_SESSION['forgot_password_otp']);
        unset($_SESSION['forgot_password_username']);
        unset($_SESSION['forgot_password_phone']);
        unset($_SESSION['forgot_password_user_id']);
        unset($_SESSION['forgot_password_otp_expiry']);
        unset($_SESSION['forgot_password_verified']);
        
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to send verification code. Please try again.'
        ]);
    }
} catch (Exception $e) {
    error_log('Forgot Password OTP Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
