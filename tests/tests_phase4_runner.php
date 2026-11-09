<?php
/**
 * Automated Test Suite for HealthCheck Phase 4:
 * Health Habits Tracker, Daily Check-in, Weekly Summary, Health Reminders & Notifications
 *
 * Runs 65 comprehensive test cases against an isolated SQLite test database.
 */

$host = '127.0.0.1';
$port = 8099;
$baseUrl = "http://{$host}:{$port}";

echo "======================================================================\n";
echo "=== Memulai HealthCheck Phase 4 Comprehensive Test Suite (65 Tests) ===\n";
echo "======================================================================\n";

// Setup isolated test database
$projectRoot = dirname(__DIR__);
$mainDbPath = $projectRoot . '/database/healthcheck.sqlite';
$testDbPath = $projectRoot . '/database/healthcheck_test_phase4.sqlite';

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
$cmd = sprintf('HEALTHCHECK_DB_PATH=%s php -S %s:%d -t "%s" > /tmp/php_phase4_test.log 2>&1 & echo $!',
    escapeshellarg($testDbPath), $host, $port, $projectRoot
);
$pid = (int)shell_exec($cmd);
echo "Server test Phase 4 aktif (PID: {$pid}) pada {$baseUrl}\n";

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

require_once $projectRoot . '/includes/habit_helper.php';
require_once $projectRoot . '/includes/auth_helper.php';

// Prepare User A (id=2) and User B (id=3)
$guestCookie = $tmpCookieDir . '/guest_p4_cookie.txt';
if (file_exists($guestCookie)) unlink($guestCookie);

$userACookie = $tmpCookieDir . '/usera_p4_cookie.txt';
loginUser($baseUrl, $userACookie, 'user', 'user123');

$userBId = fetchVal($pdo, "SELECT id FROM user WHERE username = 'user_b'");
if (!$userBId) {
    $hashB = password_hash('pass123', PASSWORD_DEFAULT);
    executeSql($pdo, "INSERT INTO user (username, password, role) VALUES ('user_b', ?, 'user')", [$hashB]);
    $userBId = (int)$pdo->lastInsertId();
} else {
    $userBId = (int)$userBId;
}

$userBCookie = $tmpCookieDir . '/userb_p4_cookie.txt';
loginUser($baseUrl, $userBCookie, 'user_b', 'pass123');

// Clean test data for User A & User B in phase 4 tables
executeSql($pdo, "DELETE FROM habit_logs WHERE user_id IN (2, ?)", [$userBId]);
executeSql($pdo, "DELETE FROM daily_checkins WHERE user_id IN (2, ?)", [$userBId]);
executeSql($pdo, "DELETE FROM notification_logs WHERE user_id IN (2, ?)", [$userBId]);
executeSql($pdo, "DELETE FROM health_reminders WHERE user_id IN (2, ?)", [$userBId]);
executeSql($pdo, "DELETE FROM health_habits WHERE user_id IN (2, ?)", [$userBId]);

// ===================================================================
// A. HEALTH HABITS TRACKER TESTS (Tests 1 - 15)
// ===================================================================
echo "\n--- [Bagian A: Health Habits Tracker] ---\n";

// 1. Guest dilarang mengakses habits.php
echo "[Test 1] Akses guest ke habits.php dialihkan ke login...\n";
$rGuestHabits = request("{$baseUrl}/user/habits.php", 'GET', null, [], $guestCookie);
if ($rGuestHabits['status'] === 302 && strpos($rGuestHabits['headers'], 'belum_login') !== false) {
    $results['test1'] = ['status' => 'PASS', 'msg' => 'Tamu dialihkan ke login (HTTP 302 belum_login)'];
} else {
    $results['test1'] = ['status' => 'FAIL', 'msg' => "Status bukan 302: {$rGuestHabits['status']}"];
}
echo "Hasil: {$results['test1']['status']} - {$results['test1']['msg']}\n";

// 2. Member membuka habits.php dan auto-inits 4 foundational habits
echo "[Test 2] Member membuka habits.php & auto-inisialisasi 4 kebiasaan standar...\n";
$rUserHabits = request("{$baseUrl}/user/habits.php", 'GET', null, [], $userACookie);
$habitsCountA = (int)fetchVal($pdo, "SELECT count(*) FROM health_habits WHERE user_id = 2");
if ($rUserHabits['status'] === 200 && $habitsCountA === 4 && strpos($rUserHabits['body'], 'Health Habits Tracker') !== false) {
    $results['test2'] = ['status' => 'PASS', 'msg' => '4 kebiasaan standar (Air, Tidur, Langkah, Olahraga) berhasil diinisialisasi'];
} else {
    $results['test2'] = ['status' => 'FAIL', 'msg' => "Habits gagal diinisialisasi: count={$habitsCountA}"];
}
echo "Hasil: {$results['test2']['status']} - {$results['test2']['msg']}\n";

// 3. Verifikasi rincian 4 habit standar (Air, Tidur, Langkah, Olahraga)
echo "[Test 3] Verifikasi parameter 4 habit standar...\n";
$waterHabit = fetchRow($pdo, "SELECT * FROM health_habits WHERE user_id = 2 AND code = 'water'");
$sleepHabit = fetchRow($pdo, "SELECT * FROM health_habits WHERE user_id = 2 AND code = 'sleep'");
$stepsHabit = fetchRow($pdo, "SELECT * FROM health_habits WHERE user_id = 2 AND code = 'steps'");
$actHabit   = fetchRow($pdo, "SELECT * FROM health_habits WHERE user_id = 2 AND code = 'activity'");
if ($waterHabit['target_harian'] == 2000 && $sleepHabit['target_harian'] == 8 && $stepsHabit['target_harian'] == 8000 && $actHabit['target_harian'] == 30) {
    $results['test3'] = ['status' => 'PASS', 'msg' => 'Target default (2000 ml, 8 jam, 8000 langkah, 30 menit) akurat'];
} else {
    $results['test3'] = ['status' => 'FAIL', 'msg' => 'Target default tidak sesuai'];
}
echo "Hasil: {$results['test3']['status']} - {$results['test3']['msg']}\n";

// 4. Pencatatan Progres Aktual Harian (Log Habit)
echo "[Test 4] Pencatatan nilai aktual kebiasaan harian (POST log_habit)...\n";
$csrfHabits = extractCsrf($rUserHabits['body']);
$today = date('Y-m-d');
$rLogWater = request("{$baseUrl}/user/habits.php", 'POST', [
    'action'       => 'log_habit',
    'habit_id'     => $waterHabit['id_habit'],
    'nilai_aktual' => 1500,
    'tanggal'      => $today,
    'csrf_token'   => $csrfHabits
], [], $userACookie);
$waterLogVal = (float)fetchVal($pdo, "SELECT nilai_aktual FROM habit_logs WHERE habit_id = ? AND tanggal = ?", [$waterHabit['id_habit'], $today]);
if ($rLogWater['status'] === 302 && $waterLogVal == 1500) {
    $results['test4'] = ['status' => 'PASS', 'msg' => 'Progres air (1500 ml) berhasil dicatat ke database'];
} else {
    $results['test4'] = ['status' => 'FAIL', 'msg' => "Log gagal dicatat: val={$waterLogVal}"];
}
echo "Hasil: {$results['test4']['status']} - {$results['test4']['msg']}\n";

// 5. Update Log pada Hari yang Sama (Mencegah Duplikasi / Idempoten Upsert)
echo "[Test 5] Update pencatatan pada hari yang sama (Mencegah duplikasi log)...\n";
$rLogWaterUpdate = request("{$baseUrl}/user/habits.php", 'POST', [
    'action'       => 'log_habit',
    'habit_id'     => $waterHabit['id_habit'],
    'nilai_aktual' => 2200,
    'tanggal'      => $today,
    'csrf_token'   => $csrfHabits
], [], $userACookie);
$logCountForToday = (int)fetchVal($pdo, "SELECT count(*) FROM habit_logs WHERE habit_id = ? AND tanggal = ?", [$waterHabit['id_habit'], $today]);
$updatedWaterVal  = (float)fetchVal($pdo, "SELECT nilai_aktual FROM habit_logs WHERE habit_id = ? AND tanggal = ?", [$waterHabit['id_habit'], $today]);
if ($logCountForToday === 1 && $updatedWaterVal == 2200) {
    $results['test5'] = ['status' => 'PASS', 'msg' => 'Log di-update idempoten tanpa duplikasi (count=1, val=2200 ml)'];
} else {
    $results['test5'] = ['status' => 'FAIL', 'msg' => "Duplikasi terdeteksi: count={$logCountForToday}"];
}
echo "Hasil: {$results['test5']['status']} - {$results['test5']['msg']}\n";

