<?php
$pageTitle = 'Zona Latihan Detak Jantung (Karvonen & Zoladz) — HealthCheck';
$activePage = 'zona_jantung';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/zona_jantung_helper.php';

$profile = get_current_user_profile($pdo);
$defaultName = $profile['nama_lengkap'] ?? ($_SESSION['username'] ?? '');
$calcAge = !empty($profile['tanggal_lahir']) ? calculate_age_from_dob($profile['tanggal_lahir']) : null;
$defaultUmur = ($calcAge !== null && $calcAge >= 10 && $calcAge <= 100) ? $calcAge : 25;
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Kalkulator</a></li>
            <li class="breadcrumb-item active" aria-current="page">Zona Latihan Kardio</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-7">
            <div class="card overflow-hidden">
                <div class="card-editorial-header">
                    <h4 class="card-heading mb-1">Zona Latihan Detak Jantung</h4>
                    <p class="small mb-0 text-muted">Hitung 5 zona intensitas kardio terarah menggunakan metode Karvonen (HRR) atau metode Zoladz.</p>
                </div>

                <div class="p-4 p-md-5">
                    <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'invalid_input'): ?>
                        <div class="alert alert-danger small mb-4">
                            <i class="bi bi-exclamation-circle-fill me-2"></i> Harap periksa input Anda. Pastikan usia (10–100 tahun) dan denyut nadi istirahat (40–180 BPM) valid.
                        </div>
                    <?php endif; ?>

                    <form action="zona_jantung_proses.php" method="POST" id="hrZonesForm" onsubmit="return validateHrZonesForm()">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label" for="nama_user">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_user" id="nama_user" class="form-control" placeholder="Contoh: Budi Santoso" value="<?= htmlspecialchars($defaultName) ?>" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="umur">Umur (tahun) <span class="text-danger">*</span></label>
                                <input type="number" name="umur" id="umur" class="form-control" placeholder="25" min="10" max="100" value="<?= htmlspecialchars((string)$defaultUmur) ?>" required>
                                <div class="form-text small">Rentang: 10 - 100 tahun</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="resting_hr">Detak Nadi Istirahat / RHR (BPM) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="resting_hr" id="resting_hr" class="form-control" placeholder="68" min="40" max="180" value="68" required>
                                    <span class="input-group-text">bpm</span>
                                </div>
                                <div class="form-text small">Denyut per menit saat bangun tidur rileks</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="metode">Metode Perhitungan Zona Latihan <span class="text-danger">*</span></label>
                            <select name="metode" id="metode" class="form-select" required>
                                <option value="karvonen" selected>Metode Karvonen — Disesuaikan dengan Heart Rate Reserve (Rekomendasi Utama)</option>
                                <option value="zoladz">Metode Zoladz — Disesuaikan dengan Defisit Tetap Detak Maksimal</option>
                            </select>
                            <div class="form-text small">Metode Karvonen paling akurat karena mempertimbangkan kebugaran denyut nadi istirahat Anda.</div>
                        </div>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary-health px-4 py-2">
                                Hitung 5 Zona Latihan
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
                <h6 class="fw-bold mb-3" style="color: var(--primary);">Perbandingan Formula</h6>
                <p class="small text-muted mb-2" style="line-height: 1.55;">
                    <strong class="text-dark">Metode Karvonen:</strong> Memperhitungkan <em>Heart Rate Reserve (HRR = HRmax - RHR)</em>, sehingga rentang denyut latihan sangat personal bagi tiap individu.
                </p>
                <p class="small text-muted mb-0" style="line-height: 1.55;">
                    <strong class="text-dark">Metode Zoladz:</strong> Membagi 5 zona menggunakan pengurangan detak bertahap dari denyut maksimal ($\text{HRmax} - 50$ hingga $\text{HRmax} - 10$), lazim dipakai dalam program atletik Polandia.
                </p>
            </div>

            <div class="card p-4">
                <h6 class="fw-bold mb-2" style="color: var(--danger);">Perhatian Keamanan Olahraga</h6>
                <p class="small text-muted mb-0" style="line-height: 1.55;">
                    Pengguna dengan riwayat gangguan kardiovaskular, aritmia, atau yang sedang mengonsumsi obat golongan penyekat beta (*beta-blockers*) wajib berkonsultasi dengan dokter spesialis jantung sebelum melakukan latihan fisik berintensitas tinggi (Zona 4 dan 5).
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function validateHrZonesForm() {
    const umur = parseInt(document.getElementById('umur').value, 10);
    const rhr = parseInt(document.getElementById('resting_hr').value, 10);

    if (isNaN(umur) || umur < 10 || umur > 100) {
        alert("Harap masukkan umur yang valid (10 - 100 tahun).");
        return false;
    }
    if (isNaN(rhr) || rhr < 40 || rhr > 180) {
        alert("Harap masukkan denyut nadi istirahat antara 40 - 180 BPM.");
        return false;
    }
    return true;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
