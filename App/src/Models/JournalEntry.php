<?php
namespace App\Models;

class JournalEntry extends Model
{
    protected string $table = 'journal_entries';
    protected array $fillable = [
        'journal_id', 'account_code', 'account_name',
        'debit', 'credit', 'description'
    ];
    protected array $casts = [
        'journal_id' => 'int',
        'debit' => 'float',
        'credit' => 'float',
        'created_at' => 'datetime'
    ];

    /**
     * Get entries by journal ID
     */
    public function getByJournalId(int $journalId): array
    {
        return $this->findWhere(['journal_id' => $journalId], ['id' => 'ASC']);
    }

    /**
     * Get entries by account code
     */
    public function getByAccountCode(string $accountCode, string $startDate = null, string $endDate = null): array
    {
        $conditions = ['account_code' => $accountCode];

        if ($startDate && $endDate) {
            // This would need to join with accounting_journals table
            $stmt = $this->db->prepare("
                SELECT je.* FROM {$this->table} je
                JOIN accounting_journals aj ON je.journal_id = aj.id
                WHERE je.account_code = ? AND aj.transaction_date BETWEEN ? AND ?
                ORDER BY aj.transaction_date ASC
            ");
            $stmt->execute([$accountCode, $startDate, $endDate]);
            $results = $stmt->fetchAll();
            return array_map([$this, 'castAttributes'], $results);
        }

        return $this->findWhere($conditions, ['created_at' => 'ASC']);
    }

    /**
     * Delete entries by journal ID
     */
    public function deleteByJournalId(int $journalId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE journal_id = ?");
        return $stmt->execute([$journalId]);
    }

    /**
     * Get account balance
     */
    public function getAccountBalance(string $accountCode, string $startDate = null, string $endDate = null): array
    {
        $entries = $this->getByAccountCode($accountCode, $startDate, $endDate);

        $debit = array_sum(array_column($entries, 'debit'));
        $credit = array_sum(array_column($entries, 'credit'));

        // Get account type to determine balance calculation
        $chartOfAccountsModel = new ChartOfAccounts();
        $account = $chartOfAccountsModel->findByCode($accountCode);

        $balance = 0;
        if ($account) {
            if (in_array($account['type'], ['asset', 'expense'])) {
                $balance = $debit - $credit;
            } else {
                $balance = $credit - $debit;
            }
        }

        return [
            'account_code' => $accountCode,
            'total_debit' => $debit,
            'total_credit' => $credit,
            'balance' => $balance,
            'entries_count' => count($entries)
        ];
    }

    /**
     * Get trial balance
     */
    public function getTrialBalance(string $startDate = null, string $endDate = null): array
    {
        $stmt = $this->db->prepare("
            SELECT
                account_code,
                account_name,
                SUM(debit) as total_debit,
                SUM(credit) as total_credit
            FROM {$this->table} je
            JOIN accounting_journals aj ON je.journal_id = aj.id
            WHERE aj.status = 'posted'
            " . ($startDate && $endDate ? "AND aj.transaction_date BETWEEN ? AND ?" : "") . "
            GROUP BY account_code, account_name
            ORDER BY account_code ASC
        ");

        if ($startDate && $endDate) {
            $stmt->execute([$startDate, $endDate]);
        } else {
            $stmt->execute();
        }

        $results = $stmt->fetchAll();

        // Calculate balances
        foreach ($results as &$result) {
            $chartOfAccountsModel = new ChartOfAccounts();
            $account = $chartOfAccountsModel->findByCode($result['account_code']);

            if ($account) {
                if (in_array($account['type'], ['asset', 'expense'])) {
                    $result['balance'] = $result['total_debit'] - $result['total_credit'];
                } else {
                    $result['balance'] = $result['total_credit'] - $result['total_debit'];
                }
            } else {
                $result['balance'] = 0;
            }
        }

        return $results;
    }

    /**
     * Get general ledger
     */
    public function getGeneralLedger(string $accountCode, string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare("
            SELECT
                aj.transaction_date,
                aj.reference_number,
                aj.description as journal_description,
                je.description as entry_description,
                je.debit,
                je.credit
            FROM {$this->table} je
            JOIN accounting_journals aj ON je.journal_id = aj.id
            WHERE je.account_code = ?
            AND aj.transaction_date BETWEEN ? AND ?
            AND aj.status = 'posted'
            ORDER BY aj.transaction_date ASC, aj.id ASC
        ");
        $stmt->execute([$accountCode, $startDate, $endDate]);

        $entries = $stmt->fetchAll();
        $balance = 0;

        // Calculate running balance
        foreach ($entries as &$entry) {
            $entry['debit'] = (float)$entry['debit'];
            $entry['credit'] = (float)$entry['credit'];

            // Get account type for balance calculation
            $chartOfAccountsModel = new ChartOfAccounts();
            $account = $chartOfAccountsModel->findByCode($accountCode);

            if ($account) {
                if (in_array($account['type'], ['asset', 'expense'])) {
                    $balance += $entry['debit'] - $entry['credit'];
                } else {
                    $balance += $entry['credit'] - $entry['debit'];
                }
            }

            $entry['balance'] = $balance;
        }

        return $entries;
    }
}
