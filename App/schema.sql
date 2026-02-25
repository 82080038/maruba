CREATE DATABASE IF NOT EXISTS maruba CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE maruba;

-- Roles
CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  permissions JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role_id INT NOT NULL,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Members (nasabah/anggota)
CREATE TABLE members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  nik VARCHAR(20) NULL,
  phone VARCHAR(30) NULL,
  address TEXT NULL,
  lat DECIMAL(10,7) NULL,
  lng DECIMAL(10,7) NULL,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products (jenis pinjaman/simpanan)
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  type ENUM('loan','savings') DEFAULT 'loan',
  rate DECIMAL(5,2) DEFAULT 0,
  tenor_months INT DEFAULT 0,
  fee DECIMAL(12,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Loans
CREATE TABLE loans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  product_id INT NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  tenor_months INT NOT NULL,
  rate DECIMAL(5,2) DEFAULT 0,
  status ENUM('draft','survey','review','approved','disbursed','closed','default') DEFAULT 'draft',
  assigned_surveyor_id INT NULL,
  assigned_collector_id INT NULL,
  approved_by INT NULL,
  disbursed_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (member_id) REFERENCES members(id),
  FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Surveys
CREATE TABLE surveys (
  id INT AUTO_INCREMENT PRIMARY KEY,
  loan_id INT NOT NULL,
  surveyor_id INT NOT NULL,
  result TEXT,
  score INT NULL,
  geo_lat DECIMAL(10,7) NULL,
  geo_lng DECIMAL(10,7) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (loan_id) REFERENCES loans(id)
);

-- Repayments
CREATE TABLE repayments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  loan_id INT NOT NULL,
  due_date DATE NOT NULL,
  paid_date DATE NULL,
  amount_due DECIMAL(15,2) NOT NULL,
  amount_paid DECIMAL(15,2) DEFAULT 0,
  method VARCHAR(50) NULL,
  proof_path VARCHAR(255) NULL,
  collector_id INT NULL,
  status ENUM('due','paid','late','partial') DEFAULT 'due',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (loan_id) REFERENCES loans(id)
);

-- Documents
CREATE TABLE loan_docs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  loan_id INT NOT NULL,
  doc_type VARCHAR(50) NOT NULL,
  path VARCHAR(255) NOT NULL,
  uploaded_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (loan_id) REFERENCES loans(id)
);

-- Audit Logs
CREATE TABLE audit_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(100) NOT NULL,
  entity VARCHAR(100) NULL,
  entity_id INT NULL,
  meta JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Savings Accounts
CREATE TABLE savings_accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  account_number VARCHAR(20) NOT NULL UNIQUE,
  type ENUM('pokok','wajib','sukarela','sisuka') NOT NULL,
  balance DECIMAL(15,2) DEFAULT 0,
  interest_rate DECIMAL(5,2) DEFAULT 0,
  status ENUM('active','inactive','closed') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (member_id) REFERENCES members(id)
);

-- Savings Transactions
CREATE TABLE savings_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  savings_account_id INT NOT NULL,
  type ENUM('deposit','withdrawal','interest','transfer') NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  balance_before DECIMAL(15,2) NOT NULL,
  balance_after DECIMAL(15,2) NOT NULL,
  description TEXT NULL,
  transaction_date DATE NOT NULL,
  processed_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (savings_account_id) REFERENCES savings_accounts(id),
  FOREIGN KEY (processed_by) REFERENCES users(id)
);

-- SHU Calculations
CREATE TABLE shu_calculations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  period_year YEAR NOT NULL,
  total_profit DECIMAL(15,2) NOT NULL,
  total_shu DECIMAL(15,2) NOT NULL,
  shu_percentage DECIMAL(5,2) NOT NULL,
  calculation_date DATE NOT NULL,
  status ENUM('draft','approved','distributed') DEFAULT 'draft',
  approved_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- SHU Distributions
CREATE TABLE shu_distributions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  shu_calculation_id INT NOT NULL,
  member_id INT NOT NULL,
  savings_balance DECIMAL(15,2) NOT NULL,
  loan_balance DECIMAL(15,2) NOT NULL,
  shu_amount DECIMAL(15,2) NOT NULL,
  distributed_at TIMESTAMP NULL,
  status ENUM('pending','distributed') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (shu_calculation_id) REFERENCES shu_calculations(id),
  FOREIGN KEY (member_id) REFERENCES members(id)
);

-- Accounting Journals
CREATE TABLE accounting_journals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  transaction_date DATE NOT NULL,
  reference_number VARCHAR(50) NOT NULL UNIQUE,
  description TEXT NOT NULL,
  total_debit DECIMAL(15,2) DEFAULT 0,
  total_credit DECIMAL(15,2) DEFAULT 0,
  status ENUM('draft','posted','cancelled') DEFAULT 'draft',
  posted_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (posted_by) REFERENCES users(id)
);

