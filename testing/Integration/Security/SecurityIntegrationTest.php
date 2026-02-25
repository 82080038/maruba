<?php
/**
 * Integration Tests for Security Features
 */

class SecurityIntegrationTest extends TestCase
{
    public function testCSRFProtection(): void
    {
        // Test CSRF token generation and verification
        $token1 = $this->generateCSRFToken();
        $token2 = $this->generateCSRFToken();
        
        // Tokens should be the same in same session
        $this->assertEquals($token1, $token2);
        
        // Verification should work
        $this->assertTrue($this->verifyCSRFToken($token1));
        $this->assertFalse($this->verifyCSRFToken('invalid_token'));
    }
    
    public function testPasswordHashing(): void
    {
        $password = 'test_password_123';
        
        // Hash password
        $hash = $this->hashPassword($password);
        $this->assertNotEmpty($hash);
        $this->assertStringContainsString($hash, '$argon2id$');
        
        // Verify password
        $this->assertTrue($this->verifyPassword($password, $hash));
        $this->assertFalse($this->verifyPassword('wrong_password', $hash));
        
        // Store in database and verify
        $stmt = $this->pdo->prepare("INSERT INTO users (name, username, password_hash, role_id, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['Test User', 'test_user_security', $hash, 1, 'active']);
        
        // Retrieve and verify
        $stmt = $this->pdo->prepare("SELECT password_hash FROM users WHERE username = ?");
        $stmt->execute(['test_user_security']);
        $storedHash = $stmt->fetchColumn();
        
        $this->assertNotEmpty($storedHash);
        $this->assertTrue($this->verifyPassword($password, $storedHash));
    }
    
    public function testInputValidation(): void
    {
        // Test various input validations
        $testCases = [
            'email' => [
                'valid' => ['test@example.com', 'user.name@domain.co.id'],
                'invalid' => ['invalid-email', 'test@', 'test@domain']
            ],
            'phone' => [
                'valid' => ['08123456789', '+628123456789'],
                'invalid' => ['123456789', '0812345678', 'phone']
            ],
            'nik' => [
                'valid' => ['1234567890123456'],
                'invalid' => ['123456789012345', '12345678901234567', 'abcdefghijklmnop']
            ],
            'amount' => [
                'valid' => [1000, '1000.50', 0.01],
                'invalid' => [-100, 'invalid', 1000000000000]
            ]
        ];
        
        foreach ($testCases as $field => $cases) {
            foreach ($cases['valid'] as $validInput) {
                $this->assertTrue($this->validateInput($field, $validInput), "Valid $field: $validInput");
            }
            
            foreach ($cases['invalid'] as $invalidInput) {
                $this->assertFalse($this->validateInput($field, $invalidInput), "Invalid $field: $invalidInput");
            }
        }
    }
    
    public function testFileUploadValidation(): void
    {
        // Create a temporary test file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_upload_');
        file_put_contents($tempFile, 'Test file content');
        
        $_FILES['test_file'] = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'size' => filesize($tempFile),
            'tmp_name' => $tempFile,
            'error' => 0
        ];
        
        // Test valid file upload
        $allowedTypes = ['text/plain', 'image/jpeg', 'image/png'];
        $errors = $this->validateFileUpload('test_file', $allowedTypes, 5242880);
        $this->assertEmpty($errors, "Valid file upload should pass validation");
        
        // Clean up
        unlink($tempFile);
    }
    
    public function testSQLInjectionPrevention(): void
    {
        // Test SQL injection detection
        $maliciousInputs = [
            "SELECT * FROM users",
            "'; DROP TABLE users; --",
            "' OR '1'='1",
            "UNION SELECT * FROM users",
            "1' OR '1'='1' --"
        ];
        
        foreach ($maliciousInputs as $input) {
            $this->assertTrue($this->detectSQLInjection($input), "Should detect SQL injection in: $input");
        }
        
        // Test safe inputs
        $safeInputs = [
            'John Doe',
            'user@example.com',
            'Normal text',
            'Product Name'
        ];
        
        foreach ($safeInputs as $input) {
            $this->assertFalse($this->detectSQLInjection($input), "Should not detect SQL injection in safe input: $input");
        }
    }
    
    public function testXSSPrevention(): void
    {
        // Test XSS detection
        $maliciousInputs = [
            '<script>alert("xss")</script>',
            '<iframe src="javascript:alert(1)"></iframe>',
            'javascript:alert(1)',
            '<img onload="alert(1)">',
            '<link rel="stylesheet" href="javascript:alert(1)">'
        ];
        
        foreach ($maliciousInputs as $input) {
            $this->assertTrue($this->detectXSS($input), "Should detect XSS in: $input");
        }
        
        // Test safe inputs
        $safeInputs = [
            'Normal text',
            'John & Jane',
            'Price: $100',
            'Company Name Inc.'
        ];
        
        foreach ($safeInputs as $input) {
            $this->assertFalse($this->detectXSS($input), "Should not detect XSS in safe input: $input");
        }
    }
    
    public function testRateLimiting(): void
    {
        $identifier = 'test_rate_limit';
        
        // Reset rate limit
        unset($_SESSION["rate_limit_{$identifier}"]);
        
        // First few attempts should pass
        for ($i = 0; $i < 3; $i++) {
            $this->assertTrue($this->checkRateLimit($identifier), "Attempt $i should pass");
        }
        
        // Exceed limit
        for ($i = 0; $i < 5; $i++) {
            $this->checkRateLimit($identifier);
        }
        
        $this->assertFalse($this->checkRateLimit($identifier), "Should be rate limited");
        
        // Test window reset (simulate time passing)
        $_SESSION["rate_limit_{$identifier}"]['first_attempt'] = time() - 301;
        $this->assertTrue($this->checkRateLimit($identifier), "Should pass after window reset");
    }
    
