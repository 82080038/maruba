-- =============================================================================
-- ADVANCED ACCOUNTING COMPLIANCE - SAK/PSAK/IFRS IMPLEMENTATION
-- =============================================================================

-- Disable foreign key checks for schema updates
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================================
-- 1. CHART OF ACCOUNTS - SAK COMPLIANCE
-- =============================================================================

CREATE TABLE IF NOT EXISTS `chart_of_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `account_code` varchar(20) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_type` enum('asset','liability','equity','revenue','expense','contra_asset','contra_liability','contra_equity','contra_revenue','contra_expense') NOT NULL,
  `category` enum('current_asset','fixed_asset','current_liability','long_term_liability','equity','operating_revenue','other_revenue','cost_of_goods_sold','operating_expense','other_expense') NOT NULL,
  `sub_category` varchar(100) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `level` int(11) DEFAULT 1,
  `is_active` boolean DEFAULT TRUE,
  `normal_balance` enum('debit','credit') DEFAULT 'debit',
  `description` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_account_code_tenant` (`account_code`, `tenant_id`),
  KEY `idx_tenant_parent` (`tenant_id`, `parent_id`),
  KEY `idx_account_type` (`account_type`, `category`),
  CONSTRAINT `fk_coa_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_coa_parent` FOREIGN KEY (`parent_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- 2. JOURNAL ENTRIES - DOUBLE ENTRY BOOKKEEPING
-- =============================================================================

CREATE TABLE IF NOT EXISTS `journal_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `entry_number` varchar(20) NOT NULL,
  `entry_date` date NOT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `description` text NOT NULL,
  `source` enum('manual','system','import','auto_adjustment') DEFAULT 'manual',
  `status` enum('draft','posted','voided') DEFAULT 'draft',
  `posted_by` int(11) DEFAULT NULL,
  `posted_at` timestamp NULL,
  `voided_by` int(11) DEFAULT NULL,
  `voided_at` timestamp NULL,
  `void_reason` text,
  `total_debit` decimal(15,2) DEFAULT 0.00,
  `total_credit` decimal(15,2) DEFAULT 0.00,
  `is_balanced` boolean DEFAULT FALSE,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_entry_number_tenant` (`entry_number`, `tenant_id`),
  KEY `idx_tenant_date` (`tenant_id`, `entry_date`),
  KEY `idx_status_date` (`status`, `entry_date`),
  CONSTRAINT `fk_journal_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_journal_posted_by` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_journal_voided_by` FOREIGN KEY (`voided_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- 3. JOURNAL LINES - INDIVIDUAL ACCOUNT ENTRIES
-- =============================================================================

CREATE TABLE IF NOT EXISTS `journal_lines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `journal_entry_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `debit_amount` decimal(15,2) DEFAULT 0.00,
  `credit_amount` decimal(15,2) DEFAULT 0.00,
  `description` text,
  `reference` varchar(100) DEFAULT NULL,
  `line_number` int(11) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_journal_account` (`journal_entry_id`, `account_id`),
  KEY `idx_account_date` (`account_id`, `created_at`),
  CONSTRAINT `fk_jl_journal` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jl_account` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- 4. FIXED ASSETS - PSAK 71 COMPLIANCE
-- =============================================================================

