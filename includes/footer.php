</main>

<footer class="mt-auto">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-lg-5">
                <div class="d-flex align-items-center mb-3">
                    <span class="brand-badge" style="width:32px; height:32px; font-size: 0.9rem;"><i class="bi bi-activity"></i></span>
                    <span class="brand-text fs-5">Health<span>Check</span></span>
                </div>
                <p class="small mb-3" style="color: var(--muted); line-height: 1.6;">
                    Platform kesehatan mandiri modern berbasis data biometrik terstandar. Didesain untuk memberikan evaluasi awal kondisi fisik, kebutuhan nutrisi, dan kesejahteraan emosional secara objektif dan mudah dipahami.
                </p>
                <div class="disclaimer-box m-0">
                    <strong class="d-block" style="color: var(--primary);">Catatan Medis Penting</strong>
                    <span class="small" style="color: var(--muted); display: block; line-height: 1.5;">
                        Seluruh kalkulasi bersifat edukatif dan estimatif berdasarkan parameter yang Anda isi. Layanan ini bukan pengganti diagnosis medis formal, resep dokter, maupun konsultasi spesialis kesehatan.
                    </span>
                </div>
            </div>

            <div class="col-6 col-lg-3 offset-lg-1">
                <h6>Kalkulator Kesehatan</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2 mt-3">
                    <li><a href="<?= $relPath ?>bmi/bmi_form.php">Indeks Massa Tubuh (BMI)</a></li>
                    <li><a href="<?= $relPath ?>bmr/bmr_form.php">Kalori & Makronutrisi (BMR/TDEE)</a></li>
                    <li><a href="<?= $relPath ?>whtr/whtr_form.php">Rasio Pinggang-Tinggi (WHtR)</a></li>
                    <li><a href="<?= $relPath ?>body_fat/body_fat_form.php">Lemak Tubuh (US Navy)</a></li>
                    <li><a href="<?= $relPath ?>cairan/cairan_form.php">Kebutuhan Cairan Harian</a></li>
                    <li><a href="<?= $relPath ?>jantung/jantung_form.php">Detak Jantung & Kardio</a></li>
                    <li><a href="<?= $relPath ?>mental/mental_form.php">Skrining Mental (PHQ-4)</a></li>
                </ul>
            </div>

            <div class="col-6 col-lg-3">
                <h6>Navigasi Akun</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2 mt-3">
                    <?php if (isset($_SESSION['username'])): ?>
                        <li><span class="fw-semibold" style="color: var(--success);"><i class="bi bi-person me-1"></i><?= htmlspecialchars($_SESSION['username']) ?></span></li>
                        <?php if (isset($_SESSION['job']) && $_SESSION['job'] === 'admin'): ?>
                            <li><a href="<?= $relPath ?>admin/admin.php">Admin Dashboard</a></li>
                        <?php endif; ?>
                        <li><a href="<?= $relPath ?>auth/logout.php" style="color: var(--danger);">Keluar</a></li>
                    <?php else: ?>
                        <li><a href="<?= $relPath ?>auth/login.php">Masuk Akun</a></li>
                        <li><a href="<?= $relPath ?>auth/register.php">Daftar Akun Baru</a></li>
                        <li><a href="<?= $relPath ?>auth/login_admin.php">Portal Administrator</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <hr style="border-color: var(--card-border); margin: 2rem 0 1.5rem;">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small" style="color: var(--muted);">
            <p class="mb-0">&copy; <?= date('Y') ?> HealthCheck. Seluruh hak cipta dilindungi.</p>
            <p class="mb-0">Informasi kesehatan edukatif berbasis parameter standar.</p>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
