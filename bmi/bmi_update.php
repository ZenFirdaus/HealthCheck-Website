<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../auth/login_cek.php';
require_once __DIR__ . '/../auth/role.php';

if (!is_admin()) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $id_bmi = (int)($_POST['id_bmi'] ?? 0);
    $nama = trim($_POST['nama_user'] ?? '');
    $umur = filter_var($_POST['umur'] ?? 0, FILTER_VALIDATE_INT);
    $berat = filter_var($_POST['berat_badan'] ?? 0, FILTER_VALIDATE_FLOAT);
    $tinggi = filter_var($_POST['tinggi_badan'] ?? 0, FILTER_VALIDATE_FLOAT);

    if ($id_bmi <= 0 || empty($nama) || !$umur || !$berat || !$tinggi || 
        $berat < 10 || $berat > 350 || $tinggi < 50 || $tinggi > 250 || $umur < 5 || $umur > 120) {
        header("Location: ../admin/admin.php?pesan=invalid_input");
        exit;
    }

    assert_record_access($pdo, 'cek_bmi', 'id_bmi', $id_bmi);

    $tinggi_m = $tinggi / 100;
    $bmi = round($berat / ($tinggi_m * $tinggi_m), 1);

    if ($bmi < 18.5) {
        $kondisi = "Kurus (Kekurangan Berat Badan)";
    } elseif ($bmi <= 22.9) {
        $kondisi = "Normal (Berat Badan Ideal)";
    } elseif ($bmi <= 24.9) {
        $kondisi = "Kelebihan Berat Badan (Overweight)";
    } elseif ($bmi <= 29.9) {
        $kondisi = "Obesitas Tingkat 1";
    } else {
        $kondisi = "Obesitas Tingkat 2 (Tinggi)";
    }

    $stmt = $pdo->prepare("UPDATE cek_bmi SET nama_user = ?, umur = ?, berat_badan = ?, tinggi_badan = ?, bmi = ?, kondisi = ? WHERE id_bmi = ?");
    $stmt->execute([$nama, $umur, $berat, $tinggi, $bmi, $kondisi, $id_bmi]);

    header("Location: ../admin/admin.php?pesan=berhasil_update");
    exit;
} else {
    header("Location: ../admin/admin.php");
    exit;
}
