<?php
$pageTitle = 'HealthCheck — Evaluasi Kesehatan Mandiri & Data Biometrik Terstandar';
$activePage = 'home';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section (Hims-Inspired Editorial Healthcare) -->
<section class="editorial-hero-section">
    <div class="container">
        <div class="row g-5 align-items-center">
            <!-- Left Editorial Narrative -->
            <div class="col-lg-7">
                <div class="hero-content-col pe-lg-4">
                    <span class="eyebrow-text">Kesehatan Biometrik Mandiri</span>
                    <h1 class="hero-heading mb-3">
                        Pahami kondisi tubuh. Ambil keputusan kesehatan lebih bijak.
                    </h1>
                    <p class="lead-editorial mb-4" style="max-width: 580px;">
                        Evaluasi biometrik mandiri berbasis acuan klinis terstandar untuk memantau komposisi tubuh, metabolisme energi, laju denyut nadi, dan kesejahteraan emosional Anda secara objektif.
                    </p>
                    
                    <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                        <a href="#toolsGrid" class="btn btn-primary-health btn-lg">
                            Mulai Pemeriksaan <i class="bi bi-arrow-down-short fs-5 ms-1"></i>
                        </a>
                        <a href="#metodologi" class="editorial-link">
                            Pelajari Panduan Klinis <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>

                    <!-- Trust Bar -->
                    <div class="hero-trust-bar">
                        <div class="trust-item">
                            <i class="bi bi-check-circle-fill"></i>
                            <span>7 Alat Evaluasi Klinis</span>
                        </div>
                        <div class="trust-item">
                            <i class="bi bi-shield-lock-fill"></i>
                            <span>100% Privasi & Tanpa Registrasi Wajib</span>
                        </div>
                        <div class="trust-item">
                            <i class="bi bi-award-fill"></i>
                            <span>Standar Acuan WHO & Kemenkes RI</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Hero Imagery -->
            <div class="col-lg-5">
                <div class="hero-image-wrap">
                    <img src="assets/images/hero-doctor.jpg" alt="Konsultasi dan Evaluasi Kesehatan Modern HealthCheck" loading="eager">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Tools & Exploration Section -->
