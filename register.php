<?php
$pageTitle = 'Daftar Akun Baru — HealthCheck';
$activePage = 'register';
require_once __DIR__ . '/includes/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Semua kolom wajib diisi.";
    } elseif (strlen($username) < 3 || strlen($username) > 30) {
        $error = "Username harus antara 3 - 30 karakter.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } elseif ($password !== $konfirmasi) {
        $error = "Konfirmasi password tidak sesuai.";
    } else {
        // Cek apakah username sudah ada
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Username sudah digunakan, silakan pilih username lain.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmtInsert = $pdo->prepare("INSERT INTO user (username, password, role) VALUES (?, ?, 'user')");
            if ($stmtInsert->execute([$username, $hashedPassword])) {
                header("Location: login.php?pesan=Silahkan_login");
                exit;
            } else {
                $error = "Terjadi kesalahan sistem saat mendaftarkan akun.";
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="p-4 bg-teal-subtle border-bottom text-center" style="background-color: #f0fdfa;">
                    <div class="brand-badge mb-2" style="width:42px; height:42px;">
                        <i class="bi bi-person-plus-fill fs-5"></i>
                    </div>
                    <h4 class="fw-bold mb-1 text-dark">Daftar Akun HealthCheck</h4>
                    <p class="text-secondary small mb-0">Simpan riwayat pemeriksaan kesehatan Anda dengan aman.</p>
                </div>

                <div class="p-4 p-md-5">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger small rounded-3 mb-4">
                            <i class="bi bi-exclamation-circle-fill me-1"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form action="register.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label" for="username">Username Baru <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                                <input type="text" name="username" id="username" class="form-control" placeholder="Nama pengguna (min. 3 karakter)" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="password">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" id="password" class="form-control" placeholder="Minimal 6 karakter" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="konfirmasi_password">Ulangi Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-shield-lock"></i></span>
                                <input type="password" name="konfirmasi_password" id="konfirmasi_password" class="form-control" placeholder="Ketik ulang password" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary-health w-100 py-2 mb-3">
                            <i class="bi bi-person-check-fill me-1"></i> Buat Akun Baru
                        </button>
                    </form>

                    <div class="text-center pt-2 border-top">
                        <p class="text-secondary small mb-0">
                            Sudah memiliki akun? <a href="login.php" class="text-teal fw-semibold text-decoration-none">Masuk di sini</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
