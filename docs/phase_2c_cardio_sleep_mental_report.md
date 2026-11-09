# Laporan Teknis Implementasi: Phase 2C Cardio, Sleep & Mental Health

**Platform:** HealthCheck — Personal Health Assessment Platform  
**Fase:** Phase 2C (Kardiovaskular, Higiene Tidur & Kesehatan Mental)  
**Tanggal Eksekusi:** November 2026  
**Peran Pelaksana:** Senior PHP Developer, Database Engineer, UI/UX Implementation Specialist & Health Application Developer  
**Status Akhir:** **SELESAI & TERVERIFIKASI (100% Tests Passed — 55/55 Skenario Uji)**

---

## 1. Executive Summary

Phase 2C memperluas jangkauan klinis platform HealthCheck dengan mengimplementasikan 5 modul penapisan mandiri penting dalam kategori **Cardio, Sleep & Mental Health**:

1. **Pemeriksaan Tekanan Darah (`tekanan_darah/`):** Evaluasi tekanan sistolik dan diastolik mengacu pedoman gabungan AHA/ACC 2017 & Konsensus PERHI 2021, lengkap dengan kalkulasi tekanan nadi (*pulse pressure*) dan peringatan darurat krisis hipertensi (*red-flag emergency*).
2. **Penilaian Kebiasaan & Higiene Tidur (`tidur/`):** Alternatif penilaian tidur mandiri yang etis dan non-diagnostik (*Sleep Habit Assessment*), mengevaluasi durasi tidur aktual, latensi tidur, frekuensi terbangun malam, jeda paparan gawai, dan indeks efisiensi tidur (%).
3. **Zona Latihan Detak Jantung (`zona_jantung/`):** Penentuan 5 zona intensitas latihan kardio menggunakan Metode Karvonen (*Heart Rate Reserve*) dan Metode Zoladz (*Max HR Deficit*), lengkap dengan batasan kontraindikasi obat beta-blocker.
4. **Evaluasi Aktivitas Fisik (`aktivitas_fisik/`):** Implementasi instrumen resmi *International Physical Activity Questionnaire (IPAQ Short Form 7-day recall)* dengan formula baku MET-menit/minggu dan aturan pembersihan data resmi (truncation & cleaning).
5. **Penilaian Tingkat Stres (`stres/`):** Instrumen psikometri terstandar *Perceived Stress Scale (PSS-10)* dengan pembalikan skor (*reverse scoring*) resmi pada 4 butir pertanyaan positif dan rujukan kontak krisis Kemenkes 119 ext. 8.

Seluruh modul dibangun dengan mempertahankan 100% konsistensi terhadap sistem desain terbaru:
* Skema warna berkarakter (*Medical Navy*, *Strong Blue*, *Rich Teal*, *Coral*, *Emerald*, *Deep Lavender*).
* Komposisi kartu organik bersudut membulat 20px dengan foto fotografi medis beresolusi tinggi tanpa ornamen/badge mengambang.
* Tombol interaktif solid dengan sudut 11–16px dan hierarki yang tegas.
* Smart Storage & perlindungan kepemilikan data (IDOR) melalui `assert_record_access()`.

---

## 2. Hasil Audit Kode & UI Terbaru

Sebelum implementasi Phase 2C, dilakukan audit terhadap seluruh aset visual dan struktur kode:
1. **Pola Desain UI Aktual (Single Source of Truth):**
   - Halaman form dan hasil pada modul Phase 2B (`whtr/`, `body_fat/`, `cairan/`) diidentifikasi sebagai standar acuan desain yang matang.
   - Menggunakan layout 2-kolom: kolom kiri untuk card form/hasil utama (`.card.overflow-hidden`, padding `1.4rem 1.75rem`), kolom kanan untuk kartu panduan klinis (`.info-card` dan `.info-card-tint`).
   - Seluruh modul Phase 2C dibangun langsung mengikuti pola markup, kelas CSS, dan hierarki visual tersebut tanpa membuat CSS global baru yang berpotensi merusak modul sebelumnya.
