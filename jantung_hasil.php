<?php
$pageTitle = 'Hasil Analisis Detak Jantung & Zona Kardio — HealthCheck';
$activePage = 'jantung';
require_once __DIR__ . '/includes/header.php';

$res = $_SESSION['jantung_result'] ?? null;
if (!$res) {
    header("Location: jantung_form.php");
    exit;
}
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="jantung_form.php" class="text-decoration-none">Kalkulator Detak Jantung</a></li>
            <li class="breadcrumb-item active" aria-current="page">Hasil Detak Jantung</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="p-4 bg-danger-subtle border-bottom d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-heart-pulse text-danger fs-4"></i>
                        <h5 class="fw-bold mb-0 text-dark">Hasil Evaluasi Detak Jantung</h5>
                    </div>
                    <span class="badge bg-white text-secondary border px-3 py-1 rounded-pill small">
                        <?= htmlspecialchars($res['nama']) ?>
                    </span>
                </div>

                <div class="p-4 p-md-5">
                    
                    <!-- Dua Metrik Utama -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-4 rounded-4 bg-light text-center border h-100">
                                <span class="text-muted small text-uppercase fw-semibold">Detak Nadi Istirahat (RHR)</span>
                                <div class="display-5 fw-bold text-<?= $res['warna'] ?> my-2">
                                    <?= $res['rhr'] ?> <span class="fs-6 fw-normal text-muted">BPM</span>
                                </div>
                                <span class="badge bg-<?= $res['warna'] ?>-subtle text-<?= $res['warna'] ?>-emphasis border border-<?= $res['warna'] ?>-subtle px-3 py-1 rounded-pill">
                                    <?= $res['kategoriRhr'] ?>
                                </span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-4 rounded-4 bg-light text-center border h-100">
                                <span class="text-muted small text-uppercase fw-semibold">Detak Jantung Maksimal (HR Max)</span>
                                <div class="display-5 fw-bold text-dark my-2">
                                    <?= $res['maxHr'] ?> <span class="fs-6 fw-normal text-muted">BPM</span>
                                </div>
                                <small class="text-muted">Estimasi batas tertinggi denyut saat beban maksimal (Usia <?= $res['umur'] ?> thn).</small>
                            </div>
                        </div>
                    </div>

                    <!-- Keterangan Status Nadi -->
                    <div class="p-3 bg-white border rounded-3 mb-4">
                        <h6 class="fw-bold text-dark mb-1"><i class="bi bi-info-circle text-primary me-2"></i>Evaluasi Kondisi Nadi Istirahat:</h6>
                        <p class="text-secondary small mb-0"><?= htmlspecialchars($res['penjelasanRhr']) ?></p>
                    </div>

                    <!-- Tabel Zona Target Heart Rate (Karvonen) -->
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-speedometer2 text-danger me-2"></i>Zona Latihan Kardio Optimal:</h6>
                    
                    <div class="d-flex flex-column gap-3 mb-4">
                        <?php foreach ($res['zones'] as $z): ?>
                            <div class="p-3 rounded-3 border bg-white d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                                <div>
                                    <div class="fw-bold text-dark small mb-1"><?= $z['nama'] ?></div>
                                    <div class="text-secondary small"><?= $z['tujuan'] ?></div>
                                </div>
                                <div class="text-md-end text-nowrap">
                                    <span class="badge bg-<?= $z['warna'] ?>-subtle text-<?= $z['warna'] ?>-emphasis border fs-6 px-3 py-2 rounded-3">
                                        <?= $z['min'] ?> – <?= $z['max'] ?> BPM
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Kapan Perlu Bantuan Medis (PRD 3.5.C) -->
                    <div class="alert alert-warning border-warning-subtle small mb-4">
                        <h6 class="fw-bold mb-1 text-warning-emphasis"><i class="bi bi-shield-exclamation me-1"></i> Kapan Harus Berkonsultasi ke Dokter?</h6>
                        <ul class="mb-0 ps-3 text-secondary">
                            <li>Jika denyut jantung istirahat rutin berada di atas 100 BPM tanpa sebab yang jelas.</li>
                            <li>Mengalami denyut yang terasa meloncat-loncat, tidak berirama (aritmia), atau berdebar kencang saat istirahat.</li>
                            <li>Disertai gejala nyeri dada sebelah kiri/tengah, sesak napas saat aktivitas ringan, pusing berkunang-kunang, atau pingsan.</li>
                        </ul>
                    </div>

                    <!-- Disclaimer Box -->
                    <div class="disclaimer-box m-0">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-exclamation-triangle-fill fs-5 mt-1 text-warning"></i>
                            <div>
                                <strong class="d-block text-dark">Disclaimer Medis:</strong>
                                Hasil perhitungan ini semata-mata estimasi umum dan bukan pembacaan elektrokardiogram (EKG). Jangan menyimpulkan diagnosis penyakit jantung dari satu kali pengukuran mandiri.
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="d-flex flex-wrap gap-2 mt-4 pt-2">
                        <a href="jantung_form.php" class="btn btn-primary-health px-4">
                            <i class="bi bi-arrow-repeat me-1"></i> Hitung Ulang
                        </a>
                        <a href="mental_form.php" class="btn btn-outline-secondary px-4">
                            Lanjut Screening Mental <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                        <a href="index.php" class="btn btn-link text-muted ms-auto">Kembali ke Beranda</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <h6 class="fw-bold text-dark mb-3">Tips Mengukur Denyut Nadi</h6>
                <p class="text-secondary small mb-2">
                    Untuk hasil resting heart rate yang paling akurat, ukur denyut nadi segera setelah Anda bangun tidur di pagi hari saat masih berbaring rileks.
                </p>
                <p class="text-secondary small mb-0">
                    Hindari mengukur denyut segera setelah minum kopi, merokok, atau setelah berolahraga karena detak jantung akan meningkat secara alami.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
