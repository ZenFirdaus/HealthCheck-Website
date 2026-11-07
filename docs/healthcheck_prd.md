# HealthCheck — Website Cek Kesehatan

## 1. Peran AI Agent

Kamu bertindak sebagai **Senior Full-Stack Developer, UI/UX Designer, dan Code Reviewer**.

Tugasmu adalah menyempurnakan website cek kesehatan yang sudah ada dengan mempertahankan fitur yang masih berfungsi, memperbaiki kekurangan, dan meningkatkan kualitas UI/UX agar terlihat minimalis, modern, profesional, dan mudah digunakan.

**PENTING:** Website sudah dibuat sebelumnya dan belum sempurna. Jangan langsung mengoding atau mengubah file sebelum melakukan analisis terhadap project yang ada.

---

## 2. Aturan Utama: Analisis Sebelum Eksekusi

Sebelum melakukan perubahan kode, wajib melakukan tahapan berikut.

### Tahap 1 — Audit Project

Periksa dan pahami:

* Struktur folder dan file project.
* Framework, bahasa pemrograman, dan library yang digunakan.
* Struktur frontend dan backend.
* Database dan relasi tabel (jika ada).
* Fitur yang sudah tersedia.
* Alur navigasi dan interaksi pengguna.
* Sistem autentikasi dan otorisasi (jika ada).
* API dan integrasi eksternal.
* Validasi form dan kalkulasi kesehatan.
* Responsive design.
* Komponen UI yang dapat digunakan kembali.

### Tahap 2 — Audit UI/UX

Evaluasi website yang sudah ada berdasarkan:

* Konsistensi desain.
* Hierarki visual.
* Pemilihan warna dan tipografi.
* Spacing dan alignment.
* Keterbacaan teks.
* Tampilan mobile, tablet, dan desktop.
* Navigasi dan kemudahan menemukan fitur.
* Empty state, loading state, dan error state.
* Accessibility.
* Konsistensi tombol, form, card, dan komponen lainnya.

Jika tersedia, jalankan website secara lokal dan gunakan hasil observasi halaman yang ada. Jangan hanya mengandalkan nama file atau asumsi desain.

### Tahap 3 — Audit Fungsional

Identifikasi:

* Fitur yang sudah berjalan dengan benar.
* Fitur yang belum selesai.
* Bug dan error yang ditemukan.
* Validasi input yang kurang.
* Perhitungan yang berpotensi tidak akurat.
* Duplikasi kode.
* Kode yang terlalu kompleks.
* Risiko keamanan dan privasi.
* Fitur yang tidak konsisten dengan kebutuhan produk.

Jangan menyatakan suatu bug ada jika belum diperiksa atau dibuktikan.

### Tahap 4 — Buat Laporan Analisis

Sebelum implementasi, tampilkan laporan yang mencakup:

1. Ringkasan kondisi project.
2. Teknologi yang terdeteksi.
3. Fitur yang sudah tersedia.
4. Fitur yang belum lengkap.
5. Temuan UI/UX.
6. Temuan kode dan arsitektur.
7. Prioritas perbaikan.
8. Rencana perubahan file.
9. Risiko perubahan dan dependensi.
10. Rekomendasi tahap implementasi.

Gunakan kategori prioritas:

* **P0 — Critical:** masalah yang menyebabkan website tidak dapat digunakan atau risiko keamanan serius.
* **P1 — High:** fitur penting rusak atau alur utama terganggu.
* **P2 — Medium:** perbaikan UX, validasi, dan struktur kode.
* **P3 — Low:** penyempurnaan visual atau optimasi tambahan.

### Tahap 5 — Persetujuan Implementasi

Setelah analisis selesai:

* Jangan langsung mengubah kode jika scope perubahan belum jelas.
* Tampilkan rencana implementasi yang ringkas dan konkret.
* Jika ditemukan perubahan besar, konflik kebutuhan, atau risiko kehilangan fitur, minta persetujuan sebelum melanjutkan.
* Jika scope kecil, jelas, dan sudah sesuai instruksi, lakukan implementasi setelah audit dan verifikasi kebutuhan.
* Jangan meminta persetujuan berulang kali untuk setiap perubahan kecil yang sudah tercakup dalam scope yang disetujui.

---

## 3. Product Requirements Document (PRD)

### 3.1 Nama Produk

**HealthCheck**

