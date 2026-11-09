<?php
$pageTitle = 'Personal Health Dashboard — HealthCheck';
$activePage = 'dashboard';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/login_cek.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/history_helper.php';
require_once __DIR__ . '/../includes/habit_helper.php';
require_once __DIR__ . '/../includes/goal_helper.php';

$userId = current_user_id();
if (!$userId) {
    header("Location: ../auth/login.php?pesan=belum_login");
    exit;
}

$profile = get_current_user_profile($pdo);
$username = $_SESSION['username'] ?? 'Member';
$displayName = !empty($profile['nama_lengkap']) ? $profile['nama_lengkap'] : $username;
$calcAge = !empty($profile['tanggal_lahir']) ? calculate_age_from_dob($profile['tanggal_lahir']) : null;

// Fetch Phase 4 Habit & Routine Data
$today = date('Y-m-d');
$todayCheckin = get_daily_checkin($pdo, $userId, $today);
$todayHabits = get_user_daily_habit_status($pdo, $userId, $today);
$weeklyHabits = get_weekly_habit_summary($pdo, $userId, date('Y-m-d', strtotime('monday this week')));
$activeReminders = get_user_reminders($pdo, $userId, true);
$unreadNotifs = get_user_in_app_notifications($pdo, $userId, 3, true);

// Fetch Phase 5 Health Goals Data
$featuredGoal = get_featured_goal($pdo, $userId);
$goalStats = get_goal_stats_summary($pdo, $userId);
$recentGoalActivities = get_recent_goal_activities($pdo, $userId, 3);
$nextMilestone = null;
if ($featuredGoal) {
    $fMilestones = get_goal_milestones($pdo, $userId, (int)$featuredGoal['id_goal']);
    foreach ($fMilestones as $fms) {
        if ($fms['status'] !== 'completed') {
            $nextMilestone = $fms;
            break;
        }
    }
}

// Fetch latest indicators across all 12 tools (NO composite 0-100 score!)
$indicators = get_user_latest_indicators($pdo, $userId);

// Fetch recent 5 activities
$recentHistory = get_user_unified_history($pdo, $userId, ['limit' => 5, 'sort' => 'DESC']);

