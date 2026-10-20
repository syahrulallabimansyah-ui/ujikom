-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: localhost    Database: aksa_nova
-- ------------------------------------------------------
-- Server version	8.0.30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;



--
-- Table structure for table `admin_profile`
--

DROP TABLE IF EXISTS `admin_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_profile` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `display_name` varchar(100) NOT NULL DEFAULT 'Admin',
  `foto` varchar(255) NOT NULL DEFAULT '',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_user` (`user_id`),
  CONSTRAINT `fk_admin_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_profile`
--

LOCK TABLES `admin_profile` WRITE;
/*!40000 ALTER TABLE `admin_profile` DISABLE KEYS */;
INSERT INTO `admin_profile` VALUES (1,1,'Admin','uploads/profil/admin_6abdd31952456.jpg','2026-10-01 03:27:21');
/*!40000 ALTER TABLE `admin_profile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `banner`
--

DROP TABLE IF EXISTS `banner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `banner` (
  `id` int NOT NULL AUTO_INCREMENT,
  `judul` varchar(150) NOT NULL DEFAULT '',
  `subjudul` varchar(255) NOT NULL DEFAULT '',
  `gambar` varchar(500) NOT NULL DEFAULT '',
  `link_url` varchar(255) NOT NULL DEFAULT '',
  `urutan` int NOT NULL DEFAULT '0',
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banner`
--

