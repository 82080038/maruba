#!/bin/bash

# Maruba Monitoring Setup Script
# Sets up comprehensive monitoring for the Maruba application

set -e

echo "📊 Maruba Monitoring Setup"
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

print_header() {
    echo -e "${BLUE}=== $1 ===${NC}"
}

# Configuration
MONITORING_DIR="/opt/lampp/htdocs/maruba/monitoring"
SCRIPTS_DIR="$MONITORING_DIR/scripts"
LOG_DIR="$MONITORING_DIR/logs"
APP_LOG_DIR="/opt/lampp/logs"

# Function to create directory structure
create_directory_structure() {
    print_header "Creating Directory Structure"
    
    mkdir -p "$MONITORING_DIR"/{scripts,logs,dashboard,config}
    mkdir -p "$SCRIPTS_DIR"
    mkdir -p "$LOG_DIR"
    
    print_success "Directory structure created"
}

# Function to create application monitor script
create_app_monitor() {
    print_header "Creating Application Monitor Script"
    
    cat > "$SCRIPTS_DIR/app_monitor.sh" << 'EOF'
#!/bin/bash

# Maruba Application Monitor Script
# Monitors application health and performance

LOG_FILE="/opt/lampp/htdocs/maruba/monitoring/logs/app_monitor.log"
ALERT_FILE="/opt/lampp/htdocs/maruba/monitoring/logs/alerts.log"

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Function to send alert
send_alert() {
    local message="$1"
    local severity="$2"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [$severity] $message" >> "$ALERT_FILE"
}

# Function to check application health
check_app_health() {
    local url="http://localhost/maruba"
    local response_time=$(curl -o /dev/null -s -w '%{time_total}' "$url" 2>/dev/null)
    local http_code=$(curl -o /dev/null -s -w '%{http_code}' "$url" 2>/dev/null)
    
    if [ "$http_code" = "200" ]; then
        log_message "Application health: OK (response time: ${response_time}s)"
        
        # Check if response time is too high
        if (( $(echo "$response_time > 5.0" | bc -l) )); then
            send_alert "Application response time is high: ${response_time}s" "WARNING"
        fi
    else
        log_message "Application health: FAILED (HTTP $http_code)"
        send_alert "Application is not responding (HTTP $http_code)" "CRITICAL"
    fi
}

# Function to check database connectivity
check_database() {
    if mysql -u root -proot -e "SELECT 1" maruba > /dev/null 2>&1; then
        log_message "Database connectivity: OK"
    else
        log_message "Database connectivity: FAILED"
        send_alert "Database connection failed" "CRITICAL"
    fi
}

# Function to check disk space
check_disk_space() {
    local disk_usage=$(df / | awk 'NR==2{print $5}' | sed 's/%//')
    
    if [ "$disk_usage" -lt 90 ]; then
        log_message "Disk space: OK (${disk_usage}%)"
    else
        log_message "Disk space: CRITICAL (${disk_usage}%)"
        send_alert "Disk usage is critical: ${disk_usage}%" "CRITICAL"
    fi
}

# Function to check memory usage
check_memory() {
    local memory_usage=$(free | awk 'NR==2{printf "%.0f", $3*100/$2}')
    
    if [ "$memory_usage" -lt 85 ]; then
        log_message "Memory usage: OK (${memory_usage}%)"
    else
        log_message "Memory usage: WARNING (${memory_usage}%)"
        send_alert "Memory usage is high: ${memory_usage}%" "WARNING"
    fi
}

# Function to check Apache status
check_apache() {
    if pgrep -f apache2 > /dev/null || pgrep -f httpd > /dev/null; then
        log_message "Apache status: RUNNING"
    else
        log_message "Apache status: STOPPED"
        send_alert "Apache web server is not running" "CRITICAL"
    fi
}

# Function to check MySQL status
check_mysql() {
    if pgrep -f mysqld > /dev/null; then
        log_message "MySQL status: RUNNING"
    else
        log_message "MySQL status: STOPPED"
        send_alert "MySQL database server is not running" "CRITICAL"
    fi
}

# Function to get application metrics
get_app_metrics() {
    local active_users=$(mysql -u root -proot maruba -e "SELECT COUNT(*) FROM users WHERE status='active'" 2>/dev/null | awk 'NR==2' || echo "0")
    local total_members=$(mysql -u root -proot maruba -e "SELECT COUNT(*) FROM members" 2>/dev/null | awk 'NR==2' || echo "0")
    local active_loans=$(mysql -u root -proot maruba -e "SELECT COUNT(*) FROM loans WHERE status='disbursed'" 2>/dev/null | awk 'NR==2' || echo "0")
    
    log_message "Active users: $active_users"
    log_message "Total members: $total_members"
    log_message "Active loans: $active_loans"
}

# Main monitoring function
main() {
    log_message "Starting application monitoring"
    
    check_app_health
    check_database
    check_disk_space
    check_memory
    check_apache
    check_mysql
    get_app_metrics
    
    log_message "Application monitoring completed"
}

# Run monitoring
main
EOF
    
    chmod +x "$SCRIPTS_DIR/app_monitor.sh"
    print_success "Application monitor script created"
}

