<?php
$pageTitle = 'Penilaian Kebiasaan & Higiene Tidur — HealthCheck';
$activePage = 'tidur';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/tidur_helper.php';

$profile = get_current_user_profile($pdo);
$defaultName = $profile['nama_lengkap'] ?? ($_SESSION['username'] ?? '');
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Kalkulator</a></li>
            <li class="breadcrumb-item active" aria-current="page">Penilaian Pola Tidur</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-7">
            <div class="card overflow-hidden">
                <div class="card-editorial-header">
                    <h4 class="card-heading mb-1">Penilaian Kebiasaan & Higiene Tidur</h4>
                    <p class="small mb-0 text-muted">Evaluasi durasi, efisiensi waktu istirahat, dan kebiasaan sirkadian Anda dalam 1 bulan terakhir.</p>
                </div>

                <div class="p-4 p-md-5">
                    <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'invalid_input'): ?>
                        <div class="alert alert-danger small mb-4">
                            <i class="bi bi-exclamation-circle-fill me-2"></i> Harap periksa kembali input Anda. Pastikan durasi tidur (1–24 jam) dan seluruh pertanyaan terisi dengan benar.
                        </div>
                    <?php endif; ?>

                    <form action="tidur_proses.php" method="POST" id="sleepForm" onsubmit="return validateSleepForm()">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label" for="nama_user">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_user" id="nama_user" class="form-control" placeholder="Contoh: Budi Santoso" value="<?= htmlspecialchars($defaultName) ?>" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label" for="durasi_jam">Rata-rata Durasi Tidur Aktual (Jam) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.5" name="durasi_jam" id="durasi_jam" class="form-control" placeholder="7.5" min="1" max="24" required>
                                    <span class="input-group-text">jam</span>
                                </div>
                                <div class="form-text small">Waktu tidur lelap sesungguhnya per malam</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="latensi_menit">Waktu untuk Tertidur (Menit) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="latensi_menit" id="latensi_menit" class="form-control" placeholder="15" min="0" max="240" required>
                                    <span class="input-group-text">menit</span>
                                </div>
                                <div class="form-text small">Waktu dari berbaring hingga terlelap</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="frekuensi_terbangun">Frekuensi Terbangun di Tengah Malam <span class="text-danger">*</span></label>
                            <select name="frekuensi_terbangun" id="frekuensi_terbangun" class="form-select" required>
                                <option value="0">Tidak pernah terbangun / tidur pulas nyenyak</option>
                                <option value="1">1 kali (terbangun sebentar lalu cepat tertidur kembali)</option>
                                <option value="2">2 kali dalam semalam</option>
                                <option value="3">3 kali atau lebih (sulit kembali tidur)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="penggunaan_gawai">Penggunaan Layar Gawai/HP 30 Menit Sebelum Tidur <span class="text-danger">*</span></label>
                            <select name="penggunaan_gawai" id="penggunaan_gawai" class="form-select" required>
                                <option value="tidak">Tidak pernah / bebas layar sebelum tidur</option>
                                <option value="kadang">Kadang-kadang (1–3 hari per minggu)</option>
                                <option value="sering">Sering / hampir setiap malam di tempat tidur</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="kesegaran_bangun">Kondisi Tubuh saat Bangun Pagi Hari <span class="text-danger">*</span></label>
                            <select name="kesegaran_bangun" id="kesegaran_bangun" class="form-select" required>
                                <option value="sangat_segar">Sangat segar, bugar, dan bersemangat</option>
                                <option value="cukup_segar">Cukup segar untuk memulai aktivitas harian</option>
                                <option value="kurang_segar">Kurang bertenaga dan butuh waktu lama untuk fokus</option>
                                <option value="lelah_mengantuk">Masih merasa sangat lelah, pusing, atau mengantuk berat</option>
                            </select>
                        </div>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary-health px-4 py-2">
                                Evaluasi Kualitas Tidur
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
                <h6 class="fw-bold mb-3" style="color: var(--primary);">Prinsip Higiene Tidur Sehat</h6>
                <p class="small text-muted mb-2" style="line-height: 1.55;">
                    Tidur berkualitas adalah proses biologis esensial untuk memulihkan fungsi kognitif otak, memperkuat sistem kekebalan tubuh, dan menyeimbangkan hormon metabolisme (leptin dan ghrelin).
                </p>
                <p class="small text-muted mb-0" style="line-height: 1.55;">
                    Durasi yang direkomendasikan bagi orang dewasa sehat menurut <em>National Sleep Foundation</em> adalah <strong>7 sampai 9 jam</strong> per malam dengan jadwal tidur-bangun yang konsisten.
                </p>
            </div>

            <div class="card p-4">
                <h6 class="fw-bold mb-2" style="color: var(--primary);">Catatan Instrumen Penilaian</h6>
                <p class="small text-muted mb-0" style="line-height: 1.55;">
                    Modul ini merupakan instrumen edukasi mandiri <strong>Sleep Habit Assessment</strong> untuk menilai kebiasaan higiene tidur non-invasif. Alat ini bukan instrumen diagnostik polisomnografi dan bukan pengganti konsultasi dengan dokter spesialis gangguan tidur.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function validateSleepForm() {
    const durasi = parseFloat(document.getElementById('durasi_jam').value);
    const latensi = parseInt(document.getElementById('latensi_menit').value, 10);

    if (isNaN(durasi) || durasi < 1 || durasi > 24) {
        alert("Harap masukkan durasi tidur yang masuk akal (1 - 24 jam).");
        return false;
    }
    if (isNaN(latensi) || latensi < 0 || latensi > 240) {
        alert("Harap masukkan waktu untuk tertidur antara 0 - 240 menit.");
        return false;
    }
    return true;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