2. **Aset Visual & Fotografi Realistis:**
   - Diunduh 5 foto editorial medis beresolusi tinggi ke dalam `assets/images/`:
     - `tool-tensi.jpg` (Pemeriksaan tensimeter manset)
     - `tool-sleep.jpg` (Suasana istirahat kamar tidur tenang)
     - `tool-hrzones.jpg` (Latihan treadmill kardiovaskular terukur)
     - `tool-activity.jpg` (Aktivitas fisik jalan dan lari luar ruangan)
     - `tool-stress.jpg` (Refleksi mindfulness dan ketenangan mental)
3. **Penyelarasan Basis Data:**
   - Migrasi skema `20261108_001_phase2c_cardio_sleep_mental_tables.php` dieksekusi secara idempoten melalui migration runner `database/migrate.php`.
   - Whitelist fungsi `assert_record_access()` pada `includes/auth_helper.php` diperluas dengan 5 nama tabel baru.

---

## 3. Keputusan Penggunaan Instrumen (PSQI, IPAQ, PSS-10)

### A. Keputusan Instrumen Tidur (PSQI vs Sleep Habit Assessment)
* **Kajian Lisensi:** Kuesioner *Pittsburgh Sleep Quality Index (PSQI)* asli 19 butir (Buysse et al., 1989) dilindungi hak cipta oleh University of Pittsburgh dan memerlukan izin lisensi institusional formal untuk reproduksi elektronik publik.
* **Keputusan Desain & Etika:** Sesuai instruksi tugas, HealthCheck **tidak mengarang atau menyalin pertanyaan PSQI secara ilegal**, dan secara jujur menamai modul ini sebagai **Sleep Habit Assessment** (Penilaian Higiene & Kebiasaan Tidur). Modul mengevaluasi parameter objektif: durasi tidur, waktu tertidur (latensi), frekuensi terbangun malam, penggunaan gawai sebelum tidur, kesegaran bangun pagi, serta indeks efisiensi tidur (%) secara transparan.

### B. Keputusan Instrumen Aktivitas Fisik (IPAQ Short Form)
* **Kajian Lisensi:** *International Physical Activity Questionnaire (IPAQ)* adalah instrumen riset terbuka (*open-access*) yang dirancang khusus untuk surveilans aktivitas fisik populasi global (Craig et al., 2003).
* **Keputusan Implementasi:** Menggunakan pertanyaan resmi 7-hari ingatan (*7-day recall*) IPAQ Short Form mencakup aktivitas berat, sedang, berjalan kaki, dan waktu duduk dengan pembobotan MET resmi dan aturan pembersihan data standar.

### C. Keputusan Instrumen Stres (PSS-10)
* **Kajian Lisensi:** *Perceived Stress Scale 10-Item (PSS-10)* oleh Dr. Sheldon Cohen dkk. (1983) adalah instrumen riset psikologi berstatus *public domain* untuk penggunaan pendidikan dan penapisan non-komersial.
* **Keputusan Implementasi:** Mengadopsi 10 butir pertanyaan resmi PSS-10 bahasa Indonesia dengan aturan pembalikan skor (*reverse scoring*) yang presisi pada 4 butir positif (butir 4, 5, 7, 8).

---

## 4. Formula dan Sumber Referensi Klinis

### 1. Tekanan Darah (Blood Pressure Assessment)
* **Sumber Acuan:** 
  - Whelton PK, et al. *2017 ACC/AHA/AAPA/ABC/ACPM/AGS/APhA/ASH/ASPC/NMA/PCNA Guideline for the Prevention, Detection, Evaluation, and Management of High Blood Pressure in Adults.* J Am Coll Cardiol. 2018;71(19):e127-e248.
  - Perhimpunan Dokter Hipertensi Indonesia (PERHI). *Konsensus Penatalaksanaan Hipertensi 2021.*
* **Kriteria Klasifikasi:**
  - **Hipotensi:** Sistolik < 90 mmHg ATAU Diastolik < 60 mmHg.
  - **Normal:** Sistolik < 120 mmHg DAN Diastolik < 80 mmHg.
  - **Pra-Hipertensi (Elevated):** Sistolik 120–129 mmHg DAN Diastolik < 80 mmHg.
  - **Hipertensi Derajat 1:** Sistolik 130–139 mmHg ATAU Diastolik 80–89 mmHg.
  - **Hipertensi Derajat 2:** Sistolik 140–180 mmHg ATAU Diastolik 90–120 mmHg.
  - **Krisis Hipertensi:** Sistolik > 180 mmHg DAN/ATAU Diastolik > 120 mmHg (*Red-flag emergency warning*).
  - *Aturan Ambang:* Bila sistolik dan diastolik berada pada dua kategori berbeda, kategori yang lebih tinggi digunakan sebagai hasil evaluasi.
