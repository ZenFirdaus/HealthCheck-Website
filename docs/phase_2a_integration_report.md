# Laporan Teknis Implementasi: Phase 2A Existing Calculator Integration

**Platform:** HealthCheck — Personal Health Assessment Platform  
**Fase:** Phase 2A (Integrasi 4 Kalkulator Eksisting, Perbaikan Bug BMR, Integrasi Profil Pengguna & Data Ownership)  
**Tanggal Eksekusi:** November 2026  
**Peran Pelaksana:** Senior PHP Backend Engineer, Database Engineer & Software QA Engineer  
**Status Akhir:** **SELESAI & TERVERIFIKASI (100% Tests Passed — 30/30 Skenario)**

---

## 1. Executive Summary

Phase 2A berfokus pada integrasi penuh empat kalkulator kesehatan utama yang telah ada (**BMI, BMR/TDEE, Detak Jantung, dan Skrining Mental PHQ-4**) dengan infrastruktur keamanan Phase 1A dan fondasi basis data relasional Phase 1B.

Sebelum Phase 2A, keempat kalkulator beroperasi secara terisolasi tanpa memanfaatkan data biometrik profil pengguna, terdapat bug kritis di mana pengeditan data BMR memaksa multiplier aktivitas menjadi statis `1.55` sehingga merusak nilai TDEE, dan halaman hasil rentan terhadap akses silang (*Insecure Direct Object Reference / IDOR*).

Seluruh target Phase 2A telah berhasil diimplementasikan dan diverifikasi:
1. **Perbaikan Tuntas Bug Multiplier BMR:** Membangun *single source of truth* konfigurasi aktivitas fisik (`includes/bmr_helper.php`), menambahkan pilihan dropdown aktivitas pada form edit admin (`bmr/bmr_edit.php`), dan menghapus pengali statis `1.55` pada proses pembaruan (`bmr/bmr_update.php`).
2. **Integrasi Profil Pengguna Bebas Mutasi Sampingan (*Decoupled Auto-Fill*):** Form kalkulator secara cerdas melakukan auto-fill dari `user_profile` (nama, jenis kelamin, umur hasil kalkulasi tanggal lahir, tinggi badan, berat badan) untuk kenyamanan member, tanpa mengubah data profil pengguna saat nilai form disesuaikan. Pengunjung tanpa akun (*guest*) tetap dapat menggunakan kalkulator secara bebas dari kondisi kosong/default.
3. **Standarisasi Smart Storage:** Hasil kalkulasi guest hanya disimpan sementara di sesi peramban tanpa melakukan `INSERT` ke basis data. Hasil kalkulasi member disimpan secara terikat dengan `user_id` yang divalidasi langsung dari sesi server.
4. **Penegakan Data Ownership & Kontrol Akses IDOR:** Seluruh endpoint detail hasil (`*_hasil.php?id=...`), edit, dan hapus diverifikasi melalui `assert_record_access()`. Akses silang antarmember atau akses record legacy oleh member biasa ditolak dengan kode status **HTTP 403 Forbidden**.
5. **Verifikasi Kualitas Komprehensif:** 30 skenario pengujian otomatis pada database pengujian terpisah dijalankan melalui `tests_phase2a_runner.php` dengan hasil **30 Lulus / 0 Gagal (100% PASS)**.

---

## 2. Kondisi Awal dan Masalah yang Ditemukan

Berdasarkan audit mendalam terhadap kode aktual sebelum Phase 2A:
1. **Bug Multiplier Statis BMR:**
   Pada `bmr/bmr_update.php` baris 32, kalkulasi TDEE dipaksa menggunakan nilai tetap:
   ```php
   $tdee = round($bmr * 1.55, 0);
   ```
   Selain itu, pada `bmr/bmr_edit.php` tidak tersedia elemen `<select>` untuk tingkat aktivitas. Ketika administrator mengedit rekam BMR milik pengguna bertipe *sedentary* (multiplier 1.2) atau *extra active* (multiplier 1.9), nilai TDEE otomatis rusak tertimpa formula pengali 1.55, dan kolom `aktivitas` di tabel `cek_bmr` tidak diperbarui.
