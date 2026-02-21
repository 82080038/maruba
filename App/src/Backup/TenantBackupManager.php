<?php
namespace App\Backup;

use App\Models\Tenant;

/**
 * Automated Backup System for Multi-Tenant Databases
 *
 * Provides comprehensive backup and restore capabilities
 * for tenant databases with automated scheduling
 */
class TenantBackupManager
{
    private Tenant $tenantModel;
    private string $backupBasePath;
    private array $backupConfig;

    public function __construct()
    {
        $this->tenantModel = new Tenant();
        $this->backupBasePath = __DIR__ . '/../../../backups/tenants';

        // Ensure backup directory exists
        if (!is_dir($this->backupBasePath)) {
            mkdir($this->backupBasePath, 0755, true);
        }

        $this->backupConfig = [
            'retention_days' => 30,
            'compression' => 'gzip',
            'schedule' => [
                'full_backup' => 'weekly',    // Sunday 02:00
                'incremental' => 'daily',     // Daily 02:00
                'cleanup' => 'daily'         // Daily 03:00
            ]
        ];
    }

    /**
     * Create full backup for specific tenant
     */
    public function createTenantBackup(int $tenantId, string $type = 'full'): array
    {
        try {
            $tenant = $this->tenantModel->find($tenantId);
            if (!$tenant) {
                throw new \Exception("Tenant not found: {$tenantId}");
            }

            // Create backup directory for tenant
            $tenantBackupPath = $this->getTenantBackupPath($tenantId);
            if (!is_dir($tenantBackupPath)) {
                mkdir($tenantBackupPath, 0755, true);
            }

            $timestamp = date('Y-m-d_H-i-s');
            $backupFilename = "{$type}_{$tenant['slug']}_{$timestamp}.sql";

            if ($this->backupConfig['compression'] === 'gzip') {
                $backupFilename .= '.gz';
            }

            $backupPath = $tenantBackupPath . '/' . $backupFilename;

            // Perform database backup
            $result = $this->performDatabaseBackup($tenantId, $backupPath, $type);

            if ($result['success']) {
                // Record backup in database
                $this->recordBackup($tenantId, [
                    'filename' => $backupFilename,
                    'path' => $backupPath,
                    'type' => $type,
                    'size' => filesize($backupPath),
                    'status' => 'completed',
                    'created_by' => $this->getCurrentUserId()
                ]);

                // Clean up old backups
                $this->cleanupOldBackups($tenantId);
            }

            return $result;

        } catch (\Exception $e) {
            error_log("Backup failed for tenant {$tenantId}: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
                'type' => $type
            ];
        }
    }

