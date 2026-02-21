<?php
namespace App\Models;

class GeneratedDocument extends Model
{
    protected string $table = 'generated_documents';
    protected array $fillable = [
        'template_id', 'reference_id', 'reference_type', 'document_number',
        'file_path', 'status', 'generated_by'
    ];
    protected array $casts = [
        'template_id' => 'int',
        'reference_id' => 'int',
        'generated_by' => 'int',
        'created_at' => 'datetime'
    ];

    /**
     * Generate document number
     */
    public function generateDocumentNumber(string $type, int $year = null): string
    {
        $year = $year ?: date('Y');

        // Get count of documents of this type for the year
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM {$this->table}
            WHERE YEAR(created_at) = ? AND reference_type = ?
        ");
        $stmt->execute([$year, $type]);
        $count = (int)$stmt->fetch()['count'] + 1;

        $prefix = match($type) {
            'skb' => 'SKB',
            'loan_agreement' => 'SPP',
            'somasi' => 'SOM',
            default => 'DOC'
        };

        return $prefix . '/' . str_pad($count, 3, '0', STR_PAD_LEFT) . '/' . $year;
    }

    /**
     * Create document
     */
    public function create(array $data): int
    {
        // Generate document number if not provided
        if (!isset($data['document_number'])) {
            $data['document_number'] = $this->generateDocumentNumber(
                $data['reference_type'],
                date('Y')
            );
        }

        return parent::create($data);
    }

    /**
     * Get documents by reference
     */
    public function getByReference(int $referenceId, string $referenceType): array
    {
        return $this->findWhere([
            'reference_id' => $referenceId,
            'reference_type' => $referenceType
        ], ['created_at' => 'DESC']);
    }

    /**
     * Get documents by type
     */
    public function getByType(string $type): array
    {
        return $this->findWhere(['reference_type' => $type], ['created_at' => 'DESC']);
    }

    /**
     * Update document status
     */
    public function updateStatus(int $documentId, string $status): bool
    {
        return $this->update($documentId, ['status' => $status]);
    }

    /**
     * Mark as signed
     */
    public function markAsSigned(int $documentId): bool
    {
        return $this->updateStatus($documentId, 'signed');
    }

    /**
     * Mark as sent
     */
    public function markAsSent(int $documentId): bool
    {
        return $this->updateStatus($documentId, 'sent');
    }

    /**
     * Get document with template info
     */
    public function findWithTemplate(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT gd.*, dt.name as template_name, dt.type as template_type
            FROM {$this->table} gd
            JOIN document_templates dt ON gd.template_id = dt.id
            WHERE gd.id = ?
        ");
        $stmt->execute([$id]);
        $result = $stmt->fetch();

        return $result ? $this->castAttributes($result) : null;
    }

