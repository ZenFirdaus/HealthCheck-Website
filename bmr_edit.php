<?php
$pageTitle = 'Edit Data BMR — HealthCheck';
$activePage = 'admin';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/login_cek.php';
require_once __DIR__ . '/role.php';

if (!is_admin()) {
    header("Location: index.php");
    exit;
}

$id_bmr = (int)($_GET['id_bmr'] ?? $_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM cek_bmr WHERE id_bmr = ?");
$stmt->execute([$id_bmr]);
$data = $stmt->fetch();

if (!$data) {
    header("Location: admin.php?pesan=not_found");
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="admin.php" class="text-decoration-none">Admin</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit Data BMR</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="p-4 bg-primary-subtle border-bottom">
                    <h5 class="fw-bold mb-1 text-dark"><i class="bi bi-fire text-primary me-2"></i>Edit Rekam Data BMR #<?= $data['id_bmr'] ?></h5>
                    <p class="text-secondary small mb-0">Perbarui data biometrik untuk menghitung ulang nilai metabolisme energi.</p>
                </div>

                <div class="p-4 p-md-5">
                    <form action="bmr_update.php" method="POST">
                        <input type="hidden" name="id_bmr" value="<?= $data['id_bmr'] ?>">

                        <div class="mb-3">
                            <label class="form-label" for="nama_user">Nama Pengguna</label>
                            <input type="text" name="nama_user" id="nama_user" class="form-control" value="<?= htmlspecialchars($data['nama_user']) ?>" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label" for="jenis_kelamin">Jenis Kelamin</label>
                                <select name="jenis_kelamin" id="jenis_kelamin" class="form-select" required>
                                    <option value="L" <?= $data['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                    <option value="P" <?= $data['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="umur">Umur (tahun)</label>
                                <input type="number" name="umur" id="umur" class="form-control" value="<?= $data['umur'] ?>" min="10" max="110" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <label class="form-label" for="berat_badan">Berat Badan (kg)</label>
                                <input type="number" step="0.1" name="berat_badan" id="berat_badan" class="form-control" value="<?= $data['berat_badan'] ?>" min="20" max="300" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="tinggi_badan">Tinggi Badan (cm)</label>
                                <input type="number" step="0.1" name="tinggi_badan" id="tinggi_badan" class="form-control" value="<?= $data['tinggi_badan'] ?>" min="80" max="250" required>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary-health px-4">
                                <i class="bi bi-save me-1"></i> Simpan Perubahan
                            </button>
                            <a href="admin.php" class="btn btn-outline-secondary px-4">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
