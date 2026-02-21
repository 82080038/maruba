<?php
namespace App\Models;

class Compliance extends Model
{
    protected string $table = 'compliance_checks';
    protected array $fillable = [
        'entity_type', 'entity_id', 'check_type', 'status',
        'severity', 'description', 'recommendation', 'checked_by',
        'resolved_at', 'resolution_notes'
    ];
    protected array $casts = [
        'entity_id' => 'int',
        'checked_by' => 'int',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime'
    ];

    /**
     * Perform compliance checks
     */
    public function performComplianceChecks(): array
    {
        $results = [];

        // Check member compliance
        $results = array_merge($results, $this->checkMemberCompliance());

        // Check loan compliance
        $results = array_merge($results, $this->checkLoanCompliance());

        // Check savings compliance
        $results = array_merge($results, $this->checkSavingsCompliance());

        // Check accounting compliance
        $results = array_merge($results, $this->checkAccountingCompliance());

        return $results;
    }

    /**
     * Check member compliance
     */
    private function checkMemberCompliance(): array
    {
        $memberModel = new \App\Models\Member();
        $members = $memberModel->findWhere(['status' => 'active']);
        $results = [];

        foreach ($members as $member) {
            // Check mandatory savings
            $savingsModel = new \App\Models\SavingsAccount();
            $savingsAccounts = $savingsModel->getByMemberId($member['id']);
            $hasMandatorySavings = false;

            foreach ($savingsAccounts as $account) {
                if ($account['type'] === 'pokok' && $account['balance'] >= 50000) {
                    $hasMandatorySavings = true;
                    break;
                }
            }

            if (!$hasMandatorySavings) {
                $results[] = [
                    'entity_type' => 'member',
                    'entity_id' => $member['id'],
                    'check_type' => 'mandatory_savings',
                    'status' => 'failed',
                    'severity' => 'medium',
                    'description' => 'Anggota tidak memiliki simpanan pokok minimal Rp 50.000',
                    'recommendation' => 'Anggota harus membayar simpanan pokok untuk memenuhi AD/ART'
                ];
            }

            // Check outstanding loans
            $loanModel = new \App\Models\Loan();
            $memberLoans = $loanModel->findWhere(['member_id' => $member['id'], 'status' => 'default']);

            if (!empty($memberLoans)) {
                $results[] = [
                    'entity_type' => 'member',
                    'entity_id' => $member['id'],
                    'check_type' => 'loan_default',
                    'status' => 'failed',
                    'severity' => 'high',
                    'description' => 'Anggota memiliki ' . count($memberLoans) . ' pinjaman yang macet',
                    'recommendation' => 'Lakukan penagihan dan evaluasi keanggotaan'
                ];
            }
        }

        return $results;
    }

    /**
     * Check loan compliance
     */
    private function checkLoanCompliance(): array
    {
        $loanModel = new \App\Models\Loan();
        $loans = $loanModel->findWhere(['status' => ['approved', 'disbursed']]);
        $results = [];

        foreach ($loans as $loan) {
            // Check DSR compliance
            $creditAnalysisModel = new \App\Models\CreditAnalysis();
            $dsrRatio = $creditAnalysisModel->calculateDSR($loan['member_id'], $loan['amount'], $loan['tenor_months']);

            if ($dsrRatio > 60) {
                $results[] = [
                    'entity_type' => 'loan',
                    'entity_id' => $loan['id'],
                    'check_type' => 'dsr_compliance',
                    'status' => 'warning',
                    'severity' => 'medium',
                    'description' => 'DSR ratio ' . $dsrRatio . '% melebihi batas 60%',
                    'recommendation' => 'Monitor pembayaran angsuran secara ketat'
                ];
            }

            // Check loan age (loans older than 2 years)
            $loanAge = (time() - strtotime($loan['created_at'])) / (60 * 60 * 24 * 365);
            if ($loanAge > 2) {
                $results[] = [
                    'entity_type' => 'loan',
                    'entity_id' => $loan['id'],
                    'check_type' => 'loan_age',
                    'status' => 'info',
                    'severity' => 'low',
                    'description' => 'Pinjaman berusia lebih dari 2 tahun',
                    'recommendation' => 'Evaluasi performa pinjaman jangka panjang'
                ];
            }
        }

        return $results;
    }