// 6. Target Tercapai Flag (is_target_tercapai = 1 saat actual >= target)
echo "[Test 6] Evaluasi flag target tercapai (is_target_tercapai)...\n";
$isAchieved = (int)fetchVal($pdo, "SELECT is_target_tercapai FROM habit_logs WHERE habit_id = ? AND tanggal = ?", [$waterHabit['id_habit'], $today]);
if ($isAchieved === 1) {
    $results['test6'] = ['status' => 'PASS', 'msg' => 'is_target_tercapai = 1 saat aktual (2200) >= target (2000)'];
} else {
    $results['test6'] = ['status' => 'FAIL', 'msg' => 'Target tercapai gagal dihitung'];
}
echo "Hasil: {$results['test6']['status']} - {$results['test6']['msg']}\n";

// 7. Non-punitive Progress Calculation (Persentase capaian realistis, max 100%)
echo "[Test 7] Kalkulasi progres positif (Non-punitive progression)...\n";
$dailyStatus = get_user_daily_habit_status($pdo, 2, $today);
$waterStatus = null;
foreach ($dailyStatus as $ds) {
    if ($ds['code'] === 'water') $waterStatus = $ds;
}
if ($waterStatus && $waterStatus['progress_percent'] == 100.0 && $waterStatus['is_target_tercapai'] === 1) {
    $results['test7'] = ['status' => 'PASS', 'msg' => 'Progres tercatat 100% dan status tercapai rapi'];
} else {
    $results['test7'] = ['status' => 'FAIL', 'msg' => 'Progres persen salah'];
}
echo "Hasil: {$results['test7']['status']} - {$results['test7']['msg']}\n";

// 8. Membuat Kebiasaan Personal Baru (Custom Personal Habit)
echo "[Test 8] Menambah kebiasaan personal baru (create_habit)...\n";
$rAddCustom = request("{$baseUrl}/user/habits.php", 'POST', [
    'action'        => 'create_habit',
    'nama_habit'    => 'Membaca Jurnal Ilmiah',
    'deskripsi'     => 'Literasi kesehatan sebelum tidur',
    'satuan'        => 'halaman',
    'target_harian' => 15,
    'warna'         => 'lavender',
    'ikon'          => 'bi-book',
    'csrf_token'    => $csrfHabits
], [], $userACookie);
$customHabitId = (int)fetchVal($pdo, "SELECT id_habit FROM health_habits WHERE user_id = 2 AND nama_habit = 'Membaca Jurnal Ilmiah'");
if ($rAddCustom['status'] === 302 && $customHabitId > 0) {
    $results['test8'] = ['status' => 'PASS', 'msg' => "Habit personal baru ID #{$customHabitId} berhasil dibuat"];
} else {
    $results['test8'] = ['status' => 'FAIL', 'msg' => 'Gagal menambah habit personal'];
}
echo "Hasil: {$results['test8']['status']} - {$results['test8']['msg']}\n";

// 9. Mengubah Target & Data Kebiasaan (update_habit)
echo "[Test 9] Mengubah target kebiasaan personal (update_habit)...\n";
$rUpdCustom = request("{$baseUrl}/user/habits.php", 'POST', [
    'action'        => 'update_habit',
    'habit_id'      => $customHabitId,
    'nama_habit'    => 'Membaca Jurnal Medis',
    'target_harian' => 20,
    'is_active'     => 1,
    'csrf_token'    => $csrfHabits
], [], $userACookie);
$updTarget = (float)fetchVal($pdo, "SELECT target_harian FROM health_habits WHERE id_habit = ?", [$customHabitId]);
$updName   = fetchVal($pdo, "SELECT nama_habit FROM health_habits WHERE id_habit = ?", [$customHabitId]);
if ($rUpdCustom['status'] === 302 && $updTarget == 20 && $updName === 'Membaca Jurnal Medis') {
    $results['test9'] = ['status' => 'PASS', 'msg' => 'Target diperbarui menjadi 20 halaman, nama diperbarui'];
} else {
    $results['test9'] = ['status' => 'FAIL', 'msg' => 'Update habit gagal'];
}
echo "Hasil: {$results['test9']['status']} - {$results['test9']['msg']}\n";

// 10. Menonaktifkan Kebiasaan (Toggle is_active = 0)
echo "[Test 10] Menonaktifkan kebiasaan (is_active = 0)...\n";
$rDeactivate = request("{$baseUrl}/user/habits.php", 'POST', [
    'action'        => 'update_habit',
    'habit_id'      => $customHabitId,
    'target_harian' => 20,
    // without is_active parameter -> sets to 0
    'csrf_token'    => $csrfHabits
], [], $userACookie);
$isActiveVal = (int)fetchVal($pdo, "SELECT is_active FROM health_habits WHERE id_habit = ?", [$customHabitId]);
if ($rDeactivate['status'] === 302 && $isActiveVal === 0) {
    $results['test10'] = ['status' => 'PASS', 'msg' => 'Kebiasaan berhasil dinonaktifkan (is_active = 0)'];
} else {
    $results['test10'] = ['status' => 'FAIL', 'msg' => 'Gagal menonaktifkan habit'];
}
echo "Hasil: {$results['test10']['status']} - {$results['test10']['msg']}\n";

// 11. Menghapus Kebiasaan Personal (delete_habit)
echo "[Test 11] Menghapus kebiasaan personal (delete_habit)...\n";
$rDelCustom = request("{$baseUrl}/user/habits.php", 'POST', [
    'action'     => 'delete_habit',
    'habit_id'   => $customHabitId,
    'csrf_token' => $csrfHabits
], [], $userACookie);
$existsCustom = fetchVal($pdo, "SELECT count(*) FROM health_habits WHERE id_habit = ?", [$customHabitId]);
if ($rDelCustom['status'] === 302 && $existsCustom == 0) {
    $results['test11'] = ['status' => 'PASS', 'msg' => 'Kebiasaan personal berhasil dihapus dari database'];
} else {
    $results['test11'] = ['status' => 'FAIL', 'msg' => 'Gagal menghapus habit'];
}
echo "Hasil: {$results['test11']['status']} - {$results['test11']['msg']}\n";

// 12. Kebiasaan Bawaan Sistem DILARANG Dihapus
echo "[Test 12] Penolakan penghapusan kebiasaan bawaan sistem (System habit protected)...\n";
$rDelSystem = request("{$baseUrl}/user/habits.php", 'POST', [
    'action'     => 'delete_habit',
    'habit_id'   => $waterHabit['id_habit'],
    'csrf_token' => $csrfHabits
], [], $userACookie);
$waterStillExists = (int)fetchVal($pdo, "SELECT count(*) FROM health_habits WHERE id_habit = ?", [$waterHabit['id_habit']]);
if ($waterStillExists === 1) {
    $results['test12'] = ['status' => 'PASS', 'msg' => 'Kebiasaan sistem kebal dari penghapusan (hanya dapat dinonaktifkan)'];
} else {
    $results['test12'] = ['status' => 'FAIL', 'msg' => 'Kebiasaan sistem terhapus!'];
}
echo "Hasil: {$results['test12']['status']} - {$results['test12']['msg']}\n";

// 13. Logging tanggal sebelumnya (Historical Log Date)
echo "[Test 13] Pencatatan kebiasaan pada tanggal kemarin (Historical date logging)...\n";
$yesterday = date('Y-m-d', strtotime('-1 day'));
$rLogYesterday = request("{$baseUrl}/user/habits.php", 'POST', [
    'action'       => 'log_habit',
    'habit_id'     => $waterHabit['id_habit'],
    'nilai_aktual' => 1800,
    'tanggal'      => $yesterday,
    'csrf_token'   => $csrfHabits
], [], $userACookie);
$yesterdayWater = (float)fetchVal($pdo, "SELECT nilai_aktual FROM habit_logs WHERE habit_id = ? AND tanggal = ?", [$waterHabit['id_habit'], $yesterday]);
if ($yesterdayWater == 1800) {
    $results['test13'] = ['status' => 'PASS', 'msg' => "Log tanggal {$yesterday} tersimpan mandiri (1800 ml)"];
} else {
    $results['test13'] = ['status' => 'FAIL', 'msg' => 'Log historis gagal'];
}
echo "Hasil: {$results['test13']['status']} - {$results['test13']['msg']}\n";

