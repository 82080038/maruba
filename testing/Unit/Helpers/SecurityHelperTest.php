<?php
namespace Unit\Helpers;

use App\Helpers\SecurityHelper;

/**
 * Unit Tests for SecurityHelper
 */

class SecurityHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testSanitize(): void
    {
        $input = '<script>alert("xss")</script>';
        $expected = '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;';
        
        $result = SecurityHelper::sanitize($input);
        $this->assertEquals($expected, $result);
    }
    
    public function testValidateEmail(): void
    {
        // Valid emails
        $this->assertTrue(SecurityHelper::validateEmail('test@example.com'));
        $this->assertTrue(SecurityHelper::validateEmail('user.name@domain.co.id'));
        
        // Invalid emails
        $this->assertFalse(SecurityHelper::validateEmail('invalid-email'));
        $this->assertFalse(SecurityHelper::validateEmail('test@'));
        $this->assertFalse(SecurityHelper::validateEmail('test@domain'));
    }
    
    public function testValidatePhone(): void
    {
        // Valid phone numbers
        $this->assertTrue(SecurityHelper::validatePhone('08123456789'));
        $this->assertTrue(SecurityHelper::validatePhone('+628123456789'));
        $this->assertTrue(SecurityHelper::validatePhone('0628123456789'));
        
        // Invalid phone numbers (should be 9-12 digits for Indonesian format)
        $this->assertFalse(SecurityHelper::validatePhone('123456789')); // Too short
        $this->assertFalse(SecurityHelper::validatePhone('0812345678')); // Too short  
        $this->assertFalse(SecurityHelper::validatePhone('phone')); // Not numeric
        
        // Test valid phone numbers with correct length
        $this->assertTrue(SecurityHelper::validatePhone('081234567890')); // 12 digits
        $this->assertTrue(SecurityHelper::validatePhone('+6281234567890')); // 13 digits with +62
    }
    
    public function testValidateNIK(): void
    {
        // Valid NIK
        $this->assertTrue(SecurityHelper::validateNIK('1234567890123456'));
        
        // Invalid NIK
        $this->assertFalse(SecurityHelper::validateNIK('123456789012345'));
        $this->assertFalse(SecurityHelper::validateNIK('12345678901234567'));
        $this->assertFalse(SecurityHelper::validateNIK('abcdefghijklmnop'));
    }
    
    public function testValidateAmount(): void
    {
        // Valid amounts
        $this->assertTrue(SecurityHelper::validateAmount(1000));
        $this->assertTrue(SecurityHelper::validateAmount('1000.50'));
        $this->assertTrue(SecurityHelper::validateAmount(0.01));
        
        // Invalid amounts
        $this->assertFalse(SecurityHelper::validateAmount(-100));
        $this->assertFalse(SecurityHelper::validateAmount('invalid'));
        $this->assertFalse(SecurityHelper::validateAmount(1000000000000));
    }
    
    public function testGenerateToken(): void
    {
        $token = SecurityHelper::generateToken();
        
        $this->assertNotEmpty($token);
        $this->assertEquals(32, strlen($token));
        $this->assertTrue(preg_match('/^[a-f0-9]{32}$/', $token));
        
        // Test different lengths
        $token16 = SecurityHelper::generateToken(16);
        $this->assertEquals(16, strlen($token16));
        
        // Test token contains only hex characters
        $this->assertTrue(preg_match('/^[a-f0-9]{32}$/', $token));
        $this->assertTrue(preg_match('/^[a-f0-9]{16}$/', $token16));
    }
    
    public function testHashPassword(): void
    {
        $password = 'testpassword';
        $hash = SecurityHelper::hashPassword($password);
        
        $this->assertNotEmpty($hash);
        $this->assertStringContainsString('argon2id', $hash);
        $this->assertTrue(SecurityHelper::verifyPassword($password, $hash));
        $this->assertFalse(SecurityHelper::verifyPassword('wrongpassword', $hash));
    }
    
    public function testCSRFToken(): void
    {
        // Generate token
        $token = SecurityHelper::generateCSRFToken();
        $this->assertNotEmpty($token);
        
        // Verify token
        $this->assertTrue(SecurityHelper::verifyCSRFToken($token));
        $this->assertFalse(SecurityHelper::verifyCSRFToken('wrongtoken'));
    }
    
    public function testSanitizeFilename(): void
    {
        // Valid filenames
        $this->assertEquals('document.pdf', SecurityHelper::sanitizeFilename('document.pdf'));
        $this->assertEquals('image-123.jpg', SecurityHelper::sanitizeFilename('image-123.jpg'));
        
        // Invalid characters removed
        $this->assertEquals('documentscript.pdf', SecurityHelper::sanitizeFilename('document<script>.pdf'));
        $this->assertEquals('filename.txt', SecurityHelper::sanitizeFilename('file/name.txt'));
        
        // Length limit
        $longName = str_repeat('a', 300);
        $this->assertEquals(255, strlen(SecurityHelper::sanitizeFilename($longName)));
    }
    
    public function testValidateDate(): void
    {
        // Validate date
        $this->assertTrue(SecurityHelper::validateDate('2024-02-24'));
        
        // Invalid dates
        $this->assertFalse(SecurityHelper::validateDate('2024-13-01'));
        $this->assertFalse(SecurityHelper::validateDate('invalid-date'));
        $this->assertFalse(SecurityHelper::validateDate('2024-02-30'));
    }
    
    public function testValidateNumeric(): void
    {
        // Valid numbers
        $this->assertTrue(SecurityHelper::validateNumeric(100));
        $this->assertTrue(SecurityHelper::validateNumeric('100.50'));
        $this->assertTrue(SecurityHelper::validateNumeric(0));
        
        // With min/max
        $this->assertTrue(SecurityHelper::validateNumeric(50, 10, 100));
        $this->assertFalse(SecurityHelper::validateNumeric(5, 10, 100));
        $this->assertFalse(SecurityHelper::validateNumeric(150, 10, 100));
        
        // Invalid numbers
        $this->assertFalse(SecurityHelper::validateNumeric('invalid'));
        $this->assertFalse(SecurityHelper::validateNumeric([]));
    }
    
    public function testGenerateSecurePassword(): void
    {
        $password = SecurityHelper::generateSecurePassword();
        
        $this->assertEquals(12, strlen($password));
        $this->assertTrue(preg_match('/^[a-zA-Z0-9!@#$%^&*]+$/', $password));
        
        // Test different lengths
        $password8 = SecurityHelper::generateSecurePassword(8);
        $this->assertEquals(8, strlen($password8));
        
        // Test password contains allowed characters
        $this->assertTrue(preg_match('/^[a-zA-Z0-9!@#$%^&*]+$/', $password));
        $this->assertTrue(preg_match('/^[a-zA-Z0-9!@#$%^&*]+$/', $password8));
    }
    
    public function testDetectSQLInjection(): void
    {
        // SQL injection patterns
        $this->assertTrue(SecurityHelper::detectSQLInjection("'; DROP TABLE users; --"));
        $this->assertTrue(SecurityHelper::detectSQLInjection("' OR '1'='1"));
        $this->assertTrue(SecurityHelper::detectSQLInjection("UNION SELECT * FROM users"));
        
        // Test safe SQL queries (should not be flagged as injection)
        $this->assertFalse(SecurityHelper::detectSQLInjection("SELECT * FROM users WHERE id = 1"));
        $this->assertFalse(SecurityHelper::detectSQLInjection("INSERT INTO users (name) VALUES ('test')"));
        $this->assertFalse(SecurityHelper::detectSQLInjection("UPDATE users SET name = 'test' WHERE id = 1"));
        
        // Safe inputs
        $this->assertFalse(SecurityHelper::detectSQLInjection('John Doe'));
        $this->assertFalse(SecurityHelper::detectSQLInjection('user@example.com'));
        $this->assertFalse(SecurityHelper::detectSQLInjection('Normal text'));
    }
    
    public function testDetectXSS(): void
    {
        // XSS patterns
        $this->assertTrue(SecurityHelper::detectXSS('<script>alert("xss")</script>'));
        $this->assertTrue(SecurityHelper::detectXSS('<iframe src="javascript:alert(1)"></iframe>'));
        $this->assertTrue(SecurityHelper::detectXSS('javascript:alert(1)'));
        $this->assertTrue(SecurityHelper::detectXSS('<img onload="alert(1)">'));
        
        // Safe inputs
        $this->assertFalse(SecurityHelper::detectXSS('Normal text'));
        $this->assertFalse(SecurityHelper::detectXSS('John & Jane'));
        $this->assertFalse(SecurityHelper::detectXSS('Price: $100'));
    }
    
    public function testRateLimit(): void
    {
        $identifier = 'test_user';
        
        // First few attempts should pass
        $this->assertTrue(SecurityHelper::checkRateLimit($identifier));
        $this->assertTrue(SecurityHelper::checkRateLimit($identifier));
        $this->assertTrue(SecurityHelper::checkRateLimit($identifier));
        
        // Exceed limit
        for ($i = 0; $i < 5; $i++) {
            SecurityHelper::checkRateLimit($identifier);
        }
        $this->assertFalse(SecurityHelper::checkRateLimit($identifier));
    }
    
    public function testLogSecurityEvent(): void
    {
        // This test just ensures the method doesn't throw an exception
        SecurityHelper::logSecurityEvent('test_event', ['test' => 'data']);
        $this->assertTrue(true, "Security event should be logged without error");
    }
}
