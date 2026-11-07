# Roadmap Pengembangan Platform HealthCheck

**Status Dokumen:** Master Implementation & Development Roadmap  
**Target Transformasi:** Mengembangkan HealthCheck dari situs kalkulator sederhana menjadi *Personal Health Assessment Platform* modern dan aman.  
**Metodologi:** Iteratif, Berbasis Prioritas Risiko (Risk-First), dan Bertahap (*Phased Rollout*).

---

## 1. Ikhtisar Strategi Implementasi

Pengembangan dibagi ke dalam **6 Fase Terstruktur**. Setiap fase memiliki prasyarat (*prerequisites*), keluaran konkret (*deliverables*), pengujian kualitas (*QA criteria*), dan mitigasi risiko khusus.

```
[ Fase 1: Fondasi Keamanan & Skema Database ] ── (P0 & Fondasi)
                     │
                     ▼
[ Fase 2: Standardisasi Modul Eksisting & Profil Pengguna ]
                     │
                     ▼
[ Fase 3: Pembangunan Klaster Kalkulator Baru (Batch 1: Biometrik Fisik) ]
                     │
                     ▼
[ Fase 4: Pembangunan Klaster Kalkulator Baru (Batch 2: Gaya Hidup & Mental) ]
                     │
                     ▼
[ Fase 5: Personal Health Dashboard, Riwayat & Laporan PDF ]
                     │
                     ▼
[ Fase 6: Health Habit Tracker & Health Education Center ]
```

---

## 2. Rincian Fase Pengembangan

### FASE 1: Fondasi Keamanan, Hardening Sesi & Migrasi Skema Basis Data
**Prioritas:** P0 (Kritis)  
**Estimasi Durasi:** Sprint 1 (1–2 Minggu)  
**Tujuan:** Menutup celah keamanan kritis, mengamankan autentikasi, dan menyiapkan skema database untuk relasi data pengguna tanpa menghilangkan data lama.

* **Aktivitas Utama:**
  1. **Penutupan Celah Keamanan Kritis:**
     * Menghapus hardcoded backdoor `admin`/`admin123` di `auth/login_admin_proses.php`.
     * Mengamankan seed user di `includes/db.php` dengan hashing BCRYPT.
     * Menerapkan `session_regenerate_id(true)` saat login berhasil.
     * Mengonfigurasi flag cookie sesi (`HttpOnly`, `SameSite=Lax`, `Secure`).
  2. **Implementasi Proteksi CSRF:**
     * Membangun helper `includes/csrf.php` (fungsi `csrf_token()` dan `verify_csrf_token()`).
     * Menyematkan token CSRF ke seluruh formulir POST dan mengubah tautan hapus GET menjadi POST form.
  3. **Proteksi Direktori Basis Data:**
     * Menempatkan berkas `.htaccess` di dalam folder `database/` untuk menolak akses langsung via HTTP.
  4. **Eksekusi Migrasi Non-Destructive Skema SQLite:**
     * Menambahkan kolom `user_id` pada tabel `cek_bmi`, `cek_bmr`, `cek_jantung`, `cek_mental`.
     * Menambahkan kolom data profil pada tabel `user` (`nama_lengkap`, `email`, `tanggal_lahir`, `jenis_kelamin`, `tinggi_badan`, `berat_badan`).
     * Menambahkan indeks performa `(user_id, created_at DESC)`.
* **Kriteria Keberhasilan (Definition of Done):**
  * Tidak ada kredensial hardcoded pada kode program.
  * Percobaan manipulasi CSRF ditolak oleh sistem.
  * Berkas `healthcheck.sqlite` tidak dapat diunduh langsung lewat peramban.
  * Basis data lama tetap utuh dan fungsi aplikasi saat ini berjalan normal.

---

### FASE 2: Standardisasi Modul Eksisting & Manajemen Profil Pengguna
**Prioritas:** P1 (Tinggi)  
**Estimasi Durasi:** Sprint 2 (1–2 Minggu)  
**Tujuan:** Memperbaiki bug pada 4 kalkulator yang sudah ada, mengaitkan hasil ke akun jika user login, dan membangun fitur manajemen profil dasar.

* **Aktivitas Utama:**
  1. **Perbaikan Bug Modul Eksisting:**
     * Memperbaiki `bmr/bmr_update.php` dan `bmr_edit.php` agar faktor aktivitas tidak tertimpa nilai statis 1.55.
     * Menyelaraskan kriteria klasifikasi BMI Asia-Pasifik pada halaman fallback `bmi_hasil.php`.
     * Memastikan input `jenis_kelamin` pada form BMI dimanfaatkan atau diselaraskan dengan profil pengguna.
     * Menambahkan tombol hapus rekaman detak jantung dan mental di dasbor admin untuk simetri CRUD.
  2. **Smart Storage (Logika Simpan Tamu vs Member):**
     * Memperbarui `bmi_proses.php`, `bmr_proses.php`, `jantung_proses.php`, dan `mental_proses.php`:
       * Jika `$_SESSION['user_id']` aktif $\to$ simpan ke DB dengan ID akun.
       * Jika tamu $\to$ simpan ke sesi saja tanpa mengotori DB publik.
  3. **Halaman Profil Pengguna (`user/profile.php`):**
     * Form edit profil biometrik dasar (usia, jenis kelamin, tinggi, berat badan awal).
