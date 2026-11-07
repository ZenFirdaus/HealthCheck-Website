# Analisis & Rencana Basis Data HealthCheck

**Status Dokumen:** Baseline Audit & Skema Arsitektur Masa Depan  
**DBMS:** SQLite 3 (berkas aktif: `database/healthcheck.sqlite`)  
**Strategi Kompatibilitas:** Mempertahankan tabel dan data lama, menerapkan migrasi *non-destructive* (penambahan kolom `user_id` *nullable*), serta menyusun tabel baru yang terstruktur.

---

## 1. Audit Skema & Kondisi Database Saat Ini

Berdasarkan inspeksi riil pada berkas `database/healthcheck.sqlite`, terdapat 5 tabel utama yang diinisialisasi melalui skrip `includes/db.php`:

### 1.1 Struktur Tabel yang Ada

#### A. Tabel `user`
Menyimpan akun pengguna sistem (pengguna umum dan administrator).
* **Kolom:**
  * `id` : `INTEGER PRIMARY KEY AUTOINCREMENT`
  * `username` : `VARCHAR(100) NOT NULL UNIQUE`
  * `password` : `VARCHAR(255) NOT NULL`
  * `role` : `VARCHAR(50) DEFAULT 'user'`
  * `created_at` : `DATETIME DEFAULT CURRENT_TIMESTAMP`
* **Primary Key:** `id`
* **Foreign Key:** Tidak ada.
* **Index:** Otomatis pada `id` (PK) dan `sqlite_autoindex_user_1` (UNIQUE `username`).
* **Kondisi Data Riil:**
  * Record 1: `1 | admin | admin123 | admin | 2026-09-25 03:58:41`
  * Record 2: `2 | user | user123 | user | 2026-09-25 03:58:41`
  * Catatan: Password tersimpan dalam bentuk teks polos (*plain text*).

#### B. Tabel `cek_bmi`
Menyimpan catatan hasil perhitungan Indeks Massa Tubuh (BMI).
* **Kolom:**
  * `id_bmi` : `INTEGER PRIMARY KEY AUTOINCREMENT`
  * `nama_user` : `VARCHAR(150) NOT NULL`
  * `umur` : `INT NOT NULL`
  * `berat_badan` : `REAL NOT NULL`
  * `tinggi_badan` : `REAL NOT NULL`
  * `bmi` : `REAL DEFAULT 0`
  * `kondisi` : `VARCHAR(100) NOT NULL`
  * `created_at` : `DATETIME DEFAULT CURRENT_TIMESTAMP`
* **Primary Key:** `id_bmi`
* **Foreign Key:** Tidak ada.
* **Index:** Hanya PK (`id_bmi`). Tidak ada index untuk `nama_user` atau `created_at`.
* **Kondisi Data Riil:** 0 baris (kosong).

#### C. Tabel `cek_bmr`
Menyimpan catatan metabolisme energi basal dan estimasi kalori harian.
* **Kolom:**
  * `id_bmr` : `INTEGER PRIMARY KEY AUTOINCREMENT`
  * `nama_user` : `VARCHAR(150) NOT NULL`
  * `jenis_kelamin` : `VARCHAR(10) NOT NULL`
  * `umur` : `INT NOT NULL`
  * `berat_badan` : `REAL NOT NULL`
  * `tinggi_badan` : `REAL NOT NULL`
  * `bmr` : `REAL NOT NULL`
  * `aktivitas` : `VARCHAR(100) DEFAULT 'Sedentary'`
  * `tdee` : `REAL DEFAULT 0`
  * `created_at` : `DATETIME DEFAULT CURRENT_TIMESTAMP`
* **Primary Key:** `id_bmr`
* **Foreign Key:** Tidak ada.
* **Index:** Hanya PK (`id_bmr`).
* **Kondisi Data Riil:** 0 baris (kosong).