2. **Ketiadaan Pemanfaatan Profil Pengguna (`user_profile`):**
   Formulir kalkulator (`bmi_form.php`, `bmr_form.php`, `jantung_form.php`, `mental_form.php`) hanya mengambil `$_SESSION['username']`. Pengguna terdaftar yang telah memiliki data tinggi badan, berat badan, jenis kelamin, dan tanggal lahir harus mengetikkan ulang seluruh data biometriknya setiap kali membuka kalkulator.
3. **Celah IDOR pada Halaman Hasil Pemeriksaan:**
   Halaman `bmi_hasil.php`, `bmr_hasil.php`, `jantung_hasil.php`, dan `mental_hasil.php` belum memvalidasi kepemilikan rekam data jika diakses melalui parameter `?id=...`. Akibatnya, Pengguna A berpotensi membaca rekam medis milik Pengguna B hanya dengan menebak ID.
4. **Inkonsistensi Validasi Server-Side:**
   Input aktivitas fisik pada BMR belum divalidasi terhadap daftar nilai yang sah (rentan manipulasi angka desimal sembarang via request POST). Pada formulir mental PHQ-4, parameter belum divalidasi rentang integer 0–3 secara ketat di server-side.

---

## 3. Daftar Bug yang Diperbaiki

| ID Bug | Lokasi | Deskripsi Masalah | Solusi Implementasi |
| :--- | :--- | :--- | :--- |
| **BUG-BMR-01** | `bmr/bmr_update.php:32` | Multiplier TDEE di-hardcode ke angka `1.55` saat admin mengedit rekaman. | Mengganti pengali statis dengan fungsi `calculate_tdee($bmr, $aktivitasFactor)` berbasis input aktivitas aktual. |
| **BUG-BMR-02** | `bmr/bmr_edit.php` | Tidak ada input dropdown aktivitas fisik pada form edit admin. | Menambahkan field `<select name="aktivitas">` dinamis berbasis `get_bmr_activity_levels()` dan `get_activity_key_from_db()`. |
| **BUG-BMR-03** | `bmr/bmr_update.php:34` | Query UPDATE tidak memperbarui kolom `aktivitas` di tabel `cek_bmr`. | Memperbarui query `UPDATE cek_bmr SET ..., aktivitas = ?, tdee = ? WHERE id_bmr = ?`. |
| **BUG-BMR-04** | `bmr/bmr_proses.php` | Input aktivitas tidak divalidasi terhadap rentang multiplier resmi. | Memvalidasi input via `get_activity_factor($_POST['aktivitas'])`; jika tidak valid (`null`), request ditolak dengan redirect `pesan=invalid_input`. |
| **BUG-SEC-01** | `*_hasil.php` | Potensi IDOR saat mengakses rekam medis via `?id=...`. | Menambahkan pemanggilan `assert_record_access($pdo, $table, $idCol, $id)` di bagian paling atas sebelum header di-render. |
| **BUG-SEC-02** | `*_hasil.php` | Header output `header.php` terpanggil sebelum pemeriksaan otorisasi, menyebabkan error *headers already sent*. | Memindahkan logika resolusi data dan verifikasi otorisasi ke atas sebelum memanggil `includes/header.php`. |
| **BUG-CLIN-01** | `bmi/bmi_hasil.php:35` | Fallback klasifikasi BMI menggunakan standar Barat ($24.9$) alih-alih Asia-Pasifik ($22.9$). | Menyelaraskan ambang normal ke standar WHO Asia-Pasifik ($18.5 \le \text{BMI} \le 22.9$). |

---

## 4. Daftar File yang Dibuat dan Dimodifikasi

