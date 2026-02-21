<?php
namespace App\Models;

class Member extends Model
{
    protected string $table = 'members';
    protected array $fillable = [
        'member_number', 'name', 'nik', 'phone', 'email', 'address',
        'province', 'city', 'district', 'village', 'postal_code',
        'birth_date', 'birth_place', 'gender', 'marital_status', 'religion',
        'occupation', 'monthly_income', 'education',
        'ktp_photo_path', 'kk_photo_path', 'selfie_photo_path',
        'latitude', 'longitude', 'status', 'verification_status',
        'verified_at', 'verified_by', 'joined_at', 'tenant_id'
    ];
    protected array $casts = [
        'birth_date' => 'date',
        'verified_at' => 'datetime',
        'joined_at' => 'datetime',
        'monthly_income' => 'float',
        'latitude' => 'float',
        'longitude' => 'float',
        'created_at' => 'datetime'
    ];

    /**
     * Find member by NIK
     */
    public function findByNik(string $nik): ?array
    {
        $members = $this->findWhere(['nik' => $nik]);
        return !empty($members) ? $members[0] : null;
    }

    /**
     * Find member by phone
     */
    public function findByPhone(string $phone): ?array
    {
        $members = $this->findWhere(['phone' => $phone]);
        return !empty($members) ? $members[0] : null;
    }

    /**
     * Generate unique member number
     */
    public function generateMemberNumber(): string
    {
        $prefix = 'MBR';
        $year = date('Y');
        $sequence = $this->getNextSequence();

        return sprintf('%s%s%06d', $prefix, $year, $sequence);
    }

    /**
     * Get next sequence number for member
     */
    private function getNextSequence(): int
    {
        $year = date('Y');
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM {$this->table}
            WHERE member_number LIKE ?
        ");
        $stmt->execute(["MBR{$year}%"]);

        return $stmt->fetch()['count'] + 1;
    }

    /**
     * Get active members
     */
    public function getActiveMembers(): array
    {
        return $this->findWhere(['status' => 'active'], ['name' => 'ASC']);
    }

    /**
     * Get members pending verification
     */
    public function getPendingVerification(): array
    {
        return $this->findWhere(['verification_status' => 'pending'], ['created_at' => 'ASC']);
    }

