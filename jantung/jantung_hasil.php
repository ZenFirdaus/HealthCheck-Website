<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_helper.php';

$res = null;

if (isset($_GET['id']) && (int)$_GET['id'] > 0) {
    $record = assert_record_access($pdo, 'cek_jantung', 'id_jantung', (int)$_GET['id']);
    $maxHr = (int)$record['max_hr'];
    $rhr = (int)$record['resting_hr'];
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

    $res = [
        'id_jantung' => (int)$record['id_jantung'],
        'user_id' => $record['user_id'] ? (int)$record['user_id'] : null,
        'nama' => $record['nama_user'],
        'umur' => (int)$record['umur'],
        'rhr' => $rhr,
        'maxHr' => $maxHr,
        'kategoriRhr' => $record['kategori'],
        'warna' => str_contains($record['kategori'], 'Normal') ? 'success' : ((str_contains($record['kategori'], 'Atletik') || str_contains($record['kategori'], 'Rendah')) ? 'info' : 'danger'),
        'penjelasanRhr' => $record['catatan'],
        'zones' => $zones
    ];
} else {
    $res = $_SESSION['jantung_result'] ?? null;
    if ($res) {
        if (isset($res['user_id']) && $res['user_id'] !== null && !can_access_user_data((int)$res['user_id'])) {
            http_response_code(403);
            die("403 Forbidden: Anda tidak memiliki hak akses terhadap hasil pemeriksaan ini.");
        }
    } else {
        header("Location: jantung_form.php");
        exit;
    }
}

$pageTitle = 'Hasil Analisis Detak Jantung & Zona Kardio — HealthCheck';
$activePage = 'jantung';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="jantung_form.php" class="text-decoration-none">Kalkulator Detak Jantung</a></li>
            <li class="breadcrumb-item active" aria-current="page">Hasil Detak Jantung</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card overflow-hidden mb-4">
                <div class="card-editorial-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0" style="color: var(--primary);">Hasil Evaluasi Detak Jantung</h5>
                    <span class="badge border px-3 py-1 small" style="background-color: var(--light-bg); color: var(--primary); border-color: var(--card-border) !important;">
                        <?= htmlspecialchars($res['nama']) ?>
                    </span>
                </div>

                <div class="p-4 p-md-5">
                    
                    <!-- Dua Metrik Utama -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-4 rounded-3 text-center border h-100" style="background-color: var(--light-bg); border-color: var(--card-border) !important;">
                                <span class="text-uppercase fw-semibold small" style="color: var(--muted); letter-spacing: 0.05em;">Detak Nadi Istirahat (RHR)</span>
                                <div class="metric-number my-2 text-<?= $res['warna'] ?>">
                                    <?= $res['rhr'] ?> <span class="fs-6 fw-normal" style="color: var(--muted);">BPM</span>
                                </div>
                                <span class="badge bg-<?= $res['warna'] ?>-subtle px-3 py-1">
                                    <?= $res['kategoriRhr'] ?>
                                </span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-4 rounded-3 text-center border h-100" style="background-color: var(--light-bg); border-color: var(--card-border) !important;">
                                <span class="text-uppercase fw-semibold small" style="color: var(--muted); letter-spacing: 0.05em;">Detak Jantung Maksimal (HR Max)</span>
                                <div class="metric-number my-2" style="color: var(--primary);">
                                    <?= $res['maxHr'] ?> <span class="fs-6 fw-normal" style="color: var(--muted);">BPM</span>
                                </div>
                                <small style="color: var(--muted);">Estimasi batas tertinggi denyut saat beban maksimal (Usia <?= $res['umur'] ?> thn).</small>
                            </div>
                        </div>
                    </div>

                    <!-- Keterangan Status Nadi -->
                    <div class="p-3 border rounded-3 mb-4" style="background-color: var(--surface); border-color: var(--card-border) !important;">
                        <h6 class="fw-bold mb-1" style="color: var(--primary);">Evaluasi Kondisi Nadi Istirahat</h6>
                        <p class="small mb-0" style="color: var(--muted); line-height: 1.55;"><?= htmlspecialchars($res['penjelasanRhr']) ?></p>
                    </div>

                    <!-- Tabel Zona Target Heart Rate (Karvonen) -->
                    <h6 class="fw-bold mb-3" style="color: var(--primary);">Zona Latihan Kardio Optimal</h6>
                    
                    <div class="d-flex flex-column gap-3 mb-4">
                        <?php foreach ($res['zones'] as $z): ?>
                            <div class="p-3 rounded-3 border d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2" style="background-color: var(--surface); border-color: var(--card-border) !important;">
                                <div>
                                    <div class="fw-bold small mb-1" style="color: var(--primary);"><?= $z['nama'] ?></div>
                                    <div class="small" style="color: var(--muted);"><?= $z['tujuan'] ?></div>
                                </div>
                                <div class="text-md-end text-nowrap">
                                    <span class="badge bg-<?= $z['warna'] ?>-subtle fs-6 px-3 py-2 rounded-2">
                                        <?= $z['min'] ?> – <?= $z['max'] ?> BPM
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Kapan Perlu Bantuan Medis -->
                    <div class="alert alert-warning small mb-4">
                        <h6 class="fw-bold mb-1" style="color: var(--primary);">Kapan Harus Berkonsultasi ke Dokter?</h6>
                        <ul class="mb-0 ps-3" style="color: var(--dark); line-height: 1.55;">
                            <li>Jika denyut jantung istirahat rutin berada di atas 100 BPM tanpa sebab yang jelas.</li>
                            <li>Mengalami denyut yang terasa meloncat-loncat, tidak berirama (aritmia), atau berdebar kencang saat istirahat.</li>
                            <li>Disertai gejala nyeri dada sebelah kiri/tengah, sesak napas saat aktivitas ringan, pusing berkunang-kunang, atau pingsan.</li>
                        </ul>
                    </div>

                    <!-- Disclaimer Box -->
                    <div class="disclaimer-box m-0">
                        <strong class="d-block" style="color: var(--primary);">Disclaimer Medis</strong>
                        <span class="small" style="color: var(--muted); line-height: 1.5; display: block;">
                            Hasil perhitungan ini semata-mata estimasi umum dan bukan pembacaan elektrokardiogram (EKG). Jangan menyimpulkan diagnosis penyakit kardiovaskular dari satu kali pengukuran mandiri.
                        </span>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="d-flex flex-wrap gap-2 mt-4 pt-2">
                        <a href="jantung_form.php" class="btn btn-primary-health px-4">
                            Hitung Ulang
                        </a>
                        <a href="../mental/mental_form.php" class="btn btn-outline-secondary px-4">
                            Lanjut Skrining Mental
                        </a>
                        <a href="../index.php" class="btn btn-link ms-auto text-decoration-none" style="color: var(--muted);">Kembali ke Beranda</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card p-4 mb-4">
                <h6 class="fw-bold mb-3" style="color: var(--primary);">Tips Mengukur Denyut Nadi</h6>
                <p class="small mb-2" style="color: var(--muted); line-height: 1.55;">
                    Untuk hasil resting heart rate yang paling akurat, ukur denyut nadi segera setelah Anda bangun tidur di pagi hari saat masih berbaring rileks.
                </p>
                <p class="small mb-0" style="color: var(--muted); line-height: 1.55;">
                    Hindari mengukur denyut segera setelah minum kopi, merokok, atau setelah berolahraga karena detak jantung akan meningkat secara alami.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
