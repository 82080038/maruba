-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Feb 25, 2026 at 06:33 AM
-- Server version: 10.11.14-MariaDB-0ubuntu0.24.04.1
-- PHP Version: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `maruba`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) NOT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity` varchar(100) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `tenant_id`, `user_id`, `action`, `entity`, `entity_id`, `meta`, `created_at`) VALUES
(1, NULL, 1, 'login', 'user', 1, '{\"ip\":\"127.0.0.1\"}', '2026-02-24 04:53:10'),
(2, 1, 1, 'create', 'loan', 2, '{\"member_id\":2,\"amount\":8000000}', '2026-02-24 04:53:10'),
(3, 1, 1, 'approve', 'loan', 2, '{\"approved_by\":1}', '2026-02-24 04:53:10'),
(4, 1, 1, 'disburse', 'loan', 2, '{\"disbursed_by\":1}', '2026-02-24 04:53:10'),
(5, 1, 4, 'create', 'survey', 2, '{\"loan_id\":2,\"score\":85}', '2026-02-24 04:53:10'),
(6, 1, 5, 'create', 'repayment', 4, '{\"loan_id\":4,\"amount\":600000}', '2026-02-24 04:53:10');

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `branch_code` varchar(20) NOT NULL,
  `branch_name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive','closed') DEFAULT 'active',
  `opening_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `budget_name` varchar(255) NOT NULL,
  `period` varchar(7) NOT NULL,
  `account_id` int(11) NOT NULL,
  `budgeted_amount` decimal(15,2) DEFAULT 0.00,
  `actual_amount` decimal(15,2) DEFAULT 0.00,
  `variance_amount` decimal(15,2) DEFAULT 0.00,
  `variance_percentage` decimal(7,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chart_of_accounts`
--

CREATE TABLE `chart_of_accounts` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `account_code` varchar(20) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_type` enum('asset','liability','equity','revenue','expense','contra_asset','contra_liability','contra_equity','contra_revenue','contra_expense') NOT NULL,
  `category` enum('current_asset','fixed_asset','current_liability','long_term_liability','equity','operating_revenue','other_revenue','cost_of_goods_sold','operating_expense','other_expense') NOT NULL,
  `sub_category` varchar(100) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `level` int(11) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `normal_balance` enum('debit','credit') DEFAULT 'debit',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cooperative_admins`
--

