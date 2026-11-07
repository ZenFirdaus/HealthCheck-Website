# Laporan Implementasi Keamanan: Phase 1A Security Hardening

**Platform:** HealthCheck — Personal Health Assessment Platform  
**Fase:** Phase 1A (Security Hardening, Session Protection & CSRF Enforcement)  
**Tanggal Eksekusi:** November 2026  
**Peran Pelaksana:** Senior PHP Security Engineer & Backend Developer  
**Status Akhir:** **SELESAI & TERVERIFIKASI (100% Tests Passed)**

---

## 1. Daftar Kerentanan yang Ditemukan (Sebelum Hardening)

Sebelum pelaksanaan Phase 1A, audit keamanan mengidentifikasi sejumlah celah berisiko tinggi dan kritis:

1. **Authentication Bypass & Hardcoded Backdoor (Severity: Critical)**
   * Ditemukan di `auth/login_admin_proses.php` (baris 19-21) kondisi fallback statis:
     ```php
     } elseif ($username === 'admin' && $password === 'admin123') { $valid = true; }
     ```
     Kondisi ini memungkinkan siapa pun masuk sebagai administrator tanpa memeriksa validitas database, bahkan jika akun di database telah dinonaktifkan atau diganti sandinya.
2. **Plaintext Password Storage & Insecure Seeding (Severity: High)**
   * Berkas `includes/db.php` menyuntikkan (*seeding*) akun awal `admin` (`admin123`) dan `user` (`user123`) dalam bentuk teks polos (*plain text*).
   * File autentikasi melakukan perbandingan string mentah `$password === $user['password']`.
3. **Ketiadaan Proteksi Cross-Site Request Forgery (CSRF) (Severity: High)**
   * Tidak ada token CSRF pada seluruh form POST (login, register, kalkulator BMI, BMR, Detak Jantung, Skrining Mental, edit admin).
   * Operasi penghapusan data (`bmi/bmi_hapus.php` dan `bmr/bmr_hapus.php`) dijalankan melalui metode HTTP `GET` (`?id=...`), memungkinkan penyerang mengeksekusi penghapusan data sepihak via tautan tersembunyi (*zero-click deletion*).
4. **Session Fixation & Insecure Session Cookie (Severity: High)**
   * Tidak ada pemanggilan `session_regenerate_id(true)` saat status autentikasi berubah pasca login.
   * Parameter cookie sesi tidak menetapkan flag keamanan standar (`HttpOnly`, `SameSite=Lax`, dan `Secure` pada HTTPS).
5. **Eksposur Berkas Basis Data SQLite (Severity: Critical)**
   * Berkas `database/healthcheck.sqlite` tersimpan di dalam *web root* tanpa aturan blokir direktori, memungkinkan berkas diunduh langsung melalui peramban jika web server tidak mengonfigurasi proteksi ekstensi.
6. **Potensi Deadlock / Lock Contention pada SQLite (Severity: Medium)**
   * Koneksi PDO SQLite tidak menetapkan `busy_timeout` dan mode jurnal WAL (*Write-Ahead Logging*), rentan memicu error *database table is locked* saat terjadi request bersamaan.

---

## 2. File yang Dimodifikasi dan Dibuat

### Berkas Baru yang Dibuat:
1. `includes/session.php` — Modul inisialisasi sesi terpusat dengan flag `HttpOnly`, `SameSite=Lax`, `use_strict_mode=1`, dan `secure` adaptif (HTTPS).
2. `includes/csrf.php` — Modul kriptografi proteksi CSRF (`csrf_token()`, `csrf_field()`, `verify_csrf_token()`, `require_csrf_token()`).
3. `database/.htaccess` — Aturan server Apache (`Require all denied` / `Deny from all`) untuk memblokir akses HTTP langsung ke direktori database.
4. `database/index.php` — Lapis kedua pertahanan (*defense-in-depth*) yang langsung mengembalikan HTTP 403 Forbidden bila direktori diakses melalui web.
5. `tests_runner.php` — Skrip pengujian otomatis komprehensif 12 skenario keamanan & regresi.
6. `backups_phase1a/` — Cadangan penuh database dan berkas konfigurasi sebelum perubahan dilakukan.

