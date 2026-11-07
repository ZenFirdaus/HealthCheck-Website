<?php
$pageTitle = 'Kalkulator Kebutuhan Cairan Harian — HealthCheck';
$activePage = 'cairan';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/cairan_helper.php';

$profile = get_current_user_profile($pdo);
$defaultName = $profile['nama_lengkap'] ?? ($_SESSION['username'] ?? '');
$defaultBb = !empty($profile['berat_badan']) ? (float)$profile['berat_badan'] : '';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Kalkulator</a></li>
            <li class="breadcrumb-item active" aria-current="page">Kebutuhan Cairan</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-7">
            <div class="card overflow-hidden">
                <div class="card-editorial-header">
                    <h4 class="card-heading mb-1">Kalkulator Kebutuhan Cairan Harian</h4>
                    <p class="small mb-0" style="color: var(--muted);">Estimasi asupan air minum harian berdasarkan berat badan, aktivitas, dan faktor iklim.</p>
                </div>

                <div class="p-4 p-md-5">
                    <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'invalid_input'): ?>
                        <div class="alert alert-danger small mb-4">
                            <i class="bi bi-exclamation-circle-fill me-2"></i> Harap periksa kembali input Anda. Pastikan berat badan (15 - 250 kg) terisi dengan benar.
                        </div>
                    <?php endif; ?>

                    <form action="cairan_proses.php" method="POST" id="cairanForm" onsubmit="return validateCairanForm()">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label" for="nama_user">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_user" id="nama_user" class="form-control" placeholder="Contoh: Budi Santoso" value="<?= htmlspecialchars($defaultName) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="berat_badan">Berat Badan (kg) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.1" name="berat_badan" id="berat_badan" class="form-control" placeholder="60.0" min="15" max="250" value="<?= $defaultBb !== '' ? htmlspecialchars((string)$defaultBb) : '' ?>" required>
                                <span class="input-group-text bg-light text-muted">kg</span>
                            </div>
                            <div class="form-text small">Rentang: 15 - 250 kg (metode baku 35 ml per kg berat badan).</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="tingkat_aktivitas">Tingkat Aktivitas / Olahraga Harian <span class="text-danger">*</span></label>
                            <select name="tingkat_aktivitas" id="tingkat_aktivitas" class="form-select" required>
                                <?php foreach (get_fluid_activity_levels() as $k => $act): ?>
                                    <option value="<?= $k ?>" <?= ($k === 'sedang') ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($act['label']) ?> (<?= htmlspecialchars($act['description']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="kondisi_iklim">Kondisi Suhu & Iklim Lingkungan <span class="text-danger">*</span></label>
                            <select name="kondisi_iklim" id="kondisi_iklim" class="form-select" required>
                                <?php foreach (get_fluid_climate_factors() as $k => $clim): ?>
                                    <option value="<?= $k ?>" <?= ($k === 'sedang') ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($clim['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary-health px-4 py-2">
                                Hitung Kebutuhan Cairan
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
                <h6 class="fw-bold mb-3" style="color: var(--primary);">Prinsip Hidrasi Sehat</h6>
                <p class="small mb-2 text-muted" style="line-height: 1.55;">
                    Setiap individu memiliki kebutuhan air yang berbeda tergantung bobot tubuh, laju metabolisme, dan tingkat keringat saat beraktivitas.
                </p>
                <p class="small mb-0 text-muted" style="line-height: 1.55;">
                    Asupan 2 liter (8 gelas) sehari adalah patokan rata-rata populasi, tetapi orang dengan aktivitas fisik berat atau di cuaca panas membutuhkan lebih banyak cairan untuk mengganti elektrolit yang hilang.
                </p>
            </div>

            <div class="card p-4">
                <h6 class="fw-bold mb-2" style="color: var(--danger);">Perhatian Klinis Penting</h6>
                <p class="small mb-0 text-muted" style="line-height: 1.55;">
                    Pasien dengan riwayat <strong>Gagal Ginjal Kronis (CKD)</strong>, <strong>Gagal Jantung (CHF)</strong>, atau gangguan fungsi hati sering kali memiliki restriksi (pembatasan) cairan ketat yang diresepkan oleh dokter spesialis. Ikuti selalu anjuran spesialis Anda.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function validateCairanForm() {
    const bb = parseFloat(document.getElementById('berat_badan').value);
    if (isNaN(bb) || bb < 15 || bb > 250) {
        alert("Harap masukkan berat badan antara 15 - 250 kg.");
        return false;
    }
    return true;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
