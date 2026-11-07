# Spesifikasi Fungsional & Medis Fitur Baru HealthCheck

**Status Dokumen:** Technical & Medical Feature Specification  
**Peran:** Product Analyst, Clinical Health Informatics Architect & Senior Full-Stack Developer  
**Prinsip Dasar:** Seluruh fitur kalkulasi harus berlandaskan metodologi ilmiah yang tervalidasi secara klinis, transparan dalam batasan estimasi, dan tidak memberikan klaim diagnostik definitif. Setiap formula klinis dicantumkan sumber pustakanya. Bagian yang memerlukan konfirmasi lebih lanjut ditandai dengan label **[Perlu Validasi Dewan Medis]**.

---

## 1. Kalkulator TDEE (Total Daily Energy Expenditure) & Makronutrisi

### 1.1 Tujuan Fitur
Menghitung total energi yang dibakar tubuh dalam 24 jam dengan mempertimbangkan Basal Metabolic Rate (BMR) dan Thermic Effect of Activity (TEA), serta memberikan rekomendasi distribusi makronutrisi (karbohidrat, protein, lemak) sesuai sasaran berat badan pengguna.

### 1.2 Target Pengguna
Individu yang merencanakan program manajemen berat badan (defisit kalori terkontrol, pemeliharaan, atau peningkatan massa otot), penggiat kebugaran, dan masyarakat umum yang ingin memahami alokasi energi harian.

### 1.3 Input yang Diperlukan
* `nama_user` (Teks, wajib)
* `jenis_kelamin` (Laki-laki / Perempuan, wajib)
* `umur` (10 - 110 tahun, wajib)
* `berat_badan` (20 - 300 kg, wajib)
* `tinggi_badan` (80 - 250 cm, wajib)
* `tingkat_aktivitas` (5 tingkatan aktivitas, wajib)
* `target_tujuan` (Penurunan BB Ringan -300 kkal, Defisit Standar -500 kkal, Pemeliharaan 0 kkal, Surplus Ringan +300 kkal, Surplus Otot +500 kkal)
* `preferensi_diet` (Seimbang 50C/20P/30F, Rendah Karbohidrat 25C/35P/40F, Tinggi Protein 40C/35P/25F)

### 1.4 Rumus & Metode Perhitungan
1. **Formula BMR:** Menggunakan formula Mifflin-St Jeor:
   $$\text{BMR}_{\text{Pria}} = (10 \times \text{BB}) + (6.25 \times \text{TB}) - (5 \times \text{Umur}) + 5$$
   $$\text{BMR}_{\text{Wanita}} = (10 \times \text{BB}) + (6.25 \times \text{TB}) - (5 \times \text{Umur}) - 161$$
2. **Kalkulasi TDEE:**
   $$\text{TDEE} = \text{BMR} \times \text{Aktivitas Multiplier}$$
   * *Sedentary (banyak duduk, minim gerak):* 1.200
   * *Lightly Active (olahraga ringan 1-3 hari/mgg):* 1.375
   * *Moderately Active (olahraga sedang 3-5 hari/mgg):* 1.550
   * *Very Active (olahraga berat 6-7 hari/mgg):* 1.725
   * *Extra Active (pekerja fisik/atlet berat):* 1.900
3. **Target Kalori Sasaran:**
   $$\text{Target Kalori} = \max(\text{Batas Aman}, \text{TDEE} + \Delta\text{Kalori})$$
   *Batas aman klinis:* Pria tidak disarankan $< 1.500$ kkal/hari, wanita $< 1.200$ kkal/hari tanpa supervisi dokter.
4. **Distribusi Makronutrisi (Gram):**
   * 1 gram Karbohidrat = 4 kkal
   * 1 gram Protein = 4 kkal
   * 1 gram Lemak = 9 kkal
   $$\text{Gram Nutrisi} = \frac{\text{Target Kalori} \times \text{Persentase Nutrisi}}{\text{Kalori per Gram}}$$

### 1.5 Output
* Angka BMR murni (kkal/hari).
* Angka TDEE pemeliharaan (kkal/hari).
* Angka target kalori harian terarah.
* Diagram rasio dan gram makronutrisi: Gram Protein, Gram Karbohidrat, Gram Lemak per hari.
* Catatan klinis terkait defisit aman (maksimal 0.5 - 1 kg penurunan BB per minggu).

### 1.6 Validasi
* Berat (20 - 300 kg), Tinggi (80 - 250 cm), Umur (10 - 110 tahun).
* Peringatan otomatis jika target kalori berada di bawah batas minimum metabolisme organ vital (1.200 kkal untuk wanita, 1.500 kkal untuk pria).

### 1.7 Referensi Medis
* Mifflin MD, St Jeor ST, et al. *A new predictive equation for resting energy expenditure in healthy individuals.* Am J Clin Nutr. 1990;51(2):241-247.
* Academy of Nutrition and Dietetics. *Position of the Academy: Interventions for the Prevention and Treatment of Overweight and Obesity in Adults.* J Acad Nutr Diet. 2016.

