<?php
namespace App\OCR;

use App\Models\Member;
use App\Models\Loan;

/**
 * OCR (Optical Character Recognition) & Automated Document Processing
 *
 * Advanced document digitization and data extraction
 * Essential for modern KSP platforms to automate member verification and loan processing
 * Competitive advantage through intelligent document processing
 */
class OCRDocumentProcessor
{
    private array $ocrConfig;
    private array $documentTypes;

    public function __construct()
    {
        $this->ocrConfig = [
            'google_vision_api_key' => $_ENV['GOOGLE_VISION_API_KEY'] ?? '',
            'azure_computer_vision_key' => $_ENV['AZURE_CV_KEY'] ?? '',
            'azure_endpoint' => $_ENV['AZURE_CV_ENDPOINT'] ?? '',
            'tesseract_path' => '/usr/bin/tesseract',
            'max_file_size' => 10 * 1024 * 1024, // 10MB
            'allowed_types' => ['image/jpeg', 'image/png', 'image/gif', 'application/pdf']
        ];

        $this->documentTypes = [
            'ktp' => [
                'name' => 'Kartu Tanda Penduduk',
                'fields' => [
                    'nik' => ['pattern' => '/\\b\\d{16}\\b/', 'required' => true],
                    'name' => ['pattern' => '/NAMA\\s*:\\s*([A-Z\\s]+)/i', 'required' => true],
                    'birth_date' => ['pattern' => '/(\\d{2}-\\d{2}-\\d{4})/', 'required' => true],
                    'birth_place' => ['pattern' => '/LAHIR\\s*:\\s*([^\\n]+)/i', 'required' => false],
                    'gender' => ['pattern' => '/JENIS KELAMIN\\s*:\\s*(LAKI-LAKI|PEREMPUAN)/i', 'required' => true],
                    'address' => ['pattern' => '/ALAMAT\\s*:\\s*([^\\n]+)/i', 'required' => true],
                    'province' => ['pattern' => '/PROVINSI\\s*:\\s*([^\\n]+)/i', 'required' => false]
                ]
            ],
            'kk' => [
                'name' => 'Kartu Keluarga',
                'fields' => [
                    'kk_number' => ['pattern' => '/NO\\.\\s*KK\\s*:\\s*(\\d+)/i', 'required' => true],
                    'family_head' => ['pattern' => '/KEPALA KELUARGA\\s*:\\s*([A-Z\\s]+)/i', 'required' => true],
                    'address' => ['pattern' => '/ALAMAT\\s*:\\s*([^\\n]+)/i', 'required' => true]
                ]
            ],
            'salary_slip' => [
                'name' => 'Slip Gaji',
                'fields' => [
                    'employee_name' => ['pattern' => '/NAMA\\s*:\\s*([A-Z\\s]+)/i', 'required' => true],
                    'employee_id' => ['pattern' => '/NIK\\s*:\\s*([A-Z0-9]+)/i', 'required' => false],
                    'basic_salary' => ['pattern' => '/GAJI POKOK\\s*:\\s*Rp\\s*([0-9,]+)/i', 'required' => true],
                    'total_allowances' => ['pattern' => '/TUNJANGAN\\s*:\\s*Rp\\s*([0-9,]+)/i', 'required' => false],
                    'gross_salary' => ['pattern' => '/GAJI BRUTO\\s*:\\s*Rp\\s*([0-9,]+)/i', 'required' => false],
                    'net_salary' => ['pattern' => '/GAJI BERSIH\\s*:\\s*Rp\\s*([0-9,]+)/i', 'required' => true],
                    'period' => ['pattern' => '/PERIODE\\s*:\\s*([^\\n]+)/i', 'required' => false]
                ]
            ],
            'bank_statement' => [
                'name' => 'Rekening Koran',
                'fields' => [
                    'account_number' => ['pattern' => '/NO\\. REKENING\\s*:\\s*([0-9-]+)/i', 'required' => true],
                    'account_name' => ['pattern' => '/NAMA\\s*:\\s*([A-Z\\s]+)/i', 'required' => true],
                    'bank_name' => ['pattern' => '/BANK\\s*:\\s*([A-Z\\s]+)/i', 'required' => true],
                    'balance' => ['pattern' => '/SALDO AKHIR\\s*:\\s*Rp\\s*([0-9,]+)/i', 'required' => true]
                ]
            ]
        ];
    }

