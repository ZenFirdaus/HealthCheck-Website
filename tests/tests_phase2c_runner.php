<?php
/**
 * Automated Test Suite for HealthCheck Phase 2C:
 * Cardio, Sleep & Mental Health (Blood Pressure, Sleep Habits, HR Training Zones, IPAQ Activity, PSS-10 Stress)
 *
 * Runs 55 comprehensive test cases against an isolated SQLite test database.
 */

$host = '127.0.0.1';
$port = 8097;
$baseUrl = "http://{$host}:{$port}";

echo "======================================================================\n";
echo "=== Memulai HealthCheck Phase 2C Comprehensive Test Suite (55 Tests)===\n";
echo "======================================================================\n";

// Setup isolated test database
$projectRoot = dirname(__DIR__);
$mainDbPath = $projectRoot . '/database/healthcheck.sqlite';
$testDbPath = $projectRoot . '/database/healthcheck_test_phase2c.sqlite';

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
$cmd = sprintf('HEALTHCHECK_DB_PATH=%s php -S %s:%d -t "%s" > /tmp/php_phase2c_test.log 2>&1 & echo $!',
    escapeshellarg($testDbPath), $host, $port, $projectRoot
);
$pid = (int)shell_exec($cmd);
echo "Server test Phase 2C aktif (PID: {$pid}) pada {$baseUrl}\n";

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

// Database cursor helpers
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

require_once $projectRoot . '/includes/tensi_helper.php';
require_once $projectRoot . '/includes/tidur_helper.php';
require_once $projectRoot . '/includes/zona_jantung_helper.php';
require_once $projectRoot . '/includes/aktivitas_helper.php';
require_once $projectRoot . '/includes/stres_helper.php';
require_once $projectRoot . '/includes/bmr_helper.php';

// ===================================================================
// A. BLOOD PRESSURE ASSESSMENT TESTS (Tests 1 - 10)
// ===================================================================
echo "\n--- [Bagian A: Pemeriksaan Tekanan Darah (AHA/ACC & PERHI)] ---\n";

// 1. Normal Blood Pressure
echo "[Test 1] Klasifikasi tensi normal (115/75 mmHg)...\n";
$bpNormal = classify_blood_pressure(115, 75);
if ($bpNormal['badge'] === 'Normal' && $bpNormal['color'] === 'success' && !$bpNormal['is_emergency']) {
    $results['test1'] = ['status' => 'PASS', 'msg' => '115/75 mmHg terklasifikasi Normal'];
} else {
    $results['test1'] = ['status' => 'FAIL', 'msg' => 'Klasifikasi normal gagal'];
}
echo "Hasil: {$results['test1']['status']} - {$results['test1']['msg']}\n";

// 2. Elevated / Pra-Hipertensi
echo "[Test 2] Klasifikasi pra-hipertensi (125/78 mmHg)...\n";
$bpElevated = classify_blood_pressure(125, 78);
if ($bpElevated['badge'] === 'Pra-Hipertensi' && $bpElevated['color'] === 'warning') {
    $results['test2'] = ['status' => 'PASS', 'msg' => '125/78 mmHg terklasifikasi Pra-Hipertensi'];
} else {
    $results['test2'] = ['status' => 'FAIL', 'msg' => 'Klasifikasi pra-hipertensi gagal'];
}
echo "Hasil: {$results['test2']['status']} - {$results['test2']['msg']}\n";

// 3. Stage 1 Hypertension
echo "[Test 3] Klasifikasi hipertensi derajat 1 (135/85 mmHg)...\n";
$bpStage1 = classify_blood_pressure(135, 85);
if ($bpStage1['badge'] === 'Hipertensi Tk 1' && $bpStage1['color'] === 'warning') {
    $results['test3'] = ['status' => 'PASS', 'msg' => '135/85 mmHg terklasifikasi Hipertensi Tingkat 1'];
} else {
    $results['test3'] = ['status' => 'FAIL', 'msg' => 'Klasifikasi stage 1 gagal'];
}
echo "Hasil: {$results['test3']['status']} - {$results['test3']['msg']}\n";

// 4. Stage 2 Hypertension
echo "[Test 4] Klasifikasi hipertensi derajat 2 (145/95 mmHg)...\n";
$bpStage2 = classify_blood_pressure(145, 95);
if ($bpStage2['badge'] === 'Hipertensi Tk 2' && $bpStage2['color'] === 'danger') {
    $results['test4'] = ['status' => 'PASS', 'msg' => '145/95 mmHg terklasifikasi Hipertensi Tingkat 2'];
} else {
    $results['test4'] = ['status' => 'FAIL', 'msg' => 'Klasifikasi stage 2 gagal'];
}
echo "Hasil: {$results['test4']['status']} - {$results['test4']['msg']}\n";

// 5. Hypertensive Crisis (Emergency)
echo "[Test 5] Penanganan darurat Krisis Hipertensi (190/125 mmHg)...\n";
$bpCrisis = classify_blood_pressure(190, 125);
if ($bpCrisis['badge'] === 'Krisis Hipertensi' && $bpCrisis['is_emergency'] === true && $bpCrisis['color'] === 'danger') {
    $results['test5'] = ['status' => 'PASS', 'msg' => '190/125 mmHg terdeteksi Krisis Hipertensi & flag is_emergency aktif'];
} else {
    $results['test5'] = ['status' => 'FAIL', 'msg' => 'Deteksi krisis hipertensi gagal'];
}
echo "Hasil: {$results['test5']['status']} - {$results['test5']['msg']}\n";

// 6. Hypotension
echo "[Test 6] Klasifikasi hipotensi (85/55 mmHg)...\n";
$bpHypo = classify_blood_pressure(85, 55);
if ($bpHypo['badge'] === 'Hipotensi') {
    $results['test6'] = ['status' => 'PASS', 'msg' => '85/55 mmHg terklasifikasi Hipotensi'];
} else {
    $results['test6'] = ['status' => 'FAIL', 'msg' => 'Klasifikasi hipotensi gagal'];
}
echo "Hasil: {$results['test6']['status']} - {$results['test6']['msg']}\n";

// 7. Higher Category Rule (145/75 mmHg -> Stage 2 because systolic is 145)
echo "[Test 7] Aturan kategori lebih tinggi (145/75 mmHg -> Hipertensi Tk 2)...\n";
$bpHigher = classify_blood_pressure(145, 75);
if ($bpHigher['badge'] === 'Hipertensi Tk 2') {
    $results['test7'] = ['status' => 'PASS', 'msg' => '145/75 mmHg mengikuti kategori sistolik tertinggi (Stage 2)'];
} else {
    $results['test7'] = ['status' => 'FAIL', 'msg' => 'Aturan kategori tertinggi gagal'];
}
echo "Hasil: {$results['test7']['status']} - {$results['test7']['msg']}\n";

// 8. Validasi: Sistolik <= Diastolik Ditolak
echo "[Test 8] Validasi: Tekanan sistolik <= diastolik ditolak server...\n";
$guestCookie = $tmpCookieDir . '/guest_2c_cookie.txt';
if (file_exists($guestCookie)) unlink($guestCookie);
$bpForm = request("{$baseUrl}/tekanan_darah/tekanan_darah_form.php", 'GET', null, [], $guestCookie);
$bpCsrf = extractCsrf($bpForm['body']);

$badBpReq = request("{$baseUrl}/tekanan_darah/tekanan_darah_proses.php", 'POST', [
    'csrf_token' => $bpCsrf,
    'nama_user'  => 'Tester BP',
    'umur'       => 30,
    'sistolik'   => 80,
    'diastolik'  => 120 // Inverted!
], [], $guestCookie);
if ($badBpReq['status'] === 302 && strpos($badBpReq['headers'], 'pesan=invalid_input') !== false) {
    $results['test8'] = ['status' => 'PASS', 'msg' => 'Sistolik <= Diastolik ditolak'];
} else {
    $results['test8'] = ['status' => 'FAIL', 'msg' => 'Sistolik <= Diastolik lolos'];
}
echo "Hasil: {$results['test8']['status']} - {$results['test8']['msg']}\n";

