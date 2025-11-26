-- Database schema for SISPER (fixed + tambahan komoditas_master & relasi)
CREATE DATABASE IF NOT EXISTS sisperlenteng CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE sisperlenteng;

CREATE TABLE IF NOT EXISTS desa (
  id_desa INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_kelompok VARCHAR(200),
  username VARCHAR(100) UNIQUE,
  password_hash VARCHAR(255),
  id_desa INT NULL,
  role ENUM('admin','kelompok') DEFAULT 'kelompok',
  kontak VARCHAR(50),
  status ENUM('aktif','nonaktif') DEFAULT 'aktif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_desa) REFERENCES desa(id_desa) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS petani (
  id_petani INT AUTO_INCREMENT PRIMARY KEY,
  id_kelompok INT NULL,
  nama_petani VARCHAR(150) NOT NULL,
  no_telepon VARCHAR(50),
  alamat TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_kelompok) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- komoditas (lama) — tetap untuk kompatibilitas sementara
CREATE TABLE IF NOT EXISTS komoditas (
  id_komoditas INT AUTO_INCREMENT PRIMARY KEY,
  nama_komoditas VARCHAR(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- jenis_tanaman diperbaiki
CREATE TABLE IF NOT EXISTS jenis_tanaman (
  id_jenis INT AUTO_INCREMENT PRIMARY KEY,
  jenis_tanaman VARCHAR(150),
  id_komoditas INT NULL,
  id_petani INT NULL,
  id_kelompok INT NULL,
  id_desa INT NULL,
  deskripsi TEXT,
  foto VARCHAR(255),
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  alasan_tolak TEXT,
  tanggal_input TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_komoditas) REFERENCES komoditas(id_komoditas) ON DELETE SET NULL,
  FOREIGN KEY (id_petani) REFERENCES petani(id_petani) ON DELETE SET NULL,
  FOREIGN KEY (id_kelompok) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (id_desa) REFERENCES desa(id_desa) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS harga_komoditas (
  id_harga INT AUTO_INCREMENT PRIMARY KEY,
  id_komoditas INT NOT NULL,
  bulan TINYINT NOT NULL,
  tahun SMALLINT NOT NULL,
  harga DECIMAL(12,2) NOT NULL,
  id_admin INT NULL,
  tanggal_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  komoditas_master_id INT NULL,
  FOREIGN KEY (id_komoditas) REFERENCES komoditas(id_komoditas),
  FOREIGN KEY (id_admin) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS history_aktivitas (
  id_history INT AUTO_INCREMENT PRIMARY KEY,
  id_kelompok INT NULL,
  id_komoditas INT NULL,
  id_petani INT NULL,
  aktivitas VARCHAR(150) NOT NULL,
  detail TEXT,
  status VARCHAR(50),
  waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_kelompok) REFERENCES users(id),
  FOREIGN KEY (id_komoditas) REFERENCES komoditas(id_komoditas),
  FOREIGN KEY (id_petani) REFERENCES petani(id_petani)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert a default admin (id_desa NULL allowed)
INSERT IGNORE INTO users (nama_kelompok, username, password_hash, role, kontak) VALUES
('Admin Kecamatan', 'admin', '$2y$10$u1xw5Z0EJkYpQx3y5s0lUe2Qx4Cq9H6b8VZJvN0mQeF8aYw2z1G6', 'admin', '08123456789');

-- Notifications table (simple in-app notifications)
CREATE TABLE IF NOT EXISTS notifications (
  id_notif INT AUTO_INCREMENT PRIMARY KEY,
  id_user INT,
  title VARCHAR(200),
  message TEXT,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel baru: komoditas_master dan relasi kelompok_komoditas (non-destruktif)
CREATE TABLE IF NOT EXISTS komoditas_master (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(191) NOT NULL,
  kategori VARCHAR(100) DEFAULT NULL,
  aktif TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_komoditas_nama (nama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kelompok_komoditas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kelompok_id INT NOT NULL,
  komoditas_id INT NOT NULL,
  jenis_tanaman VARCHAR(191) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_kelompok_komoditas (kelompok_id, komoditas_id),
  INDEX idx_kelompok (kelompok_id),
  INDEX idx_komoditas (komoditas_id),
  FOREIGN KEY (kelompok_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (komoditas_id) REFERENCES komoditas_master(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mapping table untuk migrasi bertahap dari komoditas (lama) ke master
CREATE TABLE IF NOT EXISTS komoditas_map (
  id INT AUTO_INCREMENT PRIMARY KEY,
  old_id INT NOT NULL,
  new_id INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_map_old (old_id),
  INDEX idx_map_new (new_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

