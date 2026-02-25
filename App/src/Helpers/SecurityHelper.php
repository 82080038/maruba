<?php
namespace App\Helpers;

/**
 * Security Helper Class
 * Provides security-related utility functions
 */

class SecurityHelper
{
    /**
     * Sanitize input string
     */
    public static function sanitize(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate email format
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate phone number (Indonesia format)
     */
    public static function validatePhone(string $phone): bool
    {
        // Remove spaces and dashes
        $phone = preg_replace('/[\s\-]/', '', $phone);
        
        // Indonesian format: +62 or 0, followed by 8-12 digits
        return preg_match('/^(\+62|0)[0-9]{8,12}$/', $phone);
    }
    
    /**
     * Validate NIK (Indonesian ID number)
     */
    public static function validateNIK(string $nik): bool
    {
        // NIK should be 16 digits
        return preg_match('/^[0-9]{16}$/', $nik);
    }
    
    /**
     * Validate amount (positive decimal)
     */
    public static function validateAmount($amount): bool
    {
        return is_numeric($amount) && $amount > 0 && $amount <= 999999999999.99;
    }
    
    /**
     * Generate secure random token
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Hash password securely
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 1
        ]);
    }
    
    /**
     * Verify password
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = self::generateToken();
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCSRFToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Sanitize filename
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Remove dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        // Limit length
        return substr($filename, 0, 255);
    }
    
    /**
     * Validate file upload
     */
    public static function validateFileUpload(array $file, array $allowedTypes = [], int $maxSize = 5242880): array
    {
        $errors = [];
        
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $errors[] = 'Invalid file upload';
            return $errors;
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size exceeds maximum limit';
        }
        
        // Check file type
        if (!empty($allowedTypes)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                $errors[] = 'File type not allowed';
            }
        }
        
        return $errors;
    }
    
    /**
     * Escape SQL identifiers (table names, column names)
     */
    public static function escapeIdentifier(string $identifier): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $identifier);
    }
    
    /**
     * Validate date format
     */
    public static function validateDate(string $date, string $format = 'Y-m-d'): bool
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * Validate numeric input
     */
    public static function validateNumeric($value, $min = null, $max = null): bool
    {
        if (!is_numeric($value)) {
            return false;
        }
        
        $num = (float) $value;
        
        if ($min !== null && $num < $min) {
            return false;
        }
        
        if ($max !== null && $num > $max) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Generate secure password
     */
    public static function generateSecurePassword(int $length = 12): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        return substr(str_shuffle($chars), 0, $length);
    }
    
    /**
     * Check for SQL injection patterns
     */
    public static function detectSQLInjection(string $input): bool
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
    
    /**
     * Check for XSS patterns
     */
    public static function detectXSS(string $input): bool
    {
        $patterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/<iframe[^>]*>.*?<\/iframe>/is',
            '/<object[^>]*>.*?<\/object>/is',
            '/<embed[^>]*>/is',
            '/<link[^>]*>/is',
            '/<meta[^>]*>/is',
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
    
    /**
     * Rate limiting check
     */
    public static function checkRateLimit(string $identifier, int $maxAttempts = 5, int $windowSeconds = 300): bool
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
        
        // Reset if window has passed
        if ($now - $data['first_attempt'] > $windowSeconds) {
            $_SESSION[$cacheKey] = [
                'attempts' => 1,
                'first_attempt' => $now
            ];
            return true;
        }
        
        // Check if limit exceeded
        if ($data['attempts'] >= $maxAttempts) {
            return false;
        }
        
        // Increment attempts
        $_SESSION[$cacheKey]['attempts']++;
        
        return true;
    }
    
    /**
     * Log security event
     */
    public static function logSecurityEvent(string $event, array $context = []): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'context' => $context
        ];
        
        error_log('SECURITY: ' . json_encode($logEntry));
    }
}
