<?php
$pageTitle = 'Hasil Perhitungan BMI — HealthCheck';
$activePage = 'bmi';
require_once __DIR__ . '/includes/header.php';

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
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="bmi_form.php" class="text-decoration-none">Kalkulator BMI</a></li>
            <li class="breadcrumb-item active" aria-current="page">Hasil BMI</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="p-4 bg-teal-subtle border-bottom d-flex justify-content-between align-items-center" style="background-color: #f0fdfa;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check2-circle text-teal fs-4"></i>
                        <h5 class="fw-bold mb-0 text-dark">Hasil Evaluasi Indeks Massa Tubuh</h5>
                    </div>
                    <span class="badge bg-white text-secondary border px-3 py-1 rounded-pill small">
                        <?= htmlspecialchars($res['nama']) ?>
                    </span>
                </div>

                <div class="p-4 p-md-5">
                    <!-- Skor Utama -->
                    <div class="text-center mb-4">
                        <span class="text-muted small text-uppercase fw-semibold letter-spacing-1">Nilai BMI Anda</span>
                        <div class="display-3 fw-bold my-1 text-<?= $warna ?>">
                            <?= $bmiVal ?>
                        </div>
                        <div class="h5 fw-semibold mb-3">
                            Kategori: <span class="badge bg-<?= $warna ?>-subtle text-<?= $warna ?>-emphasis px-3 py-2 rounded-pill fs-6 border border-<?= $warna ?>-subtle"><?= $kondisi ?></span>
                        </div>
                    </div>

                    <!-- Visual BMI Scale Meter -->
                    <div class="mb-5 px-md-3">
                        <div class="d-flex justify-content-between text-muted small mb-1 fw-semibold">
                            <span>Kurus (&lt;18.5)</span>
                            <span>Ideal (18.5 - 22.9)</span>
                            <span>Kelebihan (23 - 24.9)</span>
                            <span>Obesitas (&ge;25)</span>
                        </div>
                        <div class="position-relative">
                            <div class="progress" style="height: 14px; border-radius: 8px;">
                                <div class="progress-bar bg-warning" style="width: 25%" title="Kurus"></div>
                                <div class="progress-bar bg-success" style="width: 25%" title="Normal"></div>
                                <div class="progress-bar bg-info" style="width: 15%" title="Kelebihan"></div>
                                <div class="progress-bar bg-danger" style="width: 35%" title="Obesitas"></div>
                            </div>
                            <!-- Marker Indicator -->
                            <div style="position: absolute; top: -6px; left: calc(<?= $pointerPercent ?>% - 8px);">
                                <i class="bi bi-caret-down-fill text-dark fs-5"></i>
                            </div>
                        </div>
                        <div class="text-center mt-2 text-muted small">
                            Indikator panah menunjukkan perkiraan posisi skor Anda pada spektrum berat badan.
                        </div>
                    </div>

                    <!-- Detail Ringkasan Data -->
                    <div class="row g-3 p-3 bg-light rounded-4 mb-4 text-center">
                        <div class="col-4">
                            <div class="text-muted small">Tinggi Badan</div>
                            <div class="fw-bold fs-6"><?= $res['tinggi'] ?> cm</div>
                        </div>
                        <div class="col-4 border-start border-end">
                            <div class="text-muted small">Berat Badan</div>
                            <div class="fw-bold fs-6"><?= $res['berat'] ?> kg</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted small">Kisaran Berat Ideal</div>
                            <div class="fw-bold fs-6 text-success">
                                <?= ($res['bb_min'] !== '-') ? "{$res['bb_min']} - {$res['bb_max']} kg" : 'Normal' ?>
                            </div>
                        </div>
                    </div>

                    <!-- Saran & Interpretasi -->
                    <div class="card bg-white border rounded-3 p-4 mb-4">
                        <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-lightbulb-fill text-warning me-2"></i>Interpretasi & Saran Langkah:</h6>
                        <?php if (str_contains($kondisi, 'Kurus')): ?>
                            <p class="text-secondary small mb-2">
                                Berat badan Anda berada di bawah rentang acuan ideal. Dianjurkan untuk meningkatkan asupan kalori padat nutrisi (protein, lemak sehat, karbohidrat kompleks) serta latihan beban untuk menambah massa otot sehat.
                            </p>
                        <?php elseif (str_contains($kondisi, 'Normal')): ?>
                            <p class="text-secondary small mb-2">
                                Selamat! Proporsi berat badan Anda tergolong ideal. Pertahankan pola makan seimbang, kecukupan hidrasi, istirahat cukup, dan aktivitas fisik teratur minimal 150 menit per minggu.
                            </p>
                        <?php else: ?>
                            <p class="text-secondary small mb-2">
                                Berat badan Anda melebihi rentang ideal yang dianjurkan. Mengatur porsi makan harian dengan defisit kalori moderat, membatasi gula tambahan/makanan olahan, dan rutin bergerak dapat membantu menurunkan risiko metabolik.
                            </p>
                        <?php endif; ?>
                        <div class="text-muted small fst-italic">
                            Perhatikan: Angka BMI tidak membedakan berat dari otot atau lemak. Diskusikan dengan dokter atau ahli gizi untuk analisis komposisi tubuh lebih rinci.
                        </div>
                    </div>

                    <!-- Disclaimer Box -->
                    <div class="disclaimer-box m-0">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-exclamation-triangle-fill fs-5 mt-1 text-warning"></i>
                            <div>
                                <strong class="d-block text-dark">Disclaimer Medis:</strong>
                                Hasil BMI ini adalah estimasi awal dan bukan diagnosis penyakit kardiovaskular atau metabolik. Jangan melakukan diet ekstrem tanpa konsultasi dokter.
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="d-flex flex-wrap gap-2 mt-4 pt-2">
                        <a href="bmi_form.php" class="btn btn-primary-health px-4">
                            <i class="bi bi-arrow-repeat me-1"></i> Hitung Ulang
                        </a>
                        <a href="bmr_form.php" class="btn btn-outline-secondary px-4">
                            Lanjut Cek Kalori & BMR <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                        <a href="index.php" class="btn btn-link text-muted ms-auto">Kembali ke Beranda</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Related Tools -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <h6 class="fw-bold text-dark mb-3">Alat Kesehatan Terkait</h6>
                <div class="d-flex flex-column gap-3">
                    <a href="bmr_form.php" class="d-flex align-items-center gap-3 text-decoration-none p-2 rounded-3 hover-bg">
                        <div class="tool-icon icon-blue m-0" style="width:38px; height:38px; font-size:1.1rem;"><i class="bi bi-fire"></i></div>
                        <div>
                            <div class="text-dark fw-bold small">Kalkulator BMR & Kalori</div>
                            <div class="text-muted small" style="font-size:0.75rem;">Hitung kalori harian untuk diet sehat</div>
                        </div>
                    </a>
                    <a href="jantung_form.php" class="d-flex align-items-center gap-3 text-decoration-none p-2 rounded-3 hover-bg">
                        <div class="tool-icon icon-rose m-0" style="width:38px; height:38px; font-size:1.1rem;"><i class="bi bi-heart-pulse"></i></div>
                        <div>
                            <div class="text-dark fw-bold small">Target Detak Jantung</div>
                            <div class="text-muted small" style="font-size:0.75rem;">Cek denyut nadi dan zona latihan kardio</div>
                        </div>
                    </a>
                    <a href="mental_form.php" class="d-flex align-items-center gap-3 text-decoration-none p-2 rounded-3 hover-bg">
                        <div class="tool-icon icon-purple m-0" style="width:38px; height:38px; font-size:1.1rem;"><i class="bi bi-emoji-smile"></i></div>
                        <div>
                            <div class="text-dark fw-bold small">Screening Mental Mandiri</div>
                            <div class="text-muted small" style="font-size:0.75rem;">Cek tingkat stres dan kecemasan</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
