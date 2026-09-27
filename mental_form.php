<?php
$pageTitle = 'Screening Kesehatan Mental Mandiri (PHQ-4) — HealthCheck';
$activePage = 'mental';
require_once __DIR__ . '/includes/header.php';

$defaultName = $_SESSION['username'] ?? '';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">Kalkulator</a></li>
            <li class="breadcrumb-item active" aria-current="page">Screening Mental Mandiri</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="p-4 bg-purple-subtle border-bottom" style="background-color: #f5f3ff;">
                    <div class="d-flex align-items-center gap-3">
                        <div class="tool-icon icon-purple m-0" style="width:44px; height:44px;">
                            <i class="bi bi-emoji-smile"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-0 text-dark">Screening Singkat Kesehatan Mental (PHQ-4)</h4>
                            <p class="text-secondary small mb-0">Penapisan awal rasa cemas dan suasana hati selama 2 minggu terakhir.</p>
                        </div>
                    </div>
                </div>

                <div class="p-4 p-md-5">
                    <p class="text-secondary small mb-4">
                        Pilihlah salah satu opsi jawaban yang paling menggambarkan perasaan atau keadaan Anda <strong>selama 2 minggu terakhir</strong>. Tidak ada jawaban benar atau salah.
                    </p>

                    <form action="mental_proses.php" method="POST">
                        <div class="mb-4">
                            <label class="form-label" for="nama_user">Nama / Inisial Anda <span class="text-danger">*</span></label>
                            <input type="text" name="nama_user" id="nama_user" class="form-control" placeholder="Contoh: Budi" value="<?= htmlspecialchars($defaultName) ?>" required>
                        </div>

                        <!-- Bagian A: Kecemasan (GAD-2) -->
                        <div class="mb-4 p-3 bg-light rounded-3">
                            <div class="fw-bold text-dark mb-3"><i class="bi bi-patch-question text-primary me-2"></i>Bagian 1: Indikator Kecemasan</div>

                            <!-- Soal 1 -->
                            <div class="mb-3">
                                <label class="form-label text-dark fw-semibold small">1. Merasa gugup, cemas, atau gelisah?</label>
                                <div class="d-flex flex-column gap-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q1" id="q1_0" value="0" checked>
                                        <label class="form-check-label small" for="q1_0">Tidak pernah sama sekali (0)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q1" id="q1_1" value="1">
                                        <label class="form-check-label small" for="q1_1">Beberapa hari (1)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q1" id="q1_2" value="2">
                                        <label class="form-check-label small" for="q1_2">Lebih dari separuh hari (2)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q1" id="q1_3" value="3">
                                        <label class="form-check-label small" for="q1_3">Hampir setiap hari (3)</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Soal 2 -->
                            <div class="mb-2">
                                <label class="form-label text-dark fw-semibold small">2. Tidak mampu menghentikan atau mengontrol rasa khawatir?</label>
                                <div class="d-flex flex-column gap-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q2" id="q2_0" value="0" checked>
                                        <label class="form-check-label small" for="q2_0">Tidak pernah sama sekali (0)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q2" id="q2_1" value="1">
                                        <label class="form-check-label small" for="q2_1">Beberapa hari (1)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q2" id="q2_2" value="2">
                                        <label class="form-check-label small" for="q2_2">Lebih dari separuh hari (2)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q2" id="q2_3" value="3">
                                        <label class="form-check-label small" for="q2_3">Hampir setiap hari (3)</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bagian B: Suasana Hati / Mood (PHQ-2) -->
                        <div class="mb-4 p-3 bg-light rounded-3">
                            <div class="fw-bold text-dark mb-3"><i class="bi bi-patch-question text-primary me-2"></i>Bagian 2: Suasana Hati & Minat</div>

                            <!-- Soal 3 -->
                            <div class="mb-3">
                                <label class="form-label text-dark fw-semibold small">3. Kurang berminat atau kurang bersemangat dalam melakukan berbagai hal?</label>
                                <div class="d-flex flex-column gap-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q3" id="q3_0" value="0" checked>
                                        <label class="form-check-label small" for="q3_0">Tidak pernah sama sekali (0)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q3" id="q3_1" value="1">
                                        <label class="form-check-label small" for="q3_1">Beberapa hari (1)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q3" id="q3_2" value="2">
                                        <label class="form-check-label small" for="q3_2">Lebih dari separuh hari (2)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q3" id="q3_3" value="3">
                                        <label class="form-check-label small" for="q3_3">Hampir setiap hari (3)</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Soal 4 -->
                            <div class="mb-2">
                                <label class="form-label text-dark fw-semibold small">4. Merasa murung, sedih, muram, atau putus asa?</label>
                                <div class="d-flex flex-column gap-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q4" id="q4_0" value="0" checked>
                                        <label class="form-check-label small" for="q4_0">Tidak pernah sama sekali (0)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q4" id="q4_1" value="1">
                                        <label class="form-check-label small" for="q4_1">Beberapa hari (1)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q4" id="q4_2" value="2">
                                        <label class="form-check-label small" for="q4_2">Lebih dari separuh hari (2)</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="q4" id="q4_3" value="3">
                                        <label class="form-check-label small" for="q4_3">Hampir setiap hari (3)</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" class="btn btn-primary-health px-4 py-2">
                                <i class="bi bi-clipboard2-check me-1"></i> Lihat Hasil Evaluasi
                            </button>
                            <a href="index.php" class="btn btn-outline-secondary px-4 py-2">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Tentang Instrumen PHQ-4</h6>
                <p class="text-secondary small mb-2">
                    PHQ-4 (Patient Health Questionnaire-4) adalah alat penapisan ultra-singkat yang divalidasi oleh Dr. Kurt Kroenke dkk. (2009) untuk menilai gejala kecemasan dan depresi dalam pelayanan kesehatan primer.
                </p>
                <p class="text-secondary small mb-0">
                    Instrumen ini bukan vonis klinis, melainkan gambaran umum yang membantu Anda mengenali kebutuhan untuk beristirahat atau berkonsultasi.
                </p>
            </div>

            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-telephone-heart text-danger me-2"></i>Bantuan & Dukungan Krisis</h6>
                <p class="text-secondary small mb-2">
                    Jika Anda merasa sangat kewalahan, tertekan, atau memiliki dorongan untuk menyakiti diri sendiri, Anda tidak sendirian.
                </p>
                <p class="text-secondary small mb-0">
                    Hubungi Hotline Jiwa Kemenkes RI: <strong class="text-dark">119 (ext. 8)</strong> atau layanan konseling profesional terdekat.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
