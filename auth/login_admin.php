<?php
$pageTitle = 'Login Administrator — HealthCheck';
$activePage = 'admin';
require_once __DIR__ . '/../includes/header.php';

if (isset($_SESSION['job']) && $_SESSION['job'] === 'admin') {
    header("Location: ../admin/admin.php");
    exit;
}

$pesan = $_GET['pesan'] ?? '';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card overflow-hidden">
                <div class="card-editorial-header py-4 text-center" style="background-color: var(--primary); border-bottom-color: var(--primary);">
                    <h4 class="card-heading mb-1 text-white">Portal Administrator</h4>
                    <p class="small mb-0" style="color: rgba(255,255,255,0.75);">Akses manajemen dan rekam data sistem.</p>
                </div>

                <div class="p-4 p-md-5">
                    <?php if (!empty($pesan)): ?>
                        <div class="alert alert-danger small mb-4">
                            <?= htmlspecialchars($pesan) ?>
                        </div>
                    <?php endif; ?>

                    <form action="login_admin_proses.php" method="POST">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label" for="username">Username Admin</label>
                            <input type="text" name="username" id="username" class="form-control" placeholder="admin" required autofocus>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="password">Password Admin</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                        </div>

                        <button type="submit" class="btn btn-primary-health w-100 py-2 mb-3">
                            Masuk Administrator
                        </button>
                        <a href="login.php" class="btn btn-link w-100 small text-decoration-none" style="color: var(--muted);">Kembali ke Login Pengguna</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
