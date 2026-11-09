<?php
/**
 * Migration 20261108_004: Phase 5 Health Goals, Progress Logs, Milestones & Goal Activities
 */

return new class {
    public function up(PDO $pdo): void {
        // 1. Table health_goals
        $pdo->exec("CREATE TABLE IF NOT EXISTS health_goals (
            id_goal INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title VARCHAR(150) NOT NULL,
            description TEXT,
            category VARCHAR(50) NOT NULL,
            goal_type VARCHAR(50) NOT NULL,
            initial_value REAL NOT NULL,
            target_value REAL NOT NULL,
            current_value REAL NOT NULL,
            unit VARCHAR(50) NOT NULL,
            direction VARCHAR(20) DEFAULT 'increase',
            start_date DATE NOT NULL,
            target_date DATE NOT NULL,
            status VARCHAR(20) DEFAULT 'active',
            is_featured INTEGER DEFAULT 0,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME DEFAULT NULL,
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_goals_user_status ON health_goals(user_id, status)");

        // 2. Table goal_progress_logs
        $pdo->exec("CREATE TABLE IF NOT EXISTS goal_progress_logs (
            id_progress INTEGER PRIMARY KEY AUTOINCREMENT,
            goal_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            current_value REAL NOT NULL,
            progress_percentage REAL NOT NULL,
            source_type VARCHAR(50) DEFAULT 'manual',
            source_record_id VARCHAR(100) DEFAULT NULL,
            notes TEXT,
            recorded_at DATE NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (goal_id) REFERENCES health_goals(id_goal) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_goal_prog_goal ON goal_progress_logs(goal_id, recorded_at)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_goal_prog_user ON goal_progress_logs(user_id, recorded_at)");

        // 3. Table goal_milestones
        $pdo->exec("CREATE TABLE IF NOT EXISTS goal_milestones (
            id_milestone INTEGER PRIMARY KEY AUTOINCREMENT,
            goal_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            title VARCHAR(150) NOT NULL,
            description TEXT,
            target_value REAL DEFAULT NULL,
            unit VARCHAR(50) DEFAULT NULL,
            sequence_order INTEGER DEFAULT 1,
            target_date DATE DEFAULT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            completed_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (goal_id) REFERENCES health_goals(id_goal) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_milestones_goal ON goal_milestones(goal_id, sequence_order)");

        // 4. Table goal_activity_logs
        $pdo->exec("CREATE TABLE IF NOT EXISTS goal_activity_logs (
            id_activity INTEGER PRIMARY KEY AUTOINCREMENT,
            goal_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            activity_type VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (goal_id) REFERENCES health_goals(id_goal) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_goal_act_user ON goal_activity_logs(user_id, created_at)");
    }

    public function down(PDO $pdo): void {
        $pdo->exec("DROP INDEX IF EXISTS idx_goal_act_user");
        $pdo->exec("DROP TABLE IF EXISTS goal_activity_logs");
        $pdo->exec("DROP INDEX IF EXISTS idx_milestones_goal");
        $pdo->exec("DROP TABLE IF EXISTS goal_milestones");
        $pdo->exec("DROP INDEX IF EXISTS idx_goal_prog_user");
        $pdo->exec("DROP INDEX IF EXISTS idx_goal_prog_goal");
        $pdo->exec("DROP TABLE IF EXISTS goal_progress_logs");
        $pdo->exec("DROP INDEX IF EXISTS idx_goals_user_status");
        $pdo->exec("DROP TABLE IF EXISTS health_goals");
    }
};