<section class="py-5" id="alatKesehatan">
    <div class="container py-3">
        
        <!-- Section Header -->
        <div class="text-center mx-auto mb-4" style="max-width: 640px;">
            <span class="eyebrow-text">Koleksi Alat Kesehatan</span>
            <h2 class="section-heading mb-2">Pilih Alat Pemeriksaan Sesuai Kebutuhan Anda</h2>
            <p class="lead-editorial mb-0">
                Pilih kategori di bawah atau gunakan kotak pencarian untuk menemukan kalkulator yang relevan dengan sasaran kesehatan Anda hari ini.
            </p>
        </div>

        <!-- Search Bar -->
        <div class="editorial-search-box">
            <div class="input-group">
                <span class="input-group-text">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" id="toolSearch" class="form-control" placeholder="Cari alat (BMI, Kalori, Pinggang, Lemak, Air, Jantung...)" onkeyup="filterTools()">
            </div>
        </div>

        <!-- Category Shortcut Navigation (Hims-Style Horizontal Bar) -->
        <div class="category-nav-bar" id="categoryButtons">
            <button class="cat-btn active" onclick="filterCategory('all', this)">
                <i class="bi bi-grid"></i> Semua Alat (12)
            </button>
            <button class="cat-btn" onclick="filterCategory('gizi', this)">
                <i class="bi bi-rulers"></i> Gizi & Tubuh (6)
            </button>
            <button class="cat-btn" onclick="filterCategory('kardio', this)">
                <i class="bi bi-heart-pulse"></i> Jantung & Kardio (3)
            </button>
            <button class="cat-btn" onclick="filterCategory('tidur', this)">
                <i class="bi bi-moon-stars"></i> Tidur & Gaya Hidup (1)
            </button>
            <button class="cat-btn" onclick="filterCategory('mental', this)">
                <i class="bi bi-chat-heart"></i> Kesehatan Mental (2)
            </button>
        </div>

        <!-- Editorial Spotlight Banner (Featured Health Tool) -->
        <div class="editorial-spotlight-card d-none d-lg-block">
            <div class="row g-0 align-items-center">
                <div class="col-lg-5">
                    <div class="spotlight-img-wrap">
                        <img src="assets/images/hero-lifestyle.jpg" alt="Gaya Hidup Sehat & Keseimbangan Nutrisi">
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="spotlight-content">
                        <span class="eyebrow-text">Sorotan Klinis</span>
                        <h3 class="card-heading fs-3 mb-3">Komposisi Fisik & Metabolisme: Fondasi Vitalitas Harian</h3>
                        <p class="small text-muted mb-4" style="line-height: 1.65;">
                            Kebugaran tubuh tidak hanya diukur dari angka timbangan semata. Memahami perbandingan lingkar pinggang terhadap tinggi badan (WHtR), persentase cadangan lemak tubuh, serta kebutuhan kalori harian (TDEE) memberikan gambaran menyeluruh tentang risiko kardiometabolik dan kebutuhan nutrisi Anda.
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="whtr/whtr_form.php" class="btn btn-primary-health">
                                Cek Rasio WHtR Sekarang <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                            <a href="bmr/bmr_form.php" class="btn btn-blue">
                                Hitung Kebutuhan Kalori
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid Tools (Image-Led Editorial Cards with Minimal Copy) -->
        <div class="row g-4" id="toolsGrid">
            
            <!-- Tool 1: BMI -->
            <div class="col-md-6 col-lg-4 tool-item" data-category="gizi" data-title="kalkulator bmi indeks massa tubuh berat badan ideal kurus normal gemuk obesitas">
                <div class="card-tool" data-color="blue">
                    <div class="tool-img-wrap">
                        <img src="assets/images/tool-bmi.jpg" alt="Kalkulator Indeks Massa Tubuh (BMI)" loading="lazy">
                    </div>
                    <div class="tool-body">
                        <span class="tool-category">Gizi & Komposisi Tubuh</span>
                        <h5 class="tool-title">Indeks Massa Tubuh (BMI)</h5>
                        <p class="tool-desc">
                            Ketahui indeks massa dan proporsi berat tubuh ideal.
                        </p>
                        <div class="tool-footer">
                            <span class="tool-hint">Standar Asia</span>
                            <a href="bmi/bmi_form.php" class="btn btn-primary-health btn-sm">
                                Hitung BMI <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tool 2: BMR & Kebutuhan Kalori -->
            <div class="col-md-6 col-lg-4 tool-item" data-category="gizi" data-title="kalkulator bmr kalori harian basal metabolic rate tdee makanan diet energi makronutrisi protein karbohidrat lemak">
                <div class="card-tool" data-color="emerald">
                    <div class="tool-img-wrap">
                        <img src="assets/images/tool-nutrition.jpg" alt="Kebutuhan Kalori & Makronutrisi" loading="lazy">
                    </div>
                    <div class="tool-body">
                        <span class="tool-category">Metabolisme & Kalori</span>
                        <h5 class="tool-title">Kalori & Makronutrisi</h5>
                        <p class="tool-desc">
                            Hitung kebutuhan kalori harian dan target nutrisi.
                        </p>
                        <div class="tool-footer">
                            <span class="tool-hint">Mifflin-St Jeor</span>
                            <a href="bmr/bmr_form.php" class="btn btn-emerald btn-sm">
                                Hitung Kalori <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tool 3: WHtR -->
            <div class="col-md-6 col-lg-4 tool-item" data-category="gizi" data-title="kalkulator whtr rasio lingkar pinggang tinggi badan perut buncit lemak viseral kardiometabolik">
                <div class="card-tool" data-color="teal">
                    <div class="tool-img-wrap">
                        <img src="assets/images/tool-whtr.jpg" alt="Rasio Pinggang ke Tinggi (WHtR)" loading="lazy">
                    </div>
                    <div class="tool-body">
                        <span class="tool-category">Adipositas Sentral</span>
                        <h5 class="tool-title">Rasio Pinggang-Tinggi (WHtR)</h5>
                        <p class="tool-desc">
                            Evaluasi distribusi lemak perut dan risiko kardiometabolik.
                        </p>
                        <div class="tool-footer">
                            <span class="tool-hint">Ashwell & Gibson</span>
                            <a href="whtr/whtr_form.php" class="btn btn-teal btn-sm">
                                Cek Rasio <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tool 4: Body Fat US Navy -->
            <div class="col-md-6 col-lg-4 tool-item" data-category="gizi" data-title="kalkulator lemak tubuh body fat percentage persen us navy antropometri leher pinggang pinggul massa otot">
                <div class="card-tool" data-color="blue">
                    <div class="tool-img-wrap">
                        <img src="assets/images/tool-bodyfat.jpg" alt="Persentase Lemak Tubuh (US Navy)" loading="lazy">
                    </div>
                    <div class="tool-body">
                        <span class="tool-category">Komposisi Tubuh</span>
                        <h5 class="tool-title">Persentase Lemak Tubuh</h5>
                        <p class="tool-desc">
                            Estimasi komposisi lemak tubuh metode US Navy.
                        </p>
                        <div class="tool-footer">
                            <span class="tool-hint">Metode US Navy</span>
                            <a href="body_fat/body_fat_form.php" class="btn btn-blue btn-sm">
                                Estimasi Lemak <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tool 5: Daily Fluid Needs -->
            <div class="col-md-6 col-lg-4 tool-item" data-category="gizi" data-title="kalkulator kebutuhan cairan air minum hidrasi dehidrasi liter mililiter gelas harian">
                <div class="card-tool" data-color="teal">
                    <div class="tool-img-wrap">
                        <img src="assets/images/tool-hydration.jpg" alt="Kebutuhan Cairan Harian" loading="lazy">
                    </div>
                    <div class="tool-body">
                        <span class="tool-category">Hidrasi Sehat</span>
                        <h5 class="tool-title">Kebutuhan Cairan Harian</h5>
                        <p class="tool-desc">
                            Pantau target asupan air harian sesuai aktivitas.
                        </p>
                        <div class="tool-footer">
                            <span class="tool-hint">Standar Kemenkes</span>
                            <a href="cairan/cairan_form.php" class="btn btn-teal btn-sm">
                                Hitung Air <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tool 6: Kalkulator Detak Jantung -->
            <div class="col-md-6 col-lg-4 tool-item" data-category="kardio" data-title="detak jantung nadi bpm denyut heart rate zona latihan kardio">
                <div class="card-tool" data-color="coral">
                    <div class="tool-img-wrap">
                        <img src="assets/images/tool-heart.jpg" alt="Detak Jantung & Zona Kardio" loading="lazy">
                    </div>
                    <div class="tool-body">
                        <span class="tool-category">Kardiovaskular</span>
                        <h5 class="tool-title">Detak Jantung & Zona Kardio</h5>
                        <p class="tool-desc">
                            Evaluasi denyut nadi istirahat dan rentang latihan.
                        </p>
                        <div class="tool-footer">
                            <span class="tool-hint">Formula Tanaka</span>
                            <a href="jantung/jantung_form.php" class="btn btn-coral btn-sm">
                                Cek Nadi <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tool 7: Mental Health PHQ-4 -->
            <div class="col-md-6 col-lg-4 tool-item" data-category="mental" data-title="kesehatan mental stres kecemasan cemas depresi suasana hati mood phq-4 self assessment">
                <div class="card-tool" data-color="lavender">
                    <div class="tool-img-wrap">
                        <img src="assets/images/tool-mental.jpg" alt="Skrining Kesehatan Mental (PHQ-4)" loading="lazy">
                    </div>
                    <div class="tool-body">
                        <span class="tool-category">Kesehatan Mental</span>
                        <h5 class="tool-title">Skrining Mental (PHQ-4)</h5>
                        <p class="tool-desc">
                            Penapisan awal suasana hati dan tingkat kecemasan.
                        </p>
                        <div class="tool-footer">
                            <span class="tool-hint">Instrumen PHQ-4</span>
                            <a href="mental/mental_form.php" class="btn btn-lavender btn-sm">
                                Mulai Skrining <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tool 8: Tekanan Darah -->
            <div class="col-md-6 col-lg-4 tool-item" data-category="kardio" data-title="tekanan darah tensi tensimeter sistolik diastolik hipertensi hipotensi kardiometabolik jantung">
                <div class="card-tool" data-color="coral">
                    <div class="tool-img-wrap">
                        <img src="assets/images/tool-tensi.jpg" alt="Pemeriksaan Tekanan Darah (Tensi)" loading="lazy">
                    </div>
                    <div class="tool-body">
                        <span class="tool-category">Kardiovaskular</span>
                        <h5 class="tool-title">Pemeriksaan Tekanan Darah</h5>
                        <p class="tool-desc">
                            Evaluasi sistolik dan diastolik standar AHA/ACC & PERHI.
                        </p>
                        <div class="tool-footer">
                            <span class="tool-hint">AHA/ACC & PERHI</span>
                            <a href="tekanan_darah/tekanan_darah_form.php" class="btn btn-coral btn-sm">
                                Cek Tensi <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tool 9: Pola Tidur -->
            <div class="col-md-6 col-lg-4 tool-item" data-category="tidur" data-title="pola tidur kualitas tidur insomnia higiene sirkadian latensi efisiensi jam istirahat malam">
                <div class="card-tool" data-color="teal">
                    <div class="tool-img-wrap">
                        <img src="assets/images/tool-sleep.jpg" alt="Evaluasi Kualitas & Higiene Tidur" loading="lazy">
                    </div>
                    <div class="tool-body">
                        <span class="tool-category">Pola Istirahat</span>
                        <h5 class="tool-title">Kualitas & Higiene Tidur</h5>
                        <p class="tool-desc">
                            Evaluasi efisiensi waktu istirahat dan pola sirkadian.
                        </p>
                        <div class="tool-footer">
                            <span class="tool-hint">Sleep Habit Index</span>
                            <a href="tidur/tidur_form.php" class="btn btn-teal btn-sm">
                                Evaluasi Tidur <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tool 10: Zona Latihan Detak Jantung -->
            <div class="col-md-6 col-lg-4 tool-item" data-category="kardio" data-title="zona latihan detak jantung kardio karvonen zoladz target bpm hrr olahraga lari aerobik">
                <div class="card-tool" data-color="blue">
                    <div class="tool-img-wrap">
                        <img src="assets/images/tool-hrzones.jpg" alt="Zona Latihan Detak Jantung (Karvonen & Zoladz)" loading="lazy">
                    </div>
                    <div class="tool-body">
                        <span class="tool-category">Kardiovaskular</span>
                        <h5 class="tool-title">Zona Latihan Kardio</h5>
                        <p class="tool-desc">
                            Estimasi 5 zona intensitas latihan Karvonen & Zoladz.
                        </p>
                        <div class="tool-footer">
                            <span class="tool-hint">Karvonen & Zoladz</span>
                            <a href="zona_jantung/zona_jantung_form.php" class="btn btn-blue btn-sm">
                                Hitung 5 Zona <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tool 11: Aktivitas Fisik (IPAQ) -->
            <div class="col-md-6 col-lg-4 tool-item" data-category="gizi" data-title="aktivitas fisik olahraga ipaq met menit sedentary duduk jalan kaki kebugaran energi">
                <div class="card-tool" data-color="emerald">
                    <div class="tool-img-wrap">
                        <img src="assets/images/tool-activity.jpg" alt="Evaluasi Aktivitas Fisik (IPAQ)" loading="lazy">
                    </div>
                    <div class="tool-body">
                        <span class="tool-category">Kebugaran Fisik</span>
                        <h5 class="tool-title">Aktivitas Fisik (IPAQ)</h5>
                        <p class="tool-desc">
                            Ukur pengeluaran energi MET-menit standar WHO.
                        </p>
                        <div class="tool-footer">
                            <span class="tool-hint">IPAQ Short Form</span>
                            <a href="aktivitas_fisik/aktivitas_fisik_form.php" class="btn btn-emerald btn-sm">
                                Hitung MET <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tool 12: Penilaian Tingkat Stres (PSS-10) -->
            <div class="col-md-6 col-lg-4 tool-item" data-category="mental" data-title="penilaian tingkat stres persepsi beban mental emosional pss-10 skala psikometri cemas ketegangan">
                <div class="card-tool" data-color="lavender">
                    <div class="tool-img-wrap">
                        <img src="assets/images/tool-stress.jpg" alt="Penilaian Tingkat Stres (PSS-10)" loading="lazy">
                    </div>
                    <div class="tool-body">
                        <span class="tool-category">Kesehatan Mental</span>
                        <h5 class="tool-title">Tingkat Stres (PSS-10)</h5>
                        <p class="tool-desc">
                            Penapisan persepsi beban stres 1 bulan terakhir.
                        </p>
                        <div class="tool-footer">
                            <span class="tool-hint">Instrumen PSS-10</span>
                            <a href="stres/stres_form.php" class="btn btn-lavender btn-sm">
                                Uji Tingkat Stres <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Empty Search State -->
        <div id="noToolMatch" class="text-center py-5 d-none">
            <div class="p-4 bg-white border d-inline-block" style="border-radius: var(--radius-md); max-width: 420px;">
                <i class="bi bi-search fs-3 mb-2 d-block" style="color: var(--muted);"></i>
                <h6 class="fw-bold text-dark mb-1">Alat Tidak Ditemukan</h6>
                <p class="small mb-0 text-muted">Coba gunakan kata kunci lain seperti BMI, kalori, pinggang, lemak, air, denyut, atau mental.</p>
            </div>
        </div>

    </div>