### 1.8 Kebutuhan Database
Tabel `cek_tdee` (lihat `database_analysis.md`).

### 1.9 Hubungan Fitur Lain
Data BB & TB dapat langsung diambil dari data profil `user` atau kalkulator BMI. Hasil TDEE menjadi acuan target kalori pada *Personal Dashboard* dan modul *Habit Tracker*.

### 1.10 User Flow
1. Pengguna membuka `/tools/tdee.php`.
2. Pengguna memasukkan biometrik & memilih sasaran berat badan.
3. Sistem memvalidasi dan memproses perhitungan.
4. Halaman menampilkan hasil BMR, TDEE, sasaran kalori, dan visual makronutrisi.
5. Jika pengguna login, tombol "Simpan ke Profil" menyimpan data ke `cek_tdee` dan memperbarui target dashboard. Jika tamu, opsi simpan menawarkan pendaftaran akun.

---

## 2. Kalkulator WHtR (Waist-to-Height Ratio)

### 2.1 Tujuan Fitur
Menilai distribusi lemak abdominal (viseral) dan risiko kardiometabolik melalui rasio lingkar pinggang terhadap tinggi badan. WHtR terbukti secara medis lebih sensitif mendeteksi risiko sindrom metabolik dibandingkan BMI pada kelompok orang berotot (*false positive BMI*).

### 2.2 Target Pengguna
Orang dewasa usia $\ge 18$ tahun yang ingin mengevaluasi obesitas sentral dan risiko penyakit kardiovaskular.

### 2.3 Input yang Diperlukan
* `nama_user` (Teks, wajib)
* `jenis_kelamin` (Laki-laki / Perempuan, wajib)
* `tinggi_badan` (cm, wajib)
* `lingkar_pinggang` (cm, diukur setinggi pusar atau titik tengah antara tulang rusuk terbawah dan puncak krista iliaka, wajib)

### 2.4 Rumus & Klasifikasi
$$\text{WHtR} = \frac{\text{Lingkar Pinggang (cm)}}{\text{Tinggi Badan (cm)}}$$

**Klasifikasi Risiko Universal (Ashwell & Gibson Rule of Thumb: "Keep your waist circumference to less than half your height"):**
* $\text{WHtR} < 0.40$ : Kurang (Underweight Central) — Risiko malnutrisi.
* $0.40 \le \text{WHtR} \le 0.49$ : Sehat / Risiko Normal (No Increased Risk).
* $0.50 \le \text{WHtR} \le 0.59$ : Risiko Kardiometabolik Meningkat (Increased Risk).
* $\text{WHtR} \ge 0.60$ : Risiko Sangat Tinggi (Very High Risk) — Terkait erat dengan hipertensi, DMT2, dan aterosklerosis.

### 2.5 Output
* Nilai rasio WHtR numerik (misal: 0.46).
* Status kategori dan lencana warna (Hijau, Kuning, Merah).
* Rekomendasi batas lingkar pinggang maksimal yang sehat untuk tinggi badan pengguna ($\text{Batas Sehat} = 0.5 \times \text{TB}$).
* Penjelasan perbedaan lemak subkutan vs lemak viseral.

### 2.6 Validasi
* Tinggi badan: 80 - 250 cm.
* Lingkar pinggang: 30 - 200 cm.
* $\text{Lingkar Pinggang} < \text{Tinggi Badan} \times 1.5$.

### 2.7 Referensi Medis
* Ashwell M, Gibson S. *Waist-to-height ratio as an indicator of 'early health risk': simpler and more predictive than using a 'matrix' based on BMI and waist circumference.* BMJ Open. 2016;6(3):e010159.
* Browning LM, Hsieh SD, Ashwell M. *A systematic review of waist-to-height ratio as a screening tool for the prediction of cardiovascular disease and diabetes.* Nutr Res Rev. 2010;23(2):247-269.

### 2.8 Kebutuhan Database
Tabel `cek_whtr`.

### 2.9 Hubungan Fitur Lain
Dikombinasikan dengan skor BMI pada *Personal Dashboard* untuk membentuk matriks komprehensif status adipositas tubuh.

### 2.10 User Flow
Alur sama dengan standar kalkulator publik HealthCheck: Form -> Validasi -> Tampilan Hasil -> Penyimpanan Otomatis (jika login).

---

## 3. Kalkulator Estimasi Lemak Tubuh (US Navy Circumference Method)

### 3.1 Tujuan Fitur
Memperkirakan persentase massa lemak tubuh (*Body Fat Percentage*) dan massa tubuh tanpa lemak (*Lean Body Mass*) menggunakan pengukuran antropometri lingkar tubuh terstandar militer Amerika Serikat tanpa memerlukan alat laboratorium mahal seperti DEXA scan atau kaliper.

