<?php
$pageTitle = 'Rincian Pemeriksaan — HealthCheck';
$activePage = 'history';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/login_cek.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/history_helper.php';

$userId = current_user_id();
if (!$userId) {
    header("Location: ../auth/login.php?pesan=belum_login");
    exit;
}

$type = trim($_GET['type'] ?? '');
$id = (int)($_GET['id'] ?? 0);

$meta = get_tool_metadata();
if (!isset($meta[$type]) || $id <= 0) {
    http_response_code(404);
    die("404 Not Found: Jenis alat atau ID pemeriksaan tidak valid.");
}

$toolInfo = $meta[$type];
$table = $toolInfo['table'];
$idCol = $toolInfo['id_column'];

// Strict ownership verification (IDOR protection)
$record = assert_record_access($pdo, $table, $idCol, $id);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="history.php" class="text-decoration-none">Riwayat</a></li>
            <li class="breadcrumb-item active" aria-current="page">Rincian #<?= $id ?></li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card overflow-hidden mb-4">
                <div class="card-editorial-header d-flex justify-content-between align-items-center">
                    <div>
                        <span class="tool-category mb-0" style="color: var(--<?= $toolInfo['color'] ?>); font-size: 0.72rem;">
                            <?= htmlspecialchars($toolInfo['category_label']) ?>
                        </span>
                        <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($toolInfo['title']) ?></h5>
                    </div>
                    <span class="badge border px-3 py-1 small" style="background-color: var(--light-bg); color: var(--primary); border-color: var(--card-border) !important;">
                        ID: #<?= $id ?>
                    </span>
                </div>

                <div class="p-4 p-md-5">
                    <!-- Tanggal & Waktu Snapshot -->
                    <div class="p-3 mb-4 rounded-3 border d-flex justify-content-between align-items-center" style="background-color: var(--light-bg); border-color: var(--border) !important;">
                        <span class="small text-muted"><i class="bi bi-clock me-1"></i> Waktu Pemeriksaan:</span>
                        <strong class="text-dark small"><?= date('d F Y, H:i', strtotime($record['created_at'])) ?> WIB</strong>
                    </div>

                    <!-- Snapshot Parameter Masukan & Luaran Spesifik per Alat -->
                    <h6 class="fw-bold mb-3" style="color: var(--primary);">Data Snapshot Pemeriksaan</h6>

                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-sm small align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 45%;">Parameter / Indikator</th>
                                    <th>Nilai Tercatat (Saat Pemeriksaan)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Nama Pengguna</td>
                                    <td><strong><?= htmlspecialchars($record['nama_user']) ?></strong></td>
                                </tr>

                                <?php if ($type === 'bmi'): ?>
                                    <tr><td>Umur</td><td><?= $record['umur'] ?> Tahun</td></tr>
                                    <tr><td>Tinggi Badan</td><td><?= $record['tinggi_badan'] ?> cm</td></tr>
                                    <tr><td>Berat Badan</td><td><?= $record['berat_badan'] ?> kg</td></tr>
                                    <tr><td>Nilai Skor BMI</td><td><strong class="text-primary fs-6"><?= $record['bmi'] ?> kg/m²</strong></td></tr>
                                    <tr><td>Klasifikasi Standar Asia</td><td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($record['kondisi']) ?></span></td></tr>

                                <?php elseif ($type === 'bmr'): ?>
                                    <tr><td>Jenis Kelamin</td><td><?= $record['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td></tr>
                                    <tr><td>Umur / BB / TB</td><td><?= $record['umur'] ?> thn / <?= $record['berat_badan'] ?> kg / <?= $record['tinggi_badan'] ?> cm</td></tr>
                                    <tr><td>Laju Metabolisme Basal (BMR)</td><td><strong class="text-primary"><?= number_format((float)$record['bmr'], 0, ',', '.') ?> kkal/hari</strong></td></tr>
                                    <tr><td>Tingkat Aktivitas</td><td><?= htmlspecialchars($record['aktivitas']) ?></td></tr>
                                    <tr><td>Total Kebutuhan Harian (TDEE)</td><td><strong class="text-primary fs-6"><?= number_format((float)$record['tdee'], 0, ',', '.') ?> kkal/hari</strong></td></tr>
                                    <?php if (!empty($record['target_kalori'])): ?>
                                        <tr><td>Target Sasaran Kalori</td><td><strong><?= number_format((float)$record['target_kalori'], 0, ',', '.') ?> kkal</strong> (<?= htmlspecialchars($record['target_tujuan'] ?? 'maintain') ?>)</td></tr>
                                        <tr><td>Distribusi Makronutrien</td><td>Karbo: <?= $record['karbo_gram'] ?>g | Protein: <?= $record['protein_gram'] ?>g | Lemak: <?= $record['lemak_gram'] ?>g</td></tr>
                                    <?php endif; ?>

                                <?php elseif ($type === 'whtr'): ?>
                                    <tr><td>Tinggi Badan</td><td><?= $record['tinggi_badan'] ?> cm</td></tr>
                                    <tr><td>Lingkar Pinggang</td><td><?= $record['lingkar_pinggang'] ?> cm</td></tr>
                                    <tr><td>Rasio WHtR</td><td><strong class="text-primary fs-6"><?= number_format((float)$record['rasio_whtr'], 2) ?></strong></td></tr>
                                    <tr><td>Kategori Risiko Kardiometabolik</td><td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($record['kategori_risiko']) ?></span></td></tr>

                                <?php elseif ($type === 'body_fat'): ?>
                                    <tr><td>Umur / BB / TB</td><td><?= $record['umur'] ?> thn / <?= $record['berat_badan'] ?> kg / <?= $record['tinggi_badan'] ?> cm</td></tr>
                                    <tr><td>Lingkar Leher / Pinggang / Pinggul</td><td><?= $record['lingkar_leher'] ?> cm / <?= $record['lingkar_pinggang'] ?> cm <?= !empty($record['lingkar_pinggul']) ? "/ {$record['lingkar_pinggul']} cm" : '' ?></td></tr>
                                    <tr><td>Persentase Lemak Tubuh (US Navy)</td><td><strong class="text-primary fs-6"><?= number_format((float)$record['body_fat_percent'], 1) ?>%</strong></td></tr>
                                    <tr><td>Massa Lemak vs Massa Bebas Lemak</td><td>Lemak: <?= $record['fat_mass_kg'] ?> kg | Bebas Lemak: <?= $record['lean_mass_kg'] ?> kg</td></tr>
                                    <tr><td>Kategori (ACE)</td><td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($record['kategori']) ?></span></td></tr>

                                <?php elseif ($type === 'cairan'): ?>
                                    <tr><td>Berat Badan</td><td><?= $record['berat_badan'] ?> kg</td></tr>
                                    <tr><td>Aktivitas & Iklim</td><td><?= htmlspecialchars($record['tingkat_aktivitas'] ?? '-') ?> / <?= htmlspecialchars($record['kondisi_iklim'] ?? '-') ?></td></tr>
                                    <tr><td>Kebutuhan Air Total</td><td><strong class="text-primary fs-6"><?= $record['kebutuhan_liter'] ?> Liter/hari</strong> (<?= number_format((float)$record['kebutuhan_ml'], 0, ',', '.') ?> ml)</td></tr>
                                    <tr><td>Porsi Gelas Standar</td><td>~<?= $record['kebutuhan_gelas'] ?> Gelas (@ 250 ml)</td></tr>

                                <?php elseif ($type === 'tekanan_darah'): ?>
                                    <tr><td>Tekanan Sistolik / Diastolik</td><td><strong class="text-primary fs-5"><?= $record['sistolik'] ?> / <?= $record['diastolik'] ?> mmHg</strong></td></tr>
                                    <tr><td>Tekanan Nadi (Pulse Pressure)</td><td><?= (int)$record['sistolik'] - (int)$record['diastolik'] ?> mmHg</td></tr>
                                    <?php if (!empty($record['detak_nadi'])): ?><tr><td>Denyut Nadi</td><td><?= $record['detak_nadi'] ?> BPM</td></tr><?php endif; ?>
                                    <tr><td>Kategori Klinis</td><td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($record['kategori']) ?></span></td></tr>
                                    <?php if (!empty($record['catatan'])): ?><tr><td>Catatan Kondisi</td><td><?= htmlspecialchars($record['catatan']) ?></td></tr><?php endif; ?>

                                <?php elseif ($type === 'jantung'): ?>
                                    <tr><td>Umur</td><td><?= $record['umur'] ?> Tahun</td></tr>
                                    <tr><td>Resting Heart Rate (RHR)</td><td><strong class="text-primary fs-6"><?= $record['resting_hr'] ?> BPM</strong></td></tr>
                                    <tr><td>Perkiraan Max HR (Tanaka)</td><td><?= $record['max_hr'] ?> BPM</td></tr>
                                    <tr><td>Kategori Kebugaran Nadi</td><td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($record['kategori']) ?></span></td></tr>

                                <?php elseif ($type === 'zona_jantung'): ?>
                                    <tr><td>Umur / RHR / Max HR</td><td><?= $record['umur'] ?> thn / <?= $record['resting_hr'] ?> BPM / <?= $record['max_hr'] ?> BPM</td></tr>
                                    <tr><td>Metode Formula</td><td><?= ucfirst($record['metode']) ?></td></tr>
                                    <tr><td>Rentang Zona 1 (Pemulihan)</td><td><?= $record['zona_1_min'] ?> – <?= $record['zona_1_max'] ?> BPM</td></tr>
                                    <tr><td>Rentang Zona 2 (Fat Burn)</td><td><?= $record['zona_2_min'] ?> – <?= $record['zona_2_max'] ?> BPM</td></tr>
                                    <tr><td>Rentang Zona 3 (Aerobik)</td><td><?= $record['zona_3_min'] ?> – <?= $record['zona_3_max'] ?> BPM</td></tr>
                                    <tr><td>Rentang Zona 4 (Threshold)</td><td><?= $record['zona_4_min'] ?> – <?= $record['zona_4_max'] ?> BPM</td></tr>
                                    <tr><td>Rentang Zona 5 (Maksimal)</td><td><?= $record['zona_5_min'] ?> – <?= $record['zona_5_max'] ?> BPM</td></tr>

                                <?php elseif ($type === 'tidur'): ?>
                                    <tr><td>Durasi Tidur / Latensi</td><td><?= $record['durasi_jam'] ?> Jam / <?= $record['latensi_menit'] ?> Menit</td></tr>
                                    <tr><td>Frekuensi Terbangun / Gawai</td><td><?= $record['frekuensi_terbangun'] ?> kali / <?= htmlspecialchars($record['penggunaan_gawai'] ?? '-') ?></td></tr>
                                    <tr><td>Indeks Efisiensi Tidur</td><td><strong class="text-primary fs-6"><?= $record['efisiensi_tidur'] ?>%</strong></td></tr>
                                    <tr><td>Skor Kebiasaan Tidur</td><td><?= $record['skor_kebiasaan'] ?> / 15</td></tr>
                                    <tr><td>Kategori Kualitas Tidur</td><td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($record['kategori_tidur']) ?></span></td></tr>

                                <?php elseif ($type === 'aktivitas_fisik'): ?>
                                    <tr><td>Hari & Menit Aktivitas Berat</td><td><?= $record['hari_berat'] ?> hari (<?= $record['menit_berat'] ?> mnt/hari)</td></tr>
                                    <tr><td>Hari & Menit Aktivitas Sedang</td><td><?= $record['hari_sedang'] ?> hari (<?= $record['menit_sedang'] ?> mnt/hari)</td></tr>
                                    <tr><td>Hari & Menit Berjalan Kaki</td><td><?= $record['hari_jalan'] ?> hari (<?= $record['menit_jalan'] ?> mnt/hari)</td></tr>
                                    <tr><td>Waktu Duduk Harian</td><td><?= $record['jam_duduk'] ?> Jam/hari</td></tr>
                                    <tr><td>Total Pengeluaran Energi (IPAQ)</td><td><strong class="text-primary fs-6"><?= number_format((float)$record['total_met_menit'], 0, ',', '.') ?> MET-menit/mgg</strong></td></tr>
                                    <tr><td>Kategori Tingkat Aktivitas</td><td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($record['kategori_aktivitas']) ?></span></td></tr>

                                <?php elseif ($type === 'stres'): ?>
                                    <tr><td>Total Skor Stres (PSS-10)</td><td><strong class="text-primary fs-5"><?= $record['skor_stres'] ?> / 40</strong></td></tr>
                                    <tr><td>Tingkat Persepsi Stres</td><td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($record['tingkat_stres']) ?></span></td></tr>

                                <?php elseif ($type === 'mental'): ?>
                                    <tr><td>Subskor Kecemasan (GAD-2)</td><td><?= $record['skor_cemas'] ?> / 6</td></tr>
                                    <tr><td>Subskor Depresi/Mood (PHQ-2)</td><td><?= $record['skor_depresi'] ?> / 6</td></tr>
                                    <tr><td>Total Skor PHQ-4</td><td><strong class="text-primary fs-5"><?= $record['total_skor'] ?> / 12</strong></td></tr>
                                    <tr><td>Tingkat Indikasi</td><td><span class="badge bg-primary-subtle text-primary"><?= htmlspecialchars($record['tingkat']) ?></span></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Rekomendasi & Catatan Edukatif -->
                    <?php if (!empty($record['saran']) || !empty($record['anjuran']) || !empty($record['catatan'])): ?>
                        <div class="p-4 mb-4 rounded-3" style="background-color: var(--light-bg); border: 1px solid var(--border);">
                            <h6 class="fw-bold mb-2" style="color: var(--primary);">Catatan & Rekomendasi Terlampir</h6>
                            <p class="small text-muted mb-0" style="line-height: 1.6;">
                                <?= htmlspecialchars($record['saran'] ?? $record['anjuran'] ?? $record['catatan']) ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Disclaimer Medis -->
                    <div class="disclaimer-box m-0">
                        <strong class="d-block" style="color: var(--primary);">Disclaimer Medis Mandiri</strong>
                        <span class="small text-muted" style="line-height: 1.5; display: block;">
                            Data ini merupakan arsip snapshot historis pada saat pemeriksaan dilakukan. HealthCheck adalah platform evaluasi kesehatan mandiri dan bukan diagnosis medis klinis. Konsultasikan dengan dokter spesialis untuk rencana pengobatan formal.
                        </span>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="d-flex flex-wrap gap-2 mt-4 pt-2">
                        <a href="history.php" class="btn btn-primary-health px-4">
                            <i class="bi bi-arrow-left me-1"></i> Kembali ke Riwayat
                        </a>
                        <a href="<?= $toolInfo['form_url'] ?>" class="btn btn-secondary-soft px-4">
                            Lakukan Uji Ulang
                        </a>
                        <a href="report_download.php" class="btn btn-outline-secondary ms-auto px-4">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Ekspor PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
