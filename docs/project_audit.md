# Audit Komprehensif Proyek HealthCheck

**Tanggal Audit:** November 2026  
**Peran Auditor:** Senior Software Architect, Product Analyst & Senior Full-Stack Developer  
**Status Proyek:** Monolitik PHP (Procedural/Hybrid) + SQLite  
**Tujuan Evaluasi:** Penilaian menyeluruh arsitektur, fungsionalitas, keamanan, dan kesiapan transisi menuju *Personal Health Assessment Platform*.

---

## 1. Executive Summary

Platform **HealthCheck** saat ini beroperasi sebagai aplikasi web berbasis native PHP dengan penyimpanan data SQLite (didukung fallback PDO & bridge legacy MySQL). Platform menyediakan 4 alat evaluasi kesehatan mandiri:
1. **Kalkulator Indeks Massa Tubuh (BMI)**
2. **Kalkulator Basal Metabolic Rate (BMR) & Estimasi Kalori Harian (TDEE)**
3. **Kalkulator Detak Jantung & Zona Kardio**
4. **Skrining Singkat Kesehatan Mental (Instrumen PHQ-4)**

Sistem dilengkapi antarmuka modern bernuansa *editorial healthcare* memanfaatkan Bootstrap 5.3.3 dan custom CSS (`assets/css/style.css`), modul autentikasi dasar untuk pengguna dan administrator, serta panel dasbor admin untuk reviu data rekam medis mandiri.

### Temuan Kunci (Key Findings):
* **Potensi Besar Platform:** Desain visual dan antarmuka sangat rapi, responsif, dan telah mengadopsi standar medis dasar yang diakui (WHO Asia-Pacific BMI, Mifflin-St Jeor BMR, Tanaka/Karvonen Heart Rate, Kroenke PHQ-4).
* **Kelemahan Kritis Arsitektur & Relasi Data:** Tidak ada relasi relasional (`foreign key`) antara akun pengguna (`user`) dengan entitas hasil pemeriksaan (`cek_bmi`, `cek_bmr`, `cek_jantung`, `cek_mental`). Data hanya mencatat string `nama_user` secara terbuka. Hal ini menjadi penghalang utama personal dashboard dan riwayat pemeriksaan terisolasi per akun.
* **Kerentanan Keamanan Serius:** Ditemukan kredensial *hardcoded backdoor* pada login administrator, penyimpanan kredensial awal berstatus teks polos (*plain text*), ketiadaan token proteksi *Cross-Site Request Forgery* (CSRF) pada seluruh aksi manipulasi data, dan berkas database SQLite yang rentan diakses langsung via peramban web tanpa proteksi direktori web server.

---

## 2. Struktur Repository & Analisis File

Struktur folder proyek menerapkan pola pengelompokan modul parsial dengan file *forwarding/wrapper* pada root:

```
HealthCheck-Website/
├── .git/                               # Direktori VCS Git
├── admin/                              # Modul Dashboard Administrator
│   └── admin.php                       # Antarmuka dasbor admin tabular & metrik
├── assets/
│   └── css/
│       └── style.css                   # Custom design system & token warna
├── auth/                               # Modul Autentikasi & Sesi
│   ├── login.php                       # Halaman login umum
│   ├── login_admin.php                 # Form login khusus administrator
│   ├── login_admin_proses.php          # Backend verifikasi admin
│   ├── login_cek.php                   # Middleware pengecekan sesi aktif
│   ├── login_user.php                  # Wrapper ke login.php
│   ├── login_user_proses.php           # Backend verifikasi user umum
│   ├── logout.php                      # Pembersih sesi & redirect
│   ├── register.php                    # Form & backend registrasi user baru
│   └── role.php                        # Helper otorisasi peran (is_admin, is_user)
├── bmi/                                # Modul Kalkulator Indeks Massa Tubuh
│   ├── bmi_admin.php                   # Redirect helper ke admin.php#tab-bmi
│   ├── bmi_edit.php                    # Form perbaikan data rekaman BMI
│   ├── bmi_form.php                    # Form kalkulator publik BMI
│   ├── bmi_hapus.php                   # Action penghapusan data BMI
│   ├── bmi_hasil.php                   # Visualisasi hasil evaluasi BMI
│   ├── bmi_proses.php                  # Logika hitung & penyimpanan BMI
│   └── bmi_update.php                  # Logika pembaruan data BMI
├── bmr/                                # Modul Kalkulator BMR & Kalori
│   ├── bmr_edit.php                    # Form perbaikan data rekaman BMR
│   ├── bmr_form.php                    # Form kalkulator publik BMR
│   ├── bmr_hapus.php                   # Action penghapusan data BMR
│   ├── bmr_hasil.php                   # Visualisasi hasil metabolisme BMR & TDEE
│   ├── bmr_proses.php                  # Logika hitung & penyimpanan BMR
│   └── bmr_update.php                  # Logika pembaruan data BMR
├── database/
│   └── healthcheck.sqlite              # Berkas database SQLite aktif
├── includes/                           # Komponen layout & infrastruktur inti
│   ├── db.php                          # Handler PDO koneksi & auto-schema init
│   ├── footer.php                      # Layout footer global & script Bootstrap
│   └── header.php                      # Layout header global, navbar & logic path
├── jantung/                            # Modul Detak Jantung & Zona Kardio
│   ├── jantung_form.php                # Form input denyut nadi istirahat & usia
│   ├── jantung_hasil.php               # Visualisasi zona aerobik & evaluasi RHR
│   └── jantung_proses.php              # Logika kalkulasi Tanaka & Karvonen
├── mental/                             # Modul Skrining Mental PHQ-4
│   ├── mental_form.php                 # Form kuesioner GAD-2 & PHQ-2
│   ├── mental_hasil.php                # Visualisasi tingkat distres psikologis
│   └── mental_proses.php               # Logika skoring instrumen PHQ-4
├── admin.php                           # [Root Wrapper] -> admin/admin.php
├── bmi_admin.php                       # [Root Wrapper] -> bmi/bmi_admin.php
├── bmi_edit.php                        # [Root Wrapper] -> bmi/bmi_edit.php
├── bmi_form.php                        # [Root Wrapper] -> bmi/bmi_form.php
├── bmi_hapus.php                       # [Root Wrapper] -> bmi/bmi_hapus.php
├── bmi_hasil.php                       # [Root Wrapper] -> bmi/bmi_hasil.php
├── bmi_proses.php                      # [Root Wrapper] -> bmi/bmi_proses.php
├── bmi_update.php                      # [Root Wrapper] -> bmi/bmi_update.php
├── bmr_edit.php                        # [Root Wrapper] -> bmr/bmr_edit.php
├── bmr_form.php                        # [Root Wrapper] -> bmr/bmr_form.php
├── bmr_hapus.php                       # [Root Wrapper] -> bmr/bmr_hapus.php
├── bmr_hasil.php                       # [Root Wrapper] -> bmr/bmr_hasil.php
├── bmr_proses.php                      # [Root Wrapper] -> bmr/bmr_proses.php
├── bmr_update.php                      # [Root Wrapper] -> bmr/bmr_update.php
├── index.php                           # Halaman utama (Hero, pencarian, tool cards)
├── jantung_form.php                    # [Root Wrapper] -> jantung/jantung_form.php
├── jantung_hasil.php                   # [Root Wrapper] -> jantung/jantung_hasil.php
├── jantung_proses.php                  # [Root Wrapper] -> jantung/jantung_proses.php
├── koneksi.php                         # Legacy SQLite-to-MySQLi Bridge
├── login.php                           # [Root Wrapper] -> auth/login.php
├── login_admin.php                     # [Root Wrapper] -> auth/login_admin.php
├── login_admin_proses.php              # [Root Wrapper] -> auth/login_admin_proses.php
├── login_cek.php                       # [Root Wrapper] -> auth/login_cek.php
├── login_user.php                      # [Root Wrapper] -> auth/login_user.php
├── login_user_proses.php               # [Root Wrapper] -> auth/login_user_proses.php
├── logout.php                          # [Root Wrapper] -> auth/logout.php
├── mental_form.php                     # [Root Wrapper] -> mental/mental_form.php
├── mental_hasil.php                    # [Root Wrapper] -> mental/mental_hasil.php
├── mental_proses.php                   # [Root Wrapper] -> mental/mental_proses.php
├── register.php                        # [Root Wrapper] -> auth/register.php
├── role.php                            # [Root Wrapper] -> auth/role.php
├── healthcheck_prd.md                  # PRD awal pengembangan
└── README.md                           # Berkas dokumentasi dasar
```