### 3.2 Target Pengguna
Penggiat fitness, atlet, dan individu yang ingin memantau penurunan lemak murni secara lebih akurat dibanding hanya mengamati timbangan berat badan.

### 3.3 Input yang Diperlukan
* `jenis_kelamin` (L/P, wajib)
* `umur` (15 - 90 tahun, wajib)
* `tinggi_badan` (cm, wajib)
* `berat_badan` (kg, wajib)
* `lingkar_leher` (cm, diukur tepat di bawah laring / jakun, wajib)
* `lingkar_pinggang` (cm, diukur di pusar untuk pria, di titik tersempit untuk wanita, wajib)
* `lingkar_pinggul` (cm, diukur pada lingkaran panggul/bokong terlebar — **Wajib untuk Wanita**, tidak diperlukan untuk Pria)

### 3.4 Rumus & Metode Perhitungan
Menggunakan **Persamaan Hodgdon & Beckett (US Navy Standard)**:
* **Untuk Pria:**
  $$\%BF = 495 / (1.0324 - 0.19077 \times \log_{10}(\text{pinggang} - \text{leher}) + 0.15456 \times \log_{10}(\text{tinggi})) - 450$$
* **Untuk Wanita:**
  $$\%BF = 495 / (1.29579 - 0.35004 \times \log_{10}(\text{pinggang} + \text{pinggul} - \text{leher}) + 0.22100 \times \log_{10}(\text{tinggi})) - 450$$
* **Komposisi Massa:**
  $$\text{Fat Mass (kg)} = \text{Berat Badan} \times \left(\frac{\%BF}{100}\right)$$
  $$\text{Lean Body Mass (kg)} = \text{Berat Badan} - \text{Fat Mass}$$

**Klasifikasi American Council on Exercise (ACE):**
| Kategori | Pria (% Lemak) | Wanita (% Lemak) |
| :--- | :---: | :---: |
| Lemak Esensial | 2 – 5% | 10 – 13% |
| Atlet | 6 – 13% | 14 – 20% |
| Kebugaran (*Fitness*) | 14 – 17% | 21 – 24% |
| Rata-rata (*Acceptable*) | 18 – 24% | 25 – 31% |
| Obesitas | $\ge 25\%$ | $\ge 32\%$ |

### 3.5 Output
* Estimasi persentase lemak tubuh ($\%BF$).
* Kategori kebugaran ACE.
* Massa Lemak (kg) vs Massa Otot/Tulang/Organ (*Lean Mass* kg).
* Rekomendasi komposisi tubuh yang ideal.

### 3.6 Validasi
* $\text{Lingkar Pinggang} > \text{Lingkar Leher}$ (untuk pria agar nilai logaritma tidak negatif/error numerik).
* $\text{Lingkar Pinggang} + \text{Lingkar Pinggul} > \text{Lingkar Leher}$ (untuk wanita).
* Batasan fisik: Leher 20 - 60 cm, Pinggang 40 - 200 cm, Pinggul 50 - 200 cm.

### 3.7 Referensi Medis
* Hodgdon JA, Beckett MB. *Prediction of percent body fat for U.S. Navy men and women from body circumferences and height.* Naval Health Research Center Reports. 1984; Report No. 84-29 & 84-11.
* American Council on Exercise (ACE). *Personal Trainer Manual (5th Edition).* 2014.

### 3.8 Kebutuhan Database
Tabel `cek_lemak_tubuh`.

### 3.9 Hubungan Fitur Lain
Memberikan konteks pada hasil BMI. Pengguna dengan BMI tinggi namun persentase lemak rendah akan diidentifikasi sebagai binaragawan/atlet tanpa stigma obesitas.

---

## 4. Kalkulator Kebutuhan Cairan Harian

### 4.1 Tujuan Fitur
Menentukan volume asupan air putih minimum harian guna mencegah dehidrasi, mendukung filtrasi ginjal, dan menjaga termoregulasi tubuh, disesuaikan dengan berat badan, intensitas latihan, dan iklim lingkungan.

### 4.2 Target Pengguna
Semua kelompok umur, terutama pekerja kantoran, pekerja luar ruangan, dan atlet.

### 4.3 Input yang Diperlukan
* `berat_badan` (kg, wajib)
* `durasi_olahraga_menit` (angka menit/hari, default: 0)
* `iklim_lingkungan` (Tropis / Panas, Sedang, Ber-AC / Dingin)
* `kondisi_khusus` (Normal, Hamil +300ml, Menyusui +700ml)

### 4.4 Rumus & Metode Perhitungan
1. **Kebutuhan Dasar (Metode Kemenkes RI & Holiday-Segar baseline):**
   $$\text{Kebutuhan Dasar} = \text{Berat Badan (kg)} \times 35 \text{ ml}$$
2. **Kompensasi Olahraga (American College of Sports Medicine - ACSM):**
   $$\text{Tambahan Olahraga} = \left(\frac{\text{Durasi Olahraga}}{30}\right) \times 350 \text{ ml}$$
