<?php
namespace App\Notification;

use App\Models\NotificationLog;

/**
 * Multi-Channel Notification System
 *
 * Supports WhatsApp, SMS, Email, and Push notifications
 * Essential for modern Indonesian digital platforms
 */
class MultiChannelNotifier
{
    private array $providers = [];
    private NotificationLog $logModel;

    public function __construct()
    {
        $this->logModel = new NotificationLog();
        $this->initializeProviders();
    }

    /**
     * Initialize notification providers
     */
    private function initializeProviders(): void
    {
        // WhatsApp Business API
        $this->providers['whatsapp'] = new WhatsAppProvider([
            'api_key' => $_ENV['WHATSAPP_API_KEY'] ?? '',
            'phone_number_id' => $_ENV['WHATSAPP_PHONE_NUMBER_ID'] ?? '',
            'business_account_id' => $_ENV['WHATSAPP_BUSINESS_ACCOUNT_ID'] ?? '',
            'access_token' => $_ENV['WHATSAPP_ACCESS_TOKEN'] ?? ''
        ]);

        // SMS Gateway (e.g., Twilio, local Indonesian providers)
        $this->providers['sms'] = new SMSProvider([
            'provider' => $_ENV['SMS_PROVIDER'] ?? 'twilio',
            'account_sid' => $_ENV['SMS_ACCOUNT_SID'] ?? '',
            'auth_token' => $_ENV['SMS_AUTH_TOKEN'] ?? '',
            'from_number' => $_ENV['SMS_FROM_NUMBER'] ?? ''
        ]);

        // Email Service
        $this->providers['email'] = new EmailProvider([
            'provider' => $_ENV['EMAIL_PROVIDER'] ?? 'smtp',
            'smtp_host' => $_ENV['SMTP_HOST'] ?? '',
            'smtp_port' => $_ENV['SMTP_PORT'] ?? 587,
            'smtp_username' => $_ENV['SMTP_USERNAME'] ?? '',
            'smtp_password' => $_ENV['SMTP_PASSWORD'] ?? '',
            'from_email' => $_ENV['FROM_EMAIL'] ?? 'noreply@ksp.co.id',
            'from_name' => $_ENV['FROM_NAME'] ?? 'KSP Digital'
        ]);

        // Push Notifications (Firebase)
        $this->providers['push'] = new PushNotificationProvider([
            'firebase_server_key' => $_ENV['FIREBASE_SERVER_KEY'] ?? '',
            'project_id' => $_ENV['FIREBASE_PROJECT_ID'] ?? ''
        ]);
    }

    /**
     * Send notification via single channel
     */
    public function send(string $channel, string $recipient, string $message, array $options = []): array
    {
        if (!isset($this->providers[$channel])) {
            return $this->logResult($channel, $recipient, false, 'Channel not supported');
        }

        try {
            $result = $this->providers[$channel]->send($recipient, $message, $options);

            $this->logNotification($channel, $recipient, $result['success'], $message, $options, $result['message_id'] ?? null);

            return [
                'success' => $result['success'],
                'message_id' => $result['message_id'] ?? null,
                'channel' => $channel,
                'recipient' => $recipient
            ];

        } catch (\Exception $e) {
            $this->logNotification($channel, $recipient, false, $message, $options, null, $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'channel' => $channel,
                'recipient' => $recipient
            ];
        }
    }

    /**
     * Send notification via multiple channels
     */
    public function sendMultiChannel(array $channels, string $recipient, string $message, array $options = []): array
    {
        $results = [];

        foreach ($channels as $channel) {
            $results[$channel] = $this->send($channel, $recipient, $message, $options);
        }

        return $results;
    }