### File Baru:
1. `includes/bmr_helper.php` — Modul terpusat (*Single Source of Truth*) untuk multiplier aktivitas fisik, resolusi label database, kalkulasi BMR Mifflin-St Jeor, dan kalkulasi TDEE.
2. `tests_phase2a_runner.php` — Test runner otomatis untuk 30 skenario pengujian Phase 2A yang berjalan pada database pengujian terisolasi.

### File yang Dimodifikasi:
1. `includes/auth_helper.php` — Penambahan helper `calculate_age_from_dob(?string $dob): ?int` dan `get_current_user_profile(?PDO $pdo = null): ?array`.
2. `includes/db.php` — Penambahan dukungan `getenv('HEALTHCHECK_DB_PATH')` untuk isolasi pengujian database.
3. `bmr/bmr_form.php` — Integrasi profil pengguna (nama, JK, umur dari tanggal lahir, BB, TB) dan rendering opsi aktivitas berbasis `get_bmr_activity_levels()`.
4. `bmr/bmr_proses.php` — Validasi ketat multiplier aktivitas via `get_activity_factor()` dan kalkulasi BMR/TDEE berbasis helper terpusat.
5. `bmr/bmr_edit.php` — Integrasi pengecekan `assert_record_access()` dan penambahan elemen select tingkat aktivitas fisik.
6. `bmr/bmr_update.php` — Penegakan `assert_record_access()`, validasi aktivitas, dan kalkulasi TDEE dinamis.
7. `bmr/bmr_hasil.php` — Dukungan melihat hasil via `?id=...` terproteksi kepemilikan dan isolasi sesi antar-pengguna.
8. `bmi/bmi_form.php` — Integrasi profil pengguna (nama, JK, umur dari tanggal lahir, BB, TB) dan inklusi `db.php`.
9. `bmi/bmi_proses.php` — Sanitasi input `jenis_kelamin` dan penyimpanan snapshot sesi.
10. `bmi/bmi_edit.php` — Penegakan `assert_record_access()`.
11. `bmi/bmi_update.php` — Penegakan `assert_record_access()`.
12. `bmi/bmi_hapus.php` — Penegakan `assert_record_access()`.
13. `bmi/bmi_hasil.php` — Dukungan melihat hasil via `?id=...` terproteksi kepemilikan, pemindahan verifikasi sebelum `header.php`, dan standardisasi batas BMI Asia.
14. `jantung/jantung_form.php` — Integrasi profil pengguna (nama dan umur hasil kalkulasi tanggal lahir).
15. `jantung/jantung_hasil.php` — Dukungan melihat hasil via `?id=...` terproteksi kepemilikan dan pemindahan verifikasi sebelum `header.php`.
16. `mental/mental_form.php` — Integrasi profil pengguna (nama lengkap tampilan).
17. `mental/mental_proses.php` — Validasi ketat server-side untuk parameter `q1..q4` (integer 0–3) dan nama user.
18. `mental/mental_hasil.php` — Dukungan melihat hasil via `?id=...` terproteksi kepemilikan dan pemindahan verifikasi sebelum `header.php`.

---

## 5. Integrasi Profil Pengguna pada Setiap Kalkulator

Pola integrasi profil dirancang dengan prinsip **Auto-Fill for Convenience, Decoupled Snapshot for Storage**:

### A. Kalkulator BMI (`bmi/bmi_form.php`)
* **Pengguna Terdaftar (Member):** Jika akun memiliki data di `user_profile`, form secara otomatis mengisikan:
  * Nama Lengkap: `$profile['nama_lengkap'] ?: $_SESSION['username']`
  * Jenis Kelamin: `$profile['jenis_kelamin'] ?: 'L'`
  * Umur: Dihitung secara otomatis dari `tanggal_lahir` terhadap tanggal hari ini (`calculate_age_from_dob()`). Jika umur valid (5–120 tahun), nilai ini diisikan; jika belum ada tanggal lahir, fallback ke default `25`.
  * Berat Badan: `$profile['berat_badan']` (desimal kg).
  * Tinggi Badan: `$profile['tinggi_badan']` (desimal cm).
