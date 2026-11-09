# Laporan Teknis Implementasi Phase 5: Health Goals & Actionable Milestones

Tanggal: 9 November 2026  
Status: Selesai & Terverifikasi (65/65 Pengujian Otomatis Lulus 100%)  
Platform: Native PHP 8.x, SQLite 3, Bootstrap 5.3.3, Chart.js, Vanilla JavaScript  

---

## 1. Executive Summary

Phase 5 merealisasikan sistem manajemen target kesehatan personal terpadu (**Personal Health Goal Management System**) pada platform **HealthCheck**. Sistem ini mentransformasi platform dari pemantauan pasif menjadi sistem intervensi mandiri yang membantu pengguna menetapkan sasaran hidup sehat realistis, membaginya ke dalam tahapan terukur (*actionable milestones*), mencatat riwayat perkembangan, serta memantau progresnya secara visual melalui dashboard personal.

Pencapaian utama Phase 5:
1. **Health Goals Hub (`user/goals.php`):** Antarmuka pengelolaan target kesehatan terstruktur mencakup 5 kategori (Gizi & Komposisi Tubuh, Aktivitas Fisik, Pola Tidur, Hidrasi Cairan, dan Target Personal Mandiri) dengan dukungan status transisi (*active*, *paused*, *completed*, *archived*), penandaan *Featured Goal*, serta filter dan pencarian kata kunci.
2. **Formula Progres Terarah (*Directional Progress Calculation*):** Perhitungan progres matematis yang akurat membedakan target peningkatan (*increase*) seperti langkah kaki/air minum dari target penurunan (*decrease*) seperti berat badan/lingkar pinggang. Mencegah persentase negatif saat terjadi perubahan berlawanan arah dan menangani kasus target terlampaui serta pembagian nol (*division by zero*).
3. **Actionable Milestones Management (`goal_milestones`):** Pembagian target utama ke dalam tahapan kecil bertahap dengan status checklist (*pending*, *in_progress*, *completed*, *skipped*), timestamp penyelesaian, urutan tahapan, serta penyedia rekomendasi template milestone 3–4 fase berbasis pedoman kebiasaan sehat.
4. **Integrasi Data Otomatis (*Auto-sync*):** Penarikan data riil terbaru dari tes kalkulator (berat badan `cek_bmi`, lingkar pinggang `cek_whtr`) dan jurnal harian (`daily_checkins` langkah, tidur, air) ke dalam log progres target secara idempoten tanpa duplikasi.
5. **Goal Detail Page (`user/goal_detail.php`):** Halaman komprehensif yang menampilkan 3 blok nilai (Awal, Terkini, Sasaran), visualisasi progress bar, daftar checklist milestone interaktif, kurva grafik Chart.js dari data aktual, serta tabel riwayat log perubahan progres beserta sumber datanya.
6. **Dashboard Integration (`user/dashboard.php`):** Kartu *Featured Goal* terintegrasi dengan progress bar dan penunjuk tahapan milestone berikutnya, ringkasan status target (Aktif, ≥75%, Selesai), dan feed aktivitas target terkini (*goal activity stream*).
7. **Keamanan & Otorisasi Ketat:** Validasi kepemilikan mutlak (*ownership verification*) pada seluruh endpoint detail, edit, delete, status toggle, progress, dan milestone; pencegahan IDOR (HTTP 403/404); CSRF token enforcement pada semua request POST; dan whitelist entitas `assert_record_access()`.

Seluruh 65 skenario pengujian baru pada `tests/tests_phase5_runner.php` dan 277 skenario uji kumulatif sebelumnya (total 342 skenario pengujian repositori) lulus 100% tanpa regresi.

---

## 2. Audit Kondisi Repository Sebelum Implementasi