    /**
     * Get document statistics
     */
    public function getStatistics(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_documents,
                COUNT(CASE WHEN status = 'generated' THEN 1 END) as generated_count,
                COUNT(CASE WHEN status = 'signed' THEN 1 END) as signed_count,
                COUNT(CASE WHEN status = 'sent' THEN 1 END) as sent_count,
                reference_type,
                COUNT(*) as type_count
            FROM {$this->table}
            GROUP BY reference_type
        ");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Generate SKB for loan
     */
    public function generateSKB(int $loanId, int $generatedBy): int
    {
        $loanModel = new \App\Models\Loan();
        $memberModel = new \App\Models\Member();

        $loan = $loanModel->find($loanId);
        $member = $loan ? $memberModel->find($loan['member_id']) : null;

        if (!$loan || !$member) {
            throw new \Exception('Loan or member not found');
        }

        $templateModel = new DocumentTemplate();
        $templates = $templateModel->getByType('skb');

        if (empty($templates)) {
            throw new \Exception('SKB template not found');
        }

        $template = $templates[0]; // Use first available template

        $variables = [
            'document_number' => $this->generateDocumentNumber('skb'),
            'member_name' => $member['name'],
            'member_nik' => $member['nik'],
            'loan_amount' => number_format($loan['amount'], 0, ',', '.'),
            'loan_tenor' => $loan['tenor_months'] . ' bulan',
            'loan_rate' => $loan['rate'] . '% per bulan',
            'current_date' => date('d F Y'),
            'company_name' => 'APLIKASI KSP'
        ];

        $content = $templateModel->renderTemplate($template['id'], $variables);

        // Save to file
        $filename = 'skb_' . $loanId . '_' . time() . '.html';
        $filePath = 'documents/skb/' . $filename;
        $fullPath = __DIR__ . '/../../public/uploads/' . $filePath;

        // Create directory if not exists
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($fullPath, $content);

        return $this->create([
            'template_id' => $template['id'],
            'reference_id' => $loanId,
            'reference_type' => 'skb',
            'file_path' => $filePath,
            'generated_by' => $generatedBy
        ]);
    }

    /**
     * Generate loan agreement
     */
    public function generateLoanAgreement(int $loanId, int $generatedBy): int
    {
        $loanModel = new \App\Models\Loan();
        $memberModel = new \App\Models\Member();

        $loan = $loanModel->find($loanId);
        $member = $loan ? $memberModel->find($loan['member_id']) : null;

        if (!$loan || !$member) {
            throw new \Exception('Loan or member not found');
        }

        $templateModel = new DocumentTemplate();
        $templates = $templateModel->getByType('loan_agreement');

        if (empty($templates)) {
            throw new \Exception('Loan agreement template not found');
        }

        $template = $templates[0];

        $variables = [
            'document_number' => $this->generateDocumentNumber('loan_agreement'),
            'member_name' => $member['name'],
            'member_address' => $member['address'],
            'member_nik' => $member['nik'],
            'loan_amount' => number_format($loan['amount'], 0, ',', '.'),
            'loan_tenor' => $loan['tenor_months'] . ' bulan',
            'loan_rate' => $loan['rate'] . '% per bulan',
            'monthly_payment' => number_format($loan['amount'] / $loan['tenor_months'], 0, ',', '.'),
            'current_date' => date('d F Y'),
            'company_name' => 'APLIKASI KSP',
            'company_address' => 'Jl. Contoh No. 123, Kelurahan Contoh'
        ];

        $content = $templateModel->renderTemplate($template['id'], $variables);

        $filename = 'loan_agreement_' . $loanId . '_' . time() . '.html';
        $filePath = 'documents/loan_agreements/' . $filename;
        $fullPath = __DIR__ . '/../../public/uploads/' . $filePath;

        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($fullPath, $content);

        return $this->create([
            'template_id' => $template['id'],
            'reference_id' => $loanId,
            'reference_type' => 'loan_agreement',
            'file_path' => $filePath,
            'generated_by' => $generatedBy
        ]);
    }

    /**
     * Generate somasi (warning letter)
     */
    public function generateSomasi(int $repaymentId, int $generatedBy): int
    {
        $repaymentModel = new \App\Models\Repayment();
        $loanModel = new \App\Models\Loan();
        $memberModel = new \App\Models\Member();

        $repayment = $repaymentModel->find($repaymentId);
        $loan = $repayment ? $loanModel->find($repayment['loan_id']) : null;
        $member = $loan ? $memberModel->find($loan['member_id']) : null;

        if (!$repayment || !$loan || !$member) {
            throw new \Exception('Repayment, loan, or member not found');
        }

        $templateModel = new DocumentTemplate();
        $templates = $templateModel->getByType('somasi');

        if (empty($templates)) {
            throw new \Exception('Somasi template not found');
        }

        $template = $templates[0];

        $overdueAmount = $repayment['amount_due'] - $repayment['amount_paid'];
        $dueDate = date('d F Y', strtotime($repayment['due_date']));

        $variables = [
            'document_number' => $this->generateDocumentNumber('somasi'),
            'member_name' => $member['name'],
            'member_address' => $member['address'],
            'overdue_amount' => number_format($overdueAmount, 0, ',', '.'),
            'due_date' => $dueDate,
            'loan_id' => $loan['id'],
            'current_date' => date('d F Y'),
            'warning_level' => '1', // Could be calculated based on days overdue
            'company_name' => 'APLIKASI KSP'
        ];

        $content = $templateModel->renderTemplate($template['id'], $variables);

        $filename = 'somasi_' . $repaymentId . '_' . time() . '.html';
        $filePath = 'documents/somasi/' . $filename;
        $fullPath = __DIR__ . '/../../public/uploads/' . $filePath;

        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($fullPath, $content);

        return $this->create([
            'template_id' => $template['id'],
            'reference_id' => $repaymentId,
            'reference_type' => 'somasi',
            'file_path' => $filePath,
            'generated_by' => $generatedBy
        ]);
    }
}
