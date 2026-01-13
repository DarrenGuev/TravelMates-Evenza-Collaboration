<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

ob_start();

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => 'A server error occurred']);
    exit;
}, E_ALL);

set_exception_handler(function($exception) {
    ob_end_clean();
    error_log('Uncaught Exception: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An unexpected error occurred']);
    exit;
});

session_start();
header('Content-Type: application/json');

try {
    require_once '../../../config/paypal.php';
    require_once '../../../core/connect.php';
    require_once '../../../includes/helpers.php';
    
    ob_clean();
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Server configuration error']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

$input = json_decode(file_get_contents('php://input'), true);
$orderId = isset($input['orderId']) ? trim($input['orderId']) : '';
$reservationId = isset($input['reservationId']) ? intval($input['reservationId']) : 0;

if (empty($orderId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID is required']);
    exit;
}

if (isset($_SESSION['paypal_order_id']) && $_SESSION['paypal_order_id'] !== $orderId) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID mismatch']);
    exit;
}

$accessToken = getPayPalAccessToken();
if (!$accessToken) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to authenticate with PayPal']);
    exit;
}

$ch = curl_init(getPayPalBaseUrl() . '/v2/checkout/orders/' . $orderId . '/capture');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, '');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $accessToken,
    'PayPal-Request-Id: ' . uniqid('evenza-capture-', true)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to communicate with PayPal']);
    exit;
}

$captureResult = json_decode($response, true);