* **Formula Tekanan Nadi:** $\text{Pulse Pressure} = \text{Sistolik} - \text{Diastolik}$ (Rentang normal: 40–60 mmHg).

### 2. Kualitas & Higiene Tidur (Sleep Habit Assessment)
* **Sumber Acuan:**
  - Hirshkowitz M, et al. *National Sleep Foundation’s sleep time duration recommendations.* Sleep Health. 2015;1(1):40-43.
  - American Academy of Sleep Medicine (AASM) Sleep Hygiene Guidelines.
* **Indeks Efisiensi Tidur:**
  $$\text{Efisiensi Tidur (\%)} = \min\left(100, \frac{\text{Durasi Tidur}}{\text{Durasi Tidur} + (\text{Latensi}/60) + (\text{Terbangun} \times 0.25)} \times 100\right)$$
* **Skoring Higiene Tidur (Skala 0–15):**
  - Durasi tidur (0–3), latensi tidur (0–3), frekuensi terbangun (0–3), paparan gawai sebelum tidur (0–3), kesegaran bangun pagi (0–3).
  - Kategori: 0–3 Sangat Baik (Efisiensi $\ge 85\%$), 4–7 Cukup Baik, 8–11 Kurang Optimal, 12–15 Perlu Perhatian.

### 3. Zona Latihan Detak Jantung (Karvonen & Zoladz)
* **Sumber Acuan:**
  - Tanaka H, Monahan KD, Seals DR. *Age-predicted maximal heart rate revisited.* J Am Coll Cardiol. 2001;37(1):153-156.
  - Karvonen MJ, Kentala E, Mustala O. *The effects of training on heart rate; a longitudinal study.* Ann Med Exp Biol Fenn. 1957;35(3):307-315.
  - Zoladz JA, et al. *Muscle metabolite levels and fatigue during training zones.* Int J Sports Med. 1995.
* **Formula:**
  - Denyut Jantung Maksimal: $\text{HR}_{\max} = 208 - (0.7 \times \text{Umur})$
  - Heart Rate Reserve: $\text{HRR} = \text{HR}_{\max} - \text{RHR}$
  - **Metode Karvonen (5 Zona):** $\text{Target BPM} = \text{RHR} + (\% \times \text{HRR})$
    - Z1: 50–60% HRR (Pemulihan Aktif)
    - Z2: 60–70% HRR (Pembakaran Lemak & Stamina Dasar)
    - Z3: 70–80% HRR (Aerobik & Paru)
    - Z4: 80–90% HRR (Ambang Laktat)
    - Z5: 90–100% HRR (Daya Ledak Maksimal)
  - **Metode Zoladz (5 Zona):**
    - Z1: $(\text{HR}_{\max} - 50) \pm 5$ BPM
    - Z2: $(\text{HR}_{\max} - 40) \pm 5$ BPM
    - Z3: $(\text{HR}_{\max} - 30) \pm 5$ BPM
    - Z4: $(\text{HR}_{\max} - 20) \pm 5$ BPM
    - Z5: $(\text{HR}_{\max} - 10) \pm 5$ BPM

### 4. Evaluasi Aktivitas Fisik (IPAQ Short Form)
* **Sumber Acuan:**
  - Craig CL, Marshall AL, Sjöström M, et al. *International physical activity questionnaire: 12-country reliability and validity.* Med Sci Sports Exerc. 2003;35(8):1381-1395.
  - World Health Organization. *WHO Guidelines on physical activity and sedentary behaviour.* Geneva: WHO; 2020.
* **Pembobotan & Formula:**
  $$\text{Total MET-menit/mgg} = (8.0 \times \text{VigDays} \times \text{VigMin}) + (4.0 \times \text{ModDays} \times \text{ModMin}) + (3.3 \times \text{WalkDays} \times \text{WalkMin})$$
* **Aturan Data Cleaning IPAQ Resmi:**
  - Durasi aktivitas $<10$ menit di-recode menjadi 0.
  - Pemotongan (*truncation*): aktivitas $>180$ menit/hari dipotong maksimal 180 menit untuk mencegah *overreporting*.
