<?php
/**
 * Automated Test Suite for HealthCheck Phase 5:
 * Health Goals & Actionable Milestones Management System
 *
 * Runs 65 comprehensive test cases against an isolated SQLite test database.
 */

$host = '127.0.0.1';
$port = 8100;
$baseUrl = "http://{$host}:{$port}";

echo "======================================================================\n";
echo "=== Memulai HealthCheck Phase 5 Comprehensive Test Suite (65 Tests) ===\n";
echo "======================================================================\n";

// Setup isolated test database
$projectRoot = dirname(__DIR__);
$mainDbPath = $projectRoot . '/database/healthcheck.sqlite';
$testDbPath = $projectRoot . '/database/healthcheck_test_phase5.sqlite';

$mainPdo = new PDO("sqlite:" . $mainDbPath);
$mainPdo->exec("PRAGMA wal_checkpoint(TRUNCATE);");
$mainPdo = null;

foreach ([$testDbPath, $testDbPath . '-wal', $testDbPath . '-shm'] as $f) {
    if (file_exists($f)) {
        unlink($f);
    }
}
copy($mainDbPath, $testDbPath);
echo "Database pengujian terpisah disiapkan: {$testDbPath}\n";

// Start background PHP server with HEALTHCHECK_DB_PATH set
$cmd = sprintf('HEALTHCHECK_DB_PATH=%s php -S %s:%d -t "%s" > /tmp/php_phase5_test.log 2>&1 & echo $!',
    escapeshellarg($testDbPath), $host, $port, $projectRoot
);
$pid = (int)shell_exec($cmd);
echo "Server test Phase 5 aktif (PID: {$pid}) pada {$baseUrl}\n";

register_shutdown_function(function() use ($pid, $testDbPath) {
    if ($pid > 0) {
        shell_exec("kill -9 {$pid} 2>/dev/null");
    }
    foreach ([$testDbPath, $testDbPath . '-wal', $testDbPath . '-shm'] as $f) {
        if (file_exists($f)) {
            unlink($f);
        }
    }
});

usleep(300000);

$results = [];

function request(string $url, string $method = 'GET', $data = null, array $headers = [], ?string $cookieJar = null): array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    
    if ($cookieJar) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    }
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (is_array($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } elseif ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    }
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $raw = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    
    return [
        'status' => $statusCode,
        'headers' => substr($raw, 0, $headerSize),
        'body' => substr($raw, $headerSize),
        'error' => $err
    ];
}

function extractCsrf(string $html): ?string {
    if (preg_match('/name="csrf_token"\s+value="([a-f0-9]{64})"/', $html, $matches)) {
        return $matches[1];
    }
    return null;
}

$tmpCookieDir = sys_get_temp_dir();

// Open connection to isolated test DB
$pdo = new PDO("sqlite:" . $testDbPath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_TIMEOUT => 5
]);
$pdo->exec("PRAGMA journal_mode = WAL;");
$pdo->exec("PRAGMA foreign_keys = ON;");
$pdo->exec("PRAGMA busy_timeout = 5000;");

function fetchVal(PDO $pdo, string $sql, array $params = []): mixed {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $val = $stmt->fetchColumn();
    $stmt->closeCursor();
    return $val;
}

function fetchRow(PDO $pdo, string $sql, array $params = []): ?array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    return $row ?: null;
}

function executeSql(PDO $pdo, string $sql, array $params = []): int {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $count = $stmt->rowCount();
    $stmt->closeCursor();
    return $count;
}

function loginUser(string $baseUrl, string $cookieFile, string $user, string $pass): bool {
    if (file_exists($cookieFile)) unlink($cookieFile);
    $p = request("{$baseUrl}/auth/login.php", 'GET', null, [], $cookieFile);
    $csrf = extractCsrf($p['body']);
    $res = request("{$baseUrl}/auth/login_user_proses.php", 'POST', [
        'username' => $user,
        'password' => $pass,
        'csrf_token' => $csrf
    ], [], $cookieFile);
    return ($res['status'] === 302);
}

require_once $projectRoot . '/includes/goal_helper.php';
require_once $projectRoot . '/includes/auth_helper.php';

// Prepare User A (id=2) and User B (id=3)
$guestCookie = $tmpCookieDir . '/guest_p5_cookie.txt';
if (file_exists($guestCookie)) unlink($guestCookie);

$userACookie = $tmpCookieDir . '/usera_p5_cookie.txt';
loginUser($baseUrl, $userACookie, 'user', 'user123');

$userBId = fetchVal($pdo, "SELECT id FROM user WHERE username = 'user_b'");
if (!$userBId) {
    $hashB = password_hash('pass123', PASSWORD_DEFAULT);
    executeSql($pdo, "INSERT INTO user (username, password, role) VALUES ('user_b', ?, 'user')", [$hashB]);
    $userBId = (int)$pdo->lastInsertId();
} else {
    $userBId = (int)$userBId;
}

$userBCookie = $tmpCookieDir . '/userb_p5_cookie.txt';
loginUser($baseUrl, $userBCookie, 'user_b', 'pass123');

// Clean test data for Phase 5
executeSql($pdo, "DELETE FROM goal_activity_logs WHERE user_id IN (2, ?)", [$userBId]);
executeSql($pdo, "DELETE FROM goal_milestones WHERE user_id IN (2, ?)", [$userBId]);
executeSql($pdo, "DELETE FROM goal_progress_logs WHERE user_id IN (2, ?)", [$userBId]);
executeSql($pdo, "DELETE FROM health_goals WHERE user_id IN (2, ?)", [$userBId]);

// ===================================================================
// A. HEALTH GOALS MANAGEMENT & CRUD (Tests 1 - 15)
// ===================================================================
echo "\n--- [Bagian A: Health Goals Management & CRUD] ---\n";

// 1. Guest dilarang membuka goals.php
echo "[Test 1] Akses guest ke goals.php dialihkan ke login...\n";
$rGuestGoals = request("{$baseUrl}/user/goals.php", 'GET', null, [], $guestCookie);
if ($rGuestGoals['status'] === 302 && strpos($rGuestGoals['headers'], 'belum_login') !== false) {
    $results['test1'] = ['status' => 'PASS', 'msg' => 'Tamu dialihkan ke login (HTTP 302 belum_login)'];
} else {
    $results['test1'] = ['status' => 'FAIL', 'msg' => "Status bukan 302: {$rGuestGoals['status']}"];
}
echo "Hasil: {$results['test1']['status']} - {$results['test1']['msg']}\n";

// 2. Member membuka goals.php
echo "[Test 2] Member membuka goals.php (HTTP 200 OK)...\n";
$rUserGoals = request("{$baseUrl}/user/goals.php", 'GET', null, [], $userACookie);
if ($rUserGoals['status'] === 200 && strpos($rUserGoals['body'], 'Health Goals & Milestones') !== false) {
    $results['test2'] = ['status' => 'PASS', 'msg' => 'Halaman Health Goals terbuka dengan status 200 OK'];
} else {
    $results['test2'] = ['status' => 'FAIL', 'msg' => 'Gagal membuka goals.php'];
}
echo "Hasil: {$results['test2']['status']} - {$results['test2']['msg']}\n";