3. **Faktor Lingkungan:**
   * Cuaca Panas / Lembap: $+ 350$ ml
   * Suhu Normal / Ruangan: $+ 0$ ml
4. **Total Asupan Harian:**
   $$\text{Total Cairan (ml)} = \text{Kebutuhan Dasar} + \text{Tambahan Olahraga} + \text{Faktor Iklim} + \text{Kondisi Khusus}$$
   $$\text{Jumlah Gelas} = \text{round}\left(\frac{\text{Total Cairan}}{250}\right) \quad (\text{Asumsi 1 gelas standar} = 250 \text{ ml})$$

### 4.5 Output
* Total mililiter air per hari (ml).
* Ekuivalen jumlah gelas 250 ml.
* Jadwal pembagian waktu minum (bangun tidur, sebelum makan, saat berolahraga, sebelum tidur).
* Panduan warna urine (skala hidrasi visual Armstrong).

### 4.6 Validasi
* Berat badan: 15 - 250 kg.
* Durasi olahraga: 0 - 360 menit.

### 4.7 Referensi Medis
* Kemenkes RI. *Pedoman Gizi Seimbang: Anjuran Kebutuhan Air Minum Harian.* Permenkes No. 41 Tahun 2014.
* Sawka MN, et al. *American College of Sports Medicine position stand. Exercise and fluid replacement.* Med Sci Sports Exerc. 2007;39(2):377-390.
* **[Perlu Validasi Dewan Medis]**: Penyesuaian khusus restriksi cairan pada pasien penyakit ginjal kronis (CKD) dan gagal jantung kongestif (CHF). Form wajib menyertakan peringatan kontraindikasi bagi penderita gagal ginjal.

### 4.8 Kebutuhan Database & Relasi
Tabel `cek_kebutuhan_cairan`. Menjadi target harian pada fitur *Habit Tracker* modul asupan air.

---

## 5. Pemeriksaan Tekanan Darah (Blood Pressure Assessment)

### 5.1 Tujuan Fitur
Mengevaluasi hasil pengukuran tensimeter mandiri pengguna, mengklasifikasikan kategori tekanan darah menurut panduan klinis internasional, dan memberikan edukasi dini mengenai hipertensi dan hipotensi.

### 5.2 Target Pengguna
Individu dewasa dengan riwayat hipertensi keluarga, pengguna dengan tensimeter digital di rumah, dan masyarakat umum yang baru melakukan pemeriksaan di faskes.

### 5.3 Input yang Diperlukan
* `sistolik` (Angka mmHg, rentang valid 70 - 250, wajib)
* `diastolik` (Angka mmHg, rentang valid 40 - 150, wajib)
* `pulse` (BPM, opsional)
* `posisi_pengukuran` (Duduk, Berbaring)

### 5.4 Rumus & Klasifikasi Klinis
Mengadopsi **Panduan Konsensus AHA/ACC (American Heart Association / American College of Cardiology 2017) & Konsensus Perhiper 2021**:

| Kategori Klinis | Sistolik (mmHg) | Logika | Diastolik (mmHg) | Rekomendasi Tindakan |
| :--- | :---: | :---: | :---: | :--- |
| **Hipotensi** | $< 90$ | ATAU | $< 60$ | Evaluasi gejala pusing, dehidrasi, atau anemia. |
| **Normal** | $< 120$ | DAN | $< 80$ | Pertahankan gaya hidup sehat dan diet rendah natrium. |
| **Pra-Hipertensi / Elevated** | 120 – 129 | DAN | $< 80$ | Perbaiki pola makan (DASH diet), tingkatkan aktivitas. |
| **Hipertensi Derajat 1** | 130 – 139 | ATAU | 80 – 89 | Modifikasi gaya hidup, jadwalkan konsultasi dokter. |
| **Hipertensi Derajat 2** | $\ge 140$ | ATAU | $\ge 90$ | Perlu evaluasi medis formal untuk terapi farmakologis. |
| **Krisis Hipertensi** | $> 180$ | DAN/ATAU | $> 120$ | **Darurat Medis:** Segera ke Instalasi Gawat Darurat (IGD)! |

*Jika Sistolik dan Diastolik berada pada dua kategori berbeda, kategori yang lebih tinggi digunakan sebagai hasil.*

### 5.5 Output
* Kategori tekanan darah dengan kode warna waspada.
* Nilai Tekanan Nadi (*Pulse Pressure* = Sistolik - Diastolik). Normal: 40–60 mmHg.
* Peringatan Red Flag kegawatdaruratan (gejala nyeri dada, sesak napas, baal separuh badan).
* Logika edukasi faktor risiko (garam, rokok, stres, kurang tidur).

### 5.6 Validasi
* Sistolik harus selalu lebih besar dari Diastolik ($\text{Sistolik} - \text{Diastolik} \ge 15$).
* Sistolik: 70 - 260 mmHg, Diastolik: 40 - 160 mmHg.

