<?php
$pageTitle = 'HealthCheck — Kalkulator & Skrining Kesehatan Mandiri';
$activePage = 'home';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="py-5" style="background-color: var(--surface); border-bottom: 1px solid var(--card-border);">
    <div class="container text-center py-3">
        <h1 class="hero-heading mb-2">
            Kalkulator & Skrining Kesehatan Mandiri
        </h1>
        <p class="lead-editorial mx-auto mb-4" style="max-width: 620px;">
            Alat evaluasi mandiri berbasis parameter klinis standar untuk memantau komposisi tubuh, laju metabolisme, rentang denyut nadi, dan kesejahteraan emosional Anda.
        </p>
        
        <!-- Search Tool Box -->
        <div class="mx-auto" style="max-width: 480px;">
            <div class="input-group overflow-hidden" style="border: 1px solid var(--card-border); border-radius: var(--radius-md); background-color: var(--surface);">
                <span class="input-group-text bg-transparent border-0 ps-3">
                    <i class="bi bi-search" style="color: var(--muted);"></i>
                </span>
                <input type="text" id="toolSearch" class="form-control border-0 ps-2" placeholder="Cari alat (BMI, Kalori, Jantung, Mental...)" onkeyup="filterTools()">
            </div>
        </div>
    </div>
</section>

<!-- Tools & Categories Section -->
<section class="py-5">
    <div class="container">
        
        <!-- Category Filter Tabs -->
        <div class="d-flex flex-wrap justify-content-center gap-2 mb-4" id="categoryButtons">
            <button class="btn btn-primary-health btn-sm cat-btn active" onclick="filterCategory('all', this)">Semua Alat</button>
            <button class="btn btn-outline-secondary btn-sm cat-btn" onclick="filterCategory('gizi', this)">Gizi & Berat Badan</button>
            <button class="btn btn-outline-secondary btn-sm cat-btn" onclick="filterCategory('kardio', this)">Jantung & Kardiovaskular</button>
            <button class="btn btn-outline-secondary btn-sm cat-btn" onclick="filterCategory('mental', this)">Kesehatan Mental</button>
        </div>

        <!-- Grid Tools -->
        <div class="row g-4" id="toolsGrid">
            
            <!-- Tool 1: BMI -->
            <div class="col-md-6 col-lg-3 tool-item" data-category="gizi" data-title="kalkulator bmi indeks massa tubuh berat badan ideal kurus normal gemuk obesitas">
                <div class="card card-tool">
                    <div class="card-tool-header">
                        <div class="tool-icon">
                            <i class="bi bi-speedometer2"></i>
                        </div>
                        <span class="category-badge">Gizi & Tubuh</span>
                    </div>
                    <h5 class="fw-bold mb-2 text-dark">Indeks Massa Tubuh (BMI)</h5>
                    <p class="small mb-4 flex-grow-1" style="color: var(--muted); line-height: 1.55;">
                        Hitung perbandingan berat dan tinggi badan untuk mengetahui apakah proporsi tubuh Anda tergolong kurus, ideal, atau berlebih.
                    </p>
                    <a href="bmi/bmi_form.php" class="btn btn-outline-health w-100 mt-auto">
                        Hitung BMI
                    </a>
                </div>
            </div>

            <!-- Tool 2: BMR & Kebutuhan Kalori -->
            <div class="col-md-6 col-lg-3 tool-item" data-category="gizi" data-title="kalkulator bmr kalori harian basal metabolic rate tdee makanan diet energi">
                <div class="card card-tool">
                    <div class="card-tool-header">
                        <div class="tool-icon">
                            <i class="bi bi-fire"></i>
                        </div>
                        <span class="category-badge">Energi & Metabolisme</span>
                    </div>
                    <h5 class="fw-bold mb-2 text-dark">Kebutuhan Kalori & BMR</h5>
                    <p class="small mb-4 flex-grow-1" style="color: var(--muted); line-height: 1.55;">
                        Estimasi laju metabolisme basal dan kebutuhan kalori harian (TDEE) berdasarkan tingkat aktivitas fisik Anda.
                    </p>
                    <a href="bmr/bmr_form.php" class="btn btn-outline-health w-100 mt-auto">
                        Hitung Kalori
                    </a>
                </div>
            </div>

            <!-- Tool 3: Kalkulator Detak Jantung -->
            <div class="col-md-6 col-lg-3 tool-item" data-category="kardio" data-title="detak jantung nadi bpm denyut heart rate zona latihan kardio">
                <div class="card card-tool">
                    <div class="card-tool-header">
                        <div class="tool-icon">
                            <i class="bi bi-heart-pulse"></i>
                        </div>
                        <span class="category-badge">Kardiovaskular</span>
                    </div>
                    <h5 class="fw-bold mb-2 text-dark">Detak Jantung & Zona Kardio</h5>
                    <p class="small mb-4 flex-grow-1" style="color: var(--muted); line-height: 1.55;">
                        Estimasi detak jantung istirahat (RHR), denyut maksimum, serta rentang target denyut jantung optimal saat berolahraga.
                    </p>
                    <a href="jantung/jantung_form.php" class="btn btn-outline-health w-100 mt-auto">
                        Cek Detak Jantung
                    </a>
                </div>
            </div>

            <!-- Tool 4: Mental Health PHQ-4 -->
            <div class="col-md-6 col-lg-3 tool-item" data-category="mental" data-title="kesehatan mental stres kecemasan cemas depresi suasana hati mood phq-4 self assessment">
                <div class="card card-tool">
                    <div class="card-tool-header">
                        <div class="tool-icon">
                            <i class="bi bi-chat-heart"></i>
                        </div>
                        <span class="category-badge">Kesehatan Mental</span>
                    </div>
                    <h5 class="fw-bold mb-2 text-dark">Skrining Mental (PHQ-4)</h5>
                    <p class="small mb-4 flex-grow-1" style="color: var(--muted); line-height: 1.55;">
                        Kuesioner penapisan singkat (PHQ-4 terstandar) untuk mengevaluasi tingkat kecemasan dan suasana hati Anda 2 minggu terakhir.
                    </p>
                    <a href="mental/mental_form.php" class="btn btn-outline-health w-100 mt-auto">
                        Mulai Skrining
                    </a>
                </div>
            </div>

        </div>

        <!-- Empty Search State -->
        <div id="noToolMatch" class="text-center py-5 d-none">
            <div class="p-4 bg-white border d-inline-block" style="border-radius: var(--radius-md);">
                <i class="bi bi-search fs-3 mb-2 d-block" style="color: var(--muted);"></i>
                <h6 class="fw-bold text-dark mb-1">Alat Tidak Ditemukan</h6>
                <p class="small mb-0" style="color: var(--muted);">Coba gunakan kata kunci lain seperti BMI, kalori, denyut, atau mental.</p>
            </div>
        </div>

    </div>
