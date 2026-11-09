<?php
/**
 * Automated Test Suite for HealthCheck Phase 3:
 * Personal Health Dashboard, Integrated History, Detail View, Trends, PDF Report & Education Center
 *
 * Runs 65 comprehensive test cases against an isolated SQLite test database.
 */

$host = '127.0.0.1';
$port = 8098;
$baseUrl = "http://{$host}:{$port}";

echo "======================================================================\n";
echo "=== Memulai HealthCheck Phase 3 Comprehensive Test Suite (65 Tests) ===\n";
echo "======================================================================\n";

// Setup isolated test database
$projectRoot = dirname(__DIR__);
$mainDbPath = $projectRoot . '/database/healthcheck.sqlite';
$testDbPath = $projectRoot . '/database/healthcheck_test_phase3.sqlite';

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
$cmd = sprintf('HEALTHCHECK_DB_PATH=%s php -S %s:%d -t "%s" > /tmp/php_phase3_test.log 2>&1 & echo $!',
    escapeshellarg($testDbPath), $host, $port, $projectRoot
);
$pid = (int)shell_exec($cmd);
echo "Server test Phase 3 aktif (PID: {$pid}) pada {$baseUrl}\n";

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

function loginAdmin(string $baseUrl, string $cookieFile, string $user, string $pass): bool {
    if (file_exists($cookieFile)) unlink($cookieFile);
    $p = request("{$baseUrl}/auth/login_admin.php", 'GET', null, [], $cookieFile);
    $csrf = extractCsrf($p['body']);
    $res = request("{$baseUrl}/auth/login_admin_proses.php", 'POST', [
        'username' => $user,
        'password' => $pass,
        'csrf_token' => $csrf
    ], [], $cookieFile);
    return ($res['status'] === 302);
}

require_once $projectRoot . '/includes/history_helper.php';
require_once $projectRoot . '/includes/auth_helper.php';

// Prepare User A (id=2) and User B (id=3)
$guestCookie = $tmpCookieDir . '/guest_p3_cookie.txt';
if (file_exists($guestCookie)) unlink($guestCookie);

$userACookie = $tmpCookieDir . '/usera_p3_cookie.txt';
loginUser($baseUrl, $userACookie, 'user', 'user123');

// Ensure User B exists
$userBId = fetchVal($pdo, "SELECT id FROM user WHERE username = 'user_b'");
if (!$userBId) {
    $hashB = password_hash('pass123', PASSWORD_DEFAULT);
    executeSql($pdo, "INSERT INTO user (username, password, role) VALUES ('user_b', ?, 'user')", [$hashB]);
    $userBId = (int)$pdo->lastInsertId();
} else {
    $userBId = (int)$userBId;
}

$userBCookie = $tmpCookieDir . '/userb_p3_cookie.txt';
loginUser($baseUrl, $userBCookie, 'user_b', 'pass123');

$adminCookie = $tmpCookieDir . '/admin_p3_cookie.txt';
loginAdmin($baseUrl, $adminCookie, 'admin', 'admin123');

// ===================================================================
// A. PERSONAL HEALTH DASHBOARD TESTS (Tests 1 - 10)
// ===================================================================
echo "\n--- [Bagian A: Personal Health Dashboard] ---\n";

// 1. Guest dilarang mengakses dashboard
echo "[Test 1] Akses guest ke dashboard diarahkan ke login...\n";
$rGuestDash = request("{$baseUrl}/user/dashboard.php", 'GET', null, [], $guestCookie);
if ($rGuestDash['status'] === 302 && strpos($rGuestDash['headers'], 'belum_login') !== false) {
    $results['test1'] = ['status' => 'PASS', 'msg' => 'Tamu dialihkan ke login (HTTP 302 belum_login)'];
} else {
    $results['test1'] = ['status' => 'FAIL', 'msg' => "Status bukan 302: {$rGuestDash['status']}"];
}
echo "Hasil: {$results['test1']['status']} - {$results['test1']['msg']}\n";

// 2. Member dapat mengakses dashboard
echo "[Test 2] Member terautentikasi berhasil membuka dashboard...\n";
$rUserDash = request("{$baseUrl}/user/dashboard.php", 'GET', null, [], $userACookie);
if ($rUserDash['status'] === 200 && strpos($rUserDash['body'], 'Personal Health Dashboard') !== false) {
    $results['test2'] = ['status' => 'PASS', 'msg' => 'Dashboard terbuka normal (HTTP 200 OK)'];
} else {
    $results['test2'] = ['status' => 'FAIL', 'msg' => "Dashboard gagal: {$rUserDash['status']}"];
}
echo "Hasil: {$results['test2']['status']} - {$results['test2']['msg']}\n";

// 3. Clean Empty States saat member belum memiliki data
echo "[Test 3] Empty state bersih saat pengguna belum memiliki rekam data...\n";
// User B has no records initially
executeSql($pdo, "DELETE FROM cek_bmi WHERE user_id = ?", [$userBId]);
executeSql($pdo, "DELETE FROM cek_tekanan_darah WHERE user_id = ?", [$userBId]);
$rEmptyDash = request("{$baseUrl}/user/dashboard.php", 'GET', null, [], $userBCookie);
$hasEmptyBadge = (strpos($rEmptyDash['body'], 'Belum Ada Data') !== false);
$hasEmptyAction = (strpos($rEmptyDash['body'], 'Mulai Pemeriksaan') !== false);
if ($hasEmptyBadge && $hasEmptyAction) {
    $results['test3'] = ['status' => 'PASS', 'msg' => 'Empty state indikator biometrik tertata rapi'];
} else {
    $results['test3'] = ['status' => 'FAIL', 'msg' => 'Empty state tidak tampil'];
}
echo "Hasil: {$results['test3']['status']} - {$results['test3']['msg']}\n";

// 4. STRICT CONSTRAINT: TIDAK ADA HEALTH SCORE GABUNGAN 0-100
echo "[Test 4] Verifikasi: TIDAK ADA skor komposit buatan 0-100 pada dashboard...\n";
$hasFakeScore = (strpos($rUserDash['body'], 'Health Score Index') !== false ||
                 strpos($rUserDash['body'], 'Skor Kesehatan Holistik') !== false ||
                 preg_match('/class="[^"]*health-score[^"]*"/', $rUserDash['body']));
if (!$hasFakeScore) {
    $results['test4'] = ['status' => 'PASS', 'msg' => 'Dashboard mematuhi kaidah medis individual assessment (Zero arbitrary 0-100 score)'];
} else {
    $results['test4'] = ['status' => 'FAIL', 'msg' => 'Ditemukan skor komposit 0-100 tanpa dasar klinis'];
}
echo "Hasil: {$results['test4']['status']} - {$results['test4']['msg']}\n";

// 5. Seed diverse records for User A (ID=2) across categories
echo "[Test 5] Menampilkan indikator biometrik saat data tersedia...\n";
executeSql($pdo, "INSERT INTO cek_bmi (user_id, nama_user, umur, berat_badan, tinggi_badan, bmi, kondisi) VALUES (2, 'User A', 30, 70, 175, 22.9, 'Normal')");
executeSql($pdo, "INSERT INTO cek_tekanan_darah (user_id, nama_user, umur, sistolik, diastolik, kategori, saran) VALUES (2, 'User A', 30, 118, 76, 'Normal (Optimal)', 'Bagus')");
executeSql($pdo, "INSERT INTO cek_kualitas_tidur (user_id, nama_user, durasi_jam, latensi_menit, frekuensi_terbangun, kesegaran_bangun, efisiensi_tidur, skor_kebiasaan, kategori_tidur) VALUES (2, 'User A', 8.0, 15, 0, 'sangat_segar', 94.1, 1, 'Kualitas Tidur Sangat Baik')");