    /**
     * Perform database backup using mysqldump
     */
    private function performDatabaseBackup(int $tenantId, string $backupPath, string $type): array
    {
        try {
            // Get tenant database credentials
            $dbConfig = $this->getTenantDatabaseConfig($tenantId);

            // Build mysqldump command
            $command = $this->buildMysqldumpCommand($dbConfig, $backupPath, $type);

            // Execute backup
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            if ($returnCode === 0) {
                return [
                    'success' => true,
                    'path' => $backupPath,
                    'size' => filesize($backupPath),
                    'command' => $command
                ];
            } else {
                throw new \Exception("mysqldump failed with code {$returnCode}: " . implode("\n", $output));
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Build mysqldump command
     */
    private function buildMysqldumpCommand(array $dbConfig, string $backupPath, string $type): string
    {
        $host = escapeshellarg($dbConfig['host']);
        $user = escapeshellarg($dbConfig['username']);
        $password = escapeshellarg($dbConfig['password']);
        $database = escapeshellarg($dbConfig['database']);

        $baseCommand = "mysqldump -h{$host} -u{$user} -p{$password} {$database}";

        // Add options based on backup type
        if ($type === 'full') {
            // Full backup with all options
            $baseCommand .= " --single-transaction --routines --triggers --all-databases";
        } elseif ($type === 'incremental') {
            // Incremental backup (simplified - would need binary logs for true incremental)
            $baseCommand .= " --single-transaction --routines --triggers";
        }

        // Add compression if configured
        if ($this->backupConfig['compression'] === 'gzip') {
            $baseCommand .= " | gzip > " . escapeshellarg($backupPath);
        } else {
            $baseCommand .= " > " . escapeshellarg($backupPath);
        }

        return $baseCommand;
    }

    /**
     * Get tenant database configuration
     */
    private function getTenantDatabaseConfig(int $tenantId): array
    {
        // In a real implementation, this would retrieve from secure config
        // For now, return placeholder config
        return [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'username' => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASS'] ?? '',
            'database' => "tenant_{$tenantId}",
            'port' => $_ENV['DB_PORT'] ?? '3306'
        ];
    }

    /**
     * Record backup in database
     */
    private function recordBackup(int $tenantId, array $backupData): void
    {
        $mainDb = \App\Database::getConnection();

        $stmt = $mainDb->prepare("
            INSERT INTO tenant_backups (
                tenant_id, backup_name, backup_path, backup_size,
                status, backup_type, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $tenantId,
            $backupData['filename'],
            $backupData['path'],
            $backupData['size'],
            $backupData['status'],
            $backupData['type'],
            $backupData['created_by']
        ]);
    }

    /**
     * Restore tenant database from backup
     */
    public function restoreTenantBackup(int $tenantId, string $backupFilename): array
    {
        try {
            $tenant = $this->tenantModel->find($tenantId);
            if (!$tenant) {
                throw new \Exception("Tenant not found: {$tenantId}");
            }

            $backupPath = $this->getTenantBackupPath($tenantId) . '/' . $backupFilename;

            if (!file_exists($backupPath)) {
                throw new \Exception("Backup file not found: {$backupFilename}");
            }

            // Perform restore
            $result = $this->performDatabaseRestore($tenantId, $backupPath);

            if ($result['success']) {
                // Record restore operation
                $this->recordRestoreOperation($tenantId, $backupFilename, 'completed');
            }

            return $result;

        } catch (\Exception $e) {
            $this->recordRestoreOperation($tenantId, $backupFilename, 'failed', $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
                'backup_filename' => $backupFilename
            ];
        }
    }

    /**
     * Perform database restore using mysql
     */
    private function performDatabaseRestore(int $tenantId, string $backupPath): array
    {
        try {
            $dbConfig = $this->getTenantDatabaseConfig($tenantId);

            // Build mysql restore command
            $command = $this->buildMysqlRestoreCommand($dbConfig, $backupPath);

            // Execute restore
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            if ($returnCode === 0) {
                return [
                    'success' => true,
                    'command' => $command,
                    'output' => $output
                ];
            } else {
                throw new \Exception("mysql restore failed with code {$returnCode}: " . implode("\n", $output));
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Build mysql restore command
     */
    private function buildMysqlRestoreCommand(array $dbConfig, string $backupPath): string
    {
        $host = escapeshellarg($dbConfig['host']);
        $user = escapeshellarg($dbConfig['username']);
        $password = escapeshellarg($dbConfig['password']);
        $database = escapeshellarg($dbConfig['database']);

        $baseCommand = "mysql -h{$host} -u{$user} -p{$password} {$database}";

        // Handle compression
        if ($this->backupConfig['compression'] === 'gzip') {
            $baseCommand = "gzip -dc " . escapeshellarg($backupPath) . " | {$baseCommand}";
        } else {
            $baseCommand .= " < " . escapeshellarg($backupPath);
        }

        return $baseCommand;
    }

    /**
     * Record restore operation
     */
    private function recordRestoreOperation(int $tenantId, string $backupFilename, string $status, string $error = null): void
    {
        $mainDb = \App\Database::getConnection();

        $stmt = $mainDb->prepare("
            UPDATE tenant_backups
            SET restored_at = NOW(), restored_by = ?, status = ?
            WHERE tenant_id = ? AND backup_name = ?
        ");

        $stmt->execute([
            $this->getCurrentUserId(),
            $status,
            $tenantId,
            $backupFilename
        ]);

        // Log restore operation
        $stmt = $mainDb->prepare("
            INSERT INTO restore_logs (tenant_id, backup_filename, status, error_message, performed_by)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $tenantId,
            $backupFilename,
            $status,
            $error,
            $this->getCurrentUserId()
        ]);
    }

    /**
     * Create backups for all active tenants
     */
    public function createAllTenantBackups(string $type = 'full'): array
    {
        $results = [
            'total_tenants' => 0,
            'successful' => [],
            'failed' => [],
            'start_time' => date('Y-m-d H:i:s')
        ];

        $tenants = $this->tenantModel->findWhere(['status' => 'active']);
        $results['total_tenants'] = count($tenants);

        foreach ($tenants as $tenant) {
            $result = $this->createTenantBackup($tenant['id'], $type);

            if ($result['success']) {
                $results['successful'][] = [
                    'tenant_id' => $tenant['id'],
                    'tenant_name' => $tenant['name'],
                    'backup_path' => $result['path'],
                    'backup_size' => $result['size']
                ];
            } else {
                $results['failed'][] = [
                    'tenant_id' => $tenant['id'],
                    'tenant_name' => $tenant['name'],
                    'error' => $result['error']
                ];
            }
        }

        $results['end_time'] = date('Y-m-d H:i:s');
        $results['duration_seconds'] = strtotime($results['end_time']) - strtotime($results['start_time']);

        // Log batch operation
        $this->logBatchBackupOperation($results, $type);

        return $results;
    }

    /**
     * Clean up old backups
     */
    private function cleanupOldBackups(int $tenantId): void
    {
        $tenantBackupPath = $this->getTenantBackupPath($tenantId);
        $retentionDays = $this->backupConfig['retention_days'];

        if (!is_dir($tenantBackupPath)) {
            return;
        }

        $files = scandir($tenantBackupPath);
        $now = time();
        $retentionSeconds = $retentionDays * 24 * 60 * 60;

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $filePath = $tenantBackupPath . '/' . $file;
            $fileModified = filemtime($filePath);

            if (($now - $fileModified) > $retentionSeconds) {
                unlink($filePath);

                // Update database record
                $mainDb = \App\Database::getConnection();
                $stmt = $mainDb->prepare("
                    UPDATE tenant_backups
                    SET status = 'expired'
                    WHERE tenant_id = ? AND backup_path = ?
                ");
                $stmt->execute([$tenantId, $filePath]);
            }
        }
    }

    /**
     * Get tenant backup path
     */
    private function getTenantBackupPath(int $tenantId): string
    {
        return $this->backupBasePath . '/' . $tenantId;
    }

    /**
     * Get current user ID (placeholder)
     */
    private function getCurrentUserId(): ?int
    {
        // In a real implementation, get from session
        return $_SESSION['user']['id'] ?? null;
    }

    /**
     * Log batch backup operation
     */
    private function logBatchBackupOperation(array $results, string $type): void
    {
        $logData = json_encode($results, JSON_PRETTY_PRINT);
        $logFile = __DIR__ . '/../../../logs/backup_batches/' . date('Y-m-d') . '.log';

        // Ensure log directory exists
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logFile, $logData . PHP_EOL, FILE_APPEND);
    }

    /**
     * Get backup statistics
     */
    public function getBackupStatistics(): array
    {
        $mainDb = \App\Database::getConnection();

        $stmt = $mainDb->prepare("
            SELECT
                COUNT(*) as total_backups,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful_backups,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_backups,
                SUM(CASE WHEN backup_type = 'full' THEN 1 ELSE 0 END) as full_backups,
                SUM(CASE WHEN backup_type = 'incremental' THEN 1 ELSE 0 END) as incremental_backups,
                SUM(backup_size) as total_size_bytes,
                AVG(backup_size) as avg_size_bytes,
                MAX(created_at) as last_backup_date
            FROM tenant_backups
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");

        $stmt->execute();
        $stats = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Convert bytes to MB
        $stats['total_size_mb'] = round(($stats['total_size_bytes'] ?? 0) / 1024 / 1024, 2);
        $stats['avg_size_mb'] = round(($stats['avg_size_bytes'] ?? 0) / 1024 / 1024, 2);

        return $stats;
    }

    /**
     * Get tenant backup list
     */
    public function getTenantBackups(int $tenantId): array
    {
        $mainDb = \App\Database::getConnection();

        $stmt = $mainDb->prepare("
            SELECT * FROM tenant_backups
            WHERE tenant_id = ?
            ORDER BY created_at DESC
            LIMIT 50
        ");

        $stmt->execute([$tenantId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Validate backup integrity
     */
    public function validateBackupIntegrity(string $backupPath): array
    {
        $result = [
            'valid' => false,
            'size' => 0,
            'readable' => false,
            'errors' => []
        ];

        if (!file_exists($backupPath)) {
            $result['errors'][] = 'Backup file does not exist';
            return $result;
        }

        $result['size'] = filesize($backupPath);

        // Check if file is readable
        if (!is_readable($backupPath)) {
            $result['errors'][] = 'Backup file is not readable';
            return $result;
        }

        $result['readable'] = true;

        // Try to read first few lines to validate format
        $handle = fopen($backupPath, 'r');
        if ($handle) {
            $firstLine = fgets($handle);
            fclose($handle);

            // Check if it looks like a SQL dump
            if (strpos($firstLine, '-- MySQL dump') === false &&
                strpos($firstLine, '-- Dump completed') === false) {
                $result['errors'][] = 'File does not appear to be a valid SQL dump';
                return $result;
            }
        }

        $result['valid'] = true;
        return $result;
    }

    /**
     * Schedule automated backups
     */
    public function scheduleAutomatedBackups(): void
    {
        // This would typically be called from a cron job
        // For full backups (weekly)
        if (date('w') === '0' && date('H') === '02') { // Sunday 02:00
            $this->createAllTenantBackups('full');
        }

        // For incremental backups (daily)
        if (date('H') === '02') { // Daily 02:00
            $this->createAllTenantBackups('incremental');
        }

        // Cleanup old backups
        if (date('H') === '03') { // Daily 03:00
            $this->cleanupExpiredBackups();
        }
    }

    /**
     * Cleanup expired backups across all tenants
     */
    private function cleanupExpiredBackups(): void
    {
        $tenants = $this->tenantModel->findWhere(['status' => 'active']);

        foreach ($tenants as $tenant) {
            $this->cleanupOldBackups($tenant['id']);
        }
    }
}

/**
 * Backup CLI Commands for Automation
 */
class BackupCLI
{
    private TenantBackupManager $backupManager;

    public function __construct()
    {
        $this->backupManager = new TenantBackupManager();
    }

    /**
     * Run backup command
     */
    public function runBackup(array $args): void
    {
        $command = $args[0] ?? 'help';

        switch ($command) {
            case 'create':
                $tenantId = (int)($args[1] ?? 0);
                $type = $args[2] ?? 'full';

                if (!$tenantId) {
                    echo "Usage: backup create <tenant_id> [full|incremental]\n";
                    return;
                }

                echo "Creating {$type} backup for tenant {$tenantId}...\n";
                $result = $this->backupManager->createTenantBackup($tenantId, $type);

                if ($result['success']) {
                    echo "✅ Backup created successfully\n";
                    echo "Path: {$result['path']}\n";
                    echo "Size: " . round($result['size'] / 1024 / 1024, 2) . " MB\n";
                } else {
                    echo "❌ Backup failed: {$result['error']}\n";
                }
                break;

            case 'create-all':
                $type = $args[1] ?? 'full';

                echo "Creating {$type} backups for all tenants...\n";
                $results = $this->backupManager->createAllTenantBackups($type);

                echo "Total tenants: {$results['total_tenants']}\n";
                echo "Successful: " . count($results['successful']) . "\n";
                echo "Failed: " . count($results['failed']) . "\n";

                if (!empty($results['failed'])) {
                    echo "\nFailed backups:\n";
                    foreach ($results['failed'] as $failed) {
                        echo "- Tenant {$failed['tenant_id']} ({$failed['tenant_name']}): {$failed['error']}\n";
                    }
                }
                break;

            case 'restore':
                $tenantId = (int)($args[1] ?? 0);
                $filename = $args[2] ?? '';

                if (!$tenantId || !$filename) {
                    echo "Usage: backup restore <tenant_id> <backup_filename>\n";
                    return;
                }

                echo "Restoring backup for tenant {$tenantId}...\n";
                $result = $this->backupManager->restoreTenantBackup($tenantId, $filename);

                if ($result['success']) {
                    echo "✅ Restore completed successfully\n";
                } else {
                    echo "❌ Restore failed: {$result['error']}\n";
                }
                break;

            case 'stats':
                $stats = $this->backupManager->getBackupStatistics();

                echo "Backup Statistics (Last 30 Days):\n";
                echo "Total backups: {$stats['total_backups']}\n";
                echo "Successful: {$stats['successful_backups']}\n";
                echo "Failed: {$stats['failed_backups']}\n";
                echo "Full backups: {$stats['full_backups']}\n";
                echo "Incremental: {$stats['incremental_backups']}\n";
                echo "Total size: {$stats['total_size_mb']} MB\n";
                echo "Average size: {$stats['avg_size_mb']} MB\n";
                echo "Last backup: {$stats['last_backup_date']}\n";
                break;

            default:
                echo "Available commands:\n";
                echo "  create <tenant_id> [type]     - Create backup for specific tenant\n";
                echo "  create-all [type]             - Create backups for all tenants\n";
                echo "  restore <tenant_id> <file>    - Restore backup for tenant\n";
                echo "  stats                         - Show backup statistics\n";
                echo "  help                          - Show this help\n";
                break;
        }
    }
}

// =========================================
// BACKUP SYSTEM DATABASE TABLES
// =========================================

/*
-- Additional tables needed for backup system:

-- Restore operation logs
CREATE TABLE restore_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    backup_filename VARCHAR(255) NOT NULL,
    status ENUM('completed', 'failed') NOT NULL,
    error_message TEXT NULL,
    performed_by INT NULL,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (performed_by) REFERENCES users(id)
);

-- Backup schedules (for automated backups)
CREATE TABLE backup_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NULL, -- NULL for all tenants
    schedule_type ENUM('full', 'incremental') NOT NULL,
    frequency ENUM('daily', 'weekly', 'monthly') NOT NULL,
    day_of_week TINYINT NULL, -- 0-6 for weekly
    day_of_month TINYINT NULL, -- 1-31 for monthly
    time TIME NOT NULL DEFAULT '02:00:00',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

-- Insert default schedules
INSERT INTO backup_schedules (schedule_type, frequency, day_of_week, time) VALUES
('full', 'weekly', 0, '02:00:00'),        -- Sunday 02:00
('incremental', 'daily', NULL, '02:00:00'); -- Daily 02:00

-- Backup retention policies
CREATE TABLE backup_policies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NULL, -- NULL for global policy
    backup_type ENUM('full', 'incremental') NOT NULL,
    retention_days INT NOT NULL DEFAULT 30,
    max_backups INT NULL, -- Maximum number of backups to keep
    compression_enabled BOOLEAN DEFAULT TRUE,
    encryption_enabled BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

-- Insert default policies
INSERT INTO backup_policies (backup_type, retention_days, max_backups) VALUES
('full', 90, 12),           -- Keep 90 days, max 12 backups
('incremental', 30, 30);    -- Keep 30 days, max 30 backups
*/

?>
