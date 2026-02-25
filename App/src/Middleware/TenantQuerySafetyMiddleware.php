<?php
namespace App\Middleware;

use App\Database;
use PDO;

/**
 * Tenant Query Safety Middleware
 *
 * Enforces tenant isolation at the database query level
 * Prevents cross-tenant data access by automatically adding tenant_id filters
 */
class TenantQuerySafetyMiddleware
{
    private static array $tenantTables = [
        'members', 'loans', 'loan_products', 'loan_documents', 'loan_repayments',
        'savings_accounts', 'savings_products', 'savings_transactions',
        'users', 'audit_logs', 'notification_logs',
        'chart_of_accounts', 'journal_entries', 'journal_lines',
        'payroll_records', 'employees',
        'document_templates', 'generated_documents',
        'compliance_checks', 'risk_assessments',
        'payment_transactions'
    ];

    private static array $systemTables = [
        'tenants', 'cooperative_registrations', 'cooperative_onboardings',
        'subscription_plans', 'tenant_billings', 'tenant_feature_usage',
        'tenant_backups', 'navigation_menus', 'api_keys',
        'roles', 'users' // users table has tenant_id column for tenant users
    ];

    /**
     * Check if table requires tenant isolation
     */
    public static function requiresTenantIsolation(string $table): bool
    {
        return in_array($table, self::$tenantTables);
    }

    /**
     * Check if table is a system table
     */
    public static function isSystemTable(string $table): bool
    {
        return in_array($table, self::$systemTables);
    }

    /**
     * Get current tenant ID
     */
    public static function getCurrentTenantId(): ?int
    {
        // Check if we're in tenant context
        if (!TenantMiddleware::hasTenant()) {
            return null;
        }

        $tenantInfo = TenantMiddleware::getTenantInfo();
        return $tenantInfo['id'] ?? null;
    }

    /**
     * Get current tenant slug
     */
    public static function getCurrentTenantSlug(): ?string
    {
        return TenantMiddleware::getTenantSlug();
    }

    /**
     * Enforce tenant safety on SQL query
     * Automatically adds tenant_id filter for tenant tables
     */
    public static function enforceTenantSafety(string &$sql, array &$params): void
    {
        $tenantId = self::getCurrentTenantId();

        // If no tenant context, allow system operations only
        if ($tenantId === null) {
            // System admin operations - no tenant filter needed
            return;
        }

        // Parse SQL to find table names and add tenant filters
        $sql = self::addTenantFilters($sql, $tenantId, $params);
    }

