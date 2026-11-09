<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/stres_helper.php';

$res = null;

if (isset($_GET['id']) && (int)$_GET['id'] > 0) {
    $record = assert_record_access($pdo, 'cek_stres', 'id_stres', (int)$_GET['id']);
    $skor = (int)$record['skor_stres'];

    if ($skor <= 13) {
        $badge = 'Stres Rendah';
        $color = 'success';
    } elseif ($skor <= 26) {
        $badge = 'Stres Sedang';
        $color = 'warning';
    } else {
        $badge = 'Stres Tinggi';
        $color = 'danger';
    }

    $res = [
        'id_stres'      => (int)$record['id_stres'],
        'user_id'       => $record['user_id'] ? (int)$record['user_id'] : null,
        'nama'          => $record['nama_user'],
        'skor'          => $skor,
        'tingkat'       => $record['tingkat_stres'],
        'badge'         => $badge,
        'warna'         => $color,
        'saran'         => $record['saran']
    ];
} else {
    $res = $_SESSION['stress_result'] ?? null;
    if ($res) {
        if (isset($res['user_id']) && $res['user_id'] !== null && !can_access_user_data((int)$res['user_id'])) {
            http_response_code(403);
            die("403 Forbidden: Anda tidak memiliki hak akses terhadap hasil pemeriksaan ini.");
        }
    } else {
        header("Location: stres_form.php");
        exit;
    }
}

$pageTitle = 'Hasil Penilaian Tingkat Stres (PSS-10) — HealthCheck';
$activePage = 'stres';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="stres_form.php" class="text-decoration-none">Tingkat Stres</a></li>
            <li class="breadcrumb-item active" aria-current="page">Hasil Evaluasi PSS</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card overflow-hidden mb-4">
                <div class="card-editorial-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0" style="color: var(--primary);">Hasil Evaluasi Tingkat Stres (PSS-10)</h5>
                    <span class="badge border px-3 py-1 small" style="background-color: var(--light-bg); color: var(--primary); border-color: var(--card-border) !important;">
                        <?= htmlspecialchars($res['nama']) ?>
                    </span>
                </div>

                <div class="p-4 p-md-5">
                    <!-- Skor Utama -->
                    <div class="metric-hero-box">
                        <span class="text-uppercase fw-semibold small text-muted" style="letter-spacing: 0.05em;">Total Skor Persepsi Stres</span>
                        <div class="metric-number my-1 text-<?= $res['warna'] ?>">
                            <?= $res['skor'] ?> <span class="fs-4 fw-normal text-muted">/ 40</span>
                        </div>
                        <div class="h6 fw-semibold mb-0">
                            Tingkat: <span class="badge bg-<?= $res['warna'] ?>-subtle px-3 py-1"><?= htmlspecialchars($res['tingkat']) ?></span>
                        </div>
                    </div>

                    <!-- Saran & Panduan Refleksi -->
                    <div class="p-4 mb-4 rounded-3 border" style="background-color: var(--surface); border-color: var(--border) !important;">
                        <h6 class="fw-bold mb-2" style="color: var(--primary);">Refleksi & Langkah Perhatian Diri</h6>
                        <p class="small text-muted mb-3" style="line-height: 1.6;">
                            <?= htmlspecialchars($res['saran']) ?>
                        </p>
                        <div class="p-3 rounded-2 small" style="background-color: var(--light-bg); border-left: 3px solid var(--lavender); color: var(--dark);">
                            <strong style="color: var(--primary);">Teknik Regulasi Stres Harian:</strong>
                            <ul class="mb-0 mt-1 ps-3 text-muted" style="line-height: 1.55;">
                                <li>Latihan pernapasan perlahan (metode 4-7-8) saat menghadapi situasi tegang mendadak.</li>
                                <li>Fokus pada hal-hal yang berada dalam kendali langsung Anda (lingkaran pengaruh), dan lepaskan kekhawatiran berlebih pada hal di luar kendali.</li>
                                <li>Jaga kontak sosial aktif dan ceritakan perasaan Anda kepada orang terpercaya.</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Hotline Krisis jika skor tinggi -->
                    <?php if ($res['skor'] >= 27): ?>
                        <div class="alert alert-danger small mb-4">
                            <h6 class="fw-bold mb-1"><i class="bi bi-heart-pulse-fill me-2"></i> Dukungan Profesional Kesehatan Mental</h6>
                            <p class="mb-2">Jika Anda merasa sangat tertekan atau beban emosional terasa berat untuk ditanggung sendiri, mencari teman bicara profesional adalah tanda keberanian dan langkah bijak.</p>
                            <div class="fw-semibold">
                                Layanan Bantuan Kemenkes RI: Hubungi <strong>119 (ext. 8)</strong> atau kunjungi puskesmas/klinik dengan layanan psikolog klinis terdekat.
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Disclaimer Box -->
                    <div class="disclaimer-box m-0">
                        <strong class="d-block" style="color: var(--primary);">Batas Penggunaan Medis</strong>
                        <span class="small text-muted" style="line-height: 1.5; display: block;">
                            Instrumen Perceived Stress Scale (PSS-10) ini adalah instrumen penapisan psikometri reflektif untuk mengukur persepsi beban stres, <strong>bukan vonis diagnosis klinis psikiatrik</strong>. Evaluasi formal kejiwaan hanya dapat ditegakkan melalui wawancara klinis oleh psikolog atau psikiater.
                        </span>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="d-flex flex-wrap gap-2 mt-4 pt-2">
                        <a href="stres_form.php" class="btn btn-primary-health px-4">
                            Uji Ulang
                        </a>
                        <a href="../mental/mental_form.php" class="btn btn-lavender px-4">
                            Lanjut Skrining Mood (PHQ-4)
                        </a>
                        <a href="../index.php" class="btn btn-link ms-auto text-decoration-none" style="color: var(--muted);">Kembali ke Beranda</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Related Tools -->
        <div class="col-lg-4">
            <div class="card p-4 mb-4">
                <h6 class="fw-bold mb-3" style="color: var(--primary);">Interpretasi Skor PSS-10</h6>
                <div class="table-responsive">
                    <table class="table table-sm small align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Rentang</th>
                                <th class="text-end">Tingkat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>0 – 13</td>
                                <td class="text-end"><span class="badge bg-success-subtle">Rendah</span></td>
                            </tr>
                            <tr>
                                <td>14 – 26</td>
                                <td class="text-end"><span class="badge bg-warning-subtle">Sedang</span></td>
                            </tr>
                            <tr>
                                <td>27 – 40</td>
                                <td class="text-end"><span class="badge bg-danger-subtle">Tinggi</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card p-4">
                <h6 class="fw-bold mb-2" style="color: var(--primary);">Instrumen Tervalidasi</h6>
                <p class="small text-muted mb-0" style="line-height: 1.55;">
                    Dikembangkan oleh Dr. Sheldon Cohen dkk. pada tahun 1983, PSS-10 adalah salah satu instrumen paling banyak digunakan dalam riset perilaku kesehatan dunia untuk mengukur seberapa tidak terduga, tidak terkontrol, dan berlebihannya beban kehidupan yang dirasakan seseorang.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