#### D. Tabel `cek_jantung`
Menyimpan rekam evaluasi detak nadi istirahat dan zona denyut kardiovaskular.
* **Kolom:**
  * `id_jantung` : `INTEGER PRIMARY KEY AUTOINCREMENT`
  * `nama_user` : `VARCHAR(150) NOT NULL`
  * `umur` : `INT NOT NULL`
  * `resting_hr` : `INT NOT NULL`
  * `max_hr` : `INT NOT NULL`
  * `kategori` : `VARCHAR(100) NOT NULL`
  * `catatan` : `TEXT`
  * `created_at` : `DATETIME DEFAULT CURRENT_TIMESTAMP`
* **Primary Key:** `id_jantung`
* **Foreign Key:** Tidak ada.
* **Index:** Hanya PK (`id_jantung`).
* **Kondisi Data Riil:** 0 baris (kosong).

#### E. Tabel `cek_mental`
Menyimpan skor penapisan kecemasan dan suasana hati berbasis instrumen PHQ-4.
* **Kolom:**
  * `id_mental` : `INTEGER PRIMARY KEY AUTOINCREMENT`
  * `nama_user` : `VARCHAR(150) NOT NULL`
  * `skor_cemas` : `INT NOT NULL`
  * `skor_depresi` : `INT NOT NULL`
  * `total_skor` : `INT NOT NULL`
  * `tingkat` : `VARCHAR(100) NOT NULL`
  * `anjuran` : `TEXT`
  * `created_at` : `DATETIME DEFAULT CURRENT_TIMESTAMP`
* **Primary Key:** `id_mental`
* **Foreign Key:** Tidak ada.
* **Index:** Hanya PK (`id_mental`).
* **Kondisi Data Riil:** 0 baris (kosong).

---

## 2. Evaluasi Keterbatasan & Titik Masalah Basis Data

1. **Ketiadaan Konsep Kepemilikan Data (*Zero Relational Integrity*):**
   * Tidak satu pun dari 4 tabel pemeriksaan yang menyimpan `user_id`. Semua rekam medis publik maupun member disimpan campur aduk hanya berlabel string teks `nama_user`.
   * Jika pengguna bernama "Budi" login, sistem tidak dapat membedakan data Budi yang login dengan orang lain yang mengisi form dengan nama "Budi".
2. **Ketiadaan Pengindeksan untuk Pencarian & Kronologi:**
   * Setiap *query* riwayat atau visualisasi tren waktu harus melakukan *Full Table Scan* karena tidak ada index pada `created_at` atau identitas pengguna.
3. **Data Profil Pengguna Sangat Minim:**
   * Tabel `user` hanya memiliki `id`, `username`, `password`, `role`, dan `created_at`. Tidak tersedia data pendukung profil seperti nama lengkap, jenis kelamin, tanggal lahir, tinggi badan awal, dan kontak pengguna yang krusial untuk otomatisasi kalkulator di masa depan.
4. **Resiko Performa akibat *Auto-Schema* di `includes/db.php`:**
   * Pemanggilan `self::initializeSchema()` pada setiap koneksi PDO menimbulkan eksekusi kueri DDL berulang yang tidak efisien. Diperlukan mekanisme migrasi mandiri terpisah atau flag pengecekan sekali jalan.

---

## 3. Rencana Migrasi & Penyesuaian Tabel Lama (Non-Destructive)

Untuk mempertahankan riwayat yang sudah ada dan tidak merusak fungsi lama, kita menggunakan perintah `ALTER TABLE ADD COLUMN` yang didukung penuh oleh SQLite:

