-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 26, 2025 at 05:07 PM
-- Server version: 10.4.20-MariaDB
-- PHP Version: 8.0.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tiketing`
--

-- --------------------------------------------------------

--
-- Table structure for table `akses_khanza`
--

CREATE TABLE `akses_khanza` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `kategori_id` int(11) NOT NULL,
  `subjek` varchar(255) NOT NULL,
  `deskripsi` text NOT NULL,
  `tanggal` datetime NOT NULL,
  `status` enum('pending','diperiksa','diproses','selesai','ditolak') DEFAULT 'pending',
  `catatan_admin` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `akses_khanza`
--

INSERT INTO `akses_khanza` (`id`, `user_id`, `kategori_id`, `subjek`, `deskripsi`, `tanggal`, `status`, `catatan_admin`) VALUES
(13, 4, 1, 'buka akses', '- Registrasi\r\n- Mau lihat SEP\r\n- Mau lihat data pasien', '2025-06-26 21:59:16', 'selesai', 'akses sudah di buka.');

-- --------------------------------------------------------

--
-- Table structure for table `histori_status`
--

CREATE TABLE `histori_status` (
  `id` int(11) NOT NULL,
  `laporan_id` int(11) NOT NULL,
  `status` varchar(20) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `waktu` datetime DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `histori_status`
--

INSERT INTO `histori_status` (`id`, `laporan_id`, `status`, `catatan`, `waktu`, `admin_id`) VALUES
(26, 24, 'diperiksa', '', '2025-06-26 21:59:55', 2),
(27, 24, 'selesai', 'sudah normal dan bisa digunakan ya', '2025-06-26 22:00:19', 2),
(28, 23, 'diperiksa', '', '2025-06-26 22:00:25', 2),
(29, 23, 'selesai', 'sudah di buka akses nya.', '2025-06-26 22:00:41', 2),
(30, 23, 'selesai', 'Sudah bisa ya', '2025-06-26 22:01:19', 2);

-- --------------------------------------------------------

--
-- Table structure for table `jabatan`
--

CREATE TABLE `jabatan` (
  `id` int(11) NOT NULL,
  `nama_jabatan` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `jabatan`
--

INSERT INTO `jabatan` (`id`, `nama_jabatan`) VALUES
(1, 'IT Simrs'),
(2, 'IT Hardware'),
(3, 'IT Helpdesk'),
(4, 'Perawat'),
(5, 'Asisten Apoteker');

-- --------------------------------------------------------

--
-- Table structure for table `kategori_pelaporan`
--

CREATE TABLE `kategori_pelaporan` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `kategori_pelaporan`
--

INSERT INTO `kategori_pelaporan` (`id`, `nama_kategori`) VALUES
(1, 'Simrs Khanza'),
(2, 'Jaringan Internet'),
(3, 'Komputer Set'),
(4, 'Jaringan Wifi');

-- --------------------------------------------------------

--
-- Table structure for table `laporan`
--

CREATE TABLE `laporan` (
  `id` int(11) NOT NULL,
  `nomor_tiket` varchar(30) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `kategori_id` int(11) NOT NULL,
  `subjek` varchar(150) NOT NULL,
  `deskripsi` text NOT NULL,
  `tanggal` datetime NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `catatan_admin` text DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `laporan`
--

INSERT INTO `laporan` (`id`, `nomor_tiket`, `user_id`, `kategori_id`, `subjek`, `deskripsi`, `tanggal`, `status`, `catatan_admin`, `updated_at`) VALUES
(23, 'TIK20250626-0001', 4, 1, 'khanza farmasi', 'khanza farmasi komputer depan tidak bisa di buka.', '2025-06-26 21:57:53', 'selesai', 'Sudah bisa ya', '2025-06-26 22:01:19'),
(24, 'TIK20250626-0002', 4, 4, 'wifi tidak bisa', 'waktu di sambungkan , muncul tulisan tidak dapat terhubung ke internet', '2025-06-26 21:58:35', 'selesai', 'sudah normal dan bisa digunakan ya', '2025-06-26 22:00:19');

-- --------------------------------------------------------

--
-- Table structure for table `mail_settings`
--

CREATE TABLE `mail_settings` (
  `id` int(11) NOT NULL,
  `mail_host` varchar(100) DEFAULT NULL,
  `mail_port` int(11) DEFAULT NULL,
  `mail_username` varchar(100) DEFAULT NULL,
  `mail_password` varchar(100) DEFAULT NULL,
  `mail_from_email` varchar(100) DEFAULT NULL,
  `mail_from_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `monitoring_perangkat`
--

CREATE TABLE `monitoring_perangkat` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `perangkat` varchar(100) DEFAULT NULL,
  `load_status` varchar(100) DEFAULT NULL,
  `cpu_usage` decimal(5,2) DEFAULT NULL,
  `ram_usage` decimal(5,2) DEFAULT NULL,
  `root_space` decimal(6,2) DEFAULT NULL,
  `waktu_input` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `monitoring_perangkat`
--

INSERT INTO `monitoring_perangkat` (`id`, `user_id`, `perangkat`, `load_status`, `cpu_usage`, `ram_usage`, `root_space`, `waktu_input`) VALUES
(2, 2, 'Server 1 (database)', '37', '56.00', '56.00', '65.00', '2025-06-26 22:02:26');

-- --------------------------------------------------------

--
-- Table structure for table `pencatatan_server`
--

CREATE TABLE `pencatatan_server` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `suhu` decimal(5,2) DEFAULT NULL,
  `kondisi_ac` enum('Nyala','Tidak Nyala') DEFAULT NULL,
  `tegangan_ups` decimal(6,2) DEFAULT NULL,
  `waktu_input` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `pencatatan_server`
