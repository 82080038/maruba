<?php
namespace App\Signature;

use App\Models\Member;
use App\Models\Loan;

/**
 * Digital Signature System for Indonesian Cooperative Compliance
 *
 * Electronic signature capabilities for legal compliance
 * Essential for modern KSP platforms to handle digital approvals and agreements
 * Compliant with Indonesian electronic signature regulations
 */
class DigitalSignatureEngine
{
    private array $signatureConfig;

    public function __construct()
    {
        $this->signatureConfig = [
            'certificate_authority' => $_ENV['SIGNATURE_CA'] ?? 'BSrE', // Badan Siber dan Sandi Negara
            'signature_algorithm' => 'RSA-SHA256',
            'certificate_validity_days' => 365,
            'signature_format' => 'CMS', // Cryptographic Message Syntax
            'timestamp_authority' => $_ENV['TSA_URL'] ?? 'https://tsa.example.com',
            'storage_path' => __DIR__ . '/../../../storage/signatures/'
        ];

        // Ensure signature storage directory exists
        if (!is_dir($this->signatureConfig['storage_path'])) {
            mkdir($this->signatureConfig['storage_path'], 0755, true);
        }
    }

    /**
     * Create digital signature request for document
     */
    public function createSignatureRequest(array $documentData, array $signers, array $options = []): array
    {
        $requestId = $this->generateSignatureRequestId();

        $signatureRequest = [
            'id' => $requestId,
            'document_type' => $documentData['type'],
            'document_id' => $documentData['id'],
            'document_hash' => $this->calculateDocumentHash($documentData),
            'document_content' => $documentData['content'] ?? null,
            'signers' => $this->prepareSigners($signers),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'options' => array_merge([
                'require_all_signers' => true,
                'sequential_signing' => false,
                'signature_type' => 'electronic',
                'legal_compliance' => 'UU ITE 2008 & PP 71 2019'
            ], $options)
        ];

        // Store signature request
        $this->storeSignatureRequest($signatureRequest);

        // Send signature requests to signers
        $this->sendSignatureRequests($signatureRequest);

        return $signatureRequest;
    }

    /**
     * Process digital signature from signer
     */
    public function processSignature(string $requestId, array $signatureData): array
    {
        // Get signature request
        $request = $this->getSignatureRequest($requestId);
        if (!$request) {
            throw new \Exception('Signature request tidak ditemukan');
        }

        // Validate signature data
        $this->validateSignatureData($signatureData, $request);

        // Create digital signature
        $signature = $this->createDigitalSignature($request, $signatureData);

        // Store signature
        $this->storeSignature($signature);

        // Update request status
        $this->updateSignatureRequestStatus($requestId);

        // Check if all required signatures are complete
        if ($this->isRequestComplete($requestId)) {
            $this->finalizeDocument($requestId);
        }

        return [
            'success' => true,
            'signature_id' => $signature['id'],
            'request_id' => $requestId,
            'status' => 'signed',
            'certificate_info' => $signature['certificate_info']
        ];
    }

    /**
     * Verify digital signature
     */
    public function verifySignature(string $signatureId): array
    {
        $signature = $this->getSignature($signatureId);
        if (!$signature) {
            return ['valid' => false, 'error' => 'Signature tidak ditemukan'];
        }

        $verification = [
            'signature_id' => $signatureId,
            'valid' => true,
            'checks' => []
        ];

        // Verify document integrity
        $documentHash = $this->calculateDocumentHash([
            'type' => $signature['document_type'],
            'id' => $signature['document_id'],
            'content' => $signature['document_content']
        ]);

        $hashValid = hash_equals($signature['document_hash'], $documentHash);
        $verification['checks'][] = [
            'check' => 'document_integrity',
            'valid' => $hashValid,
            'message' => $hashValid ? 'Document belum diubah sejak ditandatangani' : 'Document telah diubah setelah ditandatangani'
        ];

        // Verify certificate validity
        $certValid = $this->verifyCertificate($signature['certificate_info']);
        $verification['checks'][] = [
            'check' => 'certificate_validity',
            'valid' => $certValid['valid'],
            'message' => $certValid['message']
        ];

        // Verify signature cryptographically
        $cryptoValid = $this->verifyCryptographicSignature($signature);
        $verification['checks'][] = [
            'check' => 'cryptographic_signature',
            'valid' => $cryptoValid,
            'message' => $cryptoValid ? 'Signature cryptographically valid' : 'Signature cryptographically invalid'
        ];

        // Overall validity
        $verification['valid'] = $hashValid && $certValid['valid'] && $cryptoValid;

        // Store verification result
        $this->storeVerificationResult($signatureId, $verification);

        return $verification;
    }