```sql
-- 1. Penambahan kolom profil pada tabel user
ALTER TABLE user ADD COLUMN nama_lengkap VARCHAR(150);
ALTER TABLE user ADD COLUMN email VARCHAR(150);
ALTER TABLE user ADD COLUMN jenis_kelamin VARCHAR(10);
ALTER TABLE user ADD COLUMN tanggal_lahir DATE;
ALTER TABLE user ADD COLUMN tinggi_badan REAL;
ALTER TABLE user ADD COLUMN berat_badan REAL;
ALTER TABLE user ADD COLUMN updated_at DATETIME;

-- 2. Tautkan tabel kalkulator lama ke entitas user (Nullable agar kalkulator publik anonim tetap bekerja)
ALTER TABLE cek_bmi ADD COLUMN user_id INTEGER REFERENCES user(id) ON DELETE SET NULL;
ALTER TABLE cek_bmr ADD COLUMN user_id INTEGER REFERENCES user(id) ON DELETE SET NULL;
ALTER TABLE cek_jantung ADD COLUMN user_id INTEGER REFERENCES user(id) ON DELETE SET NULL;
ALTER TABLE cek_mental ADD COLUMN user_id INTEGER REFERENCES user(id) ON DELETE SET NULL;

-- 3. Tambahkan index performa untuk riwayat dan isolasi akses pengguna
CREATE INDEX IF NOT EXISTS idx_bmi_user_date ON cek_bmi(user_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_bmr_user_date ON cek_bmr(user_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_jantung_user_date ON cek_jantung(user_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_mental_user_date ON cek_mental(user_id, created_at DESC);
```

---

## 4. Rancangan Skema Tabel Baru untuk Fitur Masa Depan

Berikut adalah spesifikasi DDL untuk seluruh target fitur baru yang dirancang modular dan teroptimasi untuk SQLite:

### 4.1 Modul Kalkulator Biometrik Tambahan

#### A. Tabel `cek_tdee` (Kalkulator TDEE Mandiri & Makronutrisi)
```sql
CREATE TABLE IF NOT EXISTS cek_tdee (
    id_tdee INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    nama_user VARCHAR(150) NOT NULL,
    jenis_kelamin VARCHAR(10) NOT NULL,
    umur INT NOT NULL,
    berat_badan REAL NOT NULL,
    tinggi_badan REAL NOT NULL,
    tingkat_aktivitas VARCHAR(50) NOT NULL,
    multiplier REAL NOT NULL,
    bmr REAL NOT NULL,
    tdee REAL NOT NULL,
    target_tujuan VARCHAR(50) NOT NULL, -- 'lose_weight', 'maintain', 'gain_weight'
    target_kalori REAL NOT NULL,
    protein_gram REAL,
    karbo_gram REAL,
    lemak_gram REAL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_tdee_user ON cek_tdee(user_id, created_at DESC);
```

#### B. Tabel `cek_whtr` (Waist-to-Height Ratio / Rasio Lingkar Pinggang ke Tinggi)
```sql
CREATE TABLE IF NOT EXISTS cek_whtr (
    id_whtr INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    nama_user VARCHAR(150) NOT NULL,
    jenis_kelamin VARCHAR(10) NOT NULL,
    lingkar_pinggang REAL NOT NULL, -- dalam cm
    tinggi_badan REAL NOT NULL,     -- dalam cm
    rasio_whtr REAL NOT NULL,       -- lingkar / tinggi
    kategori_risiko VARCHAR(100) NOT NULL, -- 'Kurang', 'Sehat/Ideal', 'Risiko Meningkat', 'Risiko Sangat Tinggi'
    saran TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_whtr_user ON cek_whtr(user_id, created_at DESC);
```

#### C. Tabel `cek_lemak_tubuh` (Estimasi Body Fat % - US Navy Method)
```sql
CREATE TABLE IF NOT EXISTS cek_lemak_tubuh (
    id_fat INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    nama_user VARCHAR(150) NOT NULL,
    jenis_kelamin VARCHAR(10) NOT NULL,
    umur INT NOT NULL,
    tinggi_badan REAL NOT NULL,
    berat_badan REAL NOT NULL,
    lingkar_leher REAL NOT NULL,
    lingkar_pinggang REAL NOT NULL,
    lingkar_pinggul REAL,            -- Wajib untuk wanita, opsional/null untuk pria
    body_fat_percent REAL NOT NULL,
    fat_mass_kg REAL,
    lean_mass_kg REAL,
    kategori VARCHAR(100) NOT NULL,  -- 'Essential Fat', 'Athletes', 'Fitness', 'Average', 'Obese'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_fat_user ON cek_lemak_tubuh(user_id, created_at DESC);
```

