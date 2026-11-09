<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/zona_jantung_helper.php';

$res = null;

if (isset($_GET['id']) && (int)$_GET['id'] > 0) {
    $record = assert_record_access($pdo, 'cek_zona_detak_jantung', 'id_zona', (int)$_GET['id']);
    $umur = (int)$record['umur'];
    $rhr = (int)$record['resting_hr'];
    $metode = $record['metode'] ?? 'karvonen';

    if ($metode === 'zoladz') {
        $calc = calculate_zoladz_zones($umur);
    } else {
        $calc = calculate_karvonen_zones($umur, $rhr);
    }

    $res = [
        'id_zona'    => (int)$record['id_zona'],
        'user_id'    => $record['user_id'] ? (int)$record['user_id'] : null,
        'nama'       => $record['nama_user'],
        'umur'       => $umur,
        'resting_hr' => $rhr,
        'max_hr'     => (int)$record['max_hr'],
        'hrr'        => (int)$record['hrr'],
        'metode'     => $metode,
        'zones'      => $calc['zones'],
        'saran'      => $record['saran'] ?? ''
    ];
} else {
    $res = $_SESSION['zona_hr_result'] ?? null;
    if ($res) {
        if (isset($res['user_id']) && $res['user_id'] !== null && !can_access_user_data((int)$res['user_id'])) {
            http_response_code(403);
            die("403 Forbidden: Anda tidak memiliki hak akses terhadap hasil pemeriksaan ini.");
        }
    } else {
        header("Location: zona_jantung_form.php");
        exit;
    }
}

