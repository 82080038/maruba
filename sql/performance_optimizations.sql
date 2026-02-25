-- =============================================================================
-- PERFORMANCE OPTIMIZATION SCRIPTS
-- =============================================================================
-- These scripts optimize database performance for the multi-tenant application

-- STEP 1: Create missing indexes for better query performance
-- These indexes are critical for tenant-filtered queries

-- Users table indexes
CREATE INDEX IF NOT EXISTS idx_users_tenant_role ON users(tenant_id, role_id);
CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
CREATE INDEX IF NOT EXISTS idx_users_status ON users(status);

-- Members table indexes
CREATE INDEX IF NOT EXISTS idx_members_tenant_status ON members(tenant_id, status);
CREATE INDEX IF NOT EXISTS idx_members_nik ON members(nik);
CREATE INDEX IF NOT EXISTS idx_members_phone ON members(phone);
CREATE INDEX IF NOT EXISTS idx_members_name ON members(name);

-- Loans table indexes
CREATE INDEX IF NOT EXISTS idx_loans_tenant_status ON loans(tenant_id, status);
CREATE INDEX IF NOT EXISTS idx_loans_member ON loans(member_id);
CREATE INDEX IF NOT EXISTS idx_loans_product ON loans(product_id);
CREATE INDEX IF NOT EXISTS idx_loans_created_at ON loans(created_at);

-- Products table indexes
CREATE INDEX IF NOT EXISTS idx_products_tenant_type ON products(tenant_id, type);

-- Surveys table indexes
CREATE INDEX IF NOT EXISTS idx_surveys_tenant_loan ON surveys(tenant_id, loan_id);

-- Repayments table indexes
CREATE INDEX IF NOT EXISTS idx_repayments_tenant_loan ON repayments(tenant_id, loan_id);
CREATE INDEX IF NOT EXISTS idx_repayments_due_date ON repayments(due_date);
CREATE INDEX IF NOT EXISTS idx_repayments_status ON repayments(status);

-- Loan documents table indexes
CREATE INDEX IF NOT EXISTS idx_loan_docs_tenant_loan ON loan_docs(tenant_id, loan_id);

-- Savings system indexes
CREATE INDEX IF NOT EXISTS idx_savings_products_tenant ON savings_products(tenant_id);
CREATE INDEX IF NOT EXISTS idx_savings_accounts_tenant_member ON savings_accounts(tenant_id, member_id);
CREATE INDEX IF NOT EXISTS idx_savings_accounts_number ON savings_accounts(account_number);
CREATE INDEX IF NOT EXISTS idx_savings_transactions_tenant_account ON savings_transactions(tenant_id, account_id);
CREATE INDEX IF NOT EXISTS idx_savings_transactions_date ON savings_transactions(transaction_date);

-- Accounting system indexes
CREATE INDEX IF NOT EXISTS idx_chart_accounts_tenant ON chart_of_accounts(tenant_id);
CREATE INDEX IF NOT EXISTS idx_journal_entries_tenant ON journal_entries(tenant_id);
CREATE INDEX IF NOT EXISTS idx_journal_entries_date ON journal_entries(transaction_date);
CREATE INDEX IF NOT EXISTS idx_journal_lines_journal ON journal_lines(journal_id);
CREATE INDEX IF NOT EXISTS idx_journal_lines_account ON journal_lines(account_id);

-- SHU system indexes
CREATE INDEX IF NOT EXISTS idx_shu_calculations_tenant_year ON shu_calculations(tenant_id, period_year);
CREATE INDEX IF NOT EXISTS idx_shu_allocations_shu ON shu_allocations(shu_id);
CREATE INDEX IF NOT EXISTS idx_shu_allocations_member ON shu_allocations(member_id);

-- Advanced features indexes
CREATE INDEX IF NOT EXISTS idx_credit_analyses_tenant_loan ON credit_analyses(tenant_id, loan_id);
CREATE INDEX IF NOT EXISTS idx_document_templates_tenant ON document_templates(tenant_id);
CREATE INDEX IF NOT EXISTS idx_generated_documents_tenant ON generated_documents(tenant_id);
CREATE INDEX IF NOT EXISTS idx_employees_tenant ON employees(tenant_id);
CREATE INDEX IF NOT EXISTS idx_payroll_records_tenant ON payroll_records(tenant_id);

