<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/aktivitas_helper.php';

$res = null;

if (isset($_GET['id']) && (int)$_GET['id'] > 0) {
    $record = assert_record_access($pdo, 'cek_aktivitas_fisik', 'id_aktivitas', (int)$_GET['id']);
    $calc = calculate_ipaq_activity(
        (int)$record['hari_berat'],
        (int)$record['menit_berat'],
        (int)$record['hari_sedang'],
        (int)$record['menit_sedang'],
        (int)$record['hari_jalan'],
        (int)$record['menit_jalan'],
        (float)$record['jam_duduk']
    );

    $res = [
        'id_aktivitas'       => (int)$record['id_aktivitas'],
        'user_id'            => $record['user_id'] ? (int)$record['user_id'] : null,
        'nama'               => $record['nama_user'],
        'total_met'          => (float)$record['total_met_menit'],
        'vigorous_met'       => $calc['vigorous_met'],
        'moderate_met'       => $calc['moderate_met'],
        'walking_met'        => $calc['walking_met'],
        'kategori'           => $record['kategori_aktivitas'],
        'badge'              => $calc['badge'],
        'warna'              => $calc['color'],
        'saran'              => $record['saran'] ?? $calc['advice'],
        'sitting_hours'      => (float)$record['jam_duduk'],
        'sitting_warning'    => $calc['sitting_warning']
    ];
} else {
    $res = $_SESSION['activity_result'] ?? null;
    if ($res) {
        if (isset($res['user_id']) && $res['user_id'] !== null && !can_access_user_data((int)$res['user_id'])) {
            http_response_code(403);
            die("403 Forbidden: Anda tidak memiliki hak akses terhadap hasil pemeriksaan ini.");
        }
    } else {
        header("Location: aktivitas_fisik_form.php");
        exit;
    }
}

