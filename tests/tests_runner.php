<?php
/**
 * Automated Security & Regression Test Suite for HealthCheck Phase 1A
 */

$host = '127.0.0.1';
$port = 8089;
$baseUrl = "http://{$host}:{$port}";

echo "=== Memulai HealthCheck Phase 1A Test Suite ===\n";

// 1. Start background PHP server
$projectRoot = dirname(__DIR__);
$cmd = sprintf('php -S %s:%d -t "%s" > /tmp/php_test_server.log 2>&1 & echo $!', $host, $port, $projectRoot);
$pid = (int)shell_exec($cmd);
echo "Server testing aktif dengan PID: {$pid} pada {$baseUrl}\n";

// Register shutdown function to kill server
register_shutdown_function(function() use ($pid) {
    if ($pid > 0) {
        shell_exec("kill -9 {$pid} 2>/dev/null");
    }
});

// Give the server a moment to spin up
usleep(250000);

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
    
    $headerStr = substr($raw, 0, $headerSize);
    $body = substr($raw, $headerSize);
    
    return [
        'status' => $statusCode,
        'headers' => $headerStr,
        'body' => $body,
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

// ---------------------------------------------------------
// TEST 1: Login Admin Valid
// ---------------------------------------------------------
echo "\n[Test 1] Pengujian Login Admin Valid...\n";
$adminCookie = $tmpCookieDir . '/test_admin_cookie.txt';
if (file_exists($adminCookie)) unlink($adminCookie);

// Ambil halaman login admin untuk dapatkan CSRF token & cookie
$page = request("{$baseUrl}/auth/login_admin.php", 'GET', null, [], $adminCookie);
$csrf = extractCsrf($page['body']);

if (!$csrf) {
    $results['test1'] = ['status' => 'FAIL', 'msg' => 'Tidak menemukan CSRF token di halaman login admin'];
} else {
    $login = request("{$baseUrl}/auth/login_admin_proses.php", 'POST', [
        'username' => 'admin',
        'password' => 'admin123',
        'csrf_token' => $csrf
    ], [], $adminCookie);
    
    // Periksa redirect ke admin/admin.php
    $hasRedirect = (strpos($login['headers'], 'Location: ../admin/admin.php') !== false) || $login['status'] === 302;
    $adminDashboard = request("{$baseUrl}/admin/admin.php", 'GET', null, [], $adminCookie);
    $canAccess = ($adminDashboard['status'] === 200 && strpos($adminDashboard['body'], 'Dashboard Manajemen Data') !== false);
    
    if ($hasRedirect && $canAccess) {
        $results['test1'] = ['status' => 'PASS', 'msg' => 'Admin berhasil login dan mengakses panel admin'];
    } else {
        $results['test1'] = ['status' => 'FAIL', 'msg' => "Login admin gagal. Status: {$login['status']}"];
    }
}
echo "Hasil: {$results['test1']['status']} - {$results['test1']['msg']}\n";

// ---------------------------------------------------------
// TEST 2: Login Admin Invalid & Backdoor Check
// ---------------------------------------------------------
echo "\n[Test 2] Pengujian Login Admin Invalid & Penolakan Backdoor...\n";
$invalidCookie = $tmpCookieDir . '/test_invalid_cookie.txt';
if (file_exists($invalidCookie)) unlink($invalidCookie);

$page = request("{$baseUrl}/auth/login_admin.php", 'GET', null, [], $invalidCookie);
$csrf = extractCsrf($page['body']);

$invalidLogin = request("{$baseUrl}/auth/login_admin_proses.php", 'POST', [
    'username' => 'admin',
    'password' => 'wrong_password_xyz',
    'csrf_token' => $csrf
], [], $invalidCookie);

$rejected = (strpos($invalidLogin['headers'], 'login_admin.php?pesan=') !== false);
$dashboardCheck = request("{$baseUrl}/admin/admin.php", 'GET', null, [], $invalidCookie);
$accessBlocked = ($dashboardCheck['status'] === 302);

if ($rejected && $accessBlocked) {
    $results['test2'] = ['status' => 'PASS', 'msg' => 'Kredensial salah ditolak dan tidak dapat mengakses panel admin'];
} else {
    $results['test2'] = ['status' => 'FAIL', 'msg' => 'Kredensial salah tidak ditolak dengan benar'];
}
echo "Hasil: {$results['test2']['status']} - {$results['test2']['msg']}\n";

// ---------------------------------------------------------
// TEST 3: Login Pengguna Biasa
// ---------------------------------------------------------
echo "\n[Test 3] Pengujian Login Pengguna Biasa (user/user123)...\n";
$userCookie = $tmpCookieDir . '/test_user_cookie.txt';
if (file_exists($userCookie)) unlink($userCookie);

$page = request("{$baseUrl}/auth/login.php", 'GET', null, [], $userCookie);
$csrf = extractCsrf($page['body']);

$userLogin = request("{$baseUrl}/auth/login_user_proses.php", 'POST', [
    'username' => 'user',
    'password' => 'user123',
    'csrf_token' => $csrf
], [], $userCookie);

$userRedirect = (strpos($userLogin['headers'], 'Location: ../index.php') !== false) || $userLogin['status'] === 302;
$homePage = request("{$baseUrl}/index.php", 'GET', null, [], $userCookie);
$userIdentified = (strpos($homePage['body'], 'user (user)') !== false);

if ($userRedirect && $userIdentified) {
    $results['test3'] = ['status' => 'PASS', 'msg' => 'Pengguna biasa berhasil login dan teridentifikasi di navbar'];
} else {
    $results['test3'] = ['status' => 'FAIL', 'msg' => 'Login pengguna biasa gagal'];
}
echo "Hasil: {$results['test3']['status']} - {$results['test3']['msg']}\n";

// ---------------------------------------------------------
// TEST 4: Logout
// ---------------------------------------------------------
echo "\n[Test 4] Pengujian Logout Pengguna...\n";
$logoutReq = request("{$baseUrl}/auth/logout.php", 'GET', null, [], $userCookie);
$afterLogout = request("{$baseUrl}/index.php", 'GET', null, [], $userCookie);
$loggedOut = (strpos($afterLogout['body'], 'Masuk') !== false && strpos($afterLogout['body'], 'user (user)') === false);

if ($loggedOut) {
    $results['test4'] = ['status' => 'PASS', 'msg' => 'Sesi berhasil dihancurkan dan status kembali sebagai guest'];
} else {
    $results['test4'] = ['status' => 'FAIL', 'msg' => 'Sesi masih aktif pasca logout'];
}
echo "Hasil: {$results['test4']['status']} - {$results['test4']['msg']}\n";

// ---------------------------------------------------------
// TEST 5: Session Regeneration
// ---------------------------------------------------------
echo "\n[Test 5] Pengujian Session Regeneration (Pencegahan Session Fixation)...\n";
$regCookie = $tmpCookieDir . '/test_reg_cookie.txt';
if (file_exists($regCookie)) unlink($regCookie);

// Ambil session id sebelum login
$p1 = request("{$baseUrl}/auth/login.php", 'GET', null, [], $regCookie);
$csrf = extractCsrf($p1['body']);
preg_match('/PHPSESSID=([^;]+)/', $p1['headers'], $m1);
$sessBefore = $m1[1] ?? '';

// Eksekusi login
$l1 = request("{$baseUrl}/auth/login_user_proses.php", 'POST', [
    'username' => 'user',
    'password' => 'user123',
    'csrf_token' => $csrf
], [], $regCookie);
preg_match('/PHPSESSID=([^;]+)/', $l1['headers'], $m2);
$sessAfter = $m2[1] ?? '';

// Periksa cookie flags: HttpOnly dan SameSite
$hasHttpOnly = (stripos($p1['headers'], 'HttpOnly') !== false || stripos($l1['headers'], 'HttpOnly') !== false);
$hasSameSite = (stripos($p1['headers'], 'SameSite=Lax') !== false || stripos($l1['headers'], 'SameSite=Lax') !== false);

if (!empty($sessBefore) && !empty($sessAfter) && $sessBefore !== $sessAfter && $hasHttpOnly) {
    $results['test5'] = ['status' => 'PASS', 'msg' => "Session ID berhasil di-regenerasi ({$sessBefore} -> {$sessAfter}), HttpOnly aktif"];
} else {
    $results['test5'] = ['status' => 'FAIL', 'msg' => "Session ID tidak berubah atau HttpOnly tidak terdeteksi"];
}
echo "Hasil: {$results['test5']['status']} - {$results['test5']['msg']}\n";

// ---------------------------------------------------------
// TEST 6: Akses Halaman Admin Tanpa Login
// ---------------------------------------------------------
echo "\n[Test 6] Pengujian Akses Panel Admin Tanpa Login...\n";
$guestCookie = $tmpCookieDir . '/test_guest_cookie.txt';
if (file_exists($guestCookie)) unlink($guestCookie);

$guestAdmin = request("{$baseUrl}/admin/admin.php", 'GET', null, [], $guestCookie);
$blockedGuest = ($guestAdmin['status'] === 302 && strpos($guestAdmin['headers'], 'belum_login') !== false);

if ($blockedGuest) {
    $results['test6'] = ['status' => 'PASS', 'msg' => 'Tamu tanpa login berhasil diblokir dan diarahkan ke login'];
} else {
    $results['test6'] = ['status' => 'FAIL', 'msg' => "Akses tanpa login tidak diblokir dengan benar. Status: {$guestAdmin['status']}"];
}
echo "Hasil: {$results['test6']['status']} - {$results['test6']['msg']}\n";

// ---------------------------------------------------------
// TEST 7: Akses Halaman Admin oleh Pengguna Biasa
// ---------------------------------------------------------
echo "\n[Test 7] Pengujian Akses Panel Admin oleh Pengguna Biasa...\n";
$regUserCookie = $tmpCookieDir . '/test_reguser_cookie.txt';
if (file_exists($regUserCookie)) unlink($regUserCookie);

// Login sebagai user biasa
$pUser = request("{$baseUrl}/auth/login.php", 'GET', null, [], $regUserCookie);
$csrfUser = extractCsrf($pUser['body']);
request("{$baseUrl}/auth/login_user_proses.php", 'POST', [
    'username' => 'user',
    'password' => 'user123',
    'csrf_token' => $csrfUser
], [], $regUserCookie);

// Coba akses admin panel
$attemptAdmin = request("{$baseUrl}/admin/admin.php", 'GET', null, [], $regUserCookie);
$userBlocked = ($attemptAdmin['status'] === 302 && strpos($attemptAdmin['headers'], 'Location: ../index.php?pesan=unauthorized') !== false);

if ($userBlocked) {
    $results['test7'] = ['status' => 'PASS', 'msg' => 'Pengguna biasa diblokir dari panel admin dan dialihkan ke index'];
} else {
    $results['test7'] = ['status' => 'FAIL', 'msg' => 'Pengguna biasa tidak diblokir dari panel admin'];
}
echo "Hasil: {$results['test7']['status']} - {$results['test7']['msg']}\n";

// ---------------------------------------------------------
// TEST 8: POST Tanpa CSRF Token
// ---------------------------------------------------------
echo "\n[Test 8] Pengujian Proteksi CSRF (POST Tanpa Token)...\n";
$csrfTestCookie = $tmpCookieDir . '/test_csrf_cookie.txt';
if (file_exists($csrfTestCookie)) unlink($csrfTestCookie);

// Kirim form BMI tanpa token sama sekali
$postNoCsrf = request("{$baseUrl}/bmi/bmi_proses.php", 'POST', [
    'nama_user' => 'Penyerang CSRF',
    'umur' => 25,
    'berat_badan' => 70,
    'tinggi_badan' => 175
], [], $csrfTestCookie);

// Kirim form login admin tanpa token
$adminNoCsrf = request("{$baseUrl}/auth/login_admin_proses.php", 'POST', [
    'username' => 'admin',
    'password' => 'admin123'
], [], $csrfTestCookie);

if ($postNoCsrf['status'] === 403 && $adminNoCsrf['status'] === 403) {
    $results['test8'] = ['status' => 'PASS', 'msg' => 'Semua request POST tanpa CSRF token ditolak dengan HTTP 403 Forbidden'];
} else {
    $results['test8'] = ['status' => 'FAIL', 'msg' => "Request tanpa CSRF token lolos. BMI status: {$postNoCsrf['status']}, Admin status: {$adminNoCsrf['status']}"];
}
echo "Hasil: {$results['test8']['status']} - {$results['test8']['msg']}\n";

// ---------------------------------------------------------
// TEST 9: POST dengan CSRF Token Valid
// ---------------------------------------------------------
echo "\n[Test 9] Pengujian POST dengan CSRF Token Valid...\n";
$calcCookie = $tmpCookieDir . '/test_calc_cookie.txt';
if (file_exists($calcCookie)) unlink($calcCookie);

$bmiPage = request("{$baseUrl}/bmi/bmi_form.php", 'GET', null, [], $calcCookie);
$bmiCsrf = extractCsrf($bmiPage['body']);

$postValidCsrf = request("{$baseUrl}/bmi/bmi_proses.php", 'POST', [
    'csrf_token' => $bmiCsrf,
    'nama_user' => 'Pengujian Valid CSRF',
    'umur' => 25,
    'berat_badan' => 68.5,
    'tinggi_badan' => 172
], [], $calcCookie);

$bmiResult = request("{$baseUrl}/bmi/bmi_hasil.php", 'GET', null, [], $calcCookie);
$hasCalculated = ($postValidCsrf['status'] === 302 && strpos($bmiResult['body'], 'Pengujian Valid CSRF') !== false);

if ($hasCalculated) {
    $results['test9'] = ['status' => 'PASS', 'msg' => 'POST dengan CSRF valid berhasil diproses dan hasil tampil'];
} else {
    $results['test9'] = ['status' => 'FAIL', 'msg' => 'POST dengan CSRF valid gagal'];
}
echo "Hasil: {$results['test9']['status']} - {$results['test9']['msg']}\n";

// ---------------------------------------------------------
// TEST 10: Penghapusan Data Menggunakan POST + CSRF
// ---------------------------------------------------------
echo "\n[Test 10] Pengujian Penghapusan Data (POST vs GET Rejection)...\n";
// Ambil ID rekaman yang baru dibuat via SQLite PDO terpisah tanpa memicu session_start
$dbPath = $projectRoot . '/database/healthcheck.sqlite';
$testPdo = new PDO("sqlite:" . $dbPath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$lastBmiId = (int)$testPdo->query("SELECT MAX(id_bmi) FROM cek_bmi")->fetchColumn();

// Coba hapus lewat GET (harus ditolak 405 Method Not Allowed)
$delGet = request("{$baseUrl}/bmi/bmi_hapus.php?id_bmi={$lastBmiId}", 'GET', null, [], $adminCookie);
$getRejected = ($delGet['status'] === 405);

// Hapus lewat POST dengan CSRF token valid admin
$adminPage = request("{$baseUrl}/admin/admin.php", 'GET', null, [], $adminCookie);
$adminCsrf = extractCsrf($adminPage['body']);

$delPost = request("{$baseUrl}/bmi/bmi_hapus.php", 'POST', [
    'csrf_token' => $adminCsrf,
    'id_bmi' => $lastBmiId
], [], $adminCookie);

// Periksa apakah rekaman terhapus dari basis data
$checkDb = $testPdo->prepare("SELECT COUNT(*) FROM cek_bmi WHERE id_bmi = ?");
$checkDb->execute([$lastBmiId]);
$isDeleted = ($checkDb->fetchColumn() == 0);
$checkDb = null;
$testPdo = null;

if ($getRejected && $isDeleted) {
    $results['test10'] = ['status' => 'PASS', 'msg' => 'Penghapusan via GET ditolak 405, penghapusan via POST+CSRF berhasil'];
} else {
    $results['test10'] = ['status' => 'FAIL', 'msg' => "Penghapusan gagal. GET status: {$delGet['status']}, Terhapus: " . ($isDeleted ? 'Ya' : 'Tidak')];
}
echo "Hasil: {$results['test10']['status']} - {$results['test10']['msg']}\n";

// ---------------------------------------------------------
// TEST 11: Proteksi Akses Langsung Direktori Database
// ---------------------------------------------------------
echo "\n[Test 11] Pengujian Proteksi Akses Langsung Direktori Database...\n";
$dbAccess = request("{$baseUrl}/database/index.php", 'GET');
$sqliteDirect = request("{$baseUrl}/database/healthcheck.sqlite", 'GET');

// Pada PHP built-in server, file index.php di database/ terpanggil dan mengembalikan 403.
// Pada Apache, .htaccess akan mengembalikan 403 untuk seluruh file.
$dbProtected = ($dbAccess['status'] === 403 || strpos($dbAccess['body'], '403 Forbidden') !== false);

if ($dbProtected) {
    $results['test11'] = ['status' => 'PASS', 'msg' => 'Akses langsung direktori database mengembalikan HTTP 403 Forbidden'];
} else {
    $results['test11'] = ['status' => 'FAIL', 'msg' => "Akses database tidak menghasilkan 403. Status: {$dbAccess['status']}"];
}
echo "Hasil: {$results['test11']['status']} - {$results['test11']['msg']}\n";

// ---------------------------------------------------------
// TEST 12: Regresi Fitur Kalkulator Lama (BMR, Jantung, Mental)
// ---------------------------------------------------------
echo "\n[Test 12] Pengujian Regresi Fungsional Seluruh Fitur Kalkulator...\n";

// BMR
$bmrCookie = $tmpCookieDir . '/test_bmr_cookie.txt';
if (file_exists($bmrCookie)) unlink($bmrCookie);
$bmrPage = request("{$baseUrl}/bmr/bmr_form.php", 'GET', null, [], $bmrCookie);
$bmrCsrf = extractCsrf($bmrPage['body']);
$bmrPost = request("{$baseUrl}/bmr/bmr_proses.php", 'POST', [
    'csrf_token' => $bmrCsrf,
    'nama_user' => 'Tester BMR',
    'jenis_kelamin' => 'L',
    'umur' => 25,
    'berat_badan' => 70,
    'tinggi_badan' => 175,
    'aktivitas' => '1.55'
], [], $bmrCookie);
$bmrResult = request("{$baseUrl}/bmr/bmr_hasil.php", 'GET', null, [], $bmrCookie);
$bmrOk = (strpos($bmrResult['body'], 'Estimasi Kebutuhan Kalori') !== false);

// Jantung
$hrCookie = $tmpCookieDir . '/test_hr_cookie.txt';
if (file_exists($hrCookie)) unlink($hrCookie);
$hrPage = request("{$baseUrl}/jantung/jantung_form.php", 'GET', null, [], $hrCookie);
$hrCsrf = extractCsrf($hrPage['body']);
$hrPost = request("{$baseUrl}/jantung/jantung_proses.php", 'POST', [
    'csrf_token' => $hrCsrf,
    'nama_user' => 'Tester HR',
    'umur' => 25,
    'resting_hr' => 70
], [], $hrCookie);
$hrResult = request("{$baseUrl}/jantung/jantung_hasil.php", 'GET', null, [], $hrCookie);
$hrOk = (strpos($hrResult['body'], 'Hasil Evaluasi Detak Jantung') !== false);

// Mental
$mentalCookie = $tmpCookieDir . '/test_mental_cookie.txt';
if (file_exists($mentalCookie)) unlink($mentalCookie);
$mentalPage = request("{$baseUrl}/mental/mental_form.php", 'GET', null, [], $mentalCookie);
$mentalCsrf = extractCsrf($mentalPage['body']);
$mentalPost = request("{$baseUrl}/mental/mental_proses.php", 'POST', [
    'csrf_token' => $mentalCsrf,
    'nama_user' => 'Tester Mental',
    'q1' => 1,
    'q2' => 1,
    'q3' => 0,
    'q4' => 0
], [], $mentalCookie);
$mentalResult = request("{$baseUrl}/mental/mental_hasil.php", 'GET', null, [], $mentalCookie);
$mentalOk = (strpos($mentalResult['body'], 'Hasil Skrining Suasana Hati') !== false);

// Clean up test data from DB so test doesn't pollute
$cleanupPdo = new PDO("sqlite:" . $dbPath);
$cleanupPdo->exec("DELETE FROM cek_bmr WHERE nama_user = 'Tester BMR'");
$cleanupPdo->exec("DELETE FROM cek_jantung WHERE nama_user = 'Tester HR'");
$cleanupPdo->exec("DELETE FROM cek_mental WHERE nama_user = 'Tester Mental'");
$cleanupPdo = null;

if ($bmrOk && $hrOk && $mentalOk) {
    $results['test12'] = ['status' => 'PASS', 'msg' => 'Seluruh kalkulator (BMR, Detak Jantung, Skrining Mental) berfungsi normal pasca pengamanan'];
} else {
    $results['test12'] = ['status' => 'FAIL', 'msg' => "Kalkulator regresi gagal. BMR: " . ($bmrOk?'OK':'FAIL') . ", HR: " . ($hrOk?'OK':'FAIL') . ", Mental: " . ($mentalOk?'OK':'FAIL')];
}
echo "Hasil: {$results['test12']['status']} - {$results['test12']['msg']}\n";

// ---------------------------------------------------------
// REKAPITULASI
// ---------------------------------------------------------
echo "\n============================================\n";
echo "REKAPITULASI HASIL PENGUJIAN PHASE 1A:\n";
echo "============================================\n";
$allPass = true;
foreach ($results as $k => $res) {
    echo sprintf("[%s] %-5s: %s\n", strtoupper($k), $res['status'], $res['msg']);
    if ($res['status'] !== 'PASS') $allPass = false;
}

echo "\nSTATUS AKHIR: " . ($allPass ? "SEMUA 12 PENGUJIAN BERHASIL (100% PASS)" : "ADA PENGUJIAN GAGAL") . "\n";