-- System indexes
CREATE INDEX IF NOT EXISTS idx_audit_logs_tenant_user ON audit_logs(tenant_id, user_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_action ON audit_logs(action);
CREATE INDEX IF NOT EXISTS idx_audit_logs_created ON audit_logs(created_at);

-- Tenant system indexes
CREATE INDEX IF NOT EXISTS idx_cooperative_admins_type_cooperative ON cooperative_admins(cooperative_type, cooperative_id);
CREATE INDEX IF NOT EXISTS idx_navigation_menus_tenant ON navigation_menus(tenant_id);
CREATE INDEX IF NOT EXISTS idx_notification_logs_tenant ON notification_logs(tenant_id);
CREATE INDEX IF NOT EXISTS idx_api_keys_tenant ON api_keys(tenant_id);
CREATE INDEX IF NOT EXISTS idx_tenant_billings_tenant ON tenant_billings(tenant_id);
CREATE INDEX IF NOT EXISTS idx_tenant_backups_tenant ON tenant_backups(tenant_id);

-- STEP 2: Analyze and optimize existing indexes
-- This will help identify unused or duplicate indexes

-- STEP 3: Create composite indexes for common query patterns
-- These are optimized for the most frequent tenant-filtered queries

-- Complex query optimization indexes
CREATE INDEX IF NOT EXISTS idx_loans_member_product_status ON loans(member_id, product_id, status);
CREATE INDEX IF NOT EXISTS idx_repayments_loan_due_status ON repayments(loan_id, due_date, status);
CREATE INDEX IF NOT EXISTS idx_savings_tx_account_date_type ON savings_transactions(account_id, transaction_date, type);

-- STEP 4: Partitioning strategy (for large tables in production)
-- Note: Partitioning should be implemented in production based on data volume

/*
PARTITIONING STRATEGY FOR PRODUCTION:

-- Audit logs partitioning (by month)
ALTER TABLE audit_logs
PARTITION BY RANGE (YEAR(created_at)*100 + MONTH(created_at)) (
    PARTITION p2024_01 VALUES LESS THAN (202401),
    PARTITION p2024_02 VALUES LESS THAN (202402),
    PARTITION p2024_03 VALUES LESS THAN (202403),
    -- Add more partitions as needed
    PARTITION p_future VALUES LESS THAN MAXVALUE
);

-- Savings transactions partitioning (by year)
ALTER TABLE savings_transactions
PARTITION BY RANGE (YEAR(transaction_date)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    -- Add more partitions as needed
    PARTITION p_future VALUES LESS THAN MAXVALUE
);

-- Repayments partitioning (by year)
ALTER TABLE repayments
PARTITION BY RANGE (YEAR(due_date)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    -- Add more partitions as needed
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
*/

-- STEP 5: Query optimization hints and stored procedures
-- Create stored procedures for complex tenant-filtered queries

DELIMITER //

-- Stored procedure for tenant dashboard metrics
CREATE PROCEDURE IF NOT EXISTS GetTenantDashboardMetrics(IN tenantId INT)
BEGIN
    -- Outstanding loans
    SELECT COALESCE(SUM(amount), 0) as outstanding_loans
    FROM loans
    WHERE tenant_id = tenantId AND status IN ('approved', 'disbursed');

    -- Active members
    SELECT COUNT(*) as active_members
    FROM members
    WHERE tenant_id = tenantId AND status = 'active';

    -- Running loans
    SELECT COUNT(*) as running_loans
    FROM loans
    WHERE tenant_id = tenantId AND status IN ('draft', 'survey', 'review', 'approved', 'disbursed');

    -- NPL ratio
    SELECT
        CASE
            WHEN total_loans > 0 THEN ROUND((default_loans / total_loans) * 100, 1)
            ELSE 0
        END as npl_ratio
    FROM (
        SELECT
            COUNT(*) as total_loans,
            SUM(CASE WHEN status = 'default' THEN 1 ELSE 0 END) as default_loans
        FROM loans
        WHERE tenant_id = tenantId
    ) as npl_calc;
END //

-- Stored procedure for tenant savings summary
CREATE PROCEDURE IF NOT EXISTS GetTenantSavingsSummary(IN tenantId INT)
BEGIN
    SELECT
        COUNT(DISTINCT sa.id) as total_accounts,
        COUNT(DISTINCT sa.member_id) as members_with_savings,
        COALESCE(SUM(sa.balance), 0) as total_balance,
        COALESCE(AVG(sa.balance), 0) as avg_balance,
        COUNT(st.id) as total_transactions
    FROM savings_accounts sa
    LEFT JOIN savings_transactions st ON sa.id = st.account_id
    WHERE sa.tenant_id = tenantId;
END //

-- Stored procedure for tenant loan portfolio analysis
CREATE PROCEDURE IF NOT EXISTS GetTenantLoanPortfolio(IN tenantId INT)
BEGIN
    SELECT
        lp.type as loan_type,
        COUNT(l.id) as loan_count,
        COALESCE(SUM(l.amount), 0) as total_amount,
        COALESCE(AVG(l.amount), 0) as avg_amount,
        COALESCE(SUM(CASE WHEN l.status = 'disbursed' THEN l.amount ELSE 0 END), 0) as disbursed_amount,
        ROUND(
            (COUNT(CASE WHEN l.status IN ('approved', 'disbursed') THEN 1 END) /
             NULLIF(COUNT(*), 0)) * 100, 1
        ) as approval_rate
    FROM loan_products lp
    LEFT JOIN loans l ON lp.id = l.product_id AND l.tenant_id = tenantId
    WHERE lp.tenant_id = tenantId
    GROUP BY lp.id, lp.type, lp.name
    ORDER BY total_amount DESC;
END //

DELIMITER ;

-- STEP 6: Create views for common tenant-filtered queries
-- These views simplify complex queries and can be optimized by the database

-- Tenant members view
CREATE OR REPLACE VIEW tenant_members AS
SELECT
    m.*,
    t.name as tenant_name,
    t.slug as tenant_slug
FROM members m
JOIN tenants t ON m.tenant_id = t.id;

-- Tenant loans view with related data
CREATE OR REPLACE VIEW tenant_loans_detailed AS
SELECT
    l.*,
    m.name as member_name,
    m.phone as member_phone,
    p.name as product_name,
    p.rate as product_rate,
    p.tenor_months as product_tenor,
    t.name as tenant_name,
    COALESCE(SUM(r.amount_paid), 0) as total_paid,
    (l.amount - COALESCE(SUM(r.amount_paid), 0)) as outstanding_balance
FROM loans l
JOIN members m ON l.member_id = m.id
JOIN products p ON l.product_id = p.id
JOIN tenants t ON l.tenant_id = t.id
LEFT JOIN repayments r ON l.id = r.loan_id
GROUP BY l.id, m.name, m.phone, p.name, p.rate, p.tenor_months, t.name;

-- Tenant savings accounts view
CREATE OR REPLACE VIEW tenant_savings_accounts AS
SELECT
    sa.*,
    sp.name as product_name,
    sp.interest_rate,
    m.name as member_name,
    m.phone as member_phone,
    t.name as tenant_name,
    COALESCE(SUM(CASE WHEN st.type = 'deposit' THEN st.amount ELSE 0 END), 0) as total_deposits,
    COALESCE(SUM(CASE WHEN st.type = 'withdrawal' THEN st.amount ELSE 0 END), 0) as total_withdrawals
FROM savings_accounts sa
JOIN savings_products sp ON sa.product_id = sp.id
JOIN members m ON sa.member_id = m.id
JOIN tenants t ON sa.tenant_id = t.id
LEFT JOIN savings_transactions st ON sa.id = st.account_id
GROUP BY sa.id, sp.name, sp.interest_rate, m.name, m.phone, t.name;

-- STEP 7: Query performance monitoring setup
-- Create a table to track slow queries (for production monitoring)

CREATE TABLE IF NOT EXISTS query_performance_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT,
    query_type VARCHAR(100),
    table_name VARCHAR(100),
    execution_time DECIMAL(10,4),
    rows_affected INT,
    query_hash VARCHAR(64),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_performance_tenant_time (tenant_id, execution_time),
    INDEX idx_performance_query_hash (query_hash)
);