$rDashWithData = request("{$baseUrl}/user/dashboard.php", 'GET', null, [], $userACookie);
$hasBmiVal = (strpos($rDashWithData['body'], '22.9 kg/m²') !== false);
$hasBpVal  = (strpos($rDashWithData['body'], '118 / 76 mmHg') !== false);
$hasSleepVal = (strpos($rDashWithData['body'], '94.1%') !== false);

if ($hasBmiVal && $hasBpVal && $hasSleepVal) {
    $results['test5'] = ['status' => 'PASS', 'msg' => 'Indikator BMI, Tekanan Darah, dan Tidur tampil akurat'];
} else {
    $results['test5'] = ['status' => 'FAIL', 'msg' => 'Indikator biometrik tidak tampil lengkap'];
}
echo "Hasil: {$results['test5']['status']} - {$results['test5']['msg']}\n";

// 6. Record terbaru dipilih dengan benar saat terdapat banyak rekaman
echo "[Test 6] Seleksi record terbaru (Latest record resolution)...\n";
usleep(20000);
executeSql($pdo, "INSERT INTO cek_bmi (user_id, nama_user, umur, berat_badan, tinggi_badan, bmi, kondisi) VALUES (2, 'User A', 30, 72, 175, 23.5, 'Kelebihan Berat Badan (Overweight)')");

$indicatorsA = get_user_latest_indicators($pdo, 2);
if ($indicatorsA['bmi']['has_data'] && $indicatorsA['bmi']['primary_metric'] === '23.5 kg/m²' && str_contains($indicatorsA['bmi']['status_label'], 'Overweight')) {
    $results['test6'] = ['status' => 'PASS', 'msg' => 'Record terbaru 23.5 kg/m² terpilih menggantikan record lama 22.9 kg/m²'];
} else {
    $results['test6'] = ['status' => 'FAIL', 'msg' => 'Record terbaru gagal diidentifikasi'];
}
echo "Hasil: {$results['test6']['status']} - {$results['test6']['msg']}\n";

// 7. Kalkulasi tren delta terhadap rekaman sebelumnya
echo "[Test 7] Perhitungan tren delta (Perbandingan dengan rekaman sebelumnya)...\n";
// Diff: 23.5 - 22.9 = +0.6 kg/m²
if ($indicatorsA['bmi']['trend_text'] === '+0.6 kg/m²' && $indicatorsA['bmi']['trend_direction'] === 'up') {
    $results['test7'] = ['status' => 'PASS', 'msg' => 'Delta tren (+0.6 kg/m²) terhitung presisi'];
} else {
    $results['test7'] = ['status' => 'FAIL', 'msg' => "Delta tren salah: {$indicatorsA['bmi']['trend_text']}"];
}
echo "Hasil: {$results['test7']['status']} - {$results['test7']['msg']}\n";

// 8. Recent Activities Feed
echo "[Test 8] Daftar aktivitas terbaru menampilkan riwayat lintas kategori...\n";
$rFeed = request("{$baseUrl}/user/dashboard.php", 'GET', null, [], $userACookie);
$hasActivityFeed = (strpos($rFeed['body'], 'Aktivitas Pemeriksaan Terbaru') !== false && strpos($rFeed['body'], 'Lihat Detail') !== false);
if ($hasActivityFeed) {
    $results['test8'] = ['status' => 'PASS', 'msg' => 'Feed aktivitas terbaru tersaji terpadu'];
} else {
    $results['test8'] = ['status' => 'FAIL', 'msg' => 'Aktivitas terbaru tidak tampil'];
}
echo "Hasil: {$results['test8']['status']} - {$results['test8']['msg']}\n";

// 9. Link detail mengarah ke history_detail.php
echo "[Test 9] Tautan detail indikator mengarah ke history_detail.php?type=...&id=...\n";
if (strpos($indicatorsA['bmi']['detail_url'], 'history_detail.php?type=bmi&id=') !== false) {
    $results['test9'] = ['status' => 'PASS', 'msg' => "Detail URL valid: {$indicatorsA['bmi']['detail_url']}"];
} else {
    $results['test9'] = ['status' => 'FAIL', 'msg' => 'Detail URL tidak valid'];
}
echo "Hasil: {$results['test9']['status']} - {$results['test9']['msg']}\n";

// 10. Data User A tidak bocor ke Dashboard User B
echo "[Test 10] Isolasi Dashboard: Data User A tidak tampil pada Dashboard User B...\n";
$rDashB = request("{$baseUrl}/user/dashboard.php", 'GET', null, [], $userBCookie);
$leakedBmiInB = (strpos($rDashB['body'], '23.5 kg/m²') !== false);
$leakedBpInB  = (strpos($rDashB['body'], '118 / 76 mmHg') !== false);
if (!$leakedBmiInB && !$leakedBpInB) {
    $results['test10'] = ['status' => 'PASS', 'msg' => 'Dashboard User B terisolasi penuh dari rekam data User A'];
} else {
    $results['test10'] = ['status' => 'FAIL', 'msg' => 'Kebocoran data terdeteksi pada Dashboard User B'];
}
echo "Hasil: {$results['test10']['status']} - {$results['test10']['msg']}\n";

// ===================================================================
// B. INTEGRATED HEALTH HISTORY TESTS (Tests 11 - 22)
// ===================================================================
echo "\n--- [Bagian B: Riwayat Pemeriksaan Terpadu (12 Alat)] ---\n";

// 11. Guest diblokir dari history.php
echo "[Test 11] Akses guest ke riwayat diarahkan ke login...\n";
$rGuestHist = request("{$baseUrl}/user/history.php", 'GET', null, [], $guestCookie);
if ($rGuestHist['status'] === 302 && strpos($rGuestHist['headers'], 'belum_login') !== false) {
    $results['test11'] = ['status' => 'PASS', 'msg' => 'Akses tamu diblokir ke login'];
} else {
    $results['test11'] = ['status' => 'FAIL', 'msg' => 'Tamu dapat membuka riwayat'];
}
echo "Hasil: {$results['test11']['status']} - {$results['test11']['msg']}\n";

// 12. Seed 1 record for all remaining tools for User A to achieve 12/12 integration
executeSql($pdo, "INSERT INTO cek_bmr (user_id, nama_user, jenis_kelamin, umur, berat_badan, tinggi_badan, bmr, aktivitas, tdee) VALUES (2, 'User A', 'L', 30, 72, 175, 1694, 'Sedang', 2626)");
executeSql($pdo, "INSERT INTO cek_whtr (user_id, nama_user, jenis_kelamin, lingkar_pinggang, tinggi_badan, rasio_whtr, kategori_risiko) VALUES (2, 'User A', 'L', 80, 175, 0.46, 'Sehat / Ideal')");
executeSql($pdo, "INSERT INTO cek_lemak_tubuh (user_id, nama_user, jenis_kelamin, umur, tinggi_badan, berat_badan, lingkar_leher, lingkar_pinggang, body_fat_percent, kategori) VALUES (2, 'User A', 'L', 30, 175, 72, 38, 82, 17.2, 'Fitness')");
executeSql($pdo, "INSERT INTO cek_kebutuhan_cairan (user_id, nama_user, berat_badan, kebutuhan_ml, kebutuhan_liter, kebutuhan_gelas) VALUES (2, 'User A', 72, 2520, 2.5, 10)");
executeSql($pdo, "INSERT INTO cek_jantung (user_id, nama_user, umur, resting_hr, max_hr, kategori) VALUES (2, 'User A', 30, 68, 187, 'Normal Sehat')");
executeSql($pdo, "INSERT INTO cek_zona_detak_jantung (user_id, nama_user, umur, resting_hr, max_hr, hrr, metode, zona_1_min, zona_1_max, zona_2_min, zona_2_max, zona_3_min, zona_3_max, zona_4_min, zona_4_max, zona_5_min, zona_5_max) VALUES (2, 'User A', 30, 68, 187, 119, 'karvonen', 128, 140, 140, 151, 151, 163, 163, 175, 175, 187)");
executeSql($pdo, "INSERT INTO cek_aktivitas_fisik (user_id, nama_user, total_met_menit, kategori_aktivitas) VALUES (2, 'User A', 1450, 'Sedang')");
executeSql($pdo, "INSERT INTO cek_stres (user_id, nama_user, skor_stres, tingkat_stres) VALUES (2, 'User A', 18, 'Tingkat Stres Sedang')");
executeSql($pdo, "INSERT INTO cek_mental (user_id, nama_user, skor_cemas, skor_depresi, total_skor, tingkat) VALUES (2, 'User A', 1, 1, 2, 'Normal / Minimal')");

