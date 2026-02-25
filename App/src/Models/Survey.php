<?php
namespace App\Models;

class Survey extends Model
{
    protected string $table = 'surveys';
    protected array $fillable = [
        'loan_id', 'surveyor_id', 'result', 'score', 'geo_lat', 'geo_lng'
    ];
    protected array $casts = [
        'loan_id' => 'int',
        'surveyor_id' => 'int',
        'score' => 'int',
        'geo_lat' => 'float',
        'geo_lng' => 'float',
        'created_at' => 'datetime'
    ];

    /**
     * Get survey with loan and surveyor details
     */
    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, l.amount as loan_amount, m.name as member_name,
                   u.name as surveyor_name
            FROM {$this->table} s
            JOIN loans l ON s.loan_id = l.id
            JOIN members m ON l.member_id = m.id
            JOIN users u ON s.surveyor_id = u.id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        $result = $stmt->fetch();

        return $result ? $this->castAttributes($result) : null;
    }

    /**
     * Get surveys by surveyor
     */
    public function getBySurveyor(int $surveyorId): array
    {
        return $this->findWhere(['surveyor_id' => $surveyorId], ['created_at' => 'DESC']);
    }

    /**
     * Get pending surveys (loans with survey status)
     */
    public function getPendingSurveys(): array
    {
        $stmt = $this->db->prepare("
            SELECT l.*, m.name as member_name, m.phone, m.address
            FROM loans l
            JOIN members m ON l.member_id = m.id
            WHERE l.status = 'survey'
            AND l.assigned_surveyor_id IS NOT NULL
            ORDER BY l.created_at ASC
        ");
        $stmt->execute();

        $results = $stmt->fetchAll();
        return array_map(function($loan) {
            $loan['amount'] = (float)$loan['amount'];
            $loan['rate'] = (float)$loan['rate'];
            $loan['tenor_months'] = (int)$loan['tenor_months'];
            return $loan;
        }, $results);
    }

    /**
     * Get completed surveys
     */
    public function getCompletedSurveys(): array
    {
        $stmt = $this->db->prepare("
            SELECT s.*, l.amount as loan_amount, m.name as member_name,
                   u.name as surveyor_name
            FROM {$this->table} s
            JOIN loans l ON s.loan_id = l.id
            JOIN members m ON l.member_id = m.id
            JOIN users u ON s.surveyor_id = u.id
            ORDER BY s.created_at DESC
        ");
        $stmt->execute();

        $results = $stmt->fetchAll();
        return array_map([$this, 'castAttributes'], $results);
    }

    /**
     * Submit survey result
     */
    public function submitSurvey(int $loanId, array $surveyData, int $surveyorId): int
    {
        $surveyData['loan_id'] = $loanId;
        $surveyData['surveyor_id'] = $surveyorId;

        $surveyId = $this->create($surveyData);

        // Update loan status to review
        $loanModel = new Loan();
        $loanModel->updateStatus($loanId, 'review', $surveyorId, [
            'survey_completed' => true,
            'survey_score' => $surveyData['score'] ?? null
        ]);

        return $surveyId;
    }

    /**
     * Get survey statistics
     */
    public function getStatistics(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_surveys,
                AVG(score) as avg_score,
                MIN(score) as min_score,
                MAX(score) as max_score,
                COUNT(CASE WHEN score >= 80 THEN 1 END) as high_score_count,
                COUNT(CASE WHEN score >= 60 AND score < 80 THEN 1 END) as medium_score_count,
                COUNT(CASE WHEN score < 60 THEN 1 END) as low_score_count
            FROM {$this->table}
        ");
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Get survey by loan ID
     */
    public function getByLoanId(int $loanId): ?array
    {
        $surveys = $this->findWhere(['loan_id' => $loanId], ['created_at' => 'DESC']);
        return !empty($surveys) ? $surveys[0] : null;
    }
}
