<?php
namespace App\Models;

class AuditLog extends Model
{
    protected string $table = 'audit_logs';
    protected array $fillable = ['user_id', 'action', 'entity', 'entity_id', 'meta'];
    protected array $casts = [
        'user_id' => 'int',
        'entity_id' => 'int',
        'created_at' => 'datetime'
    ];

    /**
     * Log an action
     */
    public function logAction(int $userId, string $action, string $entity, ?int $entityId = null, array $meta = []): int
    {
        return $this->create([
            'user_id' => $userId,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'meta' => json_encode($meta)
        ]);
    }

    /**
     * Get recent activities
     */
    public function getRecentActivities(int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT a.*, u.name as user_name
            FROM {$this->table} a
            LEFT JOIN users u ON a.user_id = u.id
            ORDER BY a.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);

        $results = $stmt->fetchAll();
        return array_map([$this, 'castAttributes'], $results);
    }

    /**
     * Get activities for specific entity
     */
    public function getEntityActivities(string $entity, int $entityId): array
    {
        return $this->findWhere(
            ['entity' => $entity, 'entity_id' => $entityId],
            ['created_at' => 'DESC']
        );
    }

    /**
     * Get activities by user
     */
    public function getUserActivities(int $userId, int $limit = 50): array
    {
        return $this->findWhere(
            ['user_id' => $userId],
            ['created_at' => 'DESC'],
            $limit
        );
    }

    /**
     * Get activities by date range
     */
    public function getActivitiesByDateRange(string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare("
            SELECT a.*, u.name as user_name
            FROM {$this->table} a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE DATE(a.created_at) BETWEEN ? AND ?
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([$startDate, $endDate]);

        $results = $stmt->fetchAll();
        return array_map([$this, 'castAttributes'], $results);
    }

    /**
     * Get action statistics
     */
    public function getActionStatistics(): array
    {
        $stmt = $this->db->prepare("
            SELECT action, COUNT(*) as count
            FROM {$this->table}
            GROUP BY action
            ORDER BY count DESC
        ");
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
