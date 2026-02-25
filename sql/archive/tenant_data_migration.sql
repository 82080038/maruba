-- =============================================================================
-- TENANT DATA MIGRATION SCRIPT
-- =============================================================================
-- This script migrates existing data to appropriate tenants after adding tenant_id columns

-- STEP 1: Create Sample Tenants for Testing
INSERT INTO `tenants` (`name`, `slug`, `status`, `district`, `city`, `province`) VALUES
('Koperasi Simpan Pinjam Samosir', 'ksp-samosir', 'active', 'Samosir', 'Pangururan', 'Sumatera Utara'),
('Koperasi Simpan Pinjam Toba', 'ksp-toba', 'active', 'Toba', 'Balige', 'Sumatera Utara'),
('Koperasi Simpan Pinjam Dairi', 'ksp-dairi', 'active', 'Dairi', 'Sidikalang', 'Sumatera Utara');

-- STEP 2: Assign Existing Users to Tenants
-- System Admin (tenant_id = NULL - can access all tenants)
UPDATE `users` SET `tenant_id` = NULL WHERE `username` = 'admin';

-- Assign demo users to first tenant for testing
UPDATE `users` SET `tenant_id` = 1 WHERE `username` IN ('kasir', 'teller', 'surveyor', 'collector');

-- STEP 3: Migrate Core Business Data to Tenant 1 (KSP Samosir)
-- Members data
UPDATE `members` SET `tenant_id` = 1 WHERE `id` IN (1, 2, 3);

-- Products data  
UPDATE `products` SET `tenant_id` = 1 WHERE `id` IN (1, 2, 3, 4, 5, 6, 7);

-- Loans data
UPDATE `loans` SET `tenant_id` = 1 WHERE `id` IN (1, 2, 3, 4);

-- Surveys data
UPDATE `surveys` SET `tenant_id` = 1 WHERE `loan_id` IN (1, 2, 3, 4);

-- Repayments data
UPDATE `repayments` SET `tenant_id` = 1 WHERE `loan_id` IN (1, 2, 3, 4);

-- Loan documents
UPDATE `loan_docs` SET `tenant_id` = 1 WHERE `loan_id` IN (1, 2, 3, 4);

-- STEP 4: Migrate Savings System Data to Tenant 1
-- Savings products
UPDATE `savings_products` SET `tenant_id` = 1 WHERE `id` IN (1, 2, 3, 4);

-- Savings accounts
UPDATE `savings_accounts` SET `tenant_id` = 1 WHERE `member_id` IN (1, 2);

-- STEP 5: Migrate Accounting Data to Tenant 1
-- Chart of accounts
UPDATE `chart_of_accounts` SET `tenant_id` = 1 WHERE `id` IN (1, 2, 3, 4, 5, 6, 7, 8, 9);

-- STEP 6: Migrate SHU Data to Tenant 1
UPDATE `shu_calculations` SET `tenant_id` = 1 WHERE `id` = 1;

-- STEP 7: Migrate Advanced Features Data to Tenant 1
-- Document templates
UPDATE `document_templates` SET `tenant_id` = 1 WHERE `id` IN (1, 2);

-- Employees
UPDATE `employees` SET `tenant_id` = 1 WHERE `user_id` IN (1, 2);

-- Credit analyses
UPDATE `credit_analyses` SET `tenant_id` = 1 WHERE `loan_id` IN (1, 2);

-- STEP 8: Update Audit Logs to Include Tenant Context
-- Update existing audit logs to show they belong to tenant 1
UPDATE `audit_logs` SET `tenant_id` = 1 WHERE `user_id` IN (1, 2, 3, 4, 5);

-- STEP 9: Create Cooperative Admins Mapping
-- Map admin users to tenants
INSERT INTO `cooperative_admins` (`cooperative_type`, `cooperative_id`, `user_id`) VALUES
('tenant', 1, 1),  -- Admin for KSP Samosir
('tenant', 2, NULL), -- KSP Toba (no admin yet)
('tenant', 3, NULL); -- KSP Dairi (no admin yet)

