<?php
$pageTitle = 'Pelacak Kebiasaan Sehat (Habits Tracker) — HealthCheck';
$activePage = 'habits';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/login_cek.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/habit_helper.php';

$userId = current_user_id();
if (!$userId) {
    header("Location: ../auth/login.php?pesan=belum_login");
    exit;
}

// Handle selected date (default today)
$selectedDate = trim($_GET['date'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = date('Y-m-d');
}

// Handle selected week for weekly summary
$selectedWeekStart = trim($_GET['week'] ?? date('Y-m-d', strtotime('monday this week', strtotime($selectedDate))));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedWeekStart)) {
    $selectedWeekStart = date('Y-m-d', strtotime('monday this week'));
}

$pesan = '';
$error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $action = trim($_POST['action'] ?? '');

    if ($action === 'log_habit') {
        $habitId = (int)($_POST['habit_id'] ?? 0);
        $actualVal = max(0, (float)($_POST['nilai_aktual'] ?? 0));
        $notes = !empty($_POST['catatan']) ? trim($_POST['catatan']) : null;
        $logDate = trim($_POST['tanggal'] ?? $selectedDate);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $logDate)) {
            $logDate = $selectedDate;
        }

        if ($habitId > 0) {
            $ok = log_habit_actual($pdo, $userId, $habitId, $logDate, $actualVal, $notes);
            if ($ok) {
                header("Location: habits.php?date=" . urlencode($logDate) . "&week=" . urlencode($selectedWeekStart) . "&pesan=log_saved");
                exit;
            } else {
                $error = 'Gagal menyimpan pencatatan kebiasaan.';
            }
        } else {
            $error = 'Data kebiasaan tidak valid.';
        }
    } elseif ($action === 'create_habit') {
        $name = trim($_POST['nama_habit'] ?? '');
        $desc = trim($_POST['deskripsi'] ?? '');
        $unit = trim($_POST['satuan'] ?? 'kali');
        $target = max(0.1, (float)($_POST['target_harian'] ?? 1));
        $color = trim($_POST['warna'] ?? 'blue');
        $icon = trim($_POST['ikon'] ?? 'bi-check2-circle');

        if (!empty($name) && !empty($unit) && $target > 0) {
            $stmt = $pdo->prepare("INSERT INTO health_habits 
                (user_id, code, nama_habit, deskripsi, ikon, warna, satuan, target_harian, is_active, is_custom) 
                VALUES (?, 'custom', ?, ?, ?, ?, ?, ?, 1, 1)");
            $stmt->execute([$userId, $name, $desc, $icon, $color, $unit, $target]);
            header("Location: habits.php?date=" . urlencode($selectedDate) . "&week=" . urlencode($selectedWeekStart) . "&pesan=habit_created");
            exit;
        } else {
            $error = 'Nama kebiasaan, satuan, dan target harian wajib diisi.';
        }
    } elseif ($action === 'update_habit') {
        $habitId = (int)($_POST['habit_id'] ?? 0);
        $target = max(0.1, (float)($_POST['target_harian'] ?? 1));
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $name = trim($_POST['nama_habit'] ?? '');

        $habit = get_habit_by_id($pdo, $habitId, $userId);
        if ($habit) {
            $updName = ($habit['is_custom'] == 1 && !empty($name)) ? $name : $habit['nama_habit'];
            $stmt = $pdo->prepare("UPDATE health_habits SET target_harian = ?, is_active = ?, nama_habit = ?, updated_at = CURRENT_TIMESTAMP WHERE id_habit = ? AND user_id = ?");
            $stmt->execute([$target, $isActive, $updName, $habitId, $userId]);
            header("Location: habits.php?date=" . urlencode($selectedDate) . "&week=" . urlencode($selectedWeekStart) . "&pesan=habit_updated");
            exit;
        } else {
            $error = 'Kebiasaan tidak ditemukan atau tidak memiliki hak akses.';
        }
    } elseif ($action === 'delete_habit') {
        $habitId = (int)($_POST['habit_id'] ?? 0);
        $habit = get_habit_by_id($pdo, $habitId, $userId);
        if ($habit && $habit['is_custom'] == 1) {
            $stmt = $pdo->prepare("DELETE FROM health_habits WHERE id_habit = ? AND user_id = ?");
            $stmt->execute([$habitId, $userId]);
            header("Location: habits.php?date=" . urlencode($selectedDate) . "&week=" . urlencode($selectedWeekStart) . "&pesan=habit_deleted");
            exit;
        } else {
            $error = 'Kebiasaan bawaan sistem tidak dapat dihapus, hanya dapat dinonaktifkan.';
        }
    }
}