-- Journal Entries
CREATE TABLE journal_entries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  journal_id INT NOT NULL,
  account_code VARCHAR(20) NOT NULL,
  account_name VARCHAR(100) NOT NULL,
  debit DECIMAL(15,2) DEFAULT 0,
  credit DECIMAL(15,2) DEFAULT 0,
  description TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (journal_id) REFERENCES accounting_journals(id)
);

-- Chart of Accounts
CREATE TABLE chart_of_accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  type ENUM('asset','liability','equity','income','expense') NOT NULL,
  category VARCHAR(50) NULL,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Document Templates
CREATE TABLE document_templates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  type VARCHAR(50) NOT NULL,
  template_content LONGTEXT NOT NULL,
  variables JSON NULL,
  is_active BOOLEAN DEFAULT TRUE,
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Generated Documents
CREATE TABLE generated_documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  template_id INT NOT NULL,
  reference_id INT NOT NULL,
  reference_type VARCHAR(50) NOT NULL,
  document_number VARCHAR(50) NOT NULL UNIQUE,
  file_path VARCHAR(255) NOT NULL,
  status ENUM('generated','signed','sent') DEFAULT 'generated',
  generated_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (template_id) REFERENCES document_templates(id),
  FOREIGN KEY (generated_by) REFERENCES users(id)
);

-- Payroll Records
CREATE TABLE payroll_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  period_month TINYINT NOT NULL,
  period_year YEAR NOT NULL,
  basic_salary DECIMAL(15,2) NOT NULL,
  allowances DECIMAL(15,2) DEFAULT 0,
  deductions DECIMAL(15,2) DEFAULT 0,
  net_salary DECIMAL(15,2) NOT NULL,
  status ENUM('draft','approved','paid') DEFAULT 'draft',
  approved_by INT NULL,
  paid_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES users(id),
  FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- Cooperative Registrations
CREATE TABLE cooperative_registrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cooperative_name VARCHAR(150) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  legal_type ENUM('koperasi_simpan_pinjam','koperasi_serba_usaha','koperasi_konsumen','koperasi_produsen') NOT NULL,
  registration_number VARCHAR(50) NULL,
  description TEXT NULL,
  address TEXT NOT NULL,
  province VARCHAR(50) NOT NULL,
  city VARCHAR(50) NOT NULL,
  postal_code VARCHAR(10) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  email VARCHAR(100) NOT NULL,
  website VARCHAR(100) NULL,
  established_date DATE NULL,
  chairman_name VARCHAR(100) NOT NULL,
  chairman_phone VARCHAR(20) NOT NULL,
  chairman_email VARCHAR(100) NULL,
  manager_name VARCHAR(100) NOT NULL,
  manager_phone VARCHAR(20) NOT NULL,
  manager_email VARCHAR(100) NULL,
  total_members INT DEFAULT 0,
  total_assets DECIMAL(15,2) DEFAULT 0,
  subscription_plan VARCHAR(50) DEFAULT 'starter',
  documents JSON NULL,
  status ENUM('draft','submitted','under_review','approved','rejected') DEFAULT 'draft',
  rejection_reason TEXT NULL,
  submitted_at TIMESTAMP NULL,
  reviewed_at TIMESTAMP NULL,
  approved_at TIMESTAMP NULL,
  approved_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- Cooperative Onboardings
CREATE TABLE cooperative_onboardings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  registration_id INT NOT NULL,
  tenant_id INT NOT NULL,
  admin_username VARCHAR(100) NOT NULL,
  admin_password VARCHAR(255) NOT NULL,
  setup_completed BOOLEAN DEFAULT FALSE,
  welcome_email_sent BOOLEAN DEFAULT FALSE,
  initial_config_done BOOLEAN DEFAULT FALSE,
  onboarding_steps JSON NULL,
  completed_at TIMESTAMP NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (registration_id) REFERENCES cooperative_registrations(id),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

-- Credit Analyses
CREATE TABLE credit_analyses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  loan_id INT NOT NULL,
  analyst_id INT NOT NULL,
  character_score DECIMAL(5,2) NOT NULL,
  capacity_score DECIMAL(5,2) NOT NULL,
  capital_score DECIMAL(5,2) NOT NULL,
  collateral_score DECIMAL(5,2) NOT NULL,
  condition_score DECIMAL(5,2) NOT NULL,
  total_score DECIMAL(5,2) NOT NULL,
  dsr_ratio DECIMAL(5,2) NOT NULL,
  recommendation TEXT NOT NULL,
  notes JSON NULL,
  status ENUM('pending','completed','reviewed') DEFAULT 'pending',
  reviewed_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (loan_id) REFERENCES loans(id),
  FOREIGN KEY (analyst_id) REFERENCES users(id),
  FOREIGN KEY (reviewed_by) REFERENCES users(id)
);

-- Subscription Plans
CREATE TABLE subscription_plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  display_name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  price_monthly DECIMAL(15,2) NOT NULL,
  price_yearly DECIMAL(15,2) NOT NULL,
  max_users INT DEFAULT 5,
  max_members INT DEFAULT 100,
  max_storage_gb INT DEFAULT 1,
  features JSON NULL,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default subscription plans
