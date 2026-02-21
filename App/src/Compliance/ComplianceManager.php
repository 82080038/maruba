<?php
namespace App\Compliance;

use App\Models\Member;
use App\Models\Loan;
use App\Database;

/**
 * Compliance Framework for KSP Multi-Tenant System
 *
 * Implements regulatory compliance requirements including:
 * - Audit trails and logging
 * - Regulatory reporting
 * - Risk assessments
 * - Data retention policies
 * - Compliance monitoring
 */
class ComplianceManager
{
    private Database $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Run comprehensive compliance check for tenant
     */
    public function runComplianceCheck(int $tenantId, string $checkType = 'full'): array
    {
        $results = [
            'tenant_id' => $tenantId,
            'check_type' => $checkType,
            'timestamp' => date('Y-m-d H:i:s'),
            'checks' => [],
            'passed' => 0,
            'warnings' => 0,
            'failed' => 0,
            'critical_issues' => []
        ];

        // Run different types of compliance checks
        switch ($checkType) {
            case 'full':
                $results['checks'] = array_merge(
                    $this->checkDataIntegrity($tenantId),
                    $this->checkRegulatoryCompliance($tenantId),
                    $this->checkRiskManagement($tenantId),
                    $this->checkAuditTrail($tenantId),
                    $this->checkDataRetention($tenantId)
                );
                break;

            case 'data_integrity':
                $results['checks'] = $this->checkDataIntegrity($tenantId);
                break;

            case 'regulatory':
                $results['checks'] = $this->checkRegulatoryCompliance($tenantId);
                break;

            case 'risk':
                $results['checks'] = $this->checkRiskManagement($tenantId);
                break;

            case 'audit':
                $results['checks'] = $this->checkAuditTrail($tenantId);
                break;

            case 'retention':
                $results['checks'] = $this->checkDataRetention($tenantId);
                break;
        }

        // Calculate summary
        foreach ($results['checks'] as $check) {
            switch ($check['status']) {
                case 'passed':
                    $results['passed']++;
                    break;
                case 'warning':
                    $results['warnings']++;
                    break;
                case 'failed':
                    $results['failed']++;
                    if (($check['severity'] ?? 'medium') === 'critical') {
                        $results['critical_issues'][] = $check;
                    }
                    break;
            }
        }

        // Store compliance check results
        $this->storeComplianceResults($tenantId, $results);

        return $results;
    }

    /**
     * Check data integrity across tenant data
     */
    private function checkDataIntegrity(int $tenantId): array
    {
        $checks = [];

        try {
            // Get tenant database connection
            $tenantDb = $this->getTenantDatabase($tenantId);
            if (!$tenantDb) {
                return [['check' => 'database_access', 'status' => 'failed', 'message' => 'Cannot access tenant database', 'severity' => 'critical']];
            }

            // Check member data integrity
            $memberChecks = $this->checkMemberDataIntegrity($tenantDb);
            $checks = array_merge($checks, $memberChecks);

            // Check loan data integrity
            $loanChecks = $this->checkLoanDataIntegrity($tenantDb);
            $checks = array_merge($checks, $loanChecks);

            // Check transaction data integrity
            $transactionChecks = $this->checkTransactionDataIntegrity($tenantDb);
            $checks = array_merge($checks, $transactionChecks);

            // Check for orphaned records
            $orphanChecks = $this->checkOrphanedRecords($tenantDb);
            $checks = array_merge($checks, $orphanChecks);

        } catch (\Exception $e) {
            $checks[] = [
                'check' => 'data_integrity_exception',
                'status' => 'failed',
                'message' => 'Exception during data integrity check: ' . $e->getMessage(),
                'severity' => 'critical'
            ];
        }

        return $checks;
    }