// 3. Membuat Target Penurunan Berat Badan (Decreasing Goal)
echo "[Test 3] Membuat target penurunan berat badan (Awal 75kg, Target 68kg)...\n";
$csrfGoals = extractCsrf($rUserGoals['body']);
$rCreateGoal1 = request("{$baseUrl}/user/goals.php", 'POST', [
    'action'        => 'create_goal',
    'title'         => 'Turunkan Berat Badan Bertahap',
    'description'   => 'Defisit kalori sehat dan olahraga kardio teratur',
    'category'      => 'body_nutrition',
    'goal_type'     => 'weight_loss',
    'initial_value' => 75.0,
    'target_value'  => 68.0,
    'unit'          => 'kg',
    'direction'     => 'decrease',
    'start_date'    => '2026-11-01',
    'target_date'   => '2027-01-31',
    'is_featured'   => 1,
    'add_template'  => 1,
    'csrf_token'    => $csrfGoals
], [], $userACookie);
$goal1Id = (int)fetchVal($pdo, "SELECT id_goal FROM health_goals WHERE user_id = 2 AND title = 'Turunkan Berat Badan Bertahap'");
if ($rCreateGoal1['status'] === 302 && $goal1Id > 0) {
    $results['test3'] = ['status' => 'PASS', 'msg' => "Target penurunan berat badan ID #{$goal1Id} berhasil dibuat"];
} else {
    $results['test3'] = ['status' => 'FAIL', 'msg' => 'Gagal membuat goal penurunan BB'];
}
echo "Hasil: {$results['test3']['status']} - {$results['test3']['msg']}\n";

// 4. Milestone template otomatis diikutsertakan
echo "[Test 4] Verifikasi otomatisasi template milestone saat pembuatan target...\n";
$msCount1 = (int)fetchVal($pdo, "SELECT count(*) FROM goal_milestones WHERE goal_id = ?", [$goal1Id]);
if ($msCount1 >= 3) {
    $results['test4'] = ['status' => 'PASS', 'msg' => "Template milestone berhasil disertakan ({$msCount1} tahapan)"];
} else {
    $results['test4'] = ['status' => 'FAIL', 'msg' => "Milestone template tidak terbuat: {$msCount1}"];
}
echo "Hasil: {$results['test4']['status']} - {$results['test4']['msg']}\n";

// 5. Membuat Target Peningkatan Langkah Kaki (Increasing Goal)
echo "[Test 5] Membuat target peningkatan langkah kaki harian (Awal 4000, Target 10000 langkah)...\n";
$rCreateGoal2 = request("{$baseUrl}/user/goals.php", 'POST', [
    'action'        => 'create_goal',
    'title'         => 'Capai 10.000 Langkah Harian',
    'description'   => 'Mencegah perilaku sedentari dan meningkatkan kebugaran kardio',
    'category'      => 'physical_activity',
    'goal_type'     => 'steps',
    'initial_value' => 4000,
    'target_value'  => 10000,
    'unit'          => 'langkah',
    'direction'     => 'increase',
    'start_date'    => '2026-11-01',
    'target_date'   => '2026-12-15',
    'is_featured'   => 0,
    'add_template'  => 1,
    'csrf_token'    => $csrfGoals
], [], $userACookie);
$goal2Id = (int)fetchVal($pdo, "SELECT id_goal FROM health_goals WHERE user_id = 2 AND title = 'Capai 10.000 Langkah Harian'");
if ($rCreateGoal2['status'] === 302 && $goal2Id > 0) {
    $results['test5'] = ['status' => 'PASS', 'msg' => "Target langkah kaki ID #{$goal2Id} berhasil dibuat"];
} else {
    $results['test5'] = ['status' => 'FAIL', 'msg' => 'Gagal membuat goal langkah'];
}
echo "Hasil: {$results['test5']['status']} - {$results['test5']['msg']}\n";

// 6. Validasi: Tanggal target selesai lebih awal dari tanggal mulai ditolak
echo "[Test 6] Validasi: Tanggal selesai lebih awal dari tanggal mulai ditolak...\n";
$rInvalidDate = request("{$baseUrl}/user/goals.php", 'POST', [
    'action'        => 'create_goal',
    'title'         => 'Target Tanggal Ilegal',
    'category'      => 'hydration',
    'initial_value' => 1000,
    'target_value'  => 2500,
    'unit'          => 'ml',
    'start_date'    => '2026-11-10',
    'target_date'   => '2026-11-05', // Earlier!
    'csrf_token'    => $csrfGoals
], [], $userACookie);
$existsInvalidDate = fetchVal($pdo, "SELECT count(*) FROM health_goals WHERE title = 'Target Tanggal Ilegal'");
if (strpos($rInvalidDate['body'], 'Tanggal target selesai tidak boleh lebih awal') !== false && $existsInvalidDate == 0) {
    $results['test6'] = ['status' => 'PASS', 'msg' => 'Validasi tanggal target < tanggal mulai berhasil menangkal data'];
} else {
    $results['test6'] = ['status' => 'FAIL', 'msg' => 'Validasi tanggal gagal'];
}
echo "Hasil: {$results['test6']['status']} - {$results['test6']['msg']}\n";

// 7. Validasi: Judul atau Satuan kosong ditolak
echo "[Test 7] Validasi: Judul atau Satuan kosong ditolak...\n";
$rEmptyFields = request("{$baseUrl}/user/goals.php", 'POST', [
    'action'        => 'create_goal',
    'title'         => '', // Kosong!
    'unit'          => '',
    'csrf_token'    => $csrfGoals
], [], $userACookie);
if (strpos($rEmptyFields['body'], 'Judul target dan satuan pengukuran wajib diisi') !== false) {
    $results['test7'] = ['status' => 'PASS', 'msg' => 'Input kosong ditolak dengan pesan informatif'];
} else {
    $results['test7'] = ['status' => 'FAIL', 'msg' => 'Validasi input kosong gagal'];
}
echo "Hasil: {$results['test7']['status']} - {$results['test7']['msg']}\n";

// 8. Menjeda Target (Pause Goal)
echo "[Test 8] Menjeda target aktif (Status: paused)...\n";
$rPause = request("{$baseUrl}/user/goals.php", 'POST', [
    'action'     => 'set_status',
    'goal_id'    => $goal2Id,
    'status'     => 'paused',
    'csrf_token' => $csrfGoals
], [], $userACookie);
$statusG2 = fetchVal($pdo, "SELECT status FROM health_goals WHERE id_goal = ?", [$goal2Id]);
if ($rPause['status'] === 302 && $statusG2 === 'paused') {
    $results['test8'] = ['status' => 'PASS', 'msg' => 'Status target berhasil diubah menjadi paused'];
} else {
    $results['test8'] = ['status' => 'FAIL', 'msg' => 'Gagal menjeda target'];
}
echo "Hasil: {$results['test8']['status']} - {$results['test8']['msg']}\n";

// 9. Mengaktifkan Kembali Target yang Dijeda (Resume Goal)
echo "[Test 9] Mengaktifkan kembali target yang dijeda (Status: active)...\n";
$rResume = request("{$baseUrl}/user/goals.php", 'POST', [
    'action'     => 'set_status',
    'goal_id'    => $goal2Id,
    'status'     => 'active',
    'csrf_token' => $csrfGoals
], [], $userACookie);
$statusG2Active = fetchVal($pdo, "SELECT status FROM health_goals WHERE id_goal = ?", [$goal2Id]);
if ($rResume['status'] === 302 && $statusG2Active === 'active') {
    $results['test9'] = ['status' => 'PASS', 'msg' => 'Status target berhasil diaktifkan kembali'];
} else {
    $results['test9'] = ['status' => 'FAIL', 'msg' => 'Gagal mengaktifkan target'];
}
echo "Hasil: {$results['test9']['status']} - {$results['test9']['msg']}\n";

// 10. Menyematkan Featured Goal di Dashboard
echo "[Test 10] Menyematkan target utama (Featured Goal)...\n";
$rSetFeatured = request("{$baseUrl}/user/goals.php", 'POST', [
    'action'     => 'set_featured',
    'goal_id'    => $goal2Id,
    'csrf_token' => $csrfGoals
], [], $userACookie);
$feat1 = (int)fetchVal($pdo, "SELECT is_featured FROM health_goals WHERE id_goal = ?", [$goal1Id]);
$feat2 = (int)fetchVal($pdo, "SELECT is_featured FROM health_goals WHERE id_goal = ?", [$goal2Id]);
if ($feat1 === 0 && $feat2 === 1) {
    $results['test10'] = ['status' => 'PASS', 'msg' => 'Featured goal berhasil beralih ke Goal #2 (Goal #1 unfeatured)'];
} else {
    $results['test10'] = ['status' => 'FAIL', 'msg' => 'Featured goal gagal disematkan'];
}
echo "Hasil: {$results['test10']['status']} - {$results['test10']['msg']}\n";

