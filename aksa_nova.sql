-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 01, 2026 at 01:33 AM
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
(1, 1, 'Admin', 'uploads/profil/admin_6aa4b8dd7040b.jpg', '2026-09-12 02:28:45');

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
(1, '', '', 'uploads/banner/banner_6aa615ffdb620.jpg', '', 1, 1, '2026-08-13 04:22:09', '2026-09-13 03:18:23'),
(4, '', '', 'uploads/banner/banner_6a7d4d43adf79.jpg', '', 7, 1, '2026-08-13 04:51:15', '2026-09-13 11:33:51'),
(14, 'binbin', '', 'uploads/banner/banner_6aa67f38884f3.gif', '', 4, 1, '2026-09-13 10:47:20', '2026-09-13 11:33:51');

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
  `rak` varchar(100) NOT NULL DEFAULT '',
  `gambar` varchar(500) NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `buku`
--

INSERT INTO `buku` (`id`, `judul`, `penulis`, `isbn`, `genre`, `sinopsis`, `stok`, `rak`, `gambar`, `created_at`, `updated_at`) VALUES
(47, 'Kota yang bernama dan tak bernama', 'Ahmadun Y. Herfanda', '9789793062792', '', 'Short stories.', 5, 'Rak Umum 1', 'uploads/gambar/buku_isbn_9789793062792_6a9550fa1f587.jpg', '2026-08-31 10:01:33', '2026-09-13 11:45:51'),
(48, 'To Kill a Mockingbird', 'Harper Lee', '9780061120084', 'fiction', 'USA/CAN', 1, 'Rak Umum 1', 'uploads/gambar/buku_isbn_9780061120084_6a955170e1ca8.jpg', '2026-08-31 10:03:33', '2026-09-13 11:45:51'),
(49, 'The Hunger Games', 'Suzanne Collins', '9780439023528', 'severe poverty', '', 0, 'Rak Umum 1', 'uploads/gambar/buku_isbn_9780439023528_6a95519f2df3e.jpg', '2026-08-31 10:04:31', '2026-09-27 09:10:39'),
(50, 'Clean Code', 'Robert C. Martin', '9780132350884', 'Agile software development', 'You are reading this book for two reasons. First, you are a programmer. Second, you want to be a better programmer. Good. We need better programmers.', 0, 'Rak Umum 1', 'uploads/gambar/buku_isbn_9780132350884_6a9551c410703.jpg', '2026-08-31 10:04:53', '2026-09-13 11:45:51'),
(51, 'Harry Potter and the Philosopher\'s Stone', 'J. K. Rowling', '9780747532699', 'series:Harry_Potter', 'Mr. And Mrs. Dursley, of number four, Privet Drive, were proud to say that they were perfectly normal, thank you very much.', 9, 'Rak Umum 1', 'uploads/gambar/buku_isbn_9780747532699_6a9551dccbb72.jpg', '2026-08-31 10:05:19', '2026-09-27 01:11:04'),
(53, 'ワンパンマン 1', 'ONE, Yusuke Murata', '1421585642', '', '', 0, '', 'uploads/gambar/buku_isbn_1421585642_6ab4f903868f5.jpg', '2026-09-24 10:19:59', '2026-09-27 09:10:42'),
(54, 'ONE PIECE 11', '尾田栄一郎', '9781421506630', '', '', 0, '', 'uploads/gambar/buku_isbn_9781421506630_6ab4f9c989302.jpg', '2026-09-24 10:22:05', '2026-09-27 09:10:44'),
(57, 'My Hero Academia, Vol. 1', 'Kohei Horikoshi, Daruma Serveis Lingüistics  S.L.', '9781421582696', 'High schools', 'What would the world be like if 80 percent of the population manifested superpowers called **\"Quirks\"** at age four? **Heroes** and **villains** would be battling it out everywhere! Being a hero would mean learning to use your power, but where would you go to study? **The Hero Academy** of course! But what would you do if you were one of the 20 percent who were born **Quirkless**?\r\n\r\n Middle school student Izuku Midoriya wants to be a hero more than anything, but he hasn\'t got an ounce of power in him. With no chance of ever getting into the prestigious U.A. High School for budding heroes, his life is looking more and more like a dead end. Then an encounter with All Might, the greatest hero of them all, gives him a chance to change his destiny...', 7, '', 'uploads/gambar/buku_isbn_9781421582696_6ab4fb675efa1.jpg', '2026-09-24 10:29:09', '2026-09-24 10:29:09');

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

--
-- Dumping data for table `buku_favorites`
--

