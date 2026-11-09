# Laporan Teknis Implementasi Phase 3: Personal Health Dashboard, Integrated History & Health Report

Tanggal: 8 November 2026  
Status: Selesai & Terverifikasi (65/65 Pengujian Otomatis Lulus 100%)  
Platform: Native PHP 8.x, SQLite 3, Bootstrap 5.3.3, Chart.js, FPDF 1.86  

---

## 1. Executive Summary

Phase 3 menyelesaikan transformasi HealthCheck dari kumpulan kalkulator terisolasi menjadi ekosistem manajemen kesehatan personal terpadu (*Personal Health Record & Education Ecosystem*). 

Poin-poin utama yang diselesaikan pada fase ini mencakup:
1. **Personal Health Dashboard (`user/dashboard.php`):** Dashboard analitik biometrik berbasis penilaian individual per kategori tanpa skor gabungan sintetis (*Zero Arbitrary Health Score Index*), dilengkapi kartu indikator biometrik terkini, delta tren perbandingan hasil sebelumnya, grafik tren dinamis, dan feed aktivitas pemeriksaan terbaru.
2. **Riwayat Pemeriksaan Terpadu (`user/history.php`):** Agregasi data dari seluruh 12 tabel pemeriksaan dengan filter kategori medis, filter rentang tanggal, filter jenis alat spesifik, pencarian cerdas berbasis kata kunci sinonim, pengurutan kronologis, dan pagination.
3. **Detail Snapshot Pemeriksaan (`user/history_detail.php`):** Menampilkan seluruh parameter masukan dan luaran saat evaluasi dilakukan tanpa melakukan re-kalkulasi dari profil baru, menjamin integritas rekam medis historis.
4. **Health Trends & Visualization:** Grafik tren waktu nyata (*real data only*) menggunakan Chart.js untuk Indeks Massa Tubuh (BMI), Tekanan Darah (Sistolik & Diastolik), dan Detak Jantung Istirahat.
5. **Health Report PDF Generator (`user/report_download.php`):** Modul ekspor laporan kesehatan terstandar A4 menggunakan pustaka murni PHP FPDF (tanpa framework eksternal), dengan opsi filter periode (30 hari, 90 hari, seluruh riwayat, atau kustom), identitas pengguna terverifikasi, dan disclaimer medis legal.
6. **Pusat Edukasi Kesehatan Berbasis Bukti (`education/index.php` & `education/detail.php`):** Modul literasi kesehatan dengan 5 kategori artikel berbasis referensi resmi (WHO, AHA, PERHI, PubMed) dan tautan kontekstual ke alat kalkulator terkait.
7. **Pemberian Catatan Medis Khusus (AHA/ACC 180/120 & Sleep Habit):** Penyesuaian protokol krisis hipertensi (membedakan urgensi tanpa gejala vs emergensi dengan gejala) serta penegasan bahwa instrumen tidur merupakan *Sleep Habit Assessment* (indikator kebiasaan mandiri, bukan diagnosis klinis).

Seluruh 65 skenario pengujian baru pada `tests/tests_phase3_runner.php` dan seluruh 147 skenario pengujian kumulatif sebelumnya (total 212 skenario pengujian) lulus 100% tanpa regresi.

---

## 2. Audit Struktur dan UI Sebelum Implementasi

Sebelum implementasi Phase 3 dimulai, dilakukan audit terhadap:
- **Tabel Basis Data:** Sebanyak 12 tabel pemeriksaan biometrik mandiri telah aktif dengan kolom `user_id` nullable dan relasi foreign key ke tabel `user(id)`.
- **Integritas Gaya Visual:** UI mengadopsi editorial modern dengan tipografi Plus Jakarta Sans, palet warna Medical Navy (`#19365B`), Strong Blue (`#347FC2`), Rich Teal (`#168C89`), Emerald (`#27845E`), Coral (`#D75F50`), dan Deep Lavender (`#7866A6`), radius kartu 20px, dan tombol solid/soft-filled 11–16px.
- **Pola Navigasi:** Pengguna terautentikasi sebelumnya hanya memiliki menu logout di navbar dan belum memiliki hub personal untuk memantau ringkasan hasil biometrik miliknya sendiri.

---

## 3. Arsitektur Dashboard (`user/dashboard.php`)

