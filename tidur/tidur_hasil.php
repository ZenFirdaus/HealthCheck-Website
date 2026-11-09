<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/tidur_helper.php';

$res = null;

if (isset($_GET['id']) && (int)$_GET['id'] > 0) {
    $record = assert_record_access($pdo, 'cek_kualitas_tidur', 'id_tidur', (int)$_GET['id']);
    $durasi = (float)$record['durasi_jam'];
    $latensi = (int)$record['latensi_menit'];
    $awakenings = (int)$record['frekuensi_terbangun'];
    $screen = $record['penggunaan_gawai'] ?? 'tidak';
    $freshness = $record['kesegaran_bangun'] ?? 'cukup_segar';

    $calc = calculate_sleep_habit_score($durasi, $latensi, $awakenings, $screen, $freshness);

    $res = [
        'id_tidur'        => (int)$record['id_tidur'],
        'user_id'         => $record['user_id'] ? (int)$record['user_id'] : null,
        'nama'            => $record['nama_user'],
        'durasi_jam'      => $durasi,
        'latensi_menit'   => $latensi,
        'awakenings'      => $awakenings,
        'efisiensi_tidur' => (float)$record['efisiensi_tidur'],
        'skor'            => (int)$record['skor_kebiasaan'],
        'kategori'        => $record['kategori_tidur'],
        'badge'           => $calc['badge'],
        'warna'           => $calc['color'],
        'saran'           => $record['saran'] ?? $calc['advice']
    ];
} else {
    $res = $_SESSION['sleep_result'] ?? null;
    if ($res) {
        if (isset($res['user_id']) && $res['user_id'] !== null && !can_access_user_data((int)$res['user_id'])) {
            http_response_code(403);
            die("403 Forbidden: Anda tidak memiliki hak akses terhadap hasil pemeriksaan ini.");
        }
    } else {
        header("Location: tidur_form.php");
        exit;
    }
}