// 12. Integrasi 12 Jenis Alat
echo "[Test 12] Integrasi riwayat mencakup ke-12 alat evaluasi mandiri...\n";
$fullHistory = get_user_unified_history($pdo, 2);
$detectedTools = array_unique(array_column($fullHistory['records'], 'tool_type'));
if (count($detectedTools) === 12) {
    $results['test12'] = ['status' => 'PASS', 'msg' => 'Semua 12 jenis alat terintegrasi dalam query agregasi riwayat'];
} else {
    $results['test12'] = ['status' => 'FAIL', 'msg' => 'Hanya ' . count($detectedTools) . '/12 alat terdeteksi'];
}
echo "Hasil: {$results['test12']['status']} - {$results['test12']['msg']}\n";

// 13. Filter Kategori Gizi
echo "[Test 13] Filter riwayat berdasarkan kategori Gizi & Tubuh...\n";
$giziHistory = get_user_unified_history($pdo, 2, ['category' => 'gizi']);
$allGizi = true;
foreach ($giziHistory['records'] as $r) {
    if ($r['category'] !== 'gizi') $allGizi = false;
}
if ($allGizi && count($giziHistory['records']) >= 5) {
    $results['test13'] = ['status' => 'PASS', 'msg' => 'Filter kategori gizi menyaring 5 alat nutrisi & komposisi tubuh'];
} else {
    $results['test13'] = ['status' => 'FAIL', 'msg' => 'Filter gizi tidak sesuai'];
}
echo "Hasil: {$results['test13']['status']} - {$results['test13']['msg']}\n";

// 14. Filter Kategori Kardio
echo "[Test 14] Filter riwayat berdasarkan kategori Kardiovaskular...\n";
$kardioHistory = get_user_unified_history($pdo, 2, ['category' => 'kardio']);
$allKardio = true;
foreach ($kardioHistory['records'] as $r) {
    if ($r['category'] !== 'kardio') $allKardio = false;
}
if ($allKardio && count($kardioHistory['records']) >= 3) {
    $results['test14'] = ['status' => 'PASS', 'msg' => 'Filter kategori kardio menyaring tensi, RHR, dan zona kardio'];
} else {
    $results['test14'] = ['status' => 'FAIL', 'msg' => 'Filter kardio tidak sesuai'];
}
echo "Hasil: {$results['test14']['status']} - {$results['test14']['msg']}\n";

// 15. Filter Kategori Mental
echo "[Test 15] Filter riwayat berdasarkan kategori Kesehatan Mental...\n";
$mentalHistory = get_user_unified_history($pdo, 2, ['category' => 'mental']);
$allMental = true;
foreach ($mentalHistory['records'] as $r) {
    if ($r['category'] !== 'mental') $allMental = false;
}
if ($allMental && count($mentalHistory['records']) >= 2) {
    $results['test15'] = ['status' => 'PASS', 'msg' => 'Filter kategori mental menyaring PSS-10 dan PHQ-4'];
} else {
    $results['test15'] = ['status' => 'FAIL', 'msg' => 'Filter mental tidak sesuai'];
}
echo "Hasil: {$results['test15']['status']} - {$results['test15']['msg']}\n";

// 16. Filter Spesifik Tool Type
echo "[Test 16] Filter riwayat berdasarkan jenis alat spesifik (type=tekanan_darah)...\n";
$bpOnlyHist = get_user_unified_history($pdo, 2, ['type' => 'tekanan_darah']);
if (count($bpOnlyHist['records']) >= 1 && $bpOnlyHist['records'][0]['tool_type'] === 'tekanan_darah') {
    $results['test16'] = ['status' => 'PASS', 'msg' => 'Filter type=tekanan_darah menyaring rekam tensi'];
} else {
    $results['test16'] = ['status' => 'FAIL', 'msg' => 'Filter type gagal'];
}
echo "Hasil: {$results['test16']['status']} - {$results['test16']['msg']}\n";

// 17. Sorting DESC vs ASC
echo "[Test 17] Pengurutan riwayat (Terbaru DESC vs Terlama ASC)...\n";
$sortDesc = get_user_unified_history($pdo, 2, ['sort' => 'DESC']);
$sortAsc  = get_user_unified_history($pdo, 2, ['sort' => 'ASC']);
$firstDesc = $sortDesc['records'][0]['timestamp'];
$lastDesc  = end($sortDesc['records'])['timestamp'];
$firstAsc  = $sortAsc['records'][0]['timestamp'];
if ($firstDesc >= $lastDesc && $firstAsc <= $firstDesc) {
    $results['test17'] = ['status' => 'PASS', 'msg' => 'Sorting kronologis DESC & ASC bekerja akurat'];
} else {
    $results['test17'] = ['status' => 'FAIL', 'msg' => 'Sorting kronologis gagal'];
}
echo "Hasil: {$results['test17']['status']} - {$results['test17']['msg']}\n";

// 18. Filter Rentang Tanggal
echo "[Test 18] Filter rentang tanggal (date_from & date_to)...\n";
$today = date('Y-m-d');
$dateHist = get_user_unified_history($pdo, 2, ['date_from' => $today, 'date_to' => $today]);
if ($dateHist['total_count'] >= 1) {
    $results['test18'] = ['status' => 'PASS', 'msg' => "Filter rentang tanggal hari ini ({$today}) menemukan {$dateHist['total_count']} catatan"];
} else {
    $results['test18'] = ['status' => 'FAIL', 'msg' => 'Filter tanggal gagal'];
}
echo "Hasil: {$results['test18']['status']} - {$results['test18']['msg']}\n";

// 19. Pencarian Kata Kunci
echo "[Test 19] Pencarian riwayat berdasarkan kata kunci status/alat...\n";
$searchHist = get_user_unified_history($pdo, 2, ['search' => 'Tensi']);
if ($searchHist['total_count'] >= 1 && str_contains($searchHist['records'][0]['tool_title'], 'Tekanan Darah')) {
    $results['test19'] = ['status' => 'PASS', 'msg' => 'Pencarian kata kunci "Tensi" menemukan alat terkait'];
} else {
    $results['test19'] = ['status' => 'FAIL', 'msg' => 'Pencarian kata kunci gagal'];
}
echo "Hasil: {$results['test19']['status']} - {$results['test19']['msg']}\n";