// Fetch active daily habits progress for selected date
$dailyHabits = get_user_daily_habit_status($pdo, $userId, $selectedDate);

// Fetch weekly summary
$weeklySummary = get_weekly_habit_summary($pdo, $userId, $selectedWeekStart);

// Fetch all habits (including inactive) for management
$allHabits = get_user_habits($pdo, $userId, false);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Pelacak Kebiasaan Sehat</li>
        </ol>
    </nav>

    <?php if (isset($_GET['pesan'])): ?>
        <?php if ($_GET['pesan'] === 'log_saved'): ?>
            <div class="alert alert-success alert-dismissible fade show small mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>Pencatatan kebiasaan berhasil disimpan!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['pesan'] === 'habit_created'): ?>
            <div class="alert alert-success alert-dismissible fade show small mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>Kebiasaan personal baru berhasil ditambahkan!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['pesan'] === 'habit_updated'): ?>
            <div class="alert alert-success alert-dismissible fade show small mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>Pengaturan kebiasaan berhasil diperbarui!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['pesan'] === 'habit_deleted'): ?>
            <div class="alert alert-success alert-dismissible fade show small mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>Kebiasaan personal berhasil dihapus.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show small mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Welcome Header & Date Selector -->
    <div class="p-4 mb-4 rounded-3 border" style="background-color: var(--surface); border-color: var(--border) !important; box-shadow: var(--shadow-xs);">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <span class="eyebrow-text">Gaya Hidup & Rutinitas</span>
                <h3 class="fw-bold mb-1" style="color: var(--primary);">Health Habits Tracker</h3>
                <p class="small text-muted mb-0">
                    Bangun dan pantau kebiasaan sehat harian Anda secara terukur tanpa tekanan sistem berorientasi hukuman (*positive habit progression*).
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="checkin.php?date=<?= urlencode($selectedDate) ?>" class="btn btn-primary-health btn-sm">
                    <i class="bi bi-pencil-square me-1"></i> Daily Health Check-in
                </a>
                <button type="button" class="btn btn-secondary-soft btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddHabit">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Habit Baru
                </button>
                <a href="reminders.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-bell me-1"></i> Atur Pengingat
                </a>
            </div>
        </div>

        <hr style="border-color: rgba(229, 232, 232, 0.7); margin: 1.25rem 0 1rem;">

        <!-- Date Navigation Bar -->
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="small fw-semibold text-muted">Tanggal Pantauan:</span>
                <span class="badge border px-3 py-2 text-dark bg-white" style="font-size: 0.85rem;">
                    <i class="bi bi-calendar-event text-primary me-1"></i>
                    <?= date('l, d F Y', strtotime($selectedDate)) ?>
                    <?= ($selectedDate === date('Y-m-d')) ? '<span class="badge bg-success-subtle text-success ms-1">Hari Ini</span>' : '' ?>
                </span>
            </div>
            <div class="d-flex align-items-center gap-1">
                <a href="habits.php?date=<?= date('Y-m-d', strtotime('-1 day', strtotime($selectedDate))) ?>&week=<?= urlencode($selectedWeekStart) ?>" class="btn btn-outline-secondary btn-sm" title="Hari Sebelumnya">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <a href="habits.php?date=<?= date('Y-m-d') ?>&week=<?= urlencode($selectedWeekStart) ?>" class="btn btn-outline-secondary btn-sm px-2">
                    Hari Ini
                </a>
                <a href="habits.php?date=<?= date('Y-m-d', strtotime('+1 day', strtotime($selectedDate))) ?>&week=<?= urlencode($selectedWeekStart) ?>" class="btn btn-outline-secondary btn-sm" title="Hari Berikutnya">
                    <i class="bi bi-chevron-right"></i>
                </a>
                <form action="habits.php" method="GET" class="d-inline-flex ms-2">
                    <input type="hidden" name="week" value="<?= htmlspecialchars($selectedWeekStart) ?>">
                    <input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars($selectedDate) ?>" onchange="this.form.submit()">
                </form>
            </div>
        </div>
    </div>

    <!-- Section 1: Today's / Selected Date's Habits Cards -->
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0" style="color: var(--primary);">Progres Kebiasaan Tanggal <?= date('d M Y', strtotime($selectedDate)) ?></h5>
            <span class="text-muted small">Target harian dapat disesuaikan kebutuhan fisik Anda</span>
        </div>

        <div class="row g-3">
            <?php foreach ($dailyHabits as $h): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card p-3 h-100 d-flex flex-column" style="background-color: var(--surface); border: 1px solid var(--border) !important;">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="tool-icon m-0" style="width: 36px; height: 36px; font-size: 1rem; background-color: var(--<?= $h['warna'] ?>-light, var(--primary-light)); color: var(--<?= $h['warna'] ?>);">
                                <i class="bi <?= htmlspecialchars($h['ikon']) ?>"></i>
                            </div>
                            <?php if ($h['is_target_tercapai']): ?>
                                <span class="badge bg-success-subtle text-success border">
                                    <i class="bi bi-check2 me-1"></i>Tercapai
                                </span>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border">
                                    <?= $h['progress_percent'] ?>%
                                </span>
                            <?php endif; ?>
                        </div>

                        <h6 class="fw-bold text-dark mb-1" style="font-size: 0.95rem;"><?= htmlspecialchars($h['nama_habit']) ?></h6>
                        <p class="small text-muted mb-2" style="font-size: 0.775rem; min-height: 32px; line-height: 1.4;">
                            <?= htmlspecialchars($h['deskripsi'] ?: 'Kebiasaan rutin harian') ?>
                        </p>

                        <div class="mt-auto">
                            <div class="d-flex justify-content-between align-items-baseline mb-1">
                                <span class="fs-5 fw-bold text-primary">
                                    <?= rtrim(rtrim(number_format($h['nilai_aktual'], 1), '0'), '.') ?>
                                    <small class="text-muted fw-normal" style="font-size: 0.75rem;"><?= htmlspecialchars($h['satuan']) ?></small>
                                </span>
                                <span class="small text-muted" style="font-size: 0.75rem;">
                                    Target: <?= rtrim(rtrim(number_format($h['target_harian'], 1), '0'), '.') ?> <?= htmlspecialchars($h['satuan']) ?>
                                </span>
                            </div>

                            <!-- Progress Bar -->
                            <div class="progress mb-3" style="height: 6px; background-color: #E2E8F0;">
                                <div class="progress-bar bg-<?= ($h['is_target_tercapai'] ? 'success' : 'primary') ?>" role="progressbar" style="width: <?= min(100, $h['progress_percent']) ?>%;" aria-valuenow="<?= $h['progress_percent'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>

                            <!-- Quick Update Form -->
                            <form action="habits.php" method="POST" class="d-flex gap-1">
                                <?= csrf_input() ?>
                                <input type="hidden" name="action" value="log_habit">
                                <input type="hidden" name="habit_id" value="<?= $h['id_habit'] ?>">
                                <input type="hidden" name="tanggal" value="<?= htmlspecialchars($selectedDate) ?>">
                                <input type="number" step="any" name="nilai_aktual" class="form-control form-control-sm" placeholder="<?= $h['satuan'] ?>" value="<?= $h['nilai_aktual'] > 0 ? $h['nilai_aktual'] : '' ?>" required>
                                <button type="submit" class="btn btn-primary-health btn-sm px-2" title="Simpan Pencatatan">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Section 2: Weekly Habit Summary & Charts -->
    <div class="card p-4 p-md-5 mb-5 overflow-hidden">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <span class="eyebrow-text">Rekapitulasi 7 Hari</span>
                <h4 class="card-heading mb-1">Ringkasan & Tren Mingguan</h4>
                <p class="small text-muted mb-0">
                    Periode: <strong><?= date('d M Y', strtotime($weeklySummary['start_date'])) ?> – <?= date('d M Y', strtotime($weeklySummary['end_date'])) ?></strong>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="habits.php?date=<?= urlencode($selectedDate) ?>&week=<?= urlencode($weeklySummary['prev_week_start']) ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-chevron-left me-1"></i> Minggu Lalu
                </a>
                <a href="habits.php?date=<?= urlencode($selectedDate) ?>&week=<?= date('Y-m-d', strtotime('monday this week')) ?>" class="btn btn-outline-secondary btn-sm">
                    Minggu Ini
                </a>
                <a href="habits.php?date=<?= urlencode($selectedDate) ?>&week=<?= urlencode($weeklySummary['next_week_start']) ?>" class="btn btn-outline-secondary btn-sm">
                    Minggu Depan <i class="bi bi-chevron-right ms-1"></i>
                </a>
            </div>
        </div>

        <!-- 4 Key Weekly Metric Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="p-3 rounded-3 border" style="background-color: var(--light-bg); border-color: var(--border) !important;">
                    <span class="small text-muted d-block mb-1"><i class="bi bi-droplet-half text-teal me-1"></i>Total Hidrasi</span>
                    <h4 class="fw-bold mb-0 text-dark"><?= $weeklySummary['total_water_liter'] ?> <small class="text-muted fs-6 fw-normal">Liter</small></h4>
                    <span class="small text-muted" style="font-size:0.75rem;"><?= number_format($weeklySummary['total_water_ml'], 0, ',', '.') ?> ml terkonsumsi</span>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="p-3 rounded-3 border" style="background-color: var(--light-bg); border-color: var(--border) !important;">
                    <span class="small text-muted d-block mb-1"><i class="bi bi-moon-stars text-blue me-1"></i>Rata-rata Tidur</span>
                    <h4 class="fw-bold mb-0 text-dark"><?= $weeklySummary['avg_sleep_hours'] ?> <small class="text-muted fs-6 fw-normal">Jam/malam</small></h4>
                    <span class="small text-muted" style="font-size:0.75rem;">Standar ideal: 7–9 jam</span>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="p-3 rounded-3 border" style="background-color: var(--light-bg); border-color: var(--border) !important;">
                    <span class="small text-muted d-block mb-1"><i class="bi bi-person-walking text-emerald me-1"></i>Rata-rata Langkah</span>
                    <h4 class="fw-bold mb-0 text-dark"><?= number_format($weeklySummary['avg_steps'], 0, ',', '.') ?> <small class="text-muted fs-6 fw-normal">Langkah</small></h4>
                    <span class="small text-muted" style="font-size:0.75rem;">Aktivitas harian</span>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="p-3 rounded-3 border" style="background-color: var(--light-bg); border-color: var(--border) !important;">
                    <span class="small text-muted d-block mb-1"><i class="bi bi-activity text-coral me-1"></i>Total Olahraga</span>
                    <h4 class="fw-bold mb-0 text-dark"><?= $weeklySummary['total_activity_minutes'] ?> <small class="text-muted fs-6 fw-normal">Menit</small></h4>
                    <span class="small text-muted" style="font-size:0.75rem;">Target WHO: 150 mnt/mgg</span>
                </div>
            </div>
        </div>

        <!-- Weekly Summary Badges -->
        <div class="d-flex flex-wrap gap-2 mb-4 p-3 rounded-3 border" style="background-color: var(--surface);">
            <div class="d-flex align-items-center gap-2 me-4">
                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                <span class="small text-muted">Target Tercapai Minggu Ini: <strong class="text-dark"><?= $weeklySummary['targets_achieved_count'] ?> kali</strong></span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-journal-check text-primary fs-5"></i>
                <span class="small text-muted">Hari Check-in Terisi: <strong class="text-dark"><?= $weeklySummary['checkin_days_count'] ?> dari 7 hari</strong></span>
            </div>
        </div>

        <!-- Charts Grid (Separated by Units, Zero Dummy Data) -->
        <?php if ($weeklySummary['has_sufficient_data']): ?>
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="border rounded-3 p-3 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-bold text-dark"><i class="bi bi-droplet-half text-teal me-1"></i>Konsumsi Air Harian</span>
                            <span class="badge border bg-light text-muted">Satuan: ml</span>
                        </div>
                        <div style="height: 180px; position: relative;">
                            <canvas id="chartWeeklyWater"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="border rounded-3 p-3 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-bold text-dark"><i class="bi bi-moon-stars text-blue me-1"></i>Durasi Tidur</span>
                            <span class="badge border bg-light text-muted">Satuan: Jam</span>
                        </div>
                        <div style="height: 180px; position: relative;">
                            <canvas id="chartWeeklySleep"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="border rounded-3 p-3 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-bold text-dark"><i class="bi bi-person-walking text-emerald me-1"></i>Jumlah Langkah Kaki</span>
                            <span class="badge border bg-light text-muted">Satuan: Langkah</span>
                        </div>
                        <div style="height: 180px; position: relative;">
                            <canvas id="chartWeeklySteps"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="border rounded-3 p-3 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-bold text-dark"><i class="bi bi-activity text-coral me-1"></i>Aktivitas Fisik / Olahraga</span>
                            <span class="badge border bg-light text-muted">Satuan: Menit</span>
                        </div>
                        <div style="height: 180px; position: relative;">
                            <canvas id="chartWeeklyActivity"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="p-5 text-center rounded-3 bg-light text-muted" style="border: 1px dashed var(--border);">
                <i class="bi bi-bar-chart fs-2 d-block mb-2 text-muted"></i>
                <h6 class="fw-bold text-dark mb-1">Belum Cukup Data Mingguan</h6>
                <p class="small text-muted mb-3" style="max-width: 480px; margin: 0 auto;">
                    Catat kebiasaan harian Anda minimal 2 hari pada minggu ini untuk menampilkan visualisasi grafik perkembangan hidup sehat Anda.
                </p>
                <a href="checkin.php?date=<?= urlencode($selectedDate) ?>" class="btn btn-primary-health btn-sm">
                    Isi Daily Health Check-in Sekarang
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Section 3: Manage Habits Settings (Target & Custom Habits) -->
    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h6 class="fw-bold mb-0 text-dark">Kelola Pengaturan & Target Kebiasaan</h6>
                <span class="small text-muted">Sesuaikan target atau nonaktifkan kebiasaan yang tidak ingin dipantau</span>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddHabit">
                <i class="bi bi-plus me-1"></i> Habit Baru
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead>
                    <tr>
                        <th>Kebiasaan</th>
                        <th>Target Harian</th>
                        <th>Satuan</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allHabits as $hab): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="tool-icon m-0" style="width: 28px; height: 28px; font-size: 0.85rem; background-color: var(--<?= $hab['warna'] ?>-light, var(--primary-light)); color: var(--<?= $hab['warna'] ?>);">
                                        <i class="bi <?= htmlspecialchars($hab['ikon']) ?>"></i>
                                    </div>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($hab['nama_habit']) ?></span>
                                </div>
                            </td>
                            <td>
                                <strong><?= $hab['target_harian'] ?></strong>
                            </td>
                            <td><?= htmlspecialchars($hab['satuan']) ?></td>
                            <td>
                                <span class="badge border bg-light text-muted">
                                    <?= ($hab['is_custom'] == 1) ? 'Personal' : 'Bawaan Sistem' ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($hab['is_active'] == 1): ?>
                                    <span class="badge bg-success-subtle text-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalEditHabit<?= $hab['id_habit'] ?>">
                                    Edit
                                </button>
                                <?php if ($hab['is_custom'] == 1): ?>
                                    <form action="habits.php" method="POST" class="d-inline" onsubmit="return confirm('Hapus kebiasaan personal ini?');">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="action" value="delete_habit">
                                        <input type="hidden" name="habit_id" value="<?= $hab['id_habit'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2 ms-1">
                                            Hapus
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Modal Edit Habit -->
                        <div class="modal fade" id="modalEditHabit<?= $hab['id_habit'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border">
                                    <form action="habits.php" method="POST">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="action" value="update_habit">
                                        <input type="hidden" name="habit_id" value="<?= $hab['id_habit'] ?>">
                                        <div class="modal-header border-bottom">
                                            <h6 class="modal-title fw-bold">Edit Kebiasaan: <?= htmlspecialchars($hab['nama_habit']) ?></h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <?php if ($hab['is_custom'] == 1): ?>
                                                <div class="mb-3">
                                                    <label class="form-label small">Nama Kebiasaan</label>
                                                    <input type="text" name="nama_habit" class="form-control form-control-sm" value="<?= htmlspecialchars($hab['nama_habit']) ?>" required>
                                                </div>
                                            <?php endif; ?>
                                            <div class="mb-3">
                                                <label class="form-label small">Target Harian (Satuan: <?= htmlspecialchars($hab['satuan']) ?>)</label>
                                                <input type="number" step="any" name="target_harian" class="form-control form-control-sm" value="<?= $hab['target_harian'] ?>" required>
                                            </div>
                                            <div class="form-check form-switch mt-3">
                                                <input class="form-check-input" type="checkbox" name="is_active" id="activeHabit<?= $hab['id_habit'] ?>" value="1" <?= $hab['is_active'] == 1 ? 'checked' : '' ?>>
                                                <label class="form-check-label small" for="activeHabit<?= $hab['id_habit'] ?>">Aktifkan kebiasaan ini dalam pantauan harian</label>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-top">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary-health btn-sm px-4">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Add Custom Habit -->