#### D. Tabel `cek_kebutuhan_cairan` (Water Intake Calculator)
```sql
CREATE TABLE IF NOT EXISTS cek_kebutuhan_cairan (
    id_cairan INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    nama_user VARCHAR(150) NOT NULL,
    berat_badan REAL NOT NULL,
    durasi_olahraga_menit INT DEFAULT 0,
    iklim_suhu VARCHAR(50) DEFAULT 'sedang', -- 'dingin', 'sedang', 'panas'
    kebutuhan_ml REAL NOT NULL,
    kebutuhan_gelas INT NOT NULL,
    saran TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_cairan_user ON cek_kebutuhan_cairan(user_id, created_at DESC);
```

#### E. Tabel `cek_tekanan_darah` (Pemeriksaan Tekanan Darah / Blood Pressure)
```sql
CREATE TABLE IF NOT EXISTS cek_tekanan_darah (
    id_bp INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    nama_user VARCHAR(150) NOT NULL,
    umur INT NOT NULL,
    sistolik INT NOT NULL,           -- Tekanan sistolik (mmHg)
    diastolik INT NOT NULL,          -- Tekanan diastolik (mmHg)
    pulse INT,                       -- Nadi (bpm) opsional
    kategori VARCHAR(100) NOT NULL,  -- 'Normal', 'Elevated', 'Hipertensi Tk 1', 'Hipertensi Tk 2', 'Krisis Hipertensi'
    saran TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_bp_user ON cek_tekanan_darah(user_id, created_at DESC);
```

#### F. Tabel `cek_kualitas_tidur` (Sleep Quality Assessment / Mini PSQI)
```sql
CREATE TABLE IF NOT EXISTS cek_kualitas_tidur (
    id_tidur INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    nama_user VARCHAR(150) NOT NULL,
    durasi_jam REAL NOT NULL,        -- jam tidur aktual
    waktu_mulai_tidur VARCHAR(10),   -- HH:MM
    efisiensi_tidur_persen REAL,
    skor_kualitas INT NOT NULL,      -- 0 - 21 (Skala adaptasi PSQI)
    kategori_tidur VARCHAR(100) NOT NULL, -- 'Sangat Baik', 'Cukup', 'Buruk', 'Sangat Buruk'
    catatan_gangguan TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_tidur_user ON cek_kualitas_tidur(user_id, created_at DESC);
```

#### G. Tabel `cek_target_heart_rate` (Extended THR Calculator)
```sql
CREATE TABLE IF NOT EXISTS cek_target_heart_rate (
    id_thr INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    nama_user VARCHAR(150) NOT NULL,
    umur INT NOT NULL,
    resting_hr INT NOT NULL,
    hr_max INT NOT NULL,
    hrr INT NOT NULL,
    zona_1_min INT, zona_1_max INT,
    zona_2_min INT, zona_2_max INT,
    zona_3_min INT, zona_3_max INT,
    zona_4_min INT, zona_4_max INT,
    zona_5_min INT, zona_5_max INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_thr_user ON cek_target_heart_rate(user_id, created_at DESC);
```