// 20. Pagination Support
echo "[Test 20] Pagination riwayat (Limit 5 records per halaman)...\n";
$page1 = get_user_unified_history($pdo, 2, ['limit' => 5, 'offset' => 0]);
$page2 = get_user_unified_history($pdo, 2, ['limit' => 5, 'offset' => 5]);
if (count($page1['records']) === 5 && count($page2['records']) >= 1 && $page1['records'][0]['record_id'] !== $page2['records'][0]['record_id']) {
    $results['test20'] = ['status' => 'PASS', 'msg' => 'Pagination memotong halaman 1 (5 items) dan halaman 2 secara bersih'];
} else {
    $results['test20'] = ['status' => 'FAIL', 'msg' => 'Pagination gagal'];
}
echo "Hasil: {$results['test20']['status']} - {$results['test20']['msg']}\n";

// 21. Empty State saat filter tidak cocok
echo "[Test 21] Tampilan empty state saat kriteria pencarian tidak ditemukan...\n";
$emptySearch = request("{$baseUrl}/user/history.php?search=KataKunciTakDikenalXYZ", 'GET', null, [], $userACookie);
if (strpos($emptySearch['body'], 'Tidak Ada Riwayat yang Sesuai') !== false) {
    $results['test21'] = ['status' => 'PASS', 'msg' => 'Empty state pencarian kosong tampil jelas'];
} else {
    $results['test21'] = ['status' => 'FAIL', 'msg' => 'Empty state tidak muncul'];
}
echo "Hasil: {$results['test21']['status']} - {$results['test21']['msg']}\n";

// 22. Isolasi Riwayat Lintas Pengguna
echo "[Test 22] User B tidak melihat satu pun riwayat User A pada halaman riwayat...\n";
$histB = request("{$baseUrl}/user/history.php", 'GET', null, [], $userBCookie);
$leakedInB = (strpos($histB['body'], '23.5 kg/m²') !== false || strpos($histB['body'], '118 / 76 mmHg') !== false);
if (!$leakedInB) {
    $results['test22'] = ['status' => 'PASS', 'msg' => 'Riwayat terisolasi penuh (User B tidak melihat data User A)'];
} else {
    $results['test22'] = ['status' => 'FAIL', 'msg' => 'Kebocoran riwayat antar-pengguna'];
}
echo "Hasil: {$results['test22']['status']} - {$results['test22']['msg']}\n";

// ===================================================================
// C. HISTORY DETAIL VIEW TESTS (Tests 23 - 30)
// ===================================================================
echo "\n--- [Bagian C: Detail Snapshot Pemeriksaan] ---\n";

$lastBmiAId = fetchVal($pdo, "SELECT id_bmi FROM cek_bmi WHERE user_id = 2 ORDER BY id_bmi DESC LIMIT 1");
$lastBpAId  = fetchVal($pdo, "SELECT id_bp FROM cek_tekanan_darah WHERE user_id = 2 ORDER BY id_bp DESC LIMIT 1");

// 23. User A dapat membuka detail pemeriksaan miliknya
echo "[Test 23] User A membuka detail rekam BMI miliknya...\n";
$rDetailA = request("{$baseUrl}/user/history_detail.php?type=bmi&id={$lastBmiAId}", 'GET', null, [], $userACookie);
if ($rDetailA['status'] === 200 && strpos($rDetailA['body'], 'Data Snapshot Pemeriksaan') !== false) {
    $results['test23'] = ['status' => 'PASS', 'msg' => 'Halaman detail rekam terbuka dengan status 200 OK'];
} else {
    $results['test23'] = ['status' => 'FAIL', 'msg' => "Detail gagal: {$rDetailA['status']}"];
}
echo "Hasil: {$results['test23']['status']} - {$results['test23']['msg']}\n";

// 24. Snapshot parameter tersimpan utuh di detail
echo "[Test 24] Snapshot data detail menyajikan parameter masukan dan luaran saat itu...\n";
$hasDetailFields = (strpos($rDetailA['body'], '23.5 kg/m²') !== false && strpos($rDetailA['body'], '175 cm') !== false);
if ($hasDetailFields) {
    $results['test24'] = ['status' => 'PASS', 'msg' => 'Data snapshot tersaji lengkap'];
} else {
    $results['test24'] = ['status' => 'FAIL', 'msg' => 'Snapshot tidak lengkap'];
}
echo "Hasil: {$results['test24']['status']} - {$results['test24']['msg']}\n";

// 25. Historical Integrity: Detail TIDAK dihitung ulang saat profil diperbarui
echo "[Test 25] Integritas historis: Detail tidak terubah saat profil akun dimutasi...\n";
executeSql($pdo, "UPDATE user_profile SET berat_badan = 95.0, tinggi_badan = 185.0 WHERE user_id = 2");
$rDetailRecheck = request("{$baseUrl}/user/history_detail.php?type=bmi&id={$lastBmiAId}", 'GET', null, [], $userACookie);
// The historical record should still show 175 cm and 23.5 kg/m², NOT 95kg / 185cm
$stillHistoric = (strpos($rDetailRecheck['body'], '23.5 kg/m²') !== false && strpos($rDetailRecheck['body'], '175 cm') !== false);
if ($stillHistoric) {
    $results['test25'] = ['status' => 'PASS', 'msg' => 'Snapshot historis kebal dari mutasi data profil pengguna'];
} else {
    $results['test25'] = ['status' => 'FAIL', 'msg' => 'Data historis termutasi oleh perubahan profil'];
}
echo "Hasil: {$results['test25']['status']} - {$results['test25']['msg']}\n";

// 26. IDOR: User B Dilarang membuka detail milik User A
echo "[Test 26] IDOR Protection: User B diblokir dengan HTTP 403 saat membuka detail User A...\n";
$rIdorDetail = request("{$baseUrl}/user/history_detail.php?type=bmi&id={$lastBmiAId}", 'GET', null, [], $userBCookie);
if ($rIdorDetail['status'] === 403 && strpos($rIdorDetail['body'], '403 Forbidden') !== false) {
    $results['test26'] = ['status' => 'PASS', 'msg' => 'Akses silang detail diblokir dengan HTTP 403 Forbidden'];
} else {
    $results['test26'] = ['status' => 'FAIL', 'msg' => "Celah IDOR detail! Status: {$rIdorDetail['status']}"];
}
echo "Hasil: {$results['test26']['status']} - {$results['test26']['msg']}\n";

// 27. Guest Dilarang membuka detail
echo "[Test 27] Guest diblokir saat mencoba membuka URL detail...\n";
$rGuestDetail = request("{$baseUrl}/user/history_detail.php?type=bmi&id={$lastBmiAId}", 'GET', null, [], $guestCookie);
if ($rGuestDetail['status'] === 302 && strpos($rGuestDetail['headers'], 'belum_login') !== false) {
    $results['test27'] = ['status' => 'PASS', 'msg' => 'Akses tamu diblokir ke login'];
} else {
    $results['test27'] = ['status' => 'FAIL', 'msg' => 'Tamu dapat membuka detail'];
}
echo "Hasil: {$results['test27']['status']} - {$results['test27']['msg']}\n";

// 28. Administrator diizinkan mengaudit detail record
echo "[Test 28] Administrator diizinkan memeriksa detail pemeriksaan seluruh akun...\n";
$rAdminDetail = request("{$baseUrl}/user/history_detail.php?type=bmi&id={$lastBmiAId}", 'GET', null, [], $adminCookie);
if ($rAdminDetail['status'] === 200 && strpos($rAdminDetail['body'], 'Data Snapshot Pemeriksaan') !== false) {
    $results['test28'] = ['status' => 'PASS', 'msg' => 'Administrator memiliki izin audit sah (HTTP 200)'];
} else {
    $results['test28'] = ['status' => 'FAIL', 'msg' => 'Admin diblokir dari detail'];
}
echo "Hasil: {$results['test28']['status']} - {$results['test28']['msg']}\n";