Sebelum pengerjaan Phase 5 dimulai:
- **Basis Data:** Telah aktif 17 tabel dari Phase 1A sampai Phase 4, mencakup profil pengguna, 12 modul alat biometrik, tabel edukasi, dan tabel rutinitas harian (`health_habits`, `habit_logs`, `daily_checkins`, `health_reminders`, `notification_logs`).
- **Pola UI:** Design system Plus Jakarta Sans dengan palet warna editorial medis (Navy `#19365B`, Blue `#347FC2`, Teal `#168C89`, Emerald `#27845E`, Coral `#D75F50`, Lavender `#7866A6`), radius kartu 20px, tombol 11–16px, dan bayangan elevasi rendah.
- **Kebutuhan Pengguna:** Pengguna telah dapat mencatat evaluasi biometrik dan jurnal harian, namun belum memiliki wadah untuk menetapkan target jangka menengah/panjang (misal: "Turunkan 7 kg dalam 60 hari" atau "Tingkatkan jalan kaki dari 4.000 ke 10.000 langkah") beserta pemantauan tahapannya.

---

## 3. Fitur yang Berhasil Dibangun

### A. Health Goals Management (`user/goals.php`)
- **Pusat Pengelolaan Target:**
  - Pembuatan target baru melalui modal terintegrasi dengan autofill satuan dan arah progres.
  - Filter status: Semua, Sedang Berjalan (Active), Selesai (Completed), Dijeda (Paused), Diarsipkan (Archived).
  - Filter kategori: Gizi & Tubuh, Aktivitas Fisik, Pola Tidur, Hidrasi, Target Personal.
  - Pencarian kata kunci pada judul dan deskripsi target.
  - Ringkasan statistik (Target Berjalan, Hampir Selesai ≥75%, Target Selesai, Total Target).
  - Aksi instan via dropdown: Ubah Status (Jeda/Lanjutkan/Selesaikan/Arsipkan), Sematkan Featured Goal, Hapus Target.
  - Tombol sinkronisasi progres otomatis dari data tes klinis dan check-in.

### B. Actionable Milestones
- Pengguna dapat menyusun tahapan bertahap untuk setiap target.
- Rekomendasi template tahapan 3–4 fase:
  - *Weight Loss Template:* Fase 1 (Adaptasi nutrisi & defisit awal), Fase 2 (Defisit konsisten 50%), Fase 3 (Penguatan kebugaran 75%), Fase 4 (Target akhir & pemeliharaan).
  - *Steps Template:* Tahap 1 (5.000 langkah), Tahap 2 (7.500 langkah), Tahap 3 (10.000 langkah).
  - *Sleep Template:* Tahap 1 (Jadwal tidur konsisten), Tahap 2 (Kurangi gawai malam), Tahap 3 (Optimal 8 jam).
  - *Water Intake Template:* Tahap 1 (Air saat bangun pagi), Tahap 2 (Botol minum teratur), Tahap 3 (Target penuh).
- Checklist interaktif: pengguna dapat menandai milestone selesai (*completed*) secara mandiri dengan perekaman waktu `completed_at`.

### C. Goal Detail Page (`user/goal_detail.php`)
- Informasi lengkap: Kategori, judul, deskripsi, nilai awal, nilai terkini, nilai target, satuan, arah capaian, tanggal mulai, deadline selesai.
- Indikator persentase progres dan sisa jarak capaian (*delta remaining*).
- Form pencatatan progres mandiri dengan catatan dan tanggal pengukuran.
- Form penambahan milestone baru dengan urutan tahapan (*sequence order*).
- Grafik kurva perkembangan Chart.js dari data riil.
- Tabel kronologis log riwayat progres beserta sumber pencatatan (*manual*, *calculator*, *checkin*).

### D. Dashboard Integration (`user/dashboard.php`)
- **Kartu Featured Goal:** Menampilkan satu target utama yang disematkan dengan progress bar, nilai awal-terkini-sasaran, dan penunjuk tahapan milestone berikutnya.
- **Empty State Informatif:** Bila pengguna belum memiliki target, disajikan ajakan informatif untuk menetapkan target pertama tanpa tampilan kosong yang membingungkan.
- **Overview & Activity Feed:** Menampilkan rekapitulasi jumlah target dan 3 aktivitas terbaru dari tabel `goal_activity_logs`.
- **Quick Action Button:** Tombol "Target Kesehatan" di bilah pintasan utama dashboard.