// 14. Isolasi Data Habit: User B dilarang membaca atau mengedit habit User A
echo "[Test 14] IDOR: User B dilarang mengubah kebiasaan User A...\n";
$rIdorHabit = request("{$baseUrl}/user/habits.php", 'POST', [
    'action'        => 'update_habit',
    'habit_id'      => $waterHabit['id_habit'], // User A's habit
    'target_harian' => 9999,
    'is_active'     => 1,
    'csrf_token'    => extractCsrf(request("{$baseUrl}/user/habits.php", 'GET', null, [], $userBCookie)['body'])
], [], $userBCookie);
$targetWaterA = (float)fetchVal($pdo, "SELECT target_harian FROM health_habits WHERE id_habit = ?", [$waterHabit['id_habit']]);
if ($targetWaterA == 2000) {
    $results['test14'] = ['status' => 'PASS', 'msg' => 'Akses silang habit ditolak, target User A tetap utuh (2000 ml)'];
} else {
    $results['test14'] = ['status' => 'FAIL', 'msg' => 'Celah IDOR pada update habit!'];
}
echo "Hasil: {$results['test14']['status']} - {$results['test14']['msg']}\n";

// 15. User B memiliki daftar kebiasaan mandiri terisolasi
echo "[Test 15] Isolasi akun: User B memiliki 4 kebiasaan independen...\n";
request("{$baseUrl}/user/habits.php", 'GET', null, [], $userBCookie);
$habitsCountB = (int)fetchVal($pdo, "SELECT count(*) FROM health_habits WHERE user_id = ?", [$userBId]);
if ($habitsCountB === 4) {
    $results['test15'] = ['status' => 'PASS', 'msg' => 'Kebiasaan User B terisolasi penuh dari User A'];
} else {
    $results['test15'] = ['status' => 'FAIL', 'msg' => 'Inisialisasi User B gagal'];
}
echo "Hasil: {$results['test15']['status']} - {$results['test15']['msg']}\n";

// ===================================================================
// B. DAILY HEALTH CHECK-IN TESTS (Tests 16 - 28)
// ===================================================================
echo "\n--- [Bagian B: Daily Health Check-in] ---\n";

// 16. Guest dilarang mengakses checkin.php
echo "[Test 16] Akses guest ke checkin.php diarahkan ke login...\n";
$rGuestCheckin = request("{$baseUrl}/user/checkin.php", 'GET', null, [], $guestCookie);
if ($rGuestCheckin['status'] === 302 && strpos($rGuestCheckin['headers'], 'belum_login') !== false) {
    $results['test16'] = ['status' => 'PASS', 'msg' => 'Tamu diblokir ke login (HTTP 302 belum_login)'];
} else {
    $results['test16'] = ['status' => 'FAIL', 'msg' => 'Tamu dapat membuka check-in'];
}
echo "Hasil: {$results['test16']['status']} - {$results['test16']['msg']}\n";

// 17. Member dapat membuka checkin.php
echo "[Test 17] Member membuka formulir check-in hari ini...\n";
$rUserCheckin = request("{$baseUrl}/user/checkin.php", 'GET', null, [], $userACookie);
if ($rUserCheckin['status'] === 200 && strpos($rUserCheckin['body'], 'Daily Health Check-in') !== false) {
    $results['test17'] = ['status' => 'PASS', 'msg' => 'Formulir Daily Check-in terbuka (HTTP 200 OK)'];
} else {
    $results['test17'] = ['status' => 'FAIL', 'msg' => 'Gagal membuka check-in'];
}
echo "Hasil: {$results['test17']['status']} - {$results['test17']['msg']}\n";

// 18. Pengiriman Data Check-in Valid
echo "[Test 18] Pengiriman check-in harian lengkap (POST check-in)...\n";
$csrfCheckin = extractCsrf($rUserCheckin['body']);
$rPostCheckin = request("{$baseUrl}/user/checkin.php", 'POST', [
    'tanggal'          => $today,
    'water_ml'         => 2400,
    'sleep_hours'      => 7.5,
    'steps_count'      => 9200,
    'activity_type'    => 'Lari / Jogging',
    'activity_minutes' => 35,
    'kondisi_energi'   => 'sangat_bugar',
    'catatan'          => 'Lari pagi di taman kota',
    'csrf_token'       => $csrfCheckin
], [], $userACookie);
$checkinRow = fetchRow($pdo, "SELECT * FROM daily_checkins WHERE user_id = 2 AND tanggal = ?", [$today]);
if ($rPostCheckin['status'] === 302 && $checkinRow && $checkinRow['steps_count'] == 9200 && $checkinRow['water_ml'] == 2400) {
    $results['test18'] = ['status' => 'PASS', 'msg' => 'Check-in harian tersimpan utuh di tabel daily_checkins'];
} else {
    $results['test18'] = ['status' => 'FAIL', 'msg' => 'Penyimpanan check-in gagal'];
}
echo "Hasil: {$results['test18']['status']} - {$results['test18']['msg']}\n";

// 19. Sinkronisasi Otomatis Check-in ke Log Habit Tracker
echo "[Test 19] Sinkronisasi otomatis nilai check-in ke habit_logs...\n";
$syncedWater = (float)fetchVal($pdo, "SELECT nilai_aktual FROM habit_logs WHERE habit_id = ? AND tanggal = ?", [$waterHabit['id_habit'], $today]);
$syncedSleep = (float)fetchVal($pdo, "SELECT nilai_aktual FROM habit_logs WHERE habit_id = ? AND tanggal = ?", [$sleepHabit['id_habit'], $today]);
$syncedSteps = (float)fetchVal($pdo, "SELECT nilai_aktual FROM habit_logs WHERE habit_id = ? AND tanggal = ?", [$stepsHabit['id_habit'], $today]);
$syncedAct   = (float)fetchVal($pdo, "SELECT nilai_aktual FROM habit_logs WHERE habit_id = ? AND tanggal = ?", [$actHabit['id_habit'], $today]);

if ($syncedWater == 2400 && $syncedSleep == 7.5 && $syncedSteps == 9200 && $syncedAct == 35) {
    $results['test19'] = ['status' => 'PASS', 'msg' => 'Semua 4 habit tersinkronisasi presisi (Air: 2400, Tidur: 7.5, Langkah: 9200, Olahraga: 35)'];
} else {
    $results['test19'] = ['status' => 'FAIL', 'msg' => "Sinkronisasi tidak lengkap: W={$syncedWater}, S={$syncedSleep}, St={$syncedSteps}, A={$syncedAct}"];
}
echo "Hasil: {$results['test19']['status']} - {$results['test19']['msg']}\n";

// 20. Update Check-in pada Tanggal yang Sama
echo "[Test 20] Memperbarui check-in pada hari yang sama (Upsert check-in)...\n";
$rPostUpdateCheckin = request("{$baseUrl}/user/checkin.php", 'POST', [
    'tanggal'          => $today,
    'water_ml'         => 2800, // +400ml
    'sleep_hours'      => 7.5,
    'steps_count'      => 10500, // +1300 steps
    'activity_type'    => 'Lari / Jogging',
    'activity_minutes' => 45,
    'kondisi_energi'   => 'baik',
    'catatan'          => 'Tambah jalan santai sore',
    'csrf_token'       => $csrfCheckin
], [], $userACookie);
$checkinCountToday = (int)fetchVal($pdo, "SELECT count(*) FROM daily_checkins WHERE user_id = 2 AND tanggal = ?", [$today]);
$updCheckinRow = fetchRow($pdo, "SELECT * FROM daily_checkins WHERE user_id = 2 AND tanggal = ?", [$today]);
if ($checkinCountToday === 1 && $updCheckinRow['water_ml'] == 2800 && $updCheckinRow['steps_count'] == 10500) {
    $results['test20'] = ['status' => 'PASS', 'msg' => 'Check-in berhasil di-update tanpa duplikasi baris'];
} else {
    $results['test20'] = ['status' => 'FAIL', 'msg' => 'Update check-in gagal'];
}
echo "Hasil: {$results['test20']['status']} - {$results['test20']['msg']}\n";

