<?php
/**
 * Migration 20261108_003: Phase 4 Habits Tracker, Daily Check-in, and Reminders
 */

return new class {
    public function up(PDO $pdo): void {
        // 1. Table health_habits
        $pdo->exec("CREATE TABLE IF NOT EXISTS health_habits (
            id_habit INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            code VARCHAR(50) NOT NULL,
            nama_habit VARCHAR(150) NOT NULL,
            deskripsi VARCHAR(255),
            ikon VARCHAR(50) DEFAULT 'bi-check2-circle',
            warna VARCHAR(30) DEFAULT 'blue',
            satuan VARCHAR(50) NOT NULL,
            target_harian REAL NOT NULL,
            is_active INTEGER DEFAULT 1,
            is_custom INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_habits_user ON health_habits(user_id, is_active)");

        // 2. Table habit_logs
        $pdo->exec("CREATE TABLE IF NOT EXISTS habit_logs (
            id_log INTEGER PRIMARY KEY AUTOINCREMENT,
            habit_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            tanggal DATE NOT NULL,
            nilai_aktual REAL NOT NULL DEFAULT 0,
            is_target_tercapai INTEGER DEFAULT 0,
            catatan TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (habit_id) REFERENCES health_habits(id_habit) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
            UNIQUE(habit_id, tanggal)
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_habit_logs_date ON habit_logs(user_id, tanggal)");

        // 3. Table daily_checkins
        $pdo->exec("CREATE TABLE IF NOT EXISTS daily_checkins (
            id_checkin INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            tanggal DATE NOT NULL,
            water_ml REAL DEFAULT NULL,
            sleep_hours REAL DEFAULT NULL,
            steps_count INT DEFAULT NULL,
            activity_type VARCHAR(100) DEFAULT NULL,
            activity_minutes INT DEFAULT NULL,
            kondisi_energi VARCHAR(50) DEFAULT NULL,
            catatan TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
            UNIQUE(user_id, tanggal)
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_checkin_user_date ON daily_checkins(user_id, tanggal)");

        // 4. Table health_reminders
        $pdo->exec("CREATE TABLE IF NOT EXISTS health_reminders (
            id_reminder INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            tipe_reminder VARCHAR(50) NOT NULL,
            judul VARCHAR(150) NOT NULL,
            pesan TEXT NOT NULL,
            jam_reminder VARCHAR(10) NOT NULL,
            hari_aktif VARCHAR(50) DEFAULT '1,2,3,4,5,6,7',
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_reminders_user ON health_reminders(user_id, is_active)");

        // 5. Table notification_logs
        $pdo->exec("CREATE TABLE IF NOT EXISTS notification_logs (
            id_notif INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            reminder_id INTEGER DEFAULT NULL,
            tipe_notif VARCHAR(50) NOT NULL DEFAULT 'in_app',
            judul VARCHAR(150) NOT NULL,
            pesan TEXT NOT NULL,
            tanggal_kirim DATE NOT NULL,
            sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(20) DEFAULT 'unread',
            read_at DATETIME DEFAULT NULL,
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
            FOREIGN KEY (reminder_id) REFERENCES health_reminders(id_reminder) ON DELETE SET NULL,
            UNIQUE(user_id, reminder_id, tanggal_kirim)
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_notif_user_status ON notification_logs(user_id, status)");
    }

    public function down(PDO $pdo): void {
        $pdo->exec("DROP INDEX IF EXISTS idx_notif_user_status");
        $pdo->exec("DROP TABLE IF EXISTS notification_logs");
        $pdo->exec("DROP INDEX IF EXISTS idx_reminders_user");
        $pdo->exec("DROP TABLE IF EXISTS health_reminders");
        $pdo->exec("DROP INDEX IF EXISTS idx_checkin_user_date");
        $pdo->exec("DROP TABLE IF EXISTS daily_checkins");
        $pdo->exec("DROP INDEX IF EXISTS idx_habit_logs_date");
        $pdo->exec("DROP TABLE IF EXISTS habit_logs");
        $pdo->exec("DROP INDEX IF EXISTS idx_habits_user");
        $pdo->exec("DROP TABLE IF EXISTS health_habits");
    }
};