### Karakteristik & Observasi Berkas Root:
Terdapat 23 file PHP di tingkat *root* yang semata-mata memanggil `require_once __DIR__ . '/<folder>/<file>.php';`. Struktur ini dibuat untuk menjaga kompatibilitas tautan lama saat proyek direorganisasi ke dalam subfolder (`admin/`, `auth/`, `bmi/`, `bmr/`, `jantung/`, `mental/`). Seluruh navigasi di `includes/header.php` telah mengarah langsung ke berkas di dalam subdirektori.

### Dependensi & Library yang Digunakan:
* **Bootstrap 5.3.3 (CSS & JS Bundle)**: Diakses via CDN (jsdelivr). Digunakan untuk grid responsive, layout kartu, badge, tombol, dan modal.
* **Bootstrap Icons 1.11.3**: Diakses via CDN (jsdelivr). Digunakan untuk ikonografi kardiovaskular, gizi, mental, dan aksi tabel.
* **Google Fonts (Plus Jakarta Sans)**: Diimpor secara daring untuk tipografi visual profesional.
* **PHP PDO SQLite Driver**: Mesin koneksi basis data standar bawaan PHP.
* **SQLiteMysqliBridge (`koneksi.php`)**: Library adapter kustom internal untuk memetakan pemanggilan fungsi lawas `mysqli_*` ke objek PDO SQLite jika migrasi skrip parsial terjadi.

---

## 3. Audit Fitur yang Sudah Berjalan

### A. Kalkulator Indeks Massa Tubuh (BMI)
* **Formulir Input (`bmi/bmi_form.php`):**
  * Nama Lengkap (`nama_user`) — Teks wajib.
  * Jenis Kelamin (`jenis_kelamin`) — Pilihan L/P (disertakan pada formulir namun tidak diproses dalam kalkulasi maupun disimpan ke basis data).
  * Umur (`umur`) — Angka tahun (validasi klien: 5 - 120 tahun).
  * Berat Badan (`berat_badan`) — Angka desimal kilogram (validasi: 10 - 350 kg).
  * Tinggi Badan (`tinggi_badan`) — Angka desimal sentimeter (validasi: 50 - 250 cm).
* **Rumus Kalkulasi (`bmi/bmi_proses.php`):**
  $$\text{Tinggi (m)} = \frac{\text{tinggi\_badan}}{100}$$
  $$\text{BMI} = \frac{\text{berat\_badan}}{\text{Tinggi (m)}^2} \quad (\text{dibulatkan 1 desimal})$$
* **Standar Klasifikasi (WHO & Kemenkes RI untuk Kawasan Asia-Pasifik):**
  * $\text{BMI} < 18.5$: Kurus (Kekurangan Berat Badan)
  * $18.5 \le \text{BMI} \le 22.9$: Normal (Berat Badan Ideal)
  * $23.0 \le \text{BMI} \le 24.9$: Kelebihan Berat Badan (Overweight)
  * $25.0 \le \text{BMI} \le 29.9$: Obesitas Tingkat 1
  * $\text{BMI} \ge 30.0$: Obesitas Tingkat 2 (Tinggi)
* **Estimasi Rentang Berat Badan Ideal:**
  $$\text{BB}_{\min} = 18.5 \times (\text{Tinggi (m)})^2$$
  $$\text{BB}_{\max} = 22.9 \times (\text{Tinggi (m)})^2$$
