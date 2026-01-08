<?php
session_start();

if (isset($_SESSION['userID']) && $_SESSION['role'] !== 'admin') {
    die('Access denied');
}

require_once __DIR__ . '/IprogSms.php';
require_once __DIR__ . '/SmsService.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IPROG SMS - Connection Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-chat-dots me-2"></i>IPROG SMS Connection Test</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        $smsGateway = new IprogSms();
                        
                        echo '<h5>Configuration Check</h5>';
                        echo '<ul class="list-group mb-4">';
                        
                        // Check API URL
                        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                        echo 'API URL';
                        echo '<span class="badge bg-success">Configured</span>';
                        echo '</li>';
                        
                        // Check API Token
                        $tokenConfigured = IPROG_SMS_API_TOKEN !== 'your_api_token_here' && !empty(IPROG_SMS_API_TOKEN);
                        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                        echo 'API Token';
                        echo '<span class="badge ' . ($tokenConfigured ? 'bg-success' : 'bg-danger') . '">';
                        echo $tokenConfigured ? 'Configured' : 'Not Configured';
                        echo '</span>';
                        echo '</li>';
                        
                        // Check cURL
                        $curlEnabled = function_exists('curl_init');
                        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                        echo 'cURL Extension';
                        echo '<span class="badge ' . ($curlEnabled ? 'bg-success' : 'bg-danger') . '">';
                        echo $curlEnabled ? 'Enabled' : 'Disabled';
                        echo '</span>';
                        echo '</li>';
                        
                        // Check Database
                        try {
                            $smsService = new SmsService();
                            $dbConnected = true;
                        } catch (Exception $e) {
                            $dbConnected = false;
                        }
                        echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                        echo 'Database Connection';
                        echo '<span class="badge ' . ($dbConnected ? 'bg-success' : 'bg-danger') . '">';
                        echo $dbConnected ? 'Connected' : 'Failed';
                        echo '</span>';
                        echo '</li>';
                        
                        echo '</ul>';
                        
                        // Validate connection
                        $isValid = $smsGateway->validateConnection();
                        
                        if ($isValid && $tokenConfigured) {
                            echo '<div class="alert alert-success">';
                            echo '<strong>✓ Gateway Ready!</strong> Your IPROG SMS configuration appears to be correct.';
                            echo '</div>';
                        } else {
                            echo '<div class="alert alert-warning">';
                            echo '<strong>⚠ Configuration Needed</strong><br>';
                            echo 'Please update your API token in <code>integrations/sms/config.php</code>';
                            echo '</div>';
                        }
                        ?>
                        
                        <hr>
                        
                        <h5>Send Test SMS</h5>
                        <form method="POST" class="mt-3">
                            <div class="mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="test_phone" class="form-control" 
                                       placeholder="e.g., 09171234567 or 639171234567" required>
                                <div class="form-text">Enter a Philippine mobile number</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Test Message</label>
                                <textarea name="test_message" class="form-control" rows="3" required>This is a test message from TravelMates Hotel SMS System.</textarea>
                            </div>
                            <button type="submit" name="send_test" class="btn btn-primary">
                                <i class="bi bi-send me-2"></i>Send Test SMS
                            </button>
                        </form>
                        
                        <?php
                        if (isset($_POST['send_test'])) {
                            $testPhone = trim($_POST['test_phone']);
                            $testMessage = trim($_POST['test_message']);
                            
                            echo '<div class="mt-4">';
                            echo '<h5>Test Result</h5>';
                            
                            $result = $smsGateway->sendSms($testPhone, $testMessage);
                            
                            if ($result['success']) {
                                echo '<div class="alert alert-success">';
                                echo '<strong>✓ SMS Sent Successfully!</strong><br>';
                                echo 'The test message was sent to ' . htmlspecialchars($testPhone);
                                echo '</div>';
                            } else {
                                echo '<div class="alert alert-danger">';
                                echo '<strong>✗ SMS Failed</strong><br>';
                                echo 'Error: ' . htmlspecialchars($result['error']);
                                echo '</div>';
                            }
                            
                            echo '<pre class="bg-dark text-light p-3 rounded mt-3" style="font-size: 12px;">';
                            echo htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT));
                            echo '</pre>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <a href="<?php echo ADMIN_URL; ?>/smsDashboard.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to SMS Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</body>
</html>
