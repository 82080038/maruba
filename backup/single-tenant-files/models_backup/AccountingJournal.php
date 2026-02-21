<?php
namespace App\Models;

class AccountingJournal extends Model
{
    protected string $table = 'accounting_journals';
    protected array $fillable = [
        'transaction_date', 'reference_number', 'description',
        'total_debit', 'total_credit', 'status', 'posted_by'
    ];
    protected array $casts = [
        'transaction_date' => 'datetime',
        'total_debit' => 'float',
        'total_credit' => 'float',
        'posted_by' => 'int',
        'created_at' => 'datetime'
    ];

    /**
     * Create journal with entries
     */
    public function createJournal(array $journalData, array $entries): int
    {
        // Validate entries balance
        $totalDebit = array_sum(array_column($entries, 'debit'));
        $totalCredit = array_sum(array_column($entries, 'credit'));

        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new \Exception('Journal entries do not balance: Debit ' . $totalDebit . ' vs Credit ' . $totalCredit);
        }

        $journalData['total_debit'] = $totalDebit;
        $journalData['total_credit'] = $totalCredit;

        // Generate reference number if not provided
        if (!isset($journalData['reference_number'])) {
            $journalData['reference_number'] = $this->generateReferenceNumber($journalData['transaction_date']);
        }

        $journalId = $this->create($journalData);

        // Create journal entries
        $journalEntryModel = new JournalEntry();
        foreach ($entries as $entry) {
            $entry['journal_id'] = $journalId;
            $journalEntryModel->create($entry);
        }

        return $journalId;
    }

    /**
     * Post journal (mark as posted)
     */
    public function postJournal(int $journalId, int $postedBy): bool
    {
        $journal = $this->find($journalId);
        if (!$journal || $journal['status'] !== 'draft') {
            return false;
        }

        return $this->update($journalId, [
            'status' => 'posted',
            'posted_by' => $postedBy
        ]);
    }

    /**
     * Generate reference number
     */
    private function generateReferenceNumber(string $date): string
    {
        $dateObj = new \DateTime($date);
        $yearMonth = $dateObj->format('Ym');

        // Get next sequence number for the month
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM {$this->table}
            WHERE DATE_FORMAT(transaction_date, '%Y%m') = ?
        ");
        $stmt->execute([$yearMonth]);
        $count = (int)$stmt->fetch()['count'] + 1;

        return 'JRN' . $yearMonth . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get journals by date range
     */
    public function getByDateRange(string $startDate, string $endDate): array
    {
        return $this->findWhere([], ['transaction_date' => 'DESC'])
            ->where('transaction_date', '>=', $startDate)
            ->where('transaction_date', '<=', $endDate);
    }

    /**
     * Get journal with entries
     */
    public function findWithEntries(int $id): ?array
    {
        $journal = $this->find($id);
        if (!$journal) {
            return null;
        }

        $journalEntryModel = new JournalEntry();
        $entries = $journalEntryModel->getByJournalId($id);

        $journal['entries'] = $entries;
        return $journal;
    }

    /**
     * Cancel journal
     */
    public function cancelJournal(int $journalId): bool
    {
        $journal = $this->find($journalId);
        if (!$journal || $journal['status'] !== 'draft') {
            return false;
        }

        // Delete journal entries first
        $journalEntryModel = new JournalEntry();
        $journalEntryModel->deleteByJournalId($journalId);

        // Delete journal
        return $this->delete($journalId);
    }

    /**
     * Get accounting statistics
     */
    public function getStatistics(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_journals,
                COUNT(CASE WHEN status = 'posted' THEN 1 END) as posted_journals,
                COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_journals,
                SUM(total_debit) as total_debit,
                SUM(total_credit) as total_credit,
                COUNT(DISTINCT DATE_FORMAT(transaction_date, '%Y-%m')) as active_months
            FROM {$this->table}
        ");
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Auto-create journals for common transactions
     */
    public function createLoanDisbursementJournal(int $loanId): ?int
    {
        $loanModel = new \App\Models\Loan();
        $loan = $loanModel->find($loanId);

        if (!$loan || $loan['status'] !== 'disbursed') {
            return null;
        }

        $entries = [
            [
                'account_code' => '1002', // Bank
                'account_name' => 'Bank',
                'debit' => $loan['amount'],
                'credit' => 0,
                'description' => 'Pencairan pinjaman ' . $loan['id']
            ],
            [
                'account_code' => '2001', // Simpanan Anggota
                'account_name' => 'Simpanan Anggota',
                'debit' => 0,
                'credit' => $loan['amount'],
                'description' => 'Pencairan pinjaman ' . $loan['id']
            ]
        ];

        return $this->createJournal([
            'transaction_date' => date('Y-m-d'),
            'description' => 'Pencairan Pinjaman #' . $loan['id'],
            'status' => 'draft'
        ], $entries);
    }

    public function createRepaymentJournal(int $repaymentId): ?int
    {
        $repaymentModel = new \App\Models\Repayment();
        $repayment = $repaymentModel->find($repaymentId);

        if (!$repayment) {
            return null;
        }

        $entries = [
            [
                'account_code' => '1002', // Bank
                'account_name' => 'Bank',
                'debit' => $repayment['amount_paid'],
                'credit' => 0,
                'description' => 'Angsuran pinjaman ' . $repayment['loan_id']
            ],
            [
                'account_code' => '4001', // Pendapatan Bunga Pinjaman
                'account_name' => 'Pendapatan Bunga Pinjaman',
                'debit' => 0,
                'credit' => $repayment['amount_paid'],
                'description' => 'Angsuran pinjaman ' . $repayment['loan_id']
            ]
        ];

        return $this->createJournal([
            'transaction_date' => date('Y-m-d'),
            'description' => 'Angsuran Pinjaman #' . $repayment['loan_id'],
            'status' => 'draft'
        ], $entries);
    }

    public function createSavingsDepositJournal(int $transactionId): ?int
    {
        $transactionModel = new \App\Models\SavingsTransaction();
        $transaction = $transactionModel->find($transactionId);

        if (!$transaction || $transaction['type'] !== 'deposit') {
            return null;
        }

        $entries = [
            [
                'account_code' => '1002', // Bank
                'account_name' => 'Bank',
                'debit' => $transaction['amount'],
                'credit' => 0,
                'description' => 'Setoran simpanan ' . $transaction['savings_account_id']
            ],
            [
                'account_code' => '2001', // Simpanan Anggota
                'account_name' => 'Simpanan Anggota',
                'debit' => 0,
                'credit' => $transaction['amount'],
                'description' => 'Setoran simpanan ' . $transaction['savings_account_id']
            ]
        ];

        return $this->createJournal([
            'transaction_date' => $transaction['transaction_date'],
            'description' => 'Setoran Simpanan #' . $transaction['savings_account_id'],
            'status' => 'draft'
        ], $entries);
    }
}
