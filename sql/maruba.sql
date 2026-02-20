-- phpMyAdmin SQL Dump
-- version 5.1.1deb5ubuntu1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Waktu pembuatan: 21 Feb 2026 pada 01.21
-- Versi server: 10.6.23-MariaDB-0ubuntu0.22.04.1
-- Versi PHP: 8.1.2-1ubuntu2.23

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
-- Struktur dari tabel `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity` varchar(100) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `entity`, `entity_id`, `meta`, `created_at`) VALUES
(1, 1, 'login', 'user', 1, '{\"ip\":\"127.0.0.1\"}', '2026-02-20 16:14:17'),
(2, 1, 'create', 'loan', 2, '{\"member_id\":2,\"amount\":8000000}', '2026-02-20 16:14:17'),
(3, 1, 'approve', 'loan', 2, '{\"approved_by\":1}', '2026-02-20 16:14:17'),
(4, 1, 'disburse', 'loan', 2, '{\"disbursed_by\":1}', '2026-02-20 16:14:17'),
(5, 4, 'create', 'survey', 2, '{\"loan_id\":2,\"score\":85}', '2026-02-20 16:14:17'),
(6, 5, 'create', 'repayment', 4, '{\"loan_id\":4,\"amount\":600000}', '2026-02-20 16:14:17');

-- --------------------------------------------------------

--
-- Struktur dari tabel `loans`
--

CREATE TABLE `loans` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `tenor_months` int(11) NOT NULL,
  `rate` decimal(5,2) DEFAULT 0.00,
  `status` enum('draft','survey','review','approved','disbursed','closed','default') DEFAULT 'draft',
  `assigned_surveyor_id` int(11) DEFAULT NULL,
  `assigned_collector_id` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `disbursed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `loans`
--

INSERT INTO `loans` (`id`, `member_id`, `product_id`, `amount`, `tenor_months`, `rate`, `status`, `assigned_surveyor_id`, `assigned_collector_id`, `approved_by`, `disbursed_by`, `created_at`) VALUES
(1, 1, 1, '5000000.00', 12, '1.50', 'survey', 4, 5, 1, NULL, '2026-02-20 16:14:13'),
(2, 2, 4, '8000000.00', 12, '2.00', 'approved', 4, 5, 1, 1, '2026-02-20 16:14:17'),
(3, 3, 5, '12000000.00', 24, '1.75', 'survey', 4, 5, NULL, NULL, '2026-02-20 16:14:17'),
(4, 4, 4, '6000000.00', 12, '2.00', 'disbursed', 4, 5, 1, 1, '2026-02-20 16:14:17');

-- --------------------------------------------------------

--
-- Struktur dari tabel `loan_docs`
--

CREATE TABLE `loan_docs` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `doc_type` varchar(50) NOT NULL,
  `path` varchar(255) NOT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `loan_docs`
--

INSERT INTO `loan_docs` (`id`, `loan_id`, `doc_type`, `path`, `uploaded_by`, `created_at`) VALUES
(1, 1, 'ktp', '/uploads/ktp-demo.jpg', 4, '2026-02-20 16:14:13'),
(2, 1, 'kk', '/uploads/kk-demo.jpg', 4, '2026-02-20 16:14:13'),
(3, 2, 'ktp', '/uploads/ktp_rina.jpg', 4, '2026-02-20 16:14:17'),
(4, 2, 'kk', '/uploads/kk_rina.jpg', 4, '2026-02-20 16:14:17'),
(5, 2, 'slip_gaji', '/uploads/slip_rina.jpg', 4, '2026-02-20 16:14:17'),
(6, 3, 'ktp', '/uploads/ktp_budi.jpg', 4, '2026-02-20 16:14:17'),
(7, 3, 'kk', '/uploads/kk_budi.jpg', 4, '2026-02-20 16:14:17'),
(8, 3, 'bukti_usaha', '/uploads/usaha_budi.jpg', 4, '2026-02-20 16:14:17'),
(9, 4, 'ktp', '/uploads/ktp_anto.jpg', 4, '2026-02-20 16:14:17'),
(10, 4, 'kk', '/uploads/kk_anto.jpg', 4, '2026-02-20 16:14:17'),
(11, 4, 'surat_kerja', '/uploads/kerja_anto.jpg', 4, '2026-02-20 16:14:17');

-- --------------------------------------------------------

--
-- Struktur dari tabel `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `nik` varchar(20) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `members`
--