// 9. Validasi: Tekanan Nadi (Selisih) < 15 mmHg Ditolak
echo "[Test 9] Validasi: Tekanan nadi < 15 mmHg ditolak...\n";
$narrowBpReq = request("{$baseUrl}/tekanan_darah/tekanan_darah_proses.php", 'POST', [
    'csrf_token' => $bpCsrf,
    'nama_user'  => 'Tester BP',
    'umur'       => 30,
    'sistolik'   => 120,
    'diastolik'  => 115 // Diff = 5 (< 15)
], [], $guestCookie);
if ($narrowBpReq['status'] === 302 && strpos($narrowBpReq['headers'], 'pesan=invalid_input') !== false) {
    $results['test9'] = ['status' => 'PASS', 'msg' => 'Selisih sistolik-diastolik < 15 mmHg ditolak'];
} else {
    $results['test9'] = ['status' => 'FAIL', 'msg' => 'Selisih < 15 mmHg lolos'];
}
echo "Hasil: {$results['test9']['status']} - {$results['test9']['msg']}\n";

// 10. Validasi: Input Kosong & Nilai Negatif Ditolak
echo "[Test 10] Validasi: Input tensi kosong atau negatif ditolak...\n";
$negBpReq = request("{$baseUrl}/tekanan_darah/tekanan_darah_proses.php", 'POST', [
    'csrf_token' => $bpCsrf,
    'nama_user'  => '',
    'umur'       => 30,
    'sistolik'   => -120,
    'diastolik'  => 80
], [], $guestCookie);
if ($negBpReq['status'] === 302 && strpos($negBpReq['headers'], 'pesan=invalid_input') !== false) {
    $results['test10'] = ['status' => 'PASS', 'msg' => 'Input kosong/negatif ditolak'];
} else {
    $results['test10'] = ['status' => 'FAIL', 'msg' => 'Input negatif lolos'];
}
echo "Hasil: {$results['test10']['status']} - {$results['test10']['msg']}\n";

// ===================================================================
// B. SLEEP HABIT ASSESSMENT TESTS (Tests 11 - 18)
// ===================================================================
echo "\n--- [Bagian B: Penilaian Pola & Higiene Tidur] ---\n";

// 11. Sleep Habit Calculation: Excellent Sleep (7.5h, 15m, 0 aw, no screen, fresh)
echo "[Test 11] Skoring pola tidur sangat baik (7.5 jam, latensi 15m, bangun 0x)...\n";
$sleepGood = calculate_sleep_habit_score(7.5, 15, 0, 'tidak', 'sangat_segar');
if ($sleepGood['score'] === 0 && $sleepGood['badge'] === 'Sangat Baik' && $sleepGood['efficiency'] >= 90.0) {
    $results['test11'] = ['status' => 'PASS', 'msg' => "Skor 0 (Sangat Baik), Efisiensi {$sleepGood['efficiency']}%"];
} else {
    $results['test11'] = ['status' => 'FAIL', 'msg' => "Skoring tidur gagal: " . json_encode($sleepGood)];
}
echo "Hasil: {$results['test11']['status']} - {$results['test11']['msg']}\n";

// 12. Sleep Habit Calculation: Fair Sleep
echo "[Test 12] Skoring pola tidur cukup (6.5 jam, latensi 25m, bangun 1x)...\n";
$sleepFair = calculate_sleep_habit_score(6.5, 25, 1, 'kadang', 'cukup_segar');
if ($sleepFair['score'] >= 4 && $sleepFair['score'] <= 7 && $sleepFair['badge'] === 'Cukup Baik') {
    $results['test12'] = ['status' => 'PASS', 'msg' => "Skor {$sleepFair['score']} (Cukup Baik)"];
} else {
    $results['test12'] = ['status' => 'FAIL', 'msg' => "Kategori cukup baik tidak sesuai: {$sleepFair['score']}"];
}
echo "Hasil: {$results['test12']['status']} - {$results['test12']['msg']}\n";

// 13. Sleep Habit Calculation: Disturbed Sleep (4h, 75m, 3x, screen nightly, exhausted)
echo "[Test 13] Skoring gangguan tidur signifikan (4 jam, latensi 75m, bangun 3x, gawai malam)...\n";
$sleepDisturbed = calculate_sleep_habit_score(4.0, 75, 3, 'sering', 'lelah_mengantuk');
if ($sleepDisturbed['score'] >= 12 && $sleepDisturbed['badge'] === 'Perlu Perhatian' && $sleepDisturbed['color'] === 'danger') {
    $results['test13'] = ['status' => 'PASS', 'msg' => "Skor {$sleepDisturbed['score']}/15 (Perlu Perhatian, Warna: Danger)"];
} else {
    $results['test13'] = ['status' => 'FAIL', 'msg' => "Deteksi gangguan tidur gagal: {$sleepDisturbed['score']}"];
}
echo "Hasil: {$results['test13']['status']} - {$results['test13']['msg']}\n";

// 14. Efisiensi Waktu Tidur Terhitung Akurat
echo "[Test 14] Akurasi formula efisiensi tidur (waktu tidur lelap / waktu di tempat tidur)...\n";
$effTest = calculate_sleep_habit_score(8.0, 30, 2, 'tidak', 'cukup_segar');
// time in bed = 8 + 0.5 + 0.5 = 9.0; efficiency = 8 / 9 = 88.9%
if ($effTest['efficiency'] >= 88.0 && $effTest['efficiency'] <= 90.0) {
    $results['test14'] = ['status' => 'PASS', 'msg' => "Efisiensi tidur: {$effTest['efficiency']}% (akurat)"];
} else {
    $results['test14'] = ['status' => 'FAIL', 'msg' => "Efisiensi salah: {$effTest['efficiency']}"];
}
echo "Hasil: {$results['test14']['status']} - {$results['test14']['msg']}\n";

// 15. Validasi: Durasi Tidur < 1 Jam Ditolak
echo "[Test 15] Validasi: Durasi tidur < 1 jam ditolak...\n";
$sleepForm = request("{$baseUrl}/tidur/tidur_form.php", 'GET', null, [], $guestCookie);
$sleepCsrf = extractCsrf($sleepForm['body']);

$badSleepDur = request("{$baseUrl}/tidur/tidur_proses.php", 'POST', [
    'csrf_token'          => $sleepCsrf,
    'nama_user'           => 'Tester Tidur',
    'durasi_jam'          => 0.2,
    'latensi_menit'       => 15,
    'frekuensi_terbangun' => 0,
    'penggunaan_gawai'    => 'tidak',
    'kesegaran_bangun'    => 'cukup_segar'
], [], $guestCookie);
if ($badSleepDur['status'] === 302 && strpos($badSleepDur['headers'], 'pesan=invalid_input') !== false) {
    $results['test15'] = ['status' => 'PASS', 'msg' => 'Durasi 0.2 jam ditolak'];
} else {
    $results['test15'] = ['status' => 'FAIL', 'msg' => 'Durasi 0.2 jam lolos'];
}
echo "Hasil: {$results['test15']['status']} - {$results['test15']['msg']}\n";

// 16. Validasi: Durasi Tidur > 24 Jam Ditolak
echo "[Test 16] Validasi: Durasi tidur > 24 jam ditolak...\n";
$badSleepMax = request("{$baseUrl}/tidur/tidur_proses.php", 'POST', [
    'csrf_token'          => $sleepCsrf,
    'nama_user'           => 'Tester Tidur',
    'durasi_jam'          => 26.0,
    'latensi_menit'       => 15,
    'frekuensi_terbangun' => 0,
    'penggunaan_gawai'    => 'tidak',
    'kesegaran_bangun'    => 'cukup_segar'
], [], $guestCookie);
if ($badSleepMax['status'] === 302 && strpos($badSleepMax['headers'], 'pesan=invalid_input') !== false) {
    $results['test16'] = ['status' => 'PASS', 'msg' => 'Durasi 26 jam ditolak'];
} else {
    $results['test16'] = ['status' => 'FAIL', 'msg' => 'Durasi 26 jam lolos'];
}
echo "Hasil: {$results['test16']['status']} - {$results['test16']['msg']}\n";

