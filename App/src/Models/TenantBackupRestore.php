<?php
namespace App\Models;

class TenantBackupRestore extends Model
{
    protected string $table = 'tenant_backups';
    protected array $fillable = [
        'tenant_id', 'backup_name', 'backup_path', 'backup_size',
        'status', 'backup_type', 'created_by', 'restored_at',
        'restored_by', 'notes'
    ];
    protected array $casts = [
        'tenant_id' => 'int',
        'backup_size' => 'int',
        'created_by' => 'int',
        'restored_at' => 'datetime',
        'restored_by' => 'int',
        'created_at' => 'datetime'
    ];

    /**
     * Create tenant database backup
     */
    public function createBackup(int $tenantId, string $backupName, int $createdBy): array
    {
        $tenantModel = new Tenant();
        $tenant = $tenantModel->find($tenantId);

        if (!$tenant) {
            throw new \Exception('Tenant not found');
        }

        try {
            // Generate backup filename
            $timestamp = date('Y-m-d_H-i-s');
            $backupFilename = "tenant_{$tenant['slug']}_{$timestamp}.sql";
            $backupPath = "backups/tenants/{$backupFilename}";

            // Create backup directory
            $backupDir = __DIR__ . '/../../storage/backups/tenants/';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $fullBackupPath = $backupDir . $backupFilename;

            // Get tenant database connection
            $tenantDb = $tenantModel->getTenantDatabase($tenant['slug']);

            // Create backup using mysqldump command
            $backupResult = $this->createDatabaseBackup($tenantDb, $fullBackupPath, $tenant['slug']);

            if (!$backupResult['success']) {
                throw new \Exception('Failed to create database backup: ' . $backupResult['error']);
            }

            // Get backup file size
            $backupSize = filesize($fullBackupPath);

            // Record backup in database
            $backupId = $this->create([
                'tenant_id' => $tenantId,
                'backup_name' => $backupName,
                'backup_path' => $backupPath,
                'backup_size' => $backupSize,
                'status' => 'completed',
                'backup_type' => 'full',
                'created_by' => $createdBy,
                'notes' => 'Automated backup'
            ]);

            return [
                'success' => true,
                'backup_id' => $backupId,
                'backup_path' => $backupPath,
                'backup_size' => $backupSize
            ];

        } catch (\Exception $e) {
            // Log failed backup
            $this->create([
                'tenant_id' => $tenantId,
                'backup_name' => $backupName,
                'backup_path' => '',
                'backup_size' => 0,
                'status' => 'failed',
                'backup_type' => 'full',
                'created_by' => $createdBy,
                'notes' => 'Backup failed: ' . $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Restore tenant database from backup
     */
    public function restoreBackup(int $backupId, int $restoredBy): array
    {
        $backup = $this->find($backupId);
        if (!$backup) {
            throw new \Exception('Backup not found');
        }

        if ($backup['status'] !== 'completed') {
            throw new \Exception('Backup is not available for restore');
        }

        $tenantModel = new Tenant();
        $tenant = $tenantModel->find($backup['tenant_id']);

        if (!$tenant) {
            throw new \Exception('Tenant not found');
        }

        try {
            // Get backup file path
            $fullBackupPath = __DIR__ . '/../../storage/' . $backup['backup_path'];

            if (!file_exists($fullBackupPath)) {
                throw new \Exception('Backup file not found');
            }

            // Get tenant database connection
            $tenantDb = $tenantModel->getTenantDatabase($tenant['slug']);

            // Perform restore
            $restoreResult = $this->restoreDatabaseBackup($tenantDb, $fullBackupPath, $tenant['slug']);

            if (!$restoreResult['success']) {
                throw new \Exception('Failed to restore database: ' . $restoreResult['error']);
            }

            // Update backup record
            $this->update($backupId, [
                'status' => 'restored',
                'restored_at' => date('Y-m-d H:i:s'),
                'restored_by' => $restoredBy,
                'notes' => 'Restored successfully'
            ]);

            return [
                'success' => true,
                'message' => 'Database restored successfully',
                'backup_id' => $backupId
            ];

        } catch (\Exception $e) {
            // Update backup record with failure
            $this->update($backupId, [
                'notes' => 'Restore failed: ' . $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Create database backup using mysqldump
     */
    private function createDatabaseBackup(\PDO $db, string $backupPath, string $dbName): array
    {
        try {
            // Get database connection info
            $dsn = $db->getAttribute(\PDO::ATTR_CONNECTION_STATUS);
            preg_match('/host=([^;]+)/', $dsn, $hostMatches);
            preg_match('/dbname=([^;]+)/', $dsn, $dbMatches);

            $host = $hostMatches[1] ?? 'localhost';
            $dbName = $dbMatches[1] ?? $dbName;

            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? '';

            // Execute mysqldump command
            $command = sprintf(
                'mysqldump --host=%s --user=%s --password=%s %s > %s 2>&1',
                escapeshellarg($host),
                escapeshellarg($user),
                escapeshellarg($pass),
                escapeshellarg($dbName),
                escapeshellarg($backupPath)
            );

            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                return [
                    'success' => false,
                    'error' => 'mysqldump failed: ' . implode("\n", $output)
                ];
            }

            // Verify backup file was created
            if (!file_exists($backupPath) || filesize($backupPath) === 0) {
                return [
                    'success' => false,
                    'error' => 'Backup file was not created or is empty'
                ];
            }

            return ['success' => true];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Backup creation failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Restore database from backup
     */
    private function restoreDatabaseBackup(\PDO $db, string $backupPath, string $dbName): array
    {
        try {
            // Get database connection info
            $dsn = $db->getAttribute(\PDO::ATTR_CONNECTION_STATUS);
            preg_match('/host=([^;]+)/', $dsn, $hostMatches);

            $host = $hostMatches[1] ?? 'localhost';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? '';

            // Execute mysql restore command
            $command = sprintf(
                'mysql --host=%s --user=%s --password=%s %s < %s 2>&1',
                escapeshellarg($host),
                escapeshellarg($user),
                escapeshellarg($pass),
                escapeshellarg($dbName),
                escapeshellarg($backupPath)
            );

            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                return [
                    'success' => false,
                    'error' => 'mysql restore failed: ' . implode("\n", $output)
                ];
            }

            return ['success' => true];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Restore failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete old backups (cleanup)
     */
    public function cleanupOldBackups(int $retentionDays = 30): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$retentionDays} days"));

        // Find old backups
        $oldBackups = $this->findWhere([], [], null, "created_at < '{$cutoffDate}'");

        $deletedCount = 0;

        foreach ($oldBackups as $backup) {
            // Delete physical file
            $fullPath = __DIR__ . '/../../storage/' . $backup['backup_path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            // Delete database record
            $this->delete($backup['id']);
            $deletedCount++;
        }

        return $deletedCount;
    }

    /**
     * Get tenant backups
     */
    public function getTenantBackups(int $tenantId): array
    {
        return $this->findWhere(['tenant_id' => $tenantId], ['created_at' => 'DESC']);
    }

    /**
     * Get backup statistics
     */
    public function getBackupStatistics(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_backups,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as successful_backups,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_backups,
                COUNT(CASE WHEN restored_at IS NOT NULL THEN 1 END) as restored_backups,
                SUM(backup_size) as total_backup_size,
                AVG(backup_size) as avg_backup_size
            FROM {$this->table}
        ");
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Validate backup file integrity
     */
    public function validateBackup(int $backupId): array
    {
        $backup = $this->find($backupId);
        if (!$backup) {
            return ['valid' => false, 'error' => 'Backup not found'];
        }

        $fullPath = __DIR__ . '/../../storage/' . $backup['backup_path'];

        if (!file_exists($fullPath)) {
            return ['valid' => false, 'error' => 'Backup file not found'];
        }

        // Basic validation - check if file is readable and contains SQL
        $content = file_get_contents($fullPath, false, null, 0, 1024);

        if (!$content) {
            return ['valid' => false, 'error' => 'Backup file is empty'];
        }

        // Check for basic SQL structure
        if (strpos($content, 'CREATE TABLE') === false && strpos($content, 'INSERT INTO') === false) {
            return ['valid' => false, 'error' => 'Backup file does not contain valid SQL'];
        }

        return ['valid' => true, 'size' => filesize($fullPath)];
    }

    /**
     * Create scheduled backup (to be called by cron)
     */
    public function createScheduledBackups(): array
    {
        $results = [];

        // Get all active tenants
        $tenantModel = new Tenant();
        $tenants = $tenantModel->getActiveTenants();

        foreach ($tenants as $tenant) {
            try {
                $backupName = "Auto Backup - " . date('Y-m-d H:i:s');
                $result = $this->createBackup($tenant['id'], $backupName, 1); // System user
                $results[] = [
                    'tenant' => $tenant['name'],
                    'success' => true,
                    'backup_id' => $result['backup_id']
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'tenant' => $tenant['name'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }
}