    /**
     * Send bulk notifications
     */
    public function sendBulk(string $channel, array $recipients, string $message, array $options = []): array
    {
        $results = [
            'total' => count($recipients),
            'successful' => 0,
            'failed' => 0,
            'results' => []
        ];

        foreach ($recipients as $recipient) {
            $result = $this->send($channel, $recipient, $message, $options);
            $results['results'][] = $result;

            if ($result['success']) {
                $results['successful']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Send templated notification
     */
    public function sendTemplate(string $channel, string $recipient, string $template, array $data, array $options = []): array
    {
        $message = $this->renderTemplate($template, $data, $channel);
        $options['template'] = $template;
        $options['template_data'] = $data;

        return $this->send($channel, $recipient, $message, $options);
    }

    /**
     * Send KSP-specific notifications
     */
    public function sendKSPNotification(string $type, array $data): array
    {
        $templates = $this->getKSPTemplates();

        if (!isset($templates[$type])) {
            return ['success' => false, 'error' => 'Template not found'];
        }

        $template = $templates[$type];
        $channels = $template['channels'];
        $recipient = $this->getRecipientForKSPNotification($type, $data);

        if (!$recipient) {
            return ['success' => false, 'error' => 'Recipient not found'];
        }

        return $this->sendMultiChannel($channels, $recipient, $template['message'], [
            'type' => $type,
            'data' => $data,
            'priority' => $template['priority'] ?? 'normal'
        ]);
    }

    /**
     * Get KSP notification templates
     */
    private function getKSPTemplates(): array
    {
        return [
            'loan_approved' => [
                'channels' => ['whatsapp', 'email', 'push'],
                'message' => [
                    'whatsapp' => "🎉 Selamat! Pinjaman Anda sebesar Rp {amount} telah *DISETUJUI*. Silakan datang ke kantor untuk proses pencairan. Nomor pinjaman: {loan_number}",
                    'email' => "Selamat! Pinjaman Anda telah disetujui.",
                    'push' => "Pinjaman Disetujui - Rp {amount}"
                ],
                'priority' => 'high'
            ],

            'payment_reminder' => [
                'channels' => ['whatsapp', 'sms'],
                'message' => [
                    'whatsapp' => "🔔 *PENGINGAT PEMBAYARAN*\nAngsuran pinjaman {loan_number} sebesar Rp {amount} jatuh tempo pada {due_date}.\n\n💳 Bayar sekarang via:\n• QRIS\n• Transfer Bank\n• Mobile App\n\nHindari denda keterlambatan!",
                    'sms' => "INGAT BAYAR: Angsuran Rp {amount} untuk pinjaman {loan_number} jatuh tempo {due_date}. Bayar sekarang via QRIS atau transfer."
                ],
                'priority' => 'high'
            ],

            'payment_overdue' => [
                'channels' => ['whatsapp', 'sms', 'email'],
                'message' => [
                    'whatsapp' => "🚨 *PERINGATAN TELAT BAYAR*\nAngsuran pinjaman {loan_number} sebesar Rp {amount} sudah lewat jatuh tempo {due_date}.\n\nDenda: Rp {penalty}\nTotal yang harus dibayar: Rp {total}\n\nSegera lakukan pembayaran untuk menghindari konsekuensi lebih lanjut.",
                    'sms' => "TELAT BAYAR: Angsuran Rp {amount} + denda Rp {penalty} = Rp {total}. Segera bayar!",
                    'email' => "Pemberitahuan Keterlambatan Pembayaran"
                ],
                'priority' => 'urgent'
            ],

            'savings_interest' => [
                'channels' => ['whatsapp', 'email'],
                'message' => [
                    'whatsapp' => "💰 *BUNGA SIMPANAN*\nSelamat! Simpanan Anda mendapatkan bunga sebesar Rp {interest_amount}.\n\nSaldo akhir: Rp {balance}\nRekening: {account_number}\n\nTerima kasih atas kepercayaan Anda menyimpan di KSP kami.",
                    'email' => "Pemberitahuan Bunga Simpanan"
                ],
                'priority' => 'normal'
            ],

            'member_registration' => [
                'channels' => ['whatsapp', 'email'],
                'message' => [
                    'whatsapp' => "✅ *SELAMAT BERGABUNG!*\n\nAnda telah berhasil terdaftar sebagai anggota KSP.\n\n📋 Nomor Anggota: {member_number}\n👤 Nama: {name}\n📱 Status: {status}\n\nSilakan lengkapi data Anda dan mulai menggunakan layanan KSP digital kami.",
                    'email' => "Selamat Bergabung - Konfirmasi Pendaftaran Anggota"
                ],
                'priority' => 'high'
            ],

            'loan_disbursement' => [
                'channels' => ['whatsapp', 'email', 'push'],
                'message' => [
                    'whatsapp' => "💰 *PINJAMAN DICAIRKAN*\n\nPinjaman Anda sebesar Rp {amount} telah berhasil dicairkan.\n\n📄 Nomor Pinjaman: {loan_number}\n🏦 Rekening: {account_info}\n📅 Tanggal: {disbursement_date}\n\nAngsuran pertama jatuh tempo: {first_payment_date}",
                    'email' => "Konfirmasi Pencairan Pinjaman",
                    'push' => "Pinjaman Dicairkan - Rp {amount}"
                ],
                'priority' => 'high'
            ]
        ];
    }

    /**
     * Get recipient for KSP notification
     */
    private function getRecipientForKSPNotification(string $type, array $data): ?string
    {
        // Extract recipient based on notification type
        switch ($type) {
            case 'loan_approved':
            case 'loan_disbursement':
                return $data['member_phone'] ?? null;

            case 'payment_reminder':
            case 'payment_overdue':
                return $data['member_phone'] ?? null;

            case 'savings_interest':
                return $data['member_phone'] ?? null;

            case 'member_registration':
                return $data['phone'] ?? null;

            default:
                return null;
        }
    }

    /**
     * Render template with data
     */
    private function renderTemplate(string $template, array $data, string $channel): string
    {
        $templates = $this->getKSPTemplates();

        if (!isset($templates[$template]['message'][$channel])) {
            return $template; // Return template name if not found
        }

        $message = $templates[$template]['message'][$channel];

        // Replace placeholders
        foreach ($data as $key => $value) {
            $message = str_replace("{{$key}}", $value, $message);
        }

        return $message;
    }

    /**
     * Log notification
     */
    private function logNotification(string $channel, string $recipient, bool $success, string $message, array $options, ?string $messageId, string $error = null): void
    {
        $tenantId = $_SESSION['tenant_context']['tenant_id'] ?? null;

        $logData = [
            'tenant_id' => $tenantId,
            'recipient_type' => $this->detectRecipientType($recipient),
            'recipient_id' => $this->extractRecipientId($recipient),
            'channel' => $channel,
            'subject' => $options['subject'] ?? null,
            'message' => $message,
            'status' => $success ? 'sent' : 'failed',
            'message_id' => $messageId,
            'error_message' => $error,
            'metadata' => json_encode($options)
        ];

        $this->logModel->create($logData);
    }

    /**
     * Detect recipient type from recipient string
     */
    private function detectRecipientType(string $recipient): string
    {
        if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        }

        if (preg_match('/^\+?[\d\s\-\(\)]+$/', $recipient)) {
            return 'phone';
        }

        return 'external';
    }

    /**
     * Extract recipient ID (simplified)
     */
    private function extractRecipientId(string $recipient): ?int
    {
        // In a real implementation, you'd map recipient to user/member ID
        return null;
    }

    /**
     * Log result for debugging
     */
    private function logResult(string $channel, string $recipient, bool $success, string $message = ''): array
    {
        return [
            'success' => $success,
            'channel' => $channel,
            'recipient' => $recipient,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStats(int $tenantId, string $period = '30d'): array
    {
        // This would query the notification logs for statistics
        return [
            'total_sent' => 0,
            'successful' => 0,
            'failed' => 0,
            'by_channel' => [],
            'by_type' => [],
            'period' => $period
        ];
    }
}

/**
 * WhatsApp Provider using WhatsApp Business API
 */
class WhatsAppProvider
{
    private array $config;
    private string $apiUrl = 'https://graph.facebook.com/v17.0/';

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function send(string $recipient, string $message, array $options = []): array
    {
        // Ensure phone number format
        $phoneNumber = $this->formatPhoneNumber($recipient);

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phoneNumber,
            'type' => 'text',
            'text' => ['body' => $message]
        ];

        $url = $this->apiUrl . $this->config['phone_number_id'] . '/messages';

        $response = $this->makeApiCall($url, $payload);

        if (isset($response['messages'][0]['id'])) {
            return [
                'success' => true,
                'message_id' => $response['messages'][0]['id']
            ];
        }

        return [
            'success' => false,
            'error' => $response['error']['message'] ?? 'Unknown error'
        ];
    }

    private function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $clean = preg_replace('/\D/', '', $phone);

        // Add Indonesia country code if not present
        if (strlen($clean) === 10 && $clean[0] === '8') {
            $clean = '628' . substr($clean, 1);
        } elseif (strlen($clean) === 11 && $clean[0] === '0') {
            $clean = '62' . substr($clean, 1);
        } elseif (strlen($clean) === 12 && substr($clean, 0, 2) === '62') {
            // Already has country code
        } else {
            // Assume it's already properly formatted
        }

        return $clean;
    }

    private function makeApiCall(string $url, array $payload): array
    {
        // In a real implementation, make HTTP request to WhatsApp API
        // For demo purposes, simulate success
        return [
            'messages' => [
                [
                    'id' => 'wamid_' . uniqid()
                ]
            ]
        ];
    }
}

/**
 * SMS Provider (Twilio or local Indonesian providers)
 */
class SMSProvider
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function send(string $recipient, string $message, array $options = []): array
    {
        // In a real implementation, integrate with SMS gateway
        // For demo, simulate sending

        $messageId = 'sms_' . uniqid();

        // Simulate API call
        return [
            'success' => true,
            'message_id' => $messageId
        ];
    }
}

/**
 * Email Provider
 */
class EmailProvider
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function send(string $recipient, string $message, array $options = []): array
    {
        $subject = $options['subject'] ?? 'Notifikasi KSP';
        $from = $this->config['from_email'];
        $fromName = $this->config['from_name'];

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $fromName . ' <' . $from . '>',
            'Reply-To: ' . $from
        ];

