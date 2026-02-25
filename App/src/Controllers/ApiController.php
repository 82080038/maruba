<?php
namespace App\Controllers;

use App\Models\Member;
use App\Models\Survey;
use App\Models\Loan;
use App\Models\Tenant;
use App\Models\TenantBilling;
use App\Models\Payment;

class ApiController
{
    public function members(): void
    {
        // API tidak butuh session
        session_write_close();
        header('Content-Type: application/json');

        $memberModel = new Member();

        // Get current tenant ID for filtering (if authenticated)
        $tenantId = $this->getCurrentTenantId();

        if ($tenantId === null) {
            // Public API - only show members with coordinates (no sensitive data)
            $members = $memberModel->findWhere([
                'lat' => ['IS NOT NULL', ''],
                'lng' => ['IS NOT NULL', ''],
                'status' => 'active'  // Only active members
            ], ['name' => 'ASC']);
        } else {
            // Tenant API - show tenant's members with coordinates
            $members = $memberModel->findWhere([
                'tenant_id' => $tenantId,
                'lat' => ['IS NOT NULL', ''],
                'lng' => ['IS NOT NULL', ''],
                'status' => 'active'
            ], ['name' => 'ASC']);
        }

        echo json_encode($members);
    }

    public function surveys(): void
    {
        session_write_close();
        header('Content-Type: application/json');

        $surveyModel = new Survey();

        // Get current tenant ID for filtering (if authenticated)
        $tenantId = $this->getCurrentTenantId();

        if ($tenantId === null) {
            // Public API - only show completed surveys with coordinates
            $surveys = $surveyModel->getCompletedSurveys();
        } else {
            // Tenant API - show tenant's surveys with coordinates
            // This needs to be implemented in Survey model with tenant filtering
            $surveys = $this->getTenantSurveysWithGeo($tenantId);
        }

        // Filter only surveys with geo coordinates
        $surveysWithGeo = array_filter($surveys, function($survey) {
            return $survey['geo_lat'] !== null && $survey['geo_lng'] !== null;
        });

        echo json_encode($surveysWithGeo);
    }