INSERT INTO `members` (`id`, `name`, `nik`, `phone`, `address`, `lat`, `lng`, `status`, `created_at`) VALUES
(1, 'Sitorus Manurung', '1204050101010001', '081234567890', 'Pangururan', '-2.6500000', '99.0500000', 'active', '2026-02-20 16:14:13'),
(2, 'Siboro Hutapea', '1204050101010002', '081234567891', 'Simanindo', '-2.6800000', '99.0700000', 'active', '2026-02-20 16:14:13'),
(3, 'Rina Siregar', '1204050101010003', '081234567892', 'Pangururan', '-2.6510000', '99.0510000', 'active', '2026-02-20 16:14:17'),
(4, 'Budi Nainggolan', '1204050101010004', '081234567893', 'Simanindo', '-2.6810000', '99.0710000', 'active', '2026-02-20 16:14:17'),
(5, 'Anto Sihombing', '1204050101010005', '081234567894', 'Onan Runggu', '-2.6700000', '99.0600000', 'active', '2026-02-20 16:14:17');

-- --------------------------------------------------------

--
-- Struktur dari tabel `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('loan','savings') DEFAULT 'loan',
  `rate` decimal(5,2) DEFAULT 0.00,
  `tenor_months` int(11) DEFAULT 0,
  `fee` decimal(12,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `products`
--

INSERT INTO `products` (`id`, `name`, `type`, `rate`, `tenor_months`, `fee`, `created_at`) VALUES
(1, 'Pinjaman Mikro', 'loan', '1.50', 12, '50000.00', '2026-02-20 16:14:13'),
(2, 'Pinjaman Kecil', 'loan', '1.80', 24, '75000.00', '2026-02-20 16:14:13'),
(3, 'Simpanan Pokok', 'savings', '0.00', 0, '0.00', '2026-02-20 16:14:17'),
(4, 'Simpanan Wajib', 'savings', '0.00', 0, '0.00', '2026-02-20 16:14:17'),
(5, 'Simpanan Sukarela', 'savings', '0.50', 0, '0.00', '2026-02-20 16:14:17'),
(6, 'Pinjaman Konsumtif', 'loan', '2.00', 12, '100000.00', '2026-02-20 16:14:17'),
(7, 'Pinjaman Produktif', 'loan', '1.75', 24, '150000.00', '2026-02-20 16:14:17');

-- --------------------------------------------------------

--
-- Struktur dari tabel `repayments`
--

CREATE TABLE `repayments` (
  `id` int(11) NOT NULL,
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
-- Dumping data untuk tabel `repayments`
--

INSERT INTO `repayments` (`id`, `loan_id`, `due_date`, `paid_date`, `amount_due`, `amount_paid`, `method`, `proof_path`, `collector_id`, `status`, `created_at`) VALUES
(1, 1, '2026-03-22', NULL, '500000.00', '0.00', NULL, NULL, NULL, 'due', '2026-02-20 16:14:13'),
(2, 2, '2026-03-22', NULL, '800000.00', '0.00', NULL, NULL, 5, 'due', '2026-02-20 16:14:17'),
(3, 2, '2026-04-21', NULL, '800000.00', '0.00', NULL, NULL, 5, 'due', '2026-02-20 16:14:17'),
(4, 4, '2026-03-22', NULL, '600000.00', '600000.00', 'tunai', NULL, 5, 'paid', '2026-02-20 16:14:17'),
(5, 4, '2026-04-21', NULL, '600000.00', '0.00', NULL, NULL, 5, 'due', '2026-02-20 16:14:17');

-- --------------------------------------------------------

--
-- Struktur dari tabel `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `roles`
--

