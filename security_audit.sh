#!/bin/bash

# Maruba Security Audit Script
# Performs comprehensive security checks on the application

set -e

echo "🔒 Maruba Security Audit Tool"
echo "=========================="

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
    -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    -e "${RED}[ERROR]${NC} $1"
}

print_critical() {
    -e "${RED}[CRITICAL]${NC} $1"
}

# Function to check file permissions
check_file_permissions() {
    local file=$1
    local expected_perm=$2
    
    if [ ! -f "$file" ]; then
        print_error "File not found: $file"
        return 1
    fi
    
    current_perm=$(stat -c "%a" "$file")
    
    if [ "$current_perm" = "$expected_perm" ]; then
        print_success "File permissions correct: $file ($current_perm)"
    else
        print_warning "File permissions incorrect: $file ($current_perm), expected: $expected_perm"
        return 1
    fi
}

# Function to check directory permissions
check_dir_permissions() {
    local dir=$1
    local expected_perm=$2
    
    if [ ! -d "$dir" ]; then
        print_error "Directory not found: $dir"
        return 1
    fi
    
    current_perm=$(stat -c "%a" "$dir")
    
    if [ "$current_perm" = "$expected_perm" ]; then
        print_success "Directory permissions correct: $dir ($current_perm)"
    else
        print_warning "Directory permissions incorrect: $dir ($current_perm), expected: $expected_perm"
        return 1
    fi
}

# Function to check for sensitive files
check_sensitive_files() {
    print_status "Checking for sensitive files..."
    
    local sensitive_files=(
        ".env"
        "config.php"
        "database.php"
        "*.key"
        "*.pem"
        "*.crt"
        "sudo_pass.txt"
        "maruba_*"
    )
    
    local found_issues=0
    
    for pattern in "${sensitive_files[@]}"; do
        if ls $pattern 2>/dev/null; then
            print_warning "Sensitive files found: $pattern"
            found_issues=$((found_issues + 1))
        fi
    done
    
    if [ $found_issues -eq 0 ]; then
        print_success "No sensitive files found in root directory"
    else
        print_error "Found $found_issues sensitive file patterns"
    fi
}

# Function to check for exposed configuration files
check_exposed_configs() {
    print_status "Checking for exposed configuration files..."
    
    local config_files=(
        "App/src/Database.php"
        "App/src/bootstrap.php"
        ".env"
        "config/"
    )
    
    local found_issues=0
    
    for config_file in "${config_files[@]}"; do
        if [ -f "$config_file" ]; then
            # Check if file is web-accessible
            if [[ "$config_file" == *"App/public"* ]] || [[ "$config_file" == *"public"* ]]; then
                print_critical "Configuration file is web-accessible: $config_file"
                found_issues=$((found_issues + 1))
            else
                print_warning "Configuration file found: $config_file"
                found_issues=$((found_issues + 1))
            fi
        fi
    done
    
    if [ $found_issues -eq 0 ]; then
        print_success "No exposed configuration files found"
    else
        print_error "Found $found_issues configuration file issues"
    fi
}

# Function to check for debug information in production
check_debug_info() {
    print_status "Checking for debug information..."
    
    local debug_patterns=(
        "var_dump("
        "print_r("
        "error_reporting("
        "ini_set('display_errors'"
        "console.log"
        "console.debug"
        "alert("
        "document.write("
    )
    
    local found_issues=0
    
    for pattern in "${debug_patterns[@]}"; do
        if grep -r "$pattern" App/src/ 2>/dev/null; then
            print_warning "Debug code found: $pattern"
            found_issues=$((found_issues + 1))
        fi
    done
    
    if [ $found_issues -eq 0 ]; then
        print_success "No debug information found"
    else
        print_error "Found $found_issues debug code patterns"
    fi
}

