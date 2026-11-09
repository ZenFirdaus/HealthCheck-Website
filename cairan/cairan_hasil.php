<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/cairan_helper.php';

$res = null;

if (isset($_GET['id']) && (int)$_GET['id'] > 0) {
    $record = assert_record_access($pdo, 'cek_kebutuhan_cairan', 'id_cairan', (int)$_GET['id']);
    $actLevels = get_fluid_activity_levels();
    $climFactors = get_fluid_climate_factors();
    $actKey = $record['tingkat_aktivitas'];
    $climKey = $record['kondisi_iklim'];

    $res = [
        'id_cairan'       => (int)$record['id_cairan'],
        'user_id'         => $record['user_id'] ? (int)$record['user_id'] : null,
        'nama'            => $record['nama_user'],
        'berat'           => (float)$record['berat_badan'],
        'aktivitas_label' => $actLevels[$actKey]['label'] ?? $actKey,
        'iklim_label'     => $climFactors[$climKey]['label'] ?? $climKey,
        'kebutuhan_ml'    => (float)$record['kebutuhan_ml'],
        'kebutuhan_liter' => (float)$record['kebutuhan_liter'],
        'gelas'           => (int)$record['kebutuhan_gelas'],
        'saran'           => $record['saran']
    ];
} else {
    $res = $_SESSION['cairan_result'] ?? null;
    if ($res) {
        if (isset($res['user_id']) && $res['user_id'] !== null && !can_access_user_data((int)$res['user_id'])) {
            http_response_code(403);
            die("403 Forbidden: Anda tidak memiliki hak akses terhadap hasil pemeriksaan ini.");
        }
    } else {
        header("Location: cairan_form.php");
        exit;
    }
}

