<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/stres_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $nama = trim($_POST['nama_user'] ?? '');
    if (empty($nama)) {
        header("Location: stres_form.php?pesan=invalid_input");
        exit;
    }

    $answers = [];
    for ($i = 1; $i <= 10; $i++) {
        if (!isset($_POST['q' . $i])) {
            header("Location: stres_form.php?pesan=invalid_input");
            exit;
        }
        $val = filter_var($_POST['q' . $i], FILTER_VALIDATE_INT);
        if ($val === false || $val < 0 || $val > 4) {
            header("Location: stres_form.php?pesan=invalid_input");
            exit;
        }
        $answers['q' . $i] = $val;
    }

    $calc = calculate_pss_stress_score($answers);
    $skor = $calc['total_score'];
    $tingkat = $calc['tingkat'];
    $saran = $calc['advice'];

    // Smart Storage:
    $userId = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0 ? (int)$_SESSION['user_id'] : null;
    $insertedId = null;

    if ($userId !== null) {
        try {
            $stmt = $pdo->prepare("INSERT INTO cek_stres 
                (user_id, nama_user, skor_stres, tingkat_stres, saran) 
                VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $nama, $skor, $tingkat, $saran]);
            $insertedId = (int)$pdo->lastInsertId();
        } catch (\Throwable $e) {
            $insertedId = null;
        }
    }

    $_SESSION['stress_result'] = [
        'id_stres'      => $insertedId,
        'user_id'       => $userId,
        'nama'          => $nama,
        'skor'          => $skor,
        'direct_score'  => $calc['direct_score'],
        'reverse_score' => $calc['reverse_score'],
        'tingkat'       => $tingkat,
        'badge'         => $calc['badge'],
        'warna'         => $calc['color'],
        'saran'         => $saran
    ];

    header("Location: stres_hasil.php");
    exit;
} else {
    header("Location: stres_form.php");
    exit;
}