-- STEP 10: Create Navigation Menus for Each Tenant
-- Navigation menus for tenant 1 (KSP Samosir)
INSERT INTO `navigation_menus` (`tenant_id`, `menu_key`, `title`, `icon`, `route`, `parent_id`, `order`, `is_active`) VALUES
(1, 'dashboard', 'Dashboard', 'home', '/dashboard', NULL, 1, 1),
(1, 'members', 'Anggota', 'users', '/members', NULL, 2, 1),
(1, 'loans', 'Pinjaman', 'credit-card', '/loans', NULL, 3, 1),
(1, 'savings', 'Simpanan', 'piggy-bank', '/savings', NULL, 4, 1),
(1, 'reports', 'Laporan', 'file-text', '/reports', NULL, 5, 1),
(1, 'settings', 'Pengaturan', 'settings', '/settings', NULL, 6, 1);

-- Navigation menus for tenant 2 (KSP Toba)
INSERT INTO `navigation_menus` (`tenant_id`, `menu_key`, `title`, `icon`, `route`, `parent_id`, `order`, `is_active`) VALUES
(2, 'dashboard', 'Dashboard', 'home', '/dashboard', NULL, 1, 1),
(2, 'members', 'Anggota', 'users', '/members', NULL, 2, 1),
(2, 'loans', 'Pinjaman', 'credit-card', '/loans', NULL, 3, 1),
(2, 'savings', 'Simpanan', 'piggy-bank', '/savings', NULL, 4, 1),
(2, 'reports', 'Laporan', 'file-text', '/reports', NULL, 5, 1);

-- Navigation menus for tenant 3 (KSP Dairi)
INSERT INTO `navigation_menus` (`tenant_id`, `menu_key`, `title`, `icon`, `route`, `parent_id`, `order`, `is_active`) VALUES
(3, 'dashboard', 'Dashboard', 'home', '/dashboard', NULL, 1, 1),
(3, 'members', 'Anggota', 'users', '/members', NULL, 2, 1),
(3, 'loans', 'Pinjaman', 'credit-card', '/loans', NULL, 3, 1),
(3, 'savings', 'Simpanan', 'piggy-bank', '/savings', NULL, 4, 1);

-- =============================================================================
-- VERIFICATION QUERIES
-- =============================================================================

/*
-- Run these queries to verify data isolation:

-- 1. Check tenant distribution
SELECT tenant_id, COUNT(*) as user_count FROM users GROUP BY tenant_id;

-- 2. Verify tenant 1 data
SELECT 'members' as table_name, COUNT(*) as count FROM members WHERE tenant_id = 1
UNION ALL
SELECT 'loans', COUNT(*) FROM loans WHERE tenant_id = 1
UNION ALL  
SELECT 'savings_accounts', COUNT(*) FROM savings_accounts WHERE tenant_id = 1;

-- 3. Test data isolation
-- Tenant 1 should see their data:
SELECT COUNT(*) FROM members WHERE tenant_id = 1;

-- Tenant 2 should see no data (empty):
SELECT COUNT(*) FROM members WHERE tenant_id = 2;

-- System admin (tenant_id = NULL) should see all data:
SELECT COUNT(*) FROM members;

-- 4. Verify user-tenant mapping through cooperative_admins
SELECT 
    t.name as tenant_name,
    u.name as admin_name,
    u.username
FROM cooperative_admins ca
JOIN tenants t ON ca.cooperative_id = t.id
LEFT JOIN users u ON ca.user_id = u.id
WHERE ca.cooperative_type = 'tenant';

-- 5. Check audit logs tenant context
SELECT tenant_id, COUNT(*) as audit_count 
FROM audit_logs 
GROUP BY tenant_id;
*/
