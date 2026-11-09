# Laporan Teknis Implementasi Phase 4: Health Habits Tracker, Daily Check-in & Health Reminder

Tanggal: 9 November 2026  
Status: Selesai & Terverifikasi (65/65 Pengujian Otomatis Lulus 100%)  
Platform: Native PHP 8.x, SQLite 3, Bootstrap 5.3.3, Chart.js, Vanilla JavaScript  

---

## 1. Executive Summary

Phase 4 memperluas platform **HealthCheck** dari sistem pemeriksaan dan pelaporan biometrik statis menjadi platform pendampingan gaya hidup sehat aktif (*Active Health Habits & Daily Lifestyle Platform*). Melalui fase ini, pengguna didukung untuk membangun, mencatat, dan memantau rutinitas sehat sehari-hari secara terstruktur tanpa tekanan sistem *streak* yang menghukum.

Pencapaian utama Phase 4:
1. **Health Habits Tracker (`user/habits.php`):** Manajemen dan pelacakan progres harian untuk 4 kebiasaan fondasi kesehatan (Konsumsi Air, Durasi Tidur, Langkah Kaki, dan Aktivitas Fisik/Olahraga) serta kemampuan menambah kebiasaan personal mandiri (*Personal Custom Habit*).
2. **Daily Health Check-in (`user/checkin.php`):** Halaman jurnal harian terpadu yang memadukan pencatatan air, tidur semalam, jumlah langkah, durasi olahraga, tingkat energi/kondisi fisik, dan refleksi harian dalam satu langkah praktis dengan sinkronisasi otomatis ke log kebiasaan harian.
3. **Weekly Habit Summary & Visualization:** Panel analitik mingguan yang menghitung rata-rata tidur, rata-rata langkah kaki, total hidrasi cairan, total menit aktivitas fisik, jumlah hari check-in, dan frekuensi target tercapai berdasarkan data riil basis data (*zero dummy data*), dilengkapi 4 grafik batang & garis Chart.js dengan pemisahan sumbu/satuan yang valid.
4. **Health Reminder & In-App Notification Center (`user/reminders.php`):** Pengaturan jadwal pengingat kebiasaan (air, tidur, check-in, olahraga, kustom) dengan frekuensi hari aktif, kotak notifikasi *in-app* idempoten, pengujian izin browser (*Web Notification API*), serta skrip background worker terjadwal (`scripts/reminder_worker.php`) untuk eksekusi otomatis via cron job.
5. **Dashboard Integration (`user/dashboard.php`):** Tampilan status check-in hari ini, kartu progres *Today's Habits*, widget pengingat aktif terdekat (*Upcoming Reminders*), dan notifikasi belum dibaca langsung di personal dashboard.
6. **Keamanan & Otorisasi Ketat:** Penegakan proteksi CSRF pada seluruh request POST, pencegahan IDOR (HTTP 403) pada habit, log, check-in, dan reminder, penolakan aksi destruktif via GET (HTTP 405), dan isolasi data total antar-pengguna.

Seluruh 65 skenario pengujian baru pada `tests/tests_phase4_runner.php` dan 212 skenario uji terdahulu (total 277 skenario pengujian repositori) lulus 100% tanpa regresi.

---

## 2. Audit UI dan Repository

Sebelum implementasi Phase 4:
- **Design System:** Telah mapan dengan Plus Jakarta Sans, Medical Navy (`#19365B`), Strong Blue (`#347FC2`), Rich Teal (`#168C89`), Emerald (`#27845E`), Coral (`#D75F50`), dan Warm Amber (`#D99A27`). Radius kartu 20px, radius tombol 11–16px dengan soft-surface dan border halus.
- **Kondisi Fitur Pengguna:** Phase 3 telah menyediakan `dashboard.php`, `history.php`, `history_detail.php`, `report_download.php`, dan katalog edukasi `education/`. Namun belum tersedia sarana pencatatan rutinitas harian (*daily habit tracking*) yang mendorong retensi pengguna.
- **Keputusan Desain:** Menolak sistem *gamified punitive streak* (misal: mereset skor ke 0 jika lupa 1 hari) yang justru memicu kecemasan dan kelelahan mental (*streak burnout*). Sistem menggunakan pendekatan *positive progress* di mana capaian diukur berdasarkan rasio pemenuhan target realistis.

---

## 3. Arsitektur Habit Tracker (`user/habits.php`)