Dashboard dirancang dengan mematuhi prinsip kehati-hatian klinis:
- **Penolakan Health Score Index 0–100:** Berdasarkan kaidah medis, parameter seperti rasio WHtR, tekanan darah, durasi tidur, dan skor depresi PHQ-4 memiliki domain fisiologis dan psikometrik yang berbeda secara fundamental. Menggabungkannya ke dalam satu angka 0–100 tanpa formula pembobotan multisenter tervalidasi merupakan pseudosains. Oleh sebab itu, dashboard menampilkan evaluasi terpisah per indikator (*individual clinical assessment*).
- **Struktur Halaman:**
  - *Header Pengguna:* Menampilkan sapaan personal, nama lengkap akun, usia (dihitung dinamis dari tanggal lahir profil), dan bilah tombol cepat (*Quick Action Bar*).
  - *Ringkasan Indikator Biometrik (12 Alat):* Dikelompokkan ke dalam kategori Gizi & Tubuh, Kardiovaskular, Pola Tidur, dan Kesehatan Mental. Kartu menampilkan nilai metrik utama, label status, tanggal evaluasi, serta delta perbandingan dengan pengukuran sebelumnya (misal: `+0.6 kg/m²` atau `Stabil`). Jika belum ada data, disajikan *empty state* bersih dengan tombol "Mulai Pemeriksaan".
  - *Grafik Tren Perkembangan:* Area visualisasi data riil pengguna.
  - *Log Aktivitas Pemeriksaan Terbaru:* Tabel ringkas berisi 5 rekaman terakhir dengan tombol tautan rincian snapshot.

---

## 4. Integrasi 12 Jenis Pemeriksaan

Seluruh 12 tabel pemeriksaan diintegrasikan melalui helper terpusat `includes/history_helper.php`:

| No | Jenis Alat | Tabel Database | Primary Key | Kategori Medis | Metrik Utama yang Dihasilkan |
|---|---|---|---|---|---|
| 1 | Indeks Massa Tubuh (BMI) | `cek_bmi` | `id_bmi` | Gizi & Tubuh | Nilai BMI (kg/m²) & Kategori Asia |
| 2 | Kebutuhan Kalori (BMR/TDEE) | `cek_bmr` | `id_bmr` | Gizi & Tubuh | Total TDEE (kkal) & Distribusi Makro |
| 3 | Rasio Pinggang ke Tinggi (WHtR) | `cek_whtr` | `id_whtr` | Gizi & Tubuh | Rasio WHtR & Risiko Kardiometabolik |
| 4 | Persentase Lemak (US Navy) | `cek_lemak_tubuh` | `id_fat` | Gizi & Tubuh | Estimasi Fat % & Massa Bebas Lemak |
| 5 | Kebutuhan Cairan Harian | `cek_kebutuhan_cairan` | `id_cairan` | Gizi & Tubuh | Liter/hari & Porsi Gelas Standar |
| 6 | Tekanan Darah (Tensi) | `cek_tekanan_darah` | `id_bp` | Kardiovaskular | Sistolik/Diastolik mmHg & Tekanan Nadi |
| 7 | Detak Nadi Istirahat (RHR) | `cek_jantung` | `id_jantung` | Kardiovaskular | RHR (BPM) & Kebugaran Nadi |
| 8 | Zona Latihan Detak Jantung | `cek_zona_detak_jantung` | `id_zona` | Kardiovaskular | 5 Zona Denyut Jantung (Karvonen/Zoladz) |
| 9 | Pola & Higiene Tidur | `cek_kualitas_tidur` | `id_tidur` | Pola Istirahat | Efisiensi Tidur (%) & Indikator Kebiasaan |
| 10 | Aktivitas Fisik (IPAQ) | `cek_aktivitas_fisik` | `id_aktivitas` | Gizi / Kebugaran | Total Skor MET-menit/minggu & Tingkat |
| 11 | Penilaian Stres (PSS-10) | `cek_stres` | `id_stres` | Kesehatan Mental | Skor Persepsi Stres (0–40) & Tingkat |
| 12 | Skrining Mood (PHQ-4) | `cek_mental` | `id_mental` | Kesehatan Mental | Total Skor PHQ-4 (0–12) & Tingkat |

---

## 5. Implementasi Riwayat (`user/history.php` & `user/history_detail.php`)

### A. Riwayat Terpadu (`user/history.php`)
- **Agregasi Multi-tabel:** Menjalankan query terarah pada ke-12 tabel yang memfilter `WHERE user_id = :session_user_id`.
- **Penyaringan Lanjutan:**
  - Berdasarkan kategori medis (`all`, `gizi`, `kardio`, `tidur`, `mental`).
  - Berdasarkan jenis alat spesifik (`type`).
  - Rentang tanggal (`date_from` dan `date_to`).
  - Pencarian cerdas menggunakan sinonim kata kunci (contoh: pencarian kata "tensi" otomatis mencakup rekaman "Pemeriksaan Tekanan Darah").
  - Pengurutan kronologis (`DESC` atau `ASC`).
  - Pagination dinamis (10 rekaman per halaman).