* **Tamu (Guest):** Form menampilkan nilai kosong untuk nama, BB, dan TB, serta default umur 25.

### B. Kalkulator BMR & Kalori (`bmr/bmr_form.php`)
* **Member:** Mengisi otomatis nama lengkap, pilihan radio jenis kelamin (`L` atau `P`), umur dari tanggal lahir (rentang valid 10–110 tahun), berat badan, dan tinggi badan.
* **Tamu:** Berfungsi mandiri tanpa auto-fill.

### C. Kalkulator Detak Jantung (`jantung/jantung_form.php`)
* **Member:** Mengisi otomatis nama lengkap dan umur dari tanggal lahir (rentang valid 10–100 tahun).
* **Tamu:** Menampilkan nama kosong dan default umur 25.

### D. Skrining Mental PHQ-4 (`mental/mental_form.php`)
* **Member:** Mengisi otomatis nama lengkap atau username sebagai identitas tampilan reflektif, tanpa menyentuh data biometrik fisik.
* **Tamu:** Menampilkan placeholder nama kosong.

### Independensi Profil (Zero Side-Effects):
Pengguna bebas mengubah angka tinggi atau berat badan pada formulir pemeriksaan untuk keperluan simulasi. Saat form disubmit, sistem **hanya** menyimpan rekaman pemeriksaan ke tabel riwayat terkait (`cek_bmi`, `cek_bmr`, dll.) sebagai snapshot historis independen, dan **tidak pernah memutasi data pada tabel `user_profile`**.

---

## 6. Perbaikan BMR dan Multiplier Aktivitas Fisik

Seluruh logika BMR dan tingkat aktivitas kini disatukan dalam berkas `includes/bmr_helper.php`:

### Daftar Multiplier Terstandar:
| Nilai Key | Faktor | Label Basis Data | Deskripsi Klinis |
| :---: | :---: | :--- | :--- |
| `1.2` | 1.200 | `Sedentary (Jarang bergerak)` | Jarang Bergerak / Sedentary (banyak duduk, tidak ada olahraga rutin) |
| `1.375` | 1.375 | `Ringan (1-3 hari/minggu)` | Aktivitas Ringan (olahraga 1-3 hari/minggu) |
| `1.55` | 1.550 | `Sedang (3-5 hari/minggu)` | Aktivitas Sedang (olahraga 3-5 hari/minggu) |
| `1.725` | 1.725 | `Sangat Aktif (6-7 hari/minggu)` | Sangat Aktif (olahraga berat 6-7 hari/minggu) |
| `1.9` | 1.900 | `Ekstra Aktif (Pekerja berat/atlet)` | Ekstra Aktif (pekerja fisik berat / atlet latihan 2x sehari) |

### Mekanisme Perbaikan pada Alur Edit Admin:
1. `bmr/bmr_edit.php` membaca kolom `aktivitas` dari baris data yang sedang diedit.
2. Fungsi `get_activity_key_from_db($data['aktivitas'])` memetakan string database lama maupun baru ke salah satu kunci opsi (`1.2` s.d. `1.9`).
3. Opsi yang sesuai otomatis diberi atribut `selected="selected"`.
4. Saat formulir disubmit ke `bmr/bmr_update.php`, parameter `$_POST['aktivitas']` divalidasi menggunakan `get_activity_factor()`. Jika bernilai di luar daftar resmi, pembaruan dibatalkan (`pesan=invalid_input`).
5. Nilai BMR dihitung ulang menggunakan rumus Mifflin-St Jeor:
   $$\text{BMR}_{\text{Pria}} = (10 \times \text{BB}) + (6.25 \times \text{TB}) - (5 \times \text{Umur}) + 5$$
   $$\text{BMR}_{\text{Wanita}} = (10 \times \text{BB}) + (6.25 \times \text{TB}) - (5 \times \text{Umur}) - 161$$
6. Nilai TDEE dihitung secara dinamis:
   $$\text{TDEE} = \text{round}(\text{BMR} \times \text{Faktor Aktivitas Aktual}, 0)$$
