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

-- Mock data
INSERT INTO roles (name) VALUES
 ('admin'),
 ('kasir'),
 ('teller'),
 ('staf_lapangan'),
 ('manajer'),
 ('akuntansi'),
 ('surveyor'),
 ('collector');

INSERT INTO users (name, username, password_hash, role_id) VALUES
 ('Admin Demo', 'admin', '$2y$10$XYEyOGvZrkY3amTxA2jYm.QFcEZjrqqEBnM/pDV6fSCqg74F.PNC2', 1),
 ('Kasir Demo', 'kasir', '$2y$10$XYEyOGvZrkY3amTxA2jYm.QFcEZjrqqEBnM/pDV6fSCqg74F.PNC2', 2),
 ('Teller Demo', 'teller', '$2y$10$XYEyOGvZrkY3amTxA2jYm.QFcEZjrqqEBnM/pDV6fSCqg74F.PNC2', 3),
 ('Surveyor Demo', 'surveyor', '$2y$10$XYEyOGvZrkY3amTxA2jYm.QFcEZjrqqEBnM/pDV6fSCqg74F.PNC2', 7),
 ('Collector Demo', 'collector', '$2y$10$XYEyOGvZrkY3amTxA2jYm.QFcEZjrqqEBnM/pDV6fSCqg74F.PNC2', 8);

INSERT INTO members (name, nik, phone, address, lat, lng) VALUES
 ('Sitorus Manurung', '1204050101010001', '081234567890', 'Pangururan', -2.6500000, 99.0500000),
 ('Siboro Hutapea', '1204050101010002', '081234567891', 'Simanindo', -2.6800000, 99.0700000);

INSERT INTO products (name, type, rate, tenor_months, fee) VALUES
 ('Pinjaman Mikro', 'loan', 1.50, 12, 50000),
 ('Pinjaman Kecil', 'loan', 1.80, 24, 75000);

INSERT INTO loans (member_id, product_id, amount, tenor_months, rate, status, assigned_surveyor_id, assigned_collector_id, approved_by, disbursed_by)
VALUES (1, 1, 5000000, 12, 1.50, 'survey', 4, 5, 1, NULL);

INSERT INTO surveys (loan_id, surveyor_id, result, score, geo_lat, geo_lng) VALUES
 (1, 4, 'Usaha warung stabil, penghasilan harian', 80, -2.6501000, 99.0502000);

INSERT INTO repayments (loan_id, due_date, amount_due, status) VALUES
 (1, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 500000, 'due');

INSERT INTO loan_docs (loan_id, doc_type, path, uploaded_by) VALUES
 (1, 'ktp', '/uploads/ktp-demo.jpg', 4),
 (1, 'kk', '/uploads/kk-demo.jpg', 4);