CREATE TABLE `cooperative_admins` (
  `id` int(11) NOT NULL,
  `cooperative_type` enum('tenant','registration') NOT NULL,
  `cooperative_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cooperative_admins`
--

INSERT INTO `cooperative_admins` (`id`, `cooperative_type`, `cooperative_id`, `user_id`, `created_at`) VALUES
(1, 'tenant', 1, 1, '2026-02-24 04:53:10');

-- --------------------------------------------------------

--
-- Table structure for table `depreciation_schedule`
--

CREATE TABLE `depreciation_schedule` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `period` varchar(7) NOT NULL,
  `depreciation_expense` decimal(15,2) DEFAULT 0.00,
  `accumulated_depreciation` decimal(15,2) DEFAULT 0.00,
  `book_value` decimal(15,2) DEFAULT 0.00,
  `journal_entry_id` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_ratios`
--

CREATE TABLE `financial_ratios` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `period` varchar(7) NOT NULL,
  `ratio_type` enum('liquidity','solvency','profitability','efficiency','coverage') NOT NULL,
  `ratio_name` varchar(100) NOT NULL,
  `ratio_code` varchar(50) NOT NULL,
  `ratio_value` decimal(10,4) DEFAULT 0.0000,
  `benchmark_value` decimal(10,4) DEFAULT NULL,
  `status` enum('good','warning','critical') DEFAULT 'good',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_statements`
--

CREATE TABLE `financial_statements` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `statement_type` enum('balance_sheet','income_statement','cash_flow','equity_changes') NOT NULL,
  `period` varchar(7) NOT NULL,
  `period_type` enum('monthly','quarterly','yearly') DEFAULT 'monthly',
  `line_item` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT 0.00,
  `is_total` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fixed_assets`
--

CREATE TABLE `fixed_assets` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `asset_code` varchar(20) NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `asset_category` enum('tangible','intangible','biological') DEFAULT 'tangible',
  `acquisition_date` date NOT NULL,
  `acquisition_cost` decimal(15,2) NOT NULL,
  `accumulated_depreciation` decimal(15,2) DEFAULT 0.00,
  `book_value` decimal(15,2) DEFAULT 0.00,
  `useful_life_years` int(11) DEFAULT 0,
  `depreciation_method` enum('straight_line','declining_balance','units_of_production') DEFAULT 'straight_line',
  `location` varchar(255) DEFAULT NULL,
  `responsible_person` varchar(150) DEFAULT NULL,
  `status` enum('active','disposed','sold') DEFAULT 'active',
  `disposal_date` date DEFAULT NULL,
  `disposal_value` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fixed_assets`
--

INSERT INTO `fixed_assets` (`id`, `tenant_id`, `asset_code`, `asset_name`, `asset_category`, `acquisition_date`, `acquisition_cost`, `accumulated_depreciation`, `book_value`, `useful_life_years`, `depreciation_method`, `location`, `responsible_person`, `status`, `disposal_date`, `disposal_value`, `created_at`, `updated_at`) VALUES
(1, 1, 'AST001', 'Bangunan Kantor Pusat', 'tangible', '2020-01-15', 500000000.00, 0.00, 0.00, 20, 'straight_line', 'Jl. Sudirman No. 123', 'Budi Santoso', 'active', NULL, 0.00, '2026-02-24 05:37:28', '2026-02-24 05:37:28'),
(2, 1, 'AST002', 'Mobil Operasional', 'tangible', '2023-03-10', 150000000.00, 0.00, 0.00, 5, 'declining_balance', 'Garasi Utama', 'Siti Aminah', 'active', NULL, 0.00, '2026-02-24 05:37:28', '2026-02-24 05:37:28'),
(3, 1, 'AST003', 'Komputer & Laptop', 'tangible', '2024-01-20', 25000000.00, 0.00, 0.00, 4, 'straight_line', 'Kantor Pusat', 'Ahmad Fauzi', 'active', NULL, 0.00, '2026-02-24 05:37:28', '2026-02-24 05:37:28');

-- --------------------------------------------------------

--
-- Table structure for table `general_ledger`
--

CREATE TABLE `general_ledger` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `period` varchar(7) NOT NULL,
  `opening_balance` decimal(15,2) DEFAULT 0.00,
  `debit_total` decimal(15,2) DEFAULT 0.00,
  `credit_total` decimal(15,2) DEFAULT 0.00,
  `closing_balance` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `item_code` varchar(20) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `unit` varchar(20) DEFAULT 'pcs',
  `current_stock` decimal(10,2) DEFAULT 0.00,
  `minimum_stock` decimal(10,2) DEFAULT 0.00,
  `maximum_stock` decimal(10,2) DEFAULT 0.00,
  `unit_cost` decimal(15,2) DEFAULT 0.00,
  `total_value` decimal(15,2) DEFAULT 0.00,
  `location` varchar(255) DEFAULT NULL,
  `supplier` varchar(150) DEFAULT NULL,
  `status` enum('active','inactive','discontinued') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `journal_entries`
--

CREATE TABLE `journal_entries` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `entry_number` varchar(20) NOT NULL,
  `entry_date` date NOT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `description` text NOT NULL,
  `source` enum('manual','system','import','auto_adjustment') DEFAULT 'manual',
  `status` enum('draft','posted','voided') DEFAULT 'draft',
  `posted_by` int(11) DEFAULT NULL,
  `posted_at` timestamp NULL DEFAULT NULL,
  `voided_by` int(11) DEFAULT NULL,
  `voided_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `journal_entry_lines`
--

CREATE TABLE `journal_entry_lines` (
  `id` int(11) NOT NULL,
  `journal_entry_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `debit` decimal(15,2) DEFAULT 0.00,
  `credit` decimal(15,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `member_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `tenor_months` int(11) NOT NULL,
  `rate` decimal(5,2) DEFAULT 0.00,
  `purpose` text DEFAULT NULL,
  `status` enum('draft','survey','review','approved','disbursed','closed','default') DEFAULT 'draft',
  `assigned_surveyor_id` int(11) DEFAULT NULL,
  `assigned_collector_id` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `disbursed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loans`
--

INSERT INTO `loans` (`id`, `tenant_id`, `member_id`, `product_id`, `amount`, `tenor_months`, `rate`, `purpose`, `status`, `assigned_surveyor_id`, `assigned_collector_id`, `approved_by`, `disbursed_by`, `created_at`) VALUES
(1, 1, 1, 1, 5000000.00, 12, 1.50, 'Modal usaha warung', 'survey', 4, 5, NULL, NULL, '2026-02-24 04:53:09'),
(2, 1, 2, 4, 8000000.00, 12, 2.00, 'Biaya pendidikan', 'approved', 4, 5, 1, 1, '2026-02-24 04:53:09'),
(3, 1, 3, 5, 12000000.00, 24, 1.75, 'Renovasi rumah', 'survey', 4, 5, NULL, NULL, '2026-02-24 04:53:09'),
(4, 1, 4, 4, 6000000.00, 12, 2.00, 'Modal tambahan usaha', 'disbursed', 4, 5, 1, 1, '2026-02-24 04:53:09');

-- --------------------------------------------------------

--
-- Table structure for table `loan_docs`
--

CREATE TABLE `loan_docs` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `loan_id` int(11) NOT NULL,
  `doc_type` varchar(50) NOT NULL,
  `path` varchar(255) NOT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loan_docs`
--

INSERT INTO `loan_docs` (`id`, `tenant_id`, `loan_id`, `doc_type`, `path`, `uploaded_by`, `created_at`) VALUES
(1, 1, 1, 'ktp', '/uploads/ktp-demo.jpg', 4, '2026-02-24 04:53:10'),
(2, 1, 1, 'kk', '/uploads/kk-demo.jpg', 4, '2026-02-24 04:53:10'),
(3, 1, 2, 'ktp', '/uploads/ktp_rina.jpg', 4, '2026-02-24 04:53:10'),
(4, 1, 2, 'kk', '/uploads/kk_rina.jpg', 4, '2026-02-24 04:53:10'),
(5, 1, 2, 'slip_gaji', '/uploads/slip_rina.jpg', 4, '2026-02-24 04:53:10'),
(6, 1, 3, 'ktp', '/uploads/ktp_budi.jpg', 4, '2026-02-24 04:53:10'),
(7, 1, 3, 'kk', '/uploads/kk_budi.jpg', 4, '2026-02-24 04:53:10'),
(8, 1, 3, 'bukti_usaha', '/uploads/usaha_budi.jpg', 4, '2026-02-24 04:53:10'),
(9, 1, 4, 'ktp', '/uploads/ktp_anto.jpg', 4, '2026-02-24 04:53:10'),
(10, 1, 4, 'kk', '/uploads/kk_anto.jpg', 4, '2026-02-24 04:53:10'),
(11, 1, 4, 'surat_kerja', '/uploads/kerja_anto.jpg', 4, '2026-02-24 04:53:10');

-- --------------------------------------------------------

--
-- Table structure for table `meetings`
--

CREATE TABLE `meetings` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `meeting_type` enum('annual','special','board','committee') DEFAULT 'annual',
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `meeting_date` datetime NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `agenda` longtext DEFAULT NULL,
  `minutes` longtext DEFAULT NULL,
  `attendees_count` int(11) DEFAULT 0,
  `decisions` longtext DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `chairperson` varchar(150) DEFAULT NULL,
  `secretary` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `nik` varchar(20) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `monthly_income` decimal(15,2) DEFAULT 0.00,
  `occupation` varchar(100) DEFAULT NULL,
  `emergency_contact_name` varchar(150) DEFAULT NULL,
  `emergency_contact_phone` varchar(30) DEFAULT NULL,
  `status` enum('active','inactive','pending') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `tenant_id`, `name`, `nik`, `phone`, `email`, `address`, `lat`, `lng`, `monthly_income`, `occupation`, `emergency_contact_name`, `emergency_contact_phone`, `status`, `created_at`) VALUES
(1, 1, 'Sitorus Manurung', '1204050101010001', '081234567890', NULL, 'Pangururan', -2.6500000, 99.0500000, 3000000.00, 'Petani', 'Jonson Manurung', '081234567891', 'active', '2026-02-24 04:53:09'),
(2, 1, 'Siboro Hutapea', '1204050101010002', '081234567891', NULL, 'Simanindo', -2.6800000, 99.0700000, 2500000.00, 'Pedagang', 'Amir Hutapea', '081234567892', 'active', '2026-02-24 04:53:09'),
(3, 1, 'Rina Siregar', '1204050101010003', '081234567892', NULL, 'Pangururan', -2.6510000, 99.0510000, 2000000.00, 'Guru', 'Budi Siregar', '081234567893', 'active', '2026-02-24 04:53:09'),
(4, 1, 'Budi Nainggolan', '1204050101010004', '081234567893', NULL, 'Simanindo', -2.6810000, 99.0710000, 4000000.00, 'Wiraswasta', 'Charles Nainggolan', '081234567894', 'active', '2026-02-24 04:53:09'),
(5, 1, 'Anto Sihombing', '1204050101010005', '081234567894', NULL, 'Onan Runggu', -2.6700000, 99.0600000, 3500000.00, 'PNS', 'Denny Sihombing', '081234567895', 'active', '2026-02-24 04:53:09');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `recipient_id` int(11) DEFAULT NULL,
  `recipient_type` enum('user','member','all_users','all_members') DEFAULT 'user',
  `notification_type` enum('email','sms','whatsapp','in_app','push') NOT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `status` enum('draft','scheduled','sent','failed','cancelled') DEFAULT 'draft',
  `template_id` int(11) DEFAULT NULL,
  `metadata` longtext DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `tenant_id`, `recipient_id`, `recipient_type`, `notification_type`, `priority`, `subject`, `message`, `scheduled_at`, `sent_at`, `status`, `template_id`, `metadata`, `created_by`, `created_at`) VALUES
