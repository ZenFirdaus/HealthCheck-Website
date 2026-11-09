<?php
$pageTitle = 'Dashboard Administrator — HealthCheck';
$activePage = 'admin';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/login_cek.php';
require_once __DIR__ . '/../auth/role.php';

if (!is_admin()) {
    if (isset($_SESSION['username'])) {
        header("Location: ../index.php?pesan=unauthorized");
    } else {
        header("Location: ../auth/login.php?pesan=belum_login");
    }
    exit;
}

// Ambil data untuk statistik dashboard
$totalBmi = $pdo->query("SELECT COUNT(*) FROM cek_bmi")->fetchColumn();
$totalBmr = $pdo->query("SELECT COUNT(*) FROM cek_bmr")->fetchColumn();
$totalWhtr = $pdo->query("SELECT COUNT(*) FROM cek_whtr")->fetchColumn();
$totalFat = $pdo->query("SELECT COUNT(*) FROM cek_lemak_tubuh")->fetchColumn();
$totalCairan = $pdo->query("SELECT COUNT(*) FROM cek_kebutuhan_cairan")->fetchColumn();
$totalJantung = $pdo->query("SELECT COUNT(*) FROM cek_jantung")->fetchColumn();
$totalMental = $pdo->query("SELECT COUNT(*) FROM cek_mental")->fetchColumn();
$totalBp = $pdo->query("SELECT COUNT(*) FROM cek_tekanan_darah")->fetchColumn();
$totalTidur = $pdo->query("SELECT COUNT(*) FROM cek_kualitas_tidur")->fetchColumn();
$totalZona = $pdo->query("SELECT COUNT(*) FROM cek_zona_detak_jantung")->fetchColumn();
$totalAktivitas = $pdo->query("SELECT COUNT(*) FROM cek_aktivitas_fisik")->fetchColumn();
$totalStres = $pdo->query("SELECT COUNT(*) FROM cek_stres")->fetchColumn();

// Ambil list data
$listBmi = $pdo->query("SELECT * FROM cek_bmi ORDER BY id_bmi DESC")->fetchAll();
$listBmr = $pdo->query("SELECT * FROM cek_bmr ORDER BY id_bmr DESC")->fetchAll();
$listWhtr = $pdo->query("SELECT * FROM cek_whtr ORDER BY id_whtr DESC")->fetchAll();
$listFat = $pdo->query("SELECT * FROM cek_lemak_tubuh ORDER BY id_fat DESC")->fetchAll();
$listCairan = $pdo->query("SELECT * FROM cek_kebutuhan_cairan ORDER BY id_cairan DESC")->fetchAll();
$listJantung = $pdo->query("SELECT * FROM cek_jantung ORDER BY id_jantung DESC")->fetchAll();
$listMental = $pdo->query("SELECT * FROM cek_mental ORDER BY id_mental DESC")->fetchAll();
$listBp = $pdo->query("SELECT * FROM cek_tekanan_darah ORDER BY id_bp DESC")->fetchAll();
$listTidur = $pdo->query("SELECT * FROM cek_kualitas_tidur ORDER BY id_tidur DESC")->fetchAll();
$listZona = $pdo->query("SELECT * FROM cek_zona_detak_jantung ORDER BY id_zona DESC")->fetchAll();
$listAktivitas = $pdo->query("SELECT * FROM cek_aktivitas_fisik ORDER BY id_aktivitas DESC")->fetchAll();
$listStres = $pdo->query("SELECT * FROM cek_stres ORDER BY id_stres DESC")->fetchAll();

