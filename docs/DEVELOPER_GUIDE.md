# Maruba Koperasi - Developer Guide

## Table of Contents
1. [Development Environment Setup](#development-environment-setup)
2. [Architecture Overview](#architecture-overview)
3. [Database Design](#database-design)
4. [API Documentation](#api-documentation)
5. [Security Implementation](#security-implementation)
6. [Testing Framework](#testing-framework)
7. [Deployment Guide](#deployment-guide)
8. [Contributing Guidelines](#contributing-guidelines)

---

## Development Environment Setup

### Prerequisites
- **PHP**: 8.1 or higher
- **MySQL**: 8.0 or higher
- **Apache**: 2.4 or higher
- **Composer**: Latest version
- **Git**: Version control
- **PHPUnit**: Testing framework

### Local Development Setup

#### 1. Clone Repository
```bash
git clone https://github.com/maruba/koperasi.git
cd maruba
```

#### 2. Install Dependencies
```bash
composer install
npm install  # If using Node.js for frontend assets
```

#### 3. Database Setup
```bash
# Create database
mysql -u root -p
CREATE DATABASE maruba_dev;
CREATE USER 'maruba_dev'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON maruba_dev.* TO 'maruba_dev'@'localhost';
FLUSH PRIVILEGES;
```

#### 4. Configuration
```bash
# Copy environment file
cp .env.example .env
# Edit .env with your settings
```

#### 5. Database Migration
```bash
# Run database migrations
php scripts/migrate.php
```

#### 6. Start Development Server
```bash
# Start Apache
sudo /opt/lampp/lampp startapache

# Or use PHP built-in server
php -S localhost:8000 -t public/
```

### Development Tools

#### IDE Configuration
- **VS Code**: Recommended extensions
  - PHP Intelephense
  - PHP Debug
  - MySQL
  - GitLens
- **PHPStorm**: Recommended settings
  - Code style configuration
  - Database connection
  - Debug configuration

#### Code Quality Tools
- **PHP CodeSniffer**: Code style checking
- **PHP Mess Detector**: Code quality analysis
- **PHPStan**: Static analysis
- **PHPUnit**: Unit testing

---

## Architecture Overview

### System Architecture
```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Application  │    │   Database      │
│                 │    │                 │    │                 │
│ • PHP Templates │◄──►│ • Controllers   │◄──►│ • MySQL 8.0     │
│ • JavaScript    │    │ • Models        │    │ • Multi-tenant  │
│ • CSS           │    │ • Helpers       │    │ • Row-level     │
│ • Bootstrap     │    │ • Middleware    │    │   security      │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

### Directory Structure
```
maruba/
├── App/
│   ├── src/
│   │   ├── Controllers/     # Application controllers
│   │   ├── Models/          # Data models
│   │   ├── Views/           # View templates
│   │   ├── Helpers/         # Helper functions
│   │   ├── Middleware/      # Request middleware
│   │   ├── Services/        # Business logic
│   │   └── bootstrap.php    # Application bootstrap
│   └── public/              # Public assets
├── config/                  # Configuration files
├── database/                # Database files
├── docs/                    # Documentation
├── scripts/                 # Utility scripts
├── testing/                 # Test files
├── vendor/                  # Composer dependencies
└── .env                     # Environment configuration
```

### MVC Pattern

#### Models
- **Location**: `App/src/Models/`
- **Purpose**: Data access and business logic
- **Base Class**: `App\Models\Model`
- **Database**: MySQL with PDO

#### Controllers
- **Location**: `App/src/Controllers/`
- **Purpose**: Request handling and response
- **Base Class**: N/A (no inheritance)
- **Routing**: Custom routing system

#### Views
- **Location**: `App/src/Views/`
- **Purpose**: Template rendering
- **Engine**: PHP templates
- **Layout**: Base layout system

### Multi-Tenant Architecture

#### Tenant Isolation
- **Database**: Separate database per tenant
- **Security**: Row-level security
- **Configuration**: Tenant-specific settings
- **Routing**: Subdomain-based routing

#### Tenant Management
```php
// Tenant detection
$tenant = TenantHelper::getCurrentTenant();

// Database connection
$database = Database::getTenantConnection($tenant['id']);

// Security filtering
$data = SecurityHelper::filterByTenant($data, $tenant['id']);
```

---

## Database Design

### Database Schema

#### Core Tables
```sql
-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    role_id INT NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Members table
CREATE TABLE members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    nik VARCHAR(16) UNIQUE NOT NULL,
    kk VARCHAR(16) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(15),
    email VARCHAR(100),
    birth_date DATE,
    gender ENUM('male', 'female'),
    status ENUM('active', 'inactive', 'withdrawn') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

-- Loans table
CREATE TABLE loans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    member_id INT NOT NULL,
    product_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    interest_rate DECIMAL(5,2) NOT NULL,
    term_months INT NOT NULL,
    purpose TEXT,
    status ENUM('pending', 'approved', 'disbursed', 'paid', 'default') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (member_id) REFERENCES members(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);
```

#### Security Tables
```sql
-- Roles table
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Audit logs table
CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_data JSON,
    new_data JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### Database Relationships

#### Entity Relationship Diagram
```
Tenants (1) ── (N) Users
Tenants (1) ── (N) Members
Tenants (1) ── (N) Loans
Members (1) ── (N) Loans
Loans (1) ── (N) Repayments
Users (N) ── (N) Roles
Users (1) ── (N) AuditLogs
```

### Database Operations

#### CRUD Operations
```php
// Create
$member = new Member();
$member->create([
    'tenant_id' => $tenantId,
    'nik' => $nik,
    'name' => $name,
    // ... other fields
]);

// Read
$member = new Member();
$members = $member->findWhere(['tenant_id' => $tenantId]);

// Update
$member = new Member();
$member->update($memberId, $updateData);

// Delete
$member = new Member();
$member->delete($memberId);
```

#### Query Optimization
```php
// Use indexes
CREATE INDEX idx_members_tenant_id ON members(tenant_id);
CREATE INDEX idx_loans_member_id ON loans(member_id);

// Use prepared statements
$stmt = $pdo->prepare("SELECT * FROM members WHERE tenant_id = ?");
$stmt->execute([$tenantId]);

// Use transactions
$pdo->beginTransaction();
try {
    // Multiple operations
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    throw $e;
}
```

---

## API Documentation

### REST API Overview

#### Base URL
```
Development: http://localhost/maruba/api
Production: https://your-domain.com/api
```

#### Authentication
```php
// JWT Token Authentication
$headers = [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
];
```

#### Response Format
```json
{
    "status": "success|error",
    "data": {},
    "message": "Optional message",
    "timestamp": "2024-02-24T12:00:00Z"
}
```

### API Endpoints

#### Authentication
```php
POST /api/auth/login
{
    "username": "admin",
    "password": "password"
}

Response:
{
    "status": "success",
    "data": {
        "token": "jwt_token_here",
        "user": {
            "id": 1,
            "username": "admin",
            "role": "admin"
        }
    }
}
```

#### Members
```php
GET /api/members
Headers: Authorization: Bearer {token}

Response:
{
    "status": "success",
    "data": {
        "members": [
            {
                "id": 1,
                "nik": "1234567890123456",
                "name": "John Doe",
                "email": "john@example.com"
            }
        ],
        "pagination": {
            "page": 1,
            "limit": 20,
            "total": 100
        }
    }
}
```

#### Loans
```php
POST /api/loans
Headers: Authorization: Bearer {token}
Content-Type: application/json

{
    "member_id": 1,
    "product_id": 1,
    "amount": 1000000,
    "purpose": "Business capital"
}

Response:
{
    "status": "success",
    "data": {
        "loan": {
            "id": 1,
            "member_id": 1,
            "amount": 1000000,
            "status": "pending"
        }
    }
}
```

### Mobile API

#### Mobile Authentication
```php
POST /api/mobile/login
{
    "username": "mobile_user",
    "password": "password",
    "device_id": "device_unique_id",
    "device_type": "android|ios",
    "app_version": "1.0.0"
}
```

#### Mobile Dashboard
```php
GET /api/mobile/dashboard
Headers: Authorization: Bearer {token}

Response:
{
    "status": "success",
    "data": {
        "metrics": {
            "total_loans": 100,
            "active_members": 500,
            "collection_rate": 95.5
        },
        "user": {
            "id": 1,
            "role": "kasir"
        }
    }
}
```

---

## Security Implementation

### Authentication System

#### Password Hashing
```php
// Use Argon2ID for password hashing
$hash = password_hash($password, PASSWORD_ARGON2ID);

// Verify password
if (password_verify($inputPassword, $storedHash)) {
    // Password is correct
}
```

#### Session Management
```php
// Secure session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);

// Session regeneration
session_regenerate_id(true);
```

#### CSRF Protection
```php
// Generate CSRF token
$token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $token;

// Verify CSRF token
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('CSRF token mismatch');
}
```

### Input Validation

#### Data Sanitization
```php
// Use SecurityHelper for input sanitization
$sanitized = SecurityHelper::sanitize($input);

// Validate email
if (!SecurityHelper::validateEmail($email)) {
    throw new ValidationException('Invalid email');
}

// Validate phone number
if (!SecurityHelper::validatePhone($phone)) {
    throw new ValidationException('Invalid phone number');
}
```

#### SQL Injection Prevention
```php
// Use prepared statements
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);

// Use parameter binding
$stmt = $pdo->prepare("INSERT INTO members (name, email) VALUES (?, ?)");
$stmt->execute([$name, $email]);
```

#### XSS Prevention
```php
// Escape output
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// Use Content Security Policy
header("Content-Security-Policy: default-src 'self'");
```

### Authorization System

#### Role-Based Access Control
```php
// Check user permissions
if (!AuthHelper::hasPermission($user, 'loans.create')) {
    throw new AuthorizationException('Access denied');
}

// Role-based menu generation
$menuItems = NavigationHelper::getMenuForRole($user['role']);
```

#### Tenant Isolation
```php
// Filter data by tenant
$data = SecurityHelper::filterByTenant($data, $tenantId);

// Tenant-specific queries
$stmt = $pdo->prepare("SELECT * FROM members WHERE tenant_id = ?");
$stmt->execute([$tenantId]);
```

### Audit Logging

#### Activity Logging
```php
// Log user actions
AuditHelper::log([
    'user_id' => $userId,
    'action' => 'member.create',
    'table_name' => 'members',
    'record_id' => $memberId,
    'old_data' => null,
    'new_data' => $memberData
]);
```

#### Security Events
```php
// Log security events
SecurityHelper::logSecurityEvent([
    'event' => 'login.failed',
    'user_id' => $userId,
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT']
]);
```

---

## Testing Framework

### PHPUnit Setup

#### Configuration
```xml
<!-- phpunit.xml -->
<phpunit>
    <testsuites>
        <testsuite name="Unit">
            <directory>testing/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>testing/Integration</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>testing/Feature</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

#### Bootstrap
```php
// testing/bootstrap.php
<?php
// Define test environment
define('APP_ENV', 'testing');
define('TESTING', true);

// Load application
require_once __DIR__ . '/../App/src/bootstrap.php';

// Setup test database
Database::setupTestDatabase();
```

### Unit Tests

#### Example Unit Test
```php
<?php
// testing/Unit/Helpers/SecurityHelperTest.php
use PHPUnit\Framework\TestCase;

class SecurityHelperTest extends TestCase
{
    public function testValidateEmail()
    {
        $this->assertTrue(SecurityHelper::validateEmail('test@example.com'));
        $this->assertFalse(SecurityHelper::validateEmail('invalid-email'));
    }
    
    public function testSanitize()
    {
        $input = '<script>alert("xss")</script>';
        $sanitized = SecurityHelper::sanitize($input);
        $this->assertStringNotContainsString($sanitized, '<script>');
    }
}
```

### Integration Tests

#### Example Integration Test
```php
<?php
// testing/Integration/Database/MemberIntegrationTest.php
use PHPUnit\Framework\TestCase;

class MemberIntegrationTest extends TestCase
{
    public function testCreateMember()
    {
        $member = new Member();
        $memberId = $member->create([
            'tenant_id' => 1,
            'nik' => '1234567890123456',
            'name' => 'Test Member'
        ]);
        
        $this->assertIsInt($memberId);
        $this->assertGreaterThan(0, $memberId);
    }
}
```

### Feature Tests

#### Example Feature Test
```php
<?php
// testing/Feature/Auth/LoginFeatureTest.php
use PHPUnit\Framework\TestCase;

class LoginFeatureTest extends TestCase
{
    public function testSuccessfulLogin()
    {
        // Simulate login request
        $response = $this->post('/login', [
            'username' => 'test_user',
            'password' => 'test_password'
        ]);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('token', $response->getData());
    }
}
```

### Running Tests

#### Command Line
```bash
# Run all tests
./testing/run_tests.sh

# Run specific test suite
phpunit --testsuite Unit

# Run specific test
phpunit --filter testValidateEmail

# Generate coverage report
phpunit --coverage-html coverage/
```

---

## Deployment Guide

### Production Deployment

#### Environment Setup
```bash
# Production environment file
APP_ENV=production
DEBUG=false
LOG_LEVEL=error
DB_HOST=localhost
DB_NAME=maruba_prod
DB_USER=maruba_user
DB_PASS=secure_password
```

#### Security Configuration
```bash
# File permissions
chmod 600 .env
chmod 755 App/src/
chmod 644 App/src/*.php

# SSL configuration
./ssl_setup.sh
```

#### Database Setup
```bash
# Create production database
mysql -u root -p
CREATE DATABASE maruba_prod;
CREATE USER 'maruba_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON maruba_prod.* TO 'maruba_user'@'localhost';
FLUSH PRIVILEGES;
```

### Deployment Script

#### Automated Deployment
```bash
#!/bin/bash
# deploy.sh

# Backup current version
./backup/create_backup.sh

# Update code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run database migrations
php scripts/migrate.php

# Clear cache
php scripts/clear_cache.php

# Restart services
sudo systemctl restart apache2
sudo systemctl restart mysql

# Verify deployment
curl -f http://localhost/maruba/health
```

### Monitoring Setup

#### Health Check
```php
// health.php
<?php
header('Content-Type: application/json');

$health = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'services' => [
        'database' => Database::isConnected(),
        'cache' => Cache::isConnected(),
        'storage' => Storage::isAvailable()
    ]
];

echo json_encode($health);
```

#### Performance Monitoring
```bash
# Setup monitoring
./monitoring/setup_monitoring.sh

# Configure alerts
./monitoring/setup_alerts.sh
```

---

## Contributing Guidelines

### Code Standards

#### PHP Standards
- Follow PSR-12 coding standards
- Use 4 spaces for indentation
- Use camelCase for variables and methods
- Use PascalCase for classes
- Add PHPDoc comments

#### Example Code
```php
<?php
/**
 * Member Model
 * 
 * @package App\Models
 * @author  Your Name
 * @version 1.0.0
 */
class Member extends Model
{
    /**
     * Create a new member
     * 
     * @param array $data Member data
     * @return int Member ID
     * @throws ValidationException
     */
    public function create(array $data): int
    {
        // Validate data
        $this->validate($data);
        
        // Insert into database
        return $this->insert($data);
    }
}
```

### Git Workflow

#### Branch Strategy
- `main`: Production branch
- `develop`: Development branch
- `feature/*`: Feature branches
- `hotfix/*`: Hotfix branches

#### Commit Messages
```
feat: Add member registration feature
fix: Fix login authentication issue
docs: Update API documentation
style: Fix code formatting
refactor: Refactor user service
test: Add unit tests for member model
chore: Update dependencies
```

#### Pull Request Process
1. Create feature branch
2. Make changes
3. Add tests
4. Update documentation
5. Create pull request
6. Code review
7. Merge to develop
8. Deploy to staging
9. Test on staging
10. Merge to main
11. Deploy to production

### Testing Requirements

#### Test Coverage
- Unit tests: 80% coverage minimum
- Integration tests: Critical paths
- Feature tests: User workflows

#### Test Types
- Unit tests: Individual components
- Integration tests: Component interactions
- Feature tests: End-to-end workflows
- Performance tests: Load and stress testing

### Documentation

#### Code Documentation
- PHPDoc for all classes and methods
- Inline comments for complex logic
- README for each module
- API documentation for all endpoints

#### User Documentation
- User guide
- Administrator guide
- Developer guide
- Deployment guide

---

## Troubleshooting

### Common Issues

#### Database Connection
```php
// Check database connection
try {
    $pdo = new PDO($dsn, $username, $password);
    echo "Database connected successfully";
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
}
```

#### Session Issues
```php
// Check session configuration
echo "Session save path: " . session_save_path();
echo "Session status: " . session_status();
```

#### Permission Issues
```bash
# Check file permissions
ls -la App/src/
chmod 755 App/src/
chmod 644 App/src/*.php
```

### Debug Tools

#### Error Logging
```php
// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/error.log');

// Custom logging
error_log("Custom error message");
```

#### Debug Mode
```php
// Enable debug mode
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}
```

---

## Performance Optimization

### Database Optimization

#### Query Optimization
```php
// Use indexes
CREATE INDEX idx_members_tenant_id ON members(tenant_id);

// Optimize queries
$stmt = $pdo->prepare("SELECT * FROM members WHERE tenant_id = ? LIMIT 10");
$stmt->execute([$tenantId]);
```

#### Caching
```php
// Use Redis for caching
$cache = RedisCache::getInstance();
$cachedData = $cache->get('members_list_' . $tenantId);

if (!$cachedData) {
    $cachedData = $this->getMembersFromDatabase($tenantId);
    $cache->set('members_list_' . $tenantId, $cachedData, 3600);
}
```

### Application Optimization

#### Code Optimization
```php
// Use lazy loading
public function getMembers()
{
    if ($this->members === null) {
        $this->members = $this->loadMembers();
    }
    return $this->members;
}
```

#### Memory Management
```php
// Free memory
unset($largeArray);
gc_collect_cycles();
```

---

## Security Best Practices

### Input Validation
```php
// Validate all inputs
$validated = $this->validate($input, [
    'name' => 'required|string|max:255',
    'email' => 'required|email',
    'phone' => 'required|phone'
]);
```

### Output Escaping
```php
// Escape all outputs
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
```

### Error Handling
```php
// Handle exceptions gracefully
try {
    $result = $this->riskyOperation();
} catch (Exception $e) {
    $this->logger->error('Operation failed', ['error' => $e->getMessage()]);
    throw new ServiceException('Operation failed');
}
```

---

*This developer guide is regularly updated. Last updated: $(date '+%Y-%m-%d')*
