<?php
require_once __DIR__ . '/../../dbconnect/load_env.php';

if (!defined('PAYPAL_SANDBOX')) define('PAYPAL_SANDBOX', true);

$paypal_client_id = getenv('PAYPAL_CLIENT_ID');
$paypal_secret = getenv('PAYPAL_SECRET');

if (!defined('PAYPAL_CLIENT_ID')) define('PAYPAL_CLIENT_ID', $paypal_client_id);
if (!defined('PAYPAL_SECRET')) define('PAYPAL_SECRET', $paypal_secret);

if (!defined('PAYPAL_API_BASE')) {
    define('PAYPAL_API_BASE', PAYPAL_SANDBOX ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com');
}

function paypal_get_access_token() {
    $clientId = PAYPAL_CLIENT_ID;
    $secret = PAYPAL_SECRET;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYPAL_API_BASE . '/v1/oauth2/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_USERPWD, $clientId . ':' . $secret);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    // DNS resolution settings
    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    // Use IPv4 only to avoid IPv6 resolution issues
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

    $response = curl_exec($ch);
    $err = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) {
        return ['error' => true, 'message' => $err, 'status' => 500];
    }

    $data = json_decode($response, true);
    if (!$data || !isset($data['access_token'])) {
        return ['error' => true, 'message' => 'Unable to obtain access token', 'status' => $status, 'raw' => $data];
    }

    return ['error' => false, 'access_token' => $data['access_token'], 'expires_in' => $data['expires_in']];
}