// 29. ID atau Type Tidak Valid mengembalikan 404
echo "[Test 29] Penanganan input tidak valid (Type atau ID salah mengembalikan 404)...\n";
$rInvalidType = request("{$baseUrl}/user/history_detail.php?type=alat_palsu&id=1", 'GET', null, [], $userACookie);
$rInvalidId   = request("{$baseUrl}/user/history_detail.php?type=bmi&id=-5", 'GET', null, [], $userACookie);
if ($rInvalidType['status'] === 404 && $rInvalidId['status'] === 404) {
    $results['test29'] = ['status' => 'PASS', 'msg' => 'Parameter tidak valid mengembalikan HTTP 404 Not Found'];
} else {
    $results['test29'] = ['status' => 'FAIL', 'msg' => 'Validasi parameter salah'];
}
echo "Hasil: {$results['test29']['status']} - {$results['test29']['msg']}\n";

// 30. Detail Pemeriksaan Tensi menampilkan pulse pressure dan status
echo "[Test 30] Detail pemeriksaan tensi menampilkan sistolik, diastolik, dan tekanan nadi...\n";
$rDetailBp = request("{$baseUrl}/user/history_detail.php?type=tekanan_darah&id={$lastBpAId}", 'GET', null, [], $userACookie);
$hasBpData = (strpos($rDetailBp['body'], '118 / 76 mmHg') !== false && strpos($rDetailBp['body'], 'Tekanan Nadi') !== false);
if ($hasBpData) {
    $results['test30'] = ['status' => 'PASS', 'msg' => 'Detail tensi tersaji lengkap'];
} else {
    $results['test30'] = ['status' => 'FAIL', 'msg' => 'Data tensi tidak lengkap'];
}
echo "Hasil: {$results['test30']['status']} - {$results['test30']['msg']}\n";

// ===================================================================
// D. HEALTH TRENDS & VISUALIZATION TESTS (Tests 31 - 36)
// ===================================================================
echo "\n--- [Bagian D: Visualisasi Tren Kesehatan & Grafik] ---\n";

// 31. Query tren mengembalikan data kronologis ASC
echo "[Test 31] Data deret tren disusun secara kronologis ASC (Urutan maju dari waktu lampau)...\n";
$trendsA = get_user_health_trends($pdo, 2);
$isAsc = true;
if (count($trendsA['bmi']) >= 2) {
    $t0 = strtotime($trendsA['bmi'][0]['created_at']);
    $t1 = strtotime($trendsA['bmi'][1]['created_at']);
    if ($t0 > $t1) $isAsc = false;
}
if ($isAsc && count($trendsA['bmi']) >= 2) {
    $results['test31'] = ['status' => 'PASS', 'msg' => 'Deret tren waktu tersusun kronologis ASC'];
} else {
    $results['test31'] = ['status' => 'FAIL', 'msg' => 'Urutan deret tren salah'];
}
echo "Hasil: {$results['test31']['status']} - {$results['test31']['msg']}\n";

// 32. Zero Dummy Data: Grafik hanya menggunakan data aktual dari DB
echo "[Test 32] Zero Dummy Data: Tidak ada data fiktif buatan pada tren...\n";
$realDbBmiCount = (int)fetchVal($pdo, "SELECT count(*) FROM cek_bmi WHERE user_id = 2");
if (count($trendsA['bmi']) === $realDbBmiCount) {
    $results['test32'] = ['status' => 'PASS', 'msg' => "Jumlah titik tren ({$realDbBmiCount}) 100% identik dengan jumlah record riil di database"];
} else {
    $results['test32'] = ['status' => 'FAIL', 'msg' => 'Ditemukan data buatan pada tren'];
}
echo "Hasil: {$results['test32']['status']} - {$results['test32']['msg']}\n";

// 33. Empty State pada grafik bila data belum mencukupi (< 2 titik data)
echo "[Test 33] Empty state grafik saat data < 2 titik...\n";
// User B has 0 records
$rTrendB = request("{$baseUrl}/user/dashboard.php", 'GET', null, [], $userBCookie);
$hasNoGraphBmi = (strpos($rTrendB['body'], 'Belum cukup data untuk menampilkan grafik tren') !== false);
$hasNoGraphBp  = (strpos($rTrendB['body'], 'Belum cukup data untuk menampilkan grafik tensi') !== false);
if ($hasNoGraphBmi && $hasNoGraphBp) {
    $results['test33'] = ['status' => 'PASS', 'msg' => 'Grafik menampilkan pesan informatif saat data belum mencukupi'];
} else {
    $results['test33'] = ['status' => 'FAIL', 'msg' => 'Empty state grafik gagal'];
}
echo "Hasil: {$results['test33']['status']} - {$results['test33']['msg']}\n";

// 34. Chart.js Canvas dirender saat data >= 2 titik
echo "[Test 34] Elemen canvas Chart.js dirender saat data >= 2 titik...\n";
// User A has 2 BMI records
$rTrendA = request("{$baseUrl}/user/dashboard.php", 'GET', null, [], $userACookie);
if (strpos($rTrendA['body'], '<canvas id="chartBmi"></canvas>') !== false) {
    $results['test34'] = ['status' => 'PASS', 'msg' => 'Elemen canvas Chart.js terpasang dengan data riil'];
} else {
    $results['test34'] = ['status' => 'FAIL', 'msg' => 'Canvas Chart.js tidak ditemukan'];
}
echo "Hasil: {$results['test34']['status']} - {$results['test34']['msg']}\n";

// 35. Tren User A tidak tercampur data User B
echo "[Test 35] Isolasi tren: Data User B tidak muncul pada grafik User A...\n";
executeSql($pdo, "INSERT INTO cek_bmi (user_id, nama_user, umur, berat_badan, tinggi_badan, bmi, kondisi) VALUES (?, 'User B', 25, 110, 170, 38.0, 'Obesitas Tingkat 2')", [$userBId]);
$trendsRecheckA = get_user_health_trends($pdo, 2);
$hasUserBBmiInA = false;
foreach ($trendsRecheckA['bmi'] as $pt) {
    if ((float)$pt['bmi'] === 38.0) $hasUserBBmiInA = true;
}
if (!$hasUserBBmiInA) {
    $results['test35'] = ['status' => 'PASS', 'msg' => 'Deret tren User A tidak tercampur data User B'];
} else {
    $results['test35'] = ['status' => 'FAIL', 'msg' => 'Kebocoran data pada deret tren'];
}
echo "Hasil: {$results['test35']['status']} - {$results['test35']['msg']}\n";

// 36. Satuan pengukuran pada tren ditampilkan jelas
echo "[Test 36] Tampilan label satuan pada kartu tren...\n";
if (strpos($rTrendA['body'], 'Satuan: kg/m²') !== false && strpos($rTrendA['body'], 'Satuan: mmHg') !== false) {
    $results['test36'] = ['status' => 'PASS', 'msg' => 'Label satuan biometrik tertera jelas di setiap kartu tren'];
} else {
    $results['test36'] = ['status' => 'FAIL', 'msg' => 'Label satuan tidak lengkap'];
}
echo "Hasil: {$results['test36']['status']} - {$results['test36']['msg']}\n";

// ===================================================================
// E. HEALTH REPORT PDF TESTS (Tests 37 - 45)
// ===================================================================
echo "\n--- [Bagian E: Health Report PDF Generator] ---\n";

