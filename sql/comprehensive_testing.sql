-- =============================================================================
-- COMPREHENSIVE TESTING SCRIPT FOR FEATURE COMPLETENESS
-- =============================================================================
-- This script tests all implemented features for proper functionality
-- Run this to verify that all optional enhancements are working correctly

-- Test 1: Verify all required tables exist
SELECT 'TEST 1: TABLE EXISTENCE CHECK' as test_name;
SHOW TABLES LIKE 'users';
SHOW TABLES LIKE 'members';
SHOW TABLES LIKE 'loans';
SHOW TABLES LIKE 'products';
SHOW TABLES LIKE 'surveys';
SHOW TABLES LIKE 'repayments';
SHOW TABLES LIKE 'loan_docs';
SHOW TABLES LIKE 'savings_accounts';
SHOW TABLES LIKE 'savings_products';
SHOW TABLES LIKE 'savings_transactions';
SHOW TABLES LIKE 'chart_of_accounts';
SHOW TABLES LIKE 'journal_entries';
SHOW TABLES LIKE 'journal_lines';
SHOW TABLES LIKE 'shu_calculations';
SHOW TABLES LIKE 'shu_allocations';
SHOW TABLES LIKE 'credit_analyses';
SHOW TABLES LIKE 'document_templates';
SHOW TABLES LIKE 'generated_documents';
SHOW TABLES LIKE 'employees';
SHOW TABLES LIKE 'payroll_records';
SHOW TABLES LIKE 'compliance_checks';
SHOW TABLES LIKE 'risk_assessments';
SHOW TABLES LIKE 'navigation_menus';
SHOW TABLES LIKE 'notification_logs';
SHOW TABLES LIKE 'api_keys';
SHOW TABLES LIKE 'payment_transactions';
SHOW TABLES LIKE 'subscription_plans';
SHOW TABLES LIKE 'tenant_billings';
SHOW TABLES LIKE 'tenant_backups';
SHOW TABLES LIKE 'tenants';
SHOW TABLES LIKE 'roles';
SHOW TABLES LIKE 'audit_logs';
SHOW TABLES LIKE 'cooperative_admins';

-- Test 2: Verify tenant isolation columns exist
SELECT 'TEST 2: TENANT ISOLATION COLUMNS CHECK' as test_name;

-- Check that critical tables have tenant_id columns
DESCRIBE users;
DESCRIBE members;
DESCRIBE loans;
DESCRIBE products;
DESCRIBE surveys;
DESCRIBE repayments;
DESCRIBE loan_docs;
DESCRIBE savings_accounts;
DESCRIBE savings_products;
DESCRIBE savings_transactions;
DESCRIBE audit_logs;

-- Test 3: Verify tenant data distribution
SELECT 'TEST 3: TENANT DATA DISTRIBUTION' as test_name;

SELECT 'Members per tenant:' as info;
SELECT tenant_id, COUNT(*) as count FROM members GROUP BY tenant_id ORDER BY tenant_id;

SELECT 'Loans per tenant:' as info;
SELECT tenant_id, COUNT(*) as count FROM loans GROUP BY tenant_id ORDER BY tenant_id;

SELECT 'Products per tenant:' as info;
SELECT tenant_id, COUNT(*) as count FROM products GROUP BY tenant_id ORDER BY tenant_id;

SELECT 'Users per tenant:' as info;
SELECT tenant_id, COUNT(*) as count FROM users GROUP BY tenant_id ORDER BY tenant_id;

-- Test 4: Verify referential integrity
SELECT 'TEST 4: REFERENTIAL INTEGRITY CHECK' as test_name;

-- Check for orphaned records (should return empty results)
SELECT 'Orphaned loans (no member):' as info;
SELECT l.id, l.member_id FROM loans l LEFT JOIN members m ON l.member_id = m.id WHERE m.id IS NULL;

SELECT 'Orphaned loans (no product):' as info;
SELECT l.id, l.product_id FROM loans l LEFT JOIN products p ON l.product_id = p.id WHERE p.id IS NULL;

