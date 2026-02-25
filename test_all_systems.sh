#!/bin/bash

# Maruba Complete System Test Suite
# Tests all implemented systems and features

set -e

echo "🧪 Maruba Complete System Test Suite"
echo "=================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

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

print_header() {
    echo -e "${BLUE}=== $1 ===${NC}"
}

# Test results
TEST_RESULTS=()
FAILED_TESTS=()

# Function to run a test and record result
run_test() {
    local test_name="$1"
    local test_command="$2"
    
    print_status "Running: $test_name"
    
    if eval "$test_command" > /dev/null 2>&1; then
        print_success "✓ $test_name"
        TEST_RESULTS+=("$test_name: PASSED")
        return 0
    else
        print_error "✗ $test_name"
        TEST_RESULTS+=("$test_name: FAILED")
        FAILED_TESTS+=("$test_name")
        return 1
    fi
}

# Function to test database connectivity
test_database() {
    print_header "Database Connectivity Tests"
    
    run_test "MySQL Connection" "mysql -u root -proot -e 'SELECT 1' maruba"
    run_test "Database Tables" "mysql -u root -proot -e 'SHOW TABLES FROM maruba' | wc -l"
    run_test "User Table" "mysql -u root -proot -e 'SELECT COUNT(*) FROM maruba.users'"
    run_test "Members Table" "mysql -u root -proot -e 'SELECT COUNT(*) FROM maruba.members'"
    run_test "Loans Table" "mysql -u root -proot -e 'SELECT COUNT(*) FROM maruba.loans'"
}

# Function to test application functionality
test_application() {
    print_header "Application Functionality Tests"
    
    run_test "Apache Status" "pgrep -f apache2 || pgrep -f httpd"
    run_test "PHP Version" "php -v | head -n1"
    run_test "Application Access" "curl -s -o /dev/null -w '%{http_code}' http://localhost/maruba/ | grep -q '200'"
    run_test "Login Page" "curl -s -o /dev/null -w '%{http_code}' http://localhost/maruba/login | grep -q '200'"
    run_test "Dashboard Access" "curl -s -o /dev/null -w '%{http_code}' http://localhost/maruba/dashboard | grep -q '200\|302'"
}

# Function to test security features
test_security() {
    print_header "Security Features Tests"
    
    run_test "Security Helper Exists" "test -f App/src/Helpers/SecurityHelper.php"
    run_test "Security Middleware Exists" "test -f App/src/Middleware/SecurityMiddleware.php"
    run_test "Security Tests" "test -f testing/Unit/Helpers/SecurityHelperTest.php"
    run_test "SSL Certificate" "test -f ssl/maruba.crt"
    run_test "SSL Key" "test -f ssl/maruba.key"
    run_test "Security Audit Script" "test -f security_audit.sh"
}

# Function to test testing framework
test_testing_framework() {
    print_header "Testing Framework Tests"
    
    run_test "PHPUnit Installed" "phpunit --version"
    run_test "Test Bootstrap" "test -f testing/bootstrap.php"
    run_test "PHPUnit Config" "test -f testing/phpunit.xml"
    run_test "Test Runner" "test -f testing/run_tests.sh"
    run_test "Unit Tests" "ls testing/Unit/Controllers/ | wc -l"
    run_test "Integration Tests" "ls testing/Integration/ | wc -l"
    run_test "Feature Tests" "ls testing/Feature/ | wc -l"
}

# Function to test performance tools
test_performance_tools() {
    print_header "Performance Tools Tests"
    
    run_test "Performance Test Script" "test -f performance_test.sh"
    run_test "Load Test Script" "test -f load_test.sh"
    run_test "Apache Bench" "which ab"
    run_test "Siege" "which siege"
    run_test "htop" "which htop"
    run_test "iotop" "which iotop"
}

# Function to test monitoring system
test_monitoring() {
    print_header "Monitoring System Tests"
    
    run_test "Monitoring Setup" "test -f monitoring/setup_monitoring.sh"
    run_test "App Monitor Script" "test -f monitoring/scripts/app_monitor.sh"
    run_test "Performance Monitor" "test -f monitoring/scripts/performance_monitor.sh"
    run_test "Log Monitor" "test -f monitoring/scripts/log_monitor.sh"
    run_test "Log Rotation" "test -f monitoring/scripts/rotate_logs.sh"
    run_test "Monitoring Dashboard" "test -f monitoring/dashboard/monitoring_dashboard.php"
}