---

## 4. Arsitektur dan Alur Sistem

```
[Pengguna] 
    │
    ├── Menetapkan Target (health_goals) ──> Auto-seed Milestones (goal_milestones)
    │                                    ──> Initial Progress Log (goal_progress_logs)
    │                                    ──> Activity Log (goal_activity_logs)
    │
    ├── Input Manual Progres ──> Hitung Arah & Persentase (calculate_goal_progress)
    │                        ──> Update current_value pada target
    │                        ──> Auto-complete jika mencapai 100%
    │
    ├── Tes Kalkulator / Daily Check-in ──> Trigger sync_external_data_to_goals()
    │                                   ──> Log progres otomatis (source: calculator/checkin)
    │
    └── Personal Dashboard & Detail ──> Visualisasi Kurva Progres (Chart.js)
                                     ──> Checklist Actionable Milestones
```

---

## 5. Database Schema dan Migration

Migrasi Phase 5 (`database/migrations/20261108_004_phase5_health_goals_milestones.php`) mengaplikasikan 4 tabel:

```sql
-- 1. Tabel health_goals
CREATE TABLE IF NOT EXISTS health_goals (
    id_goal INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    category VARCHAR(50) NOT NULL,
    goal_type VARCHAR(50) NOT NULL,
    initial_value REAL NOT NULL,
    target_value REAL NOT NULL,
    current_value REAL NOT NULL,
    unit VARCHAR(50) NOT NULL,
    direction VARCHAR(20) DEFAULT 'increase',
    start_date DATE NOT NULL,
    target_date DATE NOT NULL,
    status VARCHAR(20) DEFAULT 'active',
    is_featured INTEGER DEFAULT 0,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_goals_user_status ON health_goals(user_id, status);

-- 2. Tabel goal_progress_logs
CREATE TABLE IF NOT EXISTS goal_progress_logs (
    id_progress INTEGER PRIMARY KEY AUTOINCREMENT,
    goal_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    current_value REAL NOT NULL,
    progress_percentage REAL NOT NULL,
    source_type VARCHAR(50) DEFAULT 'manual',
    source_record_id VARCHAR(100) DEFAULT NULL,
    notes TEXT,
    recorded_at DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (goal_id) REFERENCES health_goals(id_goal) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_goal_prog_goal ON goal_progress_logs(goal_id, recorded_at);
CREATE INDEX IF NOT EXISTS idx_goal_prog_user ON goal_progress_logs(user_id, recorded_at);

-- 3. Tabel goal_milestones
CREATE TABLE IF NOT EXISTS goal_milestones (
    id_milestone INTEGER PRIMARY KEY AUTOINCREMENT,
    goal_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    target_value REAL DEFAULT NULL,
    unit VARCHAR(50) DEFAULT NULL,
    sequence_order INTEGER DEFAULT 1,
    target_date DATE DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    completed_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (goal_id) REFERENCES health_goals(id_goal) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_milestones_goal ON goal_milestones(goal_id, sequence_order);

-- 4. Tabel goal_activity_logs
CREATE TABLE IF NOT EXISTS goal_activity_logs (
    id_activity INTEGER PRIMARY KEY AUTOINCREMENT,
    goal_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (goal_id) REFERENCES health_goals(id_goal) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_goal_act_user ON goal_activity_logs(user_id, created_at);
```

---

## 6. Integrasi dengan Modul Lama

