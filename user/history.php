<?php
$pageTitle = 'Riwayat Pemeriksaan Terpadu — HealthCheck';
$activePage = 'history';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../auth/login_cek.php';
require_once __DIR__ . '/../includes/auth_helper.php';
require_once __DIR__ . '/../includes/history_helper.php';

$userId = current_user_id();
if (!$userId) {
    header("Location: ../auth/login.php?pesan=belum_login");
    exit;
}

// Read filter parameters
$categoryFilter = trim($_GET['category'] ?? 'all');
$typeFilter     = trim($_GET['type'] ?? 'all');
$dateFrom       = trim($_GET['date_from'] ?? '');
$dateTo         = trim($_GET['date_to'] ?? '');
$searchQuery    = trim($_GET['search'] ?? '');
$sortOrder      = strtoupper(trim($_GET['sort'] ?? 'DESC'));
if ($sortOrder !== 'ASC') $sortOrder = 'DESC';

// Pagination parameters
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$filters = [
    'category'  => $categoryFilter,
    'type'      => $typeFilter,
    'date_from' => $dateFrom,
    'date_to'   => $dateTo,
    'search'    => $searchQuery,
    'sort'      => $sortOrder,
    'limit'     => $limit,
    'offset'    => $offset
];

$historyData = get_user_unified_history($pdo, $userId, $filters);
$totalRecords = $historyData['total_count'];
$records = $historyData['records'];
$totalPages = ceil($totalRecords / $limit) ?: 1;

$meta = get_tool_metadata();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Riwayat Terpadu</li>
        </ol>
    </nav>

    <!-- Header & Action Summary -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <span class="eyebrow-text">Log Rekam Medis Mandiri</span>
            <h3 class="fw-bold mb-1" style="color: var(--primary);">Riwayat Pemeriksaan Terpadu</h3>
            <p class="small text-muted mb-0">Arsip kronologis seluruh evaluasi biometrik yang telah Anda lakukan di platform HealthCheck.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-speedometer2 me-1"></i> Ke Dashboard
            </a>
            <a href="report_download.php" class="btn btn-primary-health btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i> Ekspor PDF
            </a>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card p-3 mb-4" style="background-color: var(--surface); border: 1px solid var(--border) !important;">
        <form action="history.php" method="GET" class="row g-2 align-items-end">
            <!-- Filter Kategori -->
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1" for="category">Kategori Alat</label>
                <select name="category" id="category" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" <?= $categoryFilter === 'all' ? 'selected' : '' ?>>Semua Kategori (12 Alat)</option>
                    <option value="gizi" <?= $categoryFilter === 'gizi' ? 'selected' : '' ?>>Gizi & Komposisi Tubuh</option>
                    <option value="kardio" <?= $categoryFilter === 'kardio' ? 'selected' : '' ?>>Jantung & Kardiovaskular</option>
                    <option value="tidur" <?= $categoryFilter === 'tidur' ? 'selected' : '' ?>>Pola Istirahat & Tidur</option>
                    <option value="mental" <?= $categoryFilter === 'mental' ? 'selected' : '' ?>>Kesehatan Mental & Stres</option>
                </select>
            </div>

            <!-- Filter Tanggal Mulai -->
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted mb-1" for="date_from">Dari Tanggal</label>
                <input type="date" name="date_from" id="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($dateFrom) ?>">
            </div>

            <!-- Filter Tanggal Selesai -->
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted mb-1" for="date_to">Sampai Tanggal</label>
                <input type="date" name="date_to" id="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($dateTo) ?>">
            </div>

            <!-- Urutan Sorting -->
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted mb-1" for="sort">Urutan</label>
                <select name="sort" id="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="DESC" <?= $sortOrder === 'DESC' ? 'selected' : '' ?>>Terbaru Dulu</option>
                    <option value="ASC" <?= $sortOrder === 'ASC' ? 'selected' : '' ?>>Terlama Dulu</option>
                </select>
            </div>

            <!-- Pencarian Kata Kunci -->
            <div class="col-6 col-md-3 d-flex gap-2">
                <div class="flex-grow-1">
                    <label class="form-label small text-muted mb-1" for="search">Cari Riwayat</label>
                    <input type="text" name="search" id="search" class="form-control form-control-sm" placeholder="Nama alat / status..." value="<?= htmlspecialchars($searchQuery) ?>">
                </div>
                <button type="submit" class="btn btn-primary-health btn-sm align-self-end px-3" title="Terapkan Filter">
                    <i class="bi bi-funnel"></i>
                </button>
                <?php if ($categoryFilter !== 'all' || !empty($dateFrom) || !empty($dateTo) || !empty($searchQuery)): ?>
                    <a href="history.php" class="btn btn-outline-secondary btn-sm align-self-end" title="Reset Filter">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="card overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Pemeriksaan</th>
                        <th>Kategori</th>
                        <th>Hasil Metrik</th>
                        <th>Status / Klasifikasi</th>
                        <th>Tanggal Waktu</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($records)): ?>
                        <?php foreach ($records as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="tool-icon m-0" style="width: 32px; height: 32px; font-size: 0.95rem; background-color: var(--<?= $item['color'] ?>-light, var(--primary-light)); color: var(--<?= $item['color'] ?>);">
                                            <i class="bi <?= $item['icon'] ?>"></i>
                                        </div>
                                        <div>
                                            <span class="fw-semibold text-dark d-block"><?= htmlspecialchars($item['tool_title']) ?></span>
                                            <small class="text-muted" style="font-size:0.75rem;">ID: #<?= $item['record_id'] ?></small>
                                        </div>
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
                                    <a href="<?= $item['detail_url'] ?>" class="btn btn-secondary-soft btn-sm">
                                        Lihat Rincian <i class="bi bi-chevron-right ms-1"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-2 d-block mb-2 text-muted"></i>
                                <h6 class="fw-bold text-dark mb-1">Tidak Ada Riwayat yang Sesuai</h6>
                                <p class="small mb-0 text-muted">Coba ubah kriteria filter pencarian atau gunakan kalkulator untuk menambah rekam data baru.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <?php if ($totalPages > 1): ?>
            <div class="p-3 border-top d-flex justify-content-between align-items-center" style="background-color: var(--surface);">
                <span class="small text-muted">
                    Menampilkan <strong><?= count($records) ?></strong> dari total <strong><?= $totalRecords ?></strong> rekaman
                </span>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link text-primary" href="history.php?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">&laquo;</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                                <a class="page-link" href="history.php?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link text-primary" href="history.php?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">&raquo;</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
