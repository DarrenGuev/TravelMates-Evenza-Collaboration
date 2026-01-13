<?php
session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['phoneNumber']) || empty(trim($input['phoneNumber']))) {
    echo json_encode(['success' => false, 'message' => 'Phone number is required.']);
    exit;
}

if (!isset($input['otpCode']) || empty(trim($input['otpCode']))) {
    echo json_encode(['success' => false, 'message' => 'Verification code is required.']);
    exit;
}

$phoneNumber = trim($input['phoneNumber']);
$otpCode = trim($input['otpCode']);

if (!preg_match('/^[0-9]{6}$/', $otpCode)) {
    echo json_encode(['success' => false, 'message' => 'Invalid verification code format.']);
    exit;
}

//check if OTP session data exists
if (!isset($_SESSION['forgot_password_otp']) || 
    !isset($_SESSION['forgot_password_phone']) || 
    !isset($_SESSION['forgot_password_otp_expiry'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please request a new verification code.']);
    exit;
}

//this will verify phone number matches session
if ($_SESSION['forgot_password_phone'] !== $phoneNumber) {
    echo json_encode(['success' => false, 'message' => 'Phone number mismatch. Please start over.']);
    exit;
}

//this will check if OTP has expired
if (time() > $_SESSION['forgot_password_otp_expiry']) {
    unset($_SESSION['forgot_password_otp']);
    unset($_SESSION['forgot_password_otp_expiry']);
    
    echo json_encode(['success' => false, 'message' => 'Verification code has expired. Please request a new one.']);
    exit;
}

if ($_SESSION['forgot_password_otp'] !== $otpCode) {
    echo json_encode(['success' => false, 'message' => 'Invalid verification code. Please try again.']);
    exit;
}

$_SESSION['forgot_password_verified'] = true; //if the OTP is valid then it mark as verified

//clear the OTP but keep the phone and user ID for password reset
unset($_SESSION['forgot_password_otp']);
unset($_SESSION['forgot_password_otp_expiry']);

echo json_encode([
    'success' => true, 
    'message' => 'Phone number verified successfully.'
]);