INSERT INTO `roles` (`id`, `name`, `permissions`, `created_at`) VALUES
(1, 'admin', '{\n  \"dashboard\": [\"view\"],\n  \"users\": [\"view\",\"create\",\"edit\",\"delete\"],\n  \"roles\": [\"view\",\"create\",\"edit\",\"delete\"],\n  \"members\": [\"view\",\"create\",\"edit\",\"delete\"],\n  \"products\": [\"view\",\"create\",\"edit\",\"delete\"],\n  \"loans\": [\"view\",\"create\",\"edit\",\"delete\",\"approve\",\"disburse\"],\n  \"surveys\": [\"view\",\"create\",\"edit\",\"delete\"],\n  \"repayments\": [\"view\",\"create\",\"edit\",\"delete\"],\n  \"loan_docs\": [\"view\",\"create\",\"delete\"],\n  \"audit_logs\": [\"view\"],\n  \"reports\": [\"view\",\"export\"]\n}', '2026-02-20 16:14:13'),
(2, 'kasir', '{\n  \"dashboard\": [\"view\"],\n  \"cash\": [\"view\",\"create\",\"edit\"],\n  \"transactions\": [\"view\",\"create\",\"edit\"],\n  \"repayments\": [\"view\",\"create\",\"edit\"],\n  \"loan_docs\": [\"view\"]\n}', '2026-02-20 16:14:13'),
(3, 'teller', '{\n  \"dashboard\": [\"view\"],\n  \"savings\": [\"view\",\"create\",\"edit\"],\n  \"transactions\": [\"view\",\"create\",\"edit\"],\n  \"members\": [\"view\"]\n}', '2026-02-20 16:14:13'),
(4, 'staf_lapangan', '{\n  \"dashboard\": [\"view\"],\n  \"surveys\": [\"view\",\"create\",\"edit\"],\n  \"loan_docs\": [\"view\",\"create\",\"delete\"],\n  \"members\": [\"view\"]\n}', '2026-02-20 16:14:13'),
(5, 'manajer', '{\n  \"dashboard\": [\"view\"],\n  \"loans\": [\"view\",\"approve\",\"override\"],\n  \"products\": [\"view\",\"edit\"],\n  \"reports\": [\"view\",\"export\"]\n}', '2026-02-20 16:14:13'),
(6, 'akuntansi', '{\n  \"dashboard\": [\"view\"],\n  \"transactions\": [\"view\",\"reconcile\"],\n  \"reports\": [\"view\",\"export\"],\n  \"audit_logs\": [\"view\"]\n}', '2026-02-20 16:14:13'),
(7, 'surveyor', '{\n  \"dashboard\": [\"view\"],\n  \"surveys\": [\"view\",\"create\",\"edit\"],\n  \"loan_docs\": [\"view\",\"create\",\"delete\"],\n  \"members\": [\"view\"]\n}', '2026-02-20 16:14:13'),
(8, 'collector', '{\n  \"dashboard\": [\"view\"],\n  \"repayments\": [\"view\",\"create\",\"edit\"],\n  \"loan_docs\": [\"view\",\"create\",\"delete\"],\n  \"members\": [\"view\"]\n}', '2026-02-20 16:14:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `surveys`
--

CREATE TABLE `surveys` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `surveyor_id` int(11) NOT NULL,
  `result` text DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `geo_lat` decimal(10,7) DEFAULT NULL,
  `geo_lng` decimal(10,7) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `surveys`
--

INSERT INTO `surveys` (`id`, `loan_id`, `surveyor_id`, `result`, `score`, `geo_lat`, `geo_lng`, `created_at`) VALUES
(1, 1, 4, 'Usaha warung stabil, penghasilan harian', 80, '-2.6501000', '99.0502000', '2026-02-20 16:14:13'),
(2, 2, 4, 'Usaha toko kelontong stabil, lokasi strategis', 85, '-2.6811000', '99.0712000', '2026-02-20 16:14:17'),
(3, 3, 4, 'Usaha bengkel, pendapatan fluktuatif', 70, '-2.6701000', '99.0602000', '2026-02-20 16:14:17'),
(4, 4, 4, 'Usaha warung makan, ramai', 90, '-2.6511000', '99.0512000', '2026-02-20 16:14:17');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password_hash`, `role_id`, `status`, `created_at`) VALUES
(1, 'Admin Demo', 'admin', '$2y$10$XYEyOGvZrkY3amTxA2jYm.QFcEZjrqqEBnM/pDV6fSCqg74F.PNC2', 1, 'active', '2026-02-20 16:14:13'),
(2, 'Kasir Demo', 'kasir', '$2y$10$XYEyOGvZrkY3amTxA2jYm.QFcEZjrqqEBnM/pDV6fSCqg74F.PNC2', 2, 'active', '2026-02-20 16:14:13'),
(3, 'Teller Demo', 'teller', '$2y$10$XYEyOGvZrkY3amTxA2jYm.QFcEZjrqqEBnM/pDV6fSCqg74F.PNC2', 3, 'active', '2026-02-20 16:14:13'),
(4, 'Surveyor Demo', 'surveyor', '$2y$10$XYEyOGvZrkY3amTxA2jYm.QFcEZjrqqEBnM/pDV6fSCqg74F.PNC2', 7, 'active', '2026-02-20 16:14:13'),
(5, 'Collector Demo', 'collector', '$2y$10$XYEyOGvZrkY3amTxA2jYm.QFcEZjrqqEBnM/pDV6fSCqg74F.PNC2', 8, 'active', '2026-02-20 16:14:13');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeks untuk tabel `loan_docs`
--
ALTER TABLE `loan_docs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indeks untuk tabel `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `repayments`
--
ALTER TABLE `repayments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indeks untuk tabel `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indeks untuk tabel `surveys`
--
ALTER TABLE `surveys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loan_id` (`loan_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `loan_docs`
--
ALTER TABLE `loan_docs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `repayments`
--
ALTER TABLE `repayments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `surveys`
--
ALTER TABLE `surveys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `loans`
--
ALTER TABLE `loans`
  ADD CONSTRAINT `loans_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  ADD CONSTRAINT `loans_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Ketidakleluasaan untuk tabel `loan_docs`
--
ALTER TABLE `loan_docs`
  ADD CONSTRAINT `loan_docs_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`);

--
-- Ketidakleluasaan untuk tabel `repayments`
--
ALTER TABLE `repayments`
  ADD CONSTRAINT `repayments_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`);

--
-- Ketidakleluasaan untuk tabel `surveys`
--
ALTER TABLE `surveys`
  ADD CONSTRAINT `surveys_ibfk_1` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`);

--
-- Ketidakleluasaan untuk tabel `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
