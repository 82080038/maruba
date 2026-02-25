<?php
namespace App\Models;

use PDO;

class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'username', 'password_hash', 'role_id', 'status'];
    protected array $hidden = ['password_hash'];
    protected array $casts = [
        'role_id' => 'int',
        'created_at' => 'datetime'
    ];

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE username = ?");
        $stmt->execute([$username]);
        $result = $stmt->fetch();

        return $result ? $this->castAttributes($result) : null;
    }

    /**
     * Find user with role information
     */
    public function findWithRole(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT u.*, r.name as role_name, r.permissions
            FROM {$this->table} u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.{$this->primaryKey} = ?
        ");
        $stmt->execute([$id]);
        $result = $stmt->fetch();

        if ($result) {
            $result = $this->castAttributes($result);
            if ($result['permissions']) {
                $result['permissions'] = json_decode($result['permissions'], true);
            }
        }

        return $result;
    }

    /**
     * Get all users with role information
     */
    public function allWithRoles(array $orderBy = ['u.created_at' => 'DESC']): array
    {
        $stmt = $this->db->prepare("
            SELECT u.*, r.name as role_name, r.permissions
            FROM {$this->table} u
            LEFT JOIN roles r ON u.role_id = r.id
            ORDER BY u.created_at DESC
        ");
        $stmt->execute();

        $results = $stmt->fetchAll();
        return array_map(function($user) {
            $user = $this->castAttributes($user);
            if ($user['permissions']) {
                $user['permissions'] = json_decode($user['permissions'], true);
            }
            return $user;
        }, $results);
    }

    /**
     * Create user with password hashing
     */
    public function create(array $data): int
    {
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }

        return parent::create($data);
    }

    /**
     * Update user with password hashing
     */
    public function update(int $id, array $data): bool
    {
        if (isset($data['password'])) {
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }

        return parent::update($id, $data);
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Get users by role
     */
    public function getByRole(int $roleId): array
    {
        return $this->findWhere(['role_id' => $roleId]);
    }

    /**
     * Get active users only
     */
    public function getActiveUsers(): array
    {
        return $this->findWhere(['status' => 'active']);
    }
}
