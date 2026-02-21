<?php
namespace App\Models;

class RiskManagement extends Model
{
    protected string $table = 'risk_assessments';

    /**
     * Assess portfolio risk
     */
    public function assessPortfolioRisk(int $tenantId): array
    {
        // Get loan portfolio data
        $loanModel = new Loan();
        $loanStats = $loanModel->getLoanStatistics();

        $totalLoans = $loanStats['total_loans'] ?? 0;
        $outstandingBalance = $loanStats['total_outstanding'] ?? 0;
        $nplRatio = $loanModel->getNPLRatio();

        // Concentration risk (largest borrower)
        $stmt = $this->db->prepare("
            SELECT MAX(outstanding_balance) as largest_loan,
                   (MAX(outstanding_balance) / ?) * 100 as concentration_ratio
            FROM loans WHERE status IN ('disbursed', 'active')
        ");
        $stmt->execute([$outstandingBalance]);
        $concentration = $stmt->fetch();

        // Geographic concentration
        $stmt = $this->db->prepare("
            SELECT city, COUNT(*) as loan_count,
                   SUM(outstanding_balance) as total_balance,
                   (SUM(outstanding_balance) / ?) * 100 as percentage
            FROM loans l
            JOIN members m ON l.member_id = m.id
            WHERE l.status IN ('disbursed', 'active')
            GROUP BY city
            HAVING percentage > 20
            ORDER BY percentage DESC
        ");
        $stmt->execute([$outstandingBalance]);
        $geographicConcentration = $stmt->fetchAll();

        // Sector concentration
        $stmt = $this->db->prepare("
            SELECT m.occupation, COUNT(*) as loan_count,
                   SUM(l.outstanding_balance) as total_balance,
                   (SUM(l.outstanding_balance) / ?) * 100 as percentage
            FROM loans l
            JOIN members m ON l.member_id = m.id
            WHERE l.status IN ('disbursed', 'active')
            GROUP BY m.occupation
            HAVING percentage > 15
            ORDER BY percentage DESC
        ");
        $stmt->execute([$outstandingBalance]);
        $sectorConcentration = $stmt->fetchAll();

        // Calculate overall portfolio risk score
        $riskScore = 0;

        // NPL ratio risk
        if ($nplRatio > 10) $riskScore += 40;
        elseif ($nplRatio > 5) $riskScore += 20;

        // Concentration risk
        $concentrationRatio = $concentration['concentration_ratio'] ?? 0;
        if ($concentrationRatio > 20) $riskScore += 20;
        elseif ($concentrationRatio > 10) $riskScore += 10;

        // Geographic concentration risk
        if (count($geographicConcentration) > 2) $riskScore += 10;

        // Sector concentration risk
        if (count($sectorConcentration) > 3) $riskScore += 10;

        $riskLevel = $this->calculateRiskLevel($riskScore);

        return [
            'assessment_type' => 'portfolio',
            'risk_score' => min(100, $riskScore),
            'risk_level' => $riskLevel,
            'factors' => [
                'npl_ratio' => round($nplRatio, 2),
                'concentration_ratio' => round($concentrationRatio, 2),
                'geographic_concentration' => count($geographicConcentration),
                'sector_concentration' => count($sectorConcentration)
            ],
            'recommendations' => $this->getPortfolioRiskRecommendations($riskScore),
            'assessed_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Assess member risk
     */
    public function assessMemberRisk(int $memberId): array
    {
        $memberModel = new Member();
        $member = $memberModel->find($memberId);

        if (!$member) {
            return ['error' => 'Member not found'];
        }

        $riskScore = 0;
        $riskFactors = [];

        // Credit history factor
        $loans = $memberModel->getLoans($memberId);
        $totalLoans = count($loans);
        $defaultedLoans = count(array_filter($loans, fn($l) => $l['status'] === 'defaulted'));

        if ($defaultedLoans > 0) {
            $riskScore += 30;
            $riskFactors[] = "Has {$defaultedLoans} defaulted loans";
        }

        // DSR (Debt Service Ratio)
        $dsr = $memberModel->calculateDSR($memberId);
        if ($dsr > 60) {
            $riskScore += 25;
            $riskFactors[] = "High DSR: {$dsr}%";
        } elseif ($dsr > 40) {
            $riskScore += 15;
            $riskFactors[] = "Moderate DSR: {$dsr}%";
        }

        // Income stability
        if (empty($member['monthly_income']) || $member['monthly_income'] < 1000000) {
            $riskScore += 20;
            $riskFactors[] = "Low or unknown income";
        }

        // Employment stability
        if (empty($member['occupation'])) {
            $riskScore += 10;
            $riskFactors[] = "Unknown occupation";
        }

        // Age factor (too young or too old)
        $birthDate = new \DateTime($member['birth_date']);
        $age = $birthDate->diff(new \DateTime())->y;

        if ($age < 21 || $age > 65) {
            $riskScore += 10;
            $riskFactors[] = "Age outside optimal range: {$age} years";
        }

        $riskLevel = $this->calculateRiskLevel($riskScore);

        return [
            'assessment_type' => 'member',
            'reference_id' => $memberId,
            'risk_score' => min(100, $riskScore),
            'risk_level' => $riskLevel,
            'risk_factors' => $riskFactors,
            'mitigation_plan' => $this->getMemberRiskMitigation($riskScore),
            'assessed_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Assess loan risk
     */
    public function assessLoanRisk(int $loanId): array
    {
        $loanModel = new Loan();
        $loan = $loanModel->findWithDetails($loanId);

        if (!$loan) {
            return ['error' => 'Loan not found'];
        }

        $memberModel = new Member();
        $memberRisk = $this->assessMemberRisk($loan['member_id']);

        $riskScore = $memberRisk['risk_score']; // Base risk from member
        $riskFactors = $memberRisk['risk_factors'];

        // Loan-specific risk factors
        $loanAmount = $loan['principal_amount'];
        $memberIncome = $loan['monthly_income'] ?? 0;

        // Loan to income ratio
        if ($memberIncome > 0) {
            $ltiRatio = ($loanAmount / $memberIncome) * 100;
            if ($ltiRatio > 300) {
                $riskScore += 25;
                $riskFactors[] = "Very high LTI ratio: {$ltiRatio}%";
            } elseif ($ltiRatio > 200) {
                $riskScore += 15;
                $riskFactors[] = "High LTI ratio: {$ltiRatio}%";
            }
        }

        // Loan term risk
        $tenor = $loan['tenor_months'];
        if ($tenor > 36) {
            $riskScore += 10;
            $riskFactors[] = "Long tenor: {$tenor} months";
        }

        // Collateral quality
        if (empty($loan['collateral_details'])) {
            $riskScore += 20;
            $riskFactors[] = "No collateral provided";
        }

        $riskLevel = $this->calculateRiskLevel($riskScore);

        return [
            'assessment_type' => 'loan',
            'reference_id' => $loanId,
            'risk_score' => min(100, $riskScore),
            'risk_level' => $riskLevel,
            'risk_factors' => $riskFactors,
            'mitigation_plan' => $this->getLoanRiskMitigation($riskScore),
            'assessed_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Assess system risk
     */
    public function assessSystemRisk(int $tenantId): array
    {
        $riskScore = 0;
        $riskFactors = [];

        // Database backup status
        $stmt = $this->db->prepare("
            SELECT MAX(created_at) as last_backup
            FROM tenant_backups
            WHERE tenant_id = ? AND status = 'completed'
        ");
        $stmt->execute([$tenantId]);
        $backup = $stmt->fetch();

        if ($backup && $backup['last_backup']) {
            $lastBackup = strtotime($backup['last_backup']);
            $daysSinceBackup = (time() - $lastBackup) / (60 * 60 * 24);

            if ($daysSinceBackup > 7) {
                $riskScore += 20;
                $riskFactors[] = "Last backup {$daysSinceBackup} days ago";
            }
        } else {
            $riskScore += 30;
            $riskFactors[] = "No recent backups found";
        }

        // User access control
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as admin_count
            FROM users
            WHERE tenant_id = ? AND role = 'admin' AND status = 'active'
        ");
        $stmt->execute([$tenantId]);
        $adminCount = $stmt->fetch()['admin_count'];

        if ($adminCount < 2) {
            $riskScore += 15;
            $riskFactors[] = "Only {$adminCount} active administrators";
        }

        // Audit trail completeness
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as audit_count
            FROM audit_logs
            WHERE tenant_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stmt->execute([$tenantId]);
        $auditCount = $stmt->fetch()['audit_count'];

        if ($auditCount < 10) {
            $riskScore += 10;
            $riskFactors[] = "Low audit activity: {$auditCount} events in 7 days";
        }

        $riskLevel = $this->calculateRiskLevel($riskScore);

        return [
            'assessment_type' => 'system',
            'reference_id' => $tenantId,
            'risk_score' => min(100, $riskScore),
            'risk_level' => $riskLevel,
            'risk_factors' => $riskFactors,
            'mitigation_plan' => $this->getSystemRiskMitigation($riskScore),
            'assessed_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Calculate risk level from score
     */
    private function calculateRiskLevel(int $score): string
    {
        if ($score >= 70) return 'critical';
        if ($score >= 50) return 'high';
        if ($score >= 30) return 'medium';
        return 'low';
    }

    /**
     * Get portfolio risk recommendations
     */
    private function getPortfolioRiskRecommendations(int $riskScore): array
    {
        $recommendations = [];

        if ($riskScore >= 50) {
            $recommendations[] = "Implement stricter lending criteria";
            $recommendations[] = "Increase loan monitoring frequency";
            $recommendations[] = "Diversify loan portfolio across sectors";
        }

        if ($riskScore >= 30) {
            $recommendations[] = "Review collection procedures";
            $recommendations[] = "Strengthen risk assessment process";
        }

        $recommendations[] = "Regular portfolio stress testing";
        $recommendations[] = "Maintain adequate loan loss provisions";

        return $recommendations;
    }

    /**
     * Get member risk mitigation
     */
    private function getMemberRiskMitigation(int $riskScore): string
    {
        if ($riskScore >= 70) {
            return "High-risk member: Require additional collateral, shorten loan terms, increase monitoring frequency";
        } elseif ($riskScore >= 50) {
            return "Medium-high risk: Require collateral, regular financial reporting, monthly monitoring";
        } elseif ($riskScore >= 30) {
            return "Medium risk: Regular monitoring, quarterly financial reviews";
        } else {
            return "Low risk: Standard monitoring procedures";
        }
    }

    /**
     * Get loan risk mitigation
     */
    private function getLoanRiskMitigation(int $riskScore): string
    {
        if ($riskScore >= 70) {
            return "High-risk loan: Require 150% collateral coverage, weekly monitoring, personal guarantee";
        } elseif ($riskScore >= 50) {
            return "Medium-high risk: Require 125% collateral, monthly monitoring, additional documentation";
        } elseif ($riskScore >= 30) {
            return "Medium risk: Require collateral, quarterly monitoring";
        } else {
            return "Low risk: Standard loan procedures";
        }
    }

    /**
     * Get system risk mitigation
     */
    private function getSystemRiskMitigation(int $riskScore): string
    {
        if ($riskScore >= 50) {
            return "Critical system risks: Immediate action required - review security protocols, implement daily backups, add redundant systems";
        } elseif ($riskScore >= 30) {
            return "High system risks: Implement automated backups, review access controls, conduct security audit";
        } else {
            return "Low system risks: Maintain regular backup schedule, monitor system logs";
        }
    }

    /**
     * Save risk assessment
     */
    public function saveAssessment(array $assessment, int $assessedBy): int
    {
        return $this->create([
            'assessment_type' => $assessment['assessment_type'],
            'reference_id' => $assessment['reference_id'] ?? null,
            'risk_score' => $assessment['risk_score'],
            'risk_level' => $assessment['risk_level'],
            'risk_factors' => json_encode($assessment['risk_factors']),
            'mitigation_plan' => $assessment['mitigation_plan'] ?? '',
            'assessed_by' => $assessedBy,
            'review_date' => date('Y-m-d', strtotime('+6 months'))
        ]);
    }

    /**
     * Get risk dashboard data
     */
    public function getRiskDashboard(int $tenantId): array
    {
        // Recent assessments
        $recentAssessments = $this->findWhere([], ['assessed_at' => 'DESC'], 10);

        // Risk distribution
        $stmt = $this->db->prepare("
            SELECT risk_level, COUNT(*) as count
            FROM {$this->table}
            GROUP BY risk_level
        ");
        $stmt->execute();
        $distribution = $stmt->fetchAll();

        // Critical risks requiring attention
        $criticalRisks = $this->findWhere(['risk_level' => 'critical'], ['assessed_at' => 'DESC'], 5);

        return [
            'recent_assessments' => $recentAssessments,
            'risk_distribution' => $distribution,
            'critical_risks' => $criticalRisks,
            'overall_risk_score' => $this->calculateOverallRiskScore(),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Calculate overall risk score
     */
    private function calculateOverallRiskScore(): float
    {
        $stmt = $this->db->prepare("
            SELECT
                AVG(CASE
                    WHEN risk_level = 'critical' THEN 100
                    WHEN risk_level = 'high' THEN 75
                    WHEN risk_level = 'medium' THEN 50
                    WHEN risk_level = 'low' THEN 25
                    ELSE 0
                END) as avg_risk_score
            FROM {$this->table}
            WHERE assessed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        $result = $stmt->fetch();

        return round($result['avg_risk_score'] ?? 0, 2);
    }
}