    /**
     * Check savings compliance
     */
    private function checkSavingsCompliance(): array
    {
        $savingsModel = new \App\Models\SavingsAccount();
        $savingsAccounts = $savingsModel->findWhere(['status' => 'active']);
        $results = [];

        // Group by member
        $memberSavings = [];
        foreach ($savingsAccounts as $account) {
            $memberId = $account['member_id'];
            if (!isset($memberSavings[$memberId])) {
                $memberSavings[$memberId] = [];
            }
            $memberSavings[$memberId][] = $account;
        }

        foreach ($memberSavings as $memberId => $accounts) {
            $hasPokok = false;
            $hasWajib = false;

            foreach ($accounts as $account) {
                if ($account['type'] === 'pokok') {
                    $hasPokok = true;
                }
                if ($account['type'] === 'wajib') {
                    $hasWajib = true;
                }
            }

            if (!$hasPokok) {
                $results[] = [
                    'entity_type' => 'member',
                    'entity_id' => $memberId,
                    'check_type' => 'savings_pokok',
                    'status' => 'failed',
                    'severity' => 'high',
                    'description' => 'Anggota tidak memiliki rekening simpanan pokok',
                    'recommendation' => 'Buat rekening simpanan pokok sesuai AD/ART'
                ];
            }

            if (!$hasWajib) {
                $results[] = [
                    'entity_type' => 'member',
                    'entity_id' => $memberId,
                    'check_type' => 'savings_wajib',
                    'status' => 'warning',
                    'severity' => 'medium',
                    'description' => 'Anggota tidak memiliki rekening simpanan wajib',
                    'recommendation' => 'Aktifkan rekening simpanan wajib'
                ];
            }
        }

        return $results;
    }

