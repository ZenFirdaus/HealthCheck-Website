<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/bmr_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $nama = trim($_POST['nama_user'] ?? '');
    $jk = $_POST['jenis_kelamin'] ?? 'L';
    $umur = filter_var($_POST['umur'] ?? 0, FILTER_VALIDATE_INT);
    $berat = filter_var($_POST['berat_badan'] ?? 0, FILTER_VALIDATE_FLOAT);
    $tinggi = filter_var($_POST['tinggi_badan'] ?? 0, FILTER_VALIDATE_FLOAT);
    $aktivitasInput = $_POST['aktivitas'] ?? '';
    $aktivitasFactor = get_activity_factor($aktivitasInput);

    // Validasi
    if (empty($nama) || !in_array($jk, ['L', 'P'], true) ||
        !$umur || !$berat || !$tinggi || 
        $berat < 20 || $berat > 300 || 
        $tinggi < 80 || $tinggi > 250 || 
        $umur < 10 || $umur > 110 ||
        $aktivitasFactor === null) {
        header("Location: bmr_form.php?pesan=invalid_input");
        exit;
    }

    // Label aktivitas dari centralized helper
    $labelAktivitas = get_activity_label($aktivitasFactor);

    // Mifflin - St Jeor Equation via centralized helper
    $bmr = calculate_bmr($jk, $berat, $tinggi, $umur);

    // TDEE via centralized helper
    $tdee = calculate_tdee($bmr, $aktivitasFactor);

    // Target kalori
    $kaloriTurun = max(1200, $tdee - 450); // Defisit sehat
    $kaloriNaik  = $tdee + 450;            // Surplus sehat

    $tujuanInput = trim($_POST['target_tujuan'] ?? 'maintain');
    $goals = get_dietary_goals();
    $tujuan = isset($goals[$tujuanInput]) ? $tujuanInput : 'maintain';
    $targetKalori = calculate_target_calories($tdee, $tujuan, $jk);
    $macros = calculate_macronutrients($targetKalori, 'balanced');

    // Smart Storage:
    // Jika member (authenticated), simpan ke database dengan user_id server session.
    // Jika guest, simpan sementara di session saja.
    $userId = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0 ? (int)$_SESSION['user_id'] : null;
    $insertedId = null;

    if ($userId !== null) {
        try {
            $stmt = $pdo->prepare("INSERT INTO cek_bmr (user_id, nama_user, jenis_kelamin, umur, berat_badan, tinggi_badan, bmr, aktivitas, tdee, target_tujuan, target_kalori, protein_gram, karbo_gram, lemak_gram) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $nama, $jk, $umur, $berat, $tinggi, $bmr, $labelAktivitas, $tdee, $tujuan, $targetKalori, $macros['protein_gram'], $macros['carbs_gram'], $macros['fat_gram']]);
            $insertedId = (int)$pdo->lastInsertId();
        } catch (\Throwable $e) {
            $insertedId = null;
        }
    }

    $_SESSION['bmr_result'] = [
        'id_bmr'        => $insertedId,
        'user_id'       => $userId,
        'nama'          => $nama,
        'jk'            => ($jk === 'L') ? 'Laki-laki' : 'Perempuan',
        'umur'          => $umur,
        'berat'         => $berat,
        'tinggi'        => $tinggi,
        'bmr'           => $bmr,
        'aktivitas'     => $labelAktivitas,
        'tdee'          => $tdee,
        'kalori_turun'  => $kaloriTurun,
        'kalori_naik'   => $kaloriNaik,
        'target_tujuan' => $tujuan,
        'target_label'  => $goals[$tujuan]['label'],
        'target_kalori' => $targetKalori,
        'protein_gram'  => $macros['protein_gram'],
        'carbs_gram'    => $macros['carbs_gram'],
        'fat_gram'      => $macros['fat_gram']
    ];

    header("Location: bmr_hasil.php");
    exit;
} else {
    header("Location: bmr_form.php");
    exit;
}
