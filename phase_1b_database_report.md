# Laporan Teknis Implementasi: Phase 1B Database Foundation & User Data Integration

**Platform:** HealthCheck — Personal Health Assessment Platform  
**Fase:** Phase 1B (Database Schema Migration, Smart Storage & User Data Isolation)  
**Tanggal Eksekusi:** November 2026  
**Peran Pelaksana:** Senior Database Engineer & Senior PHP Backend Developer  
**Status Akhir:** **SELESAI & TERVERIFIKASI (100% Tests Passed — 14/14 Skenario)**

---

## 1. Executive Summary

Phase 1B berhasil meletakkan fondasi basis data relasional yang kokoh pada HealthCheck tanpa merusak struktur dan fungsionalitas yang ada (*non-destructive*). Sebelum Phase 1B, seluruh rekam pemeriksaan kesehatan (`cek_bmi`, `cek_bmr`, `cek_jantung`, `cek_mental`) tersimpan secara anonim tanpa tautan `user_id`.

Pada fase ini, telah berhasil diimplementasikan:
1. **Migration Runner Idempoten Berbasis Transaksi (`database/migrate.php`):** Mendukung pelacakan versi skema (`schema_migrations`), eksekusi terisolasi transaksi dengan rollback otomatis saat kegagalan, dan mekanisme *down/rollback* reversibel.
2. **Entitas Profil Pengguna Terpisah (`user_profile`):** Memisahkan data autentikasi (`user`) dari profil biometrik kesehatan dengan relasi *1-to-1* terikat constraint `UNIQUE` dan `ON DELETE CASCADE`.
3. **Relasi Relasional Foreign Key pada Seluruh Tabel Pemeriksaan:** Penambahan kolom nullable `user_id` pada `cek_bmi`, `cek_bmr`, `cek_jantung`, dan `cek_mental` terikat `REFERENCES user(id) ON DELETE SET NULL`.
4. **Penegakan Foreign Key di Level Koneksi:** Pengaktifan mandatori `PRAGMA foreign_keys = ON;` serta `PRAGMA journal_mode = WAL;` pada koneksi PDO SQLite (`includes/db.php`).
5. **Fondasi Smart Storage:**
   * **Pengunjung Tamu (Guest):** Bebas menggunakan seluruh kalkulator tanpa batasan; hasil perhitungan diproses dan disimpan di sesi sementara (`$_SESSION[...]`) tanpa mengotori basis data publik.
   * **Pengguna Terdaftar (Member):** Hasil pemeriksaan secara otomatis tersimpan ke basis data dengan `user_id` yang divalidasi langsung dari sesi server (`$_SESSION['user_id']`), bukan dari parameter input form.
6. **Isolasi Data Antarpengguna Server-Side (`includes/auth_helper.php`):** Mencegah *Insecure Direct Object Reference* (IDOR) di mana pengguna biasa hanya dapat mengakses rekam medis miliknya sendiri, sementara administrator memegang izin manajemen global.

---

## 2. Perbandingan Skema Basis Data (Sebelum vs Sesudah)

### 2.1 Skema Sebelum Migrasi (Phase 1A Baseline)
* **`user`:** `id (PK)`, `username (UNIQUE)`, `password`, `role`, `created_at`
* **`cek_bmi`:** `id_bmi (PK)`, `nama_user`, `umur`, `berat_badan`, `tinggi_badan`, `bmi`, `kondisi`, `created_at`
* **`cek_bmr`:** `id_bmr (PK)`, `nama_user`, `jenis_kelamin`, `umur`, `berat_badan`, `tinggi_badan`, `bmr`, `aktivitas`, `tdee`, `created_at`
* **`cek_jantung`:** `id_jantung (PK)`, `nama_user`, `umur`, `resting_hr`, `max_hr`, `kategori`, `catatan`, `created_at`
* **`cek_mental`:** `id_mental (PK)`, `nama_user`, `skor_cemas`, `skor_depresi`, `total_skor`, `tingkat`, `anjuran`, `created_at`

*Kelemahan:* Seluruh tabel pemeriksaan tidak memiliki foreign key `user_id`. Tidak ada tabel profil biometrik. `PRAGMA foreign_keys` bernilai 0 (nonaktif).

---

### 2.2 Skema Sesudah Migrasi (Phase 1B Final)