CREATE TABLE IF NOT EXISTS `fixed_assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `asset_code` varchar(20) NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `asset_category` enum('tangible','intangible','financial') DEFAULT 'tangible',
  `sub_category` varchar(100) DEFAULT NULL,
  `purchase_date` date NOT NULL,
  `purchase_cost` decimal(15,2) NOT NULL,
  `accumulated_depreciation` decimal(15,2) DEFAULT 0.00,
  `book_value` decimal(15,2) DEFAULT 0.00,
  `useful_life_years` int(11) NOT NULL,
  `depreciation_method` enum('straight_line','declining_balance','units_of_production') DEFAULT 'straight_line',
  `depreciation_rate` decimal(5,2) DEFAULT 0.00,
  `salvage_value` decimal(15,2) DEFAULT 0.00,
  `location` varchar(255) DEFAULT NULL,
  `responsible_person` varchar(150) DEFAULT NULL,
  `status` enum('active','disposed','fully_depreciated') DEFAULT 'active',
  `disposal_date` date DEFAULT NULL,
  `disposal_value` decimal(15,2) DEFAULT 0.00,
  `coa_asset_id` int(11) DEFAULT NULL,
  `coa_accum_dep_id` int(11) DEFAULT NULL,
  `coa_expense_id` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_asset_code_tenant` (`asset_code`, `tenant_id`),
  KEY `idx_tenant_category` (`tenant_id`, `asset_category`),
  KEY `idx_status_date` (`status`, `purchase_date`),
  CONSTRAINT `fk_fa_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fa_coa_asset` FOREIGN KEY (`coa_asset_id`) REFERENCES `chart_of_accounts` (`id`),
  CONSTRAINT `fk_fa_coa_accum` FOREIGN KEY (`coa_accum_dep_id`) REFERENCES `chart_of_accounts` (`id`),
  CONSTRAINT `fk_fa_coa_expense` FOREIGN KEY (`coa_expense_id`) REFERENCES `chart_of_accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- 5. ASSET DEPRECIATION SCHEDULE - PSAK 71
-- =============================================================================

CREATE TABLE IF NOT EXISTS `asset_depreciation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `depreciation_date` date NOT NULL,
  `depreciation_amount` decimal(15,2) NOT NULL,
  `accumulated_amount` decimal(15,2) NOT NULL,
  `book_value_after` decimal(15,2) NOT NULL,
  `journal_entry_id` int(11) DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_asset_date` (`asset_id`, `depreciation_date`),
  KEY `idx_date_amount` (`depreciation_date`, `depreciation_amount`),
  CONSTRAINT `fk_ad_asset` FOREIGN KEY (`asset_id`) REFERENCES `fixed_assets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ad_journal` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`),
  CONSTRAINT `fk_ad_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- 6. FINANCIAL INSTRUMENTS - IFRS 9 COMPLIANCE
-- =============================================================================

CREATE TABLE IF NOT EXISTS `financial_instruments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `instrument_type` enum('loan','investment','derivative','other') DEFAULT 'loan',
  `reference_id` int(11) DEFAULT NULL, -- Links to loans, investments, etc.
  `reference_type` varchar(50) DEFAULT NULL, -- 'loan', 'investment', etc.
  `classification` enum('amortized_cost','fvoci','fvtpl') DEFAULT 'amortized_cost',
  `carrying_amount` decimal(15,2) NOT NULL,
  `effective_interest_rate` decimal(5,2) DEFAULT 0.00,
  `expected_credit_losses` decimal(15,2) DEFAULT 0.00,
  `impairment_stage` enum('stage_1','stage_2','stage_3') DEFAULT 'stage_1',
  `loss_allowance` decimal(15,2) DEFAULT 0.00,
  `fair_value` decimal(15,2) DEFAULT NULL,
  `maturity_date` date DEFAULT NULL,
  `risk_rating` enum('low','medium','high','very_high') DEFAULT 'medium',
  `last_assessment_date` date DEFAULT NULL,
  `next_assessment_date` date DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_type` (`tenant_id`, `instrument_type`),
  KEY `idx_classification` (`classification`, `impairment_stage`),
  KEY `idx_maturity` (`maturity_date`),
  CONSTRAINT `fk_fi_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- 7. AUTOMATIC JOURNAL ADJUSTMENTS
-- =============================================================================

