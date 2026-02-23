#!/bin/bash

# Maruba Load Testing Script
# Performs stress testing with multiple scenarios

set -e

echo "⚡ Maruba Load Testing Tool"
echo "========================"

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

# Configuration
BASE_URL="http://localhost/maruba"
CONCURRENT_USERS=10
REQUESTS_PER_USER=50
TEST_DURATION=60

# Function to install Apache Bench (ab)
install_ab() {
    if ! command -v ab &> /dev/null; then
        print_status "Installing Apache Bench..."
        sudo apt update && sudo apt install -y apache2-utils
    fi
}

# Function to install Siege
install_siege() {
    if ! command -v siege &> /dev/null; then
        print_status "Installing Siege..."
        sudo apt update && sudo apt install -y siege
    fi
}

# Function to test homepage load
test_homepage_load() {
    print_status "Testing homepage load..."
    
    ab -n $((CONCURRENT_USERS * REQUESTS_PER_USER)) -c $CONCURRENT_USERS "$BASE_URL/" > homepage_load_test.log
    
    # Parse results
    requests_per_second=$(grep "Requests per second" homepage_load_test.log | awk '{print $4}')
    time_per_request=$(grep "Time per request" homepage_load_test.log | awk '{print $4}')
    failed_requests=$(grep "Failed requests" homepage_load_test.log | awk '{print $3}')
    
    echo "Homepage Load Test Results:"
    echo "- Requests per second: $requests_per_second"
    echo "- Time per request: $time_per_request"
    echo "- Failed requests: $failed_requests"
    
    if [ "$failed_requests" = "0" ]; then
        print_success "Homepage load test passed"
    else
        print_warning "Homepage load test had failures"
    fi
}

# Function to test login load
test_login_load() {
    print_status "Testing login load..."
    
    # Create siege configuration
    cat > login_urls.txt << EOF
${BASE_URL}/login POST username=test_admin&password=password
EOF
    
    siege -c $CONCURRENT_USERS -t $TEST_DURATION -f login_urls.txt > login_load_test.log
    
    # Parse results
    availability=$(grep "Availability" login_load_test.log | awk '{print $2}')
    response_time=$(grep "Response time" login_load_test.log | awk '{print $3}')
    transactions=$(grep "Transactions" login_load_test.log | awk '{print $2}')
    
    echo "Login Load Test Results:"
    echo "- Availability: $availability"
    echo "- Response time: $response_time"
    echo "- Total transactions: $transactions"
    
    if [ "$availability" = "100.00%" ]; then
        print_success "Login load test passed"
    else
        print_warning "Login load test had availability issues"
    fi
}

# Function to test dashboard load
test_dashboard_load() {
    print_status "Testing dashboard load..."
    
    # Create siege configuration for dashboard
    cat > dashboard_urls.txt << EOF
${BASE_URL}/dashboard
EOF
    
    siege -c $CONCURRENT_USERS -t $TEST_DURATION -f dashboard_urls.txt > dashboard_load_test.log
    
    # Parse results
    availability=$(grep "Availability" dashboard_load_test.log | awk '{print $2}')
    response_time=$(grep "Response time" dashboard_load_test.log | awk '{print $3}')
    transactions=$(grep "Transactions" dashboard_load_test.log | awk '{print $2}')
    
    echo "Dashboard Load Test Results:"
    echo "- Availability: $availability"
    echo "- Response time: $response_time"
    echo "- Total transactions: $transactions"
    
    if [ "$availability" = "100.00%" ]; then
        print_success "Dashboard load test passed"
    else
        print_warning "Dashboard load test had availability issues"
    fi
}

# Function to test API load
test_api_load() {
    print_status "Testing API load..."
    
    # Create siege configuration for API endpoints
    cat > api_urls.txt << EOF
${BASE_URL}/api/users
${BASE_URL}/api/members
${BASE_URL}/api/loans
${BASE_URL}/api/products
EOF
    
    siege -c $CONCURRENT_USERS -t $TEST_DURATION -f api_urls.txt > api_load_test.log
    
    # Parse results
    availability=$(grep "Availability" api_load_test.log | awk '{print $2}')
    response_time=$(grep "Response time" api_load_test.log | awk '{print $3}')
    transactions=$(grep "Transactions" api_load_test.log | awk '{print $2}')
    
    echo "API Load Test Results:"
    echo "- Availability: $availability"
    echo "- Response time: $response_time"
    echo "- Total transactions: $transactions"
    
    if [ "$availability" = "100.00%" ]; then
        print_success "API load test passed"
    else
        print_warning "API load test had availability issues"
    fi
}

# Function to test concurrent users
test_concurrent_users() {
    print_status "Testing concurrent users..."
    
    # Test with increasing concurrent users
    for users in 5 10 20 50; do
        print_status "Testing with $users concurrent users..."
        
        ab -n $((users * 10)) -c $users "$BASE_URL/" > concurrent_test_${users}.log
        
        # Check if test passed
        failed=$(grep "Failed requests" concurrent_test_${users}.log | awk '{print $3}')
        if [ "$failed" = "0" ]; then
            print_success "$users concurrent users: PASSED"
        else
            print_warning "$users concurrent users: $failed failures"
        fi
    done
}