    /**
     * Add tenant filters to SQL query
     */
    private static function addTenantFilters(string $sql, int $tenantId, array &$params): string
    {
        // Simple regex to find table names in FROM/WHERE clauses
        // This is a basic implementation - could be enhanced with proper SQL parsing

        $originalSql = $sql;
        $modifiedSql = $sql;

        // Add tenant_id filter for each tenant table found in the query
        foreach (self::$tenantTables as $table) {
            // Match table references in FROM, JOIN, UPDATE, DELETE statements
            $patterns = [
                // FROM table
                "/\\bFROM\\s+{$table}\\b/i",
                // JOIN table
                "/\\bJOIN\\s+{$table}\\b/i",
                // UPDATE table
                "/\\bUPDATE\\s+{$table}\\b/i",
                // DELETE FROM table
                "/\\bDELETE\\s+FROM\\s+{$table}\\b/i",
                // INSERT INTO table
                "/\\bINSERT\\s+INTO\\s+{$table}\\b/i"
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $modifiedSql)) {
                    // Add tenant filter based on operation type
                    if (preg_match("/\\bUPDATE\\s+{$table}\\b/i", $modifiedSql)) {
                        $modifiedSql = self::addTenantFilterToUpdate($modifiedSql, $tenantId, $params);
                    } elseif (preg_match("/\\bDELETE\\s+FROM\\s+{$table}\\b/i", $modifiedSql)) {
                        $modifiedSql = self::addTenantFilterToDelete($modifiedSql, $tenantId, $params);
                    } elseif (preg_match("/\\bINSERT\\s+INTO\\s+{$table}\\b/i", $modifiedSql)) {
                        // For INSERT, we need to ensure tenant_id is included in the data
                        // This will be handled by the Model class
                    } else {
                        // SELECT and JOIN operations
                        $modifiedSql = self::addTenantFilterToSelect($modifiedSql, $table, $tenantId, $params);
                    }
                    break; // Only process first match per table
                }
            }
        }

        return $modifiedSql;
    }

    /**
     * Add tenant filter to SELECT queries
     */
    private static function addTenantFilterToSelect(string $sql, string $table, int $tenantId, array &$params): string
    {
        // Check if tenant_id filter already exists
        if (strpos(strtoupper($sql), 'TENANT_ID') !== false) {
            // Tenant filter already present, don't add another
            return $sql;
        }

        // Find WHERE clause or add one
        if (strpos(strtoupper($sql), 'WHERE') === false) {
            // No WHERE clause, add tenant filter
            $sql .= " WHERE tenant_id = ?";
            $params[] = $tenantId;
        } else {
            // Has WHERE clause, add tenant filter with AND
            $sql = preg_replace('/WHERE\s+/i', 'WHERE tenant_id = ? AND ', $sql);
            array_unshift($params, $tenantId);
        }

        return $sql;
    }

    /**
     * Add tenant filter to UPDATE queries
     */
    private static function addTenantFilterToUpdate(string $sql, int $tenantId, array &$params): string
    {
        // Check if tenant_id filter already exists
        if (strpos(strtoupper($sql), 'TENANT_ID') !== false) {
            return $sql;
        }

        // Find WHERE clause or add one
        if (strpos(strtoupper($sql), 'WHERE') === false) {
            $sql .= " WHERE tenant_id = ?";
            $params[] = $tenantId;
        } else {
            $sql = preg_replace('/WHERE\s+/i', 'WHERE tenant_id = ? AND ', $sql);
            array_unshift($params, $tenantId);
        }

        return $sql;
    }

    /**
     * Add tenant filter to DELETE queries
     */
    private static function addTenantFilterToDelete(string $sql, int $tenantId, array &$params): string
    {
        // Check if tenant_id filter already exists
        if (strpos(strtoupper($sql), 'TENANT_ID') !== false) {
            return $sql;
        }

        // Find WHERE clause or add one
        if (strpos(strtoupper($sql), 'WHERE') === false) {
            $sql .= " WHERE tenant_id = ?";
            $params[] = $tenantId;
        } else {
            $sql = preg_replace('/WHERE\s+/i', 'WHERE tenant_id = ? AND ', $sql);
            array_unshift($params, $tenantId);
        }

        return $sql;
    }

    /**
     * Validate that a record belongs to current tenant
     */
    public static function validateTenantOwnership(string $table, int $recordId): bool
    {
        if (!self::requiresTenantIsolation($table)) {
            return true; // System table, no tenant validation needed
        }

        $tenantId = self::getCurrentTenantId();
        if ($tenantId === null) {
            return false; // No tenant context
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM {$table} WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$recordId, $tenantId]);

        return (int)$stmt->fetch()['count'] > 0;
    }

    /**
     * Get tenant-aware query statistics for monitoring
     */
    public static function getQueryStats(): array
    {
        return [
            'tenant_id' => self::getCurrentTenantId(),
            'tenant_slug' => self::getCurrentTenantSlug(),
            'requires_isolation' => TenantMiddleware::hasTenant(),
            'system_tables' => count(self::$systemTables),
            'tenant_tables' => count(self::$tenantTables),
            'total_protected_tables' => count(self::$tenantTables)
        ];
    }

    /**
     * Log tenant safety violations for monitoring
     */
    public static function logSafetyViolation(string $table, string $operation, string $originalSql): void
    {
        $tenantId = self::getCurrentTenantId();
        $tenantSlug = self::getCurrentTenantSlug();

        error_log("TENANT SAFETY VIOLATION: " . json_encode([
            'tenant_id' => $tenantId,
            'tenant_slug' => $tenantSlug,
            'table' => $table,
            'operation' => $operation,
            'sql' => substr($originalSql, 0, 200), // Truncate for security
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]));
    }
}
