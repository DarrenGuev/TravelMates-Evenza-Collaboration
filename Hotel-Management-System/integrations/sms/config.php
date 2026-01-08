<?php
require_once __DIR__ . '/../../dbconnect/load_env.php';

define('IPROG_SMS_API_URL', 'https://www.iprogsms.com/api/v1/sms_messages');
define('IPROG_SMS_API_TOKEN', getenv('IPROG_SMS_API_TOKEN')); 

// Database configuration for SMS logs
define('SMS_DB_HOST', 'localhost');
define('SMS_DB_USER', 'root');
define('SMS_DB_PASS', '');
define('SMS_DB_NAME', 'travelMates');

// SMS Message Templates
define('SMS_TEMPLATE_BOOKING_APPROVED', 'Your Booking is Approved.');
define('SMS_TEMPLATE_BOOKING_CANCELLED', 'Your Booking is Cancelled.');
define('SMS_TEMPLATE_BOOKING_COMPLETED', 'Your Booking is Approved.');