### A. 4 Kebiasaan Fondasi Kesehatan (System Defaults)
Saat pengguna membuka Habit Tracker untuk pertama kalinya, sistem menginisialisasi secara otomatis:
1. **Konsumsi Air Harian (`water`):** Target default 2.000 ml (dapat disesuaikan), progres visual bar, satuan ml.
2. **Durasi Tidur Malam (`sleep`):** Target default 8.0 jam (rekomendasi sirkadian 7–9 jam), satuan jam.
3. **Target Langkah Kaki (`steps`):** Target default 8.000 langkah, satuan langkah.
4. **Aktivitas Fisik / Olahraga (`activity`):** Target default 30 menit (pedoman WHO 150 menit/minggu), satuan menit.

### B. Personal Custom Habit
Pengguna dapat menambahkan kebiasaan personal melalui modal dialog terintegrasi dengan menentukan:
- Nama kebiasaan (misal: "Meditasi Pagi", "Membaca Jurnal", "Peregangan Punggung").
- Deskripsi tujuan.
- Satuan pengukuran bebas (menit, porsi, halaman, kali).
- Target numerik harian.
- Pilihan ikon Bootstrap Icons dan aksen warna scoped.

### C. Mekanisme Log & Proteksi Integritas
- **Log Harian Idempoten:** Tabel `habit_logs` menggunakan batasan `UNIQUE(habit_id, tanggal)` dengan mekanisme `INSERT ... ON CONFLICT DO UPDATE`. Pembaruan catatan pada hari yang sama tidak menduplikasi baris data.
- **Perlindungan Kebiasaan Sistem:** Kebiasaan bawaan sistem tidak dapat dihapus (tombol hapus dinonaktifkan di backend), hanya dapat dinonaktifkan (`is_active = 0`) agar konsistensi relasi check-in tetap terjaga.
- **Navigasi Tanggal Bebas:** Pengguna dapat berpindah tanggal untuk melihat log masa lalu atau mengisi catatan hari sebelumnya (*historical tracking*).

---

## 4. Implementasi Daily Health Check-in (`user/checkin.php`)

Daily Check-in dirancang sebagai pintu gerbang ringkas pengisian jurnal kesehatan harian pengguna:
- **Field yang Dicatat:**
  - `water_ml` (ml) — batas validasi 0 s/d 10.000 ml.
  - `sleep_hours` (jam) — batas validasi 0 s/d 24 jam.
  - `steps_count` (langkah) — batas validasi 0 s/d 100.000 langkah.
  - `activity_type` (dropdown aktivitas: Jalan santai, Lari/Jogging, Sepeda, Renang, Gym, Yoga, Permainan) & `activity_minutes` (menit).
  - `kondisi_energi` (opsional: Sangat Bugar, Berenergi Baik, Biasa/Cukup, Lelah/Linu).
  - `catatan` (opsional: refleksi bebas).
- **Penanganan Nilai Null:** Field opsional yang tidak diisi pengguna disimpan sebagai `NULL` dan tidak dipaksa menjadi angka 0, menjaga akurasi statistik rata-rata mingguan.
- **Sinkronisasi Otomatis:** Saat check-in harian disimpan, fungsi `save_daily_checkin()` secara otomatis menyinkronkan data air, tidur, langkah, dan menit olahraga ke tabel `habit_logs` untuk tanggal terkait.

---

## 5. Implementasi Weekly Habit Summary

Panel ringkasan mingguan (`get_weekly_habit_summary()`) menghitung agregasi 7 hari (Senin s/d Minggu) dengan aturan ketat:
1. **Zero Dummy Data:** Hanya mengekstraksi data riil dari tabel `daily_checkins` dan `habit_logs`. Jika titik data kurang dari 2 hari, sistem menampilkan pesan *empty state* yang ramah pengguna.
2. **Pemisahan Grafik berdasarkan Satuan:**
   - Grafik 1: Bar chart Konsumsi Air Harian (satuan: ml).
   - Grafik 2: Line chart Durasi Tidur (satuan: jam).
   - Grafik 3: Bar chart Jumlah Langkah Kaki (satuan: langkah).
   - Grafik 4: Bar chart Durasi Aktivitas Fisik (satuan: menit).
3. **Statistik Kumulatif:**
   - Total air mingguan (Liter & ml).
   - Rata-rata durasi tidur per malam (jam).
   - Rata-rata langkah harian (langkah/hari).
   - Total durasi aktivitas fisik per minggu (menit).
   - Frekuensi check-in (X dari 7 hari).
   - Total target tercapai (*targets achieved count*).
4. **Pencegahan Skor Gabungan:** Tidak ada angka gabungan fiktif (*No Habit Score Index*). Setiap aspek gaya hidup dievaluasi secara independen sesuai karakteristik fisiologisnya.

---

