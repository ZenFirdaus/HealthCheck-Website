<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/bmr_helper.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../auth/login_cek.php';
require_once __DIR__ . '/../auth/role.php';

if (!is_admin()) {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $id_bmr = (int)($_POST['id_bmr'] ?? 0);
    $nama = trim($_POST['nama_user'] ?? '');
    $jk = $_POST['jenis_kelamin'] ?? 'L';
    $umur = filter_var($_POST['umur'] ?? 0, FILTER_VALIDATE_INT);
    $berat = filter_var($_POST['berat_badan'] ?? 0, FILTER_VALIDATE_FLOAT);
    $tinggi = filter_var($_POST['tinggi_badan'] ?? 0, FILTER_VALIDATE_FLOAT);
    $aktivitasInput = $_POST['aktivitas'] ?? '';
    $aktivitasFactor = get_activity_factor($aktivitasInput);

    if ($id_bmr <= 0 || empty($nama) || !in_array($jk, ['L', 'P'], true) ||
        !$umur || !$berat || !$tinggi ||
        $berat < 20 || $berat > 300 ||
        $tinggi < 80 || $tinggi > 250 ||
        $umur < 10 || $umur > 110 ||
        $aktivitasFactor === null) {
        header("Location: ../admin/admin.php?pesan=invalid_input");
        exit;
    }

    assert_record_access($pdo, 'cek_bmr', 'id_bmr', $id_bmr);

    $bmr = calculate_bmr($jk, $berat, $tinggi, $umur);
    $tdee = calculate_tdee($bmr, $aktivitasFactor);
    $labelAktivitas = get_activity_label($aktivitasFactor);

    $tujuanInput = trim($_POST['target_tujuan'] ?? 'maintain');
    $goals = get_dietary_goals();
    $tujuan = isset($goals[$tujuanInput]) ? $tujuanInput : 'maintain';
    $targetKalori = calculate_target_calories($tdee, $tujuan, $jk);
    $macros = calculate_macronutrients($targetKalori, 'balanced');

    $stmt = $pdo->prepare("UPDATE cek_bmr SET nama_user = ?, jenis_kelamin = ?, umur = ?, berat_badan = ?, tinggi_badan = ?, bmr = ?, aktivitas = ?, tdee = ?, target_tujuan = ?, target_kalori = ?, protein_gram = ?, karbo_gram = ?, lemak_gram = ? WHERE id_bmr = ?");
    $stmt->execute([$nama, $jk, $umur, $berat, $tinggi, $bmr, $labelAktivitas, $tdee, $tujuan, $targetKalori, $macros['protein_gram'], $macros['carbs_gram'], $macros['fat_gram'], $id_bmr]);

    header("Location: ../admin/admin.php?pesan=bmr_update");
    exit;
} else {
    header("Location: ../admin/admin.php");
    exit;
}
