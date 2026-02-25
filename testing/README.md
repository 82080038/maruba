# 🧪 Maruba Application Testing Framework

## Overview
This directory contains the complete testing framework for the Maruba Koperasi application using PHPUnit.

## 📁 Structure

```
testing/
├── phpunit.xml              # PHPUnit configuration
├── bootstrap.php            # Test bootstrap file
├── run_tests.sh            # Test runner script
├── README.md               # This file
├── Unit/                   # Unit tests
│   ├── Controllers/        # Controller tests
│   ├── Models/            # Model tests
│   └── Helpers/           # Helper tests
├── Integration/            # Integration tests
│   ├── Database/          # Database integration tests
│   └── API/               # API integration tests
└── Feature/               # Feature/end-to-end tests
    ├── Auth/              # Authentication tests
    └── Dashboard/         # Dashboard functionality tests
```

## 🚀 Quick Start

### 1. Install Dependencies
```bash
sudo apt install phpunit php-xml php-dom php-mbstring
```

### 2. Setup Test Database
```bash
# Create test database
mysql -u root -proot -e "CREATE DATABASE maruba_test CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"

# Import schema (optional)
mysql -u root -proot maruba_test < ../sql/database_update_final.sql
```

### 3. Run All Tests
```bash
cd testing
./run_tests.sh
```

### 4. Run Specific Test Suites
```bash
# Unit tests only
phpunit --testsuite Unit

# Integration tests only
phpunit --testsuite Integration

# Feature tests only
phpunit --testsuite Feature

# Specific test file
phpunit Unit/Controllers/AuthControllerTest.php
```

## 📊 Test Coverage

### Unit Tests (50%)
- **Controllers**: Business logic testing
- **Models**: Database model testing
- **Helpers**: Utility function testing

### Integration Tests (30%)
- **Database**: Database operations testing
- **API**: API endpoint testing

### Feature Tests (20%)
- **Authentication**: Login/logout flows
- **Dashboard**: Role-based dashboard testing

## 🛠️ Test Configuration

### PHPUnit Configuration (phpunit.xml)
- Test suites: Unit, Integration, Feature
- Coverage reporting: HTML, text, XML
- Database: maruba_test
- Environment: testing

### Test Database Setup
- Automatic database setup/teardown
- Test data insertion
- Foreign key constraints
- Tenant isolation testing

### Test Environment
- `APP_ENV=testing`
- `DB_NAME=maruba_test`
- Error reporting enabled
- Session handling for tests

## 📝 Writing Tests

### Unit Test Example
```php
class AuthControllerTest extends TestCase
{
    public function testLoginWithValidCredentials(): void
    {
        $_POST['username'] = 'test_admin';
        $_POST['password'] = 'password';
        
        $this->authController->login();
        $this->assertTrue(true, "Login should succeed");
    }
}
```

### Integration Test Example
```php
class DatabaseIntegrationTest extends TestCase
{
    public function testUserCreationAndRetrieval(): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO users ...");
        $stmt->execute([...]);
        
        $user = $this->getUser($userId);
        $this->assertEquals('test_user', $user['username']);
    }
}
```

### Feature Test Example
```php
class LoginFeatureTest extends TestCase
{
    public function testCompleteLoginFlow(): void
    {
        $this->simulatePostRequest('/login', [
            'username' => 'test_admin',
            'password' => 'password'
        ]);
        
        $this->assertUserIsLoggedIn();
        $this->assertRedirectedTo('/dashboard');
    }
}
```

## 🔧 Test Helpers

### Base TestCase Class
```php
abstract class TestCase
{
    protected function setUp(): void
    protected function tearDown(): void
    protected function createTestUser(string $role): array
    protected function createTestMember(): array
    protected function assertArrayHasKey(string $key, array $array): void
    protected function assertEquals($expected, $actual): void
    protected function assertTrue($condition): void
}
```

### Database Helper
```php
class TestDatabase
{
    public static function getConnection(): PDO
    public static function setupDatabase(): void
    public static function tearDownDatabase(): void
    private static function insertTestData(): void
}
```

## 📈 Coverage Reports

### HTML Coverage Report
- Location: `coverage-html/index.html`
- Interactive coverage visualization
- Line-by-line coverage analysis

### Text Coverage Report
- Location: `coverage.txt`
- Summary statistics
- File-level coverage percentages

### XML Coverage Report
- Location: `coverage.xml`
- CI/CD integration
- Coverage trend analysis

## 🎯 Test Categories

### ✅ What to Test
- **Business Logic**: Core application logic
- **Database Operations**: CRUD operations
- **Authentication**: Login/logout flows
- **Authorization**: Role-based access
- **API Endpoints**: Request/response handling
- **Data Validation**: Input validation
- **Error Handling**: Exception management

### ❌ What NOT to Test
- **Third-party libraries**: Assume they work
- **PHP language features**: Assume they work
- **Database engine**: Assume it works
- **Server configuration**: Assume it works

## 🔄 Continuous Integration

### GitHub Actions (Example)
```yaml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          extensions: mbstring, xml, dom
      - name: Install PHPUnit
        run: composer install --no-dev
      - name: Run Tests
        run: cd testing && ./run_tests.sh
```

### Local CI/CD
```bash
# Pre-commit hook
#!/bin/sh
cd testing && ./run_tests.sh
if [ $? -ne 0 ]; then
    echo "Tests failed! Commit aborted."
    exit 1
fi
```

## 🐛 Debugging Tests

### Common Issues
1. **Database Connection**: Check test database exists
2. **Permissions**: Ensure MySQL user has access
3. **Dependencies**: Install required PHP extensions
4. **Configuration**: Verify phpunit.xml settings

### Debug Mode
```bash
# Run with verbose output
phpunit --verbose

# Run specific test with details
phpunit --filter testLoginWithValidCredentials --verbose

# Stop on first failure
phpunit --stop-on-failure
```

## 📚 Best Practices

### Test Naming
- Use descriptive test method names
- Follow `testMethodName` convention
- Group related tests together

### Test Structure
- Arrange: Setup test data
- Act: Execute test action
- Assert: Verify expected result

### Data Management
- Use test database
- Clean up after each test
- Use transactions for rollback

### Assertions
- Use specific assertions
- Provide meaningful messages
- Test both success and failure cases

## 🚀 Next Steps

1. **Add More Tests**: Expand test coverage
2. **Mock External Services**: Mock payment gateways
3. **Performance Tests**: Load testing scenarios
4. **Security Tests**: Vulnerability testing
5. **API Tests**: Comprehensive API testing

## 📞 Support

For questions or issues with the testing framework:
1. Check the test output for specific errors
2. Review the configuration files
3. Verify database setup
4. Consult PHPUnit documentation

---

**Happy Testing! 🧪✨**
