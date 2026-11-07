# Arsitektur Sistem Platform HealthCheck

**Status Dokumen:** Blueprint Arsitektur Teknis  
**Teknologi Inti:** PHP 8.x (Native Modern), SQLite 3, HTML5, CSS3 (Custom Design System + Bootstrap 5.3), JavaScript Vanilla.  
**Prinsip Arsitektur:** Modular, Reusable, Secure-by-Design, Zero-Heavy-Dependencies.

---

## 1. Visi Arsitektur & Pola Desain (Design Pattern)

Platform HealthCheck ditransformasikan dari sekadar koleksi kalkulator independen menjadi **Personal Health Assessment Platform** yang terpadu. Untuk menjaga kesederhanaan *deployment* tanpa ketergantungan framework berat (*vanilla approach*), sistem dirancang mengadopsi pola **Service-Repository Pattern (Lightweight MVC)**:

```
[ HTTP Request (Browser) ]
           │
           ▼
[ Router / Page Controller ] ── (Validasi Input & CSRF Token)
           │
           ▼
[ Business Service Layer ]   ── (Kalkulasi Medis & Logika Bisnis: BmiService, HeartService, dll.)
           │
           ▼
[ Repository / Data Layer ]  ── (PDO SQLite Prepared Statement)
           │
           ▼
[ SQLite Database ] (`database/healthcheck.sqlite`)
```

### Keunggulan Pola Ini:
1. **Pemisahan Logika Medis:** Seluruh rumus (Mifflin-St Jeor, Tanaka, Karvonen, US Navy, dll.) tidak lagi bercampur dengan markup HTML di file view, melainkan diisolasi dalam kelas-kelas PHP khusus (`src/Services/` atau `includes/services/`).
2. **Kalkulasi Dapat Digunakan Ulang (Reusable):** Layanan kalkulator yang sama dapat dipanggil oleh formulir web publik, dasbor pengguna, generator PDF, atau endpoint JSON di masa depan.
3. **Mudah Diuji (*Testable*):** Formula medis murni dapat diuji secara mandiri dengan *unit test* sederhana tanpa perlu memicu sesi HTTP atau koneksi basis data.

---

## 2. Struktur Direktori Baru yang Direkomendasikan

Struktur direktori dirancang rapi dengan mempertahankan kompatibilitas URL lama:

```
HealthCheck-Website/
├── assets/                             # Statik Frontend
│   ├── css/
│   │   ├── style.css                   # Core Design System & Tokens
│   │   ├── dashboard.css               # Khusus Dashboard & Widgets
│   │   └── print-report.css            # Styling Khusus Cetak/PDF
│   ├── js/
│   │   ├── main.js                     # Script Navigasi & Pencarian
│   │   ├── calculators.js              # Validasi Klien Interaktif
│   │   └── habit-tracker.js            # Interaksi Harian Habit Tracker
│   └── images/                         # Ikonografi, badge & ilustrasi medis
├── config/                             # Konfigurasi Lingkungan
│   ├── app.php                         # Pengaturan Situs, Timezone, Base URL
│   └── security.php                    # Header Keamanan, Session Config
├── database/                           # Penyimpanan SQLite & Migrasi
│   ├── .htaccess                       # Proteksi Blokir Akses Web Langsung!
│   ├── healthcheck.sqlite              # Berkas Database SQLite Aktif
│   └── migrations/                     # Skrip SQL DDL Berkala
├── includes/                           # Helper & Infrastruktur Inti
│   ├── db.php                          # Singleton Koneksi PDO SQLite Teroptimasi
│   ├── csrf.php                        # Generator & Validator Token CSRF
│   ├── auth_helper.php                 # Pengecekan Sesi, Role & Hak Akses
│   ├── header.php                      # Layout Header & Navbar Dinamis
│   └── footer.php                      # Layout Footer & Catatan Medis
├── services/                           # Lapisan Logika Bisnis & Formula Medis
│   ├── BmiService.php                  # Perhitungan BMI & Berat Ideal
│   ├── CalorieService.php              # BMR Mifflin-St Jeor & TDEE
│   ├── CardioService.php               # Tanaka HR Max & Karvonen Zones
│   ├── BodyFatService.php              # Formula US Navy Body Fat %
│   ├── BloodPressureService.php        # Kategori AHA/ACC Tekanan Darah
│   ├── MentalHealthService.php         # Skoring PHQ-4 & PSS-10
│   ├── HydrationService.php            # Kebutuhan Air Harian
│   ├── SleepService.php                # Efisiensi Tidur & PSQI
│   ├── HabitService.php                # Manajemen Target & Log Kebiasaan
│   └── ReportService.php               # Agregator Metrik Laporan Kesehatan
├── tools/                              # Modul Kalkulator Terpadu
│   ├── bmi/ (form.php, process.php, result.php)
│   ├── bmr/ (form.php, process.php, result.php)
│   ├── tdee/ (form.php, process.php, result.php)
│   ├── whtr/ (form.php, process.php, result.php)
│   ├── body-fat/ (form.php, process.php, result.php)
│   ├── cairan/ (form.php, process.php, result.php)
│   ├── tensi/ (form.php, process.php, result.php)
│   ├── tidur/ (form.php, process.php, result.php)
│   ├── target-hr/ (form.php, process.php, result.php)
│   ├── aktivitas/ (form.php, process.php, result.php)
│   ├── mental-phq4/ (form.php, process.php, result.php)
│   └── stres-pss/ (form.php, process.php, result.php)
├── user/                               # Personal Health Cockpit
│   ├── dashboard.php                   # Personal Health Dashboard Utama
│   ├── profile.php                     # Edit Data Biometrik Dasar
│   ├── history.php                     # Riwayat Pemeriksaan Komprehensif
│   ├── history_detail.php              # Tampilan Detail Pengukuran Lalu
│   ├── habits.php                      # Antarmuka Habit Tracker Harian
│   └── report_download.php             # Ekspor PDF Laporan Kesehatan
├── education/                          # Pusat Edukasi Kesehatan
│   ├── index.php                       # Katalog Artikel & Pencarian
│   ├── kategori.php                    # Artikel berdasarkan Kategori
│   └── detail.php                      # Pembaca Artikel & Widget Alat Terkait
├── admin/                              # Dashboard Pengelola Sistem
│   ├── dashboard.php                   # Statistik Pengguna & Metrik Sistem
│   ├── users.php                       # Manajemen Akun Pengguna
│   ├── articles.php                    # Manajemen Artikel Edukasi
│   └── assessments.php                 # Moderasi Data Rekaman
├── auth/                               # Modul Autentikasi Bersih
│   ├── login.php
│   ├── register.php
│   └── logout.php
└── index.php                           # Beranda Publik & Katalog Kalkulator
```

---

## 3. Desain Komponen Arsitektur Utama

### 3.1 Modul Kalkulator (Calculator Engine)
Setiap kalkulator mengikuti kontrak antarmuka yang seragam:
1. **Formulir Input Publik:** Dapat diakses bebas oleh tamu maupun member. Jika pengguna telah login, sistem otomatis mengisi *default value* dari profil biometrik pengguna (misal: umur, tinggi, jenis kelamin).
2. **Controller Pemrosesan:**
   * Memvalidasi token CSRF.
   * Melakukan validasi batasan klinis (*boundary check*).
   * Memanggil *Service* terkait untuk memperoleh hasil.
   * **Penyimpanan Cerdas (Smart Storage):**
     * Jika *User Login*: Hasil otomatis disimpan ke tabel basis data dengan `user_id = $_SESSION['user_id']`.
     * Jika *Tamu (Guest)*: Hasil disimpan ke dalam `$_SESSION['last_result']` dan ditawarkan opsi: *"Ingin menyimpan hasil ini ke grafik perkembangan Anda? Buat akun sekarang."*
