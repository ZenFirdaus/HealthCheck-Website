<?php
$pageTitle = 'Pemeriksaan Tekanan Darah — HealthCheck';
$activePage = 'tekanan_darah';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/tensi_helper.php';

$profile = get_current_user_profile($pdo);
$defaultName = $profile['nama_lengkap'] ?? ($_SESSION['username'] ?? '');
$calcAge = !empty($profile['tanggal_lahir']) ? calculate_age_from_dob($profile['tanggal_lahir']) : null;
$defaultUmur = ($calcAge !== null && $calcAge >= 10 && $calcAge <= 120) ? $calcAge : 30;
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Kalkulator</a></li>
            <li class="breadcrumb-item active" aria-current="page">Pemeriksaan Tekanan Darah</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-7">
            <div class="card overflow-hidden">
                <div class="card-editorial-header">
                    <h4 class="card-heading mb-1">Pemeriksaan Tekanan Darah</h4>
                    <p class="small mb-0 text-muted">Catat dan evaluasi tekanan sistolik serta diastolik berdasarkan standar AHA/ACC 2017 & Konsensus PERHI.</p>
                </div>

                <div class="p-4 p-md-5">
                    <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'invalid_input'): ?>
                        <div class="alert alert-danger small mb-4">
                            <i class="bi bi-exclamation-circle-fill me-2"></i> Harap periksa input Anda. Pastikan sistolik (70–260 mmHg) lebih besar dari diastolik (40–150 mmHg) dengan selisih minimal 15 mmHg.
                        </div>
                    <?php endif; ?>

                    <form action="tekanan_darah_proses.php" method="POST" id="bpForm" onsubmit="return validateBpForm()">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label" for="nama_user">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_user" id="nama_user" class="form-control" placeholder="Contoh: Budi Santoso" value="<?= htmlspecialchars($defaultName) ?>" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="umur">Umur (tahun) <span class="text-danger">*</span></label>
                                <input type="number" name="umur" id="umur" class="form-control" placeholder="30" min="10" max="120" value="<?= htmlspecialchars((string)$defaultUmur) ?>" required>
                                <div class="form-text small">Rentang: 10 - 120 tahun</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="detak_nadi">Denyut Nadi (BPM) <span class="text-muted small">(Opsional)</span></label>
                                <div class="input-group">
                                    <input type="number" name="detak_nadi" id="detak_nadi" class="form-control" placeholder="72" min="40" max="200">
                                    <span class="input-group-text">bpm</span>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="sistolik">Tekanan Sistolik (mmHg) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="sistolik" id="sistolik" class="form-control" placeholder="120" min="70" max="260" required>
                                    <span class="input-group-text">mmHg</span>
                                </div>
                                <div class="form-text small">Angka atas tensimeter (70–260 mmHg)</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="diastolik">Tekanan Diastolik (mmHg) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="diastolik" id="diastolik" class="form-control" placeholder="80" min="40" max="150" required>
                                    <span class="input-group-text">mmHg</span>
                                </div>
                                <div class="form-text small">Angka bawah tensimeter (40–150 mmHg)</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="catatan">Catatan Kondisi Pemeriksaan <span class="text-muted small">(Opsional)</span></label>
                            <input type="text" name="catatan" id="catatan" class="form-control" placeholder="Contoh: Pengukuran pagi hari setelah duduk istirahat 5 menit">
                        </div>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary-health px-4 py-2">
                                Evaluasi Tekanan Darah
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
                <h6 class="fw-bold mb-3" style="color: var(--primary);">Standar Klasifikasi (AHA/ACC & PERHI)</h6>
                <div class="table-responsive">
                    <table class="table table-sm small align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th class="text-end">Sistolik / Diastolik</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge bg-warning-subtle">Hipotensi</span></td>
                                <td class="text-end">&lt; 90 atau &lt; 60</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-success-subtle">Normal</span></td>
                                <td class="text-end">&lt; 120 dan &lt; 80</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-warning-subtle">Pra-Hipertensi</span></td>
                                <td class="text-end">120–129 dan &lt; 80</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-warning-subtle">Hipertensi Tk 1</span></td>
                                <td class="text-end">130–139 atau 80–89</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-danger-subtle">Hipertensi Tk 2</span></td>
                                <td class="text-end">&ge; 140 atau &ge; 90</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-danger-subtle">Krisis Hipertensi</span></td>
                                <td class="text-end">&gt; 180 dan/atau &gt; 120</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card p-4">
                <h6 class="fw-bold mb-2" style="color: var(--primary);">Petunjuk Pengukuran yang Tepat</h6>
                <p class="small text-muted mb-0" style="line-height: 1.55;">
                    Duduklah rileks dengan punggung tersandar selama minimal 5 menit sebelum pengukuran. Pastikan telapak kaki menapak rata di lantai, lengan disangga setinggi posisi jantung, dan hindari berbicara, merokok, atau minum kopi 30 menit sebelum tensi.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function validateBpForm() {
    const sis = parseInt(document.getElementById('sistolik').value, 10);
    const dia = parseInt(document.getElementById('diastolik').value, 10);
    const umur = parseInt(document.getElementById('umur').value, 10);

    if (isNaN(sis) || sis < 70 || sis > 260) {
        alert("Harap masukkan tekanan sistolik yang valid (70 - 260 mmHg).");
        return false;
    }
    if (isNaN(dia) || dia < 40 || dia > 150) {
        alert("Harap masukkan tekanan diastolik yang valid (40 - 150 mmHg).");
        return false;
    }
    if (sis <= dia) {
        alert("Tekanan sistolik (angka atas) harus selalu lebih besar dari diastolik (angka bawah).");
        return false;
    }
    if ((sis - dia) < 15) {
        alert("Selisih tekanan sistolik dan diastolik (tekanan nadi) minimal 15 mmHg.");
        return false;
    }
    if (isNaN(umur) || umur < 10 || umur > 120) {
        alert("Harap masukkan umur antara 10 - 120 tahun.");
        return false;
    }
    return true;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
