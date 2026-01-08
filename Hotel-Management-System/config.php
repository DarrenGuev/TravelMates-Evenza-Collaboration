<?php
define('BASE_PATH', __DIR__);

define('BASE_URL', '/TravelMates-Evenza-Collaboration/Hotel-Management-System');

//define key directory paths
define('CLASSES_PATH', BASE_PATH . '/classes');
define('DBCONNECT_PATH', BASE_PATH . '/dbconnect');
define('FRONTEND_PATH', BASE_PATH . '/frontend');
define('ADMIN_PATH', BASE_PATH . '/admin');
define('INTEGRATIONS_PATH', BASE_PATH . '/integrations');
define('IMAGES_PATH', BASE_PATH . '/images');
define('CSS_PATH', BASE_PATH . '/css');
define('JS_PATH', BASE_PATH . '/js');

//include paths
define('INCLUDES_PATH', FRONTEND_PATH . '/includes');
define('ADMIN_INCLUDES_PATH', ADMIN_PATH . '/includes');

//integration paths
define('CHATBOT_PATH', INTEGRATIONS_PATH . '/chatbot');
define('GMAIL_PATH', INTEGRATIONS_PATH . '/gmail');
define('SMS_PATH', INTEGRATIONS_PATH . '/sms');
define('PAYPAL_PATH', INTEGRATIONS_PATH . '/paypal');
define('API_PATH', INTEGRATIONS_PATH . '/api');

//define web-accessible paths for assets
define('IMAGES_URL', BASE_URL . '/images');
define('CSS_URL', BASE_URL . '/css');
define('JS_URL', BASE_URL . '/js');
define('ADMIN_URL', BASE_URL . '/admin');
define('FRONTEND_URL', BASE_URL . '/frontend');
?>
