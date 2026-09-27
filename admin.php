<?php
$pageTitle = 'Dashboard Administrator — HealthCheck';
$activePage = 'admin';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/login_cek.php';
require_once __DIR__ . '/role.php';

if (!is_admin()) {
    header("Location: login.php?pesan=belum_login");
    exit;
}

// Ambil data untuk statistik dashboard
$totalBmi = $pdo->query("SELECT COUNT(*) FROM cek_bmi")->fetchColumn();
$totalBmr = $pdo->query("SELECT COUNT(*) FROM cek_bmr")->fetchColumn();
$totalJantung = $pdo->query("SELECT COUNT(*) FROM cek_jantung")->fetchColumn();
$totalMental = $pdo->query("SELECT COUNT(*) FROM cek_mental")->fetchColumn();

// Ambil list data
$listBmi = $pdo->query("SELECT * FROM cek_bmi ORDER BY id_bmi DESC")->fetchAll();
$listBmr = $pdo->query("SELECT * FROM cek_bmr ORDER BY id_bmr DESC")->fetchAll();
$listJantung = $pdo->query("SELECT * FROM cek_jantung ORDER BY id_jantung DESC")->fetchAll();
$listMental = $pdo->query("SELECT * FROM cek_mental ORDER BY id_mental DESC")->fetchAll();