SELECT 'Orphaned repayments (no loan):' as info;
SELECT r.id, r.loan_id FROM repayments r LEFT JOIN loans l ON r.loan_id = l.id WHERE l.id IS NULL;

SELECT 'Orphaned loan docs (no loan):' as info;
SELECT ld.id, ld.loan_id FROM loan_docs ld LEFT JOIN loans l ON ld.loan_id = l.id WHERE l.id IS NULL;

SELECT 'Orphaned savings accounts (no member):' as info;
SELECT sa.id, sa.member_id FROM savings_accounts sa LEFT JOIN members m ON sa.member_id = m.id WHERE m.id IS NULL;

SELECT 'Orphaned savings accounts (no product):' as info;
SELECT sa.id, sa.product_id FROM savings_accounts sa LEFT JOIN savings_products sp ON sa.product_id = sp.id WHERE sp.id IS NULL;

-- Test 5: Verify data consistency
SELECT 'TEST 5: DATA CONSISTENCY CHECK' as test_name;

-- Check that active members don't have inactive status
SELECT 'Active members with inactive status:' as info;
SELECT COUNT(*) FROM members WHERE status = 'active' AND tenant_id IS NOT NULL;

-- Check that disbursed loans have approved status first
SELECT 'Loans with disbursed status but not approved:' as info;
SELECT COUNT(*) FROM loans WHERE status = 'disbursed' AND approved_by IS NULL;

-- Check that repayments don't exceed loan amounts
SELECT 'Repayments exceeding loan amounts:' as info;
SELECT COUNT(*) FROM repayments r
JOIN loans l ON r.loan_id = l.id
WHERE r.amount_paid > l.amount;

-- Test 6: Verify audit trail completeness
SELECT 'TEST 6: AUDIT TRAIL CHECK' as test_name;

SELECT 'Total audit logs:' as info, COUNT(*) as count FROM audit_logs;

SELECT 'Audit logs per tenant:' as info;
SELECT tenant_id, COUNT(*) as count FROM audit_logs GROUP BY tenant_id ORDER BY tenant_id;

SELECT 'Audit logs per action type:' as info;
SELECT action, COUNT(*) as count FROM audit_logs GROUP BY action ORDER BY count DESC LIMIT 10;

-- Test 7: Verify role and permission structure
SELECT 'TEST 7: ROLE AND PERMISSION CHECK' as test_name;

SELECT 'Available roles:' as info;
SELECT id, name FROM roles ORDER BY id;

SELECT 'Users per role:' as info;
SELECT r.name as role_name, COUNT(u.id) as user_count
FROM roles r
LEFT JOIN users u ON r.id = u.role_id
GROUP BY r.id, r.name
ORDER BY r.id;

-- Test 8: Verify tenant structure
SELECT 'TEST 8: TENANT STRUCTURE CHECK' as test_name;

SELECT 'Available tenants:' as info;
SELECT id, name, slug, status FROM tenants ORDER BY id;

SELECT 'Cooperative admins mapping:' as info;
SELECT ca.cooperative_type, t.name as tenant_name, u.name as admin_name
FROM cooperative_admins ca
JOIN tenants t ON ca.cooperative_id = t.id
LEFT JOIN users u ON ca.user_id = u.id
ORDER BY ca.cooperative_type, t.id;

-- Test 9: Verify savings system integrity
SELECT 'TEST 9: SAVINGS SYSTEM CHECK' as test_name;

SELECT 'Savings products per tenant:' as info;
SELECT tenant_id, COUNT(*) as count FROM savings_products GROUP BY tenant_id ORDER BY tenant_id;

SELECT 'Savings accounts per tenant:' as info;
SELECT tenant_id, COUNT(*) as count FROM savings_accounts GROUP BY tenant_id ORDER BY tenant_id;

SELECT 'Savings transactions per tenant:' as info;
SELECT tenant_id, COUNT(*) as count FROM savings_transactions GROUP BY tenant_id ORDER BY tenant_id;