3. **Halaman Hasil:** Menampilkan metrik, gauge visual, interpretasi risiko, batasan medis, serta tautan ke kalkulator terkait berikutnya (*recommended next assessment*).

### 3.2 Modul Personal Health Dashboard
* **Agregasi Metrik Terkini:** Mengambil rekaman paling baru (*latest record*) dari masing-masing tabel biometrik untuk pengguna yang login.
* **Indeks Kesehatan Holistik (Health Score Index):** Algoritma terbobot (skala 0 - 100) yang mengombinasikan:
  * Komposisi Tubuh (BMI / WHtR) : Bobot 25%
  * Kardiovaskular (Tekanan Darah & RHR) : Bobot 25%
  * Aktivitas Fisik & Kebiasaan : Bobot 25%
  * Kesehatan Mental & Tidur : Bobot 25%
* **Rendering Grafik Ringan:** Memanfaatkan pustaka visualisasi ringan berbasis kanvas (Chart.js via CDN atau elemen SVG native) untuk grafik tren berat badan, tensi, dan denyut nadi.

### 3.3 Modul Riwayat Pemeriksaan (Assessment History)
* **Master Unified Log:** Mengkueri *View* `v_all_health_assessments` terfilter `WHERE user_id = :user_id`.
* **Fasilitas Manajemen Riwayat:** Pengguna dapat memfilter berdasarkan kategori alat, menyortir urutan waktu, dan menghapus data pemeriksaan tertentu (*soft-delete* atau *hard-delete*) sesuai prinsip privasi kepemilikan data pengguna.

### 3.4 Modul Health Habit Tracker
* **Struktur Dual-Tabel:** `habit_trackers` (definisi target) dan `habit_logs` (capaian harian).
* **Interaksi Instan:** Memungkinkan *check-in* satu sentuhan untuk konsumsi air (tombol $+1$ gelas), durasi tidur, dan langkah fisik.

### 3.5 Modul Health Education Center
* **Integrasi Konten dengan Alat:** Setiap artikel edukasi memiliki relasi metadata dengan modul kalkulator yang relevan. Di bagian bawah artikel, disajikan *Call-to-Action* interaktif untuk mencoba kalkulator terkait.
* **Admin CMS Artikel:** Administrator dapat mempublikasikan artikel baru, memperbarui referensi medis, dan mengelola kategori.

### 3.6 Modul Health Report PDF
* **Teknologi Pembangkit Laporan:** Menggunakan template HTML-to-Print yang dirancang khusus dengan CSS media query `@media print` atau engine PHP native (FPDF / DOMPDF).
* **Fitur Keamanan:** Unduhan PDF diotorisasi melalui sesi aktif. Pengguna hanya dapat mencetak dan mengunduh data miliknya sendiri.

---

## 4. User Flow & State Diagram

### 4.1 Alur Tamu / Pengguna Publik (Guest User Flow)
```
[ Buka Web HealthCheck ]
           │
           ▼
[ Pilih Kalkulator Publik (cth: BMI) ]
           │
           ▼
[ Isi Form Input Manual ]
           │
           ▼
[ Validasi Server-Side ] ──(Gagal)──► [ Tampilkan Pesan Error ]
           │ (Sukses)
           ▼
[ Hitung Formula Medis ]
           │
           ▼
[ Simpan ke $_SESSION['bmi_result'] ] (Tidak menyimpan ke DB user)
           │
           ▼
[ Tampilkan Halaman Hasil & Interpretasi ]
           │
           ▼
[ Banner Opsi: "Daftar Akun untuk Menyimpan Riwayat ke Dashboard" ]
```

