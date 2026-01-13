<?php
/**
 * @param string 
 * @return string
 */
function formatPhoneNumber($phone) {
    if (empty($phone)) {
        return '';
    }
    
    $digits = preg_replace('/[^0-9]/', '', $phone);
    
    if (strlen($digits) >= 12 && substr($digits, 0, 2) === '63') {
        $digits = '0' . substr($digits, 2);
    }
    
    if (strlen($digits) >= 10 && substr($digits, 0, 1) !== '0') {
        $digits = '0' . $digits;
    }
    
    if (strlen($digits) === 11) {
        return substr($digits, 0, 4) . ' ' . substr($digits, 4, 3) . ' ' . substr($digits, 7, 4);
    }
    
    if (strlen($digits) === 10) {
        $digits = '0' . $digits;
        return substr($digits, 0, 4) . ' ' . substr($digits, 4, 3) . ' ' . substr($digits, 7, 4);
    }
    
    if (strlen($digits) >= 7) {
        $formatted = substr($digits, 0, 4);
        $remaining = substr($digits, 4);
        
        while (strlen($remaining) > 0) {
            $chunk = substr($remaining, 0, min(3, strlen($remaining)));
            $formatted .= ' ' . $chunk;
            $remaining = substr($remaining, strlen($chunk));
        }
        
        return $formatted;
    }
    
    return $phone;
}

/**
 * @param string
 * @return true|string
 */
function validatePassword($password) {
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters long.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return 'Password must contain at least one uppercase letter.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        return 'Password must contain at least one lowercase letter.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        return 'Password must contain at least one number.';
    }
    return true;
}

/**
 * Format time string to 12-hour format with AM/PM
 * @param string $timeString - Time string in format "HH:MM:SS" or "HH:MM" or "HH:MM:SS - HH:MM:SS"
 * @return string - Formatted time in 12-hour format (e.g., "11:00 AM - 4:00 PM")
 */
function formatTime12Hour($timeString) {
    if (empty($timeString) || $timeString === ' - ') {
        return '';
    }
    
    if (strpos($timeString, ' - ') !== false) {
        $parts = explode(' - ', $timeString);
        $start = formatTime12Hour($parts[0]);
        $end = formatTime12Hour($parts[1]);
        return $start && $end ? $start . ' - ' . $end : $timeString;
    }
    
    $timeOnly = trim(explode(' ', $timeString)[0]);
    $timeParts = explode(':', $timeOnly);
    
    if (count($timeParts) < 2) {
        return $timeString;
    }
    
    $hour = (int)$timeParts[0];
    $minute = isset($timeParts[1]) ? $timeParts[1] : '00';
    
    // Convert to 12-hour format
    $period = $hour >= 12 ? 'PM' : 'AM';
    $hour12 = $hour == 0 ? 12 : ($hour > 12 ? $hour - 12 : $hour);
    
    return sprintf('%d:%s %s', $hour12, $minute, $period);
}

/**
 * Generate a unique reservation code
 * Format: EVZ-YYYYMMDD-XXXXXX (e.g., EVZ-20241201-A3B5C7)
 * @param mysqli $conn - Database connection
 * @return string - Unique reservation code
 */
function generateUniqueReservationCode($conn) {
    $maxAttempts = 10;
    $attempt = 0;
    
    do {
        $datePart = date('Ymd');
        $randomPart = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        $reservationCode = 'EVZ-' . $datePart . '-' . $randomPart;
        
        $checkQuery = "SELECT reservationId FROM reservations WHERE reservationCode = ?";
        $checkStmt = mysqli_prepare($conn, $checkQuery);
        
        if ($checkStmt) {
            mysqli_stmt_bind_param($checkStmt, "s", $reservationCode);
            mysqli_stmt_execute($checkStmt);
            $result = mysqli_stmt_get_result($checkStmt);
            $exists = mysqli_num_rows($result) > 0;
            mysqli_stmt_close($checkStmt);
            
            if (!$exists) {
                return $reservationCode;
            }
        }
        
        $attempt++;
    } while ($attempt < $maxAttempts);
    
    return 'EVZ-' . $datePart . '-' . strtoupper(substr(md5(uniqid(rand(), true) . time()), 0, 6));
}

/**
 * Logs an email to the database for record-keeping
 * @param mysqli $conn - Database connection
 * @param string $recipientEmail - Recipient email address
 * @param string $recipientName - Recipient name
 * @param string $subject - Email subject
 * @param string $emailType - Type of email (e.g., 'reservation_confirmation')
 * @param int|null $relatedId - Related record ID (e.g., reservationId)
 * @param string|null $emailBodyHtml - HTML content of email
 * @param string|null $emailBodyText - Plain text content of email
 * @param string $status - Status: 'sent' or 'failed'
 * @param string|null $errorMessage - Error message if failed
 * @return bool - True if logged successfully, false otherwise
 */
