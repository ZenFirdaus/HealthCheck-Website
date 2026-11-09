<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_helper.php';

$res = null;

if (isset($_GET['id']) && (int)$_GET['id'] > 0) {
    $record = assert_record_access($pdo, 'cek_mental', 'id_mental', (int)$_GET['id']);
    $total = (int)$record['total_skor'];
    $warna = ($total <= 2) ? 'success' : (($total <= 5) ? 'info' : (($total <= 8) ? 'warning' : 'danger'));

    $res = [
        'id_mental' => (int)$record['id_mental'],
        'user_id' => $record['user_id'] ? (int)$record['user_id'] : null,
        'nama' => $record['nama_user'],
        'skorCemas' => (int)$record['skor_cemas'],
        'skorDepresi' => (int)$record['skor_depresi'],
        'totalSkor' => $total,
        'tingkat' => $record['tingkat'],
        'warna' => $warna,
        'anjuran' => $record['anjuran']
    ];
} else {
    $res = $_SESSION['mental_result'] ?? null;
    if ($res) {
        if (isset($res['user_id']) && $res['user_id'] !== null && !can_access_user_data((int)$res['user_id'])) {
            http_response_code(403);
            die("403 Forbidden: Anda tidak memiliki hak akses terhadap hasil pemeriksaan ini.");
        }
    } else {
        header("Location: mental_form.php");
        exit;
    }
}

