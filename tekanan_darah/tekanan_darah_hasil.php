<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/tensi_helper.php';

$res = null;

if (isset($_GET['id']) && (int)$_GET['id'] > 0) {
    $record = assert_record_access($pdo, 'cek_tekanan_darah', 'id_bp', (int)$_GET['id']);
    $sis = (int)$record['sistolik'];
    $dia = (int)$record['diastolik'];
    $bpData = classify_blood_pressure($sis, $dia);

    $res = [
        'id_bp'          => (int)$record['id_bp'],
        'user_id'        => $record['user_id'] ? (int)$record['user_id'] : null,
        'nama'           => $record['nama_user'],
        'umur'           => (int)$record['umur'],
        'sistolik'       => $sis,
        'diastolik'      => $dia,
        'detak_nadi'     => $record['detak_nadi'] ? (int)$record['detak_nadi'] : null,
        'pulse_pressure' => $sis - $dia,
        'kategori'       => $record['kategori'],
        'badge'          => $bpData['badge'],
        'warna'          => $bpData['color'],
        'is_emergency'   => $bpData['is_emergency'],
        'saran'          => $record['saran'] ?? $bpData['advice'],
        'catatan'        => $record['catatan'] ?? ''
    ];
} else {
    $res = $_SESSION['bp_result'] ?? null;
    if ($res) {
        if (isset($res['user_id']) && $res['user_id'] !== null && !can_access_user_data((int)$res['user_id'])) {
            http_response_code(403);
            die("403 Forbidden: Anda tidak memiliki hak akses terhadap hasil pemeriksaan ini.");
        }
    } else {
        header("Location: tekanan_darah_form.php");
        exit;
    }
}

