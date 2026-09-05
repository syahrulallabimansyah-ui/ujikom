-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 03, 2026 at 12:44 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `aksa_nova`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_profile`
--

CREATE TABLE `admin_profile` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `display_name` varchar(100) NOT NULL DEFAULT 'Admin',
  `foto` varchar(255) NOT NULL DEFAULT '',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin_profile`
--

INSERT INTO `admin_profile` (`id`, `user_id`, `display_name`, `foto`, `updated_at`) VALUES
(1, 1, 'Hoshimi Miyabi', 'uploads/profil/admin_6a8eadc034d7f.png', '2026-08-26 09:11:28');

-- --------------------------------------------------------

--
-- Table structure for table `banner`
--

CREATE TABLE `banner` (
  `id` int NOT NULL,
  `judul` varchar(150) NOT NULL DEFAULT '',
  `subjudul` varchar(255) NOT NULL DEFAULT '',
  `gambar` varchar(500) NOT NULL DEFAULT '',
  `link_url` varchar(255) NOT NULL DEFAULT '',
  `urutan` int NOT NULL DEFAULT '0',
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `banner`
--

INSERT INTO `banner` (`id`, `judul`, `subjudul`, `gambar`, `link_url`, `urutan`, `aktif`, `created_at`, `updated_at`) VALUES
(1, '', '', 'uploads/banner/banner_6a7d46716d677.jpg', '', 1, 0, '2026-08-13 04:22:09', '2026-08-31 09:04:13'),
(4, '', '', 'uploads/banner/banner_6a7d4d43adf79.jpg', '', 4, 1, '2026-08-13 04:51:15', '2026-08-31 09:34:42');

-- --------------------------------------------------------

--
-- Table structure for table `buku`
--

CREATE TABLE `buku` (
  `id` int NOT NULL,
  `judul` varchar(255) NOT NULL,
  `penulis` varchar(255) NOT NULL DEFAULT '',
  `isbn` varchar(50) NOT NULL DEFAULT '',
  `genre` varchar(100) NOT NULL DEFAULT '',
  `sinopsis` text,
  `stok` int NOT NULL DEFAULT '0',
  `gambar` varchar(500) NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `buku`
--

INSERT INTO `buku` (`id`, `judul`, `penulis`, `isbn`, `genre`, `sinopsis`, `stok`, `gambar`, `created_at`, `updated_at`) VALUES
(47, 'Kota yang bernama dan tak bernama', 'Ahmadun Y. Herfanda', '9789793062792', '', 'Short stories.', 1, 'uploads/gambar/buku_isbn_9789793062792_6a9550fa1f587.jpg', '2026-08-31 10:01:33', '2026-08-31 10:01:33'),
(48, 'To Kill a Mockingbird', 'Harper Lee', '9780061120084', 'fiction', 'USA/CAN', 1, 'uploads/gambar/buku_isbn_9780061120084_6a955170e1ca8.jpg', '2026-08-31 10:03:33', '2026-08-31 10:03:33'),
(49, 'The Hunger Games', 'Suzanne Collins', '9780439023528', 'severe poverty', '', 1, 'uploads/gambar/buku_isbn_9780439023528_6a95519f2df3e.jpg', '2026-08-31 10:04:31', '2026-08-31 10:04:31'),
(50, 'Clean Code', 'Robert C. Martin', '9780132350884', 'Agile software development', 'You are reading this book for two reasons. First, you are a programmer. Second, you want to be a better programmer. Good. We need better programmers.', 1, 'uploads/gambar/buku_isbn_9780132350884_6a9551c410703.jpg', '2026-08-31 10:04:53', '2026-08-31 10:04:53'),
(51, 'Harry Potter and the Philosopher\'s Stone', 'J. K. Rowling', '9780747532699', 'series:Harry_Potter', 'Mr. And Mrs. Dursley, of number four, Privet Drive, were proud to say that they were perfectly normal, thank you very much.', 1, 'uploads/gambar/buku_isbn_9780747532699_6a9551dccbb72.jpg', '2026-08-31 10:05:19', '2026-08-31 10:05:19');

-- --------------------------------------------------------

--
-- Table structure for table `buku_favorites`
--

CREATE TABLE `buku_favorites` (
  `id` int NOT NULL,
  `buku_id` int NOT NULL,
  `user_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buku_likes`
--

CREATE TABLE `buku_likes` (
  `id` int NOT NULL,
  `buku_id` int NOT NULL,
  `user_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `buku_likes`
--

INSERT INTO `buku_likes` (`id`, `buku_id`, `user_id`, `created_at`) VALUES
(24, 47, 21, '2026-09-01 04:11:52');

-- --------------------------------------------------------

--
-- Table structure for table `buku_ratings`
--

CREATE TABLE `buku_ratings` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `buku_id` int NOT NULL,
  `rating` tinyint NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `peminjaman`
--

CREATE TABLE `peminjaman` (
  `id` int NOT NULL,
  `buku_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `nama_peminjam` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `waktu_pinjam` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `batas_kembali` datetime NOT NULL,
  `waktu_kembali` datetime DEFAULT NULL,
  `terlambat_hari` int NOT NULL DEFAULT '0' COMMENT 'Jumlah hari keterlambatan',
  `denda` decimal(12,0) NOT NULL DEFAULT '0' COMMENT 'Total denda dalam rupiah',
  `status_denda` enum('tidak_ada','belum_bayar','lunas') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tidak_ada' COMMENT 'Status pembayaran denda',
  `status` enum('dipinjam','dikembalikan') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'dipinjam',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan`
--

CREATE TABLE `pengaturan` (
  `id` int NOT NULL,
  `kunci` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nama pengaturan',
  `nilai` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Nilai pengaturan',
  `keterangan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengaturan`
--

INSERT INTO `pengaturan` (`id`, `kunci`, `nilai`, `keterangan`, `updated_at`) VALUES
(1, 'denda_per_hari', '5000', 'Tarif denda per hari keterlambatan (Rupiah)', '2026-08-22 13:28:40'),
(2, 'denda_aktif', '0', 'Aktifkan fitur denda: 1=ya, 0=tidak', '2026-08-22 13:28:40'),
(3, 'denda_grace_period', '0', 'Toleransi hari sebelum denda mulai dihitung (0 = langsung denda di hari pertama)', '2026-08-22 13:28:40'),
(16, 'musik_file', 'uploads/musik/musik_6a954373ef75e.mp3', '', '2026-08-31 09:03:47'),
(17, 'musik_judul', 'OST - New Eridu', '', '2026-08-30 07:11:46'),
(18, 'musik_aktif', '1', '', '2026-08-30 11:10:55'),
(19, 'banner_mode', 'dinamis', '', '2026-08-30 08:33:20'),
(21, 'banner_background_id', '4', '', '2026-08-30 12:37:40');

-- --------------------------------------------------------

--
-- Table structure for table `reminder_log`
--

CREATE TABLE `reminder_log` (
  `id` int NOT NULL,
  `peminjaman_id` int NOT NULL,
  `tanggal` date NOT NULL COMMENT 'Tanggal deteksi/pengingat (1 baris per peminjaman per hari)',
  `terlambat_hari` int NOT NULL DEFAULT '0',
  `wa_terkirim` tinyint(1) NOT NULL DEFAULT '0',
  `wa_terkirim_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `nik` varchar(30) NOT NULL DEFAULT '',
  `kelas` varchar(50) NOT NULL DEFAULT '',
  `no_hp` varchar(20) NOT NULL DEFAULT '',
  `no_anggota` varchar(30) NOT NULL DEFAULT '',
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `foto` varchar(255) NOT NULL DEFAULT '',
  `role` enum('admin','member') DEFAULT 'member',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `card_status` enum('active','frozen') NOT NULL DEFAULT 'active' COMMENT 'Dibekukan sementara saat proses Lupa Kartu berlangsung',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `nik`, `kelas`, `no_hp`, `no_anggota`, `username`, `email`, `password`, `foto`, `role`, `status`, `card_status`, `created_at`) VALUES
(1, 'Administrator', '', '', '', 'AN-LAMA-00001', 'admin', 'admin@aksanova.com', '$2y$10$fG5Q3mZaf8XRV5K/s2S7yOULagsXHbA5rZP43ubCi9.Xq9yNerW9y', '', 'admin', 'approved', 'active', '2026-05-20 04:39:17'),
(10, 'harris', '', '', '', 'AN-LAMA-00010', 'javaname', 'fremynakano@gmail.com', '$2y$10$.RG3q02ljnrdvkbkF2ISleQl0ySQGnE6bSpbXKYdP4slJ4xtpOcnm', '', 'member', 'approved', 'active', '2026-06-09 05:43:12'),
(11, 'kamukamuaku', '', '', '', 'AN-LAMA-00011', 'bika', 'bibikiki@gmail.com', '$2y$10$C0DkJy8wtlN8HNEARk4py.mW0Qai2NhBmVPyx5GTk1Ik3MeYKTgkG', '', 'member', 'approved', 'active', '2026-08-04 05:26:28'),
(12, 'arull', '1234567890123456', '12rpl4', '083829165208', 'AN-2026-48267', 'arull', 'syahrulbimansyah@student.smkn1rongga.sch.id', '$2y$10$wzqDNyxrEd0OOkazrMUnJeZW9ad5.5DrmP34iIZ.3/kev76xKSw8q', '', 'member', 'approved', 'active', '2026-08-05 15:14:49'),
(13, 'kamukamu', '1234567890123451', '12rpl3', '083829165202', 'AN-2026-38567', 'kamukamu', 'syahrulgantengarul@gmail.com', '$2y$10$MkwjmrDAkQ0rFBbwzsMbQezy1jTQTV0lzLoIBuZxivJIMZBs5XE.q', '', 'member', 'approved', 'active', '2026-08-05 15:15:32'),
(14, 'augusta', '09876543211234', '12rpl4', '', 'AN-2026-73326', 'augusta', 'augusta@gmail.com', '$2y$10$hRdPCb5nuKXNhKO0Etz5Jeqkr4jgMUImnZZ1KB1vv1GjKmdsoGY5O', '', 'member', 'approved', 'active', '2026-08-09 10:43:39'),
(15, 'Arul Aruldoang', '111111111111111111', 'rpl2', '083816287171', 'AN-2026-93586', 'arularuldoang', 'arularuldoang@gmail.com', '$2y$10$YS0F24uVOjtWBC.TsjQRaumES7SGK01EOQZ6UB1Os7hvVIqH.NQdS', '', 'member', 'approved', 'active', '2026-08-09 12:31:55'),
(16, 'akuaku', '1234567890098765', 'xii rpl 3', '08381929997', 'AN-2026-43572', 'akuaku', 'syahrul.bimansyah42@smk.belajar.com', '$2y$10$xQUTf69jeBJfEe2uzEcCP.oeH06XuvGkhLi015XD9gJy7Gl/8Qwg2', '', 'member', 'approved', 'active', '2026-08-10 01:15:00'),
(17, 'arull', '1234567890123458', '12rpl4', '083829165209', 'AN-2026-26600', 'arull1', 'haha@gmail.com', '$2y$10$QMV9Y3SnVte6Sk1thXG7Keh4jSLXq6Q0Ns8kA71g5fymNyTZAA6ZK', '', 'member', 'approved', 'active', '2026-08-13 02:05:48'),
(18, 'Hendi', '123456789014', '12rpl4', '083829165208', 'AN-2026-71477', 'hendi', 'ahmadfikri@student.smkn1rongga.sch.id', '$2y$10$vSJZ3fvh5Lhzgi6JRKbxIuf00LJJqWw/ICqeih8AVu3ryomNDFHE2', '', 'member', 'approved', 'active', '2026-08-20 03:12:08'),
(19, 'arull', '1234567890123455', '12rpl4', '083829165201', 'AN-2026-51899', 'arull2', 'hihi@gmail.com', '$2y$10$1COVdBsrVBPmM8U6L1V2Bus23xx1/T/Rq1k1dRq2ctfGTFKSXkIJq', '', 'member', 'approved', 'active', '2026-08-22 13:21:17'),
(20, 'arull00', '1234567890123453', '12rpl7', '083829165205', 'AN-2026-43256', 'arull00', 'syahrulganteng@gmail.com', '$2y$10$FG8c7.JydudoXuX6/.LO9.fAtHGbctFFY75YcUztllfAVxy1dKc7i', 'uploads/anggota/anggota_88f2586a2760e1db.png', 'member', 'approved', 'active', '2026-08-22 14:46:30'),
(21, 'Iuno', '1234567890123452', '12rpl4', '083829165204', 'AN-2026-11635', 'iuno', 'hoho@gmail.com', '$2y$10$TDtqzEqnWr57E9/lfcdK.uucPVG3V5lLdSlMsL1Lp62JJ03Lee/hi', 'uploads/anggota/anggota_71f5803f1a7c181e.jpg', 'member', 'approved', 'active', '2026-08-27 02:01:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_profile`
--
ALTER TABLE `admin_profile`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_admin_user` (`user_id`);

--
-- Indexes for table `banner`
--
ALTER TABLE `banner`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `buku`
--
ALTER TABLE `buku`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `buku_favorites`
--
ALTER TABLE `buku_favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_fav` (`buku_id`,`user_id`),
  ADD KEY `fk_favorites_user` (`user_id`);

--
-- Indexes for table `buku_likes`
--
ALTER TABLE `buku_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_like` (`buku_id`,`user_id`),
  ADD KEY `fk_likes_user` (`user_id`);

--
-- Indexes for table `buku_ratings`
--
ALTER TABLE `buku_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_buku` (`user_id`,`buku_id`),
  ADD KEY `fk_ratings_buku` (`buku_id`);

--
-- Indexes for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_peminjaman_buku` (`buku_id`),
  ADD KEY `fk_peminjaman_user` (`user_id`);

--
-- Indexes for table `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_kunci` (`kunci`);

--
-- Indexes for table `reminder_log`
--
ALTER TABLE `reminder_log`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pinjam_tanggal` (`peminjaman_id`,`tanggal`),
  ADD KEY `idx_peminjaman` (`peminjaman_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `uq_no_anggota` (`no_anggota`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_profile`
--
ALTER TABLE `admin_profile`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `banner`
--
ALTER TABLE `banner`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `buku`
--
ALTER TABLE `buku`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `buku_favorites`
--
ALTER TABLE `buku_favorites`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `buku_likes`
--
ALTER TABLE `buku_likes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `buku_ratings`
--
ALTER TABLE `buku_ratings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `pengaturan`
--
ALTER TABLE `pengaturan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `reminder_log`
--
ALTER TABLE `reminder_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_profile`
--
ALTER TABLE `admin_profile`
  ADD CONSTRAINT `fk_admin_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `buku_favorites`
--
ALTER TABLE `buku_favorites`
  ADD CONSTRAINT `fk_favorites_buku` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_favorites_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `buku_likes`
--
ALTER TABLE `buku_likes`
  ADD CONSTRAINT `fk_likes_buku` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_likes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `buku_ratings`
--
ALTER TABLE `buku_ratings`
  ADD CONSTRAINT `fk_ratings_buku` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ratings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `fk_peminjaman_buku` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_peminjaman_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `reminder_log`
--
ALTER TABLE `reminder_log`
  ADD CONSTRAINT `fk_reminder_peminjaman` FOREIGN KEY (`peminjaman_id`) REFERENCES `peminjaman` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