// Fetch chronological trends data for charts
$trends = get_user_health_trends($pdo, $userId);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item active" aria-current="page">Personal Health Dashboard</li>
        </ol>
    </nav>

    <!-- Welcome Header & Member Snapshot -->
    <div class="p-4 mb-4 rounded-3 border" style="background-color: var(--surface); border-color: var(--border) !important; box-shadow: var(--shadow-xs);">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <span class="eyebrow-text">Personal Health Dashboard</span>
                <h3 class="fw-bold mb-1" style="color: var(--primary);">Halo, <?= htmlspecialchars($displayName) ?></h3>
                <p class="small text-muted mb-0">
                    Pantau ringkasan hasil biometrik mandiri, tren perkembangan kesehatan, dan riwayat pemeriksaan Anda secara terpadu.
                    <?php if ($calcAge !== null): ?>
                        <span class="ms-1">&bull; Usia: <strong><?= $calcAge ?> Tahun</strong></span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="goals.php" class="btn btn-primary-health btn-sm">
                    <i class="bi bi-flag me-1"></i> Target Kesehatan
                </a>
                <a href="checkin.php" class="btn btn-secondary-soft btn-sm">
                    <i class="bi bi-pencil-square me-1"></i> Daily Check-in
                </a>
                <a href="habits.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-check2-circle me-1"></i> Habits
                </a>
                <a href="reminders.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-bell me-1"></i> Pengingat
                </a>
                <a href="history.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-clock-history me-1"></i> Riwayat
                </a>
                <a href="report_download.php" class="btn btn-blue btn-sm">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Laporan PDF
                </a>
            </div>
        </div>
    </div>

    <!-- Section: Daily Health Routine, Habits & Reminders Overview (Phase 4 Integration) -->
    <div class="mb-5">
        <div class="row g-4">
            <!-- Left: Today's Habits & Check-in Status -->
            <div class="col-lg-8">
                <div class="card p-4 h-100">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
                        <div>
                            <span class="eyebrow-text">Rutinitas Hari Ini</span>
                            <h5 class="fw-bold mb-0 text-dark">Progres Kebiasaan Sehat (Today's Habits)</h5>
                        </div>
                        <a href="habits.php" class="editorial-link small">Kelola di Habits Tracker &rarr;</a>
                    </div>

                    <!-- Daily Check-in Banner Status -->
                    <div class="p-3 mb-3 rounded-3 border d-flex justify-content-between align-items-center" style="background-color: var(--light-bg); border-color: var(--border) !important;">
                        <div class="d-flex align-items-center gap-2">
                            <?php if ($todayCheckin): ?>
                                <i class="bi bi-check-circle-fill text-success fs-4"></i>
                                <div>
                                    <strong class="text-dark d-block small">Check-in Hari Ini Sudah Terisi</strong>
                                    <span class="text-muted" style="font-size: 0.725rem;">Tersimpan: <?= date('d M Y, H:i', strtotime($todayCheckin['updated_at'])) ?> WIB</span>
                                </div>
                            <?php else: ?>
                                <i class="bi bi-exclamation-circle text-warning fs-4"></i>
                                <div>
                                    <strong class="text-dark d-block small">Belum Mengisi Check-in Hari Ini</strong>
                                    <span class="text-muted" style="font-size: 0.725rem;">Catat air, tidur, langkah, dan aktivitas harian Anda dalam 1 menit.</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <a href="checkin.php" class="btn <?= $todayCheckin ? 'btn-outline-secondary' : 'btn-primary-health' ?> btn-sm px-3">
                            <?= $todayCheckin ? 'Perbarui Check-in' : 'Isi Check-in Sekarang' ?>
                        </a>
                    </div>

                    <!-- Habits Grid -->
                    <div class="row g-3">
                        <?php foreach (array_slice($todayHabits, 0, 4) as $th): ?>
                            <div class="col-sm-6">
                                <div class="p-3 rounded-3 border h-100 bg-white" style="border-color: rgba(229, 232, 232, 0.7) !important;">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="tool-icon m-0" style="width: 28px; height: 28px; font-size: 0.85rem; background-color: var(--<?= $th['warna'] ?>-light, var(--primary-light)); color: var(--<?= $th['warna'] ?>);">
                                                <i class="bi <?= htmlspecialchars($th['ikon']) ?>"></i>
                                            </div>
                                            <span class="fw-semibold text-dark small"><?= htmlspecialchars($th['nama_habit']) ?></span>
                                        </div>
                                        <?php if ($th['is_target_tercapai']): ?>
                                            <span class="badge bg-success-subtle text-success" style="font-size:0.65rem;">Tercapai</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border" style="font-size:0.65rem;"><?= $th['progress_percent'] ?>%</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-baseline mt-2 mb-1">
                                        <span class="fw-bold text-primary small">
                                            <?= rtrim(rtrim(number_format($th['nilai_aktual'], 1), '0'), '.') ?> <?= htmlspecialchars($th['satuan']) ?>
                                        </span>
                                        <span class="text-muted" style="font-size: 0.7rem;">
                                            Target: <?= rtrim(rtrim(number_format($th['target_harian'], 1), '0'), '.') ?>
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 5px; background-color: #E2E8F0;">
                                        <div class="progress-bar bg-<?= ($th['is_target_tercapai'] ? 'success' : 'primary') ?>" style="width: <?= min(100, $th['progress_percent']) ?>%;"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Right: Upcoming Reminders & In-App Alerts -->
            <div class="col-lg-4">
                <div class="card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <span class="eyebrow-text">Jadwal Pengingat</span>
                            <h5 class="fw-bold mb-0 text-dark">Upcoming Reminders</h5>
                        </div>
                        <a href="reminders.php" class="editorial-link small">Atur &rarr;</a>
                    </div>

                    <?php if (!empty($unreadNotifs)): ?>
                        <div class="p-2 mb-3 rounded-2 bg-primary-subtle text-primary small border border-primary-subtle d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-bell-fill me-1"></i> <?= count($unreadNotifs) ?> Notifikasi Baru</span>
                            <a href="reminders.php" class="text-primary fw-bold text-decoration-none" style="font-size: 0.75rem;">Buka &rarr;</a>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex flex-column gap-2 mb-3">
                        <?php if (!empty($activeReminders)): ?>
                            <?php foreach (array_slice($activeReminders, 0, 3) as $ar): ?>
                                <div class="p-2 rounded-2 border d-flex justify-content-between align-items-center" style="background-color: var(--surface);">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-primary small font-monospace"><?= htmlspecialchars($ar['jam_reminder']) ?></span>
                                        <span class="small fw-semibold text-dark text-truncate" style="max-width: 140px;"><?= htmlspecialchars($ar['judul']) ?></span>
                                    </div>
                                    <span class="badge bg-light text-muted border" style="font-size: 0.65rem;">Aktif</span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-3 text-muted small">
                                Belum ada pengingat aktif.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Weekly Progress Mini Box -->
                    <div class="mt-auto p-3 rounded-3 border" style="background-color: var(--light-bg); border-color: var(--border) !important;">
                        <span class="small text-muted d-block mb-1">Capaian Minggu Ini:</span>
                        <div class="d-flex justify-content-between align-items-center small">
                            <span>Target Tercapai: <strong class="text-success"><?= $weeklyHabits['targets_achieved_count'] ?> kali</strong></span>
                            <span>Check-in: <strong class="text-dark"><?= $weeklyHabits['checkin_days_count'] ?>/7 hari</strong></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section: My Health Goals & Featured Milestones (Phase 5 Integration) -->
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <span class="eyebrow-text">Sasaran & Tahapan</span>
                <h5 class="fw-bold mb-0" style="color: var(--primary);">My Health Goals</h5>
            </div>
            <a href="goals.php" class="editorial-link small">Kelola Seluruh Target &rarr;</a>
        </div>

        <div class="row g-4">
            <!-- Left: Featured Goal Card / Empty State -->
            <div class="col-lg-8">
                <?php if ($featuredGoal): ?>
                    <div class="card p-4 h-100 border bg-white shadow-xs">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="tool-category mb-0" style="color: var(--blue);">
                                <i class="bi bi-star-fill text-warning me-1"></i>Featured Goal &bull; <?= ucfirst($featuredGoal['category']) ?>
                            </span>
                            <span class="badge bg-primary-subtle text-primary border" style="font-size:0.7rem;">
                                <?= $featuredGoal['progress_status'] ?>
                            </span>
                        </div>

                        <h4 class="card-heading mb-1 text-dark" style="font-size: 1.25rem;">
                            <?= htmlspecialchars($featuredGoal['title']) ?>
                        </h4>
                        <p class="small text-muted mb-3" style="font-size:0.8rem; line-height:1.45;">
                            <?= htmlspecialchars($featuredGoal['description'] ?: 'Target pemeliharaan kesehatan mandiri') ?>
                        </p>

                        <!-- Values Row -->
                        <div class="row g-2 text-center p-3 rounded-3 border mb-3" style="background-color: var(--light-bg);">
                            <div class="col-4 border-end">
                                <span class="small text-muted d-block" style="font-size:0.7rem;">Awal</span>
                                <strong class="text-dark"><?= rtrim(rtrim(number_format((float)$featuredGoal['initial_value'], 1), '0'), '.') ?> <?= htmlspecialchars($featuredGoal['unit']) ?></strong>
                            </div>
                            <div class="col-4 border-end">
                                <span class="small text-muted d-block" style="font-size:0.7rem;">Terkini</span>
                                <strong class="text-primary fs-6"><?= rtrim(rtrim(number_format((float)$featuredGoal['current_value'], 1), '0'), '.') ?> <?= htmlspecialchars($featuredGoal['unit']) ?></strong>
                            </div>
                            <div class="col-4">
                                <span class="small text-muted d-block" style="font-size:0.7rem;">Sasaran</span>
                                <strong class="text-dark"><?= rtrim(rtrim(number_format((float)$featuredGoal['target_value'], 1), '0'), '.') ?> <?= htmlspecialchars($featuredGoal['unit']) ?></strong>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center small mb-1">
                                <span class="fw-semibold text-dark">Progres Pencapaian</span>
                                <span class="fw-bold text-primary"><?= $featuredGoal['progress_percent'] ?>%</span>
                            </div>
                            <div class="progress" style="height: 8px; background-color: #E2E8F0;">
                                <div class="progress-bar bg-<?= ($featuredGoal['is_achieved'] ? 'success' : 'primary') ?>" style="width: <?= $featuredGoal['progress_percent'] ?>%;"></div>
                            </div>
                        </div>

                        <!-- Next Milestone or Info -->
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mt-auto pt-2 border-top" style="border-color: rgba(229, 232, 232, 0.7) !important;">
                            <div class="small text-muted" style="font-size:0.775rem;">
                                <?php if ($nextMilestone): ?>
                                    <i class="bi bi-arrow-right-circle text-primary me-1"></i>Tahap berikutnya: <strong><?= htmlspecialchars($nextMilestone['title']) ?></strong>
                                <?php else: ?>
                                    <i class="bi bi-calendar3 me-1"></i>Target selesai: <strong><?= date('d M Y', strtotime($featuredGoal['target_date'])) ?></strong>
                                <?php endif; ?>
                            </div>
                            <a href="goal_detail.php?id=<?= $featuredGoal['id_goal'] ?>" class="btn btn-primary-health btn-sm px-3">
                                Rincian & Milestone &rarr;
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card p-4 p-md-5 h-100 text-center rounded-3 bg-light text-muted d-flex flex-column justify-content-center align-items-center" style="border: 1px dashed var(--border);">
                        <i class="bi bi-flag fs-1 d-block mb-2 text-muted"></i>
                        <h6 class="fw-bold text-dark mb-1">Belum Ada Target Kesehatan yang Ditetapkan</h6>
                        <p class="small text-muted mb-3" style="max-width: 440px;">
                            Bantu diri Anda membangun pola hidup sehat yang terukur dengan menetapkan target berat badan, langkah harian, atau tidur.
                        </p>
                        <a href="goals.php" class="btn btn-primary-health btn-sm px-4">
                            <i class="bi bi-plus-circle me-1"></i> Buat Target Kesehatan Pertama
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right: Goals Overview & Activity Stream -->
            <div class="col-lg-4">
                <div class="card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small fw-bold text-dark">Ringkasan Status Target</span>
                        <a href="goals.php" class="small text-primary text-decoration-none">Kelola</a>
                    </div>

                    <div class="row g-2 text-center mb-3">
                        <div class="col-4">
                            <div class="p-2 rounded-2 border bg-light">
                                <span class="small text-muted d-block" style="font-size:0.65rem;">Aktif</span>
                                <strong class="fs-5 text-primary"><?= $goalStats['active'] ?></strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded-2 border bg-light">
                                <span class="small text-muted d-block" style="font-size:0.65rem;">≥75%</span>
                                <strong class="fs-5 text-dark"><?= $goalStats['nearly_done'] ?></strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded-2 border bg-light">
                                <span class="small text-muted d-block" style="font-size:0.65rem;">Selesai</span>
                                <strong class="fs-5 text-success"><?= $goalStats['completed'] ?></strong>
                            </div>
                        </div>
                    </div>

                    <span class="small fw-bold text-dark mb-2 d-block">Aktivitas Target Terkini</span>
                    <div class="d-flex flex-column gap-2 mb-3 overflow-auto" style="max-height: 190px;">
                        <?php if (!empty($recentGoalActivities)): ?>
                            <?php foreach ($recentGoalActivities as $act): ?>
                                <div class="p-2 rounded-2 border bg-white small" style="font-size:0.75rem; border-color: rgba(229, 232, 232, 0.7) !important;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong class="text-primary text-truncate" style="max-width: 140px;"><?= htmlspecialchars($act['goal_title']) ?></strong>
                                        <span class="text-muted" style="font-size:0.65rem;"><?= date('d/m H:i', strtotime($act['created_at'])) ?></span>
                                    </div>
                                    <span class="text-dark d-block"><?= htmlspecialchars($act['description']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-3 text-muted small" style="font-size:0.75rem;">
                                Belum ada aktivitas target tercatat.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-auto pt-2 border-top">
                        <a href="goals.php" class="btn btn-outline-secondary btn-sm w-100">
                            Buka Health Goals Hub &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 1: Individual Health Indicators (Separated by Medical Category, No Arbitrary Composite Score) -->
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0" style="color: var(--primary);">Ringkasan Indikator Biometrik Terkini</h5>
            <span class="text-muted small">Berdasarkan pemeriksaan mandiri terbaru Anda</span>
        </div>

        <div class="row g-3">
            <?php foreach ($indicators as $toolKey => $ind): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card p-3 h-100" style="background-color: var(--surface); border: 1px solid var(--border) !important;">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="tool-category mb-0" style="color: var(--<?= $ind['color'] ?>); font-size: 0.72rem;">
                                <?= htmlspecialchars($ind['category_label']) ?>
                            </span>
                            <?php if ($ind['has_data']): ?>
                                <span class="badge bg-<?= $ind['status_color'] ?>-subtle">
                                    <?= htmlspecialchars($ind['status_label']) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border">Belum Ada Data</span>
                            <?php endif; ?>
                        </div>

                        <h6 class="fw-bold text-dark mb-1" style="font-size: 0.95rem;"><?= htmlspecialchars($ind['title']) ?></h6>

                        <?php if ($ind['has_data']): ?>
                            <div class="fs-4 fw-bold my-1 text-primary">
                                <?= $ind['primary_metric'] ?>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top" style="border-color: rgba(229, 232, 232, 0.7) !important;">
                                <small class="text-muted" style="font-size: 0.75rem;">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    <?= date('d M Y', strtotime($ind['created_at'])) ?>
                                    <?php if (!empty($ind['trend_text'])): ?>
                                        <span class="ms-1 fw-semibold text-<?= $ind['trend_direction'] === 'up' ? 'primary' : ($ind['trend_direction'] === 'down' ? 'secondary' : 'muted') ?>">
                                            (<?= $ind['trend_text'] ?>)
                                        </span>
                                    <?php endif; ?>
                                </small>
                                <a href="history_detail.php?type=<?= $toolKey ?>&id=<?= $ind['record_id'] ?>" class="text-decoration-none fw-semibold" style="font-size: 0.775rem; color: var(--blue);">
                                    Detail <i class="bi bi-chevron-right"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <p class="small text-muted my-2 flex-grow-1" style="font-size: 0.8rem; line-height: 1.5;">
                                Anda belum mencatat data untuk alat ini. Lakukan evaluasi untuk memantau status Anda.
                            </p>
                            <div class="mt-auto pt-2 border-top" style="border-color: rgba(229, 232, 232, 0.7) !important;">
                                <a href="<?= $ind['form_url'] ?>" class="btn btn-outline-secondary btn-sm w-100">
                                    Mulai Pemeriksaan
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Section 2: Health Trends & Actual Data Charts -->
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0" style="color: var(--primary);">Grafik Tren Perkembangan Kesehatan</h5>
            <span class="text-muted small">Hanya menampilkan data riil dari riwayat Anda</span>
        </div>

        <div class="row g-4">
            <!-- Trend 1: BMI & Berat Badan -->
            <div class="col-lg-6">
                <div class="card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-graph-up text-blue me-2"></i>Tren Indeks Massa Tubuh (BMI)</h6>
                        <span class="badge border bg-light text-muted">Satuan: kg/m²</span>
                    </div>
                    <?php if (count($trends['bmi']) >= 2): ?>
                        <div style="height: 220px; position: relative;">
                            <canvas id="chartBmi"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="p-4 text-center rounded-3 bg-light text-muted my-auto" style="border: 1px dashed var(--border);">
                            <i class="bi bi-bar-chart fs-3 d-block mb-1 text-muted"></i>
                            <span class="small">Belum cukup data untuk menampilkan grafik tren. Lakukan minimal 2 kali pemeriksaan BMI untuk memantau perubahan.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Trend 2: Tekanan Darah -->
            <div class="col-lg-6">
                <div class="card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-heart-pulse-fill text-coral me-2"></i>Tren Tekanan Darah (Sistolik & Diastolik)</h6>
                        <span class="badge border bg-light text-muted">Satuan: mmHg</span>
                    </div>
                    <?php if (count($trends['blood_pressure']) >= 2): ?>
                        <div style="height: 220px; position: relative;">
                            <canvas id="chartBp"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="p-4 text-center rounded-3 bg-light text-muted my-auto" style="border: 1px dashed var(--border);">
                            <i class="bi bi-heart-pulse fs-3 d-block mb-1 text-muted"></i>
                            <span class="small">Belum cukup data untuk menampilkan grafik tensi darah. Lakukan minimal 2 kali pencatatan tekanan darah untuk melihat fluktuasi.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 3: Recent Activity Log (Latest 5 records across all categories) -->
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0" style="color: var(--primary);">Aktivitas Pemeriksaan Terbaru</h5>
            <a href="history.php" class="editorial-link">Lihat Semua Riwayat &rarr;</a>
        </div>

        <div class="card overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Alat Pemeriksaan</th>
                            <th>Kategori</th>
                            <th>Hasil Utama</th>
                            <th>Status / Kesimpulan</th>
                            <th>Tanggal Waktu</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recentHistory['records'])): ?>
                            <?php foreach ($recentHistory['records'] as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="tool-icon m-0" style="width: 32px; height: 32px; font-size: 0.95rem; background-color: var(--<?= $item['color'] ?>-light, var(--primary-light)); color: var(--<?= $item['color'] ?>);">
                                                <i class="bi <?= $item['icon'] ?>"></i>
                                            </div>
                                            <span class="fw-semibold text-dark"><?= htmlspecialchars($item['tool_title']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="small text-muted"><?= htmlspecialchars($item['category_label']) ?></span>
                                    </td>
                                    <td>
                                        <strong class="text-primary"><?= htmlspecialchars($item['primary_metric']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $item['status_color'] ?>-subtle">
                                            <?= htmlspecialchars($item['status_label']) ?>
                                        </span>
                                    </td>
                                    <td class="text-muted small">
                                        <?= $item['date_formatted'] ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= $item['detail_url'] ?>" class="btn btn-outline-secondary btn-sm">
                                            Lihat Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                                    Anda belum memiliki riwayat pemeriksaan. Silakan gunakan salah satu kalkulator kesehatan untuk memulai pencatatan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js for data-driven trends -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if (count($trends['bmi']) >= 2): ?>
const bmiCtx = document.getElementById('chartBmi').getContext('2d');
new Chart(bmiCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_map(fn($r) => date('d/m', strtotime($r['created_at'])), $trends['bmi'])) ?>,
        datasets: [{
            label: 'BMI (kg/m²)',
            data: <?= json_encode(array_map(fn($r) => (float)$r['bmi'], $trends['bmi'])) ?>,
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
            y: { grid: { color: '#E2E8F0' } },
            x: { grid: { display: false } }
        }
    }
});
<?php endif; ?>

<?php if (count($trends['blood_pressure']) >= 2): ?>
const bpCtx = document.getElementById('chartBp').getContext('2d');
new Chart(bpCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_map(fn($r) => date('d/m', strtotime($r['created_at'])), $trends['blood_pressure'])) ?>,
        datasets: [
            {
                label: 'Sistolik (mmHg)',
                data: <?= json_encode(array_map(fn($r) => (int)$r['sistolik'], $trends['blood_pressure'])) ?>,
                borderColor: '#D75F50',
                backgroundColor: 'transparent',
                tension: 0.3,
                pointRadius: 4
            },
            {
                label: 'Diastolik (mmHg)',
                data: <?= json_encode(array_map(fn($r) => (int)$r['diastolik'], $trends['blood_pressure'])) ?>,
                borderColor: '#19365B',
                backgroundColor: 'transparent',
                tension: 0.3,
                pointRadius: 4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
        },
        scales: {
            y: { grid: { color: '#E2E8F0' } },
            x: { grid: { display: false } }
        }
    }
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
