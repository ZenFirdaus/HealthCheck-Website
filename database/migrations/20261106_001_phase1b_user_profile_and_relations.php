<?php
/**
 * Migration 20261106_001: Phase 1B User Profile Foundation & Health Records Relations
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
        // 1. Add email column to user table if not present
        if (!$this->columnExists($pdo, 'user', 'email')) {
            $pdo->exec("ALTER TABLE user ADD COLUMN email VARCHAR(150)");
            $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_user_email ON user(email) WHERE email IS NOT NULL");
        }

        // 2. Create user_profile table (1-to-1 with user, cascade on delete)
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_profile (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL UNIQUE,
            nama_lengkap VARCHAR(150),
            tanggal_lahir DATE,
            jenis_kelamin VARCHAR(10),
            tinggi_badan REAL,
            berat_badan REAL,
            no_telepon VARCHAR(30),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
        )");

        // 3. Add user_id to cek_bmi with foreign key and index
        if (!$this->columnExists($pdo, 'cek_bmi', 'user_id')) {
            $pdo->exec("ALTER TABLE cek_bmi ADD COLUMN user_id INTEGER REFERENCES user(id) ON DELETE SET NULL");
        }
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_bmi_user_created ON cek_bmi(user_id, created_at DESC)");

        // 4. Add user_id to cek_bmr with foreign key and index
        if (!$this->columnExists($pdo, 'cek_bmr', 'user_id')) {
            $pdo->exec("ALTER TABLE cek_bmr ADD COLUMN user_id INTEGER REFERENCES user(id) ON DELETE SET NULL");
        }
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_bmr_user_created ON cek_bmr(user_id, created_at DESC)");

        // 5. Add user_id to cek_jantung with foreign key and index
        if (!$this->columnExists($pdo, 'cek_jantung', 'user_id')) {
            $pdo->exec("ALTER TABLE cek_jantung ADD COLUMN user_id INTEGER REFERENCES user(id) ON DELETE SET NULL");
        }
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_jantung_user_created ON cek_jantung(user_id, created_at DESC)");

        // 6. Add user_id to cek_mental with foreign key and index
        if (!$this->columnExists($pdo, 'cek_mental', 'user_id')) {
            $pdo->exec("ALTER TABLE cek_mental ADD COLUMN user_id INTEGER REFERENCES user(id) ON DELETE SET NULL");
        }
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_mental_user_created ON cek_mental(user_id, created_at DESC)");
    }

    public function down(PDO $pdo): void {
        // Rollback operations (in reverse order)
        $pdo->exec("DROP INDEX IF EXISTS idx_mental_user_created");
        if ($this->columnExists($pdo, 'cek_mental', 'user_id')) {
            $pdo->exec("ALTER TABLE cek_mental DROP COLUMN user_id");
        }

        $pdo->exec("DROP INDEX IF EXISTS idx_jantung_user_created");
        if ($this->columnExists($pdo, 'cek_jantung', 'user_id')) {
            $pdo->exec("ALTER TABLE cek_jantung DROP COLUMN user_id");
        }

        $pdo->exec("DROP INDEX IF EXISTS idx_bmr_user_created");
        if ($this->columnExists($pdo, 'cek_bmr', 'user_id')) {
            $pdo->exec("ALTER TABLE cek_bmr DROP COLUMN user_id");
        }

        $pdo->exec("DROP INDEX IF EXISTS idx_bmi_user_created");
        if ($this->columnExists($pdo, 'cek_bmi', 'user_id')) {
            $pdo->exec("ALTER TABLE cek_bmi DROP COLUMN user_id");
        }

        $pdo->exec("DROP TABLE IF EXISTS user_profile");

        $pdo->exec("DROP INDEX IF EXISTS idx_user_email");
        if ($this->columnExists($pdo, 'user', 'email')) {
            $pdo->exec("ALTER TABLE user DROP COLUMN email");
        }
    }
};
