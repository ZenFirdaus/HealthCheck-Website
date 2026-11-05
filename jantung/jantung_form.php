<?php
$pageTitle = 'Kalkulator Detak Jantung & Zona Kardio — HealthCheck';
$activePage = 'jantung';
require_once __DIR__ . '/../includes/header.php';

$defaultName = $_SESSION['username'] ?? '';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Kalkulator</a></li>
            <li class="breadcrumb-item active" aria-current="page">Kalkulator Detak Jantung</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-7">
            <div class="card overflow-hidden">
                <div class="card-editorial-header">
                    <h4 class="card-heading mb-1">Kalkulator Detak Jantung & Zona Kardio</h4>
                    <p class="small mb-0" style="color: var(--muted);">Evaluasi denyut nadi istirahat dan rentang latihan aerobik ideal.</p>
                </div>

                <div class="p-4 p-md-5">
                    <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'invalid_input'): ?>
                        <div class="alert alert-danger small mb-4">
                            <i class="bi bi-exclamation-circle-fill me-2"></i> Harap masukkan usia (10-100 tahun) dan denyut nadi istirahat (40-180 bpm) yang valid.
                        </div>
                    <?php endif; ?>

                    <form action="jantung_proses.php" method="POST" id="hrForm" onsubmit="return validateHrForm()">
                        <div class="mb-3">
                            <label class="form-label" for="nama_user">Nama Lengkap <span class="text-danger">*</span></label>
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
                                    <span class="input-group-text">bpm</span>
                                </div>
                                <div class="form-text small">Denyut per menit saat santai / bangun pagi</div>
                            </div>
                        </div>

                        <div class="mb-4 p-3 rounded-2 small" style="background-color: var(--light-bg); border-left: 3px solid var(--secondary); color: var(--dark);">
                            <strong>Petunjuk Mengukur Nadi Mandiri:</strong>
                            <p class="mb-0 mt-1" style="color: var(--muted); line-height: 1.5;">Raba pergelangan tangan bagian dalam di bawah pangkal jempol menggunakan jari telunjuk dan tengah, lalu hitung detakan selama 60 detik (atau 15 detik dikalikan 4).</p>
                        </div>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary-health px-4 py-2">
                                Analisis Detak Jantung
                            </button>
                            <a href="../index.php" class="btn btn-outline-secondary px-4 py-2">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="col-lg-4">
            <div class="card p-4 mb-4">
                <h6 class="fw-bold mb-3" style="color: var(--primary);">Rentang Denyut Istirahat (RHR)</h6>
                <div class="table-responsive">
                    <table class="table table-sm small align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th class="text-end">BPM</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="badge bg-success-subtle">Kebugaran Tinggi / Atlet</span></td>
                                <td class="text-end">&lt; 60 bpm</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-success-subtle">Normal Dewasa</span></td>
                                <td class="text-end">60 – 100 bpm</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-warning-subtle">Tinggi (Takikardia)</span></td>
                                <td class="text-end">&gt; 100 bpm</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card p-4">
                <h6 class="fw-bold mb-2" style="color: var(--primary);">Perhatian Medis</h6>
                <p class="small mb-0" style="color: var(--muted); line-height: 1.55;">
                    Jika denyut istirahat Anda sering melampaui 100 BPM tanpa stimulasi aktivitas fisik atau konsumsi kafein, dianjurkan untuk berkonsultasi dengan dokter untuk evaluasi kardiologis menyeluruh.
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