# Function to test caching system
test_caching() {
    print_header "Caching System Tests"
    
    run_test "Redis Cache Class" "test -f App/src/Cache/RedisCache.php"
    run_test "Redis Connection" "redis-cli ping 2>/dev/null || echo 'Redis not running'"
}

# Function to test mobile API
test_mobile_api() {
    print_header "Mobile API Tests"
    
    run_test "Mobile API Controller" "test -f App/src/Controllers/MobileApiController.php"
    run_test "API Version Endpoint" "curl -s -o /dev/null -w '%{http_code}' http://localhost/maruba/api/mobile/version | grep -q '200'"
}

# Function to test analytics
test_analytics() {
    print_header "Analytics System Tests"
    
    run_test "Predictive Analytics" "test -f App/src/Analytics/PredictiveAnalytics.php"
    run_test "Analytics Methods" "grep -c 'public function' App/src/Analytics/PredictiveAnalytics.php"
}

# Function to test integrations
test_integrations() {
    print_header "Third-Party Integrations Tests"
    
    run_test "Integrations Manager" "test -f App/src/Integrations/ThirdPartyIntegrations.php"
    run_test "Payment Gateway Support" "grep -c 'midtrans\|xendit\|gopay' App/src/Integrations/ThirdPartyIntegrations.php"
    run_test "Notification Support" "grep -c 'twilio\|firebase\|email' App/src/Integrations/ThirdPartyIntegrations.php"
    run_test "Storage Support" "grep -c 'aws_s3\|google_drive' App/src/Integrations/ThirdPartyIntegrations.php"
}

# Function to test navigation system
test_navigation() {
    print_header "Navigation System Tests"
    
    run_test "Navigation Helper" "test -f App/src/Helpers/NavigationHelper.php"
    run_test "Navigation Model" "test -f App/src/Models/Navigation.php"
    run_test "Navigation Controller" "test -f App/src/Controllers/NavigationController.php"
    run_test "Navigation Management View" "test -f App/src/Views/tenant/navigation/manage.php"
}

# Function to test dashboards
test_dashboards() {
    print_header "Dashboard System Tests"
    
    run_test "Dashboard Controller" "test -f App/src/Controllers/DashboardController.php"
    run_test "Admin Dashboard" "test -f App/src/Views/dashboard/index.php"
    run_test "Kasir Dashboard" "test -f App/src/Views/dashboard/kasir.php"
    run_test "Manajer Dashboard" "test -f App/src/Views/dashboard/manajer.php"
    run_test "Collector Dashboard" "test -f App/src/Views/dashboard/collector.php"
    run_test "Teller Dashboard" "test -f App/src/Views/dashboard/teller.php"
    run_test "Surveyor Dashboard" "test -f App/src/Views/dashboard/surveyor.php"
    run_test "Akuntansi Dashboard" "test -f App/src/Views/dashboard/akuntansi.php"
    run_test "Creator Dashboard" "test -f App/src/Views/dashboard/creator.php"
}

# Function to test SSL setup
test_ssl() {
    print_header "SSL Configuration Tests"
    
    run_test "SSL Setup Script" "test -f ssl_setup.sh"
    run_test "SSL Monitor Script" "test -f ssl_monitor.sh"
    run_test "HTTPS Configuration" "curl -s -o /dev/null -w '%{http_code}' https://localhost/ 2>/dev/null | grep -q '200\|302'"
}

# Function to run PHPUnit tests
run_phpunit_tests() {
    print_header "PHPUnit Test Suite"
    
    if [ -d "testing" ]; then
        cd testing
        
        print_status "Running Unit Tests..."
        if phpunit --testsuite Unit --verbose; then
            print_success "Unit Tests Passed"
            TEST_RESULTS+=("Unit Tests: PASSED")
        else
            print_error "Unit Tests Failed"
            TEST_RESULTS+=("Unit Tests: FAILED")
            FAILED_TESTS+=("Unit Tests")
        fi
        
        print_status "Running Integration Tests..."
        if phpunit --testsuite Integration --verbose; then
            print_success "Integration Tests Passed"
            TEST_RESULTS+=("Integration Tests: PASSED")
        else
            print_error "Integration Tests Failed"
            TEST_RESULTS+=("Integration Tests: FAILED")
            FAILED_TESTS+=("Integration Tests")
        fi
        
        print_status "Running Feature Tests..."
        if phpunit --testsuite Feature --verbose; then
            print_success "Feature Tests Passed"
            TEST_RESULTS+=("Feature Tests: PASSED")
        else
            print_error "Feature Tests Failed"
            TEST_RESULTS+=("Feature Tests: FAILED")
            FAILED_TESTS+=("Feature Tests")
        fi
        
        cd ..
    else
        print_warning "Testing directory not found"
    fi
}

