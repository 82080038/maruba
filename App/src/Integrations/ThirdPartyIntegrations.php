<?php
namespace App\Integrations;

use App\Database;

/**
 * Third-Party Integrations Manager
 * Handles connections to external services
 */

class ThirdPartyIntegrations
{
    private $pdo;
    private $integrations = [];
    
    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->loadIntegrations();
    }
    
    /**
     * Load integration configurations
     */
    private function loadIntegrations(): void
    {
        $this->integrations = [
            'payment_gateway' => [
                'midtrans' => [
                    'api_key' => $_ENV['MIDTRANS_API_KEY'] ?? '',
                    'merchant_id' => $_ENV['MIDTRANS_MERCHANT_ID'] ?? '',
                    'server_url' => $_ENV['MIDTRANS_SERVER_URL'] ?? 'https://api.sandbox.midtrans.com',
                    'environment' => $_ENV['MIDTRANS_ENVIRONMENT'] ?? 'sandbox'
                ],
                'xendit' => [
                    'api_key' => $_ENV['XENDIT_API_KEY'] ?? '',
                    'secret_key' => $_ENV['XENDIT_SECRET_KEY'] ?? '',
                    'server_url' => $_ENV['XENDIT_SERVER_URL'] ?? 'https://api.xendit.co.id',
                    'environment' => $_ENV['XENDIT_ENVIRONMENT'] ?? 'sandbox'
                ],
                'gopay' => [
                    'merchant_code' => $_ENV['GOPAY_MERCHANT_CODE'] ?? '',
                    'client_key' => $_ENV['GOPAY_CLIENT_KEY'] ?? '',
                    'server_url' => $_ENV['GOPAY_SERVER_URL'] ?? 'https://api.gopay.co.id',
                    'environment' => $_ENV['GOPAY_ENVIRONMENT'] ?? 'sandbox'
                ],
                'ovo' => [
                    'app_key' => $_ENV['OVO_APP_KEY'] ?? '',
                    'server_url' => $_ENV['OVO_SERVER_URL'] ?? 'https://api.ovo.id',
                    'environment' => $_ENV['OVO_ENVIRONMENT'] ?? 'sandbox'
                ],
                'doku' => [
                    'api_key' => $_ENV['DOKU_API_KEY'] ?? '',
                    'secret_key' => $_ENV['DOKU_SECRET_KEY'] ?? '',
                    'server_url' => $_ENV['DOKU_SERVER_URL'] ?? 'https://api.doku.com',
                    'environment' => $_ENV['DOKU_ENVIRONMENT'] ?? 'sandbox'
                ]
            ],
            'notification' => [
                'firebase' => [
                    'server_key' => $_ENV['FIREBASE_SERVER_KEY'] ?? '',
                    'database_url' => $_ENV['FIREBASE_DATABASE_URL'] ?? '',
                    'realtime_url' => $_ENV['FIREBASE_REALTIME_URL'] ?? '',
                    'environment' => $_ENV['FIREBASE_ENVIRONMENT'] ?? 'development'
                ],
                'twilio' => [
                    'account_sid' => $_ENV['TWILIO_ACCOUNT_SID'] ?? '',
                    'auth_token' => $_ENV['TWILIO_AUTH_TOKEN'] ?? '',
                    'phone_number' => $_ENV['TWILIO_PHONE_NUMBER'] ?? '',
                    'whatsapp_api_url' => $_ENV['TWILIO_WHATSAPP_API_URL'] ?? ''
                ],
                'email' => [
                    'smtp_host' => $_ENV['SMTP_HOST'] ?? 'localhost',
                    'smtp_port' => $_ENV['SMTP_PORT'] ?? 587,
                    'smtp_username' => $_ENV['SMTP_USERNAME'] ?? '',
                    'smtp_password' => $_ENV['SMTP_PASSWORD'] ?? '',
                    'encryption' => $_ENV['SMTP_ENCRYPTION'] ?? 'tls'
                ]
            ],
            'analytics' => [
                'google_analytics' => [
                    'tracking_id' => $_ENV['GA_TRACKING_ID'] ?? '',
                    'measurement_id' => $_ENV['GA_MEASUREMENT_ID'] ?? '',
                    'api_secret' => $_ENV['GA_API_SECRET'] ?? ''
                ],
                'mixpanel' => [
                    'token' => $_ENV['MIXPANEL_TOKEN'] ?? '',
                    'project_id' => $_ENV['MIXPANEL_PROJECT_ID'] ?? ''
                ]
            ],
            'storage' => [
                'aws_s3' => [
                    'access_key' => $_ENV['AWS_ACCESS_KEY'] ?? '',
                    'secret_key' => $_ENV['AWS_SECRET_KEY'] ?? '',
                    'region' => $_ENV['AWS_REGION'] ?? 'ap-southeast-1',
                    'bucket' => $_ENV['AWS_S3_BUCKET'] ?? 'maruba-uploads'
                ],
                'google_drive' => [
                    'client_id' => $_ENV['GOOGLE_CLIENT_ID'] ?? '',
                    'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? '',
                    'redirect_uri' => $_ENV['GOOGLE_REDIRECT_URI'] ?? ''
                ]
            ]
        ];
    }
    
    /**
     * Get integration by name
     */
    public function getIntegration(string $name): ?array
    {
        return $this->integrations[$name] ?? null;
    }
    
    /**
     * Process payment via Midtrans
     */
    public function processMidtransPayment(array $paymentData): array
    {
        $integration = $this->getIntegration('payment_gateway')['midtrans'];
        
        if (!$integration || !$integration['api_key']) {
            throw new \Exception('Midtrans integration not configured');
        }
        
        // Prepare API request
        $apiUrl = $integration['server_url'] . '/v2/charge';
        $headers = [
            'Authorization: Basic ' . base64_encode($integration['api_key'] . ':' . $integration['merchant_id']),
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        $payload = [
            'payment_type' => $paymentData['payment_type'],
            'transaction_id' => $paymentData['transaction_id'],
            'amount' => $paymentData['amount'],
            'customer' => [
                'first_name' => $paymentData['customer']['first_name'],
                'last_name' => $paymentData['customer']['last_name'],
                'email' => $paymentData['customer']['email'],
                'phone' => $paymentData['customer']['phone']
            ],
            'item_details' => $paymentData['item_details'],
            'customer_details' => [
                'first_name' => $paymentData['customer']['first_name'],
                'last_name' => $paymentData['customer']['last_name'],
                'email' => $paymentData['customer']['email'],
                'phone' => $paymentData['customer']['phone']
            ],
            'expiry_time' => date('Y-m-d H:i:s', strtotime('+24 hours'))
        ];
        
        // Send request
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('Midtrans API error: ' . $error);
        }
        
        $responseData = json_decode($response, true);
        
        if ($httpCode !== 200) {
            throw new \Exception('Midtrans API error: HTTP ' . $httpCode);
        }
        
        return $responseData;
    }
    
    /**
     * Process payment via GoPay
     */
    public function processGoPayPayment(array $paymentData): array
    {
        $integration = $this->getIntegration('payment_gateway')['gopay'];
        
        if (!$integration || !$integration['merchant_code']) {
            throw new \Exception('GoPay integration not configured');
        }
        
        // Generate signature
        $signature = $this->generateGoPaySignature($paymentData, $integration);
        
        // Prepare API request
        $apiUrl = $integration['server_url'] . '/v2/charge';
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-API-Key: ' . $integration['client_key'],
            'X-Signature: ' . $signature
        ];
        
        $payload = [
            'transaction_details' => [
                'order_id' => $paymentData['transaction_id'],
                'amount' => [
                    'value' => $paymentData['amount'],
                    'currency' => 'IDR'
                ]
            ],
            'customer_details' => [
                'first_name' => $paymentData['customer']['first_name'],
                'last_name' => $paymentData['customer']['last_name'],
                'email' => $paymentData['customer']['email'],
                'phone' => $paymentData['customer']['phone']
            ],
            'item_details' => $paymentData['item_details'],
            'payment_type' => $paymentData['payment_type'],
            'callback_url' => $paymentData['callback_url'] ?? '',
            'expiry_time' => date('Y-m-d H:i:s', strtotime('+24 hours'))
        ];
        
        // Send request
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('GoPay API error: ' . $error);
        }
        
        $responseData = json_decode($response, true);
        
        if ($httpCode !== 200) {
            throw new \Exception('GoPay API error: HTTP ' . $httpCode);
        }
        
        return $responseData;
    }
    
    /**
     * Generate GoPay signature
     */
    private function generateGoPaySignature(array $data, array $integration): string
    {
        $apiKey = $integration['client_key'];
        $dataString = json_encode($data);
        
        return hash('sha512', $dataString . $apiKey);
    }
    
    /**
     * Send SMS via Twilio
     */
    public function sendSMS(string $to, string $message): array
    {
        $integration = $this->getIntegration('notification')['twilio'];
        
        if (!$integration || !$integration['auth_token']) {
            throw new \Exception('Twilio integration not configured');
        }
        
        $apiUrl = $integration['whatsapp_api_url'];
        $headers = [
            'Authorization: Bearer ' . $integration['auth_token'],
            'Content-Type: application/x-www-form-urlencoded'
        ];
        
        $data = [
            'To' => $to,
            'Body' => $message
        ];
        
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('Twilio API error: ' . $error);
        }
        
        return [
            'status' => $httpCode === 201 ? 'sent' : 'failed',
            'http_code' => $httpCode,
            'error' => $error
        ];
    }
    
    /**
     * Send email via SMTP
     */
    public function sendEmail(string $to, string $subject, string $body, array $attachments = []): array
    {
        $integration = $this->getIntegration('email');
        
        $headers = [
            'From: ' . $_ENV['SMTP_FROM'] ?? 'noreply@maruba.com',
            'Content-Type: text/html; charset=utf-8',
            'MIME-Version: 1.0'
        ];
        
        $headers[] = 'To: ' . $to;
        $headers[] = 'Subject: ' . $subject;
        
        $emailBody = $body;
        
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                $emailBody .= "\n\nAttachment: " . $attachment;
            }
        }
        
        $headers[] = 'X-Mailer: ' . $_ENV['SMTP_FROM'] ?? 'noreply@maruba.com';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_MAIL, true);
        curl_setopt($ch, CURLOPT_MAIL, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $emailBody);
        
        $success = curl_exec($ch);
        curl_close($ch);
        
        return [
            'status' => $success ? 'sent' : 'failed',
            'to' => $to,
            'subject' => $subject
        ];
    }
    
    /**
     * Upload file to AWS S3
     */
    public function uploadToS3(string $filePath, string $key = null): array
    {
        $integration = $this->getIntegration('storage')['aws_s3'];
        
        if (!$integration || !$integration['access_key']) {
            throw new \Exception('AWS S3 integration not configured');
        }
        
        $key = $key ?: basename($filePath);
        $bucket = $integration['bucket'];
        $region = $integration['region'];
        
        $fileContent = file_get_contents($filePath);
        $contentType = mime_content_type($filePath);
        
        $apiUrl = "https://{$bucket}.s3-{$region}.amazonaws.com/{$key}";
        $headers = [
            'Content-Type: ' . $contentType,
            'Authorization: AWS4-HMAC SHA256',
            'x-amz-date: ' . gmdate('D, d M Y H:i:s', time()),
            'x-amz-content-sha256: ' . hash('sha256', $fileContent)
        ];
        
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_INFILE, $filePath);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('S3 upload error: ' . $error);
        }
        
        return [
            'status' => $httpCode === 200 ? 'uploaded' : 'failed',
            'file_path' => $key,
            'url' => $apiUrl,
            'http_code' => $httpCode,
            'error' => $error
        ];
    }
    
    /**
     * Download file from Google Drive
     */
    public function downloadFromGoogleDrive(string $fileId): array
    {
        $integration = $this->getIntegration('storage')['google_drive'];
        
        if (!$integration || !$integration['client_id']) {
            throw new \Exception('Google Drive integration not configured');
        }
        
        $accessToken = $this->getGoogleDriveAccessToken();
        
        $apiUrl = "https://www.googleapis.com/drive/v3/files/$fileId?alt=media";
        $headers = [
            'Authorization: Bearer ' . $accessToken
        ];
        
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('Google Drive download error: ' . $error);
        }
        
        $responseData = json_decode($response, true);
        
        return [
            'status' => $httpCode === 200 ? 'downloaded' : 'failed',
            'file_info' => $responseData
        ];
    }
    
    /**
     * Get Google Drive access token
     */
    private function getGoogleDriveAccessToken(): string
    {
        $integration = $this->getIntegration('storage')['google_drive'];
        
        $oauthUrl = 'https://oauth2.googleapis.com/token';
        $data = [
            'client_id' => $integration['client_id'],
            'client_secret' => $integration['client_secret'],
            'grant_type' => 'refresh_token',
            'refresh_token' => $integration['refresh_token'] ?? ''
        ];
        
        $ch = curl_init($oauthUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $responseData = json_decode($response, true);
        
        return $responseData['access_token'];
    }
    
    /**
     * Get integration status
     */
    public function getIntegrationStatus(): array
    {
        $status = [];
        
        foreach ($this->integrations as $name => $config) {
            $status[$name] = [
                'configured' => !empty($config['api_key']) || !empty($config['client_id']),
                'environment' => $config['environment'] ?? 'not_set',
                'last_test' => null
            ];
        }
        
        return $status;
    }
    
    /**
     * Test integration connection
     */
    public function testIntegration(string $name): array
    {
        $integration = $this->getIntegration($name);
        
        if (!$integration) {
            return [
                'status' => 'not_configured',
                'error' => "Integration '$name' not found"
            ];
        }
        
        try {
            switch ($name) {
                case 'midtrans':
                    return $this->testMidtransConnection();
                case 'gopay':
                    return $this->testGoPayConnection();
                case 'twilio':
                    return $this->testTwilioConnection();
                case 'email':
                    return $this->testEmailConnection();
                case 'aws_s3':
                    return $this->testS3Connection();
                case 'google_drive':
                    return $this->testGoogleDriveConnection();
                default:
                    return ['status' => 'unknown', 'error' => "Unknown integration: $name"];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Test Midtrans connection
     */
    private function testMidtransConnection(): array
    {
        try {
            $integration = $this->getIntegration('payment_gateway')['midtrans'];
            
            // Test balance check
            $apiUrl = $integration['server_url'] . '/v2/balance';
            $headers = [
                'Authorization: Basic ' . base64_encode($integration['api_key'] . ':' . $integration['merchant_id']),
                'Content-Type: application/json'
            ];
            
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            curl_close($ch);
            
            return [
                'status' => $httpCode === 200 ? 'connected' : 'failed',
                'http_code' => $httpCode,
                'environment' => $integration['environment']
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Test GoPay connection
     */
    private function testGoPayConnection(): array
    {
        try {
            $integration = $this->getIntegration('payment_gateway')['gopay'];
            
            $apiUrl = $integration['server_url'] . '/v2/status';
            $headers = [
                'Content-Type: application/json',
                'X-API-Key: ' . $integration['client_key'],
                'X-Signature: ' . $this->generateGoPaySignature(['test'], $integration)
            ];
            
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            curl_close($ch);
            
            return [
                'status' => $httpCode === 200 ? 'connected' : 'failed',
                'http_code' => $httpCode,
                'environment' => $integration['environment']
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Test Twilio connection
     */
    private function testTwilioConnection(): array
    {
        try {
            $integration = $this->getIntegration('notification')['twilio'];
            
            $apiUrl = $integration['whatsapp_api_url'];
            $headers = [
                'Authorization: Bearer ' . $integration['auth_token']
            ];
            
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            curl_close($ch);
            
            return [
                'status' => $httpCode === 200 ? 'connected' : 'failed',
                'http_code' => $httpCode,
                'environment' => $integration['environment']
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Test email connection
     */
    private function testEmailConnection(): array
    {
        try {
            $testEmail = 'test@maruba.com';
            $testSubject = 'Test Email';
            $testBody = 'This is a test email from Maruba application.';
            
            $result = $this->sendEmail($testEmail, $testSubject, $testBody);
            
            return [
                'status' => $result['status'] === 'sent' ? 'connected' : 'failed',
                'error' => $result['error'] ?? null
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Test S3 connection
     */
    private function testS3Connection(): array
    {
        try {
            $integration = $this->getIntegration('storage')['aws_s3'];
            
            $apiUrl = "https://{$integration['bucket']}.s3-{$integration['region']}.amazonaws.com/";
            $headers = [
                'Authorization: AWS4-HMAC SHA256',
                'x-amz-date: ' . gmdate('Y, d M Y H:i:s', time()),
                'x-amz-content-sha256: ' . hash('sha256', 'test')
            ];
            
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            curl_close($ch);
            
            return [
                'status' => $httpCode === 200 ? 'connected' : 'failed',
                'http_code' => $httpCode,
                'environment' => $integration['environment']
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Test Google Drive connection
     */
    private function testGoogleDriveConnection(): array
    {
        try {
            $integration = $this->getIntegration('storage')['google_drive'];
            
            $accessToken = $this->getGoogleDriveAccessToken();
            
            $apiUrl = 'https://www.googleapis.com/drive/v3/about';
            $headers = [
                'Authorization: Bearer ' . $accessToken
            ];
            
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            curl_close($ch);
            
            return [
                'status' => $httpCode === 200 ? 'connected' : 'failed',
                'http_code' => $httpCode,
                'environment' => $integration['environment']
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }
}
