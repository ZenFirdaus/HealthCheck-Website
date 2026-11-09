<?php
$pageTitle = 'Health Goals & Actionable Milestones — HealthCheck';
$activePage = 'goals';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/login_cek.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/goal_helper.php';

$userId = current_user_id();
if (!$userId) {
    header("Location: ../auth/login.php?pesan=belum_login");
    exit;
}

$statusFilter   = trim($_GET['status'] ?? 'active');
$categoryFilter = trim($_GET['category'] ?? 'all');
$searchQuery    = trim($_GET['q'] ?? '');

$pesan = '';
$error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $action = trim($_POST['action'] ?? '');

    if ($action === 'create_goal') {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category    = trim($_POST['category'] ?? 'body_nutrition');
        $goalType    = trim($_POST['goal_type'] ?? 'custom');
        $initialVal  = (float)($_POST['initial_value'] ?? 0);
        $targetVal   = (float)($_POST['target_value'] ?? 0);
        $unit        = trim($_POST['unit'] ?? '');
        $direction   = trim($_POST['direction'] ?? 'increase');
        $startDate   = trim($_POST['start_date'] ?? date('Y-m-d'));
        $targetDate  = trim($_POST['target_date'] ?? date('Y-m-d', strtotime('+30 days')));
        $isFeatured  = isset($_POST['is_featured']) ? 1 : 0;
        $addTemplate = isset($_POST['add_template']) ? 1 : 0;

        // Validation
        if (empty($title) || empty($unit)) {
            $error = 'Judul target dan satuan pengukuran wajib diisi.';
        } elseif ($targetDate < $startDate) {
            $error = 'Tanggal target selesai tidak boleh lebih awal dari tanggal mulai.';
        } elseif ($initialVal <= 0 && $targetVal <= 0) {
            $error = 'Nilai awal atau nilai target harus berupa angka yang valid.';
        } else {
            $goalId = create_health_goal($pdo, $userId, [
                'title'         => $title,
                'description'   => $description,
                'category'      => $category,
                'goal_type'     => $goalType,
                'initial_value' => $initialVal,
                'target_value'  => $targetVal,
                'current_value' => $initialVal,
                'unit'          => $unit,
                'direction'     => $direction,
                'start_date'    => $startDate,
                'target_date'   => $targetDate,
                'is_featured'   => $isFeatured
            ]);

            // Add recommended milestone templates if requested
            if ($goalId > 0 && $addTemplate) {
                $suggestions = get_milestone_suggestions($category, $goalType, $initialVal, $targetVal, $unit);
                foreach ($suggestions as $s) {
                    create_goal_milestone($pdo, $userId, $goalId, [
                        'title'          => $s['title'],
                        'description'    => $s['desc'],
                        'target_value'   => $s['target_val'],
                        'unit'           => $unit,
                        'sequence_order' => $s['order']
                    ]);
                }
            }

            header("Location: goals.php?pesan=goal_created");
            exit;
        }
    } elseif ($action === 'set_status') {
        $goalId = (int)($_POST['goal_id'] ?? 0);
        $status = trim($_POST['status'] ?? 'active');
        $ok = set_goal_status($pdo, $userId, $goalId, $status);
        if ($ok) {
            header("Location: goals.php?status=" . urlencode($statusFilter) . "&pesan=status_updated");
            exit;
        } else {
            $error = 'Gagal memperbarui status target.';
        }
    } elseif ($action === 'set_featured') {
        $goalId = (int)($_POST['goal_id'] ?? 0);
        $ok = set_featured_goal($pdo, $userId, $goalId);
        if ($ok) {
            header("Location: goals.php?status=" . urlencode($statusFilter) . "&pesan=featured_updated");
            exit;
        } else {
            $error = 'Gagal menyematkan target utama.';
        }
    } elseif ($action === 'delete_goal') {
        $goalId = (int)($_POST['goal_id'] ?? 0);
        $ok = delete_health_goal($pdo, $userId, $goalId);
        if ($ok) {
            header("Location: goals.php?status=" . urlencode($statusFilter) . "&pesan=goal_deleted");
            exit;
        } else {
            $error = 'Gagal menghapus target kesehatan.';
        }
    } elseif ($action === 'sync_data') {
        $synced = sync_external_data_to_goals($pdo, $userId);
        header("Location: goals.php?status=" . urlencode($statusFilter) . "&pesan=synced&count=" . $synced);
        exit;
    }
}

