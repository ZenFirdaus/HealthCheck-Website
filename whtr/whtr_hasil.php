<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/whtr_helper.php';

$res = null;

if (isset($_GET['id']) && (int)$_GET['id'] > 0) {
    $record = assert_record_access($pdo, 'cek_whtr', 'id_whtr', (int)$_GET['id']);
    $ratio = (float)$record['rasio_whtr'];
    $catData = get_whtr_category($ratio);
    $tinggi = (float)$record['tinggi_badan'];
    $lingkar = (float)$record['lingkar_pinggang'];

    $res = [
        'id_whtr'     => (int)$record['id_whtr'],
        'user_id'     => $record['user_id'] ? (int)$record['user_id'] : null,
        'nama'        => $record['nama_user'],
        'jk'          => ($record['jenis_kelamin'] === 'L') ? 'Laki-laki' : 'Perempuan',
        'tinggi'      => $tinggi,
        'lingkar'     => $lingkar,
        'rasio'       => $ratio,
        'kategori'    => $record['kategori_risiko'],
        'badge'       => $catData['badge'],
        'warna'       => $catData['color'],
        'saran'       => $record['saran'] ?? $catData['advice'],
        'batas_sehat' => get_healthy_waist_limit($tinggi)
    ];
} else {
    $res = $_SESSION['whtr_result'] ?? null;
    if ($res) {
        if (isset($res['user_id']) && $res['user_id'] !== null && !can_access_user_data((int)$res['user_id'])) {
            http_response_code(403);
            die("403 Forbidden: Anda tidak memiliki hak akses terhadap hasil pemeriksaan ini.");
        }
    } else {
        header("Location: whtr_form.php");
        exit;
    }
}