    public function updateMemberGeo(): void
    {
        require_login();
        header('Content-Type: application/json');

        $id = (int)($_POST['id'] ?? 0);
        $lat = (float)($_POST['lat'] ?? 0);
        $lng = (float)($_POST['lng'] ?? 0);

        if (!$id || !($lat >= -90 && $lat <= 90) || !($lng >= -180 && $lng <= 180)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input']);
            return;
        }

        $memberModel = new Member();
        $success = $memberModel->update($id, ['lat' => $lat, 'lng' => $lng]);

        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Update failed']);
        }
    }

    public function updateSurveyGeo(): void
    {
        require_login();
        header('Content-Type: application/json');

        $id = (int)($_POST['id'] ?? 0);
        $lat = (float)($_POST['lat'] ?? 0);
        $lng = (float)($_POST['lng'] ?? 0);

        if (!$id || !($lat >= -90 && $lat <= 90) || !($lng >= -180 && $lng <= 180)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input']);
            return;
        }

        $surveyModel = new Survey();
        $success = $surveyModel->update($id, ['geo_lat' => $lat, 'geo_lng' => $lng]);

        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Update failed']);
        }
    }

    // ===== MEMBERS API =====
    public function getMembers(): void
    {
        require_login();
        header('Content-Type: application/json');

        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 15);
        $search = $_GET['search'] ?? null;

        $memberModel = new Member();

        if ($search) {
            $members = $memberModel->search($search, $limit);
            echo json_encode(['items' => $members]);
        } else {
            $result = $memberModel->paginate($page, $limit);
            echo json_encode($result);
        }
    }

    public function getMember(): void
    {
        require_login();
        header('Content-Type: application/json');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Member ID required']);
            return;
        }

        $memberModel = new Member();
        $member = $memberModel->findWithLoanSummary($id);

        if (!$member) {
            http_response_code(404);
            echo json_encode(['error' => 'Member not found']);
            return;
        }

        echo json_encode($member);
    }

    public function createMember(): void
    {
        require_login();
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            return;
        }

        $memberModel = new Member();
        $id = $memberModel->create($data);

        echo json_encode(['id' => $id, 'success' => true]);
    }

    public function updateMember(): void
    {
        require_login();
        header('Content-Type: application/json');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Member ID required']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            return;
        }

        $memberModel = new Member();
        $success = $memberModel->update($id, $data);

        echo json_encode(['success' => $success]);
    }

    // ===== LOANS API =====
    public function getLoans(): void
    {
        require_login();
        header('Content-Type: application/json');

        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 15);
        $status = $_GET['status'] ?? null;

        $loanModel = new Loan();
        $conditions = $status ? ['status' => $status] : [];
        $result = $loanModel->paginate($page, $limit, $conditions);

        echo json_encode($result);
    }

    public function getLoan(): void
    {
        require_login();
        header('Content-Type: application/json');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Loan ID required']);
            return;
        }

        $loanModel = new Loan();
        $loan = $loanModel->findWithDetails($id);

        if (!$loan) {
            http_response_code(404);
            echo json_encode(['error' => 'Loan not found']);
            return;
        }

        echo json_encode($loan);
    }

    public function createLoan(): void
    {
        require_login();
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            return;
        }

        $loanModel = new Loan();
        $id = $loanModel->create($data);

        echo json_encode(['id' => $id, 'success' => true]);
    }

    public function updateLoan(): void
    {
        require_login();
        header('Content-Type: application/json');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Loan ID required']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            return;
        }

        $loanModel = new Loan();
        $success = $loanModel->update($id, $data);

        echo json_encode(['success' => $success]);
    }

    // ===== DASHBOARD API =====
    public function dashboard(): void
    {
        require_login();
        header('Content-Type: application/json');

        $loanModel = new Loan();
        $memberModel = new Member();
        
        // Simple metrics without complex models
        $pdo = \App\Database::getConnection();
        
        // Outstanding loans
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM loans WHERE status IN ('approved', 'disbursed')");
        $stmt->execute();
        $outstanding = $stmt->fetch()['count'];
        
        // Active members
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM members WHERE status = 'active'");
        $stmt->execute();
        $activeMembers = $stmt->fetch()['count'];
        
        // Running loans
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM loans WHERE status IN ('draft', 'survey', 'review', 'approved', 'disbursed')");
        $stmt->execute();
        $runningLoans = $stmt->fetch()['count'];
        
        // Simple NPL calculation
        $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'default' THEN 1 ELSE 0 END) as npl FROM loans");
        $stmt->execute();
        $result = $stmt->fetch();
        $nplRatio = $result['total'] > 0 ? round(($result['npl'] / $result['total']) * 100, 1) : 0;

        echo json_encode([
            'metrics' => [
                ['label' => 'Outstanding', 'value' => $outstanding, 'type' => 'number'],
                ['label' => 'Anggota Aktif', 'value' => $activeMembers, 'type' => 'number'],
                ['label' => 'Pinjaman Berjalan', 'value' => $runningLoans, 'type' => 'number'],
                ['label' => 'NPL', 'value' => $nplRatio, 'type' => 'percent'],
            ],
            'overdue_repayments' => 0,
            'due_this_week' => 0,
            'alerts' => [
                'overdue' => [],
                'due_week' => []
            ]
        ]);
    }

    // ===== TENANT MANAGEMENT API =====
    public function getTenants(): void
    {
        require_login();
        header('Content-Type: application/json');

        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 15);
        $status = $_GET['status'] ?? null;

        $tenantModel = new Tenant();
        $conditions = $status ? ['status' => $status] : [];
        $result = $tenantModel->paginate($page, $limit, $conditions);

        echo json_encode($result);
    }

    public function getTenant(): void
    {
        require_login();
        header('Content-Type: application/json');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Tenant ID required']);
            return;
        }

        $tenantModel = new Tenant();
        $tenant = $tenantModel->findWithStats($id);

        if (!$tenant) {
            http_response_code(404);
            echo json_encode(['error' => 'Tenant not found']);
            return;
        }

        echo json_encode($tenant);
    }

    public function createTenant(): void
    {
        require_login();
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['name'], $data['slug'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Name and slug are required']);
            return;
        }

        // Validate slug format
        if (!preg_match('/^[a-z0-9-]+$/', $data['slug'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Slug must contain only lowercase letters, numbers, and hyphens']);
            return;
        }

        $tenantModel = new Tenant();

        // Check if slug already exists
        $existing = $tenantModel->findBySlug($data['slug']);
        if ($existing) {
            http_response_code(409);
            echo json_encode(['error' => 'Tenant slug already exists']);
            return;
        }

        try {
            $tenantId = $tenantModel->createTenantDatabase($data);
            echo json_encode(['id' => $tenantId, 'success' => true]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create tenant: ' . $e->getMessage()]);
        }
    }

    public function updateTenant(): void
    {
        require_login();
        header('Content-Type: application/json');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Tenant ID required']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            return;
        }

        $tenantModel = new Tenant();
        $success = $tenantModel->update($id, $data);

        echo json_encode(['success' => $success]);
    }

    public function deleteTenant(): void
    {
        require_login();
        header('Content-Type: application/json');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Tenant ID required']);
            return;
        }

        $tenantModel = new Tenant();

        // Get tenant info before deletion
        $tenant = $tenantModel->find($id);
        if (!$tenant) {
            http_response_code(404);
            echo json_encode(['error' => 'Tenant not found']);
            return;
        }

        // Tenant database cleanup - Implement soft delete with data archiving
        try {
            // Archive tenant data before deletion
            $this->archiveTenantData($id);
            
            // Mark tenant as inactive (soft delete)
            $success = $tenantModel->update($id, [
                'status' => 'inactive', 
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_by' => $_SESSION['user']['id'] ?? null
            ]);
            
            // Log the deletion
            $this->logTenantDeletion($id);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete tenant: ' . $e->getMessage()]);
            return;
        }

        echo json_encode(['success' => $success]);
    }
    
    /**
     * Archive tenant data before deletion
     */
    private function archiveTenantData(int $tenantId): void
    {
        $backupDir = "/opt/lampp/htdocs/maruba/backups/tenant_archive_{$tenantId}";
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        // Archive tenant data to backup directory
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = "{$backupDir}/tenant_{$tenantId}_archive_{$timestamp}.sql";
        
        // Create backup of tenant data
        $command = "mysqldump -u root -proot maruba --where=\"tenant_id={$tenantId}\" > {$backupFile}";
        exec($command);
        
        error_log("Tenant data archived: {$backupFile}");
    }
    
    /**
     * Log tenant deletion
     */
    private function logTenantDeletion(int $tenantId): void
    {
        $auditLog = new AuditLog();
        $auditLog->create([
            'user_id' => $_SESSION['user']['id'] ?? null,
            'action' => 'tenant_deleted',
            'entity' => 'tenant',
            'entity_id' => $tenantId,
            'meta' => json_encode([
                'deleted_at' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ])
        ]);
    }

    // ===== TENANT BILLING API =====
    public function getTenantBilling(): void
    {
        require_login();
        header('Content-Type: application/json');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Tenant ID required']);
            return;
        }

        $tenantModel = new Tenant();
        $billing = $tenantModel->getBillingInfo($id);

        if (!$billing) {
            http_response_code(404);
            echo json_encode(['error' => 'Tenant not found']);
            return;
        }

        echo json_encode($billing);
    }

    // ===== TENANT DASHBOARD API =====
    public function tenantDashboard(): void
    {
        // This API is called from tenant subdomain
        require_login();
        header('Content-Type: application/json');

        $tenantInfo = \App\Middleware\TenantMiddleware::getTenantInfo();
        if (!$tenantInfo) {
            http_response_code(400);
            echo json_encode(['error' => 'No tenant context']);
            return;
        }

        $loanModel = new Loan();
        $memberModel = new Member();
        $paymentModel = new Payment();

        $outstanding = $loanModel->count(['status' => ['approved', 'disbursed']]);
        $activeMembers = $memberModel->count(['status' => 'active']);
        $runningLoans = $loanModel->count(['status' => ['draft', 'survey', 'review', 'approved', 'disbursed']]);
        $nplRatio = $loanModel->getNPLRatio();

        // For now, we'll use placeholder values for repayments
        $overdueRepayments = [];
        $dueThisWeek = [];

        echo json_encode([
            'tenant' => $tenantInfo,
            'metrics' => [
                ['label' => 'Outstanding', 'value' => $outstanding, 'type' => 'number'],
                ['label' => 'Anggota Aktif', 'value' => $activeMembers, 'type' => 'number'],
                ['label' => 'Pinjaman Berjalan', 'value' => $runningLoans, 'type' => 'number'],
                ['label' => 'NPL', 'value' => $nplRatio, 'type' => 'percent'],
            ],
            'overdue_repayments' => count($overdueRepayments),
            'due_this_week' => count($dueThisWeek),
            'alerts' => [
                'overdue' => $overdueRepayments,
                'due_week' => $dueThisWeek
            ]
        ]);
    }

    // ===== BILLING MANAGEMENT API =====
    public function getBillings(): void
    {
        require_login();
        header('Content-Type: application/json');

        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 15);
        $status = $_GET['status'] ?? null;
        $tenantId = (int)($_GET['tenant_id'] ?? 0);

        $billingModel = new TenantBilling();
        $conditions = [];

        if ($status) {
            $conditions['status'] = $status;
        }
        if ($tenantId) {
            $conditions['tenant_id'] = $tenantId;
        }

        $result = $billingModel->paginate($page, $limit, $conditions);

        // Add tenant names to results
        foreach ($result['items'] as &$billing) {
            $tenantModel = new Tenant();
            $tenant = $tenantModel->find($billing['tenant_id']);
            $billing['tenant_name'] = $tenant ? $tenant['name'] : 'Unknown';
            $billing['tenant_slug'] = $tenant ? $tenant['slug'] : '';
        }

        echo json_encode($result);
    }

    public function createBilling(): void
    {
        require_login();
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['tenant_id'], $data['billing_period_start'], $data['billing_period_end'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Tenant ID, billing period start and end are required']);
            return;
        }

        $billingModel = new TenantBilling();

        try {
            $billingId = $billingModel->generateBilling(
                $data['tenant_id'],
                $data['billing_period_start'],
                $data['billing_period_end']
            );
            echo json_encode(['id' => $billingId, 'success' => true]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create billing: ' . $e->getMessage()]);
        }
    }

    public function recordBillingPayment(): void
    {
        require_login();
        header('Content-Type: application/json');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Billing ID required']);
            return;
        }

        $paymentData = json_decode(file_get_contents('php://input'), true);
        if (!$paymentData) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payment data']);
            return;
        }

        $billingModel = new TenantBilling();
        $success = $billingModel->recordPayment($id, $paymentData);

        if ($success) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to record payment']);
        }
    }

    public function generateMonthlyBillings(): void
    {
        require_login();
        header('Content-Type: application/json');

        $billingModel = new TenantBilling();

        try {
            $generated = $billingModel->generateMonthlyBillings();
            echo json_encode([
                'success' => true,
                'generated_count' => count($generated),
                'generated' => $generated
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to generate billings: ' . $e->getMessage()]);
        }
    }

    public function getBillingStats(): void
    {
        require_login();
        header('Content-Type: application/json');

        $billingModel = new TenantBilling();
        $stats = $billingModel->getRevenueStats();

        $overdueBillings = $billingModel->getOverdueBillings();
        $pendingBillings = $billingModel->getPendingBillings();

        echo json_encode([
            'revenue_stats' => $stats,
            'overdue_count' => count($overdueBillings),
            'pending_count' => count($pendingBillings),
            'overdue_amount' => array_sum(array_column($overdueBillings, 'amount')),
            'pending_amount' => array_sum(array_column($pendingBillings, 'amount'))
        ]);
    }

    // ===== TENANT BILLING CALCULATOR API =====
    public function calculateTenantCost(): void
    {
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['model'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Model type required']);
            return;
        }

        $model = $data['model'];
        $params = $data['params'] ?? [];

        $cost = $this->calculateTenantCostByModel($model, $params);

        echo json_encode([
            'model' => $model,
            'params' => $params,
            'calculated_cost' => $cost
        ]);
    }

    private function calculateTenantCostByModel(string $model, array $params): float
    {
        switch ($model) {
            case 'starter':
                $cost = 500000;
                if (($params['members'] ?? 0) > 100) {
                    $cost += (($params['members'] ?? 0) - 100) * 2000;
                }
                if (($params['transactions'] ?? 0) > 50) {
                    $cost += (($params['transactions'] ?? 0) - 50) * 100;
                }
                break;

            case 'professional':
                $cost = 1500000;
                if (($params['members'] ?? 0) > 500) {
                    $cost += (($params['members'] ?? 0) - 500) * 1500;
                }
                if (($params['transactions'] ?? 0) > 500) {
                    $cost += (($params['transactions'] ?? 0) - 500) * 80;
                }
                break;

            case 'enterprise':
                $cost = 3000000;
                // Unlimited for enterprise
                break;

            case 'usage':
                $cost = 300000; // Base fee
                $cost += ($params['members'] ?? 0) * 2000;
                if (($params['transactions'] ?? 0) > 100) {
                    $cost += (($params['transactions'] ?? 0) - 100) * 100;
                }
                if (($params['storage'] ?? 0) > 1) {
                    $cost += (($params['storage'] ?? 0) - 1) * 100;
                }
                break;

            case 'hybrid':
                $cost = 1000000; // Subscription
                if (($params['members'] ?? 0) > 200) {
                    $cost += (($params['members'] ?? 0) - 200) * 1500;
                }
                if (($params['transactions'] ?? 0) > 200) {
                    $cost += (($params['transactions'] ?? 0) - 200) * 50;
                }
                if (($params['storage'] ?? 0) > 1) {
                    $cost += (($params['storage'] ?? 0) - 1) * 50;
                }
                $cost += ($params['premium_features'] ?? 0) * 200000;
                break;

            default:
                $cost = 0.0;
        }

        return (float)$cost;
    }

    /**
     * Get current tenant ID for API filtering
     */
    private function getCurrentTenantId(): ?int
    {
        // Check if user is authenticated and has tenant context
        $currentUser = current_user();
        if (!$currentUser) {
            return null; // Public API access
        }

        // Get user details including tenant_id
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->prepare('SELECT tenant_id FROM users WHERE id = ?');
        $stmt->execute([$currentUser['id']]);
        $user = $stmt->fetch();

        return $user ? $user['tenant_id'] : null;
    }

    /**
     * Get tenant surveys with geo coordinates
     */
    private function getTenantSurveysWithGeo(int $tenantId): array
    {
        $pdo = \App\Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT s.*, l.amount as loan_amount, m.name as member_name,
                   u.name as surveyor_name, l.tenant_id
            FROM surveys s
            JOIN loans l ON s.loan_id = l.id
            JOIN members m ON l.member_id = m.id
            JOIN users u ON s.surveyor_id = u.id
            WHERE l.tenant_id = ? AND s.geo_lat IS NOT NULL AND s.geo_lng IS NOT NULL
            ORDER BY s.created_at DESC
        ");
        $stmt->execute([$tenantId]);
        return $stmt->fetchAll();
    }
}
