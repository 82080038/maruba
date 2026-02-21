<?php
namespace App\Controllers;

use App\Models\Tenant;
use App\Helpers\AuthHelper;
use App\Helpers\FileUpload;

class CooperativeProfileController
{
    /**
     * Show cooperative profile dashboard
     */
    public function index(): void
    {
        // Get current tenant from session/database
        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied - No tenant found';
            return;
        }

        $tenantModel = new Tenant();
        $tenant = $tenantModel->find($currentTenant['id']);
        $profileCompletion = $tenantModel->getProfileCompletionStatus($currentTenant['id']);
        $activitySummary = $tenantModel->getActivitySummary($currentTenant['id']);

        include view_path('cooperative/profile/index');
    }

    /**
     * Show profile completion status
     */
    public function completionStatus(): void
    {
        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        $tenantModel = new Tenant();
        $status = $tenantModel->getProfileCompletionStatus($currentTenant['id']);

        header('Content-Type: application/json');
        echo json_encode($status);
    }

    /**
     * Update basic cooperative information
     */
    public function updateBasicInfo(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        $data = [
            'description' => trim($_POST['description'] ?? ''),
            'registration_number' => trim($_POST['registration_number'] ?? ''),
            'tax_id' => trim($_POST['tax_id'] ?? ''),
            'business_license' => trim($_POST['business_license'] ?? ''),
            'established_date' => $_POST['established_date'] ?? null
        ];

        $tenantModel = new Tenant();

        try {
            $success = $tenantModel->updateProfile($currentTenant['id'], $data);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Basic information updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update basic information']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Update contact information
     */
    public function updateContactInfo(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        $contactData = [
            'address' => trim($_POST['address'] ?? ''),
            'province' => trim($_POST['province'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'postal_code' => trim($_POST['postal_code'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'website' => trim($_POST['website'] ?? ''),
            'latitude' => (float)($_POST['latitude'] ?? 0),
            'longitude' => (float)($_POST['longitude'] ?? 0)
        ];

        $tenantModel = new Tenant();

        try {
            $success = $tenantModel->updateContactInfo($currentTenant['id'], $contactData);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Contact information updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update contact information']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Update board members
     */
    public function updateBoardMembers(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        $boardMembers = [
            'chairman' => [
                'name' => trim($_POST['chairman_name'] ?? ''),
                'phone' => trim($_POST['chairman_phone'] ?? ''),
                'email' => trim($_POST['chairman_email'] ?? ''),
                'position' => 'Ketua'
            ],
            'manager' => [
                'name' => trim($_POST['manager_name'] ?? ''),
                'phone' => trim($_POST['manager_phone'] ?? ''),
                'email' => trim($_POST['manager_email'] ?? ''),
                'position' => 'Manajer'
            ],
            'secretary' => [
                'name' => trim($_POST['secretary_name'] ?? ''),
                'phone' => trim($_POST['secretary_phone'] ?? ''),
                'email' => trim($_POST['secretary_email'] ?? ''),
                'position' => 'Sekretaris'
            ],
            'treasurer' => [
                'name' => trim($_POST['treasurer_name'] ?? ''),
                'phone' => trim($_POST['treasurer_phone'] ?? ''),
                'email' => trim($_POST['treasurer_email'] ?? ''),
                'position' => 'Bendahara'
            ]
        ];

        $tenantModel = new Tenant();

        try {
            $success = $tenantModel->updateBoardMembers($currentTenant['id'], $boardMembers);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Board members updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update board members']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Update operating hours
     */
    public function updateOperatingHours(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        $operatingHours = [
            'monday' => [
                'open' => $_POST['monday_open'] ?? '08:00',
                'close' => $_POST['monday_close'] ?? '17:00',
                'closed' => isset($_POST['monday_closed'])
            ],
            'tuesday' => [
                'open' => $_POST['tuesday_open'] ?? '08:00',
                'close' => $_POST['tuesday_close'] ?? '17:00',
                'closed' => isset($_POST['tuesday_closed'])
            ],
            'wednesday' => [
                'open' => $_POST['wednesday_open'] ?? '08:00',
                'close' => $_POST['wednesday_close'] ?? '17:00',
                'closed' => isset($_POST['wednesday_closed'])
            ],
            'thursday' => [
                'open' => $_POST['thursday_open'] ?? '08:00',
                'close' => $_POST['thursday_close'] ?? '17:00',
                'closed' => isset($_POST['thursday_closed'])
            ],
            'friday' => [
                'open' => $_POST['friday_open'] ?? '08:00',
                'close' => $_POST['friday_close'] ?? '17:00',
                'closed' => isset($_POST['friday_closed'])
            ],
            'saturday' => [
                'open' => $_POST['saturday_open'] ?? '08:00',
                'close' => $_POST['saturday_close'] ?? '17:00',
                'closed' => isset($_POST['saturday_closed'])
            ],
            'sunday' => [
                'open' => $_POST['sunday_open'] ?? '08:00',
                'close' => $_POST['sunday_close'] ?? '17:00',
                'closed' => isset($_POST['sunday_closed'])
            ]
        ];

        $tenantModel = new Tenant();

        try {
            $success = $tenantModel->update($currentTenant['id'], [
                'operating_hours' => json_encode($operatingHours),
                'last_profile_update' => date('Y-m-d H:i:s')
            ]);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Operating hours updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update operating hours']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Update social media information
     */
    public function updateSocialMedia(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        $socialMedia = [
            'facebook' => trim($_POST['facebook'] ?? ''),
            'instagram' => trim($_POST['instagram'] ?? ''),
            'twitter' => trim($_POST['twitter'] ?? ''),
            'linkedin' => trim($_POST['linkedin'] ?? ''),
            'youtube' => trim($_POST['youtube'] ?? ''),
            'website' => trim($_POST['website'] ?? '')
        ];

        $tenantModel = new Tenant();

        try {
            $success = $tenantModel->update($currentTenant['id'], [
                'social_media' => json_encode($socialMedia),
                'last_profile_update' => date('Y-m-d H:i:s')
            ]);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Social media information updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update social media information']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Upload logo
     */
    public function uploadLogo(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        if (!isset($_FILES['logo'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No logo file provided']);
            return;
        }

        $tenantModel = new Tenant();

        try {
            $logoPath = $tenantModel->uploadLogo($currentTenant['id'], $_FILES['logo']);
            echo json_encode([
                'success' => true,
                'message' => 'Logo uploaded successfully',
                'logo_path' => $logoPath
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Upload legal document
     */
    public function uploadLegalDocument(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        $documentType = $_POST['document_type'] ?? '';
        if (empty($documentType) || !isset($_FILES['document'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Document type and file are required']);
            return;
        }

        $tenantModel = new Tenant();

        try {
            $documentPath = $tenantModel->uploadLegalDocument($currentTenant['id'], $documentType, $_FILES['document']);
            echo json_encode([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'document_path' => $documentPath
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get activity summary
     */
    public function getActivitySummary(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        $tenantModel = new Tenant();
        $summary = $tenantModel->getActivitySummary($currentTenant['id']);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'summary' => $summary]);
    }

    /**
     * Get tenant profile data
     */
    public function getProfileData(): void
    {
        require_login();

        $currentTenant = $this->getCurrentTenant();
        if (!$currentTenant) {
            http_response_code(403);
            echo 'Access denied';
            return;
        }

        $tenantModel = new Tenant();
        $tenant = $tenantModel->find($currentTenant['id']);

        if (!$tenant) {
            http_response_code(404);
            echo json_encode(['error' => 'Tenant not found']);
            return;
        }

        // Decode JSON fields for easier frontend handling
        $tenant['legal_documents'] = json_decode($tenant['legal_documents'] ?? '[]', true);
        $tenant['board_members'] = json_decode($tenant['board_members'] ?? '[]', true);
        $tenant['address_details'] = json_decode($tenant['address_details'] ?? '[]', true);
        $tenant['operating_hours'] = json_decode($tenant['operating_hours'] ?? '[]', true);
        $tenant['social_media'] = json_decode($tenant['social_media'] ?? '[]', true);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'tenant' => $tenant]);
    }

    // ===== UTILITY METHODS =====

    /**
     * Get current tenant from session or subdomain
     */
    private function getCurrentTenant(): ?array
    {
        // Check if we're in a tenant context via middleware
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
}
