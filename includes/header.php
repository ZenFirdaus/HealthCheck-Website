<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/csrf.php';
$pageTitle = $pageTitle ?? 'HealthCheck — Website Cek Kesehatan';
$activePage = $activePage ?? 'home';
$username = $_SESSION['username'] ?? null;
$role = $_SESSION['job'] ?? null;

// Determine relative path to project root
$projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$scriptDir = str_replace('\\', '/', realpath(dirname($_SERVER['SCRIPT_FILENAME'] ?? '')));
$relPath = '';
if ($scriptDir && $projectRoot && $scriptDir !== $projectRoot && str_starts_with($scriptDir, $projectRoot)) {
    $sub = trim(substr($scriptDir, strlen($projectRoot)), '/');
    $depth = count(explode('/', $sub));
    $relPath = str_repeat('../', $depth);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= $relPath ?>assets/css/style.css">
</head>
<body>

<!-- Navbar HealthCheck -->
<nav class="navbar navbar-expand-lg navbar-health sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?= $relPath ?>index.php">
            <span class="brand-badge"><i class="bi bi-activity"></i></span>
            <span class="brand-text">Health<span>Check</span></span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navHealthContent" aria-label="Buka Navigasi">
            <i class="bi bi-list fs-4"></i>
        </button>

        <div class="collapse navbar-collapse" id="navHealthContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <li class="nav-item">
                    <a class="nav-link <?= $activePage === 'home' ? 'active' : '' ?>" href="<?= $relPath ?>index.php">Beranda</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($activePage, ['bmi', 'bmr', 'kalori', 'whtr', 'body_fat', 'cairan', 'jantung', 'mental']) ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown">
                        Kalkulator Kesehatan
                    </a>
                    <ul class="dropdown-menu">
                        <li><h6 class="dropdown-header text-uppercase" style="font-size:0.75rem; letter-spacing:0.05em;">Gizi & Komposisi Tubuh</h6></li>
                        <li><a class="dropdown-item" href="<?= $relPath ?>bmi/bmi_form.php">Indeks Massa Tubuh (BMI)</a></li>
                        <li><a class="dropdown-item" href="<?= $relPath ?>bmr/bmr_form.php">Kebutuhan Kalori & Makronutrisi</a></li>
                        <li><a class="dropdown-item" href="<?= $relPath ?>whtr/whtr_form.php">Rasio Pinggang ke Tinggi (WHtR)</a></li>
                        <li><a class="dropdown-item" href="<?= $relPath ?>body_fat/body_fat_form.php">Persentase Lemak Tubuh (US Navy)</a></li>
                        <li><a class="dropdown-item" href="<?= $relPath ?>cairan/cairan_form.php">Kebutuhan Cairan Harian</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header text-uppercase" style="font-size:0.75rem; letter-spacing:0.05em;">Kardiovaskular & Mental</h6></li>
                        <li><a class="dropdown-item" href="<?= $relPath ?>jantung/jantung_form.php">Detak Jantung & Zona Kardio</a></li>
                        <li><a class="dropdown-item" href="<?= $relPath ?>mental/mental_form.php">Skrining Kesehatan Mental (PHQ-4)</a></li>
                    </ul>
                </li>
                <?php if ($role === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $activePage === 'admin' ? 'active' : '' ?>" href="<?= $relPath ?>admin/admin.php">
                        <i class="bi bi-shield-lock me-1"></i>Admin Panel
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <?php if ($username): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-health dropdown-toggle btn-sm d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person me-1"></i> <?= htmlspecialchars($username) ?> (<?= htmlspecialchars($role) ?>)
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if ($role === 'admin'): ?>
                                <li><a class="dropdown-item" href="<?= $relPath ?>admin/admin.php"><i class="bi bi-speedometer me-2"></i>Kelola Data</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item text-danger" href="<?= $relPath ?>auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= $relPath ?>auth/login.php" class="btn btn-outline-health btn-sm px-3">
                        Masuk
                    </a>
                    <a href="<?= $relPath ?>auth/register.php" class="btn btn-primary-health btn-sm px-3">
                        Daftar
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<main class="py-4">