Sistem target terintegrasi secara harmonis dengan modul-modul sebelumnya:
1. **Modul BMI (`cek_bmi`):** Saat pengguna melakukan penimbangan dan skrining BMI, fungsi `sync_external_data_to_goals()` otomatis mencatat berat badan terbaru ke target penurunan/penambahan berat badan dengan `source_type = 'calculator'` dan `source_record_id = 'bmi_ID'`.
2. **Modul WHtR (`cek_whtr`):** Hasil pengukuran lingkar pinggang otomatis menyinkronkan target lingkar pinggang dengan `source_record_id = 'whtr_ID'`.
3. **Daily Check-in (`daily_checkins`):** Rekaman harian jumlah langkah, jam tidur, dan volume air minum otomatis menyinkronkan target langkah, tidur, dan hidrasi.
4. **Idempotensi Sinkronisasi:** Kolom `source_record_id` memastikan satu catatan tes/check-in yang sama tidak pernah dimasukkan berulang kali ke dalam riwayat log progres.

---

## 7. Keamanan dan Otorisasi

- **Session Authentication:** Akses tamu tanpa sesi login ke `user/goals.php` dan `user/goal_detail.php` langsung dialihkan via HTTP 302 ke `auth/login.php?pesan=belum_login`.
- **Pencegahan IDOR Mutlak:**
  - Fungsi `get_goal_by_id($pdo, $userId, $goalId)` secara tegas menyertakan `WHERE user_id = :session_user_id`. Pengguna B yang mencoba membuka URL `goal_detail.php?id=X` milik Pengguna A langsung ditolak dengan status HTTP 404/403.
  - Seluruh operasi mutasi milestone (`set_milestone_status`, `delete_goal_milestone`) dan progres (`record_goal_progress`) memvalidasi kepemilikan ganda pada tabel target dan tabel turunan.
- **CSRF Token Enforcement:** Semua form penambahan target, pengubahan status, penyematan featured goal, penghapusan, pencatatan progres, dan milestone mewajibkan token CSRF valid (`require_csrf_token()`).
- **Penolakan Metode GET Destruktif:** Aksi hapus dan ubah status hanya diterima melalui metode POST.

---

## 8. Penjelasan Perhitungan Progres

Fungsi `calculate_goal_progress()` menerapkan formula berorientasi arah (*directional progression*):

### A. Target Penurunan (*Decrease Direction*, misal: Berat Badan, Lingkar Pinggang)
$$\text{Span} = \text{Initial} - \text{Target}$$
$$\text{Achieved} = \text{Initial} - \text{Current}$$
$$\text{Progress \%} = \left(\frac{\text{Achieved}}{\text{Span}}\right) \times 100$$
- *Contoh:* Awal 75 kg, Target 68 kg, Terkini 71.5 kg.
  $$\text{Span} = 75 - 68 = 7$$
  $$\text{Achieved} = 75 - 71.5 = 3.5$$
  $$\text{Progress} = (3.5 / 7) \times 100 = 50.0\%$$
- *Perubahan Arah Berlawanan:* Jika berat badan justru naik menjadi 78 kg, persentase diclamp ke `0.0%` (bukan negatif) dan diberi flag `is_opposite = true` dengan status "Mengalami Kenaikan".

### B. Target Peningkatan (*Increase Direction*, misal: Langkah Kaki, Tidur, Air)
$$\text{Span} = \text{Target} - \text{Initial}$$
$$\text{Achieved} = \text{Current} - \text{Initial}$$
$$\text{Progress \%} = \left(\frac{\text{Achieved}}{\text{Span}}\right) \times 100$$
- *Contoh:* Awal 4.000 langkah, Target 10.000 langkah, Terkini 7.000 langkah.
  $$\text{Progress} = (3.000 / 6.000) \times 100 = 50.0\%$$

### C. Penanganan Kasus Ekstrem
1. **Target Tercapai / Terlampaui (*Overshoot*):** Bila nilai saat ini telah melewati target, persentase diclamp maksimal pada `100.0%` dengan status "Target Tercapai", sementara angka riil tetap ditampilkan secara transparan.
2. **Pencegahan Pembagian Nol (*Division by Zero*):** Jika nilai awal sama dengan sasaran ($\text{Span} = 0$), sistem mengembalikan `100.0%` jika syarat terpenuhi, atau `0.0%` tanpa memicu fatal error.

