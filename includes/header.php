<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageTitle = $pageTitle ?? 'HealthCheck — Website Cek Kesehatan';
$activePage = $activePage ?? 'home';
$username = $_SESSION['username'] ?? null;
$role = $_SESSION['job'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Navbar HealthCheck -->
<nav class="navbar navbar-expand-lg navbar-health sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <span class="brand-badge"><i class="bi bi-heart-pulse-fill"></i></span>
            <span class="brand-text">Health<span>Check</span></span>
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navHealthContent">
            <i class="bi bi-list fs-2 text-dark"></i>
        </button>

        <div class="collapse navbar-collapse" id="navHealthContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <li class="nav-item">
                    <a class="nav-link <?= $activePage === 'home' ? 'fw-bold text-dark' : '' ?>" href="index.php">Beranda</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($activePage, ['bmi', 'bmr', 'kalori', 'jantung', 'mental']) ? 'fw-bold text-dark' : '' ?>" href="#" role="button" data-bs-toggle="dropdown">
                        Kalkulator Kesehatan
                    </a>
                    <ul class="dropdown-menu border-0 shadow-sm rounded-3 py-2">
                        <li><a class="dropdown-item py-2" href="bmi_form.php"><i class="bi bi-speedometer2 me-2 text-teal"></i>Kalkulator BMI (IMT)</a></li>
                        <li><a class="dropdown-item py-2" href="bmr_form.php"><i class="bi bi-fire me-2 text-warning"></i>Kalkulator BMR & Kalori</a></li>
                        <li><a class="dropdown-item py-2" href="jantung_form.php"><i class="bi bi-heart me-2 text-danger"></i>Kalkulator Detak Jantung</a></li>
                        <li><a class="dropdown-item py-2" href="mental_form.php"><i class="bi bi-emoji-smile me-2 text-primary"></i>Screening Mental (PHQ-4)</a></li>
                    </ul>
                </li>
                <?php if ($role === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link text-danger fw-semibold <?= $activePage === 'admin' ? 'active' : '' ?>" href="admin.php">
                        <i class="bi bi-shield-lock me-1"></i>Admin Panel
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <?php if ($username): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-health dropdown-toggle btn-sm d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($username) ?> (<?= htmlspecialchars($role) ?>)
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm rounded-3">
                            <?php if ($role === 'admin'): ?>
                                <li><a class="dropdown-item" href="admin.php"><i class="bi bi-speedometer me-2"></i>Kelola Data</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Keluar (Logout)</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-health btn-sm px-3">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Masuk
                    </a>
                    <a href="register.php" class="btn btn-primary-health btn-sm px-3">
                        Daftar
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<main class="py-4">