```sql
-- 1. Tabel Autentikasi Pengguna (Ditambahkan kolom email opsional terindeks unik)
CREATE TABLE user (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    email VARCHAR(150)
);
CREATE UNIQUE INDEX idx_user_email ON user(email) WHERE email IS NOT NULL;

-- 2. Tabel Pelacak Versi Migrasi
CREATE TABLE schema_migrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    migration VARCHAR(255) NOT NULL UNIQUE,
    applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 3. Tabel Profil Pengguna Terpisah (1-to-1 dengan user)
CREATE TABLE user_profile (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL UNIQUE,
    nama_lengkap VARCHAR(150),
    tanggal_lahir DATE,
    jenis_kelamin VARCHAR(10),
    tinggi_badan REAL,
    berat_badan REAL,
    no_telepon VARCHAR(30),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);

-- 4. Tabel Pemeriksaan BMI (Terhubung ke user_id)
CREATE TABLE cek_bmi (
    id_bmi INTEGER PRIMARY KEY AUTOINCREMENT,
    nama_user VARCHAR(150) NOT NULL,
    umur INT NOT NULL,
    berat_badan REAL NOT NULL,
    tinggi_badan REAL NOT NULL,
    bmi REAL DEFAULT 0,
    kondisi VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    user_id INTEGER REFERENCES user(id) ON DELETE SET NULL
);
CREATE INDEX idx_bmi_user_created ON cek_bmi(user_id, created_at DESC);

-- 5. Tabel Pemeriksaan BMR & Kalori (Terhubung ke user_id)
CREATE TABLE cek_bmr (
    id_bmr INTEGER PRIMARY KEY AUTOINCREMENT,
    nama_user VARCHAR(150) NOT NULL,
    jenis_kelamin VARCHAR(10) NOT NULL,
    umur INT NOT NULL,
    berat_badan REAL NOT NULL,
    tinggi_badan REAL NOT NULL,
    bmr REAL NOT NULL,
    aktivitas VARCHAR(100) DEFAULT 'Sedentary',
    tdee REAL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    user_id INTEGER REFERENCES user(id) ON DELETE SET NULL
);
CREATE INDEX idx_bmr_user_created ON cek_bmr(user_id, created_at DESC);

-- 6. Tabel Pemeriksaan Detak Jantung (Terhubung ke user_id)
CREATE TABLE cek_jantung (
    id_jantung INTEGER PRIMARY KEY AUTOINCREMENT,
    nama_user VARCHAR(150) NOT NULL,
    umur INT NOT NULL,
    resting_hr INT NOT NULL,
    max_hr INT NOT NULL,
    kategori VARCHAR(100) NOT NULL,
    catatan TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    user_id INTEGER REFERENCES user(id) ON DELETE SET NULL
);
CREATE INDEX idx_jantung_user_created ON cek_jantung(user_id, created_at DESC);

-- 7. Tabel Pemeriksaan Kesehatan Mental PHQ-4 (Terhubung ke user_id)
CREATE TABLE cek_mental (
    id_mental INTEGER PRIMARY KEY AUTOINCREMENT,
    nama_user VARCHAR(150) NOT NULL,
    skor_cemas INT NOT NULL,
    skor_depresi INT NOT NULL,
    total_skor INT NOT NULL,
    tingkat VARCHAR(100) NOT NULL,
    anjuran TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    user_id INTEGER REFERENCES user(id) ON DELETE SET NULL
);
CREATE INDEX idx_mental_user_created ON cek_mental(user_id, created_at DESC);
```

---

## 3. File yang Dimodifikasi dan Dibuat

### Berkas Baru yang Dibuat:
1. `database/migrate.php` — Migration engine CLI dengan pelacakan `schema_migrations`, transaksi ACID, idempotensi, dan dukungan rollback.
2. `database/migrations/20261106_001_phase1b_user_profile_and_relations.php` — Berkas migrasi DDL untuk pembuatan `user_profile`, penambahan `user_id` pada seluruh tabel assessment, penambahan indeks komposit, serta metode `down()` untuk rollback.
3. `includes/auth_helper.php` — Helper otorisasi data kepemilikan: `can_access_user_data()`, `assert_record_access()`, `get_user_profile()`, `upsert_user_profile()`.
4. `tests_phase1b_runner.php` — Test runner otomatis untuk 14 skenario pengujian integrasi database, foreign keys, smart storage, dan isolasi pengguna.
5. `backups_phase1b/healthcheck_pre_phase1b.sqlite` & `database/backups/healthcheck_pre_phase1b.sqlite` — Berkas backup fisik sebelum eksekusi migrasi.

