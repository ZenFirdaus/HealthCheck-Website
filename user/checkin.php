<?php
$pageTitle = 'Daily Health Check-in — HealthCheck';
$activePage = 'checkin';
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

// Handle date parameter
$date = trim($_GET['date'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = date('Y-m-d');
}

$pesan = '';
$error = '';

// Handle POST checkin submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $postDate = trim($_POST['tanggal'] ?? $date);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $postDate)) {
        $postDate = $date;
    }

    $waterMl = (isset($_POST['water_ml']) && $_POST['water_ml'] !== '') ? max(0, (float)$_POST['water_ml']) : null;
    $sleepHours = (isset($_POST['sleep_hours']) && $_POST['sleep_hours'] !== '') ? max(0, min(24, (float)$_POST['sleep_hours'])) : null;
    $stepsCount = (isset($_POST['steps_count']) && $_POST['steps_count'] !== '') ? max(0, (int)$_POST['steps_count']) : null;
    $activityType = !empty($_POST['activity_type']) ? trim($_POST['activity_type']) : null;
    $activityMins = (isset($_POST['activity_minutes']) && $_POST['activity_minutes'] !== '') ? max(0, (int)$_POST['activity_minutes']) : null;
    $kondisi = !empty($_POST['kondisi_energi']) ? trim($_POST['kondisi_energi']) : null;
    $catatan = !empty($_POST['catatan']) ? trim($_POST['catatan']) : null;

    $checkinData = [
        'water_ml'         => $waterMl,
        'sleep_hours'      => $sleepHours,
        'steps_count'      => $stepsCount,
        'activity_type'    => $activityType,
        'activity_minutes' => $activityMins,
        'kondisi_energi'   => $kondisi,
        'catatan'          => $catatan
    ];

    $saved = save_daily_checkin($pdo, $userId, $postDate, $checkinData);
    if ($saved) {
        header("Location: checkin.php?date=" . urlencode($postDate) . "&pesan=checkin_success");
        exit;
    } else {
        $error = 'Gagal menyimpan check-in harian. Silakan coba kembali.';
    }
}

