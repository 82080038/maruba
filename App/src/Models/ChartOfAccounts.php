<?php
namespace App\Models;

class ChartOfAccounts extends Model
{
    protected string $table = 'chart_of_accounts';
    protected array $fillable = [
        'code', 'name', 'type', 'category', 'is_active'
    ];
    protected array $casts = [
        'is_active' => 'bool',
        'created_at' => 'datetime'
    ];

    /**
     * Find account by code
     */
    public function findByCode(string $code): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE code = ?");
        $stmt->execute([$code]);
        $result = $stmt->fetch();

        return $result ? $this->castAttributes($result) : null;
    }

    /**
     * Get accounts by type
     */
    public function getByType(string $type): array
    {
        return $this->findWhere(['type' => $type, 'is_active' => true], ['code' => 'ASC']);
    }

    /**
     * Get active accounts
     */
    public function getActiveAccounts(): array
    {
        return $this->findWhere(['is_active' => true], ['code' => 'ASC']);
    }

    /**
     * Get accounts hierarchy
     */
    public function getHierarchy(): array
    {
        $accounts = $this->getActiveAccounts();
        $hierarchy = [];

        foreach ($accounts as $account) {
            $type = $account['type'];
            $category = $account['category'] ?? 'other';

            if (!isset($hierarchy[$type])) {
                $hierarchy[$type] = [];
            }

            if (!isset($hierarchy[$type][$category])) {
                $hierarchy[$type][$category] = [];
            }

            $hierarchy[$type][$category][] = $account;
        }

        return $hierarchy;
    }

    /**
     * Validate account code format
     */
    public function validateAccountCode(string $code): bool
    {
        // Basic validation: should be numeric, 4-6 digits
        return preg_match('/^\d{4,6}$/', $code);
    }

    /**
     * Check if account code exists
     */
    public function accountCodeExists(string $code, int $excludeId = null): bool
    {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE code = ?";
        $params = [$code];

        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        return (int)$stmt->fetch()['count'] > 0;
    }

    /**
     * Create account with validation
     */
    public function create(array $data): int
    {
        // Validate account code
        if (!$this->validateAccountCode($data['code'])) {
            throw new \Exception('Invalid account code format. Must be 4-6 digits.');
        }

        // Check if code already exists
        if ($this->accountCodeExists($data['code'])) {
            throw new \Exception('Account code already exists.');
        }

        return parent::create($data);
    }

    /**
     * Update account with validation
     */
    public function update(int $id, array $data): bool
    {
        // If updating code, validate it
        if (isset($data['code'])) {
            if (!$this->validateAccountCode($data['code'])) {
                throw new \Exception('Invalid account code format. Must be 4-6 digits.');
            }

            if ($this->accountCodeExists($data['code'], $id)) {
                throw new \Exception('Account code already exists.');
            }
        }

        return parent::update($id, $data);
    }

    /**
     * Deactivate account
     */
    public function deactivateAccount(int $id): bool
    {
        // Check if account has any journal entries
        $journalEntryModel = new JournalEntry();
        $entries = $journalEntryModel->getByAccountCode($this->find($id)['code']);

        if (!empty($entries)) {
            throw new \Exception('Cannot deactivate account with existing journal entries.');
        }

        return $this->update($id, ['is_active' => false]);
    }

    /**
     * Get account balance summary
     */
    public function getBalanceSummary(string $startDate = null, string $endDate = null): array
    {
        $accounts = $this->getActiveAccounts();
        $summary = [
            'asset' => 0,
            'liability' => 0,
            'equity' => 0,
            'income' => 0,
            'expense' => 0
        ];

        $journalEntryModel = new JournalEntry();

        foreach ($accounts as $account) {
            $balance = $journalEntryModel->getAccountBalance($account['code'], $startDate, $endDate);
            $summary[$account['type']] += $balance['balance'];
        }

        return $summary;
    }

    /**
     * Get financial position (Balance Sheet)
     */
    public function getBalanceSheet(string $date = null): array
    {
        $date = $date ?: date('Y-m-d');
        $summary = $this->getBalanceSummary(null, $date);

        return [
            'assets' => $summary['asset'],
            'liabilities' => $summary['liability'],
            'equity' => $summary['equity'],
            'total_liabilities_equity' => $summary['liability'] + $summary['equity'],
            'date' => $date
        ];
    }

    /**
     * Get profit/loss statement
     */
    public function getIncomeStatement(string $startDate, string $endDate): array
    {
        $summary = $this->getBalanceSummary($startDate, $endDate);

        return [
            'income' => $summary['income'],
            'expenses' => $summary['expense'],
            'net_profit' => $summary['income'] - $summary['expense'],
            'start_date' => $startDate,
            'end_date' => $endDate
        ];
    }

    /**
     * Search accounts
     */
    public function search(string $query): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE (code LIKE ? OR name LIKE ?) AND is_active = 1
            ORDER BY code ASC
            LIMIT 20
        ");
        $searchTerm = "%$query%";
        $stmt->execute([$searchTerm, $searchTerm]);

        $results = $stmt->fetchAll();
        return array_map([$this, 'castAttributes'], $results);
    }
}
