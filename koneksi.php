<?php
require_once __DIR__ . '/includes/db.php';

// If MySQL is active, provide real mysqli link if possible
$conn = null;
if (Database::getDriver() === 'mysql') {
    $conn = @mysqli_connect("127.0.0.1", "root", "", "gizi");
}

if (!$conn) {
    // Provide a lightweight SQLite compatibility bridge for legacy mysqli_* calls
    class SQLiteMysqliBridge {
        private PDO $pdo;
        public function __construct(PDO $pdo) {
            $this->pdo = $pdo;
        }
        public function query(string $sql) {
            try {
                // Minor MySQL to SQLite compatibility replacements
                $sqlClean = preg_replace('/AUTO_INCREMENT/i', 'AUTOINCREMENT', $sql);
                $stmt = $this->pdo->query($sqlClean);
                return new SQLiteMysqliResultBridge($stmt);
            } catch (\Throwable $e) {
                return false;
            }
        }
        public function getPdo(): PDO {
            return $this->pdo;
        }
    }

    class SQLiteMysqliResultBridge {
        private ?PDOStatement $stmt;
        private ?array $rows = null;
        private int $pointer = 0;

        public function __construct(?PDOStatement $stmt) {
            $this->stmt = $stmt;
            if ($stmt) {
                $this->rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $this->rows = [];
            }
        }

        public function num_rows(): int {
            return count($this->rows);
        }

        public function fetch_assoc(): ?array {
            if ($this->rows !== null && isset($this->rows[$this->pointer])) {
                $row = $this->rows[$this->pointer];
                $this->pointer++;
                return $row;
            }
            return null;
        }
    }

    $conn = new SQLiteMysqliBridge($pdo);

    if (!function_exists('mysqli_query')) {
        function mysqli_query($link, string $query) {
            if ($link instanceof SQLiteMysqliBridge) {
                return $link->query($query);
            }
            return false;
        }
    }

    if (!function_exists('mysqli_num_rows')) {
        function mysqli_num_rows($result) {
            if ($result instanceof SQLiteMysqliResultBridge) {
                return $result->num_rows();
            }
            return 0;
        }
    }

    if (!function_exists('mysqli_fetch_assoc')) {
        function mysqli_fetch_assoc($result) {
            if ($result instanceof SQLiteMysqliResultBridge) {
                return $result->fetch_assoc();
            }
            return null;
        }
    }

    if (!function_exists('mysqli_error')) {
        function mysqli_error($link) {
            return "Database query error";
        }
    }
}
?>