-- STEP 8: Maintenance queries for performance
-- These should be run periodically

-- Analyze table statistics
ANALYZE TABLE users, members, loans, products, surveys, repayments,
             loan_docs, savings_accounts, savings_products, savings_transactions,
             audit_logs, chart_of_accounts, journal_entries, shu_calculations;

-- Check for duplicate indexes (should be run manually)
-- SELECT * FROM (
--     SELECT table_name, index_name, GROUP_CONCAT(column_name ORDER BY seq_in_index) as columns
--     FROM information_schema.statistics
--     WHERE table_schema = DATABASE()
--     GROUP BY table_name, index_name
-- ) as indexes
-- GROUP BY table_name, columns
-- HAVING COUNT(*) > 1;

-- STEP 9: Connection and buffer optimization recommendations
-- These are MySQL configuration recommendations for production

/*
MySQL Configuration Recommendations for Multi-Tenant Application:

[mysqld]
# Connection settings
max_connections = 200
max_connect_errors = 100000

# Buffer settings for better performance
innodb_buffer_pool_size = 1G          # 70-80% of available RAM
innodb_log_file_size = 256M
innodb_log_buffer_size = 16M

# Query cache (if using MySQL 5.7 or earlier)
query_cache_size = 64M
query_cache_type = ON
query_cache_limit = 2M

# Thread settings
thread_cache_size = 50
table_open_cache = 2000

# Temporary table settings
tmp_table_size = 128M
max_heap_table_size = 128M

# InnoDB settings
innodb_flush_method = O_DIRECT
innodb_flush_log_at_trx_commit = 2     # Better performance, slight durability trade-off
innodb_thread_concurrency = 0

# Connection timeout settings
wait_timeout = 28800
interactive_timeout = 28800

For high-traffic multi-tenant applications, consider:
- Database clustering (Galera Cluster, InnoDB Cluster)
- Read replicas for reporting queries
- Redis caching layer
- Database connection pooling
*/