## 6. Implementasi Health Reminders & Notifications

### A. Pengaturan Pengingat (`user/reminders.php`)
Pengguna dapat mengatur pengingat harian untuk 4 tipe default (Minum Air, Aktivitas Fisik, Daily Check-in, Persiapan Tidur) serta jadwal kustom:
- Waktu pengingat format 24 jam (`HH:MM`).
- Pilihan hari aktif (Senin s/d Minggu).
- Sakelar toggle aktif / nonaktif.

### B. Arsitektur Pengiriman Notifikasi Bertingkat
Untuk menjaga transparansi dan kejujuran teknis, HealthCheck menerapkan 3 lapisan pengingat:
1. **In-App Notifications (Fondasi Utama):** Fungsi `generate_due_reminders()` mendeteksi jadwal yang jatuh tempo dan mencatatnya ke tabel `notification_logs` dengan status `unread`. Notifikasi muncul di banner Dashboard dan halaman Pengingat.
   - **Idempotensi Pengiriman:** Batasan `UNIQUE(user_id, reminder_id, tanggal_kirim)` menjamin satu pengingat hanya menerbitkan maksimal 1 notifikasi per hari, mencegah spamming berulang.
2. **Browser Notification API:** Pengguna dapat mengaktifkan izin notifikasi peramban (*Web Notifications*) melalui tombol "Uji Notifikasi Browser". Jika izin diberikan, notifikasi desktop native akan ditampilkan.
3. **Scheduled Background Worker (`scripts/reminder_worker.php`):** Skrip CLI mandiri yang dapat didaftarkan ke cron job server untuk mengevaluasi pengingat secara terjadwal di latar belakang tanpa interaksi pengguna.

---

## 7. Database Changes & Migration

Migrasi Phase 4 (`database/migrations/20261108_003_phase4_habits_checkin_reminders.php`) mengeksekusi skema berikut:

```sql
-- 1. Tabel health_habits
CREATE TABLE IF NOT EXISTS health_habits (
    id_habit INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    code VARCHAR(50) NOT NULL,
    nama_habit VARCHAR(150) NOT NULL,
    deskripsi VARCHAR(255),
    ikon VARCHAR(50) DEFAULT 'bi-check2-circle',
    warna VARCHAR(30) DEFAULT 'blue',
    satuan VARCHAR(50) NOT NULL,
    target_harian REAL NOT NULL,
    is_active INTEGER DEFAULT 1,
    is_custom INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_habits_user ON health_habits(user_id, is_active);

-- 2. Tabel habit_logs
CREATE TABLE IF NOT EXISTS habit_logs (
    id_log INTEGER PRIMARY KEY AUTOINCREMENT,
    habit_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    tanggal DATE NOT NULL,
    nilai_aktual REAL NOT NULL DEFAULT 0,
    is_target_tercapai INTEGER DEFAULT 0,
    catatan TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (habit_id) REFERENCES health_habits(id_habit) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    UNIQUE(habit_id, tanggal)
);
CREATE INDEX IF NOT EXISTS idx_habit_logs_date ON habit_logs(user_id, tanggal);

-- 3. Tabel daily_checkins
CREATE TABLE IF NOT EXISTS daily_checkins (
    id_checkin INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    tanggal DATE NOT NULL,
    water_ml REAL DEFAULT NULL,
    sleep_hours REAL DEFAULT NULL,
    steps_count INT DEFAULT NULL,
    activity_type VARCHAR(100) DEFAULT NULL,
    activity_minutes INT DEFAULT NULL,
    kondisi_energi VARCHAR(50) DEFAULT NULL,
    catatan TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    UNIQUE(user_id, tanggal)
);
CREATE INDEX IF NOT EXISTS idx_checkin_user_date ON daily_checkins(user_id, tanggal);

-- 4. Tabel health_reminders
CREATE TABLE IF NOT EXISTS health_reminders (
    id_reminder INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    tipe_reminder VARCHAR(50) NOT NULL,
    judul VARCHAR(150) NOT NULL,
    pesan TEXT NOT NULL,
    jam_reminder VARCHAR(10) NOT NULL,
    hari_aktif VARCHAR(50) DEFAULT '1,2,3,4,5,6,7',
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_reminders_user ON health_reminders(user_id, is_active);

-- 5. Tabel notification_logs
CREATE TABLE IF NOT EXISTS notification_logs (
    id_notif INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    reminder_id INTEGER DEFAULT NULL,
    tipe_notif VARCHAR(50) NOT NULL DEFAULT 'in_app',
    judul VARCHAR(150) NOT NULL,
    pesan TEXT NOT NULL,
    tanggal_kirim DATE NOT NULL,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'unread',
    read_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (reminder_id) REFERENCES health_reminders(id_reminder) ON DELETE SET NULL,
    UNIQUE(user_id, reminder_id, tanggal_kirim)
);
CREATE INDEX IF NOT EXISTS idx_notif_user_status ON notification_logs(user_id, status);
```