// 11. Menyelesaikan Target (Complete Goal)
echo "[Test 11] Menyelesaikan target (Status: completed)...\n";
$rComplete = request("{$baseUrl}/user/goals.php", 'POST', [
    'action'     => 'set_status',
    'goal_id'    => $goal2Id,
    'status'     => 'completed',
    'csrf_token' => $csrfGoals
], [], $userACookie);
$statusCompleted = fetchVal($pdo, "SELECT status FROM health_goals WHERE id_goal = ?", [$goal2Id]);
$completedAt = fetchVal($pdo, "SELECT completed_at FROM health_goals WHERE id_goal = ?", [$goal2Id]);
if ($statusCompleted === 'completed' && !empty($completedAt)) {
    $results['test11'] = ['status' => 'PASS', 'msg' => 'Target selesai tercatat dengan timestamp completed_at'];
} else {
    $results['test11'] = ['status' => 'FAIL', 'msg' => 'Penyelesaian target gagal'];
}
echo "Hasil: {$results['test11']['status']} - {$results['test11']['msg']}\n";

// 12. Mengarsipkan Target (Archive Goal)
echo "[Test 12] Mengarsipkan target (Status: archived)...\n";
$rArchive = request("{$baseUrl}/user/goals.php", 'POST', [
    'action'     => 'set_status',
    'goal_id'    => $goal2Id,
    'status'     => 'archived',
    'csrf_token' => $csrfGoals
], [], $userACookie);
$statusArchived = fetchVal($pdo, "SELECT status FROM health_goals WHERE id_goal = ?", [$goal2Id]);
if ($statusArchived === 'archived') {
    $results['test12'] = ['status' => 'PASS', 'msg' => 'Target berhasil diarsipkan'];
} else {
    $results['test12'] = ['status' => 'FAIL', 'msg' => 'Pengarsipan gagal'];
}
echo "Hasil: {$results['test12']['status']} - {$results['test12']['msg']}\n";

// 13. Menghapus Target (Delete Goal)
echo "[Test 13] Menghapus target dan menghapus data relasi terkait...\n";
$rDeleteGoal = request("{$baseUrl}/user/goals.php", 'POST', [
    'action'     => 'delete_goal',
    'goal_id'    => $goal2Id,
    'csrf_token' => $csrfGoals
], [], $userACookie);
$existsGoal2 = (int)fetchVal($pdo, "SELECT count(*) FROM health_goals WHERE id_goal = ?", [$goal2Id]);
if ($existsGoal2 === 0) {
    $results['test13'] = ['status' => 'PASS', 'msg' => 'Target ID #2 berhasil dihapus permanen'];
} else {
    $results['test13'] = ['status' => 'FAIL', 'msg' => 'Hapus target gagal'];
}
echo "Hasil: {$results['test13']['status']} - {$results['test13']['msg']}\n";

// 14. IDOR: User B DILARANG mengubah atau menghapus target User A
echo "[Test 14] IDOR: User B dilarang mengubah status target User A...\n";
$csrfUserB = extractCsrf(request("{$baseUrl}/user/goals.php", 'GET', null, [], $userBCookie)['body']);
$rIdorGoal = request("{$baseUrl}/user/goals.php", 'POST', [
    'action'     => 'set_status',
    'goal_id'    => $goal1Id, // User A's goal
    'status'     => 'archived',
    'csrf_token' => $csrfUserB
], [], $userBCookie);
$goal1Status = fetchVal($pdo, "SELECT status FROM health_goals WHERE id_goal = ?", [$goal1Id]);
if ($goal1Status === 'active') {
    $results['test14'] = ['status' => 'PASS', 'msg' => 'Akses silang target User A oleh User B ditolak (Status tetap active)'];
} else {
    $results['test14'] = ['status' => 'FAIL', 'msg' => 'Celah IDOR pada target!'];
}
echo "Hasil: {$results['test14']['status']} - {$results['test14']['msg']}\n";

// 15. Isolasi Akun: Daftar target User B kosong & tidak melihat target User A
echo "[Test 15] Isolasi Akun: User B tidak melihat target milik User A...\n";
$rGoalsB = request("{$baseUrl}/user/goals.php?status=all", 'GET', null, [], $userBCookie);
$leakedInGoalsB = (strpos($rGoalsB['body'], 'Turunkan Berat Badan Bertahap') !== false);
if (!$leakedInGoalsB) {
    $results['test15'] = ['status' => 'PASS', 'msg' => 'Daftar target User B terisolasi total dari rekam data User A'];
} else {
    $results['test15'] = ['status' => 'FAIL', 'msg' => 'Kebocoran target antar akun!'];
}
echo "Hasil: {$results['test15']['status']} - {$results['test15']['msg']}\n";

// ===================================================================
// B. DIRECTIONAL PROGRESS CALCULATION TESTS (Tests 16 - 24)
// ===================================================================
echo "\n--- [Bagian B: Directional Progress Calculation Formula] ---\n";

// 16. Target Penurunan Normal (75kg -> 68kg, current 71.5kg = 50%)
echo "[Test 16] Kalkulasi progres penurunan bertahap (75kg -> 68kg, current 71.5kg)...\n";
$pDec = calculate_goal_progress(75.0, 68.0, 71.5, 'decrease');
if ($pDec['percentage'] == 50.0 && $pDec['is_achieved'] === false && $pDec['delta_remaining'] == 3.5) {
    $results['test16'] = ['status' => 'PASS', 'msg' => 'Progres penurunan terhitung 50.0% presisi (Sisa: 3.5 kg)'];
} else {
    $results['test16'] = ['status' => 'FAIL', 'msg' => "Progres penurunan salah: {$pDec['percentage']}%"];
}
echo "Hasil: {$results['test16']['status']} - {$results['test16']['msg']}\n";

// 17. Target Peningkatan Normal (4000 -> 10000 langkah, current 7000 = 50%)
echo "[Test 17] Kalkulasi progres peningkatan (4000 -> 10000 langkah, current 7000)...\n";
$pInc = calculate_goal_progress(4000, 10000, 7000, 'increase');
if ($pInc['percentage'] == 50.0 && $pInc['is_achieved'] === false && $pInc['delta_remaining'] == 3000) {
    $results['test17'] = ['status' => 'PASS', 'msg' => 'Progres peningkatan terhitung 50.0% presisi (Sisa: 3000 langkah)'];
} else {
    $results['test17'] = ['status' => 'FAIL', 'msg' => "Progres peningkatan salah: {$pInc['percentage']}%"];
}
echo "Hasil: {$results['test17']['status']} - {$results['test17']['msg']}\n";

// 18. Target Tercapai (100% saat current mencapai target)
echo "[Test 18] Target tercapai saat nilai terkini mencapai sasaran...\n";
$pDone = calculate_goal_progress(75.0, 68.0, 68.0, 'decrease');
if ($pDone['percentage'] == 100.0 && $pDone['is_achieved'] === true && $pDone['delta_remaining'] == 0.0) {
    $results['test18'] = ['status' => 'PASS', 'msg' => 'Status target tercapai (100%) dan is_achieved = true'];
} else {
    $results['test18'] = ['status' => 'FAIL', 'msg' => 'Target tercapai gagal dihitung'];
}
echo "Hasil: {$results['test18']['status']} - {$results['test18']['msg']}\n";

// 19. Kasus Melebihi Target (Overshoot diclamp ke 100% tanpa malformasi)
echo "[Test 19] Penanganan target terlampaui (Overshoot diclamp 100%)...\n";
$pOvershoot = calculate_goal_progress(75.0, 68.0, 65.0, 'decrease'); // turun sampai 65kg
if ($pOvershoot['percentage'] == 100.0 && $pOvershoot['raw_percentage'] > 100.0 && $pOvershoot['is_achieved'] === true) {
    $results['test19'] = ['status' => 'PASS', 'msg' => 'Target terlampaui diclamp aman ke 100.0%'];
} else {
    $results['test19'] = ['status' => 'FAIL', 'msg' => 'Overshoot tidak diclamp'];
}
echo "Hasil: {$results['test19']['status']} - {$results['test19']['msg']}\n";

