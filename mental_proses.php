<?php
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_user'] ?? 'Pengguna');
    $q1 = filter_var($_POST['q1'] ?? 0, FILTER_VALIDATE_INT);
    $q2 = filter_var($_POST['q2'] ?? 0, FILTER_VALIDATE_INT);
    $q3 = filter_var($_POST['q3'] ?? 0, FILTER_VALIDATE_INT);
    $q4 = filter_var($_POST['q4'] ?? 0, FILTER_VALIDATE_INT);

    // Batasi nilai range 0-3
    $q1 = max(0, min(3, (int)$q1));
    $q2 = max(0, min(3, (int)$q2));
    $q3 = max(0, min(3, (int)$q3));
    $q4 = max(0, min(3, (int)$q4));

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
        $anjuran = "Hasil screening menunjukkan beban emosional yang cukup berat dalam 2 minggu terakhir. Sangat disarankan untuk segera mencari bantuan profesional ke psikolog klinis, psikiater, atau fasilitas kesehatan terdekat.";
    }

    // Simpan ke DB
    try {
        $stmt = $pdo->prepare("INSERT INTO cek_mental (nama_user, skor_cemas, skor_depresi, total_skor, tingkat, anjuran) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nama, $skorCemas, $skorDepresi, $totalSkor, $tingkat, $anjuran]);
    } catch (\Throwable $e) {}

    $_SESSION['mental_result'] = [
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