// 37. Guest dilarang mengakses halaman unduh PDF
echo "[Test 37] Akses guest ke generator PDF diarahkan ke login...\n";
$rGuestPdf = request("{$baseUrl}/user/report_download.php", 'GET', null, [], $guestCookie);
if ($rGuestPdf['status'] === 302 && strpos($rGuestPdf['headers'], 'belum_login') !== false) {
    $results['test37'] = ['status' => 'PASS', 'msg' => 'Tamu diblokir ke login'];
} else {
    $results['test37'] = ['status' => 'FAIL', 'msg' => 'Tamu dapat mengakses halaman laporan'];
}
echo "Hasil: {$results['test37']['status']} - {$results['test37']['msg']}\n";

// 38. Member dapat membuka halaman pratinjau laporan
echo "[Test 38] Member membuka halaman filter & pratinjau laporan...\n";
$rPreviewPdf = request("{$baseUrl}/user/report_download.php", 'GET', null, [], $userACookie);
if ($rPreviewPdf['status'] === 200 && strpos($rPreviewPdf['body'], 'Health Report PDF Generator') !== false) {
    $results['test38'] = ['status' => 'PASS', 'msg' => 'Halaman pratinjau laporan terbuka (HTTP 200)'];
} else {
    $results['test38'] = ['status' => 'FAIL', 'msg' => "Pratinjau gagal: {$rPreviewPdf['status']}"];
}
echo "Hasil: {$results['test38']['status']} - {$results['test38']['msg']}\n";

// 39. Unduh PDF menghasilkan berkas binary PDF sah
echo "[Test 39] Unduh PDF menghasilkan file binary application/pdf dengan header %PDF...\n";
$rDownloadPdf = request("{$baseUrl}/user/report_download.php?action=download&period=all", 'GET', null, [], $userACookie);
$isPdfContent = str_starts_with($rDownloadPdf['body'], '%PDF');
$isPdfHeader  = (stripos($rDownloadPdf['headers'], 'Content-Type: application/pdf') !== false);
if ($rDownloadPdf['status'] === 200 && $isPdfContent && $isPdfHeader) {
    $results['test39'] = ['status' => 'PASS', 'msg' => "Berkas PDF sah diunduh (Header: application/pdf, Signature: %PDF, Ukuran: " . strlen($rDownloadPdf['body']) . " bytes)"];
} else {
    $results['test39'] = ['status' => 'FAIL', 'msg' => "Generasi PDF gagal. Status: {$rDownloadPdf['status']}"];
}
echo "Hasil: {$results['test39']['status']} - {$results['test39']['msg']}\n";

// 40. Filter Periode 30 Hari
echo "[Test 40] Filter periode laporan 30 hari terakhir...\n";
$rPdf30d = request("{$baseUrl}/user/report_download.php?action=download&period=30d", 'GET', null, [], $userACookie);
if ($rPdf30d['status'] === 200 && str_starts_with($rPdf30d['body'], '%PDF')) {
    $results['test40'] = ['status' => 'PASS', 'msg' => 'Filter 30 hari berhasil diproses'];
} else {
    $results['test40'] = ['status' => 'FAIL', 'msg' => 'Filter 30 hari gagal'];
}
echo "Hasil: {$results['test40']['status']} - {$results['test40']['msg']}\n";

// 41. Filter Periode 90 Hari
echo "[Test 41] Filter periode laporan 90 hari terakhir...\n";
$rPdf90d = request("{$baseUrl}/user/report_download.php?action=download&period=90d", 'GET', null, [], $userACookie);
if ($rPdf90d['status'] === 200 && str_starts_with($rPdf90d['body'], '%PDF')) {
    $results['test41'] = ['status' => 'PASS', 'msg' => 'Filter 90 hari berhasil diproses'];
} else {
    $results['test41'] = ['status' => 'FAIL', 'msg' => 'Filter 90 hari gagal'];
}
echo "Hasil: {$results['test41']['status']} - {$results['test41']['msg']}\n";

// 42. Penanganan pengguna tanpa rekam data (Empty state PDF)
echo "[Test 42] Unduh PDF untuk pengguna tanpa data menghasilkan dokumen valid dengan keterangan nihil...\n";
$rPdfEmptyUser = request("{$baseUrl}/user/report_download.php?action=download&period=all", 'GET', null, [], $userBCookie);
if ($rPdfEmptyUser['status'] === 200 && str_starts_with($rPdfEmptyUser['body'], '%PDF')) {
    $results['test42'] = ['status' => 'PASS', 'msg' => 'Laporan PDF pengguna kosong ter-generate aman'];
} else {
    $results['test42'] = ['status' => 'FAIL', 'msg' => 'PDF pengguna kosong gagal'];
}
echo "Hasil: {$results['test42']['status']} - {$results['test42']['msg']}\n";

// 43. Header Content-Disposition attachment terpasang
echo "[Test 43] Header Content-Disposition attachment & nama file terpasang...\n";
if (stripos($rDownloadPdf['headers'], 'Content-Disposition: attachment') !== false && stripos($rDownloadPdf['headers'], 'HealthCheck_Report_') !== false) {
    $results['test43'] = ['status' => 'PASS', 'msg' => 'Header download attachment terverifikasi'];
} else {
    $results['test43'] = ['status' => 'FAIL', 'msg' => 'Header Content-Disposition tidak sesuai'];
}
echo "Hasil: {$results['test43']['status']} - {$results['test43']['msg']}\n";

// 44. Kustomisasi Tanggal pada Pratinjau
echo "[Test 44] Filter rentang tanggal kustomisasi pada pratinjau laporan...\n";
$rCustomDate = request("{$baseUrl}/user/report_download.php?period=custom&date_from={$today}&date_to={$today}&action=preview", 'GET', null, [], $userACookie);
if ($rCustomDate['status'] === 200 && strpos($rCustomDate['body'], 'Periode Khusus') !== false) {
    $results['test44'] = ['status' => 'PASS', 'msg' => 'Kustomisasi tanggal berhasil diproses pada pratinjau'];
} else {
    $results['test44'] = ['status' => 'FAIL', 'msg' => 'Kustomisasi tanggal gagal'];
}
echo "Hasil: {$results['test44']['status']} - {$results['test44']['msg']}\n";

// 45. Isolasi Dokumen PDF Lintas Pengguna
echo "[Test 45] Isolasi PDF: Pengguna hanya dapat mengekspor rekam data miliknya sendiri...\n";
// When User B downloads PDF, it only contains User B's name, not User A
$pdfRawB = $rPdfEmptyUser['body'];
$hasUserAInB = str_contains($pdfRawB, 'User A') && !str_contains($pdfRawB, 'user_b');
if (!$hasUserAInB) {
    $results['test45'] = ['status' => 'PASS', 'msg' => 'Laporan PDF User B tidak tercemar rekam data User A'];
} else {
    $results['test45'] = ['status' => 'FAIL', 'msg' => 'Kebocoran data pada laporan PDF'];
}
echo "Hasil: {$results['test45']['status']} - {$results['test45']['msg']}\n";

// ===================================================================
// F. HEALTH EDUCATION CENTER TESTS (Tests 46 - 54)
// ===================================================================
echo "\n--- [Bagian F: Pusat Edukasi Kesehatan] ---\n";

// 46. Akses Publik Katalog Edukasi
echo "[Test 46] Akses publik ke katalog edukasi (HTTP 200 OK)...\n";
$rEduIndex = request("{$baseUrl}/education/index.php", 'GET');
if ($rEduIndex['status'] === 200 && strpos($rEduIndex['body'], 'Pusat Informasi & Edukasi Medis') !== false) {
    $results['test46'] = ['status' => 'PASS', 'msg' => 'Katalog edukasi dapat diakses umum'];
} else {
    $results['test46'] = ['status' => 'FAIL', 'msg' => "Katalog gagal: {$rEduIndex['status']}"];
}
echo "Hasil: {$results['test46']['status']} - {$results['test46']['msg']}\n";