$pageTitle = 'Hasil Skrining Kesehatan Mental (PHQ-4) — HealthCheck';
$activePage = 'mental';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="mental_form.php" class="text-decoration-none">Skrining Mental</a></li>
            <li class="breadcrumb-item active" aria-current="page">Hasil Skrining</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card overflow-hidden mb-4">
                <div class="card-editorial-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0" style="color: var(--primary);">Hasil Skrining Suasana Hati & Kecemasan</h5>
                    <span class="badge border px-3 py-1 small" style="background-color: var(--light-bg); color: var(--primary); border-color: var(--card-border) !important;">
                        <?= htmlspecialchars($res['nama']) ?>
                    </span>
                </div>

                <div class="p-4 p-md-5">
                    
                    <!-- Skor Total -->
                    <div class="metric-hero-box">
                        <span class="text-uppercase fw-semibold small" style="color: var(--muted); letter-spacing: 0.05em;">Total Skor PHQ-4</span>
                        <div class="metric-number my-1 text-<?= $res['warna'] ?>">
                            <?= $res['totalSkor'] ?> <span class="fs-5 fw-normal" style="color: var(--muted);">/ 12</span>
                        </div>
                        <div class="h6 fw-semibold mb-0">
                            Interpretasi: <span class="badge bg-<?= $res['warna'] ?>-subtle px-3 py-1"><?= $res['tingkat'] ?></span>
                        </div>
                    </div>

                    <!-- Breakdown Subskor -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border text-center" style="background-color: var(--light-bg); border-color: var(--card-border) !important;">
                                <div class="small fw-semibold" style="color: var(--muted);">Subskor Kecemasan (GAD-2)</div>
                                <div class="fs-4 fw-bold my-1" style="color: var(--primary);"><?= $res['skorCemas'] ?> / 6</div>
                                <small style="color: var(--muted);"><?= ($res['skorCemas'] >= 3) ? 'Ada indikasi rasa cemas yang perlu diperhatikan' : 'Kecemasan dalam ambang batas wajar' ?></small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border text-center" style="background-color: var(--light-bg); border-color: var(--card-border) !important;">
                                <div class="small fw-semibold" style="color: var(--muted);">Subskor Depresi/Mood (PHQ-2)</div>
                                <div class="fs-4 fw-bold my-1" style="color: var(--primary);"><?= $res['skorDepresi'] ?> / 6</div>
                                <small style="color: var(--muted);"><?= ($res['skorDepresi'] >= 3) ? 'Ada indikasi penurunan suasana hati atau minat' : 'Suasana hati relatif stabil' ?></small>
                            </div>
                        </div>
                    </div>

                    <!-- Anjuran & Narasi Ramah -->
                    <div class="p-4 mb-4 rounded-3 border" style="background-color: var(--surface); border-color: var(--card-border) !important;">
                        <h6 class="fw-bold mb-2" style="color: var(--primary);">Refleksi & Langkah Perhatian Diri</h6>
                        <p class="small mb-3" style="color: var(--muted); line-height: 1.6;"><?= htmlspecialchars($res['anjuran']) ?></p>
                        
                        <div class="p-3 rounded-2 small" style="background-color: var(--light-bg); border-left: 3px solid var(--secondary); color: var(--dark);">
                            <strong style="color: var(--primary);">Tips Merawat Diri (Self-Care):</strong>
                            <ul class="mb-0 mt-1 ps-3" style="color: var(--muted); line-height: 1.55;">
                                <li>Tidur teratur 7-8 jam per malam dan kurangi paparan gawai sebelum beristirahat.</li>
                                <li>Latih pernapasan perlahan (metode 4-7-8) saat rasa tegang atau cemas mulai muncul.</li>
                                <li>Jangan memendam beban emosional sendirian; ceritakan pada rekan atau profesional tepercaya.</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Anjuran Bantuan Khusus jika Sedang/Berat -->
                    <?php if ($res['totalSkor'] >= 6): ?>
                        <div class="alert alert-danger small mb-4">
                            <h6 class="fw-bold mb-1" style="color: var(--danger);">Dukungan Layanan Profesional</h6>
                            <p class="mb-2" style="color: var(--dark);">Jika perasaan cemas atau sedih ini terus berlangsung dan mempengaruhi aktivitas harian, menghubungi konselor atau dokter umum adalah langkah yang bijak.</p>
                            <div class="fw-semibold" style="color: var(--dark);">
                                Akses Bantuan Terdekat:
                                <ul class="mb-0 mt-1 fw-normal" style="color: var(--muted);">
                                    <li>Layanan Sejiwa Kemenkes: Hubungi <strong>119 (ext. 8)</strong></li>
                                    <li>Puskesmas atau klinik terdekat (layanan dokter umum / psikolog klinis)</li>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Disclaimer Medis -->
                    <div class="disclaimer-box m-0">
                        <strong class="d-block" style="color: var(--primary);">Disclaimer Penting</strong>
                        <span class="small" style="color: var(--muted); line-height: 1.5; display: block;">
                            Hasil kuesioner PHQ-4 ini adalah penapisan awal reflektif, <strong>bukan vonis diagnosis klinis kejiwaan</strong>. Diagnosis formal hanya dapat diberikan melalui konsultasi langsung bersama psikolog atau psikiater.
                        </span>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="d-flex flex-wrap gap-2 mt-4 pt-2">
                        <a href="mental_form.php" class="btn btn-primary-health px-4">
                            Skrining Ulang
                        </a>
                        <a href="../index.php" class="btn btn-blue px-4">Kembali ke Beranda</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card p-4 mb-4">
                <h6 class="fw-bold mb-3" style="color: var(--primary);">Skala Acuan PHQ-4</h6>
                <div class="table-responsive">
                    <table class="table table-sm small align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Total Skor</th>
                                <th class="text-end">Tingkat Distress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>0 – 2</td>
                                <td class="text-end"><span class="badge bg-success-subtle">Normal</span></td>
                            </tr>
                            <tr>
                                <td>3 – 5</td>
                                <td class="text-end"><span class="badge bg-info-subtle">Ringan</span></td>
                            </tr>
                            <tr>
                                <td>6 – 8</td>
                                <td class="text-end"><span class="badge bg-warning-subtle">Sedang</span></td>
                            </tr>
                            <tr>
                                <td>9 – 12</td>
                                <td class="text-end"><span class="badge bg-danger-subtle">Berat</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