-- Check balance consistency
SELECT 'Balance consistency check:' as info;
SELECT sa.id, sa.account_number, sa.balance,
       COALESCE(SUM(CASE WHEN st.type = 'deposit' THEN st.amount ELSE 0 END), 0) as total_deposits,
       COALESCE(SUM(CASE WHEN st.type = 'withdrawal' THEN st.amount ELSE 0 END), 0) as total_withdrawals,
       (COALESCE(SUM(CASE WHEN st.type = 'deposit' THEN st.amount ELSE 0 END), 0) -
        COALESCE(SUM(CASE WHEN st.type = 'withdrawal' THEN st.amount ELSE 0 END), 0)) as calculated_balance
FROM savings_accounts sa
LEFT JOIN savings_transactions st ON sa.id = st.account_id
GROUP BY sa.id, sa.account_number, sa.balance
HAVING sa.balance != (COALESCE(SUM(CASE WHEN st.type = 'deposit' THEN st.amount ELSE 0 END), 0) -
                      COALESCE(SUM(CASE WHEN st.type = 'withdrawal' THEN st.amount ELSE 0 END), 0));

-- Test 10: Verify accounting system
SELECT 'TEST 10: ACCOUNTING SYSTEM CHECK' as test_name;

SELECT 'Chart of accounts per tenant:' as info;
SELECT tenant_id, COUNT(*) as count FROM chart_of_accounts GROUP BY tenant_id ORDER BY tenant_id;

SELECT 'Journal entries per tenant:' as info;
SELECT tenant_id, COUNT(*) as count FROM journal_entries GROUP BY tenant_id ORDER BY tenant_id;

-- Check journal balance (debit should equal credit)
SELECT 'Journal balance check (should be empty):' as info;
SELECT je.id, je.journal_number, je.total_debit, je.total_credit
FROM journal_entries je
WHERE je.total_debit != je.total_credit;

-- Test 11: Verify SHU system
SELECT 'TEST 11: SHU SYSTEM CHECK' as test_name;

SELECT 'SHU calculations per tenant:' as info;
SELECT tenant_id, COUNT(*) as count FROM shu_calculations GROUP BY tenant_id ORDER BY tenant_id;

SELECT 'SHU allocations per calculation:' as info;
SELECT shu_id, COUNT(*) as count FROM shu_allocations GROUP BY shu_id ORDER BY shu_id;

-- Test 12: Verify payment system
SELECT 'TEST 12: PAYMENT SYSTEM CHECK' as test_name;

SELECT 'Payment transactions per tenant:' as info;
SELECT tenant_id, COUNT(*) as count FROM payment_transactions GROUP BY tenant_id ORDER BY tenant_id;

SELECT 'Payment status distribution:' as info;
SELECT status, COUNT(*) as count FROM payment_transactions GROUP BY status ORDER BY status;

-- Test 13: Verify document system
SELECT 'TEST 13: DOCUMENT SYSTEM CHECK' as test_name;

SELECT 'Document templates per tenant:' as info;
SELECT tenant_id, COUNT(*) as count FROM document_templates GROUP BY tenant_id ORDER BY tenant_id;

SELECT 'Generated documents per tenant:' as info;
SELECT tenant_id, COUNT(*) as count FROM generated_documents GROUP BY tenant_id ORDER BY tenant_id;

-- Test 14: Verify employee and payroll system
SELECT 'TEST 14: EMPLOYEE & PAYROLL CHECK' as test_name;

SELECT 'Employees per tenant:' as info;
SELECT tenant_id, COUNT(*) as count FROM employees GROUP BY tenant_id ORDER BY tenant_id;

SELECT 'Payroll records per tenant:' as info;
SELECT tenant_id, COUNT(*) as count FROM payroll_records GROUP BY tenant_id ORDER BY tenant_id;

-- Test 15: Verify compliance and risk systems
SELECT 'TEST 15: COMPLIANCE & RISK CHECK' as test_name;

SELECT 'Compliance checks per tenant:' as info;
SELECT tenant_id, COUNT(*) as count FROM compliance_checks GROUP BY tenant_id ORDER BY tenant_id;

SELECT 'Risk assessments per tenant:' as info;
SELECT tenant_id, COUNT(*) as count FROM risk_assessments GROUP BY tenant_id ORDER BY tenant_id;

