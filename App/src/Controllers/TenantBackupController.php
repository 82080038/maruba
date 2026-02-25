<?php
namespace App\Controllers;

use App\Models\TenantBackupRestore;
use App\Models\Tenant;
use App\Helpers\AuthHelper;

class TenantBackupController
{
    /**
     * Show tenant backups dashboard
     */
    public function index(): void
    {
        require_login();
        AuthHelper::requirePermission('backups', 'view');

        $backupModel = new TenantBackupRestore();
        $statistics = $backupModel->getBackupStatistics();

        // Get backups for current tenant (if in tenant context) or all tenants (if admin)
        $currentTenant = $this->getCurrentTenant();
        if ($currentTenant) {
            $backups = $backupModel->getTenantBackups($currentTenant['id']);
        } else {
            // Admin view - get all backups
            $backups = $backupModel->all(['created_at' => 'DESC'], 50);
        }

        include view_path('tenant/backups/index');
    }

    /**
     * Create new backup
     */
    public function createBackup(): void
    {
        require_login();
        AuthHelper::requirePermission('backups', 'create');

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            $_SESSION['error'] = 'No tenant context found';
            header('Location: ' . route_url('tenant/backups'));
            return;
        }

        $backupName = trim($_POST['backup_name'] ?? '');
        if (empty($backupName)) {
            $_SESSION['error'] = 'Backup name is required';
            header('Location: ' . route_url('tenant/backups'));
            return;
        }

        $backupModel = new TenantBackupRestore();
        $user = current_user();

        try {
            $result = $backupModel->createBackup($currentTenant['id'], $backupName, $user['id']);

            $_SESSION['success'] = 'Backup created successfully. Size: ' .
                $this->formatBytes($result['backup_size']);

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to create backup: ' . $e->getMessage();
        }

