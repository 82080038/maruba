<?php
namespace App\Models;

use App\Database;
use PDO;

abstract class Model
{
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $casts = [];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Find record by ID
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();

        return $result ? $this->castAttributes($result) : null;
    }

    /**
     * Find records by conditions
     */
    public function findWhere(array $conditions, array $orderBy = [], int $limit = null, int $offset = null): array
    {
        $where = [];
        $params = [];

        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $where[] = "$field IN (" . str_repeat('?,', count($value) - 1) . "?)";
                $params = array_merge($params, $value);
            } else {
                $where[] = "$field = ?";
                $params[] = $value;
            }
        }

        $sql = "SELECT * FROM {$this->table}";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        if (!empty($orderBy)) {
            $orderParts = [];
            foreach ($orderBy as $field => $direction) {
                $orderParts[] = "$field $direction";
            }
            $sql .= " ORDER BY " . implode(', ', $orderParts);
        }

        if ($limit !== null) {
            $sql .= " LIMIT $limit";
        }

        if ($offset !== null) {
            $sql .= " OFFSET $offset";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $results = $stmt->fetchAll();
        return array_map([$this, 'castAttributes'], $results);
    }

    /**
     * Get all records
     */
    public function all(array $orderBy = []): array
    {
        return $this->findWhere([], $orderBy);
    }

    /**
     * Create new record
     */
    public function create(array $data): int
    {
        $data = $this->filterFillable($data);
        $data = $this->prepareAttributes($data);

        $fields = array_keys($data);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';

        $sql = "INSERT INTO {$this->table} (" . implode(',', $fields) . ") VALUES ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));

        return (int)$this->db->lastInsertId();
    }

    /**
     * Update record
     */
    public function update(int $id, array $data): bool
    {
        $data = $this->filterFillable($data);
        $data = $this->prepareAttributes($data);

        if (empty($data)) {
            return true;
        }

        $fields = array_keys($data);
        $set = implode(' = ?, ', $fields) . ' = ?';
        $params = array_values($data);
        $params[] = $id;

        $sql = "UPDATE {$this->table} SET $set WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Delete record
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Count records
     */
    public function count(array $conditions = []): int
    {
        $where = [];
        $params = [];

        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $where[] = "$field IN (" . str_repeat('?,', count($value) - 1) . "?)";
                $params = array_merge($params, $value);
            } else {
                $where[] = "$field = ?";
                $params[] = $value;
            }
        }

        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetch()['count'];
    }

    /**
     * Filter only fillable attributes
     */
    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * Prepare attributes before saving
     */
    protected function prepareAttributes(array $data): array
    {
        return $data;
    }

    /**
     * Cast attributes to proper types
     */
    protected function castAttributes(array $attributes): array
    {
        foreach ($this->casts as $field => $type) {
            if (!isset($attributes[$field])) {
                continue;
            }

            switch ($type) {
                case 'int':
                    $attributes[$field] = (int)$attributes[$field];
                    break;
                case 'float':
                    $attributes[$field] = (float)$attributes[$field];
                    break;
                case 'bool':
                    $attributes[$field] = (bool)$attributes[$field];
                    break;
                case 'json':
                    $attributes[$field] = json_decode($attributes[$field], true);
                    break;
                case 'datetime':
                    $attributes[$field] = $attributes[$field] ? new \DateTime($attributes[$field]) : null;
                    break;
            }
        }

        // Hide sensitive fields
        foreach ($this->hidden as $field) {
            unset($attributes[$field]);
        }

        return $attributes;
    }

    /**
     * Paginate results
     */
    public function paginate(int $page = 1, int $perPage = 15, array $conditions = [], array $orderBy = []): array
    {
        $offset = ($page - 1) * $perPage;
        $total = $this->count($conditions);
        $items = $this->findWhere($conditions, $orderBy, $perPage, $offset);

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_more' => $page < ceil($total / $perPage),
            ]
        ];
    }
}