$pesan = $_GET['pesan'] ?? '';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item active" aria-current="page">Dashboard Admin</li>
        </ol>
    </nav>

    <!-- Header Panel -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-speedometer2 text-teal me-2"></i>Dashboard Manajemen HealthCheck
            </h3>
            <p class="text-secondary small mb-0">Kelola dan pantau seluruh data pemeriksaan kesehatan pengguna.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-outline-secondary btn-sm px-3">
                <i class="bi bi-house me-1"></i> Ke Halaman Utama
            </a>
            <a href="logout.php" class="btn btn-outline-danger btn-sm px-3">
                <i class="bi bi-box-arrow-right me-1"></i> Keluar
            </a>
        </div>
    </div>

    <!-- Alert Notifikasi -->
    <?php if ($pesan === 'berhasil_update'): ?>
        <div class="alert alert-success alert-dismissible fade show small rounded-3 mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> Data berhasil diperbarui!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($pesan === 'berhasil_hapus'): ?>
        <div class="alert alert-info alert-dismissible fade show small rounded-3 mb-4" role="alert">
            <i class="bi bi-trash-fill me-2"></i> Data berhasil dihapus dari sistem.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($pesan === 'bmr_update'): ?>
        <div class="alert alert-success alert-dismissible fade show small rounded-3 mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> Data BMR berhasil diperbarui!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($pesan === 'bmr_hapus'): ?>
        <div class="alert alert-info alert-dismissible fade show small rounded-3 mb-4" role="alert">
            <i class="bi bi-trash-fill me-2"></i> Data BMR berhasil dihapus.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Kartu Statistik -->
    <div class="row g-3 mb-5">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="tool-icon icon-teal m-0" style="width:42px; height:42px; font-size:1.2rem;">
                        <i class="bi bi-speedometer2"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Cek BMI</div>
                        <h4 class="fw-bold text-dark mb-0"><?= $totalBmi ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="tool-icon icon-blue m-0" style="width:42px; height:42px; font-size:1.2rem;">
                        <i class="bi bi-fire"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Cek BMR</div>
                        <h4 class="fw-bold text-dark mb-0"><?= $totalBmr ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="tool-icon icon-rose m-0" style="width:42px; height:42px; font-size:1.2rem;">
                        <i class="bi bi-heart-pulse"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Detak Jantung</div>
                        <h4 class="fw-bold text-dark mb-0"><?= $totalJantung ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="tool-icon icon-purple m-0" style="width:42px; height:42px; font-size:1.2rem;">
                        <i class="bi bi-emoji-smile"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Screening Mental</div>
                        <h4 class="fw-bold text-dark mb-0"><?= $totalMental ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs Data -->
    <ul class="nav nav-pills mb-4 gap-2" id="adminTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill px-4" id="bmi-tab" data-bs-toggle="pill" data-bs-target="#tab-bmi" type="button" role="tab">
                <i class="bi bi-speedometer2 me-1"></i> Data BMI (<?= count($listBmi) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4" id="bmr-tab" data-bs-toggle="pill" data-bs-target="#tab-bmr" type="button" role="tab">
                <i class="bi bi-fire me-1"></i> Data BMR & Kalori (<?= count($listBmr) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4" id="jantung-tab" data-bs-toggle="pill" data-bs-target="#tab-jantung" type="button" role="tab">
                <i class="bi bi-heart-pulse me-1"></i> Detak Jantung (<?= count($listJantung) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4" id="mental-tab" data-bs-toggle="pill" data-bs-target="#tab-mental" type="button" role="tab">
                <i class="bi bi-emoji-smile me-1"></i> Screening Mental (<?= count($listMental) ?>)
            </button>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="adminTabContent">
        
        <!-- Tab 1: BMI -->
        <div class="tab-pane fade show active" id="tab-bmi" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">Daftar Pemeriksaan Indeks Massa Tubuh (BMI)</h6>
                    <a href="bmi_form.php" class="btn btn-sm btn-primary-health"><i class="bi bi-plus-lg me-1"></i>Tambah Cek BMI</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
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
                        <tbody class="small">
                            <?php if (count($listBmi) > 0): ?>
                                <?php foreach ($listBmi as $row): ?>
                                    <tr>
                                        <td><strong>#<?= $row['id_bmi'] ?></strong></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_user']) ?></td>
                                        <td><?= $row['umur'] ?> thn</td>
                                        <td><?= $row['berat_badan'] ?> kg</td>
                                        <td><?= $row['tinggi_badan'] ?> cm</td>
                                        <td><span class="badge bg-light text-dark border"><?= $row['bmi'] ?? round($row['berat_badan'] / (($row['tinggi_badan']/100)*($row['tinggi_badan']/100)), 1) ?></span></td>
                                        <td>
                                            <span class="badge bg-<?= str_contains($row['kondisi'], 'Normal') ? 'success' : (str_contains($row['kondisi'], 'Kurus') ? 'warning' : 'danger') ?>-subtle text-<?= str_contains($row['kondisi'], 'Normal') ? 'success' : (str_contains($row['kondisi'], 'Kurus') ? 'warning' : 'danger') ?>-emphasis">
                                                <?= htmlspecialchars($row['kondisi']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="bmi_edit.php?id=<?= $row['id_bmi'] ?>" class="btn btn-light btn-sm border me-1" title="Edit">
                                                <i class="bi bi-pencil text-warning"></i>
                                            </a>
                                            <a href="bmi_hapus.php?id=<?= $row['id_bmi'] ?>" class="btn btn-light btn-sm border text-danger" onclick="return confirm('Hapus data BMI ini?')" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </a>
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
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">Daftar Pemeriksaan BMR & Kalori</h6>
                    <a href="bmr_form.php" class="btn btn-sm btn-primary-health"><i class="bi bi-plus-lg me-1"></i>Tambah Cek BMR</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
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
                        <tbody class="small">
                            <?php if (count($listBmr) > 0): ?>
                                <?php foreach ($listBmr as $row): ?>
                                    <tr>
                                        <td><strong>#<?= $row['id_bmr'] ?></strong></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_user']) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= $row['jenis_kelamin'] ?></span></td>
                                        <td><?= $row['umur'] ?> thn</td>
                                        <td><?= $row['berat_badan'] ?> kg</td>
                                        <td><?= $row['tinggi_badan'] ?> cm</td>
                                        <td><strong class="text-primary"><?= number_format($row['bmr'], 0, ',', '.') ?></strong> kkal</td>
                                        <td><?= isset($row['tdee']) && $row['tdee'] > 0 ? number_format($row['tdee'], 0, ',', '.') . ' kkal' : '-' ?></td>
                                        <td class="text-end">
                                            <a href="bmr_edit.php?id=<?= $row['id_bmr'] ?>" class="btn btn-light btn-sm border me-1" title="Edit">
                                                <i class="bi bi-pencil text-warning"></i>
                                            </a>
                                            <a href="bmr_hapus.php?id=<?= $row['id_bmr'] ?>" class="btn btn-light btn-sm border text-danger" onclick="return confirm('Hapus data BMR ini?')" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </a>
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

        <!-- Tab 3: Jantung -->
        <div class="tab-pane fade" id="tab-jantung" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">Daftar Pemeriksaan Detak Jantung</h6>
                    <a href="jantung_form.php" class="btn btn-sm btn-primary-health"><i class="bi bi-plus-lg me-1"></i>Cek Detak Jantung</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
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
                        <tbody class="small">
                            <?php if (count($listJantung) > 0): ?>
                                <?php foreach ($listJantung as $row): ?>
                                    <tr>
                                        <td><strong>#<?= $row['id_jantung'] ?></strong></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_user']) ?></td>
                                        <td><?= $row['umur'] ?> thn</td>
                                        <td><span class="badge bg-danger-subtle text-danger fs-6"><?= $row['resting_hr'] ?> BPM</span></td>
                                        <td><?= $row['max_hr'] ?> BPM</td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['kategori']) ?></span></td>
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
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">Daftar Screening Kesehatan Mental (PHQ-4)</h6>
                    <a href="mental_form.php" class="btn btn-sm btn-primary-health"><i class="bi bi-plus-lg me-1"></i>Screening Baru</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
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
                        <tbody class="small">
                            <?php if (count($listMental) > 0): ?>
                                <?php foreach ($listMental as $row): ?>
                                    <tr>
                                        <td><strong>#<?= $row['id_mental'] ?></strong></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_user']) ?></td>
                                        <td><?= $row['skor_cemas'] ?> / 6</td>
                                        <td><?= $row['skor_depresi'] ?> / 6</td>
                                        <td><span class="badge bg-purple-subtle text-purple fs-6 border" style="background:#f3e8ff; color:#7e22ce;"><?= $row['total_skor'] ?> / 12</span></td>
                                        <td>
                                            <span class="badge bg-<?= $row['total_skor'] <= 2 ? 'success' : ($row['total_skor'] <= 5 ? 'info' : ($row['total_skor'] <= 8 ? 'warning' : 'danger')) ?>-subtle text-dark">
                                                <?= htmlspecialchars($row['tingkat']) ?>
                                            </span>
                                        </td>
                                        <td class="text-muted"><?= $row['created_at'] ?? '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Belum ada data screening mental.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
