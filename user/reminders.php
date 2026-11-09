<?php
$pageTitle = 'Pengaturan Pengingat & Notifikasi — HealthCheck';
$activePage = 'reminders';
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

ensure_user_default_reminders($pdo, $userId);

$pesan = '';
$error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $action = trim($_POST['action'] ?? '');

    if ($action === 'update_reminder') {
        $reminderId = (int)($_POST['reminder_id'] ?? 0);
        $jam = trim($_POST['jam_reminder'] ?? '08:00');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $activeDays = isset($_POST['hari']) && is_array($_POST['hari']) ? implode(',', $_POST['hari']) : '1,2,3,4,5,6,7';

        // Validate time format (HH:MM)
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $jam)) {
            $jam = '08:00';
        }

        $stmt = $pdo->prepare("SELECT * FROM health_reminders WHERE id_reminder = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$reminderId, $userId]);
        $rem = $stmt->fetch();

        if ($rem) {
            $upd = $pdo->prepare("UPDATE health_reminders SET jam_reminder = ?, hari_aktif = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id_reminder = ? AND user_id = ?");
            $upd->execute([$jam, $activeDays, $isActive, $reminderId, $userId]);
            header("Location: reminders.php?pesan=reminder_updated");
            exit;
        } else {
            $error = 'Pengingat tidak ditemukan atau tidak memiliki hak akses.';
        }
    } elseif ($action === 'create_reminder') {
        $tipe = trim($_POST['tipe_reminder'] ?? 'custom');
        $judul = trim($_POST['judul'] ?? '');
        $pesanText = trim($_POST['pesan'] ?? '');
        $jam = trim($_POST['jam_reminder'] ?? '08:00');
        $activeDays = isset($_POST['hari']) && is_array($_POST['hari']) ? implode(',', $_POST['hari']) : '1,2,3,4,5,6,7';

        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $jam)) {
            $jam = '08:00';
        }

        if (!empty($judul) && !empty($pesanText)) {
            $ins = $pdo->prepare("INSERT INTO health_reminders (user_id, tipe_reminder, judul, pesan, jam_reminder, hari_aktif, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $ins->execute([$userId, $tipe, $judul, $pesanText, $jam, $activeDays]);
            header("Location: reminders.php?pesan=reminder_created");
            exit;
        } else {
            $error = 'Judul dan pesan pengingat wajib diisi.';
        }
    } elseif ($action === 'delete_reminder') {
        $reminderId = (int)($_POST['reminder_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM health_reminders WHERE id_reminder = ? AND user_id = ?");
        $stmt->execute([$reminderId, $userId]);
        header("Location: reminders.php?pesan=reminder_deleted");
        exit;
    } elseif ($action === 'mark_read') {
        $notifId = (int)($_POST['notif_id'] ?? 0);
        if ($notifId > 0) {
            $stmt = $pdo->prepare("UPDATE notification_logs SET status = 'read', read_at = CURRENT_TIMESTAMP WHERE id_notif = ? AND user_id = ?");
            $stmt->execute([$notifId, $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE notification_logs SET status = 'read', read_at = CURRENT_TIMESTAMP WHERE user_id = ? AND status = 'unread'");
            $stmt->execute([$userId]);
        }
        header("Location: reminders.php?pesan=notif_read");
        exit;
    } elseif ($action === 'trigger_check') {
        // Trigger on-demand check for due reminders
        $count = generate_due_reminders($pdo, $userId);
        header("Location: reminders.php?pesan=checked&count=" . $count);
        exit;
    }
}

$reminders = get_user_reminders($pdo, $userId, false);
$notifications = get_user_in_app_notifications($pdo, $userId, 15);
$unreadCount = (int)fetch_val($pdo, "SELECT COUNT(*) FROM notification_logs WHERE user_id = ? AND status = 'unread'", [$userId]);

function fetch_val(PDO $pdo, string $sql, array $params = []): mixed {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="habits.php" class="text-decoration-none">Habits Tracker</a></li>
            <li class="breadcrumb-item active" aria-current="page">Pengingat & Notifikasi</li>
        </ol>
    </nav>

    <?php if (isset($_GET['pesan'])): ?>
        <?php if ($_GET['pesan'] === 'reminder_updated'): ?>
            <div class="alert alert-success alert-dismissible fade show small mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>Jadwal pengingat berhasil diperbarui!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['pesan'] === 'reminder_created'): ?>
            <div class="alert alert-success alert-dismissible fade show small mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>Pengingat kebiasaan baru berhasil ditambahkan!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['pesan'] === 'reminder_deleted'): ?>
            <div class="alert alert-success alert-dismissible fade show small mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>Pengingat berhasil dihapus.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['pesan'] === 'notif_read'): ?>
            <div class="alert alert-info alert-dismissible fade show small mb-4" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i>Notifikasi telah ditandai terbaca.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['pesan'] === 'checked'): ?>
            <div class="alert alert-info alert-dismissible fade show small mb-4" role="alert">
                <i class="bi bi-bell-fill me-2"></i>Evaluasi pengingat selesai. <?= (int)($_GET['count'] ?? 0) ?> notifikasi baru dibuat.
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

    <!-- Welcome Header -->
    <div class="p-4 mb-4 rounded-3 border" style="background-color: var(--surface); border-color: var(--border) !important; box-shadow: var(--shadow-xs);">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <span class="eyebrow-text">Jadwal & Notifikasi</span>
                <h3 class="fw-bold mb-1" style="color: var(--primary);">Health Reminders</h3>
                <p class="small text-muted mb-0">
                    Atur jadwal pengingat hidrasi, jeda aktivitas fisik, waktu istirahat malam, dan check-in kesehatan harian Anda.
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-secondary-soft btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddReminder">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Pengingat Baru
                </button>
                <form action="reminders.php" method="POST" class="d-inline">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="trigger_check">
                    <button type="submit" class="btn btn-outline-secondary btn-sm" title="Jalankan pemeriksaan pengingat sekarang">
                        <i class="bi bi-arrow-clockwise me-1"></i> Cek Jadwal
                    </button>
                </form>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="requestBrowserNotification()">
                    <i class="bi bi-app-indicator me-1"></i> Uji Notifikasi Browser
                </button>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column: Reminder Schedules List -->
        <div class="col-lg-7">
            <div class="card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0" style="color: var(--primary);">Daftar Jadwal Pengingat</h5>
                    <span class="small text-muted"><?= count($reminders) ?> Jadwal Terdaftar</span>
                </div>

                <div class="d-flex flex-column gap-3">
                    <?php foreach ($reminders as $rem): ?>
                        <?php
                        $dayLabels = [
                            '1' => 'Sen', '2' => 'Sel', '3' => 'Rab', '4' => 'Kam',
                            '5' => 'Jum', '6' => 'Sab', '7' => 'Min'
                        ];
                        $activeDayArr = explode(',', (string)$rem['hari_aktif']);
                        $isAllDays = (count($activeDayArr) >= 7);
                        ?>
                        <div class="p-3 rounded-3 border d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3" style="background-color: var(--surface);">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="fs-5 fw-bold text-primary font-monospace"><?= htmlspecialchars($rem['jam_reminder']) ?></span>
                                    <h6 class="fw-bold text-dark mb-0" style="font-size: 0.95rem;"><?= htmlspecialchars($rem['judul']) ?></h6>
                                    <?php if ($rem['is_active'] == 1): ?>
                                        <span class="badge bg-success-subtle text-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border">Mati</span>
                                    <?php endif; ?>
                                </div>
                                <p class="small text-muted mb-1" style="line-height: 1.4; font-size: 0.8rem;">
                                    <?= htmlspecialchars($rem['pesan']) ?>
                                </p>
                                <span class="small text-muted" style="font-size: 0.725rem;">
                                    <i class="bi bi-calendar-check me-1"></i>
                                    Hari: <?= $isAllDays ? 'Setiap Hari' : implode(', ', array_map(fn($d) => $dayLabels[$d] ?? $d, $activeDayArr)) ?>
                                </span>
                            </div>

                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-3" data-bs-toggle="modal" data-bs-target="#modalEditReminder<?= $rem['id_reminder'] ?>">
                                    Ubah
                                </button>
                                <form action="reminders.php" method="POST" class="d-inline" onsubmit="return confirm('Hapus pengingat ini?');">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="delete_reminder">
                                    <input type="hidden" name="reminder_id" value="<?= $rem['id_reminder'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2" title="Hapus Pengingat">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Modal Edit Reminder -->
                        <div class="modal fade" id="modalEditReminder<?= $rem['id_reminder'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border">
                                    <form action="reminders.php" method="POST">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="action" value="update_reminder">
                                        <input type="hidden" name="reminder_id" value="<?= $rem['id_reminder'] ?>">
                                        <div class="modal-header border-bottom">
                                            <h6 class="modal-title fw-bold">Ubah Jadwal: <?= htmlspecialchars($rem['judul']) ?></h6>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <div class="mb-3">
                                                <label class="form-label small" for="jam_<?= $rem['id_reminder'] ?>">Waktu Pengingat (Format 24 Jam)</label>
                                                <input type="time" name="jam_reminder" id="jam_<?= $rem['id_reminder'] ?>" class="form-control" value="<?= htmlspecialchars($rem['jam_reminder']) ?>" required>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small d-block">Hari Aktif</label>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <?php foreach ($dayLabels as $val => $lbl): ?>
                                                        <div class="form-check form-check-inline m-0">
                                                            <input class="form-check-input" type="checkbox" name="hari[]" id="day_<?= $rem['id_reminder'] ?>_<?= $val ?>" value="<?= $val ?>" <?= in_array((string)$val, $activeDayArr, true) ? 'checked' : '' ?>>
                                                            <label class="form-check-label small" for="day_<?= $rem['id_reminder'] ?>_<?= $val ?>"><?= $lbl ?></label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>

                                            <div class="form-check form-switch mt-3">
                                                <input class="form-check-input" type="checkbox" name="is_active" id="active_rem_<?= $rem['id_reminder'] ?>" value="1" <?= $rem['is_active'] == 1 ? 'checked' : '' ?>>
                                                <label class="form-check-label small" for="active_rem_<?= $rem['id_reminder'] ?>">Aktifkan jadwal pengingat ini</label>
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
                </div>

                <!-- Technical Delivery Architecture Disclosure -->
                <div class="mt-4 p-3 rounded-3 border" style="background-color: var(--light-bg); border-color: var(--border) !important;">
                    <div class="fw-bold small text-primary mb-1"><i class="bi bi-info-circle-fill me-1"></i>Informasi Teknis Sistem Notifikasi:</div>
                    <p class="small text-muted mb-0" style="font-size: 0.775rem; line-height: 1.5;">
                        Platform HealthCheck menerapkan arsitektur notifikasi bertingkat: (1) <strong>In-app Notification</strong> muncul di akun Anda saat waktu reminder tiba, (2) <strong>Browser Notification</strong> dapat ditampilkan di desktop jika izin browser aktif, dan (3) <strong>Scheduled Background Worker</strong> dapat diotomasi pada server melalui cron job <code>php scripts/reminder_worker.php</code>.
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Column: In-App Notifications Feed -->
        <div class="col-lg-5">
            <div class="card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-0" style="color: var(--primary);">Kotak Notifikasi</h5>
                        <span class="small text-muted"><?= $unreadCount ?> belum dibaca</span>
                    </div>
                    <?php if ($unreadCount > 0): ?>
                        <form action="reminders.php" method="POST">
                            <?= csrf_input() ?>
                            <input type="hidden" name="action" value="mark_read">
                            <input type="hidden" name="notif_id" value="0">
                            <button type="submit" class="btn btn-link btn-sm text-decoration-none p-0" style="font-size: 0.8rem;">
                                Tandai Semua Terbaca
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="d-flex flex-column gap-2 overflow-auto" style="max-height: 480px;">
                    <?php if (!empty($notifications)): ?>
                        <?php foreach ($notifications as $notif): ?>
                            <div class="p-3 rounded-3 border <?= ($notif['status'] === 'unread') ? 'bg-white border-primary shadow-xs' : 'bg-light' ?>">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <span class="fw-bold text-dark small"><?= htmlspecialchars($notif['judul']) ?></span>
                                    <span class="badge border <?= ($notif['status'] === 'unread') ? 'bg-primary text-white' : 'bg-light text-muted' ?>" style="font-size: 0.65rem;">
                                        <?= ($notif['status'] === 'unread') ? 'Baru' : 'Dibaca' ?>
                                    </span>
                                </div>
                                <p class="small text-muted mb-2" style="font-size: 0.8rem; line-height: 1.4;">
                                    <?= htmlspecialchars($notif['pesan']) ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small text-muted" style="font-size: 0.7rem;">
                                        <i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($notif['sent_at'])) ?>
                                    </span>
                                    <?php if ($notif['status'] === 'unread'): ?>
                                        <form action="reminders.php" method="POST">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="action" value="mark_read">
                                            <input type="hidden" name="notif_id" value="<?= $notif['id_notif'] ?>">
                                            <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none" style="font-size: 0.75rem;">
                                                Tandai Dibaca
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-bell-slash fs-2 d-block mb-2 text-muted"></i>
                            <span class="small">Belum ada riwayat notifikasi pengingat.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Add Custom Reminder -->
<div class="modal fade" id="modalAddReminder" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border">
            <form action="reminders.php" method="POST">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="create_reminder">
                <div class="modal-header border-bottom">
                    <h6 class="modal-title fw-bold">Tambah Pengingat Kebiasaan</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small">Tipe Pengingat</label>
                        <select name="tipe_reminder" class="form-select form-select-sm">
                            <option value="water">Minum Air (Hidrasi)</option>
                            <option value="sleep">Waktu Istirahat (Tidur)</option>
                            <option value="activity">Aktivitas Fisik / Olahraga</option>
                            <option value="checkin">Daily Health Check-in</option>
                            <option value="custom">Pengingat Lainnya (Custom)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Judul Pengingat</label>
                        <input type="text" name="judul" class="form-control form-control-sm" placeholder="Contoh: Minum Segelas Air" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Pesan Pengingat</label>
                        <textarea name="pesan" rows="2" class="form-control form-control-sm" placeholder="Pesan singkat motivasi..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Waktu Pengingat</label>
                        <input type="time" name="jam_reminder" class="form-control form-control-sm" value="08:00" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small d-block">Hari Aktif</label>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($dayLabels as $val => $lbl): ?>
                                <div class="form-check form-check-inline m-0">
                                    <input class="form-check-input" type="checkbox" name="hari[]" id="new_day_<?= $val ?>" value="<?= $val ?>" checked>
                                    <label class="form-check-label small" for="new_day_<?= $val ?>"><?= $lbl ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary-health btn-sm px-4">Simpan Pengingat</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function requestBrowserNotification() {
    if (!("Notification" in window)) {
        alert("Browser Anda tidak mendukung Web Notification API.");
        return;
    }
    if (Notification.permission === "granted") {
        new Notification("HealthCheck Reminder", {
            body: "Pengingat kesehatan harian Anda siap aktif!",
            icon: "../assets/images/favicon.ico"
        });
    } else if (Notification.permission !== "denied") {
        Notification.requestPermission().then(function (permission) {
            if (permission === "granted") {
                new Notification("HealthCheck Reminder", {
                    body: "Izin notifikasi berhasil diaktifkan!",
                    icon: "../assets/images/favicon.ico"
                });
            }
        });
    } else {
        alert("Notifikasi browser sebelumnya dinonaktifkan di pengaturan browser Anda.");
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