---

## 9. Dokumentasi Tampilan Halaman

1. **Halaman Health Goals Hub (`user/goals.php`):**
   - Header personal dengan tombol buat target baru dan sinkronisasi data.
   - 4 kartu ringkasan status target (Aktif, Hampir Selesai, Selesai, Total).
   - Bilah filter status, kategori, dan pencarian instan.
   - Kartu target dengan progress bar proporsional, info sisa capaian, badge kategori, dan menu dropdown aksi.
2. **Halaman Detail Target & Milestone (`user/goal_detail.php`):**
   - 3 blok metrik utama (Nilai Awal, Terkini, Sasaran).
   - Indikator sisa jarak capaian dan progress bar besar.
   - Kolom kiri: Daftar checklist Actionable Milestones interaktif (klik centang untuk menyelesaikan tahap).
   - Kolom kanan: Kurva grafik perkembangan data aktual (Chart.js) dan tabel kronologis riwayat log progres.
3. **Integrasi Dashboard (`user/dashboard.php`):**
   - Kartu *Featured Goal* di posisi teratas dashboard menyajikan sasaran utama beserta petunjuk milestone berikutnya.
   - Blok ringkasan status dan aliran aktivitas target terkini.

---

## 10. Daftar File Baru dan Dimodifikasi

### Berkas Baru:
1. `user/goals.php`: Halaman antarmuka manajemen target kesehatan, filter, dan modal pembuatan.
2. `user/goal_detail.php`: Halaman rincian target, kurva grafik progres, dan checklist milestone.
3. `includes/goal_helper.php`: Pustaka helper kalkulasi progres terarah, sinkronisasi data, milestone, dan aktivitas.
4. `database/migrations/20261108_004_phase5_health_goals_milestones.php`: Migrasi basis data 4 tabel Phase 5.
5. `tests/tests_phase5_runner.php`: Runner pengujian otomatis Phase 5 (65 skenario uji).
6. `docs/phase_5_health_goals_report.md`: Laporan komprehensif implementasi Phase 5.

### Berkas Dimodifikasi:
1. `includes/auth_helper.php`: Menambahkan 4 tabel Phase 5 ke whitelist entitas `assert_record_access()`.
2. `user/dashboard.php`: Integrasi kartu Featured Goal, ringkasan status target, dan feed aktivitas target.
3. `includes/header.php`: Menambahkan tautan "Target & Milestone" pada dropdown navigasi personal member.
4. `includes/footer.php`: Menambahkan tautan "Target & Milestone" pada navigasi akun di footer.

---

## 11. Matriks Hasil Pengujian

Pengujian dilakukan melalui automated test runner `tests/tests_phase5_runner.php` pada port 8100:

| Domain Pengujian | Jumlah Skenario | Hasil | Keterangan |
|---|---|---|---|
| A. Health Goals Management & CRUD | 15 | 15 PASS (100%) | Otorisasi tamu, akses member, pembuatan target penurunan & peningkatan, validasi tanggal < mulai, validasi input kosong, jeda target, aktivasi ulang, set featured, penyelesaian target, pengarsipan, hapus target, blokir IDOR, isolasi data |
| B. Directional Progress Formula | 9 | 9 PASS (100%) | Progres penurunan 50%, progres peningkatan 50%, target tercapai (100%), overshoot clamping 100%, arah berlawanan clamping 0% (never negative), zero division safety, label 'Hampir Tercapai', delta remaining |
| C. Actionable Milestones | 11 | 11 PASS (100%) | Tambah milestone manual, update info, status completed & timestamp, revert pending, hapus milestone, template BB 4 fase, template langkah 3 tahap, template tidur 3 tahap, blokir IDOR status & hapus, independensi kontrol |
| D. Progress Logging & Integration | 10 | 10 PASS (100%) | Log progres manual, akurasi persentase log, urutan kronologis ASC, auto-sync dari tes BMI, pencegahan duplikasi sync (idempoten), auto-sync dari Daily Check-in, auto-complete pada 100%, pencatatan activity log, pencatatan tipe sumber data, blokir IDOR progres |
| E. Goal Detail & Visualization | 9 | 9 PASS (100%) | Otorisasi detail tamu, detail member, tampilan 3 blok nilai (awal-terkini-target), rendering canvas Chart.js (≥2 log), empty state chart (<2 log), blokir IDOR detail (404/403), validasi bad ID, daftar milestone, tabel riwayat log |
| F. Dashboard & Security Integrity | 11 | 11 PASS (100%) | Featured goal di dashboard, ringkasan status target dashboard, empty state dashboard, navbar header link, footer link, CSRF enforcement progres, SQL injection resistance, whitelist assert_record_access, idempotensi migrasi runner, regresi modul lama, PHP lint 116 file |
| **Total Pengujian Phase 5** | **65** | **65 PASS (100%)** | **Seluruh skenario pengujian lulus tanpa kegagalan** |

### Kumulatif Test Suite Seluruh Repositori:
- Phase 1A (Security Hardening): 12/12 PASS
- Phase 1B (User Profile & Relations): 14/14 PASS
- Phase 2A (BMR Bugfix & Autofill): 30/30 PASS
- Phase 2B (Body & Nutrition Calculators): 36/36 PASS
- Phase 2C (Cardio, Sleep & Mental Screening): 55/55 PASS
- Phase 3 (Dashboard, History & PDF): 65/65 PASS
- Phase 4 (Habits, Check-in & Reminders): 65/65 PASS
- Phase 5 (Health Goals & Milestones): 65/65 PASS
- **Total Pengujian Keseluruhan Repositori: 342 / 342 PASS (100%)**

---

## 12. Hasil PHP Lint

Verifikasi sintaks dijalankan menggunakan `php -l` pada seluruh 116 berkas PHP di dalam repositori (di luar direktori `backups/`).  
**Hasil:** `Semua 116 file PHP lulus sintaks tanpa error (php -l)`.

---

## 13. Hasil Regression Test

- Seluruh 12 kalkulator klinis, pengunduhan laporan PDF, riwayat terpadu, pusat edukasi kesehatan, dan fitur pelacak kebiasaan Phase 4 tetap beroperasi tanpa hambatan.
- Tidak ada pergeseran gaya visual (*visual regression*) pada halaman lama.

---

## 14. Known Limitations

1. **Sinkronisasi Otomatis Satu Arah:** Sinkronisasi dari kalkulator/check-in bersifat *pull on-demand* (terpicu saat pengguna menekan tombol "Sinkronkan Progres" atau saat target dibuka), guna menghemat beban komputasi SQLite pada server.
2. **Kategori Medis Tanpa Diagnostik:** Selaras dengan prinsip etika medis HealthCheck, target kesehatan tidak memberikan saran dosis obat atau intervensi medis invasif. Target difokuskan murni pada modifikasi gaya hidup (nutrisi, hidrasi, gerak fisik, dan istirahat sirkadian).

---

## 15. Rekomendasi Pengembangan Berikutnya (Roadmap Phase 6)

1. **Sleep & Stress Correlation Insights:** Analitik korelasi silang berbasis grafik sebar (*scatter plot*) antara durasi/efisiensi tidur dengan tingkat stres PSS-10 dan detak nadi istirahat.
2. **Interactive Health Report Sharing:** Tautan publik terlindungi token kedaluwarsa pendek (*ephemeral token*) untuk membagikan laporan PDF atau progres target ke dokter pribadi secara aman.
3. **Multi-profile Family Account:** Mode pendampingan keluarga dengan isolasi data terpisah.