* **Penyimpanan Hasil:**
  Disimpan ke tabel `cek_bmi` melalui PDO prepared statement. Hasil evaluasi juga disimpan sementara di `$_SESSION['bmi_result']`.
* **Tampilan Hasil (`bmi/bmi_hasil.php`):**
  Menampilkan angka skor, badge kondisi, *visual needle scale meter* (skala 12–38), kartu ringkasan tinggi-berat-berat ideal, narasi interpretasi, batas penggunaan medis, dan navigasi lanjutan.
* **Potensi Masalah & Inkonsistensi:**
  1. Input `jenis_kelamin` dikirim melalui formulir POST tetapi diabaikan di `bmi_proses.php` dan tidak ada kolom `jenis_kelamin` pada tabel `cek_bmi`.
  2. Tabel `cek_bmi` tidak memiliki kolom `user_id`. Semua hasil pemeriksaan langsung ditulis ke basis data umum tanpa tautan kepemilikan akun.
  3. Pada blok penanganan fallback `$_GET['bmi']` di `bmi_hasil.php` (baris 19), klasifikasi warna menggunakan rentang standar global Barat (`18.5 <= bmi <= 24.9` diberi status 'success') alih-alih standar Asia-Pasifik ($22.9$).
  4. Variabel luaran `$res['tinggi']`, `$res['berat']`, dan `$res['bb_min']` di-render tanpa sanitasi `htmlspecialchars()`.

---

### B. Kalkulator Kebutuhan Kalori & BMR
* **Formulir Input (`bmr/bmr_form.php`):**
  * Nama Lengkap (`nama_user`) — Teks wajib.
  * Jenis Kelamin (`jenis_kelamin`) — Pilihan L/P (wajib).
  * Umur (`umur`) — Angka tahun (validasi: 10 - 110 tahun).
  * Berat Badan (`berat_badan`) — Angka desimal (validasi: 20 - 300 kg).
  * Tinggi Badan (`tinggi_badan`) — Angka desimal (validasi: 80 - 250 cm).
  * Faktor Aktivitas Fisik (`aktivitas`) — Pilihan dropdown:
    * 1.200: Sedentary (jarang berolahraga)
    * 1.375: Ringan (1-3 hari/minggu)
    * 1.550: Sedang (3-5 hari/minggu)
    * 1.725: Sangat Aktif (6-7 hari/minggu)
    * 1.900: Ekstra Aktif (pekerja fisik berat / atlet 2x sehari)
* **Rumus Kalkulasi (`bmr/bmr_proses.php`):**
  Menggunakan **Persamaan Mifflin-St Jeor (1990)**:
  $$\text{BMR}_{\text{Pria}} = (10 \times \text{BB}) + (6.25 \times \text{TB}) - (5 \times \text{Umur}) + 5$$
  $$\text{BMR}_{\text{Wanita}} = (10 \times \text{BB}) + (6.25 \times \text{TB}) - (5 \times \text{Umur}) - 161$$
  $$\text{TDEE} = \text{BMR} \times \text{Faktor Aktivitas}$$
  $$\text{Defisit Terarah (Turun BB)} = \max(1200, \text{TDEE} - 450) \text{ kkal}$$
  $$\text{Surplus Terukur (Naik BB)} = \text{TDEE} + 450 \text{ kkal}$$
* **Penyimpanan Hasil:**
  Disimpan ke tabel `cek_bmr` (kolom: `nama_user`, `jenis_kelamin`, `umur`, `berat_badan`, `tinggi_badan`, `bmr`, `aktivitas`, `tdee`). Disimpan pada sesi `$_SESSION['bmr_result']`.
* **Tampilan Hasil (`bmr/bmr_hasil.php`):**
  Menampilkan dua kartu metrik utama (BMR dan TDEE), tiga rekomendasi sasaran berat badan (Defisit, Pemeliharaan, Surplus), parameter masukan, dan catatan klinis.