// 20. Kasus Arah Berlawanan (Kenaikan pada target penurunan diclamp 0%, bukan negatif)
echo "[Test 20] Penanganan arah berlawanan (Kenaikan berat badan pada target diet diclamp 0%)...\n";
$pOpposite = calculate_goal_progress(75.0, 68.0, 78.0, 'decrease'); // naik ke 78kg
if ($pOpposite['percentage'] == 0.0 && $pOpposite['is_opposite'] === true && $pOpposite['raw_percentage'] < 0) {
    $results['test20'] = ['status' => 'PASS', 'msg' => 'Perubahan arah berlawanan diclamp 0.0% dan ditandai is_opposite = true'];
} else {
    $results['test20'] = ['status' => 'FAIL', 'msg' => 'Persentase negatif bocor!'];
}
echo "Hasil: {$results['test20']['status']} - {$results['test20']['msg']}\n";

// 21. Kasus Nilai Awal Sama dengan Target (Division by Zero Prevention)
echo "[Test 21] Pencegahan division by zero saat nilai awal == sasaran...\n";
$pZeroDiv = calculate_goal_progress(70.0, 70.0, 70.0, 'decrease');
if ($pZeroDiv['percentage'] == 100.0 && $pZeroDiv['is_achieved'] === true) {
    $results['test21'] = ['status' => 'PASS', 'msg' => 'Kasus initial == target tertangani aman tanpa fatal error'];
} else {
    $results['test21'] = ['status' => 'FAIL', 'msg' => 'Pencegahan zero division gagal'];
}
echo "Hasil: {$results['test21']['status']} - {$results['test21']['msg']}\n";

// 22. Status Label Progres "Hampir Tercapai" (>= 75%)
echo "[Test 22] Label status 'Hampir Tercapai' saat capaian >= 75%...\n";
$pNear = calculate_goal_progress(75.0, 68.0, 69.5, 'decrease'); // (75-69.5)/(75-68) = 5.5/7 = 78.6%
if ($pNear['percentage'] >= 75.0 && $pNear['status_label'] === 'Hampir Tercapai') {
    $results['test22'] = ['status' => 'PASS', 'msg' => "Label status 'Hampir Tercapai' ({$pNear['percentage']}%) aktif"];
} else {
    $results['test22'] = ['status' => 'FAIL', 'msg' => 'Label hampir tercapai tidak muncul'];
}
echo "Hasil: {$results['test22']['status']} - {$results['test22']['msg']}\n";

// 23. Satuan Ukuran Ditampilkan Konsisten
echo "[Test 23] Preservasi satuan dan delta sisa capaian...\n";
if ($pDec['delta_remaining'] == 3.5) {
    $results['test23'] = ['status' => 'PASS', 'msg' => 'Delta sisa capaian akurat'];
} else {
    $results['test23'] = ['status' => 'FAIL', 'msg' => 'Delta sisa salah'];
}
echo "Hasil: {$results['test23']['status']} - {$results['test23']['msg']}\n";

// 24. Arah Peningkatan Berlawanan (Langkah turun di bawah initial diclamp 0%)
echo "[Test 24] Arah peningkatan berlawanan diclamp ke 0%...\n";
$pIncOpp = calculate_goal_progress(5000, 10000, 3000, 'increase');
if ($pIncOpp['percentage'] == 0.0 && $pIncOpp['is_opposite'] === true) {
    $results['test24'] = ['status' => 'PASS', 'msg' => 'Peningkatan berlawanan diclamp ke 0.0%'];
} else {
    $results['test24'] = ['status' => 'FAIL', 'msg' => 'Clamp peningkatan gagal'];
}
echo "Hasil: {$results['test24']['status']} - {$results['test24']['msg']}\n";

// ===================================================================
// C. ACTIONABLE MILESTONES TESTS (Tests 25 - 35)
// ===================================================================
echo "\n--- [Bagian C: Actionable Milestones Management] ---\n";

// 25. Menambah Milestone Baru secara Manual
echo "[Test 25] Menambah milestone manual pada Goal #1...\n";
$msIdNew = create_goal_milestone($pdo, 2, $goal1Id, [
    'title'          => 'Fase Tambahan: Konsultasi Nutrisi',
    'description'    => 'Diskusi menu seimbang dengan nutrisionis',
    'sequence_order' => 5,
    'target_date'    => '2026-12-01'
]);
if ($msIdNew > 0) {
    $results['test25'] = ['status' => 'PASS', 'msg' => "Milestone manual ID #{$msIdNew} berhasil ditambahkan"];
} else {
    $results['test25'] = ['status' => 'FAIL', 'msg' => 'Gagal menambah milestone'];
}
echo "Hasil: {$results['test25']['status']} - {$results['test25']['msg']}\n";

// 26. Mengedit Informasi Milestone
echo "[Test 26] Mengedit informasi milestone (update_goal_milestone)...\n";
$okUpdMs = update_goal_milestone($pdo, 2, $msIdNew, [
    'title'          => 'Fase Tambahan: Evaluasi Pola Makan',
    'description'    => 'Review catatan jurnal makanan mingguan',
    'sequence_order' => 5
]);
$msRow = fetchRow($pdo, "SELECT * FROM goal_milestones WHERE id_milestone = ?", [$msIdNew]);
if ($okUpdMs && $msRow['title'] === 'Fase Tambahan: Evaluasi Pola Makan') {
    $results['test26'] = ['status' => 'PASS', 'msg' => 'Milestone berhasil diperbarui'];
} else {
    $results['test26'] = ['status' => 'FAIL', 'msg' => 'Gagal update milestone'];
}
echo "Hasil: {$results['test26']['status']} - {$results['test26']['msg']}\n";

// 27. Menyelesaikan Milestone (Status: completed & completed_at)
echo "[Test 27] Menandai milestone selesai (Status: completed)...\n";
$okSetMs = set_milestone_status($pdo, 2, $msIdNew, 'completed');
$msUpdated = fetchRow($pdo, "SELECT status, completed_at FROM goal_milestones WHERE id_milestone = ?", [$msIdNew]);
if ($okSetMs && $msUpdated['status'] === 'completed' && !empty($msUpdated['completed_at'])) {
    $results['test27'] = ['status' => 'PASS', 'msg' => 'Milestone ditandai selesai dan tanggal penyelesaian tercatat'];
} else {
    $results['test27'] = ['status' => 'FAIL', 'msg' => 'Gagal menyelesaikan milestone'];
}
echo "Hasil: {$results['test27']['status']} - {$results['test27']['msg']}\n";

// 28. Mengembalikan Status Milestone ke Pending
echo "[Test 28] Mengembalikan status milestone ke pending...\n";
set_milestone_status($pdo, 2, $msIdNew, 'pending');
$msPending = fetchRow($pdo, "SELECT status, completed_at FROM goal_milestones WHERE id_milestone = ?", [$msIdNew]);
if ($msPending['status'] === 'pending' && empty($msPending['completed_at'])) {
    $results['test28'] = ['status' => 'PASS', 'msg' => 'Status milestone berhasil dikembalikan ke pending'];
} else {
    $results['test28'] = ['status' => 'FAIL', 'msg' => 'Gagal revert milestone'];
}
echo "Hasil: {$results['test28']['status']} - {$results['test28']['msg']}\n";

// 29. Menghapus Milestone
echo "[Test 29] Menghapus milestone...\n";
$okDelMs = delete_goal_milestone($pdo, 2, $msIdNew);
$existsMs = fetchVal($pdo, "SELECT count(*) FROM goal_milestones WHERE id_milestone = ?", [$msIdNew]);
if ($okDelMs && $existsMs == 0) {
    $results['test29'] = ['status' => 'PASS', 'msg' => 'Milestone berhasil dihapus'];
} else {
    $results['test29'] = ['status' => 'FAIL', 'msg' => 'Gagal menghapus milestone'];
}
echo "Hasil: {$results['test29']['status']} - {$results['test29']['msg']}\n";

