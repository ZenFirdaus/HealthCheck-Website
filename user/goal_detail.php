<?php
$pageTitle = 'Rincian Target & Milestone — HealthCheck';
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

$goalId = (int)($_GET['id'] ?? 0);
if ($goalId <= 0) {
    header("Location: goals.php");
    exit;
}

$goal = get_goal_by_id($pdo, $userId, $goalId);
if (!$goal) {
    http_response_code(404);
    die("404 Not Found: Target kesehatan tidak ditemukan atau tidak memiliki hak akses.");
}

$pesan = '';
$error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $action = trim($_POST['action'] ?? '');

    if ($action === 'add_progress') {
        $val = (float)($_POST['current_value'] ?? 0);
        $notes = !empty($_POST['notes']) ? trim($_POST['notes']) : null;
        $recDate = !empty($_POST['recorded_at']) ? trim($_POST['recorded_at']) : date('Y-m-d');

        $ok = record_goal_progress($pdo, $userId, $goalId, $val, $notes, 'manual', null, $recDate);
        if ($ok) {
            header("Location: goal_detail.php?id={$goalId}&pesan=progress_added");
            exit;
        } else {
            $error = 'Gagal mencatat progres target.';
        }
    } elseif ($action === 'add_milestone') {
        $title    = trim($_POST['title'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $targetVal= (isset($_POST['target_value']) && $_POST['target_value'] !== '') ? (float)$_POST['target_value'] : null;
        $order    = max(1, (int)($_POST['sequence_order'] ?? 1));
        $date     = !empty($_POST['target_date']) ? trim($_POST['target_date']) : null;

        if (!empty($title)) {
            create_goal_milestone($pdo, $userId, $goalId, [
                'title'          => $title,
                'description'    => $desc,
                'target_value'   => $targetVal,
                'sequence_order' => $order,
                'target_date'    => $date
            ]);
            header("Location: goal_detail.php?id={$goalId}&pesan=milestone_added");
            exit;
        } else {
            $error = 'Nama tahapan milestone wajib diisi.';
        }
    } elseif ($action === 'toggle_milestone') {
        $msId = (int)($_POST['milestone_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? 'completed');
        $ok = set_milestone_status($pdo, $userId, $msId, $newStatus);
        if ($ok) {
            header("Location: goal_detail.php?id={$goalId}&pesan=milestone_updated");
            exit;
        } else {
            $error = 'Gagal memperbarui status milestone.';
        }
    } elseif ($action === 'delete_milestone') {
        $msId = (int)($_POST['milestone_id'] ?? 0);
        $ok = delete_goal_milestone($pdo, $userId, $msId);
        if ($ok) {
            header("Location: goal_detail.php?id={$goalId}&pesan=milestone_deleted");
            exit;
        } else {
            $error = 'Gagal menghapus milestone.';
        }
    }
}

// Refresh goal data
$goal = get_goal_by_id($pdo, $userId, $goalId);
$milestones = get_goal_milestones($pdo, $userId, $goalId);
$progressLogs = get_goal_progress_logs($pdo, $userId, $goalId);
$categoriesMeta = get_goal_categories_meta();
$cat = $categoriesMeta[$goal['category']] ?? ['name' => 'Kategori Medis', 'color' => 'blue', 'icon' => 'bi-flag'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="goals.php" class="text-decoration-none">Health Goals</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars(mb_strimwidth($goal['title'], 0, 30, '...')) ?></li>
        </ol>
    </nav>

    <?php if (isset($_GET['pesan'])): ?>
        <?php if ($_GET['pesan'] === 'progress_added'): ?>
            <div class="alert alert-success alert-dismissible fade show small mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>Perkembangan progres target berhasil dicatat!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['pesan'] === 'milestone_added'): ?>
            <div class="alert alert-success alert-dismissible fade show small mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>Tahapan milestone baru berhasil ditambahkan!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['pesan'] === 'milestone_updated'): ?>
            <div class="alert alert-success alert-dismissible fade show small mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>Status tahapan milestone berhasil diperbarui!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['pesan'] === 'milestone_deleted'): ?>
            <div class="alert alert-success alert-dismissible fade show small mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>Milestone berhasil dihapus.
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

    <!-- Main Goal Overview Header Card -->
    <div class="card p-4 p-md-5 mb-4 overflow-hidden">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <span class="tool-category mb-1" style="color: var(--<?= $cat['color'] ?>);">
                    <i class="bi <?= $cat['icon'] ?> me-1"></i><?= htmlspecialchars($cat['name']) ?>
                </span>
                <h3 class="card-heading mb-1" style="color: var(--primary);"><?= htmlspecialchars($goal['title']) ?></h3>
                <p class="small text-muted mb-0" style="max-width: 650px;">
                    <?= htmlspecialchars($goal['description'] ?: 'Target pemeliharaan kesehatan mandiri') ?>
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-primary-health btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddProgress">
                    <i class="bi bi-plus-circle me-1"></i> Catat Progres Terbaru
                </button>
                <button type="button" class="btn btn-secondary-soft btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddMilestone">
                    <i class="bi bi-list-plus me-1"></i> Tambah Milestone
                </button>
                <a href="goals.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
                </a>
            </div>
        </div>

        <!-- 3 Stat Blocks (Initial -> Current -> Target) -->
        <div class="row g-3 mb-4">
            <div class="col-sm-4">
                <div class="p-3 rounded-3 border" style="background-color: var(--light-bg); border-color: var(--border) !important;">
                    <span class="small text-muted d-block mb-1">Nilai Awal (Start)</span>
                    <h4 class="fw-bold mb-0 text-dark">
                        <?= rtrim(rtrim(number_format((float)$goal['initial_value'], 1), '0'), '.') ?>
                        <small class="text-muted fs-6 fw-normal"><?= htmlspecialchars($goal['unit']) ?></small>
                    </h4>
                    <span class="small text-muted" style="font-size:0.75rem;">Mulai: <?= date('d M Y', strtotime($goal['start_date'])) ?></span>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="p-3 rounded-3 border bg-white border-primary shadow-xs">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-semibold text-primary">Nilai Terkini</span>
                        <span class="badge <?= ($goal['is_achieved']) ? 'bg-success text-white' : 'bg-primary-subtle text-primary' ?>" style="font-size:0.65rem;">
                            <?= $goal['progress_status'] ?>
                        </span>
                    </div>
                    <h4 class="fw-bold mb-0 text-primary">
                        <?= rtrim(rtrim(number_format((float)$goal['current_value'], 1), '0'), '.') ?>
                        <small class="text-muted fs-6 fw-normal"><?= htmlspecialchars($goal['unit']) ?></small>
                    </h4>
                    <span class="small text-muted" style="font-size:0.75rem;">Pencapaian: <strong><?= $goal['progress_percent'] ?>%</strong></span>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="p-3 rounded-3 border" style="background-color: var(--light-bg); border-color: var(--border) !important;">
                    <span class="small text-muted d-block mb-1">Sasaran Akhir (Target)</span>
                    <h4 class="fw-bold mb-0 text-dark">
                        <?= rtrim(rtrim(number_format((float)$goal['target_value'], 1), '0'), '.') ?>
                        <small class="text-muted fs-6 fw-normal"><?= htmlspecialchars($goal['unit']) ?></small>
                    </h4>
                    <span class="small text-muted" style="font-size:0.75rem;">Target: <?= date('d M Y', strtotime($goal['target_date'])) ?></span>
                </div>
            </div>
        </div>

        <!-- Big Progress Bar -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center small mb-1">
                <span class="fw-semibold text-dark">Progres Menuju Sasaran</span>
                <span class="fw-bold text-primary"><?= $goal['progress_percent'] ?>%</span>
            </div>
            <div class="progress" style="height: 10px; background-color: #E2E8F0;">
                <div class="progress-bar bg-<?= ($goal['is_achieved'] ? 'success' : 'primary') ?>" style="width: <?= $goal['progress_percent'] ?>%;"></div>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2 small text-muted" style="font-size:0.75rem;">
                <span>Sisa jarak capaian: <strong><?= $goal['delta_remaining'] ?> <?= htmlspecialchars($goal['unit']) ?></strong></span>
                <span>Arah: <?= ($goal['direction'] === 'decrease') ? 'Penurunan (Makin Rendah)' : 'Peningkatan (Makin Tinggi)' ?></span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left: Actionable Milestones Checklist -->
        <div class="col-lg-7">
            <div class="card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span class="eyebrow-text">Tahapan Bertahap</span>
                        <h5 class="fw-bold mb-0 text-dark">Actionable Milestones</h5>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddMilestone">
                        <i class="bi bi-plus me-1"></i> Tambah
                    </button>
                </div>

                <div class="d-flex flex-column gap-3 mb-3">
                    <?php if (!empty($milestones)): ?>
                        <?php foreach ($milestones as $ms): ?>
                            <?php $isDone = ($ms['status'] === 'completed'); ?>
                            <div class="p-3 rounded-3 border d-flex justify-content-between align-items-start gap-3 <?= $isDone ? 'bg-light' : 'bg-white shadow-xs' ?>">
                                <div class="d-flex align-items-start gap-3">
                                    <form action="goal_detail.php?id=<?= $goalId ?>" method="POST" class="mt-1">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="action" value="toggle_milestone">
                                        <input type="hidden" name="milestone_id" value="<?= $ms['id_milestone'] ?>">
                                        <input type="hidden" name="status" value="<?= $isDone ? 'pending' : 'completed' ?>">
                                        <button type="submit" class="btn btn-sm p-0 text-<?= $isDone ? 'success' : 'muted' ?>" title="<?= $isDone ? 'Tandai Belum Selesai' : 'Tandai Selesai' ?>">
                                            <i class="bi <?= $isDone ? 'bi-check-circle-fill fs-4' : 'bi-circle fs-4' ?>"></i>
                                        </button>
                                    </form>
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="badge border bg-light text-muted" style="font-size:0.65rem;">Tahap <?= $ms['sequence_order'] ?></span>
                                            <h6 class="fw-bold mb-0 text-dark <?= $isDone ? 'text-decoration-line-through text-muted' : '' ?>" style="font-size:0.95rem;">
                                                <?= htmlspecialchars($ms['title']) ?>
                                            </h6>
                                        </div>
                                        <p class="small text-muted mb-1" style="font-size:0.8rem; line-height:1.4;">
                                            <?= htmlspecialchars($ms['description'] ?: 'Tidak ada instruksi khusus.') ?>
                                        </p>
                                        <div class="d-flex flex-wrap gap-3 small text-muted" style="font-size:0.725rem;">
                                            <?php if ($ms['target_value'] !== null): ?>
                                                <span>Target: <strong><?= $ms['target_value'] ?> <?= htmlspecialchars($ms['unit'] ?: $goal['unit']) ?></strong></span>
                                            <?php endif; ?>
                                            <?php if (!empty($ms['target_date'])): ?>
                                                <span><i class="bi bi-calendar3 me-1"></i>Target: <?= date('d M Y', strtotime($ms['target_date'])) ?></span>
                                            <?php endif; ?>
                                            <?php if ($isDone && !empty($ms['completed_at'])): ?>
                                                <span class="text-success"><i class="bi bi-check2 me-1"></i>Selesai: <?= date('d M Y', strtotime($ms['completed_at'])) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <form action="goal_detail.php?id=<?= $goalId ?>" method="POST" onsubmit="return confirm('Hapus milestone ini?');">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="delete_milestone">
                                    <input type="hidden" name="milestone_id" value="<?= $ms['id_milestone'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2" title="Hapus Milestone">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted bg-light rounded-3 border">
                            <i class="bi bi-list-task fs-3 d-block mb-1"></i>
                            <span class="small">Belum ada tahapan milestone. Tambahkan tahapan kecil untuk memudahkan pencapaian target.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: Progress History & Chart -->
        <div class="col-lg-5">
            <div class="card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span class="eyebrow-text">Grafik Aktual</span>
                        <h5 class="fw-bold mb-0 text-dark">Kurva Perkembangan</h5>
                    </div>
                    <span class="badge border bg-light text-muted">Satuan: <?= htmlspecialchars($goal['unit']) ?></span>
                </div>

                <!-- Progress Chart -->
                <?php if (count($progressLogs) >= 2): ?>
                    <div style="height: 190px; position: relative;" class="mb-4">
                        <canvas id="chartGoalProgress"></canvas>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-center rounded-3 bg-light text-muted mb-4" style="border: 1px dashed var(--border);">
                        <i class="bi bi-graph-up fs-3 d-block mb-1 text-muted"></i>
                        <span class="small">Belum cukup data untuk membuat grafik perkembangan (minimal 2 catatan). Catat progres terbaru untuk melihat kurva.</span>
                    </div>
                <?php endif; ?>

                <!-- Progress History Table -->
                <h6 class="fw-bold mb-2 text-dark small">Log Riwayat Progres</h6>
                <div class="table-responsive overflow-auto" style="max-height: 250px;">
                    <table class="table table-sm table-hover align-middle mb-0 small" style="font-size:0.775rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Nilai</th>
                                <th>Progres</th>
                                <th>Sumber</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($progressLogs)): ?>
                                <?php foreach (array_reverse($progressLogs) as $log): ?>
                                    <tr>
                                        <td><?= date('d M Y', strtotime($log['recorded_at'])) ?></td>
                                        <td><strong><?= $log['current_value'] ?> <?= htmlspecialchars($goal['unit']) ?></strong></td>
                                        <td><?= $log['progress_percentage'] ?>%</td>
                                        <td>
                                            <span class="badge border bg-light text-muted" style="font-size:0.65rem;">
                                                <?= ucfirst($log['source_type']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">Belum ada riwayat progres.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Add Progress -->
<div class="modal fade" id="modalAddProgress" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border">
            <form action="goal_detail.php?id=<?= $goalId ?>" method="POST">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="add_progress">
                <div class="modal-header border-bottom">
                    <h6 class="modal-title fw-bold">Catat Progres Baru: <?= htmlspecialchars($goal['title']) ?></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small" for="prog_val">Nilai Terkini (Satuan: <?= htmlspecialchars($goal['unit']) ?>)</label>
                        <input type="number" step="any" name="current_value" id="prog_val" class="form-control" placeholder="Contoh: <?= $goal['current_value'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small" for="prog_date">Tanggal Pengukuran</label>
                        <input type="date" name="recorded_at" id="prog_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small" for="prog_notes">Catatan Tambahan (Opsional)</label>
                        <textarea name="notes" id="prog_notes" rows="2" class="form-control" placeholder="Kondisi atau metode saat pengukuran dilakukan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-health btn-sm px-4">Simpan Progres</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Add Milestone -->
<div class="modal fade" id="modalAddMilestone" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border">
            <form action="goal_detail.php?id=<?= $goalId ?>" method="POST">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="add_milestone">
                <div class="modal-header border-bottom">
                    <h6 class="modal-title fw-bold">Tambah Tahapan Milestone</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small" for="ms_title">Nama Tahapan</label>
                        <input type="text" name="title" id="ms_title" class="form-control form-control-sm" placeholder="Contoh: Fase 1: Membiasakan Jalan Kaki 20 Menit" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small" for="ms_desc">Deskripsi & Panduan (Opsional)</label>
                        <textarea name="description" id="ms_desc" rows="2" class="form-control form-control-sm" placeholder="Detail langkah yang perlu dilakukan..."></textarea>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small" for="ms_target">Target Nilai (Opsional)</label>
                            <input type="number" step="any" name="target_value" id="ms_target" class="form-control form-control-sm" placeholder="Angka target">
                        </div>
                        <div class="col-6">
                            <label class="form-label small" for="ms_order">Nomor Urutan</label>
                            <input type="number" min="1" name="sequence_order" id="ms_order" class="form-control form-control-sm" value="<?= count($milestones) + 1 ?>" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small" for="ms_date">Target Tanggal Selesai (Opsional)</label>
                        <input type="date" name="target_date" id="ms_date" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-health btn-sm px-4">Simpan Milestone</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (count($progressLogs) >= 2): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('chartGoalProgress').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_map(fn($l) => date('d/m', strtotime($l['recorded_at'])), $progressLogs)) ?>,
        datasets: [{
            label: 'Progres (<?= htmlspecialchars($goal['unit']) ?>)',
            data: <?= json_encode(array_map(fn($l) => (float)$l['current_value'], $progressLogs)) ?>,
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
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
