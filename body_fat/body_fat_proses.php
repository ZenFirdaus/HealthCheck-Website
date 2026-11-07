<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/body_fat_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $nama = trim($_POST['nama_user'] ?? '');
    $jk = in_array($_POST['jenis_kelamin'] ?? 'L', ['L', 'P'], true) ? $_POST['jenis_kelamin'] : 'L';
    $umur = filter_var($_POST['umur'] ?? 0, FILTER_VALIDATE_INT);
    $berat = filter_var($_POST['berat_badan'] ?? 0, FILTER_VALIDATE_FLOAT);
    $tinggi = filter_var($_POST['tinggi_badan'] ?? 0, FILTER_VALIDATE_FLOAT);
    $leher = filter_var($_POST['lingkar_leher'] ?? 0, FILTER_VALIDATE_FLOAT);
    $pinggang = filter_var($_POST['lingkar_pinggang'] ?? 0, FILTER_VALIDATE_FLOAT);
    $pinggul = ($jk === 'P') ? filter_var($_POST['lingkar_pinggul'] ?? 0, FILTER_VALIDATE_FLOAT) : null;

    // Validasi dasar
    if (empty($nama) || !$umur || !$berat || !$tinggi || !$leher || !$pinggang ||
        $umur < 15 || $umur > 90 ||
        $berat < 20 || $berat > 300 ||
        $tinggi < 80 || $tinggi > 250 ||
        $leher < 20 || $leher > 60 ||
        $pinggang < 40 || $pinggang > 200) {
        header("Location: body_fat_form.php?pesan=invalid_input");
        exit;
    }

    if ($jk === 'P' && (!$pinggul || $pinggul < 50 || $pinggul > 200)) {
        header("Location: body_fat_form.php?pesan=invalid_input");
        exit;
    }

    $fatPct = calculate_body_fat_navy($jk, $tinggi, $pinggang, $leher, $pinggul);
    if ($fatPct === null) {
        header("Location: body_fat_form.php?pesan=invalid_input");
        exit;
    }

    $fatMass = round($berat * ($fatPct / 100.0), 1);
    $leanMass = round($berat - $fatMass, 1);
    $catData = get_body_fat_category($fatPct, $jk);
    $kategori = $catData['category'];

    // Smart Storage:
    $userId = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0 ? (int)$_SESSION['user_id'] : null;
    $insertedId = null;

    if ($userId !== null) {
        try {
            $stmt = $pdo->prepare("INSERT INTO cek_lemak_tubuh 
                (user_id, nama_user, jenis_kelamin, umur, tinggi_badan, berat_badan, lingkar_leher, lingkar_pinggang, lingkar_pinggul, body_fat_percent, fat_mass_kg, lean_mass_kg, kategori) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $nama, $jk, $umur, $tinggi, $berat, $leher, $pinggang, $pinggul, $fatPct, $fatMass, $leanMass, $kategori]);
            $insertedId = (int)$pdo->lastInsertId();
        } catch (\Throwable $e) {
            $insertedId = null;
        }
    }

    $_SESSION['body_fat_result'] = [
        'id_fat'           => $insertedId,
        'user_id'          => $userId,
        'nama'             => $nama,
        'jk'               => ($jk === 'L') ? 'Laki-laki' : 'Perempuan',
        'umur'             => $umur,
        'berat'            => $berat,
        'tinggi'           => $tinggi,
        'leher'            => $leher,
        'pinggang'         => $pinggang,
        'pinggul'          => $pinggul,
        'fat_percent'      => $fatPct,
        'fat_mass_kg'      => $fatMass,
        'lean_mass_kg'     => $leanMass,
        'kategori'         => $kategori,
        'badge'            => $catData['badge'],
        'warna'            => $catData['color'],
        'saran'            => $catData['advice']
    ];

    header("Location: body_fat_hasil.php");
    exit;
} else {
    header("Location: body_fat_form.php");
    exit;
}