INSERT INTO subscription_plans (name, display_name, description, price_monthly, price_yearly, max_users, max_members, max_storage_gb, features) VALUES
('starter', 'Starter Plan', 'Plan dasar untuk koperasi kecil', 500000, 5000000, 3, 100, 1, '{"loans": true, "members": true, "reports": true, "email_support": true}'),
('professional', 'Professional Plan', 'Plan lengkap untuk koperasi menengah', 1500000, 15000000, 10, 1000, 5, '{"loans": true, "members": true, "reports": true, "api": true, "email_support": true, "phone_support": true}'),
('enterprise', 'Enterprise Plan', 'Plan enterprise untuk koperasi besar', 3000000, 30000000, 50, 10000, 50, '{"loans": true, "members": true, "reports": true, "api": true, "email_support": true, "phone_support": true, "dedicated_support": true, "custom_features": true}');

-- Tenant Feature Usage Tracking
CREATE TABLE tenant_feature_usage (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  feature_name VARCHAR(100) NOT NULL,
  usage_count INT DEFAULT 0,
  usage_limit INT DEFAULT 0,
  period_start DATE NOT NULL,
  period_end DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  UNIQUE KEY unique_tenant_feature_period (tenant_id, feature_name, period_start)
);

-- MULTI-TENANT KSP (KOPERASI SIMPAN PINJAM) SYSTEM SCHEMA
-- This schema supports multiple cooperatives with complete data isolation

-- =============================================================================
-- MAIN APPLICATION DATABASE (System Admin & Tenant Management)
-- =============================================================================

-- Cooperative Registrations (for new cooperative applications)
CREATE TABLE cooperative_registrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cooperative_name VARCHAR(150) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  legal_type ENUM('koperasi_simpan_pinjam','koperasi_serba_usaha','koperasi_konsumen','koperasi_produsen') NOT NULL,
  registration_number VARCHAR(50) NULL,
  description TEXT NULL,
  address TEXT NOT NULL,
  province VARCHAR(50) NOT NULL,
  city VARCHAR(50) NOT NULL,
  postal_code VARCHAR(10) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  email VARCHAR(100) NOT NULL,
  website VARCHAR(100) NULL,
  established_date DATE NULL,
  chairman_name VARCHAR(100) NOT NULL,
  chairman_phone VARCHAR(20) NOT NULL,
  chairman_email VARCHAR(100) NULL,
  manager_name VARCHAR(100) NOT NULL,
  manager_phone VARCHAR(20) NOT NULL,
  manager_email VARCHAR(100) NULL,
  total_members INT DEFAULT 0,
  total_assets DECIMAL(15,2) DEFAULT 0,
  subscription_plan VARCHAR(50) DEFAULT 'starter',
  documents JSON NULL,
  status ENUM('draft','submitted','under_review','approved','rejected') DEFAULT 'draft',
  rejection_reason TEXT NULL,
  submitted_at TIMESTAMP NULL,
  reviewed_at TIMESTAMP NULL,
  approved_at TIMESTAMP NULL,
  approved_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- Tenants (Active Cooperatives)
CREATE TABLE tenants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  registration_id INT NULL,
  name VARCHAR(150) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  description TEXT NULL,
  logo_path VARCHAR(255) NULL,
  favicon_path VARCHAR(255) NULL,
  status ENUM('active','inactive','suspended','trial','expired') DEFAULT 'active',
  subscription_plan VARCHAR(50) DEFAULT 'starter',
  billing_cycle ENUM('monthly','yearly') DEFAULT 'monthly',
  trial_ends_at TIMESTAMP NULL,
  subscription_ends_at TIMESTAMP NULL,
  max_users INT DEFAULT 5,
  max_members INT DEFAULT 100,
  max_storage_gb INT DEFAULT 1,
  -- Cooperative Profile Fields
  legal_documents JSON NULL,
  board_members JSON NULL,
  registration_number VARCHAR(50) NULL,
  tax_id VARCHAR(20) NULL,
  business_license VARCHAR(50) NULL,
  chairman_details JSON NULL,
  manager_details JSON NULL,
  secretary_details JSON NULL,
  treasurer_details JSON NULL,
  address_details JSON NULL,
  operating_hours JSON NULL,
  social_media JSON NULL,
  theme_settings JSON NULL,
  branding_settings JSON NULL,
  ui_preferences JSON NULL,
  logo_path VARCHAR(255) NULL,
  favicon_path VARCHAR(255) NULL,
  last_profile_update TIMESTAMP NULL,
  profile_completion_percentage INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (registration_id) REFERENCES cooperative_registrations(id)
);

-- Tenant Billings
CREATE TABLE tenant_billings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  currency VARCHAR(3) DEFAULT 'IDR',
  billing_period_start DATE NOT NULL,
  billing_period_end DATE NOT NULL,
  status ENUM('pending','paid','overdue','cancelled','failed') DEFAULT 'pending',
  payment_method VARCHAR(50) NULL,
  payment_reference VARCHAR(100) NULL,
  payment_date TIMESTAMP NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

