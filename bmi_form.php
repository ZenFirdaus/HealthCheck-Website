<?php
$pageTitle = 'Kalkulator BMI (Indeks Massa Tubuh) — HealthCheck';
$activePage = 'bmi';
require_once __DIR__ . '/includes/header.php';

$defaultName = $_SESSION['username'] ?? '';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Kalkulator</a></li>
            <li class="breadcrumb-item active" aria-current="page">Kalkulator BMI</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="p-4 bg-teal-subtle border-bottom" style="background-color: #f0fdfa;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="tool-icon icon-teal m-0" style="width:44px; height:44px;">
                            <i class="bi bi-speedometer2"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-0 text-dark">Kalkulator Indeks Massa Tubuh (BMI)</h4>
                            <p class="text-secondary small mb-0">Cek apakah proporsi berat badan Anda sesuai dengan tinggi badan.</p>
                        </div>
                    </div>
                </div>

                <div class="p-4 p-md-5">
                    <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'invalid_input'): ?>
                        <div class="alert alert-danger small rounded-3 mb-4">
                            <i class="bi bi-exclamation-circle-fill me-2"></i> Mohon masukkan data berat badan (10-350 kg) dan tinggi badan (50-250 cm) yang valid.
                        </div>
                    <?php endif; ?>

                    <form action="bmi_proses.php" method="POST" id="bmiForm" onsubmit="return validateBmiForm()">
                        <div class="mb-3">
                            <label class="form-label" for="nama_user">Nama Lengkap / Panggilan <span class="text-danger">*</span></label>
                            <input type="text" name="nama_user" id="nama_user" class="form-control" placeholder="Contoh: Budi Santoso" value="<?= htmlspecialchars($defaultName) ?>" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="jenis_kelamin">Jenis Kelamin</label>
                                <select name="jenis_kelamin" id="jenis_kelamin" class="form-select">
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="umur">Umur (tahun) <span class="text-danger">*</span></label>
                                <input type="number" name="umur" id="umur" class="form-control" placeholder="Contoh: 24" min="5" max="120" value="25" required>
                                <div class="form-text small">Rentang: 5 - 120 tahun</div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label" for="berat_badan">Berat Badan (kg) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.1" name="berat_badan" id="berat_badan" class="form-control" placeholder="60.5" min="10" max="350" required>
                                    <span class="input-group-text bg-light text-muted">kg</span>
                                </div>
                                <div class="form-text small">Rentang wajar: 10 - 350 kg</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="tinggi_badan">Tinggi Badan (cm) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.1" name="tinggi_badan" id="tinggi_badan" class="form-control" placeholder="170" min="50" max="250" required>
                                    <span class="input-group-text bg-light text-muted">cm</span>
                                </div>
                                <div class="form-text small">Rentang wajar: 50 - 250 cm</div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary-health px-4 py-2">
                                <i class="bi bi-calculator me-1"></i> Hitung BMI Saya
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary px-4 py-2">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Info Card Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-bookmark-check text-teal me-2"></i>Standar Klasifikasi BMI (Asia)</h6>
                <div class="table-responsive">
                    <table class="table table-sm small align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kategori</th>
                                <th class="text-end">Rentang BMI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge bg-warning-subtle text-warning-emphasis">Kurus</span></td>
                                <td class="text-end">&lt; 18.5</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-success-subtle text-success-emphasis">Normal (Ideal)</span></td>
                                <td class="text-end">18.5 – 22.9</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-warning-subtle text-danger-emphasis">Kelebihan BB</span></td>
                                <td class="text-end">23.0 – 24.9</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-danger-subtle text-danger-emphasis">Obesitas I</span></td>
                                <td class="text-end">25.0 – 29.9</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-danger text-white">Obesitas II</span></td>
                                <td class="text-end">&ge; 30.0</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-lightbulb text-warning me-2"></i>Catatan Penting</h6>
                <p class="text-secondary small mb-0">
                    BMI mengukur rasio berat dan tinggi badan, namun tidak memperhitungkan persentase massa otot maupun kepadatan tulang. Atlet binaraga mungkin memiliki BMI tinggi meskipun persentase lemak tubuhnya sangat rendah.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function validateBmiForm() {
    const berat = parseFloat(document.getElementById('berat_badan').value);
    const tinggi = parseFloat(document.getElementById('tinggi_badan').value);
    const umur = parseInt(document.getElementById('umur').value);

    if (isNaN(berat) || berat < 10 || berat > 350) {
        alert("Harap masukkan berat badan yang masuk akal (10 - 350 kg).");
        return false;
    }
    if (isNaN(tinggi) || tinggi < 50 || tinggi > 250) {
        alert("Harap masukkan tinggi badan yang masuk akal (50 - 250 cm).");
        return false;
    }
    if (isNaN(umur) || umur < 5 || umur > 120) {
        alert("Harap masukkan umur antara 5 dan 120 tahun.");
        return false;
    }
    return true;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