// 21. Field Opsional Tidak Boleh Dipaksa Menjadi 0 / Rusak Saat Dikosongkan
echo "[Test 21] Penanganan field opsional (Nullable preserved)...\n";
$rPartialCheckin = request("{$baseUrl}/user/checkin.php", 'POST', [
    'tanggal'          => $yesterday,
    'water_ml'         => 1800,
    'sleep_hours'      => 8.0,
    'steps_count'      => '', // Kosong
    'activity_type'    => '', // Kosong
    'activity_minutes' => '', // Kosong
    'kondisi_energi'   => '', // Kosong
    'catatan'          => '', // Kosong
    'csrf_token'       => $csrfCheckin
], [], $userACookie);
$partRow = fetchRow($pdo, "SELECT * FROM daily_checkins WHERE user_id = 2 AND tanggal = ?", [$yesterday]);
if ($partRow['water_ml'] == 1800 && $partRow['sleep_hours'] == 8.0 && $partRow['steps_count'] === null && $partRow['activity_minutes'] === null) {
    $results['test21'] = ['status' => 'PASS', 'msg' => 'Field kosong tersimpan sebagai NULL (bukan nol paksa)'];
} else {
    $results['test21'] = ['status' => 'FAIL', 'msg' => 'Field opsional salah'];
}
echo "Hasil: {$results['test21']['status']} - {$results['test21']['msg']}\n";

// 22. Ringkasan Status Check-in Ditampilkan Saat Form Dibuka Kembali
echo "[Test 22] Ringkasan data check-in sebelumnya tampil pada halaman checkin.php...\n";
$rViewCheckin = request("{$baseUrl}/user/checkin.php?date={$today}", 'GET', null, [], $userACookie);
$hasSavedBanner = (strpos($rViewCheckin['body'], 'Status Check-in Tanggal Ini') !== false && strpos($rViewCheckin['body'], '2800 ml') !== false);
if ($hasSavedBanner) {
    $results['test22'] = ['status' => 'PASS', 'msg' => 'Ringkasan data tersimpan tampil informatif'];
} else {
    $results['test22'] = ['status' => 'FAIL', 'msg' => 'Ringkasan check-in tidak tampil'];
}
echo "Hasil: {$results['test22']['status']} - {$results['test22']['msg']}\n";

// 23. Validasi Batas Durasi Tidur (max 24 jam)
echo "[Test 23] Validasi batas nilai tidur (Maksimal 24 jam)...\n";
save_daily_checkin($pdo, 2, '2026-11-01', ['sleep_hours' => 35]); // input 35 jam
$clampedSleep = (float)fetchVal($pdo, "SELECT sleep_hours FROM daily_checkins WHERE user_id = 2 AND tanggal = '2026-11-01'");
if ($clampedSleep == 24.0) {
    $results['test23'] = ['status' => 'PASS', 'msg' => 'Durasi tidur dibatasi maksimal 24 jam'];
} else {
    $results['test23'] = ['status' => 'FAIL', 'msg' => "Tidur tidak dibatasi: {$clampedSleep}"];
}
echo "Hasil: {$results['test23']['status']} - {$results['test23']['msg']}\n";

// 24. Validasi Nilai Negatif Ditolak / Diclamp ke 0
echo "[Test 24] Validasi nilai negatif pada air dan langkah diclamp ke 0...\n";
save_daily_checkin($pdo, 2, '2026-11-02', ['water_ml' => -500, 'steps_count' => -100]);
$clampedRow = fetchRow($pdo, "SELECT water_ml, steps_count FROM daily_checkins WHERE user_id = 2 AND tanggal = '2026-11-02'");
if ($clampedRow['water_ml'] == 0 && $clampedRow['steps_count'] == 0) {
    $results['test24'] = ['status' => 'PASS', 'msg' => 'Nilai negatif aman diclamp ke 0'];
} else {
    $results['test24'] = ['status' => 'FAIL', 'msg' => 'Nilai negatif lolos'];
}
echo "Hasil: {$results['test24']['status']} - {$results['test24']['msg']}\n";

// 25. Switcher Tanggal Check-in
echo "[Test 25] Navigasi switcher tanggal pada formulir check-in...\n";
$rSwitchCheckin = request("{$baseUrl}/user/checkin.php?date={$yesterday}", 'GET', null, [], $userACookie);
if ($rSwitchCheckin['status'] === 200 && strpos($rSwitchCheckin['body'], date('d M Y', strtotime($yesterday))) !== false) {
    $results['test25'] = ['status' => 'PASS', 'msg' => 'Switcher tanggal membuka riwayat tanggal terkait'];
} else {
    $results['test25'] = ['status' => 'FAIL', 'msg' => 'Switcher tanggal gagal'];
}
echo "Hasil: {$results['test25']['status']} - {$results['test25']['msg']}\n";

// 26. IDOR Check-in: User B tidak dapat membaca check-in User A
echo "[Test 26] IDOR: Data checkin User A tidak tampil pada User B...\n";
$rCheckinB = request("{$baseUrl}/user/checkin.php?date={$today}", 'GET', null, [], $userBCookie);
$leakedInB = (strpos($rCheckinB['body'], '10500') !== false || strpos($rCheckinB['body'], '2800 ml') !== false);
if (!$leakedInB) {
    $results['test26'] = ['status' => 'PASS', 'msg' => 'Check-in User A terisolasi penuh dari User B'];
} else {
    $results['test26'] = ['status' => 'FAIL', 'msg' => 'Kebocoran data check-in terdeteksi!'];
}
echo "Hasil: {$results['test26']['status']} - {$results['test26']['msg']}\n";

// 27. CSRF Protection pada Check-in
echo "[Test 27] Proteksi CSRF: POST check-in tanpa token diblokir HTTP 403...\n";
$rNoCsrfCheckin = request("{$baseUrl}/user/checkin.php", 'POST', ['tanggal' => $today, 'water_ml' => 2000], [], $userACookie);
if ($rNoCsrfCheckin['status'] === 403) {
    $results['test27'] = ['status' => 'PASS', 'msg' => 'CSRF 403 enforcement aktif pada Daily Check-in'];
} else {
    $results['test27'] = ['status' => 'FAIL', 'msg' => 'CSRF check-in lolos'];
}
echo "Hasil: {$results['test27']['status']} - {$results['test27']['msg']}\n";

// 28. Status Check-in di Dashboard Terhubung
echo "[Test 28] Integrasi status check-in pada personal dashboard...\n";
$rDashStatus = request("{$baseUrl}/user/dashboard.php", 'GET', null, [], $userACookie);
$hasCheckinBanner = (strpos($rDashStatus['body'], 'Check-in Hari Ini Sudah Terisi') !== false);
if ($hasCheckinBanner) {
    $results['test28'] = ['status' => 'PASS', 'msg' => 'Dashboard menampilkan status "Check-in Hari Ini Sudah Terisi"'];
} else {
    $results['test28'] = ['status' => 'FAIL', 'msg' => 'Banner status checkin tidak tampil di dashboard'];
}
echo "Hasil: {$results['test28']['status']} - {$results['test28']['msg']}\n";

// ===================================================================
// C. WEEKLY HABIT SUMMARY & VISUALIZATION TESTS (Tests 29 - 38)
// ===================================================================
echo "\n--- [Bagian C: Weekly Habit Summary & Charts] ---\n";

// Prepare 4 days of checkins within current week for User A
$monday = date('Y-m-d', strtotime('monday this week'));
$tue = date('Y-m-d', strtotime('tuesday this week'));
$wed = date('Y-m-d', strtotime('wednesday this week'));
$thu = date('Y-m-d', strtotime('thursday this week'));

save_daily_checkin($pdo, 2, $monday, ['water_ml' => 2000, 'sleep_hours' => 8.0, 'steps_count' => 8000, 'activity_minutes' => 30]);
save_daily_checkin($pdo, 2, $tue,    ['water_ml' => 2500, 'sleep_hours' => 7.0, 'steps_count' => 10000, 'activity_minutes' => 45]);
save_daily_checkin($pdo, 2, $wed,    ['water_ml' => 2200, 'sleep_hours' => 7.5, 'steps_count' => 9000, 'activity_minutes' => 25]);
save_daily_checkin($pdo, 2, $thu,    ['water_ml' => 2300, 'sleep_hours' => 8.5, 'steps_count' => 8500, 'activity_minutes' => 40]);