$pageTitle = 'Hasil Evaluasi Aktivitas Fisik (IPAQ) — HealthCheck';
$activePage = 'aktivitas_fisik';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="aktivitas_fisik_form.php" class="text-decoration-none">Aktivitas Fisik</a></li>
            <li class="breadcrumb-item active" aria-current="page">Hasil Evaluasi IPAQ</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card overflow-hidden mb-4">
                <div class="card-editorial-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0" style="color: var(--primary);">Hasil Evaluasi Tingkat Aktivitas Fisik</h5>
                    <span class="badge border px-3 py-1 small" style="background-color: var(--light-bg); color: var(--primary); border-color: var(--card-border) !important;">
                        <?= htmlspecialchars($res['nama']) ?>
                    </span>
                </div>

                <div class="p-4 p-md-5">
                    <!-- Skor Utama MET-menit -->
                    <div class="metric-hero-box">
                        <span class="text-uppercase fw-semibold small text-muted" style="letter-spacing: 0.05em;">Total Pengeluaran Energi Mingguan</span>
                        <div class="metric-number my-1 text-<?= $res['warna'] ?>">
                            <?= number_format($res['total_met'], 0, ',', '.') ?> <span class="fs-4 fw-normal text-muted">MET-menit/mgg</span>
                        </div>
                        <div class="h6 fw-semibold mb-0">
                            Klasifikasi IPAQ: <span class="badge bg-<?= $res['warna'] ?>-subtle px-3 py-1"><?= htmlspecialchars($res['kategori']) ?></span>
                        </div>
                    </div>

                    <!-- Rincian Kontribusi per Jenis Aktivitas -->
                    <div class="row g-3 mb-4 text-center">
                        <div class="col-4">
                            <div class="p-3 rounded-2" style="background-color: var(--light-bg); border: 1px solid var(--border);">
                                <span class="badge bg-danger-subtle mb-1">Aktivitas Berat</span>
                                <div class="fs-5 fw-bold text-dark mt-1"><?= number_format($res['vigorous_met'], 0, ',', '.') ?></div>
                                <small class="text-muted" style="font-size:0.75rem;">MET-menit</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 rounded-2" style="background-color: var(--light-bg); border: 1px solid var(--border);">
                                <span class="badge bg-primary-subtle text-primary mb-1">Aktivitas Sedang</span>
                                <div class="fs-5 fw-bold text-dark mt-1"><?= number_format($res['moderate_met'], 0, ',', '.') ?></div>
                                <small class="text-muted" style="font-size:0.75rem;">MET-menit</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-3 rounded-2" style="background-color: var(--light-bg); border: 1px solid var(--border);">
                                <span class="badge bg-success-subtle mb-1">Berjalan Kaki</span>
                                <div class="fs-5 fw-bold text-dark mt-1"><?= number_format($res['walking_met'], 0, ',', '.') ?></div>
                                <small class="text-muted" style="font-size:0.75rem;">MET-menit</small>
                            </div>
                        </div>
                    </div>

                    <!-- Evaluasi Waktu Duduk Harian -->
                    <div class="p-3 mb-4 rounded-3 border" style="background-color: var(--surface); border-color: var(--border) !important;">
                        <div class="d-flex justify-content-between align-items-center small">
                            <span class="text-muted"><i class="bi bi-clock-history text-secondary me-1"></i> Rata-rata Waktu Duduk Harian:</span>
                            <strong class="text-dark fs-6"><?= $res['sitting_hours'] ?> Jam / Hari</strong>
                        </div>
                    </div>

                    <!-- Saran & Rekomendasi Klinis -->
                    <div class="p-4 mb-4 rounded-3" style="background-color: var(--surface); border: 1px solid var(--border);">
                        <h6 class="fw-bold mb-2" style="color: var(--primary);">Interpretasi & Rekomendasi WHO</h6>
                        <p class="small text-muted mb-0" style="line-height: 1.6;">
                            <?= htmlspecialchars($res['saran']) ?>
                        </p>
                    </div>

                    <!-- Disclaimer Box -->
                    <div class="disclaimer-box m-0">
                        <strong class="d-block" style="color: var(--primary);">Batas Penggunaan Medis</strong>
                        <span class="small text-muted" style="line-height: 1.5; display: block;">
                            Evaluasi ini menerapkan instrumen penapisan resmi International Physical Activity Questionnaire (IPAQ Short Form 7-day recall) dan bersifat indikator skrining mandiri. Lakukan peningkatan intensitas olahraga secara bertahap dan konsultasikan dengan dokter jika memiliki keluhan sendi atau kardiovaskular.
                        </span>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="d-flex flex-wrap gap-2 mt-4 pt-2">
                        <a href="aktivitas_fisik_form.php" class="btn btn-primary-health px-4">
                            Hitung Ulang
                        </a>
                        <a href="../bmr/bmr_form.php" class="btn btn-emerald px-4">
                            Cek Kebutuhan Kalori (TDEE)
                        </a>
                        <a href="../index.php" class="btn btn-link ms-auto text-decoration-none" style="color: var(--muted);">Kembali ke Beranda</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Related Tools -->
        <div class="col-lg-4">
            <div class="card p-4 mb-4">
                <h6 class="fw-bold mb-3" style="color: var(--primary);">Klasifikasi Standar IPAQ</h6>
                <div class="table-responsive">
                    <table class="table table-sm small align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th class="text-end">Ambang Batas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge bg-warning-subtle">Rendah</span></td>
                                <td class="text-end">&lt; 600 MET-min</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-info-subtle">Sedang</span></td>
                                <td class="text-end">600 – 2.999 MET-min</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-success-subtle">Tinggi</span></td>
                                <td class="text-end">&ge; 3.000 MET-min</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card p-4">
                <h6 class="fw-bold mb-2" style="color: var(--primary);">Definisi Satuan MET</h6>
                <p class="small text-muted mb-0" style="line-height: 1.55;">
                    1 MET setara dengan laju metabolisme energi saat duduk tenang (kira-kira 1 kkal/kg/jam atau 3.5 ml O₂/kg/menit). Berjalan setara 3.3 MET, olahraga sedang setara 4.0 MET, dan olahraga berat setara 8.0 MET.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
