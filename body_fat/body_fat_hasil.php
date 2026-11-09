<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/body_fat_helper.php';

$res = null;

if (isset($_GET['id']) && (int)$_GET['id'] > 0) {
    $record = assert_record_access($pdo, 'cek_lemak_tubuh', 'id_fat', (int)$_GET['id']);
    $fatPct = (float)$record['body_fat_percent'];
    $jk = $record['jenis_kelamin'];
    $catData = get_body_fat_category($fatPct, $jk);

    $res = [
        'id_fat'       => (int)$record['id_fat'],
        'user_id'      => $record['user_id'] ? (int)$record['user_id'] : null,
        'nama'         => $record['nama_user'],
        'jk'           => ($jk === 'L') ? 'Laki-laki' : 'Perempuan',
        'umur'         => (int)$record['umur'],
        'berat'        => (float)$record['berat_badan'],
        'tinggi'       => (float)$record['tinggi_badan'],
        'leher'        => (float)$record['lingkar_leher'],
        'pinggang'     => (float)$record['lingkar_pinggang'],
        'pinggul'      => $record['lingkar_pinggul'] ? (float)$record['lingkar_pinggul'] : null,
        'fat_percent'  => $fatPct,
        'fat_mass_kg'  => (float)$record['fat_mass_kg'],
        'lean_mass_kg' => (float)$record['lean_mass_kg'],
        'kategori'     => $record['kategori'],
        'badge'        => $catData['badge'],
        'warna'        => $catData['color'],
        'saran'        => $catData['advice']
    ];
} else {
    $res = $_SESSION['body_fat_result'] ?? null;
    if ($res) {
        if (isset($res['user_id']) && $res['user_id'] !== null && !can_access_user_data((int)$res['user_id'])) {
            http_response_code(403);
            die("403 Forbidden: Anda tidak memiliki hak akses terhadap hasil pemeriksaan ini.");
        }
    } else {
        header("Location: body_fat_form.php");
        exit;
    }
}