### Berkas yang Dimodifikasi (Security Hardening):
* `auth/login_admin_proses.php` — Penghapusan total backdoor, integrasi CSRF token, migrasi otomatis password lama, dan `session_regenerate_id(true)`.
* `auth/login_user_proses.php` — Integrasi CSRF token, verifikasi via `password_verify()`, migrasi transparan sandi plaintext, dan `session_regenerate_id(true)`.
* `auth/login.php` & `auth/login_admin.php` — Penambahan token CSRF tersembunyi (`<?= csrf_field() ?>`).
* `auth/register.php` — Penambahan verifikasi CSRF token, standarisasi hashing ke `PASSWORD_DEFAULT`.
* `auth/logout.php` — Pembersihan sesi tuntas (pengosongan array `$_SESSION`, pencabutan cookie sesi, `session_destroy()`).
* `auth/login_cek.php` — Integrasi modul `includes/session.php`.
* `includes/db.php` — Integrasi `session.php` & `csrf.php`, migrasi seed password awal ke `password_hash()`, pengaktifan SQLite WAL mode & `busy_timeout = 5000`.
* `includes/header.php` — Integrasi `session.php` dan `csrf.php` global.
* `admin/admin.php` — Pengubahan tombol hapus GET menjadi formulir POST dengan CSRF token, perbaikan redirect otorisasi non-admin.
* `bmi/bmi_hapus.php` & `bmr/bmr_hapus.php` — Penolakan metode GET (HTTP 405 Method Not Allowed), validasi token CSRF wajib via POST.
* `bmi/bmi_form.php`, `bmr/bmr_form.php`, `jantung/jantung_form.php`, `mental/mental_form.php` — Penambahan `<?= csrf_field() ?>`.
* `bmi/bmi_proses.php`, `bmr/bmr_proses.php`, `jantung/jantung_proses.php`, `mental/mental_proses.php` — Penambahan `require_csrf_token()`.
* `bmi/bmi_edit.php` & `bmr/bmr_edit.php` — Penambahan `<?= csrf_field() ?>`.
* `bmi/bmi_update.php` & `bmr/bmr_update.php` — Penambahan `require_csrf_token()`.

---

## 3. Implementasi Pengamanan Rinci

### A. Penghapusan Backdoor & Standarisasi Hashing
* Kode hardcoded `admin / admin123` dihapus total dari `login_admin_proses.php`. Autentikasi kini 100% bergantung pada baris akun tabel `user` dengan kriteria `role = 'admin'`.
* Akun admin dan user yang sudah ada di `healthcheck.sqlite` telah dimigrasikan ke hash BCRYPT standar industri (`$2y$12$...`).
* Mekanisme *Transparent Re-hash Migration* disematkan: jika di kemudian hari terdapat akun lama dengan format teks polos atau algoritma usang, saat pengguna berhasil masuk, sandi otomatis di-hash ulang dan disimpan ke database tanpa mengganggu kenyamanan pengguna.

### B. Session Security & Anti-Fixation
* Konfigurasi sesi terpusat di `includes/session.php` memastikan:
  * `session.use_strict_mode = 1` : Mencegah inisiasi ID sesi tak dikenal.
  * `session.use_only_cookies = 1` : Mematikan transmisi ID sesi lewat URL query string.
  * `session_set_cookie_params` : Memaksa flag `HttpOnly` (kebal dari pencurian cookie via XSS JavaScript) dan `SameSite=Lax` (kebal dari serangan CSRF antar-domain).
* `session_regenerate_id(true)` dieksekusi tepat setelah kredensial divalidasi, memusnahkan ID sesi lama dan menerbitkan ID baru untuk mencegah *Session Fixation*.
* Script logout menghapus cookie sesi di peramban dengan mengatur masa kedaluwarsa ke masa lalu (`time() - 42000`).

### C. Proteksi CSRF Menyeluruh
* Menggunakan token kriptografi acak 256-bit (`random_bytes(32)`) yang disimpan pada sesi.
* Validasi menggunakan `hash_equals()` untuk mencegah kebocoran *timing attack*.
* Seluruh formulir aplikasi telah dilengkapi token CSRF.
* Jika permintaan POST dikirim tanpa token yang valid atau dengan token kedaluwarsa, server langsung menolak dengan status **HTTP 403 Forbidden**.

### D. Konversi Aksi Hapus (Destructive Actions) ke POST
* Menutup celah eksploitasi URL pada `bmi_hapus.php` dan `bmr_hapus.php`.
* Permintaan via GET kini otomatis ditolak dengan pesan: **HTTP 405 Method Not Allowed**.
* Tombol hapus di dashboard admin diubah menjadi form POST terproteksi dengan konfirmasi JavaScript `confirm()` dan token CSRF terenkapsulasi.

### E. Perlindungan Berkas Database SQLite
* Menambahkan berkas `database/.htaccess` yang berisi:
  ```apache
  <IfModule mod_authz_core.c>
      Require all denied
  </IfModule>
  <IfModule !mod_authz_core.c>
      Order allow,deny
      Deny from all
  </IfModule>
  ```
* Menambahkan `database/index.php` sebagai fallback jika server web mengabaikan `.htaccess`, yang langsung memutus koneksi dengan HTTP 403 Forbidden.
* **Konfigurasi untuk Nginx (Dokumentasi Server Alternatif):**
  Jika aplikasi di-deploy pada server web Nginx, tambahkan direktif berikut pada blok `server {}`:
  ```nginx
  location ^~ /database/ {
      deny all;
      return 403;
  }
  location ~ \.(sqlite|sqlite3|db|bak)$ {
      deny all;
      return 403;
  }
  ```

---

## 4. Hasil Pengujian Lengkap (Test Suite Verification)

Pengujian otomatis dijalankan melalui test runner `tests_runner.php` terhadap 12 skenario pengujian:

| No | Skenario Pengujian | Target Endpoint | Ekspektasi | Hasil Riil | Status |
| :-: | :--- | :--- | :--- | :--- | :-: |
| 1 | **Login Admin Valid** | `POST /auth/login_admin_proses.php` | Berhasil login, redirect ke admin panel, sesi terdaftar | Status 302, sesi aktif, halaman admin terbuka | **PASS** |
| 2 | **Login Admin Invalid & Backdoor Check** | `POST /auth/login_admin_proses.php` | Kredensial salah ditolak, tidak ada bypass backdoor | Ditolak ke login dengan pesan error, akses admin diblokir | **PASS** |
| 3 | **Login Pengguna Biasa** | `POST /auth/login_user_proses.php` | User biasa login, redirect ke index, nama tampil di navbar | Status 302, identitas user terkonfirmasi di navbar | **PASS** |
| 4 | **Logout Pengguna** | `GET /auth/logout.php` | Sesi dihancurkan, cookie dihapus, kembali ke status guest | Status 302 ke index, navbar kembali menampilkan "Masuk" | **PASS** |
| 5 | **Session Regeneration** | Login process | Session ID berubah pasca login, flag HttpOnly aktif | Session ID ter-regenerasi unik, cookie memiliki flag HttpOnly | **PASS** |
| 6 | **Akses Admin Tanpa Login** | `GET /admin/admin.php` | Akses ditolak, diarahkan ke login | Status 302 diarahkan ke `login.php?pesan=belum_login` | **PASS** |
| 7 | **Akses Admin oleh User Biasa** | `GET /admin/admin.php` | Pengguna biasa diblokir dari akses panel admin | Status 302 diarahkan ke `index.php?pesan=unauthorized` | **PASS** |
| 8 | **POST Tanpa CSRF Token** | Form BMI & Login Admin | Permintaan ditolak dengan kode status 403 | HTTP 403 Forbidden diterima dari kedua endpoint | **PASS** |
| 9 | **POST dengan CSRF Valid** | Form BMI | Perhitungan berhasil diproses dan disimpan | Status 302 ke `bmi_hasil.php`, hasil evaluasi tampil | **PASS** |
| 10 | **Penghapusan Data via POST** | `bmi_hapus.php` | GET ditolak 405, POST dengan CSRF berhasil menghapus baris | GET menghasilkan 405, POST berhasil menghapus data DB | **PASS** |
| 11 | **Akses Direktori Database** | `/database/index.php` | Permintaan langsung ditolak | HTTP 403 Forbidden dikembalikan oleh server | **PASS** |
| 12 | **Regresi Fitur Kalkulator Lama** | BMR, Jantung, Mental | Seluruh kalkulator berfungsi normal dengan CSRF aktif | Ketiga kalkulator berhasil menghitung dan menampilkan hasil | **PASS** |

**Tingkat Keberhasilan:** 12 dari 12 Pengujian Berhasil (**100% Pass**).  
**Verifikasi Sintaks:** 66 file PHP diperiksa dengan linter `php -l` dengan hasil: *No syntax errors detected*.

---

## 5. Masalah yang Belum Terselesaikan (Scope Luar Phase 1A)

Sesuai batasan ketat (*strict constraints*) pada Phase 1A:
1. **Relasi Akun (`user_id`) Belum Terpasang:** Tabel kalkulator masih mencatat `nama_user` dan belum memiliki foreign key `user_id` (ini merupakan cakupan migrasi Phase 1B).
2. **Bug Formula Multiplier pada `bmr_update.php`:** Pengali statis 1.55 saat admin mengedit rekaman BMR belum diubah agar tidak melanggar batasan larangan perubahan fitur lama sebelum Phase 1B/2.
3. **Data Profil Tambahan Pengguna:** Tabel `user` belum memiliki kolom `nama_lengkap`, `tanggal_lahir`, `jenis_kelamin`, dan kontak (dijadwalkan pada Phase 1B).

---

## 6. Rekomendasi Teknis untuk Phase 1B

1. **Migrasi Database Non-Destructive (Phase 1B):**
   * Menjalankan kueri `ALTER TABLE` untuk menambahkan kolom `user_id INTEGER NULL REFERENCES user(id)` pada `cek_bmi`, `cek_bmr`, `cek_jantung`, dan `cek_mental`.
   * Menambahkan kolom data biometrik dasar pada tabel `user` (`nama_lengkap`, `email`, `tanggal_lahir`, `jenis_kelamin`, `tinggi_badan`, `berat_badan`).
   * Membuat indeks komposit `(user_id, created_at DESC)` untuk performa query *Personal Dashboard*.
2. **Implementasi Smart Storage di Seluruh Modul Kalkulator:**
   * Jika pengguna sedang login (`isset($_SESSION['user_id'])`), hasil kalkulator otomatis tersimpan dengan `user_id`.
   * Jika pengguna adalah tamu (*guest*), kalkulasi disimpan di sesi saja tanpa mengisi tabel database dengan data anonim.
3. **Penyempurnaan CRUD Admin:**
   * Menambahkan fitur hapus untuk riwayat Detak Jantung dan Skrining Mental dengan proteksi POST + CSRF yang sama.
   * Memperbaiki form edit BMR agar menyertakan dropdown aktivitas fisik.