CREATE TABLE IF NOT EXISTS `journal_adjustments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `adjustment_type` enum('accrual','deferral','depreciation','impairment','revaluation','closing') NOT NULL,
  `frequency` enum('daily','weekly','monthly','quarterly','yearly') DEFAULT 'monthly',
  `last_run_date` date DEFAULT NULL,
  `next_run_date` date DEFAULT NULL,
  `is_active` boolean DEFAULT TRUE,
  `description` text,
  `adjustment_rules` json, -- Stores adjustment logic in JSON format
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_type` (`tenant_id`, `adjustment_type`),
  KEY `idx_next_run` (`next_run_date`, `is_active`),
  CONSTRAINT `fk_ja_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ja_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- 8. FINANCIAL STATEMENTS - NERACA OTOMATIS
-- =============================================================================

CREATE TABLE IF NOT EXISTS `financial_statements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `statement_type` enum('balance_sheet','income_statement','cash_flow','changes_equity') NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `statement_data` json NOT NULL, -- Complete financial statement data
  `total_assets` decimal(15,2) DEFAULT 0.00,
  `total_liabilities` decimal(15,2) DEFAULT 0.00,
  `total_equity` decimal(15,2) DEFAULT 0.00,
  `net_income` decimal(15,2) DEFAULT 0.00,
  `status` enum('draft','final','audited') DEFAULT 'draft',
  `generated_by` int(11) DEFAULT NULL,
  `generated_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_period` (`tenant_id`, `period_start`, `period_end`),
  KEY `idx_type_status` (`statement_type`, `status`),
  CONSTRAINT `fk_fs_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fs_generated_by` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_fs_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- 9. AUTO-DEBIT SYSTEM
-- =============================================================================

