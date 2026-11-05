<?php
$pageTitle = 'Hasil Perhitungan BMI — HealthCheck';
$activePage = 'bmi';
require_once __DIR__ . '/../includes/header.php';

// Ambil data dari session atau fallback ke query param (backward compatibility)
$res = $_SESSION['bmi_result'] ?? null;
if (!$res) {
    if (isset($_GET['bmi'])) {
        $bmi = floatval($_GET['bmi']);
        $kondisi = htmlspecialchars($_GET['kondisi'] ?? 'Normal');
        $res = [
            'nama' => 'Pengguna',
            'umur' => '-',
            'berat' => '-',
            'tinggi' => '-',
            'bmi' => $bmi,
            'kondisi' => $kondisi,
            'warna' => ($bmi >= 18.5 && $bmi <= 24.9) ? 'success' : (($bmi < 18.5) ? 'warning' : 'danger'),
            'bb_min' => '-',
            'bb_max' => '-'
        ];
    } else {
        header("Location: bmi_form.php");
        exit;
    }
}

$bmiVal = $res['bmi'];
$kondisi = $res['kondisi'];
$warna = $res['warna'];

// Presentase posisi pointer pada bar skala BMI (10 sampai 40)
$clampedBmi = max(12, min(38, $bmiVal));
$pointerPercent = round((($clampedBmi - 12) / (38 - 12)) * 100);
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="bmi_form.php" class="text-decoration-none">Kalkulator BMI</a></li>
            <li class="breadcrumb-item active" aria-current="page">Hasil BMI</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card overflow-hidden mb-4">
                <div class="card-editorial-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0" style="color: var(--primary);">Hasil Evaluasi Indeks Massa Tubuh</h5>
                    <span class="badge border px-3 py-1 small" style="background-color: var(--light-bg); color: var(--primary); border-color: var(--card-border) !important;">
                        <?= htmlspecialchars($res['nama']) ?>
                    </span>
                </div>

                <div class="p-4 p-md-5">
                    <!-- Skor Utama -->
                    <div class="metric-hero-box">
                        <span class="text-uppercase fw-semibold small" style="color: var(--muted); letter-spacing: 0.05em;">Nilai BMI Terhitung</span>
                        <div class="metric-number my-1 text-<?= $warna ?>">
                            <?= $bmiVal ?>
                        </div>
                        <div class="h6 fw-semibold mb-0">
                            Klasifikasi: <span class="badge bg-<?= $warna ?>-subtle px-3 py-1"><?= $kondisi ?></span>
                        </div>
                    </div>

                    <!-- Visual BMI Scale Meter -->
                    <div class="mb-5 px-md-3">
                        <div class="d-flex justify-content-between small mb-2 fw-semibold" style="color: var(--muted); font-size: 0.8rem;">
                            <span>Kurus (&lt;18.5)</span>
                            <span>Ideal (18.5 - 22.9)</span>
                            <span>Kelebihan (23 - 24.9)</span>
                            <span>Obesitas (&ge;25)</span>
                        </div>
                        <div class="position-relative py-1">
                            <div class="progress" style="height: 10px; border-radius: var(--radius-sm); background-color: var(--card-border);">
                                <div class="progress-bar" style="width: 25%; background-color: #EAB308;" title="Kurus"></div>
                                <div class="progress-bar" style="width: 25%; background-color: #10B981;" title="Ideal"></div>
                                <div class="progress-bar" style="width: 15%; background-color: #F59E0B;" title="Kelebihan"></div>
                                <div class="progress-bar" style="width: 35%; background-color: #EF4444;" title="Obesitas"></div>
                            </div>
                            <!-- Needle Gauge Indicator -->
                            <div style="position: absolute; top: 0; left: <?= $pointerPercent ?>%; transform: translateX(-50%);">
                                <div style="width: 3px; height: 18px; background-color: var(--primary); border-radius: 2px; box-shadow: 0 1px 3px rgba(0,0,0,0.3);"></div>
                            </div>
                        </div>
                        <div class="text-center mt-2 small" style="color: var(--muted); font-size: 0.8rem;">
                            Posisi skor Anda pada skala klasifikasi IMT populasi Asia.
                        </div>
                    </div>

                    <!-- Detail Ringkasan Data -->
                    <div class="row g-3 p-3 rounded-3 mb-4 text-center" style="background-color: var(--light-bg); border: 1px solid var(--card-border);">
                        <div class="col-4">
                            <div class="small" style="color: var(--muted);">Tinggi Badan</div>
                            <div class="fw-bold fs-6" style="color: var(--dark);"><?= $res['tinggi'] ?> cm</div>
                        </div>
                        <div class="col-4 border-start border-end" style="border-color: var(--card-border) !important;">
                            <div class="small" style="color: var(--muted);">Berat Badan</div>
                            <div class="fw-bold fs-6" style="color: var(--dark);"><?= $res['berat'] ?> kg</div>
                        </div>
                        <div class="col-4">
                            <div class="small" style="color: var(--muted);">Rentang Berat Ideal</div>
                            <div class="fw-bold fs-6" style="color: var(--success);">
                                <?= ($res['bb_min'] !== '-') ? "{$res['bb_min']} - {$res['bb_max']} kg" : 'Normal' ?>
                            </div>
                        </div>
                    </div>

                    <!-- Saran & Interpretasi -->
                    <div class="p-4 mb-4 rounded-3" style="background-color: var(--surface); border: 1px solid var(--card-border);">
                        <h6 class="fw-bold mb-2" style="color: var(--primary);">Interpretasi & Panduan</h6>
                        <?php if (str_contains($kondisi, 'Kurus')): ?>
                            <p class="small mb-2" style="color: var(--muted); line-height: 1.6;">
                                Berat badan Anda berada di bawah rentang acuan ideal. Dianjurkan untuk meningkatkan asupan nutrisi padat gizi (protein hewani/nabati, lemak sehat, karbohidrat kompleks) dan melatih kekuatan otot.
                            </p>
                        <?php elseif (str_contains($kondisi, 'Normal')): ?>
                            <p class="small mb-2" style="color: var(--muted); line-height: 1.6;">
                                Proporsi berat badan Anda dalam rentang normal yang optimal. Pertahankan asupan makanan seimbang, hidrasi cukup, istirahat teratur, dan aktivitas aerobik minimal 150 menit per minggu.
                            </p>
                        <?php else: ?>
                            <p class="small mb-2" style="color: var(--muted); line-height: 1.6;">
                                Berat badan Anda berada di atas ambang rekomendasi ideal. Pertimbangkan pengaturan porsi makan seimbang dengan defisit kalori terarah, kurangi asupan gula sederhana, dan tingkatkan mobilitas harian.
                            </p>
                        <?php endif; ?>
                        <div class="small fst-italic" style="color: var(--muted);">
                            Catatan: Formula BMI tidak membedakan berat dari massa otot atau cadangan lemak. Konsultasikan dengan tenaga kesehatan untuk evaluasi komposisi tubuh yang menyeluruh.
                        </div>
                    </div>

                    <!-- Disclaimer Box -->
                    <div class="disclaimer-box m-0">
                        <strong class="d-block" style="color: var(--primary);">Batas Penggunaan Medis</strong>
                        <span class="small" style="color: var(--muted); line-height: 1.5; display: block;">
                            Hasil kalkulasi BMI ini bersifat informasi umum dan bukan diagnosis klinis resmi. Hindari diet ekstrem atau perubahan pola makan drastis tanpa pengawasan profesional.
                        </span>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="d-flex flex-wrap gap-2 mt-4 pt-2">
                        <a href="bmi_form.php" class="btn btn-primary-health px-4">
                            Hitung Ulang
                        </a>
                        <a href="../bmr/bmr_form.php" class="btn btn-outline-secondary px-4">
                            Lanjut Cek Kalori & BMR
                        </a>
                        <a href="../index.php" class="btn btn-link ms-auto text-decoration-none" style="color: var(--muted);">Kembali ke Beranda</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Related Tools -->
        <div class="col-lg-4">
            <div class="card p-4 mb-4">
                <h6 class="fw-bold mb-3" style="color: var(--primary);">Alat Terkait Lainnya</h6>
                <div class="d-flex flex-column gap-3">
                    <a href="../bmr/bmr_form.php" class="d-flex align-items-center gap-3 text-decoration-none p-2 rounded-2 hover-bg">
                        <div class="tool-icon icon-blue m-0" style="width:38px; height:38px; font-size:1.1rem;"><i class="bi bi-fire"></i></div>
                        <div>
                            <div class="fw-bold small" style="color: var(--dark);">Kalkulator BMR & Kalori</div>
                            <div class="small" style="font-size:0.75rem; color: var(--muted);">Hitung kalori harian untuk diet sehat</div>
                        </div>
                    </a>
                    <a href="../jantung/jantung_form.php" class="d-flex align-items-center gap-3 text-decoration-none p-2 rounded-2 hover-bg">
                        <div class="tool-icon icon-rose m-0" style="width:38px; height:38px; font-size:1.1rem;"><i class="bi bi-heart-pulse"></i></div>
                        <div>
                            <div class="fw-bold small" style="color: var(--dark);">Target Detak Jantung</div>
                            <div class="small" style="font-size:0.75rem; color: var(--muted);">Cek denyut nadi dan zona latihan kardio</div>
                        </div>
                    </a>
                    <a href="../mental/mental_form.php" class="d-flex align-items-center gap-3 text-decoration-none p-2 rounded-2 hover-bg">
                        <div class="tool-icon icon-purple m-0" style="width:38px; height:38px; font-size:1.1rem;"><i class="bi bi-chat-heart"></i></div>
                        <div>
                            <div class="fw-bold small" style="color: var(--dark);">Skrining Mental (PHQ-4)</div>
                            <div class="small" style="font-size:0.75rem; color: var(--muted);">Cek tingkat stres dan suasana hati</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