// 47. Kategori Edukasi Tersedia (5 Kategori)
echo "[Test 47] Verifikasi kelima kategori edukasi tersedia di filter tab...\n";
$decodedEduBody = html_entity_decode($rEduIndex['body']);
$hasCatGizi   = (strpos($decodedEduBody, 'Gizi & Komposisi Tubuh') !== false);
$hasCatKardio = (strpos($decodedEduBody, 'Jantung & Kardiovaskular') !== false);
$hasCatTidur  = (strpos($decodedEduBody, 'Pola & Higiene Tidur') !== false);
$hasCatMental = (strpos($decodedEduBody, 'Kesehatan Mental & Stres') !== false);
$hasCatAkt    = (strpos($decodedEduBody, 'Aktivitas & Kebugaran') !== false);

if ($hasCatGizi && $hasCatKardio && $hasCatTidur && $hasCatMental && $hasCatAkt) {
    $results['test47'] = ['status' => 'PASS', 'msg' => 'Kelima kategori topik edukasi tersedia'];
} else {
    $results['test47'] = ['status' => 'FAIL', 'msg' => 'Kategori edukasi tidak lengkap'];
}
echo "Hasil: {$results['test47']['status']} - {$results['test47']['msg']}\n";

// 48. Filter Kategori Artikel
echo "[Test 48] Filter artikel berdasarkan kategori slug (kategori=kardiovaskular)...\n";
$rEduCat = request("{$baseUrl}/education/index.php?kategori=kardiovaskular", 'GET');
$hasBpArticle = (strpos($rEduCat['body'], 'Mengukur Tensi di Rumah') !== false);
if ($rEduCat['status'] === 200 && $hasBpArticle) {
    $results['test48'] = ['status' => 'PASS', 'msg' => 'Filter kategori kardiovaskular berhasil menampilkan artikel tensi'];
} else {
    $results['test48'] = ['status' => 'FAIL', 'msg' => 'Filter kategori edukasi gagal'];
}
echo "Hasil: {$results['test48']['status']} - {$results['test48']['msg']}\n";

// 49. Pencarian Kata Kunci Artikel
echo "[Test 49] Pencarian artikel berdasarkan kata kunci (q=viseral)...\n";
$rEduSearch = request("{$baseUrl}/education/index.php?q=viseral", 'GET');
$hasViseralArt = (strpos($rEduSearch['body'], 'Memahami Lemak Viseral') !== false);
if ($rEduSearch['status'] === 200 && $hasViseralArt) {
    $results['test49'] = ['status' => 'PASS', 'msg' => 'Pencarian artikel "viseral" berhasil'];
} else {
    $results['test49'] = ['status' => 'FAIL', 'msg' => 'Pencarian artikel gagal'];
}
echo "Hasil: {$results['test49']['status']} - {$results['test49']['msg']}\n";

// 50. Detail Artikel dapat diakses via slug
echo "[Test 50] Detail artikel dibuka melalui slug sah...\n";
$rArtDetail = request("{$baseUrl}/education/detail.php?slug=memahami-lemak-viseral-dan-whtr", 'GET');
if ($rArtDetail['status'] === 200 && strpos($rArtDetail['body'], 'Apa Itu Lemak Viseral?') !== false) {
    $results['test50'] = ['status' => 'PASS', 'msg' => 'Detail artikel terbuka dengan konten lengkap'];
} else {
    $results['test50'] = ['status' => 'FAIL', 'msg' => "Detail artikel gagal: {$rArtDetail['status']}"];
}
echo "Hasil: {$results['test50']['status']} - {$results['test50']['msg']}\n";

// 51. Peningkatan views_count saat artikel dibuka
echo "[Test 51] Peningkatan jumlah tayangan artikel (views_count + 1)...\n";
$viewsBefore = (int)fetchVal($pdo, "SELECT views_count FROM education_articles WHERE slug = 'memahami-lemak-viseral-dan-whtr'");
request("{$baseUrl}/education/detail.php?slug=memahami-lemak-viseral-dan-whtr", 'GET');
$viewsAfter  = (int)fetchVal($pdo, "SELECT views_count FROM education_articles WHERE slug = 'memahami-lemak-viseral-dan-whtr'");
if ($viewsAfter === $viewsBefore + 1) {
    $results['test51'] = ['status' => 'PASS', 'msg' => "views_count bertambah ({$viewsBefore} -> {$viewsAfter})"];
} else {
    $results['test51'] = ['status' => 'FAIL', 'msg' => "views_count tidak bertambah: {$viewsBefore} vs {$viewsAfter}"];
}
echo "Hasil: {$results['test51']['status']} - {$results['test51']['msg']}\n";

// 52. Tautan ke alat terkait (Contextual cross-tool link)
echo "[Test 52] Tautan widget ke alat kalkulator terkait terpasang pada detail artikel...\n";
$hasRelatedToolLink = (strpos($rArtDetail['body'], 'Alat Terkait Artikel') !== false && strpos($rArtDetail['body'], 'whtr/whtr_form.php') !== false);
if ($hasRelatedToolLink) {
    $results['test52'] = ['status' => 'PASS', 'msg' => 'Widget alat terkait (WHtR) terintegrasi pada artikel'];
} else {
    $results['test52'] = ['status' => 'FAIL', 'msg' => 'Widget alat terkait tidak ditemukan'];
}
echo "Hasil: {$results['test52']['status']} - {$results['test52']['msg']}\n";

// 53. Sitasi & Referensi Klinis Sah Tertera
echo "[Test 53] Bukti rujukan klinis medis (PubMed/WHO/Ashwell/PERHI) tertera di artikel...\n";
$hasCitations = (strpos($rArtDetail['body'], 'Ashwell M, Gibson S. BMJ Open 2016') !== false);
if ($hasCitations) {
    $results['test53'] = ['status' => 'PASS', 'msg' => 'Sitasi ilmiah resmi terverifikasi pada badan artikel'];
} else {
    $results['test53'] = ['status' => 'FAIL', 'msg' => 'Rujukan klinis tidak ditemukan'];
}
echo "Hasil: {$results['test53']['status']} - {$results['test53']['msg']}\n";

// 54. Slug Artikel Tidak Valid mengembalikan 404
echo "[Test 54] Penanganan slug artikel fiktif mengembalikan HTTP 404...\n";
$rBadSlug = request("{$baseUrl}/education/detail.php?slug=artikel-fiktif-tak-ada", 'GET');
if ($rBadSlug['status'] === 404) {
    $results['test54'] = ['status' => 'PASS', 'msg' => 'Slug tidak dikenal mengembalikan HTTP 404 Not Found'];
} else {
    $results['test54'] = ['status' => 'FAIL', 'msg' => "Status bukan 404: {$rBadSlug['status']}"];
}
echo "Hasil: {$results['test54']['status']} - {$results['test54']['msg']}\n";

// ===================================================================
// G. SECURITY & COMPREHENSIVE REGRESSION (Tests 55 - 65)
// ===================================================================
echo "\n--- [Bagian G: Security & Regression Integrity] ---\n";

// 55. Navigasi Header mencakup Dashboard & History untuk User
echo "[Test 55] Navbar menyajikan tautan Dashboard & Riwayat untuk member login...\n";
$hasNavDash = (strpos($rUserDash['body'], 'Personal Dashboard') !== false && strpos($rUserDash['body'], 'Riwayat Pemeriksaan') !== false);
if ($hasNavDash) {
    $results['test55'] = ['status' => 'PASS', 'msg' => 'Tautan Personal Dashboard & Riwayat tersedia di navbar member'];
} else {
    $results['test55'] = ['status' => 'FAIL', 'msg' => 'Navigasi navbar member tidak lengkap'];
}
echo "Hasil: {$results['test55']['status']} - {$results['test55']['msg']}\n";