# Function to create performance monitor script
create_performance_monitor() {
    print_header "Creating Performance Monitor Script"
    
    cat > "$SCRIPTS_DIR/performance_monitor.sh" << 'EOF'
#!/bin/bash

# Maruba Performance Monitor Script
# Monitors system performance metrics

LOG_FILE="/opt/lampp/htdocs/maruba/monitoring/logs/performance_monitor.log"

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Function to get CPU usage
get_cpu_usage() {
    local cpu_usage=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)
    log_message "CPU usage: ${cpu_usage}%"
}

# Function to get memory usage
get_memory_usage() {
    local memory_usage=$(free | awk 'NR==2{printf "%.1f", $3*100/$2}')
    log_message "Memory usage: ${memory_usage}%"
}

# Function to get disk I/O
get_disk_io() {
    local disk_io=$(iostat -x 1 1 | awk 'NR==4{print $10}' 2>/dev/null || echo "0")
    log_message "Disk I/O wait: ${disk_io}%"
}

# Function to get network stats
get_network_stats() {
    local network_rx=$(cat /proc/net/dev | grep eth0 | awk '{print $2}' 2>/dev/null || echo "0")
    local network_tx=$(cat /proc/net/dev | grep eth0 | awk '{print $10}' 2>/dev/null || echo "0")
    log_message "Network RX: ${network_rx} bytes"
    log_message "Network TX: ${network_tx} bytes"
}

# Function to get Apache stats
get_apache_stats() {
    local apache_processes=$(pgrep -f apache2 | wc -l)
    local apache_memory=$(ps aux | grep apache2 | awk '{sum+=$6} END {print sum/1024}' 2>/dev/null || echo "0")
    
    log_message "Apache processes: $apache_processes"
    log_message "Apache memory: ${apache_memory}MB"
}

# Function to get MySQL stats
get_mysql_stats() {
    local mysql_connections=$(mysql -u root -proot -e "SHOW STATUS LIKE 'Threads_connected'" 2>/dev/null | awk 'NR==2{print $2}' || echo "0")
    local mysql_queries=$(mysql -u root -proot -e "SHOW STATUS LIKE 'Questions'" 2>/dev/null | awk 'NR==2{print $2}' || echo "0")
    
    log_message "MySQL connections: $mysql_connections"
    log_message "MySQL queries: $mysql_queries"
}

# Main performance monitoring function
main() {
    log_message "Starting performance monitoring"
    
    get_cpu_usage
    get_memory_usage
    get_disk_io
    get_network_stats
    get_apache_stats
    get_mysql_stats
    
    log_message "Performance monitoring completed"
}

# Run performance monitoring
main
EOF
    
    chmod +x "$SCRIPTS_DIR/performance_monitor.sh"
    print_success "Performance monitor script created"
}