$pageTitle = 'Hasil Estimasi Kebutuhan Cairan Harian — HealthCheck';
$activePage = 'cairan';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="cairan_form.php" class="text-decoration-none">Kebutuhan Cairan</a></li>
            <li class="breadcrumb-item active" aria-current="page">Hasil Kebutuhan Cairan</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card overflow-hidden mb-4">
                <div class="card-editorial-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0" style="color: var(--primary);">Hasil Estimasi Kebutuhan Air Minum</h5>
                    <span class="badge border px-3 py-1 small" style="background-color: var(--light-bg); color: var(--primary); border-color: var(--card-border) !important;">
                        <?= htmlspecialchars($res['nama']) ?>
                    </span>
                </div>

                <div class="p-4 p-md-5">
                    <!-- Skor Utama -->
                    <div class="metric-hero-box">
                        <span class="text-uppercase fw-semibold small" style="color: var(--muted); letter-spacing: 0.05em;">Target Asupan Air Harian</span>
                        <div class="metric-number my-1" style="color: var(--secondary);">
                            <?= number_format($res['kebutuhan_liter'], 1) ?> <span class="fs-4 fw-normal text-muted">Liter</span>
                        </div>
                        <div class="h6 fw-semibold mb-0" style="color: var(--primary);">
                            Setara dengan <span class="badge bg-info-subtle text-dark px-3 py-1 fs-6">~<?= $res['gelas'] ?> Gelas</span> (@ 250 ml)
                        </div>
                    </div>

                    <!-- Dua Metrik: Liter & Mililiter -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-4 rounded-3 text-center border h-100" style="background-color: var(--light-bg); border-color: var(--card-border) !important;">
                                <span class="text-uppercase fw-semibold small" style="color: var(--muted); letter-spacing: 0.05em;">Volume Total Mililiter</span>
                                <div class="metric-number my-2" style="font-size: 2.2rem; color: var(--primary);">
                                    <?= number_format($res['kebutuhan_ml'], 0, ',', '.') ?> <span class="fs-6 fw-normal text-muted">ml/hari</span>
                                </div>
                                <p class="small mb-0 text-muted">Kebutuhan total air harian untuk menjaga fungsi ginjal dan hidrasi seluler.</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-4 rounded-3 text-center border h-100" style="background-color: var(--primary-light); border-color: rgba(23, 44, 70, 0.1) !important;">
                                <span class="text-uppercase fw-semibold small" style="color: var(--secondary); letter-spacing: 0.05em;">Ukuran Gelas Standar</span>
                                <div class="metric-number my-2" style="font-size: 2.2rem; color: var(--primary);">
                                    <?= $res['gelas'] ?> <span class="fs-6 fw-normal text-muted">gelas</span>
                                </div>
                                <p class="small mb-0 text-muted">Gunakan gelas standar 250 ml sebagai acuan minum sepanjang hari.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Parameter yang Dihitung -->
                    <div class="p-3 border rounded-3 mb-4" style="background-color: var(--surface); border-color: var(--card-border) !important;">
                        <h6 class="fw-bold mb-2" style="color: var(--primary);">Faktor Penyesuaian Kebutuhan</h6>
                        <div class="row g-2 small text-muted">
                            <div class="col-md-4">Berat Badan: <strong class="text-dark d-block"><?= $res['berat'] ?> kg</strong></div>
                            <div class="col-md-4">Aktivitas Fisik: <strong class="text-dark d-block"><?= htmlspecialchars($res['aktivitas_label']) ?></strong></div>
                            <div class="col-md-4">Kondisi Suhu/Iklim: <strong class="text-dark d-block"><?= htmlspecialchars($res['iklim_label']) ?></strong></div>
                        </div>
                    </div>

                    <!-- Saran Jadwal Minum Praktis -->
                    <div class="p-4 mb-4 rounded-3" style="background-color: var(--surface); border: 1px solid var(--card-border);">
                        <h6 class="fw-bold mb-2" style="color: var(--primary);">Panduan Distribusi Waktu Minum</h6>
                        <ul class="small text-muted mb-3 d-flex flex-column gap-2 ps-3" style="line-height: 1.55;">
                            <li><strong>1-2 Gelas saat bangun pagi:</strong> Mengaktifkan organ internal dan mengganti cairan yang hilang saat tidur.</li>
                            <li><strong>1 Gelas 30 menit sebelum makan:</strong> Membantu kelancaran sistem pencernaan.</li>
                            <li><strong>1 Gelas sebelum dan sesudah berolahraga:</strong> Menjaga performa otot dan mengganti keringat.</li>
                            <li><strong>1-2 Gelas pada siang dan sore hari:</strong> Mempertahankan konsentrasi dan mencegah rasa lelah.</li>
                            <li><strong>1 Gelas menjelang tidur malam:</strong> Menjaga hidrasi sirkulasi darah tanpa mengganggu kenyamanan tidur.</li>
                        </ul>
                    </div>

                    <!-- Disclaimer Box & Catatan Klinis -->
                    <div class="alert alert-warning small mb-4">
                        <h6 class="fw-bold mb-1" style="color: var(--primary);">Perhatian Medis Terkait Cairan</h6>
                        <p class="mb-0 text-dark" style="line-height: 1.55;">
                            Estimasi ini berlaku untuk orang dewasa dengan kondisi organ normal. Pasien dengan <strong>Gagal Ginjal</strong>, <strong>Gagal Jantung</strong>, atau yang sedang menjalani terapi diuretik wajib mengikuti instruksi pembatasan volume cairan dari dokter spesialis yang merawat.
                        </p>
                    </div>

                    <div class="disclaimer-box m-0">
                        <strong class="d-block" style="color: var(--primary);">Batas Penggunaan Medis</strong>
                        <span class="small" style="color: var(--muted); line-height: 1.5; display: block;">
                            Hasil perhitungan ini bersifat edukatif dan estimatif awal. Warna air seni (urine) berwarna kuning sangat muda atau bening merupakan indikator praktis terbaik bahwa Anda terhidrasi dengan baik.
                        </span>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="d-flex flex-wrap gap-2 mt-4 pt-2">
                        <a href="cairan_form.php" class="btn btn-primary-health px-4">
                            Hitung Ulang
                        </a>
                        <a href="../bmr/bmr_form.php" class="btn btn-emerald px-4">
                            Cek Kebutuhan Kalori & Makronutrisi
                        </a>
                        <a href="../index.php" class="btn btn-link ms-auto text-decoration-none" style="color: var(--muted);">Kembali ke Beranda</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Related Tools -->
        <div class="col-lg-4">
            <div class="card p-4 mb-4">
                <h6 class="fw-bold mb-3" style="color: var(--primary);">Alat Terkait Lainnya</h6>
                <div class="d-flex flex-column gap-3">
                    <a href="../bmr/bmr_form.php" class="d-flex align-items-center gap-3 text-decoration-none p-2 rounded-2 hover-bg">
                        <div class="tool-icon icon-blue m-0" style="width:38px; height:38px; font-size:1.1rem;"><i class="bi bi-fire"></i></div>
                        <div>
                            <div class="fw-bold small" style="color: var(--dark);">Kalkulator Kalori & BMR</div>
                            <div class="small" style="font-size:0.75rem; color: var(--muted);">BMR, TDEE & Target Diet</div>
                        </div>
                    </a>
                    <a href="../body_fat/body_fat_form.php" class="d-flex align-items-center gap-3 text-decoration-none p-2 rounded-2 hover-bg">
                        <div class="tool-icon icon-rose m-0" style="width:38px; height:38px; font-size:1.1rem;"><i class="bi bi-person-lines-fill"></i></div>
                        <div>
                            <div class="fw-bold small" style="color: var(--dark);">Persentase Lemak Tubuh</div>
                            <div class="small" style="font-size:0.75rem; color: var(--muted);">Metode antropometri US Navy</div>
                        </div>
                    </a>
                    <a href="../whtr/whtr_form.php" class="d-flex align-items-center gap-3 text-decoration-none p-2 rounded-2 hover-bg">
                        <div class="tool-icon icon-teal m-0" style="width:38px; height:38px; font-size:1.1rem;"><i class="bi bi-rulers"></i></div>
                        <div>
                            <div class="fw-bold small" style="color: var(--dark);">Kalkulator WHtR</div>
                            <div class="small" style="font-size:0.75rem; color: var(--muted);">Rasio lingkar pinggang ke tinggi</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