-- Subscription Plans
CREATE TABLE subscription_plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  display_name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  price_monthly DECIMAL(15,2) NOT NULL,
  price_yearly DECIMAL(15,2) NOT NULL,
  max_users INT DEFAULT 5,
  max_members INT DEFAULT 100,
  max_storage_gb INT DEFAULT 1,
  features JSON NULL,
  is_active BOOLEAN DEFAULT TRUE,
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default subscription plans
INSERT INTO subscription_plans (name, display_name, description, price_monthly, price_yearly, max_users, max_members, max_storage_gb, features, sort_order) VALUES
('starter', 'Starter Plan', 'Plan dasar untuk koperasi kecil', 500000, 5000000, 3, 100, 1, '{"loans": true, "members": true, "reports": true, "email_support": true}', 1),
('professional', 'Professional Plan', 'Plan lengkap untuk koperasi menengah', 1500000, 15000000, 10, 1000, 5, '{"loans": true, "members": true, "reports": true, "api": true, "email_support": true, "phone_support": true, "savings": true, "shu": true}', 2),
('enterprise', 'Enterprise Plan', 'Plan enterprise untuk koperasi besar', 3000000, 30000000, 50, 10000, 50, '{"loans": true, "members": true, "reports": true, "api": true, "email_support": true, "phone_support": true, "dedicated_support": true, "custom_features": true, "savings": true, "shu": true, "accounting": true, "payroll": true, "compliance": true}', 3);

-- Tenant Feature Usage Tracking
CREATE TABLE tenant_feature_usage (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  feature_name VARCHAR(100) NOT NULL,
  usage_count INT DEFAULT 0,
  usage_limit INT DEFAULT 0,
  period_start DATE NOT NULL,
  period_end DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  UNIQUE KEY unique_tenant_feature_period (tenant_id, feature_name, period_start)
);

-- Cooperative Onboardings
CREATE TABLE cooperative_onboardings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  registration_id INT NOT NULL,
  tenant_id INT NOT NULL,
  admin_username VARCHAR(100) NOT NULL,
  admin_password VARCHAR(255) NOT NULL,
  setup_completed BOOLEAN DEFAULT FALSE,
  welcome_email_sent BOOLEAN DEFAULT FALSE,
  initial_config_done BOOLEAN DEFAULT FALSE,
  onboarding_steps JSON NULL,
  completed_at TIMESTAMP NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (registration_id) REFERENCES cooperative_registrations(id),
  FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

-- Navigation Menus
CREATE TABLE navigation_menus (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NULL,
  menu_key VARCHAR(100) NOT NULL,
  title VARCHAR(150) NOT NULL,
  icon VARCHAR(100) NULL,
  route VARCHAR(255) NULL,
  parent_id INT NULL,
  `order` INT DEFAULT 0,
  is_active BOOLEAN DEFAULT TRUE,
  permissions JSON NULL,
  custom_data JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  FOREIGN KEY (parent_id) REFERENCES navigation_menus(id) ON DELETE CASCADE,
  UNIQUE KEY unique_tenant_menu (tenant_id, menu_key)
);

-- System Users (Admin users for main application)
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NULL, -- NULL for system admins, specific ID for tenant users
  name VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  email VARCHAR(100) NULL,
  phone VARCHAR(20) NULL,
  role ENUM('super_admin','admin','manager','kasir','surveyor','collector','member') DEFAULT 'member',
  status ENUM('active','inactive','suspended') DEFAULT 'active',
  permissions JSON NULL,
  last_login TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

-- Notification Logs
CREATE TABLE notification_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NULL,
  recipient_type ENUM('member','user','external') NOT NULL,
  recipient_id INT NULL,
  channel ENUM('email','whatsapp','sms','push') NOT NULL,
  subject VARCHAR(255) NULL,
  message TEXT NOT NULL,
  status ENUM('sent','delivered','failed') DEFAULT 'sent',
  error_message TEXT NULL,
  sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  delivered_at TIMESTAMP NULL,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);

-- Audit Logs
CREATE TABLE audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NULL,
  user_id INT NULL,
  action VARCHAR(100) NOT NULL,
  resource_type VARCHAR(50) NULL,
  resource_id INT NULL,
  old_values JSON NULL,
  new_values JSON NULL,
  ip_address VARCHAR(45) NULL,
  user_agent TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- =============================================================================
-- TENANT DATABASE SCHEMAS (Per Cooperative Database)
-- These tables are created in each tenant's isolated database
-- =============================================================================

