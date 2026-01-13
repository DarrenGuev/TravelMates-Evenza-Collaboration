<?php
/**
 * SMS Webhook Receiver
 * Receives SMS messages from Android SMS Forwarder
 * Based on sample code provided
 */

$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

$timestamp = date("Y-m-d H:i:s");

$logFile = __DIR__ . "/sms_log.txt";
file_put_contents($logFile, "[$timestamp] " . $rawData . PHP_EOL, FILE_APPEND);

// Process and store SMS in database
// SMS Forwarder sends: {"from":"+639701319849", "text":"message", ...}
// Check for both 'body' and 'text' fields (different apps use different field names)
$from = null;
$body = null;
$date = $timestamp;

if ($data && isset($data['from'])) {
    $from = $data['from'];
    
    if (isset($data['text'])) {
        $body = $data['text'];
    } elseif (isset($data['body'])) {
        $body = $data['body'];
    } elseif (isset($data['message'])) {
        $body = $data['message'];
    }
    
    if (isset($data['receivedStamp'])) {
        $date = date('Y-m-d H:i:s', $data['receivedStamp'] / 1000);
    } elseif (isset($data['date'])) {
        $date = $data['date'];
    } elseif (isset($data['received_at'])) {
        $date = $data['received_at'];
    }
}

if ($from && $body) {
    
    $phoneNumber = preg_replace('/[^0-9+]/', '', $from);
    $phoneNumberForDB = preg_replace('/[^0-9]/', '', $from);
    
    try {
        require_once '../../core/connect.php';
        
        if ($conn) {
            $query = "INSERT INTO sms_messages (phone, message_body, received_at, raw_data) 
                      VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ssss", $phoneNumberForDB, $body, $date, $rawData);
                $result = mysqli_stmt_execute($stmt);
                
                if ($result) {
                    file_put_contents($logFile, "[$timestamp] SMS STORED: From=" . $phoneNumberForDB . ", Message=" . substr($body, 0, 50) . PHP_EOL, FILE_APPEND);
                } else {
                    file_put_contents($logFile, "[$timestamp] DB ERROR: " . mysqli_error($conn) . PHP_EOL, FILE_APPEND);
                }
                
                mysqli_stmt_close($stmt);
            } else {
                file_put_contents($logFile, "[$timestamp] DB PREPARE ERROR: " . mysqli_error($conn) . PHP_EOL, FILE_APPEND);
            }
        } else {
            file_put_contents($logFile, "[$timestamp] DB CONNECTION ERROR: No database connection" . PHP_EOL, FILE_APPEND);
        }
    } catch (Exception $e) {
        file_put_contents($logFile, "[$timestamp] EXCEPTION: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    }
} else {
    file_put_contents($logFile, "[$timestamp] MISSING DATA: from=" . ($from ?? 'NULL') . ", body=" . ($body ?? 'NULL') . ", raw=" . substr($rawData, 0, 200) . PHP_EOL, FILE_APPEND);
}

http_response_code(200);
echo "Success!!";
?>