* **Klasifikasi Kategori:**
  - **Tinggi (Highly Active):** Berat $\ge 3$ hari dengan total MET $\ge 1500$, ATAU kombinasi 7 hari $\ge 3000$ MET-menit/mgg.
  - **Sedang (Moderately Active):** Berat $\ge 3$ hari $\ge 20$ menit, ATAU sedang/jalan $\ge 5$ hari $\ge 30$ menit, ATAU kombinasi $\ge 5$ hari $\ge 600$ MET-menit/mgg.
  - **Rendah (Sedentary / Inactive):** Tidak memenuhi kriteria sedang atau tinggi ($< 600$ MET-menit/mgg).
  - Peringatan sedentari muncul jika waktu duduk harian $\ge 8$ jam/hari.

### 5. Penilaian Tingkat Stres (PSS-10)
* **Sumber Acuan:**
  - Cohen S, Kamarck T, Mermelstein R. *A global measure of perceived stress.* J Health Soc Behav. 1983;24(4):385-396.
* **Aturan Skoring:**
  - 10 butir pertanyaan Likert (skor 0–4).
  - Butir negatif (1, 2, 3, 6, 9, 10): skor langsung ($0 \dots 4$).
  - Butir positif (4, 5, 7, 8): skor terbalik ($4 - \text{jawaban}$, $0 \to 4, 1 \to 3, 2 \to 2, 3 \to 1, 4 \to 0$).
  - Total skor: 0 sampai 40.
  - Kategori: 0–13 Stres Rendah (*Low Stress*), 14–26 Stres Sedang (*Moderate Stress*), 27–40 Stres Tinggi (*High Perceived Stress*).
  - Tautan darurat Layanan Sejiwa Kemenkes RI: **119 (ext. 8)**.

---

## 5. Perubahan Database dan Migrasi

Berkas migrasi: `database/migrations/20261108_001_phase2c_cardio_sleep_mental_tables.php`  
Diterapkan via runner: `php database/migrate.php`

### Tabel Baru yang Dibuat:
1. `cek_tekanan_darah` (`id_bp`, `user_id`, `nama_user`, `umur`, `sistolik`, `diastolik`, `detak_nadi`, `kategori`, `saran`, `catatan`, `created_at`). Indeks: `idx_bp_user_created`.
2. `cek_kualitas_tidur` (`id_tidur`, `user_id`, `nama_user`, `durasi_jam`, `latensi_menit`, `frekuensi_terbangun`, `penggunaan_gawai`, `kesegaran_bangun`, `efisiensi_tidur`, `skor_kebiasaan`, `kategori_tidur`, `saran`, `created_at`). Indeks: `idx_tidur_user_created`.
3. `cek_zona_detak_jantung` (`id_zona`, `user_id`, `nama_user`, `umur`, `resting_hr`, `max_hr`, `hrr`, `metode`, `zona_1_min` s.d. `zona_5_max`, `saran`, `created_at`). Indeks: `idx_zona_hr_user_created`.
4. `cek_aktivitas_fisik` (`id_aktivitas`, `user_id`, `nama_user`, `hari_berat`, `menit_berat`, `hari_sedang`, `menit_sedang`, `hari_jalan`, `menit_jalan`, `jam_duduk`, `total_met_menit`, `kategori_aktivitas`, `saran`, `created_at`). Indeks: `idx_aktivitas_user_created`.
5. `cek_stres` (`id_stres`, `user_id`, `nama_user`, `skor_stres`, `tingkat_stres`, `saran`, `created_at`). Indeks: `idx_stres_user_created`.

Seluruh tabel terikat relasi `user_id INTEGER REFERENCES user(id) ON DELETE SET NULL` dengan integritas `PRAGMA foreign_keys = ON;`.

---

## 6. Integrasi Profil Pengguna & Smart Storage

- **Auto-Fill Profil Member:**
  Setiap form kalkulator Phase 2C memanggil `get_current_user_profile($pdo)`:
  - Mengisi otomatis nama lengkap atau username.
  - Menghitung umur otomatis dari `tanggal_lahir` (`calculate_age_from_dob()`).
  - Nilai profil hanya sebagai nilai awal; modifikasi input oleh pengguna tidak mengubah data profil (`user_profile` tetap terlindungi dari mutasi sampingan).