<div class="modal fade" id="modalAddHabit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border">
            <form action="habits.php" method="POST">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="create_habit">
                <div class="modal-header border-bottom">
                    <h6 class="modal-title fw-bold">Tambah Kebiasaan Personal Baru</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small">Nama Kebiasaan</label>
                        <input type="text" name="nama_habit" class="form-control form-control-sm" placeholder="Contoh: Meditasi Pagi, Membaca Buku..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Deskripsi Singkat (Opsional)</label>
                        <input type="text" name="deskripsi" class="form-control form-control-sm" placeholder="Tujuan atau manfaat kebiasaan ini">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small">Satuan Pengukuran</label>
                            <input type="text" name="satuan" class="form-control form-control-sm" placeholder="menit / kali / porsi / halaman" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Target Harian</label>
                            <input type="number" step="any" name="target_harian" class="form-control form-control-sm" placeholder="Contoh: 15" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small">Aksen Warna</label>
                            <select name="warna" class="form-select form-select-sm">
                                <option value="blue">Blue</option>
                                <option value="teal">Teal</option>
                                <option value="emerald">Emerald</option>
                                <option value="coral">Coral</option>
                                <option value="lavender">Lavender</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Ikon</label>
                            <select name="ikon" class="form-select form-select-sm">
                                <option value="bi-check2-circle">Centang (Check)</option>
                                <option value="bi-book">Buku (Book)</option>
                                <option value="bi-cup-hot">Minuman (Cup)</option>
                                <option value="bi-apple">Buah/Makanan (Apple)</option>
                                <option value="bi-heart">Kesehatan (Heart)</option>
                                <option value="bi-sun">Pagi Hari (Sun)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-health btn-sm px-4">Simpan Kebiasaan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Chart.js for Weekly Visualization -->