(1, 1, NULL, 'all_members', 'in_app', 'medium', 'Rapat Anggota Tahunan', 'Rapat Anggota Tahunan akan dilaksanakan tanggal 25 Februari 2026 di Kantor Pusat.', NULL, NULL, 'draft', NULL, NULL, 1, '2026-02-24 05:37:28'),
(2, 1, NULL, 'all_users', 'email', 'high', 'Update Sistem', 'Sistem akan diupdate pada tanggal 26 Februari 2026 pukul 22:00 WIB.', NULL, NULL, 'scheduled', NULL, NULL, 1, '2026-02-24 05:37:28');

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `payroll_period` varchar(7) NOT NULL,
  `basic_salary` decimal(15,2) DEFAULT 0.00,
  `allowances` decimal(15,2) DEFAULT 0.00,
  `overtime` decimal(15,2) DEFAULT 0.00,
  `bonuses` decimal(15,2) DEFAULT 0.00,
  `deductions` decimal(15,2) DEFAULT 0.00,
  `tax_deductions` decimal(15,2) DEFAULT 0.00,
  `net_salary` decimal(15,2) DEFAULT 0.00,
  `payment_date` date DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` enum('draft','approved','paid','cancelled') DEFAULT 'draft',
  `approved_by` int(11) DEFAULT NULL,
  `paid_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('loan','savings') DEFAULT 'loan',
  `rate` decimal(5,2) DEFAULT 0.00,
  `tenor_months` int(11) DEFAULT 0,
  `fee` decimal(12,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `tenant_id`, `name`, `type`, `rate`, `tenor_months`, `fee`, `created_at`) VALUES