# Function to create log monitor script
create_log_monitor() {
    print_header "Creating Log Monitor Script"
    
    cat > "$SCRIPTS_DIR/log_monitor.sh" << 'EOF'
#!/bin/bash

# Maruba Log Monitor Script
# Monitors application and system logs for errors

LOG_FILE="/opt/lampp/htdocs/maruba/monitoring/logs/log_monitor.log"
ALERT_FILE="/opt/lampp/htdocs/maruba/monitoring/logs/alerts.log"

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Function to send alert
send_alert() {
    local message="$1"
    local severity="$2"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [$severity] $message" >> "$ALERT_FILE"
}

# Function to monitor Apache error log
monitor_apache_logs() {
    local error_count=$(tail -100 /opt/lampp/logs/error_log 2>/dev/null | grep -ci "error\|fatal\|crit" || echo "0")
    
    if [ "$error_count" -gt 5 ]; then
        log_message "Apache errors: HIGH ($error_count in last 100 lines)"
        send_alert "High number of Apache errors: $error_count" "WARNING"
    else
        log_message "Apache errors: OK ($error_count in last 100 lines)"
    fi
}

# Function to monitor MySQL error log
monitor_mysql_logs() {
    local error_count=$(tail -100 /opt/lampp/logs/mysql_error.log 2>/dev/null | grep -ci "error\|warning" || echo "0")
    
    if [ "$error_count" -gt 3 ]; then
        log_message "MySQL errors: HIGH ($error_count in last 100 lines)"
        send_alert "High number of MySQL errors: $error_count" "WARNING"
    else
        log_message "MySQL errors: OK ($error_count in last 100 lines)"
    fi
}

# Function to monitor application logs
monitor_app_logs() {
    local app_log="/opt/lampp/htdocs/maruba/logs/app.log"
    
    if [ -f "$app_log" ]; then
        local error_count=$(tail -100 "$app_log" 2>/dev/null | grep -ci "error\|exception\|fatal" || echo "0")
        
        if [ "$error_count" -gt 2 ]; then
            log_message "Application errors: HIGH ($error_count in last 100 lines)"
            send_alert "High number of application errors: $error_count" "WARNING"
        else
            log_message "Application errors: OK ($error_count in last 100 lines)"
        fi
    fi
}

# Main log monitoring function
main() {
    log_message "Starting log monitoring"
    
    monitor_apache_logs
    monitor_mysql_logs
    monitor_app_logs
    
    log_message "Log monitoring completed"
}

# Run log monitoring
main
EOF
    
    chmod +x "$SCRIPTS_DIR/log_monitor.sh"
    print_success "Log monitor script created"
}

# Function to create log rotation script
create_log_rotation() {
    print_header "Creating Log Rotation Script"
    
    cat > "$SCRIPTS_DIR/rotate_logs.sh" << 'EOF'
#!/bin/bash

# Maruba Log Rotation Script
# Rotates monitoring and application logs

LOG_DIR="/opt/lampp/htdocs/maruba/monitoring/logs"
APP_LOG_DIR="/opt/lampp/logs"

# Function to rotate log files
rotate_log() {
    local log_file="$1"
    local max_size="${2:-10M}"
    
    if [ -f "$log_file" ]; then
        local file_size=$(du -h "$log_file" | cut -f1)
        
        # Rotate if file is larger than max_size
        if [ "$(stat -c%s "$log_file")" -gt 10485760 ]; then
            local timestamp=$(date +%Y%m%d_%H%M%S)
            mv "$log_file" "${log_file}.${timestamp}"
            gzip "${log_file}.${timestamp}"
            echo "Rotated log: $log_file (size: $file_size)"
        fi
    fi
}

# Function to clean old logs
clean_old_logs() {
    local days="${1:-30}"
    
    find "$LOG_DIR" -name "*.log.*" -mtime +$days -delete 2>/dev/null
    find "$APP_LOG_DIR" -name "*.log.*" -mtime +$days -delete 2>/dev/null
    
    echo "Cleaned logs older than $days days"
}

# Main log rotation function
main() {
    echo "Starting log rotation"
    
    # Rotate monitoring logs
    rotate_log "$LOG_DIR/app_monitor.log"
    rotate_log "$LOG_DIR/performance_monitor.log"
    rotate_log "$LOG_DIR/log_monitor.log"
    rotate_log "$LOG_DIR/alerts.log"
    
    # Rotate application logs
    rotate_log "$APP_LOG_DIR/app.log"
    
    # Clean old logs
    clean_old_logs 30
    
    echo "Log rotation completed"
}

# Run log rotation
main
EOF
    
    chmod +x "$SCRIPTS_DIR/rotate_logs.sh"
    print_success "Log rotation script created"
}