Website cek kesehatan dengan berbagai kalkulator dan alat pemeriksaan kesehatan yang mudah digunakan, informatif, dan memiliki tampilan profesional.

### 3.2 Tujuan Produk

* Membantu pengguna memperoleh informasi awal mengenai kondisi kesehatan.
* Menyediakan alat kesehatan dalam satu website.
* Menyajikan hasil perhitungan dengan bahasa sederhana.
* Meningkatkan kesadaran pengguna terhadap kesehatan.
* Menyediakan pengalaman digital yang nyaman pada desktop maupun mobile.

Website bukan pengganti dokter, diagnosis medis, atau konsultasi tenaga kesehatan profesional.

### 3.3 Referensi Fitur

Gunakan kategori fitur dari website referensi:

https://hellosehat.com/health-tools/

Jangan menyalin desain, kode, teks, ilustrasi, atau identitas visual website referensi.

Gunakan referensi hanya untuk memahami jenis alat kesehatan dan kebutuhan pengguna. Buat desain, struktur halaman, microcopy, dan implementasi sendiri.

### 3.4 Target Pengguna

* Mahasiswa dan pengguna umum.
* Orang yang ingin mengetahui informasi kesehatan dasar.
* Pengguna yang membutuhkan kalkulator kesehatan sederhana.
* Pengguna mobile yang menginginkan akses cepat.

### 3.5 Fitur Produk

Implementasikan fitur berdasarkan kondisi website yang sudah tersedia. Jangan menghapus fitur yang berfungsi tanpa alasan dan persetujuan yang relevan.

#### A. Kalkulator BMI

Input:

* Berat badan.
* Tinggi badan.

Output:

* Nilai BMI.
* Kategori BMI sesuai referensi klasifikasi yang dipilih.
* Penjelasan hasil.
* Catatan keterbatasan BMI.

Validasi:

* Input wajib diisi.
* Nilai harus numerik dan berada dalam rentang yang masuk akal.
* Tinggi badan dan berat badan tidak boleh nol atau negatif.
* Gunakan satuan yang jelas.
* Perhitungan tidak boleh menghasilkan nilai yang tidak valid.

#### B. Kalkulator Kebutuhan Kalori

Input dapat mencakup:

* Usia.
* Jenis kelamin, jika diperlukan rumus.
* Berat badan.
* Tinggi badan.
* Tingkat aktivitas.

Output:

* Estimasi kebutuhan kalori.
* Penjelasan bahwa hasil merupakan perkiraan.
* Informasi asumsi rumus yang digunakan.

Pastikan rumus dan interpretasi hasil terdokumentasi dengan jelas. Jangan memberikan rekomendasi medis atau diet personal tanpa dasar yang sesuai.

#### C. Kalkulator Detak Jantung

Input:

* Usia atau parameter yang diperlukan.
* Data detak jantung sesuai jenis alat yang dipilih.

Output:

* Hasil perhitungan.
* Penjelasan mengenai keterbatasan hasil.
* Informasi kapan pengguna perlu mencari bantuan medis, jika relevan.

Jangan menyimpulkan diagnosis dari satu pengukuran.

#### D. Self-Assessment Kesehatan Mental

Contoh kategori:

* Stres.
* Kecemasan.
* Suasana hati.

Ketentuan:

* Gunakan instrumen yang memiliki sumber dan ketentuan penggunaan yang sesuai.
* Jangan mengklaim hasil sebagai diagnosis.
* Jelaskan cara interpretasi skor.
* Hindari bahasa yang menghakimi.
* Jika jawaban menunjukkan kemungkinan kondisi serius, tampilkan anjuran mencari bantuan profesional secara hati-hati.
* Jangan mengarang instrumen medis atau kriteria klinis.

#### E. Kategori Alat Kesehatan Lainnya

Tambahkan secara bertahap sesuai kebutuhan dan kelayakan implementasi:

* Kesehatan jantung.
* Kesehatan anak dan keluarga.
* Kesehatan reproduksi.
* Risiko penyakit.
* Kesehatan umum.

Prioritaskan fitur yang dapat diimplementasikan dengan sumber yang dapat diverifikasi dan validasi yang memadai.

---

## 4. Prinsip Pengembangan

### 4.1 Analisis Existing Code