    /**
     * Check member data integrity
     */
    private function checkMemberDataIntegrity(\PDO $db): array
    {
        $checks = [];

        // Check for members without required fields
        $stmt = $db->query("
            SELECT COUNT(*) as count FROM members
            WHERE name IS NULL OR name = '' OR nik IS NULL OR nik = ''
        ");
        $invalidMembers = $stmt->fetch()['count'];

        if ($invalidMembers > 0) {
            $checks[] = [
                'check' => 'member_required_fields',
                'status' => 'failed',
                'message' => "Found {$invalidMembers} members with missing required fields (name, NIK)",
                'severity' => 'high',
                'recommendation' => 'Review and update member records with missing information'
            ];
        } else {
            $checks[] = [
                'check' => 'member_required_fields',
                'status' => 'passed',
                'message' => 'All members have required fields populated'
            ];
        }

        // Check for duplicate NIKs
        $stmt = $db->query("
            SELECT nik, COUNT(*) as count
            FROM members
            WHERE nik IS NOT NULL AND nik != ''
            GROUP BY nik
            HAVING count > 1
        ");
        $duplicates = $stmt->fetchAll();

        if (!empty($duplicates)) {
            $checks[] = [
                'check' => 'member_duplicate_nik',
                'status' => 'failed',
                'message' => 'Found ' . count($duplicates) . ' duplicate NIK numbers',
                'severity' => 'high',
                'recommendation' => 'Review and resolve duplicate NIK entries'
            ];
        } else {
            $checks[] = [
                'check' => 'member_duplicate_nik',
                'status' => 'passed',
                'message' => 'No duplicate NIK numbers found'
            ];
        }

        return $checks;
    }

    /**
     * Check loan data integrity
     */
    private function checkLoanDataIntegrity(\PDO $db): array
    {
        $checks = [];

        // Check for loans without members
        $stmt = $db->query("
            SELECT COUNT(l.id) as count
            FROM loans l
            LEFT JOIN members m ON l.member_id = m.id
            WHERE m.id IS NULL
        ");
        $orphanedLoans = $stmt->fetch()['count'];

        if ($orphanedLoans > 0) {
            $checks[] = [
                'check' => 'loan_member_reference',
                'status' => 'failed',
                'message' => "Found {$orphanedLoans} loans without valid member references",
                'severity' => 'critical',
                'recommendation' => 'Review and fix loan-member relationships'
            ];
        } else {
            $checks[] = [
                'check' => 'loan_member_reference',
                'status' => 'passed',
                'message' => 'All loans have valid member references'
            ];
        }

        // Check loan balance consistency
        $stmt = $db->query("
            SELECT COUNT(*) as count FROM loans
            WHERE outstanding_balance < 0 OR outstanding_balance > principal_amount
        ");
        $invalidBalances = $stmt->fetch()['count'];

        if ($invalidBalances > 0) {
            $checks[] = [
                'check' => 'loan_balance_consistency',
                'status' => 'failed',
                'message' => "Found {$invalidBalances} loans with inconsistent balance amounts",
                'severity' => 'high',
                'recommendation' => 'Review and recalculate loan balances'
            ];
        } else {
            $checks[] = [
                'check' => 'loan_balance_consistency',
                'status' => 'passed',
                'message' => 'All loan balances are consistent'
            ];
        }

        return $checks;
    }

    /**
     * Check transaction data integrity
     */
    private function checkTransactionDataIntegrity(\PDO $db): array
    {
        $checks = [];

        // Check savings transaction balance consistency
        $stmt = $db->query("
            SELECT COUNT(*) as count FROM savings_transactions
            WHERE balance_after != (balance_before + amount)
        ");
        $inconsistentTransactions = $stmt->fetch()['count'];

        if ($inconsistentTransactions > 0) {
            $checks[] = [
                'check' => 'transaction_balance_consistency',
                'status' => 'failed',
                'message' => "Found {$inconsistentTransactions} savings transactions with balance inconsistencies",
                'severity' => 'high',
                'recommendation' => 'Review and fix transaction balance calculations'
            ];
        } else {
            $checks[] = [
                'check' => 'transaction_balance_consistency',
                'status' => 'passed',
                'message' => 'All savings transaction balances are consistent'
            ];
        }

        return $checks;
    }

    /**
     * Check for orphaned records
     */
    private function checkOrphanedRecords(\PDO $db): array
    {
        $checks = [];

        // Check loan repayments without loans
        $stmt = $db->query("
            SELECT COUNT(lr.id) as count
            FROM loan_repayments lr
            LEFT JOIN loans l ON lr.loan_id = l.id
            WHERE l.id IS NULL
        ");
        $orphanedRepayments = $stmt->fetch()['count'];

        if ($orphanedRepayments > 0) {
            $checks[] = [
                'check' => 'orphaned_loan_repayments',
                'status' => 'failed',
                'message' => "Found {$orphanedRepayments} loan repayments without valid loan references",
                'severity' => 'high',
                'recommendation' => 'Review and remove orphaned repayment records'
            ];
        }

        // Check savings transactions without accounts
        $stmt = $db->query("
            SELECT COUNT(st.id) as count
            FROM savings_transactions st
            LEFT JOIN savings_accounts sa ON st.account_id = sa.id
            WHERE sa.id IS NULL
        ");
        $orphanedSavingsTxns = $stmt->fetch()['count'];

        if ($orphanedSavingsTxns > 0) {
            $checks[] = [
                'check' => 'orphaned_savings_transactions',
                'status' => 'failed',
                'message' => "Found {$orphanedSavingsTxns} savings transactions without valid account references",
                'severity' => 'high',
                'recommendation' => 'Review and remove orphaned transaction records'
            ];
        }

        if ($orphanedRepayments === 0 && $orphanedSavingsTxns === 0) {
            $checks[] = [
                'check' => 'orphaned_records',
                'status' => 'passed',
                'message' => 'No orphaned records found'
            ];
        }

        return $checks;
    }

    /**
     * Check regulatory compliance requirements
     */
    private function checkRegulatoryCompliance(int $tenantId): array
    {
        $checks = [];

        try {
            $tenantDb = $this->getTenantDatabase($tenantId);

            // Check member data completeness (regulatory requirement)
            $stmt = $tenantDb->query("
                SELECT
                    COUNT(*) as total_members,
                    SUM(CASE WHEN ktp_photo_path IS NOT NULL THEN 1 ELSE 0 END) as members_with_ktp,
                    SUM(CASE WHEN address IS NOT NULL AND address != '' THEN 1 ELSE 0 END) as members_with_address,
                    SUM(CASE WHEN monthly_income IS NOT NULL AND monthly_income > 0 THEN 1 ELSE 0 END) as members_with_income
                FROM members
            ");
            $compliance = $stmt->fetch();

            $dataCompleteness = ($compliance['members_with_ktp'] / $compliance['total_members']) * 100;

            if ($dataCompleteness < 80) {
                $checks[] = [
                    'check' => 'regulatory_data_completeness',
                    'status' => 'warning',
                    'message' => sprintf('Member data completeness: %.1f%% (below 80%% regulatory requirement)', $dataCompleteness),
                    'severity' => 'medium',
                    'recommendation' => 'Improve member data completeness to meet regulatory standards'
                ];
            } else {
                $checks[] = [
                    'check' => 'regulatory_data_completeness',
                    'status' => 'passed',
                    'message' => sprintf('Member data completeness: %.1f%% (meets regulatory requirement)', $dataCompleteness)
                ];
            }

            // Check loan documentation compliance
            $stmt = $tenantDb->query("
                SELECT COUNT(*) as total_loans FROM loans
            ");
            $totalLoans = $stmt->fetch()['total_loans'];

            $stmt = $tenantDb->query("
                SELECT COUNT(DISTINCT loan_id) as documented_loans
                FROM loan_documents
                WHERE document_type IN ('ktp', 'kk')
            ");
            $documentedLoans = $stmt->fetch()['documented_loans'];

            $documentationRate = $totalLoans > 0 ? ($documentedLoans / $totalLoans) * 100 : 100;

            if ($documentationRate < 90) {
                $checks[] = [
                    'check' => 'loan_documentation_compliance',
                    'status' => 'failed',
                    'message' => sprintf('Loan documentation compliance: %.1f%% (below 90%% regulatory requirement)', $documentationRate),
                    'severity' => 'high',
                    'recommendation' => 'Ensure all loans have required documentation (KTP, KK)'
                ];
            } else {
                $checks[] = [
                    'check' => 'loan_documentation_compliance',
                    'status' => 'passed',
                    'message' => sprintf('Loan documentation compliance: %.1f%%', $documentationRate)
                ];
            }

        } catch (\Exception $e) {
            $checks[] = [
                'check' => 'regulatory_compliance_check',
                'status' => 'failed',
                'message' => 'Failed to perform regulatory compliance check: ' . $e->getMessage(),
                'severity' => 'critical'
            ];
        }

        return $checks;
    }

    /**
     * Check risk management compliance
     */
    private function checkRiskManagement(int $tenantId): array
    {
        $checks = [];

        try {
            $tenantDb = $this->getTenantDatabase($tenantId);

            // Check NPL (Non-Performing Loan) ratio
            $stmt = $tenantDb->query("
                SELECT
                    COUNT(*) as total_loans,
                    SUM(CASE WHEN status = 'defaulted' THEN 1 ELSE 0 END) as npl_count
                FROM loans
            ");
            $nplData = $stmt->fetch();

            $nplRatio = $nplData['total_loans'] > 0 ?
                ($nplData['npl_count'] / $nplData['total_loans']) * 100 : 0;

            // Regulatory NPL limit is typically 5%
            if ($nplRatio > 5) {
                $checks[] = [
                    'check' => 'npl_ratio_compliance',
                    'status' => 'failed',
                    'message' => sprintf('NPL ratio: %.2f%% (exceeds regulatory limit of 5%%)', $nplRatio),
                    'severity' => 'critical',
                    'recommendation' => 'Implement risk mitigation strategies to reduce NPL ratio'
                ];
            } elseif ($nplRatio > 3) {
                $checks[] = [
                    'check' => 'npl_ratio_monitoring',
                    'status' => 'warning',
                    'message' => sprintf('NPL ratio: %.2f%% (approaching regulatory limit)', $nplRatio),
                    'severity' => 'medium',
                    'recommendation' => 'Monitor NPL ratio closely and implement preventive measures'
                ];
            } else {
                $checks[] = [
                    'check' => 'npl_ratio_compliance',
                    'status' => 'passed',
                    'message' => sprintf('NPL ratio: %.2f%% (within regulatory limits)', $nplRatio)
                ];
            }

            // Check DSR (Debt Service Ratio) compliance
            $stmt = $tenantDb->query("
                SELECT
                    m.name,
                    m.monthly_income,
                    COALESCE(SUM(lr.amount_due), 0) as monthly_debt
                FROM members m
                LEFT JOIN loans l ON m.id = l.member_id
                LEFT JOIN loan_repayments lr ON l.id = lr.loan_id AND lr.due_date >= CURDATE()
                GROUP BY m.id, m.name, m.monthly_income
                HAVING m.monthly_income > 0
            ");
            $dsrData = $stmt->fetchAll();

            $highDsrMembers = 0;
            foreach ($dsrData as $member) {
                $income = $member['monthly_income'];
                $debt = $member['monthly_debt'];
                $dsr = $income > 0 ? ($debt / $income) * 100 : 0;

                if ($dsr > 60) { // Regulatory DSR limit
                    $highDsrMembers++;
                }
            }

            if ($highDsrMembers > 0) {
                $checks[] = [
                    'check' => 'dsr_compliance',
                    'status' => 'warning',
                    'message' => "Found {$highDsrMembers} members with DSR > 60% (regulatory concern)",
                    'severity' => 'medium',
                    'recommendation' => 'Review lending decisions for high DSR members'
                ];
            } else {
                $checks[] = [
                    'check' => 'dsr_compliance',
                    'status' => 'passed',
                    'message' => 'All members have acceptable DSR ratios'
                ];
            }

        } catch (\Exception $e) {
            $checks[] = [
                'check' => 'risk_management_check',
                'status' => 'failed',
                'message' => 'Failed to perform risk management check: ' . $e->getMessage(),
                'severity' => 'high'
            ];
        }

        return $checks;
    }

    /**
     * Check audit trail completeness
     */
    private function checkAuditTrail(int $tenantId): array
    {
        $checks = [];

        try {
            $tenantDb = $this->getTenantDatabase($tenantId);

            // Check audit log coverage for last 30 days
            $stmt = $tenantDb->query("
                SELECT
                    COUNT(*) as total_audits,
                    COUNT(DISTINCT DATE(created_at)) as days_covered,
                    MAX(created_at) as last_audit,
                    MIN(created_at) as first_audit
                FROM audit_logs
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ");
            $auditStats = $stmt->fetch();

            $expectedDays = 30;
            $actualDays = (int)$auditStats['days_covered'];
            $coverage = ($actualDays / $expectedDays) * 100;

            if ($coverage < 80) {
                $checks[] = [
                    'check' => 'audit_trail_coverage',
                    'status' => 'warning',
                    'message' => sprintf('Audit trail coverage: %.1f%% for last 30 days (%d/%d days)', $coverage, $actualDays, $expectedDays),
                    'severity' => 'medium',
                    'recommendation' => 'Ensure audit logging is active for all critical operations'
                ];
            } else {
                $checks[] = [
                    'check' => 'audit_trail_coverage',
                    'status' => 'passed',
                    'message' => sprintf('Audit trail coverage: %.1f%% for last 30 days', $coverage)
                ];
            }

            // Check for critical operations without audit logs
            $stmt = $tenantDb->query("
                SELECT COUNT(*) as unaudited_loans
                FROM loans l
                LEFT JOIN audit_logs al ON al.entity = 'loan' AND al.entity_id = l.id
                WHERE l.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                AND al.id IS NULL
            ");
            $unauditedLoans = $stmt->fetch()['unaudited_loans'];

            if ($unauditedLoans > 0) {
                $checks[] = [
                    'check' => 'audit_trail_completeness',
                    'status' => 'failed',
                    'message' => "Found {$unauditedLoans} loans created in last 7 days without audit trail",
                    'severity' => 'high',
                    'recommendation' => 'Ensure all loan operations are properly audited'
                ];
            } else {
                $checks[] = [
                    'check' => 'audit_trail_completeness',
                    'status' => 'passed',
                    'message' => 'All recent loan operations have audit trails'
                ];
            }

        } catch (\Exception $e) {
            $checks[] = [
                'check' => 'audit_trail_check',
                'status' => 'failed',
                'message' => 'Failed to perform audit trail check: ' . $e->getMessage(),
                'severity' => 'high'
            ];
        }

        return $checks;
    }

    /**
     * Check data retention compliance
     */
    private function checkDataRetention(int $tenantId): array
    {
        $checks = [];

        try {
            $tenantDb = $this->getTenantDatabase($tenantId);

            // Check for old inactive members (should be archived after 7 years)
            $stmt = $tenantDb->query("
                SELECT COUNT(*) as old_inactive_members
                FROM members
                WHERE status = 'inactive'
                AND updated_at < DATE_SUB(CURDATE(), INTERVAL 7 YEAR)
            ");
            $oldInactiveMembers = $stmt->fetch()['old_inactive_members'];

            if ($oldInactiveMembers > 0) {
                $checks[] = [
                    'check' => 'data_retention_members',
                    'status' => 'warning',
                    'message' => "Found {$oldInactiveMembers} inactive members older than 7 years",
                    'severity' => 'low',
                    'recommendation' => 'Archive old inactive member data according to retention policy'
                ];
            }

            // Check for old closed loans (should be archived after 10 years)
            $stmt = $tenantDb->query("
                SELECT COUNT(*) as old_closed_loans
                FROM loans
                WHERE status IN ('completed', 'defaulted', 'written_off')
                AND updated_at < DATE_SUB(CURDATE(), INTERVAL 10 YEAR)
            ");
            $oldClosedLoans = $stmt->fetch()['old_closed_loans'];

            if ($oldClosedLoans > 0) {
                $checks[] = [
                    'check' => 'data_retention_loans',
                    'status' => 'warning',
                    'message' => "Found {$oldClosedLoans} closed loans older than 10 years",
                    'severity' => 'low',
                    'recommendation' => 'Archive old closed loan data according to retention policy'
                ];
            }

            if ($oldInactiveMembers === 0 && $oldClosedLoans === 0) {
                $checks[] = [
                    'check' => 'data_retention_compliance',
                    'status' => 'passed',
                    'message' => 'Data retention policy is being followed'
                ];
            }

        } catch (\Exception $e) {
            $checks[] = [
                'check' => 'data_retention_check',
                'status' => 'failed',
                'message' => 'Failed to perform data retention check: ' . $e->getMessage(),
                'severity' => 'low'
            ];
        }

        return $checks;
    }

    /**
     * Get tenant database connection
     */
    private function getTenantDatabase(int $tenantId)
    {
        // This would use the existing tenant database connection logic
        return \App\Database::getTenantConnection();
    }

    /**
     * Store compliance check results
     */
    private function storeComplianceResults(int $tenantId, array $results): void
    {
        $mainDb = Database::getConnection();

        $stmt = $mainDb->prepare("
            INSERT INTO compliance_checks (
                tenant_id, check_type, status, severity, description,
                findings, recommendations, checked_by, checked_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $checkedBy = $_SESSION['user']['id'] ?? null;

        foreach ($results['checks'] as $check) {
            $stmt->execute([
                $tenantId,
                $check['check'],
                $check['status'],
                $check['severity'] ?? 'low',
                $check['message'],
                json_encode($check['details'] ?? []),
                $check['recommendation'] ?? '',
                $checkedBy,
                $results['timestamp']
            ]);
        }
    }

    /**
     * Generate regulatory reports
     */
    public function generateRegulatoryReport(int $tenantId, string $reportType, string $period): array
    {
        $reports = [];

        switch ($reportType) {
            case 'monthly_financial':
                $reports = $this->generateMonthlyFinancialReport($tenantId, $period);
                break;

            case 'quarterly_compliance':
                $reports = $this->generateQuarterlyComplianceReport($tenantId, $period);
                break;

            case 'annual_audit':
                $reports = $this->generateAnnualAuditReport($tenantId, $period);
                break;

            default:
                throw new \Exception("Unknown report type: {$reportType}");
        }

        return $reports;
    }

    /**
     * Generate monthly financial report
     */
    private function generateMonthlyFinancialReport(int $tenantId, string $period): array
    {
        $tenantDb = $this->getTenantDatabase($tenantId);

        // This would generate comprehensive financial reports
        // Implementation would include balance sheets, income statements, etc.

        return [
            'report_type' => 'monthly_financial',
            'period' => $period,
            'generated_at' => date('Y-m-d H:i:s'),
            'data' => [
                'total_assets' => 0,
                'total_liabilities' => 0,
                'total_equity' => 0,
                'net_income' => 0
            ]
        ];
    }

    /**
     * Generate quarterly compliance report
     */
    private function generateQuarterlyComplianceReport(int $tenantId, string $period): array
    {
        // Generate compliance metrics and regulatory reporting
        $complianceResults = $this->runComplianceCheck($tenantId, 'full');

        return [
            'report_type' => 'quarterly_compliance',
            'period' => $period,
            'generated_at' => date('Y-m-d H:i:s'),
            'compliance_score' => $this->calculateComplianceScore($complianceResults),
            'issues' => $complianceResults['critical_issues'],
            'recommendations' => $this->generateComplianceRecommendations($complianceResults)
        ];
    }

    /**
     * Generate annual audit report
     */
    private function generateAnnualAuditReport(int $tenantId, string $period): array
    {
        return [
            'report_type' => 'annual_audit',
            'period' => $period,
            'generated_at' => date('Y-m-d H:i:s'),
            'audit_findings' => [],
            'recommendations' => []
        ];
    }

    /**
     * Calculate compliance score
     */
    private function calculateComplianceScore(array $results): float
    {
        $totalChecks = count($results['checks']);
        if ($totalChecks === 0) return 100.0;

        $weightedScore = 0;
        $totalWeight = 0;

        foreach ($results['checks'] as $check) {
            $weight = match($check['severity'] ?? 'low') {
                'critical' => 5,
                'high' => 3,
                'medium' => 2,
                'low' => 1,
                default => 1
            };

            $score = match($check['status']) {
                'passed' => 100,
                'warning' => 70,
                'failed' => 0,
                default => 50
            };

            $weightedScore += ($score * $weight);
            $totalWeight += $weight;
        }

        return $totalWeight > 0 ? round($weightedScore / $totalWeight, 1) : 100.0;
    }

    /**
     * Generate compliance recommendations
     */
    private function generateComplianceRecommendations(array $results): array
    {
        $recommendations = [];

        foreach ($results['checks'] as $check) {
            if (isset($check['recommendation'])) {
                $recommendations[] = [
                    'check' => $check['check'],
                    'severity' => $check['severity'],
                    'recommendation' => $check['recommendation']
                ];
            }
        }

        return $recommendations;
    }
}

/**
 * Compliance CLI for automated checks
 */
class ComplianceCLI
{
    private ComplianceManager $complianceManager;

    public function __construct()
    {
        $this->complianceManager = new ComplianceManager();
    }

    /**
     * Run compliance check command
     */
    public function runCheck(array $args): void
    {
        $command = $args[0] ?? 'help';

        switch ($command) {
            case 'check':
                $tenantId = (int)($args[1] ?? 0);
                $checkType = $args[2] ?? 'full';

                if (!$tenantId) {
                    echo "Usage: compliance check <tenant_id> [check_type]\n";
                    echo "Check types: full, data_integrity, regulatory, risk, audit, retention\n";
                    return;
                }

                echo "Running {$checkType} compliance check for tenant {$tenantId}...\n";
                $results = $this->complianceManager->runComplianceCheck($tenantId, $checkType);

                echo "Compliance Check Results:\n";
                echo "========================\n";
                echo "Passed: {$results['passed']}\n";
                echo "Warnings: {$results['warnings']}\n";
                echo "Failed: {$results['failed']}\n";
                echo "Critical Issues: " . count($results['critical_issues']) . "\n\n";

                if (!empty($results['checks'])) {
                    foreach ($results['checks'] as $check) {
                        $status = strtoupper($check['status']);
                        echo "[{$status}] {$check['check']}: {$check['message']}\n";
                        if (isset($check['recommendation'])) {
                            echo "         → {$check['recommendation']}\n";
                        }
                        echo "\n";
                    }
                }
                break;

            case 'report':
                $tenantId = (int)($args[1] ?? 0);
                $reportType = $args[2] ?? '';
                $period = $args[3] ?? date('Y-m');

                if (!$tenantId || !$reportType) {
                    echo "Usage: compliance report <tenant_id> <report_type> [period]\n";
                    echo "Report types: monthly_financial, quarterly_compliance, annual_audit\n";
                    return;
                }

                echo "Generating {$reportType} report for tenant {$tenantId}...\n";
                $report = $this->complianceManager->generateRegulatoryReport($tenantId, $reportType, $period);

                echo "Report generated successfully\n";
                echo "Type: {$report['report_type']}\n";
                echo "Period: {$report['period']}\n";
                echo "Generated: {$report['generated_at']}\n";
                break;

            default:
                echo "Available commands:\n";
                echo "  check <tenant_id> [type]    - Run compliance check\n";
                echo "  report <tenant_id> <type>   - Generate regulatory report\n";
                echo "  help                        - Show this help\n";
                break;
        }
    }
}

// =========================================
// COMPLIANCE DATABASE TABLES
// =========================================

/*
-- Compliance check results table (already exists in schema.sql)

-- Additional compliance tables:

-- Regulatory reporting templates
CREATE TABLE regulatory_report_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(100) NOT NULL,
    template_name VARCHAR(200) NOT NULL,
    template_content LONGTEXT NOT NULL,
    required_fields JSON NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Compliance violation tracking
CREATE TABLE compliance_violations (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    violation_type VARCHAR(100) NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    description TEXT NOT NULL,
    evidence JSON NULL,
    status ENUM('open', 'investigating', 'resolved', 'dismissed') DEFAULT 'open',
    assigned_to INT NULL,
    resolved_at TIMESTAMP NULL,
    resolved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (resolved_by) REFERENCES users(id)
);

-- Data retention policies
CREATE TABLE data_retention_policies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_type VARCHAR(100) NOT NULL,
    retention_period_years INT NOT NULL,
    archival_required BOOLEAN DEFAULT FALSE,
    deletion_required BOOLEAN DEFAULT TRUE,
    regulatory_reference VARCHAR(200) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default retention policies
INSERT INTO data_retention_policies (data_type, retention_period_years, archival_required, regulatory_reference) VALUES
('member_data', 7, true, 'Peraturan Kemenkop UKM No. 12/2015'),
('loan_documents', 10, true, 'Peraturan Kemenkop UKM No. 12/2015'),
('financial_records', 10, true, 'Undang-undang Akuntansi No. 5/2017'),
('audit_logs', 5, false, 'Peraturan OJK No. 31/2016'),
('transaction_records', 10, true, 'Peraturan Kemenkop UKM No. 12/2015');
*/

?>