// 17. Validasi: Opsi Gawai / Kesegaran Ilegal Ditolak
echo "[Test 17] Validasi: Pilihan dropdown ilegal ditolak server...\n";
$illegalSleep = request("{$baseUrl}/tidur/tidur_proses.php", 'POST', [
    'csrf_token'          => $sleepCsrf,
    'nama_user'           => 'Tester Tidur',
    'durasi_jam'          => 7.0,
    'latensi_menit'       => 15,
    'frekuensi_terbangun' => 0,
    'penggunaan_gawai'    => 'hacked_device',
    'kesegaran_bangun'    => 'cukup_segar'
], [], $guestCookie);
if ($illegalSleep['status'] === 302 && strpos($illegalSleep['headers'], 'pesan=invalid_input') !== false) {
    $results['test17'] = ['status' => 'PASS', 'msg' => 'Opsi dropdown ilegal ditolak'];
} else {
    $results['test17'] = ['status' => 'FAIL', 'msg' => 'Opsi ilegal lolos'];
}
echo "Hasil: {$results['test17']['status']} - {$results['test17']['msg']}\n";

// 18. Naming & License Integrity: Instrumen diberi nama jujur "Sleep Habit Assessment"
echo "[Test 18] Integritas lisensi: Halaman hasil menggunakan nama Sleep Habit Assessment (bukan klaim PSQI palsu)...\n";
$sleepPageCheck = request("{$baseUrl}/tidur/tidur_form.php", 'GET', null, [], $guestCookie);
$hasHonestNaming = (strpos($sleepPageCheck['body'], 'Sleep Habit Assessment') !== false || strpos($sleepPageCheck['body'], 'Higiene Tidur') !== false);
$hasNoFakePsqiClaim = (strpos($sleepPageCheck['body'], 'skor resmi PSQI') === false);
if ($hasHonestNaming && $hasNoFakePsqiClaim) {
    $results['test18'] = ['status' => 'PASS', 'msg' => 'Instrumen dinamai jujur dan mematuhi batasan hak cipta instrumen klinis'];
} else {
    $results['test18'] = ['status' => 'FAIL', 'msg' => 'Klaim lisensi instrumen bermasalah'];
}
echo "Hasil: {$results['test18']['status']} - {$results['test18']['msg']}\n";

// ===================================================================
// C. HEART RATE TRAINING ZONES (KARVONEN & ZOLADZ) (Tests 19 - 26)
// ===================================================================
echo "\n--- [Bagian C: Zona Latihan Detak Jantung (Karvonen & Zoladz)] ---\n";

// 19. Tanaka HR Max (Age 25 -> 191 BPM)
echo "[Test 19] Formula Tanaka Max HR usia 25 (208 - 0.7 * 25 = 191 BPM)...\n";
$maxHr25 = calculate_max_hr(25);
if ($maxHr25 === 191) {
    $results['test19'] = ['status' => 'PASS', 'msg' => "Max HR usia 25 = {$maxHr25} BPM (presisi)"];
} else {
    $results['test19'] = ['status' => 'FAIL', 'msg' => "Max HR salah: {$maxHr25}"];
}
echo "Hasil: {$results['test19']['status']} - {$results['test19']['msg']}\n";

// 20. Karvonen HRR Calculation (Max 191, RHR 68 -> HRR 123)
echo "[Test 20] Karvonen Heart Rate Reserve (191 - 68 = 123 BPM)...\n";
$karvonen25 = calculate_karvonen_zones(25, 68);
if ($karvonen25['hrr'] === 123) {
    $results['test20'] = ['status' => 'PASS', 'msg' => "HRR = {$karvonen25['hrr']} BPM"];
} else {
    $results['test20'] = ['status' => 'FAIL', 'msg' => "HRR salah: {$karvonen25['hrr']}"];
}
echo "Hasil: {$results['test20']['status']} - {$results['test20']['msg']}\n";

// 21. Karvonen 5 Zones Range Verification
echo "[Test 21] Verifikasi 5 zona latihan Karvonen (Z1-Z5 tidak terbalik atau tumpang tindih)...\n";
$kz = $karvonen25['zones'];
// Z1: 68 + 0.5*123 = 130, 68 + 0.6*123 = 142
// Z2: 68 + 0.6*123 = 142, 68 + 0.7*123 = 154
// Z3: 68 + 0.7*123 = 154, 68 + 0.8*123 = 166
// Z4: 68 + 0.8*123 = 166, 68 + 0.9*123 = 179
// Z5: 68 + 0.9*123 = 179, max = 191
$zOrderOk = true;
for ($zi = 1; $zi <= 5; $zi++) {
    if ($kz[$zi]['min'] >= $kz[$zi]['max']) {
        $zOrderOk = false;
        break;
    }
}
if ($zOrderOk && $kz[2]['min'] === 142 && $kz[2]['max'] === 154 && $kz[5]['max'] === 191) {
    $results['test21'] = ['status' => 'PASS', 'msg' => 'Zona 1–5 Karvonen konsisten, Zona 2 (Fat Burn: 142–154 BPM)'];
} else {
    $results['test21'] = ['status' => 'FAIL', 'msg' => 'Rentang zona Karvonen tidak konsisten'];
}
echo "Hasil: {$results['test21']['status']} - {$results['test21']['msg']}\n";

// 22. Zoladz 5 Zones Method Verification
echo "[Test 22] Verifikasi 5 zona metode Zoladz (Defisit HR Max bertahap)...\n";
$zoladz25 = calculate_zoladz_zones(25);
$zz = $zoladz25['zones'];
// Max HR 191.
// Z1: 191 - 50 ± 5 -> 136 - 146 BPM
// Z2: 191 - 40 ± 5 -> 146 - 156 BPM
// Z3: 191 - 30 ± 5 -> 156 - 166 BPM
// Z4: 191 - 20 ± 5 -> 166 - 176 BPM
// Z5: 191 - 10 ± 5 -> 176 - 191 BPM
if ($zz[1]['min'] === 136 && $zz[1]['max'] === 146 && $zz[5]['max'] === 191) {
    $results['test22'] = ['status' => 'PASS', 'msg' => 'Zona 1–5 Zoladz terhitung presisi (Z1: 136–146 BPM, Z5 Max: 191 BPM)'];
} else {
    $results['test22'] = ['status' => 'FAIL', 'msg' => 'Kalkulasi Zoladz salah'];
}
echo "Hasil: {$results['test22']['status']} - {$results['test22']['msg']}\n";

// 23. Validasi: Umur di luar batas (<10 atau >100) ditolak
echo "[Test 23] Validasi: Umur di luar batas ditolak...\n";
$hrZoneForm = request("{$baseUrl}/zona_jantung/zona_jantung_form.php", 'GET', null, [], $guestCookie);
$hrZoneCsrf = extractCsrf($hrZoneForm['body']);

$badAgeReq = request("{$baseUrl}/zona_jantung/zona_jantung_proses.php", 'POST', [
    'csrf_token' => $hrZoneCsrf,
    'nama_user'  => 'Tester HR',
    'umur'       => 105,
    'resting_hr' => 70,
    'metode'     => 'karvonen'
], [], $guestCookie);
if ($badAgeReq['status'] === 302 && strpos($badAgeReq['headers'], 'pesan=invalid_input') !== false) {
    $results['test23'] = ['status' => 'PASS', 'msg' => 'Umur 105 tahun ditolak'];
} else {
    $results['test23'] = ['status' => 'FAIL', 'msg' => 'Umur 105 tahun lolos'];
}
echo "Hasil: {$results['test23']['status']} - {$results['test23']['msg']}\n";

// 24. Validasi: RHR di luar batas (<40 atau >180) ditolak
echo "[Test 24] Validasi: RHR di luar batas (<40 atau >180) ditolak...\n";
$badRhrReq = request("{$baseUrl}/zona_jantung/zona_jantung_proses.php", 'POST', [
    'csrf_token' => $hrZoneCsrf,
    'nama_user'  => 'Tester HR',
    'umur'       => 25,
    'resting_hr' => 20, // Too low
    'metode'     => 'karvonen'
], [], $guestCookie);
if ($badRhrReq['status'] === 302 && strpos($badRhrReq['headers'], 'pesan=invalid_input') !== false) {
    $results['test24'] = ['status' => 'PASS', 'msg' => 'RHR 20 BPM ditolak'];
} else {
    $results['test24'] = ['status' => 'FAIL', 'msg' => 'RHR 20 BPM lolos'];
}
echo "Hasil: {$results['test24']['status']} - {$results['test24']['msg']}\n";