$pageTitle = 'Hasil Evaluasi Pola Tidur — HealthCheck';
$activePage = 'tidur';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="tidur_form.php" class="text-decoration-none">Pola Tidur</a></li>
            <li class="breadcrumb-item active" aria-current="page">Hasil Evaluasi</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card overflow-hidden mb-4">
                <div class="card-editorial-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0" style="color: var(--primary);">Hasil Evaluasi Pola & Higiene Tidur</h5>
                    <span class="badge border px-3 py-1 small" style="background-color: var(--light-bg); color: var(--primary); border-color: var(--card-border) !important;">
                        <?= htmlspecialchars($res['nama']) ?>
                    </span>
                </div>

                <div class="p-4 p-md-5">
                    <!-- Skor Utama -->
                    <div class="metric-hero-box">
                        <span class="text-uppercase fw-semibold small text-muted" style="letter-spacing: 0.05em;">Indeks Efisiensi Waktu Tidur</span>
                        <div class="metric-number my-1 text-<?= $res['warna'] ?>">
                            <?= number_format($res['efisiensi_tidur'], 1) ?> <span class="fs-4 fw-normal text-muted">%</span>
                        </div>
                        <div class="h6 fw-semibold mb-0">
                            Status Kebiasaan: <span class="badge bg-<?= $res['warna'] ?>-subtle px-3 py-1"><?= htmlspecialchars($res['kategori']) ?></span>
                        </div>
                    </div>

                    <!-- Parameter Detail Rinci -->
                    <div class="row g-3 p-3 rounded-3 mb-4 text-center" style="background-color: var(--light-bg); border: 1px solid var(--border);">
                        <div class="col-4">
                            <div class="small text-muted">Durasi Tidur</div>
                            <div class="fw-bold fs-6 text-dark"><?= $res['durasi_jam'] ?> Jam</div>
                        </div>
                        <div class="col-4 border-start border-end" style="border-color: var(--border) !important;">
                            <div class="small text-muted">Waktu Tertidur</div>
                            <div class="fw-bold fs-6 text-dark"><?= $res['latensi_menit'] ?> Menit</div>
                        </div>
                        <div class="col-4">
                            <div class="small text-muted">Terbangun Malam</div>
                            <div class="fw-bold fs-6 text-primary"><?= $res['awakenings'] ?> Kali</div>
                        </div>
                    </div>

                    <!-- Saran & Rekomendasi Higiene Tidur -->
                    <div class="p-4 mb-4 rounded-3" style="background-color: var(--surface); border: 1px solid var(--border);">
                        <h6 class="fw-bold mb-2" style="color: var(--primary);">Evaluasi & Panduan Higiene Tidur</h6>
                        <p class="small text-muted mb-3" style="line-height: 1.6;">
                            <?= htmlspecialchars($res['saran']) ?>
                        </p>
                        <div class="p-3 rounded-2 small" style="background-color: var(--light-bg); border-left: 3px solid var(--teal); color: var(--dark);">
                            <strong>Tips Higiene Tidur Sehat (Sleep Hygiene):</strong>
                            <ul class="mb-0 mt-1 ps-3 text-muted" style="line-height: 1.55;">
                                <li>Usahakan bangun dan tidur pada jam yang sama setiap hari, termasuk di akhir pekan.</li>
                                <li>Matikan notifikasi dan jauhkan layar ponsel setidaknya 30 menit sebelum tidur untuk menjaga produksi alami hormon melatonin.</li>
                                <li>Pastikan suhu kamar sejuk (sekitar 20–24°C) dan minimalkan cahaya yang mengganggu.</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Disclaimer Box -->
                    <div class="disclaimer-box m-0">
                        <strong class="d-block" style="color: var(--primary);">Catatan Instrumen & Batasan Medis</strong>
                        <span class="small text-muted" style="line-height: 1.5; display: block;">
                            Evaluasi ini merupakan penapisan edukasi mandiri (Sleep Habit Assessment) dan bukan instrumen kuesioner resmi berhak cipta PSQI maupun pemeriksaan laboratorium tidur (polisomnografi). Apabila Anda mengalami gangguan tidur kronis atau sleep apnea, segera konsultasikan dengan dokter spesialis saraf atau pulmonologi.
                        </span>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="d-flex flex-wrap gap-2 mt-4 pt-2">
                        <a href="tidur_form.php" class="btn btn-primary-health px-4">
                            Uji Ulang
                        </a>
                        <a href="../mental/mental_form.php" class="btn btn-lavender px-4">
                            Skrining Kesehatan Mental
                        </a>
                        <a href="../index.php" class="btn btn-link ms-auto text-decoration-none" style="color: var(--muted);">Kembali ke Beranda</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Related Tools -->
        <div class="col-lg-4">
            <div class="card p-4 mb-4">
                <h6 class="fw-bold mb-3" style="color: var(--primary);">Rentang Kualitas Tidur</h6>
                <div class="table-responsive">
                    <table class="table table-sm small align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th class="text-end">Efisiensi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge bg-success-subtle">Sangat Baik</span></td>
                                <td class="text-end">&ge; 85%</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-info-subtle">Cukup Baik</span></td>
                                <td class="text-end">75 – 84%</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-warning-subtle">Kurang Optimal</span></td>
                                <td class="text-end">65 – 74%</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-danger-subtle">Perlu Perhatian</span></td>
                                <td class="text-end">&lt; 65%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card p-4">
                <h6 class="fw-bold mb-2" style="color: var(--primary);">Hubungan Tidur & Metabolisme</h6>
                <p class="small text-muted mb-0" style="line-height: 1.55;">
                    Kurang tidur kronis (<6 jam/malam) memicu peningkatan hormon kortisol dan ghrelin yang merangsang rasa lapar berlebih, serta menurunkan sensitivitas insulin yang dapat memengaruhi berat badan Anda.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