* **Kriteria Keberhasilan:**
  * Pengguna yang login otomatis tersimpan riwayatnya dengan ID akun masing-masing.
  * Tamu tetap dapat menghitung bebas tanpa paksaan login.
  * Edit data BMR di admin mempertahankan faktor aktivitas fisik asli.

---

### FASE 3: Klaster Kalkulator Baru — Batch 1 (Biometrik Fisik & Nutrisi)
**Prioritas:** P1 - P2  
**Estimasi Durasi:** Sprint 3 (2 Minggu)  
**Tujuan:** Mengembangkan kalkulator biometrik lanjutan berbasis rumus klinis terstandar.

* **Fitur yang Dibangun:**
  1. **Kalkulator TDEE & Makronutrisi Mandiri (`tools/tdee/`):**
     * Integrasi kalkulasi Mifflin-St Jeor, target defisit/surplus, dan distribusi gram makronutrisi.
  2. **Kalkulator WHtR (Waist-to-Height Ratio) (`tools/whtr/`):**
     * Rumus rasio lingkar pinggang terhadap tinggi, visualisasi risiko kardiometabolik Ashwell & Gibson.
  3. **Kalkulator Estimasi Lemak Tubuh US Navy (`tools/body-fat/`):**
     * Rumus logaritmik Hodgdon & Beckett (lingkar leher, pinggang, pinggul wanita).
  4. **Kalkulator Kebutuhan Cairan Harian (`tools/cairan/`):**
     * Rumus asupan air dasar + kompensasi olahraga ACSM + faktor iklim tropis.
* **Aktivitas Teknis:**
  * Membangun Service PHP: `TdeeService.php`, `WhtrService.php`, `BodyFatService.php`, `HydrationService.php`.
  * Membuat tabel DDL terkait (`cek_tdee`, `cek_whtr`, `cek_lemak_tubuh`, `cek_kebutuhan_cairan`).
  * Menerapkan UI konsisten sesuai Design System HealthCheck.
* **Kriteria Keberhasilan:**
  * Keempat kalkulator berfungsi presisi pada rentang nilai valid.
  * Hasil perhitungan menyertakan batasan klinis dan edukasi yang jelas.

---

### FASE 4: Klaster Kalkulator Baru — Batch 2 (Gaya Hidup, Kardiovaskular & Kesehatan Mental)
**Prioritas:** P2  
**Estimasi Durasi:** Sprint 4 (2 Minggu)  
**Tujuan:** Melengkapi penapisan kesehatan holistik mencakup kardiovaskular, pola tidur, kebugaran fisik, dan tingkat stres.

* **Fitur yang Dibangun:**
  1. **Pemeriksaan Tekanan Darah (`tools/tensi/`):**
     * Klasifikasi AHA/ACC 2017 & Perhiper (Hipotensi, Normal, Elevated, Hipertensi 1, Hipertensi 2, Krisis).
     * Peringatan Red Flag kegawatdaruratan.
  2. **Kalkulator & Skrining Kualitas Tidur (`tools/tidur/`):**
     * Adaptasi instrumen PSQI ringkas, kalkulasi efisiensi tidur (%), dan saran higiene tidur.
  3. **Kalkulator Target Detak Jantung Lanjutan (`tools/target-hr/`):**
     * 5 Zona Karvonen & Zoladz untuk optimalisasi olahraga dan pembakaran lemak.
  4. **Kalkulator Aktivitas Fisik IPAQ (`tools/aktivitas/`):**
     * Kalkulasi MET-menit/minggu standar WHO.
  5. **Skrining Tingkat Stres PSS-10 (`tools/stres-pss/`):**
     * Kuesioner 10 butir dengan pembalikan skor (*reverse items*) terstandar Cohen.
* **Aktivitas Teknis:**
  * Membangun Service PHP: `BloodPressureService.php`, `SleepService.php`, `IpaqService.php`, `StressService.php`.
  * Membuat tabel DDL terkait (`cek_tekanan_darah`, `cek_kualitas_tidur`, `cek_target_heart_rate`, `cek_aktivitas_fisik`, `cek_stres`).
* **Kriteria Keberhasilan:**
  * Seluruh kalkulator tervalidasi dengan kuesioner dan standar baku internasional.
  * Tautan rujukan darurat (Layanan Sejiwa Kemenkes 119) terpasang di skrining stres dan mental.

---

### FASE 5: Personal Health Dashboard, Riwayat Terpadu & Ekspor Laporan PDF
**Prioritas:** P1 (Penting untuk Pengalaman Pengguna Member)  
**Estimasi Durasi:** Sprint 5 (2 Minggu)  
**Tujuan:** Mengintegrasikan seluruh data kesehatan pengguna ke dalam satu dasbor analitik personal yang kaya wawasan dan dapat dicetak sebagai laporan formal.