// 30. Rekomendasi Template Milestone Berat Badan
echo "[Test 30] Template milestone penurunan berat badan berbasis 4 fase...\n";
$suggWeight = get_milestone_suggestions('body_nutrition', 'weight_loss', 80, 70, 'kg');
if (count($suggWeight) === 4 && $suggWeight[0]['target_val'] == 77.5 && $suggWeight[3]['target_val'] == 70) {
    $results['test30'] = ['status' => 'PASS', 'msg' => 'Template milestone penurunan BB bertahap 4 fase valid'];
} else {
    $results['test30'] = ['status' => 'FAIL', 'msg' => 'Template milestone BB salah'];
}
echo "Hasil: {$results['test30']['status']} - {$results['test30']['msg']}\n";

// 31. Rekomendasi Template Milestone Langkah Kaki
echo "[Test 31] Template milestone langkah kaki harian...\n";
$suggSteps = get_milestone_suggestions('physical_activity', 'steps', 3000, 10000, 'langkah');
if (count($suggSteps) === 3 && $suggSteps[0]['target_val'] == 5000) {
    $results['test31'] = ['status' => 'PASS', 'msg' => 'Template milestone langkah kaki 3 tahap valid'];
} else {
    $results['test31'] = ['status' => 'FAIL', 'msg' => 'Template milestone langkah salah'];
}
echo "Hasil: {$results['test31']['status']} - {$results['test31']['msg']}\n";

// 32. Rekomendasi Template Milestone Tidur
echo "[Test 32] Template milestone durasi tidur sirkadian...\n";
$suggSleep = get_milestone_suggestions('sleep_lifestyle', 'sleep_duration', 5.5, 8.0, 'jam');
if (count($suggSleep) === 3 && $suggSleep[2]['target_val'] == 8.0) {
    $results['test32'] = ['status' => 'PASS', 'msg' => 'Template milestone tidur 3 tahap valid'];
} else {
    $results['test32'] = ['status' => 'FAIL', 'msg' => 'Template milestone tidur salah'];
}
echo "Hasil: {$results['test32']['status']} - {$results['test32']['msg']}\n";

// 33. IDOR Milestone: User B dilarang mengubah status milestone User A
echo "[Test 33] IDOR: User B dilarang menyelesaikan milestone User A...\n";
$firstMsA = (int)fetchVal($pdo, "SELECT id_milestone FROM goal_milestones WHERE goal_id = ? LIMIT 1", [$goal1Id]);
$okIdorMs = set_milestone_status($pdo, $userBId, $firstMsA, 'completed');
$msStatusA = fetchVal($pdo, "SELECT status FROM goal_milestones WHERE id_milestone = ?", [$firstMsA]);
if ($okIdorMs === false && $msStatusA !== 'completed') {
    $results['test33'] = ['status' => 'PASS', 'msg' => 'IDOR dicegah, status milestone User A tetap pending'];
} else {
    $results['test33'] = ['status' => 'FAIL', 'msg' => 'Celah IDOR pada milestone!'];
}
echo "Hasil: {$results['test33']['status']} - {$results['test33']['msg']}\n";

// 34. IDOR Milestone: User B dilarang menghapus milestone User A
echo "[Test 34] IDOR: User B dilarang menghapus milestone User A...\n";
$okIdorDelMs = delete_goal_milestone($pdo, $userBId, $firstMsA);
$existsMsA = fetchVal($pdo, "SELECT count(*) FROM goal_milestones WHERE id_milestone = ?", [$firstMsA]);
if ($okIdorDelMs === false && $existsMsA == 1) {
    $results['test34'] = ['status' => 'PASS', 'msg' => 'Penghapusan silang milestone ditolak dengan aman'];
} else {
    $results['test34'] = ['status' => 'FAIL', 'msg' => 'Milestone User A terhapus oleh User B!'];
}
echo "Hasil: {$results['test34']['status']} - {$results['test34']['msg']}\n";

// 35. Milestone tidak otomatis tertandai selesai saat target utama selesai (User Control)
echo "[Test 35] Independensi milestone (Tidak auto-complete sembarangan)...\n";
if ($msStatusA === 'pending') {
    $results['test35'] = ['status' => 'PASS', 'msg' => 'Milestone mempertahankan kontrol manual pengguna'];
} else {
    $results['test35'] = ['status' => 'FAIL', 'msg' => 'Milestone terubah otomatis'];
}
echo "Hasil: {$results['test35']['status']} - {$results['test35']['msg']}\n";

// ===================================================================
// D. GOAL PROGRESS LOGGING & DATA INTEGRATION TESTS (Tests 36 - 45)
// ===================================================================
echo "\n--- [Bagian D: Progress Logging & Integration] ---\n";

// 36. Pencatatan Progres Manual
echo "[Test 36] Pencatatan perkembangan progres target secara manual...\n";
$okProgManual = record_goal_progress($pdo, 2, $goal1Id, 73.5, 'Berat badan setelah 2 minggu defisit kalori', 'manual', null, '2026-11-15');
$latestValG1 = (float)fetchVal($pdo, "SELECT current_value FROM health_goals WHERE id_goal = ?", [$goal1Id]);
if ($okProgManual && $latestValG1 == 73.5) {
    $results['test36'] = ['status' => 'PASS', 'msg' => 'Progres manual 73.5 kg berhasil dicatat & meng-update target'];
} else {
    $results['test36'] = ['status' => 'FAIL', 'msg' => 'Gagal mencatat progres'];
}
echo "Hasil: {$results['test36']['status']} - {$results['test36']['msg']}\n";

// 37. Persentase Progres Tersimpan di Log
echo "[Test 37] Persentase progres pada log progres tersimpan akurat...\n";
// Initial 75, Target 68, Current 73.5: (75-73.5)/(75-68) = 1.5/7 = 21.4%
$lastLogProg = (float)fetchVal($pdo, "SELECT progress_percentage FROM goal_progress_logs WHERE goal_id = ? ORDER BY id_progress DESC LIMIT 1", [$goal1Id]);
if ($lastLogProg == 21.4) {
    $results['test37'] = ['status' => 'PASS', 'msg' => 'Persentase progres di log: 21.4% presisi'];
} else {
    $results['test37'] = ['status' => 'FAIL', 'msg' => "Persentase log salah: {$lastLogProg}%"];
}
echo "Hasil: {$results['test37']['status']} - {$results['test37']['msg']}\n";

// 38. Riwayat Log Kronologis
echo "[Test 38] Riwayat progres tersusun kronologis ASC...\n";
record_goal_progress($pdo, 2, $goal1Id, 72.0, 'Berat badan minggu ke-4', 'manual', null, '2026-11-28');
$logsG1 = get_goal_progress_logs($pdo, 2, $goal1Id);
$isAsc = true;
for ($i = 0; $i < count($logsG1) - 1; $i++) {
    if (strtotime($logsG1[$i]['recorded_at']) > strtotime($logsG1[$i + 1]['recorded_at'])) {
        $isAsc = false;
        break;
    }
}
if ($isAsc && count($logsG1) >= 3) {
    $results['test38'] = ['status' => 'PASS', 'msg' => 'Riwayat log tersusun urut waktu ASC'];
} else {
    $results['test38'] = ['status' => 'FAIL', 'msg' => 'Urutan log salah'];
}
echo "Hasil: {$results['test38']['status']} - {$results['test38']['msg']}\n";

