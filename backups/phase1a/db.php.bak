<?php
/**
 * Database Handler with dual-mode support:
 * 1. MySQL (as configured in gizi database)
 * 2. SQLite (automatic fallback when MySQL server is unavailable)
 * Includes prepared-statement safe helpers and auto-schema initialization.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Database {
    private static ?PDO $instance = null;
    private static string $driver = 'sqlite';

    public static function getConnection(): PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $mysqlHost = '127.0.0.1';
        $mysqlUser = 'root';
        $mysqlPass = '';
        $mysqlDb   = 'gizi';

        // Attempt MySQL connection first
        try {
            $dsn = "mysql:host={$mysqlHost};dbname={$mysqlDb};charset=utf8mb4";
            $pdo = new PDO($dsn, $mysqlUser, $mysqlPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 1,
            ]);
            self::$instance = $pdo;
            self::$driver = 'mysql';
        } catch (\Throwable $e) {
            // Fallback to SQLite database file
            $sqlitePath = __DIR__ . '/../database/healthcheck.sqlite';
            $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            self::$instance = $pdo;
            self::$driver = 'sqlite';
        }

        self::initializeSchema(self::$instance, self::$driver);
        return self::$instance;
    }

    public static function getDriver(): string {
        return self::$driver;
    }

    private static function initializeSchema(PDO $db, string $driver): void {
        $autoInc = ($driver === 'mysql') ? 'AUTO_INCREMENT' : 'AUTOINCREMENT';
        $primaryKey = ($driver === 'sqlite') ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';

        // Table user
        $db->exec("CREATE TABLE IF NOT EXISTS user (
            id {$primaryKey},
            username VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT 'user',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Table cek_bmi
        $db->exec("CREATE TABLE IF NOT EXISTS cek_bmi (
            id_bmi {$primaryKey},
            nama_user VARCHAR(150) NOT NULL,
            umur INT NOT NULL,
            berat_badan REAL NOT NULL,
            tinggi_badan REAL NOT NULL,
            bmi REAL DEFAULT 0,
            kondisi VARCHAR(100) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Table cek_bmr
        $db->exec("CREATE TABLE IF NOT EXISTS cek_bmr (
            id_bmr {$primaryKey},
            nama_user VARCHAR(150) NOT NULL,
            jenis_kelamin VARCHAR(10) NOT NULL,
            umur INT NOT NULL,
            berat_badan REAL NOT NULL,
            tinggi_badan REAL NOT NULL,
            bmr REAL NOT NULL,
            aktivitas VARCHAR(100) DEFAULT 'Sedentary',
            tdee REAL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Table cek_jantung (PRD 3.5.C)
        $db->exec("CREATE TABLE IF NOT EXISTS cek_jantung (
            id_jantung {$primaryKey},
            nama_user VARCHAR(150) NOT NULL,
            umur INT NOT NULL,
            resting_hr INT NOT NULL,
            max_hr INT NOT NULL,
            kategori VARCHAR(100) NOT NULL,
            catatan TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Table cek_mental (PRD 3.5.D - PHQ-4 Screening)
        $db->exec("CREATE TABLE IF NOT EXISTS cek_mental (
            id_mental {$primaryKey},
            nama_user VARCHAR(150) NOT NULL,
            skor_cemas INT NOT NULL,
            skor_depresi INT NOT NULL,
            total_skor INT NOT NULL,
            tingkat VARCHAR(100) NOT NULL,
            anjuran TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Seed default admin and user if not exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM user WHERE username = ?");
        $stmt->execute(['admin']);
        if ($stmt->fetchColumn() == 0) {
            $stmtInsert = $db->prepare("INSERT INTO user (username, password, role) VALUES (?, ?, ?)");
            $stmtInsert->execute(['admin', 'admin123', 'admin']);
        }

        $stmt->execute(['user']);
        if ($stmt->fetchColumn() == 0) {
            $stmtInsert = $db->prepare("INSERT INTO user (username, password, role) VALUES (?, ?, ?)");
            $stmtInsert->execute(['user', 'user123', 'user']);
        }
    }
}

// Global instance helper
$pdo = Database::getConnection();