--

INSERT INTO `pencatatan_server` (`id`, `user_id`, `suhu`, `kondisi_ac`, `tegangan_ups`, `waktu_input`) VALUES
(11, 2, '25.00', 'Nyala', '220.00', '2025-06-26 22:01:58');

-- --------------------------------------------------------

--
-- Table structure for table `perusahaan`
--

CREATE TABLE `perusahaan` (
  `id` int(11) NOT NULL,
  `nama_perusahaan` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `kota` varchar(50) DEFAULT NULL,
  `provinsi` varchar(50) DEFAULT NULL,
  `kontak` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `logo` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `perusahaan`
--

INSERT INTO `perusahaan` (`id`, `nama_perusahaan`, `alamat`, `kota`, `provinsi`, `kontak`, `email`, `logo`, `created_at`) VALUES
(2, 'RS Permata Hati', 'Jl. Lebai Hasan', 'Bungo', 'Jambi', '082177846209', 'permatahatibungo@yahoo.com', 'uploads/logo_1750778335_logo RS.png', '2025-06-24 15:18:55');

-- --------------------------------------------------------

--
-- Table structure for table `spo_it`
--

CREATE TABLE `spo_it` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `no_spo` varchar(50) DEFAULT NULL,
  `judul_spo` varchar(255) DEFAULT NULL,
  `file_pdf` varchar(255) DEFAULT NULL,
  `tanggal_upload` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `spo_it`
--

INSERT INTO `spo_it` (`id`, `user_id`, `no_spo`, `judul_spo`, `file_pdf`, `tanggal_upload`) VALUES
(1, 2, '763/SPO-IT/RSUPH/VI/2025', 'SPO Backup Data Digital', 'spo_685d096f874e84.02323792.pdf', '2025-06-26 15:48:47');

-- --------------------------------------------------------

--
-- Table structure for table `unit_kerja`
--

CREATE TABLE `unit_kerja` (
  `id` int(11) NOT NULL,
  `nama_unit` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `unit_kerja`
--

INSERT INTO `unit_kerja` (`id`, `nama_unit`) VALUES
(1, 'IT & Marketing'),
(2, 'Instalasi Farmasi'),
(3, 'Depo Farmasi'),
(4, 'IGD'),
(5, 'ICU'),
(6, 'Laboratorium');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nik` varchar(20) DEFAULT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `jabatan` varchar(100) DEFAULT NULL,
  `unit_kerja` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role` enum('user','admin','manager') DEFAULT 'user',
  `status` enum('inactive','active') DEFAULT 'inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nik`, `nama`, `jabatan`, `unit_kerja`, `email`, `password_hash`, `role`, `status`, `created_at`) VALUES
(2, '16261046', 'M Wira Satria Buna', 'IT SIMRS', 'IT', 'wiramuhammad16@gmail.com', '$2y$10$W.4zH8NK5zF6yjEPWs46.OX7IpM/zyBpXJNwmHrJCzEIfa/JedGru', 'admin', 'active', '2025-06-24 10:29:08'),
(4, '16216089', 'M. Giano Shaquille Wiandra', 'Asisten Apoteker', 'Instalasi Farmasi', 'giano@gmail.com', '$2y$10$sDs9DKWNMeofr7FaZ/ZBR.OqiymaVkSD5yg6HGpkprKDljHko7y1C', 'user', 'active', '2025-06-24 13:22:24');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `akses_khanza`
--
ALTER TABLE `akses_khanza`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `kategori_id` (`kategori_id`);

--
-- Indexes for table `histori_status`
--
ALTER TABLE `histori_status`
  ADD PRIMARY KEY (`id`),
  ADD KEY `laporan_id` (`laporan_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `jabatan`
--
ALTER TABLE `jabatan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kategori_pelaporan`
--
ALTER TABLE `kategori_pelaporan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `laporan`
--
ALTER TABLE `laporan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `kategori_id` (`kategori_id`);

--
-- Indexes for table `mail_settings`
--
ALTER TABLE `mail_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `monitoring_perangkat`
--
ALTER TABLE `monitoring_perangkat`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pencatatan_server`
--
ALTER TABLE `pencatatan_server`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `perusahaan`
--
ALTER TABLE `perusahaan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `spo_it`
--
ALTER TABLE `spo_it`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `unit_kerja`
--
ALTER TABLE `unit_kerja`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nik` (`nik`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `akses_khanza`
--
ALTER TABLE `akses_khanza`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `histori_status`
--
ALTER TABLE `histori_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `jabatan`
--
ALTER TABLE `jabatan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `kategori_pelaporan`
--
ALTER TABLE `kategori_pelaporan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `laporan`
--
ALTER TABLE `laporan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `mail_settings`
--
ALTER TABLE `mail_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `monitoring_perangkat`
--
ALTER TABLE `monitoring_perangkat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pencatatan_server`
--
ALTER TABLE `pencatatan_server`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `perusahaan`
--
ALTER TABLE `perusahaan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `spo_it`
--
ALTER TABLE `spo_it`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `unit_kerja`
--
ALTER TABLE `unit_kerja`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `akses_khanza`
--
ALTER TABLE `akses_khanza`
  ADD CONSTRAINT `akses_khanza_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `akses_khanza_ibfk_2` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_pelaporan` (`id`);

--
-- Constraints for table `histori_status`
--
ALTER TABLE `histori_status`
  ADD CONSTRAINT `histori_status_ibfk_1` FOREIGN KEY (`laporan_id`) REFERENCES `laporan` (`id`),
  ADD CONSTRAINT `histori_status_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `laporan`
--
ALTER TABLE `laporan`
  ADD CONSTRAINT `laporan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `laporan_ibfk_2` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_pelaporan` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
