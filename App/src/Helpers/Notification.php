<?php
namespace App\Helpers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Notification
{
    private static array $channels = ['email', 'whatsapp', 'sms', 'push'];

    /**
     * Send notification via specified channel
     */
    public static function send(string $channel, array $recipient, string $subject, string $message, array $options = []): array
    {
        if (!in_array($channel, self::$channels)) {
            return [
                'success' => false,
                'error' => 'Unsupported notification channel: ' . $channel
            ];
        }

        switch ($channel) {
            case 'email':
                return self::sendEmail($recipient, $subject, $message, $options);
            case 'whatsapp':
                return self::sendWhatsApp($recipient, $message, $options);
            case 'sms':
                return self::sendSMS($recipient, $message, $options);
            case 'push':
                return self::sendPushNotification($recipient, $subject, $message, $options);
            default:
                return [
                    'success' => false,
                    'error' => 'Unknown channel'
                ];
        }
    }

    /**
     * Send email notification
     */
    private static function sendEmail(array $recipient, string $subject, string $message, array $options): array
    {
        try {
            $mail = new PHPMailer(true);

            // Server settings
            $mail->isSMTP();
            $mail->Host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['SMTP_USERNAME'] ?? '';
            $mail->Password = $_ENV['SMTP_PASSWORD'] ?? '';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $_ENV['SMTP_PORT'] ?? 587;

            // Recipients
            $mail->setFrom($_ENV['SMTP_FROM_EMAIL'] ?? 'noreply@aplikasiksp.id', $_ENV['SMTP_FROM_NAME'] ?? 'APLIKASI KSP');
            $mail->addAddress($recipient['email'], $recipient['name'] ?? '');

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = self::formatEmailBody($message, $options);
            $mail->AltBody = strip_tags($message);

            // Attachments
            if (isset($options['attachments'])) {
                foreach ($options['attachments'] as $attachment) {
                    $mail->addAttachment($attachment['path'], $attachment['name'] ?? '');
                }
            }

            $mail->send();

            return [
                'success' => true,
                'message_id' => $mail->getLastMessageID(),
                'channel' => 'email'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Email sending failed: ' . $mail->ErrorInfo,
                'channel' => 'email'
            ];
        }
    }

    /**
     * Send WhatsApp notification
     */
    private static function sendWhatsApp(array $recipient, string $message, array $options): array
    {
        // WhatsApp Business API implementation
        $apiKey = $_ENV['WHATSAPP_API_KEY'] ?? '';
        $phoneNumberId = $_ENV['WHATSAPP_PHONE_NUMBER_ID'] ?? '';

        if (!$apiKey || !$phoneNumberId) {
            return [
                'success' => false,
                'error' => 'WhatsApp API not configured',
                'channel' => 'whatsapp'
            ];
        }

        try {
            $url = "https://graph.facebook.com/v17.0/{$phoneNumberId}/messages";

            $data = [
                'messaging_product' => 'whatsapp',
                'to' => self::formatPhoneNumber($recipient['phone']),
                'type' => 'text',
                'text' => [
                    'body' => $message
                ]
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $result = json_decode($response, true);

            if ($httpCode === 200 && isset($result['messages'][0]['id'])) {
                return [
                    'success' => true,
                    'message_id' => $result['messages'][0]['id'],
                    'channel' => 'whatsapp'
                ];
            }

            return [
                'success' => false,
                'error' => 'WhatsApp sending failed: ' . ($result['error']['message'] ?? 'Unknown error'),
                'channel' => 'whatsapp'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'WhatsApp sending failed: ' . $e->getMessage(),
                'channel' => 'whatsapp'
            ];
        }
    }

    /**
     * Send SMS notification (placeholder for SMS gateway integration)
     */
    private static function sendSMS(array $recipient, string $message, array $options): array
    {
        // SMS Gateway integration (e.g., Twilio, Nexmo, etc.)
        // This is a placeholder implementation

        $smsGateway = $_ENV['SMS_GATEWAY'] ?? '';

        if (!$smsGateway) {
            return [
                'success' => false,
                'error' => 'SMS gateway not configured',
                'channel' => 'sms'
            ];
        }

        // Implement SMS sending logic here
        // For now, return success for development
        return [
            'success' => true,
            'message_id' => 'sms_' . uniqid(),
            'channel' => 'sms',
            'note' => 'SMS sending not implemented yet'
        ];
    }

    /**
     * Send push notification (placeholder for mobile app push notifications)
     */
    private static function sendPushNotification(array $recipient, string $subject, string $message, array $options): array
    {
        // Push notification implementation (Firebase, OneSignal, etc.)
        // This is a placeholder for mobile app notifications

        if (!isset($recipient['device_token'])) {
            return [
                'success' => false,
                'error' => 'Device token not provided',
                'channel' => 'push'
            ];
        }

        // Implement push notification logic here
        // For now, return success for development
        return [
            'success' => true,
            'message_id' => 'push_' . uniqid(),
            'channel' => 'push',
            'note' => 'Push notification not implemented yet'
        ];
    }

    /**
     * Send bulk notifications
     */
    public static function sendBulk(string $channel, array $recipients, string $subject, string $message, array $options = []): array
    {
        $results = [];
        $successCount = 0;
        $failCount = 0;

        foreach ($recipients as $recipient) {
            $result = self::send($channel, $recipient, $subject, $message, $options);
            $results[] = $result;

            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }

            // Add delay between sends to avoid rate limiting
            usleep(100000); // 100ms delay
        }

        return [
            'total_sent' => count($recipients),
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'results' => $results
        ];
    }