* Utamakan reuse komponen dan logic yang sudah tersedia.
* Jangan membuat ulang fitur yang sudah berfungsi tanpa alasan.
* Jangan mengganti framework atau arsitektur utama tanpa kebutuhan yang jelas.
* Jangan menghapus database, route, API, atau komponen penting secara sembarangan.
* Periksa dependensi sebelum menambahkan library baru.
* Pertahankan kompatibilitas dengan konfigurasi project.

### 4.2 Kode Simple tetapi Profesional

Kualitas kode harus mengutamakan:

* Struktur yang mudah dipahami.
* Nama variabel dan fungsi yang deskriptif.
* Fungsi yang memiliki tanggung jawab jelas.
* Komponen reusable.
* Validasi input yang konsisten.
* Error handling yang sesuai.
* Minimalkan duplikasi kode.
* Hindari abstraksi berlebihan.
* Hindari kode yang terlalu panjang dalam satu file.
* Jangan menambahkan arsitektur kompleks jika kebutuhan project masih sederhana.

**Prinsip utama:**

> Simple code, clean structure, professional result.

Kode sederhana bukan berarti mengabaikan keamanan, validasi, maintainability, dan kualitas pengalaman pengguna.

### 4.3 Perubahan Minimal yang Terukur

Sebelum mengubah file:

* Pahami dependensi file tersebut.
* Identifikasi fitur yang berpotensi terdampak.
* Lakukan perubahan sekecil mungkin untuk mencapai tujuan.
* Hindari refactor besar tanpa kebutuhan.
* Jangan mengubah bagian yang tidak berkaitan dengan scope.
* Verifikasi bahwa fitur lama masih berjalan setelah perubahan.

---

## 5. Arsitektur Sistem

### 5.1 Prinsip Arsitektur

Gunakan arsitektur yang sesuai dengan teknologi existing project.

Pisahkan tanggung jawab antara:

* Presentation layer.
* Business logic.
* Data access.
* Validation.
* Utility atau kalkulasi.

Tidak wajib menggunakan pola arsitektur kompleks jika project tidak membutuhkannya.

### 5.2 Frontend

Tanggung jawab:

* Menampilkan halaman dan komponen UI.
* Mengelola input pengguna.
* Menampilkan loading, error, dan hasil.
* Melakukan validasi dasar untuk UX.
* Memanggil service atau endpoint yang diperlukan.

### 5.3 Backend

Jika backend tersedia:

* Menangani request dan response.
* Melakukan validasi server-side.
* Menjalankan logic yang memerlukan backend.
* Mengelola database melalui akses yang aman.
* Menghindari kebocoran data pribadi.

Jika aplikasi hanya memerlukan kalkulasi lokal, jangan menambahkan backend secara berlebihan.

### 5.4 Database

Jika menggunakan database:

* Identifikasi tabel existing terlebih dahulu.
* Jangan mengubah skema tanpa analisis dampak.
* Gunakan migration apabila framework mendukung.
* Jangan menyimpan data sensitif tanpa kebutuhan yang jelas.
* Jangan menyimpan data kesehatan pribadi lebih lama dari yang diperlukan.
* Gunakan akses database yang aman.

---

## 6. Rules Produk dan Medis

### 6.1 Disclaimer

Setiap alat kesehatan harus memberikan konteks penggunaan yang tepat.

Contoh:

> Hasil yang ditampilkan merupakan informasi dan perkiraan berdasarkan data yang kamu masukkan. Hasil ini bukan diagnosis medis dan tidak menggantikan konsultasi dengan tenaga kesehatan.

Sesuaikan disclaimer dengan jenis fitur. Jangan menggunakan satu disclaimer umum untuk menutupi risiko khusus setiap alat.

### 6.2 Validasi

* Semua input wajib memiliki validasi.
* Gunakan batas nilai yang relevan dengan jenis alat.
* Tampilkan pesan error yang mudah dipahami.
* Jangan menerima nilai kosong jika input wajib.
* Jangan menampilkan hasil jika input tidak valid.
* Gunakan validasi frontend dan backend jika data dikirim ke server.

### 6.3 Privasi

* Jangan mengumpulkan data pribadi yang tidak diperlukan.
* Hindari penyimpanan data kesehatan secara default jika tidak diperlukan.
* Jangan menampilkan data pengguna kepada pihak lain tanpa dasar yang sesuai.
* Jangan menuliskan data kesehatan sensitif ke log.
* Gunakan praktik keamanan yang sesuai dengan teknologi yang digunakan.

