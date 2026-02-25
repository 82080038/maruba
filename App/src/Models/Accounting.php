<?php
namespace App\Models;

class Accounting extends Model
{
    protected string $table = 'journal_entries';

    /**
     * Create journal entry for loan disbursement
     */
    public function recordLoanDisbursement(int $loanId, float $amount, int $processedBy): bool
    {
        $loanModel = new Loan();
        $loan = $loanModel->findWithDetails($loanId);

        if (!$loan) {
            return false;
        }

        $journalData = [
            'journal_number' => $this->generateJournalNumber('LN'),
            'transaction_date' => date('Y-m-d'),
            'description' => "Pencairan Pinjaman - {$loan['member_name']} - {$loan['loan_number']}",
            'reference_type' => 'loan',
            'reference_id' => $loanId,
            'status' => 'posted',
            'posted_by' => $processedBy,
            'posted_at' => date('Y-m-d H:i:s'),
            'created_by' => $processedBy
        ];

        $journalId = $this->create($journalData);

        // Debit: Piutang Pinjaman (Asset)
        $this->addJournalLine($journalId, '1101', $amount, 0, 'Piutang Pinjaman Anggota');

        // Credit: Kas/Bank (Asset)
        $this->addJournalLine($journalId, '1001', 0, $amount, 'Kas - Pencairan Pinjaman');

        return true;
    }

    /**
     * Create journal entry for loan repayment
     */
    public function recordLoanRepayment(int $repaymentId, int $processedBy): bool
    {
        $stmt = $this->db->prepare("
            SELECT lr.*, l.loan_number, l.principal_amount, m.name as member_name
            FROM loan_repayments lr
            JOIN loans l ON lr.loan_id = l.id
            JOIN members m ON lr.member_id = m.id
            WHERE lr.id = ?
        ");
        $stmt->execute([$repaymentId]);
        $repayment = $stmt->fetch();

        if (!$repayment) {
            return false;
        }

        $journalData = [
            'journal_number' => $this->generateJournalNumber('RP'),
            'transaction_date' => date('Y-m-d'),
            'description' => "Pembayaran Angsuran - {$repayment['member_name']} - {$repayment['loan_number']}",
            'reference_type' => 'repayment',
            'reference_id' => $repaymentId,
            'status' => 'posted',
            'posted_by' => $processedBy,
            'posted_at' => date('Y-m-d H:i:s'),
            'created_by' => $processedBy
        ];

        $journalId = $this->create($journalData);

        $principalAmount = $repayment['principal_amount'];
        $interestAmount = $repayment['interest_amount'];

        // Debit: Kas/Bank (Asset)
        $this->addJournalLine($journalId, '1001', $repayment['amount_paid'], 0, 'Kas - Pembayaran Angsuran');

        // Credit: Piutang Pinjaman (Asset) - Principal
        if ($principalAmount > 0) {
            $this->addJournalLine($journalId, '1101', 0, $principalAmount, 'Pelunasan Piutang Pinjaman');
        }

        // Credit: Pendapatan Bunga Pinjaman (Income) - Interest
        if ($interestAmount > 0) {
            $this->addJournalLine($journalId, '4001', 0, $interestAmount, 'Pendapatan Bunga Pinjaman');
        }

        return true;
    }

    /**
     * Create journal entry for savings deposit
     */
    public function recordSavingsDeposit(int $transactionId, int $processedBy): bool
    {
        $stmt = $this->db->prepare("
            SELECT st.*, m.name as member_name, sa.account_number
            FROM savings_transactions st
            JOIN savings_accounts sa ON st.account_id = sa.id
            JOIN members m ON st.member_id = m.id
            WHERE st.id = ?
        ");
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            return false;
        }

        $journalData = [
            'journal_number' => $this->generateJournalNumber('SD'),
            'transaction_date' => $transaction['transaction_date'],
            'description' => "Setoran Simpanan - {$transaction['member_name']} - {$transaction['account_number']}",
            'reference_type' => 'savings',
            'reference_id' => $transactionId,
            'status' => 'posted',
            'posted_by' => $processedBy,
            'posted_at' => date('Y-m-d H:i:s'),
            'created_by' => $processedBy
        ];

        $journalId = $this->create($journalData);

        // Debit: Kas/Bank (Asset)
        $this->addJournalLine($journalId, '1001', $transaction['amount'], 0, 'Kas - Setoran Simpanan');

        // Credit: Simpanan Anggota (Liability)
        $this->addJournalLine($journalId, '2001', 0, $transaction['amount'], 'Simpanan Anggota');

        return true;
    }

    /**
     * Create journal entry for savings withdrawal
     */
    public function recordSavingsWithdrawal(int $transactionId, int $processedBy): bool
    {
        $stmt = $this->db->prepare("
            SELECT st.*, m.name as member_name, sa.account_number
            FROM savings_transactions st
            JOIN savings_accounts sa ON st.account_id = sa.id
            JOIN members m ON st.member_id = m.id
            WHERE st.id = ?
        ");
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            return false;
        }

        $journalData = [
            'journal_number' => $this->generateJournalNumber('SW'),
            'transaction_date' => $transaction['transaction_date'],
            'description' => "Penarikan Simpanan - {$transaction['member_name']} - {$transaction['account_number']}",
            'reference_type' => 'savings',
            'reference_id' => $transactionId,
            'status' => 'posted',
            'posted_by' => $processedBy,
            'posted_at' => date('Y-m-d H:i:s'),
            'created_by' => $processedBy
        ];

        $journalId = $this->create($journalData);

        // Debit: Simpanan Anggota (Liability)
        $this->addJournalLine($journalId, '2001', $transaction['amount'], 0, 'Simpanan Anggota');

        // Credit: Kas/Bank (Asset)
        $this->addJournalLine($journalId, '1001', 0, $transaction['amount'], 'Kas - Penarikan Simpanan');

        return true;
    }

    /**
     * Add journal line
     */
    private function addJournalLine(int $journalId, string $accountCode, float $debit, float $credit, string $description): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO journal_lines (journal_id, account_id, debit, credit, description, created_at)
            SELECT ?, id, ?, ?, ?, NOW()
            FROM chart_of_accounts
            WHERE code = ?
        ");
        $stmt->execute([$journalId, $debit, $credit, $description, $accountCode]);
    }