- **Smart Storage Guest:**
  Pengunjung tanpa akun dapat menggunakan seluruh kalkulator tanpa batasan; hasil perhitungan disimpan di sesi (`$_SESSION['bp_result']`, `$_SESSION['sleep_result']`, `$_SESSION['zona_hr_result']`, `$_SESSION['activity_result']`, `$_SESSION['stress_result']`) tanpa memicu operasi `INSERT` ke database.
- **Smart Storage Member:**
  Pengguna yang login otomatis tersimpan rekamannya ke tabel database terkait dengan `user_id` yang divalidasi langsung dari sesi server (`$_SESSION['user_id']`).
- **Pencegahan IDOR:**
  Setiap pemanggilan detail hasil (`*_hasil.php?id=...`) divalidasi melalui `assert_record_access()`. Upaya akses silang data antar-pengguna langsung dibalas **HTTP 403 Forbidden**.
- **Proteksi CSRF:**
  Seluruh aksi form menggunakan metode `POST` dengan verifikasi token kriptografis `require_csrf_token()`.

---

## 7. Daftar File Baru dan Dimodifikasi

### Berkas Baru yang Dibuat:
1. `database/migrations/20261108_001_phase2c_cardio_sleep_mental_tables.php`
2. `includes/tensi_helper.php`
3. `includes/tidur_helper.php`
4. `includes/zona_jantung_helper.php`
5. `includes/aktivitas_helper.php`
6. `includes/stres_helper.php`
7. `tekanan_darah/tekanan_darah_form.php`
8. `tekanan_darah/tekanan_darah_proses.php`
9. `tekanan_darah/tekanan_darah_hasil.php`
10. `tidur/tidur_form.php`
11. `tidur/tidur_proses.php`
12. `tidur/tidur_hasil.php`
13. `zona_jantung/zona_jantung_form.php`
14. `zona_jantung/zona_jantung_proses.php`
15. `zona_jantung/zona_jantung_hasil.php`
16. `aktivitas_fisik/aktivitas_fisik_form.php`
17. `aktivitas_fisik/aktivitas_fisik_proses.php`
18. `aktivitas_fisik/aktivitas_fisik_hasil.php`
19. `stres/stres_form.php`
20. `stres/stres_proses.php`
21. `stres/stres_hasil.php`
22. `assets/images/tool-tensi.jpg`
23. `assets/images/tool-sleep.jpg`
24. `assets/images/tool-hrzones.jpg`
25. `assets/images/tool-activity.jpg`
26. `assets/images/tool-stress.jpg`
27. `tests/tests_phase2c_runner.php`

### Berkas yang Dimodifikasi:
1. `includes/auth_helper.php` — Penambahan 5 tabel baru ke dalam whitelist `assert_record_access()`.
2. `index.php` — Penambahan 5 kartu alat ke `toolsGrid`, penambahan shortcut filter kategori tidur (`data-category="tidur"`), dan pembaruan rujukan metodologi klinis.
3. `includes/header.php` — Penambahan tautan 5 alat baru di dropdown menu navbar dan pembaruan array `$activePage`.
4. `includes/footer.php` — Penambahan tautan alat baru pada navigasi footer.
5. `admin/admin.php` — Penambahan 5 kartu ringkasan statistik, 5 tab navigasi data, dan 5 tabel moderasi admin untuk alat baru.

---

## 8. Matriks Pengujian Lengkap (55/55 Skenario Uji Lulus)

Pengujian komprehensif dijalankan menggunakan script uji terisolasi `tests/tests_phase2c_runner.php`:

| No | Modul | Skenario Pengujian | Hasil Observasi | Status |
| :-: | :---: | :--- | :--- | :-: |
| 1 | **Tensi** | Klasifikasi tensi normal (115/75 mmHg) | Terklasifikasi Normal (<120/<80) | **PASS** |
| 2 | **Tensi** | Klasifikasi pra-hipertensi (125/78 mmHg) | Terklasifikasi Pra-Hipertensi (120-129/<80) | **PASS** |
| 3 | **Tensi** | Klasifikasi hipertensi derajat 1 (135/85 mmHg) | Terklasifikasi Hipertensi Tingkat 1 | **PASS** |
| 4 | **Tensi** | Klasifikasi hipertensi derajat 2 (145/95 mmHg) | Terklasifikasi Hipertensi Tingkat 2 | **PASS** |
| 5 | **Tensi** | Krisis hipertensi darurat (190/125 mmHg) | Terdeteksi Krisis Hipertensi & alert darurat aktif | **PASS** |
| 6 | **Tensi** | Klasifikasi hipotensi (85/55 mmHg) | Terklasifikasi Hipotensi (<90/<60) | **PASS** |
| 7 | **Tensi** | Aturan kategori lebih tinggi (145/75 mmHg) | Mengikuti kategori sistolik tertinggi (Stage 2) | **PASS** |
| 8 | **Tensi** | Validasi: Sistolik <= Diastolik ditolak | Ditolak dengan redirect `pesan=invalid_input` | **PASS** |
| 9 | **Tensi** | Validasi: Selisih sistolik-diastolik < 15 mmHg ditolak | Ditolak dengan redirect `pesan=invalid_input` | **PASS** |
| 10 | **Tensi** | Validasi: Input tensi kosong / negatif ditolak | Ditolak dengan redirect `pesan=invalid_input` | **PASS** |
| 11 | **Tidur** | Skoring tidur sangat baik (7.5 jam, latensi 15m) | Skor 0/15 (Sangat Baik), Efisiensi 96.8% | **PASS** |
| 12 | **Tidur** | Skoring tidur cukup (6.5 jam, latensi 25m) | Skor 5/15 (Cukup Baik) | **PASS** |
| 13 | **Tidur** | Skoring gangguan tidur signifikan (4 jam, latensi 75m) | Skor 15/15 (Perlu Perhatian, Warna: Danger) | **PASS** |
| 14 | **Tidur** | Akurasi formula efisiensi waktu tidur | Efisiensi 88.9% terhitung akurat | **PASS** |
| 15 | **Tidur** | Validasi: Durasi tidur < 1 jam ditolak | Ditolak dengan redirect `pesan=invalid_input` | **PASS** |
| 16 | **Tidur** | Validasi: Durasi tidur > 24 jam ditolak | Ditolak dengan redirect `pesan=invalid_input` | **PASS** |
| 17 | **Tidur** | Validasi: Dropdown gawai/kesegaran ilegal ditolak | Ditolak dengan redirect `pesan=invalid_input` | **PASS** |
| 18 | **Tidur** | Naming & License Integrity | Instrumen dinamai Sleep Habit Assessment (bukan PSQI palsu) | **PASS** |
| 19 | **Zona HR** | Formula Tanaka Max HR usia 25 | Terhitung tepat 191 BPM | **PASS** |
| 20 | **Zona HR** | Karvonen HRR (191 - 68) | Terhitung tepat 123 BPM | **PASS** |
| 21 | **Zona HR** | Verifikasi 5 zona Karvonen tidak tumpang tindih | Zona 1–5 konsisten, Zona 2 (Fat Burn: 142–154 BPM) | **PASS** |
| 22 | **Zona HR** | Verifikasi 5 zona metode Zoladz | Z1 (136–146 BPM) hingga Z5 (Max 191 BPM) presisi | **PASS** |
| 23 | **Zona HR** | Validasi: Umur > 100 tahun ditolak | Ditolak dengan redirect `pesan=invalid_input` | **PASS** |
| 24 | **Zona HR** | Validasi: RHR < 40 BPM ditolak | Ditolak dengan redirect `pesan=invalid_input` | **PASS** |
| 25 | **Zona HR** | Validasi: Input kosong ditolak | Ditolak dengan redirect `pesan=invalid_input` | **PASS** |
| 26 | **Zona HR** | Peringatan kontraindikasi obat beta-blocker | Peringatan medis tertera jelas pada antarmuka | **PASS** |
| 27 | **IPAQ** | Tingkat aktivitas Tinggi (Vigorous >= 3 hr, Total >= 1500) | Terklasifikasi Tinggi (1.935 MET-menit/mgg) | **PASS** |
| 28 | **IPAQ** | Tingkat aktivitas Sedang (Walking 5 hr >= 30m) | Terklasifikasi Sedang (660 MET-menit/mgg) | **PASS** |
| 29 | **IPAQ** | Tingkat aktivitas Rendah / Sedentari (< 600 MET) | Terklasifikasi Rendah (99 MET-menit/mgg) | **PASS** |
| 30 | **IPAQ** | Data cleaning: Durasi < 10 menit diabaikan | Bouts 8 menit di-recode menjadi 0 MET | **PASS** |
| 31 | **IPAQ** | Truncation rule: Durasi > 180 mnt dipotong ke 180 mnt | Input 300 menit dipotong ke batas 180 menit | **PASS** |
| 32 | **IPAQ** | Peringatan risiko duduk >= 8 jam/hari | Muncul peringatan risiko sedentari pada hasil | **PASS** |
| 33 | **IPAQ** | Validasi: Hari aktivitas > 7 hari ditolak | Ditolak dengan redirect `pesan=invalid_input` | **PASS** |
| 34 | **IPAQ** | Validasi: Nama kosong ditolak | Ditolak dengan redirect `pesan=invalid_input` | **PASS** |
| 35 | **PSS-10** | Uji pembalikan item positif (q=0 semua -> skor=16) | Reverse scoring terverifikasi (direct=0, reverse=16) | **PASS** |
| 36 | **PSS-10** | Klasifikasi stres rendah (Total <= 13) | Skor 6/40 terklasifikasi Stres Rendah | **PASS** |
| 37 | **PSS-10** | Klasifikasi stres sedang (Total 14–26) | Skor 20/40 terklasifikasi Stres Sedang | **PASS** |
| 38 | **PSS-10** | Klasifikasi stres tinggi (Total >= 27) | Skor 40/40 terklasifikasi Stres Tinggi (warna: Danger) | **PASS** |
| 39 | **PSS-10** | Validasi: Nilai jawaban > 4 ditolak | Ditolak dengan redirect `pesan=invalid_input` | **PASS** |
| 40 | **PSS-10** | Validasi: Form tidak lengkap ditolak | Ditolak dengan redirect `pesan=invalid_input` | **PASS** |
| 41 | **PSS-10** | Ketersediaan hotline bantuan krisis Kemenkes 119 | Hotline 119 (ext. 8) tersedia jelas pada halaman | **PASS** |
| 42 | **Security** | Smart Storage Guest: 5 alat baru tanpa INSERT DB | DB count tetap 0 baris pada pengetesan guest | **PASS** |
| 43 | **Security** | Smart Storage Member: DB insert dengan user_id | Kelima alat tersimpan di DB terikat `user_id = 2` | **PASS** |
| 44 | **Security** | Snapshot parameter klinis & luaran tersimpan utuh | Seluruh input biometrik dan skor tersimpan di DB | **PASS** |
| 45 | **Security** | IDOR: User A dilarang mengakses data User B (5 alat) | Semua percobaan akses silang dibalas HTTP 403 | **PASS** |
| 46 | **Security** | Otorisasi Admin: Akses data lintas pengguna diizinkan | Administrator berhasil membuka rekam data (HTTP 200) | **PASS** |
| 47 | **Security** | Manipulasi user_id via POST diabaikan | Tetap mengikat ke `user_id = 2` dari sesi server | **PASS** |
| 48 | **Security** | Proteksi CSRF: POST tanpa token ditolak | Permintaan tanpa token dibalas HTTP 403 Forbidden | **PASS** |
| 49 | **Security** | Proteksi Metode: GET delete ditolak | Akses GET ke file hapus dibalas HTTP 405 | **PASS** |
| 50 | **Security** | Data legacy (user_id = NULL) diblokir untuk member | Member diblokir 403, Administrator diizinkan | **PASS** |
| 51 | **Regresi** | 7 Kalkulator sebelumnya merespons HTTP 200 OK | BMI, BMR, WHtR, Lemak, Cairan, Jantung, PHQ-4 OK | **PASS** |
| 52 | **Regresi** | Kelima alat baru merespons HTTP 200 OK | Tensi, Tidur, Zona HR, IPAQ, Stres OK | **PASS** |
| 53 | **Regresi** | Formula klinis lama tidak berubah | Tanaka 191 BPM, BMI Asia 22.9, BMR 1674 kkal konsisten | **PASS** |
| 54 | **Regresi** | Idempotensi Migration Runner Phase 2C | Eksekusi ulang menghasilkan *"Nothing to migrate"* | **PASS** |
| 55 | **Regresi** | Linting seluruh berkas PHP repositori (`php -l`) | Seluruh 80 file PHP repositori lulus tanpa error | **PASS** |

