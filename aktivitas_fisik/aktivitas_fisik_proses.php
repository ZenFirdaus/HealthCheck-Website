<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/aktivitas_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $nama = trim($_POST['nama_user'] ?? '');
    $vigDays = filter_var($_POST['hari_berat'] ?? 0, FILTER_VALIDATE_INT);
    $vigMin  = filter_var($_POST['menit_berat'] ?? 0, FILTER_VALIDATE_INT);
    $modDays = filter_var($_POST['hari_sedang'] ?? 0, FILTER_VALIDATE_INT);
    $modMin  = filter_var($_POST['menit_sedang'] ?? 0, FILTER_VALIDATE_INT);
    $walkDays= filter_var($_POST['hari_jalan'] ?? 0, FILTER_VALIDATE_INT);
    $walkMin = filter_var($_POST['menit_jalan'] ?? 0, FILTER_VALIDATE_INT);
    $sitHours= filter_var($_POST['jam_duduk'] ?? 0, FILTER_VALIDATE_FLOAT);

    if (empty($nama) || $vigDays === false || $vigMin === false || $modDays === false || $modMin === false ||
        $walkDays === false || $walkMin === false || $sitHours === false ||
        $vigDays < 0 || $vigDays > 7 || $modDays < 0 || $modDays > 7 || $walkDays < 0 || $walkDays > 7 ||
        $vigMin < 0 || $vigMin > 960 || $modMin < 0 || $modMin > 960 || $walkMin < 0 || $walkMin > 960 ||
        $sitHours < 0.0 || $sitHours > 24.0) {
        header("Location: aktivitas_fisik_form.php?pesan=invalid_input");
        exit;
    }

    $calc = calculate_ipaq_activity($vigDays, $vigMin, $modDays, $modMin, $walkDays, $walkMin, $sitHours);
    $totalMet = $calc['total_met'];
    $kategori = $calc['kategori'];
    $saran = $calc['advice'];

    // Smart Storage:
    $userId = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0 ? (int)$_SESSION['user_id'] : null;
    $insertedId = null;

    if ($userId !== null) {
        try {
            $stmt = $pdo->prepare("INSERT INTO cek_aktivitas_fisik 
                (user_id, nama_user, hari_berat, menit_berat, hari_sedang, menit_sedang, hari_jalan, menit_jalan, jam_duduk, total_met_menit, kategori_aktivitas, saran) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $nama, $vigDays, $vigMin, $modDays, $modMin, $walkDays, $walkMin, $sitHours, $totalMet, $kategori, $saran]);
            $insertedId = (int)$pdo->lastInsertId();
        } catch (\Throwable $e) {
            $insertedId = null;
        }
    }

    $_SESSION['activity_result'] = [
        'id_aktivitas'       => $insertedId,
        'user_id'            => $userId,
        'nama'               => $nama,
        'total_met'          => $totalMet,
        'vigorous_met'       => $calc['vigorous_met'],
        'moderate_met'       => $calc['moderate_met'],
        'walking_met'        => $calc['walking_met'],
        'kategori'           => $kategori,
        'badge'              => $calc['badge'],
        'warna'              => $calc['color'],
        'saran'              => $saran,
        'sitting_hours'      => $sitHours,
        'sitting_warning'    => $calc['sitting_warning']
    ];

    header("Location: aktivitas_fisik_hasil.php");
    exit;
} else {
    header("Location: aktivitas_fisik_form.php");
    exit;
}