### 6.4 Keamanan

* Validasi input di server jika backend digunakan.
* Gunakan query parameterized atau ORM yang aman.
* Jangan menyimpan secret di source code.
* Jangan mengekspos kredensial database.
* Periksa otorisasi untuk setiap fitur yang memerlukan akun.
* Tangani error tanpa membocorkan detail internal sistem.

---

## 7. UI/UX — Minimalis Modern

### 7.1 Konsep Desain

Gunakan gaya:

* Minimalis.
* Modern.
* Profesional.
* Bersih dan tidak ramai.
* Mudah dipahami pengguna awam.
* Mobile-first.
* Konsisten.

Jangan meniru tampilan website referensi.

### 7.2 Arah Visual

Gunakan visual yang terasa seperti produk digital kesehatan modern:

* Background netral dan terang.
* Warna aksen hijau atau teal yang tenang.
* Kontras teks yang baik.
* Border dan shadow yang halus.
* Rounded corners secukupnya.
* White space yang cukup.
* Ikon yang konsisten.
* Hindari gradient berlebihan.
* Hindari terlalu banyak warna aksen.

Warna dan ukuran harus menyesuaikan desain existing jika sudah memiliki identitas visual yang baik.

### 7.3 Halaman Utama

Komponen:

1. Navbar.
2. Hero section dengan headline yang jelas.
3. Search atau pencarian alat kesehatan.
4. Kategori alat kesehatan.
5. Featured health tools.
6. Penjelasan singkat tentang website.
7. Disclaimer.
8. Footer.

Contoh headline:

> Pahami Kesehatanmu, Mulai dari Sini.

Contoh subheadline:

> Gunakan alat kesehatan sederhana untuk mendapatkan informasi awal berdasarkan data yang kamu masukkan.

Hindari klaim seperti "Diagnosis Akurat" atau "Ketahui Penyakitmu dengan Pasti".

### 7.4 Halaman Kategori

Komponen:

* Breadcrumb.
* Judul kategori.
* Deskripsi singkat.
* Grid card alat kesehatan.
* Search dan filter bila diperlukan.
* Empty state jika tidak ada hasil.

### 7.5 Halaman Kalkulator

Struktur:

1. Breadcrumb.
2. Judul alat.
3. Penjelasan singkat.
4. Form input.
5. Tombol hitung.
6. Result card.
7. Penjelasan hasil.
8. Disclaimer.
9. Related tools.

Pastikan pengguna dapat memahami fungsi alat sebelum mengisi data.

### 7.6 Komponen Reusable

Buat komponen yang dapat digunakan kembali, seperti:

* Navbar.
* Footer.
* ToolCard.
* CategoryCard.
* InputField.
* SelectField.
* PrimaryButton.
* ResultCard.
* Alert.
* EmptyState.
* LoadingState.
* Breadcrumb.
* Modal jika diperlukan.

Jangan membuat komponen reusable hanya untuk menghindari beberapa baris kode. Gunakan abstraksi jika memang membantu konsistensi dan pemeliharaan.

### 7.7 Responsive

Website harus berfungsi dengan baik pada:

* Mobile.
* Tablet.
* Desktop.

Perhatikan:

* Ukuran font.
* Lebar form.
* Grid layout.
* Padding.
* Ukuran tombol.
* Navigasi mobile.
* Overflow horizontal.
* Keterbacaan result card.

Jangan hanya mengecilkan tampilan desktop. Atur layout agar sesuai dengan kebutuhan layar kecil.

---

## 8. Workflow Implementasi AI Agent

### Fase 1 — Discovery

1. Scan project.
2. Identifikasi teknologi.
3. Jalankan project jika memungkinkan.
4. Baca dokumentasi yang tersedia.
5. Identifikasi halaman dan fitur.
6. Audit UI/UX.
7. Audit arsitektur dan kode.

**Output:** Laporan analisis existing project.

### Fase 2 — Planning

1. Susun daftar masalah.
2. Prioritaskan perbaikan.
3. Tentukan scope.
4. Identifikasi file yang akan diubah.
5. Tentukan komponen yang bisa digunakan kembali.
6. Tentukan strategi testing.

**Output:** Rencana implementasi.

### Fase 3 — Implementation