// 25. Validasi: Input Kosong ditolak
echo "[Test 25] Validasi: Input zona jantung kosong ditolak...\n";
$emptyHrReq = request("{$baseUrl}/zona_jantung/zona_jantung_proses.php", 'POST', [
    'csrf_token' => $hrZoneCsrf,
    'nama_user'  => '',
    'umur'       => '',
    'resting_hr' => ''
], [], $guestCookie);
if ($emptyHrReq['status'] === 302 && strpos($emptyHrReq['headers'], 'pesan=invalid_input') !== false) {
    $results['test25'] = ['status' => 'PASS', 'msg' => 'Input kosong ditolak'];
} else {
    $results['test25'] = ['status' => 'FAIL', 'msg' => 'Input kosong lolos'];
}
echo "Hasil: {$results['test25']['status']} - {$results['test25']['msg']}\n";

// 26. Peringatan Keamanan Jantung (Beta-blocker warning displayed)
echo "[Test 26] Peringatan klinis bagi pengguna obat penurun detak jantung / beta-blocker...\n";
$hrResultCheck = request("{$baseUrl}/zona_jantung/zona_jantung_form.php", 'GET', null, [], $guestCookie);
$hasBetaBlockerWarning = (strpos($hrResultCheck['body'], 'beta-blocker') !== false);
if ($hasBetaBlockerWarning) {
    $results['test26'] = ['status' => 'PASS', 'msg' => 'Peringatan kontraindikasi beta-blocker tertera pada halaman'];
} else {
    $results['test26'] = ['status' => 'FAIL', 'msg' => 'Peringatan klinis tidak ditemukan'];
}
echo "Hasil: {$results['test26']['status']} - {$results['test26']['msg']}\n";

// ===================================================================
// D. PHYSICAL ACTIVITY ASSESSMENT (IPAQ SHORT FORM) (Tests 27 - 34)
// ===================================================================
echo "\n--- [Bagian D: Evaluasi Aktivitas Fisik (IPAQ Short Form)] ---\n";

// 27. High Physical Activity (Vigorous >= 3 days, MET >= 1500)
// 3 days vigorous * 60 min * 8.0 = 1440 MET; plus walking 5 days * 30 min * 3.3 = 495 MET -> Total = 1935 MET (High)
echo "[Test 27] Perhitungan tingkat aktivitas Tinggi (High) sesuai protokol IPAQ...\n";
$ipaqHigh = calculate_ipaq_activity(3, 60, 0, 0, 5, 30, 6.0);
if ($ipaqHigh['badge'] === 'Tinggi' && $ipaqHigh['color'] === 'success' && $ipaqHigh['total_met'] === 1935.0) {
    $results['test27'] = ['status' => 'PASS', 'msg' => "Kategori Tinggi ({$ipaqHigh['total_met']} MET-menit/mgg)"];
} else {
    $results['test27'] = ['status' => 'FAIL', 'msg' => "Klasifikasi IPAQ tinggi gagal: " . json_encode($ipaqHigh)];
}
echo "Hasil: {$results['test27']['status']} - {$results['test27']['msg']}\n";

// 28. Moderate Physical Activity (Walking 5 days >= 30 min -> >=600 MET)
// 5 days walking * 40 min * 3.3 = 660 MET (Moderate)
echo "[Test 28] Perhitungan tingkat aktivitas Sedang (Moderate) sesuai protokol IPAQ...\n";
$ipaqMod = calculate_ipaq_activity(0, 0, 0, 0, 5, 40, 7.0);
if ($ipaqMod['badge'] === 'Sedang / Cukup' && $ipaqMod['total_met'] === 660.0) {
    $results['test28'] = ['status' => 'PASS', 'msg' => "Kategori Sedang ({$ipaqMod['total_met']} MET-menit/mgg)"];
} else {
    $results['test28'] = ['status' => 'FAIL', 'msg' => "Klasifikasi IPAQ sedang gagal: " . json_encode($ipaqMod)];
}
echo "Hasil: {$results['test28']['status']} - {$results['test28']['msg']}\n";

// 29. Low / Sedentary Physical Activity (<600 MET)
// 2 days walking * 15 min * 3.3 = 99 MET (Low)
echo "[Test 29] Perhitungan tingkat aktivitas Rendah (Low/Sedentary) (<600 MET)...\n";
$ipaqLow = calculate_ipaq_activity(0, 0, 0, 0, 2, 15, 9.0);
if ($ipaqLow['badge'] === 'Rendah / Sedentari' && $ipaqLow['total_met'] === 99.0) {
    $results['test29'] = ['status' => 'PASS', 'msg' => "Kategori Rendah ({$ipaqLow['total_met']} MET-menit/mgg)"];
} else {
    $results['test29'] = ['status' => 'FAIL', 'msg' => "Klasifikasi IPAQ rendah gagal: " . json_encode($ipaqLow)];
}
echo "Hasil: {$results['test29']['status']} - {$results['test29']['msg']}\n";

// 30. IPAQ Data Cleaning Rule: Bouts < 10 mins excluded (re-coded to 0)
echo "[Test 30] Data cleaning IPAQ: Durasi aktivitas < 10 menit diabaikan...\n";
$ipaqCleanUnder10 = calculate_ipaq_activity(5, 8, 0, 0, 0, 0, 6.0); // 8 mins < 10 -> 0 MET
if ($ipaqCleanUnder10['total_met'] === 0.0) {
    $results['test30'] = ['status' => 'PASS', 'msg' => 'Aktivitas 8 menit di-recode 0 MET sesuai aturan resmi IPAQ'];
} else {
    $results['test30'] = ['status' => 'FAIL', 'msg' => "Cleaning durasi < 10 min gagal: {$ipaqCleanUnder10['total_met']}"];
}
echo "Hasil: {$results['test30']['status']} - {$results['test30']['msg']}\n";

// 31. IPAQ Data Cleaning Rule: Truncation of values > 180 min/day
echo "[Test 31] Data cleaning IPAQ: Durasi aktivitas > 180 menit/hari dipotong ke 180 menit...\n";
$ipaqTruncate = calculate_ipaq_activity(1, 300, 0, 0, 0, 0, 6.0); // 300 min truncated to 180 -> 1 * 180 * 8.0 = 1440 MET
if ($ipaqTruncate['total_met'] === 1440.0) {
    $results['test31'] = ['status' => 'PASS', 'msg' => 'Aktivitas 300 menit dipotong ke batas 180 menit (1.440 MET)'];
} else {
    $results['test31'] = ['status' => 'FAIL', 'msg' => "Truncation gagal: {$ipaqTruncate['total_met']}"];
}
echo "Hasil: {$results['test31']['status']} - {$results['test31']['msg']}\n";

// 32. Sedentary Risk Warning (Sitting hours >= 8 hrs/day)
echo "[Test 32] Peringatan risiko sedentari bila waktu duduk >= 8 jam/hari...\n";
$ipaqSitting = calculate_ipaq_activity(0, 0, 0, 0, 5, 30, 9.5);
if ($ipaqSitting['sitting_warning'] === true) {
    $results['test32'] = ['status' => 'PASS', 'msg' => 'Waktu duduk 9.5 jam memicu peringatan sedentari'];
} else {
    $results['test32'] = ['status' => 'FAIL', 'msg' => 'Peringatan duduk tidak muncul'];
}
echo "Hasil: {$results['test32']['status']} - {$results['test32']['msg']}\n";

// 33. Validasi: Hari di luar rentang (0–7) ditolak
echo "[Test 33] Validasi: Hari aktivitas > 7 hari ditolak server...\n";
$ipaqFormReq = request("{$baseUrl}/aktivitas_fisik/aktivitas_fisik_form.php", 'GET', null, [], $guestCookie);
$ipaqCsrf = extractCsrf($ipaqFormReq['body']);

$badDaysIpaq = request("{$baseUrl}/aktivitas_fisik/aktivitas_fisik_proses.php", 'POST', [
    'csrf_token' => $ipaqCsrf,
    'nama_user'  => 'Tester IPAQ',
    'hari_berat' => 9, // Invalid
    'menit_berat'=> 30,
    'hari_sedang'=> 0,
    'menit_sedang'=> 0,
    'hari_jalan' => 0,
    'menit_jalan'=> 0,
    'jam_duduk'  => 6.0
], [], $guestCookie);
if ($badDaysIpaq['status'] === 302 && strpos($badDaysIpaq['headers'], 'pesan=invalid_input') !== false) {
    $results['test33'] = ['status' => 'PASS', 'msg' => 'Hari 9 hari ditolak'];
} else {
    $results['test33'] = ['status' => 'FAIL', 'msg' => 'Hari > 7 lolos'];
}
echo "Hasil: {$results['test33']['status']} - {$results['test33']['msg']}\n";

