<?php
$pageTitle = 'Kalkulator Persentase Lemak Tubuh (US Navy) — HealthCheck';
$activePage = 'body_fat';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/body_fat_helper.php';

$profile = get_current_user_profile($pdo);
$defaultName = $profile['nama_lengkap'] ?? ($_SESSION['username'] ?? '');
$defaultJk = $profile['jenis_kelamin'] ?? 'L';
$calcAge = !empty($profile['tanggal_lahir']) ? calculate_age_from_dob($profile['tanggal_lahir']) : null;
$defaultUmur = ($calcAge !== null && $calcAge >= 15 && $calcAge <= 90) ? $calcAge : 25;
$defaultBb = !empty($profile['berat_badan']) ? (float)$profile['berat_badan'] : '';
$defaultTb = !empty($profile['tinggi_badan']) ? (float)$profile['tinggi_badan'] : '';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Kalkulator</a></li>
            <li class="breadcrumb-item active" aria-current="page">Kalkulator Lemak Tubuh</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-7">
            <div class="card overflow-hidden">
                <div class="card-editorial-header">
                    <h4 class="card-heading mb-1">Kalkulator Persentase Lemak Tubuh</h4>
                    <p class="small mb-0" style="color: var(--muted);">Estimasi komposisi lemak tubuh dan massa otot menggunakan metode antropometri US Navy.</p>
                </div>

                <div class="p-4 p-md-5">
                    <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'invalid_input'): ?>
                        <div class="alert alert-danger small mb-4">
                            <i class="bi bi-exclamation-circle-fill me-2"></i> Harap periksa kembali input Anda. Pastikan lingkar leher, pinggang (dan pinggul untuk wanita) serta tinggi dan berat badan berada dalam rentang anatomi yang valid.
                        </div>
                    <?php endif; ?>

                    <form action="body_fat_proses.php" method="POST" id="fatForm" onsubmit="return validateFatForm()">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label" for="nama_user">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_user" id="nama_user" class="form-control" placeholder="Contoh: Budi Santoso" value="<?= htmlspecialchars($defaultName) ?>" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="jenis_kelamin">Jenis Kelamin <span class="text-danger">*</span></label>
                                <select name="jenis_kelamin" id="jenis_kelamin" class="form-select" onchange="toggleGenderFields()" required>
                                    <option value="L" <?= $defaultJk === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                    <option value="P" <?= $defaultJk === 'P' ? 'selected' : '' ?>>Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="umur">Umur (tahun) <span class="text-danger">*</span></label>
                                <input type="number" name="umur" id="umur" class="form-control" placeholder="25" min="15" max="90" value="<?= htmlspecialchars((string)$defaultUmur) ?>" required>
                                <div class="form-text small">Rentang: 15 - 90 tahun</div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="berat_badan">Berat Badan (kg) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.1" name="berat_badan" id="berat_badan" class="form-control" placeholder="68.0" min="20" max="300" value="<?= $defaultBb !== '' ? htmlspecialchars((string)$defaultBb) : '' ?>" required>
                                    <span class="input-group-text bg-light text-muted">kg</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="tinggi_badan">Tinggi Badan (cm) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.1" name="tinggi_badan" id="tinggi_badan" class="form-control" placeholder="172.0" min="80" max="250" value="<?= $defaultTb !== '' ? htmlspecialchars((string)$defaultTb) : '' ?>" required>
                                    <span class="input-group-text bg-light text-muted">cm</span>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="lingkar_leher">Lingkar Leher (cm) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.1" name="lingkar_leher" id="lingkar_leher" class="form-control" placeholder="38.0" min="20" max="60" required>
                                    <span class="input-group-text bg-light text-muted">cm</span>
                                </div>
                                <div class="form-text small">Ukur tepat di bawah jakun / laring (20 - 60 cm).</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="lingkar_pinggang">Lingkar Pinggang (cm) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.1" name="lingkar_pinggang" id="lingkar_pinggang" class="form-control" placeholder="80.0" min="40" max="200" required>
                                    <span class="input-group-text bg-light text-muted">cm</span>
                                </div>
                                <div class="form-text small">Ukur setinggi pusar saat mengembuskan napas (40 - 200 cm).</div>
                            </div>
                        </div>

                        <!-- Khusus Perempuan: Lingkar Pinggul -->
                        <div class="mb-4" id="hipFieldContainer" style="display: <?= $defaultJk === 'P' ? 'block' : 'none' ?>;">
                            <label class="form-label" for="lingkar_pinggul">Lingkar Pinggul (cm) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.1" name="lingkar_pinggul" id="lingkar_pinggul" class="form-control" placeholder="95.0" min="50" max="200">
                                <span class="input-group-text bg-light text-muted">cm</span>
                            </div>
                            <div class="form-text small">Ukur lingkar pinggul/pantat pada bagian paling lebar (50 - 200 cm). Wajib untuk perempuan.</div>
                        </div>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary-health px-4 py-2">
                                Estimasi Lemak Tubuh
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
                <h6 class="fw-bold mb-3" style="color: var(--primary);">Kategori Lemak Tubuh (ACE)</h6>
                <div class="table-responsive">
                    <table class="table table-sm small align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th class="text-end">Pria</th>
                                <th class="text-end">Wanita</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge bg-warning-subtle">Lemak Esensial</span></td>
                                <td class="text-end">2 – 5%</td>
                                <td class="text-end">10 – 13%</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-success-subtle">Atlet</span></td>
                                <td class="text-end">6 – 13%</td>
                                <td class="text-end">14 – 20%</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-success-subtle">Fitness</span></td>
                                <td class="text-end">14 – 17%</td>
                                <td class="text-end">21 – 24%</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-info-subtle">Rata-rata</span></td>
                                <td class="text-end">18 – 24%</td>
                                <td class="text-end">25 – 31%</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-danger-subtle">Kelebihan</span></td>
                                <td class="text-end">&ge; 25%</td>
                                <td class="text-end">&ge; 32%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card p-4">
                <h6 class="fw-bold mb-2" style="color: var(--primary);">Keterbatasan Formula</h6>
                <p class="small mb-0" style="color: var(--muted); line-height: 1.55;">
                    Metode US Navy merupakan rumus estimasi empiris antropometri lingkar tubuh dengan margin akurasi sekitar 3–4% dibandingkan uji DEXA scan atau penimbangan bawah air (*hydrostatic weighing*).
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function toggleGenderFields() {
    const jk = document.getElementById('jenis_kelamin').value;
    const hipContainer = document.getElementById('hipFieldContainer');
    const hipInput = document.getElementById('lingkar_pinggul');
    if (jk === 'P') {
        hipContainer.style.display = 'block';
        hipInput.setAttribute('required', 'required');
    } else {
        hipContainer.style.display = 'none';
        hipInput.removeAttribute('required');
    }
}