# Function to test memory usage under load
test_memory_usage() {
    print_status "Testing memory usage under load..."
    
    # Get initial memory usage
    initial_memory=$(free -m | awk 'NR==2{printf "%.1f", $3*100/$2}')
    
    # Run load test
    ab -n 1000 -c 50 "$BASE_URL/" > memory_test.log &
    ab_pid=$!
    
    # Monitor memory during test
    max_memory=0
    for i in {1..30}; do
        current_memory=$(free -m | awk 'NR==2{printf "%.1f", $3*100/$2}')
        max_memory=$(echo "$max_memory $current_memory" | awk '{if ($1 > $2) print $1; else print $2}')
        sleep 1
    done
    
    wait $ab_pid
    
    echo "Memory Usage Test Results:"
    echo "- Initial memory: ${initial_memory}%"
    echo "- Peak memory: ${max_memory}%"
    
    if (( $(echo "$max_memory < 80" | bc -l) )); then
        print_success "Memory usage is acceptable"
    else
        print_warning "Memory usage is high"
    fi
}

# Function to test CPU usage under load
test_cpu_usage() {
    print_status "Testing CPU usage under load..."
    
    # Get initial CPU usage
    initial_cpu=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)
    
    # Run load test
    ab -n 1000 -c 50 "$BASE_URL/" > cpu_test.log &
    ab_pid=$!
    
    # Monitor CPU during test
    max_cpu=0
    for i in {1..30}; do
        current_cpu=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)
        max_cpu=$(echo "$max_cpu $current_cpu" | awk '{if ($1 > $2) print $1; else print $2}')
        sleep 1
    done
    
    wait $ab_pid
    
    echo "CPU Usage Test Results:"
    echo "- Initial CPU: ${initial_cpu}%"
    echo "- Peak CPU: ${max_cpu}%"
    
    if (( $(echo "$max_cpu < 70" | bc -l) )); then
        print_success "CPU usage is acceptable"
    else
        print_warning "CPU usage is high"
    fi
}

# Function to generate load test report
generate_load_test_report() {
    local report_file="load_test_report_$(date +%Y%m%d_%H%M%S).txt"
    
    echo "Maruba Load Test Report" > "$report_file"
    echo "=====================" >> "$report_file"
    echo "Generated: $(date)" >> "$report_file"
    echo "" >> "$report_file"
    
    echo "Test Configuration:" >> "$report_file"
    echo "- Base URL: $BASE_URL" >> "$report_file"
    echo "- Concurrent Users: $CONCURRENT_USERS" >> "$report_file"
    echo "- Requests Per User: $REQUESTS_PER_USER" >> "$report_file"
    echo "- Test Duration: ${TEST_DURATION}s" >> "$report_file"
    echo "" >> "$report_file"
    
    echo "Test Results:" >> "$report_file"
    echo "- Homepage Load: $(grep -q "PASSED" <<< "$(tail -10)" && echo "PASSED" || echo "FAILED")" >> "$report_file"
    echo "- Login Load: $(grep -q "PASSED" <<< "$(tail -10)" && echo "PASSED" || echo "FAILED")" >> "$report_file"
    echo "- Dashboard Load: $(grep -q "PASSED" <<< "$(tail -10)" && echo "PASSED" || echo "FAILED")" >> "$report_file"
    echo "- API Load: $(grep -q "PASSED" <<< "$(tail -10)" && echo "PASSED" || echo "FAILED")" >> "$report_file"
    echo "" >> "$report_file"
    
    echo "Performance Metrics:" >> "$report_file"
    echo "- Memory Usage: $(grep "Peak memory" <<< "$(tail -10)" | awk '{print $3}')" >> "$report_file"
    echo "- CPU Usage: $(grep "Peak CPU" <<< "$(tail -10)" | awk '{print $3}')" >> "$report_file"
    echo "" >> "$report_file"
    
    echo "Recommendations:" >> "$report_file"
    echo "- Enable Redis caching for better performance" >> "$report_file"
    echo "- Optimize database queries" >> "$report_file"
    echo "- Use CDN for static assets" >> "$report_file"
    echo "- Implement connection pooling" >> "$report_file"
    echo "- Consider horizontal scaling for high traffic" >> "$report_file"
    
    print_success "Load test report generated: $report_file"
}

# Main load test function
run_load_tests() {
    local issues=0
    
    print_status "Starting comprehensive load testing..."
    echo ""
    
    # Install dependencies
    install_ab || issues=$((issues + 1))
    install_siege || issues=$((issues + 1))
    echo ""
    
    # Run load tests
    test_homepage_load || issues=$((issues + 1))
    echo ""
    
    test_login_load || issues=$((issues + 1))
    echo ""
    
    test_dashboard_load || issues=$((issues + 1))
    echo ""
    
    test_api_load || issues=$((issues + 1))
    echo ""
    
    test_concurrent_users || issues=$((issues + 1))
    echo ""
    
    test_memory_usage || issues=$((issues + 1))
    echo ""
    
    test_cpu_usage || issues=$((issues + 1))
    echo ""
    
    print_status "Load testing completed"
    
    if [ $issues -eq 0 ]; then
        print_success "All load tests passed! ⚡"
    else
        print_warning "Found $issues load test issues"
        print_warning "Please review and optimize the issues above"
    fi
    
    # Generate report
    generate_load_test_report
    
    return $issues
}

# Run the tests
if [ "${BASH_SOURCE[0]}" != "${0}" ]; then
    run_load_tests
fi