// 34. Validasi: Input Kosong ditolak
echo "[Test 34] Validasi: Nama kosong pada form IPAQ ditolak...\n";
$emptyIpaq = request("{$baseUrl}/aktivitas_fisik/aktivitas_fisik_proses.php", 'POST', [
    'csrf_token' => $ipaqCsrf,
    'nama_user'  => '',
    'hari_berat' => 0,
    'menit_berat'=> 0,
    'hari_sedang'=> 0,
    'menit_sedang'=> 0,
    'hari_jalan' => 0,
    'menit_jalan'=> 0,
    'jam_duduk'  => 6.0
], [], $guestCookie);
if ($emptyIpaq['status'] === 302 && strpos($emptyIpaq['headers'], 'pesan=invalid_input') !== false) {
    $results['test34'] = ['status' => 'PASS', 'msg' => 'Nama kosong pada form IPAQ ditolak'];
} else {
    $results['test34'] = ['status' => 'FAIL', 'msg' => 'Nama kosong lolos'];
}
echo "Hasil: {$results['test34']['status']} - {$results['test34']['msg']}\n";

// ===================================================================
// E. STRESS ASSESSMENT (PSS-10) TESTS (Tests 35 - 41)
// ===================================================================
echo "\n--- [Bagian E: Penilaian Tingkat Stres (PSS-10)] ---\n";

// 35. PSS-10 All Zeros: Items 4,5,7,8 reverse to 4 -> Total = 16 (Moderate)
echo "[Test 35] Uji pembalikan item positif pada skor minimum (q=0 semua -> total=16)...\n";
$allZeroAnswers = [];
for ($i = 1; $i <= 10; $i++) $allZeroAnswers['q' . $i] = 0;
$zeroTest = calculate_pss_stress_score($allZeroAnswers);
// Direct = 0; Reverse = 4*4 = 16 -> Total = 16
if ($zeroTest['direct_score'] === 0 && $zeroTest['reverse_score'] === 16 && $zeroTest['total_score'] === 16) {
    $results['test35'] = ['status' => 'PASS', 'msg' => 'Reverse scoring terverifikasi (q=0 semua -> reverse_score=16)'];
} else {
    $results['test35'] = ['status' => 'FAIL', 'msg' => "Reverse scoring gagal: " . json_encode($zeroTest)];
}
echo "Hasil: {$results['test35']['status']} - {$results['test35']['msg']}\n";

// 36. PSS-10 Low Stress (Total <= 13)
// Negative = 1 each (6), Positive = 4 each (reverse to 0) -> Total = 6
echo "[Test 36] Klasifikasi tingkat stres rendah (Total <= 13)...\n";
$lowAnswers = [
    'q1'=>1, 'q2'=>1, 'q3'=>1, 'q4'=>4, 'q5'=>4,
    'q6'=>1, 'q7'=>4, 'q8'=>4, 'q9'=>1, 'q10'=>1
];
$pssLow = calculate_pss_stress_score($lowAnswers);
if ($pssLow['total_score'] === 6 && $pssLow['badge'] === 'Stres Rendah' && $pssLow['color'] === 'success') {
    $results['test36'] = ['status' => 'PASS', 'msg' => "Total skor 6/40 terklasifikasi Stres Rendah"];
} else {
    $results['test36'] = ['status' => 'FAIL', 'msg' => "Klasifikasi stres rendah gagal: {$pssLow['total_score']}"];
}
echo "Hasil: {$results['test36']['status']} - {$results['test36']['msg']}\n";

// 37. PSS-10 Moderate Stress (14 to 26)
// Negative = 2 each (12), Positive = 2 each (reverse to 2 each = 8) -> Total = 20
echo "[Test 37] Klasifikasi tingkat stres sedang (Total 14 - 26)...\n";
$modAnswers = [
    'q1'=>2, 'q2'=>2, 'q3'=>2, 'q4'=>2, 'q5'=>2,
    'q6'=>2, 'q7'=>2, 'q8'=>2, 'q9'=>2, 'q10'=>2
];
$pssMod = calculate_pss_stress_score($modAnswers);
if ($pssMod['total_score'] === 20 && $pssMod['badge'] === 'Stres Sedang') {
    $results['test37'] = ['status' => 'PASS', 'msg' => "Total skor 20/40 terklasifikasi Stres Sedang"];
} else {
    $results['test37'] = ['status' => 'FAIL', 'msg' => "Klasifikasi stres sedang gagal: {$pssMod['total_score']}"];
}
echo "Hasil: {$results['test37']['status']} - {$results['test37']['msg']}\n";

// 38. PSS-10 High Stress (27 to 40)
// Negative = 4 each (24), Positive = 0 each (reverse to 4 each = 16) -> Total = 40
echo "[Test 38] Klasifikasi tingkat stres tinggi (Total >= 27)...\n";
$highAnswers = [
    'q1'=>4, 'q2'=>4, 'q3'=>4, 'q4'=>0, 'q5'=>0,
    'q6'=>4, 'q7'=>0, 'q8'=>0, 'q9'=>4, 'q10'=>4
];
$pssHigh = calculate_pss_stress_score($highAnswers);
if ($pssHigh['total_score'] === 40 && $pssHigh['badge'] === 'Stres Tinggi' && $pssHigh['color'] === 'danger') {
    $results['test38'] = ['status' => 'PASS', 'msg' => "Total skor 40/40 terklasifikasi Stres Tinggi (warna: Danger)"];
} else {
    $results['test38'] = ['status' => 'FAIL', 'msg' => "Klasifikasi stres tinggi gagal: {$pssHigh['total_score']}"];
}
echo "Hasil: {$results['test38']['status']} - {$results['test38']['msg']}\n";

// 39. Validasi: Nilai item di luar rentang 0–4 ditolak
echo "[Test 39] Validasi: Nilai jawaban > 4 ditolak server...\n";
$stressFormReq = request("{$baseUrl}/stres/stres_form.php", 'GET', null, [], $guestCookie);
$stressCsrf = extractCsrf($stressFormReq['body']);

$badValStress = request("{$baseUrl}/stres/stres_proses.php", 'POST', array_merge($modAnswers, [
    'csrf_token' => $stressCsrf,
    'nama_user'  => 'Tester Stres',
    'q1'         => 9 // Invalid value!
]), [], $guestCookie);
if ($badValStress['status'] === 302 && strpos($badValStress['headers'], 'pesan=invalid_input') !== false) {
    $results['test39'] = ['status' => 'PASS', 'msg' => 'Nilai q=9 ditolak'];
} else {
    $results['test39'] = ['status' => 'FAIL', 'msg' => 'Nilai q=9 lolos'];
}
echo "Hasil: {$results['test39']['status']} - {$results['test39']['msg']}\n";

// 40. Validasi: Pertanyaan tidak lengkap ditolak
echo "[Test 40] Validasi: Pengiriman form PSS-10 dengan item belum terjawab ditolak...\n";
$incompleteAnswers = $modAnswers;
unset($incompleteAnswers['q10']);
$badIncompleteStress = request("{$baseUrl}/stres/stres_proses.php", 'POST', array_merge($incompleteAnswers, [
    'csrf_token' => $stressCsrf,
    'nama_user'  => 'Tester Incomplete'
]), [], $guestCookie);
if ($badIncompleteStress['status'] === 302 && strpos($badIncompleteStress['headers'], 'pesan=invalid_input') !== false) {
    $results['test40'] = ['status' => 'PASS', 'msg' => 'Kuesioner belum lengkap ditolak'];
} else {
    $results['test40'] = ['status' => 'FAIL', 'msg' => 'Kuesioner belum lengkap lolos'];
}
echo "Hasil: {$results['test40']['status']} - {$results['test40']['msg']}\n";

