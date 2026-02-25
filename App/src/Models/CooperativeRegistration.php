<?php
namespace App\Models;

class CooperativeRegistration extends Model
{
    protected string $table = 'cooperative_registrations';
    protected array $fillable = [
        'cooperative_name', 'slug', 'legal_type', 'registration_number',
        'description', 'address', 'province', 'city', 'district', 'postal_code',
        'phone', 'email', 'website', 'established_date',
        'chairman_name', 'chairman_phone', 'chairman_email',
        'manager_name', 'manager_phone', 'manager_email',
        'total_members', 'total_assets', 'subscription_plan',
        'documents', 'status', 'rejection_reason',
        'submitted_at', 'reviewed_at', 'approved_at', 'approved_by'
    ];
    protected array $casts = [
        'total_members' => 'int',
        'total_assets' => 'float',
        'established_date' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'approved_by' => 'int',
        'documents' => 'array',
        'created_at' => 'datetime'
    ];

    /**
     * Generate slug from cooperative name
     */
    public function generateSlug(string $name): string
    {
        $slug = strtolower($name);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');

        // Ensure uniqueness
        $originalSlug = $slug;
        $counter = 1;

        while ($this->findWhere(['slug' => $slug])) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if slug exists
     */
    public function slugExists(string $slug, int $excludeId = null): bool
    {
        $conditions = ['slug' => $slug];
        if ($excludeId) {
            // This would need to be modified if we add a findWhere with exclude
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM {$this->table} WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $excludeId]);
            $result = $stmt->fetch();
            return (int)$result['count'] > 0;
        }

        $existing = $this->findWhere($conditions);
        return !empty($existing);
    }

    /**
     * Create cooperative registration
     */
    public function create(array $data): int
    {
        // Generate slug if not provided
        if (!isset($data['slug']) || empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['cooperative_name']);
        }

        // Check slug uniqueness
        if ($this->slugExists($data['slug'])) {
            throw new \Exception('Slug already exists. Please choose a different cooperative name.');
        }

        return parent::create($data);
    }

    /**
     * Submit registration for review
     */
    public function submitForReview(int $registrationId): bool
    {
        $registration = $this->find($registrationId);
        if (!$registration || $registration['status'] !== 'draft') {
            return false;
        }

        return $this->update($registrationId, [
            'status' => 'submitted',
            'submitted_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Review registration
     */
    public function reviewRegistration(int $registrationId, int $reviewedBy): bool
    {
        $registration = $this->find($registrationId);
        if (!$registration || !in_array($registration['status'], ['submitted', 'under_review'])) {
            return false;
        }

        return $this->update($registrationId, [
            'status' => 'under_review',
            'reviewed_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Approve registration and create tenant
     */
    public function approveRegistration(int $registrationId, int $approvedBy): bool
    {
        $registration = $this->find($registrationId);
        if (!$registration || $registration['status'] !== 'under_review') {
            return false;
        }

        // Start transaction
        $this->db->beginTransaction();

        try {
            // Update registration status
            $this->update($registrationId, [
                'status' => 'approved',
                'approved_at' => date('Y-m-d H:i:s'),
                'approved_by' => $approvedBy
            ]);

            // Create tenant record
            $tenantModel = new Tenant();
            $tenantId = $tenantModel->createTenantDatabase([
                'name' => $registration['cooperative_name'],
                'slug' => $registration['slug'],
                'description' => $registration['description'],
                'status' => 'active',
                'subscription_plan' => $registration['subscription_plan'],
                'billing_cycle' => 'monthly',
                'max_members' => 100, // Default limits
                'max_users' => 5
            ]);

            // Create initial billing record
            $billingModel = new TenantBilling();
            $billingModel->create([
                'tenant_id' => $tenantId,
                'amount' => $this->getSubscriptionPrice($registration['subscription_plan']),
                'billing_period_start' => date('Y-m-01'),
                'billing_period_end' => date('Y-m-t'),
                'status' => 'pending',
                'notes' => 'Initial subscription for ' . $registration['cooperative_name']
            ]);

            $this->db->commit();
            return true;

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Reject registration
     */
    public function rejectRegistration(int $registrationId, string $reason, int $approvedBy): bool
    {
        $registration = $this->find($registrationId);
        if (!$registration || $registration['status'] !== 'under_review') {
            return false;
        }

        return $this->update($registrationId, [
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'approved_at' => date('Y-m-d H:i:s'),
            'approved_by' => $approvedBy
        ]);
    }

    /**
     * Get subscription price
     */
    private function getSubscriptionPrice(string $plan): float
    {
        $prices = [
            'starter' => 500000,
            'professional' => 1500000,
            'enterprise' => 3000000
        ];

        return $prices[$plan] ?? 500000;
    }

    /**
     * Get registrations by status
     */
    public function getByStatus(string $status): array
    {
        return $this->findWhere(['status' => $status], ['created_at' => 'DESC']);
    }

    /**
     * Get pending registrations
     */
    public function getPendingRegistrations(): array
    {
        return $this->findWhere(['status' => ['submitted', 'under_review']], ['submitted_at' => 'DESC']);
    }

    /**
     * Get registration statistics
     */
    public function getStatistics(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as total_registrations,
                COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_count,
                COUNT(CASE WHEN status = 'submitted' THEN 1 END) as submitted_count,
                COUNT(CASE WHEN status = 'under_review' THEN 1 END) as under_review_count,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_count,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count
            FROM {$this->table}
        ");
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Validate required documents
     */
    public function validateDocuments(array $documents): array
    {
        $requiredDocs = [
            'akta_pendirian' => 'Akta Pendirian Koperasi',
            'sk_menkumham' => 'SK Menkumham',
            'anggaran_dasar' => 'Anggaran Dasar & Rumah Tangga',
            'ktp_ketua' => 'KTP Ketua',
            'ktp_manajer' => 'KTP Manajer'
        ];

        $missingDocs = [];
        $errors = [];

        foreach ($requiredDocs as $key => $label) {
            if (!isset($documents[$key]) || empty($documents[$key])) {
                $missingDocs[] = $label;
            }
        }

        if (!empty($missingDocs)) {
            $errors[] = 'Dokumen yang belum diupload: ' . implode(', ', $missingDocs);
        }

        return $errors;
    }

    /**
     * Upload document
     */
    public function uploadDocument(int $registrationId, string $documentType, array $file): ?string
    {
        $registration = $this->find($registrationId);
        if (!$registration) {
            throw new \Exception('Registration not found');
        }

        $uploadResult = \App\Helpers\FileUpload::upload($file, 'cooperatives/documents/', [
            'allowed_types' => ['image/jpeg', 'image/png', 'application/pdf'],
            'max_size' => 10 * 1024 * 1024, // 10MB
            'prefix' => $registrationId . '_' . $documentType . '_'
        ]);

        if (!$uploadResult['success']) {
            throw new \Exception('Failed to upload document: ' . $uploadResult['error']);
        }

        // Update documents in registration
        $documents = $registration['documents'] ?? [];
        $documents[$documentType] = $uploadResult['path'];

        $this->update($registrationId, ['documents' => json_encode($documents)]);

        return $uploadResult['path'];
    }
}
