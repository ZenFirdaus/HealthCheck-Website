<?php
$pageTitle = 'Hasil Evaluasi BMR & Kebutuhan Kalori — HealthCheck';
$activePage = 'bmr';
require_once __DIR__ . '/includes/header.php';

$res = $_SESSION['bmr_result'] ?? null;
if (!$res) {
    if (isset($_GET['bmr'])) {
        $bmrVal = floatval($_GET['bmr']);
        $res = [
            'nama' => 'Pengguna',
            'jk' => '-',
            'umur' => '-',
            'berat' => '-',
            'tinggi' => '-',
            'bmr' => $bmrVal,
            'aktivitas' => 'Sedang',
            'tdee' => round($bmrVal * 1.55),
            'kalori_turun' => round($bmrVal * 1.55) - 400,
            'kalori_naik' => round($bmrVal * 1.55) + 400
        ];
    } else {
        header("Location: bmr_form.php");
        exit;
    }
}
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="bmr_form.php" class="text-decoration-none">Kalkulator BMR</a></li>
            <li class="breadcrumb-item active" aria-current="page">Hasil BMR & Kalori</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="p-4 bg-primary-subtle border-bottom d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-fire text-primary fs-4"></i>
                        <h5 class="fw-bold mb-0 text-dark">Estimasi Kebutuhan Kalori Harian</h5>
                    </div>
                    <span class="badge bg-white text-secondary border px-3 py-1 rounded-pill small">
                        <?= htmlspecialchars($res['nama']) ?>
                    </span>
                </div>

                <div class="p-4 p-md-5">
                    
                    <!-- Dua Kartu Metrik Utama: BMR & TDEE -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-4 rounded-4 bg-light text-center border h-100">
                                <span class="text-muted small text-uppercase fw-semibold">Laju Metabolisme Basal (BMR)</span>
                                <div class="display-5 fw-bold text-dark my-2">
                                    <?= number_format($res['bmr'], 0, ',', '.') ?> <span class="fs-6 fw-normal text-muted">kkal/hari</span>
                                </div>
                                <p class="text-secondary small mb-0">Energi minimal yang dibakar saat istirahat tanpa beraktivitas sama sekali.</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-4 rounded-4 text-center border h-100" style="background-color: #f0fdfa; border-color: #ccfbf1 !important;">
                                <span class="text-teal small text-uppercase fw-semibold" style="color: #0f766e;">Kebutuhan Harian (TDEE)</span>
                                <div class="display-5 fw-bold my-2" style="color: #0d9488;">
                                    <?= number_format($res['tdee'], 0, ',', '.') ?> <span class="fs-6 fw-normal text-muted">kkal/hari</span>
                                </div>
                                <p class="text-secondary small mb-0">Kalori untuk mempertahankan berat badan dengan aktivitas: <em><?= htmlspecialchars($res['aktivitas']) ?></em>.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Rekomendasi Sasaran Kalori -->
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-bullseye text-primary me-2"></i>Target Kalori Berdasarkan Sasaran Berat Badan:</h6>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card border rounded-3 p-3 text-center h-100 bg-white">
                                <span class="badge bg-warning-subtle text-warning-emphasis align-self-center mb-2">Turun Berat Badan</span>
                                <div class="fs-4 fw-bold text-dark my-1"><?= number_format($res['kalori_turun'], 0, ',', '.') ?></div>
                                <span class="text-muted small">kkal / hari</span>
                                <small class="text-secondary mt-2 border-top pt-2" style="font-size:0.75rem;">Defisit aman ~400-500 kkal untuk penurunan 0.3 - 0.5 kg/minggu.</small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card border rounded-3 p-3 text-center h-100 bg-white border-primary-subtle">
                                <span class="badge bg-success-subtle text-success-emphasis align-self-center mb-2">Pertahankan Berat Badan</span>
                                <div class="fs-4 fw-bold text-dark my-1"><?= number_format($res['tdee'], 0, ',', '.') ?></div>
                                <span class="text-muted small">kkal / hari</span>
                                <small class="text-secondary mt-2 border-top pt-2" style="font-size:0.75rem;">Konsumsi seimbang sesuai kalori yang dibakar setiap hari.</small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card border rounded-3 p-3 text-center h-100 bg-white">
                                <span class="badge bg-info-subtle text-info-emphasis align-self-center mb-2">Tambah Berat Badan</span>
                                <div class="fs-4 fw-bold text-dark my-1"><?= number_format($res['kalori_naik'], 0, ',', '.') ?></div>
                                <span class="text-muted small">kkal / hari</span>
                                <small class="text-secondary mt-2 border-top pt-2" style="font-size:0.75rem;">Surplus terukur ~400 kkal dibarengi latihan beban untuk massa otot.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Disclaimer Box -->
                    <div class="disclaimer-box m-0">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-exclamation-triangle-fill fs-5 mt-1 text-warning"></i>
                            <div>
                                <strong class="d-block text-dark">Catatan Penting:</strong>
                                Perhitungan ini adalah estimasi matematis menggunakan formula Mifflin-St Jeor. Faktor komposisi tubuh, genetika, status hormonal (tiroid), dan kondisi medis dapat mempengaruhi metabolisme riil. Konsultasikan dengan nutrisionis atau dokter gizi klinis untuk panduan pola makan personal.
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="d-flex flex-wrap gap-2 mt-4 pt-2">
                        <a href="bmr_form.php" class="btn btn-primary-health px-4">
                            <i class="bi bi-arrow-repeat me-1"></i> Hitung Ulang
                        </a>
                        <a href="jantung_form.php" class="btn btn-outline-secondary px-4">
                            Cek Detak Jantung & Kardio <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                        <a href="index.php" class="btn btn-link text-muted ms-auto">Kembali ke Beranda</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Related Tools -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <h6 class="fw-bold text-dark mb-3">Parameter yang Dihitung</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2 text-secondary mb-0">
                    <li class="d-flex justify-content-between"><span>Nama:</span> <strong class="text-dark"><?= htmlspecialchars($res['nama']) ?></strong></li>
                    <li class="d-flex justify-content-between"><span>Jenis Kelamin:</span> <strong class="text-dark"><?= htmlspecialchars($res['jk'] ?? '-') ?></strong></li>
                    <li class="d-flex justify-content-between"><span>Umur:</span> <strong class="text-dark"><?= htmlspecialchars($res['umur'] ?? '-') ?> tahun</strong></li>
                    <li class="d-flex justify-content-between"><span>Tingkat Aktivitas:</span> <strong class="text-dark"><?= htmlspecialchars($res['aktivitas'] ?? '-') ?></strong></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