        // In a real implementation, use PHPMailer or similar
        $success = mail($recipient, $subject, $message, implode("\r\n", $headers));

        return [
            'success' => $success,
            'message_id' => $success ? 'email_' . uniqid() : null
        ];
    }
}

/**
 * Push Notification Provider (Firebase)
 */
class PushNotificationProvider
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function send(string $recipient, string $message, array $options = []): array
    {
        // In a real implementation, send to Firebase Cloud Messaging
        // For demo, simulate sending

        return [
            'success' => true,
            'message_id' => 'push_' . uniqid()
        ];
    }
}

/**
 * Notification Controller for API endpoints
 */
class NotificationController
{
    private MultiChannelNotifier $notifier;

    public function __construct()
    {
        $this->notifier = new MultiChannelNotifier();
    }

    /**
     * Send notification via API
     */
    public function sendNotification(): void
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['channel']) || !isset($data['recipient']) || !isset($data['message'])) {
                throw new \Exception('Channel, recipient, dan message diperlukan');
            }

            $result = $this->notifier->send(
                $data['channel'],
                $data['recipient'],
                $data['message'],
                $data['options'] ?? []
            );

            echo json_encode([
                'success' => $result['success'],
                'data' => $result
            ]);

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send KSP notification
     */
    public function sendKSPNotification(): void
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['type'])) {
                throw new \Exception('Notification type diperlukan');
            }

            $result = $this->notifier->sendKSPNotification($data['type'], $data['data'] ?? []);

            echo json_encode([
                'success' => $result['success'] ?? false,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get notification statistics
     */
    public function getStatistics(): void
    {
        header('Content-Type: application/json');

        try {
            $tenantId = $_SESSION['tenant_context']['tenant_id'] ?? 1;
            $period = $_GET['period'] ?? '30d';

            $stats = $this->notifier->getNotificationStats($tenantId, $period);

            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}

// =========================================
// NOTIFICATION SYSTEM INTEGRATION
// =========================================

/*
// Add to router configuration:

// API endpoints
POST /api/notifications/send -> NotificationController::sendNotification
POST /api/notifications/ksp -> NotificationController::sendKSPNotification
GET  /api/notifications/stats -> NotificationController::getStatistics

// Usage examples:

// Send WhatsApp notification
\$notifier = new MultiChannelNotifier();
\$result = \$notifier->send('whatsapp', '+6281234567890', 'Halo! Ini notifikasi dari KSP');

// Send KSP-specific notification
\$result = \$notifier->sendKSPNotification('loan_approved', [
    'member_phone' => '+6281234567890',
    'amount' => '10000000',
    'loan_number' => 'LN001'
]);

// Send bulk notifications
\$results = \$notifier->sendBulk('sms', ['+6281...', '+6282...'], 'Pengingat pembayaran');
*/

?>