</section>

<!-- Informasi Metodologi & Batasan Klinis (Editorial Healthcare Transparency) -->
<section class="py-5 bg-white border-top border-bottom" id="metodologi">
    <div class="container py-3">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="pe-lg-3">
                    <span class="eyebrow-text">Prinsip Platform</span>
                    <h3 class="section-heading mb-3">Informasi Klinis Terukur dan Transparan</h3>
                    <p class="small text-muted" style="line-height: 1.7;">
                        HealthCheck dibangun untuk menyediakan kalkulator kesehatan mandiri yang jelas sumber formulasinya, tanpa klaim diagnosis medis berlebihan, dan menjamin privasi data pengguna sepenuhnya.
                    </p>
                    <div class="d-flex align-items-center gap-2 small fw-semibold text-secondary mt-3">
                        <i class="bi bi-shield-check fs-5 text-teal"></i> Terstandar Klinis & Non-Invasif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="info-card h-100">
                    <h6 class="fw-bold mb-3" style="color: var(--primary);">Standar & Formula Acuan</h6>
                    <ul class="list-unstyled small mb-0 d-flex flex-column gap-2 text-muted" style="line-height: 1.55;">
                        <li><strong>BMI:</strong> Standar klasifikasi berat badan WHO untuk populasi Asia.</li>
                        <li><strong>BMR / TDEE:</strong> Formula Mifflin-St Jeor yang divalidasi klinis.</li>
                        <li><strong>WHtR:</strong> Standar batas rasio pinggang-tinggi Ashwell & Gibson (2016).</li>
                        <li><strong>Lemak Tubuh:</strong> Persamaan antropometri lingkar tubuh US Navy (Hodgdon & Beckett).</li>
                        <li><strong>Kebutuhan Cairan:</strong> Pedoman Gizi Seimbang Kemenkes RI & ACSM.</li>
                        <li><strong>Tekanan Darah:</strong> Panduan klinis AHA/ACC 2017 & Konsensus PERHI 2021.</li>
                        <li><strong>Zona Latihan Kardio:</strong> Formula Karvonen (HRR) & Metode Zoladz.</li>
                        <li><strong>Pola Tidur:</strong> Penilaian Higiene & Kebiasaan Tidur (Sleep Habit Assessment).</li>
                        <li><strong>Aktivitas Fisik:</strong> Kuesioner resmi IPAQ Short Form (Craig et al., 2003).</li>
                        <li><strong>Tingkat Stres:</strong> Instrumen psikometri terstandar PSS-10 (Cohen et al., 1983).</li>
                        <li><strong>Kesehatan Mental:</strong> Instrumen penapisan PHQ-4 (Kroenke et al., 2009).</li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="info-card-tint h-100">
                    <h6 class="fw-bold mb-3" style="color: var(--primary);">Batasan Penggunaan Medis</h6>
                    <p class="small text-muted mb-3" style="line-height: 1.65;">
                        Seluruh hasil perhitungan bersifat edukatif dan estimatif awal. Angka-angka ini bukan pengganti diagnosis medis, evaluasi laboratorium, maupun rencana perawatan dari dokter spesialis.
                    </p>
                    <p class="small text-muted mb-0" style="line-height: 1.65;">
                        Jika Anda memiliki riwayat hipertensi, gangguan ginjal, jantung, atau keluhan psikologis yang menetap, selalu konsultasikan secara langsung dengan tenaga medis profesional.
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
        btn.classList.remove('active', 'btn-primary-health');
    });
    btnElement.classList.add('active');

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
