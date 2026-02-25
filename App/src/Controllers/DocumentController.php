<?php
namespace App\Controllers;

use App\Models\DocumentTemplate;
use App\Models\GeneratedDocument;
use App\Models\Loan;
use App\Models\Repayment;
use App\Helpers\AuthHelper;

class DocumentController
{
    public function index(): void
    {
        require_login();
        AuthHelper::requirePermission('documents', 'view');

        $generatedModel = new GeneratedDocument();
        $documents = $generatedModel->findWhere([], ['created_at' => 'DESC'], 50);

        // Add template and reference info
        foreach ($documents as &$doc) {
            $templateModel = new DocumentTemplate();
            $template = $templateModel->find($doc['template_id']);
            $doc['template_name'] = $template ? $template['name'] : 'Unknown';

            // Add reference info based on type
            $doc['reference_info'] = $this->getReferenceInfo($doc['reference_type'], $doc['reference_id']);
        }

        include view_path('documents/index');
    }

    public function templates(): void
    {
        require_login();
        AuthHelper::requirePermission('documents', 'view');

        $templateModel = new DocumentTemplate();
        $templates = $templateModel->getActiveTemplates();

        include view_path('documents/templates');
    }

    public function createTemplate(): void
    {
        require_login();
        AuthHelper::requirePermission('documents', 'create');

        include view_path('documents/create_template');
    }

    public function storeTemplate(): void
    {
        require_login();
        AuthHelper::requirePermission('documents', 'create');
        verify_csrf();

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'type' => $_POST['type'] ?? '',
            'template_content' => trim($_POST['template_content'] ?? ''),
            'variables' => json_decode($_POST['variables'] ?? '[]', true),
            'is_active' => isset($_POST['is_active']),
            'created_by' => current_user()['id']
        ];

        if (empty($data['name']) || empty($data['type']) || empty($data['template_content'])) {
            $_SESSION['error'] = 'Nama, tipe, dan konten template wajib diisi.';
            header('Location: ' . route_url('documents/create-template'));
            return;
        }

        $templateModel = new DocumentTemplate();

