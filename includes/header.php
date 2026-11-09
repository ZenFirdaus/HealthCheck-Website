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

<!-- Announcement Bar (Editorial Minimalist) -->
<div class="announcement-bar">
    <div class="container d-flex justify-content-center justify-content-md-between align-items-center">
        <span><i class="bi bi-shield-check me-1 text-teal"></i> Platform Evaluasi Kesehatan Mandiri Berbasis Parameter Klinis Terstandar</span>
        <a href="<?= $relPath ?>index.php#toolsGrid" class="d-none d-md-inline">Jelajahi 7 Alat Pemeriksaan &rarr;</a>
    </div>
</div>

<!-- Navbar HealthCheck (Hims-Inspired Clean Healthcare) -->
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
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                <li class="nav-item">
                    <a class="nav-link <?= $activePage === 'home' ? 'active' : '' ?>" href="<?= $relPath ?>index.php">Beranda</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($activePage, ['bmi', 'bmr', 'kalori', 'whtr', 'body_fat', 'cairan', 'jantung', 'mental', 'tekanan_darah', 'tidur', 'zona_jantung', 'aktivitas_fisik', 'stres']) ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown">
                        Kalkulator Kesehatan
                    </a>
                    <ul class="dropdown-menu">
                        <li><h6 class="dropdown-header text-uppercase" style="font-size:0.72rem; letter-spacing:0.06em;">Gizi & Komposisi Tubuh</h6></li>
                        <li><a class="dropdown-item" href="<?= $relPath ?>bmi/bmi_form.php">Indeks Massa Tubuh (BMI)</a></li>
                        <li><a class="dropdown-item" href="<?= $relPath ?>bmr/bmr_form.php">Kebutuhan Kalori & Makronutrisi</a></li>
                        <li><a class="dropdown-item" href="<?= $relPath ?>whtr/whtr_form.php">Rasio Pinggang ke Tinggi (WHtR)</a></li>
                        <li><a class="dropdown-item" href="<?= $relPath ?>body_fat/body_fat_form.php">Persentase Lemak Tubuh (US Navy)</a></li>
                        <li><a class="dropdown-item" href="<?= $relPath ?>cairan/cairan_form.php">Kebutuhan Cairan Harian</a></li>
                        <li><a class="dropdown-item" href="<?= $relPath ?>aktivitas_fisik/aktivitas_fisik_form.php">Aktivitas Fisik (IPAQ)</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header text-uppercase" style="font-size:0.72rem; letter-spacing:0.06em;">Kardiovaskular</h6></li>
                        <li><a class="dropdown-item" href="<?= $relPath ?>tekanan_darah/tekanan_darah_form.php">Tekanan Darah (Tensi)</a></li>
                        <li><a class="dropdown-item" href="<?= $relPath ?>jantung/jantung_form.php">Detak Jantung Istirahat</a></li>
                        <li><a class="dropdown-item" href="<?= $relPath ?>zona_jantung/zona_jantung_form.php">Zona Latihan Kardio</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header text-uppercase" style="font-size:0.72rem; letter-spacing:0.06em;">Tidur & Kesehatan Mental</h6></li>
                        <li><a class="dropdown-item" href="<?= $relPath ?>tidur/tidur_form.php">Pola & Higiene Tidur</a></li>
                        <li><a class="dropdown-item" href="<?= $relPath ?>stres/stres_form.php">Tingkat Stres (PSS-10)</a></li>
                        <li><a class="dropdown-item" href="<?= $relPath ?>mental/mental_form.php">Skrining Mental (PHQ-4)</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activePage === 'education' ? 'active' : '' ?>" href="<?= $relPath ?>education/index.php">Pusat Edukasi</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $relPath ?>index.php#metodologi">Panduan Klinis</a>
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
                            <li><h6 class="dropdown-header text-uppercase" style="font-size:0.72rem; letter-spacing:0.05em;">Menu Personal</h6></li>
                            <li><a class="dropdown-item" href="<?= $relPath ?>user/dashboard.php"><i class="bi bi-speedometer2 me-2 text-primary"></i>Personal Dashboard</a></li>
                            <li><a class="dropdown-item" href="<?= $relPath ?>user/goals.php"><i class="bi bi-flag me-2 text-primary"></i>Target & Milestone</a></li>
                            <li><a class="dropdown-item" href="<?= $relPath ?>user/checkin.php"><i class="bi bi-pencil-square me-2 text-primary"></i>Daily Check-in</a></li>
                            <li><a class="dropdown-item" href="<?= $relPath ?>user/habits.php"><i class="bi bi-check2-circle me-2 text-primary"></i>Habits Tracker</a></li>
                            <li><a class="dropdown-item" href="<?= $relPath ?>user/reminders.php"><i class="bi bi-bell me-2 text-primary"></i>Pengingat & Notifikasi</a></li>
                            <li><a class="dropdown-item" href="<?= $relPath ?>user/history.php"><i class="bi bi-clock-history me-2 text-primary"></i>Riwayat Pemeriksaan</a></li>
                            <li><a class="dropdown-item" href="<?= $relPath ?>user/report_download.php"><i class="bi bi-file-earmark-pdf me-2 text-primary"></i>Unduh Laporan PDF</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php if ($role === 'admin'): ?>
                                <li><a class="dropdown-item" href="<?= $relPath ?>admin/admin.php"><i class="bi bi-shield-lock me-2 text-secondary"></i>Portal Administrator</a></li>
                                <li><hr class="dropdown-divider"></li>
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

<main class="pb-5">