    /**
     * Send loan approval notification
     */
    public static function sendLoanApprovalNotification(array $loan, array $member): array
    {
        $subject = 'Pengajuan Pinjaman Disetujui - KOPERASI APP';
        $message = self::getLoanApprovalMessage($loan, $member);

        $channels = [];
        if (!empty($member['email'])) {
            $channels[] = 'email';
        }
        if (!empty($member['phone'])) {
            $channels[] = 'whatsapp';
        }

        $results = [];
        foreach ($channels as $channel) {
            $results[$channel] = self::send($channel, $member, $subject, $message, [
                'priority' => 'high',
                'loan_id' => $loan['id']
            ]);
        }

        return $results;
    }

    /**
     * Send payment reminder notification
     */
    public static function sendPaymentReminder(array $repayment, array $member): array
    {
        $subject = 'Pengingat Pembayaran Angsuran - KOPERASI APP';
        $message = self::getPaymentReminderMessage($repayment, $member);

        $channels = [];
        if (!empty($member['phone'])) {
            $channels[] = 'whatsapp'; // Prioritize WhatsApp for reminders
        }
        if (!empty($member['email'])) {
            $channels[] = 'email';
        }

        $results = [];
        foreach ($channels as $channel) {
            $results[$channel] = self::send($channel, $member, $subject, $message, [
                'priority' => 'high',
                'repayment_id' => $repayment['id']
            ]);
        }

        return $results;
    }

    /**
     * Send survey completion notification
     */
    public static function sendSurveyCompletionNotification(array $survey, array $loan, array $member): array
    {
        $subject = 'Survey Pinjaman Selesai - KOPERASI APP';
        $message = self::getSurveyCompletionMessage($survey, $loan, $member);

        return self::send('whatsapp', $member, $subject, $message, [
            'priority' => 'normal',
            'survey_id' => $survey['id']
        ]);
    }

    /**
     * Format email body with template
     */
    private static function formatEmailBody(string $message, array $options): string
    {
        $template = self::getEmailTemplate();
        $logoUrl = asset_url('images/logo.png');

        return str_replace(
            ['{logo}', '{message}', '{year}'],
            [$logoUrl, nl2br(htmlspecialchars($message)), date('Y')],
            $template
        );
    }

    /**
     * Get email template
     */
    private static function getEmailTemplate(): string
    {
        return '
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>KOPERASI APP</title>
        </head>
        <body style="font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4;">
            <div style="max-width: 600px; margin: 0 auto; background-color: white;">
                    <img src="{logo}" alt="KOPERASI APP" style="max-width: 200px; height: auto;">
                </div>
                <div style="padding: 30px;">
                    {message}
                </div>
                <div style="background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #dee2e6;">
                    <p style="margin: 0; color: #6c757d; font-size: 14px;">
                        © {year} KOPERASI APP. Semua hak dilindungi.
                    </p>
                    <p style="margin: 5px 0 0 0; color: #6c757d; font-size: 12px;">
                        Email ini dikirim secara otomatis, mohon tidak membalas email ini.
                    </p>
                </div>
            </div>
        </body>
        </html>
        ';
    }

    /**
     * Format phone number for WhatsApp
     */
    private static function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/\D/', '', $phone);

        // Add country code if not present (assuming Indonesia)
        if (strlen($phone) === 10 && strpos($phone, '62') !== 0) {
            $phone = '62' . $phone;
        } elseif (strlen($phone) === 11 && strpos($phone, '62') !== 0) {
            $phone = '62' . substr($phone, 1);
        }

        return $phone;
    }

    /**
     * Get loan approval message
     */
    private static function getLoanApprovalMessage(array $loan, array $member): string
    {
        return "Halo {$member['name']},

Selamat! Pengajuan pinjaman Anda telah DISETUJUI.

Detail Pinjaman:
- Jumlah Pinjaman: Rp " . number_format($loan['amount'], 0, ',', '.') . "
- Tenor: {$loan['tenor_months']} bulan
- Bunga: {$loan['rate']}%

Silakan datang ke kantor koperasi untuk proses pencairan.

Terima kasih,
KOPERASI APP";
    }

    /**
     * Get payment reminder message
     */
    private static function getPaymentReminderMessage(array $repayment, array $member): string
    {
        $dueDate = date('d/m/Y', strtotime($repayment['due_date']));
        $amount = number_format($repayment['amount_due'], 0, ',', '.');

        return "Halo {$member['name']},

Pengingat pembayaran angsuran pinjaman Anda.

Jumlah: Rp {$amount}
Jatuh Tempo: {$dueDate}

Mohon segera lakukan pembayaran untuk menghindari denda.

Terima kasih,
KOPERASI APP";
    }

    /**
     * Get survey completion message
     */
    private static function getSurveyCompletionMessage(array $survey, array $loan, array $member): string
    {
        return "Halo {$member['name']},

Survey untuk pengajuan pinjaman Anda telah selesai dengan skor {$survey['score']}.

Status pinjaman Anda akan segera diperbarui.

Terima kasih,
KOPERASI APP";
    }

    /**
     * Log notification
     */
    private static function logNotification(string $channel, array $recipient, string $subject, array $result): void
    {
        // Log to database or file
        $logData = [
            'channel' => $channel,
            'recipient' => json_encode($recipient),
            'subject' => $subject,
            'result' => json_encode($result),
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Could save to notification_logs table
        error_log("Notification sent - Channel: {$channel}, Success: " . ($result['success'] ? 'Yes' : 'No'));
    }
}
