<?php
$pageTitle = 'HealthCheck — Pahami Kesehatanmu, Mulai dari Sini';
$activePage = 'home';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="py-5" style="background: linear-gradient(180deg, #f0fdfa 0%, #f8fafc 100%); border-bottom: 1px solid #e2e8f0;">
    <div class="container text-center py-4">
        <span class="badge bg-white text-teal border border-success-subtle px-3 py-2 rounded-pill fw-semibold mb-3 shadow-sm">
            <i class="bi bi-shield-check text-success me-1"></i> Alat Pemeriksaan Mandiri Digital
        </span>
        <h1 class="display-5 fw-bold text-dark mb-3">
            Pahami Kesehatanmu, <span class="text-teal" style="color: #0d9488;">Mulai dari Sini.</span>
        </h1>
        <p class="lead text-secondary mx-auto mb-4" style="max-width: 680px; font-size: 1.15rem;">
            Gunakan alat kesehatan sederhana untuk mendapatkan informasi awal berbasis parameter tubuh Anda dengan praktis, aman, dan mudah dipahami.
        </p>
        
        <!-- Search Tool Box -->
        <div class="mx-auto" style="max-width: 540px;">
            <div class="input-group input-group-lg shadow-sm rounded-4 overflow-hidden border">
                <span class="input-group-text bg-white border-0 ps-3">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text" id="toolSearch" class="form-control border-0 ps-2" placeholder="Cari alat kesehatan (misal: BMI, Kalori, Jantung...)" onkeyup="filterTools()">
            </div>
        </div>
    </div>
</section>

<!-- Tools & Categories Section -->
<section class="py-5">
    <div class="container">
        
        <!-- Category Filter Tabs -->
        <div class="d-flex flex-wrap justify-content-center gap-2 mb-5" id="categoryButtons">
            <button class="btn btn-primary-health btn-sm rounded-pill px-4 cat-btn active" onclick="filterCategory('all', this)">Semua Alat</button>
            <button class="btn btn-outline-secondary btn-sm rounded-pill px-4 cat-btn" onclick="filterCategory('gizi', this)">Gizi & Berat Badan</button>
            <button class="btn btn-outline-secondary btn-sm rounded-pill px-4 cat-btn" onclick="filterCategory('kardio', this)">Jantung & Kardiovaskular</button>
            <button class="btn btn-outline-secondary btn-sm rounded-pill px-4 cat-btn" onclick="filterCategory('mental', this)">Kesehatan Mental</button>
        </div>

        <!-- Grid Tools -->
        <div class="row g-4" id="toolsGrid">
            
            <!-- Tool 1: BMI -->
            <div class="col-md-6 col-lg-3 tool-item" data-category="gizi" data-title="kalkulator bmi indeks massa tubuh berat badan ideal kurus normal gemuk obesitas">
                <div class="card card-tool p-4">
                    <div class="tool-icon icon-teal">
                        <i class="bi bi-speedometer2"></i>
                    </div>
                    <span class="category-badge text-teal bg-teal-subtle align-self-start mb-2" style="background:#ccfbf1; color:#0f766e;">Gizi & Tubuh</span>
                    <h5 class="fw-bold mb-2 text-dark">Kalkulator BMI</h5>
                    <p class="text-secondary small mb-4 flex-grow-1">
                        Hitung Indeks Massa Tubuh (IMT) untuk mengetahui apakah proporsi berat badan Anda tergolong kurus, ideal, atau berlebih.
                    </p>
                    <a href="bmi_form.php" class="btn btn-outline-health w-100 mt-auto">
                        Cek Sekarang <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Tool 2: BMR & Kebutuhan Kalori -->
            <div class="col-md-6 col-lg-3 tool-item" data-category="gizi" data-title="kalkulator bmr kalori harian basal metabolic rate tdee makanan diet energi">
                <div class="card card-tool p-4">
                    <div class="tool-icon icon-blue">
                        <i class="bi bi-fire"></i>
                    </div>
                    <span class="category-badge text-primary bg-primary-subtle align-self-start mb-2">Energi & Metabolisme</span>
                    <h5 class="fw-bold mb-2 text-dark">Kalkulator BMR & Kalori</h5>
                    <p class="text-secondary small mb-4 flex-grow-1">
                        Ketahui laju metabolisme basal dan estimasi kalori harian (TDEE) sesuai tingkatan aktivitas fisik harian Anda.
                    </p>
                    <a href="bmr_form.php" class="btn btn-outline-health w-100 mt-auto">
                        Hitung Kalori <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Tool 3: Kalkulator Detak Jantung -->
            <div class="col-md-6 col-lg-3 tool-item" data-category="kardio" data-title="detak jantung nadi bpm denyut heart rate zona latihan kardio">
                <div class="card card-tool p-4">
                    <div class="tool-icon icon-rose">
                        <i class="bi bi-heart-pulse"></i>
                    </div>
                    <span class="category-badge text-danger bg-danger-subtle align-self-start mb-2">Kardiovaskular</span>
                    <h5 class="fw-bold mb-2 text-dark">Detak Jantung & Zona</h5>
                    <p class="text-secondary small mb-4 flex-grow-1">
                        Estimasi detak jantung istirahat (RHR), denyut maksimum, serta rentang target detak jantung optimal saat berolahraga.
                    </p>
                    <a href="jantung_form.php" class="btn btn-outline-health w-100 mt-auto">
                        Cek Denyut Nadi <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Tool 4: Mental Health PHQ-4 -->
            <div class="col-md-6 col-lg-3 tool-item" data-category="mental" data-title="kesehatan mental stres kecemasan cemas depresi suasana hati mood phq-4 self assessment">
                <div class="card card-tool p-4">
                    <div class="tool-icon icon-purple">
                        <i class="bi bi-emoji-smile"></i>
                    </div>
                    <span class="category-badge text-purple bg-purple-subtle align-self-start mb-2" style="background:#f3e8ff; color:#7e22ce;">Kesehatan Mental</span>
                    <h5 class="fw-bold mb-2 text-dark">Screening Mental Mandiri</h5>
                    <p class="text-secondary small mb-4 flex-grow-1">
                        Kuesioner penapisan singkat (PHQ-4 terstandar) untuk mengevaluasi tingkat kecemasan dan suasana hati Anda 2 minggu terakhir.
                    </p>
                    <a href="mental_form.php" class="btn btn-outline-health w-100 mt-auto">
                        Mulai Screening <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

        </div>

        <!-- Empty Search State -->
        <div id="noToolMatch" class="text-center py-5 d-none">
            <div class="p-4 bg-white rounded-4 border d-inline-block shadow-sm">
                <i class="bi bi-search text-muted fs-1 mb-2 d-block"></i>
                <h5 class="fw-bold text-dark">Alat Tidak Ditemukan</h5>
                <p class="text-secondary small mb-0">Coba gunakan kata kunci lain seperti BMI, kalori, denyut, atau mental.</p>
            </div>
        </div>

    </div>
