-- ============================================================
--  MIGRASI: Fitur Denda Keterlambatan Pengembalian Buku
--  Jalankan query ini di phpMyAdmin atau MySQL CLI
--  Database: aksa_nova
-- ============================================================

-- 1. Tambah kolom denda ke tabel peminjaman
ALTER TABLE `peminjaman`
  ADD COLUMN `terlambat_hari` INT NOT NULL DEFAULT 0 COMMENT 'Jumlah hari keterlambatan' AFTER `waktu_kembali`,
  ADD COLUMN `denda` DECIMAL(12,0) NOT NULL DEFAULT 0 COMMENT 'Total denda dalam rupiah' AFTER `terlambat_hari`,
  ADD COLUMN `status_denda` ENUM('tidak_ada','belum_bayar','lunas') NOT NULL DEFAULT 'tidak_ada' COMMENT 'Status pembayaran denda' AFTER `denda`;

-- 2. Buat tabel pengaturan untuk menyimpan tarif denda
CREATE TABLE IF NOT EXISTS `pengaturan` (
  `id`        INT NOT NULL AUTO_INCREMENT,
  `kunci`     VARCHAR(100) NOT NULL COMMENT 'Nama pengaturan',
  `nilai`     VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'Nilai pengaturan',
  `keterangan` VARCHAR(255) NOT NULL DEFAULT '',
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kunci` (`kunci`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Isi nilai default pengaturan denda
INSERT INTO `pengaturan` (`kunci`, `nilai`, `keterangan`) VALUES
('denda_per_hari', '1000', 'Tarif denda per hari keterlambatan (Rupiah)'),
('denda_aktif',    '1',    'Aktifkan fitur denda: 1=ya, 0=tidak'),
('denda_grace_period', '0', 'Toleransi hari sebelum denda mulai dihitung (0 = langsung denda di hari pertama)')
ON DUPLICATE KEY UPDATE `nilai` = VALUES(`nilai`);