### Berkas yang Dimodifikasi:
* `includes/db.php` — Penambahan `PRAGMA foreign_keys = ON;`, `PRAGMA journal_mode = WAL;`, dan pembaruan `initializeSchema()` untuk menyertakan `user_profile`.
* `includes/session.php` — Penanganan aman header sesi saat CLI testing.
* `bmi/bmi_proses.php` — Implementasi Smart Storage (Guest $\to$ session only, Member $\to$ insert DB dengan `$_SESSION['user_id']`).
* `bmr/bmr_proses.php` — Implementasi Smart Storage (Guest $\to$ session only, Member $\to$ insert DB dengan `$_SESSION['user_id']`).
* `jantung/jantung_proses.php` — Implementasi Smart Storage (Guest $\to$ session only, Member $\to$ insert DB dengan `$_SESSION['user_id']`).
* `mental/mental_proses.php` — Implementasi Smart Storage (Guest $\to$ session only, Member $\to$ insert DB dengan `$_SESSION['user_id']`).

---

## 4. Rekonsiliasi Jumlah Record (Zero Data Loss)

Sebelum dan sesudah migrasi, dilakukan audit jumlah baris (*row count*) untuk memastikan integritas data:

| Tabel | Jumlah Record Pra-Migrasi | Jumlah Record Pasca-Migrasi | Keterangan |
| :--- | :---: | :---: | :--- |
| `user` | 2 baris | 2 baris | `admin` (id:1) dan `user` (id:2) utuh 100% |
| `cek_bmi` | 0 baris | 0 baris (baseline) | Kolom `user_id` ditambahkan tanpa merusak skema |
| `cek_bmr` | 0 baris | 0 baris (baseline) | Kolom `user_id` ditambahkan tanpa merusak skema |
| `cek_jantung` | 0 baris | 0 baris (baseline) | Kolom `user_id` ditambahkan tanpa merusak skema |
| `cek_mental` | 0 baris | 0 baris (baseline) | Kolom `user_id` ditambahkan tanpa merusak skema |
| `user_profile` | *Tabel Baru* | 0 baris | Siap menampung profil biometrik akun |
| `schema_migrations` | *Tabel Baru* | 1 baris | Status: `20261106_001_phase1b_...` [APPLIED] |

---

## 5. Verifikasi Integritas & Fitur Baru

### 5.1 Penegakan Foreign Key & Cascading Delete
* **Constraint Test:** Percobaan melakukan `INSERT INTO cek_bmi (user_id, ...)` dengan `user_id = 999999` yang tidak ada di tabel `user` terbukti ditolak oleh SQLite engine dengan eksepsi: `FOREIGN KEY constraint failed (19)`.
* **Cascade Delete Test:** Penghapusan user pada tabel induk `user` terbukti secara otomatis membersihkan baris terkait pada `user_profile` (`ON DELETE CASCADE`), dan mengubah `user_id` pada tabel pemeriksaan menjadi `NULL` (`ON DELETE SET NULL`), mencegah *orphaned records*.
* **1-to-1 Profile Constraint:** Kolom `user_id` pada `user_profile` memiliki constraint `UNIQUE`, mencegah satu akun memiliki profil ganda.

### 5.2 Implementasi Smart Storage
* **Guest Flow:** Pengunjung tanpa sesi autentikasi mengisi form kalkulator $\to$ backend melakukan kalkulasi $\to$ hasil dimasukkan ke `$_SESSION['..._result']` $\to$ diarahkan ke halaman hasil $\to$ **Kueri INSERT ke database ditiadakan**.
* **Member Flow:** Pengguna terautentikasi (`$_SESSION['user_id']`) mengisi form $\to$ backend mengambil ID akun langsung dari sesi server $\to$ hasil disimpan ke database terhubung dengan `user_id` $\to$ rekam data siap dikonsumsi oleh modul dashboard dan riwayat.

### 5.3 Isolasi Data Pengguna (Data Ownership)
Fungsi `can_access_user_data(?int $ownerUserId)` dan `assert_record_access(...)` memastikan:
1. Pengguna A (`user_id = 2`) dilarang keras membaca atau memanipulasi data milik Pengguna B (`user_id = 3`), langsung menghasilkan HTTP 403 Forbidden.
2. Pengguna A diizinkan mengakses data miliknya sendiri.
3. Administrator (`role = 'admin'`) memiliki izin pembacaan global untuk moderasi sistem.
4. Record legacy tanpa pemilik (`user_id = NULL`) hanya dapat dimanipulasi oleh administrator.

---

## 6. Hasil Pengujian Lengkap (Test Suite Verification)

Pengujian otomatis dijalankan melalui script `tests_phase1b_runner.php` mencakup 14 skenario uji:

| No | Skenario Pengujian | Hasil Observasi | Status |
| :-: | :--- | :--- | :-: |
| 1 | **Backup & Restore Database** | Berkas cadangan valid, terbaca oleh SQLite, `PRAGMA integrity_check` = `ok` | **PASS** |
| 2 | **Migrasi Phase 1B** | Tabel `user_profile` terbentuk, kolom `user_id` tersedia pada 4 tabel | **PASS** |
| 3 | **Idempotensi Migrasi** | Eksekusi kedua menghasilkan *"Nothing to migrate"*, skema tidak rusak | **PASS** |
| 4 | **Konsistensi Record** | Jumlah baris akun `user` konsisten (2 akun, zero data loss) | **PASS** |
| 5 | **Foreign Key Enforcement** | `PRAGMA foreign_keys=1`, insert ID fiktif ditolak, UNIQUE profile terbukti | **PASS** |
| 6 | **Smart Storage: Guest** | Hasil kalkulator guest tampil via sesi tanpa menambah record ke DB | **PASS** |
| 7 | **Smart Storage: Member BMI** | Record BMI member tersimpan di DB terikat `user_id = 2` | **PASS** |
| 8 | **Smart Storage: Member BMR** | Record BMR member tersimpan di DB terikat `user_id = 2` | **PASS** |
| 9 | **Smart Storage: Member Detak Jantung** | Record Jantung member tersimpan di DB terikat `user_id = 2` | **PASS** |
| 10 | **Smart Storage: Member Skrining Mental** | Record Mental member tersimpan di DB terikat `user_id = 2` | **PASS** |
| 11 | **Isolasi Data Server-Side** | User A diblokir dari akses data User B (HTTP 403); Admin diizinkan | **PASS** |
| 12 | **Ketersediaan Data Legacy** | Data legacy anonim (`user_id = NULL`) tetap terbaca di admin panel | **PASS** |
| 13 | **Integritas Keamanan Phase 1A** | Proteksi CSRF (HTTP 403) dan DELETE via GET (HTTP 405) tetap aktif | **PASS** |
| 14 | **Regresi Fitur Kalkulator Lama** | Formula klinis BMI, Mifflin-St Jeor BMR, Tanaka HR, PHQ-4 100% akurat | **PASS** |

**Tingkat Keberhasilan:** 14 dari 14 Pengujian Berhasil (**100% Pass**).  
**Pengujian Keamanan Phase 1A (`tests_runner.php`):** 12 dari 12 Pengujian Berhasil (**100% Pass**).  
**Verifikasi Sintaks:** Seluruh 68 berkas PHP repo bebas dari syntax error (`php -l`).

---

## 7. Masalah yang Belum Terselesaikan (Scope Selanjutnya)

1. **Antarmuka Profil Pengguna di Frontend:** Tabel `user_profile` telah aktif di database beserta fungsi helper `upsert_user_profile()`, namun halaman web `user/profile.php` belum dibuat karena UI profil merupakan bagian dari fase berikutnya.
2. **Kalkulator Biometrik Tambahan:** Fitur kalkulator baru (TDEE Mandiri, WHtR, Lemak Tubuh, Cairan, Tekanan Darah, Kualitas Tidur, dll.) belum dibangun dan dijadwalkan pada Phase 2 & 3.
3. **Personal Dashboard & Riwayat Terpadu:** Antarmuka visual riwayat terpadu untuk member dijadwalkan setelah batch kalkulator selesai dibangun.

---

## 8. Rekomendasi Teknis untuk Phase 2

1. **Pembangunan Klaster Kalkulator Baru (Batch 1 — Biometrik Fisik):**
   * Mengembangkan Kalkulator TDEE Mandiri & Distribusi Makronutrisi (`tools/tdee/`).
   * Mengembangkan Kalkulator WHtR (*Waist-to-Height Ratio*) (`tools/whtr/`).
   * Mengembangkan Estimasi Lemak Tubuh US Navy Method (`tools/body-fat/`).
   * Mengembangkan Kalkulator Kebutuhan Cairan Harian (`tools/cairan/`).
2. **Penerapan Standar Smart Storage pada Seluruh Alat Baru:**
   * Setiap modul baru otomatis mengadopsi pola Smart Storage yang telah dibangun pada Phase 1B (Guest $\to$ session only, Member $\to$ simpan ke DB dengan `user_id`).
3. **Pembangunan Halaman Pengaturan Profil Kesehatan (`user/profile.php`):**
   * Memberikan fasilitas bagi pengguna untuk mengisi tinggi badan, tanggal lahir, dan berat badan awal agar otomatis terisi pada setiap kalkulator (*auto-fill convenience*).