$pageTitle = 'Hasil Estimasi Lemak Tubuh (US Navy) — HealthCheck';
$activePage = 'body_fat';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="body_fat_form.php" class="text-decoration-none">Kalkulator Lemak Tubuh</a></li>
            <li class="breadcrumb-item active" aria-current="page">Hasil Estimasi Lemak Tubuh</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card overflow-hidden mb-4">
                <div class="card-editorial-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0" style="color: var(--primary);">Hasil Estimasi Persentase Lemak Tubuh</h5>
                    <span class="badge border px-3 py-1 small" style="background-color: var(--light-bg); color: var(--primary); border-color: var(--card-border) !important;">
                        <?= htmlspecialchars($res['nama']) ?>
                    </span>
                </div>

                <div class="p-4 p-md-5">
                    <!-- Skor Utama -->
                    <div class="metric-hero-box">
                        <span class="text-uppercase fw-semibold small" style="color: var(--muted); letter-spacing: 0.05em;">Persentase Lemak Tubuh (US Navy)</span>
                        <div class="metric-number my-1 text-<?= $res['warna'] ?>">
                            <?= number_format($res['fat_percent'], 1) ?> <span class="fs-4 fw-normal text-muted">%</span>
                        </div>
                        <div class="h6 fw-semibold mb-0">
                            Kategori (ACE): <span class="badge bg-<?= $res['warna'] ?>-subtle px-3 py-1"><?= htmlspecialchars($res['kategori']) ?></span>
                        </div>
                    </div>

                    <!-- Dua Metrik Komposisi Tubuh: Massa Lemak vs Massa Bebas Lemak -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-4 rounded-3 text-center border h-100" style="background-color: var(--light-bg); border-color: var(--card-border) !important;">
                                <span class="text-uppercase fw-semibold small" style="color: var(--muted); letter-spacing: 0.05em;">Estimasi Massa Lemak (Fat Mass)</span>
                                <div class="metric-number my-2" style="font-size: 2.2rem; color: var(--danger);">
                                    <?= number_format($res['fat_mass_kg'], 1) ?> <span class="fs-6 fw-normal text-muted">kg</span>
                                </div>
                                <p class="small mb-0 text-muted">Total estimasi cadangan jaringan adiposa dalam tubuh Anda.</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-4 rounded-3 text-center border h-100" style="background-color: var(--primary-light); border-color: rgba(23, 44, 70, 0.1) !important;">
                                <span class="text-uppercase fw-semibold small" style="color: var(--secondary); letter-spacing: 0.05em;">Massa Bebas Lemak (Lean Mass)</span>
                                <div class="metric-number my-2" style="font-size: 2.2rem; color: var(--primary);">
                                    <?= number_format($res['lean_mass_kg'], 1) ?> <span class="fs-6 fw-normal text-muted">kg</span>
                                </div>
                                <p class="small mb-0 text-muted">Akumulasi massa otot, tulang, organ vital, dan kandungan air tubuh.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Detail Pengukuran Antropometri -->
                    <div class="p-3 border rounded-3 mb-4" style="background-color: var(--surface); border-color: var(--card-border) !important;">
                        <h6 class="fw-bold mb-2" style="color: var(--primary);">Parameter Antropometri yang Dihitung</h6>
                        <div class="row g-2 small text-muted">
                            <div class="col-6 col-md-3">Jenis Kelamin: <strong class="text-dark d-block"><?= htmlspecialchars($res['jk']) ?></strong></div>
                            <div class="col-6 col-md-3">Berat / Tinggi: <strong class="text-dark d-block"><?= $res['berat'] ?> kg / <?= $res['tinggi'] ?> cm</strong></div>
                            <div class="col-6 col-md-3">Lingkar Leher: <strong class="text-dark d-block"><?= $res['leher'] ?> cm</strong></div>
                            <div class="col-6 col-md-3">Lingkar Pinggang: <strong class="text-dark d-block"><?= $res['pinggang'] ?> cm <?= $res['pinggul'] ? ' (Panggul: ' . $res['pinggul'] . ' cm)' : '' ?></strong></div>
                        </div>
                    </div>

                    <!-- Saran & Interpretasi -->
                    <div class="p-4 mb-4 rounded-3" style="background-color: var(--surface); border: 1px solid var(--card-border);">
                        <h6 class="fw-bold mb-2" style="color: var(--primary);">Interpretasi & Rekomendasi</h6>
                        <p class="small mb-0" style="color: var(--muted); line-height: 1.6;">
                            <?= htmlspecialchars($res['saran']) ?>
                        </p>
                    </div>

                    <!-- Disclaimer Box -->
                    <div class="disclaimer-box m-0">
                        <strong class="d-block" style="color: var(--primary);">Batas Penggunaan Medis & Keterbatasan Metode</strong>
                        <span class="small" style="color: var(--muted); line-height: 1.5; display: block;">
                            Metode US Navy ini merupakan estimasi empiris antropometri lingkar tubuh dan bukan pengukuran medis langsung (seperti DEXA atau Hydrostatic Weighing). Hasil kalkulasi ini bermanfaat untuk memantau tren komposisi tubuh secara konsisten.
                        </span>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="d-flex flex-wrap gap-2 mt-4 pt-2">
                        <a href="body_fat_form.php" class="btn btn-primary-health px-4">
                            Hitung Ulang
                        </a>
                        <a href="../cairan/cairan_form.php" class="btn btn-teal px-4">
                            Cek Kebutuhan Cairan Harian
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
                    <a href="../whtr/whtr_form.php" class="d-flex align-items-center gap-3 text-decoration-none p-2 rounded-2 hover-bg">
                        <div class="tool-icon icon-teal m-0" style="width:38px; height:38px; font-size:1.1rem;"><i class="bi bi-rulers"></i></div>
                        <div>
                            <div class="fw-bold small" style="color: var(--dark);">Kalkulator WHtR</div>
                            <div class="small" style="font-size:0.75rem; color: var(--muted);">Rasio lingkar pinggang ke tinggi</div>
                        </div>
                    </a>
                    <a href="../bmi/bmi_form.php" class="d-flex align-items-center gap-3 text-decoration-none p-2 rounded-2 hover-bg">
                        <div class="tool-icon icon-teal m-0" style="width:38px; height:38px; font-size:1.1rem;"><i class="bi bi-speedometer2"></i></div>
                        <div>
                            <div class="fw-bold small" style="color: var(--dark);">Kalkulator BMI</div>
                            <div class="small" style="font-size:0.75rem; color: var(--muted);">Indeks massa tubuh standar Asia</div>
                        </div>
                    </a>
                    <a href="../bmr/bmr_form.php" class="d-flex align-items-center gap-3 text-decoration-none p-2 rounded-2 hover-bg">
                        <div class="tool-icon icon-blue m-0" style="width:38px; height:38px; font-size:1.1rem;"><i class="bi bi-fire"></i></div>
                        <div>
                            <div class="fw-bold small" style="color: var(--dark);">Kebutuhan Kalori & Makronutrisi</div>
                            <div class="small" style="font-size:0.75rem; color: var(--muted);">Estimasi BMR, TDEE & Target Diet</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