7. Kolom `aktivitas` dan `tdee` diperbarui bersamaan di database, mengeliminasi bug multiplier statis 1.55.

---

## 7. Standarisasi Smart Storage

Mekanisme penyimpanan hasil evaluasi kesehatan menerapkan pemisahan yang konsisten:

```
[ Form Kalkulator Disubmit ]
             │
             ▼
   [ Validasi CSRF & Input ]
             │
             ├──(Validasi Gagal)──► [ Redirect ke Form (?pesan=invalid_input) ]
             │
             ▼ (Validasi Berhasil)
   [ Hitung Formula Medis ]
             │
             ▼
   [ Cek Status Sesi Autentikasi ]
             │
      ┌──────┴─────────────────────────────────┐
      ▼ (Guest / Tamu)                         ▼ (Member / Login)
[ user_id = NULL ]                      [ user_id = $_SESSION['user_id'] ]
[ HANYA simpan ke $_SESSION ]            [ INSERT ke Database dengan user_id ]
[ TIDAK ada INSERT ke DB ]              [ Simpan snapshot ke $_SESSION ]
      │                                        │
      └──────┬─────────────────────────────────┘
             ▼
   [ Redirect HTTP 302 ke Halaman Hasil (*_hasil.php) ] (Pola Post/Redirect/Get)
             │
             ▼
   [ Render Tampilan Hasil ]
```

---

## 8. Pengamanan Data Ownership & Akses Rekam Medis

Pengamanan akses data mengadopsi fungsi penjaga gerbang terpusat `assert_record_access()` pada `includes/auth_helper.php`:

1. **Pencegahan IDOR:**
   Ketika endpoint `*_hasil.php?id=X` atau file manajemen dipanggil dengan ID tertentu:
   * Query mengambil rekaman dari database.
   * Jika kolom `user_id` pada rekaman tersebut berisi ID akun, sistem memverifikasi apakah akun yang sedang aktif memiliki `user_id` yang sama.
   * Jika bukan pemilik dan bukan administrator, server langsung menghentikan proses dan mengembalikan respons **HTTP 403 Forbidden**.
2. **Perlakuan Rekam Data Legacy (`user_id = NULL`):**
   * Rekam data yang tersimpan sebelum Phase 1B (tanpa `user_id`) dikategorikan sebagai rekam data anonim warisan.
   * Member biasa dilarang mengakses rekam data ini (HTTP 403 Forbidden).
   * Hanya pengguna dengan peran `admin` yang memiliki wewenang untuk melihat, mengedit, atau menghapus data legacy tersebut.
3. **Pencegahan Manipulasi Parameter Kepemilikan:**
   * Nilai `user_id` yang disertakan oleh penyerang melalui parameter URL `$_GET['user_id']` atau body form `$_POST['user_id']` diabaikan sepenuhnya oleh backend.
   * Identitas pemilik rekam medis secara absolut ditentukan oleh `$_SESSION['user_id']` yang tersimpan di sesi server terenkripsi.
4. **Proteksi Aksi Hapus & Ubah:**
   * Seluruh aksi mutasi data wajib menggunakan metode HTTP `POST` dan memvalidasi `require_csrf_token()`. Permintaan penghapusan via `GET` ditolak dengan HTTP 405 Method Not Allowed.

---

## 9. Hasil Pengujian Lengkap (30/30 Skenario Lulus)

Pengujian otomatis Phase 2A dijalankan pada berkas database pengujian terpisah (`healthcheck_test_phase2a.sqlite`) menggunakan script `tests_phase2a_runner.php`:

| No | Kategori | Skenario Pengujian | Hasil Observasi | Status |
| :-: | :---: | :--- | :--- | :-: |
| 1 | **BMR** | Perhitungan BMR Laki-laki (70kg, 175cm, 25thn) | BMR terhitung tepat 1674 kkal sesuai formula Mifflin-St Jeor | **PASS** |
| 2 | **BMR** | Perhitungan BMR Perempuan (55kg, 160cm, 25thn) | BMR terhitung tepat 1264 kkal sesuai formula Mifflin-St Jeor | **PASS** |
| 3 | **BMR** | TDEE untuk 5 pilihan tingkat aktivitas | TDEE untuk faktor 1.2, 1.375, 1.55, 1.725, 1.9 terhitung 100% presisi | **PASS** |
| 4 | **BMR** | Edit BMR mempertahankan multiplier asli | Rekam aktivitas 1.2 di-update beratnya, TDEE tetap menggunakan pengali 1.2 (bukan static 1.55) | **PASS** |
| 5 | **BMR** | Perubahan aktivitas memperbarui TDEE | Mengubah aktivitas dari 1.2 ke 1.725 menghasilkan TDEE baru 2922 kkal | **PASS** |
| 6 | **BMR** | Input aktivitas ilegal/manipulasi ditolak | Submisi aktivitas `99.9` ditolak server dengan redirect `pesan=invalid_input` | **PASS** |
| 7 | **BMR** | Dropdown aktivitas ter-select benar saat dibuka ulang | HTML form edit menampilkan atribut `selected="selected"` pada opsi 1.725 | **PASS** |
| 8 | **Profil** | Auto-fill kalkulator member profil lengkap | Form BMI, BMR, HR, PHQ-4 terisi otomatis (Nama, JK, Umur:30, BB:68, TB:174.5) | **PASS** |
| 9 | **Profil** | Fallback member profil parsial | Umur fallback ke 25, BB/TB fallback ke placeholder kosong | **PASS** |
| 10 | **Profil** | Guest mengisi form dari nilai awal aman | Form guest tidak tercemar data profil akun manapun | **PASS** |
| 11 | **Profil** | Input kalkulator tidak mengubah profil akun | Member hitung dengan 85kg/180cm, tabel `user_profile` tetap tersimpan 65kg/170cm | **PASS** |
| 12 | **Smart Storage** | Guest BMI tanpa INSERT ke database | Jumlah baris tabel `cek_bmi` tidak bertambah setelah kalkulasi guest | **PASS** |
| 13 | **Smart Storage** | Guest BMR tanpa INSERT ke database | Jumlah baris tabel `cek_bmr` tidak bertambah setelah kalkulasi guest | **PASS** |
| 14 | **Smart Storage** | Guest Detak Jantung tanpa INSERT ke database | Jumlah baris tabel `cek_jantung` tidak bertambah setelah kalkulasi guest | **PASS** |
| 15 | **Smart Storage** | Guest PHQ-4 tanpa INSERT ke database | Jumlah baris tabel `cek_mental` tidak bertambah setelah kalkulasi guest | **PASS** |
| 16 | **Smart Storage** | Member BMI menyimpan record terikat user_id | Baris tersimpan di `cek_bmi` dengan `user_id = 2` | **PASS** |
| 17 | **Smart Storage** | Member BMR menyimpan record terikat user_id | Baris tersimpan di `cek_bmr` dengan `user_id = 2` | **PASS** |
| 18 | **Smart Storage** | Member Jantung menyimpan record terikat user_id | Baris tersimpan di `cek_jantung` dengan `user_id = 2` | **PASS** |
| 19 | **Smart Storage** | Member PHQ-4 menyimpan record terikat user_id | Baris tersimpan di `cek_mental` dengan `user_id = 2` | **PASS** |
| 20 | **Keamanan** | User A dilarang membaca hasil User B | Permintaan `bmi_hasil.php?id=<UserB>` oleh User A dibalas HTTP 403 Forbidden | **PASS** |
| 21 | **Keamanan** | User A dilarang mengubah hasil User B | Permintaan update ke rekaman User B oleh User A diblokir server | **PASS** |
| 22 | **Keamanan** | User A dilarang menghapus hasil User B | Permintaan hapus ke rekaman User B oleh User A diblokir server | **PASS** |
| 23 | **Keamanan** | Manipulasi `user_id` via request body diabaikan | Parameter `user_id=1` disuntikkan, sistem tetap mengikat ke `user_id=2` dari sesi | **PASS** |
| 24 | **Keamanan** | POST tanpa CSRF token ditolak | Permintaan POST tanpa token keamanan dibalas HTTP 403 Forbidden | **PASS** |
| 25 | **Keamanan** | Penghapusan data via GET ditolak | Akses GET ke file hapus dibalas HTTP 405 Method Not Allowed | **PASS** |
| 26 | **Keamanan** | Record legacy diblokir untuk member biasa | Member diblokir 403 saat akses record legacy; Administrator diizinkan | **PASS** |
| 27 | **Regresi** | 4 Kalkulator berfungsi normal end-to-end | Form BMI, BMR, Detak Jantung, PHQ-4 merespons HTTP 200 OK | **PASS** |
| 28 | **Regresi** | Akurasi formula dan batas klasifikasi klinis | Tanaka Max HR 191 BPM, ambang batas BMI Asia, dan skala PHQ-4 100% akurat | **PASS** |
| 29 | **Regresi** | Idempotensi migration runner | Eksekusi ulang migrasi menghasilkan *"Nothing to migrate"*, skema database utuh | **PASS** |
| 30 | **Regresi** | Linting seluruh file repositori (`php -l`) | 73 file PHP repositori lulus pengujian sintaks tanpa error | **PASS** |

