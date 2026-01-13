<?php
define('PAYPAL_MODE', 'sandbox');

// Sandbox Credentials (for testing)
define('PAYPAL_SANDBOX_CLIENT_ID', 'AaeHtKZ9GgAxdL5naqWfmBftiK1UZ7oNoXmJyVFB4rWuDpln4eXWurl1aMhRxtmxZ57IewDj6DzHNc6D');
define('PAYPAL_SANDBOX_SECRET', 'ELAD1K3q4nLFCeKZdPBQJqOhwN1TYHF_9Sww0PjqCF14ydYdzi1lw-eSqC-y_6JYlCJzEqdplFoZq9X5');

// Live Credentials (for production)
define('PAYPAL_LIVE_CLIENT_ID', 'YOUR_LIVE_CLIENT_ID_HERE');
define('PAYPAL_LIVE_SECRET', 'YOUR_LIVE_SECRET_HERE');

function getPayPalClientId() {
    return PAYPAL_MODE === 'sandbox' ? PAYPAL_SANDBOX_CLIENT_ID : PAYPAL_LIVE_CLIENT_ID;
}

function getPayPalSecret() {
    return PAYPAL_MODE === 'sandbox' ? PAYPAL_SANDBOX_SECRET : PAYPAL_LIVE_SECRET;
}

function getPayPalBaseUrl() {
    return PAYPAL_MODE === 'sandbox' 
        ? 'https://api-m.sandbox.paypal.com' 
        : 'https://api-m.paypal.com';
}

define('PAYPAL_CURRENCY', 'PHP');