# Function to create monitoring dashboard
create_monitoring_dashboard() {
    print_header "Creating Monitoring Dashboard"
    
    # Dashboard already created separately
    if [ -f "$MONITORING_DIR/dashboard/monitoring_dashboard.php" ]; then
        print_success "Monitoring dashboard already exists"
        return 0
    fi
    
    print_warning "Monitoring dashboard not found"
    return 1
}

# Function to setup cron jobs
setup_cron_jobs() {
    print_header "Setting Up Cron Jobs"
    
    # Create crontab file
    cat > "$MONITORING_DIR/cron_jobs.txt" << EOF
# Maruba Monitoring Cron Jobs

# Application monitoring - every 5 minutes
*/5 * * * * /opt/lampp/htdocs/maruba/monitoring/scripts/app_monitor.sh

# Performance monitoring - every 15 minutes
*/15 * * * * /opt/lampp/htdocs/maruba/monitoring/scripts/performance_monitor.sh

# Log monitoring - every 10 minutes
*/10 * * * * /opt/lampp/htdocs/maruba/monitoring/scripts/log_monitor.sh

# Log rotation - daily at midnight
0 0 * * * /opt/lampp/htdocs/maruba/monitoring/scripts/rotate_logs.sh
EOF
    
    print_success "Cron jobs file created: $MONITORING_DIR/cron_jobs.txt"
    print_status "To install cron jobs, run: crontab $MONITORING_DIR/cron_jobs.txt"
}

# Function to create configuration
create_config() {
    print_header "Creating Configuration"
    
    cat > "$MONITORING_DIR/config/monitoring.json" << 'EOF'
{
    "monitoring": {
        "enabled": true,
        "interval": {
            "app_monitor": 300,
            "performance_monitor": 900,
            "log_monitor": 600
        }
    },
    "alerts": {
        "enabled": true,
        "thresholds": {
            "response_time": 5.0,
            "cpu_usage": 80,
            "memory_usage": 85,
            "disk_usage": 90,
            "error_count": 5
        }
    },
    "retention": {
        "logs": 30,
        "alerts": 90
    }
}
EOF
    
    print_success "Configuration file created"
}

# Main setup function
setup_monitoring() {
    print_status "Setting up Maruba monitoring system..."
    echo ""
    
    local issues=0
    
    create_directory_structure || issues=$((issues + 1))
    echo ""
    
    create_app_monitor || issues=$((issues + 1))
    echo ""
    
    create_performance_monitor || issues=$((issues + 1))
    echo ""
    
    create_log_monitor || issues=$((issues + 1))
    echo ""
    
    create_log_rotation || issues=$((issues + 1))
    echo ""
    
    create_monitoring_dashboard || issues=$((issues + 1))
    echo ""
    
    setup_cron_jobs || issues=$((issues + 1))
    echo ""
    
    create_config || issues=$((issues + 1))
    echo ""
    
    print_status "Monitoring setup completed"
    
    if [ $issues -eq 0 ]; then
        print_success "All monitoring components set up successfully! 📊"
        print_status "Access monitoring dashboard at: http://localhost/maruba/monitoring/dashboard/"
        print_status "Install cron jobs with: crontab $MONITORING_DIR/cron_jobs.txt"
    else
        print_warning "Found $issues setup issues"
        print_warning "Please review and fix the issues above"
    fi

    return $issues
}

# Run the setup
if [ "${BASH_SOURCE[0]}" != "${0}" ]; then
    setup_monitoring
fi
