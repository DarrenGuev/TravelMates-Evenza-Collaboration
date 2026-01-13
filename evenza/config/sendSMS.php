<?php
/**
 * SMS Sending API
 * Sends SMS messages via Android SMS Gateway
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAdmin = isset($_SESSION['admin_id']) || (isset($_SESSION['user_id']) && isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin');
if (!$isAdmin) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$phoneNumber = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (empty($phoneNumber) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Phone number and message are required']);
    exit;
}

$originalPhoneNumber = $phoneNumber;
$phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);

$phoneNumber = preg_replace('/^\+?63/', '', $phoneNumber);
$phoneNumber = preg_replace('/^0/', '', $phoneNumber);

if (strlen($phoneNumber) == 10) {
    $phoneNumber = '+63' . $phoneNumber;
} elseif (strlen($phoneNumber) == 11 && substr($phoneNumber, 0, 1) === '0') {
    $phoneNumber = '+63' . substr($phoneNumber, 1);
} elseif (strlen($phoneNumber) == 9) {
    $phoneNumber = '+630' . $phoneNumber;
} else {
    $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
    if (strlen($phoneNumber) >= 10) {
        $phoneNumber = '+63' . substr($phoneNumber, -10);
    } else {
        error_log("SMS Error: Invalid phone number format: " . $originalPhoneNumber);
        echo json_encode(['success' => false, 'message' => 'Invalid phone number format']);
        exit;
    }
}

error_log("SMS Phone Number: Original=" . $originalPhoneNumber . ", Formatted=" . $phoneNumber);

$smsGatewayBaseUrl = 'http://192.168.1.20:8080';
$smsGatewayUsername = 'sms';
$smsGatewayPassword = 'admin123';

$endpoints = ['/messages', '/message', '/send', '/', '/api/send', '/sms/send'];

$smsDataFormats = [
    ['method' => 'POST', 'format' => 'json', 'data' => json_encode(['phoneNumbers' => [$phoneNumber], 'message' => $message]), 'headers' => ['Content-Type: application/json']],
    ['method' => 'POST', 'format' => 'json', 'data' => json_encode(['phoneNumbers' => $phoneNumber, 'message' => $message]), 'headers' => ['Content-Type: application/json']],
    ['method' => 'POST', 'format' => 'json', 'data' => json_encode(['phone' => $phoneNumber, 'message' => $message]), 'headers' => ['Content-Type: application/json']],
    ['method' => 'GET', 'format' => 'query', 'data' => http_build_query(['phone' => $phoneNumber, 'message' => $message]), 'headers' => []],
];

error_log("SMS Sending: Attempting to send SMS to " . $phoneNumber . " via " . $smsGatewayBaseUrl);

$response = null;
$httpCode = 0;
$error = null;
$success = false;
$lastError = '';

foreach ($endpoints as $endpoint) {
    foreach ($smsDataFormats as $formatData) {
        if ($formatData['method'] === 'GET' && $formatData['format'] === 'query') {
            $smsGatewayUrl = $smsGatewayBaseUrl . $endpoint . '?' . $formatData['data'];
        } else {
            $smsGatewayUrl = $smsGatewayBaseUrl . $endpoint;
        }
        
        $ch = curl_init($smsGatewayUrl);
        
        if ($formatData['method'] === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $formatData['data']);
        } else {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }
        
        $headers = $formatData['headers'] ?? [];
        $authHeader = 'Authorization: Basic ' . base64_encode($smsGatewayUsername . ':' . $smsGatewayPassword);
        $headers[] = $authHeader;
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        
        curl_setopt($ch, CURLOPT_USERPWD, $smsGatewayUsername . ':' . $smsGatewayPassword);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        error_log("SMS Attempt: URL=" . $smsGatewayUrl . ", Method=" . $formatData['method'] . ", Format=" . $formatData['format'] . ", Phone=" . $phoneNumber . ", HTTP Code=" . $httpCode . ", Response=" . substr($response, 0, 200));
        
        if ($httpCode === 200 || $httpCode === 201) {
            $responseData = json_decode($response, true);
            
            if (is_array($responseData)) {
                if (isset($responseData['error']) || isset($responseData['status']) && strtolower($responseData['status']) === 'error') {
                    error_log("SMS Gateway returned error in response: " . json_encode($responseData));
                    $lastError = 'Gateway error: ' . (isset($responseData['error']) ? $responseData['error'] : $responseData['status']);
                    continue;
                }
            }
            
            $success = true;
            error_log("SMS Success: Found working endpoint " . $endpoint . " with method " . $formatData['method'] . ", format " . $formatData['format'] . ", phone " . $phoneNumber . ". Response: " . $response);
            break 2;
        }
        
        if ($error) {
            $lastError = $error;
        } else {
            $lastError = 'HTTP Code: ' . $httpCode;
        }
    }
}

error_log("SMS Final Response: HTTP Code " . $httpCode . ", Response: " . substr($response, 0, 200) . ", Error: " . ($error ?: 'None'));

if ($error && !$success) {
    error_log("SMS cURL Error: " . $error);
    echo json_encode(['success' => false, 'message' => 'SMS Gateway Connection Error: ' . $error . '. Please check if the SMS Gateway app is running on your Android device.']);
    exit;
}

if (!$success) {
    if ($httpCode === 200 || $httpCode === 201) {
        $responseData = json_decode($response, true);
        if (is_array($responseData)) {
            $success = isset($responseData['success']) ? $responseData['success'] : true;
            $errorMessage = isset($responseData['message']) ? $responseData['message'] : '';
        } else {
            $success = true;
        }
    } else {
        $responseData = json_decode($response, true);
        $errorMessage = is_array($responseData) && isset($responseData['message']) 
            ? $responseData['message'] 
            : ($lastError ?: 'SMS Gateway returned error code: ' . $httpCode);
    }
}

if ($success) {
    require_once '../core/connect.php';
    if ($conn) {
        $query = "INSERT INTO sms_messages (phone, message_body, received_at, raw_data, is_read) 
                  VALUES (?, ?, NOW(), ?, 1)";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            $rawData = json_encode(['type' => 'sent', 'to' => $phoneNumber, 'message' => $message, 'gateway_response' => $response]);
            mysqli_stmt_bind_param($stmt, "sss", $phoneNumber, $message, $rawData);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
    
    error_log("SMS Success: Message sent to " . $phoneNumber);
    echo json_encode(['success' => true, 'message' => 'SMS sent successfully']);
} else {
    error_log("SMS Failed: " . $errorMessage);
    echo json_encode(['success' => false, 'message' => $errorMessage . ' (HTTP Code: ' . $httpCode . '). Please check your SMS Gateway configuration and ensure the app is running on your Android device.']);
}
?>
