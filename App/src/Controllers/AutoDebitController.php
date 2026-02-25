<?php
/**
 * AutoDebit Controller - Automated Loan Repayment Collection
 * Handles automatic debit scheduling and processing
 */

namespace App\Controllers;

use App\Database;
use App\Models\Audit;

class AutoDebitController
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
     * Auto Debit Schedules Management
     */
    public function index()
    {
        $tenantId = $this->getCurrentTenantId();

        // Get auto debit schedules with loan and member details
        $stmt = $this->db->prepare("
            SELECT ads.*,
                   l.amount as loan_amount,
                   l.outstanding_balance,
                   m.name as member_name,
                   m.phone as member_phone,
                   COUNT(adt.id) as total_transactions,
                   SUM(CASE WHEN adt.status = 'completed' THEN 1 ELSE 0 END) as successful_transactions,
                   MAX(adt.processed_at) as last_transaction_date
            FROM auto_debit_schedules ads
            JOIN loans l ON ads.loan_id = l.id
            JOIN members m ON ads.member_id = m.id
            LEFT JOIN auto_debit_transactions adt ON ads.id = adt.auto_debit_id
            WHERE ads.tenant_id = ?
            GROUP BY ads.id, l.amount, l.outstanding_balance, m.name, m.phone
            ORDER BY ads.next_debit_date ASC, ads.created_at DESC
        ");
        $stmt->execute([$tenantId]);
        $schedules = $stmt->fetchAll();

        // Calculate success rates
        foreach ($schedules as &$schedule) {
            $schedule['success_rate'] = $schedule['total_transactions'] > 0
                ? round(($schedule['successful_transactions'] / $schedule['total_transactions']) * 100, 2)
                : 0;
        }

        require_once __DIR__ . '/../Views/payments/auto_debit.php';
    }

    /**
     * Create new auto debit schedule
     */
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->store();
            return;
        }

        $tenantId = $this->getCurrentTenantId();

        // Get eligible loans (disbursed, not fully paid, active members)
        $stmt = $this->db->prepare("
            SELECT l.id, l.amount, l.outstanding_balance, l.status,
                   m.name as member_name, m.phone as member_phone,
                   p.name as product_name
            FROM loans l
            JOIN members m ON l.member_id = m.id
            JOIN products p ON l.product_id = p.id
            WHERE l.tenant_id = ?
                AND l.status = 'disbursed'
                AND l.outstanding_balance > 0
                AND m.status = 'active'
                AND NOT EXISTS (
                    SELECT 1 FROM auto_debit_schedules ads
                    WHERE ads.loan_id = l.id AND ads.is_active = 1
                )
            ORDER BY m.name ASC
        ");
        $stmt->execute([$tenantId]);
        $eligibleLoans = $stmt->fetchAll();

        require_once __DIR__ . '/../Views/payments/create_auto_debit.php';
    }

    /**
     * Store new auto debit schedule
     */
    public function store()
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $userId = $this->getCurrentUserId();

            // Validate input
            $loanId = intval($_POST['loan_id']);
            $debitAmount = floatval($_POST['debit_amount']);
            $frequency = $_POST['frequency'];
            $debitDay = intval($_POST['debit_day'] ?? 1);
            $bankAccountId = !empty($_POST['bank_account_id']) ? $_POST['bank_account_id'] : null;
            $paymentMethod = $_POST['payment_method'];

            // Get loan details
            $stmt = $this->db->prepare("
                SELECT l.*, m.name as member_name, m.phone as member_phone
                FROM loans l
                JOIN members m ON l.member_id = m.id
                WHERE l.id = ? AND l.tenant_id = ?
            ");
            $stmt->execute([$loanId, $tenantId]);
            $loan = $stmt->fetch();

            if (!$loan) {
                throw new \Exception('Pinjaman tidak ditemukan');
            }

            if ($loan['outstanding_balance'] <= 0) {
                throw new \Exception('Pinjaman sudah lunas');
            }

            if ($debitAmount <= 0 || $debitAmount > $loan['outstanding_balance']) {
                throw new \Exception('Jumlah debit tidak valid');
            }

            // Check if auto debit already exists
            $stmt = $this->db->prepare("
                SELECT id FROM auto_debit_schedules
                WHERE loan_id = ? AND is_active = 1
            ");
            $stmt->execute([$loanId]);
            if ($stmt->fetch()) {
                throw new \Exception('Auto debit sudah ada untuk pinjaman ini');
            }

            // Calculate next debit date
            $nextDebitDate = $this->calculateNextDebitDate($frequency, $debitDay);

            // Start transaction
            $this->db->beginTransaction();

            // Insert auto debit schedule
            $scheduleData = [
                'tenant_id' => $tenantId,
                'loan_id' => $loanId,
                'member_id' => $loan['member_id'],
                'debit_amount' => $debitAmount,
                'frequency' => $frequency,
                'debit_day' => $debitDay,
                'bank_account_id' => $bankAccountId,
                'payment_method' => $paymentMethod,
                'is_active' => 1,
                'next_debit_date' => $nextDebitDate,
                'created_by' => $userId
            ];

            $stmt = $this->db->prepare("
                INSERT INTO auto_debit_schedules
                (tenant_id, loan_id, member_id, debit_amount, frequency, debit_day,
                 bank_account_id, payment_method, is_active, next_debit_date, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute(array_values($scheduleData));
            $scheduleId = $this->db->lastInsertId();

            // Commit transaction
            $this->db->commit();

            // Audit log
            $this->audit->logActivity(
                $userId,
                'auto_debit_created',
                'auto_debit_schedules',
                $scheduleId,
                null,
                ['loan_id' => $loanId, 'debit_amount' => $debitAmount, 'frequency' => $frequency]
            );

            $_SESSION['success'] = 'Auto debit berhasil dibuat untuk ' . $loan['member_name'];
            header('Location: /payments/auto-debit');

        } catch (\Exception $e) {
            $this->db->rollBack();
            $_SESSION['error'] = 'Gagal membuat auto debit: ' . $e->getMessage();
            header('Location: /payments/auto-debit/create');
        }
    }

    /**
     * Calculate next debit date
     */
    private function calculateNextDebitDate($frequency, $debitDay)
    {
        $today = date('Y-m-d');

        switch ($frequency) {
            case 'daily':
                return date('Y-m-d', strtotime($today . ' +1 day'));

            case 'weekly':
                $currentDayOfWeek = date('N'); // 1 = Monday, 7 = Sunday
                $targetDayOfWeek = $debitDay; // 1 = Monday, 7 = Sunday

                if ($currentDayOfWeek < $targetDayOfWeek) {
                    $daysToAdd = $targetDayOfWeek - $currentDayOfWeek;
                } else {
                    $daysToAdd = 7 - ($currentDayOfWeek - $targetDayOfWeek);
                }

                return date('Y-m-d', strtotime($today . " +{$daysToAdd} days"));

            case 'monthly':
                $currentDay = date('j');
                $currentMonth = date('m');
                $currentYear = date('Y');

                if ($currentDay < $debitDay) {
                    // Debit this month
                    $targetMonth = $currentMonth;
                    $targetYear = $currentYear;
                } else {
                    // Debit next month
                    $targetMonth = $currentMonth + 1;
                    $targetYear = $currentYear;
                    if ($targetMonth > 12) {
                        $targetMonth = 1;
                        $targetYear++;
                    }
                }

                $targetDate = date('Y-m-d', strtotime("{$targetYear}-{$targetMonth}-{$debitDay}"));

                // Check if date exists (e.g., Feb 30 doesn't exist)
                if (date('j', strtotime($targetDate)) != $debitDay) {
                    // Use last day of month
                    $targetDate = date('Y-m-t', strtotime("{$targetYear}-{$targetMonth}-01"));
                }

                return $targetDate;

            case 'quarterly':
                // Simplified quarterly logic - debit on specific day every 3 months
                $quarters = [
                    [1, 2, 3], // Q1: Jan, Feb, Mar
                    [4, 5, 6], // Q2: Apr, May, Jun
                    [7, 8, 9], // Q3: Jul, Aug, Sep
                    [10, 11, 12] // Q4: Oct, Nov, Dec
                ];

                $currentMonth = intval(date('m'));
                $currentQuarter = ceil($currentMonth / 3);

                // Move to next quarter
                $nextQuarter = $currentQuarter + 1;
                if ($nextQuarter > 4) {
                    $nextQuarter = 1;
                    $nextYear = date('Y') + 1;
                } else {
                    $nextYear = date('Y');
                }

                $nextQuarterMonth = $quarters[$nextQuarter - 1][0];
                $targetDate = date('Y-m-d', strtotime("{$nextYear}-{$nextQuarterMonth}-{$debitDay}"));

                // Check if date exists
                if (date('j', strtotime($targetDate)) != $debitDay) {
                    $targetDate = date('Y-m-t', strtotime("{$nextYear}-{$nextQuarterMonth}-01"));
                }

                return $targetDate;

            default:
                return date('Y-m-d', strtotime($today . ' +1 month'));
        }
    }

    /**
     * Process pending auto debits
     */
    public function processAutoDebits()
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $userId = $this->getCurrentUserId();
            $today = date('Y-m-d');

            // Get pending auto debits due today
            $stmt = $this->db->prepare("
                SELECT ads.*, l.outstanding_balance, m.name as member_name
                FROM auto_debit_schedules ads
                JOIN loans l ON ads.loan_id = l.id
                JOIN members m ON ads.member_id = m.id
                WHERE ads.tenant_id = ?
                    AND ads.is_active = 1
                    AND ads.next_debit_date <= ?
                    AND ads.failure_count < ads.max_failures
                    AND l.outstanding_balance > 0
                    AND l.status = 'disbursed'
            ");
            $stmt->execute([$tenantId, $today]);
            $pendingDebits = $stmt->fetchAll();

            $processed = 0;
            $successful = 0;
            $failed = 0;

            foreach ($pendingDebits as $debit) {
                $processed++;
                $result = $this->processSingleAutoDebit($debit, $userId);

                if ($result['success']) {
                    $successful++;
                } else {
                    $failed++;
                }
            }

            $_SESSION['success'] = "Auto debit diproses: {$processed} total, {$successful} berhasil, {$failed} gagal";
            header('Location: /payments/auto-debit');

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal memproses auto debit: ' . $e->getMessage();
            header('Location: /payments/auto-debit');
        }
    }

    /**
     * Process single auto debit
     */
    private function processSingleAutoDebit($debit, $userId)
    {
        try {
            // Start transaction
            $this->db->beginTransaction();

            $debitAmount = min($debit['debit_amount'], $debit['outstanding_balance']);

            // Create auto debit transaction record
            $stmt = $this->db->prepare("
                INSERT INTO auto_debit_transactions
                (auto_debit_id, transaction_date, amount, status, created_at)
                VALUES (?, CURDATE(), ?, 'processing', NOW())
            ");
            $stmt->execute([$debit['id'], $debitAmount]);
            $transactionId = $this->db->lastInsertId();

            // Simulate payment processing (in real implementation, this would integrate with payment gateway)
            $paymentSuccess = $this->processPayment($debit, $debitAmount);

            if ($paymentSuccess) {
                // Payment successful - create repayment record
                $stmt = $this->db->prepare("
                    INSERT INTO repayments
                    (loan_id, due_date, amount_due, amount_paid, payment_date, payment_method, status, tenant_id, created_at)
                    VALUES (?, CURDATE(), ?, ?, CURDATE(), 'auto_debit', 'paid', ?, NOW())
                ");
                $stmt->execute([
                    $debit['loan_id'],
                    $debitAmount,
                    $debitAmount,
                    $debit['tenant_id']
                ]);
                $repaymentId = $this->db->lastInsertId();

                // Update loan outstanding balance
                $stmt = $this->db->prepare("
                    UPDATE loans
                    SET outstanding_balance = outstanding_balance - ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$debitAmount, $debit['loan_id']]);

                // Update auto debit transaction
                $stmt = $this->db->prepare("
                    UPDATE auto_debit_transactions
                    SET status = 'completed', processed_at = NOW(), repayment_id = ?
                    WHERE id = ?
                ");
                $stmt->execute([$repaymentId, $transactionId]);

                // Calculate next debit date
                $nextDebitDate = $this->calculateNextDebitDate($debit['frequency'], $debit['debit_day']);

                // Update auto debit schedule
                $stmt = $this->db->prepare("
                    UPDATE auto_debit_schedules
                    SET next_debit_date = ?, last_debit_date = CURDATE(), last_debit_amount = ?,
                        failure_count = 0, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$nextDebitDate, $debitAmount, $debit['id']]);

                // Create journal entry for the payment
                $this->createPaymentJournalEntry($debit, $debitAmount, $repaymentId);

                $this->db->commit();

                // Send success notification
                $this->sendAutoDebitNotification($debit, $debitAmount, 'success');

                return ['success' => true, 'message' => 'Auto debit berhasil'];

            } else {
                // Payment failed
                $stmt = $this->db->prepare("
                    UPDATE auto_debit_transactions
                    SET status = 'failed', processed_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$transactionId]);

                // Update failure count
                $stmt = $this->db->prepare("
                    UPDATE auto_debit_schedules
                    SET failure_count = failure_count + 1, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$debit['id']]);

                $this->db->commit();

                // Send failure notification
                $this->sendAutoDebitNotification($debit, $debitAmount, 'failed');

                return ['success' => false, 'message' => 'Auto debit gagal'];
            }

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Simulate payment processing
     */
    private function processPayment($debit, $amount)
    {
        // In real implementation, this would integrate with payment gateway
        // For simulation, randomly succeed (90% success rate)
        return rand(1, 10) <= 9;
    }

    /**
     * Create journal entry for payment
     */
    private function createPaymentJournalEntry($debit, $amount, $repaymentId)
    {
        $tenantId = $debit['tenant_id'];

        // Generate entry number
        $entryNumber = 'JE-' . date('Ymd') . '-' . str_pad($repaymentId, 4, '0', STR_PAD_LEFT);

        // Create journal entry
        $stmt = $this->db->prepare("
            INSERT INTO journal_entries
            (tenant_id, entry_number, entry_date, description, source, status, total_debit, total_credit, is_balanced, created_at)
            VALUES (?, ?, CURDATE(), ?, 'auto_debit', 'posted', ?, ?, 1, NOW())
        ");
        $stmt->execute([
            $tenantId,
            $entryNumber,
            "Auto Debit - {$debit['member_name']}",
            $amount,
            $amount
        ]);
        $journalId = $this->db->lastInsertId();

        // Debit cash/bank
        $stmt = $this->db->prepare("
            INSERT INTO journal_lines
            (journal_entry_id, account_id, debit_amount, description)
            VALUES (?, (SELECT id FROM chart_of_accounts WHERE tenant_id = ? AND account_code = '11110000' LIMIT 1), ?, ?)
        ");
        $stmt->execute([$journalId, $tenantId, $amount, "Penerimaan auto debit"]);

        // Credit loan receivable
        $stmt = $this->db->prepare("
            INSERT INTO journal_lines
            (journal_entry_id, account_id, credit_amount, description)
            VALUES (?, (SELECT id FROM chart_of_accounts WHERE tenant_id = ? AND account_code = '11210000' LIMIT 1), ?, ?)
        ");
        $stmt->execute([$journalId, $tenantId, $amount, "Pelunasan piutang pinjaman"]);

        // Update transaction with journal entry
        $stmt = $this->db->prepare("
            UPDATE auto_debit_transactions
            SET journal_entry_id = ?
            WHERE auto_debit_id = ? AND transaction_date = CURDATE()
        ");
        $stmt->execute([$journalId, $debit['id']]);
    }

    /**
     * Send auto debit notification
     */
    private function sendAutoDebitNotification($debit, $amount, $status)
    {
        $tenantId = $debit['tenant_id'];

        // Get notification template
        $templateCode = $status === 'success' ? 'auto_debit_success' : 'auto_debit_failed';

        $stmt = $this->db->prepare("
            SELECT * FROM notification_templates
            WHERE tenant_id = ? AND template_code = ? AND is_active = 1
        ");
        $stmt->execute([$tenantId, $templateCode]);
        $template = $stmt->fetch();

        if ($template) {
            // Get member details
            $stmt = $this->db->prepare("SELECT name, phone, email FROM members WHERE id = ?");
            $stmt->execute([$debit['member_id']]);
            $member = $stmt->fetch();

            if ($member) {
                $variables = [
                    'member_name' => $member['name'],
                    'debit_amount' => number_format($amount, 0, ',', '.'),
                    'transaction_date' => date('d/m/Y'),
                    'loan_id' => $debit['loan_id']
                ];

                $content = $this->replaceTemplateVariables($template['content'], $variables);
                $subject = $template['subject'] ? $this->replaceTemplateVariables($template['subject'], $variables) : 'Notifikasi Auto Debit';

                // Insert notification log
                $stmt = $this->db->prepare("
                    INSERT INTO notification_logs
                    (tenant_id, template_id, recipient_type, recipient_id, recipient_email, recipient_phone,
                     type, subject, content, reference_type, reference_id, created_at)
                    VALUES (?, ?, 'member', ?, ?, ?, ?, ?, ?, 'auto_debit', ?, NOW())
                ");
                $stmt->execute([
                    $tenantId,
                    $template['id'],
                    $debit['member_id'],
                    $member['email'],
                    $member['phone'],
                    $template['type'],
                    $subject,
                    $content,
                    $debit['id']
                ]);
            }
        }
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
     * View auto debit transactions
     */
    public function transactions($scheduleId = null)
    {
        $tenantId = $this->getCurrentTenantId();

        if ($scheduleId) {
            // View transactions for specific schedule
            $stmt = $this->db->prepare("
                SELECT adt.*, ads.debit_amount as scheduled_amount,
                       l.amount as loan_amount, m.name as member_name
                FROM auto_debit_transactions adt
                JOIN auto_debit_schedules ads ON adt.auto_debit_id = ads.id
                JOIN loans l ON ads.loan_id = l.id
                JOIN members m ON ads.member_id = m.id
                WHERE ads.id = ? AND ads.tenant_id = ?
                ORDER BY adt.transaction_date DESC, adt.created_at DESC
            ");
            $stmt->execute([$scheduleId, $tenantId]);
            $transactions = $stmt->fetchAll();

            // Get schedule details
            $stmt = $this->db->prepare("SELECT * FROM auto_debit_schedules WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$scheduleId, $tenantId]);
            $schedule = $stmt->fetch();

            require_once __DIR__ . '/../Views/payments/auto_debit_transactions.php';

        } else {
            // View all transactions
            $page = $_GET['page'] ?? 1;
            $perPage = 25;
            $offset = ($page - 1) * $perPage;

            $stmt = $this->db->prepare("
                SELECT adt.*, ads.debit_amount as scheduled_amount,
                       l.amount as loan_amount, m.name as member_name,
                       ads.frequency, ads.payment_method
                FROM auto_debit_transactions adt
                JOIN auto_debit_schedules ads ON adt.auto_debit_id = ads.id
                JOIN loans l ON ads.loan_id = l.id
                JOIN members m ON ads.member_id = m.id
                WHERE ads.tenant_id = ?
                ORDER BY adt.transaction_date DESC, adt.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$tenantId, $perPage, $offset]);
            $transactions = $stmt->fetchAll();

            // Get total count
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM auto_debit_transactions adt
                JOIN auto_debit_schedules ads ON adt.auto_debit_id = ads.id
                WHERE ads.tenant_id = ?
            ");
            $stmt->execute([$tenantId]);
            $totalTransactions = $stmt->fetchColumn();
            $totalPages = ceil($totalTransactions / $perPage);

            require_once __DIR__ . '/../Views/payments/auto_debit_all_transactions.php';
        }
    }

    /**
     * Deactivate auto debit schedule
     */
    public function deactivate($scheduleId)
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $userId = $this->getCurrentUserId();

            $stmt = $this->db->prepare("
                UPDATE auto_debit_schedules
                SET is_active = 0, updated_at = NOW()
                WHERE id = ? AND tenant_id = ?
            ");
            $stmt->execute([$scheduleId, $tenantId]);

            if ($stmt->rowCount() === 0) {
                throw new \Exception('Auto debit schedule tidak ditemukan');
            }

            // Audit log
            $this->audit->logActivity(
                $userId,
                'auto_debit_deactivated',
                'auto_debit_schedules',
                $scheduleId,
                ['is_active' => 1],
                ['is_active' => 0]
            );

            $_SESSION['success'] = 'Auto debit berhasil dinonaktifkan';
            header('Location: /payments/auto-debit');

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal menonaktifkan auto debit: ' . $e->getMessage();
            header('Location: /payments/auto-debit');
        }
    }
}