# Function to check for hardcoded credentials
check_hardcoded_credentials() {
    print_status "Checking for hardcoded credentials..."
    
    local credential_patterns=(
        "password.*=.*['\"]"
        "secret.*=.*['\"]"
        "key.*=.*['\"]"
        "token.*=.*['\"]"
        "api_key.*=.*['\"]"
        "db_password.*=.*['\"]"
        "mysql.*password.*=.*['\"]"
    )
    
    local found_issues=0
    
    for pattern in "${credential_patterns[@]}"; do
        if grep -r -i "$pattern" App/src/ 2>/dev/null; then
            print_warning "Potential hardcoded credentials found: $pattern"
            found_issues=$((found_issues + 1))
        fi
    done
    
    if [ $found_issues -eq 0 ]; then
        print_success "No hardcoded credentials found"
    else
        print_error "Found $found_issues potential hardcoded credentials"
    fi
}

# Function to check for SQL injection vulnerabilities
check_sql_injection() {
    print_status "Checking for SQL injection vulnerabilities..."
    
    local vulnerable_patterns=(
        "mysql_query.*\$.*\$_"
        "mysqli_query.*\$.*\$_"
        "PDO.*query.*\$.*\$_"
        "SELECT.*FROM.*\$.*\$_"
        "INSERT.*INTO.*\$.*\$_"
        "UPDATE.*SET.*\$.*\$_"
        "DELETE.*FROM.*\$.*\$_"
        "DROP.*TABLE.*\$.*\$_"
    )
    
    local found_issues=0
    
    for pattern in "${vulnerable_patterns[@]}"; do
        if grep -r "$pattern" App/src/ 2>/dev/null; then
            print_warning "Potential SQL injection vulnerability: $pattern"
            found_issues=$((found_issues + 1))
        fi
    done
    
    # Check for prepared statements
    local safe_patterns=(
        "prepare("
        "execute("
        "bindParam("
        "bindValue("
    )
    
    local safe_count=0
    for pattern in "${safe_patterns[@]}"; do
        if grep -r "$pattern" App/src/ 2>/dev/null; then
            safe_count=$((safe_count + 1))
        fi
    done
    
    if [ $found_issues -eq 0 ] && [ $safe_count -gt 0 ]; then
        print_success "SQL injection protection in place (found $safe_count safe patterns)"
    elif [ $found_issues -eq 0 ]; then
        print_success "No SQL injection vulnerabilities found"
    else
        print_error "Found $found_issues potential SQL injection vulnerabilities"
    fi
}

# Function to check for XSS vulnerabilities
check_xss_vulnerabilities() {
    print_status "Checking for XSS vulnerabilities..."
    
    local vulnerable_patterns=(
        "echo.*\$.*"
        "print.*\$.*"
        "document\.write.*\$.*"
        "innerHTML.*\$.*"
        "outerHTML.*\$.*"
        "eval.*\$.*"
        "exec.*\$.*"
        "system.*\$.*"
        "shell_exec.*\$.*"
    )
    
    local found_issues=0
    
    for pattern in "${vulnerable_patterns[@]}"; do
        if grep -r "$pattern" App/src/ 2>/dev/null; then
            print_warning "Potential XSS vulnerability: $pattern"
            found_issues=$((found_issues + 1))
        fi
    done
    
    # Check for output encoding
    if grep -r "htmlspecialchars\|htmlentities\|filter_var.*FILTER_SANITIZE" App/src/ 2>/dev/null; then
        print_success "XSS protection measures in place"
    else
        print_warning "No XSS protection measures found"
    fi
    
    if [ $found_issues -eq 0 ]; then
        print_success "No XSS vulnerabilities found"
    else
        print_error "Found $found_issues potential XSS vulnerabilities"
    fi
}

