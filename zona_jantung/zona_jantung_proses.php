<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/zona_jantung_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $nama = trim($_POST['nama_user'] ?? '');
    $umur = filter_var($_POST['umur'] ?? 0, FILTER_VALIDATE_INT);
    $rhr = filter_var($_POST['resting_hr'] ?? 0, FILTER_VALIDATE_INT);
    $metode = trim($_POST['metode'] ?? 'karvonen');

    if (!in_array($metode, ['karvonen', 'zoladz'], true)) {
        $metode = 'karvonen';
    }

    if (empty($nama) || !$umur || !$rhr ||
        $umur < 10 || $umur > 100 ||
        $rhr < 40 || $rhr > 180) {
        header("Location: zona_jantung_form.php?pesan=invalid_input");
        exit;
    }

    $maxHr = calculate_max_hr($umur);
    $hrr = max(10, $maxHr - $rhr);

    if ($metode === 'zoladz') {
        $calc = calculate_zoladz_zones($umur);
    } else {
        $calc = calculate_karvonen_zones($umur, $rhr);
    }

    $z = $calc['zones'];
    $saran = "Gunakan rentang target denyut jantung {$metode} ini sebagai panduan intensitas latihan fisik Anda. Zona 2 optimal untuk pembakaran lemak, dan Zona 3-4 untuk ketahanan kardiorespirasi.";

    // Smart Storage:
    $userId = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0 ? (int)$_SESSION['user_id'] : null;
    $insertedId = null;

    if ($userId !== null) {
        try {
            $stmt = $pdo->prepare("INSERT INTO cek_zona_detak_jantung 
                (user_id, nama_user, umur, resting_hr, max_hr, hrr, metode, zona_1_min, zona_1_max, zona_2_min, zona_2_max, zona_3_min, zona_3_max, zona_4_min, zona_4_max, zona_5_min, zona_5_max, saran) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $userId, $nama, $umur, $rhr, $maxHr, $hrr, $metode,
                $z[1]['min'], $z[1]['max'],
                $z[2]['min'], $z[2]['max'],
                $z[3]['min'], $z[3]['max'],
                $z[4]['min'], $z[4]['max'],
                $z[5]['min'], $z[5]['max'],
                $saran
            ]);
            $insertedId = (int)$pdo->lastInsertId();
        } catch (\Throwable $e) {
            $insertedId = null;
        }
    }

    $_SESSION['zona_hr_result'] = [
        'id_zona'    => $insertedId,
        'user_id'    => $userId,
        'nama'       => $nama,
        'umur'       => $umur,
        'resting_hr' => $rhr,
        'max_hr'     => $maxHr,
        'hrr'        => $hrr,
        'metode'     => $metode,
        'zones'      => $z,
        'saran'      => $saran
    ];

    header("Location: zona_jantung_hasil.php");
    exit;
} else {
    header("Location: zona_jantung_form.php");
    exit;
}
