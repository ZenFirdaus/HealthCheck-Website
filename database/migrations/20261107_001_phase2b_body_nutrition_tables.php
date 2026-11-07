<?php
/**
 * Migration 20261107_001: Phase 2B Body & Nutrition Tables (WHtR, Body Fat, Fluid Needs, BMR Macro Extension)
 */

return new class {
    private function columnExists(PDO $pdo, string $table, string $column): bool {
        $stmt = $pdo->query("PRAGMA table_info({$table})");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (strcasecmp($row['name'], $column) === 0) {
                return true;
            }
        }
        return false;
    }

    public function up(PDO $pdo): void {
        // 1. Table cek_whtr (Waist-to-Height Ratio)
        $pdo->exec("CREATE TABLE IF NOT EXISTS cek_whtr (
            id_whtr INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            nama_user VARCHAR(150) NOT NULL,
            jenis_kelamin VARCHAR(10) NOT NULL,
            lingkar_pinggang REAL NOT NULL,
            tinggi_badan REAL NOT NULL,
            rasio_whtr REAL NOT NULL,
            kategori_risiko VARCHAR(100) NOT NULL,
            saran TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_whtr_user_created ON cek_whtr(user_id, created_at DESC)");

        // 2. Table cek_lemak_tubuh (US Navy Body Fat Percentage)
        $pdo->exec("CREATE TABLE IF NOT EXISTS cek_lemak_tubuh (
            id_fat INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            nama_user VARCHAR(150) NOT NULL,
            jenis_kelamin VARCHAR(10) NOT NULL,
            umur INT NOT NULL,
            tinggi_badan REAL NOT NULL,
            berat_badan REAL NOT NULL,
            lingkar_leher REAL NOT NULL,
            lingkar_pinggang REAL NOT NULL,
            lingkar_pinggul REAL,
            body_fat_percent REAL NOT NULL,
            fat_mass_kg REAL,
            lean_mass_kg REAL,
            kategori VARCHAR(100) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_fat_user_created ON cek_lemak_tubuh(user_id, created_at DESC)");

        // 3. Table cek_kebutuhan_cairan (Daily Fluid Needs)
        $pdo->exec("CREATE TABLE IF NOT EXISTS cek_kebutuhan_cairan (
            id_cairan INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            nama_user VARCHAR(150) NOT NULL,
            berat_badan REAL NOT NULL,
            tingkat_aktivitas VARCHAR(50) DEFAULT 'sedang',
            kondisi_iklim VARCHAR(50) DEFAULT 'sedang',
            kebutuhan_ml REAL NOT NULL,
            kebutuhan_liter REAL NOT NULL,
            kebutuhan_gelas INT NOT NULL,
            saran TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_cairan_user_created ON cek_kebutuhan_cairan(user_id, created_at DESC)");

        // 4. Upgrade cek_bmr table with macro columns if not present
        if (!$this->columnExists($pdo, 'cek_bmr', 'target_tujuan')) {
            $pdo->exec("ALTER TABLE cek_bmr ADD COLUMN target_tujuan VARCHAR(50) DEFAULT 'maintain'");
        }
        if (!$this->columnExists($pdo, 'cek_bmr', 'target_kalori')) {
            $pdo->exec("ALTER TABLE cek_bmr ADD COLUMN target_kalori REAL DEFAULT 0");
        }
        if (!$this->columnExists($pdo, 'cek_bmr', 'protein_gram')) {
            $pdo->exec("ALTER TABLE cek_bmr ADD COLUMN protein_gram REAL DEFAULT 0");
        }
        if (!$this->columnExists($pdo, 'cek_bmr', 'karbo_gram')) {
            $pdo->exec("ALTER TABLE cek_bmr ADD COLUMN karbo_gram REAL DEFAULT 0");
        }
        if (!$this->columnExists($pdo, 'cek_bmr', 'lemak_gram')) {
            $pdo->exec("ALTER TABLE cek_bmr ADD COLUMN lemak_gram REAL DEFAULT 0");
        }
    }

    public function down(PDO $pdo): void {
        // Rollback operations
        $pdo->exec("DROP INDEX IF EXISTS idx_cairan_user_created");
        $pdo->exec("DROP TABLE IF EXISTS cek_kebutuhan_cairan");

        $pdo->exec("DROP INDEX IF EXISTS idx_fat_user_created");
        $pdo->exec("DROP TABLE IF EXISTS cek_lemak_tubuh");

        $pdo->exec("DROP INDEX IF EXISTS idx_whtr_user_created");
        $pdo->exec("DROP TABLE IF EXISTS cek_whtr");

        if ($this->columnExists($pdo, 'cek_bmr', 'lemak_gram')) {
            $pdo->exec("ALTER TABLE cek_bmr DROP COLUMN lemak_gram");
        }
        if ($this->columnExists($pdo, 'cek_bmr', 'karbo_gram')) {
            $pdo->exec("ALTER TABLE cek_bmr DROP COLUMN karbo_gram");
        }
        if ($this->columnExists($pdo, 'cek_bmr', 'protein_gram')) {
            $pdo->exec("ALTER TABLE cek_bmr DROP COLUMN protein_gram");
        }
        if ($this->columnExists($pdo, 'cek_bmr', 'target_kalori')) {
            $pdo->exec("ALTER TABLE cek_bmr DROP COLUMN target_kalori");
        }
        if ($this->columnExists($pdo, 'cek_bmr', 'target_tujuan')) {
            $pdo->exec("ALTER TABLE cek_bmr DROP COLUMN target_tujuan");
        }
    }
};
