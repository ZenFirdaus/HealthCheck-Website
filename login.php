<?php
$pageTitle = 'Masuk — HealthCheck';
$activePage = 'login';
require_once __DIR__ . '/includes/header.php';

// Jika sudah login, redirect sesuai role
if (isset($_SESSION['username'])) {
    if (isset($_SESSION['job']) && $_SESSION['job'] === 'admin') {
        header("Location: admin.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$pesan = $_GET['pesan'] ?? '';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="p-4 bg-teal-subtle border-bottom text-center" style="background-color: #f0fdfa;">
                    <div class="brand-badge mb-2" style="width:42px; height:42px;">
                        <i class="bi bi-person-fill fs-5"></i>
                    </div>
                    <h4 class="fw-bold mb-1 text-dark">Masuk ke HealthCheck</h4>
                    <p class="text-secondary small mb-0">Kelola riwayat kesehatan atau akses dashboard sistem.</p>
                </div>

                <div class="p-4 p-md-5">
                    <?php if ($pesan === 'belum_login'): ?>
                        <div class="alert alert-warning small rounded-3 mb-4">
                            <i class="bi bi-info-circle-fill me-1"></i> Silakan masuk terlebih dahulu untuk mengakses halaman tersebut.
                        </div>
                    <?php elseif ($pesan === 'gagal'): ?>
                        <div class="alert alert-danger small rounded-3 mb-4">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Username atau password yang Anda masukkan salah.
                        </div>
                    <?php elseif ($pesan === 'logout'): ?>
                        <div class="alert alert-success small rounded-3 mb-4">
                            <i class="bi bi-check-circle-fill me-1"></i> Anda telah berhasil keluar.
                        </div>
                    <?php elseif ($pesan === 'Silahkan_login'): ?>
                        <div class="alert alert-success small rounded-3 mb-4">
                            <i class="bi bi-check-circle-fill me-1"></i> Pendaftaran berhasil! Silakan masuk dengan akun Anda.
                        </div>
                    <?php endif; ?>

                    <form action="login_user_proses.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label" for="username">Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                                <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="password">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary-health w-100 py-2 mb-3">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Sekarang
                        </button>
                    </form>

                    <div class="text-center pt-2 border-top">
                        <p class="text-secondary small mb-2">
                            Belum memiliki akun? <a href="register.php" class="text-teal fw-semibold text-decoration-none">Daftar Akun Baru</a>
                        </p>
                        <p class="text-secondary small mb-0">
                            Masuk sebagai pengelola sistem? <a href="login_admin.php" class="text-secondary fw-semibold text-decoration-underline">Portal Admin</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