### 5.7 Referensi Medis
* Whelton PK, Carey RM, et al. *2017 ACC/AHA/AAPA/ABC/ACPM/AGS/APhA/ASH/ASPC/NMA/PCNA Guideline for the Prevention, Detection, Evaluation, and Management of High Blood Pressure in Adults.* J Am Coll Cardiol. 2018;71(19):e127-e248.
* Perhimpunan Dokter Hipertensi Indonesia (PERHI). *Konsensus Penatalaksanaan Hipertensi 2021.*

### 5.8 Kebutuhan Database
Tabel `cek_tekanan_darah`. Terhubung ke riwayat personal dan PDF report.

---

## 6. Kalkulator & Skrining Kualitas Tidur (Adapted Sleep Quality Index)

### 6.1 Tujuan Fitur
Menilai higiene dan efisiensi tidur, mendeteksi kecenderungan insomnia atau gangguan pola sirkadian melalui kuesioner ringkas terstandar.

### 6.2 Target Pengguna
Individu yang mengalami keluhan lelah berkepanjangan, insomnia, sering terbangun di malam hari, atau pekerja *shift*.

### 6.3 Input yang Diperlukan
* Durasi tidur aktual (jam per malam).
* Waktu yang dibutuhkan untuk mulai terlelap / latensi tidur (< 15 menit, 16-30 menit, 31-60 menit, > 60 menit).
* Frekuensi terbangun di malam hari (0 = tidak pernah, 1 = 1x seminggu, 2 = 2-3x seminggu, 3 = $\ge 4$x seminggu).
* Kualitas tidur subjektif menurut persepsi diri (Sangat Nyenyak, Cukup Nyenyak, Kurang Nyenyak, Sangat Buruk).
* Penggunaan obat tidur / suplemen melatonin (Ya / Tidak).

### 6.4 Metode Skoring
Adaptasi instrumen **Pittsburgh Sleep Quality Index (PSQI) versi ringkas**:
* Menghitung efisiensi tidur:
  $$\text{Efisiensi Tidur (\%)} = \left(\frac{\text{Jam Tidur Aktual}}{\text{Waktu Berada di Tempat Tidur}}\right) \times 100$$
  Efisiensi optimal $\ge 85\%$.
* Skoring terbobot total 0 - 15:
  * 0 – 4: Kualitas Tidur Sangat Baik (*Good Sleeper*).
  * 5 – 8: Kualitas Tidur Cukup (*Mild Sleep Disturbance*).
  * 9 – 12: Kualitas Tidur Buruk (*Significant Disturbance*).
  * 13 – 15: Gangguan Tidur Kronis / Indikasi Insomnia Berat.

### 6.5 Output
* Skor indeks kualitas tidur.
* Persentase efisiensi tidur.
* Saran higiene tidur (*Sleep Hygiene Checklist*): jadwal tidur konsisten, batas paparan *blue light* sebelum tidur, suhu kamar optimal.

### 6.6 Validasi
* Durasi tidur: 0 - 24 jam. Efisiensi tidak boleh melebihi 100%.

### 6.7 Referensi Medis
* Buysse DJ, Reynolds CF, et al. *The Pittsburgh Sleep Quality Index: a new instrument for psychiatric practice and research.* Psychiatry Res. 1989;28(2):193-213.
* **[Perlu Validasi Dewan Medis]**: Adaptasi komponen ringkas 5 butir dari 19 butir kuesioner asli PSQI harus dinyatakan sebagai *screening indicator* awal, bukan pengganti polisomnografi laboratorium tidur.

### 6.8 Kebutuhan Database
Tabel `cek_kualitas_tidur`.

---

## 7. Kalkulator Target Detak Jantung (Target Heart Rate Dedicated)

### 7.1 Tujuan Fitur
Menyediakan kalkulator latihan khusus bagi pegiat olahraga untuk mengoptimalkan pembakaran lemak, membangun kapasitas aerobik, atau meningkatkan VO2 max dengan presisi detak nadi per menit berdasarkan metode Karvonen dan Zoladz.

### 7.2 Target Pengguna
Atlet, pelari, pesepeda, dan anggota gym yang berolahraga dengan monitor denyut jantung (smartwatch / chest strap).

### 7.3 Input yang Diperlukan
* `umur` (10 - 100 tahun)
* `resting_hr` (Denyut nadi istirahat bangun pagi, 40 - 120 BPM)
* `metode_formula` (Formula Tanaka $208 - 0.7 \times \text{umur}$ atau Formula Gulati khusus wanita $206 - 0.88 \times \text{umur}$)

### 7.4 Rumus & 5 Zona Pelatihan (Zoladz / Karvonen)
$$\text{HRR (Heart Rate Reserve)} = \text{HR}_{\max} - \text{RHR}$$
$$\text{Target BPM} = \text{RHR} + (\% \times \text{HRR})$$