(1, 1, 'Pinjaman Mikro', 'loan', 1.50, 12, 50000.00, '2026-02-24 04:53:09'),
(2, 1, 'Pinjaman Kecil', 'loan', 1.80, 24, 75000.00, '2026-02-24 04:53:09'),
(3, 1, 'Simpanan Pokok', 'savings', 0.00, 0, 0.00, '2026-02-24 04:53:09'),
(4, 1, 'Simpanan Wajib', 'savings', 0.00, 0, 0.00, '2026-02-24 04:53:09'),
(5, 1, 'Simpanan Sukarela', 'savings', 0.50, 0, 0.00, '2026-02-24 04:53:09'),
(6, 1, 'Pinjaman Konsumtif', 'loan', 2.00, 12, 100000.00, '2026-02-24 04:53:09'),
(7, 1, 'Pinjaman Produktif', 'loan', 1.75, 24, 150000.00, '2026-02-24 04:53:09');

-- --------------------------------------------------------

--
-- Table structure for table `repayments`
--

CREATE TABLE `repayments` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `loan_id` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `paid_date` date DEFAULT NULL,
  `amount_due` decimal(15,2) NOT NULL,
  `amount_paid` decimal(15,2) DEFAULT 0.00,
  `method` varchar(50) DEFAULT NULL,
  `proof_path` varchar(255) DEFAULT NULL,
  `collector_id` int(11) DEFAULT NULL,
  `status` enum('due','paid','late','partial') DEFAULT 'due',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `repayments`
--

