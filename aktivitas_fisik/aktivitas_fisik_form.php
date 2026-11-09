<?php
$pageTitle = 'Evaluasi Aktivitas Fisik (IPAQ Short Form) — HealthCheck';
$activePage = 'aktivitas_fisik';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/aktivitas_helper.php';

$profile = get_current_user_profile($pdo);
$defaultName = $profile['nama_lengkap'] ?? ($_SESSION['username'] ?? '');
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Kalkulator</a></li>
            <li class="breadcrumb-item active" aria-current="page">Evaluasi Aktivitas Fisik</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-7">
            <div class="card overflow-hidden">
                <div class="card-editorial-header">
                    <h4 class="card-heading mb-1">Evaluasi Aktivitas Fisik (IPAQ)</h4>
                    <p class="small mb-0 text-muted">Kuesioner penapisan resmi International Physical Activity Questionnaire (7 hari terakhir) untuk mengukur pengeluaran energi MET-menit.</p>
                </div>

                <div class="p-4 p-md-5">
                    <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'invalid_input'): ?>
                        <div class="alert alert-danger small mb-4">
                            <i class="bi bi-exclamation-circle-fill me-2"></i> Harap periksa input Anda. Pastikan hari berada di antara 0–7 hari dan durasi per hari realistis.
                        </div>
                    <?php endif; ?>

                    <form action="aktivitas_fisik_proses.php" method="POST" id="ipaqForm" onsubmit="return validateIpaqForm()">
                        <?= csrf_field() ?>
                        <div class="mb-4">
                            <label class="form-label" for="nama_user">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_user" id="nama_user" class="form-control" placeholder="Contoh: Budi Santoso" value="<?= htmlspecialchars($defaultName) ?>" required>
                        </div>

                        <!-- Bagian 1: Aktivitas Berat -->
                        <div class="p-3 mb-4 rounded-3 border" style="background-color: var(--light-bg); border-color: var(--border) !important;">
                            <div class="fw-bold small text-primary mb-1">1. Aktivitas Fisik Berat (Vigorous Activity)</div>
                            <p class="small text-muted mb-3" style="font-size:0.8rem;">Aktivitas yang memicu napas tersengal-sengal dan detak jantung kencang (cth: lari cepat, aerobik intens, angkat beban berat, sepak bola) minimal 10 menit tanpa henti.</p>

                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label small" for="hari_berat">Jumlah Hari (7 Hari Lalu)</label>
                                    <input type="number" name="hari_berat" id="hari_berat" class="form-control" placeholder="0" min="0" max="7" value="0" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small" for="menit_berat">Rata-rata Menit / Hari</label>
                                    <input type="number" name="menit_berat" id="menit_berat" class="form-control" placeholder="0" min="0" max="960" value="0" required>
                                </div>
                            </div>
                        </div>

                        <!-- Bagian 2: Aktivitas Sedang -->
                        <div class="p-3 mb-4 rounded-3 border" style="background-color: var(--light-bg); border-color: var(--border) !important;">
                            <div class="fw-bold small text-primary mb-1">2. Aktivitas Fisik Sedang (Moderate Activity)</div>
                            <p class="small text-muted mb-3" style="font-size:0.8rem;">Aktivitas fisik yang membuat napas sedikit lebih cepat dari biasanya (cth: bersepeda santai, senam ringan, berenang santai) minimal 10 menit (di luar berjalan kaki).</p>

                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label small" for="hari_sedang">Jumlah Hari (7 Hari Lalu)</label>
                                    <input type="number" name="hari_sedang" id="hari_sedang" class="form-control" placeholder="0" min="0" max="7" value="0" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small" for="menit_sedang">Rata-rata Menit / Hari</label>
                                    <input type="number" name="menit_sedang" id="menit_sedang" class="form-control" placeholder="0" min="0" max="960" value="0" required>
                                </div>
                            </div>
                        </div>

                        <!-- Bagian 3: Berjalan Kaki -->
                        <div class="p-3 mb-4 rounded-3 border" style="background-color: var(--light-bg); border-color: var(--border) !important;">
                            <div class="fw-bold small text-primary mb-1">3. Berjalan Kaki (Walking)</div>
                            <p class="small text-muted mb-3" style="font-size:0.8rem;">Berjalan kaki minimal 10 menit terus menerus (berjalan ke halte/stasiun, jalan cepat sore hari, jalan di tempat kerja/kampus).</p>

                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label small" for="hari_jalan">Jumlah Hari (7 Hari Lalu)</label>
                                    <input type="number" name="hari_jalan" id="hari_jalan" class="form-control" placeholder="5" min="0" max="7" value="5" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small" for="menit_jalan">Rata-rata Menit / Hari</label>
                                    <input type="number" name="menit_jalan" id="menit_jalan" class="form-control" placeholder="30" min="0" max="960" value="30" required>
                                </div>
                            </div>
                        </div>

                        <!-- Bagian 4: Waktu Duduk -->
                        <div class="mb-4">
                            <label class="form-label" for="jam_duduk">Rata-rata Waktu Duduk Harian pada Hari Kerja (Jam) <span class="text-danger">*</span></label>
                            <input type="number" step="0.5" name="jam_duduk" id="jam_duduk" class="form-control" placeholder="7.0" min="0" max="24" value="7.0" required>
                            <div class="form-text small">Akumulasi duduk di meja kerja, belajar, perjalanan kendaraan, dan bersantai di depan layar.</div>
                        </div>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary-health px-4 py-2">
                                Hitung Skor MET Aktivitas
                            </button>
                            <a href="../index.php" class="btn btn-outline-secondary px-4 py-2">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="col-lg-5">
            <div class="card p-4 mb-4">
                <h6 class="fw-bold mb-3" style="color: var(--primary);">Tentang Standar IPAQ</h6>
                <p class="small text-muted mb-2" style="line-height: 1.55;">
                    <em>International Physical Activity Questionnaire (IPAQ)</em> adalah instrumen resmi yang dikembangkan oleh Komite Riset IPAQ bersama Organisasi Kesehatan Dunia (WHO) untuk memantau beban aktivitas fisik lintas populasi global.
                </p>
                <p class="small text-muted mb-0" style="line-height: 1.55;">
                    Aktivitas dihitung dalam satuan <strong>MET-menit/minggu</strong>: mengalikan rasio laju metabolik (MET) dengan durasi menit dan frekuensi hari dalam seminggu.
                </p>
            </div>

            <div class="card p-4">
                <h6 class="fw-bold mb-2" style="color: var(--primary);">Target Rekomendasi WHO</h6>
                <p class="small text-muted mb-0" style="line-height: 1.55;">
                    WHO merekomendasikan orang dewasa berusia 18–64 tahun untuk mengumpulkan minimal <strong>150–300 menit</strong> aktivitas fisik intensitas sedang, atau <strong>75–150 menit</strong> aktivitas fisik berat setiap minggu.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function validateIpaqForm() {
    const vd = parseInt(document.getElementById('hari_berat').value, 10);
    const md = parseInt(document.getElementById('hari_sedang').value, 10);
    const wd = parseInt(document.getElementById('hari_jalan').value, 10);
    const sit = parseFloat(document.getElementById('jam_duduk').value);

    if (isNaN(vd) || vd < 0 || vd > 7 || isNaN(md) || md < 0 || md > 7 || isNaN(wd) || wd < 0 || wd > 7) {
        alert("Jumlah hari aktivitas harus berada dalam rentang 0 sampai 7 hari.");
        return false;
    }
    if (isNaN(sit) || sit < 0 || sit > 24) {
        alert("Waktu duduk harian harus berada di antara 0 sampai 24 jam.");
        return false;
    }
    return true;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