LOCK TABLES `banner` WRITE;
/*!40000 ALTER TABLE `banner` DISABLE KEYS */;
INSERT INTO `banner` VALUES (1,'','','uploads/banner/banner_6aa615ffdb620.jpg','',1,1,'2026-08-13 04:22:09','2026-09-13 03:18:23'),(4,'','','uploads/banner/banner_6a7d4d43adf79.jpg','',7,1,'2026-08-13 04:51:15','2026-09-13 11:33:51'),(14,'binbin','','uploads/banner/banner_6aa67f38884f3.gif','',4,1,'2026-09-13 10:47:20','2026-09-13 11:33:51');
/*!40000 ALTER TABLE `banner` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buku`
--

DROP TABLE IF EXISTS `buku`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `buku` (
  `id` int NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) NOT NULL,
  `penulis` varchar(255) NOT NULL DEFAULT '',
  `isbn` varchar(50) NOT NULL DEFAULT '',
  `genre` varchar(100) NOT NULL DEFAULT '',
  `sinopsis` text,
  `stok` int NOT NULL DEFAULT '0',
  `rak` varchar(100) NOT NULL DEFAULT '',
  `gambar` varchar(500) NOT NULL DEFAULT '',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buku`
--

LOCK TABLES `buku` WRITE;
/*!40000 ALTER TABLE `buku` DISABLE KEYS */;
INSERT INTO `buku` VALUES (47,'Kota yang bernama dan tak bernama','Ahmadun Y. Herfanda','9789793062792','','Short stories.',5,'Rak Umum 1','uploads/gambar/buku_isbn_9789793062792_6a9550fa1f587.jpg','2026-08-31 10:01:33','2026-10-10 05:55:10'),(48,'To Kill a Mockingbird','Harper Lee','9780061120084','fiction','USA/CAN',1,'Rak Umum 1','uploads/gambar/buku_isbn_9780061120084_6a955170e1ca8.jpg','2026-08-31 10:03:33','2026-09-13 11:45:51'),(49,'The Hunger Games','Suzanne Collins','9780439023528','severe poverty','',1,'Rak Umum 1','uploads/gambar/buku_isbn_9780439023528_6a95519f2df3e.jpg','2026-08-31 10:04:31','2026-10-10 05:55:14'),(50,'Clean Code','Robert C. Martin','9780132350884','Agile software development','You are reading this book for two reasons. First, you are a programmer. Second, you want to be a better programmer. Good. We need better programmers.',1,'Rak Umum 1','uploads/gambar/buku_isbn_9780132350884_6a9551c410703.jpg','2026-08-31 10:04:53','2026-10-10 05:55:19'),(51,'Harry Potter and the Philosopher\'s Stone','J. K. Rowling','9780747532699','series:Harry_Potter','Mr. And Mrs. Dursley, of number four, Privet Drive, were proud to say that they were perfectly normal, thank you very much.',10,'Rak Umum 1','uploads/gambar/buku_isbn_9780747532699_6a9551dccbb72.jpg','2026-08-31 10:05:19','2026-10-10 05:55:16'),(53,'ワンパンマン 1','ONE, Yusuke Murata','1421585642','','',0,'','uploads/gambar/buku_isbn_1421585642_6ab4f903868f5.jpg','2026-09-24 10:19:59','2026-10-13 03:40:18'),(54,'ONE PIECE 11','尾田栄一郎','9781421506630','','',1,'','uploads/gambar/buku_isbn_9781421506630_6ab4f9c989302.jpg','2026-09-24 10:22:05','2026-10-06 10:54:24'),(58,'Harry Potter and the Philosopher\'s Stone','J. K. Rowling','9781421582696','series:Harry_Potter','Turning the envelope over, his hand trembling, Harry saw a purple wax seal bearing a coat of arms; a lion, an eagle, a badger and a snake surrounding a large letter \'H\'.\r\n\r\nHARRY POTTER has never even heard of Hogwarts when the LETTERS start dropping on the doormat at number four, Privet Drive. Addressed in GREEN INK on yellowish parchment with a PURPLE SEAL, they are swiftly confiscated by his GRISLY aunt and uncle. Then, on Harry\'s eleventh birthday, a great beetle-eyed giant of a man called RUBEUS HAGRID bursts in with some ASTONISHING news: Harry Potter is a wizard, and he has a place at Hogwarts School of Witchcraft and Wizardry. An incredible adventure is about to begin!',1,'','uploads/gambar/buku_isbn_9780747532699_6abdd2e5cceff.jpg','2026-10-01 03:26:57','2026-10-01 03:26:57'),(61,'My Hero Academia, Vol. 1','Kohei Horikoshi, Daruma Serveis Lingüistics  S.L.','9781421582696','High schools','it is amazng',1,'','uploads/gambar/buku_isbn_9781421582696_6acda25d2d6a2.jpg','2026-10-13 03:15:50','2026-10-13 04:11:27');
/*!40000 ALTER TABLE `buku` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buku_favorites`
--

DROP TABLE IF EXISTS `buku_favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `buku_favorites` (
  `id` int NOT NULL AUTO_INCREMENT,
  `buku_id` int NOT NULL,
  `user_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_fav` (`buku_id`,`user_id`),
  KEY `fk_favorites_user` (`user_id`),
  CONSTRAINT `fk_favorites_buku` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_favorites_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buku_favorites`
--

LOCK TABLES `buku_favorites` WRITE;
/*!40000 ALTER TABLE `buku_favorites` DISABLE KEYS */;
INSERT INTO `buku_favorites` VALUES (35,53,54,'2026-10-10 06:10:38');
/*!40000 ALTER TABLE `buku_favorites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buku_likes`
--

DROP TABLE IF EXISTS `buku_likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `buku_likes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `buku_id` int NOT NULL,
  `user_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_like` (`buku_id`,`user_id`),
  KEY `fk_likes_user` (`user_id`),
  CONSTRAINT `fk_likes_buku` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_likes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buku_likes`
--

LOCK TABLES `buku_likes` WRITE;
/*!40000 ALTER TABLE `buku_likes` DISABLE KEYS */;
INSERT INTO `buku_likes` VALUES (28,47,54,'2026-10-10 06:10:33'),(30,53,54,'2026-10-10 06:10:38');
/*!40000 ALTER TABLE `buku_likes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `buku_ratings`
--

DROP TABLE IF EXISTS `buku_ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `buku_ratings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `buku_id` int NOT NULL,
  `rating` tinyint NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_buku` (`user_id`,`buku_id`),
  KEY `fk_ratings_buku` (`buku_id`),
  CONSTRAINT `fk_ratings_buku` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ratings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `buku_ratings`
--

LOCK TABLES `buku_ratings` WRITE;
/*!40000 ALTER TABLE `buku_ratings` DISABLE KEYS */;
/*!40000 ALTER TABLE `buku_ratings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kelas`
--

DROP TABLE IF EXISTS `kelas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kelas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_kelas` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama_kelas` (`nama_kelas`)
) ENGINE=InnoDB AUTO_INCREMENT=448 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `kelas`
--

LOCK TABLES `kelas` WRITE;
/*!40000 ALTER TABLE `kelas` DISABLE KEYS */;
INSERT INTO `kelas` VALUES (29,'Kelas DKP','2026-10-10 06:02:05'),(31,'Guru','2026-10-17 12:46:55');
/*!40000 ALTER TABLE `kelas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `peminjaman`
--

DROP TABLE IF EXISTS `peminjaman`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `peminjaman` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_peminjaman_buku` (`buku_id`),
  KEY `fk_peminjaman_user` (`user_id`),
  CONSTRAINT `fk_peminjaman_buku` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_peminjaman_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `peminjaman`
--

LOCK TABLES `peminjaman` WRITE;
/*!40000 ALTER TABLE `peminjaman` DISABLE KEYS */;
INSERT INTO `peminjaman` VALUES (40,61,54,'kixnart_aruu','2026-10-13 10:33:04','2026-10-20 23:59:59','2026-10-13 11:11:27',0,0,'tidak_ada','dikembalikan','2026-10-13 03:33:04'),(41,53,55,'marin','2026-10-13 10:40:18','2026-10-20 23:59:59',NULL,0,0,'tidak_ada','dipinjam','2026-10-13 03:40:18');
/*!40000 ALTER TABLE `peminjaman` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pengajuan_peminjaman`
--

DROP TABLE IF EXISTS `pengajuan_peminjaman`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengajuan_peminjaman` (
  `id` int NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `buku_id` (`buku_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengajuan_peminjaman`
--

LOCK TABLES `pengajuan_peminjaman` WRITE;
/*!40000 ALTER TABLE `pengajuan_peminjaman` DISABLE KEYS */;
INSERT INTO `pengajuan_peminjaman` VALUES (22,55,53,'marin','uploads/kartu_pengajuan/kartu_55_1791858868_ea7ab75f.png',1,'2026-10-20','sekarang','Diambil langsung di perpustakaan hari ini','disetujui',NULL,'2026-10-13 10:40:18','2026-10-13 02:34:28'),(23,54,48,'kixnart_aruu','uploads/kartu_pengajuan/kartu_54_1791864671_360ad1ae.png',1,'2026-10-20','nanti','Tgl: 14/10/2026 · Istirahat ke-2 (12:00 - 12:45 WIB)','menunggu',NULL,NULL,'2026-10-13 04:11:11'),(24,54,50,'kixnart_aruu','uploads/kartu_pengajuan/kartu_54_1791878306_98d3fc7e.png',1,'2026-10-20','nanti','Tgl: 14/10/2026 · Istirahat ke-2 (12:00 - 12:45 WIB)','menunggu',NULL,NULL,'2026-10-13 07:58:26'),(25,56,47,'siti romsidah','uploads/kartu_pengajuan/kartu_56_1792026952_2b6b3870.png',1,'2026-10-22','sekarang','Diambil langsung di perpustakaan hari ini','menunggu',NULL,NULL,'2026-10-15 01:15:52');
/*!40000 ALTER TABLE `pengajuan_peminjaman` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pengaturan`
--

DROP TABLE IF EXISTS `pengaturan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengaturan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `kunci` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nama pengaturan',
  `nilai` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'Nilai pengaturan',
  `keterangan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kunci` (`kunci`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengaturan`
--

LOCK TABLES `pengaturan` WRITE;
/*!40000 ALTER TABLE `pengaturan` DISABLE KEYS */;
INSERT INTO `pengaturan` VALUES (1,'denda_per_hari','5000','Tarif denda per hari keterlambatan (Rupiah)','2026-08-22 13:28:40'),(2,'denda_aktif','0','Aktifkan fitur denda: 1=ya, 0=tidak','2026-08-22 13:28:40'),(3,'denda_grace_period','0','Toleransi hari sebelum denda mulai dihitung (0 = langsung denda di hari pertama)','2026-08-22 13:28:40'),(16,'musik_file','uploads/musik/musik_6ad36e63aa65a.mp3','','2026-10-17 12:47:31'),(17,'musik_judul','OST - New Eridu','','2026-08-30 07:11:46'),(18,'musik_aktif','1','','2026-09-27 00:54:27'),(19,'banner_mode','dinamis','','2026-08-30 08:33:20'),(21,'banner_background_id','4','','2026-10-06 10:41:57'),(22,'lokasi_sekolah','SMK Negeri 1 Rongga','Nama sekolah / perpustakaan','2026-09-13 11:15:19'),(23,'lokasi_alamat','Jl. Situ Gede / Jl. Raya Rongga (RT 01/RW 04), Desa Cibedug, Kec. Rongga, Kab. Bandung Barat, Jawa Barat 40565','Alamat lengkap perpustakaan','2026-09-13 11:15:19'),(24,'lokasi_jam','Senin – Jumat, 07.00 – 15.00 WIB','Jam operasional perpustakaan','2026-09-13 11:15:19'),(25,'lokasi_telepon','083829165209','Nomor telepon / WhatsApp perpustakaan','2026-09-13 11:15:19'),(26,'lokasi_map_query','SMK Negeri 1 Rongga, Bandung Barat','Query pencarian Google Maps','2026-09-13 11:15:19');
/*!40000 ALTER TABLE `pengaturan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reminder_log`
--

DROP TABLE IF EXISTS `reminder_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reminder_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `peminjaman_id` int NOT NULL,
  `tanggal` date NOT NULL COMMENT 'Tanggal deteksi/pengingat (1 baris per peminjaman per hari)',
  `terlambat_hari` int NOT NULL DEFAULT '0',
  `wa_terkirim` tinyint(1) NOT NULL DEFAULT '0',
  `wa_terkirim_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pinjam_tanggal` (`peminjaman_id`,`tanggal`),
  KEY `idx_peminjaman` (`peminjaman_id`),
  CONSTRAINT `fk_reminder_peminjaman` FOREIGN KEY (`peminjaman_id`) REFERENCES `peminjaman` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reminder_log`
--

LOCK TABLES `reminder_log` WRITE;
/*!40000 ALTER TABLE `reminder_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `reminder_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `nik` varchar(30) NOT NULL DEFAULT '',
  `kelas` varchar(50) NOT NULL DEFAULT '',
  `no_hp` varchar(20) NOT NULL DEFAULT '',
  `no_anggota` varchar(30) NOT NULL DEFAULT '',
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `plain_password` varchar(255) NOT NULL DEFAULT '',
  `foto` varchar(255) NOT NULL DEFAULT '',
  `role` enum('admin','member') DEFAULT 'member',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `card_status` enum('active','frozen') NOT NULL DEFAULT 'active' COMMENT 'Dibekukan sementara saat proses Lupa Kartu berlangsung',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `uq_no_anggota` (`no_anggota`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Administrator','','','','AN-LAMA-00001','admin','admin@aksanova.com','$2y$10$fG5Q3mZaf8XRV5K/s2S7yOULagsXHbA5rZP43ubCi9.Xq9yNerW9y','','','admin','approved','active','2026-05-20 04:39:17'),(54,'kixnart_saya stuju','242510203','Kelas DKP','083829165208','AN-2026-12609','kixnart','kixnart@student.smkn1rongga.sch.id','$2y$10$gHLNgDwPakUDasD.PsiVfeoYBmWyLg0owCuTvtsBn9ybzXqdCWjRW','123456','uploads/anggota/anggota_7776ebdda8ae0fd3.webp','member','approved','active','2026-10-10 06:04:34'),(55,'marin','202555','Kelas DKP','083829165209','AN-2026-69923','marin','marin@student.smkn1rongga.sch.id','$2y$10$ik3sge9JdBpq93UzxIDXCeDgdoo6stnK07Zb59ljn22udHw5vmMi2','a1a2a3a4a5a6a7a8','uploads/anggota/anggota_1282fb3cb1030587.jpg','member','rejected','active','2026-10-13 01:40:50'),(56,'siti romsidah','123456789','Kelas DKP','083155990335','AN-2026-14598','sitiromsidah','sitiromsidah@student.smkn1rongga.sch.id','$2y$10$YnUzuCQKvyiLcAHslaf0Gegmu2cX0ZqarAH03.tOHNAXNdhgKa702','siro020908','uploads/anggota/anggota_a6b03ebe50b0bf6b.jpg','member','approved','active','2026-10-15 01:13:24'),(58,'bimbim','2425100208','Kelas DKP','083829165202','AN-2026-59604','bimbim','aruru@student.smkn1rongga.sch.id','$2y$10$BoZx7Z2xllATwKFirNdrleUORGVWzshSQZfo2/qBTysdEEBLZ.Hri','1234567a','uploads/anggota/anggota_a4a04b0b38a72ee8.jpg','member','approved','active','2026-10-15 02:23:44'),(59,'Bimansyah Syahrulalla','2425100211','Guru','083829165201','AN-2026-07527','bimansyahsyahrulalla','syahrulallabimansyah@gmail.com','$2y$10$MzrgSi/q2vc.6V9gXw2DBeXEHsAtYtXyQCgxs/JuamM0xl15bmCYe','12345678a','uploads/anggota/anggota_80b16e91f5ef1f4e.jpg','member','approved','active','2026-10-17 13:22:43');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-19 10:45:34