INSERT INTO `repayments` (`id`, `tenant_id`, `loan_id`, `due_date`, `paid_date`, `amount_due`, `amount_paid`, `method`, `proof_path`, `collector_id`, `status`, `created_at`) VALUES
(1, 1, 1, '2026-03-22', NULL, 500000.00, 0.00, NULL, NULL, 5, 'due', '2026-02-24 04:53:09'),
(2, 1, 2, '2026-03-22', NULL, 800000.00, 0.00, NULL, NULL, 5, 'due', '2026-02-24 04:53:09'),
(3, 1, 2, '2026-04-21', NULL, 800000.00, 0.00, NULL, NULL, 5, 'due', '2026-02-24 04:53:09'),
(4, 1, 4, '2026-03-22', NULL, 600000.00, 600000.00, 'tunai', NULL, 5, 'paid', '2026-02-24 04:53:09'),
(5, 1, 4, '2026-04-21', NULL, 600000.00, 0.00, NULL, NULL, 5, 'due', '2026-02-24 04:53:09');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `permissions`, `created_at`) VALUES
(1, 'admin', '{\"dashboard\": [\"view\"], \"users\": [\"view\",\"create\",\"edit\",\"delete\"], \"roles\": [\"view\",\"create\",\"edit\",\"delete\"], \"members\": [\"view\",\"create\",\"edit\",\"delete\"], \"products\": [\"view\",\"create\",\"edit\",\"delete\"], \"loans\": [\"view\",\"create\",\"edit\",\"delete\",\"approve\",\"disburse\"], \"surveys\": [\"view\",\"create\",\"edit\",\"delete\"], \"repayments\": [\"view\",\"create\",\"edit\",\"delete\"], \"loan_docs\": [\"view\",\"create\",\"delete\"], \"audit_logs\": [\"view\"], \"reports\": [\"view\",\"export\"]}', '2026-02-24 04:53:09'),
(2, 'kasir', '{\"dashboard\": [\"view\"], \"cash\": [\"view\",\"create\",\"edit\"], \"transactions\": [\"view\",\"create\",\"edit\"], \"repayments\": [\"view\",\"create\",\"edit\"], \"loan_docs\": [\"view\"]}', '2026-02-24 04:53:09'),
(3, 'teller', '{\"dashboard\": [\"view\"], \"savings\": [\"view\",\"create\",\"edit\"], \"transactions\": [\"view\",\"create\",\"edit\"], \"members\": [\"view\"]}', '2026-02-24 04:53:09'),
(4, 'staf_lapangan', '{\"dashboard\": [\"view\"], \"surveys\": [\"view\",\"create\",\"edit\"], \"loan_docs\": [\"view\",\"create\",\"delete\"], \"members\": [\"view\"]}', '2026-02-24 04:53:09'),
(5, 'manajer', '{\"dashboard\": [\"view\"], \"loans\": [\"view\",\"approve\",\"override\"], \"products\": [\"view\",\"edit\"], \"reports\": [\"view\",\"export\"]}', '2026-02-24 04:53:09'),
(6, 'akuntansi', '{\"dashboard\": [\"view\"], \"transactions\": [\"view\",\"reconcile\"], \"reports\": [\"view\",\"export\"], \"audit_logs\": [\"view\"]}', '2026-02-24 04:53:09'),
(7, 'surveyor', '{\"dashboard\": [\"view\"], \"surveys\": [\"view\",\"create\",\"edit\"], \"loan_docs\": [\"view\",\"create\",\"delete\"], \"members\": [\"view\"]}', '2026-02-24 04:53:09'),
(8, 'collector', '{\"dashboard\": [\"view\"], \"repayments\": [\"view\",\"create\",\"edit\"], \"loan_docs\": [\"view\",\"create\",\"delete\"], \"members\": [\"view\"]}', '2026-02-24 04:53:09');

-- --------------------------------------------------------

--
-- Table structure for table `savings_accounts`
--