$weekly = get_weekly_habit_summary($pdo, 2, $monday);

// 29. Perhitungan Total Air Mingguan
echo "[Test 29] Perhitungan total konsumsi air mingguan...\n";
// 2000 + 2500 + 2200 + 2300 = 9000 ml = 9.0 L
if ($weekly['total_water_ml'] == 9000 && $weekly['total_water_liter'] == 9.0) {
    $results['test29'] = ['status' => 'PASS', 'msg' => 'Total air mingguan: 9000 ml (9.00 Liter) presisi'];
} else {
    $results['test29'] = ['status' => 'FAIL', 'msg' => "Total air salah: {$weekly['total_water_ml']}"];
}
echo "Hasil: {$results['test29']['status']} - {$results['test29']['msg']}\n";

// 30. Perhitungan Rata-rata Durasi Tidur
echo "[Test 30] Perhitungan rata-rata durasi tidur mingguan...\n";
// (8.0 + 7.0 + 7.5 + 8.5) / 4 = 31.0 / 4 = 7.75 -> 7.8 jam
if ($weekly['avg_sleep_hours'] == 7.8) {
    $results['test30'] = ['status' => 'PASS', 'msg' => 'Rata-rata tidur: 7.8 jam/malam presisi'];
} else {
    $results['test30'] = ['status' => 'FAIL', 'msg' => "Rata-rata tidur salah: {$weekly['avg_sleep_hours']}"];
}
echo "Hasil: {$results['test30']['status']} - {$results['test30']['msg']}\n";

// 31. Perhitungan Rata-rata Langkah Harian
echo "[Test 31] Perhitungan rata-rata jumlah langkah harian...\n";
// (8000 + 10000 + 9000 + 8500) / 4 = 35500 / 4 = 8875 langkah
if ($weekly['avg_steps'] == 8875) {
    $results['test31'] = ['status' => 'PASS', 'msg' => 'Rata-rata langkah: 8875 langkah/hari presisi'];
} else {
    $results['test31'] = ['status' => 'FAIL', 'msg' => "Rata-rata langkah salah: {$weekly['avg_steps']}"];
}
echo "Hasil: {$results['test31']['status']} - {$results['test31']['msg']}\n";

// 32. Perhitungan Total Durasi Olahraga
echo "[Test 32] Perhitungan total durasi aktivitas fisik...\n";
// 30 + 45 + 25 + 40 = 140 menit
if ($weekly['total_activity_minutes'] == 140) {
    $results['test32'] = ['status' => 'PASS', 'msg' => 'Total aktivitas fisik: 140 menit presisi'];
} else {
    $results['test32'] = ['status' => 'FAIL', 'msg' => "Total olahraga salah: {$weekly['total_activity_minutes']}"];
}
echo "Hasil: {$results['test32']['status']} - {$results['test32']['msg']}\n";

// 33. Jumlah Hari Check-in
echo "[Test 33] Perhitungan jumlah hari check-in dalam 1 minggu...\n";
if ($weekly['checkin_days_count'] >= 4) {
    $results['test33'] = ['status' => 'PASS', 'msg' => "Jumlah hari check-in terdeteksi {$weekly['checkin_days_count']} dari 7 hari"];
} else {
    $results['test33'] = ['status' => 'FAIL', 'msg' => "Hari checkin salah: {$weekly['checkin_days_count']}"];
}
echo "Hasil: {$results['test33']['status']} - {$results['test33']['msg']}\n";

// 34. Jumlah Target Tercapai Mingguan
echo "[Test 34] Perhitungan jumlah target kebiasaan yang tercapai...\n";
if ($weekly['targets_achieved_count'] >= 4) {
    $results['test34'] = ['status' => 'PASS', 'msg' => "Total target tercapai mingguan: {$weekly['targets_achieved_count']} kali"];
} else {
    $results['test34'] = ['status' => 'FAIL', 'msg' => "Target tercapai salah: {$weekly['targets_achieved_count']}"];
}
echo "Hasil: {$results['test34']['status']} - {$results['test34']['msg']}\n";

// 35. STRICT RULE: Tidak ada skor gabungan habit
echo "[Test 35] Verifikasi: TIDAK ADA skor gabungan kesehatan dari habit...\n";
$rHabitsPage = request("{$baseUrl}/user/habits.php", 'GET', null, [], $userACookie);
$hasHabitCompositeScore = (strpos($rHabitsPage['body'], 'Habit Score Index') !== false || strpos($rHabitsPage['body'], 'Skor Kebiasaan Total') !== false);
if (!$hasHabitCompositeScore) {
    $results['test35'] = ['status' => 'PASS', 'msg' => 'Ringkasan mingguan mematuhi prinsip penilaian metrik terpisah'];
} else {
    $results['test35'] = ['status' => 'FAIL', 'msg' => 'Ditemukan skor gabungan kebiasaan'];
}
echo "Hasil: {$results['test35']['status']} - {$results['test35']['msg']}\n";

// 36. Chart.js Canvas dirender saat data mingguan mencukupi (>=2 data points)
echo "[Test 36] Canvas Chart.js untuk 4 metrik dirender...\n";
$hasChartWater = (strpos($rHabitsPage['body'], '<canvas id="chartWeeklyWater"></canvas>') !== false);
$hasChartSleep = (strpos($rHabitsPage['body'], '<canvas id="chartWeeklySleep"></canvas>') !== false);
$hasChartSteps = (strpos($rHabitsPage['body'], '<canvas id="chartWeeklySteps"></canvas>') !== false);
$hasChartAct   = (strpos($rHabitsPage['body'], '<canvas id="chartWeeklyActivity"></canvas>') !== false);

if ($hasChartWater && $hasChartSleep && $hasChartSteps && $hasChartAct) {
    $results['test36'] = ['status' => 'PASS', 'msg' => 'Keempat kanvas grafik mingguan (Air, Tidur, Langkah, Olahraga) dirender terpisah'];
} else {
    $results['test36'] = ['status' => 'FAIL', 'msg' => 'Kanvas grafik tidak lengkap'];
}
echo "Hasil: {$results['test36']['status']} - {$results['test36']['msg']}\n";

// 37. Empty State pada grafik bila pengguna belum memiliki data mingguan
echo "[Test 37] Empty state grafik saat data mingguan < 2 hari...\n";
// User B has 0 checkins
$rHabitsB = request("{$baseUrl}/user/habits.php", 'GET', null, [], $userBCookie);
$hasEmptyStateWeekly = (strpos($rHabitsB['body'], 'Belum Cukup Data Mingguan') !== false);
if ($hasEmptyStateWeekly) {
    $results['test37'] = ['status' => 'PASS', 'msg' => 'Empty state grafik mingguan tampil informatif'];
} else {
    $results['test37'] = ['status' => 'FAIL', 'msg' => 'Empty state mingguan tidak tampil'];
}
echo "Hasil: {$results['test37']['status']} - {$results['test37']['msg']}\n";

// 38. Navigasi Minggu Sebelumnya & Minggu Berikutnya
echo "[Test 38] Navigasi minggu sebelumnya dan sesudahnya...\n";
$prevWeekStart = date('Y-m-d', strtotime('-7 days', strtotime($monday)));
$rPrevWeek = request("{$baseUrl}/user/habits.php?week={$prevWeekStart}", 'GET', null, [], $userACookie);
if ($rPrevWeek['status'] === 200 && strpos($rPrevWeek['body'], date('d M Y', strtotime($prevWeekStart))) !== false) {
    $results['test38'] = ['status' => 'PASS', 'msg' => 'Navigasi minggu sebelumnya berfungsi'];
} else {
    $results['test38'] = ['status' => 'FAIL', 'msg' => 'Navigasi minggu gagal'];
}
echo "Hasil: {$results['test38']['status']} - {$results['test38']['msg']}\n";

// ===================================================================
// D. HEALTH REMINDERS & NOTIFICATION TESTS (Tests 39 - 50)
// ===================================================================
echo "\n--- [Bagian D: Health Reminders & Notifications] ---\n";

