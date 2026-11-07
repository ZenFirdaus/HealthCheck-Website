<?php
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $nama = trim($_POST['nama_user'] ?? '');
    
    if (empty($nama) || !isset($_POST['q1']) || !isset($_POST['q2']) || !isset($_POST['q3']) || !isset($_POST['q4'])) {
        header("Location: mental_form.php?pesan=invalid_input");
        exit;
    }

    $q1 = filter_var($_POST['q1'], FILTER_VALIDATE_INT);
    $q2 = filter_var($_POST['q2'], FILTER_VALIDATE_INT);
    $q3 = filter_var($_POST['q3'], FILTER_VALIDATE_INT);
    $q4 = filter_var($_POST['q4'], FILTER_VALIDATE_INT);

    if ($q1 === false || $q2 === false || $q3 === false || $q4 === false ||
        $q1 < 0 || $q1 > 3 || $q2 < 0 || $q2 > 3 || $q3 < 0 || $q3 > 3 || $q4 < 0 || $q4 > 3) {
        header("Location: mental_form.php?pesan=invalid_input");
        exit;
    }

    $skorCemas = $q1 + $q2;
    $skorDepresi = $q3 + $q4;
    $totalSkor = $skorCemas + $skorDepresi;

    if ($totalSkor <= 2) {
        $tingkat = "Normal / Minimal";
        $warna = "success";
        $anjuran = "Tingkat stres dan suasana hati Anda saat ini tergolong normal dan stabil. Tetap jaga keseimbangan rutinitas, waktu istirahat yang berkualitas, dan interaksi sosial yang positif.";
    } elseif ($totalSkor <= 5) {
        $tingkat = "Gejala Ringan (Mild)";
        $warna = "info";
        $anjuran = "Anda menunjukkan tanda-tanda stres atau kecemasan ringan. Luangkan waktu untuk relaksasi, kurangi beban kerja berlebih, olahraga teratur, dan bicarakan apa yang Anda rasakan dengan orang terdekat.";
    } elseif ($totalSkor <= 8) {
        $tingkat = "Gejala Sedang (Moderate)";
        $warna = "warning";
        $anjuran = "Anda mengalami tingkat kecemasan atau penurunan suasana hati sedang yang mungkin mulai mempengaruhi aktivitas harian. Disarankan untuk memprioritaskan istirahat dan mempertimbangkan konsultasi dengan psikolog atau konselor.";
    } else {
        $tingkat = "Gejala Berat (Severe)";
        $warna = "danger";
        $anjuran = "Hasil skrining menunjukkan beban emosional yang cukup berat dalam 2 minggu terakhir. Sangat disarankan untuk segera mencari bantuan profesional ke psikolog klinis, psikiater, atau fasilitas kesehatan terdekat.";
    }

    // Smart Storage:
    // Jika member (authenticated), simpan ke database dengan user_id server session.
    // Jika guest, simpan sementara di session saja.
    $userId = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0 ? (int)$_SESSION['user_id'] : null;
    $insertedId = null;

    if ($userId !== null) {
        try {
            $stmt = $pdo->prepare("INSERT INTO cek_mental (user_id, nama_user, skor_cemas, skor_depresi, total_skor, tingkat, anjuran) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $nama, $skorCemas, $skorDepresi, $totalSkor, $tingkat, $anjuran]);
            $insertedId = (int)$pdo->lastInsertId();
        } catch (\Throwable $e) {
            $insertedId = null;
        }
    }

    $_SESSION['mental_result'] = [
        'id_mental' => $insertedId,
        'user_id' => $userId,
        'nama' => $nama,
        'skorCemas' => $skorCemas,
        'skorDepresi' => $skorDepresi,
        'totalSkor' => $totalSkor,
        'tingkat' => $tingkat,
        'warna' => $warna,
        'anjuran' => $anjuran
    ];

    header("Location: mental_hasil.php");
    exit;
} else {
    header("Location: mental_form.php");
    exit;
}
