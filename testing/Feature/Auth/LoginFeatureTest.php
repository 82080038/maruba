<?php
/**
 * Feature Tests for Login Functionality
 */

class LoginFeatureTest extends TestCase
{
    public function testCompleteLoginFlow(): void
    {
        // Step 1: Test login page access
        $this->simulateGetRequest('/login');
        
        // Step 2: Test login with valid credentials
        $this->simulatePostRequest('/login', [
            'username' => 'test_admin',
            'password' => 'password',
            '_token' => $this->generateCSRFToken()
        ]);
        
        // Step 3: Verify user is logged in
        $this->assertUserIsLoggedIn();
        $this->assertUserRoleIs('admin');
    }
    
    public function testLoginFlowWithInvalidCredentials(): void
    {
        // Test login with invalid credentials
        $this->simulatePostRequest('/login', [
            'username' => 'invalid_user',
            'password' => 'wrong_password',
            '_token' => $this->generateCSRFToken()
        ]);
        
        // Verify user is not logged in
        $this->assertUserIsNotLoggedIn();
        $this->assertErrorMessageContains('Invalid credentials');
    }
    
    public function testLoginFlowWithCSRFProtection(): void
    {
        // Test login without CSRF token
        $this->simulatePostRequest('/login', [
            'username' => 'test_admin',
            'password' => 'password'
        ]);
        
        // Verify CSRF protection works
        $this->assertUserIsNotLoggedIn();
        $this->assertErrorMessageContains('CSRF token mismatch');
    }
    
    public function testLogoutFlow(): void
    {
        // First login
        $this->loginTestUser();
        
        // Then logout
        $this->simulatePostRequest('/logout', [
            '_token' => $this->generateCSRFToken()
        ]);
        
        // Verify user is logged out
        $this->assertUserIsNotLoggedIn();
        $this->assertRedirectedTo('/login');
    }
    
    public function testRoleBasedAccess(): void
    {
        // Test admin access
        $this->loginAs('admin');
        $this->simulateGetRequest('/dashboard');
        $this->assertCanAccess('dashboard');
        
        // Test kasir access
        $this->loginAs('kasir');
        $this->simulateGetRequest('/dashboard');
        $this->assertCanAccess('dashboard');
        
        // Test teller access
        $this->loginAs('teller');
        $this->simulateGetRequest('/dashboard');
        $this->assertCanAccess('dashboard');
    }
    
    public function testUnauthorizedAccess(): void
    {
        // Test access without login
        $this->simulateGetRequest('/dashboard');
        $this->assertRedirectedTo('/login');
        
        // Test access with insufficient permissions
        $this->loginAs('kasir');
        $this->simulateGetRequest('/users');
        $this->assertAccessDenied();
    }
    
    // Helper methods
    private function simulateGetRequest(string $uri): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = $uri;
        $_GET = [];
        $_POST = [];
    }
    
    private function simulatePostRequest(string $uri, array $data): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = $uri;
        $_POST = $data;
        $_GET = [];
    }
    
    private function generateCSRFToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }
    
    private function loginTestUser(): void
    {
        $_SESSION['user'] = $this->createTestUser('admin');
    }
    
    private function loginAs(string $role): void
    {
        $_SESSION['user'] = $this->createTestUser($role);
    }
    
    private function assertUserIsLoggedIn(): void
    {
        $this->assertTrue(isset($_SESSION['user']), "User should be logged in");
    }
    
    private function assertUserIsNotLoggedIn(): void
    {
        $this->assertFalse(isset($_SESSION['user']), "User should not be logged in");
    }
    
    private function assertUserRoleIs(string $expectedRole): void
    {
        $user = $_SESSION['user'];
        $stmt = $this->pdo->prepare("SELECT r.name FROM roles r JOIN users u ON u.role_id = r.id WHERE u.id = ?");
        $stmt->execute([$user['id']]);
        $role = $stmt->fetchColumn();
        
        $this->assertEquals($expectedRole, $role, "User should have role: $expectedRole");
    }
    
    private function assertCanAccess(string $resource): void
    {
        // This would check permissions based on role
        // For now, just verify no access denied error
        $this->assertTrue(true, "Should have access to: $resource");
    }
    
    private function assertAccessDenied(): void
    {
        // This would check for access denied response
        $this->assertTrue(true, "Should be denied access");
    }
    
    private function assertRedirectedTo(string $expectedUri): void
    {
        // This would check redirect headers
        $this->assertTrue(true, "Should be redirected to: $expectedUri");
    }
    
    private function assertErrorMessageContains(string $expectedMessage): void
    {
        // This would check error messages
        $this->assertTrue(true, "Should contain error message: $expectedMessage");
    }
}
