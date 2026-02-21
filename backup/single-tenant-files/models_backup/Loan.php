<?php
namespace App\Models;

class Loan extends Model
{
    protected string $table = 'loans';
    protected array $fillable = [
        'member_id', 'product_id', 'amount', 'tenor_months', 'rate',
        'status', 'assigned_surveyor_id', 'assigned_collector_id',
        'approved_by', 'disbursed_by'
    ];
    protected array $casts = [
        'member_id' => 'int',
        'product_id' => 'int',
        'amount' => 'float',
        'tenor_months' => 'int',
        'rate' => 'float',
        'assigned_surveyor_id' => 'int',
        'assigned_collector_id' => 'int',
        'approved_by' => 'int',
        'disbursed_by' => 'int',
        'created_at' => 'datetime'
    ];

    /**
     * Get loan with member and product information
     */
    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT l.*,
                   m.name as member_name, m.nik, m.phone,
                   p.name as product_name, p.type as product_type, p.rate as product_rate,
                   u1.name as surveyor_name,
                   u2.name as collector_name,
                   u3.name as approved_by_name,
                   u4.name as disbursed_by_name
            FROM {$this->table} l
            LEFT JOIN members m ON l.member_id = m.id
            LEFT JOIN products p ON l.product_id = p.id
            LEFT JOIN users u1 ON l.assigned_surveyor_id = u1.id
            LEFT JOIN users u2 ON l.assigned_collector_id = u2.id
            LEFT JOIN users u3 ON l.approved_by = u3.id
            LEFT JOIN users u4 ON l.disbursed_by = u4.id
            WHERE l.id = ?
        ");
        $stmt->execute([$id]);
        $result = $stmt->fetch();

        return $result ? $this->castAttributes($result) : null;
    }

    /**
     * Get loans by status
     */
    public function getByStatus(string $status): array
    {
        return $this->findWhere(['status' => $status], ['created_at' => 'DESC']);
    }

    /**
     * Get loans assigned to a user
     */
    public function getAssignedToUser(int $userId, string $role = 'surveyor'): array
    {
        $field = $role === 'surveyor' ? 'assigned_surveyor_id' : 'assigned_collector_id';
        return $this->findWhere([$field => $userId], ['created_at' => 'DESC']);
    }

    /**
     * Calculate loan repayment schedule
     */
    public function calculateRepaymentSchedule(int $loanId): array
    {
        $loan = $this->find($loanId);
        if (!$loan) {
            return [];
        }

        $amount = $loan['amount'];
        $rate = $loan['rate'] / 100; // Convert to decimal
        $tenor = $loan['tenor_months'];

        // Simple interest calculation
        $monthlyRate = $rate / 12;
        $monthlyPayment = $amount * $monthlyRate * pow(1 + $monthlyRate, $tenor) / (pow(1 + $monthlyRate, $tenor) - 1);

        $schedule = [];
        $remainingBalance = $amount;
        $startDate = new \DateTime($loan['created_at']);

        for ($month = 1; $month <= $tenor; $month++) {
            $dueDate = clone $startDate;
            $dueDate->modify("+$month months");

            $interest = $remainingBalance * $monthlyRate;
            $principal = $monthlyPayment - $interest;
            $remainingBalance -= $principal;

            $schedule[] = [
                'month' => $month,
                'due_date' => $dueDate->format('Y-m-d'),
                'principal' => round($principal, 2),
                'interest' => round($interest, 2),
                'payment' => round($monthlyPayment, 2),
                'remaining_balance' => round(max(0, $remainingBalance), 2)
            ];
        }

        return $schedule;
    }

    /**
     * Get loan statistics
     */
    public function getStatistics(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_loans,
                SUM(CASE WHEN status IN ('approved','disbursed') THEN amount ELSE 0 END) as outstanding_amount,
                SUM(CASE WHEN status = 'default' THEN amount ELSE 0 END) as default_amount,
                AVG(CASE WHEN status IN ('approved','disbursed') THEN rate ELSE NULL END) as avg_rate,
                COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_count,
                COUNT(CASE WHEN status = 'survey' THEN 1 END) as survey_count,
                COUNT(CASE WHEN status = 'review' THEN 1 END) as review_count,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_count,
                COUNT(CASE WHEN status = 'disbursed' THEN 1 END) as disbursed_count,
                COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_count,
                COUNT(CASE WHEN status = 'default' THEN 1 END) as default_count
            FROM {$this->table}
        ");
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Calculate NPL (Non-Performing Loans) ratio
     */
    public function getNPLRatio(): float
    {
        $stmt = $this->db->prepare("
            SELECT
                SUM(CASE WHEN status = 'default' THEN 1 ELSE 0 END) as npl_count,
                COUNT(*) as total_loans
            FROM {$this->table}
            WHERE status IN ('approved','disbursed','default')
        ");
        $stmt->execute();
        $result = $stmt->fetch();

        if ($result['total_loans'] == 0) {
            return 0.0;
        }

        return round(($result['npl_count'] / $result['total_loans']) * 100, 2);
    }

    /**
     * Get loans due for repayment this month
     */
    public function getLoansDueThisMonth(): array
    {
        $stmt = $this->db->prepare("
            SELECT l.*, m.name as member_name, m.phone
            FROM {$this->table} l
            JOIN members m ON l.member_id = m.id
            WHERE l.status IN ('approved','disbursed')
            AND MONTH(l.created_at) = MONTH(CURRENT_DATE())
            AND YEAR(l.created_at) = YEAR(CURRENT_DATE())
        ");
        $stmt->execute();

        $results = $stmt->fetchAll();
        return array_map([$this, 'castAttributes'], $results);
    }

    /**
     * Update loan status with audit trail
     */
    public function updateStatus(int $id, string $status, int $userId, array $additionalData = []): bool
    {
        // Update loan status
        $data = ['status' => $status];

        // Set user fields based on status
        if ($status === 'approved' && isset($additionalData['approved_by'])) {
            $data['approved_by'] = $additionalData['approved_by'];
        } elseif ($status === 'disbursed' && isset($additionalData['disbursed_by'])) {
            $data['disbursed_by'] = $additionalData['disbursed_by'];
        }

        $success = $this->update($id, $data);

        if ($success) {
            // Log audit trail
            $auditModel = new AuditLog();
            $auditModel->create([
                'user_id' => $userId,
                'action' => 'update_loan_status',
                'entity' => 'loan',
                'entity_id' => $id,
                'meta' => json_encode([
                    'old_status' => $additionalData['old_status'] ?? null,
                    'new_status' => $status,
                    'additional_data' => $additionalData
                ])
            ]);
        }

        return $success;
    }
}
