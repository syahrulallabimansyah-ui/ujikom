-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 13 Sep 2026 pada 12.12
-- Versi server: 8.0.30
-- Versi PHP: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Basis data: `aksa_nova`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin_profile`
--

CREATE TABLE `admin_profile` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `display_name` varchar(100) NOT NULL DEFAULT 'Admin',
  `foto` varchar(255) NOT NULL DEFAULT '',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `admin_profile`
--

INSERT INTO `admin_profile` (`id`, `user_id`, `display_name`, `foto`, `updated_at`) VALUES
(1, 1, 'Admin', 'uploads/profil/admin_6aa4b8dd7040b.jpg', '2026-09-12 02:28:45');

-- --------------------------------------------------------

--
-- Struktur dari tabel `banner`
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
-- Dumping data untuk tabel `banner`
--

INSERT INTO `banner` (`id`, `judul`, `subjudul`, `gambar`, `link_url`, `urutan`, `aktif`, `created_at`, `updated_at`) VALUES
(1, '', '', 'uploads/banner/banner_6aa615ffdb620.jpg', '', 1, 1, '2026-08-13 04:22:09', '2026-09-13 03:18:23'),
(4, '', '', 'uploads/banner/banner_6a7d4d43adf79.jpg', '', 7, 1, '2026-08-13 04:51:15', '2026-09-13 11:33:51'),
(14, 'binbin', '', 'uploads/banner/banner_6aa67f38884f3.gif', '', 4, 1, '2026-09-13 10:47:20', '2026-09-13 11:33:51');

-- --------------------------------------------------------

--
-- Struktur dari tabel `buku`
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
-- Dumping data untuk tabel `buku`
--

INSERT INTO `buku` (`id`, `judul`, `penulis`, `isbn`, `genre`, `sinopsis`, `stok`, `rak`, `gambar`, `created_at`, `updated_at`) VALUES
(47, 'Kota yang bernama dan tak bernama', 'Ahmadun Y. Herfanda', '9789793062792', '', 'Short stories.', 5, 'Rak Umum 1', 'uploads/gambar/buku_isbn_9789793062792_6a9550fa1f587.jpg', '2026-08-31 10:01:33', '2026-09-13 11:45:51'),
(48, 'To Kill a Mockingbird', 'Harper Lee', '9780061120084', 'fiction', 'USA/CAN', 1, 'Rak Umum 1', 'uploads/gambar/buku_isbn_9780061120084_6a955170e1ca8.jpg', '2026-08-31 10:03:33', '2026-09-13 11:45:51'),
(49, 'The Hunger Games', 'Suzanne Collins', '9780439023528', 'severe poverty', '', 1, 'Rak Umum 1', 'uploads/gambar/buku_isbn_9780439023528_6a95519f2df3e.jpg', '2026-08-31 10:04:31', '2026-09-13 11:45:51'),
(50, 'Clean Code', 'Robert C. Martin', '9780132350884', 'Agile software development', 'You are reading this book for two reasons. First, you are a programmer. Second, you want to be a better programmer. Good. We need better programmers.', 0, 'Rak Umum 1', 'uploads/gambar/buku_isbn_9780132350884_6a9551c410703.jpg', '2026-08-31 10:04:53', '2026-09-13 11:45:51'),
(51, 'Harry Potter and the Philosopher\'s Stone', 'J. K. Rowling', '9780747532699', 'series:Harry_Potter', 'Mr. And Mrs. Dursley, of number four, Privet Drive, were proud to say that they were perfectly normal, thank you very much.', 10, 'Rak Umum 1', 'uploads/gambar/buku_isbn_9780747532699_6a9551dccbb72.jpg', '2026-08-31 10:05:19', '2026-09-13 11:45:51');

-- --------------------------------------------------------

--
-- Struktur dari tabel `buku_favorites`
--

CREATE TABLE `buku_favorites` (
  `id` int NOT NULL,
  `buku_id` int NOT NULL,
  `user_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `buku_favorites`
--

INSERT INTO `buku_favorites` (`id`, `buku_id`, `user_id`, `created_at`) VALUES
(26, 47, 26, '2026-09-12 08:27:16');

-- --------------------------------------------------------

--
-- Struktur dari tabel `buku_likes`
--