$pesan = $_GET['pesan'] ?? '';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item active" aria-current="page">Dashboard Admin</li>
        </ol>
    </nav>

    <!-- Header Panel -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1" style="color: var(--primary);">
                Dashboard Manajemen Data
            </h3>
            <p class="small mb-0" style="color: var(--muted);">Pantau dan kelola seluruh rekam data kesehatan pengguna.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="../index.php" class="btn btn-outline-secondary btn-sm px-3">
                Ke Beranda
            </a>
            <a href="../auth/logout.php" class="btn btn-sm px-3" style="border: 1px solid var(--danger); color: var(--danger); background: transparent;">
                Keluar
            </a>
        </div>
    </div>

    <!-- Alert Notifikasi -->
    <?php if ($pesan === 'berhasil_update'): ?>
        <div class="alert alert-success alert-dismissible fade show small mb-4" role="alert">
            Data berhasil diperbarui.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($pesan === 'berhasil_hapus'): ?>
        <div class="alert alert-info alert-dismissible fade show small mb-4" role="alert">
            Data berhasil dihapus dari sistem.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($pesan === 'bmr_update'): ?>
        <div class="alert alert-success alert-dismissible fade show small mb-4" role="alert">
            Data BMR berhasil diperbarui.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($pesan === 'bmr_hapus'): ?>
        <div class="alert alert-info alert-dismissible fade show small mb-4" role="alert">
            Data BMR berhasil dihapus.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Kartu Statistik -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card p-3">
                <div class="small fw-semibold" style="color: var(--muted);">Rekam BMI</div>
                <div class="fs-3 fw-bold mt-1" style="color: var(--primary);"><?= $totalBmi ?></div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-3">
            <div class="card p-3">
                <div class="small fw-semibold" style="color: var(--muted);">Rekam BMR & Kalori</div>
                <div class="fs-3 fw-bold mt-1" style="color: var(--primary);"><?= $totalBmr ?></div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-3">
            <div class="card p-3">
                <div class="small fw-semibold" style="color: var(--muted);">Rekam WHtR</div>
                <div class="fs-3 fw-bold mt-1" style="color: var(--primary);"><?= $totalWhtr ?></div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-3">
            <div class="card p-3">
                <div class="small fw-semibold" style="color: var(--muted);">Rekam Lemak Tubuh</div>
                <div class="fs-3 fw-bold mt-1" style="color: var(--primary);"><?= $totalFat ?></div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-4">
            <div class="card p-3">
                <div class="small fw-semibold" style="color: var(--muted);">Rekam Kebutuhan Cairan</div>
                <div class="fs-3 fw-bold mt-1" style="color: var(--primary);"><?= $totalCairan ?></div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-4">
            <div class="card p-3">
                <div class="small fw-semibold" style="color: var(--muted);">Rekam Detak Jantung</div>
                <div class="fs-3 fw-bold mt-1" style="color: var(--primary);"><?= $totalJantung ?></div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-4">
            <div class="card p-3">
                <div class="small fw-semibold" style="color: var(--muted);">Rekam Skrining Mental</div>
                <div class="fs-3 fw-bold mt-1" style="color: var(--primary);"><?= $totalMental ?></div>
            </div>
        </div>
    </div>

        <div class="col-6 col-md-4 col-lg-3">
            <div class="card p-3">
                <div class="small fw-semibold" style="color: var(--muted);">Rekam Tensi Darah</div>
                <div class="fs-3 fw-bold mt-1" style="color: var(--coral);"><?= $totalBp ?></div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-3">
            <div class="card p-3">
                <div class="small fw-semibold" style="color: var(--muted);">Rekam Kualitas Tidur</div>
                <div class="fs-3 fw-bold mt-1" style="color: var(--teal);"><?= $totalTidur ?></div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-3">
            <div class="card p-3">
                <div class="small fw-semibold" style="color: var(--muted);">Rekam Zona Latihan</div>
                <div class="fs-3 fw-bold mt-1" style="color: var(--blue);"><?= $totalZona ?></div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-3">
            <div class="card p-3">
                <div class="small fw-semibold" style="color: var(--muted);">Rekam Aktivitas (IPAQ)</div>
                <div class="fs-3 fw-bold mt-1" style="color: var(--emerald);"><?= $totalAktivitas ?></div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-lg-3">
            <div class="card p-3">
                <div class="small fw-semibold" style="color: var(--muted);">Rekam Stres (PSS-10)</div>
                <div class="fs-3 fw-bold mt-1" style="color: var(--lavender);"><?= $totalStres ?></div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs Data -->
    <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="bmi-tab" data-bs-toggle="tab" data-bs-target="#tab-bmi" type="button" role="tab">
                Data BMI (<?= count($listBmi) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="bmr-tab" data-bs-toggle="tab" data-bs-target="#tab-bmr" type="button" role="tab">
                Data BMR (<?= count($listBmr) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="whtr-tab" data-bs-toggle="tab" data-bs-target="#tab-whtr" type="button" role="tab">
                WHtR (<?= count($listWhtr) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="fat-tab" data-bs-toggle="tab" data-bs-target="#tab-fat" type="button" role="tab">
                Lemak Tubuh (<?= count($listFat) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="cairan-tab" data-bs-toggle="tab" data-bs-target="#tab-cairan" type="button" role="tab">
                Cairan (<?= count($listCairan) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="bp-tab" data-bs-toggle="tab" data-bs-target="#tab-bp" type="button" role="tab">
                Tekanan Darah (<?= count($listBp) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tidur-tab" data-bs-toggle="tab" data-bs-target="#tab-tidur" type="button" role="tab">
                Pola Tidur (<?= count($listTidur) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="zona-tab" data-bs-toggle="tab" data-bs-target="#tab-zona" type="button" role="tab">
                Zona Kardio (<?= count($listZona) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="aktivitas-tab" data-bs-toggle="tab" data-bs-target="#tab-aktivitas" type="button" role="tab">
                Aktivitas IPAQ (<?= count($listAktivitas) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="stres-tab" data-bs-toggle="tab" data-bs-target="#tab-stres" type="button" role="tab">
                Stres PSS (<?= count($listStres) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="jantung-tab" data-bs-toggle="tab" data-bs-target="#tab-jantung" type="button" role="tab">
                Detak Jantung (<?= count($listJantung) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="mental-tab" data-bs-toggle="tab" data-bs-target="#tab-mental" type="button" role="tab">
                Mental PHQ (<?= count($listMental) ?>)
            </button>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="adminTabContent">
        
        <!-- Tab 1: BMI -->
        <div class="tab-pane fade show active" id="tab-bmi" role="tabpanel">
            <div class="card overflow-hidden">
                <div class="card-editorial-header d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0" style="color: var(--primary);">Daftar Pemeriksaan Indeks Massa Tubuh (BMI)</h6>
                    <a href="../bmi/bmi_form.php" class="btn btn-sm btn-primary-health"><i class="bi bi-plus-lg me-1"></i>Tambah Cek BMI</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Nama Pengguna</th>
                                <th>Umur</th>
                                <th>Berat</th>
                                <th>Tinggi</th>
                                <th>Skor BMI</th>
                                <th>Klasifikasi</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($listBmi) > 0): ?>
                                <?php foreach ($listBmi as $row): ?>
                                    <tr>
                                        <td><strong>#<?= $row['id_bmi'] ?></strong></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_user']) ?></td>
                                        <td><?= $row['umur'] ?> thn</td>
                                        <td><?= $row['berat_badan'] ?> kg</td>
                                        <td><?= $row['tinggi_badan'] ?> cm</td>
                                        <td><span class="badge border" style="background-color: var(--light-bg); color: var(--primary);"><?= $row['bmi'] ?? round($row['berat_badan'] / (($row['tinggi_badan']/100)*($row['tinggi_badan']/100)), 1) ?></span></td>
                                        <td>
                                            <span class="badge bg-<?= str_contains($row['kondisi'], 'Normal') ? 'success' : (str_contains($row['kondisi'], 'Kurus') ? 'warning' : 'danger') ?>-subtle">
                                                <?= htmlspecialchars($row['kondisi']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="../bmi/bmi_edit.php?id=<?= $row['id_bmi'] ?>" class="btn btn-light btn-sm border me-1" title="Edit">
                                                <i class="bi bi-pencil" style="color: var(--secondary);"></i>
                                            </a>
                                            <form action="../bmi/bmi_hapus.php" method="POST" class="d-inline" onsubmit="return confirm('Hapus data BMI ini?')">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id_bmi" value="<?= $row['id_bmi'] ?>">
                                                <button type="submit" class="btn btn-light btn-sm border" title="Hapus" style="color: var(--danger);">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Belum ada catatan data BMI.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 2: BMR -->
        <div class="tab-pane fade" id="tab-bmr" role="tabpanel">
            <div class="card overflow-hidden">
                <div class="card-editorial-header d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0" style="color: var(--primary);">Daftar Pemeriksaan BMR & Kalori</h6>
                    <a href="../bmr/bmr_form.php" class="btn btn-sm btn-primary-health"><i class="bi bi-plus-lg me-1"></i>Tambah Cek BMR</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Nama Pengguna</th>
                                <th>L/P</th>
                                <th>Umur</th>
                                <th>Berat</th>
                                <th>Tinggi</th>
                                <th>Nilai BMR</th>
                                <th>Estimasi TDEE</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($listBmr) > 0): ?>
                                <?php foreach ($listBmr as $row): ?>
                                    <tr>
                                        <td><strong>#<?= $row['id_bmr'] ?></strong></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_user']) ?></td>
                                        <td><span class="badge border" style="background-color: var(--light-bg); color: var(--primary);"><?= $row['jenis_kelamin'] ?></span></td>
                                        <td><?= $row['umur'] ?> thn</td>
                                        <td><?= $row['berat_badan'] ?> kg</td>
                                        <td><?= $row['tinggi_badan'] ?> cm</td>
                                        <td><strong style="color: var(--primary);"><?= number_format($row['bmr'], 0, ',', '.') ?></strong> kkal</td>
                                        <td><?= isset($row['tdee']) && $row['tdee'] > 0 ? number_format($row['tdee'], 0, ',', '.') . ' kkal' : '-' ?></td>
                                        <td class="text-end">
                                            <a href="../bmr/bmr_edit.php?id=<?= $row['id_bmr'] ?>" class="btn btn-light btn-sm border me-1" title="Edit">
                                                <i class="bi bi-pencil" style="color: var(--secondary);"></i>
                                            </a>
                                            <form action="../bmr/bmr_hapus.php" method="POST" class="d-inline" onsubmit="return confirm('Hapus data BMR ini?')">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id_bmr" value="<?= $row['id_bmr'] ?>">
                                                <button type="submit" class="btn btn-light btn-sm border" title="Hapus" style="color: var(--danger);">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">Belum ada catatan data BMR.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 2B-1: WHtR -->
        <div class="tab-pane fade" id="tab-whtr" role="tabpanel">
            <div class="card overflow-hidden">
                <div class="card-editorial-header d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0" style="color: var(--primary);">Daftar Pemeriksaan Rasio Pinggang - Tinggi (WHtR)</h6>
                    <a href="../whtr/whtr_form.php" class="btn btn-sm btn-primary-health"><i class="bi bi-plus-lg me-1"></i>Tambah Cek WHtR</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Nama Pengguna</th>
                                <th>L/P</th>
                                <th>Tinggi</th>
                                <th>Pinggang</th>
                                <th>Rasio WHtR</th>
                                <th>Kategori Risiko</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($listWhtr) > 0): ?>
                                <?php foreach ($listWhtr as $row): ?>
                                    <tr>
                                        <td><strong>#<?= $row['id_whtr'] ?></strong></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_user']) ?></td>
                                        <td><span class="badge border" style="background-color: var(--light-bg); color: var(--primary);"><?= $row['jenis_kelamin'] ?></span></td>
                                        <td><?= $row['tinggi_badan'] ?> cm</td>
                                        <td><?= $row['lingkar_pinggang'] ?> cm</td>
                                        <td><span class="badge border fs-6" style="background-color: var(--light-bg); color: var(--primary);"><?= number_format($row['rasio_whtr'], 2) ?></span></td>
                                        <td>
                                            <span class="badge bg-<?= str_contains($row['kategori_risiko'], 'Normal') || str_contains($row['kategori_risiko'], 'Sehat') ? 'success' : (str_contains($row['kategori_risiko'], 'Meningkat') || str_contains($row['kategori_risiko'], 'Kurang') ? 'warning' : 'danger') ?>-subtle">
                                                <?= htmlspecialchars($row['kategori_risiko']) ?>
                                            </span>
                                        </td>
                                        <td class="text-muted"><?= $row['created_at'] ?? '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Belum ada catatan data WHtR.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 2B-2: Lemak Tubuh -->
        <div class="tab-pane fade" id="tab-fat" role="tabpanel">
            <div class="card overflow-hidden">
                <div class="card-editorial-header d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0" style="color: var(--primary);">Daftar Pemeriksaan Lemak Tubuh (US Navy)</h6>
                    <a href="../body_fat/body_fat_form.php" class="btn btn-sm btn-primary-health"><i class="bi bi-plus-lg me-1"></i>Tambah Cek Lemak</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Nama Pengguna</th>
                                <th>L/P</th>
                                <th>Umur</th>
                                <th>BB/TB</th>
                                <th>% Lemak</th>
                                <th>Massa Lemak</th>
                                <th>Massa Otot/Lean</th>
                                <th>Kategori</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($listFat) > 0): ?>
                                <?php foreach ($listFat as $row): ?>
                                    <tr>
                                        <td><strong>#<?= $row['id_fat'] ?></strong></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_user']) ?></td>
                                        <td><span class="badge border" style="background-color: var(--light-bg); color: var(--primary);"><?= $row['jenis_kelamin'] ?></span></td>
                                        <td><?= $row['umur'] ?> thn</td>
                                        <td><?= $row['berat_badan'] ?> kg / <?= $row['tinggi_badan'] ?> cm</td>
                                        <td><strong style="color: var(--primary);"><?= number_format($row['body_fat_percent'], 1) ?>%</strong></td>
                                        <td><?= $row['fat_mass_kg'] ?> kg</td>
                                        <td><?= $row['lean_mass_kg'] ?> kg</td>
                                        <td>
                                            <span class="badge bg-<?= str_contains($row['kategori'], 'Fitness') || str_contains($row['kategori'], 'Athletes') ? 'success' : (str_contains($row['kategori'], 'Obese') || str_contains($row['kategori'], 'Kelebihan') ? 'danger' : 'info') ?>-subtle">
                                                <?= htmlspecialchars($row['kategori']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">Belum ada catatan data lemak tubuh.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 2B-3: Kebutuhan Cairan -->
        <div class="tab-pane fade" id="tab-cairan" role="tabpanel">
            <div class="card overflow-hidden">
                <div class="card-editorial-header d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0" style="color: var(--primary);">Daftar Pemeriksaan Kebutuhan Cairan</h6>
                    <a href="../cairan/cairan_form.php" class="btn btn-sm btn-primary-health"><i class="bi bi-plus-lg me-1"></i>Tambah Cek Cairan</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Nama Pengguna</th>
                                <th>Berat Badan</th>
                                <th>Aktivitas</th>
                                <th>Iklim</th>
                                <th>Kebutuhan Air</th>
                                <th>Gelas</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($listCairan) > 0): ?>
                                <?php foreach ($listCairan as $row): ?>
                                    <tr>
                                        <td><strong>#<?= $row['id_cairan'] ?></strong></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_user']) ?></td>
                                        <td><?= $row['berat_badan'] ?> kg</td>
                                        <td><span class="badge border" style="background-color: var(--light-bg); color: var(--primary);"><?= htmlspecialchars($row['tingkat_aktivitas']) ?></span></td>
                                        <td><?= htmlspecialchars($row['kondisi_iklim']) ?></td>
                                        <td><strong style="color: var(--secondary);"><?= $row['kebutuhan_liter'] ?> L</strong> (<?= number_format($row['kebutuhan_ml'], 0, ',', '.') ?> ml)</td>
                                        <td><span class="badge bg-info-subtle text-dark">~<?= $row['kebutuhan_gelas'] ?> gelas</span></td>
                                        <td class="text-muted"><?= $row['created_at'] ?? '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Belum ada catatan kebutuhan cairan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 3: Jantung -->
        <div class="tab-pane fade" id="tab-jantung" role="tabpanel">
            <div class="card overflow-hidden">
                <div class="card-editorial-header d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0" style="color: var(--primary);">Daftar Pemeriksaan Detak Jantung</h6>
                    <a href="../jantung/jantung_form.php" class="btn btn-sm btn-primary-health"><i class="bi bi-plus-lg me-1"></i>Cek Detak Jantung</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Nama Pengguna</th>
                                <th>Umur</th>
                                <th>Resting HR (BPM)</th>
                                <th>Max HR (BPM)</th>
                                <th>Kategori</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($listJantung) > 0): ?>
                                <?php foreach ($listJantung as $row): ?>
                                    <tr>
                                        <td><strong>#<?= $row['id_jantung'] ?></strong></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_user']) ?></td>
                                        <td><?= $row['umur'] ?> thn</td>
                                        <td><span class="badge bg-danger-subtle fs-6"><?= $row['resting_hr'] ?> BPM</span></td>
                                        <td><?= $row['max_hr'] ?> BPM</td>
                                        <td><span class="badge border" style="background-color: var(--light-bg); color: var(--primary);"><?= htmlspecialchars($row['kategori']) ?></span></td>
                                        <td class="text-muted"><?= $row['created_at'] ?? '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Belum ada data detak jantung.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 4: Mental -->
        <div class="tab-pane fade" id="tab-mental" role="tabpanel">
            <div class="card overflow-hidden">
                <div class="card-editorial-header d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0" style="color: var(--primary);">Daftar Skrining Kesehatan Mental (PHQ-4)</h6>
                    <a href="../mental/mental_form.php" class="btn btn-sm btn-primary-health"><i class="bi bi-plus-lg me-1"></i>Skrining Baru</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Nama Pengguna</th>
                                <th>Skor Cemas</th>
                                <th>Skor Depresi</th>
                                <th>Total Skor</th>
                                <th>Tingkat Indikasi</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($listMental) > 0): ?>
                                <?php foreach ($listMental as $row): ?>
                                    <tr>
                                        <td><strong>#<?= $row['id_mental'] ?></strong></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_user']) ?></td>
                                        <td><?= $row['skor_cemas'] ?> / 6</td>
                                        <td><?= $row['skor_depresi'] ?> / 6</td>
                                        <td><span class="badge border fs-6" style="background-color: var(--primary-light); color: var(--primary);"><?= $row['total_skor'] ?> / 12</span></td>
                                        <td>
                                            <span class="badge bg-<?= $row['total_skor'] <= 2 ? 'success' : ($row['total_skor'] <= 5 ? 'info' : ($row['total_skor'] <= 8 ? 'warning' : 'danger')) ?>-subtle">
                                                <?= htmlspecialchars($row['tingkat']) ?>
                                            </span>
                                        </td>
                                        <td class="text-muted"><?= $row['created_at'] ?? '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Belum ada data skrining mental.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 5: Tekanan Darah -->
        <div class="tab-pane fade" id="tab-bp" role="tabpanel">
            <div class="card overflow-hidden">
                <div class="card-editorial-header d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0" style="color: var(--primary);">Daftar Pemeriksaan Tekanan Darah</h6>
                    <a href="../tekanan_darah/tekanan_darah_form.php" class="btn btn-sm btn-primary-health"><i class="bi bi-plus-lg me-1"></i>Pemeriksaan Baru</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Nama Pengguna</th>
                                <th>Umur</th>
                                <th>Sistolik</th>
                                <th>Diastolik</th>
                                <th>Nadi</th>
                                <th>Kategori</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($listBp) > 0): ?>
                                <?php foreach ($listBp as $row): ?>
                                    <tr>
                                        <td><strong>#<?= $row['id_bp'] ?></strong></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_user']) ?></td>
                                        <td><?= $row['umur'] ?> thn</td>
                                        <td><span class="badge border fs-6 text-dark" style="background-color: var(--light-bg);"><?= $row['sistolik'] ?> mmHg</span></td>
                                        <td><span class="badge border fs-6 text-dark" style="background-color: var(--light-bg);"><?= $row['diastolik'] ?> mmHg</span></td>
                                        <td><?= $row['detak_nadi'] ? $row['detak_nadi'] . ' BPM' : '-' ?></td>
                                        <td>
                                            <span class="badge bg-<?= str_contains($row['kategori'], 'Normal') ? 'success' : (str_contains($row['kategori'], 'Krisis') || str_contains($row['kategori'], 'Tingkat 2') ? 'danger' : 'warning') ?>-subtle">
                                                <?= htmlspecialchars($row['kategori']) ?>
                                            </span>
                                        </td>
                                        <td class="text-muted"><?= $row['created_at'] ?? '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Belum ada data tekanan darah.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 6: Kualitas Tidur -->
        <div class="tab-pane fade" id="tab-tidur" role="tabpanel">
            <div class="card overflow-hidden">
                <div class="card-editorial-header d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0" style="color: var(--primary);">Daftar Penilaian Pola & Higiene Tidur</h6>
                    <a href="../tidur/tidur_form.php" class="btn btn-sm btn-primary-health"><i class="bi bi-plus-lg me-1"></i>Penilaian Baru</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Nama Pengguna</th>
                                <th>Durasi</th>
                                <th>Latensi</th>
                                <th>Terbangun</th>
                                <th>Efisiensi</th>
                                <th>Kategori</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($listTidur) > 0): ?>
                                <?php foreach ($listTidur as $row): ?>
                                    <tr>
                                        <td><strong>#<?= $row['id_tidur'] ?></strong></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_user']) ?></td>
                                        <td><?= $row['durasi_jam'] ?> Jam</td>
                                        <td><?= $row['latensi_menit'] ?> Menit</td>
                                        <td><?= $row['frekuensi_terbangun'] ?>x</td>
                                        <td><span class="badge border fs-6 text-dark" style="background-color: var(--teal-light);"><?= $row['efisiensi_tidur'] ?>%</span></td>
                                        <td>
                                            <span class="badge bg-<?= str_contains($row['kategori_tidur'], 'Sangat Baik') ? 'success' : (str_contains($row['kategori_tidur'], 'Cukup') ? 'info' : (str_contains($row['kategori_tidur'], 'Optimal') ? 'warning' : 'danger')) ?>-subtle">
                                                <?= htmlspecialchars($row['kategori_tidur']) ?>
                                            </span>
                                        </td>
                                        <td class="text-muted"><?= $row['created_at'] ?? '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Belum ada data pola tidur.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 7: Zona Kardio -->
        <div class="tab-pane fade" id="tab-zona" role="tabpanel">
            <div class="card overflow-hidden">
                <div class="card-editorial-header d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0" style="color: var(--primary);">Daftar Zona Latihan Kardio (Karvonen & Zoladz)</h6>
                    <a href="../zona_jantung/zona_jantung_form.php" class="btn btn-sm btn-primary-health"><i class="bi bi-plus-lg me-1"></i>Hitung Zona</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Nama Pengguna</th>
                                <th>Umur</th>
                                <th>RHR</th>
                                <th>Max HR</th>
                                <th>Metode</th>
                                <th>Zona 2 (Fat Burn)</th>
                                <th>Zona 3 (Aerobik)</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($listZona) > 0): ?>
                                <?php foreach ($listZona as $row): ?>
                                    <tr>
                                        <td><strong>#<?= $row['id_zona'] ?></strong></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_user']) ?></td>
                                        <td><?= $row['umur'] ?> thn</td>
                                        <td><?= $row['resting_hr'] ?> BPM</td>
                                        <td><strong class="text-primary"><?= $row['max_hr'] ?> BPM</strong></td>
                                        <td><span class="badge border text-dark" style="background-color: var(--light-bg);"><?= ucfirst($row['metode']) ?></span></td>
                                        <td><span class="badge bg-success-subtle"><?= $row['zona_2_min'] ?>–<?= $row['zona_2_max'] ?> BPM</span></td>
                                        <td><span class="badge bg-primary-subtle"><?= $row['zona_3_min'] ?>–<?= $row['zona_3_max'] ?> BPM</span></td>
                                        <td class="text-muted"><?= $row['created_at'] ?? '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">Belum ada data zona latihan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 8: Aktivitas Fisik -->
        <div class="tab-pane fade" id="tab-aktivitas" role="tabpanel">
            <div class="card overflow-hidden">
                <div class="card-editorial-header d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0" style="color: var(--primary);">Daftar Evaluasi Aktivitas Fisik (IPAQ)</h6>
                    <a href="../aktivitas_fisik/aktivitas_fisik_form.php" class="btn btn-sm btn-primary-health"><i class="bi bi-plus-lg me-1"></i>Evaluasi Baru</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Nama Pengguna</th>
                                <th>Hari Berat</th>
                                <th>Hari Sedang</th>
                                <th>Hari Jalan</th>
                                <th>Total MET</th>
                                <th>Kategori</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($listAktivitas) > 0): ?>
                                <?php foreach ($listAktivitas as $row): ?>
                                    <tr>
                                        <td><strong>#<?= $row['id_aktivitas'] ?></strong></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_user']) ?></td>
                                        <td><?= $row['hari_berat'] ?> hari</td>
                                        <td><?= $row['hari_sedang'] ?> hari</td>
                                        <td><?= $row['hari_jalan'] ?> hari</td>
                                        <td><strong class="text-emerald"><?= number_format($row['total_met_menit'], 0, ',', '.') ?></strong> MET</td>
                                        <td>
                                            <span class="badge bg-<?= str_contains($row['kategori_aktivitas'], 'Tinggi') ? 'success' : (str_contains($row['kategori_aktivitas'], 'Sedang') ? 'info' : 'warning') ?>-subtle">
                                                <?= htmlspecialchars($row['kategori_aktivitas']) ?>
                                            </span>
                                        </td>
                                        <td class="text-muted"><?= $row['created_at'] ?? '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Belum ada data aktivitas fisik.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 9: Stres PSS-10 -->
        <div class="tab-pane fade" id="tab-stres" role="tabpanel">
            <div class="card overflow-hidden">
                <div class="card-editorial-header d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0" style="color: var(--primary);">Daftar Penilaian Tingkat Stres (PSS-10)</h6>
                    <a href="../stres/stres_form.php" class="btn btn-sm btn-primary-health"><i class="bi bi-plus-lg me-1"></i>Penilaian Baru</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Nama Pengguna</th>
                                <th>Skor PSS</th>
                                <th>Tingkat Stres</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($listStres) > 0): ?>
                                <?php foreach ($listStres as $row): ?>
                                    <tr>
                                        <td><strong>#<?= $row['id_stres'] ?></strong></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_user']) ?></td>
                                        <td><span class="badge border fs-6 text-dark" style="background-color: var(--lavender-light);"><?= $row['skor_stres'] ?> / 40</span></td>
                                        <td>
                                            <span class="badge bg-<?= $row['skor_stres'] <= 13 ? 'success' : ($row['skor_stres'] <= 26 ? 'warning' : 'danger') ?>-subtle">
                                                <?= htmlspecialchars($row['tingkat_stres']) ?>
                                            </span>
                                        </td>
                                        <td class="text-muted"><?= $row['created_at'] ?? '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Belum ada data tingkat stres.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