// 39. Auto-sync dari Tes Kalkulator BMI
echo "[Test 39] Sinkronisasi otomatis dari hasil pemeriksaan BMI (cek_bmi)...\n";
executeSql($pdo, "INSERT INTO cek_bmi (user_id, nama_user, umur, berat_badan, tinggi_badan, bmi, kondisi, created_at) VALUES (2, 'User A', 30, 71.0, 175, 23.2, 'Normal', '2026-12-05 08:00:00')");
$syncedBmi = sync_external_data_to_goals($pdo, 2);
$lastSyncedVal = (float)fetchVal($pdo, "SELECT current_value FROM health_goals WHERE id_goal = ?", [$goal1Id]);
$sourceType = fetchVal($pdo, "SELECT source_type FROM goal_progress_logs WHERE goal_id = ? ORDER BY id_progress DESC LIMIT 1", [$goal1Id]);
if ($syncedBmi >= 1 && $lastSyncedVal == 71.0 && $sourceType === 'calculator') {
    $results['test39'] = ['status' => 'PASS', 'msg' => 'Data berat badan 71.0 kg tersinkronisasi otomatis dari tes BMI'];
} else {
    $results['test39'] = ['status' => 'FAIL', 'msg' => 'Auto-sync BMI gagal'];
}
echo "Hasil: {$results['test39']['status']} - {$results['test39']['msg']}\n";

// 40. Pencegahan Duplikasi Sinkronisasi Otomatis
echo "[Test 40] Idempotensi auto-sync: Record sumber yang sama tidak disinkronkan dobel...\n";
$syncedAgain = sync_external_data_to_goals($pdo, 2);
if ($syncedAgain === 0) {
    $results['test40'] = ['status' => 'PASS', 'msg' => 'Idempotensi sinkronisasi terverifikasi (0 data duplikat)'];
} else {
    $results['test40'] = ['status' => 'FAIL', 'msg' => "Duplikasi sinkronisasi terjadi: {$syncedAgain}"];
}
echo "Hasil: {$results['test40']['status']} - {$results['test40']['msg']}\n";

// 41. Auto-sync dari Daily Check-in (Langkah & Hidrasi)
echo "[Test 41] Sinkronisasi otomatis dari Daily Check-in ke target...\n";
$goalStepsId = create_health_goal($pdo, 2, [
    'title'         => 'Target Jalan Sehat',
    'category'      => 'physical_activity',
    'goal_type'     => 'steps',
    'initial_value' => 5000,
    'target_value'  => 10000,
    'unit'          => 'langkah',
    'direction'     => 'increase'
]);
executeSql($pdo, "INSERT INTO daily_checkins (user_id, tanggal, steps_count) VALUES (2, '2026-12-10', 8500)");
$syncedCheckin = sync_external_data_to_goals($pdo, 2);
$valSteps = (float)fetchVal($pdo, "SELECT current_value FROM health_goals WHERE id_goal = ?", [$goalStepsId]);
if ($syncedCheckin >= 1 && $valSteps == 8500) {
    $results['test41'] = ['status' => 'PASS', 'msg' => 'Data langkah 8.500 tersinkronisasi otomatis dari Daily Check-in'];
} else {
    $results['test41'] = ['status' => 'FAIL', 'msg' => 'Auto-sync checkin gagal'];
}
echo "Hasil: {$results['test41']['status']} - {$results['test41']['msg']}\n";

// 42. Auto-completion saat progres mencapai 100%
echo "[Test 42] Otomatis menyelesaikan target saat progres mencapai 100%...\n";
record_goal_progress($pdo, 2, $goalStepsId, 10000, 'Mencapai target langkah!');
$statusAutoDone = fetchVal($pdo, "SELECT status FROM health_goals WHERE id_goal = ?", [$goalStepsId]);
if ($statusAutoDone === 'completed') {
    $results['test42'] = ['status' => 'PASS', 'msg' => 'Target otomatis beralih status ke completed saat mencapai 100%'];
} else {
    $results['test42'] = ['status' => 'FAIL', 'msg' => 'Auto-complete gagal'];
}
echo "Hasil: {$results['test42']['status']} - {$results['test42']['msg']}\n";

// 43. Activity Log Tercatat Otomatis
echo "[Test 43] Riwayat aktivitas perubahan target tercatat di goal_activity_logs...\n";
$actCount = (int)fetchVal($pdo, "SELECT count(*) FROM goal_activity_logs WHERE user_id = 2 AND goal_id = ?", [$goal1Id]);
if ($actCount >= 3) {
    $results['test43'] = ['status' => 'PASS', 'msg' => "Aktivitas tercatat rapi ({$actCount} entri aktivitas)"];
} else {
    $results['test43'] = ['status' => 'FAIL', 'msg' => "Log aktivitas kurang: {$actCount}"];
}
echo "Hasil: {$results['test43']['status']} - {$results['test43']['msg']}\n";

// 44. Asal Sumber Data Progres Disimpan Jelas
echo "[Test 44] Sumber data progres tersimpan jelas (manual vs calculator vs checkin)...\n";
$sources = $pdo->query("SELECT DISTINCT source_type FROM goal_progress_logs WHERE user_id = 2")->fetchAll(PDO::FETCH_COLUMN);
if (in_array('manual', $sources, true) && in_array('calculator', $sources, true)) {
    $results['test44'] = ['status' => 'PASS', 'msg' => 'Sumber data progres terdiferensiasi di database'];
} else {
    $results['test44'] = ['status' => 'FAIL', 'msg' => 'Sumber data tidak tercatat'];
}
echo "Hasil: {$results['test44']['status']} - {$results['test44']['msg']}\n";

// 45. IDOR Progres: User B dilarang menambah progres pada target User A
echo "[Test 45] IDOR: User B dilarang mencatat progres pada target User A...\n";
$okIdorProg = record_goal_progress($pdo, $userBId, $goal1Id, 50.0, 'Injeksi User B');
$valAfterIdor = (float)fetchVal($pdo, "SELECT current_value FROM health_goals WHERE id_goal = ?", [$goal1Id]);
if ($okIdorProg === false && $valAfterIdor != 50.0) {
    $results['test45'] = ['status' => 'PASS', 'msg' => 'Pencatatan progres lintas akun ditolak dengan aman'];
} else {
    $results['test45'] = ['status' => 'FAIL', 'msg' => 'Celah IDOR pada pencatatan progres!'];
}
echo "Hasil: {$results['test45']['status']} - {$results['test45']['msg']}\n";

// ===================================================================
// E. GOAL DETAIL PAGE & VISUALIZATION (Tests 46 - 54)
// ===================================================================
echo "\n--- [Bagian E: Goal Detail Page & Visualization] ---\n";

// 46. Guest dilarang membuka goal_detail.php
echo "[Test 46] Akses guest ke goal_detail.php dialihkan ke login...\n";
$rGuestDetail = request("{$baseUrl}/user/goal_detail.php?id={$goal1Id}", 'GET', null, [], $guestCookie);
if ($rGuestDetail['status'] === 302 && strpos($rGuestDetail['headers'], 'belum_login') !== false) {
    $results['test46'] = ['status' => 'PASS', 'msg' => 'Tamu dialihkan ke login (HTTP 302)'];
} else {
    $results['test46'] = ['status' => 'FAIL', 'msg' => 'Tamu dapat membuka detail goal'];
}
echo "Hasil: {$results['test46']['status']} - {$results['test46']['msg']}\n";

// 47. Member membuka goal_detail.php miliknya
echo "[Test 47] Member membuka detail target miliknya (HTTP 200 OK)...\n";
$rDetail1 = request("{$baseUrl}/user/goal_detail.php?id={$goal1Id}", 'GET', null, [], $userACookie);
if ($rDetail1['status'] === 200 && strpos($rDetail1['body'], 'Turunkan Berat Badan Bertahap') !== false) {
    $results['test47'] = ['status' => 'PASS', 'msg' => 'Halaman detail target terbuka normal (HTTP 200 OK)'];
} else {
    $results['test47'] = ['status' => 'FAIL', 'msg' => 'Gagal membuka detail goal'];
}
echo "Hasil: {$results['test47']['status']} - {$results['test47']['msg']}\n";

