<?php
/**
 * Unit Tests for Database Class
 */

use App\Database;

class DatabaseTest extends \PHPUnit\Framework\TestCase
{
    public function testDatabaseConnection(): void
    {
        // Test database connection
        $pdo = Database::getConnection();
        $this->assertNotNull($pdo, "Database connection should not be null");
        $this->assertInstanceOf(PDO::class, $pdo, "Should return PDO instance");
    }
    
    public function testDatabaseSingleton(): void
    {
        // Test singleton pattern
        $pdo1 = Database::getConnection();
        $pdo2 = Database::getConnection();
        
        $this->assertSame($pdo1, $pdo2, "Database should return same instance (singleton)");
    }
    
    public function testDatabaseQuery(): void
    {
        // Test basic query execution
        $pdo = Database::getConnection();
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        
        $this->assertArrayHasKey('count', $result, "Query result should have 'count' key");
        $this->assertGreaterThan(0, $result['count'], "Should have test users in database");
    }
    
    public function testDatabasePreparedStatement(): void
    {
        // Test prepared statement
        $pdo = Database::getConnection();
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute(['test_admin']);
        $result = $stmt->fetch();
        
        $this->assertNotNull($result, "Should find test admin user");
        $this->assertEquals('test_admin', $result['username'], "Should return correct username");
    }
    
    public function testDatabaseTransaction(): void
    {
        // Test transaction rollback
        $pdo = Database::getConnection();
        
        try {
            $pdo->beginTransaction();
            
            // Insert test data
            $stmt = $pdo->prepare("INSERT INTO users (name, username, password_hash, role_id, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(['Test Transaction', 'test_transaction', password_hash('password', PASSWORD_DEFAULT), 1, 'active']);
            
            // Rollback transaction
            $pdo->rollBack();
            
            // Verify data was not inserted
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE username = ?");
            $stmt->execute(['test_transaction']);
            $result = $stmt->fetch();
            
            $this->assertEquals(0, $result['count'], "Transaction should be rolled back");
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $this->assertTrue(true, "Transaction should handle exceptions gracefully");
        }
    }
    
    public function testDatabaseErrorHandling(): void
    {
        // Test error handling with invalid query
        $pdo = Database::getConnection();
        
        try {
            $stmt = $pdo->query("SELECT * FROM non_existent_table");
            $this->assertTrue(false, "Should throw exception for invalid table");
        } catch (PDOException $e) {
            $this->assertTrue(true, "Should throw PDOException for invalid query");
        }
    }
    
    public function testDatabaseConnectionParameters(): void
    {
        // Test database connection parameters
        $pdo = Database::getConnection();
        
        // Test attributes
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $pdo->getAttribute(PDO::ATTR_ERRMODE));
        $this->assertEquals(PDO::FETCH_ASSOC, $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE));
        $this->assertFalse($pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES));
    }
}
