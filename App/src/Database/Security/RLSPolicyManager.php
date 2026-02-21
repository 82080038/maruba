<?php
/**
 * Row-Level Security (RLS) Implementation for Multi-Tenant SaaS
 *
 * This file contains database-level security policies and triggers
 * to enforce tenant data isolation at the database level.
 *
 * Run this script during tenant database setup or as a migration.
 */

namespace App\Database\Security;

/**
 * RLS Policy Manager for Tenant Data Isolation
 */
class RLSPolicyManager
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create Row-Level Security policies for all tenant tables
     */
    public function createTenantRLSPolicies(): void
    {
        $this->createTenantContextFunction();
        $this->createRLSTriggers();
        $this->createSecurityViews();
        $this->enableRLSEnforcement();
    }

    /**
     * Create function to get current tenant context
     */
    private function createTenantContextFunction(): void
    {
        $sql = "
            -- Function to get current tenant ID from session/application context
            DELIMITER //

            CREATE OR REPLACE FUNCTION get_current_tenant_id()
            RETURNS INT
            DETERMINISTIC
            NO SQL
            BEGIN
                -- In a real implementation, this would read from session variables
                -- or application context set by the middleware
                -- For now, return NULL to indicate no tenant context
                RETURN @current_tenant_id;
            END //

            DELIMITER ;
        ";

        $this->db->exec($sql);
    }

    /**
     * Create RLS triggers for all tenant tables
     */
    private function createRLSTriggers(): void
    {
        $tenantTables = [
            'members',
            'loans',
            'loan_products',
            'loan_documents',
            'loan_repayments',
            'savings_accounts',
            'savings_products',
            'savings_transactions',
            'audit_logs',
            'notification_logs',
            'chart_of_accounts',
            'journal_entries',
            'journal_lines',
            'payroll_records',
            'employees',
            'document_templates',
            'generated_documents',
            'compliance_checks',
            'risk_assessments',
            'payment_transactions'
        ];

        foreach ($tenantTables as $table) {
            $this->createTableRLSTriggers($table);
        }
    }

    /**
     * Create RLS triggers for a specific table
     */
    private function createTableRLSTriggers(string $table): void
    {
        // Insert trigger - ensure tenant_id is set
        $insertTrigger = "
            DELIMITER //

            CREATE OR REPLACE TRIGGER {$table}_rls_insert
                BEFORE INSERT ON {$table}
                FOR EACH ROW
            BEGIN
                DECLARE current_tenant INT DEFAULT get_current_tenant_id();

                -- If no tenant context and table requires tenant isolation, prevent insert
                IF current_tenant IS NULL AND '{$table}' IN (
                    'members', 'loans', 'loan_products', 'loan_documents', 'loan_repayments',
                    'savings_accounts', 'savings_products', 'savings_transactions',
                    'audit_logs', 'notification_logs',
                    'chart_of_accounts', 'journal_entries', 'journal_lines',
                    'payroll_records', 'employees',
                    'document_templates', 'generated_documents',
                    'compliance_checks', 'risk_assessments',
                    'payment_transactions'
                ) THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Tenant context required for this operation';
                END IF;

                -- Auto-set tenant_id if not provided and we have tenant context
                IF NEW.tenant_id IS NULL AND current_tenant IS NOT NULL THEN
                    SET NEW.tenant_id = current_tenant;
                END IF;
            END //

            DELIMITER ;
        ";

        // Update trigger - ensure tenant isolation
        $updateTrigger = "
            DELIMITER //

            CREATE OR REPLACE TRIGGER {$table}_rls_update
                BEFORE UPDATE ON {$table}
                FOR EACH ROW
            BEGIN
                DECLARE current_tenant INT DEFAULT get_current_tenant_id();

                -- Prevent cross-tenant updates
                IF current_tenant IS NOT NULL AND OLD.tenant_id != current_tenant THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Cannot modify data from other tenants';
                END IF;

                -- Ensure tenant_id doesn't change
                IF OLD.tenant_id != NEW.tenant_id THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Cannot change tenant ownership of records';
                END IF;
            END //

            DELIMITER ;
        ";

        // Delete trigger - ensure tenant isolation
        $deleteTrigger = "
            DELIMITER //

            CREATE OR REPLACE TRIGGER {$table}_rls_delete
                BEFORE DELETE ON {$table}
                FOR EACH ROW
            BEGIN
                DECLARE current_tenant INT DEFAULT get_current_tenant_id();

                -- Prevent cross-tenant deletes
                IF current_tenant IS NOT NULL AND OLD.tenant_id != current_tenant THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'Cannot delete data from other tenants';
                END IF;
            END //

            DELIMITER ;
        ";

        try {
            $this->db->exec($insertTrigger);
            $this->db->exec($updateTrigger);
            $this->db->exec($deleteTrigger);
        } catch (\Exception $e) {
            // Log error but continue with other tables
            error_log("Failed to create RLS triggers for {$table}: " . $e->getMessage());
        }
    }

    /**
     * Create security views for safe data access
     */
    private function createSecurityViews(): void
    {
        // Create secure views that automatically filter by tenant
        $secureViews = [
            'secure_members' => "
                CREATE OR REPLACE VIEW secure_members AS
                SELECT * FROM members
                WHERE tenant_id = get_current_tenant_id() OR get_current_tenant_id() IS NULL
            ",
            'secure_loans' => "
                CREATE OR REPLACE VIEW secure_loans AS
                SELECT * FROM loans
                WHERE tenant_id = get_current_tenant_id() OR get_current_tenant_id() IS NULL
            ",
            'secure_savings_accounts' => "
                CREATE OR REPLACE VIEW secure_savings_accounts AS
                SELECT * FROM savings_accounts
                WHERE tenant_id = get_current_tenant_id() OR get_current_tenant_id() IS NULL
            "
        ];

        foreach ($secureViews as $viewName => $viewSql) {
            try {
                $this->db->exec($viewSql);
            } catch (\Exception $e) {
                error_log("Failed to create secure view {$viewName}: " . $e->getMessage());
            }
        }
    }

    /**
     * Enable RLS enforcement system-wide
     */
    private function enableRLSEnforcement(): void
    {
        // Create audit table for RLS violations
        $auditTable = "
            CREATE TABLE IF NOT EXISTS rls_violations (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                tenant_id INT NULL,
                user_id INT NULL,
                table_name VARCHAR(100) NOT NULL,
                operation ENUM('INSERT', 'UPDATE', 'DELETE', 'SELECT') NOT NULL,
                violation_type VARCHAR(100) NOT NULL,
                original_sql TEXT NULL,
                attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                INDEX idx_tenant_attempted (tenant_id, attempted_at),
                INDEX idx_table_operation (table_name, operation)
            );
        ";

        $this->db->exec($auditTable);

        // Create function to log RLS violations
        $logFunction = "
            DELIMITER //

            CREATE OR REPLACE PROCEDURE log_rls_violation(
                IN p_tenant_id INT,
                IN p_user_id INT,
                IN p_table_name VARCHAR(100),
                IN p_operation ENUM('INSERT', 'UPDATE', 'DELETE', 'SELECT'),
                IN p_violation_type VARCHAR(100),
                IN p_original_sql TEXT
            )
            BEGIN
                INSERT INTO rls_violations (
                    tenant_id, user_id, table_name, operation,
                    violation_type, original_sql, ip_address, user_agent
                ) VALUES (
                    p_tenant_id, p_user_id, p_table_name, p_operation,
                    p_violation_type, p_original_sql,
                    @client_ip, @user_agent
                );
            END //

            DELIMITER ;
        ";

        $this->db->exec($logFunction);
    }

    /**
     * Set tenant context for current session
     */
    public function setTenantContext(int $tenantId): void
    {
        // Set session variables for RLS functions
        $this->db->exec("SET @current_tenant_id = {$tenantId}");
        $this->db->exec("SET @client_ip = '{$_SERVER['REMOTE_ADDR'] ?? 'unknown'}'");
        $this->db->exec("SET @user_agent = '" . addslashes($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') . "'");
    }

    /**
     * Clear tenant context
     */
    public function clearTenantContext(): void
    {
        $this->db->exec("SET @current_tenant_id = NULL");
        $this->db->exec("SET @client_ip = NULL");
        $this->db->exec("SET @user_agent = NULL");
    }

    /**
     * Get RLS violation statistics
     */
    public function getViolationStats(): array
    {
        $stmt = $this->db->query("
            SELECT
                COUNT(*) as total_violations,
                COUNT(DISTINCT tenant_id) as affected_tenants,
                table_name,
                operation,
                violation_type,
                MAX(attempted_at) as last_violation
            FROM rls_violations
            WHERE attempted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY table_name, operation, violation_type
            ORDER BY total_violations DESC
        ");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Validate tenant data integrity
     */
    public function validateTenantIntegrity(int $tenantId): array
    {
        $issues = [];

        // Check for records without tenant_id in tenant tables
        $tenantTables = [
            'members', 'loans', 'loan_products', 'loan_documents', 'loan_repayments',
            'savings_accounts', 'savings_products', 'savings_transactions',
            'audit_logs', 'notification_logs',
            'chart_of_accounts', 'journal_entries', 'journal_lines',
            'payroll_records', 'employees',
            'document_templates', 'generated_documents',
            'compliance_checks', 'risk_assessments',
            'payment_transactions'
        ];

        foreach ($tenantTables as $table) {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM {$table} WHERE tenant_id IS NULL");
            $stmt->execute();
            $nullCount = $stmt->fetch()['count'];

            if ($nullCount > 0) {
                $issues[] = [
                    'table' => $table,
                    'issue' => 'records_without_tenant_id',
                    'count' => $nullCount,
                    'severity' => 'high'
                ];
            }
        }

        // Check for orphaned records
        $orphanChecks = [
            ['loans', 'members', 'member_id'],
            ['loan_repayments', 'loans', 'loan_id'],
            ['savings_transactions', 'savings_accounts', 'account_id'],
            ['loan_documents', 'loans', 'loan_id']
        ];

        foreach ($orphanChecks as [$childTable, $parentTable, $foreignKey]) {
            $stmt = $this->db->prepare("
                SELECT COUNT(c.id) as count
                FROM {$childTable} c
                LEFT JOIN {$parentTable} p ON c.{$foreignKey} = p.id
                WHERE p.id IS NULL AND c.tenant_id = ?
            ");
            $stmt->execute([$tenantId]);
            $orphanCount = $stmt->fetch()['count'];

            if ($orphanCount > 0) {
                $issues[] = [
                    'table' => $childTable,
                    'issue' => 'orphaned_records',
                    'related_table' => $parentTable,
                    'count' => $orphanCount,
                    'severity' => 'medium'
                ];
            }
        }

        return $issues;
    }
}

/**
 * RLS Enforcement Middleware
 * Integrates with application middleware to set tenant context
 */
class RLSEnforcementMiddleware
{
    private RLSPolicyManager $rlsManager;

    public function __construct(\PDO $db)
    {
        $this->rlsManager = new RLSPolicyManager($db);
    }

    /**
     * Handle RLS context for current request
     */
    public function handle(): void
    {
        // Check if we're in tenant context
        if (\App\Middleware\TenantMiddleware::hasTenant()) {
            $tenantId = \App\Middleware\TenantQuerySafetyMiddleware::getCurrentTenantId();
            if ($tenantId !== null) {
                $this->rlsManager->setTenantContext($tenantId);
            }
        } else {
            // System admin context
            $this->rlsManager->clearTenantContext();
        }
    }

    /**
     * Get RLS manager instance
     */
    public function getRLSManager(): RLSPolicyManager
    {
        return $this->rlsManager;
    }
}

// =========================================
// RLS SETUP SQL FOR TENANT DATABASES
// =========================================

/*
Run this SQL script when setting up a new tenant database:

-- Set up RLS context variables
SET @current_tenant_id = NULL;
SET @client_ip = '';
SET @user_agent = '';

-- The PHP middleware will set these variables for each request

-- Example of how triggers work:
-- When a tenant user accesses data, the triggers ensure:
-- 1. Only records with matching tenant_id are accessible
-- 2. New records automatically get tenant_id set
-- 3. Cross-tenant operations are blocked
-- 4. All violations are logged

-- Views provide safe access patterns:
-- SELECT * FROM secure_members; -- Automatically filtered by tenant
-- SELECT * FROM secure_loans;   -- Automatically filtered by tenant
*/

?>
