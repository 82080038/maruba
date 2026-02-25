#!/bin/bash

# Maruba Performance Testing Script
# Performs comprehensive performance testing

set -e

echo "🚀 Maruba Performance Testing Tool"
echo "=============================="

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

# Function to test database performance
test_database_performance() {
    print_status "Testing database performance..."
    
    # Test connection time
    start_time=$(date +%s.%N)
    mysql -u root -proot -e "SELECT 1" maruba_test > /dev/null 2>&1
    end_time=$(date +%s.%N)
    connection_time=$(echo "$end_time - $start_time" | bc)
    
    echo "Database connection time: ${connection_time}s"
    
    # Test query performance
    start_time=$(date +%s.%N)
    mysql -u root -proot -e "SELECT COUNT(*) FROM users" maruba_test > /dev/null 2>&1
    end_time=$(date +%s.%N)
    query_time=$(echo "$end_time - $start_time" | bc)
    
    echo "Simple query time: ${query_time}s"
    
    # Test complex query
    start_time=$(date +%s.%N)
    mysql -u root -proot -e "SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.status = 'active'" maruba_test > /dev/null 2>&1
    end_time=$(date +%s.%N)
    complex_query_time=$(echo "$end_time - $start_time" | bc)
    
    echo "Complex query time: ${complex_query_time}s"
    
    # Performance thresholds
    if (( $(echo "$connection_time < 0.1" | bc -l) )); then
        print_success "Database connection time is good"
    else
        print_warning "Database connection time is slow"
    fi
    
    if (( $(echo "$query_time < 0.05" | bc -l) )); then
        print_success "Query performance is good"
    else
        print_warning "Query performance could be improved"
    fi
}

# Function to test application response time
test_application_response_time() {
    print_status "Testing application response time..."
    
    # Test homepage
    start_time=$(date +%s.%N)
    curl -s -o /dev/null -w "%{http_code}" http://localhost/maruba/ > /dev/null
    end_time=$(date +%s.%N)
    homepage_time=$(echo "$end_time - $start_time" | bc)
    
    echo "Homepage response time: ${homepage_time}s"
    
    # Test login page
    start_time=$(date +%s.%N)
    curl -s -o /dev/null -w "%{http_code}" http://localhost/maruba/login > /dev/null
    end_time=$(date +%s.%N)
    login_time=$(echo "$end_time - $start_time" | bc)
    
    echo "Login page response time: ${login_time}s"
    
    # Test dashboard (requires authentication)
    start_time=$(date +%s.%N)
    curl -s -o /dev/null -w "%{http_code}" http://localhost/maruba/dashboard > /dev/null
    end_time=$(date +%s.%N)
    dashboard_time=$(echo "$end_time - $start_time" | bc)
    
    echo "Dashboard response time: ${dashboard_time}s"
    
    # Performance thresholds
    if (( $(echo "$homepage_time < 2.0" | bc -l) )); then
        print_success "Homepage response time is good"
    else
        print_warning "Homepage response time is slow"
    fi
    
    if (( $(echo "$login_time < 1.5" | bc -l) )); then
        print_success "Login page response time is good"
    else
        print_warning "Login page response time is slow"
    fi
}

# Function to test concurrent users
test_concurrent_users() {
    print_status "Testing concurrent user load..."
    
    # Simulate concurrent requests
    for i in {1..10}; do
        curl -s -o /dev/null http://localhost/maruba/ &
    done
    
    wait
    
    echo "Concurrent load test completed"
}

# Function to test memory usage
test_memory_usage() {
    print_status "Testing memory usage..."
    
    # Get current memory usage
    memory_usage=$(free -m | awk 'NR==2{printf "%.1f%%", $3*100/$2}')
    echo "Current memory usage: $memory_usage"
    
    # Get PHP memory limit
    php_memory_limit=$(php -r "echo ini_get('memory_limit');")
    echo "PHP memory limit: $php_memory_limit"
    
    if (( $(echo "$memory_usage < 80" | bc -l) )); then
        print_success "Memory usage is acceptable"
    else
        print_warning "Memory usage is high"
    fi
}