function validateFatForm() {
    const jk = document.getElementById('jenis_kelamin').value;
    const tb = parseFloat(document.getElementById('tinggi_badan').value);
    const bb = parseFloat(document.getElementById('berat_badan').value);
    const leher = parseFloat(document.getElementById('lingkar_leher').value);
    const pinggang = parseFloat(document.getElementById('lingkar_pinggang').value);
    const pinggul = parseFloat(document.getElementById('lingkar_pinggul').value);

    if (isNaN(tb) || tb < 80 || tb > 250) {
        alert("Harap masukkan tinggi badan antara 80 - 250 cm.");
        return false;
    }
    if (isNaN(bb) || bb < 20 || bb > 300) {
        alert("Harap masukkan berat badan antara 20 - 300 kg.");
        return false;
    }
    if (isNaN(leher) || leher < 20 || leher > 60) {
        alert("Harap masukkan lingkar leher antara 20 - 60 cm.");
        return false;
    }
    if (isNaN(pinggang) || pinggang < 40 || pinggang > 200) {
        alert("Harap masukkan lingkar pinggang antara 40 - 200 cm.");
        return false;
    }

    if (jk === 'L') {
        if (pinggang <= leher) {
            alert("Untuk pria, lingkar pinggang harus lebih besar dari lingkar leher.");
            return false;
        }
    } else {
        if (isNaN(pinggul) || pinggul < 50 || pinggul > 200) {
            alert("Harap masukkan lingkar pinggul antara 50 - 200 cm untuk wanita.");
            return false;
        }
        if ((pinggang + pinggul) <= leher) {
            alert("Untuk wanita, total lingkar pinggang dan pinggul harus lebih besar dari lingkar leher.");
            return false;
        }
    }

    return true;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