- **Proteksi Akses:** Memastikan pengguna tamu dialihkan ke halaman login.

### B. Detail Snapshot Pemeriksaan (`user/history_detail.php`)
- **Proteksi IDOR:** Memverifikasi kepemilikan melalui `assert_record_access()`. Upaya pengguna lain membuka rekam data milik akun berbeda akan diblokir dengan HTTP 403 Forbidden.
- **Integritas Historis:** Seluruh nilai parameter masukan (berat, tinggi, umur, lingkar pinggang, jawaban kuesioner) disajikan murni dari snapshot tabel pada saat pemeriksaan dilakukan. Modifikasi data pada profil akun pengguna di kemudian hari tidak mengubah nilai historis.

---

## 6. Implementasi Health Trends & Visualization

Modul visualisasi data tren (`get_user_health_trends()`) dibangun dengan ketentuan ketat:
- **Zero Dummy Data:** Hanya mengekstraksi data riil dari database milik pengguna yang bersangkutan dengan urutan kronologis maju (`ORDER BY created_at ASC`).
- **Penanganan Ambang Batas:** Grafik garis Chart.js hanya dirender jika terdapat minimal 2 titik rekaman ($\ge 2$). Jika titik data kurang dari 2, ditampilkan pesan empty state informatif yang mengajak pengguna melakukan pemeriksaan lanjutan.
- **Pemisahan Skala:** Grafik BMI (kg/m²) dan grafik Tekanan Darah (mmHg) dipisahkan ke dalam kanvas berbeda untuk mencegah distorsi interpretasi data akibat perbedaan satuan pengukuran.

---

## 7. Implementasi Health Report PDF (`user/report_download.php`)

Fitur ekspor PDF menggunakan library resmi FPDF 1.86 yang ditempatkan secara mandiri pada `includes/fpdf/`:
- **Kompatibilitas Native PHP:** Beroperasi tanpa node.js, headless browser, maupun framework berat.
- **Tata Letak Bersih & Terstruktur:**
  - Header resmi dengan latar belakang Medical Navy (`#19365B`) dan tipografi Helvetica.
  - Kartu identitas pengguna (nama, usia, jenis kelamin, tinggi, berat badan, periode laporan, dan stempel waktu cetak).
  - Bagian A: Tabel ringkasan status biometrik terkini dari seluruh alat yang pernah dilakukan.
  - Bagian B: Tabel log riwayat pemeriksaan kronologis dalam periode yang dipilih.
  - Bagian C: Lembar disclaimer medis legal yang menegaskan bahwa dokumen ini bukan pengganti rekam medis rumah sakit.
  - Footer dengan penomoran halaman otomatis (`Page X of Y`).
- **Filter Periode:** Mendukung pilihan 30 Hari Terakhir, 90 Hari Terakhir, Seluruh Riwayat, atau Rentang Tanggal Khusus.
- **Keamanan:** Memverifikasi sesi login server, mencegah akses pengguna lain, dan mengirimkan header HTTP `Content-Type: application/pdf` serta `Content-Disposition: attachment`.

---

## 8. Implementasi Health Education Center (`education/`)

Pusat edukasi dibangun untuk mendampingi pengguna memahami hasil biometrik secara ilmiah:
- **Basis Data Artikel:** Tabel `article_categories` dan `education_articles` dimigrasikan dan di-seed dengan artikel berbobot ilmiah.
- **5 Kategori Edukasi:**
  1. *Gizi & Komposisi Tubuh:* Membahas bahaya lemak viseral dan rasio WHtR (Ashwell & Gibson, BMJ Open).
  2. *Jantung & Kardiovaskular:* Panduan pengukuran tensi mandiri dan pencegahan efek jas putih (Konsensus PERHI 2021).
  3. *Pola & Higiene Tidur:* Edukasi ritme sirkadian dan protokol hygiene tidur (Sleep Health Journal & Walker, 2017).
  4. *Kesehatan Mental & Stres:* Perbedaan stres adaptif vs distres kronis serta rujukan hotline Sejiwa 119 ext. 8.
  5. *Aktivitas & Kebugaran:* Rekomendasi 150 menit aktivitas WHO dan pencegahan bahaya "active couch potato".
