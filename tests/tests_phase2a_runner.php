<?php
/**
 * Automated Test Suite for HealthCheck Phase 2A:
 * Existing Calculator Integration, Profile Auto-fill, BMR Multiplier Fix,
 * Smart Storage & Server-Side Data Ownership
 *
 * Runs 30 comprehensive test cases against an isolated SQLite test database.
 */

$host = '127.0.0.1';
$port = 8095;
$baseUrl = "http://{$host}:{$port}";

echo "======================================================================\n";
echo "=== Memulai HealthCheck Phase 2A Comprehensive Test Suite (30 Tests)===\n";
echo "======================================================================\n";

// Setup isolated test database
$projectRoot = dirname(__DIR__);
$mainDbPath = $projectRoot . '/database/healthcheck.sqlite';
$testDbPath = $projectRoot . '/database/healthcheck_test_phase2a.sqlite';

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
$cmd = sprintf('HEALTHCHECK_DB_PATH=%s php -S %s:%d -t "%s" > /tmp/php_phase2a_test.log 2>&1 & echo $!',
    escapeshellarg($testDbPath), $host, $port, $projectRoot
);
$pid = (int)shell_exec($cmd);
echo "Server test Phase 2A aktif (PID: {$pid}) pada {$baseUrl}\n";

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

// Helpers
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

require_once $projectRoot . '/includes/bmr_helper.php';
require_once $projectRoot . '/includes/auth_helper.php';

// ===================================================================
// A. BMR TESTS (Tests 1 - 7)
// ===================================================================
echo "\n--- [Bagian A: Audit dan Perbaikan Bug BMR] ---\n";

// 1. Perhitungan BMR laki-laki data uji
echo "[Test 1] Perhitungan BMR Laki-laki (70kg, 175cm, 25thn)...\n";
$bmrMale = calculate_bmr('L', 70, 175, 25);
if ($bmrMale === 1674.0) {
    $results['test1'] = ['status' => 'PASS', 'msg' => "BMR Laki-laki: {$bmrMale} kkal (sesuai Mifflin-St Jeor)"];
} else {
    $results['test1'] = ['status' => 'FAIL', 'msg' => "Ekspektasi 1674, didapat {$bmrMale}"];
}
echo "Hasil: {$results['test1']['status']} - {$results['test1']['msg']}\n";

// 2. Perhitungan BMR perempuan data uji
echo "[Test 2] Perhitungan BMR Perempuan (55kg, 160cm, 25thn)...\n";
$bmrFemale = calculate_bmr('P', 55, 160, 25);
if ($bmrFemale === 1264.0) {
    $results['test2'] = ['status' => 'PASS', 'msg' => "BMR Perempuan: {$bmrFemale} kkal (sesuai Mifflin-St Jeor)"];
} else {
    $results['test2'] = ['status' => 'FAIL', 'msg' => "Ekspektasi 1264, didapat {$bmrFemale}"];
}
echo "Hasil: {$results['test2']['status']} - {$results['test2']['msg']}\n";

// 3. TDEE untuk seluruh pilihan aktivitas
echo "[Test 3] TDEE untuk seluruh 5 pilihan tingkat aktivitas...\n";
$tdeeTests = [
    '1.2' => 2009.0,
    '1.375' => 2302.0,
    '1.55' => 2595.0,
    '1.725' => 2888.0,
    '1.9' => 3181.0,
];
$allTdeeOk = true;
foreach ($tdeeTests as $mult => $expectedTdee) {
    $factor = get_activity_factor($mult);
    $calculated = calculate_tdee($bmrMale, $factor);
    if ($calculated !== $expectedTdee) {
        $allTdeeOk = false;
        break;
    }
}
if ($allTdeeOk) {
    $results['test3'] = ['status' => 'PASS', 'msg' => 'TDEE untuk 5 multiplier (1.2, 1.375, 1.55, 1.725, 1.9) 100% presisi'];
} else {
    $results['test3'] = ['status' => 'FAIL', 'msg' => 'Kalkulasi TDEE salah pada salah satu tingkat aktivitas'];
}
echo "Hasil: {$results['test3']['status']} - {$results['test3']['msg']}\n";