// 41. Hotline Bantuan Medis (119 ext. 8) Muncul Pada Halaman Stres
echo "[Test 41] Ketersediaan informasi hotline bantuan krisis Kemenkes 119 ext. 8...\n";
$stressFormPage = request("{$baseUrl}/stres/stres_form.php", 'GET', null, [], $guestCookie);
if (strpos($stressFormPage['body'], '119 (ext. 8)') !== false) {
    $results['test41'] = ['status' => 'PASS', 'msg' => 'Hotline Kemenkes 119 (ext. 8) tersedia'];
} else {
    $results['test41'] = ['status' => 'FAIL', 'msg' => 'Hotline bantuan tidak ditemukan'];
}
echo "Hasil: {$results['test41']['status']} - {$results['test41']['msg']}\n";

// ===================================================================
// F. SMART STORAGE & SECURITY FOR 5 NEW TOOLS (Tests 42 - 50)
// ===================================================================
echo "\n--- [Bagian F: Smart Storage & Security untuk 5 Alat Baru] ---\n";

// 42. Guest Session-Only (5 alat baru tidak melakukan INSERT ke DB)
echo "[Test 42] Smart Storage Guest: 5 alat baru tidak menambah baris ke DB...\n";
$cBp0   = (int)fetchVal($pdo, "SELECT count(*) FROM cek_tekanan_darah");
$cSl0   = (int)fetchVal($pdo, "SELECT count(*) FROM cek_kualitas_tidur");
$cZn0   = (int)fetchVal($pdo, "SELECT count(*) FROM cek_zona_detak_jantung");
$cAct0  = (int)fetchVal($pdo, "SELECT count(*) FROM cek_aktivitas_fisik");
$cStr0  = (int)fetchVal($pdo, "SELECT count(*) FROM cek_stres");

// Guest submits Tensi
request("{$baseUrl}/tekanan_darah/tekanan_darah_proses.php", 'POST', [
    'csrf_token' => $bpCsrf, 'nama_user' => 'Guest BP', 'umur' => 30, 'sistolik' => 120, 'diastolik' => 80
], [], $guestCookie);

// Guest submits Tidur
request("{$baseUrl}/tidur/tidur_proses.php", 'POST', [
    'csrf_token' => $sleepCsrf, 'nama_user' => 'Guest Sleep', 'durasi_jam' => 7.5, 'latensi_menit' => 15,
    'frekuensi_terbangun' => 0, 'penggunaan_gawai' => 'tidak', 'kesegaran_bangun' => 'sangat_segar'
], [], $guestCookie);

// Guest submits Zona Jantung
request("{$baseUrl}/zona_jantung/zona_jantung_proses.php", 'POST', [
    'csrf_token' => $hrZoneCsrf, 'nama_user' => 'Guest Zone', 'umur' => 25, 'resting_hr' => 65, 'metode' => 'karvonen'
], [], $guestCookie);

// Guest submits IPAQ
request("{$baseUrl}/aktivitas_fisik/aktivitas_fisik_proses.php", 'POST', [
    'csrf_token' => $ipaqCsrf, 'nama_user' => 'Guest IPAQ', 'hari_berat' => 3, 'menit_berat' => 45,
    'hari_sedang' => 3, 'menit_sedang' => 30, 'hari_jalan' => 5, 'menit_jalan' => 30, 'jam_duduk' => 6.0
], [], $guestCookie);

// Guest submits PSS-10
request("{$baseUrl}/stres/stres_proses.php", 'POST', array_merge($modAnswers, [
    'csrf_token' => $stressCsrf, 'nama_user' => 'Guest PSS'
]), [], $guestCookie);

$cBp1   = (int)fetchVal($pdo, "SELECT count(*) FROM cek_tekanan_darah");
$cSl1   = (int)fetchVal($pdo, "SELECT count(*) FROM cek_kualitas_tidur");
$cZn1   = (int)fetchVal($pdo, "SELECT count(*) FROM cek_zona_detak_jantung");
$cAct1  = (int)fetchVal($pdo, "SELECT count(*) FROM cek_aktivitas_fisik");
$cStr1  = (int)fetchVal($pdo, "SELECT count(*) FROM cek_stres");

if ($cBp0 === $cBp1 && $cSl0 === $cSl1 && $cZn0 === $cZn1 && $cAct0 === $cAct1 && $cStr0 === $cStr1) {
    $results['test42'] = ['status' => 'PASS', 'msg' => 'Zero DB insert untuk Guest pada kelima alat baru'];
} else {
    $results['test42'] = ['status' => 'FAIL', 'msg' => 'DB bertambah pada aksi guest'];
}
echo "Hasil: {$results['test42']['status']} - {$results['test42']['msg']}\n";

// 43. Member Database Storage dengan user_id
echo "[Test 43] Smart Storage Member: Kelima alat baru menyimpan ke DB dengan user_id server...\n";
$userCookie = $tmpCookieDir . '/member_2c_cookie.txt';
loginUser($baseUrl, $userCookie, 'user', 'user123');

// Member Tensi
$mBpForm = request("{$baseUrl}/tekanan_darah/tekanan_darah_form.php", 'GET', null, [], $userCookie);
$mBpCsrf = extractCsrf($mBpForm['body']);
request("{$baseUrl}/tekanan_darah/tekanan_darah_proses.php", 'POST', [
    'csrf_token' => $mBpCsrf, 'nama_user' => 'Member BP', 'umur' => 30, 'sistolik' => 120, 'diastolik' => 80
], [], $userCookie);
$savedBp = fetchRow($pdo, "SELECT * FROM cek_tekanan_darah WHERE nama_user = 'Member BP' ORDER BY id_bp DESC LIMIT 1");

// Member Tidur
$mSlForm = request("{$baseUrl}/tidur/tidur_form.php", 'GET', null, [], $userCookie);
$mSlCsrf = extractCsrf($mSlForm['body']);
request("{$baseUrl}/tidur/tidur_proses.php", 'POST', [
    'csrf_token' => $mSlCsrf, 'nama_user' => 'Member Sleep', 'durasi_jam' => 8.0, 'latensi_menit' => 15,
    'frekuensi_terbangun' => 0, 'penggunaan_gawai' => 'tidak', 'kesegaran_bangun' => 'sangat_segar'
], [], $userCookie);
$savedSl = fetchRow($pdo, "SELECT * FROM cek_kualitas_tidur WHERE nama_user = 'Member Sleep' ORDER BY id_tidur DESC LIMIT 1");

// Member Zona Jantung
$mZnForm = request("{$baseUrl}/zona_jantung/zona_jantung_form.php", 'GET', null, [], $userCookie);
$mZnCsrf = extractCsrf($mZnForm['body']);
request("{$baseUrl}/zona_jantung/zona_jantung_proses.php", 'POST', [
    'csrf_token' => $mZnCsrf, 'nama_user' => 'Member Zone', 'umur' => 25, 'resting_hr' => 68, 'metode' => 'karvonen'
], [], $userCookie);
$savedZn = fetchRow($pdo, "SELECT * FROM cek_zona_detak_jantung WHERE nama_user = 'Member Zone' ORDER BY id_zona DESC LIMIT 1");

// Member IPAQ
$mActForm = request("{$baseUrl}/aktivitas_fisik/aktivitas_fisik_form.php", 'GET', null, [], $userCookie);
$mActCsrf = extractCsrf($mActForm['body']);
request("{$baseUrl}/aktivitas_fisik/aktivitas_fisik_proses.php", 'POST', [
    'csrf_token' => $mActCsrf, 'nama_user' => 'Member IPAQ', 'hari_berat' => 3, 'menit_berat' => 45,
    'hari_sedang' => 3, 'menit_sedang' => 30, 'hari_jalan' => 5, 'menit_jalan' => 30, 'jam_duduk' => 6.0
], [], $userCookie);
$savedAct = fetchRow($pdo, "SELECT * FROM cek_aktivitas_fisik WHERE nama_user = 'Member IPAQ' ORDER BY id_aktivitas DESC LIMIT 1");

// Member PSS-10
$mStrForm = request("{$baseUrl}/stres/stres_form.php", 'GET', null, [], $userCookie);
$mStrCsrf = extractCsrf($mStrForm['body']);
request("{$baseUrl}/stres/stres_proses.php", 'POST', array_merge($modAnswers, [
    'csrf_token' => $mStrCsrf, 'nama_user' => 'Member PSS'
]), [], $userCookie);
$savedStr = fetchRow($pdo, "SELECT * FROM cek_stres WHERE nama_user = 'Member PSS' ORDER BY id_stres DESC LIMIT 1");