CREATE TABLE IF NOT EXISTS `auto_debit_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `debit_amount` decimal(15,2) NOT NULL,
  `frequency` enum('daily','weekly','monthly','quarterly') DEFAULT 'monthly',
  `debit_day` int(11) DEFAULT 1, -- Day of month/week
  `bank_account_id` int(11) DEFAULT NULL,
  `payment_method` enum('bank_transfer','virtual_account','auto_debit') DEFAULT 'auto_debit',
  `is_active` boolean DEFAULT TRUE,
  `next_debit_date` date DEFAULT NULL,
  `last_debit_date` date DEFAULT NULL,
  `last_debit_amount` decimal(15,2) DEFAULT 0.00,
  `failure_count` int(11) DEFAULT 0,
  `max_failures` int(11) DEFAULT 3,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_loan` (`tenant_id`, `loan_id`),
  KEY `idx_member_active` (`member_id`, `is_active`),
  KEY `idx_next_debit` (`next_debit_date`, `is_active`),
  CONSTRAINT `fk_ads_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ads_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`),
  CONSTRAINT `fk_ads_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `fk_ads_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `auto_debit_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `auto_debit_id` int(11) NOT NULL,
  `transaction_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `status` enum('pending','processing','completed','failed','cancelled') DEFAULT 'pending',
  `reference_number` varchar(50) DEFAULT NULL,
  `bank_reference` varchar(100) DEFAULT NULL,
  `failure_reason` text,
  `processed_at` timestamp NULL,
  `repayment_id` int(11) DEFAULT NULL, -- Links to repayments table
  `journal_entry_id` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_auto_debit_date` (`auto_debit_id`, `transaction_date`),
  KEY `idx_status_date` (`status`, `transaction_date`),
  KEY `idx_reference` (`reference_number`),
  CONSTRAINT `fk_adt_auto_debit` FOREIGN KEY (`auto_debit_id`) REFERENCES `auto_debit_schedules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_adt_repayment` FOREIGN KEY (`repayment_id`) REFERENCES `repayments` (`id`),
  CONSTRAINT `fk_adt_journal` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- 10. NOTIFICATION SYSTEM
-- =============================================================================

CREATE TABLE IF NOT EXISTS `notification_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `template_code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('email','sms','push','whatsapp','in_app') NOT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `content` text NOT NULL,
  `variables` json, -- Available variables for template
  `is_active` boolean DEFAULT TRUE,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_template_code_tenant` (`template_code`, `tenant_id`),
  KEY `idx_tenant_type` (`tenant_id`, `type`),
  CONSTRAINT `fk_nt_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_nt_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `notification_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `template_id` int(11) DEFAULT NULL,
  `recipient_type` enum('user','member','admin') NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `recipient_email` varchar(150) DEFAULT NULL,
  `recipient_phone` varchar(30) DEFAULT NULL,
  `type` enum('email','sms','push','whatsapp','in_app') NOT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `content` text NOT NULL,
  `status` enum('queued','sent','delivered','failed','read') DEFAULT 'queued',
  `sent_at` timestamp NULL,
  `delivered_at` timestamp NULL,
  `read_at` timestamp NULL,
  `failure_reason` text,
  `reference_type` varchar(50) DEFAULT NULL, -- 'loan', 'payment', etc.
  `reference_id` int(11) DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_status` (`tenant_id`, `status`),
  KEY `idx_recipient` (`recipient_type`, `recipient_id`),
  KEY `idx_reference` (`reference_type`, `reference_id`),
  KEY `idx_created_priority` (`created_at`, `priority`),
  CONSTRAINT `fk_nl_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_nl_template` FOREIGN KEY (`template_id`) REFERENCES `notification_templates` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- 11. ANALYTICS & PREDICTIVE MODELS
-- =============================================================================

CREATE TABLE IF NOT EXISTS `analytics_dashboards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `dashboard_code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `role_access` json, -- Which roles can access this dashboard
  `config` json, -- Dashboard configuration
  `is_active` boolean DEFAULT TRUE,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_dashboard_code_tenant` (`dashboard_code`, `tenant_id`),
  KEY `idx_tenant_active` (`tenant_id`, `is_active`),
  CONSTRAINT `fk_ad_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ad_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `analytics_kpis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `kpi_code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` enum('financial','operational','risk','member','portfolio') NOT NULL,
  `calculation_type` enum('simple','ratio','formula','aggregate') DEFAULT 'simple',
  `calculation_config` json, -- How to calculate this KPI
  `target_value` decimal(15,2) DEFAULT NULL,
  `target_type` enum('minimum','maximum','range','benchmark') DEFAULT 'benchmark',
  `frequency` enum('real_time','daily','weekly','monthly','quarterly') DEFAULT 'monthly',
  `is_active` boolean DEFAULT TRUE,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_kpi_code_tenant` (`kpi_code`, `tenant_id`),
  KEY `idx_tenant_category` (`tenant_id`, `category`),
  KEY `idx_calculation` (`calculation_type`, `frequency`),
  CONSTRAINT `fk_ak_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `analytics_predictions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `prediction_type` enum('npl_forecast','member_churn','loan_demand','payment_default') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `prediction_date` date NOT NULL,
  `prediction_value` decimal(10,4) NOT NULL, -- Probability or predicted value
  `confidence_level` decimal(5,2) DEFAULT NULL, -- 0-100
  `model_version` varchar(20) DEFAULT NULL,
  `input_data` json, -- Data used for prediction
  `prediction_result` json, -- Detailed prediction results
  `is_accurate` boolean DEFAULT NULL, -- Validated accuracy
  `actual_outcome` decimal(10,4) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tenant_type_date` (`tenant_id`, `prediction_type`, `prediction_date`),
  KEY `idx_reference` (`reference_id`, `prediction_type`),
  KEY `idx_accuracy` (`is_accurate`, `prediction_date`),
  CONSTRAINT `fk_ap_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `risk_scoring_models` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `model_code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `model_type` enum('credit_score','npl_risk','churn_risk','fraud_detection') NOT NULL,
  `algorithm` enum('logistic_regression','random_forest','neural_network','gradient_boosting','rule_based') DEFAULT 'rule_based',
  `model_data` json, -- Model coefficients/weights/rules
  `accuracy_score` decimal(5,2) DEFAULT NULL,
  `is_active` boolean DEFAULT TRUE,
  `last_trained` timestamp NULL,
  `version` varchar(20) DEFAULT '1.0',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_model_code_tenant` (`model_code`, `tenant_id`),
  KEY `idx_tenant_type` (`tenant_id`, `model_type`),
  KEY `idx_active_version` (`is_active`, `version`),
  CONSTRAINT `fk_rsm_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rsm_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- 12. CUSTOMER SEGMENTATION