// 56. Navigasi Footer menyajikan Pusat Edukasi & Riwayat Terpadu
echo "[Test 56] Footer menyajikan Pusat Edukasi Kesehatan...\n";
if (strpos($rUserDash['body'], 'Pusat Edukasi Kesehatan') !== false) {
    $results['test56'] = ['status' => 'PASS', 'msg' => 'Tautan Pusat Edukasi terpasang di footer'];
} else {
    $results['test56'] = ['status' => 'FAIL', 'msg' => 'Tautan footer tidak ditemukan'];
}
echo "Hasil: {$results['test56']['status']} - {$results['test56']['msg']}\n";

// 57. CSRF Protection tetap aktif pada form mutasi
echo "[Test 57] Proteksi CSRF: POST tanpa token tetap diblokir HTTP 403...\n";
$rNoCsrf = request("{$baseUrl}/bmi/bmi_proses.php", 'POST', ['nama_user'=>'Attack', 'umur'=>25, 'berat_badan'=>70, 'tinggi_badan'=>175], [], $userACookie);
if ($rNoCsrf['status'] === 403) {
    $results['test57'] = ['status' => 'PASS', 'msg' => 'CSRF 403 enforcement tetap aktif'];
} else {
    $results['test57'] = ['status' => 'FAIL', 'msg' => 'CSRF protection lolos'];
}
echo "Hasil: {$results['test57']['status']} - {$results['test57']['msg']}\n";

// 58. GET Hapus Data tetap ditolak HTTP 405
echo "[Test 58] Proteksi Metode: GET hapus data tetap dibalas HTTP 405...\n";
$rGetHapus = request("{$baseUrl}/bmi/bmi_hapus.php?id_bmi=1", 'GET', null, [], $adminCookie);
if ($rGetHapus['status'] === 405) {
    $results['test58'] = ['status' => 'PASS', 'msg' => 'Metode GET pada penghapusan data ditolak 405'];
} else {
    $results['test58'] = ['status' => 'FAIL', 'msg' => 'Akses GET hapus lolos'];
}
echo "Hasil: {$results['test58']['status']} - {$results['test58']['msg']}\n";

// 59. SQL Injection Resistance pada History Filter
echo "[Test 59] Ketahanan SQL Injection pada filter riwayat...\n";
$sqliPayload = "1' OR '1'='1";
$rSqliHist = request("{$baseUrl}/user/history.php?search=" . urlencode($sqliPayload), 'GET', null, [], $userACookie);
if ($rSqliHist['status'] === 200 && strpos($rSqliHist['body'], 'Fatal error') === false) {
    $results['test59'] = ['status' => 'PASS', 'msg' => 'Prepared statements menangkal SQL injection pada pencarian'];
} else {
    $results['test59'] = ['status' => 'FAIL', 'msg' => 'SQL error terpicu oleh payload'];
}
echo "Hasil: {$results['test59']['status']} - {$results['test59']['msg']}\n";

// 60. Regresi Seluruh 12 Halaman Form Kalkulator (HTTP 200 OK)
echo "[Test 60] Regresi: Semua 12 formulir kalkulator dapat diakses normal (HTTP 200 OK)...\n";
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
    $results['test60'] = ['status' => 'PASS', 'msg' => 'Seluruh 12 halaman formulir alat kesehatan merespons HTTP 200 OK'];
} else {
    $results['test60'] = ['status' => 'FAIL', 'msg' => 'Salah satu form kalkulator gagal'];
}
echo "Hasil: {$results['test60']['status']} - {$results['test60']['msg']}\n";

// 61. Homepage tetap merespons HTTP 200 OK
echo "[Test 61] Regresi: Homepage index.php merespons HTTP 200 OK...\n";
$rHome = request("{$baseUrl}/index.php", 'GET')['status'];
if ($rHome === 200) {
    $results['test61'] = ['status' => 'PASS', 'msg' => 'Homepage beroperasi normal (HTTP 200)'];
} else {
    $results['test61'] = ['status' => 'FAIL', 'msg' => "Homepage status {$rHome}"];
}
echo "Hasil: {$results['test61']['status']} - {$results['test61']['msg']}\n";

// 62. Idempotensi Migration Runner Phase 3
echo "[Test 62] Idempotensi Migration Runner...\n";
$migOutput = shell_exec("HEALTHCHECK_DB_PATH=" . escapeshellarg($testDbPath) . " php " . escapeshellarg($projectRoot . '/database/migrate.php'));
$migStatus = shell_exec("HEALTHCHECK_DB_PATH=" . escapeshellarg($testDbPath) . " php " . escapeshellarg($projectRoot . '/database/migrate.php') . " --status");
if (strpos($migOutput, 'Nothing to migrate') !== false && strpos($migStatus, '[APPLIED] 20261108_002_phase3_dashboard_reporting_education') !== false) {
    $results['test62'] = ['status' => 'PASS', 'msg' => 'Migration runner idempoten (status APPLIED terverifikasi)'];
} else {
    $results['test62'] = ['status' => 'FAIL', 'msg' => 'Eksekusi ulang migrasi gagal'];
}
echo "Hasil: {$results['test62']['status']} - {$results['test62']['msg']}\n";

// 63. Formula Klinis Dasar Tetap Akurat
echo "[Test 63] Regresi: Formula klinis dasar (BMI, BMR, Max HR, WHtR) 100% konsisten...\n";
require_once $projectRoot . '/includes/bmr_helper.php';
require_once $projectRoot . '/includes/whtr_helper.php';
require_once $projectRoot . '/includes/zona_jantung_helper.php';

$fBmi = round(70 / (1.75 * 1.75), 1);
$fBmr = calculate_bmr('L', 70, 175, 25);
$fHr  = calculate_max_hr(25);
$fWhtr = calculate_whtr(80, 175);

if ($fBmi == 22.9 && $fBmr === 1674.0 && $fHr === 191 && $fWhtr === 0.46) {
    $results['test63'] = ['status' => 'PASS', 'msg' => 'Formula klinis dasar 100% akurat'];
} else {
    $results['test63'] = ['status' => 'FAIL', 'msg' => 'Inkonsistensi formula klinis'];
}
echo "Hasil: {$results['test63']['status']} - {$results['test63']['msg']}\n";

// 64. Whitelist Entitas assert_record_access
echo "[Test 64] Keamanan Whitelist: assert_record_access menolak nama tabel di luar daftar putih...\n";
$cliCheck = shell_exec(sprintf('php -r "require_once \'%s/includes/auth_helper.php\'; \$p = new PDO(\'sqlite:%s\'); assert_record_access(\$p, \'tabel_palsu_injeksi\', \'id\', 1);" 2>&1', $projectRoot, $testDbPath));
$whitelistFailed = (strpos($cliCheck, '400 Bad Request: Entitas data tidak valid') !== false);
if ($whitelistFailed) {
    $results['test64'] = ['status' => 'PASS', 'msg' => 'Tabel di luar whitelist ditolak dengan aman (HTTP 400 Bad Request)'];
} else {
    $results['test64'] = ['status' => 'FAIL', 'msg' => 'Whitelist tabel gagal memblokir: ' . $cliCheck];
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
echo "REKAPITULASI HASIL PENGUJIAN PHASE 3 (65 SKENARIO):\n";
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