$pageTitle = 'Hasil Evaluasi Tekanan Darah — HealthCheck';
$activePage = 'tekanan_darah';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="tekanan_darah_form.php" class="text-decoration-none">Tekanan Darah</a></li>
            <li class="breadcrumb-item active" aria-current="page">Hasil Evaluasi</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card overflow-hidden mb-4">
                <div class="card-editorial-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0" style="color: var(--primary);">Hasil Evaluasi Tekanan Darah</h5>
                    <span class="badge border px-3 py-1 small" style="background-color: var(--light-bg); color: var(--primary); border-color: var(--card-border) !important;">
                        <?= htmlspecialchars($res['nama']) ?>
                    </span>
                </div>

                <div class="p-4 p-md-5">
                    <!-- Emergency Alert if Hypertensive Crisis -->
                    <?php if (!empty($res['is_emergency'])): ?>
                        <div class="alert alert-danger mb-4">
                            <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i> Peringatan Medis: Krisis Hipertensi</h6>
                            <p class="small mb-0">Tekanan darah Anda terukur di atas 180/120 mmHg. Jika Anda merasakan nyeri dada, sesak napas, pusing berputar, atau kebas separuh badan, segera datangi IGD rumah sakit terdekat!</p>
                        </div>
                    <?php endif; ?>

                    <!-- Skor Utama -->
                    <div class="metric-hero-box">
                        <span class="text-uppercase fw-semibold small text-muted" style="letter-spacing: 0.05em;">Tekanan Darah Terukur</span>
                        <div class="metric-number my-1 text-<?= $res['warna'] ?>">
                            <?= $res['sistolik'] ?> / <?= $res['diastolik'] ?> <span class="fs-4 fw-normal text-muted">mmHg</span>
                        </div>
                        <div class="h6 fw-semibold mb-0">
                            Klasifikasi: <span class="badge bg-<?= $res['warna'] ?>-subtle px-3 py-1"><?= htmlspecialchars($res['kategori']) ?></span>
                        </div>
                    </div>

                    <!-- Parameter Detail Rinci -->
                    <div class="row g-3 p-3 rounded-3 mb-4 text-center" style="background-color: var(--light-bg); border: 1px solid var(--border);">
                        <div class="col-4">
                            <div class="small text-muted">Sistolik (Atas)</div>
                            <div class="fw-bold fs-6 text-dark"><?= $res['sistolik'] ?> mmHg</div>
                        </div>
                        <div class="col-4 border-start border-end" style="border-color: var(--border) !important;">
                            <div class="small text-muted">Diastolik (Bawah)</div>
                            <div class="fw-bold fs-6 text-dark"><?= $res['diastolik'] ?> mmHg</div>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted">Tekanan Nadi</div>
                            <div class="fw-bold fs-6 text-primary"><?= $res['pulse_pressure'] ?> mmHg</div>
                        </div>
                    </div>

                    <?php if (!empty($res['detak_nadi'])): ?>
                        <div class="p-3 mb-4 rounded-3 border" style="background-color: var(--surface); border-color: var(--border) !important;">
                            <div class="d-flex justify-content-between align-items-center small">
                                <span class="text-muted"><i class="bi bi-heart-pulse text-coral me-1"></i> Denyut Nadi Terukur:</span>
                                <strong class="text-dark fs-6"><?= $res['detak_nadi'] ?> BPM</strong>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Saran & Rekomendasi Klinis -->
                    <div class="p-4 mb-4 rounded-3" style="background-color: var(--surface); border: 1px solid var(--border);">
                        <h6 class="fw-bold mb-2" style="color: var(--primary);">Panduan & Interpretasi Hasil</h6>
                        <p class="small text-muted mb-3" style="line-height: 1.6;">
                            <?= htmlspecialchars($res['saran']) ?>
                        </p>
                        <div class="p-3 rounded-2 small" style="background-color: var(--light-bg); border-left: 3px solid var(--blue); color: var(--dark);">
                            <strong>Penting Diingat:</strong>
                            <span class="text-muted">Satu kali pengukuran tensi tidak cukup untuk menegakkan diagnosis hipertensi. Tekanan darah dapat berfluktuasi karena stres, kelelahan, kafein, atau kurang tidur. Lakukan pencatatan berkala pada jam yang sama selama 3–7 hari berturut-turut.</span>
                        </div>
                    </div>

                    <!-- Disclaimer Box -->
                    <div class="disclaimer-box m-0">
                        <strong class="d-block" style="color: var(--primary);">Batas Penggunaan Medis</strong>
                        <span class="small text-muted" style="line-height: 1.5; display: block;">
                            Hasil evaluasi ini bersumber pada panduan klinis AHA/ACC 2017 & Konsensus PERHI 2021 dan bersifat edukatif. Alat ini bukan diagnosis medis formal dan tidak menggantikan evaluasi dokter atau resep obat antihipertensi.
                        </span>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="d-flex flex-wrap gap-2 mt-4 pt-2">
                        <a href="tekanan_darah_form.php" class="btn btn-primary-health px-4">
                            Ukur Ulang
                        </a>
                        <a href="../jantung/jantung_form.php" class="btn btn-coral px-4">
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
                <h6 class="fw-bold mb-3" style="color: var(--primary);">Kategori Tekanan Darah</h6>
                <div class="table-responsive">
                    <table class="table table-sm small align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th class="text-end">Rentang</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge bg-success-subtle">Normal</span></td>
                                <td class="text-end">&lt;120 / &lt;80</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-warning-subtle">Pra-Hipertensi</span></td>
                                <td class="text-end">120-129 / &lt;80</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-warning-subtle">Hipertensi Tk 1</span></td>
                                <td class="text-end">130-139 / 80-89</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-danger-subtle">Hipertensi Tk 2</span></td>
                                <td class="text-end">&ge;140 / &ge;90</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card p-4">
                <h6 class="fw-bold mb-2" style="color: var(--primary);">Kondisi Pemeriksaan Ideal</h6>
                <p class="small text-muted mb-0" style="line-height: 1.55;">
                    Ukur tensi saat kondisi tubuh tenang, tidak sedang tergesa-gesa atau menahan buang air kecil. Hindari merokok, minum kopi, dan berolahraga setidaknya 30 menit sebelum pemeriksaan tensi.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
