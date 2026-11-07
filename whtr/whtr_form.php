<?php
$pageTitle = 'Kalkulator Rasio Lingkar Pinggang ke Tinggi (WHtR) — HealthCheck';
$activePage = 'whtr';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/whtr_helper.php';

$profile = get_current_user_profile($pdo);
$defaultName = $profile['nama_lengkap'] ?? ($_SESSION['username'] ?? '');
$defaultJk = $profile['jenis_kelamin'] ?? 'L';
$defaultTb = !empty($profile['tinggi_badan']) ? (float)$profile['tinggi_badan'] : '';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Kalkulator</a></li>
            <li class="breadcrumb-item active" aria-current="page">Kalkulator WHtR</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-7">
            <div class="card overflow-hidden">
                <div class="card-editorial-header">
                    <h4 class="card-heading mb-1">Kalkulator Rasio Lingkar Pinggang ke Tinggi (WHtR)</h4>
                    <p class="small mb-0" style="color: var(--muted);">Evaluasi distribusi lemak perut (viseral) dan risiko kardiometabolik mandiri.</p>
                </div>

                <div class="p-4 p-md-5">
                    <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'invalid_input'): ?>
                        <div class="alert alert-danger small mb-4">
                            <i class="bi bi-exclamation-circle-fill me-2"></i> Harap masukkan tinggi badan (50–250 cm) dan lingkar pinggang (30–200 cm) yang valid.
                        </div>
                    <?php endif; ?>

                    <form action="whtr_proses.php" method="POST" id="whtrForm" onsubmit="return validateWhtrForm()">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label" for="nama_user">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_user" id="nama_user" class="form-control" placeholder="Contoh: Budi Santoso" value="<?= htmlspecialchars($defaultName) ?>" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="jenis_kelamin">Jenis Kelamin</label>
                                <select name="jenis_kelamin" id="jenis_kelamin" class="form-select">
                                    <option value="L" <?= $defaultJk === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                    <option value="P" <?= $defaultJk === 'P' ? 'selected' : '' ?>>Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="tinggi_badan">Tinggi Badan (cm) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.1" name="tinggi_badan" id="tinggi_badan" class="form-control" placeholder="170" min="50" max="250" value="<?= $defaultTb !== '' ? htmlspecialchars((string)$defaultTb) : '' ?>" required>
                                    <span class="input-group-text">cm</span>
                                </div>
                                <div class="form-text small">Rentang: 50 - 250 cm</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="lingkar_pinggang">Lingkar Pinggang (cm) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.1" name="lingkar_pinggang" id="lingkar_pinggang" class="form-control" placeholder="78.0" min="30" max="200" required>
                                <span class="input-group-text">cm</span>
                            </div>
                            <div class="form-text small">Ukur lingkar perut sejajar pusar saat mengembuskan napas rileks (30 - 200 cm).</div>
                        </div>

                        <div class="mb-4 p-3 rounded-2 small" style="background-color: var(--light-bg); border-left: 3px solid var(--secondary); color: var(--dark);">
                            <strong style="color: var(--primary);">Aturan Emas Kesehatan (Rule of Thumb):</strong>
                            <p class="mb-0 mt-1" style="color: var(--muted); line-height: 1.55;">
                                Jaga lingkar pinggang Anda kurang dari setengah tinggi badan Anda (WHtR &lt; 0.50) untuk meminimalkan risiko diabetes dan penyakit kardiovaskular.
                            </p>
                        </div>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary-health px-4 py-2">
                                Hitung Rasio WHtR
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
                <h6 class="fw-bold mb-3" style="color: var(--primary);">Standar Klasifikasi WHtR</h6>
                <div class="table-responsive">
                    <table class="table table-sm small align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th class="text-end">Rasio WHtR</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge bg-warning-subtle">Kurang</span></td>
                                <td class="text-end">&lt; 0.40</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-success-subtle">Sehat / Ideal</span></td>
                                <td class="text-end">0.40 – 0.49</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-warning-subtle">Risiko Meningkat</span></td>
                                <td class="text-end">0.50 – 0.59</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-danger-subtle">Risiko Sangat Tinggi</span></td>
                                <td class="text-end">&ge; 0.60</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card p-4">
                <h6 class="fw-bold mb-2" style="color: var(--primary);">Keunggulan Medis WHtR</h6>
                <p class="small mb-0" style="color: var(--muted); line-height: 1.55;">
                    Rasio lingkar pinggang terhadap tinggi badan (WHtR) dinilai lebih sensitif mendeteksi penumpukan lemak viseral organ dalam dibandingkan indeks massa tubuh (BMI) murni, serta relevan untuk semua kelompok etnis dan usia.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function validateWhtrForm() {
    const tb = parseFloat(document.getElementById('tinggi_badan').value);
    const lp = parseFloat(document.getElementById('lingkar_pinggang').value);

    if (isNaN(tb) || tb < 50 || tb > 250) {
        alert("Harap masukkan tinggi badan yang valid (50 - 250 cm).");
        return false;
    }
    if (isNaN(lp) || lp < 30 || lp > 200) {
        alert("Harap masukkan lingkar pinggang yang valid (30 - 200 cm).");
        return false;
    }
    return true;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
