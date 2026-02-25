<?php
namespace App\Models;

class Loan extends Model
{
    protected string $table = 'loans';
    protected array $fillable = [
        'loan_number', 'member_id', 'product_id', 'principal_amount', 'interest_rate',
        'interest_type', 'tenor_months', 'monthly_installment', 'total_amount',
        'amount', 'purpose', 'collateral_details', 'status',
        'application_date', 'approval_date', 'disbursement_date', 'completion_date',
        'survey_date', 'surveyed_by', 'approved_by', 'disbursed_by', 'rejection_reason', 'tenant_id'
    ];
    protected array $casts = [
        'member_id' => 'int',
        'product_id' => 'int',
        'principal_amount' => 'float',
        'interest_rate' => 'float',
        'tenor_months' => 'int',
        'monthly_installment' => 'float',
        'total_amount' => 'float',
        'amount' => 'float',
        'application_date' => 'date',
        'approval_date' => 'date',
        'disbursement_date' => 'date',
        'completion_date' => 'date',
        'survey_date' => 'date',
        'surveyed_by' => 'int',
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
                   m.name as member_name, m.nik, m.phone, m.email,
                   lp.name as product_name, lp.type as product_type,
                   u1.name as surveyor_name,
                   u2.name as approved_by_name,
                   u3.name as disbursed_by_name
            FROM {$this->table} l
            LEFT JOIN members m ON l.member_id = m.id
            LEFT JOIN loan_products lp ON l.product_id = lp.id
            LEFT JOIN users u1 ON l.surveyed_by = u1.id
            LEFT JOIN users u2 ON l.approved_by = u2.id
            LEFT JOIN users u3 ON l.disbursed_by = u3.id
            WHERE l.id = ?
        ");
        $stmt->execute([$id]);
        $result = $stmt->fetch();

        return $result ? $this->castAttributes($result) : null;
    }

    /**
     * Generate unique loan number
     */
    public function generateLoanNumber(): string
    {
        $prefix = 'LN';
        $year = date('Y');
        $month = date('m');

        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM {$this->table}
            WHERE loan_number LIKE ?
        ");
        $stmt->execute(["{$prefix}{$year}{$month}%"]);

        $sequence = $stmt->fetch()['count'] + 1;

        return sprintf('%s%s%s%04d', $prefix, $year, $month, $sequence);
    }

    /**
     * Submit loan for review
     */
    public function submitForReview(int $loanId): bool
    {
        return $this->update($loanId, [
            'status' => 'submitted',
            'application_date' => date('Y-m-d')
        ]);
    }

    /**
     * Assign loan to surveyor
     */
    public function assignToSurveyor(int $loanId, int $surveyorId): bool
    {
        return $this->update($loanId, [
            'status' => 'survey_pending',
            'surveyed_by' => $surveyorId
        ]);
    }

    /**
     * Complete loan survey
     */
    public function completeSurvey(int $loanId, array $surveyData): bool
    {
        return $this->update($loanId, [
            'status' => 'survey_completed',
            'survey_date' => date('Y-m-d'),
            'collateral_details' => json_encode($surveyData)
        ]);
    }

    /**
     * Approve loan
     */
    public function approveLoan(int $loanId, int $approvedBy, string $approvalNotes = ''): bool
    {
        return $this->update($loanId, [
            'status' => 'approved',
            'approval_date' => date('Y-m-d'),
            'approved_by' => $approvedBy
        ]);
    }

    /**
     * Reject loan
     */
    public function rejectLoan(int $loanId, string $reason, int $rejectedBy): bool
    {
        return $this->update($loanId, [
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'approved_by' => $rejectedBy
        ]);
    }

    /**
     * Disburse loan
     */
    public function disburseLoan(int $loanId, int $disbursedBy): bool
    {
        return $this->update($loanId, [
            'status' => 'disbursed',
            'disbursement_date' => date('Y-m-d'),
            'disbursed_by' => $disbursedBy
        ]);
    }

    /**
     * Get loans by status
     */
    public function getLoansByStatus(string $status): array
    {
        return $this->findWhere(['status' => $status], ['application_date' => 'DESC']);
    }

