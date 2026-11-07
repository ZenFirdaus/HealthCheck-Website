<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/bmr_helper.php';

$res = null;

if (isset($_GET['id']) && (int)$_GET['id'] > 0) {
    $record = assert_record_access($pdo, 'cek_bmr', 'id_bmr', (int)$_GET['id']);
    $bmrVal = (float)$record['bmr'];
    $tdeeVal = (float)$record['tdee'];
    $tujuan = $record['target_tujuan'] ?? 'maintain';
    $targetKalori = !empty($record['target_kalori']) ? (float)$record['target_kalori'] : calculate_target_calories($tdeeVal, $tujuan, $record['jenis_kelamin']);
    $macros = calculate_macronutrients($targetKalori, 'balanced');
    $goals = get_dietary_goals();

    $res = [
        'id_bmr'        => (int)$record['id_bmr'],
        'user_id'       => $record['user_id'] ? (int)$record['user_id'] : null,
        'nama'          => $record['nama_user'],
        'jk'            => ($record['jenis_kelamin'] === 'L') ? 'Laki-laki' : 'Perempuan',
        'umur'          => $record['umur'],
        'berat'         => $record['berat_badan'],
        'tinggi'        => $record['tinggi_badan'],
        'bmr'           => $bmrVal,
        'aktivitas'     => $record['aktivitas'],
        'tdee'          => $tdeeVal,
        'kalori_turun'  => max(1200, $tdeeVal - 450),
        'kalori_naik'   => $tdeeVal + 450,
        'target_tujuan' => $tujuan,
        'target_label'  => $goals[$tujuan]['label'] ?? 'Pemeliharaan',
        'target_kalori' => $targetKalori,
        'protein_gram'  => !empty($record['protein_gram']) ? (float)$record['protein_gram'] : $macros['protein_gram'],
        'carbs_gram'    => !empty($record['karbo_gram']) ? (float)$record['karbo_gram'] : $macros['carbs_gram'],
        'fat_gram'      => !empty($record['lemak_gram']) ? (float)$record['lemak_gram'] : $macros['fat_gram']
    ];
} else {
    $res = $_SESSION['bmr_result'] ?? null;
    if ($res) {
        if (isset($res['user_id']) && $res['user_id'] !== null && !can_access_user_data((int)$res['user_id'])) {
            http_response_code(403);
            die("403 Forbidden: Anda tidak memiliki hak akses terhadap hasil pemeriksaan ini.");
        }
        if (!isset($res['protein_gram'])) {
            $tujuan = $res['target_tujuan'] ?? 'maintain';
            $targetKalori = $res['target_kalori'] ?? calculate_target_calories((float)$res['tdee'], $tujuan, $res['jk'] ?? 'L');
            $macros = calculate_macronutrients($targetKalori, 'balanced');
            $res['target_kalori'] = $targetKalori;
            $res['protein_gram'] = $macros['protein_gram'];
            $res['carbs_gram'] = $macros['carbs_gram'];
            $res['fat_gram'] = $macros['fat_gram'];
        }
    } else {
        if (isset($_GET['bmr'])) {
            $bmrVal = floatval($_GET['bmr']);
            $tdeeEst = round($bmrVal * 1.55);
            $macros = calculate_macronutrients($tdeeEst, 'balanced');
            $res = [
                'nama' => 'Pengguna',
                'jk' => '-',
                'umur' => '-',
                'berat' => '-',
                'tinggi' => '-',
                'bmr' => $bmrVal,
                'aktivitas' => 'Sedang',
                'tdee' => $tdeeEst,
                'kalori_turun' => round($tdeeEst - 450),
                'kalori_naik' => round($tdeeEst + 450),
                'target_tujuan' => 'maintain',
                'target_label' => 'Pemeliharaan',
                'target_kalori' => $tdeeEst,
                'protein_gram' => $macros['protein_gram'],
                'carbs_gram' => $macros['carbs_gram'],
                'fat_gram' => $macros['fat_gram']
            ];
        } else {
            header("Location: bmr_form.php");
            exit;
        }
    }
}