// 39. Guest dilarang membuka reminders.php
echo "[Test 39] Akses guest ke reminders.php dialihkan ke login...\n";
$rGuestRem = request("{$baseUrl}/user/reminders.php", 'GET', null, [], $guestCookie);
if ($rGuestRem['status'] === 302 && strpos($rGuestRem['headers'], 'belum_login') !== false) {
    $results['test39'] = ['status' => 'PASS', 'msg' => 'Tamu diblokir ke login (HTTP 302 belum_login)'];
} else {
    $results['test39'] = ['status' => 'FAIL', 'msg' => 'Tamu dapat membuka reminders'];
}
echo "Hasil: {$results['test39']['status']} - {$results['test39']['msg']}\n";

// 40. Member membuka reminders.php & auto-inits default reminders
echo "[Test 40] Member membuka reminders.php & inisialisasi default pengingat...\n";
$rUserRem = request("{$baseUrl}/user/reminders.php", 'GET', null, [], $userACookie);
$remCountA = (int)fetchVal($pdo, "SELECT count(*) FROM health_reminders WHERE user_id = 2");
if ($rUserRem['status'] === 200 && $remCountA >= 4 && strpos($rUserRem['body'], 'Health Reminders') !== false) {
    $results['test40'] = ['status' => 'PASS', 'msg' => '4 jadwal pengingat bawaan (Air, Olahraga, Checkin, Tidur) diinisialisasi'];
} else {
    $results['test40'] = ['status' => 'FAIL', 'msg' => "Inisialisasi reminder gagal: count={$remCountA}"];
}
echo "Hasil: {$results['test40']['status']} - {$results['test40']['msg']}\n";

// 41. Menambah Pengingat Kustom Baru
echo "[Test 41] Menambah pengingat kustom baru (create_reminder)...\n";
$csrfRem = extractCsrf($rUserRem['body']);
$rAddRem = request("{$baseUrl}/user/reminders.php", 'POST', [
    'action'        => 'create_reminder',
    'tipe_reminder' => 'custom',
    'judul'         => 'Minum Vitamin D3',
    'pesan'         => 'Waktunya konsumsi vitamin D3 setelah sarapan',
    'jam_reminder'  => '08:30',
    'hari'          => ['1', '2', '3', '4', '5'],
    'csrf_token'    => $csrfRem
], [], $userACookie);
$newRemId = (int)fetchVal($pdo, "SELECT id_reminder FROM health_reminders WHERE user_id = 2 AND judul = 'Minum Vitamin D3'");
if ($rAddRem['status'] === 302 && $newRemId > 0) {
    $results['test41'] = ['status' => 'PASS', 'msg' => "Pengingat baru ID #{$newRemId} berhasil dibuat (Jam: 08:30)"];
} else {
    $results['test41'] = ['status' => 'FAIL', 'msg' => 'Gagal membuat reminder'];
}
echo "Hasil: {$results['test41']['status']} - {$results['test41']['msg']}\n";

// 42. Mengubah Jadwal & Jam Pengingat
echo "[Test 42] Mengubah waktu jadwal pengingat (update_reminder)...\n";
$rUpdRem = request("{$baseUrl}/user/reminders.php", 'POST', [
    'action'        => 'update_reminder',
    'reminder_id'   => $newRemId,
    'jam_reminder'  => '09:00',
    'is_active'     => 1,
    'hari'          => ['1', '2', '3', '4', '5', '6', '7'],
    'csrf_token'    => $csrfRem
], [], $userACookie);
$updRemTime = fetchVal($pdo, "SELECT jam_reminder FROM health_reminders WHERE id_reminder = ?", [$newRemId]);
if ($rUpdRem['status'] === 302 && $updRemTime === '09:00') {
    $results['test42'] = ['status' => 'PASS', 'msg' => 'Waktu pengingat diperbarui ke 09:00'];
} else {
    $results['test42'] = ['status' => 'FAIL', 'msg' => 'Gagal mengubah waktu reminder'];
}
echo "Hasil: {$results['test42']['status']} - {$results['test42']['msg']}\n";

// 43. Menonaktifkan Pengingat
echo "[Test 43] Menonaktifkan pengingat (is_active = 0)...\n";
$rDeactivateRem = request("{$baseUrl}/user/reminders.php", 'POST', [
    'action'        => 'update_reminder',
    'reminder_id'   => $newRemId,
    'jam_reminder'  => '09:00',
    // without is_active -> 0
    'csrf_token'    => $csrfRem
], [], $userACookie);
$isRemActive = (int)fetchVal($pdo, "SELECT is_active FROM health_reminders WHERE id_reminder = ?", [$newRemId]);
if ($rDeactivateRem['status'] === 302 && $isRemActive === 0) {
    $results['test43'] = ['status' => 'PASS', 'msg' => 'Pengingat berhasil dinonaktifkan'];
} else {
    $results['test43'] = ['status' => 'FAIL', 'msg' => 'Gagal menonaktifkan reminder'];
}
echo "Hasil: {$results['test43']['status']} - {$results['test43']['msg']}\n";

// 44. Menghapus Pengingat
echo "[Test 44] Menghapus pengingat (delete_reminder)...\n";
$rDelRem = request("{$baseUrl}/user/reminders.php", 'POST', [
    'action'      => 'delete_reminder',
    'reminder_id' => $newRemId,
    'csrf_token'  => $csrfRem
], [], $userACookie);
$existsRem = fetchVal($pdo, "SELECT count(*) FROM health_reminders WHERE id_reminder = ?", [$newRemId]);
if ($rDelRem['status'] === 302 && $existsRem == 0) {
    $results['test44'] = ['status' => 'PASS', 'msg' => 'Pengingat berhasil dihapus'];
} else {
    $results['test44'] = ['status' => 'FAIL', 'msg' => 'Gagal menghapus reminder'];
}
echo "Hasil: {$results['test44']['status']} - {$results['test44']['msg']}\n";

// 45. In-App Notification Generation (generate_due_reminders)
echo "[Test 45] Pembangkitan notifikasi in-app otomatis saat waktu jatuh tempo...\n";
// Set one reminder to time '00:00' so it is guaranteed due
$waterRemId = (int)fetchVal($pdo, "SELECT id_reminder FROM health_reminders WHERE user_id = 2 AND tipe_reminder = 'water'");
executeSql($pdo, "UPDATE health_reminders SET jam_reminder = '00:01', is_active = 1 WHERE id_reminder = ?", [$waterRemId]);
$genCount1 = generate_due_reminders($pdo, 2, '12:00', $today);
$notifCount = (int)fetchVal($pdo, "SELECT count(*) FROM notification_logs WHERE user_id = 2 AND reminder_id = ? AND tanggal_kirim = ?", [$waterRemId, $today]);
if ($genCount1 >= 1 && $notifCount === 1) {
    $results['test45'] = ['status' => 'PASS', 'msg' => 'Notifikasi in-app berhasil diterbitkan ke notification_logs'];
} else {
    $results['test45'] = ['status' => 'FAIL', 'msg' => 'Pembangkitan notifikasi gagal'];
}
echo "Hasil: {$results['test45']['status']} - {$results['test45']['msg']}\n";

// 46. Idempotensi Notifikasi: Pengingat yang sama tidak mengirim dobel di hari yang sama
echo "[Test 46] Idempotensi: Pengingat yang sama tidak menghasilkan notifikasi ganda pada hari yang sama...\n";
$genCount2 = generate_due_reminders($pdo, 2, '12:00', $today);
$notifCountAfter = (int)fetchVal($pdo, "SELECT count(*) FROM notification_logs WHERE user_id = 2 AND reminder_id = ? AND tanggal_kirim = ?", [$waterRemId, $today]);
if ($genCount2 === 0 && $notifCountAfter === 1) {
    $results['test46'] = ['status' => 'PASS', 'msg' => 'Idempotensi terverifikasi (Zero duplicate notification on same day)'];
} else {
    $results['test46'] = ['status' => 'FAIL', 'msg' => "Terjadi notifikasi ganda! count={$notifCountAfter}"];
}
echo "Hasil: {$results['test46']['status']} - {$results['test46']['msg']}\n";