$pageTitle = 'Hasil Zona Latihan Detak Jantung — HealthCheck';
$activePage = 'zona_jantung';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="zona_jantung_form.php" class="text-decoration-none">Zona Kardio</a></li>
            <li class="breadcrumb-item active" aria-current="page">Hasil 5 Zona Latihan</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card overflow-hidden mb-4">
                <div class="card-editorial-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0" style="color: var(--primary);">5 Zona Target Detak Jantung Latihan</h5>
                    <span class="badge border px-3 py-1 small" style="background-color: var(--light-bg); color: var(--primary); border-color: var(--card-border) !important;">
                        <?= htmlspecialchars($res['nama']) ?>
                    </span>
                </div>

                <div class="p-4 p-md-5">
                    <!-- Skor Ringkasan -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-4 rounded-3 text-center border h-100" style="background-color: var(--light-bg); border-color: var(--border) !important;">
                                <span class="text-uppercase fw-semibold small text-muted" style="letter-spacing: 0.05em;">Detak Maksimal (HR Max)</span>
                                <div class="metric-number my-2" style="font-size: 2.2rem; color: var(--primary);">
                                    <?= $res['max_hr'] ?> <span class="fs-6 fw-normal text-muted">BPM</span>
                                </div>
                                <p class="small mb-0 text-muted">Estimasi batas denyut tertinggi saat beban fisik puncak (Formula Tanaka).</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-4 rounded-3 text-center border h-100" style="background-color: var(--primary-light); border-color: rgba(25, 54, 91, 0.1) !important;">
                                <span class="text-uppercase fw-semibold small text-muted" style="letter-spacing: 0.05em;">Heart Rate Reserve (HRR)</span>
                                <div class="metric-number my-2" style="font-size: 2.2rem; color: var(--blue);">
                                    <?= $res['hrr'] ?> <span class="fs-6 fw-normal text-muted">BPM</span>
                                </div>
                                <p class="small mb-0 text-muted">Cadangan kapasitas denyut (HR Max - Resting HR: <?= $res['resting_hr'] ?> BPM).</p>
                            </div>
                        </div>
                    </div>

                    <!-- 5 Zona Intensitas Kardio -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0" style="color: var(--primary);">Rincian Zona Intensitas Latihan (Metode <?= ucfirst($res['metode']) ?>)</h6>
                        <span class="badge bg-secondary-subtle text-dark small">5 Tingkatan</span>
                    </div>

                    <div class="d-flex flex-column gap-3 mb-4">
                        <?php foreach ($res['zones'] as $zoneNum => $z): ?>
                            <div class="p-3 rounded-3 border d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2" style="background-color: var(--surface); border-color: var(--border) !important;">
                                <div>
                                    <div class="fw-bold small mb-1" style="color: var(--primary);"><?= $z['name'] ?></div>
                                    <div class="small text-muted mb-1"><?= $z['benefit'] ?></div>
                                    <span class="badge bg-<?= $z['color'] ?>-subtle text-dark" style="font-size: 0.72rem;"><?= $z['intensity'] ?></span>
                                </div>
                                <div class="text-md-end text-nowrap">
                                    <span class="badge bg-<?= $z['color'] ?>-subtle fs-6 px-3 py-2 rounded-2">
                                        <?= $z['min'] ?> – <?= $z['max'] ?> BPM
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Panduan Penggunaan Zona -->
                    <div class="p-4 mb-4 rounded-3" style="background-color: var(--surface); border: 1px solid var(--border);">
                        <h6 class="fw-bold mb-2" style="color: var(--primary);">Strategi Pelatihan Berbasis Zona</h6>
                        <p class="small text-muted mb-0" style="line-height: 1.6;">
                            Untuk membakar lemak secara optimal dan membangun stamina dasar, lakukan latihan pada <strong>Zona 2</strong> selama 45–60 menit (misal: jogging santai atau bersepeda statis). Batasi latihan intensitas tinggi (Zona 4 & 5) maksimal 1–2 kali per minggu untuk memberi waktu pemulihan pada serat otot dan sistem saraf.
                        </p>
                    </div>

                    <!-- Peringatan Medis -->
                    <div class="alert alert-warning small mb-4">
                        <h6 class="fw-bold mb-1" style="color: var(--primary);">Perhatian Keamanan Olahraga</h6>
                        <p class="mb-0 text-dark" style="line-height: 1.55;">
                            Jika Anda baru memulai kembali program olahraga setelah lama tidak aktif, memiliki riwayat penyakit jantung, atau mengonsumsi obat tekanan darah golongan beta-blocker, selalu konsultasikan target denyut nadi latihan Anda dengan dokter spesialis kedokteran olahraga.
                        </p>
                    </div>

                    <!-- Disclaimer Box -->
                    <div class="disclaimer-box m-0">
                        <strong class="d-block" style="color: var(--primary);">Batas Penggunaan Medis</strong>
                        <span class="small text-muted" style="line-height: 1.5; display: block;">
                            Hasil perhitungan zona ini adalah estimasi matematis terstandar (Formula Tanaka, Karvonen, dan Zoladz) dan bukan pengganti uji stres fisik treadmill klinis (*exercise stress test*).
                        </span>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="d-flex flex-wrap gap-2 mt-4 pt-2">
                        <a href="zona_jantung_form.php" class="btn btn-primary-health px-4">
                            Hitung Ulang
                        </a>
                        <a href="../aktivitas_fisik/aktivitas_fisik_form.php" class="btn btn-coral px-4">
                            Cek Aktivitas Fisik (IPAQ)
                        </a>
                        <a href="../index.php" class="btn btn-link ms-auto text-decoration-none" style="color: var(--muted);">Kembali ke Beranda</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Related Tools -->
        <div class="col-lg-4">
            <div class="card p-4 mb-4">
                <h6 class="fw-bold mb-3" style="color: var(--primary);">Parameter Biometrik</h6>
                <div class="small text-muted d-flex flex-column gap-2">
                    <div class="d-flex justify-content-between"><span>Nama:</span> <strong class="text-dark"><?= htmlspecialchars($res['nama']) ?></strong></div>
                    <div class="d-flex justify-content-between"><span>Umur:</span> <strong class="text-dark"><?= $res['umur'] ?> Tahun</strong></div>
                    <div class="d-flex justify-content-between"><span>Resting Heart Rate:</span> <strong class="text-dark"><?= $res['resting_hr'] ?> BPM</strong></div>
                    <div class="d-flex justify-content-between"><span>Metode Acuan:</span> <strong class="text-dark"><?= ucfirst($res['metode']) ?></strong></div>
                </div>
            </div>

            <div class="card p-4">
                <h6 class="fw-bold mb-2" style="color: var(--primary);">Alat Terkait</h6>
                <div class="d-flex flex-column gap-3 mt-3">
                    <a href="../jantung/jantung_form.php" class="d-flex align-items-center gap-3 text-decoration-none p-2 rounded-2 hover-bg">
                        <div class="tool-icon icon-rose m-0" style="width:38px; height:38px; font-size:1.1rem;"><i class="bi bi-heart-pulse"></i></div>
                        <div>
                            <div class="fw-bold small text-dark">Detak Jantung Istirahat</div>
                            <div class="small text-muted" style="font-size:0.75rem;">Evaluasi RHR & batas denyut</div>
                        </div>
                    </a>
                    <a href="../tekanan_darah/tekanan_darah_form.php" class="d-flex align-items-center gap-3 text-decoration-none p-2 rounded-2 hover-bg">
                        <div class="tool-icon icon-blue m-0" style="width:38px; height:38px; font-size:1.1rem;"><i class="bi bi-speedometer2"></i></div>
                        <div>
                            <div class="fw-bold small text-dark">Tekanan Darah (Tensi)</div>
                            <div class="small text-muted" style="font-size:0.75rem;">Sistolik & Diastolik AHA/ACC</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
