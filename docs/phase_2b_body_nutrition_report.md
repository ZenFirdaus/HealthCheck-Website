# Laporan Teknis Implementasi: Phase 2B Body & Nutrition Calculators

**Platform:** HealthCheck — Personal Health Assessment Platform  
**Fase:** Phase 2B (Kalkulator Kategori Komposisi Tubuh & Nutrisi)  
**Tanggal Eksekusi:** November 2026  
**Peran Pelaksana:** Senior PHP Developer, Database Engineer & Health Application Developer  
**Status Akhir:** **SELESAI & TERVERIFIKASI (100% Tests Passed — 36/36 Skenario)**

---

## 1. Executive Summary

Phase 2B berhasil memperluas kapabilitas HealthCheck dengan menambahkan klaster alat evaluasi kesehatan mandiri kategori **Body & Nutrition**. Tiga kalkulator baru telah dibangun dari nol dan satu modul kalkulator yang sudah ada telah ditingkatkan secara signifikan:

1. **Kalkulator Rasio Lingkar Pinggang ke Tinggi (WHtR - Waist-to-Height Ratio):** Skrining adipositas sentral dan risiko kardiometabolik berbasis metode Ashwell & Gibson (2016).
2. **Kalkulator Persentase Lemak Tubuh (US Navy Method):** Estimasi komposisi lemak tubuh murni dan massa bebas lemak (*lean body mass*) berbasis persamaan antropometri Hodgdon & Beckett (1984) serta klasifikasi American Council on Exercise (ACE).
3. **Kalkulator Kebutuhan Cairan Harian (Daily Fluid Needs):** Estimasi hidrasi air minum harian yang disesuaikan dengan berat badan, durasi aktivitas olahraga, dan faktor iklim mengacu panduan Kemenkes RI (Permenkes 41/2014) dan American College of Sports Medicine (ACSM).
4. **Peningkatan Modul BMR/TDEE dengan Estimasi Makronutrisi:** Melengkapi modul kalori yang ada dengan pilihan sasaran diet (*maintain*, *lose weight*, *gain weight*) serta estimasi gram harian untuk Protein, Karbohidrat, dan Lemak Sehat.

Seluruh modul baru telah terintegrasi secara penuh dengan:
* **User Profile Auto-Fill:** Pengisian awal cerdas berbasis `user_profile` tanpa efek samping mutasi profil (*decoupled snapshot*).
* **Smart Storage:** Pengunjung tamu (*guest*) tidak mencemari basis data (sesi sementara), sedangkan anggota (*member*) menyimpan rekam pemeriksaan terikat `user_id` sesi server.
* **Keamanan Berlapis:** Proteksi CSRF wajib, penolakan mutasi via GET (HTTP 405), dan pencegahan IDOR via `assert_record_access()`.

---

## 2. Hasil Audit Sebelum Implementasi

Sebelum penulisan kode Phase 2B, dilakukan audit menyeluruh terhadap arsitektur dan basis data eksisting:
1. **Audit Modul BMR/TDEE Eksisting:**
   * Fitur BMR dan TDEE dasar telah bekerja dengan baik setelah perbaikan bug multiplier di Phase 2A.
   * Sesuai arahan teknis, **tidak dibuat kalkulator TDEE terpisah/duplikat**. Modul `bmr/` ditingkatkan dengan integrasi sasaran berat badan dan distribusi gram makronutrisi (Protein, Karbohidrat, Lemak) menggunakan `includes/bmr_helper.php` sebagai satu-satunya sumber konfigurasi multiplier.
2. **Audit Skema Database SQLite:**
   * Tabel `cek_whtr`, `cek_lemak_tubuh`, dan `cek_kebutuhan_cairan` belum tersedia di database.
   * Tabel `cek_bmr` belum memiliki kolom penyimpanan hasil makronutrisi dan sasaran diet.
   * Dibuat migrasi baru `20261107_001_phase2b_body_nutrition_tables.php` yang dijalankan melalui migration runner resmi `database/migrate.php`.
3. **Audit Otorisasi & Whitelist IDOR:**
   * Fungsi `assert_record_access()` pada `includes/auth_helper.php` memiliki daftar putih tabel yang perlu diperluas dengan menambahkan `'cek_whtr'`, `'cek_lemak_tubuh'`, dan `'cek_kebutuhan_cairan'`.

