<?php
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $nama = trim($_POST['nama_user'] ?? '');
    $umur = filter_var($_POST['umur'] ?? 0, FILTER_VALIDATE_INT);
    $berat = filter_var($_POST['berat_badan'] ?? 0, FILTER_VALIDATE_FLOAT);
    $tinggi = filter_var($_POST['tinggi_badan'] ?? 0, FILTER_VALIDATE_FLOAT);

    // Validasi range masuk akal sesuai PRD 3.5.A
    if (empty($nama) || !$umur || !$berat || !$tinggi || 
        $berat < 10 || $berat > 350 || 
        $tinggi < 50 || $tinggi > 250 || 
        $umur < 5 || $umur > 120) {
        header("Location: bmi_form.php?pesan=invalid_input");
        exit;
    }

    $tinggi_m = $tinggi / 100;
    $bmi = $berat / ($tinggi_m * $tinggi_m);
    $bmi = round($bmi, 1);

    // Klasifikasi WHO & Kemenkes RI Asia-Pacific
    if ($bmi < 18.5) {
        $kondisi = "Kurus (Kekurangan Berat Badan)";
        $warna = "warning";
    } elseif ($bmi <= 22.9) {
        $kondisi = "Normal (Berat Badan Ideal)";
        $warna = "success";
    } elseif ($bmi <= 24.9) {
        $kondisi = "Kelebihan Berat Badan (Overweight)";
        $warna = "warning";
    } elseif ($bmi <= 29.9) {
        $kondisi = "Obesitas Tingkat 1";
        $warna = "danger";
    } else {
        $kondisi = "Obesitas Tingkat 2 (Tinggi)";
        $warna = "danger";
    }

    // Hitung berat badan ideal perkiraan (Metode Broca modifikasi)
    $bb_ideal_min = round(18.5 * ($tinggi_m * $tinggi_m), 1);
    $bb_ideal_max = round(22.9 * ($tinggi_m * $tinggi_m), 1);

    // Smart Storage:
    // Jika member (authenticated), simpan ke database dengan user_id server session.
    // Jika guest, jangan simpan ke tabel pengguna; simpan sementara di session saja.
    $userId = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0 ? (int)$_SESSION['user_id'] : null;
    $insertedId = null;

    if ($userId !== null) {
        try {
            $stmt = $pdo->prepare("INSERT INTO cek_bmi (user_id, nama_user, umur, berat_badan, tinggi_badan, bmi, kondisi) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $nama, $umur, $berat, $tinggi, $bmi, $kondisi]);
            $insertedId = (int)$pdo->lastInsertId();
        } catch (\Throwable $e) {
            $insertedId = null;
        }
    }

    // Simpan hasil ke session agar tidak terekspos di URL atau bisa diakses kembali
    $_SESSION['bmi_result'] = [
        'id_bmi' => $insertedId,
        'user_id' => $userId,
        'nama' => $nama,
        'umur' => $umur,
        'berat' => $berat,
        'tinggi' => $tinggi,
        'bmi' => $bmi,
        'kondisi' => $kondisi,
        'warna' => $warna,
        'bb_min' => $bb_ideal_min,
        'bb_max' => $bb_ideal_max
    ];

    header("Location: bmi_hasil.php");
    exit;
} else {
    header("Location: bmi_form.php");
    exit;
}