    /**
     * Get loans assigned to surveyor
     */
    public function getLoansForSurveyor(int $surveyorId): array
    {
        $stmt = $this->db->prepare("
            SELECT l.*, m.name as member_name, m.phone, lp.name as product_name
            FROM {$this->table} l
            JOIN members m ON l.member_id = m.id
            JOIN loan_products lp ON l.product_id = lp.id
            WHERE l.surveyed_by = ? AND l.status IN ('survey_pending', 'survey_completed')
            ORDER BY l.application_date ASC
        ");
        $stmt->execute([$surveyorId]);
        return $stmt->fetchAll();
    }

    /**
     * Get loans pending approval
     */
    public function getLoansPendingApproval(): array
    {
        return $this->findWhere(['status' => 'survey_completed'], ['application_date' => 'ASC']);
    }

    /**
     * Get loans ready for disbursement
     */
    public function getLoansReadyForDisbursement(): array
    {
        return $this->findWhere(['status' => 'approved'], ['approval_date' => 'ASC']);
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

        $principal = $loan['principal_amount'];
        $rate = $loan['interest_rate'] / 100; // Convert to decimal
        $tenor = $loan['tenor_months'];
        $monthlyInstallment = $loan['monthly_installment'];

        $schedule = [];
        $remainingBalance = $loan['total_amount'];
        $startDate = new \DateTime($loan['disbursement_date'] ?? $loan['approval_date'] ?? $loan['application_date']);

        for ($month = 1; $month <= $tenor; $month++) {
            $dueDate = clone $startDate;
            $dueDate->modify("+$month months");

            if ($loan['interest_type'] === 'flat') {
                // Flat interest: fixed monthly payment
                $interest = $principal * $rate / 12;
                $principalPayment = $monthlyInstallment - $interest;
            } else {
                // Effective interest: declining balance
                $interest = $remainingBalance * $rate / 12;
                $principalPayment = $monthlyInstallment - $interest;
            }

            $remainingBalance -= $principalPayment;

            $schedule[] = [
                'installment_number' => $month,
                'due_date' => $dueDate->format('Y-m-d'),
                'principal_payment' => round($principalPayment, 2),
                'interest_payment' => round($interest, 2),
                'total_payment' => round($monthlyInstallment, 2),
                'remaining_balance' => round(max(0, $remainingBalance), 2)
            ];
        }

        return $schedule;
    }

    /**
     * Get loan statistics
     */
    public function getLoanStatistics(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_loans,
                COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_loans,
                COUNT(CASE WHEN status = 'submitted' THEN 1 END) as submitted_loans,
                COUNT(CASE WHEN status = 'survey_pending' THEN 1 END) as survey_pending,
                COUNT(CASE WHEN status = 'survey_completed' THEN 1 END) as survey_completed,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_loans,
                COUNT(CASE WHEN status = 'disbursed' THEN 1 END) as disbursed_loans,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_loans,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_loans,
                COUNT(CASE WHEN status = 'defaulted' THEN 1 END) as defaulted_loans,
                SUM(CASE WHEN status IN ('disbursed', 'active') THEN amount ELSE 0 END) as total_outstanding,
                AVG(CASE WHEN status IN ('disbursed', 'active') THEN rate ELSE NULL END) as avg_interest_rate,
                SUM(CASE WHEN status = 'defaulted' THEN amount ELSE 0 END) as total_defaults
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
        $stats = $this->getLoanStatistics();
        $totalPerforming = $stats['active_loans'] + $stats['completed_loans'];

        if ($totalPerforming == 0) {
            return 0.0;
        }

        return round(($stats['defaulted_loans'] / $totalPerforming) * 100, 2);
    }

    /**
     * Get loans due for repayment this month
     */
    public function getLoansDueThisMonth(): array
    {
        $currentMonth = date('m');
        $currentYear = date('Y');

        $stmt = $this->db->prepare("
            SELECT DISTINCT l.*, m.name as member_name, m.phone, m.email
            FROM {$this->table} l
            JOIN members m ON l.member_id = m.id
            JOIN loan_repayments lr ON l.id = lr.loan_id
            WHERE l.status IN ('disbursed', 'active')
            AND lr.status = 'pending'
            AND MONTH(lr.due_date) = ?
            AND YEAR(lr.due_date) = ?
            ORDER BY lr.due_date ASC
        ");
        $stmt->execute([$currentMonth, $currentYear]);
        return $stmt->fetchAll();
    }

    /**
     * Get overdue loans
     */
    public function getOverdueLoans(): array
    {
        $stmt = $this->db->prepare("
            SELECT DISTINCT l.*, m.name as member_name, m.phone, m.email,
                   DATEDIFF(CURDATE(), lr.due_date) as days_overdue,
                   lr.amount_due as overdue_amount
            FROM {$this->table} l
            JOIN members m ON l.member_id = m.id
            JOIN loan_repayments lr ON l.id = lr.loan_id
            WHERE l.status IN ('disbursed', 'active')
            AND lr.status = 'pending'
            AND lr.due_date < CURDATE()
            ORDER BY lr.due_date ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Update loan status with audit trail
     */
    public function updateStatus(int $id, string $status, int $userId, array $additionalData = []): bool
    {
        $loan = $this->find($id);
        if (!$loan) {
            return false;
        }

        // Update loan status
        $data = ['status' => $status];

        // Set user fields and dates based on status
        switch ($status) {
            case 'submitted':
                $data['application_date'] = date('Y-m-d');
                break;
            case 'survey_completed':
                $data['survey_date'] = date('Y-m-d');
                break;
            case 'approved':
                $data['approval_date'] = date('Y-m-d');
                $data['approved_by'] = $userId;
                break;
            case 'disbursed':
                $data['disbursement_date'] = date('Y-m-d');
                $data['disbursed_by'] = $userId;
                break;
            case 'completed':
                $data['completion_date'] = date('Y-m-d');
                break;
        }

        // Add rejection reason if provided
        if (isset($additionalData['rejection_reason'])) {
            $data['rejection_reason'] = $additionalData['rejection_reason'];
        }

        $success = $this->update($id, $data);

        if ($success) {
            // Log audit trail
            $auditModel = new \App\Models\AuditLog();
            $auditModel->create([
                'user_id' => $userId,
                'action' => 'update_loan_status',
                'resource_type' => 'loan',
                'resource_id' => $id,
                'old_values' => json_encode(['status' => $loan['status']]),
                'new_values' => json_encode(['status' => $status] + $data)
            ]);
        }

        return $success;
    }
}