CREATE TABLE `savings_accounts` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `account_number` varchar(20) NOT NULL,
  `account_type` enum('pokok','wajib','sukarela','investasi') DEFAULT 'pokok',
  `balance` decimal(15,2) DEFAULT 0.00,
  `interest_rate` decimal(5,2) DEFAULT 0.00,
  `status` enum('active','inactive','frozen','closed') DEFAULT 'active',
  `opening_date` date DEFAULT NULL,
  `last_transaction_date` date DEFAULT NULL,
  `maturity_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `savings_accounts`
--

INSERT INTO `savings_accounts` (`id`, `tenant_id`, `member_id`, `account_number`, `account_type`, `balance`, `interest_rate`, `status`, `opening_date`, `last_transaction_date`, `maturity_date`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '001-001-001', 'pokok', 100000.00, 0.00, 'active', '2023-01-15', NULL, NULL, '2026-02-24 05:37:28', '2026-02-24 05:37:28'),
(2, 1, 1, '001-001-002', 'wajib', 240000.00, 0.00, 'active', '2023-01-15', NULL, NULL, '2026-02-24 05:37:28', '2026-02-24 05:37:28'),
(3, 1, 1, '001-001-003', 'sukarela', 500000.00, 2.50, 'active', '2023-02-01', NULL, NULL, '2026-02-24 05:37:28', '2026-02-24 05:37:28'),
(4, 1, 2, '001-002-001', 'pokok', 100000.00, 0.00, 'active', '2023-02-20', NULL, NULL, '2026-02-24 05:37:28', '2026-02-24 05:37:28'),
(5, 1, 2, '001-002-002', 'wajib', 240000.00, 0.00, 'active', '2023-02-20', NULL, NULL, '2026-02-24 05:37:28', '2026-02-24 05:37:28'),
(6, 1, 3, '001-003-001', 'pokok', 100000.00, 0.00, 'active', '2023-03-10', NULL, NULL, '2026-02-24 05:37:28', '2026-02-24 05:37:28'),
(7, 1, 3, '001-003-002', 'wajib', 240000.00, 0.00, 'active', '2023-03-10', NULL, NULL, '2026-02-24 05:37:28', '2026-02-24 05:37:28'),
(8, 1, 4, '001-004-001', 'pokok', 100000.00, 0.00, 'active', '2023-03-15', NULL, NULL, '2026-02-24 05:37:28', '2026-02-24 05:37:28'),
(9, 1, 5, '001-005-001', 'pokok', 100000.00, 0.00, 'active', '2023-04-01', NULL, NULL, '2026-02-24 05:37:28', '2026-02-24 05:37:28');

-- --------------------------------------------------------

--
-- Table structure for table `shu_distribution`
--

CREATE TABLE `shu_distribution` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `fiscal_year` year(4) NOT NULL,
  `member_id` int(11) NOT NULL,
  `total_savings` decimal(15,2) DEFAULT 0.00,
  `total_loans` decimal(15,2) DEFAULT 0.00,
  `activity_points` decimal(10,2) DEFAULT 0.00,
  `shu_percentage` decimal(5,2) DEFAULT 0.00,
  `shu_amount` decimal(15,2) DEFAULT 0.00,
  `distributed_amount` decimal(15,2) DEFAULT 0.00,
  `distribution_date` date DEFAULT NULL,
  `status` enum('calculated','approved','distributed') DEFAULT 'calculated',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `surveys`
--

CREATE TABLE `surveys` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `loan_id` int(11) NOT NULL,
  `surveyor_id` int(11) NOT NULL,
  `result` text DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `geo_lat` decimal(10,7) DEFAULT NULL,
  `geo_lng` decimal(10,7) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `surveys`
--

INSERT INTO `surveys` (`id`, `tenant_id`, `loan_id`, `surveyor_id`, `result`, `score`, `geo_lat`, `geo_lng`, `created_at`) VALUES
(1, 1, 1, 4, 'Usaha warung stabil, penghasilan harian', 80, -2.6501000, 99.0502000, '2026-02-24 04:53:09'),
(2, 1, 2, 4, 'Usaha toko kelontong stabil, lokasi strategis', 85, -2.6811000, 99.0712000, '2026-02-24 04:53:09'),
(3, 1, 3, 4, 'Usaha bengkel, pendapatan fluktuatif', 70, -2.6701000, 99.0602000, '2026-02-24 04:53:09'),
(4, 1, 4, 4, 'Usaha warung makan, ramai', 90, -2.6511000, 99.0512000, '2026-02-24 04:53:09');

-- --------------------------------------------------------

--
-- Table structure for table `tax_calculations`
--

CREATE TABLE `tax_calculations` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `tax_period` varchar(7) NOT NULL,
  `tax_type` enum('income_tax','vat','withholding','corporate') NOT NULL,
  `taxable_amount` decimal(15,2) DEFAULT 0.00,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `paid_amount` decimal(15,2) DEFAULT 0.00,
  `outstanding_amount` decimal(15,2) DEFAULT 0.00,
  `due_date` date DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `status` enum('pending','paid','overdue') DEFAULT 'pending',
  `reference_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tenants`
--

CREATE TABLE `tenants` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `status` varchar(50) DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `district` varchar(150) DEFAULT NULL,
  `city` varchar(150) DEFAULT NULL,
  `province` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tenants`
--

INSERT INTO `tenants` (`id`, `name`, `slug`, `status`, `created_at`, `district`, `city`, `province`) VALUES
(1, 'Koperasi Simpan Pinjam Samosir', 'ksp-samosir', 'active', '2026-02-24 04:53:09', 'Samosir', 'Pangururan', 'Sumatera Utara');

-- --------------------------------------------------------

--
-- Table structure for table `trial_balance`
--

CREATE TABLE `trial_balance` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `period` varchar(7) NOT NULL,
  `account_id` int(11) NOT NULL,
  `account_code` varchar(20) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `debit_balance` decimal(15,2) DEFAULT 0.00,
  `credit_balance` decimal(15,2) DEFAULT 0.00,
  `balance_type` enum('debit','credit') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `tenant_id`, `name`, `username`, `password_hash`, `role_id`, `status`, `created_at`) VALUES