    /**
     * Generate journal number
     */
    private function generateJournalNumber(string $prefix): string
    {
        $date = date('ymd');
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM {$this->table}
            WHERE journal_number LIKE ?
        ");
        $stmt->execute(["{$prefix}{$date}%"]);

        $sequence = $stmt->fetch()['count'] + 1;
        return sprintf('%s%s%04d', $prefix, $date, $sequence);
    }

    /**
     * Get general ledger for account
     */
    public function getGeneralLedger(string $accountCode, string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare("
            SELECT
                jl.created_at as date,
                je.journal_number,
                je.description,
                jl.debit,
                jl.credit,
                je.reference_type,
                je.reference_id
            FROM journal_lines jl
            JOIN journal_entries je ON jl.journal_id = je.id
            JOIN chart_of_accounts coa ON jl.account_id = coa.id
            WHERE coa.code = ?
            AND je.transaction_date BETWEEN ? AND ?
            AND je.status = 'posted'
            ORDER BY je.transaction_date ASC, je.created_at ASC
        ");
        $stmt->execute([$accountCode, $startDate, $endDate]);
        $entries = $stmt->fetchAll();

        // Calculate running balance
        $balance = 0;
        foreach ($entries as &$entry) {
            if ($entry['debit'] > 0) {
                $balance += $entry['debit'];
                $entry['balance'] = $balance;
            } elseif ($entry['credit'] > 0) {
                $balance -= $entry['credit'];
                $entry['balance'] = $balance;
            }
        }

        return $entries;
    }

    /**
     * Get trial balance
     */
    public function getTrialBalance(string $asOfDate): array
    {
        $stmt = $this->db->prepare("
            SELECT
                coa.code,
                coa.name,
                coa.type,
                COALESCE(SUM(jl.debit), 0) as total_debit,
                COALESCE(SUM(jl.credit), 0) as total_credit,
                (COALESCE(SUM(jl.debit), 0) - COALESCE(SUM(jl.credit), 0)) as balance
            FROM chart_of_accounts coa
            LEFT JOIN journal_lines jl ON coa.id = jl.account_id
            LEFT JOIN journal_entries je ON jl.journal_id = je.id
            WHERE je.transaction_date <= ? OR je.transaction_date IS NULL
            GROUP BY coa.id, coa.code, coa.name, coa.type
            HAVING total_debit > 0 OR total_credit > 0
            ORDER BY coa.code ASC
        ");
        $stmt->execute([$asOfDate]);
        return $stmt->fetchAll();
    }

    /**
     * Generate income statement
     */
    public function generateIncomeStatement(string $startDate, string $endDate): array
    {
        // Revenue accounts (4000 series)
        $stmt = $this->db->prepare("
            SELECT
                coa.name,
                COALESCE(SUM(jl.credit - jl.debit), 0) as amount
            FROM chart_of_accounts coa
            LEFT JOIN journal_lines jl ON coa.id = jl.account_id
            LEFT JOIN journal_entries je ON jl.journal_id = je.id
            WHERE coa.type = 'income'
            AND je.transaction_date BETWEEN ? AND ?
            AND je.status = 'posted'
            GROUP BY coa.id, coa.name
            HAVING amount != 0
            ORDER BY coa.code ASC
        ");
        $stmt->execute([$startDate, $endDate]);
        $revenues = $stmt->fetchAll();

        // Expense accounts (5000 series)
        $stmt = $this->db->prepare("
            SELECT
                coa.name,
                COALESCE(SUM(jl.debit - jl.credit), 0) as amount
            FROM chart_of_accounts coa
            LEFT JOIN journal_lines jl ON coa.id = jl.account_id
            LEFT JOIN journal_entries je ON jl.journal_id = je.id
            WHERE coa.type = 'expense'
            AND je.transaction_date BETWEEN ? AND ?
            AND je.status = 'posted'
            GROUP BY coa.id, coa.name
            HAVING amount != 0
            ORDER BY coa.code ASC
        ");
        $stmt->execute([$startDate, $endDate]);
        $expenses = $stmt->fetchAll();

        $totalRevenue = array_sum(array_column($revenues, 'amount'));
        $totalExpenses = array_sum(array_column($expenses, 'amount'));
        $netIncome = $totalRevenue - $totalExpenses;

        return [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'revenues' => $revenues,
            'total_revenue' => $totalRevenue,
            'expenses' => $expenses,
            'total_expenses' => $totalExpenses,
            'net_income' => $netIncome
        ];
    }

    /**
     * Generate balance sheet
     */
    public function generateBalanceSheet(string $asOfDate): array
    {
        $stmt = $this->db->prepare("
            SELECT
                coa.type,
                coa.category,
                coa.name,
                CASE
                    WHEN coa.type IN ('asset', 'expense') THEN COALESCE(SUM(jl.debit - jl.credit), 0)
                    WHEN coa.type IN ('liability', 'equity', 'income') THEN COALESCE(SUM(jl.credit - jl.debit), 0)
                    ELSE 0
                END as balance
            FROM chart_of_accounts coa
            LEFT JOIN journal_lines jl ON coa.id = jl.account_id
            LEFT JOIN journal_entries je ON jl.journal_id = je.id
            WHERE (je.transaction_date <= ? OR je.transaction_date IS NULL)
            AND je.status = 'posted'
            GROUP BY coa.id, coa.type, coa.category, coa.name
            HAVING balance != 0
            ORDER BY coa.type ASC, coa.category ASC, coa.code ASC
        ");
        $stmt->execute([$asOfDate]);
        $accounts = $stmt->fetchAll();

        // Group by type
        $grouped = [];
        foreach ($accounts as $account) {
            $type = $account['type'];
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = $account;
        }

        // Calculate totals
        $assets = array_sum(array_column($grouped['asset'] ?? [], 'balance'));
        $liabilities = array_sum(array_column($grouped['liability'] ?? [], 'balance'));
        $equity = array_sum(array_column($grouped['equity'] ?? [], 'balance'));

        return [
            'as_of_date' => $asOfDate,
            'accounts' => $grouped,
            'totals' => [
                'assets' => $assets,
                'liabilities' => $liabilities,
                'equity' => $equity,
                'liabilities_and_equity' => $liabilities + $equity
            ]
        ];
    }

    /**
     * Generate cash flow statement
     */
    public function generateCashFlowStatement(string $startDate, string $endDate): array
    {
        // Operating activities
        $stmt = $this->db->prepare("
            SELECT
                CASE
                    WHEN je.reference_type = 'repayment' THEN 'loan_repayments'
                    WHEN je.reference_type = 'savings' THEN 'savings_transactions'
                    ELSE 'other'
                END as activity_type,
                SUM(CASE WHEN jl.account_id = (SELECT id FROM chart_of_accounts WHERE code = '1001') THEN jl.debit - jl.credit ELSE 0 END) as cash_change
            FROM journal_entries je
            JOIN journal_lines jl ON je.id = jl.journal_id
            WHERE je.transaction_date BETWEEN ? AND ?
            AND je.status = 'posted'
            GROUP BY activity_type
        ");
        $stmt->execute([$startDate, $endDate]);
        $activities = $stmt->fetchAll();

        $operatingCash = 0;
        $investingCash = 0;
        $financingCash = 0;

        foreach ($activities as $activity) {
            switch ($activity['activity_type']) {
                case 'loan_repayments':
                case 'savings_transactions':
                    $operatingCash += $activity['cash_change'];
                    break;
            }
        }

        $netCashFlow = $operatingCash + $investingCash + $financingCash;

        return [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'operating_activities' => $operatingCash,
            'investing_activities' => $investingCash,
            'financing_activities' => $financingCash,
            'net_cash_flow' => $netCashFlow
        ];
    }

    /**
     * Get accounting statistics
     */
    public function getAccountingStatistics(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_journals,
                COUNT(CASE WHEN status = 'posted' THEN 1 END) as posted_journals,
                COUNT(DISTINCT DATE(transaction_date)) as active_days,
                SUM(CASE WHEN reference_type = 'loan' THEN 1 ELSE 0 END) as loan_transactions,
                SUM(CASE WHEN reference_type = 'savings' THEN 1 ELSE 0 END) as savings_transactions,
                SUM(CASE WHEN reference_type = 'repayment' THEN 1 ELSE 0 END) as repayment_transactions
            FROM {$this->table}
        ");
        $stmt->execute();
        return $stmt->fetch();
    }
}