-- =============================================================================

CREATE TABLE IF NOT EXISTS `customer_segments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `segment_code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `segmentation_criteria` json, -- Rules for segmentation
  `member_count` int(11) DEFAULT 0,
  `total_outstanding` decimal(15,2) DEFAULT 0.00,
  `avg_loan_amount` decimal(15,2) DEFAULT 0.00,
  `avg_payment_score` decimal(5,2) DEFAULT 0.00,
  `risk_profile` enum('low','medium','high') DEFAULT 'medium',
  `is_active` boolean DEFAULT TRUE,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_segment_code_tenant` (`segment_code`, `tenant_id`),
  KEY `idx_tenant_risk` (`tenant_id`, `risk_profile`),
  KEY `idx_active_count` (`is_active`, `member_count`),
  CONSTRAINT `fk_cs_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `member_segments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `segment_id` int(11) NOT NULL,
  `assigned_date` date NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `segmentation_score` decimal(5,2) DEFAULT NULL,
  `segmentation_factors` json, -- Factors used for segmentation
  `is_current` boolean DEFAULT TRUE,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_member_segment` (`member_id`, `segment_id`),
  KEY `idx_current_date` (`is_current`, `assigned_date`),
  CONSTRAINT `fk_ms_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ms_segment` FOREIGN KEY (`segment_id`) REFERENCES `customer_segments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ms_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- INSERT DEFAULT CHART OF ACCOUNTS (SAK COMPLIANCE)
-- =============================================================================

INSERT IGNORE INTO `chart_of_accounts` (`tenant_id`, `account_code`, `account_name`, `account_type`, `category`, `level`, `normal_balance`, `description`) VALUES
-- ASSETS
(1, '10000000', 'AKTIVA', 'asset', 'current_asset', 1, 'debit', 'Grup Aktiva'),
(1, '11000000', 'AKTIVA LANCAR', 'asset', 'current_asset', 2, 'debit', 'Aktiva Lancar'),
(1, '11100000', 'KAS DAN SETARA KAS', 'asset', 'current_asset', 3, 'debit', 'Kas dan Setara Kas'),
(1, '11110000', 'KAS DI BANK', 'asset', 'current_asset', 4, 'debit', 'Kas di Bank'),
(1, '11120000', 'KAS DI TANGAN', 'asset', 'current_asset', 4, 'debit', 'Kas di Tangan'),
(1, '11200000', 'PIUTANG ANGGOTA', 'asset', 'current_asset', 3, 'debit', 'Piutang Anggota'),
(1, '11210000', 'PIUTANG PINJAMAN', 'asset', 'current_asset', 4, 'debit', 'Piutang Pinjaman'),
(1, '11220000', 'PIUTANG SIMPANAN', 'asset', 'current_asset', 4, 'debit', 'Piutang Simpanan'),

-- FIXED ASSETS
(1, '12000000', 'AKTIVA TETAP', 'asset', 'fixed_asset', 2, 'debit', 'Aktiva Tetap'),
(1, '12100000', 'AKTIVA TETAP BERSIH', 'asset', 'fixed_asset', 3, 'debit', 'Aktiva Tetap Bersih'),
(1, '12200000', 'AKUMULASI PENYUSUTAN', 'contra_asset', 'fixed_asset', 3, 'credit', 'Akumulasi Penyusutan'),

-- LIABILITIES
(1, '20000000', 'KEWAJIBAN', 'liability', 'current_liability', 1, 'credit', 'Grup Kewajiban'),
(1, '21000000', 'KEWAJIBAN LANCAR', 'liability', 'current_liability', 2, 'credit', 'Kewajiban Lancar'),
(1, '21100000', 'HUTANG ANGGOTA', 'liability', 'current_liability', 3, 'credit', 'Hutang Anggota'),
(1, '21110000', 'HUTANG SIMPANAN', 'liability', 'current_liability', 4, 'credit', 'Hutang Simpanan'),

-- EQUITY
(1, '30000000', 'EKUITAS', 'equity', 'equity', 1, 'credit', 'Grup Ekuitas'),
(1, '31000000', 'MODAL', 'equity', 'equity', 2, 'credit', 'Modal'),
(1, '32000000', 'SHU TAHUN BERJALAN', 'equity', 'equity', 2, 'credit', 'SHU Tahun Berjalan'),
(1, '33000000', 'SHU TAHUN LALU', 'equity', 'equity', 2, 'credit', 'SHU Tahun Lalu'),

-- REVENUE
(1, '40000000', 'PENDAPATAN', 'revenue', 'operating_revenue', 1, 'credit', 'Grup Pendapatan'),
(1, '41000000', 'PENDAPATAN BUNGA PINJAMAN', 'revenue', 'operating_revenue', 2, 'credit', 'Pendapatan Bunga Pinjaman'),
(1, '42000000', 'PENDAPATAN PROVISI', 'revenue', 'operating_revenue', 2, 'credit', 'Pendapatan Provisi'),

-- EXPENSES
(1, '50000000', 'BEBAN', 'expense', 'operating_expense', 1, 'debit', 'Grup Beban'),
(1, '51000000', 'BEBAN OPERASIONAL', 'expense', 'operating_expense', 2, 'debit', 'Beban Operasional'),
(1, '51100000', 'BEBAN ADMINISTRASI', 'expense', 'operating_expense', 3, 'debit', 'Beban Administrasi'),
(1, '51200000', 'BEBAN PENYUSUTAN', 'expense', 'operating_expense', 3, 'debit', 'Beban Penyusutan'),
(1, '52000000', 'BEBAN KEUANGAN', 'expense', 'other_expense', 2, 'debit', 'Beban Keuangan');

-- =============================================================================
-- INSERT DEFAULT NOTIFICATION TEMPLATES
-- =============================================================================

INSERT IGNORE INTO `notification_templates` (`tenant_id`, `template_code`, `name`, `type`, `subject`, `content`, `variables`) VALUES
(1, 'loan_approved', 'Persetujuan Pinjaman', 'email', 'Pinjaman Anda Telah Disetujui', 'Dear {{member_name}},

Pinjaman sebesar Rp {{loan_amount}} untuk keperluan {{loan_purpose}} telah disetujui.

Detail Pinjaman:
- Jumlah: Rp {{loan_amount}}
- Tenor: {{loan_tenor}} bulan
- Bunga: {{interest_rate}}% per bulan
- Angsuran: Rp {{monthly_payment}}

Silakan datang ke kantor untuk proses pencairan.

Terima kasih,
{{cooperative_name}}', '["member_name","loan_amount","loan_purpose","loan_tenor","interest_rate","monthly_payment","cooperative_name"]'),

(1, 'payment_reminder', 'Pengingat Pembayaran Angsuran', 'email', 'Pengingat Pembayaran Angsuran', 'Dear {{member_name}},

Ini adalah pengingat bahwa angsuran pinjaman Anda sebesar Rp {{payment_amount}} akan jatuh tempo pada {{due_date}}.

Detail Pembayaran:
- Angsuran Ke: {{installment_number}}
- Jumlah: Rp {{payment_amount}}
- Tanggal Jatuh Tempo: {{due_date}}

Mohon lakukan pembayaran sebelum tanggal jatuh tempo untuk menghindari denda.

Terima kasih,
{{cooperative_name}}', '["member_name","payment_amount","due_date","installment_number","cooperative_name"]'),

(1, 'loan_overdue', 'Pemberitahuan Tunggakan', 'email', 'Pemberitahuan Tunggakan Pinjaman', 'Dear {{member_name}},

Kami informasikan bahwa pinjaman Anda telah mengalami tunggakan.

Detail Tunggakan:
- Jumlah Tunggakan: Rp {{overdue_amount}}
- Lama Tunggakan: {{overdue_days}} hari
- Denda: Rp {{penalty_amount}}

Mohon segera lakukan pembayaran untuk menghindari konsekuensi lebih lanjut.

Terima kasih,
{{cooperative_name}}', '["member_name","overdue_amount","overdue_days","penalty_amount","cooperative_name"]'),

(1, 'savings_deposit', 'Konfirmasi Setoran Simpanan', 'email', 'Konfirmasi Setoran Simpanan', 'Dear {{member_name}},

Setoran simpanan Anda telah berhasil diproses.

Detail Transaksi:
- Tanggal: {{transaction_date}}
- Jumlah Setoran: Rp {{deposit_amount}}
- Saldo Sekarang: Rp {{current_balance}}

Terima kasih atas kepercayaan Anda kepada {{cooperative_name}}.

Hormat kami,
{{cooperative_name}}', '["member_name","transaction_date","deposit_amount","current_balance","cooperative_name"]'),

(1, 'shu_distribution', 'Pemberitahuan Pembagian SHU', 'email', 'Pemberitahuan Pembagian Sisa Hasil Usaha (SHU)', 'Dear {{member_name}},

Berdasarkan Rapat Anggota Tahunan, kami informasikan pembagian SHU Tahun {{year}}.

Detail SHU Anda:
- SHU Simpanan Pokok: Rp {{shu_pokok}}
- SHU Simpanan Wajib: Rp {{shu_wajib}}
- SHU Simpanan Sukarela: Rp {{shu_sukarela}}
- Total SHU: Rp {{total_shu}}

SHU akan disetor ke rekening simpanan Anda.

Terima kasih,
{{cooperative_name}}', '["member_name","year","shu_pokok","shu_wajib","shu_sukarela","total_shu","cooperative_name"]');

-- =============================================================================
-- INSERT DEFAULT ANALYTICS DASHBOARDS
-- =============================================================================

INSERT IGNORE INTO `analytics_dashboards` (`tenant_id`, `dashboard_code`, `name`, `description`, `role_access`, `config`) VALUES
(1, 'admin_overview', 'Dashboard Admin', 'Dashboard utama untuk administrator sistem', '["admin","creator"]', '{"widgets": ["total_members", "total_loans", "total_savings", "monthly_revenue", "npl_ratio", "system_uptime"]}'),
(1, 'kasir_dashboard', 'Dashboard Kasir', 'Dashboard untuk operasional kasir harian', '["kasir"]', '{"widgets": ["daily_transactions", "pending_approvals", "cash_balance", "payment_reminders", "member_registrations"]}'),
(1, 'teller_dashboard', 'Dashboard Teller', 'Dashboard untuk teller pelayanan anggota', '["teller"]', '{"widgets": ["service_queue", "member_inquiries", "transaction_summary", "savings_transactions", "loan_applications"]}'),
(1, 'manajer_dashboard', 'Dashboard Manajer', 'Dashboard untuk pengawasan manajemen', '["manajer"]', '{"widgets": ["portfolio_performance", "risk_metrics", "staff_performance", "financial_summary", "compliance_status"]}'),
(1, 'surveyor_dashboard', 'Dashboard Surveyor', 'Dashboard untuk surveyor lapangan', '["surveyor"]', '{"widgets": ["survey_schedule", "location_tracking", "pending_surveys", "survey_completion", "risk_assessments"]}'),
(1, 'collector_dashboard', 'Dashboard Collector', 'Dashboard untuk penagihan angsuran', '["collector"]', '{"widgets": ["overdue_payments", "collection_targets", "payment_plans", "collection_history", "recovery_actions"]}'),
(1, 'akuntansi_dashboard', 'Dashboard Akuntansi', 'Dashboard untuk akuntansi dan pelaporan', '["akuntansi"]', '{"widgets": ["journal_entries", "trial_balance", "financial_statements", "reconciliation_status", "tax_compliance"]}'),
(1, 'creator_dashboard', 'Dashboard Creator', 'Dashboard untuk system creator/administrator', '["creator"]', '{"widgets": ["system_health", "user_activity", "error_logs", "backup_status", "security_alerts"]}');

-- =============================================================================
-- INSERT DEFAULT ANALYTICS KPIs
-- =============================================================================

INSERT IGNORE INTO `analytics_kpis` (`tenant_id`, `kpi_code`, `name`, `category`, `calculation_type`, `calculation_config`, `target_value`, `frequency`) VALUES
(1, 'total_members', 'Total Anggota Aktif', 'member', 'aggregate', '{"table": "members", "field": "id", "condition": "status = ''active''"}', NULL, 'real_time'),
(1, 'total_loans', 'Total Outstanding Pinjaman', 'financial', 'aggregate', '{"table": "loans", "field": "amount", "condition": "status IN (''approved'', ''disbursed'')"}', NULL, 'daily'),
(1, 'total_savings', 'Total Simpanan', 'financial', 'aggregate', '{"table": "savings_accounts", "field": "balance"}', NULL, 'daily'),
(1, 'monthly_revenue', 'Pendapatan Bulanan', 'financial', 'aggregate', '{"table": "journal_lines", "field": "credit_amount", "condition": "account_id IN (SELECT id FROM chart_of_accounts WHERE account_type = ''revenue'') AND created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)"}', NULL, 'monthly'),
(1, 'npl_ratio', 'NPL Ratio', 'risk', 'ratio', '{"numerator": {"table": "loans", "field": "amount", "condition": "DATEDIFF(CURDATE(), last_payment_date) > 90"}, "denominator": {"table": "loans", "field": "amount", "condition": "status = ''disbursed''"}}', 5.00, 'monthly'),
(1, 'loan_growth', 'Pertumbuhan Pinjaman', 'portfolio', 'ratio', '{"current": {"table": "loans", "field": "amount", "condition": "disbursed_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)"}, "previous": {"table": "loans", "field": "amount", "condition": "disbursed_at >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH) AND disbursed_at < DATE_SUB(CURDATE(), INTERVAL 1 MONTH)"}}', NULL, 'monthly'),
(1, 'member_satisfaction', 'Tingkat Kepuasan Anggota', 'operational', 'aggregate', '{"table": "surveys", "field": "satisfaction_score", "aggregation": "avg"}', 4.50, 'quarterly'),
(1, 'collection_rate', 'Tingkat Penagihan', 'operational', 'ratio', '{"numerator": {"table": "repayments", "field": "amount_paid", "condition": "status = ''paid'' AND payment_date <= due_date"}, "denominator": {"table": "repayments", "field": "amount_due", "condition": "due_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)"}}', 95.00, 'monthly'),
(1, 'profit_margin', 'Margin Laba', 'financial', 'ratio', '{"numerator": {"table": "financial_statements", "field": "net_income"}, "denominator": {"table": "financial_statements", "field": "total_revenue"}}', NULL, 'quarterly'),
(1, 'risk_coverage', 'Coverage Risiko', 'risk', 'ratio', '{"numerator": {"table": "loan_loss_provisions", "field": "amount"}, "denominator": {"table": "loans", "field": "amount", "condition": "risk_rating IN (''high'', ''very_high'')"}}', 80.00, 'quarterly');

-- =============================================================================
-- INSERT DEFAULT RISK SCORING MODEL
-- =============================================================================

INSERT IGNORE INTO `risk_scoring_models` (`tenant_id`, `model_code`, `name`, `model_type`, `algorithm`, `model_data`, `accuracy_score`, `version`) VALUES
(1, 'basic_credit_score', 'Basic Credit Scoring Model', 'credit_score', 'rule_based', '{"rules": [{"field": "monthly_income", "operator": ">", "value": 3000000, "score": 10}, {"field": "employment_stability", "operator": "=", "value": "stable", "score": 15}, {"field": "existing_loans", "operator": "<", "value": 2, "score": 10}, {"field": "payment_history", "operator": ">", "value": 0.95, "score": 20}]}', 78.50, '1.0');

COMMIT;