INSERT INTO `buku_favorites` (`id`, `buku_id`, `user_id`, `created_at`) VALUES
(26, 47, 26, '2026-09-12 08:27:16');

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
(27, 47, 26, '2026-09-12 08:26:49');

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
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `id` int NOT NULL,
  `nama_kelas` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kelas`
--

INSERT INTO `kelas` (`id`, `nama_kelas`, `created_at`) VALUES
(5, 'XI RPL 1', '2026-09-06 05:57:26'),
(6, 'XI RPL 2', '2026-09-06 05:57:26'),
(7, 'XII RPL 1', '2026-09-06 05:57:26'),
(8, 'XII RPL 2', '2026-09-06 05:57:26'),
(13, 'Alumni', '2026-09-06 07:28:21');

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

--
-- Dumping data for table `peminjaman`
--

INSERT INTO `peminjaman` (`id`, `buku_id`, `user_id`, `nama_peminjam`, `waktu_pinjam`, `batas_kembali`, `waktu_kembali`, `terlambat_hari`, `denda`, `status_denda`, `status`, `created_at`) VALUES
(31, 50, 26, 'Tsaritsa Anastasia', '2026-09-06 16:41:23', '2026-09-13 23:59:59', '2026-09-12 15:22:14', 0, 0, 'tidak_ada', 'dikembalikan', '2026-09-06 09:41:23'),
(33, 49, 26, 'Tsaritsa Anastasia', '2026-09-12 15:21:38', '2026-09-19 23:59:59', '2026-09-12 15:22:12', 0, 0, 'tidak_ada', 'dikembalikan', '2026-09-12 08:21:38'),
(34, 50, 52, 'Nur Janah', '2026-09-13 11:29:21', '2026-09-20 23:59:59', NULL, 0, 0, 'tidak_ada', 'dipinjam', '2026-09-13 04:29:21'),
(35, 51, 53, 'Bimansyah Syahrulalla', '2026-09-27 08:11:04', '2026-10-04 23:59:59', NULL, 0, 0, 'tidak_ada', 'dipinjam', '2026-09-27 01:11:04'),
(36, 49, 26, 'Tsaritsa Anastasia', '2026-09-27 16:10:39', '2026-10-04 23:59:59', NULL, 0, 0, 'tidak_ada', 'dipinjam', '2026-09-27 09:10:39'),
(37, 53, 26, 'Tsaritsa Anastasia', '2026-09-27 16:10:42', '2026-10-04 23:59:59', NULL, 0, 0, 'tidak_ada', 'dipinjam', '2026-09-27 09:10:42'),
(38, 54, 26, 'Tsaritsa Anastasia', '2026-09-27 16:10:44', '2026-10-04 23:59:59', NULL, 0, 0, 'tidak_ada', 'dipinjam', '2026-09-27 09:10:44');

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan_peminjaman`
--

CREATE TABLE `pengajuan_peminjaman` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `buku_id` int NOT NULL,
  `nama_peminjam` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_kartu` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_buku` int NOT NULL DEFAULT '1',
  `batas_kembali` date NOT NULL,
  `waktu_pengambilan` enum('sekarang','nanti') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sekarang',
  `catatan_pengambilan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('menunggu','disetujui','ditolak') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'menunggu',
  `alasan_penolakan` text COLLATE utf8mb4_unicode_ci,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengajuan_peminjaman`
--

