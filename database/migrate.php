<?php
/**
 * Database Migration Runner for HealthCheck
 * Usage:
 *   php database/migrate.php          (run all pending migrations)
 *   php database/migrate.php --status (view migration status)
 *   php database/migrate.php --rollback (rollback last migration)
 */

require_once __DIR__ . '/../includes/db.php';

class MigrationRunner {
    private PDO $pdo;
    private string $migrationsDir;

    public function __construct(PDO $pdo, string $migrationsDir) {
        $this->pdo = $pdo;
        $this->migrationsDir = $migrationsDir;
        $this->initMigrationsTable();
    }

    private function initMigrationsTable(): void {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration VARCHAR(255) NOT NULL UNIQUE,
            applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }

    public function getAppliedMigrations(): array {
        $stmt = $this->pdo->query("SELECT migration FROM schema_migrations ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function run(): array {
        $applied = $this->getAppliedMigrations();
        $files = glob($this->migrationsDir . '/*.php');
        sort($files);

        $executed = [];

        foreach ($files as $file) {
            $migrationName = basename($file, '.php');
            if (in_array($migrationName, $applied, true)) {
                continue;
            }

            $migration = require $file;
            if (!is_object($migration) || !method_exists($migration, 'up')) {
                throw new RuntimeException("Invalid migration object in {$file}");
            }

            echo "Migrating: {$migrationName}... ";
            $this->pdo->beginTransaction();
            try {
                $migration->up($this->pdo);
                $stmt = $this->pdo->prepare("INSERT INTO schema_migrations (migration) VALUES (?)");
                $stmt->execute([$migrationName]);
                $this->pdo->commit();
                echo "DONE\n";
                $executed[] = $migrationName;
            } catch (Throwable $e) {
                $this->pdo->rollBack();
                echo "FAILED: " . $e->getMessage() . "\n";
                throw $e;
            }
        }

        if (empty($executed)) {
            echo "Nothing to migrate. All migrations are up to date.\n";
        }

        return $executed;
    }

    public function rollback(): ?string {
        $applied = $this->getAppliedMigrations();
        if (empty($applied)) {
            echo "No migrations to rollback.\n";
            return null;
        }

        $lastMigration = end($applied);
        $file = $this->migrationsDir . '/' . $lastMigration . '.php';

        if (!file_exists($file)) {
            throw new RuntimeException("Migration file not found: {$file}");
        }

        $migration = require $file;
        if (!is_object($migration) || !method_exists($migration, 'down')) {
            throw new RuntimeException("Migration does not implement down(): {$lastMigration}");
        }

        echo "Rolling back: {$lastMigration}... ";
        $this->pdo->beginTransaction();
        try {
            $migration->down($this->pdo);
            $stmt = $this->pdo->prepare("DELETE FROM schema_migrations WHERE migration = ?");
            $stmt->execute([$lastMigration]);
            $this->pdo->commit();
            echo "DONE\n";
            return $lastMigration;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            echo "FAILED: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    public function status(): void {
        $applied = $this->getAppliedMigrations();
        $files = glob($this->migrationsDir . '/*.php');
        sort($files);

        echo "=== Migration Status ===\n";
        foreach ($files as $file) {
            $name = basename($file, '.php');
            $isApplied = in_array($name, $applied, true);
            printf("[%s] %s\n", $isApplied ? 'APPLIED' : 'PENDING', $name);
        }
    }
}

// CLI Execution handler
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    $runner = new MigrationRunner($pdo, __DIR__ . '/migrations');
    $arg = $argv[1] ?? '';

    if ($arg === '--status') {
        $runner->status();
    } elseif ($arg === '--rollback') {
        $runner->rollback();
    } else {
        $runner->run();
    }
}
