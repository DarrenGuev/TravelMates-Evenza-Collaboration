<?php
require_once __DIR__ . '/config.php';

class IprogSms
{
    private $apiUrl;
    private $apiToken;
    private $lastError;
    private $lastResponse;

    public function __construct()
    {
        $this->apiUrl = IPROG_SMS_API_URL;
        $this->apiToken = IPROG_SMS_API_TOKEN;
        $this->lastError = null;
        $this->lastResponse = null;
    }

    public function validateConnection()
    {
        if (empty($this->apiToken) || $this->apiToken === 'your_api_token_here') {
            $this->lastError = 'API token is not configured';
            return false;
        }

        if (!function_exists('curl_init')) {
            $this->lastError = 'cURL extension is not enabled';
            return false;
        }

        return true;
    }

    public function sendSms($phoneNumber, $message)
    {
        // Validate connection first
        if (!$this->validateConnection()) {
            return [
                'success' => false,
                'error' => $this->lastError,
                'status' => 'failed'
            ];
        }

        // Validate phone number
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);
        if (!$this->validatePhoneNumber($phoneNumber)) {
            return [
                'success' => false,
                'error' => 'Invalid phone number format',
                'status' => 'failed'
            ];
        }

        // Validate message
        if (empty(trim($message))) {
            return [
                'success' => false,
                'error' => 'Message cannot be empty',
                'status' => 'failed'
            ];
        }

        // Prepare data for API
        $data = [
            'api_token' => $this->apiToken,
            'message' => $message,
            'phone_number' => $phoneNumber
        ];

        // Initialize cURL
        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Handle cURL errors
        if ($curlError) {
            $this->lastError = 'Network error: ' . $curlError;
            return [
                'success' => false,
                'error' => $this->lastError,
                'status' => 'failed',
                'http_code' => $httpCode
            ];
        }

        // Parse response
        $this->lastResponse = $response;
        $responseData = json_decode($response, true);

        // Check for successful response
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'response' => $responseData,
                'raw_response' => $response,
                'status' => 'sent',
                'http_code' => $httpCode
            ];
        }

        // Handle error response
        $errorMessage = isset($responseData['message']) ? $responseData['message'] : 'Unknown API error';
        $this->lastError = $errorMessage;

        return [
            'success' => false,
            'error' => $errorMessage,
            'response' => $responseData,
            'raw_response' => $response,
            'status' => 'failed',
            'http_code' => $httpCode
        ];
    }

    public function formatPhoneNumber($phoneNumber)
    {
        $phoneNumber = preg_replace('/[^\d+]/', '', $phoneNumber);
        $phoneNumber = ltrim($phoneNumber, '+');
        if (preg_match('/^0?9\d{9}$/', $phoneNumber)) {
            $phoneNumber = '63' . ltrim($phoneNumber, '0');
        } elseif (preg_match('/^63\d{10}$/', $phoneNumber)) {
            // already in correct format
        }

        return $phoneNumber;
    }

    public function validatePhoneNumber($phoneNumber)
    {
        if (!preg_match('/^\d{10,15}$/', $phoneNumber)) {
            return false;
        }

        return true;
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    public function getLastResponse()
    {
        return $this->lastResponse;
    }
}