        header('Location: ' . route_url('tenant/backups'));
    }

    /**
     * Download backup file
     */
    public function downloadBackup(): void
    {
        require_login();
        AuthHelper::requirePermission('backups', 'download');

        $backupId = (int)($_GET['id'] ?? 0);
        if (!$backupId) {
            http_response_code(400);
            echo 'Backup ID required';
            return;
        }

        $backupModel = new TenantBackupRestore();
        $backup = $backupModel->find($backupId);

        if (!$backup) {
            http_response_code(404);
            echo 'Backup not found';
            return;
        }

        // Check if user can access this backup
        $currentTenant = $this->getCurrentTenant();
        if ($currentTenant && $backup['tenant_id'] !== $currentTenant['id']) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        // Check if backup is available
        if ($backup['status'] !== 'completed') {
            http_response_code(400);
            echo 'Backup is not available for download';
            return;
        }

        $fullPath = __DIR__ . '/../../storage/' . $backup['backup_path'];

        if (!file_exists($fullPath)) {
            http_response_code(404);
            echo 'Backup file not found';
            return;
        }

        // Set headers for download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($backup['backup_path']) . '"');
        header('Content-Length: ' . filesize($fullPath));

        // Output file
        readfile($fullPath);
    }

    /**
     * Restore from backup
     */
    public function restoreBackup(): void
    {
        require_login();
        AuthHelper::requirePermission('backups', 'restore');

        $backupId = (int)($_POST['backup_id'] ?? 0);
        if (!$backupId) {
            $_SESSION['error'] = 'Backup ID required';
            header('Location: ' . route_url('tenant/backups'));
            return;
        }

        $backupModel = new TenantBackupRestore();
        $backup = $backupModel->find($backupId);

        if (!$backup) {
            $_SESSION['error'] = 'Backup not found';
            header('Location: ' . route_url('tenant/backups'));
            return;
        }

        // Check if user can restore this backup
        $currentTenant = $this->getCurrentTenant();
        if ($currentTenant && $backup['tenant_id'] !== $currentTenant['id']) {
            $_SESSION['error'] = 'Access denied';
            header('Location: ' . route_url('tenant/backups'));
            return;
        }

        $user = current_user();

        try {
            $result = $backupModel->restoreBackup($backupId, $user['id']);

            $_SESSION['success'] = 'Database restored successfully from backup';

            // Log the restore operation
            error_log("Database restored for tenant {$backup['tenant_id']} from backup {$backupId} by user {$user['id']}");

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to restore backup: ' . $e->getMessage();
        }

        header('Location: ' . route_url('tenant/backups'));
    }

    /**
     * Delete backup
     */
    public function deleteBackup(): void
    {
        require_login();
        AuthHelper::requirePermission('backups', 'delete');

        $backupId = (int)($_GET['id'] ?? 0);
        if (!$backupId) {
            $_SESSION['error'] = 'Backup ID required';
            header('Location: ' . route_url('tenant/backups'));
            return;
        }

        $backupModel = new TenantBackupRestore();
        $backup = $backupModel->find($backupId);

        if (!$backup) {
            $_SESSION['error'] = 'Backup not found';
            header('Location: ' . route_url('tenant/backups'));
            return;
        }

        // Check if user can delete this backup
        $currentTenant = $this->getCurrentTenant();
        if ($currentTenant && $backup['tenant_id'] !== $currentTenant['id']) {
            $_SESSION['error'] = 'Access denied';
            header('Location: ' . route_url('tenant/backups'));
            return;
        }

        try {
            // Delete physical file
            $fullPath = __DIR__ . '/../../storage/' . $backup['backup_path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            // Delete database record
            $backupModel->delete($backupId);

            $_SESSION['success'] = 'Backup deleted successfully';

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to delete backup: ' . $e->getMessage();
        }

        header('Location: ' . route_url('tenant/backups'));
    }

    /**
     * Validate backup integrity
     */
    public function validateBackup(): void
    {
        require_login();
        AuthHelper::requirePermission('backups', 'view');

        $backupId = (int)($_GET['id'] ?? 0);
        if (!$backupId) {
            http_response_code(400);
            echo json_encode(['error' => 'Backup ID required']);
            return;
        }

        $backupModel = new TenantBackupRestore();
        $backup = $backupModel->find($backupId);

        if (!$backup) {
            http_response_code(404);
            echo json_encode(['error' => 'Backup not found']);
            return;
        }

        // Check if user can access this backup
        $currentTenant = $this->getCurrentTenant();
        if ($currentTenant && $backup['tenant_id'] !== $currentTenant['id']) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            return;
        }

        try {
            $validation = $backupModel->validateBackup($backupId);

            header('Content-Type: application/json');
            echo json_encode($validation);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // ===== ADMIN FUNCTIONS =====

    /**
     * Create scheduled backups for all tenants (admin only)
     */
    public function createScheduledBackups(): void
    {
        require_login();
        AuthHelper::requirePermission('backups', 'create');

        $backupModel = new TenantBackupRestore();

        try {
            $results = $backupModel->createScheduledBackups();

            $successCount = count(array_filter($results, fn($r) => $r['success']));
            $totalCount = count($results);

            $_SESSION['success'] = "Scheduled backups completed: {$successCount}/{$totalCount} successful";

            if ($successCount < $totalCount) {
                $failedResults = array_filter($results, fn($r) => !$r['success']);
                $_SESSION['warning'] = 'Some backups failed: ' . implode(', ', array_column($failedResults, 'error'));
            }

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to create scheduled backups: ' . $e->getMessage();
        }

        header('Location: ' . route_url('tenant/backups'));
    }

    /**
     * Cleanup old backups (admin only)
     */
    public function cleanupOldBackups(): void
    {
        require_login();
        AuthHelper::requirePermission('backups', 'delete');

        $retentionDays = (int)($_POST['retention_days'] ?? 30);

        $backupModel = new TenantBackupRestore();

        try {
            $deletedCount = $backupModel->cleanupOldBackups($retentionDays);

            $_SESSION['success'] = "Cleanup completed: {$deletedCount} old backups deleted";

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to cleanup old backups: ' . $e->getMessage();
        }

        header('Location: ' . route_url('tenant/backups'));
    }

    // ===== UTILITY METHODS =====

    /**
     * Get current tenant from session or subdomain
     */
    private function getCurrentTenant(): ?array
    {
        // Check if we're in tenant context via middleware
        if (isset($_SESSION['tenant'])) {
            return $_SESSION['tenant'];
        }

        // Try to get tenant from subdomain
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (preg_match('/^([a-z0-9-]+)\.' . preg_quote($_SERVER['SERVER_NAME'] ?? 'localhost', '/') . '$/', $host, $matches)) {
            $slug = $matches[1];
            $tenantModel = new Tenant();
            $tenant = $tenantModel->findBySlug($slug);
            if ($tenant && $tenant['status'] === 'active') {
                $_SESSION['tenant'] = $tenant;
                return $tenant;
            }
        }

        return null;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