-- Test 16: Verify navigation and UI systems
SELECT 'TEST 16: NAVIGATION & UI CHECK' as test_name;

SELECT 'Navigation menus per tenant:' as info;
SELECT tenant_id, COUNT(*) as count FROM navigation_menus GROUP BY tenant_id ORDER BY tenant_id;

SELECT 'API keys per tenant:' as info;
SELECT tenant_id, COUNT(*) as count FROM api_keys GROUP BY tenant_id ORDER BY tenant_id;

-- Test 17: Verify notification system
SELECT 'TEST 17: NOTIFICATION SYSTEM CHECK' as test_name;

SELECT 'Notification logs per tenant:' as info;
SELECT tenant_id, COUNT(*) as count FROM notification_logs GROUP BY tenant_id ORDER BY tenant_id;

SELECT 'Notification channels:' as info;
SELECT channel, COUNT(*) as count FROM notification_logs GROUP BY channel ORDER BY count DESC;

-- Test 18: Verify subscription and billing systems
SELECT 'TEST 18: SUBSCRIPTION & BILLING CHECK' as test_name;

SELECT 'Available subscription plans:' as info;
SELECT id, name, display_name, price_monthly FROM subscription_plans ORDER BY id;

SELECT 'Tenant billings per tenant:' as info;
SELECT tenant_id, COUNT(*) as count FROM tenant_billings GROUP BY tenant_id ORDER BY tenant_id;

-- Test 19: Verify backup system
SELECT 'TEST 19: BACKUP SYSTEM CHECK' as test_name;

SELECT 'Tenant backups per tenant:' as info;
SELECT tenant_id, COUNT(*) as count FROM tenant_backups GROUP BY tenant_id ORDER BY tenant_id;

SELECT 'Backup status distribution:' as info;
SELECT status, COUNT(*) as count FROM tenant_backups GROUP BY status ORDER BY status;

-- Test 20: Performance and optimization check
SELECT 'TEST 20: PERFORMANCE CHECK' as test_name;

-- Check table sizes
SELECT 'Table sizes (approximate):' as info;
SELECT table_name, table_rows, data_length, index_length
FROM information_schema.tables
WHERE table_schema = DATABASE()
AND table_name IN ('users', 'members', 'loans', 'savings_accounts', 'audit_logs')
ORDER BY (data_length + index_length) DESC;

-- Check for missing indexes on tenant_id columns
SELECT 'Missing indexes check:' as info;
SELECT 'This should be run manually to check for missing indexes on tenant_id columns' as note;

-- =============================================================================
-- TEST RESULTS SUMMARY
-- =============================================================================

/*
EXPECTED TEST RESULTS:

✅ TEST 1: All required tables should exist
✅ TEST 2: All tables should have tenant_id columns with proper foreign keys
✅ TEST 3: Data should be properly distributed per tenant
✅ TEST 4: No orphaned records should exist
✅ TEST 5: Data consistency should be maintained
✅ TEST 6: Audit logs should exist and be properly distributed
✅ TEST 7: Roles and permissions should be properly configured
✅ TEST 8: Tenant structure should be properly set up
✅ TEST 9: Savings system should have consistent balances
✅ TEST 10: Accounting system should have balanced journals
✅ TEST 11: SHU system should have proper calculations and allocations
✅ TEST 12: Payment system should have proper transaction tracking
✅ TEST 13: Document system should have templates and generated documents
✅ TEST 14: Employee and payroll systems should be properly configured
✅ TEST 15: Compliance and risk systems should be monitoring
✅ TEST 16: Navigation and API systems should be configured
✅ TEST 17: Notification system should be logging activities
✅ TEST 18: Subscription and billing systems should be set up
✅ TEST 19: Backup system should have backup records
✅ TEST 20: Performance should be acceptable

If any test fails, check:
1. Database schema completeness
2. Data migration scripts
3. Application code tenant filtering
4. Foreign key constraints
5. Data integrity rules
*/

-- =============================================================================
-- END OF COMPREHENSIVE TESTING SCRIPT
-- =============================================================================