# Function to check file upload security
check_file_upload_security() {
    print_status "Checking file upload security..."
    
    local upload_dirs=(
        "App/public/uploads/"
        "uploads/"
        "temp/"
        "storage/"
    )
    
    local found_issues=0
    
    for dir in "${upload_dirs[@]}"; do
        if [ -d "$dir" ]; then
            # Check if directory is web-accessible
            if [[ "$dir" == *"public"* ]] || [[ "$dir" == *"uploads"* ]]; then
                print_warning "Upload directory is web-accessible: $dir"
                found_issues=$((found_issues + 1))
            fi
            
            # Check for executable files
            if find "$dir" -type f -executable 2>/dev/null | head -5; then
                print_warning "Executable files found in upload directory: $dir"
                found_issues=$((found_issues + 1))
            fi
            
            # Check for dangerous file types
            if find "$dir" -name "*.php" -o -name "*.sh" -o -name "*.exe" 2>/dev/null | head -5; then
                print_critical "Dangerous files found in upload directory: $dir"
                found_issues=$((found_issues + 1))
            fi
        fi
    done
    
    if [ $found_issues -eq 0 ]; then
        print_success "File upload security looks good"
    else
        print_error "Found $found_issues file upload security issues"
    fi
}

# Function to check session security
check_session_security() {
    print_status "Checking session security..."
    
    # Check session configuration
    if [ -f "/etc/php/*/cli/php.ini" ]; then
        local session_save_path=$(grep "session.save_path" /etc/php/*/cli/php.ini | cut -d= -f2 | tr -d ' ')
        local session_cookie_httponly=$(grep "session.cookie_httponly" /etc/php/*/cli/php.ini | cut -d= -f2 | tr -d ' ')
        local session_use_strict_mode=$(grep "session.use_strict_mode" /etc/php/*/cli/php.ini | cut -d -f2 | tr -d ' ')
        
        if [ "$session_cookie_httponly" = "1" ]; then
            print_success "Session cookie HTTPOnly enabled"
        else
            print_warning "Session cookie HTTPOnly not enabled"
        fi
        
        if [ "$session_use_strict_mode" = "1" ]; then
            print_success "Session strict mode enabled"
        else
            print_warning "Session strict mode not enabled"
        fi
    else
        print_warning "PHP configuration not found"
    fi
    
    # Check for session fixation protection in code
    if grep -r "session_regenerate_id" App/src/ 2>/dev/null; then
        print_success "Session fixation protection found"
    else
        print_warning "Session fixation protection not found"
    fi
}

# Function to check CSRF protection
check_csrf_protection() {
    print_status "Checking CSRF protection..."
    
    local csrf_patterns=(
        "csrf_token"
        "_token"
        "session.*token"
        "bin2hex.*random_bytes"
    )
    
    local found_protection=0
    
    for pattern in "${csrf_patterns[@]}"; do
        if grep -r "$pattern" App/src/ 2>/dev/null; then
            found_protection=$((found_protection + 1))
        fi
    done
    
    if [ $found_protection -ge 2 ]; then
        print_success "CSRF protection measures found"
    else
        print_warning "CSRF protection may be incomplete"
    fi
}

# Function to check password security
check_password_security() {
    print_status "Checking password security..."
    
    # Check for weak password hashing
    if grep -r "md5\|sha1\|crypt.*\$" App/src/ 2>/dev/null; then
        print_warning "Weak password hashing found (MD5/SHA1/crypt)"
    fi
    
    # Check for strong password hashing
    if grep -r "password_hash.*PASSWORD_BCRYPT\|password_hash.*PASSWORD_ARGON2ID" App/src/ 2>/dev/null; then
        print_success "Strong password hashing found"
    else
        print_warning "Strong password hashing not found"
    fi
    
    # Check for password policies
    if grep -r "min_length\|max_length\|preg_match.*password" App/src/ 2>/dev/null; then
        print_success "Password validation policies found"
    else
        print_warning "Password validation policies not found"
    fi
}

# Function to check for security headers
check_security_headers() {
    print_status "Checking security headers..."
    
    local header_files=(
        "App/src/Controllers/"
        "App/src/Middleware/"
        "App/src/bootstrap.php"
    )
    
    local security_headers=(
        "X-Frame-Options"
        "X-Content-Type-Options"
        "X-XSS-Protection"
        "Strict-Transport-Security"
        "Content-Security-Policy"
    )
    
    local found_headers=0
    
    for file in "${header_files[@]}"; do
        if [ -f "$file" ]; then
            for header in "${security_headers[@]}"; do
                if grep -r "$header" "$file" 2>/dev/null; then
                    found_headers=$((found_headers + 1))
                fi
            done
        fi
    done
    
    if [ $found_headers -ge 4 ]; then
        print_success "Security headers implemented ($found_headers/6)"
    else
        print_warning "Security headers may be incomplete ($found_headers/6)"
    fi
}

# Function to generate security report
generate_security_report() {
    local report_file="security_report_$(date +%Y%m%d_%H%M%S).txt"
    
    echo "Maruba Security Report" > "$report_file"
    echo "====================" >> "$report_file"
    echo "Generated: $(date)" >> "$report_file"
    echo "" >> "$report_file"
    
    echo "File Permissions:" >> "$report_file"
    ls -la App/src/ >> "$report_file" 2>/dev/null || echo "No App/src directory found" >> "$report_file"
    echo "" >> "$report_file"
    
    echo "Sensitive Files:" >> "$report_file"
    find . -name "*.env" -o -name "*.key" -o -name "*.pem" 2>/dev/null | head -10 >> "$report_file" || echo "No sensitive files found" >> "$report_file"
    echo "" >> "$report_file"
    
    echo "Security Configuration:" >> "$report_file"
    echo "- CSRF Protection: $(grep -r "csrf" App/src/ | wc -l)" >> "$report_file"
    echo "- Session Security: $(grep -r "session_regenerate_id" App/src/ | wc -l)" >> "$report_file"
    echo "- Password Hashing: $(grep -r "PASSWORD_ARGON2ID\|PASSWORD_BCRYPT" App/src/ | wc -l)" >> "$report_file"
    echo "- XSS Protection: $(grep -r "htmlspecialchars\|htmlentities" App/src/ | wc -l)" >> "$report_file"
    echo "" >> "$report_file"
    
    echo "Security Headers:" >> "$report_file"
    grep -r "X-Frame-Options\|X-Content-Type-Options\|X-XSS-Protection" App/src/ | wc -l >> "$report_file" || echo "No security headers found" >> "$report_file"
    
    print_success "Security report generated: $report_file"
}

# Main audit function
run_security_audit() {
    local issues=0
    
    print_status "Starting comprehensive security audit..."
    echo ""
    
    # Check file permissions
    check_file_permissions ".env" "600" || issues=$((issues + 1))
    check_dir_permissions "App/src" "755" || issues=$((issues + 1))
    check_dir_permissions "App/public" "755" || issues=$((issues + 1))
    
    # Check for sensitive files
    check_sensitive_files || issues=$((issues + 1))
    
    # Check for exposed configurations
    check_exposed_configs || issues=$((issues + 1))
    
    # Check for debug information
    check_debug_info || issues=$((issues + 1))
    
    # Check for hardcoded credentials
    check_hardcoded_credentials || issues=$((issues + 1))
    
    # Check SQL injection protection
    check_sql_injection || issues=$((issues + 1))
    
    # Check XSS protection
    check_xss_vulnerabilities || issues=$((issues + 1))
    
    # Check file upload security
    check_file_upload_security || issues=$((issues + 1))
    
    # Check session security
    check_session_security || issues=$((issues + 1))
    
    # Check CSRF protection
    check_csrf_protection || issues=$((issues + 1))
    
    # Check password security
    check_password_security || issues=$((issues + 1))
    
    # Check security headers
    check_security_headers || issues=$((issues + 1))
    
    echo ""
    print_status "Security audit completed"
    
    if [ $issues -eq 0 ]; then
        print_success "No critical security issues found! 🎉"
    else
        print_warning "Found $issues potential security issues"
        print_warning "Please review and fix the issues above"
    fi
    
    # Generate report
    generate_security_report
    
    return $issues
}

# Run the audit
if [ "${BASH_SOURCE[0]}" != "${0}" ]; then
    run_security_audit
fi