* **Potensi Masalah & Inkonsistensi:**
  1. **Bug Kritis pada Pembaruan Data (`bmr/bmr_update.php` baris 31):** Saat admin mengedit rekaman BMR via `bmr_edit.php`, formulir edit tidak menyediakan pilihan tingkat aktivitas fisik. Akibatnya file pembaruan menggunakan pengali tetap *hardcoded*: `$tdee = round($bmr * 1.55, 0);`. Nilai TDEE pengguna yang bertipe sedentary atau ekstra aktif otomatis rusak tertimpa menjadi pengali 1.55.
  2. Fallback backward compatibility pada `bmr_hasil.php` menggunakan pengurangan $\pm 400$ kkal, sedangkan logika formulir utama menggunakan $450$ kkal.
  3. Tidak ada `user_id` pada tabel `cek_bmr`.

---

### C. Kalkulator Detak Jantung & Zona Kardio
* **Formulir Input (`jantung/jantung_form.php`):**
  * Nama Lengkap (`nama_user`) — Teks wajib.
  * Umur (`umur`) — Angka tahun (validasi: 10 - 100 tahun).
  * Detak Nadi Istirahat / RHR (`resting_hr`) — Angka BPM (validasi: 40 - 180 bpm).
* **Rumus Kalkulasi (`jantung/jantung_proses.php`):**
  * **Detak Jantung Maksimal (Formula Tanaka et al., 2001):**
    $$\text{HR}_{\max} = 208 - (0.7 \times \text{Umur})$$
    *(Catatan: Teks metodologi di index.php menyebutkan Formula Gellish $207 - 0.7 \times \text{Umur}$, tetapi implementasi kode riil menggunakan Tanaka $208 - 0.7 \times \text{Umur}$)*.
  * **Heart Rate Reserve (HRR):**
    $$\text{HRR} = \text{HR}_{\max} - \text{RHR}$$
  * **Zona Latihan Target (Metode Karvonen):**
    * Zona 1 (Pemulihan Aktif): $\text{RHR} + (0.50 \times \text{HRR}) \dots \text{RHR} + (0.60 \times \text{HRR})$
    * Zona 2 (Bakar Lemak & Stamina): $\text{RHR} + (0.60 \times \text{HRR}) \dots \text{RHR} + (0.70 \times \text{HRR})$
    * Zona 3 (Aerobik / Ketahanan Kardio): $\text{RHR} + (0.70 \times \text{HRR}) \dots \text{RHR} + (0.80 \times \text{HRR})$
    * Zona 4 (Ambang Anaerobik): $\text{RHR} + (0.80 \times \text{HRR}) \dots \text{RHR} + (0.90 \times \text{HRR})$
* **Penyimpanan Hasil:**
  Disimpan ke tabel `cek_jantung` (`nama_user`, `umur`, `resting_hr`, `max_hr`, `kategori`, `catatan`).
* **Tampilan Hasil (`jantung/jantung_hasil.php`):**
  Menampilkan RHR dengan status warna, perkiraan denyut maksimal, perincian 4 zona detak jantung latihan dalam BPM, rambu peringatan medis (kapan harus berkonsultasi ke dokter spesialis jantung), dan panduan pengukuran denyut nadi radialis.
* **Potensi Masalah & Inkonsistensi:**
  1. Pada dashboard admin (`admin/admin.php`), modul jantung bersifat *read-only*. Tidak ada fitur edit atau hapus rekaman detak jantung sebagaimana pada modul BMI dan BMR.
  2. Ketiadaan kolom `user_id`.

---

### D. Skrining Kesehatan Mental (Instrumen PHQ-4)
* **Formulir Input (`mental/mental_form.php`):**
  * Nama / Inisial (`nama_user`) — Teks wajib.
  * 4 Pertanyaan Skala Likert 4-titik (0 = Tidak pernah, 1 = Beberapa hari, 2 = Lebih dari separuh hari, 3 = Hampir setiap hari) mengacu periode 2 minggu terakhir:
    * Pertanyaan 1 & 2: Subskala Kecemasan / GAD-2 (*Generalized Anxiety Disorder-2*).
    * Pertanyaan 3 & 4: Subskala Gejala Suasana Hati / PHQ-2 (*Patient Health Questionnaire-2*).
