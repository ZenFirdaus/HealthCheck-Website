<?php
$pageTitle = 'Skrining Kesehatan Mental (PHQ-4) — HealthCheck';
$activePage = 'mental';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_helper.php';

$profile = get_current_user_profile($pdo);
$defaultName = $profile['nama_lengkap'] ?? ($_SESSION['username'] ?? '');
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="../index.php" class="text-decoration-none">Kalkulator</a></li>
            <li class="breadcrumb-item active" aria-current="page">Skrining Mental Mandiri</li>
        </ol>
    </nav>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-8">
            <div class="card overflow-hidden">
                <div class="card-editorial-header">
                    <h4 class="card-heading mb-1">Skrining Singkat Kesehatan Mental (PHQ-4)</h4>
                    <p class="small mb-0" style="color: var(--muted);">Penapisan awal gejala kecemasan dan suasana hati selama 2 minggu terakhir.</p>
                </div>

                <div class="p-4 p-md-5">
                    <?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'invalid_input'): ?>
                        <div class="alert alert-danger small mb-4">
                            <i class="bi bi-exclamation-circle-fill me-2"></i> Mohon lengkapi nama dan seluruh pertanyaan skrining dengan pilihan yang valid.
                        </div>
                    <?php endif; ?>

                    <p class="small mb-4" style="color: var(--muted);">
                        Pilihlah salah satu opsi jawaban yang paling menggambarkan perasaan Anda <strong>selama 2 minggu terakhir</strong>. Tidak ada jawaban benar atau salah.
                    </p>

                    <form action="mental_proses.php" method="POST">
                        <?= csrf_field() ?>
                        <div class="mb-4">
                            <label class="form-label" for="nama_user">Nama atau Inisial <span class="text-danger">*</span></label>
                            <input type="text" name="nama_user" id="nama_user" class="form-control" placeholder="Contoh: Budi" value="<?= htmlspecialchars($defaultName) ?>" required>
                        </div>

                        <!-- Bagian A: Kecemasan (GAD-2) -->
                        <div class="mb-4 p-3 rounded-2 border" style="background-color: var(--light-bg); border-color: var(--card-border) !important;">
                            <div class="fw-bold mb-3" style="color: var(--primary);">Bagian 1: Indikator Kecemasan (GAD-2)</div>

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
                        <div class="mb-4 p-3 rounded-2 border" style="background-color: var(--light-bg); border-color: var(--card-border) !important;">
                            <div class="fw-bold mb-3" style="color: var(--primary);">Bagian 2: Suasana Hati & Minat (PHQ-2)</div>

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
                                Lihat Hasil Skrining
                            </button>
                            <a href="../index.php" class="btn btn-outline-secondary px-4 py-2">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar Info -->
        <div class="col-lg-4">
            <div class="card p-4 mb-4">
                <h6 class="fw-bold mb-3" style="color: var(--primary);">Instrumen Validasi PHQ-4</h6>
                <p class="small mb-2" style="color: var(--muted); line-height: 1.55;">
                    PHQ-4 (Patient Health Questionnaire-4) adalah instrumen skrining ultra-singkat yang divalidasi oleh Dr. Kurt Kroenke dkk. (2009) untuk menapis gejala kecemasan dan suasana hati pada layanan primer.
                </p>
                <p class="small mb-0" style="color: var(--muted); line-height: 1.55;">
                    Hasil ini bersifat penapisan awal reflektif, bukan vonis kejiwaan klinis.
                </p>
            </div>

            <div class="card p-4">
                <h6 class="fw-bold mb-2" style="color: var(--primary);">Bantuan & Dukungan Krisis</h6>
                <p class="small mb-2" style="color: var(--muted); line-height: 1.55;">
                    Jika Anda merasa sangat tertekan atau membutuhkan teman bicara tepercaya, jangan ragu untuk mencari bantuan profesional.
                </p>
                <p class="small mb-0" style="color: var(--muted); line-height: 1.55;">
                    Hotline Jiwa Kemenkes RI: <strong style="color: var(--primary);">119 (ext. 8)</strong> atau kunjungi fasilitas kesehatan terdekat.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