// 47. Menandai Notifikasi Terbaca (mark_read)
echo "[Test 47] Menandai notifikasi sebagai terbaca (POST mark_read)...\n";
$firstNotifId = (int)fetchVal($pdo, "SELECT id_notif FROM notification_logs WHERE user_id = 2 LIMIT 1");
$rMarkRead = request("{$baseUrl}/user/reminders.php", 'POST', [
    'action'     => 'mark_read',
    'notif_id'   => $firstNotifId,
    'csrf_token' => $csrfRem
], [], $userACookie);
$notifStatus = fetchVal($pdo, "SELECT status FROM notification_logs WHERE id_notif = ?", [$firstNotifId]);
if ($rMarkRead['status'] === 302 && $notifStatus === 'read') {
    $results['test47'] = ['status' => 'PASS', 'msg' => 'Status notifikasi berhasil diubah menjadi read'];
} else {
    $results['test47'] = ['status' => 'FAIL', 'msg' => 'Gagal mengubah status notifikasi'];
}
echo "Hasil: {$results['test47']['status']} - {$results['test47']['msg']}\n";

// 48. Skrip Worker CLI (scripts/reminder_worker.php) Berjalan Aman
echo "[Test 48] Eksekusi worker CLI (scripts/reminder_worker.php)...\n";
$workerOut = shell_exec("HEALTHCHECK_DB_PATH=" . escapeshellarg($testDbPath) . " php " . escapeshellarg($projectRoot . '/scripts/reminder_worker.php') . " --user=2");
if (strpos($workerOut, 'Memulai evaluasi reminder') !== false && strpos($workerOut, 'Selesai') !== false) {
    $results['test48'] = ['status' => 'PASS', 'msg' => 'Worker CLI cron job beroperasi normal dan aman'];
} else {
    $results['test48'] = ['status' => 'FAIL', 'msg' => 'Worker CLI gagal'];
}
echo "Hasil: {$results['test48']['status']} - {$results['test48']['msg']}\n";

// 49. IDOR Reminder: User B dilarang mengubah reminder User A
echo "[Test 49] IDOR: User B dilarang mengubah jadwal pengingat User A...\n";
$rIdorRem = request("{$baseUrl}/user/reminders.php", 'POST', [
    'action'        => 'update_reminder',
    'reminder_id'   => $waterRemId, // User A's reminder
    'jam_reminder'  => '23:59',
    'is_active'     => 1,
    'csrf_token'    => extractCsrf(request("{$baseUrl}/user/reminders.php", 'GET', null, [], $userBCookie)['body'])
], [], $userBCookie);
$waterRemTimeA = fetchVal($pdo, "SELECT jam_reminder FROM health_reminders WHERE id_reminder = ?", [$waterRemId]);
if ($waterRemTimeA !== '23:59') {
    $results['test49'] = ['status' => 'PASS', 'msg' => 'Akses silang reminder ditolak, jadwal User A terlindungi'];
} else {
    $results['test49'] = ['status' => 'FAIL', 'msg' => 'Celah IDOR pada update reminder!'];
}
echo "Hasil: {$results['test49']['status']} - {$results['test49']['msg']}\n";

// 50. Pengingat Nonaktif TIDAK Menghasilkan Notifikasi
echo "[Test 50] Pengingat nonaktif (is_active=0) tidak menghasilkan notifikasi...\n";
executeSql($pdo, "UPDATE health_reminders SET is_active = 0 WHERE id_reminder = ?", [$waterRemId]);
executeSql($pdo, "DELETE FROM notification_logs WHERE reminder_id = ?", [$waterRemId]);
$genCountInactive = generate_due_reminders($pdo, 2, '23:59', $today);
$notifInactiveCount = (int)fetchVal($pdo, "SELECT count(*) FROM notification_logs WHERE reminder_id = ?", [$waterRemId]);
if ($notifInactiveCount === 0) {
    $results['test50'] = ['status' => 'PASS', 'msg' => 'Pengingat nonaktif tidak memicu notifikasi'];
} else {
    $results['test50'] = ['status' => 'FAIL', 'msg' => 'Pengingat nonaktif tetap memicu notifikasi!'];
}
echo "Hasil: {$results['test50']['status']} - {$results['test50']['msg']}\n";

// ===================================================================
// E. DASHBOARD & NAVIGATION INTEGRATION (Tests 51 - 56)
// ===================================================================
echo "\n--- [Bagian E: Integrasi Dashboard & Navigasi] ---\n";

// 51. Widget Today's Habits pada Dashboard
echo "[Test 51] Widget Today's Habits tampil di Dashboard...\n";
$rDashHabits = request("{$baseUrl}/user/dashboard.php", 'GET', null, [], $userACookie);
if (strpos($rDashHabits['body'], "Today's Habits") !== false && strpos($rDashHabits['body'], 'Konsumsi Air Harian') !== false) {
    $results['test51'] = ['status' => 'PASS', 'msg' => 'Widget Today\'s Habits tampil terintegrasi di Dashboard'];
} else {
    $results['test51'] = ['status' => 'FAIL', 'msg' => 'Widget habits tidak tampil'];
}
echo "Hasil: {$results['test51']['status']} - {$results['test51']['msg']}\n";

// 52. Widget Upcoming Reminders pada Dashboard
echo "[Test 52] Widget Upcoming Reminders tampil di Dashboard...\n";
if (strpos($rDashHabits['body'], 'Upcoming Reminders') !== false) {
    $results['test52'] = ['status' => 'PASS', 'msg' => 'Widget Upcoming Reminders terpasang di Dashboard'];
} else {
    $results['test52'] = ['status' => 'FAIL', 'msg' => 'Widget reminder tidak tampil'];
}
echo "Hasil: {$results['test52']['status']} - {$results['test52']['msg']}\n";

// 53. Tautan Menu Personal di Header Navbar
echo "[Test 53] Tautan Habits Tracker & Check-in tersedia di navbar member...\n";
$hasHeaderHabits = (strpos($rDashHabits['body'], 'user/habits.php') !== false && strpos($rDashHabits['body'], 'user/checkin.php') !== false && strpos($rDashHabits['body'], 'user/reminders.php') !== false);
if ($hasHeaderHabits) {
    $results['test53'] = ['status' => 'PASS', 'msg' => 'Navbar menyajikan tautan Habits Tracker, Check-in, dan Pengingat'];
} else {
    $results['test53'] = ['status' => 'FAIL', 'msg' => 'Navigasi navbar tidak lengkap'];
}
echo "Hasil: {$results['test53']['status']} - {$results['test53']['msg']}\n";

// 54. Tautan Menu Personal di Footer
echo "[Test 54] Tautan Habits Tracker & Check-in tersedia di footer...\n";
$hasFooterHabits = (strpos($rDashHabits['body'], 'Habits Tracker') !== false && strpos($rDashHabits['body'], 'Daily Health Check-in') !== false);
if ($hasFooterHabits) {
    $results['test54'] = ['status' => 'PASS', 'msg' => 'Footer menyajikan tautan Habits & Check-in'];
} else {
    $results['test54'] = ['status' => 'FAIL', 'msg' => 'Footer tidak lengkap'];
}
echo "Hasil: {$results['test54']['status']} - {$results['test54']['msg']}\n";

// 55. Quick Actions Bar pada Dashboard
echo "[Test 55] Tombol aksi cepat (Quick Actions) di Dashboard mengarah ke fitur Phase 4...\n";
$hasQuickActions = (strpos($rDashHabits['body'], 'btn-primary-health btn-sm') !== false && strpos($rDashHabits['body'], 'checkin.php') !== false);
if ($hasQuickActions) {
    $results['test55'] = ['status' => 'PASS', 'msg' => 'Quick action bar Dashboard memuat jalan pintas Check-in dan Habits'];
} else {
    $results['test55'] = ['status' => 'FAIL', 'msg' => 'Quick action bar tidak lengkap'];
}
echo "Hasil: {$results['test55']['status']} - {$results['test55']['msg']}\n";

