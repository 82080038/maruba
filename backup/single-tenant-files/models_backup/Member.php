<?php
namespace App\Models;

class Member extends Model
{
    protected string $table = 'members';
    protected array $fillable = ['name', 'nik', 'phone', 'address', 'lat', 'lng', 'status'];
    protected array $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'created_at' => 'datetime'
    ];

    /**
     * Find member by NIK
     */
    public function findByNik(string $nik): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE nik = ?");
        $stmt->execute([$nik]);
        $result = $stmt->fetch();

        return $result ? $this->castAttributes($result) : null;
    }

    /**
     * Search members by name or NIK
     */
    public function search(string $query, int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table}
            WHERE name LIKE ? OR nik LIKE ?
            ORDER BY name ASC
            LIMIT ?
        ");
        $searchTerm = "%$query%";
        $stmt->execute([$searchTerm, $searchTerm, $limit]);

        $results = $stmt->fetchAll();
        return array_map([$this, 'castAttributes'], $results);
    }

    /**
     * Get active members only
     */
    public function getActiveMembers(): array
    {
        return $this->findWhere(['status' => 'active'], ['name' => 'ASC']);
    }

    /**
     * Get member with loan summary
     */
    public function findWithLoanSummary(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT m.*,
                   COUNT(l.id) as total_loans,
                   COALESCE(SUM(CASE WHEN l.status IN ('approved','disbursed') THEN l.amount ELSE 0 END), 0) as outstanding_amount,
                   COALESCE(SUM(CASE WHEN l.status = 'default' THEN 1 ELSE 0 END), 0) as default_loans
            FROM {$this->table} m
            LEFT JOIN loans l ON m.id = l.member_id
            WHERE m.id = ?
            GROUP BY m.id
        ");
        $stmt->execute([$id]);
        $result = $stmt->fetch();

        return $result ? $this->castAttributes($result) : null;
    }

    /**
     * Get members with outstanding loans
     */
    public function getMembersWithOutstandingLoans(): array
    {
        $stmt = $this->db->prepare("
            SELECT m.*,
                   COUNT(l.id) as loan_count,
                   SUM(l.amount) as total_outstanding
            FROM {$this->table} m
            INNER JOIN loans l ON m.id = l.member_id
            WHERE l.status IN ('approved','disbursed')
            GROUP BY m.id
            ORDER BY m.name ASC
        ");
        $stmt->execute();

        $results = $stmt->fetchAll();
        return array_map([$this, 'castAttributes'], $results);
    }

    /**
     * Get member statistics
     */
    public function getStatistics(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_members,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_members,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_members
            FROM {$this->table}
        ");
        $stmt->execute();

        return $stmt->fetch();
    }
}
