<?php
$pageTitle = 'Dashboard Administrator — HealthCheck';
$activePage = 'admin';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/login_cek.php';
require_once __DIR__ . '/../auth/role.php';

if (!is_admin()) {
    header("Location: ../auth/login.php?pesan=belum_login");
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
        <div class="col-6 col-lg-3">
            <div class="card p-3">
                <div class="small fw-semibold" style="color: var(--muted);">Rekam BMI</div>
                <div class="fs-3 fw-bold mt-1" style="color: var(--primary);"><?= $totalBmi ?></div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card p-3">
                <div class="small fw-semibold" style="color: var(--muted);">Rekam BMR & Kalori</div>
                <div class="fs-3 fw-bold mt-1" style="color: var(--primary);"><?= $totalBmr ?></div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card p-3">
                <div class="small fw-semibold" style="color: var(--muted);">Rekam Detak Jantung</div>
                <div class="fs-3 fw-bold mt-1" style="color: var(--primary);"><?= $totalJantung ?></div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card p-3">
                <div class="small fw-semibold" style="color: var(--muted);">Rekam Skrining Mental</div>
                <div class="fs-3 fw-bold mt-1" style="color: var(--primary);"><?= $totalMental ?></div>
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
                Data BMR & Kalori (<?= count($listBmr) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="jantung-tab" data-bs-toggle="tab" data-bs-target="#tab-jantung" type="button" role="tab">
                Detak Jantung (<?= count($listJantung) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="mental-tab" data-bs-toggle="tab" data-bs-target="#tab-mental" type="button" role="tab">
                Skrining Mental (<?= count($listMental) ?>)
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
                                            <a href="../bmi/bmi_hapus.php?id=<?= $row['id_bmi'] ?>" class="btn btn-light btn-sm border" onclick="return confirm('Hapus data BMI ini?')" title="Hapus" style="color: var(--danger);">
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
                                            <a href="../bmr/bmr_hapus.php?id=<?= $row['id_bmr'] ?>" class="btn btn-light btn-sm border" onclick="return confirm('Hapus data BMR ini?')" title="Hapus" style="color: var(--danger);">
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

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