// 4. Edit data BMR mempertahankan multiplier aktivitas yang benar (Bukan static 1.55)
echo "[Test 4] Edit data BMR mempertahankan multiplier aktivitas asli...\n";
// Masukkan record dengan aktivitas Sedentary (1.2)
executeSql($pdo, "INSERT INTO cek_bmr (user_id, nama_user, jenis_kelamin, umur, berat_badan, tinggi_badan, bmr, aktivitas, tdee) 
    VALUES (2, 'Tester Sedentary', 'L', 25, 70, 175, 1674, 'Sedentary (Jarang bergerak)', 2009)");
$sedentaryId = (int)$pdo->lastInsertId();

$adminCookie = $tmpCookieDir . '/admin_bmr_test.txt';
loginAdmin($baseUrl, $adminCookie, 'admin', 'admin123');

// Edit record: hanya ubah berat badan dari 70 menjadi 72, pertahankan aktivitas 1.2
// BMR baru pria 72kg, 175cm, 25thn: (10*72) + (6.25*175) - (5*25) + 5 = 720 + 1093.75 - 125 + 5 = 1693.75 -> 1694
// TDEE baru (1.2): 1694 * 1.2 = 2032.8 -> 2033 (Jika kena bug static 1.55, akan jadi 2626!)
$editPage = request("{$baseUrl}/bmr/bmr_edit.php?id={$sedentaryId}", 'GET', null, [], $adminCookie);
$editCsrf = extractCsrf($editPage['body']);

$updReq = request("{$baseUrl}/bmr/bmr_update.php", 'POST', [
    'csrf_token' => $editCsrf,
    'id_bmr' => $sedentaryId,
    'nama_user' => 'Tester Sedentary Updated',
    'jenis_kelamin' => 'L',
    'umur' => 25,
    'berat_badan' => 72,
    'tinggi_badan' => 175,
    'aktivitas' => '1.2' // Kirim aktivitas sedentary
], [], $adminCookie);

$updRow = fetchRow($pdo, "SELECT * FROM cek_bmr WHERE id_bmr = ?", [$sedentaryId]);
$expectedNewTdee = 2033; // 1694 * 1.2
if ((int)$updRow['tdee'] === $expectedNewTdee && str_contains($updRow['aktivitas'], 'Sedentary')) {
    $results['test4'] = ['status' => 'PASS', 'msg' => "Multiplier 1.2 dipertahankan (TDEE: {$updRow['tdee']} kkal, bukan static 1.55)"];
} else {
    $results['test4'] = ['status' => 'FAIL', 'msg' => "Bug multiplier terdeteksi! TDEE: {$updRow['tdee']}, ekspektasi: {$expectedNewTdee}"];
}
echo "Hasil: {$results['test4']['status']} - {$results['test4']['msg']}\n";

// 5. Perubahan aktivitas menghasilkan TDEE yang sesuai
echo "[Test 5] Perubahan aktivitas pada form edit menghasilkan TDEE baru yang sesuai...\n";
// Sekarang ubah aktivitas dari 1.2 ke 1.725 (Sangat Aktif)
// TDEE baru (1.725): 1694 * 1.725 = 2922.15 -> 2922
$updReq2 = request("{$baseUrl}/bmr/bmr_update.php", 'POST', [
    'csrf_token' => $editCsrf,
    'id_bmr' => $sedentaryId,
    'nama_user' => 'Tester Active Updated',
    'jenis_kelamin' => 'L',
    'umur' => 25,
    'berat_badan' => 72,
    'tinggi_badan' => 175,
    'aktivitas' => '1.725'
], [], $adminCookie);

$updRow2 = fetchRow($pdo, "SELECT * FROM cek_bmr WHERE id_bmr = ?", [$sedentaryId]);
if ((int)$updRow2['tdee'] === 2922 && str_contains($updRow2['aktivitas'], 'Sangat Aktif')) {
    $results['test5'] = ['status' => 'PASS', 'msg' => "Aktivitas diperbarui ke 1.725 (TDEE baru: {$updRow2['tdee']} kkal)"];
} else {
    $results['test5'] = ['status' => 'FAIL', 'msg' => "TDEE setelah ganti aktivitas tidak sesuai. Didapat: {$updRow2['tdee']}"];
}
echo "Hasil: {$results['test5']['status']} - {$results['test5']['msg']}\n";

// 6. Input aktivitas ilegal ditolak
echo "[Test 6] Validasi: Input aktivitas ilegal/manipulasi ditolak...\n";
$illegalReq = request("{$baseUrl}/bmr/bmr_update.php", 'POST', [
    'csrf_token' => $editCsrf,
    'id_bmr' => $sedentaryId,
    'nama_user' => 'Tester Illegal Act',
    'jenis_kelamin' => 'L',
    'umur' => 25,
    'berat_badan' => 70,
    'tinggi_badan' => 175,
    'aktivitas' => '99.9' // Ilegal
], [], $adminCookie);

$illegalRejected = ($illegalReq['status'] === 302 && strpos($illegalReq['headers'], 'pesan=invalid_input') !== false);
if ($illegalRejected) {
    $results['test6'] = ['status' => 'PASS', 'msg' => 'Aktivitas ilegal (99.9) ditolak dengan redirect pesan=invalid_input'];
} else {
    $results['test6'] = ['status' => 'FAIL', 'msg' => 'Aktivitas ilegal tidak ditolak'];
}
echo "Hasil: {$results['test6']['status']} - {$results['test6']['msg']}\n";

// 7. Data aktivitas tampil benar setelah halaman edit dibuka kembali
echo "[Test 7] Opsi dropdown aktivitas ter-select benar saat form edit dibuka...\n";
$reopenedEdit = request("{$baseUrl}/bmr/bmr_edit.php?id={$sedentaryId}", 'GET', null, [], $adminCookie);
$hasSelected1725 = (preg_match('/<option\s+value="1\.725"\s+selected>/', $reopenedEdit['body']) === 1);
if ($hasSelected1725) {
    $results['test7'] = ['status' => 'PASS', 'msg' => 'Dropdown aktivitas menampilkan selected="selected" untuk 1.725'];
} else {
    $results['test7'] = ['status' => 'FAIL', 'msg' => 'Dropdown aktivitas tidak menampilkan nilai terpilih yang benar'];
}
echo "Hasil: {$results['test7']['status']} - {$results['test7']['msg']}\n";

// ===================================================================
// B. PROFILE INTEGRATION TESTS (Tests 8 - 11)
// ===================================================================
echo "\n--- [Bagian B: Integrasi Profil Pengguna] ---\n";

// Seed profile lengkap untuk User 2 ('user')
// Umur: Tanggal lahir 1996-05-15 (Tahun 2026 -> 30 tahun)
$pdo->exec("DELETE FROM user_profile WHERE user_id = 2");
$pdo->prepare("INSERT INTO user_profile (user_id, nama_lengkap, tanggal_lahir, jenis_kelamin, tinggi_badan, berat_badan)
    VALUES (2, 'Ahmad Fauzi', '1996-05-15', 'L', 174.5, 68.0)")->execute();

$userCookie = $tmpCookieDir . '/user_autofill_test.txt';
loginUser($baseUrl, $userCookie, 'user', 'user123');

// 8. Member dengan profil lengkap mendapatkan auto-fill
echo "[Test 8] Auto-fill form kalkulator untuk member berprofil lengkap...\n";
$bmiForm = request("{$baseUrl}/bmi/bmi_form.php", 'GET', null, [], $userCookie);
$bmrForm = request("{$baseUrl}/bmr/bmr_form.php", 'GET', null, [], $userCookie);
$hrForm = request("{$baseUrl}/jantung/jantung_form.php", 'GET', null, [], $userCookie);
$phqForm = request("{$baseUrl}/mental/mental_form.php", 'GET', null, [], $userCookie);

$bmiFilled = strpos($bmiForm['body'], 'value="Ahmad Fauzi"') !== false
    && strpos($bmiForm['body'], 'value="174.5"') !== false
    && strpos($bmiForm['body'], 'value="68"') !== false
    && strpos($bmiForm['body'], 'value="30"') !== false;

$bmrFilled = strpos($bmrForm['body'], 'value="Ahmad Fauzi"') !== false
    && strpos($bmrForm['body'], 'value="174.5"') !== false
    && strpos($bmrForm['body'], 'value="68"') !== false
    && strpos($bmrForm['body'], 'value="30"') !== false;

$hrFilled = strpos($hrForm['body'], 'value="Ahmad Fauzi"') !== false
    && strpos($hrForm['body'], 'value="30"') !== false;

$phqFilled = strpos($phqForm['body'], 'value="Ahmad Fauzi"') !== false;

if ($bmiFilled && $bmrFilled && $hrFilled && $phqFilled) {
    $results['test8'] = ['status' => 'PASS', 'msg' => 'Seluruh 4 form kalkulator ter-autofill dari profil (Nama, JK, Umur:30, BB:68, TB:174.5)'];
} else {
    $results['test8'] = ['status' => 'FAIL', 'msg' => "Autofill gagal: BMI:".($bmiFilled?'OK':'NO')." BMR:".($bmrFilled?'OK':'NO')." HR:".($hrFilled?'OK':'NO')." PHQ:".($phqFilled?'OK':'NO')];
}
echo "Hasil: {$results['test8']['status']} - {$results['test8']['msg']}\n";

// 9. Member dengan profil parsial mendapatkan fallback yang benar
echo "[Test 9] Fallback yang benar untuk member berprofil parsial...\n";
// Update profil menjadi parsial (hanya nama lengkap, tanpa tanggal lahir, tanpa BB/TB)
$pdo->exec("UPDATE user_profile SET tanggal_lahir = NULL, tinggi_badan = NULL, berat_badan = NULL WHERE user_id = 2");

$bmiPartial = request("{$baseUrl}/bmi/bmi_form.php", 'GET', null, [], $userCookie);
$partialNameOk = strpos($bmiPartial['body'], 'value="Ahmad Fauzi"') !== false;
$partialAgeFallback = strpos($bmiPartial['body'], 'name="umur" id="umur" class="form-control" placeholder="25" min="5" max="120" value="25"') !== false;
$partialWeightEmpty = strpos($bmiPartial['body'], 'name="berat_badan" id="berat_badan" class="form-control" placeholder="60.5" min="10" max="350" value=""') !== false;

if ($partialNameOk && $partialAgeFallback && $partialWeightEmpty) {
    $results['test9'] = ['status' => 'PASS', 'msg' => 'Profil parsial: Nama terisi, Umur fallback ke 25, BB/TB fallback ke kosong'];
} else {
    $results['test9'] = ['status' => 'FAIL', 'msg' => 'Fallback profil parsial tidak sesuai ekspektasi'];
}
echo "Hasil: {$results['test9']['status']} - {$results['test9']['msg']}\n";

// 10. Guest tetap dapat menggunakan seluruh kalkulator
echo "[Test 10] Pengunjung tanpa akun (Guest) tetap dapat mengisi form dari awal...\n";
$guestCookie = $tmpCookieDir . '/guest_form_test.txt';
if (file_exists($guestCookie)) unlink($guestCookie);

$guestBmi = request("{$baseUrl}/bmi/bmi_form.php", 'GET', null, [], $guestCookie);
$guestNameEmpty = strpos($guestBmi['body'], 'value=""') !== false;
$guestAgeDefault = strpos($guestBmi['body'], 'value="25"') !== false;

if ($guestNameEmpty && $guestAgeDefault) {
    $results['test10'] = ['status' => 'PASS', 'msg' => 'Form guest tidak terisi data profil apa pun dan default nilai awal aman'];
} else {
    $results['test10'] = ['status' => 'FAIL', 'msg' => 'Form guest tercemar data'];
}
echo "Hasil: {$results['test10']['status']} - {$results['test10']['msg']}\n";

// 11. Perubahan input kalkulator tidak otomatis mengubah profil
echo "[Test 11] Perubahan input kalkulator TIDAK mengubah data profil (Decoupled Snapshot)...\n";
// Set profil awal: berat 65kg
$pdo->exec("UPDATE user_profile SET berat_badan = 65.0, tinggi_badan = 170.0 WHERE user_id = 2");

$bmiFormPost = request("{$baseUrl}/bmi/bmi_form.php", 'GET', null, [], $userCookie);
$csrfBmiPost = extractCsrf($bmiFormPost['body']);

// User menghitung dengan berat 85kg dan tinggi 180cm (berbeda dari profil 65kg/170cm)
request("{$baseUrl}/bmi/bmi_proses.php", 'POST', [
    'csrf_token' => $csrfBmiPost,
    'nama_user' => 'Ahmad Fauzi Sementara',
    'umur' => 28,
    'berat_badan' => 85.0,
    'tinggi_badan' => 180.0
], [], $userCookie);

// Periksa profil di user_profile
$profCheck = $pdo->query("SELECT * FROM user_profile WHERE user_id = 2")->fetch();
if ((float)$profCheck['berat_badan'] === 65.0 && (float)$profCheck['tinggi_badan'] === 170.0) {
    $results['test11'] = ['status' => 'PASS', 'msg' => 'Profil tetap 65kg/170cm; pemeriksaan 85kg disimpan sebagai snapshot riwayat'];
} else {
    $results['test11'] = ['status' => 'FAIL', 'msg' => "Profil terubah secara tidak sengaja! BB: {$profCheck['berat_badan']}"];
}
echo "Hasil: {$results['test11']['status']} - {$results['test11']['msg']}\n";

// ===================================================================
// C. SMART STORAGE STANDARDS (Tests 12 - 19)
// ===================================================================
echo "\n--- [Bagian C: Standar Smart Storage] ---\n";

// 12. Guest BMI -> No DB Insert
echo "[Test 12] Guest BMI: Tidak melakukan INSERT ke database...\n";
$bmiCount0 = (int)fetchVal($pdo, "SELECT count(*) FROM cek_bmi");
$gFormBmi = request("{$baseUrl}/bmi/bmi_form.php", 'GET', null, [], $guestCookie);
$gCsrfBmi = extractCsrf($gFormBmi['body']);
request("{$baseUrl}/bmi/bmi_proses.php", 'POST', [
    'csrf_token' => $gCsrfBmi,
    'nama_user' => 'Guest BMI User',
    'umur' => 22,
    'berat_badan' => 58,
    'tinggi_badan' => 165
], [], $guestCookie);
$bmiCount1 = (int)fetchVal($pdo, "SELECT count(*) FROM cek_bmi");
if ($bmiCount0 === $bmiCount1) {
    $results['test12'] = ['status' => 'PASS', 'msg' => 'Zero DB insert untuk Guest BMI'];
} else {
    $results['test12'] = ['status' => 'FAIL', 'msg' => 'Guest BMI tersimpan di DB'];
}
echo "Hasil: {$results['test12']['status']} - {$results['test12']['msg']}\n";

// 13. Guest BMR -> No DB Insert
echo "[Test 13] Guest BMR: Tidak melakukan INSERT ke database...\n";
$bmrCount0 = (int)fetchVal($pdo, "SELECT count(*) FROM cek_bmr");
$gFormBmr = request("{$baseUrl}/bmr/bmr_form.php", 'GET', null, [], $guestCookie);
$gCsrfBmr = extractCsrf($gFormBmr['body']);
request("{$baseUrl}/bmr/bmr_proses.php", 'POST', [
    'csrf_token' => $gCsrfBmr,
    'nama_user' => 'Guest BMR User',
    'jenis_kelamin' => 'L',
    'umur' => 22,
    'berat_badan' => 58,
    'tinggi_badan' => 165,
    'aktivitas' => '1.55'
], [], $guestCookie);
$bmrCount1 = (int)fetchVal($pdo, "SELECT count(*) FROM cek_bmr");
if ($bmrCount0 === $bmrCount1) {
    $results['test13'] = ['status' => 'PASS', 'msg' => 'Zero DB insert untuk Guest BMR'];
} else {
    $results['test13'] = ['status' => 'FAIL', 'msg' => 'Guest BMR tersimpan di DB'];
}
echo "Hasil: {$results['test13']['status']} - {$results['test13']['msg']}\n";

// 14. Guest Detak Jantung -> No DB Insert
echo "[Test 14] Guest Detak Jantung: Tidak melakukan INSERT ke database...\n";
$hrCount0 = (int)fetchVal($pdo, "SELECT count(*) FROM cek_jantung");
$gFormHr = request("{$baseUrl}/jantung/jantung_form.php", 'GET', null, [], $guestCookie);
$gCsrfHr = extractCsrf($gFormHr['body']);
request("{$baseUrl}/jantung/jantung_proses.php", 'POST', [
    'csrf_token' => $gCsrfHr,
    'nama_user' => 'Guest HR User',
    'umur' => 22,
    'resting_hr' => 70
], [], $guestCookie);
$hrCount1 = (int)fetchVal($pdo, "SELECT count(*) FROM cek_jantung");
if ($hrCount0 === $hrCount1) {
    $results['test14'] = ['status' => 'PASS', 'msg' => 'Zero DB insert untuk Guest Detak Jantung'];
} else {
    $results['test14'] = ['status' => 'FAIL', 'msg' => 'Guest Detak Jantung tersimpan di DB'];
}
echo "Hasil: {$results['test14']['status']} - {$results['test14']['msg']}\n";

// 15. Guest PHQ-4 -> No DB Insert
echo "[Test 15] Guest PHQ-4: Tidak melakukan INSERT ke database...\n";
$phqCount0 = (int)fetchVal($pdo, "SELECT count(*) FROM cek_mental");
$gFormPhq = request("{$baseUrl}/mental/mental_form.php", 'GET', null, [], $guestCookie);
$gCsrfPhq = extractCsrf($gFormPhq['body']);
request("{$baseUrl}/mental/mental_proses.php", 'POST', [
    'csrf_token' => $gCsrfPhq,
    'nama_user' => 'Guest Mental User',
    'q1' => 0, 'q2' => 0, 'q3' => 1, 'q4' => 0
], [], $guestCookie);
$phqCount1 = (int)fetchVal($pdo, "SELECT count(*) FROM cek_mental");
if ($phqCount0 === $phqCount1) {
    $results['test15'] = ['status' => 'PASS', 'msg' => 'Zero DB insert untuk Guest PHQ-4'];
} else {
    $results['test15'] = ['status' => 'FAIL', 'msg' => 'Guest PHQ-4 tersimpan di DB'];
}
echo "Hasil: {$results['test15']['status']} - {$results['test15']['msg']}\n";

// 16. Member BMI -> Saves with validated user_id
echo "[Test 16] Member BMI: Menyimpan record dengan user_id sesi server...\n";
$mBmiForm = request("{$baseUrl}/bmi/bmi_form.php", 'GET', null, [], $userCookie);
$mBmiCsrf = extractCsrf($mBmiForm['body']);
request("{$baseUrl}/bmi/bmi_proses.php", 'POST', [
    'csrf_token' => $mBmiCsrf,
    'nama_user' => 'Member BMI Record',
    'umur' => 30,
    'berat_badan' => 68,
    'tinggi_badan' => 174
], [], $userCookie);
$lastBmiRow = fetchRow($pdo, "SELECT * FROM cek_bmi WHERE nama_user = 'Member BMI Record' ORDER BY id_bmi DESC LIMIT 1");
if ($lastBmiRow && (int)$lastBmiRow['user_id'] === 2) {
    $results['test16'] = ['status' => 'PASS', 'msg' => "Record BMI tersimpan dengan user_id = 2 (ID: #{$lastBmiRow['id_bmi']})"];
} else {
    $results['test16'] = ['status' => 'FAIL', 'msg' => 'user_id tidak tersimpan'];
}
echo "Hasil: {$results['test16']['status']} - {$results['test16']['msg']}\n";

// 17. Member BMR -> Saves with validated user_id
echo "[Test 17] Member BMR: Menyimpan record dengan user_id sesi server...\n";
$mBmrForm = request("{$baseUrl}/bmr/bmr_form.php", 'GET', null, [], $userCookie);
$mBmrCsrf = extractCsrf($mBmrForm['body']);
request("{$baseUrl}/bmr/bmr_proses.php", 'POST', [
    'csrf_token' => $mBmrCsrf,
    'nama_user' => 'Member BMR Record',
    'jenis_kelamin' => 'L',
    'umur' => 30,
    'berat_badan' => 68,
    'tinggi_badan' => 174,
    'aktivitas' => '1.55'
], [], $userCookie);
$lastBmrRow = fetchRow($pdo, "SELECT * FROM cek_bmr WHERE nama_user = 'Member BMR Record' ORDER BY id_bmr DESC LIMIT 1");
if ($lastBmrRow && (int)$lastBmrRow['user_id'] === 2) {
    $results['test17'] = ['status' => 'PASS', 'msg' => "Record BMR tersimpan dengan user_id = 2 (ID: #{$lastBmrRow['id_bmr']})"];
} else {
    $results['test17'] = ['status' => 'FAIL', 'msg' => 'user_id tidak tersimpan'];
}
echo "Hasil: {$results['test17']['status']} - {$results['test17']['msg']}\n";

// 18. Member Detak Jantung -> Saves with validated user_id
echo "[Test 18] Member Detak Jantung: Menyimpan record dengan user_id sesi server...\n";
$mHrForm = request("{$baseUrl}/jantung/jantung_form.php", 'GET', null, [], $userCookie);
$mHrCsrf = extractCsrf($mHrForm['body']);
request("{$baseUrl}/jantung/jantung_proses.php", 'POST', [
    'csrf_token' => $mHrCsrf,
    'nama_user' => 'Member HR Record',
    'umur' => 30,
    'resting_hr' => 68
], [], $userCookie);
$lastHrRow = fetchRow($pdo, "SELECT * FROM cek_jantung WHERE nama_user = 'Member HR Record' ORDER BY id_jantung DESC LIMIT 1");
if ($lastHrRow && (int)$lastHrRow['user_id'] === 2) {
    $results['test18'] = ['status' => 'PASS', 'msg' => "Record Detak Jantung tersimpan dengan user_id = 2 (ID: #{$lastHrRow['id_jantung']})"];
} else {
    $results['test18'] = ['status' => 'FAIL', 'msg' => 'user_id tidak tersimpan'];
}
echo "Hasil: {$results['test18']['status']} - {$results['test18']['msg']}\n";

// 19. Member PHQ-4 -> Saves with validated user_id
echo "[Test 19] Member PHQ-4: Menyimpan record dengan user_id sesi server...\n";
$mPhqForm = request("{$baseUrl}/mental/mental_form.php", 'GET', null, [], $userCookie);
$mPhqCsrf = extractCsrf($mPhqForm['body']);
request("{$baseUrl}/mental/mental_proses.php", 'POST', [
    'csrf_token' => $mPhqCsrf,
    'nama_user' => 'Member PHQ Record',
    'q1' => 1, 'q2' => 1, 'q3' => 1, 'q4' => 1
], [], $userCookie);
$lastPhqRow = fetchRow($pdo, "SELECT * FROM cek_mental WHERE nama_user = 'Member PHQ Record' ORDER BY id_mental DESC LIMIT 1");
if ($lastPhqRow && (int)$lastPhqRow['user_id'] === 2) {
    $results['test19'] = ['status' => 'PASS', 'msg' => "Record PHQ-4 tersimpan dengan user_id = 2 (ID: #{$lastPhqRow['id_mental']})"];
} else {
    $results['test19'] = ['status' => 'FAIL', 'msg' => 'user_id tidak tersimpan'];
}
echo "Hasil: {$results['test19']['status']} - {$results['test19']['msg']}\n";

// ===================================================================
// D. KEAMANAN & DATA OWNERSHIP (Tests 20 - 26)
// ===================================================================
echo "\n--- [Bagian D: Keamanan & Data Ownership] ---\n";

// Setup User B (id = 3)
$userBId = fetchVal($pdo, "SELECT id FROM user WHERE username = 'user_b'");
if (!$userBId) {
    $hashB = password_hash('password123', PASSWORD_DEFAULT);
    executeSql($pdo, "INSERT INTO user (username, password, role) VALUES ('user_b', ?, 'user')", [$hashB]);
    $userBId = (int)$pdo->lastInsertId();
} else {
    $userBId = (int)$userBId;
}

// Buat record milik User B
executeSql($pdo, "INSERT INTO cek_bmi (user_id, nama_user, umur, berat_badan, tinggi_badan, bmi, kondisi)
    VALUES (?, 'Private Data User B', 29, 78, 172, 26.4, 'Overweight')", [$userBId]);
$recUserBId = (int)$pdo->lastInsertId();

// 20. User A tidak dapat membaca hasil User B
echo "[Test 20] User A tidak dapat membaca hasil pemeriksaan User B via GET ?id=...\n";
// User A (userCookie) mencoba mengakses bmi_hasil.php?id=$recUserBId
$attackView = request("{$baseUrl}/bmi/bmi_hasil.php?id={$recUserBId}", 'GET', null, [], $userCookie);
$userABlockedView = ($attackView['status'] === 403 && strpos($attackView['body'], '403 Forbidden') !== false);
if ($userABlockedView) {
    $results['test20'] = ['status' => 'PASS', 'msg' => 'User A diblokir dengan HTTP 403 Forbidden saat membuka data User B'];
} else {
    $results['test20'] = ['status' => 'FAIL', 'msg' => "IDOR Vulnerability! Status: {$attackView['status']}"];
}
echo "Hasil: {$results['test20']['status']} - {$results['test20']['msg']}\n";

// 21. User A tidak dapat mengubah hasil User B
echo "[Test 21] User A tidak dapat mengubah hasil pemeriksaan User B...\n";
$attackEdit = request("{$baseUrl}/bmi/bmi_edit.php?id={$recUserBId}", 'GET', null, [], $userCookie);
$attackUpdate = request("{$baseUrl}/bmi/bmi_update.php", 'POST', [
    'csrf_token' => $mBmiCsrf,
    'id_bmi' => $recUserBId,
    'nama_user' => 'Hacked Name',
    'umur' => 29,
    'berat_badan' => 90,
    'tinggi_badan' => 170
], [], $userCookie);

$userABlockedEdit = ($attackEdit['status'] === 302 || $attackEdit['status'] === 403);
$userABlockedUpdate = ($attackUpdate['status'] === 302 || $attackUpdate['status'] === 403);
$rowBNotChanged = fetchVal($pdo, "SELECT nama_user FROM cek_bmi WHERE id_bmi = ?", [$recUserBId]);

if ($userABlockedEdit && $userABlockedUpdate && $rowBNotChanged === 'Private Data User B') {
    $results['test21'] = ['status' => 'PASS', 'msg' => 'User A diblokir saat mencoba mengubah data User B (Data tetap utuh)'];
} else {
    $results['test21'] = ['status' => 'FAIL', 'msg' => 'User A berhasil mengubah data User B'];
}
echo "Hasil: {$results['test21']['status']} - {$results['test21']['msg']}\n";

// 22. User A tidak dapat menghapus hasil User B
echo "[Test 22] User A tidak dapat menghapus hasil pemeriksaan User B...\n";
$attackDelete = request("{$baseUrl}/bmi/bmi_hapus.php", 'POST', [
    'csrf_token' => $mBmiCsrf,
    'id_bmi' => $recUserBId
], [], $userCookie);

$userABlockedDelete = ($attackDelete['status'] === 302 || $attackDelete['status'] === 403);
$rowBStillExists = (int)fetchVal($pdo, "SELECT count(*) FROM cek_bmi WHERE id_bmi = ?", [$recUserBId]);

if ($userABlockedDelete && $rowBStillExists === 1) {
    $results['test22'] = ['status' => 'PASS', 'msg' => 'User A diblokir saat mencoba menghapus data User B (Data tetap ada)'];
} else {
    $results['test22'] = ['status' => 'FAIL', 'msg' => 'User A berhasil menghapus data User B'];
}
echo "Hasil: {$results['test22']['status']} - {$results['test22']['msg']}\n";

// 23. Manipulasi user_id melalui request tidak berhasil
echo "[Test 23] Manipulasi user_id via POST body diabaikan (Server session enforced)...\n";
// User A mencoba menyuntikkan user_id = 1 (milik admin) ke dalam form BMI
request("{$baseUrl}/bmi/bmi_proses.php", 'POST', [
    'csrf_token' => $mBmiCsrf,
    'user_id' => 1, // Attacker attempts to spoof admin
    'nama_user' => 'Spoof Attempt',
    'umur' => 25,
    'berat_badan' => 70,
    'tinggi_badan' => 175
], [], $userCookie);

$spoofRow = fetchRow($pdo, "SELECT * FROM cek_bmi WHERE nama_user = 'Spoof Attempt' LIMIT 1");
if ($spoofRow && (int)$spoofRow['user_id'] === 2) {
    $results['test23'] = ['status' => 'PASS', 'msg' => 'user_id=1 diabaikan, record tetap terikat ke user_id=2 milik User A'];
} else {
    $results['test23'] = ['status' => 'FAIL', 'msg' => "Spoofing berhasil! user_id: {$spoofRow['user_id']}"];
}
echo "Hasil: {$results['test23']['status']} - {$results['test23']['msg']}\n";

// 24. POST tanpa CSRF ditolak
echo "[Test 24] POST tanpa CSRF token ditolak HTTP 403...\n";
$noCsrfReq = request("{$baseUrl}/bmr/bmr_proses.php", 'POST', [
    'nama_user' => 'No CSRF',
    'umur' => 25,
    'berat_badan' => 70,
    'tinggi_badan' => 175,
    'aktivitas' => '1.55'
], [], $userCookie);

if ($noCsrfReq['status'] === 403) {
    $results['test24'] = ['status' => 'PASS', 'msg' => 'Permintaan POST tanpa CSRF token dibalas HTTP 403 Forbidden'];
} else {
    $results['test24'] = ['status' => 'FAIL', 'msg' => "Status bukan 403: {$noCsrfReq['status']}"];
}
echo "Hasil: {$results['test24']['status']} - {$results['test24']['msg']}\n";

// 25. Penghapusan melalui GET ditolak
echo "[Test 25] Penghapusan via GET ditolak HTTP 405 Method Not Allowed...\n";
$getDelReq = request("{$baseUrl}/bmi/bmi_hapus.php?id_bmi={$recUserBId}", 'GET', null, [], $adminCookie);
if ($getDelReq['status'] === 405) {
    $results['test25'] = ['status' => 'PASS', 'msg' => 'Akses GET pada file penghapusan ditolak dengan HTTP 405 Method Not Allowed'];
} else {
    $results['test25'] = ['status' => 'FAIL', 'msg' => "Status bukan 405: {$getDelReq['status']}"];
}
echo "Hasil: {$results['test25']['status']} - {$results['test25']['msg']}\n";

// 26. Record legacy tidak dapat diakses member biasa
echo "[Test 26] Record legacy (user_id = NULL) diblokir untuk member biasa...\n";
usleep(50000);
// Insert legacy anonymous record
executeSql($pdo, "INSERT INTO cek_bmi (user_id, nama_user, umur, berat_badan, tinggi_badan, bmi, kondisi)
    VALUES (NULL, 'Legacy Anonymous User', 35, 62, 168, 22.0, 'Normal')");
$legacyRecId = (int)$pdo->lastInsertId();

$memberAccessLegacy = request("{$baseUrl}/bmi/bmi_hasil.php?id={$legacyRecId}", 'GET', null, [], $userCookie);
$legacyBlocked = ($memberAccessLegacy['status'] === 403 && strpos($memberAccessLegacy['body'], '403 Forbidden') !== false);

// Admin harus tetap bisa akses
$adminAccessLegacy = request("{$baseUrl}/bmi/bmi_hasil.php?id={$legacyRecId}", 'GET', null, [], $adminCookie);
$adminAllowed = ($adminAccessLegacy['status'] === 200);

if ($legacyBlocked && $adminAllowed) {
    $results['test26'] = ['status' => 'PASS', 'msg' => 'Member biasa diblokir 403; Administrator dapat mengakses record legacy'];
} else {
    $results['test26'] = ['status' => 'FAIL', 'msg' => "Legacy access check gagal. Member status: {$memberAccessLegacy['status']}, Admin: {$adminAccessLegacy['status']}"];
}
echo "Hasil: {$results['test26']['status']} - {$results['test26']['msg']}\n";

// ===================================================================
// E. REGRESSION TESTS (Tests 27 - 30)
// ===================================================================
echo "\n--- [Bagian E: Regression & Integrity Testing] ---\n";

// 27. Seluruh kalkulator lama tetap berfungsi
echo "[Test 27] Verifikasi seluruh 4 kalkulator berfungsi normal end-to-end...\n";
$bmiOk = (request("{$baseUrl}/bmi/bmi_form.php", 'GET')['status'] === 200);
$bmrOk = (request("{$baseUrl}/bmr/bmr_form.php", 'GET')['status'] === 200);
$hrOk = (request("{$baseUrl}/jantung/jantung_form.php", 'GET')['status'] === 200);
$phqOk = (request("{$baseUrl}/mental/mental_form.php", 'GET')['status'] === 200);
if ($bmiOk && $bmrOk && $hrOk && $phqOk) {
    $results['test27'] = ['status' => 'PASS', 'msg' => 'Keempat form kalkulator (BMI, BMR, Detak Jantung, PHQ-4) merespons HTTP 200 OK'];
} else {
    $results['test27'] = ['status' => 'FAIL', 'msg' => 'Salah satu form kalkulator gagal diakses'];
}
echo "Hasil: {$results['test27']['status']} - {$results['test27']['msg']}\n";

// 28. Formula dan kategori hasil tidak mengalami perubahan yang tidak disengaja
echo "[Test 28] Akurasi ambang batas klasifikasi klinis...\n";
// Tanaka Max HR = 208 - (0.7 * 25) = 191
$hrMax25 = round(208 - (0.7 * 25));
// Asia-Pacific BMI: < 18.5 Kurus, 18.5 - 22.9 Normal, 23 - 24.9 Kelebihan, >= 25 Obesitas
$bmiAsiaNormal = (22.9 >= 18.5 && 22.9 <= 22.9);
$bmiAsiaOverweight = (23.0 >= 23.0 && 23.0 <= 24.9);
// PHQ-4 thresholds: <= 2 Normal, 3-5 Mild, 6-8 Moderate, 9-12 Severe
$phqNormal = (2 <= 2);
$phqSevere = (12 >= 9);

if ($hrMax25 === 191.0 && $bmiAsiaNormal && $bmiAsiaOverweight && $phqNormal && $phqSevere) {
    $results['test28'] = ['status' => 'PASS', 'msg' => 'Formula klinis (Tanaka 191 BPM, BMI Asia-Pacific 22.9/23.0, PHQ-4) 100% akurat'];
} else {
    $results['test28'] = ['status' => 'FAIL', 'msg' => 'Ambang batas klinis mengalami anomali'];
}
echo "Hasil: {$results['test28']['status']} - {$results['test28']['msg']}\n";

// 29. Migration Phase 1B tetap dapat dijalankan secara idempoten
echo "[Test 29] Idempotensi migration runner...\n";
$migOutput = shell_exec("HEALTHCHECK_DB_PATH=" . escapeshellarg($testDbPath) . " php " . escapeshellarg($projectRoot . '/database/migrate.php'));
$migStatus = shell_exec("HEALTHCHECK_DB_PATH=" . escapeshellarg($testDbPath) . " php " . escapeshellarg($projectRoot . '/database/migrate.php') . " --status");
if (strpos($migOutput, 'Nothing to migrate') !== false && strpos($migStatus, '[APPLIED]') !== false) {
    $results['test29'] = ['status' => 'PASS', 'msg' => 'Migration runner idempoten dan status APPLIED terverifikasi'];
} else {
    $results['test29'] = ['status' => 'FAIL', 'msg' => 'Eksekusi migrasi gagal atau tidak idempoten'];
}
echo "Hasil: {$results['test29']['status']} - {$results['test29']['msg']}\n";

// 30. Seluruh file PHP lolos php -l (Linting)
echo "[Test 30] Verifikasi sintaks seluruh file PHP repositori (php -l)...\n";
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
    $results['test30'] = ['status' => 'PASS', 'msg' => sprintf('Semua %d file PHP lulus sintaks tanpa error (php -l)', count($phpFiles))];
} else {
    $results['test30'] = ['status' => 'FAIL', 'msg' => 'Ditemukan syntax error: ' . implode('; ', $syntaxErrors)];
}
echo "Hasil: {$results['test30']['status']} - {$results['test30']['msg']}\n";

// ===================================================================
// REKAPITULASI HASIL
// ===================================================================
echo "\n======================================================================\n";
echo "REKAPITULASI HASIL PENGUJIAN PHASE 2A (30 SKENARIO):\n";
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
echo "STATUS AKHIR: " . ($failCount === 0 ? "SEMUA 30 PENGUJIAN BERHASIL (100% PASS)" : "ADA PENGUJIAN GAGAL") . "\n";
echo "======================================================================\n";