-- Members (Anggota Koperasi)
CREATE TABLE members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_number VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  nik VARCHAR(20) NOT NULL UNIQUE,
  phone VARCHAR(20) NOT NULL,
  email VARCHAR(100) NULL,
  address TEXT NOT NULL,
  province VARCHAR(50) NOT NULL,
  city VARCHAR(50) NOT NULL,
  district VARCHAR(50) NULL,
  village VARCHAR(50) NULL,
  postal_code VARCHAR(10) NULL,
  birth_date DATE NOT NULL,
  birth_place VARCHAR(50) NOT NULL,
  gender ENUM('L','P') NOT NULL,
  marital_status ENUM('single','married','divorced','widowed') DEFAULT 'single',
  religion ENUM('islam','christian','catholic','hindu','buddhist','other') DEFAULT 'islam',
  occupation VARCHAR(100) NULL,
  monthly_income DECIMAL(15,2) DEFAULT 0,
  education ENUM('sd','smp','sma','diploma','sarjana','magister','doktor') DEFAULT 'sma',
  ktp_photo_path VARCHAR(255) NULL,
  kk_photo_path VARCHAR(255) NULL,
  selfie_photo_path VARCHAR(255) NULL,
  latitude DECIMAL(10,8) NULL,
  longitude DECIMAL(11,8) NULL,
  status ENUM('draft','pending_verification','active','inactive','suspended','blacklisted') DEFAULT 'draft',
  verification_status ENUM('pending','verified','rejected') DEFAULT 'pending',
  verified_at TIMESTAMP NULL,
  verified_by INT NULL,
  joined_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Savings Products
CREATE TABLE savings_products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  type ENUM('pokok','wajib','sukarela','investasi','berjangka') NOT NULL,
  description TEXT NULL,
  minimum_balance DECIMAL(15,2) DEFAULT 0,
  interest_rate DECIMAL(5,2) DEFAULT 0,
  interest_calculation ENUM('monthly','yearly','end_of_term') DEFAULT 'monthly',
  term_months INT DEFAULT 0,
  early_withdrawal_penalty DECIMAL(5,2) DEFAULT 0,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Savings Accounts
CREATE TABLE savings_accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  product_id INT NOT NULL,
  account_number VARCHAR(20) NOT NULL UNIQUE,
  balance DECIMAL(15,2) DEFAULT 0,
  interest_accrued DECIMAL(15,2) DEFAULT 0,
  last_interest_calculation DATE NULL,
  status ENUM('active','inactive','frozen','closed') DEFAULT 'active',
  opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  closed_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (member_id) REFERENCES members(id),
  FOREIGN KEY (product_id) REFERENCES savings_products(id)
);

-- Savings Transactions
CREATE TABLE savings_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  account_id INT NOT NULL,
  member_id INT NOT NULL,
  type ENUM('deposit','withdrawal','interest','fee','transfer') NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  balance_before DECIMAL(15,2) NOT NULL,
  balance_after DECIMAL(15,2) NOT NULL,
  reference_number VARCHAR(50) NULL,
  transaction_date DATE NOT NULL,
  processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  processed_by INT NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (account_id) REFERENCES savings_accounts(id),
  FOREIGN KEY (member_id) REFERENCES members(id),
  FOREIGN KEY (processed_by) REFERENCES users(id)
);

-- Loan Products
CREATE TABLE loan_products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  type ENUM('produktif','konsumtif','darurat','modal_kerja','renovasi','pendidikan') NOT NULL,
  description TEXT NULL,
  min_amount DECIMAL(15,2) NOT NULL,
  max_amount DECIMAL(15,2) NOT NULL,
  min_tenor INT NOT NULL,
  max_tenor INT NOT NULL,
  interest_rate DECIMAL(5,2) NOT NULL,
  interest_type ENUM('flat','effective') DEFAULT 'flat',
  admin_fee DECIMAL(15,2) DEFAULT 0,
  insurance_fee DECIMAL(15,2) DEFAULT 0,
  provision_fee DECIMAL(15,2) DEFAULT 0,
  grace_period_days INT DEFAULT 0,
  late_payment_penalty DECIMAL(5,2) DEFAULT 0,
  early_payment_discount DECIMAL(5,2) DEFAULT 0,
  required_documents JSON NULL,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Loans
CREATE TABLE loans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  loan_number VARCHAR(20) NOT NULL UNIQUE,
  member_id INT NOT NULL,
  product_id INT NOT NULL,
  principal_amount DECIMAL(15,2) NOT NULL,
  interest_rate DECIMAL(5,2) NOT NULL,
  interest_type ENUM('flat','effective') DEFAULT 'flat',
  tenor_months INT NOT NULL,
  monthly_installment DECIMAL(15,2) NOT NULL,
  total_amount DECIMAL(15,2) NOT NULL,
  outstanding_balance DECIMAL(15,2) NOT NULL,
  purpose TEXT NULL,
  collateral_details JSON NULL,
  status ENUM('draft','submitted','survey_pending','survey_completed','approved','rejected','disbursed','active','completed','defaulted','written_off') DEFAULT 'draft',
  application_date DATE NOT NULL,
  approval_date DATE NULL,
  disbursement_date DATE NULL,
  completion_date DATE NULL,
  survey_date DATE NULL,
  surveyed_by INT NULL,
  approved_by INT NULL,
  disbursed_by INT NULL,
  rejection_reason TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (member_id) REFERENCES members(id),
  FOREIGN KEY (product_id) REFERENCES loan_products(id),
  FOREIGN KEY (surveyed_by) REFERENCES users(id),
  FOREIGN KEY (approved_by) REFERENCES users(id),
  FOREIGN KEY (disbursed_by) REFERENCES users(id)
);