* **Zona 1 (Sangat Ringan, 50 - 60%):** Pemulihan aktif, relaksasi kardio.
* **Zona 2 (Ringan / Fat Burn, 60 - 70%):** Pembakaran lemak maksimal, daya tahan dasar.
* **Zona 3 (Sedang / Aerobik, 70 - 80%):** Peningkatan kapasitas vaskular dan efisiensi paru.
* **Zona 4 (Keras / Ambang Laktat, 80 - 90%):** Peningkatan batas toleransi asam laktat & kecepatan.
* **Zona 5 (Maksimal / Neuromuskular, 90 - 100%):** Daya ledak interval anaerobik singkat (HIIT).

### 7.5 Output
* Tabel 5 zona target denyut jantung (BPM minimum dan maksimum).
* Visualisasi grafik target kardio.
* Rekomendasi durasi latihan per zona per minggu.

### 7.6 Referensi Medis
* Tanaka H, Monahan KD, Seals DR. *Age-predicted maximal heart rate revisited.* J Am Coll Cardiol. 2001;37(1):153-156.
* Gulati M, et al. *Heart rate response to exercise stress testing in asymptomatic women.* Circulation. 2010;122(2):130-137.

### 7.7 Kebutuhan Database
Tabel `cek_target_heart_rate`.

---

## 8. Kalkulator Aktivitas Fisik (IPAQ Short Form)

### 8.1 Tujuan Fitur
Mengukur total pengeluaran energi aktivitas fisik mingguan dalam satuan MET-menit/minggu (*Metabolic Equivalent of Task*) berdasarkan standar kuesioner global WHO/IPAQ untuk mengidentifikasi apakah individu tergolong aktif atau berisiko *sedentary lifestyle*.

### 8.2 Target Pengguna
Masyarakat umum yang ingin mengetahui apakah kuantitas aktivitas fisiknya telah memenuhi rekomendasi WHO ($\ge 150$ menit aerobik sedang per minggu).

### 8.3 Input yang Diperlukan
* Hari dan rata-rata menit aktivitas berat (*vigorous*: lari, angkat beban berat, aerobik intens) dalam 7 hari terakhir.
* Hari dan rata-rata menit aktivitas sedang (*moderate*: bersepeda santai, jalan cepat, pekerjaan rumah tangga sedang).
* Hari dan rata-rata menit berjalan kaki ($\ge 10$ menit berkelanjutan).
* Rata-rata jam duduk per hari pada hari kerja.

### 8.4 Rumus & Metode Skoring
Menggunakan bobot koefisien **IPAQ (International Physical Activity Questionnaire)**:
$$\text{MET Berat} = 8.0 \times \text{hari} \times \text{menit/hari}$$
$$\text{MET Sedang} = 4.0 \times \text{hari} \times \text{menit/hari}$$
$$\text{MET Jalan} = 3.3 \times \text{hari} \times \text{menit/hari}$$
$$\text{Total MET-menit/minggu} = \text{MET Berat} + \text{MET Sedang} + \text{MET Jalan}$$

**Klasifikasi Tingkat Aktivitas Fisik:**
1. **Rendah (Sedentary):** Total $< 600$ MET-menit/minggu.
2. **Sedang (Moderately Active):** Total 600 – 2.999 MET-menit/minggu (memenuhi standar dasar WHO).
3. **Tinggi (Highly Active):** Total $\ge 3.000$ MET-menit/minggu.

### 8.5 Output
* Skor total MET-menit/minggu.
* Status pencapaian target fisik mingguan WHO.
* Estimasi kalori tambahan yang terbakar per minggu.
* Peringatan dampak duduk terlalu lama ($> 8$ jam/hari).

### 8.6 Referensi Medis
* Craig CL, et al. *International physical activity questionnaire: 12-country reliability and validity.* Med Sci Sports Exerc. 2003;35(8):1381-1395.
* World Health Organization. *WHO Guidelines on physical activity and sedentary behaviour.* Geneva: WHO; 2020.

### 8.7 Kebutuhan Database
Tabel `cek_aktivitas_fisik`.

---

## 9. Skrining Tingkat Stres (Perceived Stress Scale / PSS-10)

### 9.1 Tujuan Fitur
Menilai persepsi individu terhadap tingkat ketidakpastian, beban mental, dan daya kendali atas tuntutan hidup dalam satu bulan terakhir menggunakan instrumen psikometrik terpopuler di dunia (PSS-10).

### 9.2 Target Pengguna
Individu yang merasakan ketegangan emosional, burnout kerja/kuliah, atau ingin melengkapi skrining PHQ-4 yang sudah ada.

