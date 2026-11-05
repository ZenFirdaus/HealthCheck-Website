<?php
$pageTitle = 'Daftar Akun Baru — HealthCheck';
$activePage = 'register';
require_once __DIR__ . '/../includes/db.php';

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

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card overflow-hidden">
                <div class="card-editorial-header py-4 text-center">
                    <h4 class="card-heading mb-1">Daftar Akun HealthCheck</h4>
                    <p class="small mb-0" style="color: var(--muted);">Simpan riwayat hasil pemeriksaan kesehatan Anda.</p>
                </div>

                <div class="p-4 p-md-5">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger small mb-4">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form action="register.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label" for="username">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" id="username" class="form-control" placeholder="3 - 30 karakter" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="password">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Minimal 6 karakter" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="konfirmasi_password">Konfirmasi Password <span class="text-danger">*</span></label>
                            <input type="password" name="konfirmasi_password" id="konfirmasi_password" class="form-control" placeholder="Ketik ulang password" required>
                        </div>

                        <button type="submit" class="btn btn-primary-health w-100 py-2 mb-3">
                            Daftar Akun
                        </button>
                    </form>

                    <div class="text-center pt-3 border-top" style="border-color: var(--card-border) !important;">
                        <p class="small mb-0" style="color: var(--muted);">
                            Sudah memiliki akun? <a href="login.php" class="fw-semibold text-decoration-none" style="color: var(--secondary);">Masuk di sini</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
