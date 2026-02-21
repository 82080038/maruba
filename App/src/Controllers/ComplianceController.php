<?php
namespace App\Controllers;

use App\Models\Compliance;
use App\Models\AuditLog;
use App\Helpers\AuthHelper;

class ComplianceController
{
    public function index(): void
    {
        require_login();
        AuthHelper::requirePermission('compliance', 'view');

        $complianceModel = new Compliance();
        $kpiData = $complianceModel->getKPIData();
        $statistics = $complianceModel->getStatistics();

        include view_path('compliance/index');
    }

    public function runChecks(): void
    {
        require_login();
        AuthHelper::requirePermission('compliance', 'run_checks');

        $complianceModel = new Compliance();

        try {
            $results = $complianceModel->performComplianceChecks();

            // Log the results
            foreach ($results as $result) {
                $complianceModel->logComplianceCheck($result);
            }

            $_SESSION['success'] = 'Pemeriksaan kepatuhan selesai. Ditemukan ' . count($results) . ' temuan.';

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal menjalankan pemeriksaan kepatuhan: ' . $e->getMessage();
        }

        header('Location: ' . route_url('compliance'));
    }

    public function issues(): void
    {
        require_login();
        AuthHelper::requirePermission('compliance', 'view');

        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 15);
        $status = $_GET['status'] ?? null;
        $severity = $_GET['severity'] ?? null;

        $complianceModel = new Compliance();

        $conditions = [];
        if ($status) {
            $conditions['status'] = $status;
        }
        if ($severity) {
            $conditions['severity'] = $severity;
        }

        $result = $complianceModel->paginate($page, $limit, $conditions);

        include view_path('compliance/issues');
    }

    public function showIssue(): void
    {
        require_login();
        AuthHelper::requirePermission('compliance', 'view');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(404);
            echo 'Issue not found';
            return;
        }

        $complianceModel = new Compliance();
        $issue = $complianceModel->find($id);

        if (!$issue) {
            http_response_code(404);
            echo 'Issue not found';
            return;
        }

        // Get related entity info
        $entityInfo = $this->getEntityInfo($issue['entity_type'], $issue['entity_id']);

        include view_path('compliance/show_issue');
    }

    public function resolveIssue(): void
    {
        require_login();
        AuthHelper::requirePermission('compliance', 'resolve');

        $id = (int)($_POST['issue_id'] ?? 0);
        $resolutionNotes = trim($_POST['resolution_notes'] ?? '');

        if (!$id) {
            $_SESSION['error'] = 'ID issue diperlukan.';
            header('Location: ' . route_url('compliance/issues'));
            return;
        }

        $complianceModel = new Compliance();

        try {
            $success = $complianceModel->resolveIssue($id, $resolutionNotes);

            if ($success) {
                $_SESSION['success'] = 'Issue berhasil diselesaikan.';
            } else {
                $_SESSION['error'] = 'Gagal menyelesaikan issue.';
            }

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error resolving issue: ' . $e->getMessage();
        }

        header('Location: ' . route_url('compliance/show-issue') . '?id=' . $id);
    }

    public function riskAssessment(): void
    {
        require_login();
        AuthHelper::requirePermission('compliance', 'view');

        $complianceModel = new Compliance();
        $riskData = $complianceModel->getRiskAssessment();

        include view_path('compliance/risk_assessment');
    }

    public function auditTrail(): void
    {
        require_login();
        AuthHelper::requirePermission('audit', 'view');

        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 50);
        $userId = (int)($_GET['user_id'] ?? 0);
        $action = $_GET['action'] ?? '';
        $entity = $_GET['entity'] ?? '';

        $auditModel = new AuditLog();

        $conditions = [];
        if ($userId) {
            $conditions['user_id'] = $userId;
        }
        if ($action) {
            $conditions['action'] = $action;
        }
        if ($entity) {
            $conditions['entity'] = $entity;
        }

        $result = $auditModel->paginate($page, $limit, $conditions);

        // Add user information
        foreach ($result['items'] as &$log) {
            $userModel = new \App\Models\User();
            $user = $userModel->find($log['user_id']);
            $log['user_name'] = $user ? $user['name'] : 'Unknown';
        }

        include view_path('compliance/audit_trail');
    }

    public function generateReport(): void
    {
        require_login();
        AuthHelper::requirePermission('compliance', 'view');

        $periodStart = $_GET['start_date'] ?? date('Y-m-01');
        $periodEnd = $_GET['end_date'] ?? date('Y-m-t');

        $complianceModel = new Compliance();
        $reportHtml = $complianceModel->generateComplianceReport($periodStart, $periodEnd);

        // Output as HTML
        echo $reportHtml;
        exit;
    }

    // ===== API ENDPOINTS =====
    public function getKPIDataApi(): void
    {
        require_login();

        $complianceModel = new Compliance();
        $kpiData = $complianceModel->getKPIData();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'kpi_data' => $kpiData]);
    }

    public function getComplianceStatsApi(): void
    {
        require_login();

        $complianceModel = new Compliance();
        $stats = $complianceModel->getStatistics();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'stats' => $stats]);
    }

    public function getRiskDataApi(): void
    {
        require_login();

        $complianceModel = new Compliance();
        $riskData = $complianceModel->getRiskAssessment();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'risk_data' => $riskData]);
    }

    public function runComplianceCheckApi(): void
    {
        require_login();
        AuthHelper::requirePermission('compliance', 'run_checks');

        $complianceModel = new Compliance();

        try {
            $results = $complianceModel->performComplianceChecks();

            // Log results
            foreach ($results as $result) {
                $complianceModel->logComplianceCheck($result);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Compliance check completed',
                'total_issues' => count($results),
                'results' => $results
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function getAuditLogsApi(): void
    {
        require_login();
        AuthHelper::requirePermission('audit', 'view');

        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 50);

        $auditModel = new AuditLog();
        $result = $auditModel->paginate($page, $limit);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'audit_logs' => $result]);
    }

    // ===== UTILITY METHODS =====
    private function getEntityInfo(string $type, int $id): ?array
    {
        switch ($type) {
            case 'member':
                $memberModel = new \App\Models\Member();
                $member = $memberModel->find($id);
                return $member ? [
                    'name' => $member['name'],
                    'type' => 'Anggota',
                    'link' => route_url('members/show') . '?id=' . $id
                ] : null;

            case 'loan':
                $loanModel = new \App\Models\Loan();
                $loan = $loanModel->find($id);
                if ($loan) {
                    $memberModel = new \App\Models\Member();
                    $member = $memberModel->find($loan['member_id']);
                    return [
                        'name' => 'Pinjaman #' . $loan['id'],
                        'type' => 'Pinjaman',
                        'member_name' => $member ? $member['name'] : 'Unknown',
                        'amount' => $loan['amount'],
                        'link' => route_url('loans/show') . '?id=' . $id
                    ];
                }
                break;

            case 'accounting_journal':
                $journalModel = new \App\Models\AccountingJournal();
                $journal = $journalModel->find($id);
                return $journal ? [
                    'name' => $journal['reference_number'],
                    'type' => 'Jurnal Akuntansi',
                    'description' => $journal['description'],
                    'link' => route_url('accounting/show-journal') . '?id=' . $id
                ] : null;
        }

        return null;
    }
}