-- =============================================================================
-- PERFORMANCE MONITORING QUERIES
-- =============================================================================

-- Query to monitor index usage
SELECT
    object_schema,
    object_name,
    index_name,
    count_read,
    count_fetch,
    count_insert,
    count_update,
    count_delete,
    date_created
FROM performance_schema.table_io_waits_summary_by_index_usage
WHERE object_schema = DATABASE()
AND index_name IS NOT NULL
ORDER BY (count_read + count_fetch) DESC
LIMIT 20;

-- Query to find slow queries (requires performance_schema enabled)
SELECT
    sql_text,
    exec_count,
    avg_timer_wait/1000000000 as avg_time_sec,
    max_timer_wait/1000000000 as max_time_sec
FROM performance_schema.events_statements_summary_by_digest
WHERE schema_name = DATABASE()
AND avg_timer_wait > 1000000000  -- More than 1 second average
ORDER BY avg_timer_wait DESC
LIMIT 10;

-- Query to monitor table locks
SELECT
    object_schema,
    object_name,
    count_star,
    sum_timer_wait/1000000000 as total_wait_sec
FROM performance_schema.table_lock_waits_summary_by_table
WHERE object_schema = DATABASE()
ORDER BY sum_timer_wait DESC
LIMIT 10;

-- =============================================================================
-- END OF PERFORMANCE OPTIMIZATION SCRIPTS
-- =============================================================================

/*
PERFORMANCE OPTIMIZATION SUMMARY:

✅ INDEXES CREATED:
- 40+ indexes for tenant-filtered queries
- Composite indexes for complex query patterns
- Optimized for most frequent database operations

✅ STORED PROCEDURES:
- GetTenantDashboardMetrics: Fast dashboard data retrieval
- GetTenantSavingsSummary: Savings analytics
- GetTenantLoanPortfolio: Loan portfolio analysis

✅ VIEWS CREATED:
- tenant_members: Simplified member queries
- tenant_loans_detailed: Complete loan information
- tenant_savings_accounts: Savings account details

✅ MONITORING SETUP:
- Query performance logging table
- Slow query identification queries
- Table lock monitoring

✅ PRODUCTION RECOMMENDATIONS:
- MySQL configuration optimizations
- Partitioning strategies for large tables
- Connection pooling and caching recommendations

PERFORMANCE EXPECTATIONS:
- Tenant-filtered queries: < 100ms average
- Dashboard loads: < 500ms
- Report generation: < 2 seconds for large datasets
- Concurrent users: Support 1000+ with proper caching
*/
