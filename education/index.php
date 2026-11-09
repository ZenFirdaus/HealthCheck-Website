<?php
$pageTitle = 'Pusat Edukasi Kesehatan — HealthCheck';
$activePage = 'education';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/history_helper.php';

// Filter & Search
$kategoriSlug = trim($_GET['kategori'] ?? 'all');
$searchQuery  = trim($_GET['q'] ?? '');

$sql = "SELECT a.*, c.nama_kategori, c.slug AS kategori_slug 
        FROM education_articles a 
        JOIN article_categories c ON a.kategori_id = c.id_kategori 
        WHERE a.status_publikasi = 'published'";
$params = [];

if ($kategoriSlug !== 'all' && !empty($kategoriSlug)) {
    $sql .= " AND c.slug = ?";
    $params[] = $kategoriSlug;
}

if (!empty($searchQuery)) {
    $sql .= " AND (a.judul LIKE ? OR a.ringkasan LIKE ? OR a.isi_konten LIKE ?)";
    $like = '%' . $searchQuery . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY a.id_artikel DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories for filter tabs
$categories = $pdo->query("SELECT * FROM article_categories ORDER BY id_kategori ASC")->fetchAll(PDO::FETCH_ASSOC);
$toolMeta = get_tool_metadata();
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item active" aria-current="page">Pusat Edukasi Kesehatan</li>
        </ol>
    </nav>

    <!-- Header Section -->
    <div class="text-center mx-auto mb-4" style="max-width: 680px;">
        <span class="eyebrow-text">Literasi Kesehatan Berbasis Bukti</span>
        <h2 class="section-heading mb-2">Pusat Informasi & Edukasi Medis</h2>
        <p class="lead-editorial mb-0">
            Panduan kesehatan, nutrisi, dan kebugaran yang disusun secara objektif berlandaskan publikasi ilmiah dan pedoman organisasi kesehatan terpercaya.
        </p>
    </div>

    <!-- Search & Filter Controls -->
    <div class="editorial-search-box mb-4">
        <form action="index.php" method="GET">
            <?php if ($kategoriSlug !== 'all'): ?>
                <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategoriSlug) ?>">
            <?php endif; ?>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control" placeholder="Cari topik (lemak viseral, tensi, tidur, stres...)" value="<?= htmlspecialchars($searchQuery) ?>">
                <?php if (!empty($searchQuery)): ?>
                    <a href="index.php?kategori=<?= urlencode($kategoriSlug) ?>" class="btn btn-outline-secondary btn-sm" title="Reset Pencarian">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Category Filter Bar -->
    <div class="category-nav-bar mb-4">
        <a href="index.php?kategori=all<?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" class="cat-btn <?= ($kategoriSlug === 'all') ? 'active' : '' ?>">
            Semua Topik
        </a>
        <?php foreach ($categories as $cat): ?>
            <a href="index.php?kategori=<?= urlencode($cat['slug']) ?><?= !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : '' ?>" class="cat-btn <?= ($kategoriSlug === $cat['slug']) ? 'active' : '' ?>">
                <?= htmlspecialchars($cat['nama_kategori']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Article Cards Grid -->
    <div class="row g-4 mb-5">
        <?php if (!empty($articles)): ?>
            <?php foreach ($articles as $art): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card-tool h-100">
                        <div class="tool-img-wrap" style="height: 190px;">
                            <img src="../<?= htmlspecialchars($art['gambar_cover'] ?: 'assets/images/hero-lifestyle.jpg') ?>" alt="<?= htmlspecialchars($art['judul']) ?>" loading="lazy">
                        </div>
                        <div class="tool-body">
                            <span class="tool-category mb-1" style="color: var(--blue);">
                                <?= htmlspecialchars($art['nama_kategori']) ?> &bull; <?= $art['waktu_baca_menit'] ?> mnt baca
                            </span>
                            <h5 class="tool-title" style="font-size: 1.05rem; line-height: 1.4;">
                                <a href="detail.php?slug=<?= urlencode($art['slug']) ?>" class="text-decoration-none text-dark">
                                    <?= htmlspecialchars($art['judul']) ?>
                                </a>
                            </h5>
                            <p class="tool-desc small mb-3">
                                <?= htmlspecialchars($art['ringkasan']) ?>
                            </p>
                            <div class="tool-footer d-flex justify-content-between align-items-center">
                                <?php if (!empty($art['terkait_alat']) && isset($toolMeta[$art['terkait_alat']])): ?>
                                    <span class="tool-hint">
                                        <i class="bi bi-calculator me-1"></i> Terkait <?= $toolMeta[$art['terkait_alat']]['title'] ?>
                                    </span>
                                <?php else: ?>
                                    <span class="tool-hint"><?= date('d M Y', strtotime($art['created_at'])) ?></span>
                                <?php endif; ?>
                                <a href="detail.php?slug=<?= urlencode($art['slug']) ?>" class="btn btn-primary-health btn-sm">
                                    Baca Artikel &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-journal-x fs-2 d-block mb-2 text-muted"></i>
                <h6 class="fw-bold text-dark mb-1">Artikel Tidak Ditemukan</h6>
                <p class="small text-muted mb-0">Coba gunakan kata kunci pencarian lain atau pilih kategori Semua Topik.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
