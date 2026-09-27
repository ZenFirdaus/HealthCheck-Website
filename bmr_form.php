<?php
$pageTitle = 'Kalkulator BMR & Kebutuhan Kalori (TDEE) — HealthCheck';
$activePage = 'bmr';
require_once __DIR__ . '/includes/header.php';

$defaultName = $_SESSION['username'] ?? '';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Kalkulator</a></li>
            <li class="breadcrumb-item active" aria-current="page">Kalkulator BMR & Kalori</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="p-4 bg-primary-subtle border-bottom">
                    <div class="d-flex align-items-center gap-3">
                        <div class="tool-icon icon-blue m-0" style="width:44px; height:44px;">
                            <i class="bi bi-fire"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-0 text-dark">Kalkulator BMR & Estimasi Kalori Harian (TDEE)</h4>
                            <p class="text-secondary small mb-0">Hitung energi dasar yang dibakar tubuh dan total kalori yang Anda perlukan setiap hari.</p>
                        </div>
                    </div>
                </div>

                <div class="p-4 p-md-5">
                    <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'invalid_input'): ?>
                        <div class="alert alert-danger small rounded-3 mb-4">
                            <i class="bi bi-exclamation-circle-fill me-2"></i> Harap periksa kembali input Anda. Pastikan umur, berat badan, dan tinggi badan valid.
                        </div>
                    <?php endif; ?>

                    <form action="bmr_proses.php" method="POST" id="bmrForm" onsubmit="return validateBmrForm()">
                        <div class="mb-3">
                            <label class="form-label" for="nama_user">Nama Anda <span class="text-danger">*</span></label>
                            <input type="text" name="nama_user" id="nama_user" class="form-control" placeholder="Contoh: Budi Santoso" value="<?= htmlspecialchars($defaultName) ?>" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="jenis_kelamin">Jenis Kelamin <span class="text-danger">*</span></label>
                                <select name="jenis_kelamin" id="jenis_kelamin" class="form-select" required>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="umur">Umur (tahun) <span class="text-danger">*</span></label>
                                <input type="number" name="umur" id="umur" class="form-control" placeholder="25" min="10" max="110" value="25" required>
                                <div class="form-text small">Rentang: 10 - 110 tahun</div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="berat_badan">Berat Badan (kg) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.1" name="berat_badan" id="berat_badan" class="form-control" placeholder="65" min="20" max="300" required>
                                    <span class="input-group-text bg-light text-muted">kg</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="tinggi_badan">Tinggi Badan (cm) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.1" name="tinggi_badan" id="tinggi_badan" class="form-control" placeholder="170" min="80" max="250" required>
                                    <span class="input-group-text bg-light text-muted">cm</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="aktivitas">Tingkat Aktivitas Fisik Harian <span class="text-danger">*</span></label>
                            <select name="aktivitas" id="aktivitas" class="form-select" required>
                                <option value="1.2">Jarang Bergerak / Sedentary (banyak duduk, tidak ada olahraga rutin)</option>
                                <option value="1.375">Aktivitas Ringan (olahraga 1-3 hari/minggu)</option>
                                <option value="1.55" selected>Aktivitas Sedang (olahraga 3-5 hari/minggu)</option>
                                <option value="1.725">Sangat Aktif (olahraga berat 6-7 hari/minggu)</option>
                                <option value="1.9">Ekstra Aktif (pekerja fisik berat / atlet latihan 2x sehari)</option>
                            </select>
                        </div>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary-health px-4 py-2">
                                <i class="bi bi-lightning-charge me-1"></i> Hitung BMR & Kalori
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary px-4 py-2">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-question-circle text-primary me-2"></i>Apa itu BMR & TDEE?</h6>
                <p class="text-secondary small mb-2">
                    <strong>BMR (Basal Metabolic Rate):</strong> Jumlah energi minimal yang dibutuhkan organ tubuh untuk tetap bertahan hidup saat istirahat penuh (bernapas, sirkulasi darah, regenerasi sel).
                </p>
                <p class="text-secondary small mb-0">
                    <strong>TDEE (Total Daily Energy Expenditure):</strong> Total kalori yang Anda bakar per hari setelah memperhitungkan aktivitas fisik dan pekerjaan harian.
                </p>
            </div>

            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-calculator text-success me-2"></i>Rumus Standar</h6>
                <p class="text-secondary small mb-0">
                    Kalkulator ini menggunakan rumus <strong>Mifflin-St Jeor</strong> yang diakui secara internasional oleh ahli gizi klinis sebagai standar estimasi energi yang paling presisi bagi populasi umum.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function validateBmrForm() {
    const bb = parseFloat(document.getElementById('berat_badan').value);
    const tb = parseFloat(document.getElementById('tinggi_badan').value);
    const umur = parseInt(document.getElementById('umur').value);

    if (isNaN(bb) || bb < 20 || bb > 300) {
        alert("Harap masukkan berat badan antara 20 - 300 kg.");
        return false;
    }
    if (isNaN(tb) || tb < 80 || tb > 250) {
        alert("Harap masukkan tinggi badan antara 80 - 250 cm.");
        return false;
    }
    if (isNaN(umur) || umur < 10 || umur > 110) {
        alert("Harap masukkan umur antara 10 - 110 tahun.");
        return false;
    }
    return true;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