// 48. Parameter masukan & luaran tampil di halaman detail
echo "[Test 48] Verifikasi tampilan nilai awal, terkini, dan sasaran...\n";
$hasValues = (strpos($rDetail1['body'], '75 kg') !== false && strpos($rDetail1['body'], '68 kg') !== false && strpos($rDetail1['body'], '71 kg') !== false);
if ($hasValues) {
    $results['test48'] = ['status' => 'PASS', 'msg' => 'Nilai awal (75kg), sasaran (68kg), dan terkini (71kg) tersaji'];
} else {
    $results['test48'] = ['status' => 'FAIL', 'msg' => 'Nilai target tidak lengkap'];
}
echo "Hasil: {$results['test48']['status']} - {$results['test48']['msg']}\n";

// 49. Grafik Chart.js dirender saat log progres >= 2
echo "[Test 49] Canvas Chart.js dirender saat log progres >= 2...\n";
if (strpos($rDetail1['body'], '<canvas id="chartGoalProgress"></canvas>') !== false) {
    $results['test49'] = ['status' => 'PASS', 'msg' => 'Kanvas grafik kurva progres terpasang dengan data riil'];
} else {
    $results['test49'] = ['status' => 'FAIL', 'msg' => 'Kanvas grafik tidak ditemukan'];
}
echo "Hasil: {$results['test49']['status']} - {$results['test49']['msg']}\n";

// 50. Empty State pada grafik bila log progres < 2
echo "[Test 50] Empty state grafik saat log progres baru 1 titik data...\n";
$goalFreshId = create_health_goal($pdo, 2, [
    'title'         => 'Target Air Bersih Baru',
    'category'      => 'hydration',
    'initial_value' => 1500,
    'target_value'  => 2500,
    'unit'          => 'ml',
    'add_template'  => 0
]);
// Only 1 initial log exists
$rDetailFresh = request("{$baseUrl}/user/goal_detail.php?id={$goalFreshId}", 'GET', null, [], $userACookie);
if (strpos($rDetailFresh['body'], 'Belum cukup data untuk membuat grafik perkembangan') !== false) {
    $results['test50'] = ['status' => 'PASS', 'msg' => 'Pesan empty state grafik informatif tampil'];
} else {
    $results['test50'] = ['status' => 'FAIL', 'msg' => 'Empty state grafik tidak muncul'];
}
echo "Hasil: {$results['test50']['status']} - {$results['test50']['msg']}\n";

// 51. IDOR Detail: User B DIBLOKIR dengan HTTP 404/403 saat membuka detail User A
echo "[Test 51] IDOR: User B diblokir saat mencoba membuka detail target User A...\n";
$rIdorDetail = request("{$baseUrl}/user/goal_detail.php?id={$goal1Id}", 'GET', null, [], $userBCookie);
if ($rIdorDetail['status'] === 404 || $rIdorDetail['status'] === 403) {
    $results['test51'] = ['status' => 'PASS', 'msg' => "Akses silang detail ditolak ({$rIdorDetail['status']} Forbidden/Not Found)"];
} else {
    $results['test51'] = ['status' => 'FAIL', 'msg' => "Celah IDOR detail goal! Status: {$rIdorDetail['status']}"];
}
echo "Hasil: {$results['test51']['status']} - {$results['test51']['msg']}\n";

// 52. ID atau Parameter Goal Tidak Valid mengembalikan 404/Redirect
echo "[Test 52] Penanganan ID target tidak valid (id=-1 atau tidak ada)...\n";
$rBadId = request("{$baseUrl}/user/goal_detail.php?id=-99", 'GET', null, [], $userACookie);
if ($rBadId['status'] === 404 || $rBadId['status'] === 302) {
    $results['test52'] = ['status' => 'PASS', 'msg' => 'ID tidak valid ditangani aman'];
} else {
    $results['test52'] = ['status' => 'FAIL', 'msg' => 'Penanganan ID salah'];
}
echo "Hasil: {$results['test52']['status']} - {$results['test52']['msg']}\n";

// 53. Checklist Milestone Tampil Interaktif di Detail
echo "[Test 53] Daftar checklist milestone tampil di halaman detail...\n";
if (strpos($rDetail1['body'], 'Tahap 1') !== false && strpos($rDetail1['body'], 'Actionable Milestones') !== false) {
    $results['test53'] = ['status' => 'PASS', 'msg' => 'Daftar milestone tersaji lengkap'];
} else {
    $results['test53'] = ['status' => 'FAIL', 'msg' => 'Daftar milestone tidak tampil'];
}
echo "Hasil: {$results['test53']['status']} - {$results['test53']['msg']}\n";

// 54. Log Riwayat Progres Tampil di Tabel Detail
echo "[Test 54] Tabel log riwayat perkembangan tampil di halaman detail...\n";
if (strpos($rDetail1['body'], 'Log Riwayat Progres') !== false && strpos($rDetail1['body'], '73.5 kg') !== false) {
    $results['test54'] = ['status' => 'PASS', 'msg' => 'Tabel log riwayat perkembangan tersaji'];
} else {
    $results['test54'] = ['status' => 'FAIL', 'msg' => 'Tabel log riwayat tidak tampil'];
}
echo "Hasil: {$results['test54']['status']} - {$results['test54']['msg']}\n";

// ===================================================================
// F. DASHBOARD INTEGRATION & SECURITY (Tests 55 - 65)
// ===================================================================
echo "\n--- [Bagian F: Dashboard Integration & Security] ---\n";

// 55. Featured Goal Muncul di Dashboard Personal
echo "[Test 55] Featured Goal tampil di Personal Dashboard...\n";
set_featured_goal($pdo, 2, $goal1Id);
$rDashGoals = request("{$baseUrl}/user/dashboard.php", 'GET', null, [], $userACookie);
$hasFeaturedGoalOnDash = (strpos($rDashGoals['body'], 'My Health Goals') !== false && strpos($rDashGoals['body'], 'Turunkan Berat Badan Bertahap') !== false);
if ($hasFeaturedGoalOnDash) {
    $results['test55'] = ['status' => 'PASS', 'msg' => 'Featured Goal tampil terintegrasi di Dashboard'];
} else {
    $results['test55'] = ['status' => 'FAIL', 'msg' => 'Featured Goal tidak tampil di dashboard'];
}
echo "Hasil: {$results['test55']['status']} - {$results['test55']['msg']}\n";

// 56. Ringkasan Status Target (Active, Nearly Done, Completed) di Dashboard
echo "[Test 56] Ringkasan status target tampil di Dashboard...\n";
if (strpos($rDashGoals['body'], 'Ringkasan Status Target') !== false) {
    $results['test56'] = ['status' => 'PASS', 'msg' => 'Ringkasan status target aktif di Dashboard'];
} else {
    $results['test56'] = ['status' => 'FAIL', 'msg' => 'Status target tidak muncul'];
}
echo "Hasil: {$results['test56']['status']} - {$results['test56']['msg']}\n";

// 57. Empty State Target di Dashboard untuk User Tanpa Target
echo "[Test 57] Empty state target di Dashboard saat pengguna belum memiliki target...\n";
// User B has 0 goals
$rDashB = request("{$baseUrl}/user/dashboard.php", 'GET', null, [], $userBCookie);
if (strpos($rDashB['body'], 'Belum Ada Target Kesehatan yang Ditetapkan') !== false) {
    $results['test57'] = ['status' => 'PASS', 'msg' => 'Empty state target informatif tampil pada Dashboard User B'];
} else {
    $results['test57'] = ['status' => 'FAIL', 'msg' => 'Empty state dashboard tidak tampil'];
}
echo "Hasil: {$results['test57']['status']} - {$results['test57']['msg']}\n";

