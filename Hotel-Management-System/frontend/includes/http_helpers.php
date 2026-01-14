<?php
if (!function_exists('ajaxResponse')) {
    function ajaxResponse($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}

if (!function_exists('handleRedirectOrJson')) {
    function handleRedirectOrJson($message, $status = 400, $redirectTo = '../rooms.php', $isAjax = null)
    {
        if ($isAjax === null) {
            $isAjax = (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] == '1')
                || (!empty($GLOBALS['isAjax']));
        }

        if ($isAjax) {
            ajaxResponse(['success' => false, 'message' => $message], $status);
        } else {
            header("Location: {$redirectTo}?error=" . urlencode($message));
            exit();
        }
    }
}

if (!function_exists('sendJsonResponse')) {
    function sendJsonResponse($success, $message)
    {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit();
    }
}