// Fetch stats summary
$stats = get_goal_stats_summary($pdo, $userId);

// Fetch goals based on filters
$goals = get_user_goals($pdo, $userId, [
    'status'   => $statusFilter,
    'category' => $categoryFilter,
    'search'   => $searchQuery
]);

$categoriesMeta = get_goal_categories_meta();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Health Goals & Milestones</li>
        </ol>
    </nav>

    <?php if (isset($_GET['pesan'])): ?>
        <?php if ($_GET['pesan'] === 'goal_created'): ?>
            <div class="alert alert-success alert-dismissible fade show small mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>Target kesehatan dan tahapan milestone berhasil ditetapkan!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['pesan'] === 'status_updated'): ?>
            <div class="alert alert-success alert-dismissible fade show small mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>Status target kesehatan berhasil diperbarui!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['pesan'] === 'featured_updated'): ?>
            <div class="alert alert-success alert-dismissible fade show small mb-4" role="alert">
                <i class="bi bi-star-fill text-warning me-2"></i>Target kesehatan berhasil disematkan sebagai Featured Goal di Dashboard!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['pesan'] === 'goal_deleted'): ?>
            <div class="alert alert-success alert-dismissible fade show small mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>Target kesehatan berhasil dihapus.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['pesan'] === 'synced'): ?>
            <div class="alert alert-info alert-dismissible fade show small mb-4" role="alert">
                <i class="bi bi-arrow-repeat me-2"></i>Sinkronisasi otomatis selesai. <?= (int)($_GET['count'] ?? 0) ?> data progres baru dicatatkan dari hasil tes dan check-in harian.
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

    <!-- Header & Quick Actions -->
    <div class="p-4 mb-4 rounded-3 border" style="background-color: var(--surface); border-color: var(--border) !important; box-shadow: var(--shadow-xs);">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <span class="eyebrow-text">Manajemen Target Kesehatan</span>
                <h3 class="fw-bold mb-1" style="color: var(--primary);">Health Goals & Milestones</h3>
                <p class="small text-muted mb-0">
                    Wujudkan transformasi kesehatan nyata dengan menetapkan sasaran realistis dan memecahnya ke dalam tahapan (*actionable milestones*).
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-primary-health btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddGoal">
                    <i class="bi bi-plus-circle me-1"></i> Buat Target Baru
                </button>
                <form action="goals.php" method="POST" class="d-inline">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="sync_data">
                    <button type="submit" class="btn btn-secondary-soft btn-sm" title="Sinkronkan data dari tes kalkulator dan check-in">
                        <i class="bi bi-arrow-repeat me-1"></i> Sinkronkan Progres
                    </button>
                </form>
                <a href="habits.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-check2-circle me-1"></i> Habits Tracker
                </a>
            </div>
        </div>

        <hr style="border-color: rgba(229, 232, 232, 0.7); margin: 1.25rem 0 1rem;">

        <!-- 4 Stats Summary Cards -->
        <div class="row g-2 text-center">
            <div class="col-6 col-md-3">
                <a href="goals.php?status=active" class="text-decoration-none d-block p-2 rounded-2 border <?= ($statusFilter === 'active') ? 'border-primary bg-white shadow-xs' : 'bg-light' ?>">
                    <span class="small text-muted d-block" style="font-size:0.75rem;">Target Berjalan</span>
                    <strong class="fs-5 text-primary"><?= $stats['active'] ?></strong>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-2 rounded-2 border bg-light">
                    <span class="small text-muted d-block" style="font-size:0.75rem;">Hampir Selesai (≥75%)</span>
                    <strong class="fs-5 text-dark"><?= $stats['nearly_done'] ?></strong>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <a href="goals.php?status=completed" class="text-decoration-none d-block p-2 rounded-2 border <?= ($statusFilter === 'completed') ? 'border-primary bg-white shadow-xs' : 'bg-light' ?>">
                    <span class="small text-muted d-block" style="font-size:0.75rem;">Target Selesai</span>
                    <strong class="fs-5 text-success"><?= $stats['completed'] ?></strong>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="goals.php?status=all" class="text-decoration-none d-block p-2 rounded-2 border <?= ($statusFilter === 'all') ? 'border-primary bg-white shadow-xs' : 'bg-light' ?>">
                    <span class="small text-muted d-block" style="font-size:0.75rem;">Total Keseluruhan</span>
                    <strong class="fs-5 text-dark"><?= $stats['total'] ?></strong>
                </a>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card p-3 mb-4" style="background-color: var(--surface); border: 1px solid var(--border) !important;">
        <form action="goals.php" method="GET" class="row g-2 align-items-end">
            <!-- Filter Status -->
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1" for="status">Status Target</label>
                <select name="status" id="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Semua Status</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Sedang Berjalan (Active)</option>
                    <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Selesai (Completed)</option>
                    <option value="paused" <?= $statusFilter === 'paused' ? 'selected' : '' ?>>Dijeda (Paused)</option>
                    <option value="archived" <?= $statusFilter === 'archived' ? 'selected' : '' ?>>Diarsipkan (Archived)</option>
                </select>
            </div>

            <!-- Filter Kategori -->
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1" for="category">Kategori</label>
                <select name="category" id="category" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" <?= $categoryFilter === 'all' ? 'selected' : '' ?>>Semua Kategori</option>
                    <option value="body_nutrition" <?= $categoryFilter === 'body_nutrition' ? 'selected' : '' ?>>Gizi & Tubuh</option>
                    <option value="physical_activity" <?= $categoryFilter === 'physical_activity' ? 'selected' : '' ?>>Aktivitas Fisik</option>
                    <option value="sleep_lifestyle" <?= $categoryFilter === 'sleep_lifestyle' ? 'selected' : '' ?>>Pola Tidur</option>
                    <option value="hydration" <?= $categoryFilter === 'hydration' ? 'selected' : '' ?>>Hidrasi Cairan</option>
                    <option value="personal" <?= $categoryFilter === 'personal' ? 'selected' : '' ?>>Target Personal</option>
                </select>
            </div>

            <!-- Search Field -->
            <div class="col-md-6 d-flex gap-2">
                <div class="flex-grow-1">
                    <label class="form-label small text-muted mb-1" for="q">Cari Target</label>
                    <input type="text" name="q" id="q" class="form-control form-control-sm" placeholder="Nama target atau deskripsi..." value="<?= htmlspecialchars($searchQuery) ?>">
                </div>
                <button type="submit" class="btn btn-primary-health btn-sm align-self-end px-3" title="Terapkan Filter">
                    <i class="bi bi-search"></i>
                </button>
                <?php if ($statusFilter !== 'active' || $categoryFilter !== 'all' || !empty($searchQuery)): ?>
                    <a href="goals.php" class="btn btn-outline-secondary btn-sm align-self-end" title="Reset Filter">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Goals Grid -->
    <div class="row g-4 mb-5">
        <?php if (!empty($goals)): ?>
            <?php foreach ($goals as $goal): ?>
                <?php
                $cat = $categoriesMeta[$goal['category']] ?? [
                    'name' => 'Kategori Medis', 'color' => 'blue', 'icon' => 'bi-flag'
                ];
                $milestones = get_goal_milestones($pdo, $userId, (int)$goal['id_goal']);
                $completedMs = count(array_filter($milestones, fn($m) => $m['status'] === 'completed'));
                $totalMs = count($milestones);
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card p-4 h-100 d-flex flex-column" style="background-color: var(--surface); border: 1px solid var(--border) !important;">
                        <!-- Card Top Bar -->
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="tool-category mb-0" style="color: var(--<?= $cat['color'] ?>); font-size: 0.725rem;">
                                <i class="bi <?= $cat['icon'] ?> me-1"></i><?= htmlspecialchars($cat['name']) ?>
                            </span>
                            <div class="d-flex align-items-center gap-1">
                                <?php if ($goal['is_featured'] == 1): ?>
                                    <span class="badge bg-warning-subtle text-warning border px-2" title="Featured Goal di Dashboard">
                                        <i class="bi bi-star-fill"></i>
                                    </span>
                                <?php endif; ?>
                                <span class="badge <?= ($goal['status'] === 'active') ? 'bg-primary-subtle text-primary' : (($goal['status'] === 'completed') ? 'bg-success-subtle text-success' : 'bg-light text-muted border') ?>" style="font-size:0.7rem;">
                                    <?= ucfirst($goal['status']) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Goal Title & Description -->
                        <h5 class="fw-bold text-dark mb-1" style="font-size: 1.05rem; line-height: 1.35;">
                            <a href="goal_detail.php?id=<?= $goal['id_goal'] ?>" class="text-decoration-none text-dark">
                                <?= htmlspecialchars($goal['title']) ?>
                            </a>
                        </h5>
                        <p class="small text-muted mb-3 flex-grow-1" style="font-size: 0.8rem; line-height: 1.45;">
                            <?= htmlspecialchars($goal['description'] ?: 'Tidak ada deskripsi tambahan.') ?>
                        </p>

                        <!-- Progress Bar & Metrics -->
                        <div class="mt-auto pt-2 border-top" style="border-color: rgba(229, 232, 232, 0.7) !important;">
                            <div class="d-flex justify-content-between align-items-baseline mb-1">
                                <span class="fs-4 fw-bold text-primary">
                                    <?= rtrim(rtrim(number_format((float)$goal['current_value'], 1), '0'), '.') ?>
                                    <small class="text-muted fw-normal" style="font-size: 0.75rem;"><?= htmlspecialchars($goal['unit']) ?></small>
                                </span>
                                <span class="small text-muted" style="font-size: 0.75rem;">
                                    Target: <strong><?= rtrim(rtrim(number_format((float)$goal['target_value'], 1), '0'), '.') ?> <?= htmlspecialchars($goal['unit']) ?></strong>
                                </span>
                            </div>

                            <div class="progress mb-2" style="height: 6px; background-color: #E2E8F0;">
                                <div class="progress-bar bg-<?= ($goal['is_achieved'] ? 'success' : 'primary') ?>" style="width: <?= $goal['progress_percent'] ?>%;"></div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center small text-muted mb-3" style="font-size: 0.725rem;">
                                <span>Awal: <?= rtrim(rtrim(number_format((float)$goal['initial_value'], 1), '0'), '.') ?> <?= htmlspecialchars($goal['unit']) ?></span>
                                <span class="fw-semibold text-<?= $goal['is_achieved'] ? 'success' : ($goal['is_opposite'] ? 'danger' : 'primary') ?>">
                                    <?= $goal['progress_status'] ?> (<?= $goal['progress_percent'] ?>%)
                                </span>
                            </div>

                            <!-- Milestone & Deadline Info -->
                            <div class="p-2 mb-3 rounded-2 bg-light d-flex justify-content-between align-items-center" style="font-size: 0.725rem;">
                                <span><i class="bi bi-list-check text-primary me-1"></i>Milestone: <strong><?= $completedMs ?>/<?= $totalMs ?> selesai</strong></span>
                                <span><i class="bi bi-calendar3 me-1"></i>s/d <?= date('d M Y', strtotime($goal['target_date'])) ?></span>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="goal_detail.php?id=<?= $goal['id_goal'] ?>" class="btn btn-secondary-soft btn-sm py-1 px-3">
                                    Detail & Progres &rarr;
                                </a>

                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm py-1 px-2" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end small">
                                        <?php if ($goal['is_featured'] == 0 && $goal['status'] === 'active'): ?>
                                            <li>
                                                <form action="goals.php" method="POST">
                                                    <?= csrf_input() ?>
                                                    <input type="hidden" name="action" value="set_featured">
                                                    <input type="hidden" name="goal_id" value="<?= $goal['id_goal'] ?>">
                                                    <button type="submit" class="dropdown-item"><i class="bi bi-star text-warning me-2"></i>Jadikan Featured Goal</button>
                                                </form>
                                            </li>
                                        <?php endif; ?>

                                        <?php if ($goal['status'] === 'active'): ?>
                                            <li>
                                                <form action="goals.php" method="POST">
                                                    <?= csrf_input() ?>
                                                    <input type="hidden" name="action" value="set_status">
                                                    <input type="hidden" name="goal_id" value="<?= $goal['id_goal'] ?>">
                                                    <input type="hidden" name="status" value="paused">
                                                    <button type="submit" class="dropdown-item"><i class="bi bi-pause-circle text-muted me-2"></i>Jeda Target</button>
                                                </form>
                                            </li>
                                            <li>
                                                <form action="goals.php" method="POST">
                                                    <?= csrf_input() ?>
                                                    <input type="hidden" name="action" value="set_status">
                                                    <input type="hidden" name="goal_id" value="<?= $goal['id_goal'] ?>">
                                                    <input type="hidden" name="status" value="completed">
                                                    <button type="submit" class="dropdown-item text-success"><i class="bi bi-check2-circle me-2"></i>Tandai Selesai</button>
                                                </form>
                                            </li>
                                        <?php elseif ($goal['status'] === 'paused'): ?>
                                            <li>
                                                <form action="goals.php" method="POST">
                                                    <?= csrf_input() ?>
                                                    <input type="hidden" name="action" value="set_status">
                                                    <input type="hidden" name="goal_id" value="<?= $goal['id_goal'] ?>">
                                                    <input type="hidden" name="status" value="active">
                                                    <button type="submit" class="dropdown-item text-primary"><i class="bi bi-play-circle me-2"></i>Lanjutkan Target</button>
                                                </form>
                                            </li>
                                        <?php endif; ?>

                                        <?php if ($goal['status'] !== 'archived'): ?>
                                            <li>
                                                <form action="goals.php" method="POST">
                                                    <?= csrf_input() ?>
                                                    <input type="hidden" name="action" value="set_status">
                                                    <input type="hidden" name="goal_id" value="<?= $goal['id_goal'] ?>">
                                                    <input type="hidden" name="status" value="archived">
                                                    <button type="submit" class="dropdown-item text-secondary"><i class="bi bi-archive me-2"></i>Arsipkan</button>
                                                </form>
                                            </li>
                                        <?php endif; ?>

                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="goals.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus target ini beserta riwayat progresnya?');">
                                                <?= csrf_input() ?>
                                                <input type="hidden" name="action" value="delete_goal">
                                                <input type="hidden" name="goal_id" value="<?= $goal['id_goal'] ?>">
                                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Hapus Target</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-flag fs-2 d-block mb-2 text-muted"></i>
                <h6 class="fw-bold text-dark mb-1">Belum Ada Target Kesehatan</h6>
                <p class="small text-muted mb-3" style="max-width: 480px; margin: 0 auto;">
                    Mulailah perjalanan hidup sehat Anda dengan menetapkan target berat badan, langkah harian, hidrasi, atau durasi tidur sekarang.
                </p>
                <button type="button" class="btn btn-primary-health btn-sm px-4" data-bs-toggle="modal" data-bs-target="#modalAddGoal">
                    <i class="bi bi-plus-circle me-1"></i> Buat Target Pertama Anda
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Create Health Goal -->
<div class="modal fade" id="modalAddGoal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border">
            <form action="goals.php" method="POST">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="create_goal">
                <div class="modal-header border-bottom">
                    <h6 class="modal-title fw-bold" style="color: var(--primary);">Buat Target Kesehatan Baru</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small" for="category_select">Kategori Target</label>
                            <select name="category" id="category_select" class="form-select form-select-sm" onchange="updateGoalTypeOptions(this.value)" required>
                                <option value="body_nutrition">Gizi & Komposisi Tubuh</option>
                                <option value="physical_activity">Aktivitas Fisik & Kebugaran</option>
                                <option value="sleep_lifestyle">Pola Tidur & Pemulihan</option>
                                <option value="hydration">Hidrasi & Asupan Cairan</option>
                                <option value="personal">Target Personal Mandiri</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small" for="goal_type_select">Jenis Sasaran</label>
                            <select name="goal_type" id="goal_type_select" class="form-select form-select-sm" onchange="autoFillGoalDefaults(this.value)" required>
                                <option value="weight_loss" data-unit="kg" data-dir="decrease">Menurunkan Berat Badan (kg)</option>
                                <option value="weight_gain" data-unit="kg" data-dir="increase">Menaikkan Berat Badan (kg)</option>
                                <option value="waist" data-unit="cm" data-dir="decrease">Mengecilkan Lingkar Pinggang (cm)</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small" for="goal_title">Nama / Judul Target</label>
                            <input type="text" name="title" id="goal_title" class="form-control form-control-sm" placeholder="Contoh: Capai berat badan ideal atau target langkah harian" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label small" for="goal_desc">Deskripsi & Tujuan (Opsional)</label>
                            <textarea name="description" id="goal_desc" rows="2" class="form-control form-control-sm" placeholder="Jelaskan alasan atau motivasi Anda menetapkan target ini..."></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small" for="initial_value">Nilai Awal</label>
                            <input type="number" step="any" name="initial_value" id="initial_value" class="form-control form-control-sm" placeholder="75" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small" for="target_value">Nilai Sasaran (Target)</label>
                            <input type="number" step="any" name="target_value" id="target_value" class="form-control form-control-sm" placeholder="70" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small" for="goal_unit">Satuan</label>
                            <input type="text" name="unit" id="goal_unit" class="form-control form-control-sm" value="kg" required>
                        </div>

                        <input type="hidden" name="direction" id="goal_direction" value="decrease">

                        <div class="col-md-6">
                            <label class="form-label small" for="start_date">Tanggal Mulai</label>
                            <input type="date" name="start_date" id="start_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small" for="target_date">Target Tanggal Selesai</label>
                            <input type="date" name="target_date" id="target_date" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('+60 days')) ?>" required>
                        </div>

                        <div class="col-12 pt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="add_template" id="add_template" value="1" checked>
                                <label class="form-check-label small" for="add_template">
                                    <strong>Otomatis sertakan rekomendasi tahapan milestone</strong> (Dapat disesuaikan kembali nanti)
                                </label>
                            </div>
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" value="1" checked>
                                <label class="form-check-label small" for="is_featured">
                                    Sematkan target ini sebagai <strong>Featured Goal</strong> di Personal Dashboard
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-health btn-sm px-4">Simpan Target</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const typeMapping = {
    body_nutrition: [
        { val: 'weight_loss', label: 'Menurunkan Berat Badan (kg)', unit: 'kg', dir: 'decrease' },
        { val: 'weight_gain', label: 'Menaikkan Berat Badan (kg)', unit: 'kg', dir: 'increase' },
        { val: 'waist', label: 'Mengecilkan Lingkar Pinggang (cm)', unit: 'cm', dir: 'decrease' }
    ],
    physical_activity: [
        { val: 'steps', label: 'Target Langkah Harian (langkah)', unit: 'langkah', dir: 'increase' },
        { val: 'activity_minutes', label: 'Aktivitas Olahraga Mingguan (menit)', unit: 'menit', dir: 'increase' }
    ],
    sleep_lifestyle: [
        { val: 'sleep_duration', label: 'Target Durasi Tidur (jam)', unit: 'jam', dir: 'increase' }
    ],
    hydration: [
        { val: 'water_intake', label: 'Target Asupan Air Harian (ml)', unit: 'ml', dir: 'increase' }
    ],
    personal: [
        { val: 'custom_inc', label: 'Peningkatan Rutinitas (Makin Tinggi)', unit: 'kali', dir: 'increase' },
        { val: 'custom_dec', label: 'Pengurangan Kebiasaan (Makin Rendah)', unit: 'kali', dir: 'decrease' }
    ]
};

function updateGoalTypeOptions(category) {
    const typeSelect = document.getElementById('goal_type_select');
    typeSelect.innerHTML = '';
    const options = typeMapping[category] || typeMapping.personal;

    options.forEach(opt => {
        const el = document.createElement('option');
        el.value = opt.val;
        el.textContent = opt.label;
        el.dataset.unit = opt.unit;
        el.dataset.dir = opt.dir;
        typeSelect.appendChild(el);
    });

    autoFillGoalDefaults(typeSelect.value);
}

function autoFillGoalDefaults() {
    const typeSelect = document.getElementById('goal_type_select');
    const selected = typeSelect.options[typeSelect.selectedIndex];
    if (selected) {
        document.getElementById('goal_unit').value = selected.dataset.unit || 'kali';
        document.getElementById('goal_direction').value = selected.dataset.dir || 'increase';
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