</section>

<!-- Informasi Metodologi & Batasan Klinis -->
<section class="py-5 bg-white border-top border-bottom">
    <div class="container py-2">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="pe-lg-3">
                    <span class="eyebrow-text">Prinsip Platform</span>
                    <h3 class="section-heading mb-3">Informasi Klinis yang Terukur dan Transparan</h3>
                    <p class="small" style="color: var(--muted); line-height: 1.65;">
                        HealthCheck dibangun untuk menyediakan kalkulator kesehatan mandiri yang jelas sumber rumusnya, tanpa janji medis berlebihan, dan menghormati privasi data pengguna secara penuh.
                    </p>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="p-4 h-100 border rounded-3" style="border-color: var(--card-border) !important; background-color: var(--light-bg);">
                    <h6 class="fw-bold mb-2" style="color: var(--primary);">Standar & Formula Acuan</h6>
                    <ul class="list-unstyled small mb-0 d-flex flex-column gap-2" style="color: var(--muted); line-height: 1.55;">
                        <li><strong>BMI:</strong> Standar klasifikasi berat badan WHO untuk populasi Asia.</li>
                        <li><strong>BMR / TDEE:</strong> Formula Mifflin-St Jeor yang tervalidasi secara klinis.</li>
                        <li><strong>Denyut Maksimal:</strong> Formula Gellish untuk batas denyut nadi latihan.</li>
                        <li><strong>Kesehatan Mental:</strong> Instrumen penapisan PHQ-4 (Kroenke et al., 2009).</li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="p-4 h-100 border rounded-3" style="border-color: var(--card-border) !important; background-color: var(--light-bg);">
                    <h6 class="fw-bold mb-2" style="color: var(--primary);">Batasan Penggunaan</h6>
                    <p class="small mb-0" style="color: var(--muted); line-height: 1.6;">
                        Seluruh hasil perhitungan bersifat edukatif dan estimatif awal. Angka-angka ini bukan pengganti diagnosis medis, evaluasi laboratorium, maupun rencana perawatan dari dokter spesialis.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Client Side Filter Script -->
<script>
let currentCategory = 'all';

function filterCategory(category, btnElement) {
    currentCategory = category;
    
    // Update active button styling
    document.querySelectorAll('.cat-btn').forEach(btn => {
        btn.classList.remove('btn-primary-health', 'active');
        btn.classList.add('btn-outline-secondary');
    });
    btnElement.classList.remove('btn-outline-secondary');
    btnElement.classList.add('btn-primary-health', 'active');

    filterTools();
}

function filterTools() {
    const searchVal = document.getElementById('toolSearch').value.toLowerCase().trim();
    const items = document.querySelectorAll('.tool-item');
    let visibleCount = 0;

    items.forEach(item => {
        const itemCat = item.getAttribute('data-category');
        const itemTitle = item.getAttribute('data-title').toLowerCase();
        
        const matchesCategory = (currentCategory === 'all' || itemCat === currentCategory);
        const matchesSearch = (searchVal === '' || itemTitle.includes(searchVal));

        if (matchesCategory && matchesSearch) {
            item.style.display = 'block';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    const emptyBox = document.getElementById('noToolMatch');
    if (visibleCount === 0) {
        emptyBox.classList.remove('d-none');
    } else {
        emptyBox.classList.add('d-none');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