### 9.3 Input yang Diperlukan
10 butir pertanyaan skala Likert 5-titik (0 = Tidak Pernah, 1 = Hampir Tidak Pernah, 2 = Kadang-kadang, 3 = Cukup Sering, 4 = Sangat Sering).
* Pertanyaan positif (butir 4, 5, 7, 8) menggunakan pembalikan skor (*reverse scoring*: $0 \to 4, 1 \to 3, 2 \to 2, 3 \to 1, 4 \to 0$).
* Pertanyaan negatif (butir 1, 2, 3, 6, 9, 10) menggunakan skor langsung.

### 9.4 Metode Skoring
$$\text{Skor PSS} = \sum (\text{Skor 6 Pertanyaan Negatif}) + \sum (\text{Skor Pembalikan 4 Pertanyaan Positif})$$
Rentang skor: 0 sampai 40.

**Klasifikasi Tingkat Stres:**
* 0 – 13 : Stres Rendah (*Low Stress*).
* 14 – 26 : Stres Sedang (*Moderate Stress*) — Paling umum dijumpai.
* 27 – 40 : Stres Tinggi (*High Perceived Stress*) — Memerlukan intervensi manajemen stres atau pendampingan profesional.

### 9.5 Output
* Skor PSS total per 40.
* Kategori tingkat stres.
* Identifikasi faktor protektif diri (*sense of control* vs *perceived helplessness*).
* Panduan teknik regulasi emosi: *grounding exercise* 5-4-3-2-1, pernapasan diafragma, dan kontak layanan psikologis.

### 9.6 Referensi Medis
* Cohen S, Kamarck T, Mermelstein R. *A global measure of perceived stress.* J Health Soc Behav. 1983;24(4):385-396.
* **[Perlu Validasi Dewan Medis]**: Terjemahan resmi instrumen bahasa Indonesia divalidasi silang dengan publikasi psikometri lokal terkini.

### 9.7 Kebutuhan Database
Tabel `cek_stres`.

---

## 10. Health Habit Tracker

### 10.1 Tujuan Fitur
Menumbuhkan kebiasaan hidup sehat yang berkesinambungan melalui pencatatan target harian (*daily habit streak*) pada 5 pilar utama: Asupan Air, Durasi Tidur, Langkah Kaki/Olahraga, Konsumsi Sayur-Buah, dan Kepatuhan Suplemen/Obat.

### 10.2 Target Pengguna
Pengguna terdaftar (*authenticated user*) yang menginginkan pendamping konsistensi harian.

### 10.3 Input yang Diperlukan
* Pilihan kebiasaan yang ingin dilacak.
* Input capaian harian: Jumlah gelas air (checkbox/counter), jam tidur (angka desimal), menit olahraga (angka), checklist sayur/buah.
* Tombol cepat *Check-in* satu klik pada dashboard.

### 10.4 Logika & Gamifikasi
* Perhitungan persentase kepatuhan harian: $\frac{\text{Capaian}}{\text{Target}} \times 100\%$.
* *Streak Counter:* Akumulasi hari berturut-turut yang mencapai target $\ge 80\%$.
* Kalender riwayat *heatmap* (visual titik hijau seperti GitHub contribution grid).

### 10.5 Output
* Visual progress bar harian.
* Angka streak aktif.
* Lencana pencapaian (Badge: "Konsistensi 7 Hari", "Hidrasi Master", dll.).

### 10.6 Kebutuhan Database
Tabel `habit_trackers` dan `habit_logs`.

---

## 11. Personal Health Dashboard

### 11.1 Tujuan Fitur
Menjadi pusat kendali kesehatan pribadi pengguna (*health cockpit*), menyajikan ringkasan metrik biometrik terkini, grafik perkembangan dari waktu ke waktu, skor kebugaran holistik, dan notifikasi jadwal pemeriksaan ulang.

### 11.2 Target Pengguna
Pengguna terdaftar yang memiliki akun di HealthCheck.

### 11.3 Komponen Dashboard
1. **Header Pengguna:** Foto/Inisial, nama, usia, jenis kelamin, dan tanggal bergabung.
2. **Indeks Skor Kesehatan Holistik (0 - 100):** Komposit terbobot dari BMI, Tekanan Darah, Tingkat Aktivitas, Kualitas Tidur, dan Skrining Stres.
3. **Kartu Status Biometrik Terkini:**
   * Berat Badan & BMI terakhir (indikator tren naik/turun).
   * Tekanan Darah terakhir.
   * Rata-rata Hidrasi & Tidur 7 hari terakhir.
   * Status Mental terakhir (PHQ-4 / PSS).
4. **Grafik Tren Visual (Chart.js / CSS SVG):**
   * Grafik perubahan berat badan & BMI (30–90 hari terakhir).
   * Grafik fluktuasi denyut nadi istirahat & tensi darah.
5. **Aksi Cepat (Quick Action):** Tombol pintas "Ukur BMI Hari Ini", "Catat Tensi", "Check-in Kebiasaan".
6. **Rekomendasi Edukasi Terpersonalisasi:** Menampilkan artikel kesehatan yang relevan dengan kondisi pengguna (misal: jika tensi tergolong *elevated*, menampilkan artikel diet rendah garam).