</section>

<!-- Mengapa HealthCheck Section -->
<section class="py-5 bg-white border-top border-bottom">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="badge bg-light text-teal border border-teal-subtle px-3 py-2 rounded-pill fw-semibold mb-3">Tentang Platform</span>
                <h2 class="fw-bold text-dark mb-3">Solusi Digital Cek Mandiri yang Sederhana & Transparan</h2>
                <p class="text-secondary mb-4">
                    HealthCheck dirancang dengan rumus-rumus ilmiah tervalidasi (WHO BMI standards, Mifflin-St Jeor equation, Gellish HR formula, PHQ-4 Screening) tanpa istilah medis yang membingungkan.
                </p>
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="bg-success-subtle text-success rounded-circle p-2 px-3 fw-bold"><i class="bi bi-check2"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1 text-dark">Akses Cepat & Gratis</h6>
                            <p class="text-secondary small mb-0">Anda dapat langsung menggunakan semua kalkulator tanpa harus repot registrasi jika hanya butuh hasil cepat.</p>
                        </div>
                    </div>
                    <div class="d-flex gap-3 align-items-start">
                        <div class="bg-success-subtle text-success rounded-circle p-2 px-3 fw-bold"><i class="bi bi-shield-check"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1 text-dark">Privasi Terjaga</h6>
                            <p class="text-secondary small mb-0">Data Anda tidak diekspos maupun diperjualbelikan kepada pihak ketiga manapun.</p>
                        </div>
                    </div>
                    <div class="d-flex gap-3 align-items-start">
                        <div class="bg-success-subtle text-success rounded-circle p-2 px-3 fw-bold"><i class="bi bi-book"></i></div>
                        <div>
                            <h6 class="fw-bold mb-1 text-dark">Edukasi & Konteks Jelas</h6>
                            <p class="text-secondary small mb-0">Setiap hasil dilengkapi interpretasi mudah dipahami serta rambu kapan harus berkonsultasi ke dokter.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="p-4 p-md-5 rounded-4 border bg-light shadow-sm">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Panduan Penggunaan yang Bijak</h5>
                    <p class="text-secondary small mb-3">
                        Kalkulator kesehatan merupakan alat skrining awal dan estimasi numerik. Ingatlah bahwa:
                    </p>
                    <ul class="text-secondary small mb-4 d-flex flex-column gap-2 ps-3">
                        <li>BMI tidak membedakan massa lemak dari massa otot (misalnya pada atlet).</li>
                        <li>Kalori harian adalah estimasi teoretis, metabolisme setiap individu dapat bervariasi.</li>
                        <li>Detak jantung dapat dipengaruhi kafein, dehidrasi, stres, dan konsumsi obat.</li>
                        <li>Screening mental bukan vonis klinis, melainkan cermin refleksi untuk mencari pertolongan yang tepat.</li>
                    </ul>
                    <a href="bmi_form.php" class="btn btn-primary-health btn-sm px-4">Mulai Cek Sekarang</a>
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
