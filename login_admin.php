<?php
$pageTitle = 'Login Administrator — HealthCheck';
$activePage = 'admin';
require_once __DIR__ . '/includes/header.php';

if (isset($_SESSION['job']) && $_SESSION['job'] === 'admin') {
    header("Location: admin.php");
    exit;
}

$pesan = $_GET['pesan'] ?? '';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="p-4 bg-dark text-white text-center">
                    <div class="brand-badge bg-secondary mb-2" style="width:42px; height:42px;">
                        <i class="bi bi-shield-lock-fill fs-5"></i>
                    </div>
                    <h4 class="fw-bold mb-1">Portal Administrator</h4>
                    <p class="text-white-50 small mb-0">Hanya untuk staf & pengelola sistem HealthCheck.</p>
                </div>

                <div class="p-4 p-md-5">
                    <?php if (!empty($pesan)): ?>
                        <div class="alert alert-danger small rounded-3 mb-4">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($pesan) ?>
                        </div>
                    <?php endif; ?>

                    <form action="login_admin_proses.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label" for="username">Username Admin</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-person-badge"></i></span>
                                <input type="text" name="username" id="username" class="form-control" placeholder="admin" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="password">Password Admin</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-key"></i></span>
                                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-dark w-100 py-2 mb-3">
                            <i class="bi bi-unlock-fill me-1"></i> Masuk Administrator
                        </button>
                        <a href="login.php" class="btn btn-link text-muted w-100 small">Kembali ke Login Pengguna</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