        try {
            $templateId = $templateModel->create($data);
            $_SESSION['success'] = 'Template dokumen berhasil dibuat.';
            header('Location: ' . route_url('documents/templates'));
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal membuat template: ' . $e->getMessage();
            header('Location: ' . route_url('documents/create-template'));
        }
    }

    public function showTemplate(): void
    {
        require_login();
        AuthHelper::requirePermission('documents', 'view');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(404);
            echo 'Template not found';
            return;
        }

        $templateModel = new DocumentTemplate();
        $template = $templateModel->find($id);

        if (!$template) {
            http_response_code(404);
            echo 'Template not found';
            return;
        }

        // Generate preview
        $preview = $templateModel->getPreview($id);

        include view_path('documents/show_template');
    }

    public function editTemplate(): void
    {
        require_login();
        AuthHelper::requirePermission('documents', 'edit');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(404);
            echo 'Template not found';
            return;
        }

        $templateModel = new DocumentTemplate();
        $template = $templateModel->find($id);

        if (!$template) {
            http_response_code(404);
            echo 'Template not found';
            return;
        }

        include view_path('documents/edit_template');
    }

    public function updateTemplate(): void
    {
        require_login();
        AuthHelper::requirePermission('documents', 'edit');
        verify_csrf();

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo 'Template ID required';
            return;
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'type' => $_POST['type'] ?? '',
            'template_content' => trim($_POST['template_content'] ?? ''),
            'variables' => json_decode($_POST['variables'] ?? '[]', true),
            'is_active' => isset($_POST['is_active'])
        ];

        if (empty($data['name']) || empty($data['type']) || empty($data['template_content'])) {
            $_SESSION['error'] = 'Nama, tipe, dan konten template wajib diisi.';
            header('Location: ' . route_url('documents/edit-template') . '?id=' . $id);
            return;
        }

        $templateModel = new DocumentTemplate();

        try {
            $success = $templateModel->update($id, $data);

            if ($success) {
                $_SESSION['success'] = 'Template dokumen berhasil diperbarui.';
                header('Location: ' . route_url('documents/templates'));
            } else {
                $_SESSION['error'] = 'Gagal memperbarui template.';
                header('Location: ' . route_url('documents/edit-template') . '?id=' . $id);
            }
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal memperbarui template: ' . $e->getMessage();
            header('Location: ' . route_url('documents/edit-template') . '?id=' . $id);
        }
    }

    public function generateDocument(): void
    {
        require_login();
        AuthHelper::requirePermission('documents', 'create');

        $type = $_GET['type'] ?? '';
        $referenceId = (int)($_GET['reference_id'] ?? 0);

        if (empty($type) || !$referenceId) {
            http_response_code(400);
            echo 'Document type and reference ID required';
            return;
        }

        $generatedModel = new GeneratedDocument();
        $user = current_user();

        try {
            switch ($type) {
                case 'skb':
                    $documentId = $generatedModel->generateSKB($referenceId, $user['id']);
                    break;
                case 'loan_agreement':
                    $documentId = $generatedModel->generateLoanAgreement($referenceId, $user['id']);
                    break;
                case 'somasi':
                    $documentId = $generatedModel->generateSomasi($referenceId, $user['id']);
                    break;
                default:
                    http_response_code(400);
                    echo 'Unknown document type';
                    return;
            }

            $_SESSION['success'] = 'Dokumen berhasil dibuat.';
            header('Location: ' . route_url('documents/show') . '?id=' . $documentId);
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gagal membuat dokumen: ' . $e->getMessage();
            header('Location: ' . route_url('documents'));
        }
    }

    public function show(): void
    {
        require_login();
        AuthHelper::requirePermission('documents', 'view');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(404);
            echo 'Document not found';
            return;
        }

        $generatedModel = new GeneratedDocument();
        $document = $generatedModel->findWithTemplate($id);

        if (!$document) {
            http_response_code(404);
            echo 'Document not found';
            return;
        }

        // Load document content
        $filePath = __DIR__ . '/../../public/uploads/' . $document['file_path'];
        $content = file_exists($filePath) ? file_get_contents($filePath) : 'Document file not found';

        include view_path('documents/show');
    }

    public function download(): void
    {
        require_login();
        AuthHelper::requirePermission('documents', 'view');

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            http_response_code(404);
            echo 'Document not found';
            return;
        }

        $generatedModel = new GeneratedDocument();
        $document = $generatedModel->find($id);

        if (!$document) {
            http_response_code(404);
            echo 'Document not found';
            return;
        }

        $filePath = __DIR__ . '/../../public/uploads/' . $document['file_path'];

        if (!file_exists($filePath)) {
            http_response_code(404);
            echo 'Document file not found';
            return;
        }

        // Set headers for download
        header('Content-Type: text/html');
        header('Content-Disposition: attachment; filename="' . basename($document['file_path']) . '"');
        header('Content-Length: ' . filesize($filePath));

        readfile($filePath);
    }

    public function updateStatus(): void
    {
        require_login();
        AuthHelper::requirePermission('documents', 'edit');

        $id = (int)($_GET['id'] ?? 0);
        $status = $_GET['status'] ?? '';

        if (!$id || !in_array($status, ['generated', 'signed', 'sent'])) {
            http_response_code(400);
            echo 'Invalid parameters';
            return;
        }

        $generatedModel = new GeneratedDocument();

        try {
            $success = $generatedModel->updateStatus($id, $status);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update status']);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // ===== API ENDPOINTS =====
    public function getTemplatesApi(): void
    {
        require_login();

        $type = $_GET['type'] ?? null;

        $templateModel = new DocumentTemplate();

        if ($type) {
            $templates = $templateModel->getByType($type);
        } else {
            $templates = $templateModel->getActiveTemplates();
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'templates' => $templates]);
    }

    public function getDocumentsApi(): void
    {
        require_login();

        $referenceType = $_GET['reference_type'] ?? null;
        $referenceId = (int)($_GET['reference_id'] ?? 0);

        $generatedModel = new GeneratedDocument();

        if ($referenceType && $referenceId) {
            $documents = $generatedModel->getByReference($referenceId, $referenceType);
        } elseif ($referenceType) {
            $documents = $generatedModel->getByType($referenceType);
        } else {
            $documents = $generatedModel->findWhere([], ['created_at' => 'DESC'], 50);
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'documents' => $documents]);
    }

    public function previewTemplateApi(): void
    {
        require_login();

        $templateId = (int)($_GET['template_id'] ?? 0);
        if (!$templateId) {
            http_response_code(400);
            echo json_encode(['error' => 'Template ID required']);
            return;
        }

        $templateModel = new DocumentTemplate();

        try {
            $preview = $templateModel->getPreview($templateId);
            echo json_encode(['success' => true, 'preview' => $preview]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // ===== UTILITY METHODS =====
    private function getReferenceInfo(string $type, int $id): string
    {
        switch ($type) {
            case 'skb':
                $loanModel = new Loan();
                $loan = $loanModel->find($id);
                return $loan ? 'Pinjaman #' . $loan['id'] : 'Unknown loan';

            case 'loan_agreement':
                $loanModel = new Loan();
                $loan = $loanModel->find($id);
                return $loan ? 'Pinjaman #' . $loan['id'] : 'Unknown loan';

            case 'somasi':
                $repaymentModel = new Repayment();
                $repayment = $repaymentModel->find($id);
                return $repayment ? 'Pembayaran #' . $repayment['id'] : 'Unknown repayment';

            default:
                return 'Reference #' . $id;
        }
    }
}