---

## 3. Fitur yang Berhasil Dibangun

### A. Waist-to-Height Ratio (WHtR)
* **Direktori Modul:** `whtr/` (`whtr_form.php`, `whtr_proses.php`, `whtr_hasil.php`) serta root wrapper (`whtr_form.php`, `whtr_proses.php`, `whtr_hasil.php`).
* **Helper:** `includes/whtr_helper.php`.
* **Input:** Nama lengkap, jenis kelamin, tinggi badan (cm), lingkar pinggang (cm).
* **Formula:**
  $$\text{WHtR} = \frac{\text{Lingkar Pinggang (cm)}}{\text{Tinggi Badan (cm)}}$$
* **Klasifikasi Klinis (Ashwell & Gibson, 2016):**
  * $\text{WHtR} < 0.40$ : Kurang (Underweight Central)
  * $0.40 \le \text{WHtR} \le 0.49$ : Sehat / Risiko Normal (Ideal)
  * $0.50 \le \text{WHtR} \le 0.59$ : Risiko Meningkat (Increased Risk)
  * $\text{WHtR} \ge 0.60$ : Risiko Sangat Tinggi (Very High Risk)
* **Batas Sehat:** Menghitung rekomendasi batas lingkar pinggang maksimal sehat pengguna:
  $$\text{Batas Sehat (cm)} = 0.5 \times \text{Tinggi Badan (cm)}$$

---

### B. Persentase Lemak Tubuh (US Navy Method)
* **Direktori Modul:** `body_fat/` (`body_fat_form.php`, `body_fat_proses.php`, `body_fat_hasil.php`) serta root wrapper.
* **Helper:** `includes/body_fat_helper.php`.
* **Input:** Nama lengkap, jenis kelamin, umur, berat badan (kg), tinggi badan (cm), lingkar leher (cm), lingkar pinggang (cm), serta lingkar pinggul (cm, khusus wanita). Form interaktif secara otomatis menyembunyikan atau menampilkan input lingkar pinggul sesuai pilihan jenis kelamin.
* **Formula Antropometri (Hodgdon & Beckett, 1984):**
  * **Laki-laki:**
    $$\%BF = \frac{495}{1.0324 - 0.19077 \times \log_{10}(\text{pinggang} - \text{leher}) + 0.15456 \times \log_{10}(\text{tinggi})} - 450$$
  * **Perempuan:**
    $$\%BF = \frac{495}{1.29579 - 0.35004 \times \log_{10}(\text{pinggang} + \text{pinggul} - \text{leher}) + 0.22100 \times \log_{10}(\text{tinggi})} - 450$$
* **Komposisi Tubuh:**
  $$\text{Fat Mass (kg)} = \text{Berat Badan} \times \left(\frac{\%BF}{100}\right)$$
  $$\text{Lean Mass (kg)} = \text{Berat Badan} - \text{Fat Mass}$$
* **Klasifikasi ACE (American Council on Exercise):**
  * Pria: Essential Fat (2–5%), Athletes (6–13%), Fitness (14–17%), Average (18–24%), Obese ($\ge 25\%$)
  * Wanita: Essential Fat (10–13%), Athletes (14–20%), Fitness (21–24%), Average (25–31%), Obese ($\ge 32\%$)
* **Proteksi Matematis:** Pemeriksaan prasyarat ketat `pinggang > leher` (pria) dan `pinggang + pinggul > leher` (wanita) untuk mencegah nilai logaritma negatif/nol yang menghasilkan `NaN` atau `Infinity`.

---