- **Fitur Interaktif:**
  - Pencarian artikel instan dan filter kategori.
  - Penghitung tayangan artikel (`views_count + 1`).
  - Widget tautan kontekstual ke kalkulator terkait (misal: artikel lemak viseral langsung menyediakan tombol menuju kalkulator WHtR).
  - Daftar pustaka rujukan klinis yang dapat diverifikasi secara publik.

---

## 9. Database Changes & Migration

Migrasi Phase 3 (`database/migrations/20261108_002_phase3_dashboard_reporting_education.php`) menambahkan tabel berikut:
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
    penulis VARCHAR(100) DEFAULT 'Tim Edukasi Medis HealthCheck',
    referensi_medis TEXT,
    terkait_alat VARCHAR(50),
    gambar_cover VARCHAR(255),
    waktu_baca_menit INT DEFAULT 4,
    views_count INT DEFAULT 0,
    status_publikasi VARCHAR(20) DEFAULT 'published',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES article_categories(id_kategori) ON DELETE CASCADE
);
```
Migrasi ini bersifat 100% idempoten dan mendukung rollback tanpa merusak data lama.

---

## 10. Security & Authorization

Prinsip keamanan berlapis dipertahankan secara ketat:
1. **Pencegahan Akses Tak Terotentikasi:** Halaman `user/dashboard.php`, `user/history.php`, `user/history_detail.php`, dan `user/report_download.php` memverifikasi sesi login secara ketat. Tamu dialihkan dengan kode HTTP 302 ke `auth/login.php?pesan=belum_login`.
2. **Perlindungan IDOR (Insecure Direct Object References):** Fungsi `assert_record_access()` memverifikasi bahwa `record.user_id === session.user_id`. Pengguna A yang mencoba mengakses rekaman Pengguna B via URL manipulation langsung ditolak dengan status HTTP 403 Forbidden.
3. **Pemberian Wewenang Administrator:** Akun dengan peran `admin` memiliki hak akses audit sah untuk memeriksa rekam data pengguna.
4. **Perlindungan SQL Injection:** Seluruh parameter pencarian, penyaringan tanggal, dan penarikan ID menggunakan PDO Prepared Statements dengan parameter binding eksplisit.

---

## 11. UI/UX dan Responsiveness

- Seluruh komponen baru pada direktori `user/` dan `education/` memanfaatkan token CSS dan kelas utilitas yang sudah ada di `assets/css/style.css` (kartu 20px, border halus, bayangan elevasi rendah, tombol radius 11–16px, dan tipografi Plus Jakarta Sans).
- Navigasi navbar diperbarui secara terintegrasi: pengguna login memiliki dropdown personal berisi tautan Dashboard, Riwayat Terpadu, dan Unduh Laporan PDF.
- Seluruh antarmuka telah teruji responsif pada resolusi desktop, tablet, dan smartphone mobile.

---

## 12. Daftar File Baru dan Dimodifikasi

### Berkas Baru:
1. `user/dashboard.php`: Halaman antarmuka Personal Health Dashboard.
2. `user/history.php`: Halaman antarmuka Riwayat Pemeriksaan Terpadu.
3. `user/history_detail.php`: Halaman rincian snapshot pemeriksaan per alat.
4. `user/report_download.php`: Modul generator dan pengunduhan Health Report PDF.
5. `education/index.php`: Katalog artikel pusat edukasi kesehatan.
6. `education/detail.php`: Halaman pembaca artikel edukasi dengan sitasi klinis.
7. `includes/history_helper.php`: Helper agregasi data multi-tabel, tren, dan detail snapshot.
8. `includes/fpdf/`: Pustaka FPDF 1.86 untuk pembuatan PDF mandiri.
9. `database/migrations/20261108_002_phase3_dashboard_reporting_education.php`: Migrasi skema edukasi.
10. `tests/tests_phase3_runner.php`: Runner pengujian otomatis Phase 3 (65 skenario uji).
11. `docs/phase_3_dashboard_reporting_report.md`: Laporan komprehensif implementasi Phase 3.

### Berkas Dimodifikasi:
1. `includes/tensi_helper.php`: Penyempurnaan pesan darurat krisis hipertensi (membedakan urgensi tanpa gejala vs emergensi dengan gejala).
2. `includes/header.php`: Penambahan navigasi dropdown personal (Dashboard, Riwayat, Laporan PDF) dan tautan Edukasi.
3. `includes/footer.php`: Penambahan tautan navigasi akun personal dan Pusat Edukasi Kesehatan.

---

## 13. Matriks Pengujian

Pengujian dilakukan melalui automated test runner `tests/tests_phase3_runner.php` yang menjalankan server terisolasi pada port 8098:

| Domain Pengujian | Jumlah Skenario | Hasil | Keterangan |
|---|---|---|---|
| A. Personal Health Dashboard | 10 | 10 PASS (100%) | Otorisasi member, empty state, pencegahan arbitrary 0-100 score, resolusi data terbaru, delta tren, feed aktivitas |
| B. Riwayat Pemeriksaan Terpadu | 12 | 12 PASS (100%) | Integrasi 12 alat, filter kategori gizi/kardio/mental, filter tanggal, pencarian sinonim, sorting, pagination, isolasi user |
| C. Detail Snapshot Pemeriksaan | 8 | 8 PASS (100%) | Akses snapshot, parameter utuh, kebal perubahan profil, blokir IDOR 403, izin admin, 404 invalid parameter |
| D. Visualisasi Tren Kesehatan | 6 | 6 PASS (100%) | Urutan kronologis ASC, zero dummy data, empty state <2 titik, rendering canvas Chart.js, pemisahan satuan |
| E. Health Report PDF Generator | 9 | 9 PASS (100%) | Download binary application/pdf, signature %PDF, filter 30d/90d/kustom, isolasi dokumen lintas akun |
| F. Pusat Edukasi Kesehatan | 9 | 9 PASS (100%) | Akses publik, 5 kategori topik, pencarian q=, detail slug, penambahan views_count, widget alat terkait, sitasi medis, 404 bad slug |
| G. Keamanan & Regresi Sistem | 11 | 11 PASS (100%) | CSRF protection, pencegahan GET hapus (405), ketahanan SQL injection, regresi 12 alat, homepage 200 OK, idempotensi migrasi, whitelist entitas, sintaks 104 file PHP |
| **Total Pengujian Phase 3** | **65** | **65 PASS (100%)** | **Seluruh skenario pengujian lulus tanpa kegagalan** |

### Kumulatif Test Suite Seluruh Fase:
- Phase 1A (Security Hardening): 12/12 PASS
- Phase 1B (User Profile & Database Relations): 14/14 PASS
- Phase 2A (BMR Bugfix & Profile Autofill): 30/30 PASS
- Phase 2B (Body & Nutrition Calculators): 36/36 PASS
- Phase 2C (Cardio, Sleep & Mental Screening): 55/55 PASS
- Phase 3 (Dashboard, History, PDF & Education): 65/65 PASS
- **Total Pengujian Keseluruhan Repositori: 212 / 212 PASS (100%)**

---

## 14. Hasil PHP Lint

Verifikasi sintaks dijalankan menggunakan `php -l` pada seluruh 104 berkas PHP di dalam repositori (di luar direktori `backups/`).  
**Hasil:** `Semua 104 file PHP lulus sintaks tanpa error (php -l)`.

---

## 15. Hasil Visual & UX Regression

- Antarmuka halaman publik (`index.php`), formulir kalkulator lama (BMI, BMR, Jantung, Mental, WHtR, Lemak Tubuh, Cairan), dan modul Phase 2C (Tekanan Darah, Tidur, Zona Jantung, Aktivitas Fisik, Stres) tetap beroperasi secara konsisten tanpa ada pergeseran tata letak maupun style breakage.
- Dashboard dan Riwayat terintegrasi secara mulus ke dalam navbar desktop dan mobile.

---

## 16. Known Limitations

1. **Format PDF Standard Type-1 Fonts:** Pustaka FPDF bawaan menggunakan standar font bawaan PDF (Helvetica). Teks dengan karakter non-Latin (misal emoji atau simbol khusus) disaring secara otomatis untuk mencegah malformasi berkas PDF.
2. **Chart.js Dependency:** Visualisasi grafik pada Dashboard mengandalkan skrip Chart.js yang dimuat via CDN berkecepatan tinggi. Jika perangkat pengguna offline, data tetap disajikan dalam format tabel dan kartu indikator biometrik tanpa hambatan fungsional.

---

## 17. Rekomendasi Tahap Berikutnya (Roadmap Phase 4)

1. **Health Habits Tracker & Daily Check-in:** Fitur pelacak kebiasaan harian (konsumsi air harian, jam tidur, dan langkah kaki) dengan rekap mingguan.
2. **Notification & Health Reminder:** Pengingat jadwal evaluasi berkala (misal tensi tiap minggu, evaluasi stres tiap bulan).
3. **Multi-profile Family Account:** Fitur pendampingan kesehatan keluarga di mana satu akun induk dapat mencatatkan rekam kesehatan bagi anggota keluarga (anak/orang tua).
