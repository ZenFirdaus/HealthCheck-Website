<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/tensi_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $nama = trim($_POST['nama_user'] ?? '');
    $umur = filter_var($_POST['umur'] ?? 0, FILTER_VALIDATE_INT);
    $sistolik = filter_var($_POST['sistolik'] ?? 0, FILTER_VALIDATE_INT);
    $diastolik = filter_var($_POST['diastolik'] ?? 0, FILTER_VALIDATE_INT);
    $nadi = !empty($_POST['detak_nadi']) ? filter_var($_POST['detak_nadi'], FILTER_VALIDATE_INT) : null;
    $catatan = trim($_POST['catatan'] ?? '');

    // Server-side validation
    if (empty($nama) || !$umur || !$sistolik || !$diastolik ||
        $umur < 10 || $umur > 120 ||
        $sistolik < 70 || $sistolik > 260 ||
        $diastolik < 40 || $diastolik > 150 ||
        $sistolik <= $diastolik ||
        ($sistolik - $diastolik) < 15) {
        header("Location: tekanan_darah_form.php?pesan=invalid_input");
        exit;
    }

    if ($nadi !== null && ($nadi < 40 || $nadi > 200)) {
        header("Location: tekanan_darah_form.php?pesan=invalid_input");
        exit;
    }

    $bpData = classify_blood_pressure($sistolik, $diastolik);
    $kategori = $bpData['category'];
    $saran = $bpData['advice'];
    $pulsePressure = $sistolik - $diastolik;

    // Smart Storage:
    $userId = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0 ? (int)$_SESSION['user_id'] : null;
    $insertedId = null;

    if ($userId !== null) {
        try {
            $stmt = $pdo->prepare("INSERT INTO cek_tekanan_darah 
                (user_id, nama_user, umur, sistolik, diastolik, detak_nadi, kategori, saran, catatan) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $nama, $umur, $sistolik, $diastolik, $nadi, $kategori, $saran, $catatan]);
            $insertedId = (int)$pdo->lastInsertId();
        } catch (\Throwable $e) {
            $insertedId = null;
        }
    }

    $_SESSION['bp_result'] = [
        'id_bp'          => $insertedId,
        'user_id'        => $userId,
        'nama'           => $nama,
        'umur'           => $umur,
        'sistolik'       => $sistolik,
        'diastolik'      => $diastolik,
        'detak_nadi'     => $nadi,
        'pulse_pressure' => $pulsePressure,
        'kategori'       => $kategori,
        'badge'          => $bpData['badge'],
        'warna'          => $bpData['color'],
        'is_emergency'   => $bpData['is_emergency'],
        'saran'          => $saran,
        'catatan'        => $catatan
    ];

    header("Location: tekanan_darah_hasil.php");
    exit;
} else {
    header("Location: tekanan_darah_form.php");
    exit;
}