    /**
     * Check accounting compliance
     */
    private function checkAccountingCompliance(): array
    {
        $journalModel = new \App\Models\AccountingJournal();
        $results = [];

        // Check for unbalanced journals
        $stmt = $this->db->prepare("
            SELECT id, reference_number, description
            FROM accounting_journals
            WHERE status = 'draft' AND (total_debit != total_credit OR total_debit = 0)
        ");
        $stmt->execute();
        $unbalancedJournals = $stmt->fetchAll();

        foreach ($unbalancedJournals as $journal) {
            $results[] = [
                'entity_type' => 'accounting_journal',
                'entity_id' => $journal['id'],
                'check_type' => 'journal_balance',
                'status' => 'failed',
                'severity' => 'high',
                'description' => 'Jurnal ' . $journal['reference_number'] . ' tidak balance',
                'recommendation' => 'Perbaiki jurnal agar debit = credit'
            ];
        }

        // Check for old draft journals
        $stmt = $this->db->prepare("
            SELECT id, reference_number, description
            FROM accounting_journals
            WHERE status = 'draft' AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        $oldDrafts = $stmt->fetchAll();

        foreach ($oldDrafts as $journal) {
            $results[] = [
                'entity_type' => 'accounting_journal',
                'entity_id' => $journal['id'],
                'check_type' => 'old_draft_journal',
                'status' => 'warning',
                'severity' => 'medium',
                'description' => 'Jurnal draft ' . $journal['reference_number'] . ' berusia >30 hari',
                'recommendation' => 'Posting atau hapus jurnal draft yang sudah lama'
            ];
        }

        return $results;
    }

    /**
     * Get KPI dashboard data
     */
    public function getKPIData(): array
    {
        $loanModel = new \App\Models\Loan();
        $memberModel = new \App\Models\Member();
        $repaymentModel = new \App\Models\Repayment();
        $savingsModel = new \App\Models\SavingsAccount();

        // Portfolio KPIs
        $totalLoans = count($loanModel->all());
        $activeLoans = count($loanModel->findWhere(['status' => ['approved', 'disbursed']]));
        $defaultLoans = count($loanModel->findWhere(['status' => 'default']));

        $nplRatio = $totalLoans > 0 ? ($defaultLoans / $totalLoans) * 100 : 0;

        // Member KPIs
        $totalMembers = count($memberModel->findWhere(['status' => 'active']));

        // Savings KPIs
        $savingsStats = $savingsModel->getStatistics();
        $totalSavings = $savingsStats['total_balance'] ?? 0;

        // Repayment KPIs
        $repaymentStats = $repaymentModel->getStatistics();
        $overdueCount = count($repaymentModel->getOverdueRepayments());

        return [
            'portfolio' => [
                'total_loans' => $totalLoans,
                'active_loans' => $activeLoans,
                'npl_ratio' => round($nplRatio, 2),
                'outstanding_amount' => $loanModel->count(['status' => ['approved', 'disbursed']])
            ],
            'members' => [
                'total_members' => $totalMembers,
                'active_members' => $totalMembers,
                'avg_savings_per_member' => $totalMembers > 0 ? $totalSavings / $totalMembers : 0
            ],
            'savings' => [
                'total_savings' => $totalSavings,
                'total_accounts' => $savingsStats['total_accounts'] ?? 0
            ],
            'repayments' => [
                'overdue_count' => $overdueCount,
                'paid_count' => $repaymentStats['paid_count'] ?? 0
            ],
            'compliance' => [
                'critical_issues' => count($this->findWhere(['severity' => 'high', 'status' => 'failed'])),
                'warning_issues' => count($this->findWhere(['severity' => 'medium', 'status' => 'failed']))
            ]
        ];
    }

    /**
     * Get risk assessment
     */
    public function getRiskAssessment(): array
    {
        $loanModel = new \App\Models\Loan();
        $repaymentModel = new \App\Models\Repayment();

        // Concentration risk (top borrower)
        $stmt = $this->db->prepare("
            SELECT m.name, COUNT(l.id) as loan_count, SUM(l.amount) as total_amount
            FROM loans l
            JOIN members m ON l.member_id = m.id
            WHERE l.status IN ('approved', 'disbursed')
            GROUP BY m.id, m.name
            ORDER BY total_amount DESC
            LIMIT 5
        ");
        $stmt->execute();
        $topBorrowers = $stmt->fetchAll();

        // Sector risk (if we had business type data)
        $sectorRisk = [];

        // Liquidity risk
        $overdueRepayments = $repaymentModel->getOverdueRepayments();
        $totalOverdue = array_sum(array_column($overdueRepayments, 'amount_due'));

        return [
            'concentration_risk' => $topBorrowers,
            'sector_risk' => $sectorRisk,
            'liquidity_risk' => [
                'overdue_amount' => $totalOverdue,
                'overdue_count' => count($overdueRepayments)
            ],
            'credit_risk' => [
                'npl_ratio' => $loanModel->getNPLRatio()
            ]
        ];
    }

    /**
     * Log compliance check result
     */
    public function logComplianceCheck(array $checkData): int
    {
        return $this->create($checkData);
    }

    /**
     * Resolve compliance issue
     */
    public function resolveIssue(int $issueId, string $resolutionNotes): bool
    {
        return $this->update($issueId, [
            'status' => 'resolved',
            'resolved_at' => date('Y-m-d H:i:s'),
            'resolution_notes' => $resolutionNotes
        ]);
    }

    /**
     * Get compliance statistics
     */
    public function getStatistics(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_checks,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_checks,
                COUNT(CASE WHEN status = 'passed' THEN 1 END) as passed_checks,
                COUNT(CASE WHEN status = 'warning' THEN 1 END) as warning_checks,
                COUNT(CASE WHEN severity = 'high' AND status = 'failed' THEN 1 END) as critical_issues,
                COUNT(CASE WHEN severity = 'medium' AND status = 'failed' THEN 1 END) as warning_issues
            FROM {$this->table}
        ");
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Generate compliance report
     */
    public function generateComplianceReport(string $periodStart, string $periodEnd): string
    {
        $checks = $this->findWhere([], ['created_at' => 'DESC']);

        // Filter by date range
        $checks = array_filter($checks, function($check) use ($periodStart, $periodEnd) {
            return $check['created_at'] >= $periodStart && $check['created_at'] <= $periodEnd;
        });

        $html = '
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <title>Laporan Kepatutan - KSP LAM GABE JAYA</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .stats { margin: 20px 0; padding: 15px; background: #f5f5f5; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .critical { background-color: #ffe6e6; }
                .warning { background-color: #fff3cd; }
                .passed { background-color: #d4edda; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="company">APLIKASI KSP</div>
                <h2>Laporan Kepatutan & Kepatuhan</h2>
                <p>Periode: ' . date('d/m/Y', strtotime($periodStart)) . ' - ' . date('d/m/Y', strtotime($periodEnd)) . '</p>
            </div>

            <div class="stats">
                <h3>Statistik Pemeriksaan</h3>
                <p>Total Pemeriksaan: ' . count($checks) . '</p>
                <p>Gagal: ' . count(array_filter($checks, fn($c) => $c['status'] === 'failed')) . '</p>
                <p>Warning: ' . count(array_filter($checks, fn($c) => $c['status'] === 'warning')) . '</p>
                <p>Lulus: ' . count(array_filter($checks, fn($c) => $c['status'] === 'passed')) . '</p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Entitas</th>
                        <th>Status</th>
                        <th>Tingkat Risiko</th>
                        <th>Deskripsi</th>
                        <th>Rekomendasi</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($checks as $check) {
            $rowClass = '';
            if ($check['status'] === 'failed' && $check['severity'] === 'high') {
                $rowClass = 'critical';
            } elseif ($check['status'] === 'failed' && $check['severity'] === 'medium') {
                $rowClass = 'warning';
            } elseif ($check['status'] === 'passed') {
                $rowClass = 'passed';
            }

            $html .= '
                <tr class="' . $rowClass . '">
                    <td>' . date('d/m/Y', strtotime($check['created_at'])) . '</td>
                    <td>' . ucfirst($check['entity_type']) . '</td>
                    <td>' . $check['entity_id'] . '</td>
                    <td>' . ucfirst($check['status']) . '</td>
                    <td>' . ucfirst($check['severity']) . '</td>
                    <td>' . htmlspecialchars($check['description']) . '</td>
                    <td>' . htmlspecialchars($check['recommendation']) . '</td>
                </tr>';
        }

        $html .= '
                </tbody>
            </table>
        </body>
        </html>';

        return $html;
    }
}