---

## 12. Health Report PDF

### 12.1 Tujuan Fitur
Menghasilkan dokumen laporan kesehatan formal berformat PDF yang merangkum hasil seluruh evaluasi mandiri pengguna selama periode tertentu, dirancang secara profesional untuk dibawa saat berkonsultasi dengan dokter umum atau spesialis.

### 12.2 Target Pengguna
Pengguna yang hendak melakukan *check-up* medis ke klinik/rumah sakit, atau ingin mengarsipkan rekam kesehatan mandirinya.

### 12.3 Isi Laporan PDF
* Kop Dokumen Resmi: Logo HealthCheck, Nomor Laporan Unik, Tanggal Pembuatan.
* Identitas Pengguna: Nama Lengkap, Umur, Jenis Kelamin.
* Ringkasan Parameter Biometrik Kunci (BMI, BMR, Estimasi Lemak, WHtR, Tekanan Darah, RHR).
* Riwayat Skrining Emosional & Stres (PHQ-4 & PSS-10).
* Kepatuhan Pola Hidup (Rata-rata tidur, aktivitas fisik, hidrasi).
* Lembar Persiapan Konsultasi Dokter (*Doctor Consultation Checklist*): Pertanyaan penting yang disarankan diajukan kepada dokter.
* **Disclaimer Medis Hukum Resmi:** Menegaskan bahwa dokumen merupakan hasil tes mandiri instrumen edukasi dan bukan hasil laboratorium medis.

### 12.4 Teknologi Pembuatan
* Pilihan engine PDF PHP ringan tanpa library node eksternal: FPDF atau native print stylesheet / DOMPDF / TCPDF.

---

## 13. Health Education Center

### 13.1 Tujuan Fitur
Menyediakan repositori artikel kesehatan berbasis bukti ilmiah (*evidence-based articles*) yang dikurasi oleh tim medis untuk meningkatkan literasi kesehatan masyarakat.

### 13.2 Target Pengguna
Pengguna umum dan terdaftar yang mencari rujukan informasi kesehatan tepercaya bebas dari hoaks dan mitos.

### 13.3 Kategori Topik
1. Gizi & Manajemen Berat Badan.
2. Jantung & Kesehatan Vaskular.
3. Kebugaran & Olahraga Terukur.
4. Kesehatan Mental & Manajemen Stres.
5. Kualitas Tidur & Irama Sirkadian.

### 13.4 Fitur Modul
* Pencarian artikel berbasis kata kunci dan tagar.
* Estimasi waktu baca (*reading time* menit).
* Tautan rujukan ilmiah resmi (Jurnal PubMed / WHO / Kemenkes) di bagian akhir artikel.
* Fitur *Terkait Alat:* Di setiap artikel tertanam widget kalkulator terkait (misal artikel hipertensi memiliki kartu pintas kalkulator tensi).

---

## 14. Riwayat Pemeriksaan (Comprehensive Assessment History)

### 14.1 Tujuan Fitur
Memberikan riwayat kronologis lengkap seluruh pemeriksaan yang pernah dilakukan oleh pengguna, dengan opsi filter berdasarkan jenis alat dan rentang tanggal, serta opsi ekspor data.

### 14.2 Kemampuan Fungsional
* **Universal Timeline:** Menampilkan urutan log aktivitas kesehatan dari seluruh tabel menggunakan View `v_all_health_assessments`.
* **Filter Multifaset:** Filter per alat (Semua, BMI, BMR, Jantung, Mental, Tensi, dll.), filter waktu (7 hari, 30 hari, 1 tahun, Semua).
* **Pencarian Riwayat:** Berdasarkan kata kunci kesimpulan atau tanggal.
* **Detail Modal / Halaman Rinci:** Membuka kembali detail laporan pengukuran masa lalu.
* **Fasilitas Hapus Mandiri (Hak Privasi):** Pengguna berhak menghapus rekaman riwayat tertentu miliknya sendiri demi privasi data.

---

## 15. Matriks Ringkasan Hubungan Antarfitur

```
+-----------------------------------------------------------------------------------+
|                           HEALTHCHECK PLATFORM ECOSYSTEM                          |
+-----------------------------------------------------------------------------------+
                                          |
                      +-------------------+-------------------+
                      |                                       |
                      v                                       v
          [Public / Guest Access]                 [Registered User Access]
                      |                                       |
          +-----------+-----------+                           v
          | Free Public Calculators|               [Personal Health Dashboard]
          | - BMI, BMR, Jantung   |                           |
          | - Mental PHQ-4, TDEE  |        +------------------+------------------+
          | - WHtR, Body Fat, BP  |        |                  |                  |
          | - Air, Tidur, Stres   |        v                  v                  v
          +-----------+-----------+  [Habit Tracker]  [Assessment History] [PDF Report]
                      |                    ^                  ^
                      +---- Login / -------+                  |
                            Save Result ----------------------+
```