### Ringkasan Angka Pengujian:
* **Total Skenario Uji:** 30
* **Lulus (PASS):** 30
* **Gagal (FAIL):** 0
* **Tingkat Keberhasilan:** **100%**
* **Pengujian Regression Phase 1A & 1B:** Keduanya lulus 100% (12/12 dan 14/14).

---

## 10. Hal yang Belum Terselesaikan (Scope Luar Phase 2A)

1. **Kalkulator Biometrik Baru (Batch 1 & 2):** Fitur kalkulator TDEE Mandiri & Makronutrisi, WHtR, Estimasi Lemak Tubuh US Navy, Kebutuhan Cairan, Tekanan Darah, Kualitas Tidur, dll. belum dibangun (merupakan cakupan Phase 2B, 3, dan 4).
2. **Antarmuka Pengaturan Profil Pengguna di Frontend:** Tabel `user_profile` dan helper backend telah berfungsi penuh, namun form antarmuka mandiri untuk mengedit profil (`user/profile.php`) belum dibuat.
3. **Personal Dashboard & Halaman Riwayat Komprehensif:** Antarmuka visual riwayat terpadu untuk member dijadwalkan pada Phase 5.

---

## 11. Rekomendasi Teknis untuk Phase 2B

1. **Pembangunan Halaman Pengaturan Profil Kesehatan (`user/profile.php`):**
   * Menyediakan antarmuka mandiri bagi pengguna terdaftar untuk memperbarui nama lengkap, jenis kelamin, tanggal lahir, tinggi badan, dan berat badan dasar.
   * Dilengkapi validasi server-side dan CSRF token.
2. **Pembangunan Kalkulator Biometrik Fisik Lanjutan (Fase 3):**
   * Mengembangkan Kalkulator TDEE Mandiri & Rasio Makronutrisi (`tools/tdee/`).
   * Mengembangkan Kalkulator WHtR (*Waist-to-Height Ratio*) (`tools/whtr/`).
   * Mengembangkan Kalkulator Estimasi Lemak Tubuh US Navy (`tools/body-fat/`).
   * Mengembangkan Kalkulator Kebutuhan Cairan Harian (`tools/cairan/`).
3. **Replikasi Standar Arsitektur Phase 2A:**
   * Seluruh kalkulator baru wajib langsung mengadopsi integrasi profil auto-fill, Smart Storage (guest vs member), isolasi data kepemilikan, dan proteksi CSRF yang telah dibakukan pada fase ini.