### Ringkasan Pengujian Kumulatif Repositori:
* **Phase 1A Suite:** 12 / 12 PASS
* **Phase 1B Suite:** 14 / 14 PASS
* **Phase 2A Suite:** 30 / 30 PASS
* **Phase 2B Suite:** 36 / 36 PASS
* **Phase 2C Suite:** 55 / 55 PASS
* **Grand Total Pengujian Otomatis:** **147 / 147 PASS (100% SUCCESS)**

---

## 9. Hasil Verifikasi Sintaks PHP (Linting)

Seluruh 80 berkas PHP aktif di repositori (di luar arsip cadangan `backups/`) diverifikasi dengan linter CLI bawaan PHP:
```bash
for f in $(find . -name "*.php" -not -path "./backups/*"); do php -l "$f" || exit 1; done
```
**Hasil:** `No syntax errors detected` pada seluruh 80 berkas.

---

## 10. Hasil Visual Regression Check

Dilakukan perbandingan visual halaman baru terhadap halaman representatif terbaru:
- **Konsistensi Kartu & Spacing:** Seluruh modul baru menggunakan radius kontur 20px (`--radius-lg`), fotografi medis dengan sudut terintegrasi (tanpa floating corner badge), dan chip tipografi kategori di dalam konten.
- **Hierarki Tombol:** Menggunakan tombol solid berkarakter (`.btn-coral` untuk tensi/jantung, `.btn-teal` untuk tidur/cairan, `.btn-blue` untuk zona kardio, `.btn-emerald` untuk aktivitas fisik, `.btn-lavender` untuk stres) dengan kontras tinggi teks putih.
- **Halaman Lama Tidak Berubah:** Homepage (`index.php`), formulir dan hasil BMI, BMR, WHtR, Lemak Tubuh, dan Cairan diverifikasi tetap mempertahankan visual terbaru secara presisi.