* **Metode Penilaian & Skoring (`mental/mental_proses.php`):**
  $$\text{Skor Cemas} = Q_1 + Q_2 \quad (\text{Rentang: } 0 - 6)$$
  $$\text{Skor Depresi} = Q_3 + Q_4 \quad (\text{Rentang: } 0 - 6)$$
  $$\text{Total Skor} = \text{Skor Cemas} + \text{Skor Depresi} \quad (\text{Rentang: } 0 - 12)$$
* **Kategori Tingkat Distres Psikologis (Kroenke et al., 2009):**
  * 0 – 2: Normal / Minimal
  * 3 – 5: Gejala Ringan (*Mild*)
  * 6 – 8: Gejala Sedang (*Moderate*)
  * 9 – 12: Gejala Berat (*Severe*)
* **Penyimpanan Hasil:**
  Disimpan ke tabel `cek_mental` (`nama_user`, `skor_cemas`, `skor_depresi`, `total_skor`, `tingkat`, `anjuran`).
* **Tampilan Hasil (`mental/mental_hasil.php`):**
  Menampilkan skor total per 12, penguraian subskor cemas (GAD-2) dan depresi (PHQ-2), teks anjuran *self-care*, kotak kontak krisis (Layanan Sejiwa Kemenkes 119 ekstensi 8), dan penegasan bahwa instrumen ini adalah penapisan awal reflektif dan bukan diagnosis medis kejiwaan.
* **Potensi Masalah & Inkonsistensi:**
  1. Tidak ada tombol edit atau hapus di panel admin.
  2. Kolom `user_id` belum ada. Pengguna yang melakukan skrining berkala tidak memiliki grafik pemantauan tren kondisi psikologis.

---

## 4. Audit Sistem Autentikasi, Sesi & Otorisasi

Sistem autentikasi mengelola dua jenis aktor: pengguna biasa (`user`) dan pengelola sistem (`admin`).

### Alur Pendaftaran Pengguna (`auth/register.php`):
* Meminta input: `username`, `password`, dan `konfirmasi_password`.
* Validasi: panjang username 3–30 karakter, password minimal 6 karakter.
* Pemeriksaan duplikasi username menggunakan prepared statement.
* Hashing password: menggunakan `password_hash($password, PASSWORD_BCRYPT)`.
* Role ditetapkan secara otomatis bernilai `'user'`.

### Alur Masuk Pengguna (`auth/login_user_proses.php`):
* Mengambil data pengguna berdasarkan `username`.
* Verifikasi password mendukung dua kondisi:
  1. `password_verify($password, $user['password'])` (untuk akun ter-hash).
  2. `$password === $user['password']` (kompatibilitas akun lama bertipe teks polos).
* Jika valid, sesi diinisialisasi:
  ```php
  $_SESSION['username'] = $user['username'];
  $_SESSION['job'] = $user['role'] ?? 'user';
  ```

### Alur Masuk Admin (`auth/login_admin_proses.php`):
* Memeriksa keberadaan pengguna dengan `role = 'admin'` di tabel `user`.
* **Celah Keamanan Kritis (Hardcoded Backdoor):**
  Pada baris 19-21:
  ```php
  } elseif ($username === 'admin' && $password === 'admin123') {
      $valid = true;
  }
  ```
  Jika akun admin di basis data dihapus atau diubah password-nya, siapa pun yang memasukkan username `admin` dan sandi `admin123` tetap memperoleh hak akses penuh administrator karena *fallback logic* statis ini.