-- Loan Documents
CREATE TABLE loan_documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  loan_id INT NOT NULL,
  document_type ENUM('ktp','kk','slip_gaji','bukti_usaha','agunan','foto_rumah','surat_izin','lainnya') NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  original_filename VARCHAR(255) NOT NULL,
  file_size INT NOT NULL,
  uploaded_by INT NOT NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  verified BOOLEAN DEFAULT FALSE,
  verified_by INT NULL,
  verified_at TIMESTAMP NULL,
  notes TEXT NULL,
  FOREIGN KEY (loan_id) REFERENCES loans(id),
  FOREIGN KEY (uploaded_by) REFERENCES users(id),
  FOREIGN KEY (verified_by) REFERENCES users(id)
);

-- Credit Analysis
CREATE TABLE credit_analyses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  loan_id INT NOT NULL,
  analyst_id INT NOT NULL,
  character_score DECIMAL(5,2) NOT NULL,
  capacity_score DECIMAL(5,2) NOT NULL,
  capital_score DECIMAL(5,2) NOT NULL,
  collateral_score DECIMAL(5,2) NOT NULL,
  condition_score DECIMAL(5,2) NOT NULL,
  total_score DECIMAL(5,2) NOT NULL,
  dsr_ratio DECIMAL(5,2) NOT NULL,
  recommendation ENUM('approve','reject','review','conditional') NOT NULL,
  recommendation_reason TEXT NULL,
  risk_level ENUM('low','medium','high','very_high') DEFAULT 'medium',
  analysis_date DATE NOT NULL,
  reviewed_by INT NULL,
  reviewed_at TIMESTAMP NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (loan_id) REFERENCES loans(id),
  FOREIGN KEY (analyst_id) REFERENCES users(id),
  FOREIGN KEY (reviewed_by) REFERENCES users(id)
);

-- Loan Repayments
CREATE TABLE loan_repayments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  loan_id INT NOT NULL,
  member_id INT NOT NULL,
  installment_number INT NOT NULL,
  due_date DATE NOT NULL,
  amount_due DECIMAL(15,2) NOT NULL,
  principal_amount DECIMAL(15,2) NOT NULL,
  interest_amount DECIMAL(15,2) NOT NULL,
  amount_paid DECIMAL(15,2) DEFAULT 0,
  paid_date DATE NULL,
  payment_method ENUM('cash','transfer','virtual_account','auto_debit') NULL,
  payment_reference VARCHAR(100) NULL,
  status ENUM('pending','paid','late','partial','waived') DEFAULT 'pending',
  late_days INT DEFAULT 0,
  penalty_amount DECIMAL(15,2) DEFAULT 0,
  collected_by INT NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (loan_id) REFERENCES loans(id),
  FOREIGN KEY (member_id) REFERENCES members(id),
  FOREIGN KEY (collected_by) REFERENCES users(id)
);

-- Accounting System
CREATE TABLE chart_of_accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  type ENUM('asset','liability','equity','income','expense') NOT NULL,
  category VARCHAR(50) NULL,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Journal Entries
CREATE TABLE journal_entries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  journal_number VARCHAR(20) NOT NULL UNIQUE,
  transaction_date DATE NOT NULL,
  description TEXT NOT NULL,
  reference_type ENUM('loan','savings','repayment','fee','adjustment','other') DEFAULT 'other',
  reference_id INT NULL,
  status ENUM('draft','posted','cancelled') DEFAULT 'draft',
  posted_by INT NULL,
  posted_at TIMESTAMP NULL,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (posted_by) REFERENCES users(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Journal Lines
CREATE TABLE journal_lines (
  id INT AUTO_INCREMENT PRIMARY KEY,
  journal_id INT NOT NULL,
  account_id INT NOT NULL,
  debit DECIMAL(15,2) DEFAULT 0,
  credit DECIMAL(15,2) DEFAULT 0,
  description TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (journal_id) REFERENCES journal_entries(id),
  FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id)
);