(1, NULL, 'Admin Demo', 'admin', '$2y$10$XYEyOGvZrkY3amTxA2jYm.QFcEZjrqqEBnM/pDV6fSCqg74F.PNC2', 1, 'active', '2026-02-24 04:53:09'),
(2, 1, 'Kasir Demo', 'kasir', '$2y$10$XYEyOGvZrkY3amTxA2jYm.QFcEZjrqqEBnM/pDV6fSCqg74F.PNC2', 2, 'active', '2026-02-24 04:53:09'),
(3, 1, 'Teller Demo', 'teller', '$2y$10$XYEyOGvZrkY3amTxA2jYm.QFcEZjrqqEBnM/pDV6fSCqg74F.PNC2', 3, 'active', '2026-02-24 04:53:09'),
(4, 1, 'Surveyor Demo', 'surveyor', '$2y$10$XYEyOGvZrkY3amTxA2jYm.QFcEZjrqqEBnM/pDV6fSCqg74F.PNC2', 7, 'active', '2026-02-24 04:53:09'),
(5, 1, 'Collector Demo', 'collector', '$2y$10$XYEyOGvZrkY3amTxA2jYm.QFcEZjrqqEBnM/pDV6fSCqg74F.PNC2', 8, 'active', '2026-02-24 04:53:09');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_logs_tenant` (`tenant_id`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_branch_code_tenant` (`branch_code`,`tenant_id`),
  ADD KEY `idx_tenant_status` (`tenant_id`,`status`),
  ADD KEY `fk_branches_manager` (`manager_id`);

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_budget` (`tenant_id`,`period`,`account_id`),
  ADD KEY `idx_tenant_period` (`tenant_id`,`period`),
  ADD KEY `fk_budget_account` (`account_id`);

--
-- Indexes for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_account_code_tenant` (`account_code`,`tenant_id`),
  ADD KEY `idx_tenant_parent` (`tenant_id`,`parent_id`),
  ADD KEY `idx_account_type` (`account_type`,`category`),
  ADD KEY `fk_coa_parent` (`parent_id`);

