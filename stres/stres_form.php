<?php
$pageTitle = 'Penilaian Tingkat Stres (PSS-10) — HealthCheck';
$activePage = 'stres';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/stres_helper.php';

$profile = get_current_user_profile($pdo);
$defaultName = $profile['nama_lengkap'] ?? ($_SESSION['username'] ?? '');

$questions = [
    1 => '1. Seberapa sering Anda merasa kesal karena sesuatu yang terjadi di luar dugaan?',
    2 => '2. Seberapa sering Anda merasa tidak mampu mengontrol hal-hal penting dalam hidup Anda?',
    3 => '3. Seberapa sering Anda merasa gugup, tegang, dan stres?',
    4 => '4. Seberapa sering Anda merasa yakin akan kemampuan Anda menangani masalah pribadi?',
    5 => '5. Seberapa sering Anda merasa bahwa segala sesuatunya berjalan sesuai rencana Anda?',
    6 => '6. Seberapa sering Anda merasa kewalahan mengatasi semua tugas yang harus diselesaikan?',
    7 => '7. Seberapa sering Anda mampu mengendalikan rasa jengkel atau kejenuhan dalam hidup?',
    8 => '8. Seberapa sering Anda merasa mampu menguasai keadaan hidup Anda dengan baik?',
    9 => '9. Seberapa sering Anda merasa marah karena hal-hal yang terjadi di luar kendali Anda?',
    10=> '10. Seberapa sering Anda merasa kesulitan menumpuk begitu tinggi hingga merasa tidak sanggup mengatasinya?'
];
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Kalkulator</a></li>
            <li class="breadcrumb-item active" aria-current="page">Penilaian Tingkat Stres</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card overflow-hidden">
                <div class="card-editorial-header">
                    <h4 class="card-heading mb-1">Penilaian Tingkat Stres (PSS-10)</h4>
                    <p class="small mb-0 text-muted">Instrumen psikometri terstandar Perceived Stress Scale untuk mengevaluasi persepsi ketidakpastian dan beban stres selama 1 bulan terakhir.</p>
                </div>

                <div class="p-4 p-md-5">
                    <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'invalid_input'): ?>
                        <div class="alert alert-danger small mb-4">
                            <i class="bi bi-exclamation-circle-fill me-2"></i> Harap pastikan nama lengkap dan seluruh 10 butir pertanyaan telah dijawab.
                        </div>
                    <?php endif; ?>

                    <p class="small text-muted mb-4">
                        Pilihlah salah satu opsi jawaban yang paling menggambarkan perasaan dan pikiran Anda <strong>selama 1 bulan terakhir</strong>. Tidak ada jawaban yang salah.
                    </p>

                    <form action="stres_proses.php" method="POST" id="stressForm">
                        <?= csrf_field() ?>
                        <div class="mb-4">
                            <label class="form-label" for="nama_user">Nama atau Inisial <span class="text-danger">*</span></label>
                            <input type="text" name="nama_user" id="nama_user" class="form-control" placeholder="Contoh: Budi" value="<?= htmlspecialchars($defaultName) ?>" required>
                        </div>

                        <?php foreach ($questions as $num => $text): ?>
                            <div class="p-3 mb-3 rounded-3 border" style="background-color: var(--light-bg); border-color: var(--border) !important;">
                                <label class="form-label text-dark fw-semibold small mb-2 d-block">
                                    <?= htmlspecialchars($text) ?>
                                    <?php if (in_array($num, [4, 5, 7, 8])): ?>
                                        <span class="text-muted" style="font-size:0.75rem; font-weight:normal;">(Item Positif)</span>
                                    <?php endif; ?>
                                </label>
                                <div class="d-flex flex-column gap-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q<?= $num ?>" id="q<?= $num ?>_0" value="0" checked>
                                        <label class="form-check-label small" for="q<?= $num ?>_0">Tidak pernah sama sekali (0)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q<?= $num ?>" id="q<?= $num ?>_1" value="1">
                                        <label class="form-check-label small" for="q<?= $num ?>_1">Hampir tidak pernah (1)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q<?= $num ?>" id="q<?= $num ?>_2" value="2">
                                        <label class="form-check-label small" for="q<?= $num ?>_2">Kadang-kadang (2)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q<?= $num ?>" id="q<?= $num ?>_3" value="3">
                                        <label class="form-check-label small" for="q<?= $num ?>_3">Cukup sering (3)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q<?= $num ?>" id="q<?= $num ?>_4" value="4">
                                        <label class="form-check-label small" for="q<?= $num ?>_4">Sangat sering (4)</label>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="d-flex gap-2 pt-3">
                            <button type="submit" class="btn btn-primary-health px-4 py-2">
                                Lihat Hasil Evaluasi Stres
                            </button>
                            <a href="../index.php" class="btn btn-outline-secondary px-4 py-2">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="col-lg-4">
            <div class="card p-4 mb-4">
                <h6 class="fw-bold mb-3" style="color: var(--primary);">Skala Acuan PSS-10</h6>
                <div class="table-responsive">
                    <table class="table table-sm small align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Total Skor</th>
                                <th class="text-end">Tingkat Stres</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>0 – 13</td>
                                <td class="text-end"><span class="badge bg-success-subtle">Stres Rendah</span></td>
                            </tr>
                            <tr>
                                <td>14 – 26</td>
                                <td class="text-end"><span class="badge bg-warning-subtle">Stres Sedang</span></td>
                            </tr>
                            <tr>
                                <td>27 – 40</td>
                                <td class="text-end"><span class="badge bg-danger-subtle">Stres Tinggi</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card p-4">
                <h6 class="fw-bold mb-2" style="color: var(--primary);">Layanan Dukungan & Bantuan</h6>
                <p class="small text-muted mb-2" style="line-height: 1.55;">
                    Jika beban stres atau kecemasan Anda terasa berat dan mengganggu produktivitas, ketahuilah bahwa Anda tidak sendirian. Jangan ragu menghubungi profesional konseling.
                </p>
                <p class="small text-muted mb-0" style="line-height: 1.55;">
                    Layanan Konseling Jiwa Kemenkes RI: <strong class="text-primary">119 (ext. 8)</strong> atau kunjungi fasilitas kesehatan terdekat.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