### Masalah Sesi & Otorisasi:
1. **Tidak Ada `session_regenerate_id(true)`:** Sesi ID tidak diperbarui saat berhasil login, membuka potensi serangan *Session Fixation*.
2. **Atribut Cookie Sesi Tidak Dikonfigurasi:** Parameter `HttpOnly`, `SameSite=Lax/Strict`, dan flag `Secure` tidak disetel eksplisit pada saat `session_start()`.
3. **Penyimpanan ID Pengguna Tidak Ada di Sesi:** Sesi hanya menyimpan `$_SESSION['username']` dan `$_SESSION['job']`. Nilai `id` (primary key tabel user) tidak disimpan pada sesi sehingga menyulitkan *query* relasional.

---

## 5. Audit Panel Administrator & CRUD

Dashboard admin (`admin/admin.php`) diakses melalui pemeriksaan `login_cek.php` dan `role.php` (`is_admin()`).

### Tampilan & Komponen:
1. **4 Kartu Statistik Metrik:** Menghitung jumlah baris data via `SELECT COUNT(*) FROM cek_...`.
2. **Sistem Tab Navigasi Data:**
   * Tab 1: Rekam Data BMI (disertai tombol Edit & Hapus).
   * Tab 2: Rekam Data BMR & Kalori (disertai tombol Edit & Hapus).
   * Tab 3: Rekam Detak Jantung (hanya tampilan tabel riwayat).
   * Tab 4: Rekam Skrining Mental (hanya tampilan tabel riwayat).

### Analisis Asimetri CRUD:
| Entitas | Create (Publik/Admin) | Read (Admin) | Update (Admin) | Delete (Admin) |
| :--- | :---: | :---: | :---: | :---: |
| `cek_bmi` | Ya (`bmi_proses.php`) | Ya | Ya (`bmi_edit.php` & `bmi_update.php`) | Ya (`bmi_hapus.php`) |
| `cek_bmr` | Ya (`bmr_proses.php`) | Ya | Ya (`bmr_edit.php` & `bmr_update.php`) | Ya (`bmr_hapus.php`) |
| `cek_jantung` | Ya (`jantung_proses.php`) | Ya | **Tidak Ada** | **Tidak Ada** |
| `cek_mental` | Ya (`mental_proses.php`) | Ya | **Tidak Ada** | **Tidak Ada** |
| `user` | Ya (`register.php`) | **Tidak Ada** | **Tidak Ada** | **Tidak Ada** |

Operasi penghapusan data (`bmi_hapus.php` dan `bmr_hapus.php`) dilakukan melalui metode HTTP `GET` (`?id=...`) tanpa konfirmasi otorisasi berbasis token CSRF.

---

## 6. Audit Keamanan & Privasi Data

| Komponen | Status Saat Ini | Tingkat Risiko | Konsekuensi & Rekomendasi Solusi |
| :--- | :--- | :---: | :--- |
| **Penyimpanan Password** | Akun baru ter-hash BCRYPT, namun akun default seeded (`includes/db.php`) tersimpan teks polos (`admin123`, `user123`) | **Tinggi (High)** | Password awal mudah terbaca jika berkas database diunduh. Harus di-hash sejak proses seed pertama. |
| **Backdoor Autentikasi** | Hardcoded kredensial `admin` / `admin123` di `login_admin_proses.php` | **Kritis (Critical)** | Melewati autentikasi basis data. Wajib dihapus total. |
| **Proteksi CSRF** | Tidak ada token CSRF pada seluruh form POST dan link GET deletion | **Tinggi (High)** | Penyerang dapat merekayasa tautan atau formulir tersembunyi di web lain untuk menghapus rekaman atau mengirim data palsu atas nama admin/user. |
| **Eksposur Berkas SQLite** | Berkas `database/healthcheck.sqlite` berada di dalam direktori web yang dapat diakses publik | **Kritis (Critical)** | Jika web server tidak memblokir ekstensi `.sqlite`, pihak luar dapat mengunduh seluruh database secara langsung. Dibutuhkan file `.htaccess` atau pemindahan direktori. |
| **Injeksi SQL** | Mayoritas query CRUD telah menggunakan PDO Prepared Statements | **Rendah (Low)** | Sudah menerapkan *parameter binding* dengan baik (`$stmt->prepare()` dan `$stmt->execute()`). Perlu dipertahankan konsistensinya. |
| **Isolasi Data Pengguna** | Tidak ada kolom `user_id` pada tabel data kesehatan. Seluruh data bercampur | **Tinggi (High)** | Pengguna tidak dapat melihat riwayat pribadinya sendiri, dan privasi antar-pengguna rentan bocor. Wajib ditambahkan relasi `user_id`. |
| **Kepatuhan Privasi Medis (UU PDP)** | Data kesehatan mental & fisik disimpan tanpa opsi persetujuan (consent) dan tanpa fasilitas penghapusan mandiri | **Sedang (Medium)** | Data kesehatan masuk kategori data pribadi spesifik. Platform harus menyediakan *consent notice* serta opsi penghapusan akun dan riwayat. |