function logEmailToDatabase($conn, $recipientEmail, $recipientName, $subject, $emailType, $relatedId = null, $emailBodyHtml = null, $emailBodyText = null, $status = 'sent', $errorMessage = null) {
    $query = "INSERT INTO email_logs 
              (recipient_email, recipient_name, subject, email_type, related_id, email_body_html, email_body_text, status, error_message, sent_at) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log("Email Log Error: Failed to prepare query - " . mysqli_error($conn));
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "ssssissss", 
        $recipientEmail, 
        $recipientName, 
        $subject, 
        $emailType, 
        $relatedId, 
        $emailBodyHtml, 
        $emailBodyText, 
        $status, 
        $errorMessage
    );
    
    $result = mysqli_stmt_execute($stmt);
    if (!$result) {
        error_log("Email Log Error: Failed to execute query - " . mysqli_error($conn));
        mysqli_stmt_close($stmt);
        return false;
    }
    
    mysqli_stmt_close($stmt);
    return true;
}

/**
 * @param mysqli $conn - Database connection
 * @param int $reservationId - Reservation ID
 * @return bool - True if email sent successfully, false otherwise
 */
function sendReservationConfirmationEmail($conn, $reservationId) {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../config/email.php';
    
    $query = "SELECT 
                r.reservationId,
                r.reservationCode,
                r.reservationDate,
                r.startTime,
                r.endTime,
                r.totalAmount,
                r.status,
                u.fullName AS customerName,
                u.email AS customerEmail,
                e.title AS eventName,
                e.venue AS eventLocation,
                p.packageName
              FROM reservations r
              INNER JOIN users u ON r.userId = u.userid
              INNER JOIN events e ON r.eventId = e.eventId
              INNER JOIN packages p ON r.packageId = p.packageId
              WHERE r.reservationId = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log("Email Error: Failed to prepare query - " . mysqli_error($conn));
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $reservationId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $reservation = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$reservation || empty($reservation['customerEmail'])) {
        error_log("Email Error: Reservation not found or no email address");
        return false;
    }
    
    $emailConfig = require __DIR__ . '/../config/email.php';
    
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = $emailConfig['smtp']['host'];
        $mail->SMTPAuth = $emailConfig['smtp']['auth'];
        $mail->Username = $emailConfig['smtp']['username'];
        $mail->Password = $emailConfig['smtp']['password'];
        $mail->SMTPSecure = $emailConfig['smtp']['secure'] === 'ssl' ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $emailConfig['smtp']['port'];
        $mail->CharSet = 'UTF-8';
        
        $mail->setFrom($emailConfig['from']['email'], $emailConfig['from']['name']);
        $mail->addAddress($reservation['customerEmail'], $reservation['customerName']);
        $mail->addReplyTo($emailConfig['reply_to']['email'], $emailConfig['reply_to']['name']);
        
        $mail->isHTML(true);
        $mail->Subject = 'Reservation Confirmed - ' . $reservation['eventName'];
        
        $reservationDate = date('F d, Y', strtotime($reservation['reservationDate']));
        $timeRange = '';
        if (!empty($reservation['startTime']) && !empty($reservation['endTime'])) {
            $timeRange = formatTime12Hour($reservation['startTime']) . ' - ' . formatTime12Hour($reservation['endTime']);
        } elseif (!empty($reservation['startTime'])) {
            $timeRange = formatTime12Hour($reservation['startTime']);
        }
        
        $mail->Body = buildReservationEmailHTML($reservation, $reservationDate, $timeRange);
        $emailBodyHtml = $mail->Body;
        
        $mail->AltBody = buildReservationEmailText($reservation, $reservationDate, $timeRange);
        $emailBodyText = $mail->AltBody;
        
        $mail->send();
        
        // Log successful email to database
        logEmailToDatabase(
            $conn,
            $reservation['customerEmail'],
            $reservation['customerName'],
            $mail->Subject,
            'reservation_confirmation',
            $reservationId,
            $emailBodyHtml,
            $emailBodyText,
            'sent',
            null
        );
        
        return true;
        
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        $errorMessage = $mail->ErrorInfo;
        error_log("Email Error: {$errorMessage}");
        
        // Log failed email to database
        if (isset($reservation) && !empty($reservation['customerEmail'])) {
            logEmailToDatabase(
                $conn,
                $reservation['customerEmail'],
                $reservation['customerName'] ?? null,
                $mail->Subject ?? 'Reservation Confirmed - ' . ($reservation['eventName'] ?? 'Unknown Event'),
                'reservation_confirmation',
                $reservationId,
                $emailBodyHtml ?? null,
                $emailBodyText ?? null,
                'failed',
                $errorMessage
            );
        }
        
        return false;
    }
}

