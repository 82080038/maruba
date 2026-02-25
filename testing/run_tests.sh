#!/bin/bash

# Maruba Application Test Runner
# This script runs all PHPUnit tests for the Maruba application

set -e

echo "🧪 Maruba Application Test Runner"
echo "================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if PHPUnit is installed
if ! command -v phpunit &> /dev/null; then
    print_error "PHPUnit is not installed. Please install it first:"
    echo "sudo apt install phpunit"
    exit 1
fi

# Check if test database exists
print_status "Checking test database..."
DB_NAME="maruba_test"
if ! mysql -u root -proot -e "USE $DB_NAME" 2>/dev/null; then
    print_warning "Test database '$DB_NAME' does not exist. Creating it..."
    mysql -u root -proot -e "CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"
    
    # Import schema
    print_status "Importing database schema..."
    mysql -u root -proot $DB_NAME < ../sql/database_update_final.sql 2>/dev/null || {
        print_warning "Could not import schema. Using basic setup..."
    }
fi

# Run tests
print_status "Running PHPUnit tests..."

# Change to testing directory
cd "$(dirname "$0")"

# Run unit tests
print_status "Running Unit Tests..."
if phpunit --testsuite Unit --verbose; then
    print_success "Unit Tests Passed"
else
    print_error "Unit Tests Failed"
    UNIT_TESTS_FAILED=true
fi

# Run integration tests
print_status "Running Integration Tests..."
if phpunit --testsuite Integration --verbose; then
    print_success "Integration Tests Passed"
else
    print_error "Integration Tests Failed"
    INTEGRATION_TESTS_FAILED=true
fi

# Run feature tests
print_status "Running Feature Tests..."
if phpunit --testsuite Feature --verbose; then
    print_success "Feature Tests Passed"
else
    print_error "Feature Tests Failed"
    FEATURE_TESTS_FAILED=true
fi

# Run all tests with coverage
print_status "Running All Tests with Coverage..."
if phpunit --coverage-html=coverage-html --coverage-text=coverage.txt --verbose; then
    print_success "All Tests Passed with Coverage"
else
    print_error "Some Tests Failed"
    ALL_TESTS_FAILED=true
fi

# Generate test report
print_status "Generating Test Report..."
cat > test_report.txt << EOF
Maruba Application Test Report
============================
Generated: $(date)

Test Results:
- Unit Tests: ${UNIT_TESTS_FAILED:-PASSED}
- Integration Tests: ${INTEGRATION_TESTS_FAILED:-PASSED}
- Feature Tests: ${FEATURE_TESTS_FAILED:-PASSED}
- All Tests: ${ALL_TESTS_FAILED:-PASSED}

Coverage Report:
$(tail -n 20 coverage.txt 2>/dev/null || echo "Coverage report not available")

Next Steps:
1. Review any failed tests
2. Check coverage report in coverage-html/
3. Fix any issues found
4. Run tests again to verify fixes
EOF

print_success "Test report generated: test_report.txt"

# Show coverage summary if available
if [ -f "coverage.txt" ]; then
    print_status "Coverage Summary:"
    tail -n 10 coverage.txt | head -n 5
fi

# Final status
if [ "$UNIT_TESTS_FAILED" ] || [ "$INTEGRATION_TESTS_FAILED" ] || [ "$FEATURE_TESTS_FAILED" ] || [ "$ALL_TESTS_FAILED" ]; then
    print_error "Some tests failed. Please check the output above."
    exit 1
else
    print_success "All tests passed successfully! 🎉"
    
    # Show coverage HTML location
    if [ -d "coverage-html" ]; then
        print_status "Coverage report available at: coverage-html/index.html"
    fi
fi

echo ""
echo "🚀 Testing completed!"