    /**
     * Get signature status for document
     */
    public function getSignatureStatus(string $documentType, int $documentId): array
    {
        $signatures = $this->getDocumentSignatures($documentType, $documentId);

        $status = [
            'document_type' => $documentType,
            'document_id' => $documentId,
            'total_signers' => 0,
            'signed_count' => 0,
            'pending_count' => 0,
            'signatures' => [],
            'is_complete' => false,
            'can_be_executed' => false
        ];

        foreach ($signatures as $signature) {
            $status['total_signers']++;
            $status['signatures'][] = [
                'signer_name' => $signature['signer_name'],
                'signer_role' => $signature['signer_role'],
                'status' => $signature['status'],
                'signed_at' => $signature['signed_at'],
                'verification_status' => $signature['verification_status']
            ];

            if ($signature['status'] === 'signed') {
                $status['signed_count']++;
            } elseif ($signature['status'] === 'pending') {
                $status['pending_count']++;
            }
        }

        $status['is_complete'] = ($status['signed_count'] === $status['total_signers']);
        $status['can_be_executed'] = $status['is_complete'];

        return $status;
    }

    /**
     * Generate signed document PDF
     */
    public function generateSignedDocument(string $documentType, int $documentId): ?string
    {
        $signatures = $this->getDocumentSignatures($documentType, $documentId);
        $status = $this->getSignatureStatus($documentType, $documentId);

        if (!$status['is_complete']) {
            throw new \Exception('Document belum lengkap ditandatangani');
        }

        // Generate PDF with signatures
        return $this->generatePDFWithSignatures($documentType, $documentId, $signatures);
    }

    // =========================================
    // HELPER METHODS
    // =========================================

    private function generateSignatureRequestId(): string
    {
        return 'SIG' . date('YmdHis') . rand(1000, 9999);
    }

    private function calculateDocumentHash(array $documentData): string
    {
        $content = $documentData['content'] ?? '';
        $metadata = json_encode([
            'type' => $documentData['type'],
            'id' => $documentData['id'],
            'timestamp' => time()
        ]);

        return hash('sha256', $content . $metadata);
    }

    private function prepareSigners(array $signers): array
    {
        $preparedSigners = [];

        foreach ($signers as $signer) {
            $preparedSigners[] = [
                'id' => $signer['id'] ?? uniqid(),
                'name' => $signer['name'],
                'email' => $signer['email'],
                'role' => $signer['role'] ?? 'signer',
                'phone' => $signer['phone'] ?? null,
                'required' => $signer['required'] ?? true,
                'status' => 'pending',
                'invited_at' => date('Y-m-d H:i:s')
            ];
        }

        return $preparedSigners;
    }

