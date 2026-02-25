<?php
namespace App\Models;

class Repayment extends Model
{
    protected string $table = 'repayments';
    protected array $fillable = [
        'loan_id', 'due_date', 'paid_date', 'amount_due', 'amount_paid',
        'method', 'proof_path', 'collector_id', 'status'
    ];
    protected array $casts = [
        'loan_id' => 'int',
        'amount_due' => 'float',
        'amount_paid' => 'float',
        'collector_id' => 'int',
        'created_at' => 'datetime'
    ];

    /**
     * Get repayments for a loan
     */
    public function getByLoanId(int $loanId): array
    {
        return $this->findWhere(['loan_id' => $loanId], ['due_date' => 'ASC']);
    }

    /**
     * Get overdue repayments
     */
    public function getOverdueRepayments(): array
    {
        $stmt = $this->db->prepare("
            SELECT r.*, l.amount as loan_amount, m.name as member_name, m.phone
            FROM {$this->table} r
            JOIN loans l ON r.loan_id = l.id
            JOIN members m ON l.member_id = m.id
            WHERE r.status = 'due'
            AND r.due_date < CURDATE()
            ORDER BY r.due_date ASC
        ");
        $stmt->execute();

        $results = $stmt->fetchAll();
        return array_map([$this, 'castAttributes'], $results);
    }

    /**
     * Get repayments due this week
     */
    public function getDueThisWeek(): array
    {
        $stmt = $this->db->prepare("
            SELECT r.*, l.amount as loan_amount, m.name as member_name, m.phone
            FROM {$this->table} r
            JOIN loans l ON r.loan_id = l.id
            JOIN members m ON l.member_id = m.id
            WHERE r.status = 'due'
            AND r.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            ORDER BY r.due_date ASC
        ");
        $stmt->execute();

        $results = $stmt->fetchAll();
        return array_map([$this, 'castAttributes'], $results);
    }

    /**
     * Record payment
     */
    public function recordPayment(int $repaymentId, array $paymentData, int $userId): bool
    {
        $repayment = $this->find($repaymentId);
        if (!$repayment) {
            return false;
        }

        // Update repayment
        $updateData = [
            'paid_date' => $paymentData['paid_date'] ?? date('Y-m-d'),
            'amount_paid' => $paymentData['amount_paid'] ?? $repayment['amount_due'],
            'method' => $paymentData['method'] ?? 'cash',
            'proof_path' => $paymentData['proof_path'] ?? null,
            'collector_id' => $userId,
            'status' => 'paid'
        ];

        $success = $this->update($repaymentId, $updateData);

        if ($success) {
            // Log audit trail
            $auditModel = new AuditLog();
            $auditModel->create([
                'user_id' => $userId,
                'action' => 'record_repayment',
                'entity' => 'repayment',
                'entity_id' => $repaymentId,
                'meta' => json_encode($paymentData)
            ]);
        }

        return $success;
    }

    /**
     * Get repayment statistics
     */
    public function getStatistics(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_repayments,
                SUM(amount_due) as total_due,
                SUM(amount_paid) as total_paid,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN status = 'due' THEN 1 ELSE 0 END) as due_count,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) as partial_count,
                SUM(CASE WHEN due_date < CURDATE() AND status = 'due' THEN 1 ELSE 0 END) as overdue_count
            FROM {$this->table}
        ");
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Generate repayment schedule for a loan
     */
    public function generateSchedule(int $loanId, array $scheduleData): bool
    {
        // Delete existing schedule
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE loan_id = ?");
        $stmt->execute([$loanId]);

        // Insert new schedule
        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (loan_id, due_date, amount_due, status)
            VALUES (?, ?, ?, 'due')
        ");

        foreach ($scheduleData as $repayment) {
            $stmt->execute([
                $loanId,
                $repayment['due_date'],
                $repayment['amount_due']
            ]);
        }

        return true;
    }

    /**
     * Get total outstanding repayments for a loan
     */
    public function getOutstandingAmount(int $loanId): float
    {
        $stmt = $this->db->prepare("
            SELECT SUM(amount_due - amount_paid) as outstanding
            FROM {$this->table}
            WHERE loan_id = ? AND status IN ('due', 'late', 'partial')
        ");
        $stmt->execute([$loanId]);

        $result = $stmt->fetch();
        return (float)($result['outstanding'] ?? 0);
    }
}