$pageTitle = 'Hasil Evaluasi Rasio Lingkar Pinggang ke Tinggi (WHtR) — HealthCheck';
$activePage = 'whtr';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="whtr_form.php" class="text-decoration-none">Kalkulator WHtR</a></li>
            <li class="breadcrumb-item active" aria-current="page">Hasil WHtR</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card overflow-hidden mb-4">
                <div class="card-editorial-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0" style="color: var(--primary);">Hasil Evaluasi Rasio Pinggang - Tinggi (WHtR)</h5>
                    <span class="badge border px-3 py-1 small" style="background-color: var(--light-bg); color: var(--primary); border-color: var(--card-border) !important;">
                        <?= htmlspecialchars($res['nama']) ?>
                    </span>
                </div>

                <div class="p-4 p-md-5">
                    <!-- Skor Utama -->
                    <div class="metric-hero-box">
                        <span class="text-uppercase fw-semibold small" style="color: var(--muted); letter-spacing: 0.05em;">Rasio Terhitung (WHtR)</span>
                        <div class="metric-number my-1 text-<?= $res['warna'] ?>">
                            <?= number_format($res['rasio'], 2, '.', '') ?>
                        </div>
                        <div class="h6 fw-semibold mb-0">
                            Klasifikasi Risiko: <span class="badge bg-<?= $res['warna'] ?>-subtle px-3 py-1"><?= htmlspecialchars($res['kategori']) ?></span>
                        </div>
                    </div>

                    <!-- Detail Ringkasan Data -->
                    <div class="row g-3 p-3 rounded-3 mb-4 text-center" style="background-color: var(--light-bg); border: 1px solid var(--card-border);">
                        <div class="col-4">
                            <div class="small" style="color: var(--muted);">Tinggi Badan</div>
                            <div class="fw-bold fs-6" style="color: var(--dark);"><?= $res['tinggi'] ?> cm</div>
                        </div>
                        <div class="col-4 border-start border-end" style="border-color: var(--card-border) !important;">
                            <div class="small" style="color: var(--muted);">Lingkar Pinggang</div>
                            <div class="fw-bold fs-6" style="color: var(--dark);"><?= $res['lingkar'] ?> cm</div>
                        </div>
                        <div class="col-4">
                            <div class="small" style="color: var(--muted);">Batas Sehat Maksimal</div>
                            <div class="fw-bold fs-6" style="color: var(--success);">&lt; <?= $res['batas_sehat'] ?> cm</div>
                        </div>
                    </div>

                    <!-- Saran & Interpretasi Klinis -->
                    <div class="p-4 mb-4 rounded-3" style="background-color: var(--surface); border: 1px solid var(--card-border);">
                        <h6 class="fw-bold mb-2" style="color: var(--primary);">Interpretasi & Panduan Kesehatan</h6>
                        <p class="small mb-3" style="color: var(--muted); line-height: 1.6;">
                            <?= htmlspecialchars($res['saran']) ?>
                        </p>
                        <div class="p-3 rounded-2 small" style="background-color: var(--light-bg); border-left: 3px solid var(--secondary); color: var(--dark);">
                            <strong>Aturan Praktis (Rule of Thumb):</strong>
                            <span style="color: var(--muted);">Untuk tinggi badan Anda (<?= $res['tinggi'] ?> cm), lingkar pinggang yang optimal sebaiknya berada di bawah <strong><?= $res['batas_sehat'] ?> cm</strong>.</span>
                        </div>
                    </div>

                    <!-- Disclaimer Box -->
                    <div class="disclaimer-box m-0">
                        <strong class="d-block" style="color: var(--primary);">Batas Penggunaan Medis</strong>
                        <span class="small" style="color: var(--muted); line-height: 1.5; display: block;">
                            Hasil perhitungan rasio WHtR ini bersifat skrining awal adipositas sentral dan bukan diagnosis formal penyakit kardiometabolik. Konsultasikan dengan dokter untuk evaluasi profil lipid dan tekanan darah menyeluruh.
                        </span>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="d-flex flex-wrap gap-2 mt-4 pt-2">
                        <a href="whtr_form.php" class="btn btn-primary-health px-4">
                            Hitung Ulang
                        </a>
                        <a href="../body_fat/body_fat_form.php" class="btn btn-outline-secondary px-4">
                            Lanjut Cek Lemak Tubuh (US Navy)
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
                    <a href="../bmi/bmi_form.php" class="d-flex align-items-center gap-3 text-decoration-none p-2 rounded-2 hover-bg">
                        <div class="tool-icon icon-teal m-0" style="width:38px; height:38px; font-size:1.1rem;"><i class="bi bi-speedometer2"></i></div>
                        <div>
                            <div class="fw-bold small" style="color: var(--dark);">Kalkulator BMI</div>
                            <div class="small" style="font-size:0.75rem; color: var(--muted);">Bandingkan berat dan tinggi badan</div>
                        </div>
                    </a>
                    <a href="../body_fat/body_fat_form.php" class="d-flex align-items-center gap-3 text-decoration-none p-2 rounded-2 hover-bg">
                        <div class="tool-icon icon-rose m-0" style="width:38px; height:38px; font-size:1.1rem;"><i class="bi bi-person-lines-fill"></i></div>
                        <div>
                            <div class="fw-bold small" style="color: var(--dark);">Estimasi Lemak Tubuh</div>
                            <div class="small" style="font-size:0.75rem; color: var(--muted);">Metode antropometri US Navy</div>
                        </div>
                    </a>
                    <a href="../bmr/bmr_form.php" class="d-flex align-items-center gap-3 text-decoration-none p-2 rounded-2 hover-bg">
                        <div class="tool-icon icon-blue m-0" style="width:38px; height:38px; font-size:1.1rem;"><i class="bi bi-fire"></i></div>
                        <div>
                            <div class="fw-bold small" style="color: var(--dark);">Kebutuhan Kalori & Makronutrisi</div>
                            <div class="small" style="font-size:0.75rem; color: var(--muted);">BMR, TDEE & Target Karbo/Protein/Lemak</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