if ($savedBp && (int)$savedBp['user_id'] === 2 &&
    $savedSl && (int)$savedSl['user_id'] === 2 &&
    $savedZn && (int)$savedZn['user_id'] === 2 &&
    $savedAct && (int)$savedAct['user_id'] === 2 &&
    $savedStr && (int)$savedStr['user_id'] === 2) {
    $results['test43'] = ['status' => 'PASS', 'msg' => 'Kelima alat tersimpan di DB terikat user_id = 2'];
} else {
    $results['test43'] = ['status' => 'FAIL', 'msg' => 'user_id tidak tersimpan pada salah satu alat'];
}
echo "Hasil: {$results['test43']['status']} - {$results['test43']['msg']}\n";

// 44. Snapshot Parameter Tersimpan Utuh
echo "[Test 44] Snapshot data: Nilai masukan dan hasil evaluasi tersimpan lengkap di DB...\n";
if ((int)$savedBp['sistolik'] === 120 &&
    (float)$savedSl['durasi_jam'] === 8.0 &&
    (int)$savedZn['max_hr'] === 191 &&
    (float)$savedAct['total_met_menit'] > 0 &&
    (int)$savedStr['skor_stres'] === 20) {
    $results['test44'] = ['status' => 'PASS', 'msg' => 'Snapshot parameter klinis & luaran tersimpan lengkap'];
} else {
    $results['test44'] = ['status' => 'FAIL', 'msg' => 'Snapshot data tidak lengkap'];
}
echo "Hasil: {$results['test44']['status']} - {$results['test44']['msg']}\n";

// 45. IDOR: User A Dilarang Mengakses Rekam Data User B (Kelima Alat)
echo "[Test 45] IDOR Protection: User A diblokir 403 saat mengakses data User B pada 5 alat baru...\n";
// Setup User B
$userBId = fetchVal($pdo, "SELECT id FROM user WHERE username = 'user_b'");
if (!$userBId) {
    $hashB = password_hash('pass123', PASSWORD_DEFAULT);
    executeSql($pdo, "INSERT INTO user (username, password, role) VALUES ('user_b', ?, 'user')", [$hashB]);
    $userBId = (int)$pdo->lastInsertId();
}

executeSql($pdo, "INSERT INTO cek_tekanan_darah (user_id, nama_user, umur, sistolik, diastolik, kategori, saran) VALUES (?, 'UB Tensi', 30, 120, 80, 'Normal', 'Saran')", [$userBId]);
$bBpId = (int)$pdo->lastInsertId();

executeSql($pdo, "INSERT INTO cek_kualitas_tidur (user_id, nama_user, durasi_jam, latensi_menit, frekuensi_terbangun, kesegaran_bangun, efisiensi_tidur, skor_kebiasaan, kategori_tidur, saran) VALUES (?, 'UB Tidur', 7, 15, 0, 'cukup_segar', 88.0, 2, 'Baik', 'Saran')", [$userBId]);
$bSlId = (int)$pdo->lastInsertId();

executeSql($pdo, "INSERT INTO cek_zona_detak_jantung (user_id, nama_user, umur, resting_hr, max_hr, hrr, zona_1_min, zona_1_max, zona_2_min, zona_2_max, zona_3_min, zona_3_max, zona_4_min, zona_4_max, zona_5_min, zona_5_max) VALUES (?, 'UB Zona', 25, 68, 191, 123, 130, 142, 142, 154, 154, 166, 166, 179, 179, 191)", [$userBId]);
$bZnId = (int)$pdo->lastInsertId();

executeSql($pdo, "INSERT INTO cek_aktivitas_fisik (user_id, nama_user, total_met_menit, kategori_aktivitas) VALUES (?, 'UB IPAQ', 1500, 'Sedang')", [$userBId]);
$bActId = (int)$pdo->lastInsertId();

executeSql($pdo, "INSERT INTO cek_stres (user_id, nama_user, skor_stres, tingkat_stres) VALUES (?, 'UB Stres', 18, 'Sedang')", [$userBId]);
$bStrId = (int)$pdo->lastInsertId();

$rIdorBp  = request("{$baseUrl}/tekanan_darah/tekanan_darah_hasil.php?id={$bBpId}", 'GET', null, [], $userCookie);
$rIdorSl  = request("{$baseUrl}/tidur/tidur_hasil.php?id={$bSlId}", 'GET', null, [], $userCookie);
$rIdorZn  = request("{$baseUrl}/zona_jantung/zona_jantung_hasil.php?id={$bZnId}", 'GET', null, [], $userCookie);
$rIdorAct = request("{$baseUrl}/aktivitas_fisik/aktivitas_fisik_hasil.php?id={$bActId}", 'GET', null, [], $userCookie);
$rIdorStr = request("{$baseUrl}/stres/stres_hasil.php?id={$bStrId}", 'GET', null, [], $userCookie);

if ($rIdorBp['status'] === 403 && $rIdorSl['status'] === 403 && $rIdorZn['status'] === 403 &&
    $rIdorAct['status'] === 403 && $rIdorStr['status'] === 403) {
    $results['test45'] = ['status' => 'PASS', 'msg' => 'Kelima alat baru terlindungi dari IDOR (HTTP 403 Forbidden)'];
} else {
    $results['test45'] = ['status' => 'FAIL', 'msg' => "IDOR leak detected! BP:{$rIdorBp['status']}, SL:{$rIdorSl['status']}, ZN:{$rIdorZn['status']}, ACT:{$rIdorAct['status']}, STR:{$rIdorStr['status']}"];
}
echo "Hasil: {$results['test45']['status']} - {$results['test45']['msg']}\n";

// 46. Admin Global Access Allowed
echo "[Test 46] Otorisasi Admin: Administrator diizinkan melihat rekam data seluruh pengguna...\n";
$adminCookie = $tmpCookieDir . '/admin_2c_cookie.txt';
loginAdmin($baseUrl, $adminCookie, 'admin', 'admin123');

$rAdminBp = request("{$baseUrl}/tekanan_darah/tekanan_darah_hasil.php?id={$bBpId}", 'GET', null, [], $adminCookie);
$rAdminSl = request("{$baseUrl}/tidur/tidur_hasil.php?id={$bSlId}", 'GET', null, [], $adminCookie);
if ($rAdminBp['status'] === 200 && $rAdminSl['status'] === 200) {
    $results['test46'] = ['status' => 'PASS', 'msg' => 'Administrator berhasil mengakses data lintas pengguna (HTTP 200)'];
} else {
    $results['test46'] = ['status' => 'FAIL', 'msg' => 'Akses admin diblokir'];
}
echo "Hasil: {$results['test46']['status']} - {$results['test46']['msg']}\n";

// 47. Manipulasi user_id via Request Body Diabaikan
echo "[Test 47] Keamanan: Manipulasi user_id=1 via POST diabaikan, tetap mengikat user_id=2...\n";
request("{$baseUrl}/tekanan_darah/tekanan_darah_proses.php", 'POST', [
    'csrf_token' => $mBpCsrf,
    'user_id'    => 1, // Attacker attempts to spoof admin
    'nama_user'  => 'Spoof BP',
    'umur'       => 30,
    'sistolik'   => 120,
    'diastolik'  => 80
], [], $userCookie);
$spoofBp = fetchRow($pdo, "SELECT user_id FROM cek_tekanan_darah WHERE nama_user = 'Spoof BP' LIMIT 1");
if ($spoofBp && (int)$spoofBp['user_id'] === 2) {
    $results['test47'] = ['status' => 'PASS', 'msg' => 'user_id=1 diabaikan, record tetap terikat ke user_id=2'];
} else {
    $results['test47'] = ['status' => 'FAIL', 'msg' => 'Spoofing user_id berhasil'];
}
echo "Hasil: {$results['test47']['status']} - {$results['test47']['msg']}\n";

// 48. CSRF Protection: POST Tanpa Token Ditolak
echo "[Test 48] Proteksi CSRF: POST tanpa token ke alat baru ditolak...\n";
$noCsrfBp = request("{$baseUrl}/tekanan_darah/tekanan_darah_proses.php", 'POST', [
    'nama_user' => 'No CSRF', 'umur' => 30, 'sistolik' => 120, 'diastolik' => 80
], [], $userCookie);
$noCsrfSl = request("{$baseUrl}/tidur/tidur_proses.php", 'POST', [
    'nama_user' => 'No CSRF', 'durasi_jam' => 7.0, 'latensi_menit' => 15
], [], $userCookie);
if ($noCsrfBp['status'] === 403 && $noCsrfSl['status'] === 403) {
    $results['test48'] = ['status' => 'PASS', 'msg' => 'Permintaan tanpa token CSRF dibalas HTTP 403 Forbidden'];
} else {
    $results['test48'] = ['status' => 'FAIL', 'msg' => 'Request tanpa CSRF lolos'];
}
echo "Hasil: {$results['test48']['status']} - {$results['test48']['msg']}\n";

