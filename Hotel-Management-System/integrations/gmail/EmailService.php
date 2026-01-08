<?php
require_once __DIR__ . '/config.php';

class EmailService
{
    private $conn;
    private $tokenFile;
    private $fromEmail;
    private $fromName;

    //Constructor - Initialize email service with configuration
    public function __construct()
    {
        $this->tokenFile = __DIR__ . '/tokens.json';
        $this->fromEmail = defined('EMAIL_FROM') ? EMAIL_FROM : '';
        $this->fromName = defined('EMAIL_FROM_NAME') ? EMAIL_FROM_NAME : 'TravelMates Hotel';
        
        $this->initDatabase();
    }

    //Initialize database connection
    private function initDatabase()
    {
        $dbHost = defined('EMAIL_DB_HOST') ? EMAIL_DB_HOST : 'localhost';
        $dbUser = defined('EMAIL_DB_USER') ? EMAIL_DB_USER : 'root';
        $dbPass = defined('EMAIL_DB_PASS') ? EMAIL_DB_PASS : '';
        $dbName = defined('EMAIL_DB_NAME') ? EMAIL_DB_NAME : 'travelMates';

        $this->conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

        if ($this->conn->connect_error) {
            throw new Exception('Database connection failed: ' . $this->conn->connect_error);
        }

        $this->conn->set_charset('utf8mb4');
    }

    //Get valid access token, refreshing if necessary
    private function getAccessToken()
    {
        if (!file_exists($this->tokenFile)) {
            throw new Exception('Gmail tokens not found. Please connect Gmail account first.');
        }

        $tokens = json_decode(file_get_contents($this->tokenFile), true);
        
        // check if expired (subtract 60s buffer)
        if (time() >= ($tokens['created'] + $tokens['expires_in'] - 60)) {
            if (empty($tokens['refresh_token'])) {
                throw new Exception('Access token expired and no refresh token available. Please reconnect Gmail.');
            }
            return $this->refreshAccessToken($tokens['refresh_token']);
        }

        return $tokens['access_token'];
    }

    //Refresh OAuth access token
    private function refreshAccessToken($refreshToken)
    {
        $url = 'https://oauth2.googleapis.com/token';
        $params = [
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('Failed to refresh token: ' . $response);
        }

        $data = json_decode($response, true);
        
        // Update token file
        $tokens = json_decode(file_get_contents($this->tokenFile), true);
        $tokens['access_token'] = $data['access_token'];
        $tokens['expires_in'] = $data['expires_in'];
        $tokens['created'] = time();
        
        file_put_contents($this->tokenFile, json_encode($tokens));

        return $data['access_token'];
    }

    //Make authenticated request to Gmail API
    private function gmailRequest($endpoint, $method = 'GET', $data = null)
    {
        $accessToken = $this->getAccessToken();
        $url = 'https://gmail.googleapis.com/gmail/v1/users/me/' . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            throw new Exception('Gmail API Error (' . $httpCode . '): ' . $response);
        }