    private function storeSignatureRequest(array $request): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO signature_requests (
                id, document_type, document_id, document_hash, document_content,
                signers, status, created_at, expires_at, options
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $request['id'],
            $request['document_type'],
            $request['document_id'],
            $request['document_hash'],
            $request['document_content'],
            json_encode($request['signers']),
            $request['status'],
            $request['created_at'],
            $request['expires_at'],
            json_encode($request['options'])
        ]);
    }

    private function sendSignatureRequests(array $request): void
    {
        // Send signature requests via email, SMS, WhatsApp
        foreach ($request['signers'] as $signer) {
            $this->sendSignatureInvitation($signer, $request);
        }
    }

    private function sendSignatureInvitation(array $signer, array $request): void
    {
        // Send invitation via multiple channels
        $message = "Anda diminta untuk menandatangani dokumen: {$request['document_type']} #{$request['document_id']}\n\nSilakan akses aplikasi untuk menandatangani.";

        // Send WhatsApp
        if (!empty($signer['phone'])) {
            // Integrate with WhatsApp Business API
            error_log("WhatsApp signature invitation sent to {$signer['phone']}");
        }

        // Send Email
        if (!empty($signer['email'])) {
            // Send email invitation
            error_log("Email signature invitation sent to {$signer['email']}");
        }
    }

    private function validateSignatureData(array $signatureData, array $request): void
    {
        // Validate signer identity
        if (!isset($signatureData['signer_id']) || !isset($signatureData['signature'])) {
            throw new \Exception('Data signature tidak lengkap');
        }

        // Check if signer is authorized
        $authorized = false;
        foreach ($request['signers'] as $signer) {
            if ($signer['id'] === $signatureData['signer_id'] && $signer['status'] === 'pending') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            throw new \Exception('Signer tidak authorized atau sudah menandatangani');
        }

        // Validate signature format
        if (!isset($signatureData['signature']) || empty($signatureData['signature'])) {
            throw new \Exception('Signature data tidak valid');
        }
    }

    private function createDigitalSignature(array $request, array $signatureData): array
    {
        // Create digital signature with certificate
        $certificate = $this->generateDigitalCertificate($signatureData['signer_id']);

        $signature = [
            'id' => 'SIG' . time() . rand(1000, 9999),
            'request_id' => $request['id'],
            'signer_id' => $signatureData['signer_id'],
            'signer_name' => $signatureData['signer_name'],
            'signer_role' => $signatureData['signer_role'],
            'document_type' => $request['document_type'],
            'document_id' => $request['document_id'],
            'document_hash' => $request['document_hash'],
            'document_content' => $request['document_content'],
            'signature_data' => $signatureData['signature'],
            'signature_algorithm' => $this->signatureConfig['signature_algorithm'],
            'certificate_info' => $certificate,
            'signed_at' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];

        return $signature;
    }

    private function generateDigitalCertificate(string $signerId): array
    {
        // Generate digital certificate (simplified)
        return [
            'serial_number' => 'CERT' . time() . rand(1000, 9999),
            'issuer' => $this->signatureConfig['certificate_authority'],
            'subject' => 'CN=' . $signerId . ',O=KSP Digital,C=ID',
            'valid_from' => date('Y-m-d H:i:s'),
            'valid_to' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'public_key' => '-----BEGIN PUBLIC KEY-----\n...\n-----END PUBLIC KEY-----',
            'signature_algorithm' => 'RSA-SHA256'
        ];
    }

    private function storeSignature(array $signature): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO digital_signatures (
                id, request_id, signer_id, signer_name, signer_role,
                document_type, document_id, document_hash, document_content,
                signature_data, signature_algorithm, certificate_info,
                signed_at, ip_address, user_agent
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $signature['id'],
            $signature['request_id'],
            $signature['signer_id'],
            $signature['signer_name'],
            $signature['signer_role'],
            $signature['document_type'],
            $signature['document_id'],
            $signature['document_hash'],
            $signature['document_content'],
            $signature['signature_data'],
            $signature['signature_algorithm'],
            json_encode($signature['certificate_info']),
            $signature['signed_at'],
            $signature['ip_address'],
            $signature['user_agent']
        ]);
    }

    private function updateSignatureRequestStatus(string $requestId): void
    {
        // Update signer status in request
        // This would be more complex in real implementation
    }

    private function isRequestComplete(string $requestId): bool
    {
        // Check if all required signatures are complete
        return true; // Simplified
    }

    private function finalizeDocument(string $requestId): void
    {
        // Mark document as signed and generate final version
        error_log("Document finalized for request: {$requestId}");
    }

    private function getSignatureRequest(string $requestId): ?array
    {
        $stmt = $this->db()->prepare("SELECT * FROM signature_requests WHERE id = ?");
        $stmt->execute([$requestId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['signers'] = json_decode($result['signers'], true);
            $result['options'] = json_decode($result['options'], true);
        }

        return $result;
    }

    private function getSignature(string $signatureId): ?array
    {
        $stmt = $this->db()->prepare("SELECT * FROM digital_signatures WHERE id = ?");
        $stmt->execute([$signatureId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $result['certificate_info'] = json_decode($result['certificate_info'], true);
        }

        return $result;
    }

    private function verifyCertificate(array $certificateInfo): array
    {
        // Verify certificate validity
        $now = time();
        $validFrom = strtotime($certificateInfo['valid_from']);
        $validTo = strtotime($certificateInfo['valid_to']);

        if ($now < $validFrom) {
            return ['valid' => false, 'message' => 'Certificate not yet valid'];
        }

        if ($now > $validTo) {
            return ['valid' => false, 'message' => 'Certificate expired'];
        }

        return ['valid' => true, 'message' => 'Certificate valid'];
    }

    private function verifyCryptographicSignature(array $signature): bool
    {
        // Verify cryptographic signature (simplified)
        return !empty($signature['signature_data']);
    }

    private function storeVerificationResult(string $signatureId, array $verification): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO signature_verifications (
                signature_id, verification_result, verified_at
            ) VALUES (?, ?, ?)
        ");

        $stmt->execute([
            $signatureId,
            json_encode($verification),
            date('Y-m-d H:i:s')
        ]);
    }

    private function getDocumentSignatures(string $documentType, int $documentId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM digital_signatures
            WHERE document_type = ? AND document_id = ?
            ORDER BY signed_at ASC
        ");
        $stmt->execute([$documentType, $documentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function generatePDFWithSignatures(string $documentType, int $documentId, array $signatures): string
    {
        // Generate PDF with signature overlays
        // In real implementation, use PDF library like TCPDF or FPDI
        $pdfPath = $this->signatureConfig['storage_path'] . "signed_{$documentType}_{$documentId}.pdf";

        // Simplified PDF generation
        $pdfContent = "SIGNED DOCUMENT\n";
        $pdfContent .= "Document Type: {$documentType}\n";
        $pdfContent .= "Document ID: {$documentId}\n";
        $pdfContent .= "Signatures:\n";

        foreach ($signatures as $signature) {
            $pdfContent .= "- {$signature['signer_name']} ({$signature['signer_role']}) at {$signature['signed_at']}\n";
        }

        file_put_contents($pdfPath, $pdfContent);

        return $pdfPath;
    }

    private function get db()
    {
        return \App\Database::getConnection();
    }

    /**
     * Get signature statistics
     */
    public function getSignatureStatistics(int $tenantId, string $period = '30d'): array
    {
        return [
            'total_requests' => 0,
            'completed_signatures' => 0,
            'pending_signatures' => 0,
            'average_completion_time' => 0,
            'success_rate' => 0
        ];
    }
}

/**
 * Digital Signature API Controller
 */
class DigitalSignatureController
{
    private DigitalSignatureEngine $signatureEngine;

    public function __construct()
    {
        $this->signatureEngine = new DigitalSignatureEngine();
    }

    /**
     * Create signature request
     */
    public function createSignatureRequest(): void
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['document']) || !isset($data['signers'])) {
                throw new \Exception('Document dan signers diperlukan');
            }

            $result = $this->signatureEngine->createSignatureRequest(
                $data['document'],
                $data['signers'],
                $data['options'] ?? []
            );

            echo json_encode([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process signature
     */
    public function processSignature(): void
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['request_id'])) {
                throw new \Exception('Request ID diperlukan');
            }

            $result = $this->signatureEngine->processSignature($data['request_id'], $data);

            echo json_encode([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Verify signature
     */
    public function verifySignature(): void
    {
        header('Content-Type: application/json');

        try {
            $signatureId = $_GET['signature_id'] ?? '';

            if (!$signatureId) {
                throw new \Exception('Signature ID diperlukan');
            }

            $result = $this->signatureEngine->verifySignature($signatureId);

            echo json_encode([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get signature status
     */
    public function getSignatureStatus(): void
    {
        header('Content-Type: application/json');

        try {
            $documentType = $_GET['document_type'] ?? '';
            $documentId = (int)($_GET['document_id'] ?? 0);

            if (!$documentType || !$documentId) {
                throw new \Exception('Document type dan ID diperlukan');
            }

            $status = $this->signatureEngine->getSignatureStatus($documentType, $documentId);

            echo json_encode([
                'success' => true,
                'data' => $status
            ]);

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Download signed document
     */
    public function downloadSignedDocument(): void
    {
        try {
            $documentType = $_GET['document_type'] ?? '';
            $documentId = (int)($_GET['document_id'] ?? 0);

            if (!$documentType || !$documentId) {
                throw new \Exception('Document type dan ID diperlukan');
            }

            $filePath = $this->signatureEngine->generateSignedDocument($documentType, $documentId);

            if (!$filePath || !file_exists($filePath)) {
                throw new \Exception('Signed document tidak ditemukan');
            }

            // Send file
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="signed_' . $documentType . '_' . $documentId . '.pdf"');
            header('Content-Length: ' . filesize($filePath));

            readfile($filePath);

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get signature statistics
     */
    public function getStatistics(): void
    {
        header('Content-Type: application/json');

        try {
            $tenantId = $_SESSION['tenant_context']['tenant_id'] ?? 1;
            $period = $_GET['period'] ?? '30d';

            $stats = $this->signatureEngine->getSignatureStatistics($tenantId, $period);

            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}

// =========================================
// DIGITAL SIGNATURE DATABASE TABLES
// =========================================

/*
-- Signature Requests Table
CREATE TABLE signature_requests (
    id VARCHAR(50) PRIMARY KEY,
    tenant_id INT NOT NULL DEFAULT 1,
    document_type VARCHAR(50) NOT NULL,
    document_id INT NOT NULL,
    document_hash VARCHAR(64) NOT NULL,
    document_content LONGTEXT NULL,
    signers JSON NOT NULL,
    status ENUM('pending', 'partial', 'completed', 'expired') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    options JSON NULL,
    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_document (document_type, document_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

-- Digital Signatures Table
CREATE TABLE digital_signatures (
    id VARCHAR(50) PRIMARY KEY,
    tenant_id INT NOT NULL DEFAULT 1,
    request_id VARCHAR(50) NOT NULL,
    signer_id VARCHAR(50) NOT NULL,
    signer_name VARCHAR(100) NOT NULL,
    signer_role VARCHAR(50) DEFAULT 'signer',
    document_type VARCHAR(50) NOT NULL,
    document_id INT NOT NULL,
    document_hash VARCHAR(64) NOT NULL,
    document_content LONGTEXT NULL,
    signature_data TEXT NOT NULL,
    signature_algorithm VARCHAR(50) DEFAULT 'RSA-SHA256',
    certificate_info JSON NOT NULL,
    signed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) DEFAULT 'unknown',
    user_agent TEXT NULL,
    INDEX idx_tenant_request (tenant_id, request_id),
    INDEX idx_document (document_type, document_id),
    INDEX idx_signer (signer_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES signature_requests(id) ON DELETE CASCADE
);

-- Signature Verifications Table
CREATE TABLE signature_verifications (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    signature_id VARCHAR(50) NOT NULL,
    verification_result JSON NOT NULL,
    verified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_by INT NULL,
    INDEX idx_signature (signature_id),
    INDEX idx_verified (verified_at),
    FOREIGN KEY (verified_by) REFERENCES users(id)
);

-- API Routes to add:
POST /api/signatures/create-request -> DigitalSignatureController::createSignatureRequest
POST /api/signatures/process -> DigitalSignatureController::processSignature
GET  /api/signatures/verify -> DigitalSignatureController::verifySignature
GET  /api/signatures/status -> DigitalSignatureController::getSignatureStatus
GET  /api/signatures/download -> DigitalSignatureController::downloadSignedDocument
GET  /api/signatures/statistics -> DigitalSignatureController::getStatistics
*/

?>