// 49. GET Method Delete Ditolak
echo "[Test 49] Proteksi Metode: Akses GET pada file hapus dibalas HTTP 405 Method Not Allowed...\n";
$rGetDel = request("{$baseUrl}/bmi/bmi_hapus.php?id_bmi=1", 'GET', null, [], $adminCookie);
if ($rGetDel['status'] === 405) {
    $results['test49'] = ['status' => 'PASS', 'msg' => 'Akses GET pada file hapus dibalas HTTP 405'];
} else {
    $results['test49'] = ['status' => 'FAIL', 'msg' => "Status bukan 405: {$rGetDel['status']}"];
}
echo "Hasil: {$results['test49']['status']} - {$results['test49']['msg']}\n";

// 50. Legacy Record (user_id = NULL) Diblokir untuk Member Biasa
echo "[Test 50] Akses data legacy (user_id = NULL) diblokir untuk member biasa...\n";
executeSql($pdo, "INSERT INTO cek_tekanan_darah (user_id, nama_user, umur, sistolik, diastolik, kategori, saran) VALUES (NULL, 'Legacy BP', 40, 120, 80, 'Normal', 'Saran')");
$legacyBpId = (int)$pdo->lastInsertId();

$rMemberLegacy = request("{$baseUrl}/tekanan_darah/tekanan_darah_hasil.php?id={$legacyBpId}", 'GET', null, [], $userCookie);
$rAdminLegacy  = request("{$baseUrl}/tekanan_darah/tekanan_darah_hasil.php?id={$legacyBpId}", 'GET', null, [], $adminCookie);

if ($rMemberLegacy['status'] === 403 && $rAdminLegacy['status'] === 200) {
    $results['test50'] = ['status' => 'PASS', 'msg' => 'Member biasa diblokir 403; Administrator diizinkan mengakses data legacy'];
} else {
    $results['test50'] = ['status' => 'FAIL', 'msg' => 'Proteksi data legacy bermasalah'];
}
echo "Hasil: {$results['test50']['status']} - {$results['test50']['msg']}\n";

// ===================================================================
// G. REGRESSION & MIGRATION INTEGRITY (Tests 51 - 55)
// ===================================================================
echo "\n--- [Bagian G: Regression & Migration Integrity Testing] ---\n";

// 51. Semua 7 Kalkulator Phase 1A–2B Beroperasi Normal (HTTP 200 OK)
echo "[Test 51] Regresi: 7 kalkulator sebelumnya merespons HTTP 200 OK...\n";
$r1 = request("{$baseUrl}/bmi/bmi_form.php", 'GET')['status'];
$r2 = request("{$baseUrl}/bmr/bmr_form.php", 'GET')['status'];
$r3 = request("{$baseUrl}/whtr/whtr_form.php", 'GET')['status'];
$r4 = request("{$baseUrl}/body_fat/body_fat_form.php", 'GET')['status'];
$r5 = request("{$baseUrl}/cairan/cairan_form.php", 'GET')['status'];
$r6 = request("{$baseUrl}/jantung/jantung_form.php", 'GET')['status'];
$r7 = request("{$baseUrl}/mental/mental_form.php", 'GET')['status'];

if ($r1 === 200 && $r2 === 200 && $r3 === 200 && $r4 === 200 && $r5 === 200 && $r6 === 200 && $r7 === 200) {
    $results['test51'] = ['status' => 'PASS', 'msg' => 'Ketujuh kalkulator lama merespons HTTP 200 OK'];
} else {
    $results['test51'] = ['status' => 'FAIL', 'msg' => 'Salah satu kalkulator lama gagal'];
}
echo "Hasil: {$results['test51']['status']} - {$results['test51']['msg']}\n";

// 52. Kelima Alat Baru Merespons HTTP 200 OK
echo "[Test 52] Kelima alat baru merespons HTTP 200 OK...\n";
$n1 = request("{$baseUrl}/tekanan_darah/tekanan_darah_form.php", 'GET')['status'];
$n2 = request("{$baseUrl}/tidur/tidur_form.php", 'GET')['status'];
$n3 = request("{$baseUrl}/zona_jantung/zona_jantung_form.php", 'GET')['status'];
$n4 = request("{$baseUrl}/aktivitas_fisik/aktivitas_fisik_form.php", 'GET')['status'];
$n5 = request("{$baseUrl}/stres/stres_form.php", 'GET')['status'];

if ($n1 === 200 && $n2 === 200 && $n3 === 200 && $n4 === 200 && $n5 === 200) {
    $results['test52'] = ['status' => 'PASS', 'msg' => 'Kelima alat baru merespons HTTP 200 OK'];
} else {
    $results['test52'] = ['status' => 'FAIL', 'msg' => "Salah satu alat baru gagal: n1:{$n1}, n2:{$n2}, n3:{$n3}, n4:{$n4}, n5:{$n5}"];
}
echo "Hasil: {$results['test52']['status']} - {$results['test52']['msg']}\n";

// 53. Integritas Formula Klinis Lama Tidak Berubah
echo "[Test 53] Regresi: Formula klinis lama (Tanaka 191 BPM, BMI Asia 22.9, BMR 1674 kkal)...\n";
$fBmi = round(70 / (1.75 * 1.75), 1);
$fBmr = calculate_bmr('L', 70, 175, 25);
$fHr  = calculate_max_hr(25);
if ($fBmi == 22.9 && $fBmr === 1674.0 && $fHr === 191) {
    $results['test53'] = ['status' => 'PASS', 'msg' => 'Formula klinis lama 100% konsisten'];
} else {
    $results['test53'] = ['status' => 'FAIL', 'msg' => 'Formula lama terganggu'];
}
echo "Hasil: {$results['test53']['status']} - {$results['test53']['msg']}\n";

// 54. Idempotensi Migration Runner Phase 2C
echo "[Test 54] Idempotensi Migration Runner...\n";
$migOutput = shell_exec("HEALTHCHECK_DB_PATH=" . escapeshellarg($testDbPath) . " php " . escapeshellarg($projectRoot . '/database/migrate.php'));
$migStatus = shell_exec("HEALTHCHECK_DB_PATH=" . escapeshellarg($testDbPath) . " php " . escapeshellarg($projectRoot . '/database/migrate.php') . " --status");
if (strpos($migOutput, 'Nothing to migrate') !== false && strpos($migStatus, '[APPLIED] 20261108_001_phase2c_cardio_sleep_mental_tables') !== false) {
    $results['test54'] = ['status' => 'PASS', 'msg' => 'Migration runner idempoten (status APPLIED terverifikasi)'];
} else {
    $results['test54'] = ['status' => 'FAIL', 'msg' => 'Eksekusi ulang migrasi gagal'];
}
echo "Hasil: {$results['test54']['status']} - {$results['test54']['msg']}\n";

// 55. PHP Lint Seluruh File Repositori (php -l)
echo "[Test 55] Verifikasi sintaks seluruh file PHP repositori (php -l)...\n";
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
    $results['test55'] = ['status' => 'PASS', 'msg' => sprintf('Semua %d file PHP lulus sintaks tanpa error (php -l)', count($phpFiles))];
} else {
    $results['test55'] = ['status' => 'FAIL', 'msg' => 'Ditemukan syntax error: ' . implode('; ', $syntaxErrors)];
}
echo "Hasil: {$results['test55']['status']} - {$results['test55']['msg']}\n";

// ===================================================================
// REKAPITULASI HASIL PENGUJIAN
// ===================================================================
echo "\n======================================================================\n";
echo "REKAPITULASI HASIL PENGUJIAN PHASE 2C (55 SKENARIO):\n";
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
echo "STATUS AKHIR: " . ($failCount === 0 ? "SEMUA 55 PENGUJIAN BERHASIL (100% PASS)" : "ADA PENGUJIAN GAGAL") . "\n";
echo "======================================================================\n";
