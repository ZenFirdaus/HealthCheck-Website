<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/tidur_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $nama = trim($_POST['nama_user'] ?? '');
    $durasi = filter_var($_POST['durasi_jam'] ?? 0, FILTER_VALIDATE_FLOAT);
    $latensi = filter_var($_POST['latensi_menit'] ?? 0, FILTER_VALIDATE_INT);
    $awakenings = filter_var($_POST['frekuensi_terbangun'] ?? 0, FILTER_VALIDATE_INT);
    $screen = trim($_POST['penggunaan_gawai'] ?? 'tidak');
    $freshness = trim($_POST['kesegaran_bangun'] ?? 'cukup_segar');

    $validScreens = ['tidak', 'kadang', 'sering'];
    $validFreshness = ['sangat_segar', 'cukup_segar', 'kurang_segar', 'lelah_mengantuk'];

    if (empty($nama) || !$durasi || $latensi === false || $awakenings === false ||
        $durasi < 1.0 || $durasi > 24.0 ||
        $latensi < 0 || $latensi > 240 ||
        $awakenings < 0 || $awakenings > 20 ||
        !in_array($screen, $validScreens, true) ||
        !in_array($freshness, $validFreshness, true)) {
        header("Location: tidur_form.php?pesan=invalid_input");
        exit;
    }

    $calc = calculate_sleep_habit_score($durasi, $latensi, $awakenings, $screen, $freshness);
    $skor = $calc['score'];
    $efisiensi = $calc['efficiency'];
    $kategori = $calc['category'];
    $saran = $calc['advice'];

    // Smart Storage:
    $userId = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0 ? (int)$_SESSION['user_id'] : null;
    $insertedId = null;

    if ($userId !== null) {
        try {
            $stmt = $pdo->prepare("INSERT INTO cek_kualitas_tidur 
                (user_id, nama_user, durasi_jam, latensi_menit, frekuensi_terbangun, penggunaan_gawai, kesegaran_bangun, efisiensi_tidur, skor_kebiasaan, kategori_tidur, saran) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $nama, $durasi, $latensi, $awakenings, $screen, $freshness, $efisiensi, $skor, $kategori, $saran]);
            $insertedId = (int)$pdo->lastInsertId();
        } catch (\Throwable $e) {
            $insertedId = null;
        }
    }

    $_SESSION['sleep_result'] = [
        'id_tidur'        => $insertedId,
        'user_id'         => $userId,
        'nama'            => $nama,
        'durasi_jam'      => $durasi,
        'latensi_menit'   => $latensi,
        'awakenings'      => $awakenings,
        'efisiensi_tidur' => $efisiensi,
        'skor'            => $skor,
        'kategori'        => $kategori,
        'badge'           => $calc['badge'],
        'warna'           => $calc['color'],
        'saran'           => $saran
    ];

    header("Location: tidur_hasil.php");
    exit;
} else {
    header("Location: tidur_form.php");
    exit;
}
