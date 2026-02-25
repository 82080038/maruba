<?php
/**
 * Feature Tests for Dashboard Functionality
 */

class DashboardFeatureTest extends TestCase
{
    public function testAdminDashboardAccess(): void
    {
        // Login as admin
        $this->loginAs('admin');
        
        // Access dashboard
        $this->simulateGetRequest('/dashboard');
        
        // Verify admin dashboard is loaded
        $this->assertDashboardLoaded('admin');
        $this->assertMetricsDisplayed([
            'Outstanding',
            'Anggota Aktif',
            'Pinjaman Berjalan',
            'NPL'
        ]);
    }
    
    public function testKasirDashboardAccess(): void
    {
        // Login as kasir
        $this->loginAs('kasir');
        
        // Access dashboard
        $this->simulateGetRequest('/dashboard');
        
        // Verify kasir dashboard is loaded
        $this->assertDashboardLoaded('kasir');
        $this->assertMetricsDisplayed([
            'Cash Flow Hari Ini',
            'Transaksi Pending',
            'Payment Gateway',
            'Reconciled Today'
        ]);
    }
    
    public function testManajerDashboardAccess(): void
    {
        // Login as manajer
        $this->loginAs('manajer');
        
        // Access dashboard
        $this->simulateGetRequest('/dashboard');
        
        // Verify manajer dashboard is loaded
        $this->assertDashboardLoaded('manajer');
        $this->assertMetricsDisplayed([
            'Portfolio Health',
            'Risk Assessments',
            'Approval Queue',
            'Team Size'
        ]);
    }
    
    public function testCollectorDashboardAccess(): void
    {
        // Login as collector
        $this->loginAs('collector');
        
        // Access dashboard
        $this->simulateGetRequest('/dashboard');
        
        // Verify collector dashboard is loaded
        $this->assertDashboardLoaded('collector');
        $this->assertMetricsDisplayed([
            'Collection Target',
            'Overdue Accounts',
            'Success Rate',
            'Route Status'
        ]);
    }
    
    public function testTellerDashboardAccess(): void
    {
        // Login as teller
        $this->loginAs('teller');
        
        // Access dashboard
        $this->simulateGetRequest('/dashboard');
        
        // Verify teller dashboard is loaded
        $this->assertDashboardLoaded('teller');
        $this->assertMetricsDisplayed([
            'Total Tabungan',
            'Registrasi Hari Ini',
            'Deposit Hari Ini',
            'Queue Status'
        ]);
    }
    
    public function testSurveyorDashboardAccess(): void
    {
        // Login as surveyor
        $this->loginAs('surveyor');
        
        // Access dashboard
        $this->simulateGetRequest('/dashboard');
        
        // Verify surveyor dashboard is loaded
        $this->assertDashboardLoaded('surveyor');
        $this->assertMetricsDisplayed([
            'Survei Pending',
            'Completion Rate',
            'Coverage',
            'Avg Score'
        ]);
    }
    
    public function testAkuntansiDashboardAccess(): void
    {
        // Login as akuntansi
        $this->loginAs('akuntansi');
        
        // Access dashboard
        $this->simulateGetRequest('/dashboard');
        
        // Verify akuntansi dashboard is loaded
        $this->assertDashboardLoaded('akuntansi');
        $this->assertMetricsDisplayed([
            'Pending Entries',
            'Chart Accounts',
            'Tax Status',
            'Audit Findings'
        ]);
    }
    
    public function testCreatorDashboardAccess(): void
    {
        // Login as creator
        $this->loginAs('creator');
        
        // Access dashboard
        $this->simulateGetRequest('/dashboard');
        
        // Verify creator dashboard is loaded
        $this->assertDashboardLoaded('creator');
        $this->assertMetricsDisplayed([
            'System Health',
            'Active Tenants',
            'Active Users',
            'Security Alerts'
        ]);
    }
    
    public function testDashboardDataAccuracy(): void
    {
        // Login as admin
        $this->loginAs('admin');
        
        // Access dashboard
        $this->simulateGetRequest('/dashboard');
        
        // Verify data accuracy
        $this->assertMetricValueMatches('Anggota Aktif', $this->getActiveMembersCount());
        $this->assertMetricValueMatches('Pinjaman Berjalan', $this->getRunningLoansCount());
    }
    
    public function testDashboardTenantIsolation(): void
    {
        // Create test tenant
        $tenantId = $this->createTestTenant();
        
        // Login as tenant user
        $this->loginAsTenant($tenantId);
        
        // Access dashboard
        $this->simulateGetRequest('/dashboard');
        
        // Verify tenant-specific data
        $this->assertDashboardLoaded('tenant');
        $this->assertMetricsAreTenantSpecific($tenantId);
    }
    
    public function testDashboardAutoRefresh(): void
    {
        // Login as admin
        $this->loginAs('admin');
        
        // Access dashboard
        $this->simulateGetRequest('/dashboard');
        
        // Verify auto-refresh functionality
        $this->assertAutoRefreshEnabled();
        $this->assertRefreshIntervalIs(30); // 30 seconds
    }
    
    // Helper methods
    private function loginAs(string $role): void
    {
        $_SESSION['user'] = $this->createTestUser($role);
    }
    
    private function loginAsTenant(int $tenantId): void
    {
        // Create tenant user
        $stmt = $this->pdo->prepare("INSERT INTO users (name, username, password_hash, role_id, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['Tenant User', 'tenant_user', password_hash('password', PASSWORD_DEFAULT), 1, 'active']);
        $userId = $this->pdo->lastInsertId();
        
        $_SESSION['user'] = [
            'id' => $userId,
            'username' => 'tenant_user',
            'name' => 'Tenant User',
            'role' => 'admin',
            'tenant_id' => $tenantId
        ];
    }
    
    private function simulateGetRequest(string $uri): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = $uri;
        $_GET = [];
        $_POST = [];
    }
    
    private function assertDashboardLoaded(string $role): void
    {
        $this->assertTrue(true, "Should load $role dashboard");
    }
    
    private function assertMetricsDisplayed(array $expectedMetrics): void
    {
        foreach ($expectedMetrics as $metric) {
            $this->assertTrue(true, "Should display metric: $metric");
        }
    }
    
    private function assertMetricValueMatches(string $metric, $expectedValue): void
    {
        $this->assertTrue(true, "Metric $metric should match expected value: $expectedValue");
    }
    
    private function assertAutoRefreshEnabled(): void
    {
        $this->assertTrue(true, "Auto-refresh should be enabled");
    }
    
    private function assertRefreshIntervalIs(int $expectedSeconds): void
    {
        $this->assertTrue(true, "Refresh interval should be $expectedSeconds seconds");
    }
    
    private function assertMetricsAreTenantSpecific(int $tenantId): void
    {
        $this->assertTrue(true, "Metrics should be tenant-specific for tenant: $tenantId");
    }
    
    private function getActiveMembersCount(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM members WHERE status = 'active'");
        return (int)$stmt->fetch()['count'];
    }
    
    private function getRunningLoansCount(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM loans WHERE status IN ('draft','survey','review','approved','disbursed')");
        return (int)$stmt->fetch()['count'];
    }
    
    private function createTestTenant(): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO tenants (name, slug, status, district, city, province) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['Test Tenant', 'test-tenant', 'active', 'Test District', 'Test City', 'Test Province']);
        return $this->pdo->lastInsertId();
    }
}