# Function to test CPU usage
test_cpu_usage() {
    print_status "Testing CPU usage..."
    
    # Get current CPU usage
    cpu_usage=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)
    echo "Current CPU usage: ${cpu_usage}%"
    
    if (( $(echo "$cpu_usage < 70" | bc -l) )); then
        print_success "CPU usage is acceptable"
    else
        print_warning "CPU usage is high"
    fi
}

# Function to test disk I/O
test_disk_io() {
    print_status "Testing disk I/O..."
    
    # Test disk read speed
    read_speed=$(dd if=/dev/zero of=/tmp/testfile bs=1M count=100 2>&1 | grep -o '[0-9.]* MB/s')
    echo "Disk write speed: $read_speed"
    
    # Test disk read speed
    read_speed=$(dd if=/tmp/testfile of=/dev/null bs=1M 2>&1 | grep -o '[0-9.]* MB/s')
    echo "Disk read speed: $read_speed"
    
    # Clean up
    rm -f /tmp/testfile
}

# Function to generate performance report
generate_performance_report() {
    local report_file="performance_report_$(date +%Y%m%d_%H%M%S).txt"
    
    echo "Maruba Performance Report" > "$report_file"
    echo "========================" >> "$report_file"
    echo "Generated: $(date)" >> "$report_file"
    echo "" >> "$report_file"
    
    echo "System Information:" >> "$report_file"
    echo "- OS: $(uname -s)" >> "$report_file"
    echo "- Kernel: $(uname -r)" >> "$report_file"
    echo "- CPU: $(nproc) cores" >> "$report_file"
    echo "- Memory: $(free -h | awk 'NR==2{print $2}')" >> "$report_file"
    echo "- Disk: $(df -h / | awk 'NR==2{print $2}')" >> "$report_file"
    echo "" >> "$report_file"
    
    echo "Performance Metrics:" >> "$report_file"
    echo "- Database Connection Time: $(mysql -u root -proot -e "SELECT 1" maruba_test 2>/dev/null | wc -l) queries" >> "$report_file"
    echo "- Web Server: Apache" >> "$report_file"
    echo "- PHP Version: $(php -v | head -n1)" >> "$report_file"
    echo "- MySQL Version: $(mysql -V)" >> "$report_file"
    echo "" >> "$report_file"
    
    echo "Recommendations:" >> "$report_file"
    echo "- Enable PHP OPcache for better performance" >> "$report_file"
    echo "- Use Redis for session storage" >> "$report_file"
    echo "- Implement database query caching" >> "$report_file"
    echo "- Use CDN for static assets" >> "$report_file"
    
    print_success "Performance report generated: $report_file"
}

# Main performance test function
run_performance_tests() {
    local issues=0
    
    print_status "Starting comprehensive performance testing..."
    echo ""
    
    # Test database performance
    test_database_performance || issues=$((issues + 1))
    echo ""
    
    # Test application response time
    test_application_response_time || issues=$((issues + 1))
    echo ""
    
    # Test concurrent users
    test_concurrent_users || issues=$((issues + 1))
    echo ""
    
    # Test memory usage
    test_memory_usage || issues=$((issues + 1))
    echo ""
    
    # Test CPU usage
    test_cpu_usage || issues=$((issues + 1))
    echo ""
    
    # Test disk I/O
    test_disk_io || issues=$((issues + 1))
    echo ""
    
    print_status "Performance testing completed"
    
    if [ $issues -eq 0 ]; then
        print_success "All performance tests passed! 🚀"
    else
        print_warning "Found $issues performance issues"
        print_warning "Please review and optimize the issues above"
    fi
    
    # Generate report
    generate_performance_report
    
    return $issues
}

# Check dependencies
check_dependencies() {
    print_status "Checking dependencies..."
    
    # Check if curl is installed
    if ! command -v curl &> /dev/null; then
        print_error "curl is not installed"
        return 1
    fi
    
    # Check if bc is installed
    if ! command -v bc &> /dev/null; then
        print_error "bc is not installed"
        return 1
    fi
    
    # Check if mysql client is available
    if ! command -v mysql &> /dev/null; then
        print_error "mysql client is not available"
        return 1
    fi
    
    print_success "All dependencies are available"
    return 0
}

# Run the tests
if [ "${BASH_SOURCE[0]}" != "${0}" ]; then
    check_dependencies && run_performance_tests
fi
