<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/login_cek.php';
require_once __DIR__ . '/role.php';

if (!is_admin()) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_bmr = (int)($_POST['id_bmr'] ?? 0);
    $nama = trim($_POST['nama_user'] ?? '');
    $jk = $_POST['jenis_kelamin'] ?? 'L';
    $umur = filter_var($_POST['umur'] ?? 0, FILTER_VALIDATE_INT);
    $berat = filter_var($_POST['berat_badan'] ?? 0, FILTER_VALIDATE_FLOAT);
    $tinggi = filter_var($_POST['tinggi_badan'] ?? 0, FILTER_VALIDATE_FLOAT);

    if ($id_bmr <= 0 || empty($nama) || !$umur || !$berat || !$tinggi || $berat <= 0 || $tinggi <= 0) {
        header("Location: admin.php?pesan=invalid_input");
        exit;
    }

    // Mifflin - St Jeor formula
    if ($jk === 'L') {
        $bmr = (10 * $berat) + (6.25 * $tinggi) - (5 * $umur) + 5;
    } else {
        $bmr = (10 * $berat) + (6.25 * $tinggi) - (5 * $umur) - 161;
    }
    $bmr = round($bmr, 0);
    $tdee = round($bmr * 1.55, 0);

    $stmt = $pdo->prepare("UPDATE cek_bmr SET nama_user = ?, jenis_kelamin = ?, umur = ?, berat_badan = ?, tinggi_badan = ?, bmr = ?, tdee = ? WHERE id_bmr = ?");
    $stmt->execute([$nama, $jk, $umur, $berat, $tinggi, $bmr, $tdee, $id_bmr]);

    header("Location: admin.php?pesan=bmr_update");
    exit;
} else {
    header("Location: admin.php");
    exit;
}
