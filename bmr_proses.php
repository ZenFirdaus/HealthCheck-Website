<?php
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_user'] ?? '');
    $jk = $_POST['jenis_kelamin'] ?? 'L';
    $umur = filter_var($_POST['umur'] ?? 0, FILTER_VALIDATE_INT);
    $berat = filter_var($_POST['berat_badan'] ?? 0, FILTER_VALIDATE_FLOAT);
    $tinggi = filter_var($_POST['tinggi_badan'] ?? 0, FILTER_VALIDATE_FLOAT);
    $aktivitasFactor = filter_var($_POST['aktivitas'] ?? 1.55, FILTER_VALIDATE_FLOAT);

    // Validasi
    if (empty($nama) || !$umur || !$berat || !$tinggi || 
        $berat < 20 || $berat > 300 || 
        $tinggi < 80 || $tinggi > 250 || 
        $umur < 10 || $umur > 110) {
        header("Location: bmr_form.php?pesan=invalid_input");
        exit;
    }

    // Label aktivitas
    $labelAktivitas = match (strval($aktivitasFactor)) {
        '1.2' => 'Sedentary (Jarang bergerak)',
        '1.375' => 'Ringan (1-3 hari/minggu)',
        '1.55' => 'Sedang (3-5 hari/minggu)',
        '1.725' => 'Sangat Aktif (6-7 hari/minggu)',
        '1.9' => 'Ekstra Aktif (Pekerja berat/atlet)',
        default => 'Sedang'
    };

    // Mifflin - St Jeor Equation
    if ($jk === 'L') {
        $bmr = (10 * $berat) + (6.25 * $tinggi) - (5 * $umur) + 5;
    } else {
        $bmr = (10 * $berat) + (6.25 * $tinggi) - (5 * $umur) - 161;
    }
    $bmr = round($bmr, 0);

    // TDEE
    $tdee = round($bmr * $aktivitasFactor, 0);

    // Target kalori
    $kaloriTurun = max(1200, $tdee - 450); // Defisit sehat
    $kaloriNaik  = $tdee + 450;            // Surplus sehat

    // Simpan ke DB dengan prepared statement
    try {
        $stmt = $pdo->prepare("INSERT INTO cek_bmr (nama_user, jenis_kelamin, umur, berat_badan, tinggi_badan, bmr, aktivitas, tdee) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nama, $jk, $umur, $berat, $tinggi, $bmr, $labelAktivitas, $tdee]);
    } catch (\Throwable $e) {
        // Continue gracefully even if table has minor diff
    }

    $_SESSION['bmr_result'] = [
        'nama' => $nama,
        'jk' => ($jk === 'L') ? 'Laki-laki' : 'Perempuan',
        'umur' => $umur,
        'berat' => $berat,
        'tinggi' => $tinggi,
        'bmr' => $bmr,
        'aktivitas' => $labelAktivitas,
        'tdee' => $tdee,
        'kalori_turun' => $kaloriTurun,
        'kalori_naik' => $kaloriNaik
    ];

    header("Location: bmr_hasil.php");
    exit;
} else {
    header("Location: bmr_form.php");
    exit;
}
