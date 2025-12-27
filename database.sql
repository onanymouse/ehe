-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 27 Des 2025 pada 23.37
-- Versi server: 10.6.24-MariaDB
-- Versi PHP: 8.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `awgnetbi_nms`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `acs_servers`
--

CREATE TABLE `acs_servers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `url` varchar(200) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `areas`
--

CREATE TABLE `areas` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `customer_code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `area_id` int(11) DEFAULT NULL,
  `collector_id` int(11) DEFAULT NULL,
  `package_id` int(11) NOT NULL,
  `router_id` int(11) DEFAULT NULL,
  `olt_id` int(11) DEFAULT NULL,
  `onu_interface` varchar(50) DEFAULT NULL,
  `is_mikrotik` tinyint(1) DEFAULT 1,
  `connection_type` enum('pppoe','static') DEFAULT 'pppoe',
  `pppoe_user` varchar(50) DEFAULT NULL,
  `pppoe_password` varchar(50) DEFAULT NULL,
  `static_ip_address` varchar(40) DEFAULT NULL,
  `due_date` int(2) DEFAULT 1,
  `auto_isolir` tinyint(1) DEFAULT 1,
  `status` enum('active','isolated','nonactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `package_name` varchar(100) DEFAULT NULL,
  `period_month` varchar(7) NOT NULL,
  `due_date` date DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `status` enum('unpaid','paid','cancelled') DEFAULT 'unpaid',
  `paid_at` datetime DEFAULT NULL,
  `paid_by_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `olts`
--

CREATE TABLE `olts` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `ip_address` varchar(40) NOT NULL,
  `telnet_port` int(5) DEFAULT 23,
  `username` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `type` enum('ZTE_C320','ZTE_C300','HUAWEI') DEFAULT 'ZTE_C320'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `olt_onus`
--

CREATE TABLE `olt_onus` (
  `id` int(11) NOT NULL,
  `olt_id` int(11) NOT NULL,
  `interface` varchar(50) NOT NULL,
  `olt_name` varchar(100) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `sn` varchar(50) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT 'Offline',
  `dbm` varchar(20) DEFAULT NULL,
  `last_sync` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `olt_onus_pro`
--

CREATE TABLE `olt_onus_pro` (
  `id` int(11) NOT NULL,
  `olt_id` int(11) NOT NULL,
  `interface` varchar(64) NOT NULL,
  `pon_port` varchar(64) NOT NULL,
  `name` varchar(128) DEFAULT NULL,
  `sn` varchar(64) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `onu_type` varchar(64) DEFAULT NULL,
  `status` varchar(32) DEFAULT 'offline',
  `rx_power` varchar(32) DEFAULT '-',
  `last_sync` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `packages`
--

CREATE TABLE `packages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `mikrotik_profile` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `type` enum('pppoe','hotspot','dedicated') DEFAULT 'pppoe'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `routers`
--

CREATE TABLE `routers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `ip_address` varchar(40) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `port` int(5) DEFAULT 8728,
  `description` text DEFAULT NULL,
  `isolir_mode` enum('disable','profile') DEFAULT 'disable',
  `isolir_profile` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `role` enum('admin','teknisi','keuangan','kolektor') DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `acs_servers`
--
ALTER TABLE `acs_servers`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `areas`
--
ALTER TABLE `areas`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_code` (`customer_code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_collector` (`collector_id`),
  ADD KEY `idx_onu_interface` (`onu_interface`);

--
-- Indeks untuk tabel `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `idx_customer` (`customer_id`);

--
-- Indeks untuk tabel `olts`
--
ALTER TABLE `olts`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `olt_onus`
--
ALTER TABLE `olt_onus`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_onu` (`olt_id`,`interface`),
  ADD KEY `idx_olt_id` (`olt_id`),
  ADD KEY `idx_interface` (`interface`);

--
-- Indeks untuk tabel `olt_onus_pro`
--
ALTER TABLE `olt_onus_pro`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_onu` (`olt_id`,`interface`),
  ADD KEY `idx_pon` (`pon_port`),
  ADD KEY `idx_status` (`status`);

--
-- Indeks untuk tabel `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `routers`
--
ALTER TABLE `routers`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `acs_servers`
--
ALTER TABLE `acs_servers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `areas`
--
ALTER TABLE `areas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `olts`
--
ALTER TABLE `olts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `olt_onus`
--
ALTER TABLE `olt_onus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `olt_onus_pro`
--
ALTER TABLE `olt_onus_pro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `routers`
--
ALTER TABLE `routers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
