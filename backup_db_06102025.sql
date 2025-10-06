-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 06, 2025 at 04:40 AM
-- Server version: 10.1.21-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kas_musholla`
--

-- --------------------------------------------------------

--
-- Table structure for table `coa`
--

CREATE TABLE `coa` (
  `id` int(11) NOT NULL,
  `kode` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `tipe` enum('Material','Konsumsi','Jasa Tukang','Operasional','Donasi Masuk','Kas','Lainnya') DEFAULT 'Lainnya',
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `coa`
--

INSERT INTO `coa` (`id`, `kode`, `nama`, `tipe`, `is_active`) VALUES
(1, 'KAS', 'Kas Utama', 'Kas', 1),
(2, 'BM', 'Biaya Material', 'Material', 1),
(3, 'BK', 'Biaya Konsumsi', 'Konsumsi', 1),
(4, 'BT', 'Biaya Tukang', 'Jasa Tukang', 1),
(5, 'SM', 'Sodakoh/Donasi Masuk', 'Donasi Masuk', 1),
(6, 'OP', 'Operasional', 'Operasional', 1);

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL,
  `tgl` date NOT NULL,
  `keterangan` varchar(255) NOT NULL,
  `coa_id` int(11) NOT NULL,
  `jenis` enum('IN','OUT','OPENING') NOT NULL,
  `nominal` decimal(14,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id`, `tgl`, `keterangan`, `coa_id`, `jenis`, `nominal`, `created_at`) VALUES
(1, '2025-10-01', 'Sodakoh Awal', 1, 'OPENING', '2000000.00', '2025-10-03 23:52:02'),
(2, '2025-10-02', 'Sodakah bapak Andiek', 5, 'IN', '500000.00', '2025-10-03 23:53:01'),
(3, '2025-10-05', 'Biaya komsumsi', 3, 'OUT', '150000.00', '2025-10-03 23:53:45');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `coa`
--
ALTER TABLE `coa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode` (`kode`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_transaksi_coa` (`coa_id`),
  ADD KEY `idx_trans_tgl` (`tgl`),
  ADD KEY `idx_trans_jenis` (`jenis`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `coa`
--
ALTER TABLE `coa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `fk_transaksi_coa` FOREIGN KEY (`coa_id`) REFERENCES `coa` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
