<?php

$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

$timestamp = date("Y-m-d H:i:s");

$logFile = __DIR__ . "/sms_log.php";
file_put_contents($logFile, "[$timestamp]" . $rawData . PHP_EOL, FILE_APPEND);

// Log to database to keep the SMS Dashboard updated
try {
    require_once __DIR__ . '/SmsService.php';
    $smsService = new SmsService();
    
    // Extract data (adjusting for common IPROG SMS field names)
    $phoneNumber = $data['phone_number'] ?? $data['from'] ?? $data['sender'] ?? '';
    $message = $data['message'] ?? $data['text'] ?? $data['body'] ?? '';
    
    if (!empty($phoneNumber) && !empty($message)) {
        $smsService->logMessage(
            null, 
            $phoneNumber, 
            $message, 
            'incoming', 
            'received', 
            'incoming', 
            $rawData
        );
    }
} catch (Exception $e) {
    // Silently fail database logging to ensure webhook returns 200
}

http_response_code(200);
echo "Success!!";

?>