    /**
     * Process document with OCR and extract data
     */
    public function processDocument(string $filePath, string $documentType): array
    {
        $result = [
            'success' => false,
            'document_type' => $documentType,
            'extracted_data' => [],
            'confidence_scores' => [],
            'processing_time' => 0,
            'errors' => []
        ];

        $startTime = microtime(true);

        try {
            // Validate file
            $this->validateFile($filePath);

            // Extract text using OCR
            $extractedText = $this->performOCR($filePath);

            // Parse document data based on type
            if (isset($this->documentTypes[$documentType])) {
                $parsedData = $this->parseDocumentData($extractedText, $documentType);
                $result['extracted_data'] = $parsedData['data'];
                $result['confidence_scores'] = $parsedData['confidence'];
            }

            // Validate extracted data
            $validation = $this->validateExtractedData($result['extracted_data'], $documentType);
            $result['validation'] = $validation;

            $result['success'] = !empty($result['extracted_data']) && $validation['is_valid'];
            $result['processing_time'] = round(microtime(true) - $startTime, 2);

            // Store processing result
            $this->storeProcessingResult($filePath, $result);

        } catch (\Exception $e) {
            $result['errors'][] = $e->getMessage();
            $result['processing_time'] = round(microtime(true) - $startTime, 2);
        }

        return $result;
    }

    /**
     * Perform OCR on document
     */
    private function performOCR(string $filePath): string
    {
        // Try Google Vision API first (highest accuracy)
        if (!empty($this->ocrConfig['google_vision_api_key'])) {
            try {
                return $this->performGoogleVisionOCR($filePath);
            } catch (\Exception $e) {
                // Fallback to next method
            }
        }

        // Try Azure Computer Vision
        if (!empty($this->ocrConfig['azure_computer_vision_key'])) {
            try {
                return $this->performAzureVisionOCR($filePath);
            } catch (\Exception $e) {
                // Fallback to next method
            }
        }

        // Fallback to Tesseract OCR
        return $this->performTesseractOCR($filePath);
    }

    /**
     * Google Vision API OCR
     */
    private function performGoogleVisionOCR(string $filePath): string
    {
        // In real implementation, call Google Vision API
        // For demo, simulate OCR result

        $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($fileExtension === 'pdf') {
            // Handle PDF files
            return $this->extractTextFromPDF($filePath);
        }

        // Simulate OCR for demo
        return "NIK: 1234567890123456\nNAMA: JOHN DOE\nLAHIR: 01-01-1990\nALAMAT: JL. EXAMPLE NO. 123\nJENIS KELAMIN: LAKI-LAKI";
    }

    /**
     * Azure Computer Vision OCR
     */
    private function performAzureVisionOCR(string $filePath): string
    {
        // In real implementation, call Azure Computer Vision API
        // For demo, return sample text
        return "NIK: 1234567890123456\nNAMA: JOHN DOE\nLAHIR: 01-01-1990\nALAMAT: JL. EXAMPLE NO. 123";
    }

    /**
     * Tesseract OCR (fallback)
     */
    private function performTesseractOCR(string $filePath): string
    {
        $outputFile = tempnam(sys_get_temp_dir(), 'ocr_');
        $command = escapeshellcmd("{$this->ocrConfig['tesseract_path']} {$filePath} {$outputFile} -l ind");

        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($outputFile . '.txt')) {
            $text = file_get_contents($outputFile . '.txt');
            unlink($outputFile . '.txt');
            return $text;
        }