<?php if ($weeklySummary['has_sufficient_data']): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const labels = <?= json_encode($weeklySummary['day_labels']) ?>;

// Chart 1: Water
new Chart(document.getElementById('chartWeeklyWater').getContext('2d'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Konsumsi Air (ml)',
            data: <?= json_encode($weeklySummary['water_series']) ?>,
            backgroundColor: '#168C89',
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { grid: { color: '#E2E8F0' }, beginAtZero: true },
            x: { grid: { display: false } }
        }
    }
});

// Chart 2: Sleep
new Chart(document.getElementById('chartWeeklySleep').getContext('2d'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Durasi Tidur (Jam)',
            data: <?= json_encode($weeklySummary['sleep_series']) ?>,
            borderColor: '#347FC2',
            backgroundColor: 'rgba(52, 127, 194, 0.1)',
            fill: true,
            tension: 0.3,
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { grid: { color: '#E2E8F0' }, beginAtZero: true },
            x: { grid: { display: false } }
        }
    }
});

// Chart 3: Steps
new Chart(document.getElementById('chartWeeklySteps').getContext('2d'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Langkah Kaki',
            data: <?= json_encode($weeklySummary['steps_series']) ?>,
            backgroundColor: '#27845E',
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { grid: { color: '#E2E8F0' }, beginAtZero: true },
            x: { grid: { display: false } }
        }
    }
});

// Chart 4: Activity
new Chart(document.getElementById('chartWeeklyActivity').getContext('2d'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Olahraga (Menit)',
            data: <?= json_encode($weeklySummary['activity_series']) ?>,
            backgroundColor: '#D75F50',
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { grid: { color: '#E2E8F0' }, beginAtZero: true },
            x: { grid: { display: false } }
        }
    }
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