/**
 * @param array $reservation
 * @param string $reservationDate
 * @param string $timeRange
 * @return string
 */
function buildReservationEmailHTML($reservation, $reservationDate, $timeRange) {
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #4A5D4A 0%, #6B8E6B 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .detail-row { margin: 15px 0; padding: 10px; background: white; border-radius: 5px; }
        .detail-label { font-weight: bold; color: #4A5D4A; }
        .reservation-code { font-size: 24px; font-weight: bold; color: #4A5D4A; letter-spacing: 2px; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Reservation Confirmed!</h1>
            <p>Thank you for your reservation with EVENZA</p>
        </div>
        <div class="content">
            <p>Dear ' . htmlspecialchars($reservation['customerName']) . ',</p>
            <p>Your reservation has been confirmed. Please find the details below:</p>
            
            <div class="detail-row">
                <div class="detail-label">Reservation Code:</div>
                <div class="reservation-code">' . htmlspecialchars($reservation['reservationCode']) . '</div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Event Name:</div>
                <div>' . htmlspecialchars($reservation['eventName']) . '</div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Package:</div>
                <div>' . htmlspecialchars($reservation['packageName']) . '</div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Date:</div>
                <div>' . htmlspecialchars($reservationDate) . '</div>
            </div>';
    
    if (!empty($timeRange)) {
        $html .= '<div class="detail-row">
                <div class="detail-label">Time:</div>
                <div>' . htmlspecialchars($timeRange) . '</div>
            </div>';
    }
    
    $html .= '<div class="detail-row">
                <div class="detail-label">Location:</div>
                <div>' . htmlspecialchars($reservation['eventLocation']) . '</div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Total Amount:</div>
                <div>₱ ' . number_format($reservation['totalAmount'], 2) . '</div>
            </div>
            
            <p style="margin-top: 30px;"><strong>Important:</strong> Please keep your reservation code safe. You will need it for check-in and any inquiries.</p>
            
            <p>If you have any questions, please contact us at <a href="mailto:info@evenza.com">info@evenza.com</a></p>
        </div>
        <div class="footer">
            <p>&copy; ' . date('Y') . ' EVENZA. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';
    
    return $html;
}

/**
 */
function buildReservationEmailText($reservation, $reservationDate, $timeRange) {
    $text = "Reservation Confirmed!\n\n";
    $text .= "Dear " . $reservation['customerName'] . ",\n\n";
    $text .= "Your reservation has been confirmed. Please find the details below:\n\n";
    $text .= "Reservation Code: " . $reservation['reservationCode'] . "\n";
    $text .= "Event Name: " . $reservation['eventName'] . "\n";
    $text .= "Package: " . $reservation['packageName'] . "\n";
    $text .= "Date: " . $reservationDate . "\n";
    
    if (!empty($timeRange)) {
        $text .= "Time: " . $timeRange . "\n";
    }
    
    $text .= "Location: " . $reservation['eventLocation'] . "\n";
    $text .= "Total Amount: ₱ " . number_format($reservation['totalAmount'], 2) . "\n\n";
    $text .= "Important: Please keep your reservation code safe. You will need it for check-in and any inquiries.\n\n";
    $text .= "If you have any questions, please contact us at info@evenza.com\n\n";
    $text .= "© " . date('Y') . " EVENZA. All rights reserved.";
    
    return $text;
}

/**
 * @param mysqli $conn - Database connection
 * @param int $reservationId - Reservation ID
 * @param string $status - New status ('confirmed' or 'cancelled')
 * @param string $cancellationType - Cancellation type: 'non_payment', 'date_occupied', 'other', or false for non-cancellation
 * @return bool - True if SMS sent successfully, false otherwise
 */
function sendReservationStatusSMS($conn, $reservationId, $status, $cancellationType = false) {
    $query = "SELECT 
                r.reservationId,
                r.reservationDate,
                r.startTime,
                r.endTime,
                r.status,
                u.fullName AS customerName,
                u.phone AS customerPhone,
                e.title AS eventName,
                e.venue AS eventVenue,
                p.packageName
              FROM reservations r
              INNER JOIN users u ON r.userId = u.userid
              INNER JOIN events e ON r.eventId = e.eventId
              INNER JOIN packages p ON r.packageId = p.packageId
              WHERE r.reservationId = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    if (!$stmt) {
        error_log("SMS Error: Failed to prepare query - " . mysqli_error($conn));
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "i", $reservationId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $reservation = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$reservation || empty($reservation['customerPhone'])) {
        error_log("SMS Error: Reservation not found or no phone number");
        return false;
    }
    
    $phoneNumber = preg_replace('/[^0-9]/', '', $reservation['customerPhone']);
    if (empty($phoneNumber)) {
        error_log("SMS Error: Invalid phone number");
        return false;
    }
    
    $reservationDate = date('F d, Y', strtotime($reservation['reservationDate']));
    
    $timeRange = '';
    if (!empty($reservation['startTime']) && !empty($reservation['endTime'])) {
        $timeRange = formatTime12Hour($reservation['startTime']) . ' - ' . formatTime12Hour($reservation['endTime']);
    } elseif (!empty($reservation['startTime'])) {
        $timeRange = formatTime12Hour($reservation['startTime']);
    }
    
    $message = '';
    if (strtolower($status) === 'confirmed') {
        $message = "Hello " . $reservation['customerName'] . "! Your reservation for " . $reservation['eventName'] . " has been CONFIRMED. ";
        $message .= "Date: " . $reservationDate;
        if (!empty($timeRange)) {
            $message .= " | Time: " . $timeRange;
        }
        $message .= " | Venue: " . $reservation['eventVenue'] . " | Package: " . $reservation['packageName'] . ". ";
        $message .= "We look forward to seeing you! - EVENZA";
    } elseif (strtolower($status) === 'cancelled') {
        // Check if user cancelled the reservation and get paymentDeadline for fallback check
        $checkQuery = "SELECT userCancelled, paymentDeadline FROM reservations WHERE reservationId = ?";
        $checkStmt = mysqli_prepare($conn, $checkQuery);
        $userCancelled = false;
        $hasPaymentDeadline = false;
        
        if ($checkStmt) {
            mysqli_stmt_bind_param($checkStmt, "i", $reservationId);
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);
            $checkRow = mysqli_fetch_assoc($checkResult);
            mysqli_stmt_close($checkStmt);
            
            if ($checkRow) {
                $userCancelled = (bool)$checkRow['userCancelled'];
                // Check if paymentDeadline exists (indicates it was a confirmed reservation)
                if ($checkRow['paymentDeadline'] && !empty($checkRow['paymentDeadline']) && $checkRow['paymentDeadline'] !== '0000-00-00 00:00:00') {
                    $hasPaymentDeadline = true;
                }
            }
        }
        
        // Skip SMS entirely for user-initiated cancellations
        if ($userCancelled || strtolower((string)$cancellationType) === 'user') {
            return true;
        }
        
        if ($cancellationType === false && $hasPaymentDeadline) {
            $cancellationType = 'non_payment';
        }
        
        // Determine message based on cancellation type
        if ($cancellationType === 'non_payment') {
            // Confirmed → Cancelled: Non-payment (admin cancelled after payment deadline passed)
            $message = "Hello " . $reservation['customerName'] . ", we regret to inform you that your reservation for " . $reservation['eventName'] . " on " . $reservationDate;
            if (!empty($timeRange)) {
                $message .= " (" . $timeRange . ")";
            }
            $message .= " has been CANCELLED due to failure to settle the payment within the required 2-day timeframe. ";
            $message .= "Please contact us if you wish to make a new reservation. - EVENZA";
        } elseif ($cancellationType === 'date_occupied') {
            // Pending → Cancelled: Date/time occupied
            $message = "Hello " . $reservation['customerName'] . ", we regret to inform you that your reservation for " . $reservation['eventName'] . " on " . $reservationDate;
            if (!empty($timeRange)) {
                $message .= " (" . $timeRange . ")";
            }
            $message .= " has been CANCELLED. The selected date and time are already occupied. ";
            $message .= "Please contact us for alternative dates. - EVENZA";
        } else {
            // Other admin cancellation reasons (fallback)
            $message = "Hello " . $reservation['customerName'] . ", we regret to inform you that your reservation for " . $reservation['eventName'] . " on " . $reservationDate;
            if (!empty($timeRange)) {
                $message .= " (" . $timeRange . ")";
            }
            $message .= " has been CANCELLED. The selected date and time are already occupied. ";
            $message .= "Please contact us for alternative dates. - EVENZA";
        }
    } else {
        return false;
    }
    
    $smsApiUrl = 'http://localhost/evenza/config/sendSMS.php';
    
    $ch = curl_init($smsApiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'phone' => $phoneNumber,
        'message' => $message
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("SMS Error: cURL error - " . $error);
        return false;
    }
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if (isset($result['success']) && $result['success']) {
            return true;
        } else {
            error_log("SMS Error: " . ($result['message'] ?? 'Unknown error'));
            return false;
        }
    }
    
    error_log("SMS Error: HTTP Code " . $httpCode);
    return false;
}
?>