--
-- Indexes for table `cooperative_admins`
--
ALTER TABLE `cooperative_admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_coop_admin` (`cooperative_type`,`cooperative_id`),
  ADD UNIQUE KEY `uniq_user` (`user_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `fk_coop_admin_tenant` (`cooperative_id`);

--
-- Indexes for table `depreciation_schedule`
--
ALTER TABLE `depreciation_schedule`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_depreciation` (`tenant_id`,`asset_id`,`period`),
  ADD KEY `idx_tenant_period` (`tenant_id`,`period`),
  ADD KEY `idx_asset` (`asset_id`),
  ADD KEY `fk_ds_journal` (`journal_entry_id`);

--
-- Indexes for table `financial_ratios`
--
ALTER TABLE `financial_ratios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant_ratio_period` (`tenant_id`,`ratio_type`,`period`),
  ADD KEY `idx_ratio_code` (`ratio_code`,`period`);

--
-- Indexes for table `financial_statements`
--
ALTER TABLE `financial_statements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant_statement_period` (`tenant_id`,`statement_type`,`period`),
  ADD KEY `idx_statement_type` (`statement_type`,`period`);

--
-- Indexes for table `fixed_assets`
--
ALTER TABLE `fixed_assets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_asset_code_tenant` (`asset_code`,`tenant_id`),
  ADD KEY `idx_tenant_category` (`tenant_id`,`asset_category`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `general_ledger`
--
ALTER TABLE `general_ledger`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_ledger_tenant_account_period` (`tenant_id`,`account_id`,`period`),
  ADD KEY `idx_tenant_period` (`tenant_id`,`period`),
  ADD KEY `fk_gl_account` (`account_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_item_code_tenant` (`item_code`,`tenant_id`),
  ADD KEY `idx_tenant_category` (`tenant_id`,`category`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_entry_number_tenant` (`entry_number`,`tenant_id`),
  ADD KEY `idx_tenant_date` (`tenant_id`,`entry_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_journal_entry` (`journal_entry_id`),
  ADD KEY `idx_account` (`account_id`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`);

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_loans_tenant` (`tenant_id`);

--
-- Indexes for table `loan_docs`
--
ALTER TABLE `loan_docs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`),
  ADD KEY `idx_loan_docs_tenant` (`tenant_id`);

--
-- Indexes for table `meetings`
--
ALTER TABLE `meetings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant_date` (`tenant_id`,`meeting_date`),
  ADD KEY `idx_type_status` (`meeting_type`,`status`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_members_nik` (`nik`),
  ADD UNIQUE KEY `uniq_members_phone` (`phone`),
  ADD KEY `idx_members_tenant` (`tenant_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant_recipient` (`tenant_id`,`recipient_id`,`recipient_type`),
  ADD KEY `idx_status_type` (`status`,`notification_type`),
  ADD KEY `idx_scheduled` (`scheduled_at`),
  ADD KEY `fk_notifications_recipient` (`recipient_id`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_payroll` (`tenant_id`,`employee_id`,`payroll_period`),
  ADD KEY `idx_tenant_period` (`tenant_id`,`payroll_period`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_products_tenant` (`tenant_id`);

--
-- Indexes for table `repayments`
--
ALTER TABLE `repayments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`),
  ADD KEY `idx_repayments_tenant` (`tenant_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `savings_accounts`
--
ALTER TABLE `savings_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_account_number` (`account_number`,`tenant_id`),
  ADD KEY `idx_member_type` (`member_id`,`account_type`),
  ADD KEY `idx_tenant_status` (`tenant_id`,`status`);

--
-- Indexes for table `shu_distribution`
--
ALTER TABLE `shu_distribution`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_shu` (`tenant_id`,`fiscal_year`,`member_id`),
  ADD KEY `idx_tenant_year` (`tenant_id`,`fiscal_year`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_shu_member` (`member_id`);

--
-- Indexes for table `surveys`
--
ALTER TABLE `surveys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`),
  ADD KEY `idx_surveys_tenant` (`tenant_id`);

--
-- Indexes for table `tax_calculations`
--
ALTER TABLE `tax_calculations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tenant_tax_period` (`tenant_id`,`tax_type`,`tax_period`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `tenants`
--
ALTER TABLE `tenants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `uniq_tenant_name_district` (`name`,`district`);

--
-- Indexes for table `trial_balance`
--
ALTER TABLE `trial_balance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_trial_balance` (`tenant_id`,`period`,`account_id`),
  ADD KEY `idx_tenant_period` (`tenant_id`,`period`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `idx_users_tenant` (`tenant_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cooperative_admins`
--
ALTER TABLE `cooperative_admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `depreciation_schedule`
--
ALTER TABLE `depreciation_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_ratios`
--
ALTER TABLE `financial_ratios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_statements`
--
ALTER TABLE `financial_statements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fixed_assets`
--
ALTER TABLE `fixed_assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `general_ledger`
--
ALTER TABLE `general_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `journal_entries`
--
ALTER TABLE `journal_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `loan_docs`
--
ALTER TABLE `loan_docs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `meetings`
--
ALTER TABLE `meetings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `repayments`
--
ALTER TABLE `repayments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `savings_accounts`
--
ALTER TABLE `savings_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `shu_distribution`
--
ALTER TABLE `shu_distribution`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `surveys`
--
ALTER TABLE `surveys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tax_calculations`
--
ALTER TABLE `tax_calculations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tenants`
--
ALTER TABLE `tenants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `trial_balance`
--
ALTER TABLE `trial_balance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_logs_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `branches`
--
ALTER TABLE `branches`
  ADD CONSTRAINT `fk_branches_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_branches_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `fk_budget_account` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`),
  ADD CONSTRAINT `fk_budget_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  ADD CONSTRAINT `fk_coa_parent` FOREIGN KEY (`parent_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_coa_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cooperative_admins`
--
ALTER TABLE `cooperative_admins`
  ADD CONSTRAINT `fk_coop_admin_tenant` FOREIGN KEY (`cooperative_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_coop_admin_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `depreciation_schedule`
--
ALTER TABLE `depreciation_schedule`
  ADD CONSTRAINT `fk_ds_asset` FOREIGN KEY (`asset_id`) REFERENCES `fixed_assets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ds_journal` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`),
  ADD CONSTRAINT `fk_ds_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `financial_ratios`
--
ALTER TABLE `financial_ratios`
  ADD CONSTRAINT `fk_fr_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `financial_statements`
--
ALTER TABLE `financial_statements`
  ADD CONSTRAINT `fk_fs_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fixed_assets`
--
ALTER TABLE `fixed_assets`
  ADD CONSTRAINT `fk_fa_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `general_ledger`
--
ALTER TABLE `general_ledger`
  ADD CONSTRAINT `fk_gl_account` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`),
  ADD CONSTRAINT `fk_gl_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `fk_inv_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD CONSTRAINT `fk_je_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `journal_entry_lines`
--
ALTER TABLE `journal_entry_lines`
  ADD CONSTRAINT `fk_jel_account` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`),
  ADD CONSTRAINT `fk_jel_journal_entry` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `fk_loans_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loans_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  ADD CONSTRAINT `loans_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `loan_docs`
--
ALTER TABLE `loan_docs`
  ADD CONSTRAINT `fk_loan_docs_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loan_docs_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`);

--
-- Constraints for table `meetings`
--
ALTER TABLE `meetings`
  ADD CONSTRAINT `fk_meetings_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `fk_members_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_notifications_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `fk_payroll_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `repayments`
--
ALTER TABLE `repayments`
  ADD CONSTRAINT `fk_repayments_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `repayments_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`);

--
-- Constraints for table `savings_accounts`
--
ALTER TABLE `savings_accounts`
  ADD CONSTRAINT `fk_sa_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  ADD CONSTRAINT `fk_sa_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shu_distribution`
--
ALTER TABLE `shu_distribution`
  ADD CONSTRAINT `fk_shu_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  ADD CONSTRAINT `fk_shu_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `surveys`
--
ALTER TABLE `surveys`
  ADD CONSTRAINT `fk_surveys_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `surveys_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`);

--
-- Constraints for table `tax_calculations`
--
ALTER TABLE `tax_calculations`
  ADD CONSTRAINT `fk_tc_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trial_balance`
--
ALTER TABLE `trial_balance`
  ADD CONSTRAINT `fk_tb_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