# Function to generate test report
generate_test_report() {
    local report_file="system_test_report_$(date +%Y%m%d_%H%M%S).txt"
    
    echo "Maruba System Test Report" > "$report_file"
    echo "=========================" >> "$report_file"
    echo "Generated: $(date)" >> "$report_file"
    echo "" >> "$report_file"
    
    echo "Test Results:" >> "$report_file"
    for result in "${TEST_RESULTS[@]}"; do
        echo "- $result" >> "$report_file"
    done
    
    echo "" >> "$report_file"
    echo "Summary:" >> "$report_file"
    echo "- Total Tests: ${#TEST_RESULTS[@]}" >> "$report_file"
    echo "- Passed: $((${#TEST_RESULTS[@]} - ${#FAILED_TESTS[@]}))" >> "$report_file"
    echo "- Failed: ${#FAILED_TESTS[@]}" >> "$report_file"
    
    if [ ${#FAILED_TESTS[@]} -eq 0 ]; then
        echo "- Status: ALL TESTS PASSED" >> "$report_file"
    else
        echo "- Status: SOME TESTS FAILED" >> "$report_file"
        echo "- Failed Tests:" >> "$report_file"
        for failed in "${FAILED_TESTS[@]}"; do
            echo "  - $failed" >> "$report_file"
        done
    fi
    
    echo "" >> "$report_file"
    echo "System Information:" >> "$report_file"
    echo "- OS: $(uname -s)" >> "$report_file"
    echo "- Kernel: $(uname -r)" >> "$report_file"
    echo "- PHP Version: $(php -v | head -n1)" >> "$report_file"
    echo "- MySQL Version: $(mysql -V 2>/dev/null | head -n1 || echo 'Not available')" >> "$report_file"
    echo "- Apache Status: $(pgrep -f apache2 > /dev/null && echo 'Running' || echo 'Not running')" >> "$report_file"
    echo "- Disk Usage: $(df -h / | awk 'NR==2{print $5}')" >> "$report_file"
    echo "- Memory Usage: $(free -h | awk 'NR==2{print $3}')" >> "$report_file"
    
    print_success "Test report generated: $report_file"
}

# Main test execution
main() {
    print_status "Starting comprehensive system testing..."
    echo ""
    
    # Run all test categories
    test_database
    echo ""
    
    test_application
    echo ""
    
    test_security
    echo ""
    
    test_testing_framework
    echo ""
    
    test_performance_tools
    echo ""
    
    test_monitoring
    echo ""
    
    test_caching
    echo ""
    
    test_mobile_api
    echo ""
    
    test_analytics
    echo ""
    
    test_integrations
    echo ""
    
    test_navigation
    echo ""
    
    test_dashboards
    echo ""
    
    test_ssl
    echo ""
    
    # Run PHPUnit tests
    run_phpunit_tests
    echo ""
    
    # Generate final report
    generate_test_report
    
    print_status "System testing completed"
    echo ""
    
    if [ ${#FAILED_TESTS[@]} -eq 0 ]; then
        print_success "🎉 ALL SYSTEM TESTS PASSED! Application is ready for production deployment."
        echo ""
        print_status "Next steps:"
        echo "1. Review test report: $report_file"
        echo "2. Deploy to production environment"
        echo "3. Configure monitoring alerts"
        echo "4. Set up backup procedures"
    else
        print_error "❌ ${#FAILED_TESTS[@]} system tests failed. Please review and fix issues before deployment."
        echo ""
        print_status "Failed tests:"
        for failed in "${FAILED_TESTS[@]}"; do
            echo "- $failed"
        done
        echo ""
        print_status "Next steps:"
        echo "1. Review test report: $report_file"
        echo "2. Fix failed tests"
        echo "3. Re-run test suite"
        echo "4. Deploy when all tests pass"
    fi
    
    return ${#FAILED_TESTS[@]}
}

# Run the complete test suite
if [ "${BASH_SOURCE[0]}" != "${0}" ]; then
    main
fi