### C. Estimasi Kebutuhan Cairan Harian (Daily Fluid Needs)
* **Direktori Modul:** `cairan/` (`cairan_form.php`, `cairan_proses.php`, `cairan_hasil.php`) serta root wrapper.
* **Helper:** `includes/cairan_helper.php`.
* **Input:** Nama lengkap, berat badan (kg), tingkat aktivitas olahraga harian, kondisi suhu/iklim lingkungan.
* **Metode Perhitungan (Kemenkes RI Permenkes 41/2014 & ACSM):**
  $$\text{Kebutuhan Dasar (ml)} = \text{Berat Badan (kg)} \times 35\text{ ml}$$
  $$\text{Kompensasi Olahraga} = 0\text{ ml (sedang)}, +400\text{ ml (aktif 30–60 mnt)}, +800\text{ ml (sangat aktif >60 mnt)}$$
  $$\text{Kompensasi Iklim} = 0\text{ ml (suhu normal/ber-AC)}, +350\text{ ml (cuaca panas/tropis)}$$
  $$\text{Total Cairan (ml)} = \text{Kebutuhan Dasar} + \text{Kompensasi Olahraga} + \text{Kompensasi Iklim}$$
  $$\text{Volume Liter} = \text{round}\left(\frac{\text{Total Cairan (ml)}}{1000}, 1\right)$$
  $$\text{Jumlah Gelas} = \text{round}\left(\frac{\text{Total Cairan (ml)}}{250}\right)$$
* **Peringatan Klinis:** Dilengkapi peringatan eksplisit bahwa pasien penyakit ginjal kronis (CKD) atau gagal jantung (CHF) wajib berkonsultasi dengan dokter terkait batasan volume cairan.

---

### D. Peningkatan TDEE & Estimasi Makronutrisi
* **Modul yang Ditingkatkan:** `bmr/` (`bmr_form.php`, `bmr_proses.php`, `bmr_hasil.php`, `bmr_edit.php`, `bmr_update.php`).
* **Helper:** `includes/bmr_helper.php`.
* **Fitur Baru:**
  * Penambahan opsi sasaran diet: `maintain` (pemeliharaan), `lose_weight` (defisit terkontrol ~450 kkal), `gain_weight` (surplus terukur ~450 kkal).
  * Batasan bawah klinis: defisit kalori pria tidak boleh tembus di bawah 1.400 kkal dan wanita 1.200 kkal.
  * Pembagian gram makronutrien pola seimbang (*Balanced Diet*):
    * **Karbohidrat (50%):** $\frac{\text{Target Kalori} \times 0.50}{4}\text{ gram}$
    * **Protein (20%):** $\frac{\text{Target Kalori} \times 0.20}{4}\text{ gram}$
    * **Lemak Sehat (30%):** $\frac{\text{Target Kalori} \times 0.30}{9}\text{ gram}$

---

## 4. Perubahan Database dan Migrasi

Dibuat berkas migrasi:  
`database/migrations/20261107_001_phase2b_body_nutrition_tables.php`

Dieksekusi menggunakan runner idempoten:
```bash
php database/migrate.php
```

### Tabel Baru yang Dibuat:
1. **`cek_whtr`:** Menyimpan id, `user_id` (nullable FK), nama, jenis kelamin, lingkar pinggang, tinggi badan, rasio WHtR, kategori risiko, saran, dan timestamp. Dilengkapi indeks `idx_whtr_user_created`.
2. **`cek_lemak_tubuh`:** Menyimpan id, `user_id` (nullable FK), nama, jenis kelamin, umur, tinggi badan, berat badan, lingkar leher, lingkar pinggang, lingkar pinggul, persen lemak, massa lemak kg, massa bebas lemak kg, kategori, dan timestamp. Dilengkapi indeks `idx_fat_user_created`.
3. **`cek_kebutuhan_cairan`:** Menyimpan id, `user_id` (nullable FK), nama, berat badan, aktivitas, iklim, kebutuhan ml, kebutuhan liter, kebutuhan gelas, saran, dan timestamp. Dilengkapi indeks `idx_cairan_user_created`.

### Tabel yang Diperbarui:
* **`cek_bmr`:** Ditambahkan 5 kolom baru secara non-destructive:
  * `target_tujuan VARCHAR(50) DEFAULT 'maintain'`
  * `target_kalori REAL DEFAULT 0`
  * `protein_gram REAL DEFAULT 0`
  * `karbo_gram REAL DEFAULT 0`
  * `lemak_gram REAL DEFAULT 0`

---

## 5. Integrasi Profil Pengguna (Auto-Fill & Decoupled Snapshot)

