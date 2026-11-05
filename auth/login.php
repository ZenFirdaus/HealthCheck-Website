<?php
$pageTitle = 'Masuk — HealthCheck';
$activePage = 'login';
require_once __DIR__ . '/../includes/header.php';

// Jika sudah login, redirect sesuai role
if (isset($_SESSION['username'])) {
    if (isset($_SESSION['job']) && $_SESSION['job'] === 'admin') {
        header("Location: ../admin/admin.php");
    } else {
        header("Location: ../index.php");
    }
    exit;
}

$pesan = $_GET['pesan'] ?? '';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card overflow-hidden">
                <div class="card-editorial-header py-4 text-center">
                    <h4 class="card-heading mb-1">Masuk ke HealthCheck</h4>
                    <p class="small mb-0" style="color: var(--muted);">Gunakan akun Anda untuk melihat riwayat pemeriksaan.</p>
                </div>

                <div class="p-4 p-md-5">
                    <?php if ($pesan === 'belum_login'): ?>
                        <div class="alert alert-warning small mb-4">
                            Silakan masuk terlebih dahulu untuk mengakses halaman tersebut.
                        </div>
                    <?php elseif ($pesan === 'gagal'): ?>
                        <div class="alert alert-danger small mb-4">
                            Username atau password yang Anda masukkan tidak sesuai.
                        </div>
                    <?php elseif ($pesan === 'logout'): ?>
                        <div class="alert alert-success small mb-4">
                            Anda telah berhasil keluar dari akun.
                        </div>
                    <?php elseif ($pesan === 'Silahkan_login'): ?>
                        <div class="alert alert-success small mb-4">
                            Pendaftaran berhasil. Silakan masuk menggunakan akun baru Anda.
                        </div>
                    <?php endif; ?>

                    <form action="login_user_proses.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label" for="username">Username</label>
                            <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username" required autofocus>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="password">Password</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
                        </div>

                        <button type="submit" class="btn btn-primary-health w-100 py-2 mb-3">
                            Masuk
                        </button>
                    </form>

                    <div class="text-center pt-3 border-top" style="border-color: var(--card-border) !important;">
                        <p class="small mb-2" style="color: var(--muted);">
                            Belum memiliki akun? <a href="register.php" class="fw-semibold text-decoration-none" style="color: var(--secondary);">Daftar Akun Baru</a>
                        </p>
                        <p class="small mb-0" style="color: var(--muted);">
                            Pengelola sistem? <a href="login_admin.php" class="fw-semibold text-decoration-none" style="color: var(--primary);">Portal Admin</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