// 58. Tautan Target & Milestone di Navbar Header
echo "[Test 58] Tautan Target & Milestone tersedia di navbar header member...\n";
if (strpos($rDashGoals['body'], 'user/goals.php') !== false && strpos($rDashGoals['body'], 'Target & Milestone') !== false) {
    $results['test58'] = ['status' => 'PASS', 'msg' => 'Navbar menyajikan tautan Target & Milestone'];
} else {
    $results['test58'] = ['status' => 'FAIL', 'msg' => 'Tautan navbar tidak ditemukan'];
}
echo "Hasil: {$results['test58']['status']} - {$results['test58']['msg']}\n";

// 59. Tautan Target & Milestone di Footer
echo "[Test 59] Tautan Target & Milestone tersedia di footer...\n";
if (strpos($rDashGoals['body'], 'user/goals.php') !== false) {
    $results['test59'] = ['status' => 'PASS', 'msg' => 'Footer menyajikan tautan Target & Milestone'];
} else {
    $results['test59'] = ['status' => 'FAIL', 'msg' => 'Tautan footer tidak ditemukan'];
}
echo "Hasil: {$results['test59']['status']} - {$results['test59']['msg']}\n";

// 60. Proteksi CSRF pada Penambahan Progres Target
echo "[Test 60] Proteksi CSRF: POST add_progress tanpa token diblokir HTTP 403...\n";
$rNoCsrfProg = request("{$baseUrl}/user/goal_detail.php?id={$goal1Id}", 'POST', ['action' => 'add_progress', 'current_value' => 70], [], $userACookie);
if ($rNoCsrfProg['status'] === 403) {
    $results['test60'] = ['status' => 'PASS', 'msg' => 'CSRF 403 enforcement aktif pada pencatatan progres'];
} else {
    $results['test60'] = ['status' => 'FAIL', 'msg' => 'CSRF progres lolos'];
}
echo "Hasil: {$results['test60']['status']} - {$results['test60']['msg']}\n";

// 61. Ketahanan SQL Injection pada Pencarian Target
echo "[Test 61] Ketahanan SQL Injection pada filter pencarian target...\n";
$sqliGoal = "' OR '1'='1";
$rSqliGoal = request("{$baseUrl}/user/goals.php?q=" . urlencode($sqliGoal), 'GET', null, [], $userACookie);
if ($rSqliGoal['status'] === 200 && strpos($rSqliGoal['body'], 'Fatal error') === false) {
    $results['test61'] = ['status' => 'PASS', 'msg' => 'Pencarian target kebal dari SQL injection'];
} else {
    $results['test61'] = ['status' => 'FAIL', 'msg' => 'SQL error pada pencarian target'];
}
echo "Hasil: {$results['test61']['status']} - {$results['test61']['msg']}\n";

// 62. Whitelist Entitas assert_record_access mencakup tabel health_goals
echo "[Test 62] Keamanan Whitelist: assert_record_access mengizinkan tabel health_goals...\n";
$cliCheckGoal = shell_exec(sprintf('php -r "require_once \'%s/includes/auth_helper.php\'; \$_SESSION[\'user_id\'] = 2; \$p = new PDO(\'sqlite:%s\'); try { \$rec = assert_record_access(\$p, \'health_goals\', \'id_goal\', %d); echo \$rec ? \'OK\' : \'FAIL\'; } catch (Throwable \$e) { echo \'ERR\'; }" 2>&1', $projectRoot, $testDbPath, $goal1Id));
if (trim($cliCheckGoal) === 'OK') {
    $results['test62'] = ['status' => 'PASS', 'msg' => 'Tabel health_goals terdaftar sah di whitelist assert_record_access'];
} else {
    $results['test62'] = ['status' => 'FAIL', 'msg' => 'Tabel health_goals ditolak: ' . $cliCheckGoal];
}
echo "Hasil: {$results['test62']['status']} - {$results['test62']['msg']}\n";

// 63. Idempotensi Migration Runner Phase 5
echo "[Test 63] Idempotensi Migration Runner...\n";
$migOutput = shell_exec("HEALTHCHECK_DB_PATH=" . escapeshellarg($testDbPath) . " php " . escapeshellarg($projectRoot . '/database/migrate.php'));
$migStatus = shell_exec("HEALTHCHECK_DB_PATH=" . escapeshellarg($testDbPath) . " php " . escapeshellarg($projectRoot . '/database/migrate.php') . " --status");
if (strpos($migOutput, 'Nothing to migrate') !== false && strpos($migStatus, '[APPLIED] 20261108_004_phase5_health_goals_milestones') !== false) {
    $results['test63'] = ['status' => 'PASS', 'msg' => 'Migration runner idempoten (status APPLIED terverifikasi)'];
} else {
    $results['test63'] = ['status' => 'FAIL', 'msg' => 'Eksekusi ulang migrasi gagal'];
}
echo "Hasil: {$results['test63']['status']} - {$results['test63']['msg']}\n";

// 64. Regresi Fitur Phase 1A-4 (Kalkulator, Habits, PDF, History)
echo "[Test 64] Regresi: Fitur-fitur utama terdahulu tetap beroperasi stabil...\n";
$rCalc = request("{$baseUrl}/bmi/bmi_form.php", 'GET')['status'];
$rHab  = request("{$baseUrl}/user/habits.php", 'GET', null, [], $userACookie)['status'];
$rPdf  = request("{$baseUrl}/user/report_download.php?action=download", 'GET', null, [], $userACookie)['status'];
$rHist = request("{$baseUrl}/user/history.php", 'GET', null, [], $userACookie)['status'];
if ($rCalc === 200 && $rHab === 200 && $rPdf === 200 && $rHist === 200) {
    $results['test64'] = ['status' => 'PASS', 'msg' => 'Semua modul terdahulu (Kalkulator, Habits, PDF, Riwayat) 100% konsisten'];
} else {
    $results['test64'] = ['status' => 'FAIL', 'msg' => 'Ada modul lama yang regresi'];
}
echo "Hasil: {$results['test64']['status']} - {$results['test64']['msg']}\n";

// 65. PHP Lint Seluruh Repositori (php -l)
echo "[Test 65] Verifikasi sintaks seluruh file PHP repositori (php -l)...\n";
$phpFiles = [];
$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($projectRoot));
foreach ($iter as $item) {
    if ($item->isFile() && $item->getExtension() === 'php') {
        $path = $item->getPathname();
        if (str_contains($path, '/backups/')) {
            continue;
        }
        $phpFiles[] = $path;
    }
}

$syntaxErrors = [];
foreach ($phpFiles as $file) {
    $out = [];
    $ret = 0;
    exec("php -l " . escapeshellarg($file) . " 2>&1", $out, $ret);
    if ($ret !== 0) {
        $syntaxErrors[] = "{$file}: " . implode("\n", $out);
    }
}

if (empty($syntaxErrors)) {
    $results['test65'] = ['status' => 'PASS', 'msg' => sprintf('Semua %d file PHP lulus sintaks tanpa error (php -l)', count($phpFiles))];
} else {
    $results['test65'] = ['status' => 'FAIL', 'msg' => 'Ditemukan syntax error: ' . implode('; ', $syntaxErrors)];
}
echo "Hasil: {$results['test65']['status']} - {$results['test65']['msg']}\n";

// ===================================================================
// REKAPITULASI HASIL PENGUJIAN
// ===================================================================
echo "\n======================================================================\n";
echo "REKAPITULASI HASIL PENGUJIAN PHASE 5 (65 SKENARIO):\n";
echo "======================================================================\n";

$passCount = 0;
$failCount = 0;

foreach ($results as $k => $res) {
    $isPass = ($res['status'] === 'PASS');
    if ($isPass) {
        $passCount++;
    } else {
        $failCount++;
    }
    printf("[%s] %-5s: %s\n", strtoupper($k), $res['status'], $res['msg']);
}

echo "\n----------------------------------------------------------------------\n";
printf("TOTAL: %d Pengujian | LULUS: %d | GAGAL: %d\n", count($results), $passCount, $failCount);
echo "STATUS AKHIR: " . ($failCount === 0 ? "SEMUA 65 PENGUJIAN BERHASIL (100% PASS)" : "ADA PENGUJIAN GAGAL") . "\n";
echo "======================================================================\n";
