<?php
$pageTitle = 'Hasil Screening Kesehatan Mental (PHQ-4) — HealthCheck';
$activePage = 'mental';
require_once __DIR__ . '/includes/header.php';

$res = $_SESSION['mental_result'] ?? null;
if (!$res) {
    header("Location: mental_form.php");
    exit;
}
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="mental_form.php" class="text-decoration-none">Screening Mental</a></li>
            <li class="breadcrumb-item active" aria-current="page">Hasil Screening</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="p-4 bg-purple-subtle border-bottom d-flex justify-content-between align-items-center" style="background-color: #f5f3ff;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-emoji-smile text-purple fs-4"></i>
                        <h5 class="fw-bold mb-0 text-dark">Hasil Screening Suasana Hati & Kecemasan</h5>
                    </div>
                    <span class="badge bg-white text-secondary border px-3 py-1 rounded-pill small">
                        <?= htmlspecialchars($res['nama']) ?>
                    </span>
                </div>

                <div class="p-4 p-md-5">
                    
                    <!-- Skor Total -->
                    <div class="text-center mb-4">
                        <span class="text-muted small text-uppercase fw-semibold">Total Skor PHQ-4</span>
                        <div class="display-3 fw-bold text-<?= $res['warna'] ?> my-1">
                            <?= $res['totalSkor'] ?> <span class="fs-5 text-muted fw-normal">/ 12</span>
                        </div>
                        <div class="h5 fw-semibold mb-3">
                            Kategori: <span class="badge bg-<?= $res['warna'] ?>-subtle text-<?= $res['warna'] ?>-emphasis border border-<?= $res['warna'] ?>-subtle px-3 py-2 rounded-pill fs-6"><?= $res['tingkat'] ?></span>
                        </div>
                    </div>

                    <!-- Breakdown Subskor -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border text-center">
                                <div class="text-muted small fw-semibold">Subskor Kecemasan (GAD-2)</div>
                                <div class="fs-4 fw-bold text-dark my-1"><?= $res['skorCemas'] ?> / 6</div>
                                <small class="text-secondary"><?= ($res['skorCemas'] >= 3) ? 'Ada indikasi kecemasan yang perlu diperhatikan' : 'Kecemasan dalam batas wajar' ?></small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border text-center">
                                <div class="text-muted small fw-semibold">Subskor Depresi/Mood (PHQ-2)</div>
                                <div class="fs-4 fw-bold text-dark my-1"><?= $res['skorDepresi'] ?> / 6</div>
                                <small class="text-secondary"><?= ($res['skorDepresi'] >= 3) ? 'Ada penurunan minat atau suasana hati murung' : 'Suasana hati relatif stabil' ?></small>
                            </div>
                        </div>
                    </div>

                    <!-- Anjuran & Narasi Ramah -->
                    <div class="card bg-white border rounded-3 p-4 mb-4">
                        <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-heart-pulse-fill text-danger me-2"></i>Refleksi & Langkah Perhatian Diri:</h6>
                        <p class="text-secondary small mb-3"><?= htmlspecialchars($res['anjuran']) ?></p>
                        
                        <div class="p-3 bg-light rounded-2 border-start border-3 border-primary small text-secondary">
                            <strong>Tips Merawat Diri (Self-Care):</strong>
                            <ul class="mb-0 mt-1 ps-3">
                                <li>Tidur teratur 7-8 jam per malam dan kurangi paparan gawai sebelum tidur.</li>
                                <li>Latih pernapasan lambat (metode 4-7-8) ketika mulai merasa cemas atau tegang.</li>
                                <li>Jangan memendam beban sendirian, hubungi teman, keluarga, atau tenaga ahli terpercaya.</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Anjuran Bantuan Khusus jika Sedang/Berat (PRD 3.5.D) -->
                    <?php if ($res['totalSkor'] >= 6): ?>
                        <div class="alert alert-danger border-danger-subtle small mb-4">
                            <h6 class="fw-bold mb-1 text-danger"><i class="bi bi-telephone-inbound-fill me-2"></i>Dukungan Layanan Profesional</h6>
                            <p class="mb-2">Jika perasaan cemas atau sedih ini terus bertahan dan mengganggu kuliah, pekerjaan, atau hubungan sehari-hari, berkonsultasi dengan profesional kesehatan jiwa adalah langkah yang bijak dan berani.</p>
                            <div class="fw-semibold text-dark">
                                Layanan Konseling & Krisis:
                                <ul class="mb-0 mt-1">
                                    <li>Layanan Sejiwa Kemenkes: Hubungi <strong>119 ext. 8</strong></li>
                                    <li>Puskesmas terdekat (tersedia layanan psikolog klinis / dokter umum)</li>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Disclaimer Medis (PRD 3.5.D) -->
                    <div class="disclaimer-box m-0">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-exclamation-triangle-fill fs-5 mt-1 text-warning"></i>
                            <div>
                                <strong class="d-block text-dark">Disclaimer Penting:</strong>
                                Hasil kuesioner PHQ-4 ini murni sebagai penapisan awal (screening) dan refleksi pribadi, <strong>bukan vonis diagnosis klinis depresi atau gangguan kecemasan</strong>. Diagnosis medis hanya dapat ditegakkan melalui pemeriksaan langsung oleh psikolog klinis atau psikiater.
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="d-flex flex-wrap gap-2 mt-4 pt-2">
                        <a href="mental_form.php" class="btn btn-primary-health px-4">
                            <i class="bi bi-arrow-repeat me-1"></i> Screening Ulang
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary px-4">Kembali ke Beranda</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <h6 class="fw-bold text-dark mb-3">Skala Acuan PHQ-4</h6>
                <div class="table-responsive">
                    <table class="table table-sm small align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Total Skor</th>
                                <th class="text-end">Tingkat Distress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>0 – 2</td>
                                <td class="text-end"><span class="badge bg-success-subtle text-success">Normal</span></td>
                            </tr>
                            <tr>
                                <td>3 – 5</td>
                                <td class="text-end"><span class="badge bg-info-subtle text-info">Ringan</span></td>
                            </tr>
                            <tr>
                                <td>6 – 8</td>
                                <td class="text-end"><span class="badge bg-warning-subtle text-warning-emphasis">Sedang</span></td>
                            </tr>
                            <tr>
                                <td>9 – 12</td>
                                <td class="text-end"><span class="badge bg-danger-subtle text-danger">Berat</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