        throw new \Exception('Tesseract OCR failed');
    }

    /**
     * Extract text from PDF
     */
    private function extractTextFromPDF(string $filePath): string
    {
        // Use pdftotext or similar tool
        $outputFile = tempnam(sys_get_temp_dir(), 'pdf_');
        $command = escapeshellcmd("pdftotext -layout {$filePath} {$outputFile}.txt");

        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($outputFile . '.txt')) {
            $text = file_get_contents($outputFile . '.txt');
            unlink($outputFile . '.txt');
            return $text;
        }

        throw new \Exception('PDF text extraction failed');
    }

    /**
     * Parse document data based on type
     */
    private function parseDocumentData(string $text, string $documentType): array
    {
        $docConfig = $this->documentTypes[$documentType];
        $extractedData = [];
        $confidenceScores = [];

        foreach ($docConfig['fields'] as $fieldName => $fieldConfig) {
            $pattern = $fieldConfig['pattern'];

            if (preg_match($pattern, $text, $matches)) {
                $value = trim($matches[1] ?? $matches[0]);

                // Clean up the value
                $value = $this->cleanExtractedValue($value, $fieldName);

                $extractedData[$fieldName] = $value;
                $confidenceScores[$fieldName] = $this->calculateConfidence($fieldName, $value, $fieldConfig);
            } elseif ($fieldConfig['required']) {
                // Required field not found
                $confidenceScores[$fieldName] = 0;
            }
        }

        return [
            'data' => $extractedData,
            'confidence' => $confidenceScores
        ];
    }

    /**
     * Clean extracted value
     */
    private function cleanExtractedValue(string $value, string $fieldName): string
    {
        // Remove extra whitespace
        $value = preg_replace('/\s+/', ' ', $value);
        $value = trim($value);

        // Field-specific cleaning
        switch ($fieldName) {
            case 'nik':
                // Ensure only digits
                $value = preg_replace('/\D/', '', $value);
                break;

            case 'name':
                // Capitalize words
                $value = ucwords(strtolower($value));
                break;

            case 'birth_date':
                // Standardize date format
                if (preg_match('/(\d{2})-(\d{2})-(\d{4})/', $value, $matches)) {
                    $value = "{$matches[1]}-{$matches[2]}-{$matches[3]}";
                }
                break;

            case 'basic_salary':
            case 'net_salary':
                // Extract numeric value
                $value = preg_replace('/[^\d]/', '', $value);
                break;
        }

        return $value;
    }

    /**
     * Calculate confidence score for extracted field
     */
    private function calculateConfidence(string $fieldName, string $value, array $fieldConfig): float
    {
        if (empty($value)) {
            return 0.0;
        }

        $confidence = 0.8; // Base confidence

        // Field-specific validation
        switch ($fieldName) {
            case 'nik':
                // NIK should be 16 digits
                if (strlen($value) === 16 && is_numeric($value)) {
                    $confidence = 0.95;
                } else {
                    $confidence = 0.3;
                }
                break;

            case 'birth_date':
                // Valid date format
                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $value)) {
                    $confidence = 0.9;
                } else {
                    $confidence = 0.4;
                }
                break;

            case 'basic_salary':
            case 'net_salary':
                // Reasonable salary range
                $salary = (int)$value;
                if ($salary > 100000 && $salary < 100000000) { // 100k - 100M
                    $confidence = 0.85;
                } else {
                    $confidence = 0.5;
                }
                break;

            case 'name':
                // Name should have reasonable length
                if (strlen($value) >= 3 && strlen($value) <= 50) {
                    $confidence = 0.9;
                } else {
                    $confidence = 0.6;
                }
                break;
        }

        return round($confidence, 2);
    }

    /**
     * Validate extracted data
     */
    private function validateExtractedData(array $data, string $documentType): array
    {
        $docConfig = $this->documentTypes[$documentType];
        $validation = [
            'is_valid' => true,
            'missing_required' => [],
            'validation_errors' => [],
            'warnings' => []
        ];

        // Check required fields
        foreach ($docConfig['fields'] as $fieldName => $fieldConfig) {
            if ($fieldConfig['required'] && empty($data[$fieldName])) {
                $validation['is_valid'] = false;
                $validation['missing_required'][] = $fieldName;
            }
        }

        // Field-specific validation
        foreach ($data as $fieldName => $value) {
            $errors = $this->validateField($fieldName, $value);
            if (!empty($errors)) {
                $validation['validation_errors'] = array_merge($validation['validation_errors'], $errors);
                $validation['is_valid'] = false;
            }
        }

        return $validation;
    }

    /**
     * Validate individual field
     */
    private function validateField(string $fieldName, string $value): array
    {
        $errors = [];

        switch ($fieldName) {
            case 'nik':
                if (strlen($value) !== 16) {
                    $errors[] = 'NIK harus 16 digit';
                }
                if (!is_numeric($value)) {
                    $errors[] = 'NIK harus berupa angka';
                }
                break;

            case 'birth_date':
                if (!preg_match('/^\d{2}-\d{2}-\d{4}$/', $value)) {
                    $errors[] = 'Format tanggal lahir tidak valid (DD-MM-YYYY)';
                } else {
                    $date = DateTime::createFromFormat('d-m-Y', $value);
                    if (!$date || $date > new DateTime()) {
                        $errors[] = 'Tanggal lahir tidak valid';
                    }
                }
                break;

            case 'gender':
                if (!in_array(strtoupper($value), ['LAKI-LAKI', 'PEREMPUAN', 'L', 'P'])) {
                    $errors[] = 'Jenis kelamin tidak valid';
                }
                break;
        }

        return $errors;
    }

    /**
     * Validate uploaded file
     */
    private function validateFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new \Exception('File tidak ditemukan');
        }

        $fileSize = filesize($filePath);
        if ($fileSize > $this->ocrConfig['max_file_size']) {
            throw new \Exception('Ukuran file terlalu besar (max 10MB)');
        }

        $mimeType = mime_content_type($filePath);
        if (!in_array($mimeType, $this->ocrConfig['allowed_types'])) {
            throw new \Exception('Tipe file tidak didukung');
        }
    }

    /**
     * Store processing result
     */
    private function storeProcessingResult(string $filePath, array $result): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO ocr_processing_results (
                tenant_id, file_path, document_type, extracted_data,
                confidence_scores, validation_result, processing_time,
                success, errors, processed_at
            ) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $filePath,
            $result['document_type'],
            json_encode($result['extracted_data']),
            json_encode($result['confidence_scores']),
            json_encode($result['validation'] ?? []),
            $result['processing_time'],
            $result['success'] ? 1 : 0,
            json_encode($result['errors'])
        ]);
    }

    /**
     * Auto-populate member data from KTP
     */
    public function autoPopulateMemberData(int $memberId, array $ktpData): array
    {
        $memberModel = new Member();

        $updateData = [];

        // Map KTP fields to member fields
        $fieldMapping = [
            'nik' => 'nik',
            'name' => 'name',
            'birth_date' => 'birth_date',
            'birth_place' => 'birth_place',
            'gender' => 'gender',
            'address' => 'address'
        ];

        foreach ($fieldMapping as $ktpField => $memberField) {
            if (!empty($ktpData[$ktpField])) {
                $value = $ktpData[$ktpField];

                // Convert gender format
                if ($memberField === 'gender') {
                    $value = $this->convertGenderFormat($value);
                }

                // Convert date format
                if ($memberField === 'birth_date') {
                    $value = $this->convertDateFormat($value);
                }

                $updateData[$memberField] = $value;
            }
        }

        if (!empty($updateData)) {
            $memberModel->update($memberId, $updateData);

            return [
                'success' => true,
                'updated_fields' => array_keys($updateData),
                'data' => $updateData
            ];
        }

        return ['success' => false, 'message' => 'Tidak ada data yang bisa diupdate'];
    }

    /**
     * Get OCR processing statistics
     */
    public function getProcessingStatistics(int $tenantId, string $period = '30d'): array
    {
        // This would query processing statistics
        return [
            'total_processed' => 0,
            'successful_extractions' => 0,
            'accuracy_rate' => 0,
            'processing_time_avg' => 0,
            'popular_document_types' => [],
            'period' => $period
        ];
    }

    // =========================================
    // HELPER METHODS
    // =========================================

    private function get db()
    {
        return \App\Database::getConnection();
    }

    private function convertGenderFormat(string $gender): string
    {
        $gender = strtoupper($gender);
        return $gender === 'LAKI-LAKI' ? 'L' : 'P';
    }

    private function convertDateFormat(string $date): string
    {
        // Convert DD-MM-YYYY to YYYY-MM-DD
        if (preg_match('/(\d{2})-(\d{2})-(\d{4})/', $date, $matches)) {
            return "{$matches[3]}-{$matches[2]}-{$matches[1]}";
        }
        return $date;
    }

    /**
     * Batch process multiple documents
     */
    public function batchProcessDocuments(array $documents): array
    {
        $results = [
            'total' => count($documents),
            'successful' => 0,
            'failed' => 0,
            'results' => []
        ];

        foreach ($documents as $doc) {
            try {
                $result = $this->processDocument($doc['file_path'], $doc['document_type']);
                $results['results'][] = $result;

                if ($result['success']) {
                    $results['successful']++;
                } else {
                    $results['failed']++;
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['results'][] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'document' => $doc
                ];
            }
        }

        return $results;
    }

    /**
     * Get supported document types
     */
    public function getSupportedDocumentTypes(): array
    {
        $types = [];
        foreach ($this->documentTypes as $code => $config) {
            $types[$code] = [
                'name' => $config['name'],
                'fields' => array_keys($config['fields']),
                'required_fields' => array_keys(array_filter($config['fields'], fn($f) => $f['required']))
            ];
        }
        return $types;
    }

    /**
     * Train OCR model with feedback
     */
    public function trainModel(array $feedback): void
    {
        // Store feedback for model improvement
        // In real implementation, this would update ML models
        error_log("OCR training feedback received: " . json_encode($feedback));
    }
}

