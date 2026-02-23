<?php
namespace App\Middleware;

use App\Helpers\SecurityHelper;

/**
 * Security Middleware
 * Handles security checks and validations
 */

class SecurityMiddleware
{
    /**
     * Apply security headers
     */
    public static function applySecurityHeaders(): void
    {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Force HTTPS
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self';");
        
        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Permissions policy
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }
    
    /**
     * Validate request method
     */
    public static function validateRequestMethod(array $allowedMethods): bool
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        return in_array($method, $allowedMethods);
    }
    
    /**
     * Validate input parameters
     */
    public static function validateInput(array $data, array $rules): array
    {
        $errors = [];
        $sanitized = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            // Check if field is required
            if (isset($rule['required']) && $rule['required'] && ($value === null || $value === '')) {
                $errors[$field] = "Field {$field} is required";
                continue;
            }
            
            // Skip validation if field is empty and not required
            if ($value === null || $value === '') {
                continue;
            }
            
            // Type validation
            if (isset($rule['type'])) {
                switch ($rule['type']) {
                    case 'string':
                        if (!is_string($value)) {
                            $errors[$field] = "Field {$field} must be a string";
                        } else {
                            $sanitized[$field] = SecurityHelper::sanitize($value);
                        }
                        break;
                        
                    case 'email':
                        if (!SecurityHelper::validateEmail($value)) {
                            $errors[$field] = "Field {$field} must be a valid email";
                        } else {
                            $sanitized[$field] = strtolower(trim($value));
                        }
                        break;
                        
                    case 'phone':
                        if (!SecurityHelper::validatePhone($value)) {
                            $errors[$field] = "Field {$field} must be a valid phone number";
                        } else {
                            $sanitized[$field] = preg_replace('/[\s\-]/', '', $value);
                        }
                        break;
                        
                    case 'nik':
                        if (!SecurityHelper::validateNIK($value)) {
                            $errors[$field] = "Field {$field} must be a valid NIK (16 digits)";
                        } else {
                            $sanitized[$field] = $value;
                        }
                        break;
                        
                    case 'amount':
                        if (!SecurityHelper::validateAmount($value)) {
                            $errors[$field] = "Field {$field} must be a valid positive amount";
                        } else {
                            $sanitized[$field] = (float) $value;
                        }
                        break;
                        
                    case 'date':
                        $format = $rule['format'] ?? 'Y-m-d';
                        if (!SecurityHelper::validateDate($value, $format)) {
                            $errors[$field] = "Field {$field} must be a valid date ({$format})";
                        } else {
                            $sanitized[$field] = $value;
                        }
                        break;
                        
                    case 'numeric':
                        $min = $rule['min'] ?? null;
                        $max = $rule['max'] ?? null;
                        if (!SecurityHelper::validateNumeric($value, $min, $max)) {
                            $errors[$field] = "Field {$field} must be a valid number";
                        } else {
                            $sanitized[$field] = (float) $value;
                        }
                        break;
                        
                    case 'integer':
                        if (!is_numeric($value) || (int) $value != $value) {
                            $errors[$field] = "Field {$field} must be an integer";
                        } else {
                            $sanitized[$field] = (int) $value;
                        }
                        break;
                        
                    case 'boolean':
                        $sanitized[$field] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                        break;
                }
            }
            
            // Length validation
            if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                $errors[$field] = "Field {$field} must be at least {$rule['min_length']} characters";
            }
            
            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                $errors[$field] = "Field {$field} must not exceed {$rule['max_length']} characters";
            }
            
            // Pattern validation
            if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                $errors[$field] = "Field {$field} format is invalid";
            }
            
            // Custom validation
            if (isset($rule['custom']) && is_callable($rule['custom'])) {
                $customError = $rule['custom']($value);
                if ($customError) {
                    $errors[$field] = $customError;
                }
            }
            
            // XSS detection
            if (SecurityHelper::detectXSS($value)) {
                $errors[$field] = "Field {$field} contains potentially dangerous content";
            }
            
            // SQL injection detection
            if (SecurityHelper::detectSQLInjection($value)) {
                $errors[$field] = "Field {$field} contains potentially dangerous content";
            }
        }
        
        return [
            'errors' => $errors,
            'sanitized' => $sanitized
        ];
    }
    
    /**
     * Validate file upload
     */
    public static function validateFileUpload(string $fieldName, array $allowedTypes = [], int $maxSize = 5242880): array
    {
        if (!isset($_FILES[$fieldName])) {
            return ['error' => 'No file uploaded'];
        }
        
        $file = $_FILES[$fieldName];
        $errors = SecurityHelper::validateFileUpload($file, $allowedTypes, $maxSize);
        
        if (!empty($errors)) {
            return ['error' => implode(', ', $errors)];
        }
        
        return ['success' => true, 'file' => $file];
    }
    
    /**
     * Check rate limiting
     */
    public static function checkRateLimit(string $identifier, int $maxAttempts = 5, int $windowSeconds = 300): bool
    {
        return SecurityHelper::checkRateLimit($identifier, $maxAttempts, $windowSeconds);
    }
    
    /**
     * Log security event
     */
    public static function logSecurityEvent(string $event, array $context = []): void
    {
        SecurityHelper::logSecurityEvent($event, $context);
    }
    
    /**
     * Check for suspicious activity
     */
    public static function checkSuspiciousActivity(): array
    {
        $warnings = [];
        
        // Check for unusual user agent
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $suspiciousAgents = ['sqlmap', 'nikto', 'scanner', 'bot'];
        
        foreach ($suspiciousAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                $warnings[] = "Suspicious user agent detected: {$agent}";
            }
        }
        
        // Check for unusual request patterns
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $suspiciousPatterns = ['/admin', '/wp-', '/phpmyadmin', '/.env'];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (stripos($uri, $pattern) !== false) {
                $warnings[] = "Suspicious request pattern detected: {$pattern}";
            }
        }
        
        // Check for too many parameters
        if (count($_GET) > 50 || count($_POST) > 50) {
            $warnings[] = "Unusual number of parameters detected";
        }
        
        return $warnings;
    }
    
    /**
     * Sanitize URL parameters
     */
    public static function sanitizeURLParams(): void
    {
        // Sanitize GET parameters
        foreach ($_GET as $key => $value) {
            if (is_string($value)) {
                $_GET[$key] = SecurityHelper::sanitize($value);
            }
        }
        
        // Sanitize POST parameters
        foreach ($_POST as $key => $value) {
            if (is_string($value)) {
                $_POST[$key] = SecurityHelper::sanitize($value);
            }
        }
    }
    
    /**
     * Validate session security
     */
    public static function validateSession(): bool
    {
        // Check if session is active
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        
        // Check for session fixation
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
        }
        
        // Check session timeout
        $timeout = 3600; // 1 hour
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
            session_destroy();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * Apply input validation to common fields
     */
    public static function applyCommonValidation(): array
    {
        $errors = [];
        
        // Validate common GET parameters
        $getRules = [
            'id' => ['type' => 'integer', 'min' => 1],
            'page' => ['type' => 'integer', 'min' => 1],
            'limit' => ['type' => 'integer', 'min' => 1, 'max' => 100],
            'search' => ['type' => 'string', 'max_length' => 255],
            'sort' => ['type' => 'string', 'max_length' => 50],
            'order' => ['type' => 'string', 'pattern' => '/^(asc|desc)$/i']
        ];
        
        $result = self::validateInput($_GET, $getRules);
        if (!empty($result['errors'])) {
            $errors = array_merge($errors, $result['errors']);
        }
        
        // Validate common POST parameters
        $postRules = [
            'username' => ['type' => 'string', 'min_length' => 3, 'max_length' => 50, 'pattern' => '/^[a-zA-Z0-9_]+$/'],
            'email' => ['type' => 'email'],
            'password' => ['type' => 'string', 'min_length' => 8, 'max_length' => 255],
            'phone' => ['type' => 'phone'],
            'nik' => ['type' => 'nik'],
            'amount' => ['type' => 'amount'],
            'name' => ['type' => 'string', 'min_length' => 2, 'max_length' => 255],
            'address' => ['type' => 'string', 'max_length' => 1000]
        ];
        
        $result = self::validateInput($_POST, $postRules);
        if (!empty($result['errors'])) {
            $errors = array_merge($errors, $result['errors']);
        }
        
        return $errors;
    }
}
