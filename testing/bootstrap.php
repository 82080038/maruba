<?php
/**
 * PHPUnit Bootstrap File for Maruba Application
 */

// Error reporting for testing
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define test environment
define('APP_ENV', 'testing');
define('TESTING', true);

// Load application bootstrap
require_once __DIR__ . '/../App/src/bootstrap.php';

// Load SecurityHelper for testing
require_once __DIR__ . '/../App/src/Helpers/SecurityHelper.php';

// Test database setup
class TestDatabase
{
    private static $pdo = null;
    
    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $dbname = $_ENV['DB_NAME'] ?? 'maruba_test';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? 'root';
            
            self::$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        
        return self::$pdo;
    }
    
    public static function setupDatabase(): void
    {
        $pdo = self::getConnection();
        
        // Clean up test data
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Clear test tables
        $tables = ['audit_logs', 'loan_docs', 'repayments', 'surveys', 'loans', 'members', 'users', 'roles', 'products', 'tenants'];
        
        foreach ($tables as $table) {
            $pdo->exec("TRUNCATE TABLE $table");
        }
        
        // Insert test data
        self::insertTestData();
        
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    
    private static function insertTestData(): void
    {
        $pdo = self::getConnection();
        
        // Insert test roles
        $roles = [
            ['name' => 'admin', 'permissions' => '{"dashboard": ["view"], "users": ["view","create","edit","delete"]}'],
            ['name' => 'kasir', 'permissions' => '{"dashboard": ["view"], "cash": ["view","create","edit"]}'],
            ['name' => 'teller', 'permissions' => '{"dashboard": ["view"], "savings": ["view","create","edit"]}'],
        ];
        
        foreach ($roles as $role) {
            $stmt = $pdo->prepare("INSERT INTO roles (name, permissions) VALUES (?, ?)");
            $stmt->execute([$role['name'], $role['permissions']]);
        }
        
        // Insert test users
        $users = [
            ['name' => 'Test Admin', 'username' => 'test_admin', 'password_hash' => password_hash('password', PASSWORD_DEFAULT), 'role_id' => 1],
            ['name' => 'Test Kasir', 'username' => 'test_kasir', 'password_hash' => password_hash('password', PASSWORD_DEFAULT), 'role_id' => 2],
            ['name' => 'Test Teller', 'username' => 'test_teller', 'password_hash' => password_hash('password', PASSWORD_DEFAULT), 'role_id' => 3],
        ];
        
        foreach ($users as $user) {
            $stmt = $pdo->prepare("INSERT INTO users (name, username, password_hash, role_id, status) VALUES (?, ?, ?, ?, 'active')");
            $stmt->execute([$user['name'], $user['username'], $user['password_hash'], $user['role_id']]);
        }
        
        // Insert test products
        $products = [
            ['name' => 'Pinjaman Test', 'type' => 'loan', 'rate' => 1.5, 'tenor_months' => 12, 'fee' => 50000],
            ['name' => 'Tabungan Test', 'type' => 'savings', 'rate' => 0.5, 'tenor_months' => 0, 'fee' => 0],
        ];
        
        foreach ($products as $product) {
            $stmt = $pdo->prepare("INSERT INTO products (name, type, rate, tenor_months, fee) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$product['name'], $product['type'], $product['rate'], $product['tenor_months'], $product['fee']]);
        }
        
        // Insert test members
        $members = [
            ['name' => 'Test Member 1', 'nik' => '1234567890123456', 'phone' => '08123456789', 'address' => 'Test Address 1'],
            ['name' => 'Test Member 2', 'nik' => '9876543210987654', 'phone' => '08198765432', 'address' => 'Test Address 2'],
        ];
        
        foreach ($members as $member) {
            $stmt = $pdo->prepare("INSERT INTO members (name, nik, phone, address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$member['name'], $member['nik'], $member['phone'], $member['address']]);
        }
    }
    
    public static function tearDownDatabase(): void
    {
        $pdo = self::getConnection();
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        $tables = ['audit_logs', 'loan_docs', 'repayments', 'surveys', 'loans', 'members', 'users', 'roles', 'products', 'tenants'];
        
        foreach ($tables as $table) {
            $pdo->exec("TRUNCATE TABLE $table");
        }
        
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
}

// Base test class
abstract class TestCase
{
    protected PDO $pdo;
    
    protected function setUp(): void
    {
        TestDatabase::setupDatabase();
        $this->pdo = TestDatabase::getConnection();
    }
    
    protected function tearDown(): void
    {
        TestDatabase::tearDownDatabase();
    }
    
    protected function createTestUser(string $role = 'admin'): array
    {
        $stmt = $this->pdo->prepare("SELECT u.*, r.name as role FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = ? LIMIT 1");
        $stmt->execute([$role]);
        return $stmt->fetch() ?: [];
    }
    
    protected function createTestMember(): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM members LIMIT 1");
        $stmt->execute();
        return $stmt->fetch() ?: [];
    }
    
    protected function createTestProduct(string $type = 'loan'): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE type = ? LIMIT 1");
        $stmt->execute([$type]);
        return $stmt->fetch() ?: [];
    }
    
    protected function assertArrayHasKey(string $key, array $array, string $message = ''): void
    {
        if (!array_key_exists($key, $array)) {
            throw new PHPUnit\Framework\AssertionFailedError($message ?: "Failed asserting that array has key '$key'");
        }
    }
    
    protected function assertEquals($expected, $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            throw new PHPUnit\Framework\AssertionFailedError($message ?: "Failed asserting that " . var_export($expected, true) . " equals " . var_export($actual, true));
        }
    }
    
    protected function assertTrue($condition, string $message = ''): void
    {
        if (!$condition) {
            throw new PHPUnit\Framework\AssertionFailedError($message ?: "Failed asserting that condition is true");
        }
    }
    
    protected function assertFalse($condition, string $message = ''): void
    {
        if ($condition) {
            throw new PHPUnit\Framework\AssertionFailedError($message ?: "Failed asserting that condition is false");
        }
    }
    
    protected function assertNotNull($value, string $message = ''): void
    {
        if ($value === null) {
            throw new PHPUnit\Framework\AssertionFailedError($message ?: "Failed asserting that value is not null");
        }
    }
    
    protected function assertNull($value, string $message = ''): void
    {
        if ($value !== null) {
            throw new PHPUnit\Framework\AssertionFailedError($message ?: "Failed asserting that value is null");
        }
    }
}