$pageTitle = 'Hasil Evaluasi BMR & Kebutuhan Kalori — HealthCheck';
$activePage = 'bmr';
require_once __DIR__ . '/../includes/header.php';
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
                    <h5 class="fw-bold mb-0" style="color: var(--primary);">Estimasi Kebutuhan Kalori & Makronutrisi</h5>
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

                    <!-- Target Sasaran Terpilih & Distribusi Makronutrisi -->
                    <div class="p-4 mb-4 rounded-3 border" style="background-color: var(--surface); border-color: var(--card-border) !important;">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                            <div>
                                <span class="text-uppercase fw-semibold small text-muted" style="letter-spacing: 0.05em;">Target Sasaran Terpilih:</span>
                                <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($res['target_label'] ?? 'Pemeliharaan Berat Badan') ?></h6>
                            </div>
                            <div class="text-md-end">
                                <span class="fs-4 fw-bold" style="color: var(--primary);"><?= number_format($res['target_kalori'] ?? $res['tdee'], 0, ',', '.') ?></span>
                                <span class="small text-muted">kkal / hari</span>
                            </div>
                        </div>

                        <hr style="border-color: var(--card-border);">

                        <h6 class="fw-bold mb-3" style="color: var(--primary);">Rekomendasi Makronutrisi Harian (Pola Seimbang)</h6>
                        <div class="row g-3 text-center">
                            <div class="col-4">
                                <div class="p-3 rounded-2" style="background-color: var(--light-bg); border: 1px solid var(--card-border);">
                                    <span class="badge bg-primary-subtle text-primary mb-1">Karbohidrat (50%)</span>
                                    <div class="fs-4 fw-bold mt-1 text-dark"><?= number_format($res['carbs_gram'], 1) ?> g</div>
                                    <small class="text-muted" style="font-size: 0.75rem;">~<?= number_format($res['carbs_gram'] * 4, 0) ?> kkal</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-3 rounded-2" style="background-color: var(--light-bg); border: 1px solid var(--card-border);">
                                    <span class="badge bg-success-subtle text-success mb-1">Protein (20%)</span>
                                    <div class="fs-4 fw-bold mt-1 text-dark"><?= number_format($res['protein_gram'], 1) ?> g</div>
                                    <small class="text-muted" style="font-size: 0.75rem;">~<?= number_format($res['protein_gram'] * 4, 0) ?> kkal</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-3 rounded-2" style="background-color: var(--light-bg); border: 1px solid var(--card-border);">
                                    <span class="badge bg-warning-subtle text-warning mb-1">Lemak Sehat (30%)</span>
                                    <div class="fs-4 fw-bold mt-1 text-dark"><?= number_format($res['fat_gram'], 1) ?> g</div>
                                    <small class="text-muted" style="font-size: 0.75rem;">~<?= number_format($res['fat_gram'] * 9, 0) ?> kkal</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rekomendasi Sasaran Kalori Perbandingan Tiga Skenario -->
                    <h6 class="fw-bold mb-3" style="color: var(--primary);">Perbandingan Sasaran Kalori</h6>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card p-3 text-center h-100 <?= ($res['target_tujuan'] ?? '') === 'lose_weight' ? 'border-primary' : '' ?>">
                                <span class="badge bg-warning-subtle align-self-center mb-2">Defisit (Turun BB)</span>
                                <div class="fs-4 fw-bold my-1" style="color: var(--primary);"><?= number_format($res['kalori_turun'], 0, ',', '.') ?></div>
                                <span class="small" style="color: var(--muted);">kkal / hari</span>
                                <small class="mt-2 border-top pt-2" style="font-size:0.75rem; color: var(--muted); border-color: var(--card-border) !important;">Defisit terkontrol ~450 kkal untuk penurunan bertahap 0.3 - 0.5 kg/minggu.</small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card p-3 text-center h-100 <?= ($res['target_tujuan'] ?? 'maintain') === 'maintain' ? 'border-primary' : '' ?>" style="border-color: var(--secondary) !important;">
                                <span class="badge bg-success-subtle align-self-center mb-2">Pemeliharaan (Ideal)</span>
                                <div class="fs-4 fw-bold my-1" style="color: var(--primary);"><?= number_format($res['tdee'], 0, ',', '.') ?></div>
                                <span class="small" style="color: var(--muted);">kkal / hari</span>
                                <small class="mt-2 border-top pt-2" style="font-size:0.75rem; color: var(--muted); border-color: var(--card-border) !important;">Konsumsi seimbang sebanding kalori yang dikeluarkan setiap hari.</small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card p-3 text-center h-100 <?= ($res['target_tujuan'] ?? '') === 'gain_weight' ? 'border-primary' : '' ?>">
                                <span class="badge bg-info-subtle align-self-center mb-2">Surplus (Naik BB)</span>
                                <div class="fs-4 fw-bold my-1" style="color: var(--primary);"><?= number_format($res['kalori_naik'], 0, ',', '.') ?></div>
                                <span class="small" style="color: var(--muted);">kkal / hari</span>
                                <small class="mt-2 border-top pt-2" style="font-size:0.75rem; color: var(--muted); border-color: var(--card-border) !important;">Surplus terukur ~450 kkal diimbangi latihan beban untuk peningkatan massa otot.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Disclaimer Box -->
                    <div class="disclaimer-box m-0">
                        <strong class="d-block" style="color: var(--primary);">Panduan Gizi & Batasan Medis</strong>
                        <span class="small" style="color: var(--muted); line-height: 1.5; display: block;">
                            Estimasi kalori dan makronutrisi ini dihitung dengan formula Mifflin-St Jeor. Hindari diet ekstrem di bawah 1.200 kkal tanpa pengawasan dokter atau ahli gizi teregistrasi.
                        </span>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="d-flex flex-wrap gap-2 mt-4 pt-2">
                        <a href="bmr_form.php" class="btn btn-primary-health px-4">
                            Hitung Ulang
                        </a>
                        <a href="../cairan/cairan_form.php" class="btn btn-outline-secondary px-4">
                            Cek Kebutuhan Cairan
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