INSERT INTO `pengajuan_peminjaman` (`id`, `user_id`, `buku_id`, `nama_peminjam`, `file_kartu`, `total_buku`, `batas_kembali`, `waktu_pengambilan`, `catatan_pengambilan`, `status`, `alasan_penolakan`, `approved_at`, `created_at`) VALUES
(5, 26, 50, 'Tsaritsa Anastasia', 'uploads/kartu_pengajuan/kartu_26_1788687614_f4eec17d.png', 1, '2026-09-13', 'sekarang', '', 'disetujui', NULL, '2026-09-06 16:41:23', '2026-09-06 09:40:14'),
(13, 26, 49, 'Tsaritsa Anastasia', 'uploads/kartu_pengajuan/kartu_26_1789201078_a28303b5.png', 1, '2026-09-19', 'sekarang', 'Diambil langsung di perpustakaan hari ini', 'disetujui', NULL, '2026-09-12 15:21:38', '2026-09-12 08:17:58'),
(14, 53, 51, 'Bimansyah Syahrulalla', 'uploads/kartu_pengajuan/kartu_53_1790471198_d0fe4f04.png', 1, '2026-10-04', 'nanti', 'Tgl: 29/09/2026 · Istirahat ke-2 (12:00 - 12:45 WIB) · saya makan dulu', 'ditolak', 'salah foto', '2026-09-27 08:09:25', '2026-09-27 01:06:38'),
(15, 53, 51, 'Bimansyah Syahrulalla', 'uploads/kartu_pengajuan/kartu_53_1790471269_72646e85.png', 1, '2026-10-04', 'sekarang', 'Diambil langsung di perpustakaan hari ini', 'disetujui', NULL, '2026-09-27 08:11:04', '2026-09-27 01:07:49'),
(16, 26, 54, 'Tsaritsa Anastasia', 'uploads/kartu_pengajuan/kartu_26_1790500101_4bc046ba.png', 1, '2026-10-04', 'sekarang', 'Diambil langsung di perpustakaan hari ini', 'disetujui', NULL, '2026-09-27 16:10:44', '2026-09-27 09:08:21'),
(17, 26, 53, 'Tsaritsa Anastasia', 'uploads/kartu_pengajuan/kartu_26_1790500131_d5f9c8eb.png', 1, '2026-10-04', 'sekarang', 'Diambil langsung di perpustakaan hari ini', 'disetujui', NULL, '2026-09-27 16:10:42', '2026-09-27 09:08:51'),
(18, 26, 49, 'Tsaritsa Anastasia', 'uploads/kartu_pengajuan/kartu_26_1790500190_f7850687.png', 1, '2026-10-04', 'sekarang', 'Diambil langsung di perpustakaan hari ini', 'disetujui', NULL, '2026-09-27 16:10:39', '2026-09-27 09:09:50'),
(19, 26, 51, 'Tsaritsa Anastasia', 'uploads/kartu_pengajuan/kartu_26_1790580070_b749a65c.png', 1, '2026-10-05', 'sekarang', 'Diambil langsung di perpustakaan hari ini', 'menunggu', NULL, NULL, '2026-09-28 07:21:10');

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
(16, 'musik_file', 'uploads/musik/musik_6ab869336704a.mp3', '', '2026-09-27 00:54:11'),
(17, 'musik_judul', 'OST - New Eridu', '', '2026-08-30 07:11:46'),
(18, 'musik_aktif', '1', '', '2026-09-27 00:54:27'),
(19, 'banner_mode', 'dinamis', '', '2026-08-30 08:33:20'),
(21, 'banner_background_id', '14', '', '2026-09-13 10:47:26'),
(22, 'lokasi_sekolah', 'SMK Negeri 1 Rongga', 'Nama sekolah / perpustakaan', '2026-09-13 11:15:19'),
(23, 'lokasi_alamat', 'Jl. Situ Gede / Jl. Raya Rongga (RT 01/RW 04), Desa Cibedug, Kec. Rongga, Kab. Bandung Barat, Jawa Barat 40565', 'Alamat lengkap perpustakaan', '2026-09-13 11:15:19'),
(24, 'lokasi_jam', 'Senin – Jumat, 07.00 – 15.00 WIB', 'Jam operasional perpustakaan', '2026-09-13 11:15:19'),
(25, 'lokasi_telepon', '083829165209', 'Nomor telepon / WhatsApp perpustakaan', '2026-09-13 11:15:19'),
(26, 'lokasi_map_query', 'SMK Negeri 1 Rongga, Bandung Barat', 'Query pencarian Google Maps', '2026-09-13 11:15:19');

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
(26, 'Tsaritsa Anastasia', '', 'Alumni', '083829165202', 'AN-2026-84262', 'tsaritsaanastasia', 'tsaritsaanastasia@student.smkn1rongga.sch.id', '$2y$10$zhUhf66PZbAEJMksIMM3K.mSyzFASdorET5kpvORkHLrGr3toA6pe', 'uploads/anggota/anggota_3a9a51558685d13a.jpg', 'member', 'approved', 'active', '2026-09-06 06:40:36'),
(52, 'Nur Janah', '', 'XI RPL 1', '083829165208', 'AN-2026-45952', 'nurjanah', 'nurj88230@student.smkn1rongga.sch.id', '$2y$10$Rai8g7YisUAbOz1kpEuXceB605A0krDct7DYKOp8i5Rp3uUhS8Pie', 'uploads/anggota/anggota_e26c6355378d9400.jpg', 'member', 'rejected', 'active', '2026-09-12 08:09:20'),
(53, 'Bimansyah Syahrulalla', '', 'XI RPL 1', '083829165208', 'AN-2026-01627', 'bimansyahsyahrulalla', 'syahrulbimansyah@student.smkn1rongga.sch.id', '$2y$10$L17C0oXfqB/z1qTk31rrd.28uz1uX1772XPTY9h9XnPIV9Ic8qj7m', 'uploads/anggota/anggota_a811679367ed2f7f.png', 'member', 'approved', 'active', '2026-09-27 00:36:30');

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
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_kelas` (`nama_kelas`);

--
-- Indexes for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_peminjaman_buku` (`buku_id`),
  ADD KEY `fk_peminjaman_user` (`user_id`);

--
-- Indexes for table `pengajuan_peminjaman`
--
ALTER TABLE `pengajuan_peminjaman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `buku_id` (`buku_id`),
  ADD KEY `status` (`status`);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `buku`
--
ALTER TABLE `buku`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `buku_favorites`
--
ALTER TABLE `buku_favorites`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `buku_likes`
--
ALTER TABLE `buku_likes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `buku_ratings`
--
ALTER TABLE `buku_ratings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `pengajuan_peminjaman`
--
ALTER TABLE `pengajuan_peminjaman`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `pengaturan`
--
ALTER TABLE `pengaturan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `reminder_log`
--
ALTER TABLE `reminder_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

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