#### H. Tabel `cek_aktivitas_fisik` (Physical Activity Score - IPAQ Short Form)
```sql
CREATE TABLE IF NOT EXISTS cek_aktivitas_fisik (
    id_aktivitas INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    nama_user VARCHAR(150) NOT NULL,
    hari_berat INT DEFAULT 0,
    menit_berat INT DEFAULT 0,
    hari_sedang INT DEFAULT 0,
    menit_sedang INT DEFAULT 0,
    hari_jalan INT DEFAULT 0,
    menit_jalan INT DEFAULT 0,
    total_met_menit REAL NOT NULL,
    kategori_aktivitas VARCHAR(50) NOT NULL, -- 'Rendah (Sedentary)', 'Sedang', 'Tinggi'
    rekomendasi TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_akt_user ON cek_aktivitas_fisik(user_id, created_at DESC);
```

#### I. Tabel `cek_stres` (Skrining Tingkat Stres - Instrumen PSS-10)
```sql
CREATE TABLE IF NOT EXISTS cek_stres (
    id_stres INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    nama_user VARCHAR(150) NOT NULL,
    skor_pss INT NOT NULL,           -- Rentang: 0 - 40
    tingkat_stres VARCHAR(100) NOT NULL, -- 'Stres Rendah (0-13)', 'Stres Sedang (14-26)', 'Stres Tinggi (27-40)'
    saran TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_stres_user ON cek_stres(user_id, created_at DESC);
```

---

### 4.2 Modul Habit Tracker, Education & Reporting

#### J. Tabel `habit_trackers` & `habit_logs` (Health Habit Tracker)
```sql
-- Definisi Target Kebiasaan Pengguna
CREATE TABLE IF NOT EXISTS habit_trackers (
    id_habit INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    nama_habit VARCHAR(100) NOT NULL, -- contoh: 'Konsumsi Air 2L', 'Tidur 8 Jam', 'Jalan 7000 Langkah'
    kategori VARCHAR(50) NOT NULL,   -- 'cairan', 'tidur', 'olahraga', 'nutrisi', 'obat'
    target_kuantitas REAL NOT NULL,  -- misal: 2000 (ml), 8 (jam), 7000 (langkah)
    satuan VARCHAR(20) NOT NULL,      -- 'ml', 'jam', 'langkah', 'porsi'
    status_aktif INT DEFAULT 1,       -- 1: aktif, 0: diarsipkan
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);

-- Catatan Harian Kebiasaan (Log Aktual)
CREATE TABLE IF NOT EXISTS habit_logs (
    id_log INTEGER PRIMARY KEY AUTOINCREMENT,
    habit_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    tanggal DATE NOT NULL,
    capaian_kuantitas REAL NOT NULL,
    status_selesai INT DEFAULT 0,    -- 1 jika capaian >= target
    catatan TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(habit_id, tanggal),
    FOREIGN KEY (habit_id) REFERENCES habit_trackers(id_habit) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_habit_logs ON habit_logs(user_id, tanggal DESC);
```

#### K. Tabel `education_articles` & `article_categories` (Health Education Center)
```sql
CREATE TABLE IF NOT EXISTS article_categories (
    id_kategori INTEGER PRIMARY KEY AUTOINCREMENT,
    nama_kategori VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    deskripsi TEXT
);

CREATE TABLE IF NOT EXISTS education_articles (
    id_artikel INTEGER PRIMARY KEY AUTOINCREMENT,
    kategori_id INTEGER NOT NULL,
    judul VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    ringkasan TEXT NOT NULL,
    isi_konten TEXT NOT NULL,
    penulis VARCHAR(100) DEFAULT 'Tim Medis HealthCheck',
    referensi_medis TEXT,
    gambar_cover VARCHAR(255),
    views_count INT DEFAULT 0,
    status_publikasi VARCHAR(20) DEFAULT 'published', -- 'draft', 'published'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME,
    FOREIGN KEY (kategori_id) REFERENCES article_categories(id_kategori)
);
CREATE INDEX IF NOT EXISTS idx_art_slug ON education_articles(slug);
CREATE INDEX IF NOT EXISTS idx_art_kat ON education_articles(kategori_id, created_at DESC);
```