    /**
     * Verify member
     */
    public function verifyMember(int $memberId, int $verifiedBy): bool
    {
        return $this->update($memberId, [
            'verification_status' => 'verified',
            'verified_at' => date('Y-m-d H:i:s'),
            'verified_by' => $verifiedBy,
            'status' => 'active',
            'joined_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Reject member verification
     */
    public function rejectMember(int $memberId, string $reason): bool
    {
        return $this->update($memberId, [
            'verification_status' => 'rejected',
            'status' => 'inactive'
        ]);
    }

    /**
     * Get member savings accounts
     */
    public function getSavingsAccounts(int $memberId): array
    {
        $stmt = $this->db->prepare("
            SELECT sa.*, sp.name as product_name, sp.type as product_type
            FROM savings_accounts sa
            JOIN savings_products sp ON sa.product_id = sp.id
            WHERE sa.member_id = ? AND sa.status = 'active'
            ORDER BY sa.opened_at DESC
        ");
        $stmt->execute([$memberId]);
        return $stmt->fetchAll();
    }

    /**
     * Get member loans
     */
    public function getLoans(int $memberId): array
    {
        $stmt = $this->db->prepare("
            SELECT l.*, lp.name as product_name, lp.type as product_type
            FROM loans l
            JOIN loan_products lp ON l.product_id = lp.id
            WHERE l.member_id = ?
            ORDER BY l.application_date DESC
        ");
        $stmt->execute([$memberId]);
        return $stmt->fetchAll();
    }

    /**
     * Get member transaction history
     */
    public function getTransactionHistory(int $memberId, int $limit = 50): array
    {
        // Get savings transactions
        $stmt = $this->db->prepare("
            (SELECT
                st.transaction_date as date,
                'savings' as type,
                st.type as transaction_type,
                st.amount,
                st.balance_after as balance,
                st.notes,
                st.reference_number,
                st.created_at
            FROM savings_transactions st
            WHERE st.member_id = ?)
            UNION ALL
            (SELECT
                lr.paid_date as date,
                'loan' as type,
                'repayment' as transaction_type,
                lr.amount_paid as amount,
                l.outstanding_balance as balance,
                lr.notes,
                lr.payment_reference as reference_number,
                lr.created_at
            FROM loan_repayments lr
            JOIN loans l ON lr.loan_id = l.id
            WHERE lr.member_id = ? AND lr.status = 'paid')
            ORDER BY date DESC, created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$memberId, $memberId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get member dashboard data
     */
    public function getDashboardData(int $memberId): array
    {
        $member = $this->find($memberId);
        if (!$member) {
            return ['error' => 'Member not found'];
        }

        // Get savings summary
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_accounts,
                SUM(balance) as total_balance,
                SUM(interest_accrued) as total_interest
            FROM savings_accounts
            WHERE member_id = ? AND status = 'active'
        ");
        $stmt->execute([$memberId]);
        $savingsSummary = $stmt->fetch();

        // Get loan summary
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_loans,
                SUM(outstanding_balance) as total_outstanding,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_loans
            FROM loans
            WHERE member_id = ?
        ");
        $stmt->execute([$memberId]);
        $loanSummary = $stmt->fetch();

        // Get recent transactions
        $recentTransactions = array_slice($this->getTransactionHistory($memberId, 10), 0, 10);

        // Get upcoming loan payments
        $stmt = $this->db->prepare("
            SELECT lr.*, l.loan_number
            FROM loan_repayments lr
            JOIN loans l ON lr.loan_id = l.id
            WHERE lr.member_id = ? AND lr.status = 'pending'
            AND lr.due_date >= CURDATE()
            ORDER BY lr.due_date ASC
            LIMIT 5
        ");
        $stmt->execute([$memberId]);
        $upcomingPayments = $stmt->fetchAll();

        return [
            'member' => $member,
            'savings' => [
                'total_accounts' => $savingsSummary['total_accounts'] ?? 0,
                'total_balance' => $savingsSummary['total_balance'] ?? 0,
                'total_interest' => $savingsSummary['total_interest'] ?? 0
            ],
            'loans' => [
                'total_loans' => $loanSummary['total_loans'] ?? 0,
                'total_outstanding' => $loanSummary['total_outstanding'] ?? 0,
                'active_loans' => $loanSummary['active_loans'] ?? 0
            ],
            'recent_transactions' => $recentTransactions,
            'upcoming_payments' => $upcomingPayments
        ];
    }

    /**
     * Calculate member's DSR (Debt Service Ratio)
     */
    public function calculateDSR(int $memberId): float
    {
        // Get monthly income
        $member = $this->find($memberId);
        $monthlyIncome = $member['monthly_income'] ?? 0;

        if ($monthlyIncome <= 0) {
            return 0;
        }

        // Get monthly loan payments
        $stmt = $this->db->prepare("
            SELECT SUM(monthly_installment) as total_monthly_payments
            FROM loans
            WHERE member_id = ? AND status IN ('active', 'disbursed')
        ");
        $stmt->execute([$memberId]);
        $result = $stmt->fetch();

        $monthlyPayments = $result['total_monthly_payments'] ?? 0;

        // Calculate DSR = (Monthly Debt Payments / Monthly Income) * 100
        return round(($monthlyPayments / $monthlyIncome) * 100, 2);
    }

    /**
     * Check if member can apply for loan
     */
    public function canApplyForLoan(int $memberId, float $requestedAmount): array
    {
        $member = $this->find($memberId);
        if (!$member) {
            return ['allowed' => false, 'reason' => 'Member not found'];
        }

        if ($member['status'] !== 'active') {
            return ['allowed' => false, 'reason' => 'Member account is not active'];
        }

        // Check DSR limit (typically 60% max)
        $dsr = $this->calculateDSR($memberId);
        $maxDsr = 60.0; // 60% DSR limit

        if ($dsr >= $maxDsr) {
            return [
                'allowed' => false,
                'reason' => "DSR too high: {$dsr}% (max {$maxDsr}%)",
                'current_dsr' => $dsr
            ];
        }

        // Check existing loans
        $loans = $this->getLoans($memberId);
        $activeLoans = array_filter($loans, fn($loan) => in_array($loan['status'], ['active', 'disbursed']));

        if (count($activeLoans) >= 3) { // Max 3 active loans
            return ['allowed' => false, 'reason' => 'Maximum active loans reached'];
        }

        return ['allowed' => true, 'dsr' => $dsr];
    }

    /**
     * Upload member documents
     */
    public function uploadDocument(int $memberId, string $documentType, array $file): ?string
    {
        $member = $this->find($memberId);
        if (!$member) {
            throw new \Exception('Member not found');
        }

        $uploadResult = \App\Helpers\FileUpload::upload($file, 'members/documents/', [
            'allowed_types' => ['image/jpeg', 'image/png', 'application/pdf'],
            'max_size' => 5 * 1024 * 1024, // 5MB
            'prefix' => "member_{$memberId}_{$documentType}_",
            'check_limits' => false // Don't check tenant limits for member uploads
        ]);

        if (!$uploadResult['success']) {
            throw new \Exception('Failed to upload document: ' . $uploadResult['error']);
        }

        // Update member record with document path
        $fieldMap = [
            'ktp' => 'ktp_photo_path',
            'kk' => 'kk_photo_path',
            'selfie' => 'selfie_photo_path'
        ];

        if (isset($fieldMap[$documentType])) {
            $this->update($memberId, [$fieldMap[$documentType] => $uploadResult['path']]);
        }

        return $uploadResult['path'];
    }

    /**
     * Get members by status
     */
    public function getMembersByStatus(string $status): array
    {
        return $this->findWhere(['status' => $status], ['created_at' => 'DESC']);
    }

    /**
     * Get members by verification status
     */
    public function getMembersByVerificationStatus(string $status): array
    {
        return $this->findWhere(['verification_status' => $status], ['created_at' => 'ASC']);
    }

    /**
     * Get member statistics
     */
    public function getMemberStatistics(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_members,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_members,
                COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_members,
                COUNT(CASE WHEN verification_status = 'pending' THEN 1 END) as pending_verification,
                COUNT(CASE WHEN verification_status = 'verified' THEN 1 END) as verified_members,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_members_30d
            FROM {$this->table}
        ");
        $stmt->execute();
        return $stmt->fetch();
    }
}