        return json_decode($response, true);
    }

    //Send an email using Gmail API
    public function sendEmail($to, $subject, $body, $options = [])
    {
        try {
            $boundary = md5(time());
            
            // Headers
            $headers = [
                "MIME-Version: 1.0",
                "To: $to",
                "Subject: $subject",
                "From: {$this->fromName} <{$this->fromEmail}>",
                "Content-Type: multipart/alternative; boundary=\"$boundary\""
            ];

            if (!empty($options['cc'])) $headers[] = "Cc: " . $options['cc'];
            if (!empty($options['bcc'])) $headers[] = "Bcc: " . $options['bcc'];
            if (!empty($options['replyTo'])) $headers[] = "Reply-To: " . $options['replyTo'];
            if (!empty($options['inReplyTo'])) $headers[] = "In-Reply-To: " . $options['inReplyTo'];
            if (!empty($options['references'])) $headers[] = "References: " . $options['references'];

            // Body
            $message = implode("\r\n", $headers) . "\r\n\r\n";
            $message .= "--$boundary\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
            $message .= strip_tags($body) . "\r\n\r\n";
            $message .= "--$boundary\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
            $message .= $body . "\r\n\r\n";
            $message .= "--$boundary--";

            // Base64 URL Safe Encoding
            $rawMessage = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($message));

            $result = $this->gmailRequest('messages/send', 'POST', ['raw' => $rawMessage]);
            
            // Log success
            $this->logEmail([
                'direction' => 'outbound',
                'from_email' => $this->fromEmail,
                'to_email' => $to,
                'subject' => $subject,
                'body' => $body,
                'status' => 'sent',
                'message_id' => $result['id']
            ]);

            return ['success' => true, 'message_id' => $result['id']];

        } catch (Exception $e) {
            $this->logEmail([
                'direction' => 'outbound',
                'from_email' => $this->fromEmail,
                'to_email' => $to,
                'subject' => $subject,
                'body' => $body,
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    //Fetch emails from Gmail API
    public function fetchEmails($folder = 'INBOX', $limit = 50, $offset = 0)
    {
        try {
            // List messages
            $query = $folder === 'INBOX' ? 'label:INBOX' : '';
            $list = $this->gmailRequest("messages?maxResults=$limit&q=$query");
            
            if (empty($list['messages'])) {
                return ['success' => true, 'emails' => [], 'total' => 0];
            }

            $emails = [];
            foreach ($list['messages'] as $msg) {
                // Check if already synced
                if ($this->emailExists($msg['id'])) continue;

                // Get full message details
                $details = $this->gmailRequest("messages/{$msg['id']}");
                $parsed = $this->parseGmailMessage($details);
                
                $emails[] = $parsed;
                $this->syncEmailToDatabase($parsed, $folder);
            }

            return ['success' => true, 'emails' => $emails, 'total' => count($emails)];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    //Parse Gmail API message response
    private function parseGmailMessage($message)
    {
        $headers = [];
        foreach ($message['payload']['headers'] as $header) {
            $headers[$header['name']] = $header['value'];
        }

        $body = $this->getBodyFromPayload($message['payload']);

        return [
            'id' => $message['id'],
            'message_id' => $message['id'], // Gmail ID is unique
            'thread_id' => $message['threadId'],
            'from' => $headers['From'] ?? '',
            'to' => $headers['To'] ?? '',
            'subject' => $headers['Subject'] ?? '(No Subject)',
            'date' => date('Y-m-d H:i:s', strtotime($headers['Date'] ?? 'now')),
            'snippet' => $message['snippet'],
            'body' => $body,
            'seen' => !in_array('UNREAD', $message['labelIds'])
        ];
    }

    //Extract body from payload (recursive)
    private function getBodyFromPayload($payload)
    {
        // Direct body data
        if (isset($payload['body']['data']) && !empty($payload['body']['data'])) {
            return base64_decode(str_replace(['-', '_'], ['+', '/'], $payload['body']['data']));
        }

        if (isset($payload['parts']) && is_array($payload['parts'])) {
            $htmlBody = '';
            $plainBody = '';
            
            foreach ($payload['parts'] as $part) {
                $mimeType = $part['mimeType'] ?? '';
                
                // Check for nested multipart
                if (strpos($mimeType, 'multipart/') === 0 && isset($part['parts'])) {
                    $nestedBody = $this->getBodyFromPayload($part);
                    if (!empty($nestedBody)) {
                        return $nestedBody;
                    }
                }
                
                // Get HTML body
                if ($mimeType === 'text/html' && isset($part['body']['data']) && !empty($part['body']['data'])) {
                    $htmlBody = base64_decode(str_replace(['-', '_'], ['+', '/'], $part['body']['data']));
                }
                
                // Get plain text body
                if ($mimeType === 'text/plain' && isset($part['body']['data']) && !empty($part['body']['data'])) {
                    $plainBody = base64_decode(str_replace(['-', '_'], ['+', '/'], $part['body']['data']));
                }
                
                // Recurse into nested parts
                if (isset($part['parts']) && is_array($part['parts'])) {
                    $nestedBody = $this->getBodyFromPayload($part);
                    if (!empty($nestedBody)) {
                        return $nestedBody;
                    }
                }
            }
            
            // Prefer HTML over plain text
            if (!empty($htmlBody)) {
                return $htmlBody;
            }
            if (!empty($plainBody)) {
                return nl2br(htmlspecialchars($plainBody));
            }
        }
        
        return '';
    }

    //Check if email exists in DB
    private function emailExists($messageId)
    {
        $stmt = $this->conn->prepare("SELECT id FROM email_logs WHERE message_id = ?");
        $stmt->bind_param('s', $messageId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    //Get single email (from DB or API)
    public function getEmail($emailId, $folder = 'INBOX')
    {
        // Try DB first
        $filters = ['id' => $emailId];
        $result = $this->getEmailsFromDatabase($filters, 1, 0);
        
        if ($result['success'] && !empty($result['emails'])) {
            $this->markAsRead($emailId);
            return ['success' => true, 'email' => $result['emails'][0]];
        }

        return ['success' => false, 'error' => 'Email not found'];
    }

    //Reply to an email
    public function replyToEmail($originalEmailId, $replyBody, $folder = 'INBOX')
    {
        $originalResult = $this->getEmail($originalEmailId);
        if (!$originalResult['success']) {
            return $originalResult;
        }
        
        $original = $originalResult['email'];
        
        // Build reply subject
        $subject = $original['subject'];
        if (stripos($subject, 'Re:') !== 0) {
            $subject = 'Re: ' . $subject;
        }

        // Build reply body
        $replyHtml = $this->buildReplyHtml($replyBody, $original);

        // Send reply
        $result = $this->sendEmail(
            $original['from_email'], // Use from_email from DB
            $subject,
            $replyHtml,
            [
                'inReplyTo' => $original['message_id'],
                'references' => $original['message_id']
            ]
        );

        if ($result['success']) {
            $this->logEmailReply($originalEmailId, $replyBody);
        }

        return $result;
    }

    //Send booking receipt email
    public function sendBookingReceipt($booking)
    {
        $to = $booking['email'];
        $subject = "Booking Confirmation - Reservation #" . $booking['bookingID'];
        $body = $this->generateReceiptHtml($booking);
        
        return $this->sendEmail($to, $subject, $body);
    }

    //Get email statistics
    public function getStatistics()
    {
        $stats = [
            'total_sent' => 0,
            'total_received' => 0,
            'unread' => 0,
            'sent_today' => 0,
            'received_today' => 0,
            'failed' => 0
        ];

        try {
            // Total sent
            $result = $this->conn->query("SELECT COUNT(*) as count FROM email_logs WHERE direction = 'outbound' AND status = 'sent'");
            if ($row = $result->fetch_assoc()) {
                $stats['total_sent'] = (int)$row['count'];
            }

            // Total received
            $result = $this->conn->query("SELECT COUNT(*) as count FROM email_logs WHERE direction = 'inbound'");
            if ($row = $result->fetch_assoc()) {
                $stats['total_received'] = (int)$row['count'];
            }

            // Unread
            $result = $this->conn->query("SELECT COUNT(*) as count FROM email_logs WHERE direction = 'inbound' AND is_read = 0");
            if ($row = $result->fetch_assoc()) {
                $stats['unread'] = (int)$row['count'];
            }

            // Sent today
            $result = $this->conn->query("SELECT COUNT(*) as count FROM email_logs WHERE direction = 'outbound' AND DATE(created_at) = CURDATE()");
            if ($row = $result->fetch_assoc()) {
                $stats['sent_today'] = (int)$row['count'];
            }

            // Received today
            $result = $this->conn->query("SELECT COUNT(*) as count FROM email_logs WHERE direction = 'inbound' AND DATE(created_at) = CURDATE()");
            if ($row = $result->fetch_assoc()) {
                $stats['received_today'] = (int)$row['count'];
            }

            // Failed
            $result = $this->conn->query("SELECT COUNT(*) as count FROM email_logs WHERE status = 'failed'");
            if ($row = $result->fetch_assoc()) {
                $stats['failed'] = (int)$row['count'];
            }

        } catch (Exception $e) {
            error_log('Email stats error: ' . $e->getMessage());
        }

        return $stats;
    }

    //Get emails from database with filters
    public function getEmailsFromDatabase($filters = [], $limit = 50, $offset = 0)
    {
        $where = [];
        $params = [];
        $types = '';

        if (!empty($filters['id'])) {
            $where[] = 'id = ?';
            $params[] = $filters['id'];
            $types .= 'i';
        }

        if (!empty($filters['direction'])) {
            $where[] = 'direction = ?';
            $params[] = $filters['direction'];
            $types .= 's';
        }

        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
            $types .= 's';
        }

        if (isset($filters['is_read'])) {
            $where[] = 'is_read = ?';
            $params[] = (int)$filters['is_read'];
            $types .= 'i';
        }

        if (!empty($filters['search'])) {
            $where[] = '(subject LIKE ? OR from_email LIKE ? OR to_email LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= 'sss';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM email_logs $whereClause";
        $stmt = $this->conn->prepare($countQuery);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];

        // Get emails
        $query = "SELECT * FROM email_logs $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $emails = [];
        while ($row = $result->fetch_assoc()) {
            $emails[] = $row;
        }

        return [
            'success' => true,
            'emails' => $emails,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    //Mark email as read
    public function markAsRead($emailId)
    {
        $stmt = $this->conn->prepare("UPDATE email_logs SET is_read = 1 WHERE id = ?");
        $stmt->bind_param('i', $emailId);
        return $stmt->execute();
    }

    //Log email to database
    private function logEmail($data)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO email_logs (direction, from_email, to_email, subject, body, status, message_id, error_message, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        
        // Ensure all values are variables (bind_param requires references)
        $direction = $data['direction'] ?? '';
        $fromEmail = $data['from_email'] ?? '';
        $toEmail = $data['to_email'] ?? '';
        $subject = $data['subject'] ?? '';
        $body = $data['body'] ?? '';
        $status = $data['status'] ?? '';
        $messageId = $data['message_id'] ?? '';
        $errorMessage = $data['error_message'] ?? null;
        
        $stmt->bind_param(
            'ssssssss',
            $direction,
            $fromEmail,
            $toEmail,
            $subject,
            $body,
            $status,
            $messageId,
            $errorMessage
        );
        
        return $stmt->execute();
    }

    //Sync fetched email to database
    private function syncEmailToDatabase($email, $folder)
    {
        // Check if already exists
        $stmt = $this->conn->prepare("SELECT id FROM email_logs WHERE message_id = ?");
        $stmt->bind_param('s', $email['message_id']);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            return; // Already synced
        }
        
        $direction = 'inbound';
        $status = 'received';
        $isRead = $email['seen'] ? 1 : 0;
        
        // Use body or snippet as fallback
        $bodyContent = !empty($email['body']) ? $email['body'] : ($email['snippet'] ?? '');
        
        $stmt = $this->conn->prepare(
            "INSERT INTO email_logs (direction, from_email, to_email, subject, body, status, message_id, is_read, folder, email_date, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        
        $stmt->bind_param(
            'sssssssiss',
            $direction,
            $email['from'],
            $email['to'],
            $email['subject'],
            $bodyContent,
            $status,
            $email['message_id'],
            $isRead,
            $folder,
            $email['date']
        );
        
        $stmt->execute();
    }

    //Log email reply
    private function logEmailReply($originalEmailId, $replyBody)
    {
        $stmt = $this->conn->prepare(
            "INSERT INTO email_replies (original_email_id, reply_body, created_at) VALUES (?, ?, NOW())"
        );
        $stmt->bind_param('is', $originalEmailId, $replyBody);
        return $stmt->execute();
    }

    //Generate booking receipt HTML
    private function generateReceiptHtml($booking)
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #1a1a2e, #16213e); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
                .booking-details { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
                .detail-label { font-weight: bold; color: #666; }
                .total { font-size: 24px; color: #1a1a2e; text-align: right; margin-top: 20px; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>TravelMates Hotel</h1>
                    <p>Booking Confirmation</p>
                </div>
                <div class="content">
                    <p>Dear ' . htmlspecialchars($booking['customerName'] ?? $booking['firstName'] ?? 'Guest') . ',</p>
                    <p>Thank you for your booking! Your reservation has been confirmed.</p>
                    
                    <div class="booking-details">
                        <h3>Booking Details</h3>
                        <div class="detail-row">
                            <span class="detail-label">Booking ID:</span>
                            <span>#' . htmlspecialchars($booking['bookingID']) . '</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Room:</span>
                            <span>' . htmlspecialchars($booking['roomName'] ?? 'N/A') . '</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Check-in:</span>
                            <span>' . htmlspecialchars($booking['checkInDate'] ?? 'N/A') . '</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Check-out:</span>
                            <span>' . htmlspecialchars($booking['checkOutDate'] ?? 'N/A') . '</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Guests:</span>
                            <span>' . htmlspecialchars($booking['numberOfGuests'] ?? '1') . '</span>
                        </div>
                        <div class="total">
                            Total: ₱' . number_format($booking['totalPrice'] ?? 0, 2) . '
                        </div>
                    </div>
                    
                    <p>If you have any questions, please reply to this email or contact us.</p>
                    <p>We look forward to welcoming you!</p>
                </div>
                <div class="footer">
                    <p>TravelMates Hotel | Your comfort is our priority</p>
                    <p>This is an automated email. Please do not reply directly unless you have questions.</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }

    //Build reply HTML with quoted original message
    private function buildReplyHtml($replyBody, $original)
    {
        // Handle different key names from database vs API
        $originalDate = $original['date'] ?? $original['email_date'] ?? $original['created_at'] ?? '';
        $originalFrom = $original['from'] ?? $original['from_email'] ?? '';
        $originalFromName = $original['from_name'] ?? $originalFrom;
        $originalBody = $original['body'] ?? '';
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .reply { margin-bottom: 20px; }
                .signature { color: #666; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; }
                .quoted { border-left: 3px solid #ccc; padding-left: 15px; margin-top: 20px; color: #666; }
            </style>
        </head>
        <body>
            <div class="reply">
                ' . nl2br(htmlspecialchars($replyBody)) . '
            </div>
            <div class="signature">
                <p>Best regards,<br>TravelMates Hotel Team</p>
            </div>
            <div class="quoted">
                <p><strong>On ' . htmlspecialchars($originalDate) . ', ' . htmlspecialchars($originalFromName) . ' wrote:</strong></p>
                <p>' . $originalBody . '</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }

    //Destructor - Close database connection
    public function __destruct()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
