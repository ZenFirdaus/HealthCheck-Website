<?php
/**
 * Migration 20261108_002: Phase 3 Education Center Tables and Seed Content
 */

return new class {
    public function up(PDO $pdo): void {
        // 1. Article Categories
        $pdo->exec("CREATE TABLE IF NOT EXISTS article_categories (
            id_kategori INTEGER PRIMARY KEY AUTOINCREMENT,
            nama_kategori VARCHAR(100) NOT NULL UNIQUE,
            slug VARCHAR(100) NOT NULL UNIQUE,
            deskripsi TEXT
        )");

        // 2. Education Articles
        $pdo->exec("CREATE TABLE IF NOT EXISTS education_articles (
            id_artikel INTEGER PRIMARY KEY AUTOINCREMENT,
            kategori_id INTEGER NOT NULL,
            judul VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            ringkasan TEXT NOT NULL,
            isi_konten TEXT NOT NULL,
            penulis VARCHAR(100) DEFAULT 'Tim Edukasi Medis HealthCheck',
            referensi_medis TEXT,
            terkait_alat VARCHAR(50),
            gambar_cover VARCHAR(255),
            waktu_baca_menit INT DEFAULT 4,
            views_count INT DEFAULT 0,
            status_publikasi VARCHAR(20) DEFAULT 'published',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (kategori_id) REFERENCES article_categories(id_kategori) ON DELETE CASCADE
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_art_slug ON education_articles(slug)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_art_kat ON education_articles(kategori_id, created_at DESC)");

        // Seed default categories
        $categories = [
            ['Gizi & Komposisi Tubuh', 'gizi-tubuh', 'Panduan nutrisi seimbang, manajemen kalori, dan evaluasi adipositas tubuh.'],
            ['Jantung & Kardiovaskular', 'kardiovaskular', 'Informasi pemeliharaan tekanan darah, denyut nadi, dan zona latihan kardio.'],
            ['Pola & Higiene Tidur', 'tidur-pemulihan', 'Edukasi ritme sirkadian, efisiensi istirahat, dan pemulihan seluler.'],
            ['Kesehatan Mental & Stres', 'kesehatan-mental', 'Strategi regulasi stres, penapisan kecemasan, dan kesejahteraan emosional.'],
            ['Aktivitas & Kebugaran', 'aktivitas-fisik', 'Standar kebugaran WHO, pemantauan energi MET, dan latihan terukur.']
        ];

        $stmtCat = $pdo->prepare("INSERT OR IGNORE INTO article_categories (nama_kategori, slug, deskripsi) VALUES (?, ?, ?)");
        foreach ($categories as $cat) {
            $stmtCat->execute($cat);
        }

        // Seed 5 verified evidence-based articles
        $articles = [
            [
                'gizi-tubuh',
                'Memahami Lemak Viseral: Mengapa Rasio Lingkar Pinggang Lebih Kritis dari Timbangan',
                'memahami-lemak-viseral-dan-whtr',
                'Berat badan normal belum tentu bebas dari risiko kardiometabolik jika lemak terkonsentrasi di area rongga perut.',
                '<p>Banyak orang mengira indeks massa tubuh (BMI) adalah satu-satunya indikator kesehatan komposisi fisik. Padahal, lokasi penyimpanan lemak dalam tubuh memegang peranan yang jauh lebih menentukan terhadap risiko penyakit kronis.</p><h5>Apa Itu Lemak Viseral?</h5><p>Berbeda dengan lemak subkutan yang berada tepat di bawah lapisan kulit, lemak viseral menumpuk di rongga perut bagian dalam dan membungkus organ-organ vital seperti hati, pankreas, dan usus. Lemak viseral bersifat aktif secara metabolik—ia memproduksi senyawa pro-inflamasi (sitokin) yang dapat memicu resistensi insulin dan aterosklerosis pembuluh darah.</p><h5>Aturan Emas Rasio Lingkar Pinggang (WHtR)</h5><p>Penelitian dari Dr. Margaret Ashwell (2016) mempopulerkan aturan sederhana: pertahankan lingkar pinggang Anda kurang dari setengah tinggi badan Anda. Jika tinggi badan Anda 170 cm, batas aman lingkar pinggang adalah di bawah 85 cm. Rasio di atas 0.50 menandakan peningkatan risiko kardiometabolik yang perlu diantisipasi melalui defisit kalori dan olahraga teratur.</p>',
                'Ashwell M, Gibson S. BMJ Open 2016;6:e010159; Browning LM, et al. Nutr Res Rev 2010;23(2):247-269.',
                'whtr',
                'assets/images/tool-whtr.jpg',
                4
            ],
            [
                'kardiovaskular',
                'Mengukur Tensi di Rumah: Cara Mencegah Efek "Jas Putih" dan Fluktuasi Palsu',
                'panduan-pengukuran-tekanan-darah-mandiri',
                'Langkah teknis dan protokol klinis 5 menit istirahat untuk mendapatkan angka tensimeter digital yang akurat dan stabil.',
                '<p>Pemeriksaan tekanan darah mandiri di rumah (Home Blood Pressure Monitoring) kini direkomendasikan secara global oleh konsensus PERHI dan AHA/ACC untuk memantau tren sirkulasi darah tanpa terpengaruh kecemasan sesaat.</p><h5>Fenomena "White Coat Hypertension"</h5><p>Banyak pasien mengalami lonjakan tensi saat diukur di klinik akibat rasa tegang bawah sadar di hadapan tenaga medis. Sebaliknya, pengukuran mandiri di rumah memberikan gambaran rata-rata yang lebih mendekati kondisi fisiologis harian Anda sesungguhnya.</p><h5>Protokol 5 Menit yang Benar</h5><ul><li>Duduk bersandar tegak selama minimal 5 menit sebelum menekan tombol start.</li><li>Telapak kaki menapak rata di lantai (jangan menyilangkan kaki).</li><li>Posisikan manset setinggi letak jantung.</li><li>Hindari merokok, konsumsi kopi, dan berolahraga 30 menit sebelum pengukuran.</li><li>Jika angka terukur >180/>120 mmHg tanpa gejala, istirahat tenang 5 menit lalu ukur ulang.</li></ul>',
                'Konsensus PERHI 2021; Whelton PK, et al. J Am Coll Cardiol 2018;71(19):e127-e248.',
                'tensi',
                'assets/images/tool-tensi.jpg',
                5
            ],
            [
                'tidur-pemulihan',
                'Higiene Tidur dan Ritme Sirkadian: Mengapa Waktu Konsisten Mengalahkan Durasi Acak',
                'higiene-tidur-dan-ritme-sirkadian',
                'Tidur 8 jam dengan jam tidur berantakan tidak menghasilkan pemulihan seluler yang sama dengan jadwal sirkadian konsisten.',
                '<p>Tidur bukan sekadar tombol istirahat pasif, melainkan fase pembersihan metabolik otak (sistem glimfatik) dan sintesis hormon pertumbuhan. Jam biologis utama (nukleus suprakiasmatik) di otak bekerja berdasarkan keteraturan siklus cahaya dan gelap.</p><h5>Dampak Paparan Blue Light Sebelum Tidur</h5><p>Spektrum cahaya biru dari layar ponsel menipu otak bahwa waktu masih siang, sehingga menekan sekresi hormon melatonin alami. Dampaknya, waktu yang dibutuhkan untuk terlelap (latensi tidur) meningkat dan fase tidur gelombang lambat (deep sleep) berkurang drastis.</p><h5>Langkah Praktis Sleep Hygiene</h5><p>Jaga waktu bangun pagi yang sama setiap hari bahkan di akhir pekan. Matikan perangkat elektronik minimal 30 menit sebelum tidur, dan pastikan suhu kamar sejuk sekitar 20–24 derajat Celsius untuk memicu penurunan suhu inti tubuh alami saat tidur.</p>',
                'Hirshkowitz M, et al. Sleep Health 2015;1(1):40-43; Walker M. Why We Sleep, Scribner 2017.',
                'tidur',
                'assets/images/tool-sleep.jpg',
                4
            ],
            [
                'kesehatan-mental',
                'Membedakan Stres Normal dan Distres Kronis: Kapan Harus Mencari Bantuan Profesional',
                'membedakan-stres-normal-dan-distres-kronis',
                'Penjelasan ilmiah mengenai kurva respons stres biologis serta indikator penapisan mandiri PSS-10 dan PHQ-4.',
                '<p>Stres pada tingkat tertentu (eustress) bermanfaat memicu fokus dan performa. Namun, ketika aktivasi sistem saraf simpatis dan hormon kortisol terus menyala tanpa fase pemulihan, tubuh memasuki kondisi distres kronis.</p><h5>Gejala Distres yang Perlu Diwaspadai</h5><p>Perhatikan apakah rasa cemas atau suasana hati murung mulai mengganggu pola makan, siklus tidur, dan relasi sosial lebih dari 2 minggu berturut-turut. Instrumen penapisan reflektif seperti PSS-10 dan PHQ-4 membantu kita menyadari taraf beban emosional secara objektif.</p><h5>Dukungan Profesional Bukan Kelemahan</h5><p>Menghubungi psikolog klinis, konselor, atau layanan bantuan seperti hotline Sejiwa Kemenkes (119 ext. 8) adalah langkah preventif rasional untuk memulihkan kapasitas daya lentur mental (resiliensi) Anda.</p>',
                'Cohen S, et al. J Health Soc Behav 1983; Kroenke K, et al. Psychosomatics 2009;50(6):613-621.',
                'stres',
                'assets/images/tool-stress.jpg',
                5
            ],
            [
                'aktivitas-fisik',
                'Target 150 Menit WHO dan Bahaya "Active Couch Potato"',
                'rekomendasi-aktivitas-fisik-who-dan-met-minutes',
                'Mengapa berolahraga 1 jam sehari belum sepenuhnya melindungi jika Anda duduk diam selama 9 jam sisanya.',
                '<p>Pedoman global World Health Organization (WHO) merekomendasikan minimal 150–300 menit aktivitas fisik intensitas sedang atau 75–150 menit aktivitas intensitas berat per minggu bagi orang dewasa.</p><h5>Fenomena Active Couch Potato</h5><p>Seseorang yang berolahraga lari 30 menit di pagi hari namun duduk diam di kursi kantor selama 9 jam tanpa jeda tetap memiliki risiko kardiometabolik akibat terhentinya kontraksi otot rangka pengurai glukosa dan trigliserida.</p><h5>Solusi Selingan Mikro (Exercise Snacks)</h5><p>Gunakan pengingat untuk berdiri, berjalan santai, atau meregangkan otot selama 2 menit setiap 45–60 menit duduk. Akumulasi aktivitas fisik harian terukur dalam satuan MET-menit memberikan perlindungan kardiovaskular jangka panjang.</p>',
                'Bull FC, et al. Br J Sports Med 2020;54(24):1451-1462; Craig CL, et al. Med Sci Sports Exerc 2003.',
                'aktivitas',
                'assets/images/tool-activity.jpg',
                4
            ]
        ];

        $stmtArt = $pdo->prepare("INSERT OR IGNORE INTO education_articles 
            (kategori_id, judul, slug, ringkasan, isi_konten, referensi_medis, terkait_alat, gambar_cover, waktu_baca_menit) 
            SELECT id_kategori, ?, ?, ?, ?, ?, ?, ?, ? FROM article_categories WHERE slug = ?");

        foreach ($articles as $art) {
            $stmtArt->execute([
                $art[1], // judul
                $art[2], // slug
                $art[3], // ringkasan
                $art[4], // isi_konten
                $art[5], // referensi
                $art[6], // terkait_alat
                $art[7], // cover
                $art[8], // waktu_baca
                $art[0]  // kategori slug
            ]);
        }
    }

    public function down(PDO $pdo): void {
        $pdo->exec("DROP INDEX IF EXISTS idx_art_kat");
        $pdo->exec("DROP INDEX IF EXISTS idx_art_slug");
        $pdo->exec("DROP TABLE IF EXISTS education_articles");
        $pdo->exec("DROP TABLE IF EXISTS article_categories");
    }
};
