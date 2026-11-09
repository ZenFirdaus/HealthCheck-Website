<?php
/**
 * Authorization and Data Ownership Helper for HealthCheck
 * Enforces server-side data isolation: users can only view/manage their own records.
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../auth/role.php';

if (!function_exists('current_user_id')) {
    function current_user_id(): ?int {
        if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
            return (int)$_SESSION['user_id'];
        }
        return null;
    }
}

if (!function_exists('is_authenticated')) {
    function is_authenticated(): bool {
        return current_user_id() !== null;
    }
}

if (!function_exists('can_access_user_data')) {
    /**
     * Checks if the currently authenticated user is allowed to access data owned by $ownerUserId.
     * Administrators have global access; members can only access their own data.
     */
    function can_access_user_data(?int $ownerUserId): bool {
        if (is_admin()) {
            return true;
        }

        $myId = current_user_id();
        if ($myId !== null && $ownerUserId !== null && $myId === $ownerUserId) {
            return true;
        }

        return false;
    }
}

if (!function_exists('assert_record_access')) {
    /**
     * Validates that the current user owns the specified record or is an admin.
     * If unauthorized, halts execution with HTTP 403 Forbidden.
     */
    function assert_record_access(PDO $pdo, string $table, string $idColumn, int $recordId): array {
        // Whitelist allowed assessment, habits, and health goals tables to prevent SQL injection
        $allowedTables = [
            'cek_bmi', 'cek_bmr', 'cek_jantung', 'cek_mental', 'user_profile',
            'cek_whtr', 'cek_lemak_tubuh', 'cek_kebutuhan_cairan',
            'cek_tekanan_darah', 'cek_kualitas_tidur', 'cek_zona_detak_jantung', 'cek_aktivitas_fisik', 'cek_stres',
            'health_habits', 'habit_logs', 'daily_checkins', 'health_reminders', 'notification_logs',
            'health_goals', 'goal_progress_logs', 'goal_milestones', 'goal_activity_logs'
        ];
        if (!in_array($table, $allowedTables, true)) {
            http_response_code(400);
            die("400 Bad Request: Entitas data tidak valid.");
        }

        $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE {$idColumn} = ? LIMIT 1");
        $stmt->execute([$recordId]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            http_response_code(404);
            die("404 Not Found: Rekam pemeriksaan tidak ditemukan.");
        }

        $ownerId = isset($record['user_id']) && $record['user_id'] !== null ? (int)$record['user_id'] : null;

        // If record belongs to a user, enforce ownership check
        if ($ownerId !== null && !can_access_user_data($ownerId)) {
            http_response_code(403);
            die("403 Forbidden: Anda tidak memiliki hak akses terhadap rekam medis ini.");
        }

        // If legacy record has user_id = NULL, only administrators can modify/delete it
        if ($ownerId === null && !is_admin()) {
            http_response_code(403);
            die("403 Forbidden: Rekam pemeriksaan legacy anonim hanya dapat dikelola oleh administrator.");
        }

        return $record;
    }
}

if (!function_exists('get_user_profile')) {
    function get_user_profile(?PDO $pdo, int $userId): ?array {
        if ($pdo === null) {
            require_once __DIR__ . '/db.php';
            $pdo = Database::getConnection();
        }
        $stmt = $pdo->prepare("SELECT * FROM user_profile WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        return $profile ?: null;
    }
}

if (!function_exists('upsert_user_profile')) {
    function upsert_user_profile(?PDO $pdo, int $userId, array $data): bool {
        if ($pdo === null) {
            require_once __DIR__ . '/db.php';
            $pdo = Database::getConnection();
        }
        $profile = get_user_profile($pdo, $userId);

        $namaLengkap = trim($data['nama_lengkap'] ?? '');
        $tglLahir = !empty($data['tanggal_lahir']) ? $data['tanggal_lahir'] : null;
        $jk = in_array($data['jenis_kelamin'] ?? '', ['L', 'P'], true) ? $data['jenis_kelamin'] : null;
        $tb = filter_var($data['tinggi_badan'] ?? null, FILTER_VALIDATE_FLOAT) ?: null;
        $bb = filter_var($data['berat_badan'] ?? null, FILTER_VALIDATE_FLOAT) ?: null;
        $noTelp = trim($data['no_telepon'] ?? '');

        if ($profile) {
            $stmt = $pdo->prepare("UPDATE user_profile SET 
                nama_lengkap = ?, tanggal_lahir = ?, jenis_kelamin = ?, tinggi_badan = ?, berat_badan = ?, no_telepon = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE user_id = ?");
            return $stmt->execute([$namaLengkap, $tglLahir, $jk, $tb, $bb, $noTelp, $userId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO user_profile 
                (user_id, nama_lengkap, tanggal_lahir, jenis_kelamin, tinggi_badan, berat_badan, no_telepon) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            return $stmt->execute([$userId, $namaLengkap, $tglLahir, $jk, $tb, $bb, $noTelp]);
        }
    }
}

if (!function_exists('calculate_age_from_dob')) {
    /**
     * Calculates age in full years from a date of birth string (YYYY-MM-DD).
     * Returns null if date is empty, future date, or invalid.
     */
    function calculate_age_from_dob(?string $dob): ?int {
        if (empty($dob)) {
            return null;
        }
        try {
            $birthDate = new DateTime($dob);
            $today = new DateTime('today');
            if ($birthDate > $today) {
                return null;
            }
            return (int)$birthDate->diff($today)->y;
        } catch (\Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('get_current_user_profile')) {
    /**
     * Retrieves the profile of the currently logged-in user, or null if guest.
     */
    function get_current_user_profile(?PDO $pdo = null): ?array {
        $userId = current_user_id();
        if ($userId === null) {
            return null;
        }
        if ($pdo === null) {
            require_once __DIR__ . '/db.php';
            $pdo = Database::getConnection();
        }
        return get_user_profile($pdo, $userId);
    }
}
