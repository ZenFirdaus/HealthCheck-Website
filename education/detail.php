<?php
$activePage = 'education';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/history_helper.php';

$slug = trim($_GET['slug'] ?? '');
$id = (int)($_GET['id'] ?? 0);

if (!empty($slug)) {
    $stmt = $pdo->prepare("SELECT a.*, c.nama_kategori, c.slug AS kategori_slug 
        FROM education_articles a 
        JOIN article_categories c ON a.kategori_id = c.id_kategori 
        WHERE a.slug = ? AND a.status_publikasi = 'published' LIMIT 1");
    $stmt->execute([$slug]);
} elseif ($id > 0) {
    $stmt = $pdo->prepare("SELECT a.*, c.nama_kategori, c.slug AS kategori_slug 
        FROM education_articles a 
        JOIN article_categories c ON a.kategori_id = c.id_kategori 
        WHERE a.id_artikel = ? AND a.status_publikasi = 'published' LIMIT 1");
    $stmt->execute([$id]);
} else {
    header("Location: index.php");
    exit;
}

$article = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$article) {
    http_response_code(404);
    die("404 Not Found: Artikel edukasi tidak ditemukan.");
}

// Increment views count
$updStmt = $pdo->prepare("UPDATE education_articles SET views_count = views_count + 1 WHERE id_artikel = ?");
$updStmt->execute([$article['id_artikel']]);

$pageTitle = $article['judul'] . ' — HealthCheck Edukasi';
$toolMeta = get_tool_metadata();
$relatedTool = !empty($article['terkait_alat']) && isset($toolMeta[$article['terkait_alat']]) ? $toolMeta[$article['terkait_alat']] : null;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Pusat Edukasi</a></li>
            <li class="breadcrumb-item"><a href="index.php?kategori=<?= urlencode($article['kategori_slug']) ?>" class="text-decoration-none"><?= htmlspecialchars($article['nama_kategori']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars(mb_strimwidth($article['judul'], 0, 35, '...')) ?></li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <!-- Main Article Content -->
        <div class="col-lg-8">
            <article class="card overflow-hidden p-4 p-md-5 mb-4">
                <span class="eyebrow-text"><?= htmlspecialchars($article['nama_kategori']) ?></span>
                
                <h1 class="card-heading fs-2 mb-3" style="line-height: 1.35; color: var(--primary);">
                    <?= htmlspecialchars($article['judul']) ?>
                </h1>

                <!-- Metadata Row -->
                <div class="d-flex flex-wrap align-items-center gap-3 text-muted small pb-3 mb-4 border-bottom">
                    <span><i class="bi bi-person me-1"></i> <?= htmlspecialchars($article['penulis']) ?></span>
                    <span>&bull;</span>
                    <span><i class="bi bi-calendar3 me-1"></i> <?= date('d F Y', strtotime($article['created_at'])) ?></span>
                    <span>&bull;</span>
                    <span><i class="bi bi-clock me-1"></i> <?= $article['waktu_baca_menit'] ?> Menit Membaca</span>
                </div>

                <!-- Cover Image -->
                <?php if (!empty($article['gambar_cover'])): ?>
                    <div class="rounded-3 overflow-hidden mb-4" style="max-height: 380px;">
                        <img src="../<?= htmlspecialchars($article['gambar_cover']) ?>" alt="<?= htmlspecialchars($article['judul']) ?>" class="w-100 h-100 object-fit-cover">
                    </div>
                <?php endif; ?>

                <!-- Lead Summary -->
                <p class="lead text-dark fw-medium mb-4" style="font-size: 1.05rem; line-height: 1.65;">
                    <?= htmlspecialchars($article['ringkasan']) ?>
                </p>

                <!-- Body HTML Content -->
                <div class="article-body text-dark" style="line-height: 1.75; font-size: 0.95rem;">
                    <?= $article['isi_konten'] ?>
                </div>

                <!-- Verified Medical Reference Citations -->
                <?php if (!empty($article['referensi_medis'])): ?>
                    <div class="p-3 mt-4 rounded-3 border" style="background-color: var(--light-bg); border-color: var(--border) !important;">
                        <div class="fw-bold small text-primary mb-1"><i class="bi bi-journal-bookmark me-1"></i> Sumber & Referensi Klinis:</div>
                        <div class="small text-muted" style="font-size: 0.8rem; line-height: 1.55;">
                            <?= htmlspecialchars($article['referensi_medis']) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Medical Disclaimer -->
                <div class="disclaimer-box mt-4 mb-2">
                    <strong class="d-block" style="color: var(--primary);">Catatan Medis</strong>
                    <span class="small text-muted" style="line-height: 1.5; display: block;">
                        Artikel ini diterbitkan untuk tujuan edukasi kesehatan masyarakat umum dan tidak menggantikan konsultasi langsung dengan dokter spesialis atau tenaga medis berlisensi.
                    </span>
                </div>
            </article>

            <!-- Back to Index Button -->
            <div class="d-flex justify-content-between align-items-center mb-5">
                <a href="index.php" class="btn btn-outline-secondary btn-sm px-4">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Katalog Edukasi
                </a>
            </div>
        </div>

        <!-- Sidebar (Related Tool & Categories) -->
        <div class="col-lg-4">
            <!-- Related Tool Widget -->
            <?php if ($relatedTool): ?>
                <div class="card p-4 mb-4 border" style="background-color: var(--surface);">
                    <span class="eyebrow-text">Alat Terkait Artikel</span>
                    <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars($relatedTool['title']) ?></h5>
                    <p class="small text-muted mb-3">
                        Terapkan wawasan dari artikel ini langsung dengan melakukan evaluasi mandiri menggunakan kalkulator kesehatan resmi kami.
                    </p>
                    <a href="<?= $relatedTool['form_url'] ?>" class="btn btn-primary-health btn-sm w-100">
                        Coba <?= htmlspecialchars($relatedTool['title']) ?> &rarr;
                    </a>
                </div>
            <?php endif; ?>

            <div class="card p-4">
                <h6 class="fw-bold mb-3" style="color: var(--primary);">Kategori Topik Edukasi</h6>
                <?php
                $allCats = $pdo->query("SELECT * FROM article_categories ORDER BY id_kategori ASC")->fetchAll();
                ?>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($allCats as $c): ?>
                        <a href="index.php?kategori=<?= urlencode($c['slug']) ?>" class="p-2 rounded-2 text-decoration-none hover-bg d-flex justify-content-between align-items-center">
                            <span class="small fw-semibold text-dark"><?= htmlspecialchars($c['nama_kategori']) ?></span>
                            <i class="bi bi-chevron-right text-muted small"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