// Fetch existing checkin for the date
$existing = get_daily_checkin($pdo, $userId, $date);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="habits.php" class="text-decoration-none">Habits Tracker</a></li>
            <li class="breadcrumb-item active" aria-current="page">Daily Health Check-in</li>
        </ol>
    </nav>

    <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'checkin_success'): ?>
        <div class="alert alert-success alert-dismissible fade show small mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>Daily Health Check-in berhasil disimpan dan tersinkronisasi ke log kebiasaan harian Anda!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show small mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card overflow-hidden mb-4">
                <div class="card-editorial-header d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                    <div>
                        <span class="eyebrow-text">Jurnal Harian Kesehatan</span>
                        <h4 class="card-heading mb-0">Daily Health Check-in</h4>
                    </div>
                    <!-- Date Selector Switcher -->
                    <div class="d-flex align-items-center gap-1">
                        <a href="checkin.php?date=<?= date('Y-m-d', strtotime('-1 day', strtotime($date))) ?>" class="btn btn-outline-secondary btn-sm" title="Kemarin">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                        <span class="badge border bg-white text-dark px-3 py-2 small fw-semibold">
                            <?= date('d M Y', strtotime($date)) ?>
                            <?= ($date === date('Y-m-d')) ? '<span class="badge bg-success-subtle text-success ms-1">Hari Ini</span>' : '' ?>
                        </span>
                        <a href="checkin.php?date=<?= date('Y-m-d', strtotime('+1 day', strtotime($date))) ?>" class="btn btn-outline-secondary btn-sm" title="Besok">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                </div>

                <div class="p-4 p-md-5">
                    <p class="small text-muted mb-4">
                        Catat aktivitas biologis dan rutinitas harian Anda dalam satu langkah praktis. Data ini secara otomatis menyinkronkan progres pelacak kebiasaan (*Habits Tracker*).
                    </p>

                    <!-- Summary of existing check-in if submitted -->
                    <?php if ($existing): ?>
                        <div class="p-3 mb-4 rounded-3 border" style="background-color: var(--light-bg); border-color: var(--border) !important;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small fw-bold text-primary"><i class="bi bi-check-all me-1"></i>Status Check-in Tanggal Ini:</span>
                                <span class="small text-muted" style="font-size:0.75rem;">Terakhir disimpan: <?= date('d M Y, H:i', strtotime($existing['updated_at'])) ?> WIB</span>
                            </div>
                            <div class="row g-2 small text-dark">
                                <div class="col-6 col-sm-3">Air: <strong><?= $existing['water_ml'] !== null ? $existing['water_ml'] . ' ml' : '-' ?></strong></div>
                                <div class="col-6 col-sm-3">Tidur: <strong><?= $existing['sleep_hours'] !== null ? $existing['sleep_hours'] . ' jam' : '-' ?></strong></div>
                                <div class="col-6 col-sm-3">Langkah: <strong><?= $existing['steps_count'] !== null ? number_format($existing['steps_count'], 0, ',', '.') : '-' ?></strong></div>
                                <div class="col-6 col-sm-3">Olahraga: <strong><?= $existing['activity_minutes'] !== null ? $existing['activity_minutes'] . ' mnt' : '-' ?></strong></div>
                            </div>
                            <?php if (!empty($existing['kondisi_energi'])): ?>
                                <div class="small mt-2 pt-2 border-top text-muted" style="font-size:0.775rem;">
                                    Tingkat Energi: <strong class="text-dark"><?= ucfirst(str_replace('_', ' ', $existing['kondisi_energi'])) ?></strong>
                                    <?php if (!empty($existing['catatan'])): ?>
                                        &bull; Catatan: "<?= htmlspecialchars($existing['catatan']) ?>"
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <form action="checkin.php" method="POST">
                        <?= csrf_input() ?>
                        <input type="hidden" name="tanggal" value="<?= htmlspecialchars($date) ?>">

                        <!-- 1. Water Intake -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark small mb-1" for="water_ml">
                                <i class="bi bi-droplet-half text-teal me-1"></i> 1. Konsumsi Air Minum Hari Ini
                            </label>
                            <div class="input-group">
                                <input type="number" step="any" min="0" max="10000" name="water_ml" id="water_ml" class="form-control" placeholder="Contoh: 2000" value="<?= $existing['water_ml'] ?? '' ?>">
                                <span class="input-group-text">ml</span>
                            </div>
                            <small class="text-muted" style="font-size: 0.775rem;">Tips: 1 gelas standar setara &plusmn;250 ml. Target umum: 2.000–2.500 ml/hari.</small>
                        </div>

                        <!-- 2. Sleep Duration -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark small mb-1" for="sleep_hours">
                                <i class="bi bi-moon-stars text-blue me-1"></i> 2. Durasi Tidur Semalam
                            </label>
                            <div class="input-group">
                                <input type="number" step="0.1" min="0" max="24" name="sleep_hours" id="sleep_hours" class="form-control" placeholder="Contoh: 7.5" value="<?= $existing['sleep_hours'] ?? '' ?>">
                                <span class="input-group-text">Jam</span>
                            </div>
                            <small class="text-muted" style="font-size: 0.775rem;">Durasi total tidur lelap malam hari. Rekomendasi dewasa: 7–9 jam.</small>
                        </div>

                        <!-- 3. Steps Count -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark small mb-1" for="steps_count">
                                <i class="bi bi-person-walking text-emerald me-1"></i> 3. Jumlah Langkah Kaki Hari Ini
                            </label>
                            <div class="input-group">
                                <input type="number" min="0" max="100000" name="steps_count" id="steps_count" class="form-control" placeholder="Contoh: 8500" value="<?= $existing['steps_count'] ?? '' ?>">
                                <span class="input-group-text">Langkah</span>
                            </div>
                            <small class="text-muted" style="font-size: 0.775rem;">Data dari pedometer ponsel, jam pintar, atau estimasi aktivitas harian.</small>
                        </div>

                        <!-- 4. Physical Activity & Duration -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark small mb-1">
                                <i class="bi bi-activity text-coral me-1"></i> 4. Aktivitas Fisik / Olahraga Terukur
                            </label>
                            <div class="row g-2">
                                <div class="col-sm-6">
                                    <select name="activity_type" class="form-select form-select-sm">
                                        <option value="">-- Pilih Jenis Aktivitas (Opsional) --</option>
                                        <option value="Jalan Santai / Cepat" <?= ($existing && $existing['activity_type'] === 'Jalan Santai / Cepat') ? 'selected' : '' ?>>Jalan Santai / Cepat</option>
                                        <option value="Lari / Jogging" <?= ($existing && $existing['activity_type'] === 'Lari / Jogging') ? 'selected' : '' ?>>Lari / Jogging</option>
                                        <option value="Bersepeda" <?= ($existing && $existing['activity_type'] === 'Bersepeda') ? 'selected' : '' ?>>Bersepeda</option>
                                        <option value="Berenang" <?= ($existing && $existing['activity_type'] === 'Berenang') ? 'selected' : '' ?>>Berenang</option>
                                        <option value="Latihan Beban / Gym" <?= ($existing && $existing['activity_type'] === 'Latihan Beban / Gym') ? 'selected' : '' ?>>Latihan Beban / Gym</option>
                                        <option value="Senam / Yoga" <?= ($existing && $existing['activity_type'] === 'Senam / Yoga') ? 'selected' : '' ?>>Senam / Yoga</option>
                                        <option value="Olahraga Permainan" <?= ($existing && $existing['activity_type'] === 'Olahraga Permainan') ? 'selected' : '' ?>>Olahraga Permainan (Futsal, Badminton, dll.)</option>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <div class="input-group input-group-sm">
                                        <input type="number" min="0" max="1440" name="activity_minutes" class="form-control" placeholder="Durasi" value="<?= $existing['activity_minutes'] ?? '' ?>">
                                        <span class="input-group-text">Menit</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 5. Energy Level (Optional) -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark small mb-1">
                                <i class="bi bi-battery-charging text-primary me-1"></i> 5. Kondisi & Tingkat Energi Hari Ini (Opsional)
                            </label>
                            <div class="row g-2">
                                <div class="col-6 col-sm-3">
                                    <input type="radio" class="btn-check" name="kondisi_energi" id="energi_sangat_bugar" value="sangat_bugar" <?= ($existing && $existing['kondisi_energi'] === 'sangat_bugar') ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-secondary btn-sm w-100 text-truncate" for="energi_sangat_bugar">Sangat Bugar</label>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <input type="radio" class="btn-check" name="kondisi_energi" id="energi_baik" value="baik" <?= ($existing && $existing['kondisi_energi'] === 'baik') ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-secondary btn-sm w-100 text-truncate" for="energi_baik">Berenergi Baik</label>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <input type="radio" class="btn-check" name="kondisi_energi" id="energi_cukup" value="cukup" <?= ($existing && $existing['kondisi_energi'] === 'cukup') ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-secondary btn-sm w-100 text-truncate" for="energi_cukup">Biasa / Cukup</label>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <input type="radio" class="btn-check" name="kondisi_energi" id="energi_lelah" value="lelah" <?= ($existing && $existing['kondisi_energi'] === 'lelah') ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-secondary btn-sm w-100 text-truncate" for="energi_lelah">Lelah / Linu</label>
                                </div>
                            </div>
                        </div>

                        <!-- 6. Personal Notes (Optional) -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark small mb-1" for="catatan">
                                <i class="bi bi-journal-text text-muted me-1"></i> 6. Catatan Refleksi Pribadi (Opsional)
                            </label>
                            <textarea name="catatan" id="catatan" rows="2" class="form-control" placeholder="Contoh: Merasa segar setelah jalan pagi, asupan makan terkontrol..."><?= htmlspecialchars($existing['catatan'] ?? '') ?></textarea>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex flex-wrap gap-2 pt-2">
                            <button type="submit" class="btn btn-primary-health px-4">
                                <i class="bi bi-save me-1"></i> Simpan Check-in Hari Ini
                            </button>
                            <a href="habits.php?date=<?= urlencode($date) ?>" class="btn btn-secondary-soft px-4">
                                Buka Habit Tracker
                            </a>
                            <a href="dashboard.php" class="btn btn-outline-secondary ms-auto px-4">
                                Ke Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
