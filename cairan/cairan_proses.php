<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/cairan_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $nama = trim($_POST['nama_user'] ?? '');
    $berat = filter_var($_POST['berat_badan'] ?? 0, FILTER_VALIDATE_FLOAT);
    $aktivitas = trim($_POST['tingkat_aktivitas'] ?? 'sedang');
    $iklim = trim($_POST['kondisi_iklim'] ?? 'sedang');

    $actLevels = get_fluid_activity_levels();
    $climFactors = get_fluid_climate_factors();

    if (empty($nama) || !$berat || $berat < 15 || $berat > 250 ||
        !isset($actLevels[$aktivitas]) || !isset($climFactors[$iklim])) {
        header("Location: cairan_form.php?pesan=invalid_input");
        exit;
    }

    $calc = calculate_fluid_needs($berat, $aktivitas, $iklim);
    $kebutuhanMl = $calc['kebutuhan_ml'];
    $kebutuhanLiter = $calc['kebutuhan_l'];
    $gelas = $calc['gelas'];

    $saran = "Estimasi kebutuhan hidrasi harian Anda adalah sekitar {$kebutuhanLiter} liter ({$kebutuhanMl} ml) atau setara {$gelas} gelas air (@250 ml). Bagilah konsumsi air secara merata dari pagi hingga malam.";

    // Smart Storage:
    $userId = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0 ? (int)$_SESSION['user_id'] : null;
    $insertedId = null;

    if ($userId !== null) {
        try {
            $stmt = $pdo->prepare("INSERT INTO cek_kebutuhan_cairan 
                (user_id, nama_user, berat_badan, tingkat_aktivitas, kondisi_iklim, kebutuhan_ml, kebutuhan_liter, kebutuhan_gelas, saran) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $nama, $berat, $aktivitas, $iklim, $kebutuhanMl, $kebutuhanLiter, $gelas, $saran]);
            $insertedId = (int)$pdo->lastInsertId();
        } catch (\Throwable $e) {
            $insertedId = null;
        }
    }

    $_SESSION['cairan_result'] = [
        'id_cairan'       => $insertedId,
        'user_id'         => $userId,
        'nama'            => $nama,
        'berat'           => $berat,
        'aktivitas_label' => $actLevels[$aktivitas]['label'],
        'iklim_label'     => $climFactors[$iklim]['label'],
        'kebutuhan_ml'    => $kebutuhanMl,
        'kebutuhan_liter' => $kebutuhanLiter,
        'gelas'           => $gelas,
        'saran'           => $saran
    ];

    header("Location: cairan_hasil.php");
    exit;
} else {
    header("Location: cairan_form.php");
    exit;
}
