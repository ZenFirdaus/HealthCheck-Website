<?php
$pageTitle = 'Kalkulator Detak Jantung & Zona Kardio — HealthCheck';
$activePage = 'jantung';
require_once __DIR__ . '/includes/header.php';

$defaultName = $_SESSION['username'] ?? '';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Kalkulator</a></li>
            <li class="breadcrumb-item active" aria-current="page">Kalkulator Detak Jantung</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="p-4 bg-danger-subtle border-bottom">
                    <div class="d-flex align-items-center gap-3">
                        <div class="tool-icon icon-rose m-0" style="width:44px; height:44px;">
                            <i class="bi bi-heart-pulse"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-0 text-dark">Kalkulator Detak Jantung & Zona Latihan</h4>
                            <p class="text-secondary small mb-0">Evaluasi denyut nadi istirahat dan ketahui rentang denyut optimal saat berolahraga.</p>
                        </div>
                    </div>
                </div>

                <div class="p-4 p-md-5">
                    <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'invalid_input'): ?>
                        <div class="alert alert-danger small rounded-3 mb-4">
                            <i class="bi bi-exclamation-circle-fill me-2"></i> Harap masukkan usia (10-100 tahun) dan denyut nadi istirahat (40-180 bpm) yang valid.
                        </div>
                    <?php endif; ?>

                    <form action="jantung_proses.php" method="POST" id="hrForm" onsubmit="return validateHrForm()">
                        <div class="mb-3">
                            <label class="form-label" for="nama_user">Nama Anda <span class="text-danger">*</span></label>
                            <input type="text" name="nama_user" id="nama_user" class="form-control" placeholder="Contoh: Budi Santoso" value="<?= htmlspecialchars($defaultName) ?>" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="umur">Umur (tahun) <span class="text-danger">*</span></label>
                                <input type="number" name="umur" id="umur" class="form-control" placeholder="25" min="10" max="100" value="25" required>
                                <div class="form-text small">Rentang: 10 - 100 tahun</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="resting_hr">Detak Nadi Istirahat / RHR (BPM) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="resting_hr" id="resting_hr" class="form-control" placeholder="70" min="40" max="180" value="72" required>
                                    <span class="input-group-text bg-light text-muted">bpm</span>
                                </div>
                                <div class="form-text small">Denyut per menit saat santai / bangun pagi</div>
                            </div>
                        </div>

                        <div class="mb-4 p-3 bg-light rounded-3 small text-secondary">
                            <i class="bi bi-info-circle text-primary me-1"></i>
                            <strong>Cara menghitung denyut nadi mandiri:</strong> Raba pergelangan tangan bagian dalam di bawah pangkal jempol menggunakan jari telunjuk dan tengah, hitung detakan selama 60 detik (atau hitung 15 detik lalu kalikan 4).
                        </div>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary-health px-4 py-2">
                                <i class="bi bi-heart-pulse-fill me-1"></i> Analisis Detak Jantung
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
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-activity text-danger me-2"></i>Kategori Denyut Nadi Istirahat</h6>
                <div class="table-responsive">
                    <table class="table table-sm small align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kategori</th>
                                <th class="text-end">BPM</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge bg-info-subtle text-info-emphasis">Atletis / Rendah</span></td>
                                <td class="text-end">&lt; 60 bpm</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-success-subtle text-success-emphasis">Normal Dewasa</span></td>
                                <td class="text-end">60 – 100 bpm</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-warning-subtle text-danger-emphasis">Tinggi (Waspada)</span></td>
                                <td class="text-end">&gt; 100 bpm</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-shield-exclamation text-danger me-2"></i>Peringatan Kesehatan</h6>
                <p class="text-secondary small mb-0">
                    Bila detak jantung istirahat Anda sering berada di atas 100 bpm saat sedang tenang tanpa aktivitas, atau disertai pusing, sesak napas, dan nyeri dada, segera periksakan diri ke dokter atau fasilitas kesehatan terdekat.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function validateHrForm() {
    const umur = parseInt(document.getElementById('umur').value);
    const rhr = parseInt(document.getElementById('resting_hr').value);

    if (isNaN(umur) || umur < 10 || umur > 100) {
        alert("Harap masukkan umur yang valid (10 - 100 tahun).");
        return false;
    }
    if (isNaN(rhr) || rhr < 40 || rhr > 180) {
        alert("Harap masukkan denyut nadi istirahat yang masuk akal (40 - 180 bpm).");
        return false;
    }
    return true;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
