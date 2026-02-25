<?php
/**
 * Integration Tests for Database Operations
 */

class DatabaseIntegrationTest extends TestCase
{
    public function testUserCreationAndRetrieval(): void
    {
        // Test creating a user
        $stmt = $this->pdo->prepare("INSERT INTO users (name, username, password_hash, role_id, status) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute(['Integration Test User', 'integration_user', password_hash('password', PASSWORD_DEFAULT), 1, 'active']);
        
        $this->assertTrue($result, "User creation should succeed");
        
        // Test retrieving the user
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute(['integration_user']);
        $user = $stmt->fetch();
        
        $this->assertNotNull($user, "Should retrieve created user");
        $this->assertEquals('integration_user', $user['username'], "Should have correct username");
        $this->assertEquals('Integration Test User', $user['name'], "Should have correct name");
    }
    
    public function testMemberCreationWithLoan(): void
    {
        // Create a member
        $stmt = $this->pdo->prepare("INSERT INTO members (name, nik, phone, address) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Integration Member', '1234567890123456', '08123456789', 'Test Address']);
        $memberId = $this->pdo->lastInsertId();
        
        // Create a loan for the member
        $stmt = $this->pdo->prepare("INSERT INTO loans (member_id, product_id, amount, tenor_months, rate, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$memberId, 1, 1000000, 12, 1.5, 'draft']);
        $loanId = $this->pdo->lastInsertId();
        
        // Verify relationship
        $stmt = $this->pdo->prepare("SELECT l.*, m.name as member_name FROM loans l JOIN members m ON l.member_id = m.id WHERE l.id = ?");
        $stmt->execute([$loanId]);
        $loan = $stmt->fetch();
        
        $this->assertNotNull($loan, "Should retrieve loan with member");
        $this->assertEquals('Integration Member', $loan['member_name'], "Should have correct member name");
        $this->assertEquals(1000000, $loan['amount'], "Should have correct loan amount");
    }
    
    public function testRepaymentCreation(): void
    {
        // Create member and loan first
        $stmt = $this->pdo->prepare("INSERT INTO members (name, nik, phone, address) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Repayment Member', '9876543210987654', '08198765432', 'Test Address']);
        $memberId = $this->pdo->lastInsertId();
        
        $stmt = $this->pdo->prepare("INSERT INTO loans (member_id, product_id, amount, tenor_months, rate, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$memberId, 1, 1000000, 12, 1.5, 'disbursed']);
        $loanId = $this->pdo->lastInsertId();
        
        // Create repayment
        $stmt = $this->pdo->prepare("INSERT INTO repayments (loan_id, due_date, amount_due, amount_paid, method, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$loanId, date('Y-m-d'), 100000, 100000, 'tunai', 'paid']);
        $repaymentId = $this->pdo->lastInsertId();
        
        // Verify repayment
        $stmt = $this->pdo->prepare("SELECT r.*, l.amount as loan_amount FROM repayments r JOIN loans l ON r.loan_id = l.id WHERE r.id = ?");
        $stmt->execute([$repaymentId]);
        $repayment = $stmt->fetch();
        
        $this->assertNotNull($repayment, "Should retrieve repayment");
        $this->assertEquals(100000, $repayment['amount_due'], "Should have correct due amount");
        $this->assertEquals(100000, $repayment['amount_paid'], "Should have correct paid amount");
        $this->assertEquals('paid', $repayment['status'], "Should have paid status");
    }
    
    public function testSurveyCreation(): void
    {
        // Create member and loan first
        $stmt = $this->pdo->prepare("INSERT INTO members (name, nik, phone, address) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Survey Member', '1111222233334444', '08111111111', 'Survey Address']);
        $memberId = $this->pdo->lastInsertId();
        
        $stmt = $this->pdo->prepare("INSERT INTO loans (member_id, product_id, amount, tenor_months, rate, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$memberId, 1, 1000000, 12, 1.5, 'survey']);
        $loanId = $this->pdo->lastInsertId();
        
        // Create survey
        $stmt = $this->pdo->prepare("INSERT INTO surveys (loan_id, surveyor_id, result, score, geo_lat, geo_lng) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$loanId, 1, 'Good business, stable income', 85, '-2.6500000', '99.0500000']);
        $surveyId = $this->pdo->lastInsertId();
        
        // Verify survey
        $stmt = $this->pdo->prepare("SELECT s.*, m.name as member_name FROM surveys s JOIN loans l ON s.loan_id = l.id JOIN members m ON l.member_id = m.id WHERE s.id = ?");
        $stmt->execute([$surveyId]);
        $survey = $stmt->fetch();
        
        $this->assertNotNull($survey, "Should retrieve survey");
        $this->assertEquals('Good business, stable income', $survey['result'], "Should have correct result");
        $this->assertEquals(85, $survey['score'], "Should have correct score");
        $this->assertEquals('Survey Member', $survey['member_name'], "Should have correct member name");
    }
    
    public function testAuditLogCreation(): void
    {
        // Create an audit log
        $stmt = $this->pdo->prepare("INSERT INTO audit_logs (user_id, action, entity, entity_id, meta) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([1, 'create', 'member', 1, '{"test": "integration test"}']);
        $logId = $this->pdo->lastInsertId();
        
        // Verify audit log
        $stmt = $this->pdo->prepare("SELECT a.*, u.name as user_name FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id WHERE a.id = ?");
        $stmt->execute([$logId]);
        $log = $stmt->fetch();
        
        $this->assertNotNull($log, "Should retrieve audit log");
        $this->assertEquals('create', $log['action'], "Should have correct action");
        $this->assertEquals('member', $log['entity'], "Should have correct entity");
        $this->assertEquals('{"test": "integration test"}', $log['meta'], "Should have correct metadata");
    }
    
    public function testForeignKeyConstraints(): void
    {
        // Test foreign key constraint violation
        try {
            // Try to create loan with non-existent member
            $stmt = $this->pdo->prepare("INSERT INTO loans (member_id, product_id, amount, tenor_months, rate, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([999, 1, 1000000, 12, 1.5, 'draft']);
            
            $this->assertTrue(false, "Should throw foreign key constraint violation");
        } catch (PDOException $e) {
            $this->assertTrue(true, "Should throw foreign key constraint violation");
        }
    }
}
