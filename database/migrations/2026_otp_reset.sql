-- Migrasi Penambahan Tabel password_otp untuk Fitur Pemulihan Sandi / Lupa Kartu
-- Proyek: AKSA NOVA (Perpustakaan SMKN 1 Rongga)
-- Tahun: 2026

CREATE TABLE IF NOT EXISTS `password_otp` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `otp_hash` CHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `attempts` TINYINT NOT NULL DEFAULT 0,
  `used_at` DATETIME NULL DEFAULT NULL,
  `last_sent_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `request_ip` VARCHAR(45) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_created` (`user_id`, `created_at`),
  CONSTRAINT `fk_password_otp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
