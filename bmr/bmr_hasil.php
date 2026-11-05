<?php
$pageTitle = 'Hasil Evaluasi BMR & Kebutuhan Kalori — HealthCheck';
$activePage = 'bmr';
require_once __DIR__ . '/../includes/header.php';

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
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="bmr_form.php" class="text-decoration-none">Kalkulator BMR</a></li>
            <li class="breadcrumb-item active" aria-current="page">Hasil BMR & Kalori</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card overflow-hidden mb-4">
                <div class="card-editorial-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0" style="color: var(--primary);">Estimasi Kebutuhan Kalori Harian</h5>
                    <span class="badge border px-3 py-1 small" style="background-color: var(--light-bg); color: var(--primary); border-color: var(--card-border) !important;">
                        <?= htmlspecialchars($res['nama']) ?>
                    </span>
                </div>

                <div class="p-4 p-md-5">
                    
                    <!-- Dua Kartu Metrik Utama: BMR & TDEE -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-4 rounded-3 text-center border h-100" style="background-color: var(--light-bg); border-color: var(--card-border) !important;">
                                <span class="text-uppercase fw-semibold small" style="color: var(--muted); letter-spacing: 0.05em;">Laju Metabolisme Basal (BMR)</span>
                                <div class="metric-number my-2" style="font-size: 2.2rem; color: var(--primary);">
                                    <?= number_format($res['bmr'], 0, ',', '.') ?> <span class="fs-6 fw-normal" style="color: var(--muted);">kkal/hari</span>
                                </div>
                                <p class="small mb-0" style="color: var(--muted); line-height: 1.5;">Energi dasar yang dibakar organ vital saat istirahat tanpa aktivitas.</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-4 rounded-3 text-center border h-100" style="background-color: var(--primary-light); border-color: rgba(23, 44, 70, 0.1) !important;">
                                <span class="text-uppercase fw-semibold small" style="color: var(--secondary); letter-spacing: 0.05em;">Kebutuhan Harian (TDEE)</span>
                                <div class="metric-number my-2" style="font-size: 2.2rem; color: var(--primary);">
                                    <?= number_format($res['tdee'], 0, ',', '.') ?> <span class="fs-6 fw-normal" style="color: var(--muted);">kkal/hari</span>
                                </div>
                                <p class="small mb-0" style="color: var(--muted); line-height: 1.5;">Target pemeliharaan berat badan (Aktivitas: <em><?= htmlspecialchars($res['aktivitas']) ?></em>).</p>
                            </div>
                        </div>
                    </div>

                    <!-- Rekomendasi Sasaran Kalori -->
                    <h6 class="fw-bold mb-3" style="color: var(--primary);">Target Kalori Berdasarkan Sasaran Berat Badan</h6>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card p-3 text-center h-100">
                                <span class="badge bg-warning-subtle align-self-center mb-2">Defisit (Turun BB)</span>
                                <div class="fs-4 fw-bold my-1" style="color: var(--primary);"><?= number_format($res['kalori_turun'], 0, ',', '.') ?></div>
                                <span class="small" style="color: var(--muted);">kkal / hari</span>
                                <small class="mt-2 border-top pt-2" style="font-size:0.75rem; color: var(--muted); border-color: var(--card-border) !important;">Defisit terkontrol ~400 kkal untuk penurunan bertahap 0.3 - 0.5 kg/minggu.</small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card p-3 text-center h-100" style="border-color: var(--secondary) !important;">
                                <span class="badge bg-success-subtle align-self-center mb-2">Pemeliharaan (Ideal)</span>
                                <div class="fs-4 fw-bold my-1" style="color: var(--primary);"><?= number_format($res['tdee'], 0, ',', '.') ?></div>
                                <span class="small" style="color: var(--muted);">kkal / hari</span>
                                <small class="mt-2 border-top pt-2" style="font-size:0.75rem; color: var(--muted); border-color: var(--card-border) !important;">Konsumsi seimbang sebanding kalori yang dikeluarkan setiap hari.</small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card p-3 text-center h-100">
                                <span class="badge bg-info-subtle align-self-center mb-2">Surplus (Naik BB)</span>
                                <div class="fs-4 fw-bold my-1" style="color: var(--primary);"><?= number_format($res['kalori_naik'], 0, ',', '.') ?></div>
                                <span class="small" style="color: var(--muted);">kkal / hari</span>
                                <small class="mt-2 border-top pt-2" style="font-size:0.75rem; color: var(--muted); border-color: var(--card-border) !important;">Surplus terukur ~400 kkal diimbangi latihan kekuatan untuk peningkatan massa otot.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Disclaimer Box -->
                    <div class="disclaimer-box m-0">
                        <strong class="d-block" style="color: var(--primary);">Panduan Gizi</strong>
                        <span class="small" style="color: var(--muted); line-height: 1.5; display: block;">
                            Estimasi kalori ini dihitung dengan formula baku Mifflin-St Jeor. Faktor variasi metabolisme, massa otot riil, dan kondisi tiroid dapat mempengaruhi kebutuhan harian Anda.
                        </span>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="d-flex flex-wrap gap-2 mt-4 pt-2">
                        <a href="bmr_form.php" class="btn btn-primary-health px-4">
                            Hitung Ulang
                        </a>
                        <a href="../jantung/jantung_form.php" class="btn btn-outline-secondary px-4">
                            Cek Detak Jantung & Kardio
                        </a>
                        <a href="../index.php" class="btn btn-link ms-auto text-decoration-none" style="color: var(--muted);">Kembali ke Beranda</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Related Tools -->
        <div class="col-lg-4">
            <div class="card p-4 mb-4">
                <h6 class="fw-bold mb-3" style="color: var(--primary);">Parameter yang Dihitung</h6>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