---

## 11. Kendala dan Solusi Teknis

1. **Kendala Lisensi Kuesioner PSQI:**
   - *Masalah:* PSQI dilindungi hak cipta institusional dan dilarang direproduksi secara bebas di aplikasi publik tanpa kontrak lisensi resmi.
   - *Solusi:* Mematuhi arahan etika tugas dengan menamai instrumen sebagai **Sleep Habit Assessment**, menyajikan kuesioner kebiasaan tidur non-diagnostik, dan mendokumentasikan keterbatasan secara eksplisit pada antarmuka.
2. **Kondisi Khusus Krisis Hipertensi:**
   - *Masalah:* Tekanan darah di atas 180/120 mmHg berisiko komplikasi stroke atau infark miokard akut bila diabaikan.
   - *Solusi:* Ditambahkan logika deteksi gawat darurat (`is_emergency = true`) yang secara otomatis menampilkan kotak peringatan bahaya (*danger alert*) di bagian paling atas halaman hasil dengan instruksi pencarian pertolongan medis segera.
3. **Pembersihan Data IPAQ (Data Cleaning Protocols):**
   - *Masalah:* Pengguna sering mengisi durasi aktivitas melebihi batas fisiologis normal (cth. 10 jam lari).
   - *Solusi:* Menerapkan protokol resmi komite IPAQ: aktivitas $<10$ menit diabaikan, dan durasi $>180$ menit dipotong (*truncated*) ke 180 menit.

---

## 12. Rekomendasi untuk Phase 3 (Personal Health Dashboard & Reporting)

1. **Pembangunan Personal Health Dashboard (`user/dashboard.php`):**
   - Menghubungkan seluruh 12 instrumen yang telah aktif ke dalam ringkasan metrik biometrik pengguna terdaftar.
   - Menghitung skor kesehatan komposit holistik (*Health Score Index* 0–100).
   - Menampilkan grafik tren tekanan darah, denyut nadi, berat badan, dan aktivitas fisik.
2. **Riwayat Pemeriksaan Terpadu (`user/history.php`):**
   - Mengintegrasikan kueri view `v_all_health_assessments` dengan filter kategori dan penyortiran waktu.
3. **Pusat Edukasi Kesehatan & Ekspor Laporan PDF:**
   - Mengaitkan artikel medis dengan masing-masing kalkulator terkait.
   - Menyediakan fitur unduh laporan kesehatan format PDF untuk persiapan konsultasi dokter.