### 4.2 Alur Pengguna Terdaftar (Logged-in User Flow)
```
[ Login Berhasil ] ──► [ Generate Session ID Baru + Simpan user_id ]
           │
           ▼
[ Buka Dashboard Pribadi atau Pilih Kalkulator ]
           │
           ▼
[ Form Terisi Otomatis dari Profil Biometrik ]
           │
           ▼
[ Validasi Server-Side + CSRF Valid ]
           │
           ▼
[ Hitung Formula Medis ]
           │
           ▼
[ Simpan ke DB dengan user_id ]
           │
           ▼
[ Tampilkan Hasil + Notifikasi "Tersimpan di Riwayat" ]
           │
           ▼
[ Dashboard & Master Timeline Otomatis Terbarui ]
```

---

## 5. Rencana Keamanan & Privasi Data (Security & Privacy Blueprint)

### 5.1 Keamanan Kredensial & Autentikasi
1. **Hashing Standar Industri:**
   * Mewajibkan `password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])`.
   * Menghilangkan pengecekan sandi teks polos (`$password === $user['password']`) dan menghapus seluruh *hardcoded backdoor* di `auth/login_admin_proses.php`.
2. **Session Hardening:**
   ```php
   // Konfigurasi cookie sesi aman sebelum session_start()
   ini_set('session.cookie_httponly', 1);
   ini_set('session.use_only_cookies', 1);
   ini_set('session.cookie_samesite', 'Lax');
   if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
       ini_set('session.cookie_secure', 1);
   }
   session_start();
   ```
3. **Session Regeneration:**
   Memanggil `session_regenerate_id(true)` setiap kali status autentikasi berubah (saat login dan logout) untuk menangkal *Session Fixation*.

### 5.2 Proteksi Cross-Site Request Forgery (CSRF)
Membuat helper global `includes/csrf.php`:
```php
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}
```
* Seluruh form mutasi data (`POST`) wajib menyertakan input tersembunyi:
  `<input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">`.
* Mengubah seluruh aksi penghapusan (seperti `bmi_hapus.php`) dari metode `GET` menjadi metode `POST` dengan verifikasi token CSRF.

### 5.3 Perlindungan Terhadap SQL Injection
* Seluruh operasi basis data wajib 100% menggunakan PDO Prepared Statements dengan parameter binding (`?` atau `:named_param`).
* Menghilangkan konkatenasi string langsung ke dalam kueri SQL.

### 5.4 Otorisasi & Pencegahan IDOR (Insecure Direct Object References)
* Dalam setiap kueri pembacaan riwayat, pembaharuan, atau penghapusan data, sistem wajib menyertakan klausa kepemilikan akun:
  `WHERE id = :record_id AND user_id = :session_user_id`.
* Pengguna dilarang keras dapat membaca atau memodifikasi data pemeriksaan milik pengguna lain.

### 5.5 Perlindungan Berkas Database SQLite
Untuk mencegah unduhan berkas basis data secara langsung dari internet (`https://domain.com/database/healthcheck.sqlite`), dibuat berkas `database/.htaccess`:
```apache
# database/.htaccess
Require all denied
<Files ~ "\.(sqlite|db)$">
    Order allow,deny
    Deny from all
</Files>
```
Alternatif pada server modern adalah memindahkan direktori `database/` satu tingkat di atas *document root* web server (`public_html` atau `www`).

### 5.6 Kepatuhan Privasi Data Medis (UU PDP No. 27 Tahun 2022)
1. **Persetujuan Eksplisit (*Informed Consent*):** Pada saat pendaftaran akun dan penyimpanan data skrining mental/tensi, disediakan *checkbox* persetujuan pemrosesan data kesehatan pribadi.
2. **Hak untuk Dihapus (*Right to Erasure*):** Menyediakan fitur bagi pengguna untuk menghapus akun dan seluruh catatan riwayat kesehatannya secara permanen dari sistem (*self-service account wipe*).
3. **Anonimitas Data Publik:** Kalkulasi oleh pengunjung tanpa akun tidak disimpan di basis data fisik, menjaga privasi penuh bagi mereka yang hanya ingin melakukan simulasi kesehatan anonim.