1. Perbaiki masalah prioritas.
2. Pertahankan fitur existing yang berfungsi.
3. Implementasikan perubahan UI/UX.
4. Gunakan kode sederhana dan profesional.
5. Tambahkan validasi yang diperlukan.
6. Hindari perubahan di luar scope.
7. Jangan menambahkan fitur yang tidak dibutuhkan tanpa alasan.

### Fase 4 — Testing

Lakukan pemeriksaan:

* Build atau compile.
* Lint atau static analysis jika tersedia.
* Validasi form.
* Perhitungan alat kesehatan.
* Navigasi.
* Responsive layout.
* Error state.
* Fitur existing yang terdampak.

Jangan menyatakan testing berhasil jika belum benar-benar dijalankan.

### Fase 5 — Final Review

Tampilkan:

* Ringkasan perubahan.
* Daftar file yang diubah.
* Fitur yang diperbaiki.
* Testing yang dijalankan.
* Error yang masih tersisa.
* Rekomendasi lanjutan.

---

## 9. Aturan Git dan File

* Jangan menghapus file tanpa alasan yang jelas.
* Jangan mengubah file environment berisi secret.
* Jangan meng-commit credentials.
* Jangan mereset atau menghapus perubahan pengguna tanpa izin.
* Periksa status Git sebelum perubahan besar.
* Gunakan commit yang deskriptif jika diminta.
* Jangan menganggap file generated sebagai source code utama.
* Jangan mengubah konfigurasi deployment tanpa analisis dampak.

---

## 10. Format Laporan Sebelum Coding

Gunakan format berikut:

### A. Project Summary

* Nama project:
* Framework:
* Bahasa:
* Database:
* Status project:
* Halaman utama:

### B. Existing Features

| Fitur             | Status      | Catatan |
| ----------------- | ----------- | ------- |
| Homepage          | Ada / Tidak | ...     |
| Kalkulator BMI    | Ada / Tidak | ...     |
| Kalkulator Kalori | Ada / Tidak | ...     |
| Detak Jantung     | Ada / Tidak | ...     |
| Self-Assessment   | Ada / Tidak | ...     |

Sesuaikan tabel dengan fitur yang benar-benar ditemukan.

### C. Findings

| No | Masalah | Prioritas | File | Solusi |
| -- | ------- | --------- | ---- | ------ |
| 1  | ...     | P0–P3     | ...  | ...    |

### D. Implementation Plan

1. ...
2. ...
3. ...

### E. Verification

* [ ] Build berhasil.
* [ ] Static analysis dijalankan.
* [ ] Fitur utama diuji.
* [ ] Responsive diperiksa.
* [ ] Tidak ada perubahan di luar scope yang tidak disengaja.

Gunakan hanya status yang benar-benar sudah diverifikasi.

---

## 11. Kriteria Keberhasilan

Website dianggap mengalami peningkatan apabila:

* Fitur existing yang berfungsi tetap dapat digunakan.
* Masalah prioritas berhasil ditangani.
* UI terlihat konsisten dan profesional.
* Navigasi mudah dipahami.
* Tampilan responsive.
* Input memiliki validasi yang sesuai.
* Hasil kalkulasi ditampilkan secara jelas.
* Kode mudah dipahami dan dirawat.
* Tidak ada kredensial atau data sensitif yang terekspos.
* Testing dilakukan sesuai perubahan.
* Tidak ada klaim keberhasilan tanpa verifikasi.

---

## 12. Instruksi Awal untuk AI Agent

Mulai dengan **ANALYZE ONLY**.

Jangan langsung mengubah file atau mengimplementasikan fitur.

Lakukan audit terhadap website yang sudah ada, pahami struktur project, evaluasi UI/UX, dan identifikasi kekurangan yang perlu diperbaiki.

Setelah analisis selesai, tampilkan laporan:

1. Kondisi existing project.
2. Fitur yang sudah tersedia.
3. Kekurangan dan bug yang ditemukan.
4. Evaluasi UI/UX.
5. Evaluasi kualitas kode.
6. Prioritas perbaikan.
7. Rencana implementasi.
8. File yang akan diubah.

Setelah scope implementasi jelas dan disetujui jika diperlukan, lanjutkan dengan perubahan yang terukur.

**Prioritas utama:**

> Pahami project yang sudah ada terlebih dahulu. Jangan asal mengoding. Pertahankan fitur yang berfungsi, gunakan kode simple tetapi profesional, dan hasilkan UI minimalis modern yang memberikan pengalaman pengguna yang baik.
