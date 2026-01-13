<?php
/**
 * PayPal Callback Handler
 * 
 * This page handles redirects from PayPal for cases where:
 * 1. User is redirected back from PayPal (redirect flow instead of popup)
 * 2. User lands here from an old bookmark or direct access
 * 
 * In the standard JavaScript SDK flow, payments are captured via API
 * and users are redirected directly to confirmation.php
 */

session_start();
require_once '../../../core/connect.php';
require_once '../../../config/paypal.php';

error_log('PayPal Callback reached - checking for pending payment');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in, redirecting to login');
    header('Location: ../../pages/login.php');
    exit;
}

if (isset($_SESSION['payment_success_token']) && isset($_SESSION['payment_success_time'])) {
    $successToken = $_SESSION['payment_success_token'];
    $transactionId = $_SESSION['payment_transaction_id'] ?? '';
    
    header('Location: ../../pages/confirmation.php?success=' . urlencode($successToken) . '&tx=' . urlencode($transactionId));
    exit;
}

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$payerId = isset($_GET['PayerID']) ? trim($_GET['PayerID']) : '';

if (!empty($token) && !empty($payerId)) {
    error_log('PayPal redirect flow detected - token: ' . $token . ', PayerID: ' . $payerId);
    
    
    $eventId = $_SESSION['paypal_order_event_id'] ?? ($_SESSION['pending_event_id'] ?? 0);
    $packageId = $_SESSION['paypal_order_package_id'] ?? ($_SESSION['pending_package_id'] ?? 0);
    $amount = $_SESSION['paypal_order_amount'] ?? ($_SESSION['pending_amount'] ?? 0);
    
    $accessToken = getPayPalAccessToken();
    
    if ($accessToken) {
        $ch = curl_init(getPayPalBaseUrl() . '/v2/checkout/orders/' . $token . '/capture');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $captureResult = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300 && isset($captureResult['status']) && $captureResult['status'] === 'COMPLETED') {
            $transactionId = '';
            if (isset($captureResult['purchase_units'][0]['payments']['captures'][0])) {
                $transactionId = $captureResult['purchase_units'][0]['payments']['captures'][0]['id'];
            }
            
            $successToken = bin2hex(random_bytes(32));
            
            $_SESSION['payment_success_token'] = $successToken;
            $_SESSION['payment_success_time'] = time();
            $_SESSION['payment_transaction_id'] = $transactionId;
            $_SESSION['payment_order_id'] = $token;
            $_SESSION['payment_payer_id'] = $payerId;
            $_SESSION['payment_reservation_id'] = 0;
            $_SESSION['payment_event_id'] = $eventId;
            $_SESSION['payment_package_id'] = $packageId;
            $_SESSION['payment_amount'] = $amount;
            
            unset($_SESSION['paypal_order_id']);
            unset($_SESSION['paypal_order_amount']);
            unset($_SESSION['paypal_order_event_id']);
            unset($_SESSION['paypal_order_package_id']);
            
            header('Location: ../../pages/confirmation.php?success=' . urlencode($successToken) . '&tx=' . urlencode($transactionId));
            exit;
        } else {
            error_log('PayPal capture failed: ' . $response);
            $_SESSION['error_message'] = 'Failed to complete payment. Please try again or contact support.';
            header('Location: ../../pages/payment.php?eventId=' . $eventId . '&error=capture_failed');
            exit;
        }
    } else {
        error_log('Failed to get PayPal access token');
        $_SESSION['error_message'] = 'Payment service unavailable. Please try again later.';
        header('Location: ../../pages/payment.php?error=auth_failed');
        exit;
    }
}

if (isset($_GET['cancelled']) || isset($_GET['cancel'])) {
    $eventId = $_SESSION['pending_event_id'] ?? 0;
    $packageId = $_SESSION['pending_package_id'] ?? 0;
    
    $_SESSION['error_message'] = 'Payment was cancelled. You can try again when ready.';
    header('Location: ../../pages/payment.php?eventId=' . $eventId . '&packageId=' . $packageId);
    exit;
}

$_SESSION['error_message'] = 'No payment in progress. Please start your reservation again.';
header('Location: ../../index.php');
exit;

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
?>