---

## 7. Temuan Prioritas Masalah (Priority Issues)

### P0 — Critical (Masalah Keamanan & Blokir Fungsional Utama)
1. **P0-1: Ketiadaan Relasi Akun pada Data Pemeriksaan.** Seluruh tabel kesehatan (`cek_bmi`, `cek_bmr`, `cek_jantung`, `cek_mental`) tidak memiliki referensi `user_id`. Data disimpan secara anonim berbasis input teks bebas. Pembangunan *Personal Dashboard*, *Riwayat*, dan *Health Report* mustahil dilakukan tanpa relasi akun.
2. **P0-2: Hardcoded Backdoor Login Admin.** Logika fallback `admin` / `admin123` di `auth/login_admin_proses.php` harus dihilangkan.
3. **P0-3: Resiko Unduh Langsung Berkas Database.** Berkas `database/healthcheck.sqlite` rentan diekstraksi langsung melalui peramban jika di-deploy ke server produksi tanpa proteksi berkas.

### P1 — High (Bug Fungsional & Celah Akses)
1. **P1-1: Bug Perhitungan TDEE saat Edit BMR.** Skrip `bmr/bmr_update.php` memaksa pengali TDEE sebesar 1.55 tanpa memperhitungkan pilihan aktivitas awal pengguna karena field aktivitas dihilangkan dari `bmr_edit.php`.
2. **P1-2: Kerentanan CSRF pada Operasi Hapus.** Penghapusan via tautan GET (`bmi_hapus.php?id=...` dan `bmr_hapus.php?id=...`) tanpa perlindungan token sesi.
3. **P1-3: Ketiadaan `session_regenerate_id()`.** Risiko *session hijacking* dan *fixation* saat eskalasi hak akses pasca login.
4. **P1-4: Asimetri Fitur Admin.** Riwayat Detak Jantung dan Skrining Mental tidak memiliki fasilitas penghapusan data sampah/uji coba.

### P2 — Medium (Inkonsistensi Logika & Desain Arsitektur)
1. **P2-1: Auto-Schema Init Dijalankan di Setiap Request.** Pada `includes/db.php`, `self::initializeSchema()` dipanggil setiap kali koneksi dibuka, mengeksekusi berkali-kali kueri `CREATE TABLE IF NOT EXISTS` dan `SELECT COUNT(*)` yang membebani kinerja I/O disk.
2. **P2-2: Field `jenis_kelamin` pada Form BMI Mubazir.** Input jenis kelamin tersedia di form BMI namun diabaikan pada backend.
3. **P2-3: Duplikasi Kode & Inkonsistensi Kriteria Fallback.** Pengkondisian fallback GET pada `bmi_hasil.php` menggunakan ambang batas berat badan non-Asia ($24.9$ vs $22.9$).

### P3 — Low (Penyempurnaan Struktur & Kebersihan Kode)
1. **P3-1: Redundansi 23 File Root Wrapper.** File di root direktori perlu diselaraskan dengan arsitektur rute tunggal (*router*) atau rute modular yang bersih tanpa perlu menduplikasi puluhan file wrapper statis.
2. **P3-2: Penulisan Sanitasi Output.** Konsistensi pemanggilan `htmlspecialchars()` pada variabel numerik/string yang ditampilkan di halaman hasil.