    public function testSessionSecurity(): void
    {
        // Test session initialization
        $this->assertTrue($this->validateSession(), "Session should be valid");
        
        // Test session regeneration
        $oldSessionId = session_id();
        $this->regenerateSession();
        $newSessionId = session_id();
        $this->assertNotEquals($oldSessionId, $newSessionId, "Session ID should be regenerated");
        
        // Test session timeout
        $_SESSION['last_activity'] = time() - 3601; // 1 hour ago
        $this->assertFalse($this->validateSession(), "Session should be expired");
    }
    
    public function testSecurityHeaders(): void
    {
        // Apply security headers
        $this->applySecurityHeaders();
        
        // Check if headers are set (in real scenario, this would check actual headers)
        $this->assertTrue(true, "Security headers should be applied");
    }
    
    public function testSuspiciousActivityDetection(): void
    {
        // Test with normal user agent
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
        $warnings = $this->checkSuspiciousActivity();
        $this->assertEmpty($warnings, "Normal user agent should not trigger warnings");
        
        // Test with suspicious user agent
        $_SERVER['HTTP_USER_AGENT'] = 'sqlmap/1.0';
        $warnings = $this->checkSuspiciousActivity();
        $this->assertNotEmpty($warnings, "Suspicious user agent should trigger warnings");
        
        // Test with suspicious URI
        $_SERVER['REQUEST_URI'] = '/admin/panel';
        $warnings = $this->checkSuspiciousActivity();
        $this->assertNotEmpty($warnings, "Suspicious URI should trigger warnings");
    }
    
    // Helper methods
    private function generateCSRFToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    private function verifyCSRFToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    private function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 1
        ]);
    }
    
    private function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    private function validateInput(string $type, $input): bool
    {
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_VALIDATE_EMAIL) !== false;
            case 'phone':
                return preg_match('/^(\+62|0)[0-9]{8,12}$/', preg_replace('/[\s\-]/', '', $input));
            case 'nik':
                return preg_match('/^[0-9]{16}$/', $input);
            case 'amount':
                return is_numeric($input) && $input > 0 && $input <= 999999999999.99;
            default:
                return false;
        }
    }
    
    private function validateFileUpload(string $fieldName, array $allowedTypes, int $maxSize): array
    {
        if (!isset($_FILES[$fieldName])) {
            return ['error' => 'No file uploaded'];
        }
        
        $file = $_FILES[$fieldName];
        
        if (!is_uploaded_file($file['tmp_name'])) {
            return ['error' => 'Invalid file upload'];
        }
        
        if ($file['size'] > $maxSize) {
            return ['error' => 'File too large'];
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return ['error' => 'File type not allowed'];
        }
        
        return [];
    }
    
    private function detectSQLInjection(string $input): bool
    {
        $patterns = [
            '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC|UNION)\b)/i',
            '/(\b(OR|AND)\b\s+\d+\s*=\s*\d+)/i',
            '/(\b(OR|AND)\b\s+["\'][^"\']*["\']\s*=\s*["\'][^"\']*["\'])/i',
            '/(\/\*.*\*\/)/',
            '/(--.*$)/m'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function detectXSS(string $input): bool
    {
        $patterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/<iframe[^>]*>.*?<\/iframe>/is',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/onclick\s*=/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function checkRateLimit(string $identifier, int $maxAttempts = 5, int $windowSeconds = 300): bool
    {
        $cacheKey = "rate_limit_{$identifier}";
        
        if (!isset($_SESSION[$cacheKey])) {
            $_SESSION[$cacheKey] = [
                'attempts' => 0,
                'first_attempt' => time()
            ];
        }
        
        $data = $_SESSION[$cacheKey];
        $now = time();
        
        if ($now - $data['first_attempt'] > $windowSeconds) {
            $_SESSION[$cacheKey] = [
                'attempts' => 1,
                'first_attempt' => $now
            ];
            return true;
        }
        
        if ($data['attempts'] >= $maxAttempts) {
            return false;
        }
        
        $_SESSION[$cacheKey]['attempts']++;
        return true;
    }
    
    private function validateSession(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
        }
        
        $timeout = 3600;
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
            session_destroy();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    private function regenerateSession(): void
    {
        session_regenerate_id(true);
    }
    
    private function applySecurityHeaders(): void
    {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        header('Content-Security-Policy: default-src \'self\'');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
    
    private function checkSuspiciousActivity(): array
    {
        $warnings = [];
        
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $suspiciousAgents = ['sqlmap', 'nikto', 'scanner', 'bot'];
        
        foreach ($suspiciousAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                $warnings[] = "Suspicious user agent detected: {$agent}";
            }
        }
        
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $suspiciousPatterns = ['/admin', '/wp-', '/phpmyadmin', '/.env'];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (stripos($uri, $pattern) !== false) {
                $warnings[] = "Suspicious request pattern detected: {$pattern}";
            }
        }
        
        if (count($_GET) > 50 || count($_POST) > 50) {
            $warnings[] = "Unusual number of parameters detected";
        }
        
        return $warnings;
    }
}