try {
    if ($httpCode >= 200 && $httpCode < 300 && isset($captureResult['status']) && $captureResult['status'] === 'COMPLETED') {
        $transactionId = '';
        $payerId = $captureResult['payer']['payer_id'] ?? '';
        $payerEmail = $captureResult['payer']['email_address'] ?? '';
        
        if (isset($captureResult['purchase_units'][0]['payments']['captures'][0])) {
            $capture = $captureResult['purchase_units'][0]['payments']['captures'][0];
            $transactionId = $capture['id'];
        }
        
        $eventId = $_SESSION['paypal_order_event_id'] ?? 0;
        $packageId = $_SESSION['paypal_order_package_id'] ?? 0;
        $amount = $_SESSION['paypal_order_amount'] ?? 0;
        
        if ($reservationId <= 0) {
            $reservationId = $_SESSION['paypal_order_reservation_id'] ?? 0;
        }
        
        if ($reservationId > 0) {
            $checkCodeQuery = "SELECT reservationCode FROM reservations WHERE reservationId = ?";
            $checkCodeStmt = mysqli_prepare($conn, $checkCodeQuery);
            $existingCode = null;
            if ($checkCodeStmt) {
                mysqli_stmt_bind_param($checkCodeStmt, "i", $reservationId);
                mysqli_stmt_execute($checkCodeStmt);
                $checkCodeResult = mysqli_stmt_get_result($checkCodeStmt);
                if ($codeRow = mysqli_fetch_assoc($checkCodeResult)) {
                    $existingCode = $codeRow['reservationCode'];
                }
                mysqli_stmt_close($checkCodeStmt);
            }
            
            if (empty($existingCode)) {
                $reservationCode = generateUniqueReservationCode($conn);
                $updateQuery = "UPDATE reservations SET status = 'completed', reservationCode = ? WHERE reservationId = ? AND userId = ?";
                $updateStmt = mysqli_prepare($conn, $updateQuery);
                if ($updateStmt) {
                    mysqli_stmt_bind_param($updateStmt, "sii", $reservationCode, $reservationId, $userId);
                    mysqli_stmt_execute($updateStmt);
                    mysqli_stmt_close($updateStmt);
                }
            } else {
                $updateQuery = "UPDATE reservations SET status = 'completed' WHERE reservationId = ? AND userId = ?";
                $updateStmt = mysqli_prepare($conn, $updateQuery);
                if ($updateStmt) {
                    mysqli_stmt_bind_param($updateStmt, "ii", $reservationId, $userId);
                    mysqli_stmt_execute($updateStmt);
                    mysqli_stmt_close($updateStmt);
                }
            }
            
            $packageName = '';
            if ($packageId > 0) {
                $pkgQuery = "SELECT packageName FROM packages WHERE packageId = ?";
                $pkgStmt = mysqli_prepare($conn, $pkgQuery);
                if ($pkgStmt) {
                    mysqli_stmt_bind_param($pkgStmt, "i", $packageId);
                    mysqli_stmt_execute($pkgStmt);
                    $pkgResult = mysqli_stmt_get_result($pkgStmt);
                    if ($pkgRow = mysqli_fetch_assoc($pkgResult)) {
                        $packageName = $pkgRow['packageName'];
                    }
                    mysqli_stmt_close($pkgStmt);
                }
            }
            
            $paymentQuery = "INSERT INTO payments (reservationId, userId, transactionId, amount, packageName, paymentStatus) 
                             VALUES (?, ?, ?, ?, ?, 'completed')";
            $paymentStmt = mysqli_prepare($conn, $paymentQuery);
            if ($paymentStmt) {
                mysqli_stmt_bind_param($paymentStmt, "iisds", $reservationId, $userId, $transactionId, $amount, $packageName);
                mysqli_stmt_execute($paymentStmt);
                mysqli_stmt_close($paymentStmt);
            }
            
            try {
                sendReservationConfirmationEmail($conn, $reservationId);
            } catch (Exception $emailException) {
                error_log("Email sending failed: " . $emailException->getMessage());
            }
        }
        
        $successToken = bin2hex(random_bytes(32));
        
        $_SESSION['payment_success_token'] = $successToken;
        $_SESSION['payment_success_time'] = time();
        $_SESSION['payment_transaction_id'] = $transactionId;
        $_SESSION['payment_order_id'] = $orderId;
        $_SESSION['payment_payer_id'] = $payerId;
        $_SESSION['payment_payer_email'] = $payerEmail;
        $_SESSION['payment_reservation_id'] = $reservationId;
        $_SESSION['payment_event_id'] = $eventId;
        $_SESSION['payment_package_id'] = $packageId;
        $_SESSION['payment_amount'] = $amount;
        
        unset($_SESSION['paypal_order_id']);
        unset($_SESSION['paypal_order_amount']);
        unset($_SESSION['paypal_order_event_id']);
        unset($_SESSION['paypal_order_package_id']);
        unset($_SESSION['paypal_order_reservation_id']);
        
        ob_end_clean();
        echo json_encode([
            'status' => 'COMPLETED',
            'transactionId' => $transactionId,
            'redirectUrl' => '/evenza/user/pages/confirmation.php?success=' . urlencode($successToken) . '&tx=' . urlencode($transactionId)
        ]);
        exit;
    } else {
        ob_end_clean();
        error_log('PayPal Capture Error: ' . $response);
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to capture PayPal payment',
            'status' => $captureResult['status'] ?? 'UNKNOWN',
            'details' => $captureResult['details'] ?? null
        ]);
        exit;
    }
} catch (Exception $e) {
    ob_end_clean();
    error_log('PayPal Capture Exception: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'error' => 'An error occurred while processing your payment',
        'message' => 'Please contact support if this issue persists'
    ]);
    exit;
} catch (Error $e) {
    ob_end_clean();
    error_log('PayPal Capture Fatal Error: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'error' => 'A server error occurred',
        'message' => 'Please contact support if this issue persists'
    ]);
    exit;
}

function getPayPalAccessToken() {
    $clientId = getPayPalClientId();
    $secret = getPayPalSecret();
    
    $ch = curl_init(getPayPalBaseUrl() . '/v1/oauth2/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_USERPWD, $clientId . ':' . $secret);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }
    
    error_log('PayPal Auth Error: ' . $response);
    return null;
}
