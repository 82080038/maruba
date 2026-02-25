<?php
namespace App\Database\Migrations;

use App\Models\Tenant;

/**
 * Multi-Tenant Migration Manager
 *
 * Handles schema migrations across all tenant databases
 * Ensures consistency and rollback capabilities
 */
class TenantMigrationManager
{
    private Tenant $tenantModel;

    public function __construct()
    {
        $this->tenantModel = new Tenant();
    }

    /**
     * Run migration on all active tenant databases
     */
    public function runMigrationOnAllTenants(string $migrationClass): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'skipped' => []
        ];

        $tenants = $this->tenantModel->findWhere(['status' => 'active']);

        foreach ($tenants as $tenant) {
            try {
                $result = $this->runMigrationOnTenant($tenant['id'], $migrationClass);

                if ($result['success']) {
                    $results['success'][] = [
                        'tenant_id' => $tenant['id'],
                        'tenant_name' => $tenant['name'],
                        'migration' => $migrationClass,
                        'executed_at' => date('Y-m-d H:i:s')
                    ];
                } else {
                    $results['failed'][] = [
                        'tenant_id' => $tenant['id'],
                        'tenant_name' => $tenant['name'],
                        'migration' => $migrationClass,
                        'error' => $result['error'],
                        'failed_at' => date('Y-m-d H:i:s')
                    ];
                }
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'tenant_id' => $tenant['id'],
                    'tenant_name' => $tenant['name'],
                    'migration' => $migrationClass,
                    'error' => $e->getMessage(),
                    'failed_at' => date('Y-m-d H:i:s')
                ];
            }
        }

        return $results;
    }

    /**
     * Run migration on specific tenant
     */
    public function runMigrationOnTenant(int $tenantId, string $migrationClass): array
    {
        try {
            // Get tenant database connection
            $tenantDb = $this->tenantModel->getTenantDatabaseById($tenantId);

            if (!$tenantDb) {
                return [
                    'success' => false,
                    'error' => 'Tenant database not found'
                ];
            }

            // Create migration instance
            $migration = $this->createMigrationInstance($migrationClass, $tenantDb);

            // Run migration
            $migration->up();

            // Record migration execution
            $this->recordMigrationExecution($tenantId, $migrationClass, 'up');

            return ['success' => true];

        } catch (\Exception $e) {
            // Record failed migration
            $this->recordMigrationExecution($tenantId, $migrationClass, 'failed', $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Rollback migration on all tenants
     */
    public function rollbackMigrationOnAllTenants(string $migrationClass): array
    {
        $results = [
            'success' => [],
            'failed' => []
        ];

        $tenants = $this->tenantModel->findWhere(['status' => 'active']);

        foreach ($tenants as $tenant) {
            try {
                $result = $this->rollbackMigrationOnTenant($tenant['id'], $migrationClass);

                if ($result['success']) {
                    $results['success'][] = [
                        'tenant_id' => $tenant['id'],
                        'tenant_name' => $tenant['name'],
                        'migration' => $migrationClass,
                        'rolled_back_at' => date('Y-m-d H:i:s')
                    ];
                } else {
                    $results['failed'][] = [
                        'tenant_id' => $tenant['id'],
                        'tenant_name' => $tenant['name'],
                        'migration' => $migrationClass,
                        'error' => $result['error']
                    ];
                }
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'tenant_id' => $tenant['id'],
                    'tenant_name' => $tenant['name'],
                    'migration' => $migrationClass,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Create migration instance
     */
    private function createMigrationInstance(string $migrationClass, \PDO $db): object
    {
        // Assuming migrations are in App\Database\Migrations namespace
        $fullClass = "App\\Database\\Migrations\\{$migrationClass}";

        if (!class_exists($fullClass)) {
            throw new \Exception("Migration class {$fullClass} not found");
        }

        return new $fullClass($db);
    }

    /**
     * Record migration execution in tenant_migrations table
     */
    private function recordMigrationExecution(int $tenantId, string $migrationClass, string $status, string $error = null): void
    {
        $mainDb = \App\Database::getConnection();

        $stmt = $mainDb->prepare("
            INSERT INTO tenant_migrations (tenant_id, migration_class, status, executed_at, error_message)
            VALUES (?, ?, ?, NOW(), ?)
        ");

        $stmt->execute([$tenantId, $migrationClass, $status, $error]);
    }

    /**
     * Get migration status across all tenants
     */
    public function getMigrationStatus(string $migrationClass = null): array
    {
        $mainDb = \App\Database::getConnection();

        $sql = "
            SELECT
                tm.*,
                t.name as tenant_name,
                t.slug as tenant_slug
            FROM tenant_migrations tm
            JOIN tenants t ON tm.tenant_id = t.id
        ";

        $params = [];
        if ($migrationClass) {
            $sql .= " WHERE tm.migration_class = ?";
            $params[] = $migrationClass;
        }

        $sql .= " ORDER BY tm.executed_at DESC";

        $stmt = $mainDb->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Check if migration is pending for any tenant
     */
    public function hasPendingMigrations(): array
    {
        $mainDb = \App\Database::getConnection();

        // Get all active tenants
        $tenants = $this->tenantModel->findWhere(['status' => 'active']);

        $pending = [];

        foreach ($tenants as $tenant) {
            // Check if tenant has any failed or missing migrations
            $stmt = $mainDb->prepare("
                SELECT migration_class
                FROM tenant_migrations
                WHERE tenant_id = ? AND status = 'failed'
            ");
            $stmt->execute([$tenant['id']]);
            $failedMigrations = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            if (!empty($failedMigrations)) {
                $pending[] = [
                    'tenant_id' => $tenant['id'],
                    'tenant_name' => $tenant['name'],
                    'issues' => 'failed_migrations',
                    'failed_count' => count($failedMigrations),
                    'failed_migrations' => $failedMigrations
                ];
            }
        }

        return $pending;
    }

    /**
     * Get migration execution summary
     */
    public function getMigrationSummary(): array
    {
        $mainDb = \App\Database::getConnection();

        $stmt = $mainDb->prepare("
            SELECT
                migration_class,
                status,
                COUNT(*) as count
            FROM tenant_migrations
            GROUP BY migration_class, status
            ORDER BY migration_class, status
        ");

        $stmt->execute();
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $summary = [];
        foreach ($results as $result) {
            $migration = $result['migration_class'];
            if (!isset($summary[$migration])) {
                $summary[$migration] = [
                    'migration' => $migration,
                    'success' => 0,
                    'failed' => 0,
                    'total' => 0
                ];
            }

            $summary[$migration][$result['status']] = $result['count'];
            $summary[$migration]['total'] += $result['count'];
        }

        return array_values($summary);
    }
}

/**
 * Base Migration Class for Tenant Databases
 */
abstract class TenantMigration
{
    protected \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Run the migration
     */
    abstract public function up(): void;

    /**
     * Rollback the migration
     */
    abstract public function down(): void;

    /**
     * Helper to execute SQL
     */
    protected function execute(string $sql): void
    {
        $this->db->exec($sql);
    }

    /**
     * Helper to add column
     */
    protected function addColumn(string $table, string $column, string $definition): void
    {
        $this->execute("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }

    /**
     * Helper to drop column
     */
    protected function dropColumn(string $table, string $column): void
    {
        $this->execute("ALTER TABLE {$table} DROP COLUMN {$column}");
    }

    /**
     * Helper to create index
     */
    protected function createIndex(string $table, string $indexName, string $columns): void
    {
        $this->execute("CREATE INDEX {$indexName} ON {$table} ({$columns})");
    }

    /**
     * Helper to drop index
     */
    protected function dropIndex(string $table, string $indexName): void
    {
        $this->execute("DROP INDEX {$indexName} ON {$table}");
    }
}

/**
 * Migration Runner for Batch Operations
 */
class MigrationRunner
{
    private TenantMigrationManager $migrationManager;

    public function __construct()
    {
        $this->migrationManager = new TenantMigrationManager();
    }

    /**
     * Run a batch of migrations
     */
    public function runMigrations(array $migrations): array
    {
        $batchResults = [
            'batch_id' => uniqid('batch_', true),
            'started_at' => date('Y-m-d H:i:s'),
            'migrations' => [],
            'summary' => [
                'total_migrations' => count($migrations),
                'successful' => 0,
                'failed' => 0,
                'total_tenants_affected' => 0
            ]
        ];

        foreach ($migrations as $migrationClass) {
            $result = $this->migrationManager->runMigrationOnAllTenants($migrationClass);

            $migrationResult = [
                'migration_class' => $migrationClass,
                'executed_at' => date('Y-m-d H:i:s'),
                'results' => $result
            ];

            $batchResults['migrations'][] = $migrationResult;

            $successCount = count($result['success']);
            $failCount = count($result['failed']);

            $batchResults['summary']['successful'] += $successCount;
            $batchResults['summary']['failed'] += $failCount;
            $batchResults['summary']['total_tenants_affected'] += $successCount + $failCount;
        }

        $batchResults['completed_at'] = date('Y-m-d H:i:s');

        // Log batch results
        $this->logBatchResults($batchResults);

        return $batchResults;
    }

    /**
     * Log batch execution results
     */
    private function logBatchResults(array $batchResults): void
    {
        $logData = json_encode($batchResults, JSON_PRETTY_PRINT);
        $logFile = __DIR__ . '/../../../logs/migration_batches/' . date('Y-m-d') . '.log';

        // Ensure log directory exists
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logFile, $logData . PHP_EOL, FILE_APPEND);
    }

    /**
     * Rollback a batch of migrations
     */
    public function rollbackMigrations(array $migrations): array
    {
        $batchResults = [
            'batch_id' => uniqid('rollback_', true),
            'started_at' => date('Y-m-d H:i:s'),
            'migrations' => [],
            'summary' => [
                'total_migrations' => count($migrations),
                'successful' => 0,
                'failed' => 0
            ]
        ];

        // Rollback in reverse order
        $migrations = array_reverse($migrations);

        foreach ($migrations as $migrationClass) {
            $result = $this->migrationManager->rollbackMigrationOnAllTenants($migrationClass);

            $migrationResult = [
                'migration_class' => $migrationClass,
                'rolled_back_at' => date('Y-m-d H:i:s'),
                'results' => $result
            ];

            $batchResults['migrations'][] = $migrationResult;

            $batchResults['summary']['successful'] += count($result['success']);
            $batchResults['summary']['failed'] += count($result['failed']);
        }

        $batchResults['completed_at'] = date('Y-m-d H:i:s');

        return $batchResults;
    }
}

// =========================================
// TENANT MIGRATIONS TABLE SETUP
// =========================================

/*
-- Create this table in the main application database:

CREATE TABLE tenant_migrations (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    migration_class VARCHAR(255) NOT NULL,
    status ENUM('up', 'down', 'failed') NOT NULL,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    error_message TEXT NULL,
    batch_id VARCHAR(100) NULL,
    INDEX idx_tenant_migration (tenant_id, migration_class),
    INDEX idx_status_executed (status, executed_at),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

-- Example migration file structure:

<?php
use App\Database\Migrations\TenantMigration;

class AddTenantIdToUsersTable extends TenantMigration
{
    public function up(): void
    {
        // Add tenant_id column to users table
        $this->addColumn('users', 'tenant_id', 'INT NULL');

        // Create index
        $this->createIndex('users', 'idx_users_tenant', 'tenant_id');

        // Add foreign key (optional, depending on design)
        $this->execute('ALTER TABLE users ADD CONSTRAINT fk_users_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id)');
    }

    public function down(): void
    {
        // Remove foreign key
        $this->execute('ALTER TABLE users DROP FOREIGN KEY fk_users_tenant');

        // Drop index
        $this->dropIndex('users', 'idx_users_tenant');

        // Remove column
        $this->dropColumn('users', 'tenant_id');
    }
}
*/

?>
