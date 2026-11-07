<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/whtr_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $nama = trim($_POST['nama_user'] ?? '');
    $jk = in_array($_POST['jenis_kelamin'] ?? 'L', ['L', 'P'], true) ? $_POST['jenis_kelamin'] : 'L';
    $tinggi = filter_var($_POST['tinggi_badan'] ?? 0, FILTER_VALIDATE_FLOAT);
    $lingkar = filter_var($_POST['lingkar_pinggang'] ?? 0, FILTER_VALIDATE_FLOAT);

    // Validasi range masuk akal
    if (empty($nama) || !$tinggi || !$lingkar || 
        $tinggi < 50 || $tinggi > 250 || 
        $lingkar < 30 || $lingkar > 200) {
        header("Location: whtr_form.php?pesan=invalid_input");
        exit;
    }

    $ratio = calculate_whtr($lingkar, $tinggi);
    $categoryData = get_whtr_category($ratio);
    $kategori = $categoryData['category'];
    $saran = $categoryData['advice'];
    $batasSehat = get_healthy_waist_limit($tinggi);

    // Smart Storage:
    // Member -> simpan ke DB dengan user_id server session.
    // Guest -> simpan sementara di session saja.
    $userId = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0 ? (int)$_SESSION['user_id'] : null;
    $insertedId = null;

    if ($userId !== null) {
        try {
            $stmt = $pdo->prepare("INSERT INTO cek_whtr (user_id, nama_user, jenis_kelamin, lingkar_pinggang, tinggi_badan, rasio_whtr, kategori_risiko, saran) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $nama, $jk, $lingkar, $tinggi, $ratio, $kategori, $saran]);
            $insertedId = (int)$pdo->lastInsertId();
        } catch (\Throwable $e) {
            $insertedId = null;
        }
    }

    $_SESSION['whtr_result'] = [
        'id_whtr'          => $insertedId,
        'user_id'          => $userId,
        'nama'             => $nama,
        'jk'               => ($jk === 'L') ? 'Laki-laki' : 'Perempuan',
        'tinggi'           => $tinggi,
        'lingkar'          => $lingkar,
        'rasio'            => $ratio,
        'kategori'         => $kategori,
        'badge'            => $categoryData['badge'],
        'warna'            => $categoryData['color'],
        'saran'            => $saran,
        'batas_sehat'      => $batasSehat
    ];

    header("Location: whtr_hasil.php");
    exit;
} else {
    header("Location: whtr_form.php");
    exit;
}