#### L. Tabel `health_reports` (Arsip Pembuatan Laporan PDF)
```sql
CREATE TABLE IF NOT EXISTS health_reports (
    id_report INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    nomor_laporan VARCHAR(50) NOT NULL UNIQUE, -- misal: HC-REP-202611-0001
    periode_awal DATE NOT NULL,
    periode_akhir DATE NOT NULL,
    ringkasan_snapshot TEXT, -- JSON ringkasan skor BMI, BP, Kebugaran saat dicetak
    file_path VARCHAR(255),  -- lokasi file PDF statis jika diarsipkan fisik
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_report_user ON health_reports(user_id, created_at DESC);
```

---

## 5. Master Timeline & Unified History View

Untuk menyediakan tampilan riwayat terintegrasi (*Universal History Timeline*) tanpa melakukan *UNION* berat di seluruh tabel secara simultan pada aplikasi berskala besar, kita dapat membuat SQL View di SQLite:

```sql
CREATE VIEW IF NOT EXISTS v_all_health_assessments AS
SELECT 'bmi' AS tipe_alat, 'Indeks Massa Tubuh' AS label_alat, id_bmi AS record_id, user_id, nama_user, 
       kondisi AS kesimpulan, bmi AS skor_utama, 'kg/m²' AS satuan, created_at 
FROM cek_bmi
UNION ALL
SELECT 'bmr' AS tipe_alat, 'Kebutuhan Kalori (BMR/TDEE)' AS label_alat, id_bmr AS record_id, user_id, nama_user, 
       aktivitas AS kesimpulan, tdee AS skor_utama, 'kkal' AS satuan, created_at 
FROM cek_bmr
UNION ALL
SELECT 'jantung' AS tipe_alat, 'Detak Jantung & Kardio' AS label_alat, id_jantung AS record_id, user_id, nama_user, 
       kategori AS kesimpulan, resting_hr AS skor_utama, 'BPM' AS satuan, created_at 
FROM cek_jantung
UNION ALL
SELECT 'mental' AS tipe_alat, 'Skrining Mental (PHQ-4)' AS label_alat, id_mental AS record_id, user_id, nama_user, 
       tingkat AS kesimpulan, total_skor AS skor_utama, '/12' AS satuan, created_at 
FROM cek_mental
UNION ALL
SELECT 'tekanan_darah' AS tipe_alat, 'Tekanan Darah' AS label_alat, id_bp AS record_id, user_id, nama_user, 
       kategori AS kesimpulan, sistolik || '/' || diastolik AS skor_utama, 'mmHg' AS satuan, created_at 
FROM cek_tekanan_darah
UNION ALL
SELECT 'tidur' AS tipe_alat, 'Kualitas Tidur' AS label_alat, id_tidur AS record_id, user_id, nama_user, 
       kategori_tidur AS kesimpulan, skor_kualitas AS skor_utama, 'skor' AS satuan, created_at 
FROM cek_kualitas_tidur
UNION ALL
SELECT 'stres' AS tipe_alat, 'Tingkat Stres (PSS)' AS label_alat, id_stres AS record_id, user_id, nama_user, 
       tingkat_stres AS kesimpulan, skor_pss AS skor_utama, '/40' AS satuan, created_at 
FROM cek_stres;
```

---

## 6. Strategi Eksekusi Migrasi Aman

1. **Prinsip Idempoten:** Seluruh perintah DDL dijalankan menggunakan klausa `IF NOT EXISTS` dan `ALTER TABLE` terenkapsulasi dalam blok penanganan eksepsi `try-catch` SQLite.
2. **Tanpa Hapus Data (Zero Data Loss):** Nilai `user_id` pada tabel eksisting berstatus `NULLABLE`. Data lama tetap utuh dan dapat diakses administrator seperti biasa.
3. **Pemberian Indeks Terarah:** Indeks komposit `(user_id, created_at DESC)` memastikan query personal dashboard memfilter hanya data milik akun yang sedang terotentikasi dalam orde logaritmik $O(\log n)$.
