/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.3-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: mysql    Database: maruba
-- ------------------------------------------------------
-- Server version	8.0.45

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `accounting_journals`
--

DROP TABLE IF EXISTS `accounting_journals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `accounting_journals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `transaction_date` date NOT NULL,
  `reference_number` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `total_debit` decimal(15,2) DEFAULT '0.00',
  `total_credit` decimal(15,2) DEFAULT '0.00',
  `status` enum('draft','posted','cancelled') DEFAULT 'draft',
  `posted_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference_number` (`reference_number`),
  KEY `posted_by` (`posted_by`),
  CONSTRAINT `accounting_journals_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_journals`
--

LOCK TABLES `accounting_journals` WRITE;
/*!40000 ALTER TABLE `accounting_journals` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `accounting_journals` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity` varchar(100) DEFAULT NULL,
  `entity_id` int DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `audit_logs` VALUES
(1,1,'create','member',1,NULL,'2026-03-05 21:41:53'),
(2,1,'create','member',2,NULL,'2026-03-05 21:41:53'),
(3,1,'create','loan',1,NULL,'2026-03-05 21:41:53'),
(4,1,'create','loan',2,NULL,'2026-03-05 21:41:53');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `chart_of_accounts`
--

DROP TABLE IF EXISTS `chart_of_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chart_of_accounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('asset','liability','equity','income','expense') NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chart_of_accounts`
--

LOCK TABLES `chart_of_accounts` WRITE;
/*!40000 ALTER TABLE `chart_of_accounts` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `chart_of_accounts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `cooperative_onboardings`
--

DROP TABLE IF EXISTS `cooperative_onboardings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cooperative_onboardings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `registration_id` int NOT NULL,
  `tenant_id` int NOT NULL,
  `admin_username` varchar(100) NOT NULL,
  `admin_password` varchar(255) NOT NULL,
  `setup_completed` tinyint(1) DEFAULT '0',
  `welcome_email_sent` tinyint(1) DEFAULT '0',
  `initial_config_done` tinyint(1) DEFAULT '0',
  `onboarding_steps` json DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `registration_id` (`registration_id`),
  KEY `tenant_id` (`tenant_id`),
  CONSTRAINT `cooperative_onboardings_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `cooperative_registrations` (`id`),
  CONSTRAINT `cooperative_onboardings_ibfk_2` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cooperative_onboardings`
--

LOCK TABLES `cooperative_onboardings` WRITE;
/*!40000 ALTER TABLE `cooperative_onboardings` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `cooperative_onboardings` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `cooperative_registrations`
--

DROP TABLE IF EXISTS `cooperative_registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cooperative_registrations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cooperative_name` varchar(150) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `legal_type` enum('koperasi_simpan_pinjam','koperasi_serba_usaha','koperasi_konsumen','koperasi_produsen') NOT NULL,
  `registration_number` varchar(50) DEFAULT NULL,
  `description` text,
  `address` text NOT NULL,
  `province` varchar(50) NOT NULL,
  `city` varchar(50) NOT NULL,
  `postal_code` varchar(10) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `website` varchar(100) DEFAULT NULL,
  `established_date` date DEFAULT NULL,
  `chairman_name` varchar(100) NOT NULL,
  `chairman_phone` varchar(20) NOT NULL,
  `chairman_email` varchar(100) DEFAULT NULL,
  `manager_name` varchar(100) NOT NULL,
  `manager_phone` varchar(20) NOT NULL,
  `manager_email` varchar(100) DEFAULT NULL,
  `total_members` int DEFAULT '0',
  `total_assets` decimal(15,2) DEFAULT '0.00',
  `subscription_plan` varchar(50) DEFAULT 'starter',
  `documents` json DEFAULT NULL,
  `status` enum('draft','submitted','under_review','approved','rejected') DEFAULT 'draft',
  `rejection_reason` text,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `cooperative_registrations_ibfk_1` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cooperative_registrations`
--

LOCK TABLES `cooperative_registrations` WRITE;
/*!40000 ALTER TABLE `cooperative_registrations` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `cooperative_registrations` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `credit_analyses`
--

DROP TABLE IF EXISTS `credit_analyses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_analyses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `loan_id` int NOT NULL,
  `analyst_id` int NOT NULL,
  `character_score` decimal(5,2) NOT NULL,
  `capacity_score` decimal(5,2) NOT NULL,
  `capital_score` decimal(5,2) NOT NULL,
  `collateral_score` decimal(5,2) NOT NULL,
  `condition_score` decimal(5,2) NOT NULL,
  `total_score` decimal(5,2) NOT NULL,
  `dsr_ratio` decimal(5,2) NOT NULL,
  `recommendation` text NOT NULL,
  `notes` json DEFAULT NULL,
  `status` enum('pending','completed','reviewed') DEFAULT 'pending',
  `reviewed_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `loan_id` (`loan_id`),
  KEY `analyst_id` (`analyst_id`),
  KEY `reviewed_by` (`reviewed_by`),
  CONSTRAINT `credit_analyses_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`),
  CONSTRAINT `credit_analyses_ibfk_2` FOREIGN KEY (`analyst_id`) REFERENCES `users` (`id`),
  CONSTRAINT `credit_analyses_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_analyses`
--

LOCK TABLES `credit_analyses` WRITE;
/*!40000 ALTER TABLE `credit_analyses` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `credit_analyses` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `document_templates`
--

DROP TABLE IF EXISTS `document_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `template_content` longtext NOT NULL,
  `variables` json DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `document_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_templates`
--

LOCK TABLES `document_templates` WRITE;
/*!40000 ALTER TABLE `document_templates` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `document_templates` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `generated_documents`
--

DROP TABLE IF EXISTS `generated_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `generated_documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template_id` int NOT NULL,
  `reference_id` int NOT NULL,
  `reference_type` varchar(50) NOT NULL,
  `document_number` varchar(50) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `status` enum('generated','signed','sent') DEFAULT 'generated',
  `generated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_number` (`document_number`),
  KEY `template_id` (`template_id`),
  KEY `generated_by` (`generated_by`),
  CONSTRAINT `generated_documents_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `document_templates` (`id`),
  CONSTRAINT `generated_documents_ibfk_2` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `generated_documents`
--

LOCK TABLES `generated_documents` WRITE;
/*!40000 ALTER TABLE `generated_documents` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `generated_documents` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `journal_entries`
--

DROP TABLE IF EXISTS `journal_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `journal_entries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `journal_id` int NOT NULL,
  `account_code` varchar(20) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `debit` decimal(15,2) DEFAULT '0.00',
  `credit` decimal(15,2) DEFAULT '0.00',
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `journal_id` (`journal_id`),
  CONSTRAINT `journal_entries_ibfk_1` FOREIGN KEY (`journal_id`) REFERENCES `accounting_journals` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `journal_entries`
--

LOCK TABLES `journal_entries` WRITE;
/*!40000 ALTER TABLE `journal_entries` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `journal_entries` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `loan_docs`
--

DROP TABLE IF EXISTS `loan_docs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loan_docs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `loan_id` int NOT NULL,
  `doc_type` varchar(50) NOT NULL,
  `path` varchar(255) NOT NULL,
  `uploaded_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `loan_id` (`loan_id`),
  CONSTRAINT `loan_docs_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_docs`
--

LOCK TABLES `loan_docs` WRITE;
/*!40000 ALTER TABLE `loan_docs` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `loan_docs` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `loans`
--

DROP TABLE IF EXISTS `loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `product_id` int NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `tenor_months` int NOT NULL,
  `rate` decimal(5,2) DEFAULT '0.00',
  `status` enum('draft','survey','review','approved','disbursed','closed','default') DEFAULT 'draft',
  `assigned_surveyor_id` int DEFAULT NULL,
  `assigned_collector_id` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `disbursed_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `member_id` (`member_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `loans_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  CONSTRAINT `loans_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loans`
--

LOCK TABLES `loans` WRITE;
/*!40000 ALTER TABLE `loans` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `loans` VALUES
(3,1,1,5000000.00,12,12.50,'approved',NULL,NULL,NULL,NULL,'2026-03-05 21:41:49'),
(4,2,1,3000000.00,6,12.50,'disbursed',NULL,NULL,NULL,NULL,'2026-03-05 21:41:49');
/*!40000 ALTER TABLE `loans` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `members`
--

DROP TABLE IF EXISTS `members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `members` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `nik` varchar(20) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `members`
--

LOCK TABLES `members` WRITE;
/*!40000 ALTER TABLE `members` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `members` VALUES
(1,'John Doe','1234567890123456','08123456789','Jl. Test No. 123',NULL,NULL,'active','2026-03-05 21:41:33'),
(2,'Jane Smith','9876543210987654','08987654321','Jl. Example No. 456',NULL,NULL,'active','2026-03-05 21:41:33');
/*!40000 ALTER TABLE `members` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `payroll_records`
--

DROP TABLE IF EXISTS `payroll_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `period_month` tinyint NOT NULL,
  `period_year` year NOT NULL,
  `basic_salary` decimal(15,2) NOT NULL,
  `allowances` decimal(15,2) DEFAULT '0.00',
  `deductions` decimal(15,2) DEFAULT '0.00',
  `net_salary` decimal(15,2) NOT NULL,
  `status` enum('draft','approved','paid') DEFAULT 'draft',
  `approved_by` int DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `payroll_records_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`),
  CONSTRAINT `payroll_records_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_records`
--

LOCK TABLES `payroll_records` WRITE;
/*!40000 ALTER TABLE `payroll_records` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `payroll_records` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('loan','savings') DEFAULT 'loan',
  `rate` decimal(5,2) DEFAULT '0.00',
  `tenor_months` int DEFAULT '0',
  `fee` decimal(12,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `products` VALUES
(1,'Pinjaman Produktif','loan',12.50,12,50000.00,'2026-03-05 21:41:46');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `repayments`
--

DROP TABLE IF EXISTS `repayments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `repayments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `loan_id` int NOT NULL,
  `due_date` date NOT NULL,
  `paid_date` date DEFAULT NULL,
  `amount_due` decimal(15,2) NOT NULL,
  `amount_paid` decimal(15,2) DEFAULT '0.00',
  `method` varchar(50) DEFAULT NULL,
  `proof_path` varchar(255) DEFAULT NULL,
  `collector_id` int DEFAULT NULL,
  `status` enum('due','paid','late','partial') DEFAULT 'due',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `loan_id` (`loan_id`),
  CONSTRAINT `repayments_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repayments`
--

LOCK TABLES `repayments` WRITE;
/*!40000 ALTER TABLE `repayments` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `repayments` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `permissions` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `roles` VALUES
(1,'super_admin','{\"all\": true, \"shu\": [\"view\", \"create\"], \"audit\": [\"view\"], \"loans\": [\"view\", \"create\", \"edit\", \"delete\", \"approve\"], \"users\": [\"view\", \"create\", \"edit\", \"delete\"], \"tenant\": [\"view\", \"create\", \"edit\"], \"members\": [\"view\", \"create\", \"edit\", \"delete\"], \"payroll\": [\"view\", \"create\"], \"reports\": [\"view\", \"export\"], \"savings\": [\"view\", \"create\", \"edit\"], \"surveys\": [\"view\", \"create\", \"edit\"], \"payments\": [\"view\", \"create\"], \"products\": [\"view\", \"create\", \"edit\", \"delete\"], \"dashboard\": [\"view\"], \"documents\": [\"view\", \"create\"], \"accounting\": [\"view\", \"create\", \"edit\"], \"repayments\": [\"view\", \"create\"], \"disbursement\": [\"view\", \"create\"], \"subscriptions\": [\"view\", \"create\"]}','2026-03-05 20:53:24'),
(2,'tenant_admin','{\"all\": true, \"shu\": [\"view\"], \"tenant\": [\"view\"], \"dashboard\": [\"view\"]}','2026-03-05 20:58:46'),
(3,'manager','{\"all\": true, \"shu\": [\"view\"], \"tenant\": [\"view\"], \"dashboard\": [\"view\"]}','2026-03-05 20:58:46'),
(4,'kasir','{\"all\": true, \"shu\": [\"view\"], \"tenant\": [\"view\"], \"dashboard\": [\"view\"]}','2026-03-05 20:58:46'),
(5,'surveyor','{\"all\": true, \"shu\": [\"view\"], \"tenant\": [\"view\"], \"dashboard\": [\"view\"]}','2026-03-05 20:58:46'),
(6,'collector','{\"all\": true, \"shu\": [\"view\"], \"tenant\": [\"view\"], \"dashboard\": [\"view\"]}','2026-03-05 20:58:46'),
(7,'teller','{\"all\": true, \"shu\": [\"view\"], \"tenant\": [\"view\"], \"dashboard\": [\"view\"]}','2026-03-05 20:58:46'),
(8,'staf_lapangan','{\"all\": true, \"shu\": [\"view\"], \"tenant\": [\"view\"], \"dashboard\": [\"view\"]}','2026-03-05 20:58:46');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `savings_accounts`
--

DROP TABLE IF EXISTS `savings_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `savings_accounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `account_number` varchar(20) NOT NULL,
  `type` enum('pokok','wajib','sukarela','sisuka') NOT NULL,
  `balance` decimal(15,2) DEFAULT '0.00',
  `interest_rate` decimal(5,2) DEFAULT '0.00',
  `status` enum('active','inactive','closed') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_number` (`account_number`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `savings_accounts_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `savings_accounts`
--

LOCK TABLES `savings_accounts` WRITE;
/*!40000 ALTER TABLE `savings_accounts` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `savings_accounts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `savings_transactions`
--

DROP TABLE IF EXISTS `savings_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `savings_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `savings_account_id` int NOT NULL,
  `type` enum('deposit','withdrawal','interest','transfer') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `balance_before` decimal(15,2) NOT NULL,
  `balance_after` decimal(15,2) NOT NULL,
  `description` text,
  `transaction_date` date NOT NULL,
  `processed_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `savings_account_id` (`savings_account_id`),
  KEY `processed_by` (`processed_by`),
  CONSTRAINT `savings_transactions_ibfk_1` FOREIGN KEY (`savings_account_id`) REFERENCES `savings_accounts` (`id`),
  CONSTRAINT `savings_transactions_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `savings_transactions`
--

LOCK TABLES `savings_transactions` WRITE;
/*!40000 ALTER TABLE `savings_transactions` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `savings_transactions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `shu_calculations`
--

DROP TABLE IF EXISTS `shu_calculations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shu_calculations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `period_year` year NOT NULL,
  `total_profit` decimal(15,2) NOT NULL,
  `total_shu` decimal(15,2) NOT NULL,
  `shu_percentage` decimal(5,2) NOT NULL,
  `calculation_date` date NOT NULL,
  `status` enum('draft','approved','distributed') DEFAULT 'draft',
  `approved_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `shu_calculations_ibfk_1` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shu_calculations`
--

LOCK TABLES `shu_calculations` WRITE;
/*!40000 ALTER TABLE `shu_calculations` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `shu_calculations` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `shu_distributions`
--

DROP TABLE IF EXISTS `shu_distributions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shu_distributions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `shu_calculation_id` int NOT NULL,
  `member_id` int NOT NULL,
  `savings_balance` decimal(15,2) NOT NULL,
  `loan_balance` decimal(15,2) NOT NULL,
  `shu_amount` decimal(15,2) NOT NULL,
  `distributed_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','distributed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `shu_calculation_id` (`shu_calculation_id`),
  KEY `member_id` (`member_id`),
  CONSTRAINT `shu_distributions_ibfk_1` FOREIGN KEY (`shu_calculation_id`) REFERENCES `shu_calculations` (`id`),
  CONSTRAINT `shu_distributions_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shu_distributions`
--

LOCK TABLES `shu_distributions` WRITE;
/*!40000 ALTER TABLE `shu_distributions` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `shu_distributions` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `subscription_plans`
--

DROP TABLE IF EXISTS `subscription_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscription_plans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(150) NOT NULL,
  `description` text,
  `price_monthly` decimal(15,2) NOT NULL,
  `price_yearly` decimal(15,2) NOT NULL,
  `max_users` int DEFAULT '5',
  `max_members` int DEFAULT '100',
  `max_storage_gb` int DEFAULT '1',
  `features` json DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription_plans`
--

LOCK TABLES `subscription_plans` WRITE;
/*!40000 ALTER TABLE `subscription_plans` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `subscription_plans` VALUES
(1,'starter','Starter Plan','Plan dasar untuk koperasi kecil',500000.00,5000000.00,3,100,1,'{\"loans\": true, \"members\": true, \"reports\": true, \"email_support\": true}',1,'2026-03-05 20:52:31'),
(2,'professional','Professional Plan','Plan lengkap untuk koperasi menengah',1500000.00,15000000.00,10,1000,5,'{\"api\": true, \"loans\": true, \"members\": true, \"reports\": true, \"email_support\": true, \"phone_support\": true}',1,'2026-03-05 20:52:31'),
(3,'enterprise','Enterprise Plan','Plan enterprise untuk koperasi besar',3000000.00,30000000.00,50,10000,50,'{\"api\": true, \"loans\": true, \"members\": true, \"reports\": true, \"email_support\": true, \"phone_support\": true, \"custom_features\": true, \"dedicated_support\": true}',1,'2026-03-05 20:52:31');
/*!40000 ALTER TABLE `subscription_plans` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `surveys`
--

DROP TABLE IF EXISTS `surveys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `surveys` (
  `id` int NOT NULL AUTO_INCREMENT,
  `loan_id` int NOT NULL,
  `surveyor_id` int NOT NULL,
  `result` text,
  `score` int DEFAULT NULL,
  `geo_lat` decimal(10,7) DEFAULT NULL,
  `geo_lng` decimal(10,7) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `loan_id` (`loan_id`),
  CONSTRAINT `surveys_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `surveys`
--

LOCK TABLES `surveys` WRITE;
/*!40000 ALTER TABLE `surveys` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `surveys` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `tenant_feature_usage`
--

DROP TABLE IF EXISTS `tenant_feature_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_feature_usage` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tenant_id` int NOT NULL,
  `feature_name` varchar(100) NOT NULL,
  `usage_count` int DEFAULT '0',
  `usage_limit` int DEFAULT '0',
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tenant_feature_period` (`tenant_id`,`feature_name`,`period_start`),
  CONSTRAINT `tenant_feature_usage_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_feature_usage`
--

LOCK TABLES `tenant_feature_usage` WRITE;
/*!40000 ALTER TABLE `tenant_feature_usage` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `tenant_feature_usage` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `tenants`
--

DROP TABLE IF EXISTS `tenants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text,
  `logo_path` varchar(255) DEFAULT NULL,
  `favicon_path` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','suspended','trial','expired') DEFAULT 'active',
  `subscription_plan` varchar(50) DEFAULT 'starter',
  `billing_cycle` enum('monthly','yearly') DEFAULT 'monthly',
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `subscription_ends_at` timestamp NULL DEFAULT NULL,
  `max_users` int DEFAULT '5',
  `max_members` int DEFAULT '100',
  `max_storage_gb` int DEFAULT '1',
  `legal_documents` json DEFAULT NULL,
  `board_members` json DEFAULT NULL,
  `registration_number` varchar(50) DEFAULT NULL,
  `tax_id` varchar(20) DEFAULT NULL,
  `business_license` varchar(50) DEFAULT NULL,
  `chairman_details` json DEFAULT NULL,
  `manager_details` json DEFAULT NULL,
  `secretary_details` json DEFAULT NULL,
  `treasurer_details` json DEFAULT NULL,
  `address_details` json DEFAULT NULL,
  `operating_hours` json DEFAULT NULL,
  `social_media` json DEFAULT NULL,
  `theme_settings` json DEFAULT NULL,
  `branding_settings` json DEFAULT NULL,
  `ui_preferences` json DEFAULT NULL,
  `last_profile_update` timestamp NULL DEFAULT NULL,
  `profile_completion_percentage` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenants`
--

LOCK TABLES `tenants` WRITE;
/*!40000 ALTER TABLE `tenants` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `tenants` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `users` VALUES
(2,'Super Admin','admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',1,'active','2026-03-05 20:53:27'),
(3,'Tenant Admin','tenant_admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',2,'active','2026-03-05 20:58:51'),
(4,'Manager','manager','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',3,'active','2026-03-05 20:58:51'),
(5,'Kasir','kasir','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',4,'active','2026-03-05 20:58:51'),
(6,'Surveyor','surveyor','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',5,'active','2026-03-05 20:58:51'),
(7,'Collector','collector','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',6,'active','2026-03-05 20:58:51'),
(8,'Teller','teller','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',7,'active','2026-03-05 20:58:51'),
(9,'Staf Lapangan','staf_lapangan','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',8,'active','2026-03-05 20:58:51');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
commit;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-03-05 23:41:25