CREATE TABLE `buku_likes` (
  `id` int NOT NULL,
  `buku_id` int NOT NULL,
  `user_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `buku_likes`
--

INSERT INTO `buku_likes` (`id`, `buku_id`, `user_id`, `created_at`) VALUES
(27, 47, 26, '2026-09-12 08:26:49');

-- --------------------------------------------------------

--
-- Struktur dari tabel `buku_ratings`
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
-- Struktur dari tabel `kelas`
--

CREATE TABLE `kelas` (
  `id` int NOT NULL,
  `nama_kelas` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `kelas`
--

INSERT INTO `kelas` (`id`, `nama_kelas`, `created_at`) VALUES
(5, 'XI RPL 1', '2026-09-06 05:57:26'),
(6, 'XI RPL 2', '2026-09-06 05:57:26'),
(7, 'XII RPL 1', '2026-09-06 05:57:26'),
(8, 'XII RPL 2', '2026-09-06 05:57:26'),
(13, 'Alumni', '2026-09-06 07:28:21');

-- --------------------------------------------------------

--
-- Struktur dari tabel `peminjaman`
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
-- Dumping data untuk tabel `peminjaman`
--

INSERT INTO `peminjaman` (`id`, `buku_id`, `user_id`, `nama_peminjam`, `waktu_pinjam`, `batas_kembali`, `waktu_kembali`, `terlambat_hari`, `denda`, `status_denda`, `status`, `created_at`) VALUES
(31, 50, 26, 'Tsaritsa Anastasia', '2026-09-06 16:41:23', '2026-09-13 23:59:59', '2026-09-12 15:22:14', 0, 0, 'tidak_ada', 'dikembalikan', '2026-09-06 09:41:23'),
(33, 49, 26, 'Tsaritsa Anastasia', '2026-09-12 15:21:38', '2026-09-19 23:59:59', '2026-09-12 15:22:12', 0, 0, 'tidak_ada', 'dikembalikan', '2026-09-12 08:21:38'),
(34, 50, 52, 'Nur Janah', '2026-09-13 11:29:21', '2026-09-20 23:59:59', NULL, 0, 0, 'tidak_ada', 'dipinjam', '2026-09-13 04:29:21');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengajuan_peminjaman`
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
-- Dumping data untuk tabel `pengajuan_peminjaman`
--

INSERT INTO `pengajuan_peminjaman` (`id`, `user_id`, `buku_id`, `nama_peminjam`, `file_kartu`, `total_buku`, `batas_kembali`, `waktu_pengambilan`, `catatan_pengambilan`, `status`, `alasan_penolakan`, `approved_at`, `created_at`) VALUES
(5, 26, 50, 'Tsaritsa Anastasia', 'uploads/kartu_pengajuan/kartu_26_1788687614_f4eec17d.png', 1, '2026-09-13', 'sekarang', '', 'disetujui', NULL, '2026-09-06 16:41:23', '2026-09-06 09:40:14'),
(13, 26, 49, 'Tsaritsa Anastasia', 'uploads/kartu_pengajuan/kartu_26_1789201078_a28303b5.png', 1, '2026-09-19', 'sekarang', 'Diambil langsung di perpustakaan hari ini', 'disetujui', NULL, '2026-09-12 15:21:38', '2026-09-12 08:17:58');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengaturan`
--

CREATE TABLE `pengaturan` (
  `id` int NOT NULL,
  `kunci` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nama pengaturan',
  `nilai` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Nilai pengaturan',
  `keterangan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pengaturan`
--

INSERT INTO `pengaturan` (`id`, `kunci`, `nilai`, `keterangan`, `updated_at`) VALUES
(1, 'denda_per_hari', '5000', 'Tarif denda per hari keterlambatan (Rupiah)', '2026-08-22 13:28:40'),
(2, 'denda_aktif', '0', 'Aktifkan fitur denda: 1=ya, 0=tidak', '2026-08-22 13:28:40'),
(3, 'denda_grace_period', '0', 'Toleransi hari sebelum denda mulai dihitung (0 = langsung denda di hari pertama)', '2026-08-22 13:28:40'),
(16, 'musik_file', 'uploads/musik/musik_6aa689b59fc23.mp3', '', '2026-09-13 11:32:05'),
(17, 'musik_judul', 'OST - New Eridu', '', '2026-08-30 07:11:46'),
(18, 'musik_aktif', '1', '', '2026-09-13 11:32:05'),
(19, 'banner_mode', 'dinamis', '', '2026-08-30 08:33:20'),
(21, 'banner_background_id', '14', '', '2026-09-13 10:47:26'),
(22, 'lokasi_sekolah', 'SMK Negeri 1 Rongga', 'Nama sekolah / perpustakaan', '2026-09-13 11:15:19'),
(23, 'lokasi_alamat', 'Jl. Situ Gede / Jl. Raya Rongga (RT 01/RW 04), Desa Cibedug, Kec. Rongga, Kab. Bandung Barat, Jawa Barat 40565', 'Alamat lengkap perpustakaan', '2026-09-13 11:15:19'),
(24, 'lokasi_jam', 'Senin – Jumat, 07.00 – 15.00 WIB', 'Jam operasional perpustakaan', '2026-09-13 11:15:19'),
(25, 'lokasi_telepon', '083829165209', 'Nomor telepon / WhatsApp perpustakaan', '2026-09-13 11:15:19'),
(26, 'lokasi_map_query', 'SMK Negeri 1 Rongga, Bandung Barat', 'Query pencarian Google Maps', '2026-09-13 11:15:19');

-- --------------------------------------------------------

--
-- Struktur dari tabel `reminder_log`
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
-- Struktur dari tabel `users`
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
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `full_name`, `nik`, `kelas`, `no_hp`, `no_anggota`, `username`, `email`, `password`, `foto`, `role`, `status`, `card_status`, `created_at`) VALUES
(1, 'Administrator', '', '', '', 'AN-LAMA-00001', 'admin', 'admin@aksanova.com', '$2y$10$fG5Q3mZaf8XRV5K/s2S7yOULagsXHbA5rZP43ubCi9.Xq9yNerW9y', '', 'admin', 'approved', 'active', '2026-05-20 04:39:17'),
(26, 'Tsaritsa Anastasia', '', 'Alumni', '083829165202', 'AN-2026-84262', 'tsaritsaanastasia', 'tsaritsaanastasia@student.smkn1rongga.sch.id', '$2y$10$zhUhf66PZbAEJMksIMM3K.mSyzFASdorET5kpvORkHLrGr3toA6pe', 'uploads/anggota/anggota_3a9a51558685d13a.jpg', 'member', 'approved', 'active', '2026-09-06 06:40:36'),
(52, 'Nur Janah', '', 'XI RPL 1', '083829165208', 'AN-2026-45952', 'nurjanah', 'nurj88230@student.smkn1rongga.sch.id', '$2y$10$Rai8g7YisUAbOz1kpEuXceB605A0krDct7DYKOp8i5Rp3uUhS8Pie', 'uploads/anggota/anggota_e26c6355378d9400.jpg', 'member', 'approved', 'active', '2026-09-12 08:09:20');

--
-- Indeks untuk tabel yang dibuang
--

--
-- Indeks untuk tabel `admin_profile`
--
ALTER TABLE `admin_profile`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_admin_user` (`user_id`);

--
-- Indeks untuk tabel `banner`
--
ALTER TABLE `banner`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `buku`
--
ALTER TABLE `buku`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `buku_favorites`
--
ALTER TABLE `buku_favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_fav` (`buku_id`,`user_id`),
  ADD KEY `fk_favorites_user` (`user_id`);

--
-- Indeks untuk tabel `buku_likes`
--
ALTER TABLE `buku_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_like` (`buku_id`,`user_id`),
  ADD KEY `fk_likes_user` (`user_id`);

--
-- Indeks untuk tabel `buku_ratings`
--
ALTER TABLE `buku_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_buku` (`user_id`,`buku_id`),
  ADD KEY `fk_ratings_buku` (`buku_id`);

--
-- Indeks untuk tabel `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_kelas` (`nama_kelas`);

--
-- Indeks untuk tabel `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_peminjaman_buku` (`buku_id`),
  ADD KEY `fk_peminjaman_user` (`user_id`);

--
-- Indeks untuk tabel `pengajuan_peminjaman`
--
ALTER TABLE `pengajuan_peminjaman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `buku_id` (`buku_id`),
  ADD KEY `status` (`status`);

--
-- Indeks untuk tabel `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_kunci` (`kunci`);

--
-- Indeks untuk tabel `reminder_log`
--
ALTER TABLE `reminder_log`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pinjam_tanggal` (`peminjaman_id`,`tanggal`),
  ADD KEY `idx_peminjaman` (`peminjaman_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `uq_no_anggota` (`no_anggota`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin_profile`
--
ALTER TABLE `admin_profile`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `banner`
--
ALTER TABLE `banner`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `buku`
--
ALTER TABLE `buku`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT untuk tabel `buku_favorites`
--
ALTER TABLE `buku_favorites`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT untuk tabel `buku_likes`
--
ALTER TABLE `buku_likes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT untuk tabel `buku_ratings`
--
ALTER TABLE `buku_ratings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT untuk tabel `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT untuk tabel `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT untuk tabel `pengajuan_peminjaman`
--
ALTER TABLE `pengajuan_peminjaman`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `pengaturan`
--
ALTER TABLE `pengaturan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT untuk tabel `reminder_log`
--
ALTER TABLE `reminder_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `admin_profile`
--
ALTER TABLE `admin_profile`
  ADD CONSTRAINT `fk_admin_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `buku_favorites`
--
ALTER TABLE `buku_favorites`
  ADD CONSTRAINT `fk_favorites_buku` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_favorites_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `buku_likes`
--
ALTER TABLE `buku_likes`
  ADD CONSTRAINT `fk_likes_buku` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_likes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `buku_ratings`
--
ALTER TABLE `buku_ratings`
  ADD CONSTRAINT `fk_ratings_buku` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ratings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `fk_peminjaman_buku` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_peminjaman_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `reminder_log`
--
ALTER TABLE `reminder_log`
  ADD CONSTRAINT `fk_reminder_peminjaman` FOREIGN KEY (`peminjaman_id`) REFERENCES `peminjaman` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
