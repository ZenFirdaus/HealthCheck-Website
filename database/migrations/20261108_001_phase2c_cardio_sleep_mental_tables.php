<?php
/**
 * Migration 20261108_001: Phase 2C Cardio, Sleep & Mental Health Tables
 * Tables: cek_tekanan_darah, cek_kualitas_tidur, cek_zona_detak_jantung, cek_aktivitas_fisik, cek_stres
 */

return new class {
    public function up(PDO $pdo): void {
        // 1. Table cek_tekanan_darah (Blood Pressure Assessment)
        $pdo->exec("CREATE TABLE IF NOT EXISTS cek_tekanan_darah (
            id_bp INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            nama_user VARCHAR(150) NOT NULL,
            umur INT NOT NULL,
            sistolik INT NOT NULL,
            diastolik INT NOT NULL,
            detak_nadi INT,
            kategori VARCHAR(100) NOT NULL,
            saran TEXT,
            catatan TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_bp_user_created ON cek_tekanan_darah(user_id, created_at DESC)");

        // 2. Table cek_kualitas_tidur (Sleep Habit Assessment)
        $pdo->exec("CREATE TABLE IF NOT EXISTS cek_kualitas_tidur (
            id_tidur INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            nama_user VARCHAR(150) NOT NULL,
            durasi_jam REAL NOT NULL,
            latensi_menit INT NOT NULL,
            frekuensi_terbangun INT NOT NULL,
            penggunaan_gawai VARCHAR(20) DEFAULT 'tidak',
            kesegaran_bangun VARCHAR(50) NOT NULL,
            efisiensi_tidur REAL NOT NULL,
            skor_kebiasaan INT NOT NULL,
            kategori_tidur VARCHAR(100) NOT NULL,
            saran TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_tidur_user_created ON cek_kualitas_tidur(user_id, created_at DESC)");

        // 3. Table cek_zona_detak_jantung (Heart Rate Training Zones - Karvonen & Zoladz)
        $pdo->exec("CREATE TABLE IF NOT EXISTS cek_zona_detak_jantung (
            id_zona INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            nama_user VARCHAR(150) NOT NULL,
            umur INT NOT NULL,
            resting_hr INT NOT NULL,
            max_hr INT NOT NULL,
            hrr INT NOT NULL,
            metode VARCHAR(50) DEFAULT 'karvonen',
            zona_1_min INT NOT NULL,
            zona_1_max INT NOT NULL,
            zona_2_min INT NOT NULL,
            zona_2_max INT NOT NULL,
            zona_3_min INT NOT NULL,
            zona_3_max INT NOT NULL,
            zona_4_min INT NOT NULL,
            zona_4_max INT NOT NULL,
            zona_5_min INT NOT NULL,
            zona_5_max INT NOT NULL,
            saran TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_zona_hr_user_created ON cek_zona_detak_jantung(user_id, created_at DESC)");

        // 4. Table cek_aktivitas_fisik (Physical Activity Assessment - IPAQ Short Form)
        $pdo->exec("CREATE TABLE IF NOT EXISTS cek_aktivitas_fisik (
            id_aktivitas INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            nama_user VARCHAR(150) NOT NULL,
            hari_berat INT DEFAULT 0,
            menit_berat INT DEFAULT 0,
            hari_sedang INT DEFAULT 0,
            menit_sedang INT DEFAULT 0,
            hari_jalan INT DEFAULT 0,
            menit_jalan INT DEFAULT 0,
            jam_duduk REAL DEFAULT 0,
            total_met_menit REAL NOT NULL,
            kategori_aktivitas VARCHAR(50) NOT NULL,
            saran TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_aktivitas_user_created ON cek_aktivitas_fisik(user_id, created_at DESC)");

        // 5. Table cek_stres (Perceived Stress Assessment - PSS-10)
        $pdo->exec("CREATE TABLE IF NOT EXISTS cek_stres (
            id_stres INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            nama_user VARCHAR(150) NOT NULL,
            skor_stres INT NOT NULL,
            tingkat_stres VARCHAR(100) NOT NULL,
            saran TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_stres_user_created ON cek_stres(user_id, created_at DESC)");
    }

    public function down(PDO $pdo): void {
        $pdo->exec("DROP INDEX IF EXISTS idx_stres_user_created");
        $pdo->exec("DROP TABLE IF EXISTS cek_stres");

        $pdo->exec("DROP INDEX IF EXISTS idx_aktivitas_user_created");
        $pdo->exec("DROP TABLE IF EXISTS cek_aktivitas_fisik");

        $pdo->exec("DROP INDEX IF EXISTS idx_zona_hr_user_created");
        $pdo->exec("DROP TABLE IF EXISTS cek_zona_detak_jantung");

        $pdo->exec("DROP INDEX IF EXISTS idx_tidur_user_created");
        $pdo->exec("DROP TABLE IF EXISTS cek_kualitas_tidur");

        $pdo->exec("DROP INDEX IF EXISTS idx_bp_user_created");
        $pdo->exec("DROP TABLE IF EXISTS cek_tekanan_darah");
    }
};