// 56. Banner Notifikasi Belum Dibaca di Dashboard
echo "[Test 56] Banner alert notifikasi baru di Dashboard saat ada reminder...\n";
executeSql($pdo, "INSERT INTO notification_logs (user_id, reminder_id, tipe_notif, judul, pesan, tanggal_kirim, status) VALUES (2, 1, 'in_app', 'Notif Uji', 'Pesan Uji', ?, 'unread')", [$today]);
$rDashWithNotif = request("{$baseUrl}/user/dashboard.php", 'GET', null, [], $userACookie);
if (strpos($rDashWithNotif['body'], 'Notifikasi Baru') !== false) {
    $results['test56'] = ['status' => 'PASS', 'msg' => 'Alert notifikasi baru muncul di Dashboard'];
} else {
    $results['test56'] = ['status' => 'FAIL', 'msg' => 'Alert notifikasi tidak muncul'];
}
echo "Hasil: {$results['test56']['status']} - {$results['test56']['msg']}\n";

// ===================================================================
// F. SECURITY & REGRESSION INTEGRITY (Tests 57 - 65)
// ===================================================================
echo "\n--- [Bagian F: Security & Regression Integrity] ---\n";

// 57. Proteksi CSRF pada Habit Tracker
echo "[Test 57] Proteksi CSRF: POST create_habit tanpa token diblokir HTTP 403...\n";
$rNoCsrfHabit = request("{$baseUrl}/user/habits.php", 'POST', ['action' => 'create_habit', 'nama_habit' => 'Attack'], [], $userACookie);
if ($rNoCsrfHabit['status'] === 403) {
    $results['test57'] = ['status' => 'PASS', 'msg' => 'CSRF 403 enforcement aktif pada pembuatan habit'];
} else {
    $results['test57'] = ['status' => 'FAIL', 'msg' => 'CSRF habit lolos'];
}
echo "Hasil: {$results['test57']['status']} - {$results['test57']['msg']}\n";

// 58. Proteksi CSRF pada Reminder
echo "[Test 58] Proteksi CSRF: POST create_reminder tanpa token diblokir HTTP 403...\n";
$rNoCsrfRem = request("{$baseUrl}/user/reminders.php", 'POST', ['action' => 'create_reminder', 'judul' => 'Attack'], [], $userACookie);
if ($rNoCsrfRem['status'] === 403) {
    $results['test58'] = ['status' => 'PASS', 'msg' => 'CSRF 403 enforcement aktif pada pembuatan reminder'];
} else {
    $results['test58'] = ['status' => 'FAIL', 'msg' => 'CSRF reminder lolos'];
}
echo "Hasil: {$results['test58']['status']} - {$results['test58']['msg']}\n";

// 59. Ketahanan SQL Injection pada Input Tanggal & Habits
echo "[Test 59] Ketahanan SQL Injection pada parameter tanggal...\n";
$sqliDate = "' OR '1'='1";
$rSqliDate = request("{$baseUrl}/user/habits.php?date=" . urlencode($sqliDate), 'GET', null, [], $userACookie);
if ($rSqliDate['status'] === 200 && strpos($rSqliDate['body'], 'Fatal error') === false) {
    $results['test59'] = ['status' => 'PASS', 'msg' => 'Input tanggal tervalidasi regex aman dari SQL injection'];
} else {
    $results['test59'] = ['status' => 'FAIL', 'msg' => 'SQL injection tanggal bocor'];
}
echo "Hasil: {$results['test59']['status']} - {$results['test59']['msg']}\n";

// 60. Regresi Seluruh 12 Halaman Formulir Kalkulator (HTTP 200 OK)
echo "[Test 60] Regresi: Semua 12 formulir kalkulator tetap beroperasi normal (HTTP 200 OK)...\n";
$tools = [
    'bmi/bmi_form.php', 'bmr/bmr_form.php', 'whtr/whtr_form.php', 'body_fat/body_fat_form.php',
    'cairan/cairan_form.php', 'tekanan_darah/tekanan_darah_form.php', 'jantung/jantung_form.php',
    'zona_jantung/zona_jantung_form.php', 'tidur/tidur_form.php', 'aktivitas_fisik/aktivitas_fisik_form.php',
    'stres/stres_form.php', 'mental/mental_form.php'
];
$allToolsOk = true;
foreach ($tools as $t) {
    $code = request("{$baseUrl}/{$t}", 'GET')['status'];
    if ($code !== 200) {
        $allToolsOk = false;
        break;
    }
}
if ($allToolsOk) {
    $results['test60'] = ['status' => 'PASS', 'msg' => 'Seluruh 12 halaman formulir kalkulator merespons HTTP 200 OK'];
} else {
    $results['test60'] = ['status' => 'FAIL', 'msg' => 'Ada kalkulator yang gagal'];
}
echo "Hasil: {$results['test60']['status']} - {$results['test60']['msg']}\n";

// 61. Regresi Riwayat & Unduh PDF Tetap Bekerja
echo "[Test 61] Regresi: Riwayat terpadu & Unduh PDF laporan tetap berfungsi...\n";
$rHist = request("{$baseUrl}/user/history.php", 'GET', null, [], $userACookie);
$rPdf  = request("{$baseUrl}/user/report_download.php?action=download", 'GET', null, [], $userACookie);
$pdfOk = ($rPdf['status'] === 200 && str_starts_with($rPdf['body'], '%PDF'));
if ($rHist['status'] === 200 && $pdfOk) {
    $results['test61'] = ['status' => 'PASS', 'msg' => 'Riwayat (HTTP 200) dan Ekspor PDF (%PDF binary) beroperasi stabil'];
} else {
    $results['test61'] = ['status' => 'FAIL', 'msg' => 'Riwayat atau PDF terganggu'];
}
echo "Hasil: {$results['test61']['status']} - {$results['test61']['msg']}\n";

// 62. Regresi Pusat Edukasi Kesehatan
echo "[Test 62] Regresi: Pusat edukasi kesehatan tetap dapat diakses publik...\n";
$rEdu = request("{$baseUrl}/education/index.php", 'GET');
if ($rEdu['status'] === 200 && strpos($rEdu['body'], 'Pusat Informasi & Edukasi Medis') !== false) {
    $results['test62'] = ['status' => 'PASS', 'msg' => 'Pusat edukasi kesehatan merespons normal (HTTP 200 OK)'];
} else {
    $results['test62'] = ['status' => 'FAIL', 'msg' => 'Pusat edukasi gagal'];
}
echo "Hasil: {$results['test62']['status']} - {$results['test62']['msg']}\n";

// 63. Idempotensi Migration Runner Phase 4
echo "[Test 63] Idempotensi Migration Runner...\n";
$migOutput = shell_exec("HEALTHCHECK_DB_PATH=" . escapeshellarg($testDbPath) . " php " . escapeshellarg($projectRoot . '/database/migrate.php'));
$migStatus = shell_exec("HEALTHCHECK_DB_PATH=" . escapeshellarg($testDbPath) . " php " . escapeshellarg($projectRoot . '/database/migrate.php') . " --status");
if (strpos($migOutput, 'Nothing to migrate') !== false && strpos($migStatus, '[APPLIED] 20261108_003_phase4_habits_checkin_reminders') !== false) {
    $results['test63'] = ['status' => 'PASS', 'msg' => 'Migration runner idempoten (status APPLIED terverifikasi)'];
} else {
    $results['test63'] = ['status' => 'FAIL', 'msg' => 'Eksekusi ulang migrasi gagal'];
}
echo "Hasil: {$results['test63']['status']} - {$results['test63']['msg']}\n";

// 64. Whitelist Entitas assert_record_access mencakup tabel habits
echo "[Test 64] Keamanan Whitelist: assert_record_access mengizinkan tabel habits...\n";
$cliCheck = shell_exec(sprintf('php -r "require_once \'%s/includes/auth_helper.php\'; \$_SESSION[\'user_id\'] = 2; \$p = new PDO(\'sqlite:%s\'); try { \$rec = assert_record_access(\$p, \'health_habits\', \'id_habit\', %d); echo \$rec ? \'OK\' : \'FAIL\'; } catch (Throwable \$e) { echo \'ERR\'; }" 2>&1', $projectRoot, $testDbPath, $waterHabit['id_habit']));
$tableHabitAllowed = (trim($cliCheck) === 'OK');
if ($tableHabitAllowed) {
    $results['test64'] = ['status' => 'PASS', 'msg' => 'Tabel health_habits terdaftar sah di whitelist assert_record_access'];
} else {
    $results['test64'] = ['status' => 'FAIL', 'msg' => 'Tabel health_habits ditolak whitelist: ' . $cliCheck];
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
echo "REKAPITULASI HASIL PENGUJIAN PHASE 4 (65 SKENARIO):\n";
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