* **Aktivitas Utama:**
  1. **Personal Health Dashboard (`user/dashboard.php`):**
     * Widget skor kesehatan holistik terbobot.
     * Ringkasan parameter biometrik terbaru (BMI, Tensi, Tidur, Kalori).
     * Grafik tren visual interaktif (riwayat berat badan dan tensi darah 30–90 hari terakhir).
  2. **Riwayat Pemeriksaan Terpadu (`user/history.php`):**
     * Pemanfaatan View `v_all_health_assessments`.
     * Filter berdasarkan tipe alat kesehatan dan rentang waktu.
     * Fasilitas penghapusan rekaman data mandiri (*data ownership*).
  3. **Laporan Kesehatan PDF (`user/report_download.php`):**
     * Penyusunan template cetak profesional berbasis CSS Print / FPDF.
     * Memuat identitas pengguna, grafik tren, rangkuman biometrik, lembar persiapan konsultasi dokter, dan klausul disclaimer medis formal.
* **Kriteria Keberhasilan:**
  * Pengguna hanya dapat melihat dan mengekspor datanya sendiri (terlindungi dari IDOR).
  * Dokumen PDF dicetak bersih, terbaca rapi, dan memiliki nomor identifikasi laporan.

---

### FASE 6: Health Habit Tracker & Health Education Center
**Prioritas:** P2 - P3  
**Estimasi Durasi:** Sprint 6 (2 Minggu)  
**Tujuan:** Meningkatkan retensi harian pengguna melalui pelacakan kebiasaan sehat (*habit tracker*) dan pusat literasi edukasi kesehatan.

* **Aktivitas Utama:**
  1. **Health Habit Tracker (`user/habits.php`):**
     * Pencatatan harian: air minum, jam tidur, aktivitas olahraga, buah/sayur, vitamin.
     * Penghitungan streak konsistensi dan kalender heatmap harian.
  2. **Health Education Center (`education/`):**
     * Katalog artikel terbagi ke dalam 5 kategori medis.
     * Pencarian artikel dan estimasi waktu baca.
     * Penyematan widget kalkulator interaktif di akhir setiap artikel (*contextual cross-selling*).
     * Modul CMS mini di panel admin untuk manajemen artikel.
* **Kriteria Keberhasilan:**
  * Pengguna dapat melakukan check-in harian dalam < 10 detik.
  * Seluruh artikel edukasi memuat referensi jurnal ilmiah medis yang sahih.

---

## 3. Rencana Manajemen Risiko & Mitigasi

| Risiko Potensial | Dampak | Probabilitas | Rencana Mitigasi |
| :--- | :---: | :---: | :--- |
| **Kehilangan Data Akibat Migrasi Database** | Tinggi | Sangat Rendah | Seluruh migrasi SQLite menggunakan `ALTER TABLE ADD COLUMN` tanpa menghapus tabel lama. Pencadangan (*backup*) berkas `healthcheck.sqlite` wajib dilakukan sebelum migrasi dijalankan. |
| **Klaim Medis Keliru oleh Pengguna** | Kritis | Sedang | Penempatan *Disclaimer Medis Eksplisit* di setiap halaman hasil kalkulator, footer, dashboard, dan laporan PDF bahwa platform adalah alat penapisan mandiri, bukan diagnosis klinis. |
| **Beban Query Lambat pada Riwayat SQLite** | Sedang | Sedang | Pembuatan indeks komposit pada `(user_id, created_at DESC)` dan pembatasan pagination ($10 - 20$ rekaman per halaman). |
| **Regresi Tautan Lama (Broken Links)** | Sedang | Rendah | Mempertahankan 23 berkas wrapper di root direktori sehingga URL yang sudah dibagikan pengguna tetap mengarah ke modul yang benar. |

---

## 4. Strategi Pengujian Kualitas (Testing & QA)

Sebelum setiap fase dirilis, wajib dilakukan pengujian terstruktur:
1. **Clinical Formula Unit Testing:**
   * Menyiapkan tabel kasus uji (*test cases*) dengan angka masukan dan luaran manual baku dari jurnal referensi (misal: BMI pria 70 kg 175 cm harus tepat 22.9; BMR pria 70 kg 175 cm 25 thn harus 1.699 kkal).
2. **Boundary & Negative Input Testing:**
   * Memasukkan nilai ekstrim (berat 0, negatif, 999 kg, karakter huruf) untuk memastikan validasi server-side menggagalkan input secara elegan tanpa memicu fatal error.
3. **Security Penetration Testing (Internal):**
   * Pengujian otorisasi IDOR: mencoba memanggil `history_detail.php?id=X` milik akun lain menggunakan akun yang berbeda.
   * Pengujian CSRF: mencoba mengirim request POST tanpa token atau dengan token kadaluarsa.
   * Pengujian akses langsung berkas SQLite via URL.
4. **Responsive UI & Cross-Browser Testing:**
   * Pengujian tampilan pada layar mobile (iOS Safari, Android Chrome), tablet, dan desktop resolusi tinggi.