-- Payroll System
CREATE TABLE employees (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  employee_number VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  position VARCHAR(100) NOT NULL,
  department VARCHAR(50) NULL,
  basic_salary DECIMAL(15,2) NOT NULL,
  allowances JSON NULL,
  deductions JSON NULL,
  bank_account VARCHAR(50) NULL,
  bank_name VARCHAR(100) NULL,
  tax_id VARCHAR(20) NULL,
  join_date DATE NOT NULL,
  status ENUM('active','inactive','terminated') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Payroll Records
CREATE TABLE payroll_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id INT NOT NULL,
  period_start DATE NOT NULL,
  period_end DATE NOT NULL,
  basic_salary DECIMAL(15,2) NOT NULL,
  allowances DECIMAL(15,2) DEFAULT 0,
  deductions DECIMAL(15,2) DEFAULT 0,
  overtime DECIMAL(15,2) DEFAULT 0,
  gross_salary DECIMAL(15,2) NOT NULL,
  tax_amount DECIMAL(15,2) DEFAULT 0,
  net_salary DECIMAL(15,2) NOT NULL,
  status ENUM('draft','approved','paid') DEFAULT 'draft',
  approved_by INT NULL,
  paid_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (employee_id) REFERENCES employees(id),
  FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- Document Templates
CREATE TABLE document_templates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  type ENUM('loan_agreement','skb','somasi','receipt','report','letter') NOT NULL,
  template_content LONGTEXT NOT NULL,
  variables JSON NULL,
  is_active BOOLEAN DEFAULT TRUE,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Generated Documents
CREATE TABLE generated_documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  template_id INT NULL,
  reference_type ENUM('loan','member','payroll','report') NOT NULL,
  reference_id INT NOT NULL,
  document_number VARCHAR(50) NOT NULL,
  title VARCHAR(200) NOT NULL,
  content LONGTEXT NOT NULL,
  file_path VARCHAR(255) NULL,
  format ENUM('html','pdf','docx') DEFAULT 'html',
  status ENUM('draft','generated','sent','archived') DEFAULT 'draft',
  generated_by INT NOT NULL,
  generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  sent_at TIMESTAMP NULL,
  archived_at TIMESTAMP NULL,
  FOREIGN KEY (template_id) REFERENCES document_templates(id),
  FOREIGN KEY (generated_by) REFERENCES users(id)
);

-- Compliance & Risk Management
CREATE TABLE compliance_checks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  check_type ENUM('member_verification','loan_limits','dsr_compliance','collateral_valuation','audit_trail','regulatory_reporting') NOT NULL,
  reference_type ENUM('member','loan','system','periodical') DEFAULT 'system',
  reference_id INT NULL,
  status ENUM('passed','warning','failed') DEFAULT 'passed',
  severity ENUM('low','medium','high','critical') DEFAULT 'low',
  description TEXT NOT NULL,
  findings TEXT NULL,
  recommendations TEXT NULL,
  checked_by INT NULL,
  checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  resolved_at TIMESTAMP NULL,
  resolved_by INT NULL,
  notes TEXT NULL,
  FOREIGN KEY (checked_by) REFERENCES users(id),
  FOREIGN KEY (resolved_by) REFERENCES users(id)
);

-- Risk Assessments
CREATE TABLE risk_assessments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  assessment_type ENUM('portfolio','member','loan','system') NOT NULL,
  reference_id INT NULL,
  risk_score DECIMAL(5,2) DEFAULT 0,
  risk_level ENUM('low','medium','high','critical') DEFAULT 'low',
  risk_factors JSON NULL,
  mitigation_plan TEXT NULL,
  assessed_by INT NOT NULL,
  assessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  review_date DATE NULL,
  status ENUM('active','mitigated','closed') DEFAULT 'active',
  FOREIGN KEY (assessed_by) REFERENCES users(id)
);

-- Payment Gateway Integration
CREATE TABLE payment_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reference_type ENUM('loan_repayment','savings_deposit','fee','other') NOT NULL,
  reference_id INT NOT NULL,
  member_id INT NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  payment_method ENUM('virtual_account','bank_transfer','cash','e_wallet','auto_debit') NOT NULL,
  gateway_provider VARCHAR(50) NULL,
  transaction_id VARCHAR(100) NULL,
  gateway_reference VARCHAR(100) NULL,
  status ENUM('pending','processing','completed','failed','cancelled','refunded') DEFAULT 'pending',
  payment_date TIMESTAMP NULL,
  confirmation_date TIMESTAMP NULL,
  failure_reason TEXT NULL,
  processed_by INT NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (member_id) REFERENCES members(id),
  FOREIGN KEY (processed_by) REFERENCES users(id)
);

