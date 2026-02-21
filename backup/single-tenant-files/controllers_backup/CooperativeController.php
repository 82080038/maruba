<?php
namespace App\Controllers;

use App\Models\CooperativeOnboarding;

class CooperativeController
{
    // ===== PUBLIC REGISTRATION =====

    /**
     * Public cooperative registration form
     */
    public function register(): void
    {
        include view_path('cooperative/register');
    }

    /**
     * Save cooperative registration draft
     */
    public function saveDraft(): void
    {
        $data = $this->getRegistrationDataFromPost();
        $data['status'] = 'draft';

        $registrationModel = new CooperativeRegistration();

        try {
            $registrationId = $registrationModel->create($data);
            $_SESSION['registration_draft_id'] = $registrationId;

            echo json_encode([
                'success' => true,
                'registration_id' => $registrationId,
                'message' => 'Draft saved successfully'
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Submit cooperative registration
     */
    public function submitRegistration(): void
    {
        $data = $this->getRegistrationDataFromPost();
        $registrationId = (int)($_POST['registration_id'] ?? 0);

        $registrationModel = new CooperativeRegistration();

        try {
            if ($registrationId) {
                // Update existing draft
                $registrationModel->update($registrationId, $data);
            } else {
                // Create new registration
                $registrationId = $registrationModel->create($data);
            }

            // Validate documents
            $registration = $registrationModel->find($registrationId);
            $documentErrors = $registrationModel->validateDocuments($registration['documents'] ?? []);

            if (!empty($documentErrors)) {
                echo json_encode([
                    'success' => false,
                    'errors' => $documentErrors
                ]);
                return;
            }

            // Submit for review
            $registrationModel->submitForReview($registrationId);

            // Send notification to admin
            \App\Helpers\Notification::send(
                'email',
                ['email' => 'admin@' . APP_NAME . '.id', 'name' => 'Admin'],
                'Pendaftaran Koperasi Baru',
                "Koperasi '{$data['cooperative_name']}' telah mengajukan pendaftaran dan menunggu verifikasi."
            );

            unset($_SESSION['registration_draft_id']);

            echo json_encode([
                'success' => true,
                'message' => 'Registration submitted successfully. We will review your application within 3-5 business days.'
            ]);

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Upload document for registration
     */
    public function uploadDocument(): void
    {
        $registrationId = (int)($_POST['registration_id'] ?? 0);
        $documentType = $_POST['document_type'] ?? '';

        if (!$registrationId || empty($documentType)) {
            http_response_code(400);
            echo json_encode(['error' => 'Registration ID and document type required']);
            return;
        }

        if (!isset($_FILES['document'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No file uploaded']);
            return;
        }

        $registrationModel = new CooperativeRegistration();

        try {
            $filePath = $registrationModel->uploadDocument($registrationId, $documentType, $_FILES['document']);

            echo json_encode([
                'success' => true,
                'file_path' => $filePath,
                'message' => 'Document uploaded successfully'
            ]);

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Check registration status
     */
    public function checkStatus(): void
    {
        $registrationId = (int)($_GET['id'] ?? 0);

        if (!$registrationId) {
            http_response_code(400);
            echo json_encode(['error' => 'Registration ID required']);
            return;
        }

        $registrationModel = new CooperativeRegistration();
        $registration = $registrationModel->find($registrationId);

        if (!$registration) {
            http_response_code(404);
            echo json_encode(['error' => 'Registration not found']);
            return;
        }

        echo json_encode([
            'success' => true,
            'status' => $registration['status'],
            'submitted_at' => $registration['submitted_at'],
            'reviewed_at' => $registration['reviewed_at'],
            'approved_at' => $registration['approved_at'],
            'rejection_reason' => $registration['rejection_reason']
        ]);
    }

    // ===== ADMIN MANAGEMENT =====

    /**
     * List all cooperative registrations
     */
    public function index(): void
    {
        require_login();
        AuthHelper::requirePermission('cooperatives', 'view');

        $status = $_GET['status'] ?? 'all';
        $registrationModel = new CooperativeRegistration();

        if ($status === 'all') {
            $registrations = $registrationModel->all(['created_at' => 'DESC']);
        } else {
            $registrations = $registrationModel->getByStatus($status);
        }

        include view_path('cooperative/index');
    }

    /**
     * View registration details
     */
    public function show(): void
    {
        require_login();
        AuthHelper::requirePermission('cooperatives', 'view');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(404);
            echo 'Registration not found';
            return;
        }

        $registrationModel = new CooperativeRegistration();
        $registration = $registrationModel->find($id);

        if (!$registration) {
            http_response_code(404);
            echo 'Registration not found';
            return;
        }

        include view_path('cooperative/show');
    }

    /**
     * Review registration
     */
    public function review(): void
    {
        require_login();
        AuthHelper::requirePermission('cooperatives', 'review');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo 'Registration ID required';
            return;
        }

        $registrationModel = new CooperativeRegistration();
        $user = current_user();

        $success = $registrationModel->reviewRegistration($id, $user['id']);

        if ($success) {
            $_SESSION['success'] = 'Registration marked for review.';
        } else {
            $_SESSION['error'] = 'Failed to update registration status.';
        }

        header('Location: ' . route_url('cooperative/show') . '?id=' . $id);
    }

    /**
     * Approve registration
     */
    public function approve(): void
    {
        require_login();
        AuthHelper::requirePermission('cooperatives', 'approve');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo 'Registration ID required';
            return;
        }

        $registrationModel = new CooperativeRegistration();
        $user = current_user();

        try {
            $success = $registrationModel->approveRegistration($id, $user['id']);

            if ($success) {
                // Get the created tenant ID
                $tenantModel = new \App\Models\Tenant();
                $tenant = $tenantModel->findBySlug($registration['slug']);

                if ($tenant) {
                    // Create onboarding record
                    $onboardingModel = new CooperativeOnboarding();
                    $onboardingId = $onboardingModel->createOnboarding($id, $tenant['id'], $registration);

                    // Execute onboarding process
                    $onboardingModel->executeOnboarding($onboardingId);

                    $_SESSION['success'] = 'Cooperative registration approved, tenant created, and onboarding completed successfully.';

                    // Get onboarding details for notification
                    $onboarding = $onboardingModel->find($onboardingId);

                    // Send approval notification with credentials
                    if ($registration && !empty($registration['email'])) {
                        $welcomeMessage = "
                        Selamat datang di " . APP_NAME . "!

                        Koperasi {$registration['cooperative_name']} telah berhasil didaftarkan dan diaktifkan.

                        Akses Sistem:
                        - URL: https://{$registration['slug']}." . $_SERVER['HTTP_HOST'] . "
                        - Username Admin: {$onboarding['admin_username']}
                        - Password: ChangeMe123! (ubah segera setelah login pertama)

                        Panduan Penggunaan:
                        1. Login dengan kredensial di atas
                        2. Ubah password default untuk keamanan
                        3. Lengkapi profil koperasi
                        4. Mulai menambahkan anggota dan produk

                        Dukungan teknis: support@" . APP_NAME . ".id

                        Selamat menggunakan sistem!
                        ";

                        \App\Helpers\Notification::send(
                            'email',
                            [
                                'email' => $registration['email'],
                                'name' => $registration['chairman_name']
                            ],
                            'Selamat Datang di ' . APP_NAME,
                            $welcomeMessage
                        );
                    }
                } else {
                    $_SESSION['success'] = 'Cooperative registration approved but tenant creation failed.';
                }
            } else {
                $_SESSION['error'] = 'Failed to approve registration.';
            }

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Error approving registration: ' . $e->getMessage();
        }

        header('Location: ' . route_url('cooperative/show') . '?id=' . $id);
    }

    /**
     * Reject registration
     */
    public function reject(): void
    {
        require_login();
        AuthHelper::requirePermission('cooperatives', 'approve');

        $id = (int)($_POST['registration_id'] ?? 0);
        $reason = trim($_POST['rejection_reason'] ?? '');

        if (!$id) {
            $_SESSION['error'] = 'Registration ID required.';
            header('Location: ' . route_url('cooperatives'));
            return;
        }

        if (empty($reason)) {
            $_SESSION['error'] = 'Rejection reason is required.';
            header('Location: ' . route_url('cooperative/show') . '?id=' . $id);
            return;
        }

        $registrationModel = new CooperativeRegistration();
        $user = current_user();

        $success = $registrationModel->rejectRegistration($id, $reason, $user['id']);

        if ($success) {
            $_SESSION['success'] = 'Registration rejected.';

            // Get registration details for notification
            $registration = $registrationModel->find($id);

            // Send rejection notification
            if ($registration && !empty($registration['email'])) {
                \App\Helpers\Notification::send(
                    'email',
                    [
                        'email' => $registration['email'],
                        'name' => $registration['chairman_name']
                    ],
                    'Pendaftaran Koperasi Ditolak',
                    "Mohon maaf, pendaftaran koperasi '{$registration['cooperative_name']}' tidak dapat disetujui.\n\n" .
                    "Alasan: {$reason}\n\n" .
                    "Silakan perbaiki dokumen dan ajukan kembali."
                );
            }
        } else {
            $_SESSION['error'] = 'Failed to reject registration.';
        }

        header('Location: ' . route_url('cooperative/show') . '?id=' . $id);
    }

    // ===== UTILITY METHODS =====

    /**
     * Get registration data from POST
     */
    private function getRegistrationDataFromPost(): array
    {
        return [
            'cooperative_name' => trim($_POST['cooperative_name'] ?? ''),
            'legal_type' => $_POST['legal_type'] ?? '',
            'registration_number' => trim($_POST['registration_number'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'province' => trim($_POST['province'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'postal_code' => trim($_POST['postal_code'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'website' => trim($_POST['website'] ?? ''),
            'established_date' => $_POST['established_date'] ?? null,
            'chairman_name' => trim($_POST['chairman_name'] ?? ''),
            'chairman_phone' => trim($_POST['chairman_phone'] ?? ''),
            'chairman_email' => trim($_POST['chairman_email'] ?? ''),
            'manager_name' => trim($_POST['manager_name'] ?? ''),
            'manager_phone' => trim($_POST['manager_phone'] ?? ''),
            'manager_email' => trim($_POST['manager_email'] ?? ''),
            'total_members' => (int)($_POST['total_members'] ?? 0),
            'total_assets' => (float)($_POST['total_assets'] ?? 0),
            'subscription_plan' => $_POST['subscription_plan'] ?? 'starter'
        ];
    }

    /**
     * Get registration statistics
     */
    public function getStatsApi(): void
    {
        require_login();

        $registrationModel = new CooperativeRegistration();
        $stats = $registrationModel->getStatistics();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'stats' => $stats]);
    }
}