Keempat alat menerapkan integrasi profil pengguna:
* **Auto-Fill Nilai Awal:** Member yang memiliki rekaman di `user_profile` mendapati form terisi otomatis (nama lengkap, jenis kelamin, tinggi badan, berat badan, serta umur hasil kalkulasi tanggal lahir terhadap tanggal saat ini via `calculate_age_from_dob()`).
* **Member Profil Parsial / Tamu:** Jika tanggal lahir belum diisi, form menyediakan default 25 tahun; jika berat/tinggi badan belum ada di profil, input form berstatus kosong untuk diisi manual. Form tamu berstatus kosong tanpa tercemar data profil siapapun.
* **Decoupled Snapshot:** Nilai profil hanya menjadi nilai awal untuk kenyamanan pengguna. Pengguna bebas mengubah nilai di form kalkulator; pemeriksaan disimpan sebagai rekaman historis mandiri dan **tidak memutasi baris data di `user_profile`**.

---

## 6. Implementasi Smart Storage & Keamanan

1. **Smart Storage Guest:**
   * Pengunjung tanpa login tidak memicu query `INSERT INTO` ke tabel database manapun.
   * Hasil perhitungan disimpan di sesi (`$_SESSION['whtr_result']`, `$_SESSION['body_fat_result']`, `$_SESSION['cairan_result']`).
2. **Smart Storage Member:**
   * Pengguna login otomatis menyimpan hasil ke tabel basis data terkait dengan kolom `user_id` diisi langsung dari `$_SESSION['user_id']`.
   * Parameter `user_id` yang disuntikkan penyerang via form POST atau query URL diabaikan sepenuhnya.
3. **Pencegahan IDOR (Insecure Direct Object Reference):**
   * Endpoint detail hasil (`*_hasil.php?id=...`) diverifikasi menggunakan `assert_record_access()`. Pengguna A dilarang membaca atau mengubah data milik Pengguna B (dibalas **HTTP 403 Forbidden**).
   * Data legacy (`user_id = NULL`) hanya dapat diakses oleh administrator.
4. **Proteksi CSRF & Metode HTTP:**
   * Semua mutasi wajib menggunakan `POST` dan divalidasi via `require_csrf_token()`. Permintaan tanpa token dibalas **HTTP 403 Forbidden**.
   * Upaya penghapusan data via metode `GET` ditolak dengan **HTTP 405 Method Not Allowed**.

---

## 7. Matriks Pengujian Lengkap (36/36 Skenario Lulus)

Pengujian otomatis Phase 2B dijalankan pada database terisolasi (`healthcheck_test_phase2b.sqlite`) via `tests_phase2b_runner.php`:

| No | Modul | Skenario Pengujian | Hasil Observasi | Status |
| :-: | :---: | :--- | :--- | :-: |
| 1 | **WHtR** | Perhitungan normal (Pinggang 78 cm, Tinggi 170 cm) | WHtR = 0.46, kategori Sehat / Ideal, batas sehat < 85 cm | **PASS** |
| 2 | **WHtR** | Validasi input kosong | Ditolak dengan HTTP 302 redirect `pesan=invalid_input` | **PASS** |
| 3 | **WHtR** | Validasi nilai nol atau negatif | Ditolak dengan HTTP 302 redirect `pesan=invalid_input` | **PASS** |
| 4 | **WHtR** | Validasi tinggi badan di luar rentang (400 cm) | Ditolak dengan HTTP 302 redirect `pesan=invalid_input` | **PASS** |
| 5 | **WHtR** | Validasi lingkar pinggang di luar rentang (15 cm) | Ditolak dengan HTTP 302 redirect `pesan=invalid_input` | **PASS** |
| 6 | **WHtR** | Akurasi seluruh 4 kategori klinis Ashwell & Gibson | Kurang (<0.40), Sehat (0.40–0.49), Meningkat (0.50–0.59), Sangat Tinggi (≥0.60) 100% presisi | **PASS** |
| 7 | **Body Fat** | Perhitungan Body Fat Pria (TB 175, Pinggang 85, Leher 38) | %BF = 16.9% (Kategori ACE: Fitness Ideal) | **PASS** |
| 8 | **Body Fat** | Perhitungan Body Fat Wanita (TB 160, Pinggang 75, Leher 32, Pinggul 95) | %BF = 28.8% (Kategori ACE: Rata-rata) | **PASS** |
| 9 | **Body Fat** | Validasi lingkar leher di luar rentang (10 cm) | Ditolak dengan HTTP 302 redirect `pesan=invalid_input` | **PASS** |
| 10 | **Body Fat** | Validasi lingkar pinggang ≤ lingkar leher pria | Ditolak dengan aman sebelum memicu evaluasi logaritma | **PASS** |
| 11 | **Body Fat** | Validasi lingkar pinggul wanita wajib diisi & valid | Permintaan tanpa pinggul ditolak | **PASS** |
| 12 | **Body Fat** | Validasi tinggi badan di luar rentang (40 cm) | Ditolak dengan HTTP 302 redirect `pesan=invalid_input` | **PASS** |
| 13 | **Body Fat** | Robustness: Kebal dari `NaN` atau `Infinity` | Input matematis ilegal mengembalikan `null` terproteksi | **PASS** |
| 14 | **Fluid** | Perhitungan normal (BB 60 kg, aktivitas & iklim sedang) | Kebutuhan = 2.1 Liter (2.100 ml / 8 gelas @250ml) | **PASS** |
| 15 | **Fluid** | Validasi input cairan kosong | Ditolak dengan HTTP 302 redirect `pesan=invalid_input` | **PASS** |
| 16 | **Fluid** | Validasi berat badan negatif / nol | Ditolak dengan HTTP 302 redirect `pesan=invalid_input` | **PASS** |
| 17 | **Fluid** | Kompensasi olahraga aktif & cuaca panas | BB 60kg: 2.100 + 400 + 350 = 2.850 ml (~11 gelas) | **PASS** |
| 18 | **Fluid** | Konsistensi volume matematis: liter = ml / 1000 | Liter (2.5 L) dan ml (2.450 ml) 100% konsisten | **PASS** |
| 19 | **TDEE & Macro** | Integrasi multiplier BMR dari `bmr_helper.php` | BMR 1.674 * 1.55 = TDEE 2.595 kkal terhitung akurat | **PASS** |
| 20 | **TDEE & Macro** | Ketiga sasaran kalori (maintain, deficit, surplus) | Maintain: 2.595 kkal, Defisit (-450): 2.145 kkal, Surplus (+450): 3.045 kkal | **PASS** |
| 21 | **TDEE & Macro** | Perhitungan gram makronutrien (Target 2000 kkal) | Protein: 100g (20%), Karbo: 250g (50%), Lemak: 66.7g (30%) | **PASS** |
| 22 | **TDEE & Macro** | Input BMR/TDEE tidak valid ditolak | Umur 5 / BB 10 ditolak dengan redirect `pesan=invalid_input` | **PASS** |
| 23 | **TDEE & Macro** | Multiplier aktivitas fisik tidak dikenal ditolak | Nilai aktivitas acak ditolak server-side | **PASS** |
| 24 | **TDEE & Macro** | Batas bawah klinis kalori (Boundary check) | Target kalori pria dibatasi minimal 1.400 kkal dan wanita 1.200 kkal | **PASS** |
| 25 | **Security** | Smart Storage Guest: Zero DB insert | Tabel `cek_whtr`, `cek_lemak_tubuh`, `cek_kebutuhan_cairan` tetap 0 baris pada aksi guest | **PASS** |
| 26 | **Security** | Smart Storage Member: DB insert dengan user_id | Ketiga alat tersimpan di DB terikat `user_id = 2` | **PASS** |
| 27 | **Security** | Snapshot parameter biometrik tersimpan utuh | Seluruh input antropometri dan hasil evaluasi tersimpan di DB | **PASS** |
| 28 | **Security** | IDOR: User A dilarang mengakses data User B | Percobaan akses `*_hasil.php?id=X` milik User B dibalas HTTP 403 Forbidden | **PASS** |
| 29 | **Security** | Manipulasi `user_id` via request body diabaikan | Nilai `user_id=1` disuntikkan, sistem tetap mengikat ke `user_id=2` dari sesi | **PASS** |
| 30 | **Security** | Proteksi CSRF wajib untuk mutasi | POST tanpa token dibalas HTTP 403 Forbidden | **PASS** |
| 31 | **Security** | Operasi GET dilarang menghapus data | Permintaan GET ke file hapus dibalas HTTP 405 Method Not Allowed | **PASS** |
| 32 | **Security** | Snapshot historis kebal dari pembaruan profil | Profil diubah dari 68 kg ke 80 kg, rekam historis tetap 68 kg | **PASS** |
| 33 | **Regresi** | 4 Kalkulator lama tetap merespons 200 OK | Form BMI, BMR, Detak Jantung, PHQ-4 beroperasi normal | **PASS** |
| 34 | **Regresi** | Integritas formula klinis lama | Tanaka Max HR 191 BPM, ambang batas BMI Asia 22.9 100% konsisten | **PASS** |
| 35 | **Regresi** | Idempotensi Migration Runner Phase 2B | Eksekusi ulang migrasi menghasilkan *"Nothing to migrate"*, skema database utuh | **PASS** |
| 36 | **Regresi** | Linting seluruh file PHP repositori (`php -l`) | 96 file PHP repositori lulus verifikasi sintaks tanpa error | **PASS** |