---

## 8. Security & Authorization

- **Autentikasi Sesi:** Tamu tanpa login diblokir dengan kode HTTP 302 menuju `auth/login.php?pesan=belum_login`.
- **Proteksi CSRF:** Seluruh mutasi data (`log_habit`, `create_habit`, `update_habit`, `delete_habit`, `save_daily_checkin`, `create_reminder`, `update_reminder`, `delete_reminder`, `mark_read`) mewajibkan token CSRF yang divalidasi via `require_csrf_token()`. Permintaan tanpa token dibalas HTTP 403 Forbidden.
- **Proteksi IDOR:** Setiap operasi edit/hapus pada `health_habits` dan `health_reminders` memverifikasi `WHERE user_id = :session_user_id`. Pengguna B dilarang keras mengubah atau menghapus data milik Pengguna A.
- **Whitelist `assert_record_access`:** Kelima tabel baru (`health_habits`, `habit_logs`, `daily_checkins`, `health_reminders`, `notification_logs`) ditambahkan ke whitelist di `includes/auth_helper.php`.
- **Penolakan GET Destruktif:** Aksi penghapusan hanya dapat dipicu via metode HTTP POST.

---

## 9. UI/UX dan Responsiveness

- Halaman `user/habits.php`, `user/checkin.php`, dan `user/reminders.php` mempertahankan 100% konsistensi visual HealthCheck (tanpa gaya template admin generik).
- Menggunakan komponen Bootstrap 5.3.3 standar dengan token warna dan bayangan halus dari `assets/css/style.css`.
- Progress bar menggunakan transisi CSS halus, modal dialog terpusat (*centered modal*), dan formulir yang nyaman digunakan pada layar ponsel (*mobile-friendly touch targets*).

---

## 10. Daftar File Baru dan Dimodifikasi

### Berkas Baru:
1. `user/habits.php`: Halaman antarmuka Health Habits Tracker, manajemen target, dan ringkasan mingguan.
2. `user/checkin.php`: Halaman Daily Health Check-in harian.
3. `user/reminders.php`: Halaman pengaturan pengingat dan kotak notifikasi in-app.
4. `includes/habit_helper.php`: Pustaka helper kebiasaan, kalkulasi progres, sinkronisasi check-in, dan notifikasi.
5. `scripts/reminder_worker.php`: Skrip CLI background worker untuk cron job pengingat.
6. `database/migrations/20261108_003_phase4_habits_checkin_reminders.php`: Migrasi basis data Phase 4.
7. `tests/tests_phase4_runner.php`: Runner pengujian otomatis Phase 4 (65 skenario uji).
8. `docs/phase_4_habits_reminder_report.md`: Laporan komprehensif implementasi Phase 4.

### Berkas Dimodifikasi:
1. `includes/auth_helper.php`: Menambahkan 5 tabel Phase 4 ke whitelist entitas `assert_record_access()`.
2. `includes/csrf.php`: Menambahkan alias fungsi `csrf_input()` untuk `csrf_field()`.
3. `user/dashboard.php`: Integrasi widget Today's Habits, status check-in, upcoming reminders, dan alert notifikasi.
4. `includes/header.php`: Menambahkan tautan Habits Tracker, Check-in, dan Pengingat ke dropdown personal.
5. `includes/footer.php`: Menambahkan navigasi akun personal untuk Habits Tracker dan Daily Check-in.

---

## 11. Matriks Pengujian

Pengujian dilakukan melalui automated test runner `tests/tests_phase4_runner.php` pada port 8099:

| Domain Pengujian | Jumlah Skenario | Hasil | Keterangan |
|---|---|---|---|
| A. Health Habits Tracker | 15 | 15 PASS (100%) | Inisialisasi 4 habit default, log aktual harian, idempoten upsert hari sama, kalkulasi target tercapai, progres non-punitive, CRUD habit personal, larangan hapus sistem, isolasi user B |
| B. Daily Health Check-in | 13 | 13 PASS (100%) | Form check-in, pengiriman lengkap, auto-sinkronisasi ke 4 habit, update hari sama, preservasi field NULL, clamping tidur 24 jam & nilai negatif, switcher tanggal, IDOR check-in, CSRF check-in, banner dashboard |
| C. Weekly Habit Summary | 10 | 10 PASS (100%) | Akurasi total air (9.0L), rata-rata tidur (7.8 jam), rata-rata langkah (8875), total olahraga (140 menit), hari check-in (4/7), target tercapai (13x), zero dummy data, 4 kanvas terpisah, empty state, navigasi minggu |
| D. Health Reminders & Notifikasi | 12 | 12 PASS (100%) | Inisialisasi 4 pengingat default, CRUD reminder, toggle aktif/mati, evaluasi waktu jatuh tempo, idempotensi notifikasi hari sama, mark read, eksekusi worker CLI, IDOR reminder, supresi pengingat nonaktif |
| E. Integrasi Dashboard & Navigasi | 6 | 6 PASS (100%) | Widget Today's Habits, widget Upcoming Reminders, navbar dropdown links, footer links, quick action buttons, alert notifikasi belum dibaca |
| F. Keamanan & Regresi Repositori | 9 | 9 PASS (100%) | CSRF protection habits & reminder, SQLi regex protection pada tanggal, regresi 12 kalkulator lama, regresi riwayat & PDF (%PDF), regresi education, idempotensi migrasi runner, whitelist auth_helper, PHP lint 111 file |
| **Total Pengujian Phase 4** | **65** | **65 PASS (100%)** | **Seluruh skenario pengujian lulus tanpa kegagalan** |

### Kumulatif Test Suite Seluruh Repositori:
- Phase 1A (Security Hardening): 12/12 PASS
- Phase 1B (User Profile & Relations): 14/14 PASS
- Phase 2A (BMR Bugfix & Autofill): 30/30 PASS
- Phase 2B (Body & Nutrition Calculators): 36/36 PASS
- Phase 2C (Cardio, Sleep & Mental Screening): 55/55 PASS
- Phase 3 (Dashboard, History & PDF): 65/65 PASS
- Phase 4 (Habits, Check-in & Reminders): 65/65 PASS
- **Total Pengujian Keseluruhan: 277 / 277 PASS (100%)**

---

## 12. Hasil PHP Lint

Verifikasi sintaks dijalankan menggunakan `php -l` pada seluruh 111 berkas PHP di dalam repositori (di luar direktori `backups/`).  
**Hasil:** `Semua 111 file PHP lulus sintaks tanpa error (php -l)`.

---

## 13. Hasil Visual Regression

- Halaman utama `index.php`, seluruh 12 kalkulator klinis, riwayat terpadu `user/history.php`, dan pusat edukasi `education/` tetap berfungsi utuh tanpa perubahan layout yang tidak diinginkan.
- Personal Dashboard menyajikan penambahan blok *Rutinitas Hari Ini* dan *Upcoming Reminders* secara proporsional.

---

## 14. Known Limitations

1. **Browser Notification User Permission:** Fitur notifikasi peramban memerlukan persetujuan eksplisit dari pengguna via browser dialog. Jika pengguna menolak, sistem secara otomatis beralih (*graceful fallback*) ke *In-App Notification Feed* yang tersimpan di database.
2. **Cron Job Server Dependency:** Pengiriman pengingat di latar belakang saat pengguna tidak sedang membuka tab peramban memerlukan konfigurasi cron job server hosting untuk memanggil `php scripts/reminder_worker.php` setiap menit atau 15 menit.

---

## 15. Deployment Notes

Untuk mengaktifkan evaluasi pengingat terjadwal di lingkungan server Linux/Unix, tambahkan entri cron berikut melalui `crontab -e`:

```bash
# Evaluasi pengingat kebiasaan HealthCheck setiap 15 menit
*/15 * * * * /usr/bin/php /var/www/html/scripts/reminder_worker.php > /dev/null 2>&1
```

Skrip worker ini dirancang aman: jika dijalankan berulang kali, batasan idempotensi `UNIQUE(user_id, reminder_id, tanggal_kirim)` akan mencegah pengiriman notifikasi ganda pada hari yang sama.

---

## 16. Rekomendasi Phase Berikutnya (Roadmap Phase 5)

1. **Health Goals & Actionable Milestones:** Penyusunan sasaran kesehatan bertahap (misal: target penurunan berat badan bertahap, target penurunan tekanan darah pra-hipertensi) dengan rekomendasi nutrisi dari data kalkulator.
2. **Sleep & Stress Correlation Insights:** Analitik korelasi silang antara kualitas kebiasaan tidur dengan skor stres PSS-10 dan detak jantung istirahat (RHR).
3. **Multi-profile Family Account:** Akun keluarga untuk mendampingi orang tua atau anak dengan isolasi profil yang aman.
