<?php
/**
 * Notification Controller - Automated Email & Communication System
 * Handles email notifications, SMS, WhatsApp, and in-app notifications
 */

namespace App\Controllers;

use App\Database;
use App\Models\Audit;

class NotificationController
{
    private $db;
    private $audit;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->audit = new Audit();
    }

    /**
     * Get current tenant ID
     */
    private function getCurrentTenantId()
    {
        if (!isset($_SESSION['tenant_id'])) {
            throw new \Exception('Tenant context not found');
        }
        return $_SESSION['tenant_id'];
    }

    /**
     * Get current user ID
     */
    private function getCurrentUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Notification Templates Management
     */
    public function templates()
    {
        $tenantId = $this->getCurrentTenantId();

        $stmt = $this->db->prepare("
            SELECT nt.*,
                   COUNT(nl.id) as usage_count,
                   MAX(nl.sent_at) as last_used
            FROM notification_templates nt
            LEFT JOIN notification_logs nl ON nt.id = nl.template_id
            WHERE nt.tenant_id = ?
            GROUP BY nt.id
            ORDER BY nt.created_at DESC
        ");
        $stmt->execute([$tenantId]);
        $templates = $stmt->fetchAll();

        require_once __DIR__ . '/../Views/notifications/templates.php';
    }

    /**
     * Create new notification template
     */
    public function createTemplate()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->storeTemplate();
            return;
        }

        require_once __DIR__ . '/../Views/notifications/create_template.php';
    }

    /**
     * Store notification template
     */
    public function storeTemplate()
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $userId = $this->getCurrentUserId();

            $data = [
                'tenant_id' => $tenantId,
                'template_code' => trim($_POST['template_code']),
                'name' => trim($_POST['name']),
                'type' => $_POST['type'],
                'subject' => trim($_POST['subject'] ?? ''),
                'content' => trim($_POST['content']),
                'variables' => $this->parseTemplateVariables($_POST['content']),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'created_by' => $userId
            ];

            // Check for duplicate template code
            $stmt = $this->db->prepare("
                SELECT id FROM notification_templates
                WHERE tenant_id = ? AND template_code = ?
            ");
            $stmt->execute([$tenantId, $data['template_code']]);
            if ($stmt->fetch()) {
                throw new \Exception('Kode template sudah digunakan');
            }

            $stmt = $this->db->prepare("
                INSERT INTO notification_templates
                (tenant_id, template_code, name, type, subject, content, variables, is_active, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute(array_values($data));

            // Audit log
            $this->audit->logActivity(
                $userId,
                'notification_template_created',
                'notification_templates',
                $this->db->lastInsertId(),
                null,
                ['template_code' => $data['template_code'], 'name' => $data['name']]
            );

            $_SESSION['success'] = 'Template notifikasi berhasil dibuat';
            header('Location: /notifications/templates');

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal membuat template: ' . $e->getMessage();
            header('Location: /notifications/templates/create');
        }
    }

    /**
     * Parse template variables from content
     */
    private function parseTemplateVariables($content)
    {
        preg_match_all('/\{\{(\w+)\}\}/', $content, $matches);
        return array_unique($matches[1] ?? []);
    }

    /**
     * Edit notification template
     */
    public function editTemplate($templateId)
    {
        $tenantId = $this->getCurrentTenantId();

        $stmt = $this->db->prepare("
            SELECT * FROM notification_templates
            WHERE id = ? AND tenant_id = ?
        ");
        $stmt->execute([$templateId, $tenantId]);
        $template = $stmt->fetch();

        if (!$template) {
            $_SESSION['error'] = 'Template tidak ditemukan';
            header('Location: /notifications/templates');
        }

        require_once __DIR__ . '/../Views/notifications/edit_template.php';
    }

    /**
     * Update notification template
     */
    public function updateTemplate($templateId)
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $userId = $this->getCurrentUserId();

            // Get existing template
            $stmt = $this->db->prepare("
                SELECT * FROM notification_templates
                WHERE id = ? AND tenant_id = ?
            ");
            $stmt->execute([$templateId, $tenantId]);
            $existingTemplate = $stmt->fetch();

            if (!$existingTemplate) {
                throw new \Exception('Template tidak ditemukan');
            }

            $data = [
                'name' => trim($_POST['name']),
                'type' => $_POST['type'],
                'subject' => trim($_POST['subject'] ?? ''),
                'content' => trim($_POST['content']),
                'variables' => $this->parseTemplateVariables($_POST['content']),
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];

            $stmt = $this->db->prepare("
                UPDATE notification_templates
                SET name = ?, type = ?, subject = ?, content = ?, variables = ?, is_active = ?, updated_at = NOW()
                WHERE id = ? AND tenant_id = ?
            ");
            $stmt->execute([...array_values($data), $templateId, $tenantId]);

            // Audit log
            $this->audit->logActivity(
                $userId,
                'notification_template_updated',
                'notification_templates',
                $templateId,
                $existingTemplate,
                $data
            );

            $_SESSION['success'] = 'Template notifikasi berhasil diperbarui';
            header('Location: /notifications/templates');

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal memperbarui template: ' . $e->getMessage();
            header('Location: /notifications/templates/edit/' . $templateId);
        }
    }

    /**
     * Notification Logs/Dashboard
     */
    public function logs()
    {
        $tenantId = $this->getCurrentTenantId();

        $page = $_GET['page'] ?? 1;
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        // Build filters
        $whereConditions = ["nl.tenant_id = ?"];
        $params = [$tenantId];

        if (!empty($_GET['type'])) {
            $whereConditions[] = "nl.type = ?";
            $params[] = $_GET['type'];
        }

        if (!empty($_GET['status'])) {
            $whereConditions[] = "nl.status = ?";
            $params[] = $_GET['status'];
        }

        if (!empty($_GET['date_from'])) {
            $whereConditions[] = "nl.created_at >= ?";
            $params[] = $_GET['date_from'] . ' 00:00:00';
        }

        if (!empty($_GET['date_to'])) {
            $whereConditions[] = "nl.created_at <= ?";
            $params[] = $_GET['date_to'] . ' 23:59:59';
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Get notification logs
        $stmt = $this->db->prepare("
            SELECT nl.*,
                   nt.name as template_name,
                   CASE
                       WHEN nl.recipient_type = 'user' THEN u.name
                       WHEN nl.recipient_type = 'member' THEN m.name
                       ELSE 'Admin'
                   END as recipient_name
            FROM notification_logs nl
            LEFT JOIN notification_templates nt ON nl.template_id = nt.id
            LEFT JOIN users u ON nl.recipient_type = 'user' AND nl.recipient_id = u.id
            LEFT JOIN members m ON nl.recipient_type = 'member' AND nl.recipient_id = m.id
            WHERE {$whereClause}
            ORDER BY nl.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([...$params, $perPage, $offset]);
        $logs = $stmt->fetchAll();

        // Get total count
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM notification_logs nl WHERE {$whereClause}
        ");
        $stmt->execute($params);
        $totalLogs = $stmt->fetchColumn();
        $totalPages = ceil($totalLogs / $perPage);

        // Get statistics
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_sent,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read
            FROM notification_logs
            WHERE tenant_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$tenantId]);
        $stats = $stmt->fetch();

        require_once __DIR__ . '/../Views/notifications/logs.php';
    }

    /**
     * Send test notification
     */
    public function sendTest($templateId)
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $userId = $this->getCurrentUserId();

            // Get template
            $stmt = $this->db->prepare("
                SELECT * FROM notification_templates
                WHERE id = ? AND tenant_id = ? AND is_active = 1
            ");
            $stmt->execute([$templateId, $tenantId]);
            $template = $stmt->fetch();

            if (!$template) {
                throw new \Exception('Template tidak ditemukan atau tidak aktif');
            }

            // Get current user as recipient
            $stmt = $this->db->prepare("SELECT name, email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user || empty($user['email'])) {
                throw new \Exception('Email pengirim tidak ditemukan');
            }

            // Prepare test variables
            $testVariables = [
                'member_name' => 'Test User',
                'loan_amount' => '1.000.000',
                'due_date' => date('d/m/Y'),
                'cooperative_name' => 'Koperasi Test'
            ];

            $content = $this->replaceTemplateVariables($template['content'], $testVariables);
            $subject = $template['subject'] ? $this->replaceTemplateVariables($template['subject'], $testVariables) : 'Test Notification';

            // Send test notification
            $result = $this->sendNotification([
                'type' => $template['type'],
                'recipient_email' => $user['email'],
                'recipient_phone' => null,
                'subject' => $subject,
                'content' => $content,
                'template_id' => $templateId,
                'reference_type' => 'test',
                'reference_id' => $templateId
            ]);

            if ($result) {
                $_SESSION['success'] = 'Test notifikasi berhasil dikirim ke ' . $user['email'];
            } else {
                $_SESSION['error'] = 'Gagal mengirim test notifikasi';
            }

            header('Location: /notifications/templates');

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal mengirim test: ' . $e->getMessage();
            header('Location: /notifications/templates');
        }
    }

    /**
     * Automated Notification Triggers
     */
    public function triggerLoanApproval($loanId)
    {
        $tenantId = $this->getCurrentTenantId();

        // Get loan details with member info
        $stmt = $this->db->prepare("
            SELECT l.*, m.name as member_name, m.email as member_email, m.phone as member_phone,
                   p.name as product_name
            FROM loans l
            JOIN members m ON l.member_id = m.id
            JOIN products p ON l.product_id = p.id
            WHERE l.id = ? AND l.tenant_id = ?
        ");
        $stmt->execute([$loanId, $tenantId]);
        $loan = $stmt->fetch();

        if ($loan && !empty($loan['member_email'])) {
            $this->sendTemplateNotification('loan_approved', $loan['member_email'], $loan['member_phone'], [
                'member_name' => $loan['member_name'],
                'loan_amount' => number_format($loan['amount'], 0, ',', '.'),
                'loan_purpose' => $loan['purpose'] ?? 'Pengembangan usaha',
                'loan_tenor' => '12', // Default tenor
                'interest_rate' => '1.5',
                'monthly_payment' => number_format($loan['amount'] * 0.09, 0, ',', '.'), // Rough calculation
                'cooperative_name' => 'Koperasi Simpan Pinjam'
            ], 'loan', $loanId);
        }
    }

    public function triggerPaymentReminder($repaymentId)
    {
        $tenantId = $this->getCurrentTenantId();

        // Get repayment details
        $stmt = $this->db->prepare("
            SELECT r.*, l.amount as loan_amount, m.name as member_name,
                   m.email as member_email, m.phone as member_phone
            FROM repayments r
            JOIN loans l ON r.loan_id = l.id
            JOIN members m ON l.member_id = m.id
            WHERE r.id = ? AND r.tenant_id = ?
        ");
        $stmt->execute([$repaymentId, $tenantId]);
        $repayment = $stmt->fetch();

        if ($repayment && !empty($repayment['member_email'])) {
            $this->sendTemplateNotification('payment_reminder', $repayment['member_email'], $repayment['member_phone'], [
                'member_name' => $repayment['member_name'],
                'payment_amount' => number_format($repayment['amount_due'], 0, ',', '.'),
                'due_date' => date('d/m/Y', strtotime($repayment['due_date'])),
                'installment_number' => '1', // Would need to calculate
                'cooperative_name' => 'Koperasi Simpan Pinjam'
            ], 'repayment', $repaymentId);
        }
    }

    public function triggerOverdueAlert($loanId)
    {
        $tenantId = $this->getCurrentTenantId();

        // Get overdue loan details
        $stmt = $this->db->prepare("
            SELECT l.*, m.name as member_name, m.email as member_email, m.phone as member_phone,
                   DATEDIFF(CURDATE(), r.due_date) as overdue_days,
                   r.amount_due - COALESCE(r.amount_paid, 0) as overdue_amount
            FROM loans l
            JOIN members m ON l.member_id = m.id
            LEFT JOIN repayments r ON l.id = r.loan_id AND r.status = 'pending'
            WHERE l.id = ? AND l.tenant_id = ?
            ORDER BY r.due_date DESC LIMIT 1
        ");
        $stmt->execute([$loanId, $tenantId]);
        $loan = $stmt->fetch();

        if ($loan && !empty($loan['member_email'])) {
            $penaltyAmount = $loan['overdue_amount'] * 0.01; // 1% penalty

            $this->sendTemplateNotification('loan_overdue', $loan['member_email'], $loan['member_phone'], [
                'member_name' => $loan['member_name'],
                'overdue_amount' => number_format($loan['overdue_amount'], 0, ',', '.'),
                'overdue_days' => $loan['overdue_days'],
                'penalty_amount' => number_format($penaltyAmount, 0, ',', '.'),
                'cooperative_name' => 'Koperasi Simpan Pinjam'
            ], 'loan', $loanId);
        }
    }

    /**
     * Send template-based notification
     */
    private function sendTemplateNotification($templateCode, $email, $phone, $variables, $referenceType = null, $referenceId = null)
    {
        $tenantId = $this->getCurrentTenantId();

        // Get template
        $stmt = $this->db->prepare("
            SELECT * FROM notification_templates
            WHERE tenant_id = ? AND template_code = ? AND is_active = 1
        ");
        $stmt->execute([$tenantId, $templateCode]);
        $template = $stmt->fetch();

        if (!$template) {
            return false;
        }

        $content = $this->replaceTemplateVariables($template['content'], $variables);
        $subject = $template['subject'] ? $this->replaceTemplateVariables($template['subject'], $variables) : 'Notifikasi';

        return $this->sendNotification([
            'type' => $template['type'],
            'recipient_email' => $email,
            'recipient_phone' => $phone,
            'subject' => $subject,
            'content' => $content,
            'template_id' => $template['id'],
            'reference_type' => $referenceType,
            'reference_id' => $referenceId
        ]);
    }

    /**
     * Send notification (core method)
     */
    private function sendNotification($notification)
    {
        try {
            $tenantId = $this->getCurrentTenantId();

            // Insert notification log
            $stmt = $this->db->prepare("
                INSERT INTO notification_logs
                (tenant_id, template_id, recipient_type, recipient_id, recipient_email, recipient_phone,
                 type, subject, content, reference_type, reference_id, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'queued', NOW())
            ");
            $stmt->execute([
                $tenantId,
                $notification['template_id'] ?? null,
                'member', // Default to member
                0, // Would need to get actual recipient ID
                $notification['recipient_email'],
                $notification['recipient_phone'],
                $notification['type'],
                $notification['subject'],
                $notification['content'],
                $notification['reference_type'] ?? null,
                $notification['reference_id'] ?? null
            ]);

            $logId = $this->db->lastInsertId();

            // Process notification based on type
            $result = false;
            switch ($notification['type']) {
                case 'email':
                    $result = $this->sendEmail($notification['recipient_email'], $notification['subject'], $notification['content']);
                    break;
                case 'sms':
                    $result = $this->sendSMS($notification['recipient_phone'], $notification['content']);
                    break;
                case 'whatsapp':
                    $result = $this->sendWhatsApp($notification['recipient_phone'], $notification['content']);
                    break;
                case 'push':
                case 'in_app':
                    $result = $this->sendInAppNotification($logId, $notification['content']);
                    break;
            }

            // Update notification status
            $status = $result ? 'sent' : 'failed';
            $stmt = $this->db->prepare("
                UPDATE notification_logs
                SET status = ?, sent_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$status, $logId]);

            return $result;

        } catch (\Exception $e) {
            // Log error but don't throw
            error_log('Notification send error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send email notification
     */
    private function sendEmail($to, $subject, $content)
    {
        // In real implementation, use PHPMailer or similar
        // For now, just log the email
        $headers = 'From: noreply@koperasi.com' . "\r\n" .
                  'Reply-To: noreply@koperasi.com' . "\r\n" .
                  'X-Mailer: PHP/' . phpversion();

        return mail($to, $subject, $content, $headers);
    }

    /**
     * Send SMS notification
     */
    private function sendSMS($phone, $content)
    {
        // In real implementation, integrate with SMS gateway
        // For now, just log the SMS
        error_log("SMS to {$phone}: {$content}");
        return true; // Simulate success
    }

    /**
     * Send WhatsApp notification
     */
    private function sendWhatsApp($phone, $content)
    {
        // In real implementation, use WhatsApp Business API
        // For now, just log the message
        error_log("WhatsApp to {$phone}: {$content}");
        return true; // Simulate success
    }

    /**
     * Send in-app notification
     */
    private function sendInAppNotification($logId, $content)
    {
        // For in-app notifications, we just mark as delivered
        $stmt = $this->db->prepare("
            UPDATE notification_logs
            SET status = 'delivered', delivered_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$logId]);
        return true;
    }

    /**
     * Replace template variables
     */
    private function replaceTemplateVariables($content, $variables)
    {
        foreach ($variables as $key => $value) {
            $content = str_replace("{{{$key}}}", $value, $content);
        }
        return $content;
    }

    /**
     * Process queued notifications (cron job)
     */
    public function processQueue()
    {
        $tenantId = $this->getCurrentTenantId();

        // Get queued notifications
        $stmt = $this->db->prepare("
            SELECT * FROM notification_logs
            WHERE tenant_id = ? AND status = 'queued'
            ORDER BY created_at ASC
            LIMIT 50
        ");
        $stmt->execute([$tenantId]);
        $queuedNotifications = $stmt->fetchAll();

        $processed = 0;
        $sent = 0;
        $failed = 0;

        foreach ($queuedNotifications as $notification) {
            $processed++;

            try {
                $result = false;
                switch ($notification['type']) {
                    case 'email':
                        $result = $this->sendEmail($notification['recipient_email'], $notification['subject'], $notification['content']);
                        break;
                    case 'sms':
                        $result = $this->sendSMS($notification['recipient_phone'], $notification['content']);
                        break;
                    case 'whatsapp':
                        $result = $this->sendWhatsApp($notification['recipient_phone'], $notification['content']);
                        break;
                }

                $status = $result ? 'sent' : 'failed';
                if ($result) $sent++; else $failed++;

                $stmt = $this->db->prepare("
                    UPDATE notification_logs
                    SET status = ?, sent_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$status, $notification['id']]);

            } catch (\Exception $e) {
                $failed++;
                $stmt = $this->db->prepare("
                    UPDATE notification_logs
                    SET status = 'failed', failure_reason = ?
                    WHERE id = ?
                ");
                $stmt->execute([$e->getMessage(), $notification['id']]);
            }
        }

        echo json_encode([
            'processed' => $processed,
            'sent' => $sent,
            'failed' => $failed
        ]);
    }
}
