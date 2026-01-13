<?php
/**
 * Import SMS messages from sms_log.txt into database
 * Run this once to import existing messages that weren't stored
 */

require_once '../../../core/connect.php';
require_once '../auth/adminAuth.php';

$logFile = __DIR__ . "/../../../sms_log.txt";

if (!file_exists($logFile)) {
    die("sms_log.txt file not found!");
}

$logContent = file_get_contents($logFile);
$lines = explode("\n", $logContent);

$imported = 0;
$skipped = 0;
$errors = 0;

foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line) || !preg_match('/^\[(.+?)\]\s*(.+)$/', $line, $matches)) {
        continue;
    }
    
    $timestamp = $matches[1];
    $jsonData = $matches[2];
    
    $data = json_decode($jsonData, true);
    
    if (!$data || !isset($data['from']) || !isset($data['text'])) {
        if (isset($data['body'])) {
            $data['text'] = $data['body'];
        } else {
            $skipped++;
            continue;
        }
    }
    
    $from = $data['from'];
    $body = $data['text'] ?? $data['body'] ?? '';
    
    if (empty($body)) {
        $skipped++;
        continue;
    }
    
    $date = $timestamp;
    if (isset($data['receivedStamp'])) {
        $date = date('Y-m-d H:i:s', $data['receivedStamp'] / 1000);
    } elseif (isset($data['date'])) {
        $date = $data['date'];
    }
    
    $phoneNumber = preg_replace('/[^0-9]/', '', $from);
    
    $checkQuery = "SELECT sms_id FROM sms_messages WHERE phone = ? AND message_body = ? AND received_at = ?";
    $checkStmt = mysqli_prepare($conn, $checkQuery);
    
    if ($checkStmt) {
        mysqli_stmt_bind_param($checkStmt, "sss", $phoneNumber, $body, $date);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        
        if (mysqli_num_rows($checkResult) > 0) {
            mysqli_stmt_close($checkStmt);
            $skipped++;
            continue;
        }
        mysqli_stmt_close($checkStmt);
    }
    
    $query = "INSERT INTO sms_messages (phone, message_body, received_at, raw_data) 
              VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssss", $phoneNumber, $body, $date, $jsonData);
        
        if (mysqli_stmt_execute($stmt)) {
            $imported++;
        } else {
            $errors++;
            echo "Error importing: " . mysqli_error($conn) . "<br>";
        }
        
        mysqli_stmt_close($stmt);
    } else {
        $errors++;
        echo "Error preparing statement: " . mysqli_error($conn) . "<br>";
    }
}

echo "<h2>SMS Import Results</h2>";
echo "<p>Imported: $imported messages</p>";
echo "<p>Skipped (duplicates): $skipped messages</p>";
echo "<p>Errors: $errors messages</p>";
echo "<p><a href='../../adminPages/smsInbox.php'>Go to SMS Inbox</a></p>";
?>