/**
 * OCR Document Processing API Controller
 */
class OCRDocumentController
{
    private OCRDocumentProcessor $ocrProcessor;

    public function __construct()
    {
        $this->ocrProcessor = new OCRDocumentProcessor();
    }

    /**
     * Process document with OCR
     */
    public function processDocument(): void
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['file_path']) || !isset($data['document_type'])) {
                throw new \Exception('File path dan tipe dokumen diperlukan');
            }

            $result = $this->ocrProcessor->processDocument($data['file_path'], $data['document_type']);

            echo json_encode([
                'success' => $result['success'],
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
     * Auto-populate member data from KTP
     */
    public function autoPopulateMember(): void
    {
        header('Content-Type: application/json');

        try {
            $user = $this->authenticateUser();
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['member_id']) || !isset($data['ktp_data'])) {
                throw new \Exception('Member ID dan data KTP diperlukan');
            }

            $result = $this->ocrProcessor->autoPopulateMemberData($data['member_id'], $data['ktp_data']);

            echo json_encode([
                'success' => $result['success'],
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
     * Get supported document types
     */
    public function getSupportedTypes(): void
    {
        header('Content-Type: application/json');

        try {
            $types = $this->ocrProcessor->getSupportedDocumentTypes();

            echo json_encode([
                'success' => true,
                'data' => $types
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
     * Get OCR processing statistics
     */
    public function getStatistics(): void
    {
        header('Content-Type: application/json');

        try {
            $tenantId = $_SESSION['tenant_context']['tenant_id'] ?? 1;
            $period = $_GET['period'] ?? '30d';

            $stats = $this->ocrProcessor->getProcessingStatistics($tenantId, $period);

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

    /**
     * Submit OCR training feedback
     */
    public function submitFeedback(): void
    {
        header('Content-Type: application/json');

        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['processing_id']) || !isset($data['corrections'])) {
                throw new \Exception('Processing ID dan koreksi diperlukan');
            }

            $this->ocrProcessor->trainModel($data);

            echo json_encode([
                'success' => true,
                'message' => 'Feedback berhasil disimpan untuk training model'
            ]);

        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    // =========================================
    // HELPER METHODS
    // =========================================

    private function authenticateUser(): array
    {
        return [
            'id' => 1,
            'username' => 'test_user'
        ];
    }
}

// =========================================
// OCR PROCESSING DATABASE TABLES
// =========================================

/*
-- OCR Processing Results Table
CREATE TABLE ocr_processing_results (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL DEFAULT 1,
    file_path VARCHAR(500) NOT NULL,
    document_type VARCHAR(50) NOT NULL,
    extracted_data JSON NULL,
    confidence_scores JSON NULL,
    validation_result JSON NULL,
    processing_time DECIMAL(5,2) NOT NULL,
    success BOOLEAN NOT NULL,
    errors JSON NULL,
    processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant_type (tenant_id, document_type),
    INDEX idx_success (success),
    INDEX idx_processed (processed_at),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

-- OCR Training Feedback Table
CREATE TABLE ocr_training_feedback (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL DEFAULT 1,
    processing_result_id BIGINT NOT NULL,
    original_extracted_data JSON NOT NULL,
    corrected_data JSON NOT NULL,
    feedback_type ENUM('correction', 'validation', 'improvement') DEFAULT 'correction',
    submitted_by INT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tenant_result (tenant_id, processing_result_id),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (processing_result_id) REFERENCES ocr_processing_results(id)
);

-- API Routes to add:
POST /api/ocr/process -> OCRDocumentController::processDocument
POST /api/ocr/auto-populate -> OCRDocumentController::autoPopulateMember
GET  /api/ocr/supported-types -> OCRDocumentController::getSupportedTypes
GET  /api/ocr/statistics -> OCRDocumentController::getStatistics
POST /api/ocr/feedback -> OCRDocumentController::submitFeedback
*/

?>
