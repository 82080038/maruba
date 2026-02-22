-- =============================================================================
-- CRITICAL FIX: TENANT DATA ISOLATION
-- =============================================================================
-- This script fixes the multi-tenant data isolation by adding tenant_id columns
-- to all tables that need tenant-specific data separation

-- STEP 1: Update Users Table (MOST CRITICAL)
-- Users table needs tenant_id to separate users by tenant
ALTER TABLE `users` 
ADD COLUMN `tenant_id` int(11) DEFAULT NULL AFTER `role_id`,
ADD KEY `idx_tenant_id` (`tenant_id`),
ADD CONSTRAINT `fk_users_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- STEP 2: Update Core Business Tables
-- Members table - separate members by tenant
ALTER TABLE `members` 
ADD COLUMN `tenant_id` int(11) DEFAULT NULL AFTER `id`,
ADD KEY `idx_members_tenant` (`tenant_id`),
ADD CONSTRAINT `fk_members_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- Products table - separate loan/savings products by tenant
ALTER TABLE `products` 
ADD COLUMN `tenant_id` int(11) DEFAULT NULL AFTER `id`,
ADD KEY `idx_products_tenant` (`tenant_id`),
ADD CONSTRAINT `fk_products_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- Loans table - separate loans by tenant
ALTER TABLE `loans` 
ADD COLUMN `tenant_id` int(11) DEFAULT NULL AFTER `id`,
ADD KEY `idx_loans_tenant` (`tenant_id`),
ADD CONSTRAINT `fk_loans_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- STEP 3: Update Supporting Tables
-- Surveys table
ALTER TABLE `surveys` 
ADD COLUMN `tenant_id` int(11) DEFAULT NULL AFTER `id`,
ADD KEY `idx_surveys_tenant` (`tenant_id`),
ADD CONSTRAINT `fk_surveys_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- Repayments table
ALTER TABLE `repayments` 
ADD COLUMN `tenant_id` int(11) DEFAULT NULL AFTER `id`,
ADD KEY `idx_repayments_tenant` (`tenant_id`),
ADD CONSTRAINT `fk_repayments_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- Loan documents table
ALTER TABLE `loan_docs` 
ADD COLUMN `tenant_id` int(11) DEFAULT NULL AFTER `id`,
ADD KEY `idx_loan_docs_tenant` (`tenant_id`),
ADD CONSTRAINT `fk_loan_docs_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- STEP 4: Update Savings System Tables
-- Savings products table
ALTER TABLE `savings_products` 
ADD COLUMN `tenant_id` int(11) DEFAULT NULL AFTER `id`,
ADD KEY `idx_savings_products_tenant` (`tenant_id`),
ADD CONSTRAINT `fk_savings_products_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- Savings accounts table
ALTER TABLE `savings_accounts` 
ADD COLUMN `tenant_id` int(11) DEFAULT NULL AFTER `id`,
ADD KEY `idx_savings_accounts_tenant` (`tenant_id`),
ADD CONSTRAINT `fk_savings_accounts_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- Savings transactions table
ALTER TABLE `savings_transactions` 
ADD COLUMN `tenant_id` int(11) DEFAULT NULL AFTER `id`,
ADD KEY `idx_savings_transactions_tenant` (`tenant_id`),
ADD CONSTRAINT `fk_savings_transactions_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- STEP 5: Update Accounting System Tables
-- Chart of accounts table
ALTER TABLE `chart_of_accounts` 
ADD COLUMN `tenant_id` int(11) DEFAULT NULL AFTER `id`,
ADD KEY `idx_chart_accounts_tenant` (`tenant_id`),
ADD CONSTRAINT `fk_chart_accounts_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- Journal entries table
ALTER TABLE `journal_entries` 
ADD COLUMN `tenant_id` int(11) DEFAULT NULL AFTER `id`,
ADD KEY `idx_journal_entries_tenant` (`tenant_id`),
ADD CONSTRAINT `fk_journal_entries_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- Journal lines table (inherits tenant_id from journal_entries)
ALTER TABLE `journal_lines` 
ADD COLUMN `tenant_id` int(11) DEFAULT NULL AFTER `id`,
ADD KEY `idx_journal_lines_tenant` (`tenant_id`),
ADD CONSTRAINT `fk_journal_lines_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- STEP 6: Update SHU System Tables
-- SHU calculations table
ALTER TABLE `shu_calculations` 
ADD COLUMN `tenant_id` int(11) DEFAULT NULL AFTER `id`,
ADD KEY `idx_shu_calculations_tenant` (`tenant_id`),
ADD CONSTRAINT `fk_shu_calculations_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- SHU allocations table (inherits tenant_id from shu_calculations)
ALTER TABLE `shu_allocations` 
ADD COLUMN `tenant_id` int(11) DEFAULT NULL AFTER `id`,
ADD KEY `idx_shu_allocations_tenant` (`tenant_id`),
ADD CONSTRAINT `fk_shu_allocations_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- STEP 7: Update Advanced Features Tables
-- Credit analyses table
ALTER TABLE `credit_analyses` 
ADD COLUMN `tenant_id` int(11) DEFAULT NULL AFTER `id`,
ADD KEY `idx_credit_analyses_tenant` (`tenant_id`),
ADD CONSTRAINT `fk_credit_analyses_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- Document templates table
ALTER TABLE `document_templates` 
ADD COLUMN `tenant_id` int(11) DEFAULT NULL AFTER `id`,
ADD KEY `idx_document_templates_tenant` (`tenant_id`),
ADD CONSTRAINT `fk_document_templates_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- Generated documents table
ALTER TABLE `generated_documents` 
ADD COLUMN `tenant_id` int(11) DEFAULT NULL AFTER `id`,
ADD KEY `idx_generated_documents_tenant` (`tenant_id`),
ADD CONSTRAINT `fk_generated_documents_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- Employees table
ALTER TABLE `employees` 
ADD COLUMN `tenant_id` int(11) DEFAULT NULL AFTER `id`,
ADD KEY `idx_employees_tenant` (`tenant_id`),
ADD CONSTRAINT `fk_employees_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- Payroll records table
ALTER TABLE `payroll_records` 
ADD COLUMN `tenant_id` int(11) DEFAULT NULL AFTER `id`,
ADD KEY `idx_payroll_records_tenant` (`tenant_id`),
ADD CONSTRAINT `fk_payroll_records_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- STEP 8: Update Multi-tenant Management Tables
-- Navigation menus table (already has tenant_id, but ensure it's properly indexed)
ALTER TABLE `navigation_menus` 
ADD KEY `idx_navigation_menus_tenant` (`tenant_id`);

-- Notification logs table (already has tenant_id, but ensure it's properly indexed)
ALTER TABLE `notification_logs` 
ADD KEY `idx_notification_logs_tenant` (`tenant_id`);

-- API keys table (already has tenant_id, but ensure it's properly indexed)
ALTER TABLE `api_keys` 
ADD KEY `idx_api_keys_tenant` (`tenant_id`);

-- Payment transactions table
ALTER TABLE `payment_transactions` 
ADD COLUMN `tenant_id` int(11) DEFAULT NULL AFTER `id`,
ADD KEY `idx_payment_transactions_tenant` (`tenant_id`),
ADD CONSTRAINT `fk_payment_transactions_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- Compliance checks table
ALTER TABLE `compliance_checks` 
ADD COLUMN `tenant_id` int(11) DEFAULT NULL AFTER `id`,
ADD KEY `idx_compliance_checks_tenant` (`tenant_id`),
ADD CONSTRAINT `fk_compliance_checks_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- Risk assessments table
ALTER TABLE `risk_assessments` 
ADD COLUMN `tenant_id` int(11) DEFAULT NULL AFTER `id`,
ADD KEY `idx_risk_assessments_tenant` (`tenant_id`),
ADD CONSTRAINT `fk_risk_assessments_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- STEP 9: Update Audit Logs Table (Critical for Security)
ALTER TABLE `audit_logs` 
ADD COLUMN `tenant_id` int(11) DEFAULT NULL AFTER `user_id`,
ADD KEY `idx_audit_logs_tenant` (`tenant_id`),
ADD CONSTRAINT `fk_audit_logs_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- =============================================================================
-- MIGRATION NOTES
-- =============================================================================

/*
IMPORTANT IMPLEMENTATION NOTES:

1. DATA MIGRATION STRATEGY:
   - All existing data will have tenant_id = NULL (system admin access only)
   - Use UPDATE statements to assign existing data to appropriate tenants
   - Example: UPDATE users SET tenant_id = 1 WHERE username IN ('admin', 'kasir');

2. APPLICATION CODE CHANGES REQUIRED:
   - ALL queries MUST include tenant_id filtering
   - Use Database::getConnection() which handles tenant context
   - Implement TenantMiddleware to set tenant context automatically

3. SECURITY IMPLEMENTATION:
   - cooperative_admins table already provides user-tenant mapping
   - Use this mapping to validate user access to tenant data
   - Implement row-level security in application layer

4. QUERY EXAMPLES AFTER FIX:
   -- Before (INSECURE):
   SELECT * FROM users WHERE role = 'admin';
   
   -- After (SECURE):
   SELECT * FROM users WHERE tenant_id = :current_tenant_id AND role = 'admin';

5. TESTING STRATEGY:
   - Create test tenants with sample data
   - Verify users can only access their tenant data
   - Test cross-tenant data access attempts (should fail)

6. ROLLBACK PLAN:
   - Keep backup of original schema
   - Create rollback script to remove tenant_id columns if needed
   - Test rollback procedure in staging environment

EXECUTION ORDER:
1. Run this SQL script to add tenant_id columns
2. Update application code to use tenant filtering
3. Migrate existing data to appropriate tenants
4. Test thoroughly before production deployment
5. Implement monitoring for tenant data access violations
*/