-- API Keys & Access Control
CREATE TABLE api_keys (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  api_key VARCHAR(128) NOT NULL UNIQUE,
  secret_key VARCHAR(128) NOT NULL,
  permissions JSON NULL,
  rate_limit INT DEFAULT 1000,
  is_active BOOLEAN DEFAULT TRUE,
  expires_at TIMESTAMP NULL,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_used TIMESTAMP NULL,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Insert default data
INSERT INTO savings_products (name, type, minimum_balance, interest_rate, interest_calculation, is_active) VALUES
('Simpanan Pokok', 'pokok', 50000, 0, 'monthly', true),
('Simpanan Wajib', 'wajib', 0, 0, 'monthly', true),
('Simpanan Sukarela', 'sukarela', 0, 3, 'monthly', true),
('SISUKA', 'investasi', 100000, 6, 'yearly', true);

INSERT INTO loan_products (name, type, min_amount, max_amount, min_tenor, max_tenor, interest_rate, admin_fee, is_active) VALUES
('Pinjaman Produktif', 'produktif', 1000000, 50000000, 6, 24, 1.5, 50000, true),
('Pinjaman Konsumtif', 'konsumtif', 1000000, 20000000, 6, 36, 1.8, 75000, true),
('Pinjaman Darurat', 'darurat', 500000, 5000000, 3, 12, 2.5, 25000, true);

INSERT INTO chart_of_accounts (code, name, type, category) VALUES
('1001', 'Kas', 'asset', 'current'),
('1002', 'Bank', 'asset', 'current'),
('2001', 'Simpanan Anggota', 'liability', 'member_equity'),
('3001', 'Modal Sendiri', 'equity', 'equity'),
('4001', 'Pendapatan Bunga Pinjaman', 'income', 'interest_income'),
('4002', 'Pendapatan Bunga Simpanan', 'income', 'interest_income'),
('5001', 'Beban Bunga Simpanan', 'expense', 'interest_expense'),
('5002', 'Beban Operasional', 'expense', 'operating_expense'),
('5003', 'Beban Administrasi', 'expense', 'admin_expense');

-- SHU (Sisa Hasil Usaha) System
CREATE TABLE shu_calculations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  period_year INT NOT NULL,
  total_profit DECIMAL(15,2) NOT NULL,
  total_shu DECIMAL(15,2) NOT NULL,
  shu_percentage DECIMAL(5,2) DEFAULT 40.00,
  distribution_rules JSON NULL,
  distribution_amounts JSON NULL,
  distribution_date DATE NULL,
  status ENUM('draft','approved','distributed') DEFAULT 'draft',
  approved_by INT NULL,
  approved_at TIMESTAMP NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (approved_by) REFERENCES users(id)
);

CREATE TABLE shu_allocations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  shu_id INT NOT NULL,
  member_id INT NOT NULL,
  allocation_amount DECIMAL(15,2) NOT NULL,
  weight DECIMAL(10,4) NOT NULL,
  distributed BOOLEAN DEFAULT FALSE,
  distributed_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (shu_id) REFERENCES shu_calculations(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

-- Insert default SHU calculation for 2024
INSERT INTO shu_calculations (period_year, total_profit, total_shu, shu_percentage, distribution_rules, distribution_amounts, status) VALUES
(2024, 50000000, 20000000, 40.00,
 '{"member_dividend": 40, "loan_interest": 30, "reserve_fund": 15, "education_fund": 10, "social_fund": 5}',
 '{"member_dividend": 8000000, "loan_interest": 6000000, "reserve_fund": 3000000, "education_fund": 2000000, "social_fund": 1000000}',
 'draft');

-- Tenants (for multi-tenant system)
CREATE TABLE tenants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  description TEXT NULL,
  logo_path VARCHAR(255) NULL,
  status ENUM('active','inactive','suspended') DEFAULT 'active',
  subscription_plan VARCHAR(50) DEFAULT 'starter',
  billing_cycle ENUM('monthly','yearly') DEFAULT 'monthly',
  trial_ends_at TIMESTAMP NULL,
  subscription_ends_at TIMESTAMP NULL,
  max_members INT DEFAULT 100,
  max_storage_gb INT DEFAULT 1,
  -- Additional cooperative profile fields
  legal_documents JSON NULL,
  board_members JSON NULL,
  registration_number VARCHAR(50) NULL,
  tax_id VARCHAR(20) NULL,
  business_license VARCHAR(50) NULL,
  chairman_details JSON NULL,
  manager_details JSON NULL,
  secretary_details JSON NULL,
  treasurer_details JSON NULL,
  address_details JSON NULL,
  operating_hours JSON NULL,
  social_media JSON NULL,
  -- Tenant customization fields
  theme_settings JSON NULL,
  branding_settings JSON NULL,
  ui_preferences JSON NULL,
  logo_path VARCHAR(255) NULL,
  favicon_path VARCHAR(255) NULL,
  last_profile_update TIMESTAMP NULL,
  profile_completion_percentage INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tenant Billings
CREATE TABLE tenant_billings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  currency VARCHAR(3) DEFAULT 'IDR',
  billing_period_start DATE NOT NULL,
  billing_period_end DATE NOT NULL,
  status ENUM('pending','paid','overdue','cancelled') DEFAULT 'pending',
  payment_method VARCHAR(50) NULL,
  payment_date TIMESTAMP NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
);

-- Tenant Backups
CREATE TABLE tenant_backups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT NOT NULL,
  backup_name VARCHAR(200) NOT NULL,
  backup_path VARCHAR(500) NOT NULL,
  backup_size BIGINT DEFAULT 0,
  status ENUM('pending','completed','failed','restored') DEFAULT 'pending',
  backup_type ENUM('full','incremental') DEFAULT 'full',
  created_by INT NULL,
  restored_at TIMESTAMP NULL,
  restored_by INT NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id),
  FOREIGN KEY (restored_by) REFERENCES users(id)
);