### Ringkasan Angka Pengujian:
* **Total Skenario Uji Phase 2B:** 36
* **Lulus (PASS):** 36
* **Gagal (FAIL):** 0
* **Tingkat Kelulusan:** **100%**
* **Pengujian Kumulatif Repositori:**
  * Phase 1A Suite: 12 / 12 PASS
  * Phase 1B Suite: 14 / 14 PASS
  * Phase 2A Suite: 30 / 30 PASS
  * Phase 2B Suite: 36 / 36 PASS
  * **Grand Total:** **92 / 92 PASS (100%)**

---

## 8. Verifikasi Sintaks PHP (Linting)

Seluruh 96 file PHP pada repositori (di luar folder backup cadangan) diperiksa menggunakan linter CLI bawaan PHP:
```bash
for f in $(find . -name "*.php" -not -path "./backups_phase1a/*" -not -path "./backups_phase1b/*"); do php -l "$f" || exit 1; done
```
**Hasil:** `No syntax errors detected` pada seluruh berkas.

---

## 9. Kendala yang Dihadapi & Solusi Teknis

1. **Lock Contention pada SQLite saat Pengujian Paralel:**
   * *Kendala:* Eksekusi test runner yang menyalin database saat proses lain membuka file dapat memicu peringatan *database disk image is malformed*.
   * *Solusi:* Ditambahkan pemanggilan `PRAGMA wal_checkpoint(TRUNCATE);` pada koneksi sebelum proses duplikasi file uji, serta pembersihan file `-wal` dan `-shm` sementara secara teratur.
2. **Pencegahan Error Evaluasi Logaritma pada US Navy Formula:**
   * *Kendala:* Jika pengguna pria memasukkan lingkar pinggang lebih kecil dari leher, argumen fungsi `log10()` bernilai negatif atau nol, memicu fatal math error.
   * *Solusi:* Validasi anatomi ketat diterapkan di sisi klien (JS) dan di sisi server (PHP) sebelum fungsi logaritma dipanggil, mengembalikan respons redirect yang elegan jika parameter tidak masuk akal.

---

## 10. Rekomendasi Teknis untuk Phase 2C

1. **Klaster Kardiovaskular, Tidur & Manajemen Stres (Phase 2C):**
   * Pemeriksaan Tekanan Darah (*Blood Pressure Assessment* AHA/ACC 2017 & Perhiper).
   * Kalkulator & Skrining Kualitas Tidur (*Mini Pittsburgh Sleep Quality Index / PSQI*).
   * Kalkulator Target Detak Jantung Lanjutan (5 Zona Karvonen & Zoladz).
   * Kalkulator Aktivitas Fisik (*IPAQ Short Form* - MET-menit/minggu).
   * Skrining Tingkat Stres (*Perceived Stress Scale / PSS-10*).
2. **Replikasi Standar Arsitektur Phase 2B:**
   * Seluruh kalkulator baru pada Phase 2C wajib mengadopsi pola Smart Storage, integrasi profil auto-fill, dan proteksi IDOR yang telah distandardisasi pada Phase 2A dan Phase 2B.
