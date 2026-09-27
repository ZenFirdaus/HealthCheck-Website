<?php
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_user'] ?? '');
    $umur = filter_var($_POST['umur'] ?? 0, FILTER_VALIDATE_INT);
    $rhr = filter_var($_POST['resting_hr'] ?? 0, FILTER_VALIDATE_INT);

    if (empty($nama) || !$umur || !$rhr || $umur < 10 || $umur > 100 || $rhr < 40 || $rhr > 180) {
        header("Location: jantung_form.php?pesan=invalid_input");
        exit;
    }

    // Tanaka Formula for Maximum Heart Rate
    $maxHr = round(208 - (0.7 * $umur));

    // Evaluasi Resting Heart Rate
    if ($rhr < 60) {
        $kategoriRhr = "Rendah / Sangat Bugar (Atletik)";
        $warna = "info";
        $penjelasanRhr = "Denyut nadi istirahat di bawah 60 bpm lazim dijumpai pada atlet atau orang dengan kebugaran kardiovaskular tinggi. Namun jika disertai pusing atau lemas, konsultasikan ke dokter.";
    } elseif ($rhr <= 100) {
        $kategoriRhr = "Normal Sehat";
        $warna = "success";
        $penjelasanRhr = "Denyut nadi istirahat Anda berada dalam rentang ideal orang dewasa sehat (60 - 100 bpm).";
    } else {
        $kategoriRhr = "Tinggi (Takikardia Ringan)";
        $warna = "danger";
        $penjelasanRhr = "Denyut nadi istirahat di atas 100 bpm saat sedang tenang dapat dipicu dehidrasi, konsumsi kafein/nikotin, stres, demam, atau kondisi kardiovaskular tertentu.";
    }

    // Target Heart Rate Zones (Karvonen Method: Target = RHR + (% * (MaxHR - RHR)))
    $hrr = $maxHr - $rhr;

    $zones = [
        [
            'nama' => 'Zona 1: Pemulihan Ringan (50 - 60%)',
            'min' => round($rhr + (0.50 * $hrr)),
            'max' => round($rhr + (0.60 * $hrr)),
            'tujuan' => 'Pemanasan, pendinginan, dan pemulihan aktif setelah olahraga berat.',
            'warna' => 'secondary'
        ],
        [
            'nama' => 'Zona 2: Pembakaran Lemak & Stamina (60 - 70%)',
            'min' => round($rhr + (0.60 * $hrr)),
            'max' => round($rhr + (0.70 * $hrr)),
            'tujuan' => 'Meningkatkan efisiensi pembakaran lemak dasar dan daya tahan tubuh jangka panjang.',
            'warna' => 'success'
        ],
        [
            'nama' => 'Zona 3: Aerobik & Kardio Paru (70 - 80%)',
            'min' => round($rhr + (0.70 * $hrr)),
            'max' => round($rhr + (0.80 * $hrr)),
            'tujuan' => 'Memperkuat otot jantung dan kapasitas paru-paru secara optimal.',
            'warna' => 'primary'
        ],
        [
            'nama' => 'Zona 4: Anaerobik Intensif (80 - 90%)',
            'min' => round($rhr + (0.80 * $hrr)),
            'max' => round($rhr + (0.90 * $hrr)),
            'tujuan' => 'Melatih ambang laktat dan kecepatan sprint (lakukan dalam durasi pendek).',
            'warna' => 'warning'
        ]
    ];

    // Simpan ke DB
    try {
        $stmt = $pdo->prepare("INSERT INTO cek_jantung (nama_user, umur, resting_hr, max_hr, kategori, catatan) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nama, $umur, $rhr, $maxHr, $kategoriRhr, $penjelasanRhr]);
    } catch (\Throwable $e) {}

    $_SESSION['jantung_result'] = [
        'nama' => $nama,
        'umur' => $umur,
        'rhr' => $rhr,
        'maxHr' => $maxHr,
        'kategoriRhr' => $kategoriRhr,
        'warna' => $warna,
        'penjelasanRhr' => $penjelasanRhr,
        'zones' => $zones
    ];

    header("Location: jantung_hasil.php");
    exit;
} else {
    header("Location: jantung_form.php");
    exit;
}
